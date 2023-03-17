<?php

/*
Plugin Name: PHP Playground
Plugin URI: https://mafer.dev/plugins/php-playground
Description: This plugin is intended for administrators to run PHP code in a single environment without having to create snippets. For instance, this could be useful for testing/debugging purposes.
Version: 1.0
Author: Martín Fernández
Author URI: https://mafer.dev/
Text Domain: phpp
License: GPL2
*/

// Plugin Activation
function phpp_activate() {

}

function phpp_deactivate() {
    flush_rewrite_rules();
}

register_activation_hook( __FILE__, 'phpp_activate' );
register_activation_hook( __FILE__, 'phpp_deactivate' );

// Internationalization
function phpp_load_textdomain() {
    $lang_path = dirname( plugin_basename( __FILE__ ) ) . '/languages';
    $loaded = load_plugin_textdomain('phpp', false, $lang_path);
    if ( $loaded ) {
        error_log( 'Textdomain loaded successfully.' );
    } else {
        error_log( 'Textdomain not loaded.' );
    }
}

add_action( 'init', 'phpp_load_textdomain' );

// Admin Menu

add_action( 'admin_menu', 'create_menu' );

function create_menu() {
    add_menu_page(
        'PHP Playground',
        'PHP Playground',
        'manage_options',
        'phpp_index',
        'phpp_index_page',
        plugin_dir_url( __FILE__ ) . 'admin/img/logo.png',
        null
    );

    add_submenu_page(
        'phpp_index',
        'Settings',
        __('Settings', 'phpp'),
        'manage_options',
        'phpp_settings',
        'phpp_settings_page'  
    );
}

function phpp_index_page() {
    include_once( plugin_dir_path( __FILE__ ) . 'admin/pages/index.php' );
}

function phpp_settings_page() {
    include_once( plugin_dir_path( __FILE__ ) . 'admin/pages/settings.php' );
}

// Plugin List Links
add_filter( 'plugin_action_links', 'phpp_settings_link' );
function phpp_settings_link( $links ) {
    // Build and escape URL
    $url = esc_url(
        add_query_arg(
            'page',
            'phpp_settings',
            get_admin_url() . 'admin.php'
        )
    );

    // Create the link
    $settings_link = "<a href='$url'>" . __('Settings', 'phpp') . '</a>';

    // Adds the link to the end of the array.
    array_push( $links, $settings_link );

    return $links;
}