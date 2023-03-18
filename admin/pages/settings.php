<?php
wp_enqueue_style('phpp-css', plugins_url('css/style.css', dirname(__FILE__)));
wp_enqueue_script('phpp-cookies-js', plugins_url('js/cookies.js', dirname(__FILE__)));
?>

<div class="wrap">
    <h1 class="wp-heading-inline"><?= get_admin_page_title(); ?></h1>
    <br><br><br>
    <div class="notice notice-info">
        <p><strong><?php _e('These settings won\'t affect your WordPress PHP settings.', 'phpp'); ?></p>
    </div>

    <form method="POST" action="" id="settings-form">
        <div class="form-field">
            <label for="theme"><?php _e('Theme:', 'phpp'); ?></label>
            <select id="theme" name="theme">
                <option value="vs-dark">Dark</option>
                <option value="vs">Light</option>
            </select>
        </div>

        <div class="form-field">
            <label for="timeout"><?php _e('Max. Execution Time (milliseconds):', 'phpp'); ?></label>
            <input type="number" id="timeout" name="timeout" step="1000" />
        </div>

        <button type="submit" class="button button-primary"><?php _e('Apply changes', 'phpp'); ?></button>
    </form>
</div>

<script>
    document.addEventListener('DOMContentLoaded', e => {
        let theme = getCookie('wp-phpp-theme');
        let timeout = getCookie('wp-phpp-timeout');

        if (theme === null) {
            theme = 'twilight';
        }

        if (timeout === null) {
            timeout = 8000
        }

        document.getElementById('theme').value = theme;
        document.getElementById('timeout').value = timeout;
    });

    document.getElementById('settings-form').addEventListener('submit', e => {
        const theme = e.target.theme.value;
        const timeout = e.target.timeout.value;

        setCookie('wp-phpp-theme', theme, 7);
        setCookie('wp-phpp-timeout', timeout, 7);
    })
</script>