<?php
/**
 * Registratie van de agenda-shortcode en bijbehorende AJAX-handlers.
 */

if (! defined('ABSPATH')) {
    exit;
}

require_once __DIR__ . '/agenda.php';

class Hageman_Catering_Agenda
{
    public static function init(): void
    {
        add_shortcode('wc_orders_week_agenda', [__CLASS__, 'render_shortcode']);

        add_action('wp_ajax_wc_orders_week_agenda_ajax', 'wc_orders_week_agenda_ajax_handler');
        add_action('wp_ajax_nopriv_wc_orders_week_agenda_ajax', 'wc_orders_week_agenda_ajax_handler');

        add_action('wp_ajax_wc_get_order_details_ajax', 'wc_get_order_details_ajax_handler');
        add_action('wp_ajax_nopriv_wc_get_order_details_ajax', 'wc_get_order_details_ajax_handler');

        add_action('wp_ajax_wc_get_daily_product_summary_ajax', 'wc_get_daily_product_summary_handler');
        add_action('wp_ajax_nopriv_wc_get_daily_product_summary_ajax', 'wc_get_daily_product_summary_handler');

        add_action('save_post', [__CLASS__, 'capture_logbook'], 10, 3);
    }

    public static function render_shortcode($atts = []): string
    {
        Hageman_Catering_Assets::enqueue_agenda_assets();
        return show_wc_orders_week_agenda($atts);
    }

    public static function capture_logbook($post_id, $post, $update): void
    {
        if (wp_is_post_revision($post_id)) {
            return;
        }

        if (! in_array($post->post_type, ['maatwerk_bestelling', 'shop_order'], true)) {
            return;
        }

        $user_id = get_current_user_id();
        if (! $user_id) {
            return;
        }

        if (! get_post_meta($post_id, '_created_by', true)) {
            update_post_meta($post_id, '_created_by', $user_id);
        }

        update_post_meta($post_id, '_last_edited_by', $user_id);
    }
}
