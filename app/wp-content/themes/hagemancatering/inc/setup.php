<?php
if (!defined('ABSPATH')) exit;

/**
 * Thema-setup (menu's, thumbnails, etc.)
 */
add_action('after_setup_theme', function () {
    add_theme_support('title-tag');
    add_theme_support('post-thumbnails');
    register_nav_menus([
        'primary' => __('Primary Menu', 'hagemancatering'),
    ]);
});
