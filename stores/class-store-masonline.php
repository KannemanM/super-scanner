<?php

defined('ABSPATH') || exit;

class Super_Scanner_Store_MasOnline extends Super_Scanner_Store {

    public function get_slug() {
        return 'masonline';
    }

    public function get_name() {
        return 'Mas Online';
    }

    public function get_base_url() {
        return 'https://www.masonline.com.ar/api/catalog_system/pub/products/search';
    }

    public function get_sales_channels() {
        return apply_filters('super_scanner_masonline_sc_list', [1, 2, 3, 4, 5, 7]);
    }

    public function get_ean_filter_prefix() {
        return 'alternateIds_Ean';
    }
}
