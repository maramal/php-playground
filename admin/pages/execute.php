<?php
$path = $_SERVER['DOCUMENT_ROOT'];
require_once $path . '/wp-load.php';

// Check if user is allowed to execute this file
if (!current_user_can('manage_options')) {
    status_header(403);
    echo 'Forbidden';
    exit;
}

// Check if the nonce is valid
if (!isset($_POST['phpp_nonce']) || !wp_verify_nonce($_POST['phpp_nonce'], 'phpp_execute_code')) {
    status_header(403);
    echo 'Invalid nonce';
    exit;
}

require WP_PLUGIN_DIR . '/php-playground/vendor/autoload.php';

$output = [];
$data = json_decode(file_get_contents("php://input"), true);

// Remove E_WARNING from output
error_reporting(E_ALL ^ E_WARNING);
ini_set('display_errors', true);

// Custom output buffer handler
function output_handler($buffer)
{
    global $output;
    global $start_time;

    $output['output'] = $buffer;
    $output['execution_time'] = number_format(microtime(1) - $start_time, 2, '.', '') * 100 . 'ms';
    $output['buffer_length'] = number_format((float)strlen($buffer), 0, ',', '.') . 'B';

    return json_encode($output, JSON_PRETTY_PRINT);
}

if ($data['data']) {
    ob_start('output_handler');
    $start_time = microtime(1);

    try {
        // Execute client's code
        eval($data['data']);
    } catch (\Exception $e) {
        $output['output'] = 'N/A';
    }

    ob_end_flush();
}
