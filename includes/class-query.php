<?php

if (!defined('ABSPATH')) exit;

class ACF_Filter_Query {

    public static function build($current_lang = '', $posts_per_page = 10) {

        $config         = get_option('acf_filter_config', []);
        $filters_config = json_decode($config['filters'] ?? '[]', true);

        $lang_arg = $current_lang !== '' ? $current_lang : '';

        $args = [
            'post_type'      => 'post',
            'posts_per_page' => (int) $posts_per_page,
            'lang'           => $lang_arg,
        ];

        $tax_query = ['relation' => 'AND'];
        $has_tax   = false;

        foreach ($filters_config as $filter) {
            $type  = $filter['type'] ?? '';
            $logic = strtoupper($filter['logic'] ?? 'OR');

            if ($type !== 'taxonomy') continue;

            $taxonomy = sanitize_key($filter['taxonomy'] ?? '');
            if (empty($taxonomy)) continue;

            $selected_slugs = isset($_POST[$taxonomy]) ? (array) $_POST[$taxonomy] : [];
            $selected_slugs = array_map('sanitize_text_field', $selected_slugs);
            $selected_slugs = array_filter($selected_slugs);

            if (empty($selected_slugs)) continue;

            $has_tax  = true;

            if ($logic === 'AND') {
                foreach ($selected_slugs as $slug) {
                    $term_ids = self::resolve_term_ids([$slug], $taxonomy);
                    if (empty($term_ids)) continue;

                    $tax_query[] = [
                        'taxonomy' => $taxonomy,
                        'field'    => 'term_id',
                        'terms'    => $term_ids,
                        'operator' => 'IN',
                    ];
                }
            } else {
                $term_ids = self::resolve_term_ids($selected_slugs, $taxonomy);
                if (empty($term_ids)) continue;

                $tax_query[] = [
                    'taxonomy' => $taxonomy,
                    'field'    => 'term_id',
                    'terms'    => $term_ids,
                    'operator' => 'IN',
                ];
            }
        }

        if ($has_tax) {
            $args['tax_query'] = $tax_query;
        }

        return $args;
    }

    private static function resolve_term_ids(array $slugs, string $taxonomy): array {

        $ids = [];

        foreach ($slugs as $slug) {
            $term = get_term_by('slug', $slug, $taxonomy);
            if (!$term) continue;

            $ids[] = $term->term_id;

            if (function_exists('pll_get_term_translations')) {
                $translations = pll_get_term_translations($term->term_id);
                foreach ($translations as $translated_id) {
                    if ($translated_id && $translated_id !== $term->term_id) {
                        $ids[] = $translated_id;
                    }
                }
            }
        }

        return array_unique($ids);
    }
}