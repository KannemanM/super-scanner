<?php

defined('ABSPATH') || exit;

class Super_Scanner_Shortcode {

    private static $instance = null;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_shortcode('super_scanner_precio', array($this, 'render'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_styles'));
    }

    public function enqueue_styles() {
        wp_enqueue_style(
            'super-scanner-style',
            SUPER_SCANNER_PLUGIN_URL . 'assets/style.css',
            array(),
            SUPER_SCANNER_VERSION
        );
    }

    public function render($atts) {
        $atts = shortcode_atts(array(
            'ean' => '',
        ), $atts, 'super_scanner_precio');

        if (empty($atts['ean']) || !is_numeric($atts['ean']) || strlen($atts['ean']) < 8) {
            return '<div class="super-scanner-error">Código EAN inválido.</div>';
        }

        $store = new Super_Scanner_Store_MasOnline();
        $result = $store->get_product_by_ean($atts['ean']);

        if (!isset($result['success']) || true !== $result['success']) {
            return '<div class="super-scanner-error">' . esc_html($result['message'] ?? 'Producto no encontrado') . '</div>';
        }

        $html = '<div class="super-scanner-producto">';

        if (!empty($result['imagen'])) {
            $html .= '<div class="super-scanner-imagen">';
            $html .= '<img src="' . esc_url($result['imagen']) . '" alt="' . esc_attr($result['nombre']) . '" loading="lazy">';
            $html .= '</div>';
        }

        $html .= '<div class="super-scanner-info">';
        $html .= '<h3 class="super-scanner-nombre">' . esc_html($result['nombre']) . '</h3>';
        $html .= '<p class="super-scanner-marca">' . esc_html($result['marca']) . '</p>';

        if ($result['tiene_desc']) {
            $html .= '<p class="super-scanner-precio-lista super-scanner-tachado">$ ' . number_format($result['precio_lista'], 2, ',', '.') . '</p>';
            $html .= '<p class="super-scanner-precio-web super-scanner-oferta">$ ' . number_format($result['precio_web'], 2, ',', '.') . '</p>';
        } else {
            $html .= '<p class="super-scanner-precio-web">$ ' . number_format($result['precio_web'], 2, ',', '.') . '</p>';
        }

        $html .= '</div>';
        $html .= '</div>';

        return $html;
    }
}
