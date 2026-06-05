<?php

defined('ABSPATH') || exit;

abstract class Super_Scanner_Store {

    const CACHE_TTL = 900;

    abstract public function get_slug();
    abstract public function get_name();
    abstract public function get_base_url();
    abstract public function get_sales_channels();
    abstract public function get_ean_filter_prefix();

    private static function get_random_user_agent() {
        $agents = array(
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/125.0.0.0 Safari/537.36',
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36',
            'Mozilla/5.0 (Macintosh; Intel Mac OS X 14_5) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/125.0.0.0 Safari/537.36',
            'Mozilla/5.0 (Macintosh; Intel Mac OS X 14_4) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.4 Safari/605.1.15',
            'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36',
            'Mozilla/5.0 (Linux; Android 14; Pixel 8) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/125.0.6422.113 Mobile Safari/537.36',
            'Mozilla/5.0 (Linux; Android 13; SM-S23) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.6367.83 Mobile Safari/537.36',
            'Mozilla/5.0 (iPhone; CPU iPhone OS 17_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.5 Mobile/15E148 Safari/604.1',
            'Mozilla/5.0 (iPhone; CPU iPhone OS 17_4 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) CriOS/124.0.6367.111 Mobile/15E148 Safari/604.1',
        );
        return $agents[array_rand($agents)];
    }

    public function get_product_by_ean($ean) {
        $cache_key = 'ss_store_' . $this->get_slug() . '_' . sanitize_key($ean);
        $cached = get_transient($cache_key);
        if (false !== $cached) {
            return $cached;
        }

        // Try without sales channel first (some stores like Vea don't use sc)
        $result = $this->query_vtex($ean, null);
        if (!is_wp_error($result) && !empty($result)) {
            $ttl = apply_filters('super_scanner_cache_ttl', self::CACHE_TTL);
            set_transient($cache_key, $result, $ttl);
            return $result;
        }

        // Then try with each configured sales channel
        $channels = $this->get_sales_channels();
        foreach ($channels as $sc) {
            $result = $this->query_vtex($ean, $sc);
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
            'store'   => $this->get_slug(),
            'message' => 'Producto no encontrado en ' . $this->get_name(),
        );
        $ttl = apply_filters('super_scanner_cache_ttl', self::CACHE_TTL);
        set_transient($cache_key, $not_found, $ttl);
        return $not_found;
    }

    protected function query_vtex($ean, $sc) {
        $query_args = array(
            'fq' => $this->get_ean_filter_prefix() . ':' . $ean,
        );
        if ($sc !== null) {
            $query_args['sc'] = $sc;
        }
        $url = add_query_arg($query_args, $this->get_base_url());

        $args = array(
            'timeout' => 10,
            'headers' => array(
                'User-Agent' => self::get_random_user_agent(),
                'Accept'     => 'application/json',
            ),
        );

        $response = wp_remote_get($url, $args);

        // Retry once with backoff if there's a transport error
        if (is_wp_error($response)) {
            sleep(1);
            $args['headers']['User-Agent'] = self::get_random_user_agent();
            $response = wp_remote_get($url, $args);
        }

        if (is_wp_error($response)) {
            return $response;
        }

        $body = wp_remote_retrieve_body($response);
        $productos = json_decode($body, true);

        if (!is_array($productos) || empty($productos)) {
            return array();
        }

        return $this->format_product($productos[0]);
    }

    protected function format_product($producto) {
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
            'store'        => $this->get_slug(),
            'store_name'   => $this->get_name(),
            'nombre'       => $producto['productName'] ?? '',
            'marca'        => $producto['brand'] ?? '',
            'imagen'       => $sku['images'][0]['imageUrl'] ?? '',
            'link'         => $producto['link'] ?? '',
            'precio_lista' => $list_price,
            'precio_web'   => $price,
            'tiene_desc'   => $list_price > $price,
        );
    }
}
