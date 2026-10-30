<?php
if (!defined('ABSPATH')) {
    exit;
}

function pcvs_bulk_upload_page_callback() {
    ?>
    <div class="wrap pcvs-bulk-upload-wrap">
        <h1><?php esc_html_e('Bulk Upload Product Codes', 'product-code-verification-system'); ?></h1>
        <p><?php esc_html_e('Upload a CSV file with product codes. Format: product_name,code1,code2,... (multiple codes separated by commas).', 'product-code-verification-system'); ?></p>
        <form method="post" enctype="multipart/form-data">
            <?php wp_nonce_field('pcvs_bulk_upload_action', 'pcvs_bulk_upload_nonce'); ?>
            <input type="file" name="pcvs_csv_file" accept=".csv" required>
            <input type="submit" value="<?php esc_attr_e('Upload CSV', 'product-code-verification-system'); ?>" class="button button-primary">
        </form>
    </div>
    <?php

    if (isset($_POST['pcvs_bulk_upload_nonce']) && wp_verify_nonce($_POST['pcvs_bulk_upload_nonce'], 'pcvs_bulk_upload_action')) {
        if (isset($_FILES['pcvs_csv_file']) && $_FILES['pcvs_csv_file']['error'] === UPLOAD_ERR_OK) {
            $file = $_FILES['pcvs_csv_file']['tmp_name'];
            if (($handle = fopen($file, 'r')) !== false) {
                $all_codes = pcvs_get_all_product_codes();
                $duplicates = 0;
                $row = 1;
                while (($data = fgetcsv($handle)) !== false) {
                    if ($row === 1) { // Skip header if present
                        $row++;
                        continue;
                    }
                    $product_name = sanitize_text_field($data[0]);
                    $codes_str = isset($data[1]) ? $data[1] : '';
                    $codes = array_map('sanitize_text_field', explode(',', $codes_str));
                    $codes = array_filter(array_unique($codes));
                    $unique_codes = array_diff($codes, $all_codes);
                    $duplicates += count($codes) - count($unique_codes);
                    $all_codes = array_merge($all_codes, $unique_codes);
                    $post_id = wp_insert_post(array(
                        'post_title' => $product_name,
                        'post_type' => 'product_code',
                        'post_status' => 'publish',
                    ));
                    if (!is_wp_error($post_id)) {
                        update_post_meta($post_id, 'product_codes', $unique_codes);
                    }
                    $row++;
                }
                fclose($handle);
                echo '<div class="updated"><p>' . esc_html__('Uploaded successfully! Duplicates skipped: ', 'product-code-verification-system') . esc_html($duplicates) . '</p></div>';
            }
        }
    }
}

function pcvs_get_all_product_codes($exclude_post_id = 0) {
    $args = array(
        'post_type' => 'product_code',
        'posts_per_page' => -1,
        'post__not_in' => array($exclude_post_id),
    );
    $products = get_posts($args);
    $all_codes = array();
    foreach ($products as $product) {
        $codes = get_post_meta($product->ID, 'product_codes', true);
        if (is_array($codes)) {
            $all_codes = array_merge($all_codes, $codes);
        }
    }
    return array_unique($all_codes);
}