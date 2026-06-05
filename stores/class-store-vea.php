<?php

defined('ABSPATH') || exit;

class Super_Scanner_Store_Vea extends Super_Scanner_Store {

    public function get_slug() {
        return 'vea';
    }

    public function get_name() {
        return 'Vea';
    }

    public function get_base_url() {
        return 'https://www.vea.com.ar/api/catalog_system/pub/products/search';
    }

    public function get_sales_channels() {
        return apply_filters('super_scanner_vea_sc_list', array());
    }

    public function get_ean_filter_prefix() {
        return 'alternateIds_Ean';
    }
}
