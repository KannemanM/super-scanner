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
        add_shortcode('super_scanner', array($this, 'render_widget'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_assets'));
    }

    public function enqueue_assets() {
        wp_enqueue_style(
            'super-scanner-style',
            SUPER_SCANNER_PLUGIN_URL . 'assets/style.css',
            array(),
            SUPER_SCANNER_VERSION
        );

        wp_enqueue_style(
            'font-awesome',
            'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css',
            array(),
            null
        );

        wp_enqueue_script(
            'html5-qrcode',
            'https://unpkg.com/html5-qrcode',
            array(),
            null,
            true
        );

        wp_enqueue_script(
            'super-scanner-widget',
            SUPER_SCANNER_PLUGIN_URL . 'assets/scanner.js',
            array('html5-qrcode'),
            SUPER_SCANNER_VERSION,
            true
        );

        $controller = Super_Scanner_REST_Controller::get_instance();
        $stores = $controller->get_stores();

        $store_data = array();
        foreach ($stores as $slug => $store) {
            $colors = array(
                'masonline' => '#e53935',
                'carrefour' => '#004d99',
            );
            $store_data[] = array(
                'slug'  => $slug,
                'label' => $store->get_name(),
                'color' => $colors[$slug] ?? '#333',
            );
        }

        wp_localize_script('super-scanner-widget', 'SuperScannerData', array(
            'apiBase' => rest_url('super-scanner/v1/precio'),
            'stores'  => $store_data,
        ));
    }

    public function render_widget() {

        ob_start();
        ?>
        <div class="ss-container" id="ss-container">
            <div class="ss-search-bar">
                <input type="text" id="ss-ean-input" placeholder="Ingresá el código de barras..." autocomplete="off">
                <button class="ss-btn-camera" id="ss-btn-camera" title="Escanear código de barras">
                    <i class="fas fa-camera"></i>
                </button>
            </div>
            <button class="ss-btn-consultar" id="ss-btn-consultar" disabled>Comparar precios</button>
            <div id="ss-reader" style="display:none;"></div>
            <div id="ss-resultados" class="ss-resultados"></div>
        </div>
        <?php
        return ob_get_clean();
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
