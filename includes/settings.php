<?php
if (!defined('ABSPATH')) {
    exit;
}

function pcvs_get_advanced_defaults() {
    return array(
        'form_title' => __('Verify your product / documents', 'product-code-verification-system'),
        'form_description' => __('Enter the unique code found on your product/document to verify its authenticity.', 'product-code-verification-system'),
        'button_text' => __('Verify Now', 'product-code-verification-system'),
        'email_alerts' => true,
        'enable_logs' => false,
        'log_ip' => false,
        'log_retention_days' => 30,
    );
}

function pcvs_get_advanced_settings() {
    $saved = get_option('pcvs_advanced_settings', array());
    return array_merge(pcvs_get_advanced_defaults(), is_array($saved) ? $saved : array());
}

function pcvs_sanitize_advanced_settings($input) {
    $defaults = pcvs_get_advanced_defaults();
    $settings = array();
    foreach (array('form_title', 'form_description', 'button_text') as $key) {
        $value = isset($input[$key]) && is_string($input[$key]) ? $input[$key] : '';
        $value = $key === 'form_description' ? sanitize_textarea_field($value) : sanitize_text_field($value);
        $settings[$key] = trim($value) !== '' ? $value : $defaults[$key];
    }
    foreach (array('email_alerts', 'enable_logs', 'log_ip') as $key) {
        $settings[$key] = !empty($input[$key]);
    }
    $settings['log_retention_days'] = max(1, min(365, absint($input['log_retention_days'] ?? 30)));
    return $settings;
}

function pcvs_render_advanced_settings() {
    $settings = pcvs_get_advanced_settings();
    ?>
    <h2><?php esc_html_e('Verification Form', 'product-code-verification-system'); ?></h2>
    <table class="form-table" role="presentation">
        <?php foreach (array(
            'form_title' => __('Heading', 'product-code-verification-system'),
            'form_description' => __('Description', 'product-code-verification-system'),
            'button_text' => __('Button text', 'product-code-verification-system'),
        ) as $key => $label) : ?>
            <tr>
                <th scope="row"><label for="pcvs-<?php echo esc_attr($key); ?>"><?php echo esc_html($label); ?></label></th>
                <td>
                    <?php if ($key === 'form_description') : ?>
                        <textarea id="pcvs-<?php echo esc_attr($key); ?>" name="pcvs_advanced[<?php echo esc_attr($key); ?>]" rows="3" class="large-text"><?php echo esc_textarea($settings[$key]); ?></textarea>
                    <?php else : ?>
                        <input id="pcvs-<?php echo esc_attr($key); ?>" name="pcvs_advanced[<?php echo esc_attr($key); ?>]" value="<?php echo esc_attr($settings[$key]); ?>" class="regular-text" type="text">
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
    </table>
    <h2><?php esc_html_e('Advanced Options', 'product-code-verification-system'); ?></h2>
    <table class="form-table" role="presentation">
        <?php foreach (array(
            'email_alerts' => __('Send email alerts for invalid codes', 'product-code-verification-system'),
            'enable_logs' => __('Record verification history', 'product-code-verification-system'),
            'log_ip' => __('Include visitor IP addresses in new logs', 'product-code-verification-system'),
        ) as $key => $label) : ?>
            <tr>
                <th scope="row"><?php echo esc_html($label); ?></th>
                <td><label><input type="checkbox" name="pcvs_advanced[<?php echo esc_attr($key); ?>]" value="1" <?php checked(!empty($settings[$key])); ?>> <?php esc_html_e('Enable', 'product-code-verification-system'); ?></label></td>
            </tr>
        <?php endforeach; ?>
        <tr>
            <th scope="row"><label for="pcvs-log-retention"><?php esc_html_e('Log retention (days)', 'product-code-verification-system'); ?></label></th>
            <td>
                <input id="pcvs-log-retention" type="number" name="pcvs_advanced[log_retention_days]" min="1" max="365" value="<?php echo esc_attr($settings['log_retention_days']); ?>" class="small-text">
                <p class="description"><?php esc_html_e('Keep logs for 1–365 days, up to 5,000 entries. Older entries are removed daily and when logs are viewed or recorded. Disabling history stops new entries; existing logs remain until retention expires or you clear them.', 'product-code-verification-system'); ?></p>
            </td>
        </tr>
    </table>
    <?php
}
