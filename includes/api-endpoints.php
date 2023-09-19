<?php


// Function to retrieve WooCommerce products and serialize them
function get_apisearch_feed()
{
    // Check if the auth_uuid is provided in the URL
    $auth_uuid = isset($_GET['auth_uuid']) ? sanitize_text_field($_GET['auth_uuid']) : '';

    // Get the stored auth_uuid from the options
    $stored_auth_uuid = get_option('uuid_auth_token');

    // Compare the provided auth_uuid with the stored one
    if ($auth_uuid !== $stored_auth_uuid) {
        // Return a 403 Forbidden response
        status_header(403);
        exit;
    }

    // Get the optional lang parameter from the URL
    $lang = isset($_GET['lang']) ? sanitize_text_field($_GET['lang']) : '';

    // Retrieve the plugin settings from the database
    $index_product_without_image = get_option('index_product_without_image');

    // Define common query parameters
    $per_page = 10; // Set your desired number of products per batch
    $page = 1;

    $jsonl = "";

    while (true) {
        // Initialize the query args array
        $args = array(
            'status' => 'publish',
            'limit' => $per_page,
            'page' => $page,
        );

        // Add lang parameter if it's set
        if (!empty($lang)) {
            $args['lang'] = $lang;
        }

        // Retrieve products based on the args
        $products = wc_get_products($args);

        if (empty($products)) {
            // No more products found, exit the loop
            break;
        }

        foreach ($products as $product) {
            if (!$index_product_without_image) {
                if ($product->get_image_id() == "") {
                    continue;
                }
            }
            // Serialize each product and echo it as JSON
            $apisearch_product = serialize_product_for_apisearch($product);
            $jsonl .= json_encode($apisearch_product) . "\n";
        }

        $page++; // Move to the next page
    }

    return $jsonl;

    exit;
}

// Callback function for the check plugin route
function check_plugin_status()
{
    // Return a 204 No Content response
    return rest_ensure_response('', 204);
}


// Register a custom REST API route for sending products to Apisearch
function register_apisearch_feed_route()
{
    register_rest_route('apisearch/v1', '/feed', array(
        'methods' => 'GET',
        'callback' => 'get_apisearch_feed',
    ));
}
add_action('rest_api_init', 'register_apisearch_feed_route');

// Register a custom REST API route for checking if the plugin is working
function register_check_plugin_route()
{
    register_rest_route('apisearch/v1', '/check', array(
        'methods' => 'GET',
        'callback' => 'check_plugin_status',
    ));
}

add_action('rest_api_init', 'register_check_plugin_route');
