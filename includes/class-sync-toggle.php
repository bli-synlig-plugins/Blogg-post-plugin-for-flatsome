<?php

if (!defined('ABSPATH')) exit;

class ACF_Filter_Sync_Toggle {

    const META_KEY = '_acf_sync_disabled';

    public static function init() {
        add_action('add_meta_boxes', [__CLASS__, 'add_meta_box']);
        add_action('save_post',      [__CLASS__, 'save'],        10, 2);
    }

    public static function add_meta_box() {
        add_meta_box(
            'acf-sync-toggle',
            'Innlegg synkronisering',
            [__CLASS__, 'render'],
            'post',
            'side',
            'default'
        );
    }

    public static function render($post) {

        wp_nonce_field('acf_sync_toggle', 'acf_sync_toggle_nonce');

        $disabled = get_post_meta($post->ID, self::META_KEY, true);
        $lang     = function_exists('pll_get_post_language')
            ? pll_get_post_language($post->ID)
            : '';

        // Sjekk om dette er et speilinnlegg
        $is_mirror  = (bool) get_post_meta($post->ID, ACF_Filter_Sync::META_KEY, true);
        $mirror_ids = get_post_meta($post->ID, ACF_Filter_Sync::META_KEY, false);
        ?>

        <div style="padding: 4px 0;">

            <?php if ($is_mirror): ?>
                <p style="margin: 0 0 10px; font-size: 12px; color: #666;">
                    Dette er et automatisk synkronisert speilinnlegg.
                </p>
            <?php else: ?>
                <p style="margin: 0 0 10px; font-size: 12px; color: #666;">
                    Kildeinnlegg
                    <?= $lang ? '(' . esc_html(strtoupper($lang)) . ')' : '' ?>.
                    <?= !empty($mirror_ids) ? count($mirror_ids) . ' speilinnlegg.' : 'Ingen speilinnlegg enda.' ?>
                </p>
            <?php endif; ?>

            <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                <input type="checkbox"
                       name="acf_sync_disabled"
                       value="1"
                       <?= checked($disabled, '1', false) ?>>
                <span style="font-size: 13px;">
                    Deaktiver automatisk synkronisering
                </span>
            </label>

            <?php if ($disabled): ?>
                <p style="margin: 8px 0 0; font-size: 12px; color: #b32d2e;">
                    ⚠ Synkronisering er deaktivert for dette innlegget.
                </p>
            <?php endif; ?>

        </div>

        <?php
    }

    public static function save($post_id, $post) {

        if (!isset($_POST['acf_sync_toggle_nonce'])) return;
        if (!wp_verify_nonce($_POST['acf_sync_toggle_nonce'], 'acf_sync_toggle')) return;
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
        if (!current_user_can('edit_post', $post_id)) return;

        if (!empty($_POST['acf_sync_disabled'])) {
            update_post_meta($post_id, self::META_KEY, '1');
        } else {
            delete_post_meta($post_id, self::META_KEY);
        }
    }

    public static function is_disabled($post_id) {
        return get_post_meta($post_id, self::META_KEY, true) === '1';
    }
}

ACF_Filter_Sync_Toggle::init();