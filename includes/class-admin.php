<?php

if (!defined('ABSPATH')) exit;

class ACF_Filter_Admin {

    public static function init() {
        add_action('admin_menu', [__CLASS__, 'menu']);
        add_action('admin_enqueue_scripts', [__CLASS__, 'assets']);
    }

    public static function menu() {
        add_menu_page(
            'ACF Filter Builder',
            'ACF Filter',
            'manage_options',
            'acf-filter',
            [__CLASS__, 'page'],
            'dashicons-filter'
        );
    }

    public static function assets($hook) {
        if ($hook !== 'toplevel_page_acf-filter') return;

        wp_enqueue_script(
            'acf-filter-admin',
            plugin_dir_url(__FILE__) . '../assets/admin.js',
            ['jquery'],
            time(), // Tvinger ny versjon hver gang – bytt til '1.1' etter testing
            true
        );

        wp_enqueue_style(
            'acf-filter-admin-css',
            plugin_dir_url(__FILE__) . '../assets/admin.css'
        );

        $taxonomies = get_taxonomies(['public' => true], 'objects');
        $tax_options = [];
        foreach ($taxonomies as $tax) {
            $tax_options[] = [
                'value' => $tax->name,
                'label' => $tax->label,
            ];
        }

        // Hent aktive språk fra Polylang
        $languages = [];
        if (function_exists('pll_languages_list')) {
            $langs = PLL()->model->get_languages_list();
            foreach ($langs as $lang) {
                $languages[] = [
                    'slug' => $lang->slug,
                    'name' => $lang->name,
                ];
            }
        }

        wp_localize_script('acf-filter-admin', 'acfFilterAdmin', [
            'taxonomies'  => $tax_options,
            'languages'   => $languages,
        ]);
    }

    public static function page() {

        if (isset($_POST['save'])) {
            update_option('acf_filter_config', [
                'filters' => stripslashes($_POST['filters'])
            ]);
            echo '<div class="acf-filter-wrap-updated updated"><p>Lagret!</p></div>';
        }

        $config = get_option('acf_filter_config', []);
        $json = $config['filters'] ?? '[]';

        ?>
        <div class="acf-filter-wrap">
            <h1>ACF Filter Builder</h1>

            <button type="button" class="acf-filter-button" id="add-filter">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-plus-icon lucide-plus"><path d="M5 12h14"/><path d="M12 5v14"/></svg>
                Legg til filter
            </button>

            <div id="acf-builder"></div>

            <form method="post">
                <textarea name="filters" id="acf-json" style="display:none;">
                    <?= esc_textarea($json) ?>
                </textarea>
                <button class="acf-filter-button acf-filter-button-primary" name="save">
                    Lagre filter
                </button>
            </form>
        </div>
        <?php
    }
}

ACF_Filter_Admin::init();