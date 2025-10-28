<?php
/**
 * Basis bootstrap voor de Hageman Catering plugin.
 */

if (! defined('ABSPATH')) {
    exit;
}

require_once HAGEMAN_CATERING_PLUGIN_DIR . 'includes/class-hageman-catering-assets.php';
require_once HAGEMAN_CATERING_PLUGIN_DIR . 'includes/agenda/class-hageman-catering-agenda.php';
require_once HAGEMAN_CATERING_PLUGIN_DIR . 'includes/maatwerk/class-hageman-catering-maatwerk.php';

class Hageman_Catering_Plugin
{
    public static function init(): void
    {
        add_action('plugins_loaded', [__CLASS__, 'bootstrap']);
    }

    public static function bootstrap(): void
    {
        Hageman_Catering_Assets::init();
        Hageman_Catering_Agenda::init();
        Hageman_Catering_Maatwerk::init();
    }
}
