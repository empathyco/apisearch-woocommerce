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

// Define and register REST API routes with dynamic base URL
function register_apisearch_rest_routes()
{
    // Register a custom REST API route for sending products to Apisearch
    register_rest_route('apisearch', '/feed', array(
        'methods' => 'GET',
        'callback' => 'get_apisearch_feed'
    ));

    // Register a custom REST API route for checking if the plugin is working
    register_rest_route('apisearch', '/check', array(
        'methods' => 'GET',
        'callback' => 'check_plugin_status'
    ));
}

add_action('rest_api_init', 'register_apisearch_rest_routes');