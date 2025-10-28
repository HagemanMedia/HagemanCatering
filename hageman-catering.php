<?php
/**
 * Plugin Name:       Hageman Catering Toolkit
 * Description:       Verzamelt de agenda- en maatwerkfunctionaliteit in een plugin-structuur.
 * Version:           1.0.0
 * Author:            Hageman Catering
 */

if (! defined('ABSPATH')) {
    exit;
}

define('HAGEMAN_CATERING_PLUGIN_FILE', __FILE__);
define('HAGEMAN_CATERING_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('HAGEMAN_CATERING_PLUGIN_URL', plugin_dir_url(__FILE__));

require_once HAGEMAN_CATERING_PLUGIN_DIR . 'includes/class-hageman-catering-plugin.php';

Hageman_Catering_Plugin::init();
