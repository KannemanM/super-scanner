<?php
/**
 * Plugin Name:     SuperScanner
 * Plugin URI:      https://github.com/KannemanM/super-scanner
 * Description:     Escanea códigos de barras y compara precios en Mas Online, Carrefour y más vía API VTEX.
 * Version:         1.0.0
 * Author:          Martin Kanneman
 * Text Domain:     super-scanner
 * Domain Path:     /languages
 * Requires PHP:    7.4
 * Requires WP:     5.0
 */

defined('ABSPATH') || exit;

define('SUPER_SCANNER_VERSION', '1.0.0');
define('SUPER_SCANNER_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('SUPER_SCANNER_PLUGIN_URL', plugin_dir_url(__FILE__));

require_once SUPER_SCANNER_PLUGIN_DIR . 'includes/class-store.php';
require_once SUPER_SCANNER_PLUGIN_DIR . 'stores/class-store-masonline.php';
require_once SUPER_SCANNER_PLUGIN_DIR . 'stores/class-store-carrefour.php';
require_once SUPER_SCANNER_PLUGIN_DIR . 'includes/class-rest-controller.php';
require_once SUPER_SCANNER_PLUGIN_DIR . 'includes/class-shortcode.php';

function super_scanner_init() {
    $controller = Super_Scanner_REST_Controller::get_instance();
    $controller->add_store(new Super_Scanner_Store_MasOnline());
    $controller->add_store(new Super_Scanner_Store_Carrefour());
    Super_Scanner_Shortcode::get_instance();
}
add_action('plugins_loaded', 'super_scanner_init');
