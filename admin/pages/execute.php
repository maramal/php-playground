<?php
$path = $_SERVER['DOCUMENT_ROOT'];
require_once $path . '/wp-load.php';

// Check if user is allowed to execute this file
if (!current_user_can('manage_options')) {
    status_header(403);
    echo 'Forbidden';
    exit;
}

require WP_PLUGIN_DIR . '/php-playground/vendor/autoload.php';

$output = [];
$data = json_decode(file_get_contents("php://input"), true);

// Check if the nonce is valid
if (!isset($data['form_nonce']) || !wp_verify_nonce($data['form_nonce'], 'phpp_nonce')) {
    status_header(403);
    echo 'Invalid nonce';
    exit;
}

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

function str_replace_first($search, $replace, $subject) {
    return implode($replace, explode($search, $subject, 2));
}

if ($data['data']) {
    ob_start('output_handler');
    $start_time = microtime(1);

    try {
        // Execute client's code
        if (strpos($data['data'], '<?php') !== false) {
            $data = str_replace_first('<?php', '', $data['data']);
        } else {
            $data = '?>'.$data['data'];
        }

        eval($data);
    } catch (\Exception $e) {
        $output['output'] = 'N/A';
    }

    ob_end_flush();
}
