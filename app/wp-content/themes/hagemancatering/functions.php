<?php
if (!defined('ABSPATH')) exit;

/**
 * Minimal theme loader: laad losse modules uit /inc
 */

$inc = __DIR__ . '/inc';

// Altijd laden
require_once $inc . '/setup.php';

// Submappen systematisch includen
foreach (['hooks', 'post-types', 'taxonomies', 'shortcodes'] as $folder) {
    $dir = $inc . '/' . $folder;
    if (is_dir($dir)) {
        foreach (glob($dir . '/*.php') as $file) {
            require_once $file;
        }
    }
}
