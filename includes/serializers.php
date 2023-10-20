<?php


function clean_list_apisearch($list) {
    if (!is_array($list)) {
        return $list;
    }

    return array_values(array_unique(array_filter($list)));
}

/**
 * Serialize a WooCommerce product to Apisearch format.
 *
 * @param WC_Product $product The WooCommerce product to be serialized.
 * @return array The product data in Apisearch format.
 */
function serialize_product_for_apisearch($product)
{
    $categories = wp_get_post_terms($product->get_id(), 'product_cat', array('fields' => 'names'));
    $tags = clean_list_apisearch(wp_get_post_terms($product->get_id(), 'product_tag', array('fields' => 'names')));
    $creation_date = get_post_field('post_date', $product->get_id());
    $creation_timestamp = strtotime($creation_date);
    $index_short_descriptions = get_option('index_short_descriptions');
    $index_descriptions = get_option('index_description');

    $woocommerce_product = array(
        'id' => $product->get_id(),
        'title' => $product->get_title(),
        'description' => $product->get_description(),
        'short_description' => $product->get_short_description(),
        'image' => wp_get_attachment_url($product->get_image_id()),
        'regular_price' => \round(\floatval($product->get_regular_price()), 2),
        'sale_price' => \round(\floatval($product->get_sale_price())),
        'categories' => $categories,
        'sku' => $product->get_sku(),
        'product_url' => get_permalink($product->get_id()),
        'product_type' => $product->get_type(),
        'product_attributes' => $product->get_attributes(),
        'tags' => $tags,
        'creation_datetime' => $creation_timestamp, // Add creation datetime in Unix timestamp format
    );

    $currency_code = get_woocommerce_currency();
    $currency_symbol = get_currency_symbol($currency_code);

    $apisearch_product = array(
        'uuid' => array(
            "id" => (string)$woocommerce_product['id'],
            "type" => "product"
        ),
        'metadata' => array(
            'title' => (string)$woocommerce_product['title'],
            'url' => $woocommerce_product['product_url'],
            'image_id' => $product->get_image_id(),
            'image' => $woocommerce_product['image'],
            'old_price' => $woocommerce_product['regular_price'],
            'old_price_with_currency' => $woocommerce_product['regular_price'] . ' ' . $currency_symbol,
            'price' => $woocommerce_product['sale_price'],
            'price_with_currency' => $woocommerce_product['sale_price'] . ' ' . $currency_symbol,
            'show_price' => true,
            'supplier_reference' => [],
        ),
        'indexed_metadata' => array(
            'as_version' => mt_rand(1000, 9999),
            'price' => $woocommerce_product['sale_price'],
            'with_discount' => $woocommerce_product['regular_price'] - $woocommerce_product['sale_price'] > 0,
            'categories' => $categories,
            'product_type' => $woocommerce_product['product_type'],
            'reference' => $woocommerce_product['sku'],
            "date_add" => $woocommerce_product['creation_datetime'],
        ),
        'searchable_metadata' => array(
            'name' => (string)$woocommerce_product['title'],
            'categories' => $categories,
            'tags' => $woocommerce_product['tags'],
        ),
        'suggest' => $categories,
        'exact_matching_metadata' => clean_list_apisearch(array(
            $woocommerce_product['sku'],
            $woocommerce_product['reference']
        )),
    );


    // prices of variations
    if ($product->is_type('variable')) {
        $variations = $product->get_available_variations();

        // sort by price so we can get in and max
        usort($variations, function($a, $b) {
            $price_a = wc_get_product($a['variation_id'])->get_price();
            $price_b = wc_get_product($b['variation_id'])->get_price();
            return $price_a - $price_b;
        });

        if (!empty($variations)) {
            // First variation has the minimum price
            $min_price = wc_get_product($variations[0]['variation_id'])->get_price();

            // Last variation has the maximum price
            $max_price = wc_get_product(end($variations)['variation_id'])->get_price();

            $apisearch_product['metadata']['min_price'] = $min_price;
            $apisearch_product['metadata']['min_price_with_currency'] = $min_price . ' ' . $currency_symbol;
            $apisearch_product['metadata']['max_price'] = $max_price;
            $apisearch_product['metadata']['max_price_with_currency'] = $max_price . ' ' . $currency_symbol;
        }
    }

    if ($index_descriptions) {
        $apisearch_product['searchable_metadata']['description'] = $woocommerce_product['description'];
    }

    // short description
    if ($index_short_descriptions) {
        $apisearch_product['searchable_metadata']['short_description'] = $woocommerce_product['short_description'];
    }

    return $apisearch_product;
}

function get_currency_symbol($currency_code) {
    $currency_symbols = array(
        'USD' => '$',
        'EUR' => '€',
        'GBP' => '£',
        // Add more currency codes and symbols as needed
    );

    return isset($currency_symbols[$currency_code]) ? $currency_symbols[$currency_code] : '';
}

function get_product_category_tree($product_id)
{
    // Get product categories with parent categories
    $categories = wp_get_post_terms($product_id, 'product_cat', array('fields' => 'all'));

    // Initialize an empty array to store the category tree
    $category_tree = array();

    // Create a recursive function to build the category tree
    function build_category_tree($category, $categories)
    {
        $category_item = array(
            'id' => $category->term_id,
            'name' => $category->name,
            'slug' => $category->slug,
            'parent' => $category->parent,
        );

        foreach ($categories as $key => $cat) {
            if ($cat->parent == $category->term_id) {
                $category_item['children'][] = build_category_tree($cat, $categories);
                unset($categories[$key]); // Remove the category to avoid duplicate processing
            }
        }

        return $category_item;
    }

    // Find top-level categories (categories without parents)
    foreach ($categories as $key => $category) {
        if ($category->parent == 0) {
            $category_tree[] = build_category_tree($category, $categories);
            unset($categories[$key]); // Remove the category to avoid duplicate processing
        }
    }

    return $category_tree;
}