<?php
/*
Plugin Name: Apisearch for WooCommerce
Description: Integrates WooCommerce products with the Apisearch search engine.
Version: 1.0
Author: Apisearch SL
*/

// Function to retrieve WooCommerce products

define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('WP_DEBUG_DISPLAY', false);

function index_all_woocommerce_products_to_apisearch()
{

    $formatted_products = get_and_serialize_all_products();
    send_products_to_apisearch_in_batches($formatted_products);

    // Return a response or success message
    return array(
        'success' => true,
        'message' => 'Products sent to Apisearch successfully.',
        'data' => $formatted_products
    );
}

/**
 * Send products to Apisearch in batches of 100.
 *
 * @param array $products An array of products to send to Apisearch.
 */
function send_products_to_apisearch_in_batches($products)
{
    $batch_size = 100;
    $total_products = count($products);
    $batches = ceil($total_products / $batch_size);

    for ($i = 0; $i < $batches; $i++) {
        $start = $i * $batch_size;
        $end = ($i + 1) * $batch_size;
        $batch = array_slice($products, $start, $batch_size);

        // Send the batch to the Apisearch endpoint via PUT request
//        $response = wp_safe_remote_request('https://eu1.apisearch.cloud', array(
//            'method' => 'PUT',
//            'body' => json_encode($batch),
//            'headers' => array(
//                'Content-Type' => 'application/json',
//            ),
//        ));
        $response = wp_safe_remote_request('http://localhost:8300/v1/{app_id}/indices/{index_id}', array(
            'method' => 'PUT',
            'body' => json_encode($batch),
            'headers' => array(
                'Content-Type' => 'application/json',
            ),
        ));

        // Check the response for errors or handle success as needed
        if (is_wp_error($response)) {
            // Handle error
            error_log('Error sending batch to Apisearch: ' . $response->get_error_message());
        } else {
            // Handle success
            $response_code = wp_remote_retrieve_response_code($response);
            $response_body = wp_remote_retrieve_body($response);

            if ($response_code === 200) {
                // Batch was successfully sent
                // You can log or process the response here
            } else {
                // Handle other response codes (e.g., 4xx or 5xx)
                error_log('Batch sending failed. Response code: ' . $response_code);
            }
        }
    }
}

// Register a custom REST API route for sending products to Apisearch
function register_send_products_to_apisearch_route()
{
    register_rest_route('apisearch/v1', '/index-products', array(
        'methods' => 'POST',
        'callback' => 'index_all_woocommerce_products_to_apisearch',
    ));
}

add_action('rest_api_init', 'register_send_products_to_apisearch_route');

// Function to register and initialize settings
function register_apisearch_settings()
{
    register_setting('apisearch_settings', 'apisearch_app_id');
    register_setting('apisearch_settings', 'apisearch_index_id');
    register_setting('apisearch_settings', 'apisearch_display_search_bar');
    register_setting('apisearch_settings', 'apisearch_index_product_without_image');

    add_settings_section(
        'apisearch_section',
        'ApiSearch Configuration',
        'render_apisearch_section',
        'apisearch_settings'
    );

    add_settings_field(
        'apisearch_app_id',
        'App ID',
        'render_apisearch_app_id_field',
        'apisearch_settings',
        'apisearch_section'
    );

    add_settings_field(
        'apisearch_index_id',
        'Index ID',
        'render_apisearch_index_id_field',
        'apisearch_settings',
        'apisearch_section'
    );

    add_settings_field(
        'apisearch_display_search_bar',
        'Display Search Bar',
        'render_apisearch_display_search_bar_field',
        'apisearch_settings',
        'apisearch_section'
    );

    add_settings_field(
        'apisearch_index_product_without_image',
        'Index Products Without Image',
        'render_apisearch_index_product_without_image_field',
        'apisearch_settings',
        'apisearch_section'
    );
}

function render_apisearch_section()
{
    // Section description (if needed)
}

function render_apisearch_app_id_field()
{
    $app_id = get_option('apisearch_app_id');
    echo '<input type="text" name="apisearch_app_id" value="' . esc_attr($app_id) . '" />';
}

function render_apisearch_index_id_field()
{
    $index_id = get_option('apisearch_index_id');
    echo '<input type="text" name="apisearch_index_id" value="' . esc_attr($index_id) . '" />';
}

function render_apisearch_display_search_bar_field()
{
    $display_search_bar = get_option('apisearch_display_search_bar');
    echo '<input type="checkbox" name="apisearch_display_search_bar" value="1" ' . checked(1, $display_search_bar, false) . ' />';
}

