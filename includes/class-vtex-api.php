<?php

defined('ABSPATH') || exit;

class Super_Scanner_Vtex_Api {

    const BASE_URL = 'https://www.masonline.com.ar/api/catalog_system/pub/products/search';
    const CACHE_TTL = 300;

    public static function get_product_by_ean($ean) {
        $cache_key = 'super_scanner_ean_' . sanitize_key($ean);
        $cached = get_transient($cache_key);
        if (false !== $cached) {
            return $cached;
        }

        $channels = apply_filters('super_scanner_sc_list', [1, 2, 3, 4, 5, 7]);

        foreach ($channels as $sc) {
            $result = self::query_vtex($ean, $sc);
            if (is_wp_error($result)) {
                continue;
            }
            if (!empty($result)) {
                $ttl = apply_filters('super_scanner_cache_ttl', self::CACHE_TTL);
                set_transient($cache_key, $result, $ttl);
                return $result;
            }
        }

        $not_found = array(
            'success' => false,
            'message' => 'Producto no encontrado en Mas Online',
        );
        $ttl = apply_filters('super_scanner_cache_ttl', self::CACHE_TTL);
        set_transient($cache_key, $not_found, $ttl);
        return $not_found;
    }

    private static function query_vtex($ean, $sc) {
        $url = add_query_arg(
            array(
                'fq' => 'alternateIds_Ean:' . $ean,
                'sc' => $sc,
            ),
            self::BASE_URL
        );

        $args = array(
            'timeout' => 10,
            'headers' => array(
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
                'Accept'     => 'application/json',
            ),
        );

        $response = wp_remote_get($url, $args);

        if (is_wp_error($response)) {
            return $response;
        }

        $body = wp_remote_retrieve_body($response);
        $productos = json_decode($body, true);

        if (!is_array($productos) || empty($productos)) {
            return array();
        }

        return self::format_product($productos[0]);
    }

    private static function format_product($producto) {
        if (empty($producto['items'][0])) {
            return array();
        }

        $sku = $producto['items'][0];
        $seller = $sku['sellers'][0] ?? array();
        $offer = $seller['commertialRecord'] ?? $seller['commertialOffer'] ?? array();

        $list_price = isset($offer['ListPrice']) ? (float) $offer['ListPrice'] : 0;
        $price = isset($offer['Price']) ? (float) $offer['Price'] : 0;
        $available = isset($offer['AvailableQuantity']) ? (int) $offer['AvailableQuantity'] : 0;

        if (0 === $available) {
            return array();
        }

        return array(
            'success'      => true,
            'nombre'       => $producto['productName'] ?? '',
            'marca'        => $producto['brand'] ?? '',
            'imagen'       => $sku['images'][0]['imageUrl'] ?? '',
            'precio_lista' => $list_price,
            'precio_web'   => $price,
            'tiene_desc'   => $list_price > $price,
        );
    }
}
