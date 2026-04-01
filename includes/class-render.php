<?php

if (!defined('ABSPATH')) exit;

class ACF_Filter_Render {

    public static function form($atts = []) {

        $atts = shortcode_atts([
            'style'           => 'sidebar',
            'style__md'       => '',
            'style__sm'       => '',
            'offcanvas_label' => 'Filter',
            'close_label'     => '✕',
            'apply_label'     => 'Vis resultater',
            'reset_label'     => 'Nullstill',
        ], $atts);

        $config         = get_option('acf_filter_config', []);
        $filters_config = json_decode($config['filters'] ?? '[]', true);

        if (empty($filters_config)) return '';

        $current_lang = function_exists('pll_current_language') ? pll_current_language() : '';

        // Samle unike stiler som skal rendres
        $styles = array_unique([
            $atts['style'],
            $atts['style__md'],
            $atts['style__sm'],
        ]);

        // Generer CSS-klasser for synlighet per breakpoint
        $visibility = self::get_visibility_classes($atts);

        ob_start();

        foreach ($styles as $style):

            $vis_class = $visibility[$style] ?? '';

            if ($style === 'offcanvas'): ?>
                <div class="acf-filter-offcanvas-wrap <?= esc_attr($vis_class) ?>">
                    <button class="acf-filter-trigger" aria-expanded="false" aria-controls="acf-offcanvas-panel">
                        <span class="acf-filter-trigger-icon">
                            <span></span><span></span><span></span>
                        </span>
                        <?= esc_html($atts['offcanvas_label']) ?>
                        <span class="acf-filter-count" aria-live="polite"></span>
                    </button>

                    <div class="acf-filter-overlay"></div>

                    <div class="acf-offcanvas-panel" id="acf-offcanvas-panel" aria-hidden="true">
                        <div class="acf-offcanvas-header">
                            <span><?= esc_html($atts['offcanvas_label']) ?></span>
                            <button class="acf-offcanvas-close" aria-label="Lukk filter">
                                <?= esc_html($atts['close_label']) ?>
                            </button>
                        </div>

                        <form id="acf-filter-form-offcanvas"
                            class="acf-filter-form acf-filter--offcanvas"
                            data-filter-form>
                            <div class="acf-offcanvas-body">
                                <?= self::render_filter_fields($filters_config, $current_lang) ?>
                            </div>

                            <div class="acf-offcanvas-footer">
                                <button type="reset" class="acf-filter-reset">
                                    <?= esc_html($atts['reset_label']) ?>
                                </button>
                                <button type="button" class="acf-offcanvas-apply button">
                                    <?= esc_html($atts['apply_label']) ?>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

            <?php elseif ($style === 'topbar'): ?>
                <div class="acf-filter-topbar-wrap <?= esc_attr($vis_class) ?>">
                    <form id="acf-filter-form-topbar"
                        class="acf-filter-form acf-filter--topbar"
                        data-filter-form>
                        <?= self::render_filter_fields($filters_config, $current_lang) ?>
                    </form>
                </div>

            <?php else: // sidebar ?>
                <div class="acf-filter-sidebar-wrap <?= esc_attr($vis_class) ?>">
                    <form id="acf-filter-form-sidebar"
                        class="acf-filter-form acf-filter--sidebar"
                        data-filter-form>
                        <?= self::render_filter_fields($filters_config, $current_lang) ?>
                    </form>
                </div>

            <?php endif;

        endforeach;

        return ob_get_clean();
    }

    /**
     * Generer synlighetsklasser for hver stil basert på breakpoint-atts.
     */
    private static function get_visibility_classes(array $atts): array {

        $desktop = $atts['style'];
        $md      = $atts['style__md'];
        $sm      = $atts['style__sm'];

        $visibility = [];

        // Alle unike stiler
        $all = array_unique([$desktop, $md, $sm]);

        foreach ($all as $style) {
            $classes = [];

            // Desktop (>1024px): vis hvis style === desktop
            if ($style !== $desktop) $classes[] = 'acf-hide--lg';

            // Tablet (768–1024px): vis hvis style__md === style
            if ($style !== $md) $classes[] = 'acf-hide--md';

            // Mobil (<768px): vis hvis style__sm === style
            if ($style !== $sm) $classes[] = 'acf-hide--sm';

            $visibility[$style] = implode(' ', $classes);
        }

        return $visibility;
    }

