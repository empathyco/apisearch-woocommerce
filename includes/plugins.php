<?php

require_once __DIR__ . '/plugins/ApisearchPlugin.php';
require_once __DIR__ . '/plugins/ApisearchPlugin.php';

global $apisearchPlugins;
$apisearchPlugins = array();

$list = array(
    'ApisearchPerfectBrandsPlugin' => __DIR__ . '/plugins/ApisearchPerfectBrandsPlugin.php'
);

foreach ($list as $className => $filepath) {
    require_once($filepath);
    $instance = new $className();
    if ($instance->isPluginActive()) {
        $instance->preload();
        $apisearchPlugins[] = $instance;
    }
}


function apisearch_complement_product($product)
{
    global $apisearchPlugins;
    foreach ($apisearchPlugins as $plugin) {
        $product = $plugin->complementProduct($product);
    }

    return $product;
}

