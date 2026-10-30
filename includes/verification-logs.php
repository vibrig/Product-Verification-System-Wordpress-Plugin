<?php
if (!defined('ABSPATH')) {
    exit;
}

function pcvs_logs_page_callback() {
    if (!current_user_can('manage_options')) {
        wp_die(esc_html__('Unauthorized.', 'product-code-verification-system'));
    }
    // Display a success message after clearing logs
    if (isset($_GET['logs_cleared']) && $_GET['logs_cleared'] === 'true') {
        echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__('All verification logs have been cleared.', 'product-code-verification-system') . '</p></div>';
    }

    $logs = pcvs_get_verification_logs();
    $settings = pcvs_get_advanced_settings();
    $per_page = 20;
    $paged = max(1, isset($_GET['paged']) ? absint($_GET['paged']) : 1);
    $total = count($logs);
    $offset = ($paged - 1) * $per_page;
    $logs_page = array_slice(array_reverse($logs), $offset, $per_page);
    ?>
    <div class="wrap">
        <h1><?php esc_html_e('Verification Logs', 'product-code-verification-system'); ?></h1>
        <p><?php echo !empty($settings['enable_logs']) ? esc_html__('Verification history is enabled.', 'product-code-verification-system') : esc_html__('Verification history is disabled. Enable it in Settings to record new attempts.', 'product-code-verification-system'); ?></p>
        <p><?php printf(esc_html__('Total logs: %d', 'product-code-verification-system'), $total); ?></p>
        
        <div class="pcvs-log-actions">
            <a href="<?php echo esc_url(wp_nonce_url(admin_url('admin-post.php?action=pcvs_export_logs'), 'pcvs_export_logs_nonce', 'export_nonce')); ?>" class="button button-primary"><?php esc_html_e('Export All Logs as CSV', 'product-code-verification-system'); ?></a>
            
            <?php if ($total > 0) : ?>
                <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                    <input type="hidden" name="action" value="pcvs_clear_logs">
                    <?php wp_nonce_field('pcvs_clear_logs_nonce'); ?>
                    <button type="submit" class="button pcvs-delete-logs-btn" onclick="return confirm('<?php echo esc_js(__('Are you sure you want to permanently delete all logs? This cannot be undone.', 'product-code-verification-system')); ?>');"><?php esc_html_e('Clear All Logs', 'product-code-verification-system'); ?></button>
                </form>
            <?php endif; ?>
        </div>

        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th><?php esc_html_e('Time', 'product-code-verification-system'); ?></th>
                    <th><?php esc_html_e('Code', 'product-code-verification-system'); ?></th>
                    <th><?php esc_html_e('Result', 'product-code-verification-system'); ?></th>
                    <th><?php esc_html_e('IP Address', 'product-code-verification-system'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($logs_page)) : ?>
                    <tr><td colspan="4"><?php esc_html_e('No logs found.', 'product-code-verification-system'); ?></td></tr>
                <?php else : ?>
                    <?php foreach ($logs_page as $log) : ?>
                        <tr>
                            <td><?php echo esc_html($log['time']); ?></td>
                            <td><?php echo esc_html($log['code']); ?></td>
                            <td class="pcvs-log-<?php echo esc_attr($log['result']); ?>"><?php echo $log['result'] === 'genuine' ? esc_html__('Genuine', 'product-code-verification-system') : esc_html__('Invalid', 'product-code-verification-system'); ?></td>
                            <td><?php echo esc_html($log['ip']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
        <?php
        $pagination = paginate_links(array(
            'base' => add_query_arg('paged', '%#%'),
            'format' => '',
            'prev_text' => '&laquo;',
            'next_text' => '&raquo;',
            'total' => ceil($total / $per_page),
            'current' => $paged,
        ));
        if ($pagination) {
            echo '<div class="tablenav bottom"><div class="tablenav-pages">' . $pagination . '</div></div>';
        }
        ?>
    </div>
    <style>
        .pcvs-log-genuine { color: #17613d; font-weight: bold; }
        .pcvs-log-invalid { color: #a12736; font-weight: bold; }
        .pcvs-log-actions { margin: 15px 0; display: flex; gap: 10px; }
        .pcvs-delete-logs-btn, .pcvs-delete-logs-btn:hover {
             background: #dc3545;
             border-color: #dc3545;
             color: #fff;
        }
    </style>
    <?php
}

function pcvs_get_verification_logs() {
    $stored = get_option('pcvs_verification_logs', array());
    // Apply retention after an administrator has saved the new options.
    // This preserves history from older installations until configuration.
    if (get_option('pcvs_advanced_settings', false) === false) {
        return is_array($stored) ? $stored : array();
    }
    $settings = pcvs_get_advanced_settings();
    $cutoff = time() - max(1, min(365, (int) $settings['log_retention_days'])) * DAY_IN_SECONDS;
    $logs = array();
    foreach (is_array($stored) ? $stored : array() as $log) {
        if (!is_array($log) || !isset($log['time'], $log['code'], $log['result'], $log['ip'])) {
            continue;
        }
        try {
            $timestamp = isset($log['timestamp']) ? (int) $log['timestamp'] : (new DateTimeImmutable($log['time'], wp_timezone()))->getTimestamp();
        } catch (Exception $exception) {
            continue;
        }
        if ($timestamp >= $cutoff) {
            $logs[] = $log;
        }
    }
    $logs = array_slice($logs, -5000);
    if ($stored !== $logs) {
        update_option('pcvs_verification_logs', $logs, false);
    }
    return $logs;
}

function pcvs_schedule_log_cleanup() {
    if (get_option('pcvs_advanced_settings', false) === false) {
        return;
    }
    if (!wp_next_scheduled('pcvs_cleanup_verification_logs')) {
        wp_schedule_event(time() + DAY_IN_SECONDS, 'daily', 'pcvs_cleanup_verification_logs');
    }
}
add_action('init', 'pcvs_schedule_log_cleanup');
add_action('pcvs_cleanup_verification_logs', 'pcvs_get_verification_logs');

function pcvs_deactivate() {
    wp_clear_scheduled_hook('pcvs_cleanup_verification_logs');
}
register_deactivation_hook(PCVS_PLUGIN_DIR . 'product-code-verification-system.php', 'pcvs_deactivate');

function pcvs_log_verification($code, $result, $ip = '') {
    $settings = pcvs_get_advanced_settings();
    if (empty($settings['enable_logs'])) {
        return;
    }
    $logs = pcvs_get_verification_logs();
    $log_entry = array(
        'time' => current_time('Y-m-d H:i:s'),
        'timestamp' => time(),
        'code' => $code,
        'result' => $result,
        'ip' => !empty($settings['log_ip']) ? $ip : '',
    );
    $logs[] = $log_entry;
    if (count($logs) > 5000) {
        $logs = array_slice($logs, -5000);
    }
    update_option('pcvs_verification_logs', $logs, false);
}

function pcvs_csv_safe_cell($value) {
    $value = (string) $value;
    // Prevent user-supplied codes from becoming spreadsheet formulas on export.
    return preg_match('/^[\s]*[=+@-]/u', $value) || preg_match('/^[\t\r\n]/', $value) ? "'" . $value : $value;
}

function pcvs_export_logs() {
    if (!current_user_can('manage_options') || !isset($_GET['export_nonce']) || !is_string($_GET['export_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_GET['export_nonce'])), 'pcvs_export_logs_nonce')) {
        wp_die(__('Unauthorized.', 'product-code-verification-system'));
    }
    $logs = pcvs_get_verification_logs();
    nocache_headers();
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="pcvs-logs-' . current_time('Y-m-d-H-i-s') . '.csv"');
    $output = fopen('php://output', 'w');
    fputcsv($output, array('Time', 'Code', 'Result', 'IP'));
    if (!empty($logs)) {
        foreach (array_reverse($logs) as $log) {
            fputcsv($output, array_map('pcvs_csv_safe_cell', array($log['time'], $log['code'], $log['result'], $log['ip'])));
        }
    }
    fclose($output);
    exit;
}
add_action('admin_post_pcvs_export_logs', 'pcvs_export_logs');

function pcvs_clear_logs_action() {
    if (!current_user_can('manage_options') || !isset($_POST['_wpnonce']) || !is_string($_POST['_wpnonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['_wpnonce'])), 'pcvs_clear_logs_nonce')) {
        wp_die(__('Security check failed.', 'product-code-verification-system'));
    }

    delete_option('pcvs_verification_logs');

    $redirect_url = admin_url('edit.php?post_type=product_code&page=pcvs-logs&logs_cleared=true');
    wp_safe_redirect($redirect_url);
    exit;
}
add_action('admin_post_pcvs_clear_logs', 'pcvs_clear_logs_action');
