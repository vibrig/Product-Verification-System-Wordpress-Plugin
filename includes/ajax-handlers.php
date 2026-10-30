<?php
if (!defined('ABSPATH')) {
    exit;
}

function pcvs_verify_code() {
    if (!isset($_POST['pcvs_nonce']) || !is_string($_POST['pcvs_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['pcvs_nonce'])), 'pcvs_verify_action')) {
        wp_send_json_error(array('message' => __('Security check failed.', 'product-code-verification-system')));
    }

    $code = isset($_POST['code']) && is_string($_POST['code']) ? trim(sanitize_text_field(wp_unslash($_POST['code']))) : '';
    if ($code === '') {
        wp_send_json_error(array('message' => __('No code provided.', 'product-code-verification-system')));
    }

    $ip = sanitize_text_field($_SERVER['REMOTE_ADDR'] ?? '');
    
    $args = ['post_type' => 'product_code', 'posts_per_page' => -1, 'post_status' => 'publish'];
    $query = new WP_Query($args);
    $found_post_id = null;

    if ($query->have_posts()) {
        while ($query->have_posts()) {
            $query->the_post();
            $post_codes = get_post_meta(get_the_ID(), 'product_codes', true) ?: [];
            if (is_array($post_codes) && in_array($code, $post_codes, true)) {
                $found_post_id = get_the_ID();
                break;
            }
        }
        wp_reset_postdata();
    }

    pcvs_log_verification($code, $found_post_id ? 'genuine' : 'invalid', $ip);

    if ($found_post_id) {
        $fields = pcvs_get_display_fields();
        $details = array();
        
        $image_id = !empty($fields['product_image']['visible']) ? get_post_meta($found_post_id, 'product_image', true) : 0;
        $image_url = $image_id ? wp_get_attachment_image_url($image_id, 'medium') : '';

        $certificate_id = !empty($fields['product_certificate']['visible']) ? get_post_meta($found_post_id, 'product_certificate', true) : 0;
        $certificate_url = $certificate_id ? wp_get_attachment_url($certificate_id) : '';

        $bill_id = !empty($fields['product_bill']['visible']) ? get_post_meta($found_post_id, 'product_bill', true) : 0;
        $bill_url = $bill_id ? wp_get_attachment_url($bill_id) : '';

        foreach ($fields as $field) {
            if (empty($field['visible']) || in_array($field['key'], ['product_image', 'product_certificate', 'product_bill'])) {
                continue;
            }

            $value = '';
            switch ($field['key']) {
                case 'product_name':
                    $value = get_the_title($found_post_id);
                    break;
                case 'code':
                    $value = $code;
                    break;
                case 'project_id':
                    $project_id = pcvs_valid_relation_id(get_post_meta($found_post_id, 'project_id', true), 'pcvs_project');
                    $value = $project_id ? get_the_title($project_id) : '';
                    break;
                case 'client_id':
                    $client_id = pcvs_product_client_id($found_post_id);
                    $value = $client_id ? get_the_title($client_id) : '';
                    break;
                case 'warranty_status':
                    $expiry = get_post_meta($found_post_id, 'expiry_date', true);
                    $value = $expiry ? ((strtotime($expiry) > current_time('timestamp')) ? __('Active', 'product-code-verification-system') : __('Expired', 'product-code-verification-system')) : __('N/A', 'product-code-verification-system');
                    break;
                default:
                    $value = get_post_meta($found_post_id, $field['key'], true);
                    break;
            }
            
            if (!empty($value)) {
                $details[] = array(
                    'label' => $field['key'] === 'warranty_status' ? __('Validity status', 'product-code-verification-system') : $field['label'],
                    'key'   => $field['key'],
                    'value' => $value,
                );
            }
        }
        
        $message = get_option('pcvs_genuine_message', __('This product is genuine!', 'product-code-verification-system'));
        wp_send_json_success(array(
            'message' => $message,
            'image_url' => $image_url,
            'certificate_url' => $certificate_url,
            'bill_url' => $bill_url,
            'details' => $details
        ));

    } else {
        $message = get_option('pcvs_invalid_message', __('Code not found.', 'product-code-verification-system'));
        $admin_email = get_option('pcvs_admin_email', get_option('admin_email'));
        $settings = pcvs_get_advanced_settings();
        if ($admin_email && !empty($settings['email_alerts'])) {
            $subject = __('Invalid Code Attempt', 'product-code-verification-system');
            $body = sprintf(__('An invalid code "%s" was attempted on %s from IP address %s', 'product-code-verification-system'), $code, current_time('Y-m-d H:i:s'), $ip);
            wp_mail($admin_email, $subject, $body);
        }
        wp_send_json_error(array('message' => $message));
    }
}
add_action('wp_ajax_pcvs_verify_code', 'pcvs_verify_code');
add_action('wp_ajax_nopriv_pcvs_verify_code', 'pcvs_verify_code');
