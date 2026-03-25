<?php

if (!defined('ABSPATH')) exit;

class ACF_Filter_Ajax {

    public static function init() {
        add_action('wp_ajax_acf_filter', [__CLASS__, 'handle']);
        add_action('wp_ajax_nopriv_acf_filter', [__CLASS__, 'handle']);
    }

    public static function handle() {

        if (!check_ajax_referer('acf_filter_nonce', 'nonce', false)) {
            wp_send_json_error('Ugyldig nonce', 403);
        }

        // Bruk språk sendt fra frontend
        $current_lang = isset($_POST['current_lang'])
            ? sanitize_text_field($_POST['current_lang'])
            : '';

        $atts = isset($_POST['atts']) ? (array) $_POST['atts'] : [];
        $atts = shortcode_atts([
            'posts_per_page'  => 10,
            'columns'         => 3,
            'columns__md'     => 2,
            'columns__sm'     => 1,
            'show_image'      => 'yes',
            'image_size'      => 'medium',
            'show_date'       => 'yes',
            'show_author'     => 'yes',
            'acf_taxonomies'  => '',
            'show_title'      => 'yes',
            'show_excerpt'    => 'yes',
            'excerpt_length'  => 20,
            'show_read_more'  => 'yes',
            'read_more_text'  => 'Les mer',
            'read_more_style' => 'outline',
            'read_more_size'  => '',
            'show_pagination' => 'yes',
            'prev_text'       => '« Forrige',
            'next_text'       => 'Neste »',
        ], array_map('sanitize_text_field', $atts));

        $acf_taxonomies = array_filter(
            array_map('trim', explode(',', $atts['acf_taxonomies']))
        );

        $args         = ACF_Filter_Query::build($current_lang);
        $query        = new WP_Query($args);
        $paged        = isset($_POST['paged']) ? (int) $_POST['paged'] : 1;

        if ($query->have_posts()) {

            echo '<div class="acf-posts-grid sm-columns-' . esc_attr($atts['columns__sm']) . ' md-columns-' . esc_attr($atts['columns__md']) . ' columns-' . esc_attr($atts['columns']) . '">';

            while ($query->have_posts()) {
                $query->the_post();
                echo ACF_Filter_Render::render_post($atts, $acf_taxonomies, $atts['read_more_text']);
            }

            echo '</div>';

            if ($atts['show_pagination'] === 'yes') {
                echo '<div class="acf-pagination">';
                echo paginate_links([
                    'total'     => $query->max_num_pages,
                    'current'   => $paged,
                    'prev_text' => $atts['prev_text'],
                    'next_text' => $atts['next_text'],
                ]);
                echo '</div>';
            }

            wp_reset_postdata();

        } else {
            echo '<p class="acf-no-results">' . ($current_lang === 'en' ? 'No posts found.' : 'Ingen innlegg funnet.') . '</p>';
        }

        wp_die();
    }
}

ACF_Filter_Ajax::init();