<?php
/**
 * Plugin Name: HM Bootstrap
 * Description: Vroege bootstrap voor paden/constanten en (optioneel) Composer autoload.
 */

if (!defined('ABSPATH')) exit;

define('HM_APP_PATH', dirname(dirname(__DIR__)));                 // .../app
define('HM_THEME_PATH', HM_APP_PATH . '/wp-content/themes/hagemancatering');
define('HM_PLUGINS_PATH', HM_APP_PATH . '/wp-content/plugins');
define('HM_SHARED_PATH', HM_APP_PATH . '/shared');

// (Optioneel) Composer autoload (indien vendor aanwezig):
$autoload = dirname(__DIR__, 2) . '/vendor/autoload.php';
if (file_exists($autoload)) {
    require_once $autoload;
}
