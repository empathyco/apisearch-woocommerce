<?php

// Enqueue FontAwesome icons in your plugin
function enqueue_font_awesome() {
    wp_enqueue_style('font-awesome', 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css');
}

add_action('wp_enqueue_scripts', 'enqueue_font_awesome');


