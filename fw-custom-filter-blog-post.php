<?php
/**
 * Plugin Name: Blogg filter (ACF + Polylang)
 * Description: AJAX filter for blog posts using ACF + Polylang
 * Author: Benjamin Pedersen | Bli Synlig AS
 * Version: 1.0.4
 */

if (!defined('ABSPATH')) exit;

// Sjekk at avhengigheter er aktive før vi laster noe
add_action('plugins_loaded', function () {

    // Polylang må være aktivt
    if (!function_exists('pll_languages_list')) {
        add_action('admin_notices', function () {
            echo '<div class="notice notice-error"><p>';
            echo '<strong>Blogg filter:</strong> Krever at Polylang er installert og aktivert.';
            echo '</p></div>';
        });
        return;
    }

    // ACF må være aktivt
    if (!function_exists('get_fields')) {
        add_action('admin_notices', function () {
            echo '<div class="notice notice-error"><p>';
            echo '<strong>Blogg filter:</strong> Krever at Advanced Custom Fields er installert og aktivert.';
            echo '</p></div>';
        });
        return;
    }

    add_action('init', function () {
        add_filter('pll_get_taxonomies', function ($taxonomies) {

            if (!function_exists('acf_get_taxonomies')) return $taxonomies;

            $skip = [
                'category', 'post_tag', 'post_format',
                'language', 'post_translations', 'term_language', 'term_translations',
            ];

            $acf_taxonomies = acf_get_taxonomies();
            foreach ($acf_taxonomies as $name) {
                if (!in_array($name, $skip) && taxonomy_exists($name)) {
                    $taxonomies[$name] = $name;
                }
            }

            return $taxonomies;
        });
    }, 20);

    add_filter('pll_get_taxonomies', function ($taxonomies) {

        $skip = [
            'category', 'post_tag', 'post_format',
            'language', 'post_translations', 'term_language', 'term_translations',
        ];

        if (!function_exists('acf_get_taxonomies')) return $taxonomies;

        $acf_taxonomies = acf_get_taxonomies();
        foreach ($acf_taxonomies as $name) {
            if (!in_array($name, $skip) && taxonomy_exists($name)) {
                $taxonomies[$name] = $name;
            }
        }

        return $taxonomies;
    }, 20);

    // Alt OK – last inn pluginen
    require_once plugin_dir_path(__FILE__) . 'includes/class-admin.php';
    require_once plugin_dir_path(__FILE__) . 'includes/class-ajax.php';
    require_once plugin_dir_path(__FILE__) . 'includes/class-query.php';
    require_once plugin_dir_path(__FILE__) . 'includes/class-render.php';
    require_once plugin_dir_path(__FILE__) . 'includes/class-sync.php';
    require_once plugin_dir_path(__FILE__) . 'includes/class-ux-element.php';

    add_shortcode('acf_filter', ['ACF_Filter_Render', 'form']);
    add_shortcode('acf_filter_results', ['ACF_Filter_Render', 'results']);
});

add_action('wp_enqueue_scripts', function () {
    // Ikke last scripts hvis Polylang ikke er aktivt
    if (!function_exists('pll_languages_list')) return;

    wp_enqueue_style(
        'acf-filter-filterlist-css',
        plugin_dir_url(__FILE__) . 'assets/filter.css',
        [],
        null
    );

    wp_enqueue_style(
        'acf-filter-css',
        plugin_dir_url(__FILE__) . 'assets/style.css',
        [],
        null
    );

    wp_enqueue_script(
        'acf-filter-js',
        plugin_dir_url(__FILE__) . 'assets/script.js',
        [],
        null,
        true
    );

    wp_localize_script('acf-filter-js', 'acfFilter', [
        'ajaxUrl' => admin_url('admin-ajax.php'),
        'nonce'   => wp_create_nonce('acf_filter_nonce'),
    ]);
});

