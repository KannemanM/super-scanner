<?php

defined('ABSPATH') || exit;

class Super_Scanner_REST_Controller {

    private static $instance = null;
    private $stores = array();

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action('rest_api_init', array($this, 'register_routes'));
    }

    public function add_store(Super_Scanner_Store $store) {
        $this->stores[$store->get_slug()] = $store;
    }

    public function get_stores() {
        return $this->stores;
    }

    public function register_routes() {
        foreach ($this->stores as $slug => $store) {
            register_rest_route('super-scanner/v1', '/precio/' . $slug . '/(?P<ean>\d+)', array(
                'methods'             => 'GET',
                'permission_callback' => '__return_true',
                'callback'            => function ($request) use ($store) {
                    $ean = $request->get_param('ean');
                    $result = $store->get_product_by_ean($ean);

                    if (isset($result['success']) && false === $result['success']) {
                        return new WP_Error(
                            'producto_no_encontrado',
                            $result['message'],
                            array('status' => 404)
                        );
                    }

                    return rest_ensure_response($result);
                },
                'args' => array(
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
    }
}
