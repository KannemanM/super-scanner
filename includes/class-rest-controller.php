<?php

defined('ABSPATH') || exit;

class Super_Scanner_REST_Controller {

    private static $instance = null;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action('rest_api_init', array($this, 'register_routes'));
    }

    public function register_routes() {
        register_rest_route('super-scanner/v1', '/precio/(?P<ean>\d+)', array(
            'methods'             => 'GET',
            'callback'            => array($this, 'get_precio'),
            'permission_callback' => '__return_true',
            'args'                => array(
                'ean' => array(
                    'required'          => true,
                    'validate_callback' => function ($param) {
                        return is_numeric($param) && strlen($param) >= 8;
                    },
                    'sanitize_callback' => 'sanitize_text_field',
                ),
            ),
        ));
    }

    public function get_precio($request) {
        $ean = $request->get_param('ean');
        $result = Super_Scanner_Vtex_Api::get_product_by_ean($ean);

        if (isset($result['success']) && false === $result['success']) {
            return new WP_Error(
                'producto_no_encontrado',
                $result['message'],
                array('status' => 404)
            );
        }

        return rest_ensure_response($result);
    }
}
