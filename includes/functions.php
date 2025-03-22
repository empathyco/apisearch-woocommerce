<?php

/*
function custom_woocommerce_search_input() {
    if (is_shop()) {
        echo '<input type="text" id="custom-search" placeholder="Search products">';
    }
}
add_action('woocommerce_before_shop_loop', 'custom_woocommerce_search_input');
*/


// Add the search input to the header
function enqueue_script() {
    if (isApisearchActive()) {
        if (class_exists('WooCommerce')) {
            wp_enqueue_script(
                'apisearch-script',
                'https://static.apisearch.cloud/' . get_option('index_id') . '.layer.js'
                // 'http://localhost:8300/' . get_option('index_id') . '.layer.js'
            );
        }
    }
}
add_action('wp_enqueue_scripts', 'enqueue_script');

function isApisearchActive() {
    return get_option('show_search_input') == "1";
}