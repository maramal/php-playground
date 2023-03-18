<?php

$path = $_SERVER['DOCUMENT_ROOT'];
require_once $path . '/wp-load.php';

require WP_PLUGIN_DIR . '/php-playground/vendor/autoload.php';

$output = [];
$data = json_decode( file_get_contents( "php://input" ), true );

// Remove E_WARNING from output
error_reporting(E_ALL ^ E_WARNING);
ini_set('display_errors', true);

if ( $data['data'] ) {
    ob_start();
    $start_time = microtime(1);
    
    // Execute client's code
    eval( $data['data'] );

    // Generate response
    $output['output'] = ob_get_contents();
    $output['execution_time'] = number_format(microtime(1) - $start_time, 2, '.', '') * 100 . 'ms';
    $output['buffer_length'] = number_format((float)ob_get_length(), 0, ',', '.') . 'B';
    ob_end_clean();

    // Return response as Pretty JSON
    echo json_encode($output, JSON_PRETTY_PRINT);
} 