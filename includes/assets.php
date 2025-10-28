<?php

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Registreert de scripts en styles die door de plugin worden gebruikt.
 */
function hageman_catering_register_assets() {
    $style_files = [
        'hageman-catering-agenda-look-and-feel' => 'assets/css/agenda-look-and-feel.css',
        'hageman-catering-agenda-header' => 'assets/css/agenda-header.css',
        'hageman-catering-agenda-content' => 'assets/css/agenda-content.css',
    ];

    foreach ($style_files as $handle => $relative_path) {
        wp_register_style(
            $handle,
            HAGEMAN_CATERING_PLUGIN_URL . $relative_path,
            [],
            filemtime(HAGEMAN_CATERING_PLUGIN_DIR . $relative_path)
        );
    }

    $script_files = [
        'hageman-catering-agenda-header' => 'assets/js/agenda-header.js',
        'hageman-catering-agenda-content' => 'assets/js/agenda-content.js',
    ];

    foreach ($script_files as $handle => $relative_path) {
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
        HAGEMAN_CATERING_PLUGIN_URL . 'assets/js/agenda.js',
        ['hageman-catering-agenda-header', 'hageman-catering-agenda-content'],
        filemtime(HAGEMAN_CATERING_PLUGIN_DIR . 'assets/js/agenda.js'),
        true
    );
}
add_action('wp_enqueue_scripts', 'hageman_catering_register_assets');

/**
 * Zorgt dat de agenda-assets geladen worden wanneer de shortcode actief is.
 */
function hageman_catering_enqueue_agenda_assets() {
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
