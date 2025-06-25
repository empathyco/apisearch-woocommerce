<?php

class ApisearchPerfectBrandsPlugin implements ApisearchPlugin
{
    public function isPluginActive()
    {
        return in_array('perfect-woocommerce-brands/perfect-woocommerce-brands.php', get_option('active_plugins'));
    }

    public function preload()
    {}

    public function complementProduct(array $product)
    {
        $terms = get_the_terms($product['uuid']['id'], 'pwb-brand');
        if ($terms && !is_wp_error($terms)) {
            $brands = array_map(function($term) {
                return esc_html($term->name);
            }, $terms);

            $brands = array_unique($brands);
            $brands = array_filter($brands);
            $brands = array_values($brands);
            $product['indexed_metadata']['brand'] = $brands;
            $product['searchable_metadata']['brand'] = $brands;
        }

        return $product;
    }
}