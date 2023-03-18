<?php
wp_enqueue_style('phpp-css', plugins_url('css/style.css', dirname(__FILE__)));
wp_enqueue_script('phpp-monaco-js', 'https://unpkg.com/monaco-editor@0.36.1/min/vs/loader.js', array(), null);
wp_enqueue_script('phpp-cookies-js', plugins_url('js/cookies.js', dirname(__FILE__)));

// Check user is administrator
$user_id = get_current_user_id();
$user = new WP_User($user_id);
if ($user->roles[0] !== 'administrator') {
    echo '<div class="notice notice-error"><p>' . _e('This plugin is intended for administration usage only.', 'phpp') . '</p></div>';
    exit(401);
}

?>

<div class="wrap">
    <h1 class="wp-heading-inline"><?= get_admin_page_title(); ?></h1>
    <br>
    <div class="notice notice-warning is-dismissible">
        <p><strong><?php _e('Caution: The usage of this plugin could lead to harm the site. Use it under your own risk.', 'phpp'); ?></p>
    </div>

    <form method="POST" id="php-playground-form">
        <p id="phpinit">&lt;?php</p>
        <div id="editor" style="height: 600px;"></div>
        <input type="hidden" name="code" id="code" />
        <br>
        <button type="submit" class="button button-primary"><?php _e('Run', 'phpp') ?></button>
        <img src="<?php echo esc_url(includes_url() . 'js/thickbox/loadingAnimation.gif'); ?>" id="loading" />

        <h3 class="wp-heading-inline"><?php _e('Result:', 'phpp'); ?></h3>
        <pre id="result"></pre>
        <table class="exectime">
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
    </form>
</div>

<script>
    document.addEventListener("DOMContentLoaded", () => {
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

            const editor = monaco.editor.create(element, {
                value: "",
                language: "php",
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

                fetchWithTimeout('<?= plugins_url('execute.php', __FILE__) ?>', options)
                    .then(response => response.text())
                    .then(output => {
                        try {
                            const data = JSON.parse(output);
                            document.getElementById("result").innerHTML = data.output;
                            document.getElementById("exectime").innerHTML = data.execution_time;
                            document.getElementById("buffer-length").innerHTML = data.buffer_length;
                        } catch (err) {
                            let $output = output;

                            if (!$output.includes('<script>')) {
                                const splittedOutput = $output.split('<br />');
                                $output = splittedOutput[1];
                                if ($output) {
                                    $output = $output.replace(
                                        / in (.*) eval\(\)'d code/,
                                        ""
                                    );
                                }
                            }

                            document.getElementById("result").innerHTML = $output;
                            document.getElementById("exectime").innerHTML = '<?php _e('Uncaught', 'phpp'); ?>';
                            document.getElementById("buffer-length").innerHTML = '<?php _e('Uncaught', 'phpp'); ?>';
                        }
                    })
                    .catch(err => {
                        let message = err.message;

                        if (err.name === 'AbortError') {
                            message = '<?php _e('Execution time exceeded timeout.', 'phpp'); ?>';
                        }

                        document.getElementById("result").innerHTML = `<?php _e('<b>Request Error:</b>', 'phpp'); ?> ${message}`;
                        document.getElementById("exectime").innerHTML = '<?php _e('Uncaught', 'phpp'); ?>';
                        document.getElementById("buffer-length").innerHTML = '<?php _e('Uncaught', 'phpp'); ?>';
                    })
                    .finally(() => {
                        loadingElement.style.display = 'none';
                        buttonElement.removeAttribute('disabled')
                    })
            });
        })

        async function fetchWithTimeout(resource, options = {}) {
            const defaultTimeout = getCookie('wp-phpp-timeout') || 8000;
            const {
                timeout = defaultTimeout
            } = options

            const controller = new AbortController()
            const id = setTimeout(() => controller.abort(), timeout)

            const response = await fetch(resource, {
                ...options,
                signal: controller.signal
            })

            clearTimeout(id)

            return response
        }
    })
</script>