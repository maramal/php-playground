initMonacoEditor = function (editorBaseUrl, workerMainUrl, formNonce) {
    const element = document.querySelector("#editor")
    const theme = getCookie("wp-phpp-theme") || "vs-dark"

    const requireConfig = {
        paths: {
            "vs": editorBaseUrl + '/vs'
        }
    };

    window.MonacoEnvironment = {
        getWorkerUrl: () => proxy
    };

    let proxy = URL.createObjectURL(new Blob([`
        self.MonacoEnvironment = {
            baseUrl: '${editorBaseUrl}'
        };
        importScripts('${workerMainUrl}');
    `], {
        type: 'text/javascript'
    }));

    window.require.config(requireConfig);

    window.require(["vs/editor/editor.main"], () => {
        const model = createCustomPhpModel('', monaco);
        editor = monaco.editor.create(element, {
            model: model,
            theme: theme
        });

        document.getElementById("php-playground-form").addEventListener("submit", function (e) {
            e.preventDefault();
            const loadingElement = document.getElementById('loading');
            const buttonElement = document.querySelector('button[type="submit"]')
            loadingElement.style.display = 'block';
            buttonElement.setAttribute('disabled', true)

            const data = {
                data: editor.getValue(),
                form_nonce: formNonce
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

                        if (data.output.includes('<b>Parse error</b>')) {
                            let $data = data.output.split('\n');
                            $data = $data[1].replace(/ in (<b>.*code<\/b>)/, '');
                            data.output = $data;
                        }

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
                    console.log(err);
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
}