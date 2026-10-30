<?php
if (!defined('ABSPATH')) {
    exit;
}
// Enqueue jQuery UI Sortable for the settings page
function pcvs_settings_page_scripts($hook) {
    if ($hook === 'product_code_page_pcvs-settings') {
        wp_enqueue_script('jquery-ui-sortable');
    }
}
add_action('admin_enqueue_scripts', 'pcvs_settings_page_scripts');
function pcvs_get_default_fields() {
    return array(
        'product_name' => [
            'key' => 'product_name',
            'label' => __('Product Name', 'product-code-verification-system'),
            'visible' => true,
            'core' => true, // Cannot be removed
        ],
        'code' => [
            'key' => 'code',
            'label' => __('Verified Code', 'product-code-verification-system'),
            'visible' => true,
            'core' => true, // Cannot be removed
        ],
        'project_id' => [
            'key' => 'project_id',
            'label' => __('Project', 'product-code-verification-system'),
            'visible' => true,
            'core' => true,
        ],
        'client_id' => [
            'key' => 'client_id',
            'label' => __('Client', 'product-code-verification-system'),
            'visible' => true,
            'core' => true,
        ],
        'product_description' => [
            'key' => 'product_description',
            'label' => __('Description', 'product-code-verification-system'),
            'visible' => true,
            'core' => false, // Can be removed
        ],
        'batch_number' => [
            'key' => 'batch_number',
            'label' => __('Batch/Series No.', 'product-code-verification-system'),
            'visible' => true,
            'core' => false, // Can be removed
        ],
        'mfg_date' => [
            'key' => 'mfg_date',
            'label' => __('Manufacturing Date', 'product-code-verification-system'),
            'visible' => true,
            'core' => false, // Can be removed
        ],
        'expiry_date' => [
            'key' => 'expiry_date',
            'label' => __('Expiry Date', 'product-code-verification-system'),
            'visible' => true,
            'core' => false, // Can be removed
        ],
        'warranty_status' => [
            'key' => 'warranty_status',
            'label' => __('Validity status', 'product-code-verification-system'),
            'visible' => true,
            'core' => true, // Cannot be removed
        ],
        'product_image' => [
            'key' => 'product_image',
            'label' => __('Product Image', 'product-code-verification-system'),
            'visible' => true,
            'core' => false, // Can be removed
        ],
        'product_certificate' => [
            'key' => 'product_certificate',
            'label' => __('Certificate File', 'product-code-verification-system'),
            'visible' => true,
            'core' => false, // Can be removed
        ],
        'product_bill' => [
            'key' => 'product_bill',
            'label' => __('Bill / Invoice File', 'product-code-verification-system'),
            'visible' => true,
            'core' => false, // Can be removed
        ],
    );
}
// Use the same field order and visibility in settings, editing, and verification.
function pcvs_get_display_fields() {
    $defaults = pcvs_get_default_fields();
    $saved = get_option('pcvs_fields', false);
    if (!is_array($saved)) {
        return $defaults;
    }
    $fields = $saved;
    foreach ($defaults as $key => $field) {
        if (isset($fields[$key])) {
            $fields[$key]['core'] = $field['core'];
        } elseif (!empty($field['core'])) {
            // Add new built-in fields on upgrade without restoring removed optional fields.
            $fields[$key] = $field;
        }
    }
    $fields['warranty_status']['label'] = $defaults['warranty_status']['label'];
    return $fields;
}
function pcvs_add_admin_menus() {
    add_submenu_page(
        'edit.php?post_type=product_code',
        __('Verification Logs', 'product-code-verification-system'),
        __('Verification Logs', 'product-code-verification-system'),
        'manage_options',
        'pcvs-logs',
        'pcvs_logs_page_callback'
    );
    add_submenu_page(
        'edit.php?post_type=product_code',
        __('Settings', 'product-code-verification-system'),
        __('Settings', 'product-code-verification-system'),
        'manage_options',
        'pcvs-settings',
        'pcvs_settings_page_callback'
    );
    add_submenu_page(
        'edit.php?post_type=product_code',
        __('How to Use', 'product-code-verification-system'),
        __('How to Use', 'product-code-verification-system'),
        'manage_options',
        'pcvs-how-to-use',
        'pcvs_how_to_use_page_callback'
    );
}
add_action('admin_menu', 'pcvs_add_admin_menus');
function pcvs_settings_page_callback() {
    if (!current_user_can('manage_options')) {
        wp_die(esc_html__('Unauthorized.', 'product-code-verification-system'));
    }
    $tab = isset($_GET['tab']) && $_GET['tab'] === 'fields' ? 'fields' : 'general';
    if (isset($_POST['pcvs_settings_nonce']) && is_string($_POST['pcvs_settings_nonce']) && wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['pcvs_settings_nonce'])), 'pcvs_settings_action')) {
        if ($tab === 'general') {
        update_option('pcvs_genuine_message', sanitize_textarea_field(wp_unslash($_POST['genuine_message'])));
        update_option('pcvs_invalid_message', sanitize_textarea_field(wp_unslash($_POST['invalid_message'])));
        update_option('pcvs_admin_email', sanitize_email(wp_unslash($_POST['admin_email'])));
        $advanced_input = isset($_POST['pcvs_advanced']) && is_array($_POST['pcvs_advanced']) ? wp_unslash($_POST['pcvs_advanced']) : array();
        update_option('pcvs_advanced_settings', pcvs_sanitize_advanced_settings($advanced_input));
        pcvs_save_update_settings();
        } else {
        $fields_data = isset($_POST['pcvs_fields']) ? wp_unslash($_POST['pcvs_fields']) : array();
        $sanitized_fields = array();
        if (!empty($fields_data['key'])) {
            foreach ($fields_data['key'] as $index => $key) {
                if (empty($key)) continue;
                $sanitized_key = sanitize_text_field($key);
                $sanitized_fields[$sanitized_key] = array(
                    'key' => $sanitized_key,
                    'label' => $sanitized_key === 'warranty_status' ? __('Validity status', 'product-code-verification-system') : sanitize_text_field($fields_data['label'][$index]),
                    'visible' => isset($fields_data['visible'][$sanitized_key]),
                    'core' => ($fields_data['core'][$index] == '1'),
                );
            }
        }
        update_option('pcvs_fields', $sanitized_fields);
        }
        add_settings_error('pcvs_messages', 'settings_updated', __('Settings saved successfully!', 'product-code-verification-system'), 'success');
    }
    $genuine_message = get_option('pcvs_genuine_message', __('This product is genuine!', 'product-code-verification-system'));
    $invalid_message = get_option('pcvs_invalid_message', __('Code not found.', 'product-code-verification-system'));
    $admin_email = get_option('pcvs_admin_email', get_option('admin_email'));
   
    $fields = pcvs_get_display_fields();
    ?>
    <div class="wrap">
        <h1><?php esc_html_e('Product Verification System Settings', 'product-code-verification-system'); ?></h1>
        <p><?php esc_html_e('Owner: Vibrig', 'product-code-verification-system'); ?> &middot; <a href="https://vibrig.com/" target="_blank" rel="noopener noreferrer">vibrig.com</a></p>
        <?php settings_errors('pcvs_messages'); ?>
        <nav class="nav-tab-wrapper" aria-label="<?php esc_attr_e('Settings sections', 'product-code-verification-system'); ?>">
            <a class="nav-tab <?php echo $tab === 'general' ? 'nav-tab-active' : ''; ?>" href="<?php echo esc_url(admin_url('edit.php?post_type=product_code&page=pcvs-settings&tab=general')); ?>" <?php if ($tab === 'general') echo 'aria-current="page"'; ?>><?php esc_html_e('General Settings', 'product-code-verification-system'); ?></a>
            <a class="nav-tab <?php echo $tab === 'fields' ? 'nav-tab-active' : ''; ?>" href="<?php echo esc_url(admin_url('edit.php?post_type=product_code&page=pcvs-settings&tab=fields')); ?>" <?php if ($tab === 'fields') echo 'aria-current="page"'; ?>><?php esc_html_e('Field Settings', 'product-code-verification-system'); ?></a>
        </nav>
        <form method="post" action="">
            <?php wp_nonce_field('pcvs_settings_action', 'pcvs_settings_nonce'); ?>
            <?php if ($tab === 'general') : ?>
            <h2><?php esc_html_e('General Settings', 'product-code-verification-system'); ?></h2>
            <?php pcvs_render_advanced_settings(); ?>
           
            <h2 class="title"><?php esc_html_e('Messages & Alerts', 'product-code-verification-system'); ?></h2>
            <table class="form-table">
                 <tr>
                    <th scope="row"><label for="genuine_message"><?php esc_html_e('Genuine Message', 'product-code-verification-system'); ?></label></th>
                    <td><textarea id="genuine_message" name="genuine_message" rows="3" cols="50" class="large-text"><?php echo esc_textarea($genuine_message); ?></textarea></td>
                </tr>
                <tr>
                    <th scope="row"><label for="invalid_message"><?php esc_html_e('Invalid Message', 'product-code-verification-system'); ?></label></th>
                    <td><textarea id="invalid_message" name="invalid_message" rows="3" cols="50" class="large-text"><?php echo esc_textarea($invalid_message); ?></textarea></td>
                </tr>
                <tr>
                    <th scope="row"><label for="admin_email"><?php esc_html_e('Admin Email for Alerts', 'product-code-verification-system'); ?></label></th>
                    <td><input type="email" id="admin_email" name="admin_email" value="<?php echo esc_attr($admin_email); ?>" class="regular-text"></td>
                </tr>
            </table>
            <?php pcvs_render_update_settings(); ?>
            <?php else : ?>
            <h2><?php esc_html_e('Field Settings', 'product-code-verification-system'); ?></h2>
            <h2 class="title"><?php esc_html_e('Frontend Display Fields', 'product-code-verification-system'); ?></h2>
            <p class="description"><?php esc_html_e('Configure the fields that appear on the frontend after a successful verification. Drag to reorder.', 'product-code-verification-system'); ?></p>
            <div id="pcvs-fields-manager">
                <div class="fields-header">
                    <div class="col-drag"></div>
                    <div class="col-label"><b><?php esc_html_e('Field Label', 'product-code-verification-system'); ?></b></div>
                    <div class="col-key"><b><?php esc_html_e('Field Key', 'product-code-verification-system'); ?></b></div>
                    <div class="col-visible"><b><?php esc_html_e('Visible', 'product-code-verification-system'); ?></b></div>
                    <div class="col-actions"><b><?php esc_html_e('Actions', 'product-code-verification-system'); ?></b></div>
                </div>
                <div id="pcvs-fields-container">
                    <?php foreach ($fields as $field_key => $field) : ?>
                        <div class="pcvs-field-row <?php echo !empty($field['core']) ? 'is-core' : ''; ?>">
                            <input type="hidden" name="pcvs_fields[key][]" value="<?php echo esc_attr($field['key']); ?>">
                            <input type="hidden" name="pcvs_fields[core][]" value="<?php echo !empty($field['core']) ? '1' : '0'; ?>">
                            <div class="col-drag"><span class="dashicons dashicons-menu"></span></div>
                            <div class="col-label"><input type="text" name="pcvs_fields[label][]" value="<?php echo esc_attr($field['label']); ?>" <?php echo !empty($field['core']) ? 'readonly' : ''; ?>></div>
                            <div class="col-key"><code><?php echo esc_html($field['key']); ?></code></div>
                            <div class="col-visible"><input type="checkbox" name="pcvs_fields[visible][<?php echo esc_attr($field_key); ?>]" value="1" <?php checked(!empty($field['visible'])); ?>></div>
                            <div class="col-actions">
                                <?php if (empty($field['core'])) : ?>
                                    <button type="button" class="button pcvs-remove-field"><?php esc_html_e('Remove', 'product-code-verification-system'); ?></button>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                <button type="button" id="pcvs-add-field" class="button"><?php esc_html_e('Add Custom Field', 'product-code-verification-system'); ?></button>
            </div>
            <?php endif; ?>
           
            <?php submit_button(); ?>
        </form>
    </div>
    <div id="pcvs-field-template" style="display: none;">
        <div class="pcvs-field-row">
            <input type="hidden" name="pcvs_fields[key][]" value="%%KEY%%">
            <input type="hidden" name="pcvs_fields[core][]" value="0">
            <div class="col-drag"><span class="dashicons dashicons-menu"></span></div>
            <div class="col-label"><input type="text" name="pcvs_fields[label][]" value="%%LABEL%%"></div>
            <div class="col-key"><code>%%KEY%%</code></div>
            <div class="col-visible"><input type="checkbox" name="pcvs_fields[visible][%%KEY%%]" value="1" checked></div>
            <div class="col-actions"><button type="button" class="button pcvs-remove-field"><?php esc_html_e('Remove', 'product-code-verification-system'); ?></button></div>
        </div>
    </div>
    <style>
        #pcvs-fields-manager { background: #fff; border: 1px solid #ccd0d4; padding: 20px; border-radius: 4px; margin-top: 20px; }
        .pcvs-field-row, .fields-header { display: flex; align-items: center; gap: 10px; padding: 10px; border-bottom: 1px solid #f0f0f1; }
        .pcvs-field-row.is-core { background-color: #f8f9fa; }
        .pcvs-field-row.is-core .col-label input { font-weight: bold; background: #e9ecef; }
        .col-drag { flex: 0 0 30px; cursor: move; color: #787c82; text-align: center;}
        .col-drag .dashicons-menu { vertical-align: middle; }
        .col-label { flex: 1; }
        .col-label input { width: 100%; }
        .col-key { flex: 1; color: #50575e; }
        .col-visible { flex: 0 0 60px; text-align: center; }
        .col-actions { flex: 0 0 80px; }
        .ui-sortable-placeholder { height: 40px; background: #f0f6ff; border: 1px dashed #0073aa; visibility: visible !important; }
    </style>
    <script>
        jQuery(document).ready(function($) {
            $('#pcvs-fields-container').sortable({ handle: '.col-drag', placeholder: 'ui-sortable-placeholder', forcePlaceholderSize: true });
           
            $('#pcvs-add-field').on('click', function() {
                var label = window.prompt('<?php echo esc_js( __('Enter a label for the new field (e.g., Distributor Name)', 'product-code-verification-system') ); ?>');
                if (!label || label.trim() === '') return;
                var key = 'custom_' + label.trim().toLowerCase().replace(/[^a-z0-9]+/g, '_').replace(/^_|_$/g, '');
                var template = $('#pcvs-field-template').html();
                var newRow = template.replace(/%%LABEL%%/g, label).replace(/%%KEY%%/g, key);
                $('#pcvs-fields-container').append(newRow);
            });
            $(document).on('click', '.pcvs-remove-field', function() {
                if (window.confirm('<?php echo esc_js( __('Are you sure you want to remove this field?', 'product-code-verification-system') ); ?>')) {
                    $(this).closest('.pcvs-field-row').remove();
                }
            });
        });
    </script>
    <?php
}
