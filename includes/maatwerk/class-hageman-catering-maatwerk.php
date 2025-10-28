<?php
/**
 * Registratie van het maatwerkformulier en AJAX-handlers.
 */

if (! defined('ABSPATH')) {
    exit;
}

require_once __DIR__ . '/form.php';

class Hageman_Catering_Maatwerk
{
    public static function init(): void
    {
        add_shortcode('maatwerk_bestelformulier', [__CLASS__, 'render_shortcode']);

        add_action('wp_ajax_maatwerk_search_users', 'maatwerk_search_users_ajax');
        add_action('wp_ajax_delete_maatwerk_order', 'delete_maatwerk_order_ajax');
    }

    public static function render_shortcode($atts = []): string
    {
        Hageman_Catering_Assets::enqueue_maatwerk_assets();
        return display_maatwerk_bestelformulier($atts);
    }
}
