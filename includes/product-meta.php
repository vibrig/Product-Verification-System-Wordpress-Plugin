<?php
if (!defined('ABSPATH')) {
    exit;
}

function pcvs_add_product_meta_box() {
    add_meta_box(
        'pcvs_product_details',
        __('Product Details & Codes', 'product-code-verification-system'),
        'pcvs_product_meta_box_callback',
        'product_code',
        'normal',
        'high'
    );
}
add_action('add_meta_boxes', 'pcvs_add_product_meta_box');

function pcvs_product_meta_box_callback($post) {
    wp_nonce_field('pcvs_product_meta_save', 'pcvs_product_nonce');
    
    $fields = pcvs_get_display_fields();

    $meta = get_post_meta($post->ID);
    ?>
    <table class="form-table pcvs-form-table">
        <tr>
            <th><label for="pcvs-item-type"><?php esc_html_e('Item type', 'product-code-verification-system'); ?></label></th>
            <td><select id="pcvs-item-type" name="pcvs_item_type">
                <option value="product" <?php selected(get_post_meta($post->ID, 'pcvs_item_type', true), 'product'); ?>><?php esc_html_e('Product', 'product-code-verification-system'); ?></option>
                <option value="service" <?php selected(get_post_meta($post->ID, 'pcvs_item_type', true), 'service'); ?>><?php esc_html_e('Service', 'product-code-verification-system'); ?></option>
            </select></td>
        </tr>
        <?php foreach ($fields as $field) : ?>
            <?php
            if (in_array($field['key'], ['product_name', 'code', 'warranty_status'])) {
                continue;
            }

            $meta_value = isset($meta[$field['key']][0]) ? $meta[$field['key']][0] : '';

            if (in_array($field['key'], array('project_id', 'client_id'), true)) :
                $is_project = $field['key'] === 'project_id';
                $project_id = pcvs_valid_relation_id(get_post_meta($post->ID, 'project_id', true), 'pcvs_project');
                $inherited_client = pcvs_project_client_id($project_id);
                $selected_id = $is_project ? $project_id : pcvs_product_client_id($post->ID);
                ?>
                <tr>
                    <th><label for="pcvs-<?php echo esc_attr($field['key']); ?>"><?php echo esc_html($field['label']); ?></label></th>
                    <td>
                        <?php pcvs_render_relation_select($field['key'], $is_project ? 'pcvs_project' : 'pcvs_client', $selected_id, !$is_project && $inherited_client > 0); ?>
                        <?php if ($is_project) : ?>
                            <p class="description"><?php esc_html_e('Select a published project. One project can contain many products or services.', 'product-code-verification-system'); ?></p>
                        <?php else : ?>
                            <p class="description" id="pcvs-client-inherited" <?php if (!$inherited_client) echo 'hidden'; ?>><?php esc_html_e('Client is assigned automatically from the project. Change the client on the project to update all its items.', 'product-code-verification-system'); ?></p>
                            <p class="description" id="pcvs-client-manual" <?php if ($inherited_client) echo 'hidden'; ?>><?php esc_html_e('Select a client. This project has no client assigned, or no project is selected.', 'product-code-verification-system'); ?></p>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php elseif ($field['key'] === 'product_image') : ?>
                 <tr>
                    <th><label for="product_image"><?php echo esc_html($field['label']); ?></label></th>
                    <td>
                        <input type="hidden" id="product_image" name="pcvs_meta[product_image]" value="<?php echo esc_attr($meta_value); ?>" class="pcvs-input">
                        <button type="button" class="button pcvs-upload-image-button"><?php esc_html_e('Upload/Select Image', 'product-code-verification-system'); ?></button>
                        <div class="pcvs-image-preview">
                            <?php if ($meta_value && $image_html = wp_get_attachment_image($meta_value, 'medium')) : ?>
                                <?php echo $image_html; ?>
                                <p><a href="#" class="pcvs-remove-image-button" style="color: #a00;">(Remove)</a></p>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
            <?php elseif ($field['key'] === 'product_certificate') : ?>
                <tr>
                    <th><label for="product_certificate"><?php echo esc_html($field['label']); ?></label></th>
                    <td>
                        <input type="hidden" id="product_certificate" name="pcvs_meta[product_certificate]" value="<?php echo esc_attr($meta_value); ?>" class="pcvs-input">
                        <button type="button" class="button pcvs-upload-cert-button"><?php esc_html_e('Upload/Select File', 'product-code-verification-system'); ?></button>
                        <div class="pcvs-cert-preview">
                            <?php if ($meta_value && ($file_url = wp_get_attachment_url($meta_value))) : 
                                $file_name = get_the_title($meta_value) ?: basename($file_url);
                                echo '<p><a href="' . esc_url($file_url) . '" target="_blank">' . esc_html($file_name) . '</a> <a href="#" class="pcvs-remove-cert-button" style="color: #a00; text-decoration: none; margin-left: 10px;">(Remove)</a></p>';
                            endif; ?>
                        </div>
                    </td>
                </tr>
            <?php elseif ($field['key'] === 'product_bill') : ?>
                <tr>
                    <th><label for="product_bill"><?php echo esc_html($field['label']); ?></label></th>
                    <td>
                        <input type="hidden" id="product_bill" name="pcvs_meta[product_bill]" value="<?php echo esc_attr($meta_value); ?>" class="pcvs-input">
                        <button type="button" class="button pcvs-upload-bill-button"><?php esc_html_e('Upload/Select File', 'product-code-verification-system'); ?></button>
                        <div class="pcvs-bill-preview">
                             <?php if ($meta_value && ($file_url = wp_get_attachment_url($meta_value))) : 
                                $file_name = get_the_title($meta_value) ?: basename($file_url);
                                echo '<p><a href="' . esc_url($file_url) . '" target="_blank">' . esc_html($file_name) . '</a> <a href="#" class="pcvs-remove-bill-button" style="color: #a00; text-decoration: none; margin-left: 10px;">(Remove)</a></p>';
                            endif; ?>
                        </div>
                    </td>
                </tr>
            <?php elseif ($field['key'] === 'product_description') : ?>
                <tr>
                    <th><label for="<?php echo esc_attr($field['key']); ?>"><?php echo esc_html($field['label']); ?></label></th>
                    <td><textarea id="<?php echo esc_attr($field['key']); ?>" name="pcvs_meta[<?php echo esc_attr($field['key']); ?>]" rows="4" class="large-text pcvs-input"><?php echo esc_textarea($meta_value); ?></textarea></td>
                </tr>
            <?php else : 
                $classes = 'regular-text pcvs-input';
                if (in_array($field['key'], ['mfg_date', 'expiry_date'])) {
                    $classes .= ' pcvs-date-picker';
                }
            ?>
                 <tr>
                    <th><label for="<?php echo esc_attr($field['key']); ?>"><?php echo esc_html($field['label']); ?></label></th>
                    <td><input type="text" id="<?php echo esc_attr($field['key']); ?>" name="pcvs_meta[<?php echo esc_attr($field['key']); ?>]" value="<?php echo esc_attr($meta_value); ?>" class="<?php echo esc_attr($classes); ?>" autocomplete="off"></td>
                </tr>
            <?php endif; ?>
        <?php endforeach; ?>
        
        <tr>
            <th><?php esc_html_e('Product Codes', 'product-code-verification-system'); ?></th>
            <td>
                <?php $codes = get_post_meta($post->ID, 'product_codes', true); ?>
                <ul id="pcvs-code-list" class="pcvs-code-list">
                    <?php if (!empty($codes) && is_array($codes)) : foreach ($codes as $code) : ?>
                        <li class="pcvs-code-item">
                            <input type="text" name="product_codes[]" value="<?php echo esc_attr($code); ?>" class="regular-text pcvs-input">
                            <button type="button" class="button pcvs-remove-code"><?php esc_html_e('Remove', 'product-code-verification-system'); ?></button>
                        </li>
                    <?php endforeach; else: ?>
                         <li class="pcvs-code-item">
                            <input type="text" name="product_codes[]" class="regular-text pcvs-input">
                            <button type="button" class="button pcvs-remove-code"><?php esc_html_e('Remove', 'product-code-verification-system'); ?></button>
                        </li>
                    <?php endif; ?>
                </ul>
                <p>
                    <button type="button" id="pcvs-generate-code" class="button"><?php esc_html_e('Generate Random Code', 'product-code-verification-system'); ?></button>
                </p>
            </td>
        </tr>
    </table>
    <?php
}

