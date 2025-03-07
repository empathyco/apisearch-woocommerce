<?php

interface ApisearchPlugin
{
    public function isPluginActive();
    public function preload();
    public function complementProduct(array $product);
}