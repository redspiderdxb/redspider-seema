<?php
/**
 * RedSpider Child Theme Functions
 *
 * @package RedSpiderChild
 */

if (!defined('ABSPATH')) {
  exit;
}

function redspider_child_enqueue_styles() {
    // Load parent styles first, then child theme stylesheet
    wp_enqueue_style( 'parent-style', get_template_directory_uri() . '/style.css' );
    wp_enqueue_style( 'child-style', get_stylesheet_uri(), array('parent-style', 'custom-css'), wp_get_theme()->get('Version') );
}
add_action( 'wp_enqueue_scripts', 'redspider_child_enqueue_styles', 20 );
