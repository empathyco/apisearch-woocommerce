<?php


// Function to retrieve WooCommerce products and serialize them
function get_apisearch_feed()
{
    require_once __DIR__ . '/plugins.php';

    // Get the optional lang parameter from the URL
    $lang = isset($_GET['lang']) ? sanitize_text_field($_GET['lang']) : '';

    $withTax = isset($_GET['tax']) ? $_GET['tax'] : true;
    $withTax = boolval($withTax);

    // Retrieve the plugin settings from the database
    $index_product_without_image = get_option('index_product_without_image');

    // Define common query parameters
    $per_page = 100; // Set your desired number of products per batch
    $page = 1;

    header('Content-Type:text/plain; charset=utf-8');

    while (true) {
        // Initialize the query args array
        $args = array(
            'status' => 'publish',
            'orderby' => 'ID',
            'order' => 'DESC',
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
            try {
                $apisearch_product = serialize_product_for_apisearch($product, $withTax);
            } catch (\Exception $exception) {
                var_dump($exception->getMessage());
                var_dump($exception->getFile());
                var_dump($exception->getLine());
                die();
            }

            if (empty($apisearch_product)) {
                continue;
            }
            echo json_encode($apisearch_product);
            echo PHP_EOL;
            ob_flush();
        }

        $page++; // Move to the next page
    }

    exit;
}

// Callback function for the check plugin route
function check_plugin_status()
{
    http_response_code(204);
    header('Access-Control-Allow-Origin: *');
    exit;
}

function set_index_id_callback($request)
{
    // Check if the auth_uuid is already validated by the permission callback
    $index_id = sanitize_text_field($request->get_param('index_id'));

    // Update the index_id value in the database
    update_option('index_id', $index_id);

    // You can return a success message or any response you need
    $response = array('message' => 'Index ID updated successfully');
    return rest_ensure_response($response);
}

function apisearch_permission_callback($request)
{
    //todo: temporary not using auth token, so return always true
    return true;

    // Check if the auth_uuid is provided in the URL
    $auth_uuid = isset($_GET['auth_uuid']) ? sanitize_text_field($_GET['auth_uuid']) : '';

    // Get the stored auth_uuid from the options
    $stored_auth_uuid = get_option('uuid_auth_token');

    // Compare the provided auth_uuid with the stored one
    if ($auth_uuid !== $stored_auth_uuid) {
        return new WP_Error('forbidden', 'Unauthorized', array('status' => 403));
    }

    return true; // Access is allowed
}

// Define and register REST API routes with dynamic base URL
function register_apisearch_rest_routes()
{

    // Register a custom REST API route for sending products to Apisearch
    register_rest_route('apisearch', '/feed', array(
        'methods' => 'GET',
        'callback' => 'get_apisearch_feed',
        'permission_callback' => 'apisearch_permission_callback',
    ));

    // Register a custom REST API route for checking if the plugin is working
    register_rest_route('apisearch', '/check', array(
        'methods' => 'GET',
        'callback' => 'check_plugin_status',
        'permission_callback' => 'apisearch_permission_callback',
    ));

    // Register a custom REST API route for setting the index_id
    register_rest_route('apisearch', '/set-index-id', array(
        'methods' => 'PUT',
        'callback' => 'set_index_id_callback',
        'permission_callback' => 'apisearch_permission_callback',
        'args' => array(
            'auth_uuid' => array(
                'sanitize_callback' => 'sanitize_text_field',
            ),
        ),
    ));
}

add_action('rest_api_init', 'register_apisearch_rest_routes');