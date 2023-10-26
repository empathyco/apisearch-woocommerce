<?php

// Enqueue FontAwesome icons in plugin
function enqueue_font_awesome() {
    wp_enqueue_style('font-awesome', 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css');
}

add_action('wp_enqueue_scripts', 'enqueue_font_awesome');


// Enqueue custom scripts and styles
function custom_woocommerce_search_enqueue_scripts_and_styles() {
    if (isApisearchActive()) {
        wp_enqueue_script('custom-woocommerce-search', plugin_dir_url(__FILE__) . 'custom-woocommerce-search.js', array('jquery'), null, true);
        wp_enqueue_style('custom-woocommerce-search', plugin_dir_url(__FILE__) . 'custom-woocommerce-search.css');
    }
}
add_action('wp_enqueue_scripts', 'custom_woocommerce_search_enqueue_scripts_and_styles');

//// Add the search input to the body (commented for now, maybe usable for other templates or if input already exists...)
//function custom_woocommerce_search_input() {
//    if (is_shop()) {
//        echo '<input type="text" id="custom-search" placeholder="Search products">';
//    }
//}
//add_action('woocommerce_before_shop_loop', 'custom_woocommerce_search_input');


// Add the search input to the header
function custom_woocommerce_search_header() {
    if (isApisearchActive()) {
        if (class_exists('WooCommerce')) {
            ?>
            <link href="https://eu1.apisearch.cloud" rel="dns-prefetch" crossOrigin="anonymous">
            <script
                    type="application/javascript"
                    src='https://static.apisearch.cloud/<? echo get_option('index_id') ?>.layer.js'
                    charSet='UTF-8'
                    crossOrigin="anonymous"
            ></script>
            <?php
        }
    }
}
add_action('wp_head', 'custom_woocommerce_search_header');

function isApisearchActive() {
    return get_option('show_search_input') == "1";
}