<?php

defined('ABSPATH') || exit;

class Super_Scanner_Store_Carrefour extends Super_Scanner_Store {

    public function get_slug() {
        return 'carrefour';
    }

    public function get_name() {
        return 'Carrefour';
    }

    public function get_base_url() {
        return 'https://www.carrefour.com.ar/api/catalog_system/pub/products/search';
    }

    public function get_sales_channels() {
        return apply_filters('super_scanner_carrefour_sc_list', [1, 3, 5]);
    }

    public function get_ean_filter_prefix() {
        return 'alternateIds_Ean';
    }
}
