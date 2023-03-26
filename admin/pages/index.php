<?php
// Enqueue scripts and styles
wp_enqueue_style('phpp-css', plugins_url('css/style.css', dirname(__FILE__)));
wp_enqueue_script('phpp-monaco-js', plugins_url('js/editor/min/vs/loader.js', dirname(__FILE__)));
wp_enqueue_script('phpp-cookies-js', plugins_url('js/cookies.js', dirname(__FILE__)));
wp_enqueue_script('phpp-viewer-js', plugins_url('js/viewer.js', dirname(__FILE__)));
wp_enqueue_script('phpp-fetch-js', plugins_url('js/fetch.js', dirname(__FILE__)));
wp_enqueue_script('phpp-customPHP-js', plugins_url('js/customPHP.js', dirname(__FILE__)));
wp_enqueue_script('phpp-main-js', plugins_url('js/main.js', dirname(__FILE__)));

$phpp_params = array(
    'execute_url' => plugins_url('execute.php', __FILE__),
    'uncaught_text' => __('Uncaught', 'phpp'),
    'request_error_text' => __('Request Error:', 'phpp'),
    'execution_timeout_text' => __('Execution time exceeded timeout.', 'phpp'),
);

wp_localize_script('phpp-fetch-js', 'phpp_params', $phpp_params);

// Check user has the proper capabilities
if (!current_user_can('manage_options')) {
    echo '<div class="notice notice-error"><p>' . esc_html__('This plugin is intended for administration usage only.', 'phpp') . '</p></div>';
    exit(401);
}

?>
<img src="<?php echo esc_url(includes_url() . 'js/thickbox/loadingAnimation.gif'); ?>" id="loading" />
<div class="wrap">
    <h1 class="wp-heading-inline"><?= get_admin_page_title(); ?></h1>
    <br>
    <div class="notice notice-warning is-dismissible">
        <p><strong><?php _e('Caution: The usage of this plugin could lead to harm the site. Use it under your own risk.', 'phpp'); ?></p>
    </div>

    <div class="view">
        <button class="button button-primary selected" id="view-h"><img src="<?= plugins_url('img/horizontal-view.png', dirname(__FILE__)) ?>" /></button>
        <button class="button button-primary" id="view-v"><img src="<?= plugins_url('img/vertical-view.png', dirname(__FILE__)) ?>" /></button>
    </div>

    <div class="row">
        <div class="col">
            <form method="POST" id="php-playground-form">
                <div id="editor" style="height: 600px;"></div>
                <input type="hidden" name="code" id="code" />
                <br>
                <button type="submit" class="button button-primary"><?php _e('Run', 'phpp') ?></button>
            </form>
        </div>
        <div class="col">
            <h3 class="wp-heading-inline"><?php _e('Result:', 'phpp'); ?></h3>
            <pre id="result"></pre>
            <table class="metrics">
                <tbody>
                    <tr>
                        <th><?php _e('Execution Time:', 'phpp'); ?></th>
                        <td id="exectime">N/A</td>
                    </tr>
                    <tr>
                        <th><?php _e('Output Length:', 'phpp'); ?></th>
                        <td id="buffer-length">N/A</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>
<script>
    document.querySelectorAll('.view button').forEach(button => {
        button.addEventListener('click', function() {
            changeDirection(this)
        })
    });

    document.addEventListener("DOMContentLoaded", () => {
        const editorBaseUrl = '<?= esc_url(plugins_url('js/editor/min', dirname(__FILE__))); ?>';
        const workerMainUrl = '<?= esc_url(plugins_url('js/editor/min/vs/base/worker/workerMain.js', dirname(__FILE__))); ?>';
        const formNonce = '<?= wp_create_nonce('phpp_nonce'); ?>';

        initMonacoEditor(editorBaseUrl, workerMainUrl, formNonce);

        setInitialView();
    })
</script>