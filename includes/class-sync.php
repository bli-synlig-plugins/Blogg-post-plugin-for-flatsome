<?php

if (!defined('ABSPATH')) exit;

class ACF_Filter_Sync {

    const META_KEY = '_acf_sync_mirror'; // lagrer mirror_id på kilden

    public static function init() {
        add_action('save_post', [__CLASS__, 'on_save'], 99, 2);
        add_action('wp_after_insert_post', [__CLASS__, 'maybe_create_mirror'], 99, 2);
    }

    public static function maybe_create_mirror($post_id, $post) {

        static $running = false;
        if ($running) return;

        if ($post->post_status !== 'publish') return;
        if ($post->post_type !== 'post') return;
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
        if (defined('REST_REQUEST') && REST_REQUEST) return;
        if (!function_exists('pll_get_post_language')) return;

        $current_lang = pll_get_post_language($post_id);
        if (!$current_lang) return;

        $all_languages   = pll_languages_list();
        $other_languages = array_diff($all_languages, [$current_lang]);
        if (empty($other_languages)) return;

        // Hent eksisterende speil fra vår egen meta – mer pålitelig enn Polylang
        $existing_mirrors = get_post_meta($post_id, self::META_KEY, false);

        $translations                = pll_get_post_translations($post_id);
        $translations[$current_lang] = $post_id;

        $updated = false;
        $running = true;

        foreach ($other_languages as $lang) {

            // Sjekk vår egen meta først
            $mirror_id = null;
            foreach ($existing_mirrors as $mid) {
                if (get_post($mid) && pll_get_post_language($mid) === $lang) {
                    $mirror_id = $mid;
                    break;
                }
            }

            // Fallback: sjekk Polylang sine koblinger
            if (!$mirror_id) {
                $mirror_id = $translations[$lang] ?? null;
            }

            // Speilinnlegg finnes – hopp over
            if ($mirror_id && get_post($mirror_id)) {
                $translations[$lang] = $mirror_id;
                continue;
            }

            // Opprett speilinnlegg
            $new_id = self::create_mirror($post_id, $post, $lang);

            if ($new_id) {
                $translations[$lang] = $new_id;

                // Lagre kobling i begge retninger
                add_post_meta($post_id, self::META_KEY, $new_id);
                update_post_meta($new_id, self::META_KEY, $post_id);

                $updated = true;
            }
        }

        $running = false;

        pll_save_post_translations($translations);

    }

    public static function on_save($post_id, $post) {

        if ($post->post_status !== 'publish') return;
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
        if (wp_is_post_revision($post_id)) return;
        if (wp_is_post_autosave($post_id)) return;
        if ($post->post_type !== 'post') return;
        if (defined('REST_REQUEST') && REST_REQUEST) return;
        if (!function_exists('pll_get_post_language')) return;

        $current_lang = pll_get_post_language($post_id);
        if (!$current_lang) return;

        // Hent speil fra vår meta
        $mirror_ids = get_post_meta($post_id, self::META_KEY, false);
        if (empty($mirror_ids)) return;

        remove_action('save_post', [__CLASS__, 'on_save'], 99);

        foreach ($mirror_ids as $mirror_id) {

            if (!get_post($mirror_id)) continue;

            $lang = pll_get_post_language($mirror_id);
            if (!$lang) continue;

            self::update_mirror($post_id, $post, (int) $mirror_id, $lang);
        }

        // Oppdater Polylang-kobling så språkbytteren alltid peker riktig
        $translations = [$current_lang => $post_id];
        foreach ($mirror_ids as $mirror_id) {
            $lang = pll_get_post_language($mirror_id);
            if ($lang) {
                $translations[$lang] = (int) $mirror_id;
            }
        }
        pll_save_post_translations($translations);

        add_action('save_post', [__CLASS__, 'on_save'], 99, 2);
    }

    private static function create_mirror($source_id, $source_post, $lang) {

        $mirror_id = wp_insert_post([
            'post_title'   => $source_post->post_title,
            'post_content' => $source_post->post_content,
            'post_excerpt' => $source_post->post_excerpt,
            'post_status'  => 'publish',
            'post_type'    => 'post',
            'post_author'  => $source_post->post_author,
        ]);

        if (is_wp_error($mirror_id)) {
            error_log('ACF Sync – create feil: ' . $mirror_id->get_error_message());
            return null;
        }

        pll_set_post_language($mirror_id, $lang);
        self::sync_thumbnail($source_id, $mirror_id);
        self::sync_acf_fields($source_id, $mirror_id);
        self::sync_terms($source_id, $mirror_id, $lang);

        return $mirror_id;
    }

    private static function update_mirror($source_id, $source_post, $mirror_id, $lang) {

        wp_update_post([
            'ID'           => $mirror_id,
            'post_title'   => $source_post->post_title,
            'post_content' => $source_post->post_content,
            'post_excerpt' => $source_post->post_excerpt,
            'post_status'  => 'publish',
        ]);

        self::sync_thumbnail($source_id, $mirror_id);
        self::sync_acf_fields($source_id, $mirror_id);
        self::sync_terms($source_id, $mirror_id, $lang);
    }

    private static function sync_thumbnail($source_id, $mirror_id) {

        $thumbnail_id = get_post_thumbnail_id($source_id);

        if ($thumbnail_id) {
            set_post_thumbnail($mirror_id, $thumbnail_id);
        } else {
            delete_post_thumbnail($mirror_id);
        }
    }

    private static function sync_acf_fields($source_id, $mirror_id) {

        if (!function_exists('get_fields')) return;

        $fields = get_fields($source_id, false);
        if (empty($fields) || !is_array($fields)) return;

        foreach ($fields as $key => $value) {
            if ($value === null || $value === '') continue;
            update_field($key, $value, $mirror_id);
        }
    }

    private static function sync_terms($source_id, $mirror_id, $target_lang) {

        $skip       = ['language', 'post_translations'];
        $taxonomies = get_object_taxonomies('post');

        foreach ($taxonomies as $taxonomy) {

            if (in_array($taxonomy, $skip)) continue;

            $source_terms = wp_get_object_terms($source_id, $taxonomy, ['fields' => 'all']);

            if (is_wp_error($source_terms) || empty($source_terms)) {
                wp_set_object_terms($mirror_id, [], $taxonomy);
                continue;
            }

            $target_term_ids = [];

            foreach ($source_terms as $term) {
                $translated_id = self::get_translated_term($term->term_id, $target_lang);
                $use_id        = $translated_id ?? (int) $term->term_id;

                $verify = get_term((int) $use_id, $taxonomy);
                if ($verify && !is_wp_error($verify)) {
                    $target_term_ids[] = (int) $use_id;
                }
            }

            wp_set_object_terms($mirror_id, $target_term_ids, $taxonomy);
        }
    }

    private static function get_translated_term($term_id, $lang) {

        if (!function_exists('pll_get_term_translations')) return null;

        $translations = pll_get_term_translations($term_id);
        return isset($translations[$lang]) ? (int) $translations[$lang] : null;
    }
}

ACF_Filter_Sync::init();