    private static function render_filter_fields($filters_config, $current_lang) {

        ob_start();

        foreach ($filters_config as $filter):
            $type       = $filter['type'] ?? '';
            $input_type = $filter['input_type'] ?? 'checkbox';
            $labels     = $filter['labels'] ?? [];
            $label      = $labels[$current_lang] ?? reset($labels) ?: '';

            if ($type !== 'taxonomy') continue;

            $taxonomy = $filter['taxonomy'] ?? '';
            if (!taxonomy_exists($taxonomy)) continue;

            $terms = get_terms([
                'taxonomy'   => $taxonomy,
                'hide_empty' => true,
            ]);

            if (is_wp_error($terms) || empty($terms)) continue;

            if ($current_lang && function_exists('pll_get_term_language')) {
                $terms = array_filter($terms, function ($term) use ($current_lang) {
                    return pll_get_term_language($term->term_id) === $current_lang;
                });
            }

            if (empty($terms)) continue;
        ?>
            <fieldset class="acf-filter-group" data-taxonomy="<?= esc_attr($taxonomy) ?>">
                <?php if ($label): ?>
                    <legend class="acf-filter-group__legend">
                        <?= esc_html($label) ?>
                    </legend>
                <?php endif; ?>

                <div class="acf-filter-group__options">
                    <?php foreach ($terms as $term): ?>
                        <label class="acf-filter-option">
                            <input class="acf-filter-option__input"
                                    type="<?= esc_attr($input_type) ?>"
                                    name="<?= esc_attr($taxonomy) ?><?= $input_type === 'radio' ? '' : '[]' ?>"
                                    value="<?= esc_attr($term->slug) ?>">
                            <span class="acf-filter-option__box"></span>
                            <span class="acf-filter-option__label">
                                <?= esc_html($term->name) ?>
                            </span>
                        </label>
                    <?php endforeach; ?>
                </div>
            </fieldset>
        <?php endforeach;

        return ob_get_clean();
    }

    public static function results($atts = []) {

        $atts = shortcode_atts([
            'posts_per_page'  => 10,
            'columns'         => 3,
            'columns__md'     => 2,
            'columns__sm'     => 1,
            'style'           => 'simple',
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
        ], $atts);

        $current_lang = function_exists('pll_current_language') ? pll_current_language() : '';
        $paged        = get_query_var('paged') ? get_query_var('paged') : 1;

        $acf_taxonomies = array_filter(
            array_map('trim', explode(',', $atts['acf_taxonomies']))
        );

        $query = new WP_Query([
            'post_type'      => 'post',
            'posts_per_page' => (int) $atts['posts_per_page'],
            'paged'          => $paged,
            'lang'           => $current_lang,
        ]);

        ob_start(); ?>

        <div id="acf-results" class="acf-results-<?= esc_attr($atts['style']) ?>" data-atts="<?= esc_attr(json_encode($atts)) ?>" data-lang="<?= esc_attr($current_lang) ?>">
            <?php if ($query->have_posts()): ?>

                <div class="acf-posts-grid sm-columns-<?= esc_attr($atts['columns__sm']) ?> md-columns-<?= esc_attr($atts['columns__md']) ?> columns-<?= esc_attr($atts['columns']) ?>">
                    <?php while ($query->have_posts()): $query->the_post(); ?>
                        <?= self::render_post($atts, $acf_taxonomies, $atts['read_more_text']) ?>
                    <?php endwhile; ?>
                    <?php wp_reset_postdata(); ?>
                </div>

                <?php if ($atts['show_pagination'] === 'yes'): ?>
                    <div class="acf-pagination">
                        <?= self::render_pagination($query->max_num_pages, $paged, $atts) ?>
                    </div>
                <?php endif; ?>

            <?php else: ?>
                <p class="acf-no-results">
                    <?= $current_lang === 'en' ? 'No posts found.' : 'Ingen innlegg funnet.' ?>
                </p>
            <?php endif; ?>
        </div>

        <?php
        return ob_get_clean();
    }

