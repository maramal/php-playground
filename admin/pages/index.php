<?php
// Enqueue scripts and styles
wp_enqueue_style('phpp-css', plugins_url('css/style.css', dirname(__FILE__)));
wp_enqueue_script('phpp-monaco-js', 'https://unpkg.com/monaco-editor@0.36.1/min/vs/loader.js', array(), null);
wp_enqueue_script('phpp-cookies-js', plugins_url('js/cookies.js', dirname(__FILE__)));
wp_enqueue_script('phpp-viewer-js', plugins_url('js/viewer.js', dirname(__FILE__)));
wp_enqueue_script('phpp-fetch-js', plugins_url('js/fetch.js', dirname(__FILE__)));
wp_enqueue_script('phpp-customPHP-languageDef-js', plugins_url('js/customPHP.languageDef.js', dirname(__FILE__)));

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

// Add nonce field
$nonce_field = wp_nonce_field('php_playground_nonce', '_wpnonce', true, false);

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
                <p id="phpinit">&lt;?php</p>
                <div id="editor" style="height: 600px;"></div>
                <input type="hidden" name="code" id="code" />
                <?= $nonce_field; ?>
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
        setInitialView();
        const element = document.querySelector("#editor")
        const theme = getCookie("wp-phpp-theme") || "vs-dark"

        const requireConfig = {
            paths: {
                "vs": "https://unpkg.com/monaco-editor@0.36.1/min/vs"
            }
        };

        window.MonacoEnvironment = {
            getWorkerUrl: () => proxy
        };

        let proxy = URL.createObjectURL(new Blob([`
    self.MonacoEnvironment = {
        baseUrl: 'https://unpkg.com/monaco-editor@0.36.1/min/'
    };
    importScripts('https://unpkg.com/monaco-editor@0.36.1/min/vs/base/worker/workerMain.js');
`], {
            type: 'text/javascript'
        }));

        window.require.config(requireConfig);

        window.require(["vs/editor/editor.main"], () => {

            // PHP Syntax
            monaco.languages.register({
                id: 'customPHP'
            });

            monaco.languages.setMonarchTokensProvider('customPHP', customPHPLanguageDef);
            // <-- PHP Syntax

            const editor = monaco.editor.create(element, {
                value: "",
                language: "customPHP",
                theme,
                automaticLayout: true
            });

            editor.getModel().onDidChangeContent(() => {
                document.querySelector("#code").value = editor.getValue()
            })

            document.getElementById("php-playground-form").addEventListener("submit", function(e) {
                e.preventDefault();
                const loadingElement = document.getElementById('loading');
                const buttonElement = document.querySelector('button[type="submit"]')
                loadingElement.style.display = 'block';
                buttonElement.setAttribute('disabled', true)

                const data = {
                    data: editor.getValue()
                };

                const options = {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(data)
                }

                fetchWithTimeout(phpp_params.execute_url, options)
                    .then(response => response.text())
                    .then(output => {
                        try {
                            // Parse JSON Response (OK)
                            const data = JSON.parse(output);
                            document.getElementById("result").innerHTML = data.output;
                            document.getElementById("exectime").innerHTML = data.execution_time;
                            document.getElementById("buffer-length").innerHTML = data.buffer_length;
                        } catch (err) {
                            let $output = output;

                            if (!$output.includes('<script>')) {
                                const splittedOutput = $output.split('<br />');
                                if (splittedOutput.length > 1) {
                                    $output = splittedOutput[1].replace(
                                        / in (.*) eval\(\)'d code/,
                                        ""
                                    );
                                } else {
                                    $output = splittedOutput;
                                }
                            }

                            document.getElementById("result").innerHTML = $output;
                            document.getElementById("exectime").innerHTML = phpp_params.uncaught_text;
                            document.getElementById("buffer-length").innerHTML = phpp_params.uncaught_text;
                        }
                    })
                    .catch(err => {
                        let message = err.message;

                        if (err.name === 'AbortError') {
                            message = phpp_params.execution_timeout_text;
                        }

                        document.getElementById("result").innerHTML = `${phpp_params.request_error_text} ${message}`;
                        document.getElementById("exectime").innerHTML = phpp_params.uncaught_text;
                        document.getElementById("buffer-length").innerHTML = phpp_params.uncaught_text;
                    })
                    .finally(() => {
                        loadingElement.style.display = 'none';
                        buttonElement.removeAttribute('disabled')
                    })
            });
        })
    })
</script>