function render_apisearch_index_product_without_image_field()
{
    $index_product_without_image = get_option('apisearch_index_product_without_image');
    echo '<input type="checkbox" name="apisearch_index_product_without_image" value="1" ' . checked(1, $index_product_without_image, false) . ' />';
}

add_action('admin_init', 'register_apisearch_settings');

// Function to add a settings page to the WooCommerce settings menu
function add_apisearch_settings_page()
{
    add_submenu_page(
        'woocommerce', // Parent menu slug (WooCommerce)
        'ApiSearch Settings',
        'ApiSearch',
        'manage_options',
        'apisearch-settings',
        'render_apisearch_settings_page'
    );
}

function create_custom_table()
{
    global $wpdb;
    $table_name = $wpdb->prefix . 'apisearch_language_configs';

    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE $table_name (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        shop_id mediumint(9) NOT NULL,
        language VARCHAR(10) NOT NULL,
        index_id VARCHAR(255) NOT NULL,
        PRIMARY KEY  (id)
    ) $charset_collate;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
}

register_activation_hook(__FILE__, 'create_custom_table');


// Function to render the settings page
function render_apisearch_settings_page()
{
    ?>
    <div class="wrap">
        <h2>ApiSearch Settings</h2>
        <form method="post" action="options.php">
            <?php
            settings_fields('apisearch_settings');
            do_settings_sections('apisearch_settings');

            // Get active languages from WPML
            $active_languages = apply_filters('wpml_active_languages', NULL, 'orderby=id&order=desc');

            if ($active_languages) {
                foreach ($active_languages as $language) {
                    $language_code = $language['language_code'];
                    $index_id = get_language_specific_index_id($language_code);
                    ?>
                    <label for="apisearch_index_id_<?php echo $language_code; ?>"><?php echo $language_code; ?> Index
                        ID:</label>
                    <input type="text" name="apisearch_index_id_<?php echo $language_code; ?>"
                           value="<?php echo esc_attr($index_id); ?>"/><br>
                    <?php
                }
            }

            submit_button();
            ?>
        </form>
    </div>
    <?php
}

add_action('admin_menu', 'add_apisearch_settings_page');


/**
 * Serialize a WooCommerce product to Apisearch format.
 *
 * @param WC_Product $product The WooCommerce product to be serialized.
 * @return array The product data in Apisearch format.
 */
function serialize_product_for_apisearch($product)
{
    // Get product categories
    $categories = wp_get_post_terms($product->get_id(), 'product_cat', array('fields' => 'names'));

    // Get product tags
    $tags = wp_get_post_terms($product->get_id(), 'product_tag', array('fields' => 'names'));

    // Create the Apisearch product data
    $woocommerce_product = array(
        'id' => $product->get_id(),
        'title' => $product->get_title(),
        'description' => $product->get_description(),
        'short_description' => $product->get_short_description(),
        'image' => wp_get_attachment_url($product->get_image_id()),
        'regular_price' => $product->get_regular_price(),
        'sale_price' => $product->get_sale_price(),
        'categories' => $categories,
        'sku' => $product->get_sku(),
        'product_url' => get_permalink($product->get_id()),
        'product_type' => $product->get_type(),
        'product_attributes' => $product->get_attributes(),
        'weight' => $product->get_weight(),
        'dimensions' => $product->get_dimensions(false),
        'tags' => $tags,
        // Add more fields as needed
    );

    $apisearch_product = array(
        'uuid' => array(
            "id" => (string)$woocommerce_product['id'],
            "type" => "product"
        ),
        'metadata' => array(
            'name' => (string)$woocommerce_product['title'],
            'img' => $woocommerce_product['image'],
            'price' => $woocommerce_product['regular_price']
        ),
        'indexed_metadata' => array(
            'name' => (string)$woocommerce_product['title'],
        ),
        'searchable_metadata' => array(
            'name' => (string)$woocommerce_product['title'],
        ),
        'exact_matching_metadata' => array(),
    );

    return $apisearch_product;
}

/**
 * Get all products from WooCommerce, paginated, and serialize them in batches.
 *
 * @param int $per_page Number of products to fetch per batch.
 * @return array An array of serialized product data.
 */
function get_and_serialize_all_products($per_page = 10)
{
    $page = 1;
    $serialized_products = array();

    while (true) {
        $args = array(
            'status' => 'publish', // Fetch only published products
            'limit' => $per_page,
            'page' => $page,
        );

        $products = wc_get_products($args);

        if (empty($products)) {
            // No more products found, exit the loop
            break;
        }

        foreach ($products as $product) {
            // Serialize each product and add it to the array
            $serialized_products[] = serialize_product_for_apisearch($product);
        }

        $page++; // Move to the next page
    }

    return $serialized_products;
}