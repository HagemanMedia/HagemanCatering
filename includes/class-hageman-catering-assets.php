<?php
/**
 * Asset registratie voor de plugin.
 */

if (! defined('ABSPATH')) {
    exit;
}

class Hageman_Catering_Assets
{
    public static function init(): void
    {
        add_action('wp_enqueue_scripts', [__CLASS__, 'register_public_assets']);
    }

    public static function register_public_assets(): void
    {
        self::register_agenda_assets();
        self::register_maatwerk_assets();
    }

    private static function register_agenda_assets(): void
    {
        $styles = [
            'hageman-catering-agenda-look-and-feel' => 'assets/agenda/css/look-and-feel.css',
            'hageman-catering-agenda-header' => 'assets/agenda/css/header.css',
            'hageman-catering-agenda-content' => 'assets/agenda/css/content.css',
        ];

        foreach ($styles as $handle => $relative_path) {
            wp_register_style(
                $handle,
                HAGEMAN_CATERING_PLUGIN_URL . $relative_path,
                [],
                filemtime(HAGEMAN_CATERING_PLUGIN_DIR . $relative_path)
            );
        }

        $scripts = [
            'hageman-catering-agenda-header' => 'assets/agenda/js/header.js',
            'hageman-catering-agenda-content' => 'assets/agenda/js/content.js',
        ];

        foreach ($scripts as $handle => $relative_path) {
            wp_register_script(
                $handle,
                HAGEMAN_CATERING_PLUGIN_URL . $relative_path,
                [],
                filemtime(HAGEMAN_CATERING_PLUGIN_DIR . $relative_path),
                true
            );
        }

        wp_register_script(
            'hageman-catering-agenda',
            HAGEMAN_CATERING_PLUGIN_URL . 'assets/agenda/js/index.js',
            ['hageman-catering-agenda-header', 'hageman-catering-agenda-content'],
            filemtime(HAGEMAN_CATERING_PLUGIN_DIR . 'assets/agenda/js/index.js'),
            true
        );
    }

    private static function register_maatwerk_assets(): void
    {
        $style = 'assets/maatwerk/css/form.css';
        $script = 'assets/maatwerk/js/form.js';

        if (file_exists(HAGEMAN_CATERING_PLUGIN_DIR . $style)) {
            wp_register_style(
                'hageman-catering-maatwerk-form',
                HAGEMAN_CATERING_PLUGIN_URL . $style,
                [],
                filemtime(HAGEMAN_CATERING_PLUGIN_DIR . $style)
            );
        }

        if (file_exists(HAGEMAN_CATERING_PLUGIN_DIR . $script)) {
            wp_register_script(
                'hageman-catering-maatwerk-form',
                HAGEMAN_CATERING_PLUGIN_URL . $script,
                [],
                filemtime(HAGEMAN_CATERING_PLUGIN_DIR . $script),
                true
            );
        }
    }

    public static function enqueue_agenda_assets(): void
    {
        wp_enqueue_style('hageman-catering-agenda-look-and-feel');
        wp_enqueue_style('hageman-catering-agenda-header');
        wp_enqueue_style('hageman-catering-agenda-content');

        wp_enqueue_script('hageman-catering-agenda-header');
        wp_enqueue_script('hageman-catering-agenda-content');
        wp_enqueue_script('hageman-catering-agenda');

        wp_localize_script(
            'hageman-catering-agenda',
            'HagemanAgendaData',
            [
                'ajaxUrl' => admin_url('admin-ajax.php'),
            ]
        );
    }

    public static function enqueue_maatwerk_assets(): void
    {
        wp_enqueue_style('hageman-catering-maatwerk-form');
        wp_enqueue_script('hageman-catering-maatwerk-form');

        wp_localize_script(
            'hageman-catering-maatwerk-form',
            'HagemanMaatwerkData',
            [
                'ajaxUrl' => admin_url('admin-ajax.php'),
                'searchNonce' => wp_create_nonce('maatwerk_search_users_nonce'),
                'deleteNonce' => wp_create_nonce('delete_maatwerk_order_nonce'),
                'i18n' => [
                    'confirmDelete' => __('Weet je zeker dat je deze bestelling wilt verwijderen?', 'hageman-catering'),
                    'noResults' => __('Geen resultaten gevonden', 'hageman-catering'),
                ],
            ]
        );
    }
}