function pcvs_save_product_details($post_id) {
    if (!isset($_POST['pcvs_product_nonce']) || !is_string($_POST['pcvs_product_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['pcvs_product_nonce'])), 'pcvs_product_meta_save')) return;
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
    if (!current_user_can('edit_post', $post_id)) return;
    if (get_post_type($post_id) !== 'product_code') return;

    update_post_meta($post_id, 'pcvs_item_type', isset($_POST['pcvs_item_type']) && $_POST['pcvs_item_type'] === 'service' ? 'service' : 'product');

    if (isset($_POST['pcvs_meta']) && is_array($_POST['pcvs_meta'])) {
        foreach ($_POST['pcvs_meta'] as $key => $value) {
            if (in_array($key, array('project_id', 'client_id'), true) || !is_string($value)) continue;
            update_post_meta($post_id, sanitize_key($key), sanitize_textarea_field(wp_unslash($value)));
        }
        $project_id = pcvs_valid_relation_id($_POST['pcvs_meta']['project_id'] ?? 0, 'pcvs_project');
        $client_id = pcvs_valid_relation_id($_POST['pcvs_meta']['client_id'] ?? 0, 'pcvs_client');
        update_post_meta($post_id, 'project_id', $project_id);
        // Inherited clients are resolved dynamically, so project edits apply immediately.
        update_post_meta($post_id, 'client_id', pcvs_project_client_id($project_id) ? 0 : $client_id);
    }

    if (isset($_POST['product_codes'])) {
        $codes = is_array($_POST['product_codes']) ? array_map('sanitize_text_field', wp_unslash($_POST['product_codes'])) : [];
        $codes = array_filter(array_unique($codes));
        update_post_meta($post_id, 'product_codes', $codes);
    } else {
        delete_post_meta($post_id, 'product_codes');
    }
}
add_action('save_post', 'pcvs_save_product_details');