    public static function render_post($atts, $acf_taxonomies, $read_more_text) {

        ob_start(); ?>

        <article class="acf-post-item">

            <?php if ($atts['show_image'] === 'yes' && has_post_thumbnail()): ?>
                <a href="<?= esc_url(get_permalink()) ?>" class="acf-post-image">
                    <?= get_the_post_thumbnail(null, $atts['image_size']) ?>
                </a>
            <?php endif; ?>
            <?php if ($atts['show_image'] === 'yes' && !has_post_thumbnail()): ?>
                <a href="<?= esc_url(get_permalink()) ?>" class="acf-post-image">
                    <div class="acf-post-image-placeholder">
                        <svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-image-icon lucide-image"><rect width="18" height="18" x="3" y="3" rx="2" ry="2"/><circle cx="9" cy="9" r="2"/><path d="m21 15-3.086-3.086a2 2 0 0 0-2.828 0L6 21"/></svg>
                    </div>
                </a>
            <?php endif; ?>

            <div class="acf-post-content">

                <?php
                $has_meta = $atts['show_date'] === 'yes'
                    || $atts['show_author'] === 'yes'
                    || !empty($acf_taxonomies);

                if ($has_meta): ?>
                    <div class="acf-post-meta">

                        <div class="acf-post-meta-top">
                            <?php if ($atts['show_author'] === 'yes'): ?>
                                <span class="acf-post-author">
                                    <?= esc_html(get_the_author()) ?>
                                </span>
                            <?php endif; ?>

                            <?php if ($atts['show_date'] === 'yes'): ?>
                                <span class="acf-post-date">
                                    <?= esc_html(get_the_date()) ?>
                                </span>
                            <?php endif; ?>
                        </div>

                        <div class="acf-post-meta-bottom">
                            <?php foreach ($acf_taxonomies as $taxonomy):
                                if (!taxonomy_exists($taxonomy)) continue;
                                $terms = get_the_terms(get_the_ID(), $taxonomy);
                                if (empty($terms) || is_wp_error($terms)) continue;
                            ?>
                                <span class="acf-post-tax acf-post-tax--<?= esc_attr($taxonomy) ?>">
                                    <?php foreach ($terms as $term): ?>
                                        <span class="acf-post-tax-term">
                                            <?= esc_html($term->name) ?>
                                        </span>
                                    <?php endforeach; ?>
                                </span>
                            <?php endforeach; ?>
                        </div>

                    </div>
                <?php endif; ?>

                <?php if ($atts['show_title'] === 'yes'): ?>
                    <h3 class="acf-post-title">
                        <a href="<?= esc_url(get_permalink()) ?>">
                            <?= esc_html(get_the_title()) ?>
                        </a>
                    </h3>
                <?php endif; ?>

                <?php if ($atts['show_excerpt'] === 'yes'): ?>
                    <div class="acf-post-excerpt">
                        <?= esc_html(wp_trim_words(get_the_excerpt(), (int) $atts['excerpt_length'])) ?>
                    </div>
                <?php endif; ?>

                <?php if ($atts['show_read_more'] === 'yes'): ?>
                    <?php
                    $btn_classes = 'button';
                    if ($atts['read_more_style']) $btn_classes .= ' ' . $atts['read_more_style'];
                    if ($atts['read_more_size'])  $btn_classes .= ' ' . $atts['read_more_size'];
                    ?>
                    <a href="<?= esc_url(get_permalink()) ?>" class="<?= esc_attr($btn_classes) ?>">
                        <?= esc_html($read_more_text) ?>
                    </a>
                <?php endif; ?>

            </div>
        </article>

        <?php
        return ob_get_clean();
    }

    public static function render_pagination($total_pages, $current_page, $atts) {

        if ($total_pages <= 1) return '';

        ob_start(); ?>
        <div class="acf-pagination">
            <?php if ($current_page > 1): ?>
                <a href="#" class="page-numbers prev" data-page="<?= $current_page - 1 ?>">
                    <?= esc_html($atts['prev_text']) ?>
                </a>
            <?php endif; ?>

            <?php for ($i = 1; $i <= $total_pages; $i++):
                if (
                    $i === 1
                    || $i === $total_pages
                    || abs($i - $current_page) <= 2
                ): ?>
                    <a href="#"
                    class="page-numbers <?= $i === (int) $current_page ? 'current' : '' ?>"
                    data-page="<?= $i ?>">
                        <?= $i ?>
                    </a>
                <?php elseif (abs($i - $current_page) === 3): ?>
                    <span class="page-numbers dots">…</span>
                <?php endif;
            endfor; ?>

            <?php if ($current_page < $total_pages): ?>
                <a href="#" class="page-numbers next" data-page="<?= $current_page + 1 ?>">
                    <?= esc_html($atts['next_text']) ?>
                </a>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }
}