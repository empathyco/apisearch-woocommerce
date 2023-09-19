<?php


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
//    $category_tree = get_product_category_tree($product->get_id());

    // Get product tags
    $tags = wp_get_post_terms($product->get_id(), 'product_tag', array('fields' => 'names'));

    // Get product creation date
    $creation_date = get_post_field('post_date', $product->get_id());

    // Convert creation date to Unix timestamp
    $creation_timestamp = strtotime($creation_date);

    $index_short_descriptions = get_option('index_short_descriptions');

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
        'creation_datetime' => $creation_timestamp, // Add creation datetime in Unix timestamp format

        // Add more fields as needed
    );

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
            'old_price_with_currency' => $woocommerce_product['regular_price'] . ' €',
            'price_with_currency' => $woocommerce_product['sale_price'] . ' €',
            'show_price' => true,
            'supplier_reference' => [],
        ),
        'indexed_metadata' => array(
            'as_version' => mt_rand(1000, 9999),
            'price' => (float)$woocommerce_product['sale_price'],
            'categories' => $categories,
            'reference' => $woocommerce_product['sku'],
            "date_add" => $woocommerce_product['creation_datetime'],
        ),
        'searchable_metadata' => array(
            'name' => (string)$woocommerce_product['title'],
            'categories' => $categories,
            'tags' => $tags,
        ),
        'suggest' => $categories,
        'exact_matching_metadata' => array(
            $woocommerce_product['sku']
        ),
    );

    if ($index_short_descriptions) {
        $apisearch_product['metadata']['short_description'] = $woocommerce_product['short_description'];
    }

    return $apisearch_product;
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