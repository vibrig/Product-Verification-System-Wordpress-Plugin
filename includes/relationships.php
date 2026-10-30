<?php
if (!defined('ABSPATH')) {
    exit;
}

function pcvs_register_relationship_types() {
    foreach (array(
        'pcvs_project' => array('Projects', 'Project', 'Add New Project', 'Edit Project'),
        'pcvs_client' => array('Clients', 'Client', 'Add New Client', 'Edit Client'),
    ) as $type => $names) {
        register_post_type($type, array(
            'labels' => array(
                'name' => __($names[0], 'product-code-verification-system'),
                'singular_name' => __($names[1], 'product-code-verification-system'),
                'menu_name' => __($names[0], 'product-code-verification-system'),
                'all_items' => __($names[0], 'product-code-verification-system'),
                'add_new_item' => __($names[2], 'product-code-verification-system'),
                'edit_item' => __($names[3], 'product-code-verification-system'),
            ),
            'public' => false,
            'publicly_queryable' => false,
            'exclude_from_search' => true,
            'show_ui' => true,
            'show_in_menu' => 'edit.php?post_type=product_code',
            'show_in_rest' => false,
            'supports' => array('title'),
            'capability_type' => 'post',
            'map_meta_cap' => true,
        ));
    }
}
add_action('init', 'pcvs_register_relationship_types');

function pcvs_valid_relation_id($id, $type) {
    $id = is_scalar($id) ? absint($id) : 0;
    return $id && get_post_type($id) === $type && get_post_status($id) === 'publish' ? $id : 0;
}

function pcvs_project_client_id($project_id) {
    $project_id = pcvs_valid_relation_id($project_id, 'pcvs_project');
    return $project_id ? pcvs_valid_relation_id(get_post_meta($project_id, 'client_id', true), 'pcvs_client') : 0;
}

function pcvs_product_client_id($product_id) {
    $inherited = pcvs_project_client_id(get_post_meta($product_id, 'project_id', true));
    return $inherited ?: pcvs_valid_relation_id(get_post_meta($product_id, 'client_id', true), 'pcvs_client');
}

function pcvs_relation_choices($type) {
    return get_posts(array(
        'post_type' => $type,
        'post_status' => 'publish',
        'posts_per_page' => -1,
        'orderby' => 'title',
        'order' => 'ASC',
    ));
}

function pcvs_render_relation_select($key, $type, $selected_id, $disabled = false) {
    ?>
    <select id="pcvs-<?php echo esc_attr($key); ?>" name="pcvs_meta[<?php echo esc_attr($key); ?>]" class="regular-text" <?php disabled($disabled); ?>>
        <option value=""><?php echo $type === 'pcvs_project' ? esc_html__('Select a project', 'product-code-verification-system') : esc_html__('Select a client', 'product-code-verification-system'); ?></option>
        <?php foreach (pcvs_relation_choices($type) as $entry) : ?>
            <option value="<?php echo esc_attr($entry->ID); ?>" <?php selected($selected_id, $entry->ID); ?><?php if ($type === 'pcvs_project') : ?> data-client-id="<?php echo esc_attr(pcvs_project_client_id($entry->ID)); ?>"<?php endif; ?>><?php echo esc_html($entry->post_title); ?></option>
        <?php endforeach; ?>
    </select>
    <?php
}

function pcvs_client_detail_fields() {
    return array(
        'company' => array(__('Company / organization', 'product-code-verification-system'), 'text'),
        'contact_person' => array(__('Contact person', 'product-code-verification-system'), 'text'),
        'email' => array(__('Email', 'product-code-verification-system'), 'email'),
        'phone' => array(__('Phone', 'product-code-verification-system'), 'tel'),
        'website' => array(__('Website', 'product-code-verification-system'), 'url'),
        'address' => array(__('Address', 'product-code-verification-system'), 'textarea'),
        'notes' => array(__('Other information / notes', 'product-code-verification-system'), 'textarea'),
    );
}

function pcvs_add_relationship_meta_boxes() {
    add_meta_box('pcvs-project-details', __('Project Details', 'product-code-verification-system'), 'pcvs_project_meta_box', 'pcvs_project', 'normal', 'high');
    add_meta_box('pcvs-client-details', __('Client Details', 'product-code-verification-system'), 'pcvs_client_meta_box', 'pcvs_client', 'normal', 'high');
}
add_action('add_meta_boxes', 'pcvs_add_relationship_meta_boxes');

function pcvs_project_meta_box($post) {
    wp_nonce_field('pcvs_relationship_save', 'pcvs_relationship_nonce');
    ?>
    <table class="form-table">
        <tr>
            <th><label for="pcvs-client_id"><?php esc_html_e('Client', 'product-code-verification-system'); ?></label></th>
            <td>
                <?php pcvs_render_relation_select('client_id', 'pcvs_client', pcvs_valid_relation_id(get_post_meta($post->ID, 'client_id', true), 'pcvs_client')); ?>
                <p class="description"><?php esc_html_e('Products and services in this project automatically use this client. Leave empty to select clients individually on each item. Publish clients before selecting them.', 'product-code-verification-system'); ?></p>
            </td>
        </tr>
        <tr>
            <th><label for="pcvs-project-description"><?php esc_html_e('Description / notes', 'product-code-verification-system'); ?></label></th>
            <td><textarea id="pcvs-project-description" name="pcvs_project_description" rows="5" class="large-text"><?php echo esc_textarea(get_post_meta($post->ID, 'project_description', true)); ?></textarea></td>
        </tr>
    </table>
    <p><a class="button" href="<?php echo esc_url(add_query_arg(array('post_type' => 'product_code', 'pcvs_project_id' => $post->ID), admin_url('edit.php'))); ?>"><?php esc_html_e('View products / services in this project', 'product-code-verification-system'); ?></a></p>
    <?php
}

function pcvs_client_meta_box($post) {
    wp_nonce_field('pcvs_relationship_save', 'pcvs_relationship_nonce');
    ?>
    <p><?php esc_html_e('Only the client name is included in verification results when the Client display field is visible. Contact details and notes stay in the admin area.', 'product-code-verification-system'); ?></p>
    <table class="form-table">
        <?php foreach (pcvs_client_detail_fields() as $key => $field) : ?>
            <tr>
                <th><label for="pcvs-client-<?php echo esc_attr($key); ?>"><?php echo esc_html($field[0]); ?></label></th>
                <td>
                    <?php if ($field[1] === 'textarea') : ?>
                        <textarea id="pcvs-client-<?php echo esc_attr($key); ?>" name="pcvs_client[<?php echo esc_attr($key); ?>]" rows="3" class="large-text"><?php echo esc_textarea(get_post_meta($post->ID, 'pcvs_client_' . $key, true)); ?></textarea>
                    <?php else : ?>
                        <input id="pcvs-client-<?php echo esc_attr($key); ?>" type="<?php echo esc_attr($field[1]); ?>" name="pcvs_client[<?php echo esc_attr($key); ?>]" value="<?php echo esc_attr(get_post_meta($post->ID, 'pcvs_client_' . $key, true)); ?>" class="regular-text">
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
    </table>
    <?php
}

function pcvs_save_relationship_details($post_id) {
    if (!isset($_POST['pcvs_relationship_nonce']) || !is_string($_POST['pcvs_relationship_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['pcvs_relationship_nonce'])), 'pcvs_relationship_save')) return;
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
    if (wp_is_post_revision($post_id) || !current_user_can('edit_post', $post_id)) return;
    $type = get_post_type($post_id);
    if ($type === 'pcvs_project') {
        $input = isset($_POST['pcvs_meta']) && is_array($_POST['pcvs_meta']) ? $_POST['pcvs_meta'] : array();
        update_post_meta($post_id, 'client_id', pcvs_valid_relation_id($input['client_id'] ?? 0, 'pcvs_client'));
        $description = isset($_POST['pcvs_project_description']) && is_string($_POST['pcvs_project_description']) ? sanitize_textarea_field(wp_unslash($_POST['pcvs_project_description'])) : '';
        update_post_meta($post_id, 'project_description', $description);
    } elseif ($type === 'pcvs_client') {
        $input = isset($_POST['pcvs_client']) && is_array($_POST['pcvs_client']) ? wp_unslash($_POST['pcvs_client']) : array();
        foreach (pcvs_client_detail_fields() as $key => $field) {
            $value = isset($input[$key]) && is_string($input[$key]) ? $input[$key] : '';
            if ($field[1] === 'email') {
                $value = sanitize_email($value);
            } elseif ($field[1] === 'url') {
                $value = esc_url_raw($value);
            } elseif ($field[1] === 'textarea') {
                $value = sanitize_textarea_field($value);
            } else {
                $value = sanitize_text_field($value);
            }
            update_post_meta($post_id, 'pcvs_client_' . $key, $value);
        }
    }
}
add_action('save_post', 'pcvs_save_relationship_details');

function pcvs_relationship_title_placeholder($title, $post) {
    if ($post->post_type === 'pcvs_project') return __('Enter project name', 'product-code-verification-system');
    if ($post->post_type === 'pcvs_client') return __('Enter client name', 'product-code-verification-system');
    return $title;
}
add_filter('enter_title_here', 'pcvs_relationship_title_placeholder', 10, 2);

function pcvs_project_columns($columns) {
    $columns['pcvs_client'] = __('Client', 'product-code-verification-system');
    $columns['pcvs_items'] = __('Products / services', 'product-code-verification-system');
    return $columns;
}
add_filter('manage_pcvs_project_posts_columns', 'pcvs_project_columns');

function pcvs_project_column_content($column, $post_id) {
    if ($column === 'pcvs_client') {
        $client_id = pcvs_valid_relation_id(get_post_meta($post_id, 'client_id', true), 'pcvs_client');
        echo $client_id ? esc_html(get_the_title($client_id)) : '&mdash;';
    } elseif ($column === 'pcvs_items') {
        $query = new WP_Query(array('post_type' => 'product_code', 'post_status' => array('publish', 'draft', 'pending', 'future', 'private'), 'meta_key' => 'project_id', 'meta_value' => $post_id, 'posts_per_page' => 1, 'fields' => 'ids'));
        echo '<a href="' . esc_url(add_query_arg(array('post_type' => 'product_code', 'pcvs_project_id' => $post_id), admin_url('edit.php'))) . '">' . esc_html($query->found_posts) . '</a>';
    }
}
add_action('manage_pcvs_project_posts_custom_column', 'pcvs_project_column_content', 10, 2);

function pcvs_client_columns($columns) {
    $columns['pcvs_company'] = __('Company / organization', 'product-code-verification-system');
    $columns['pcvs_email'] = __('Email', 'product-code-verification-system');
    $columns['pcvs_phone'] = __('Phone', 'product-code-verification-system');
    return $columns;
}
add_filter('manage_pcvs_client_posts_columns', 'pcvs_client_columns');

function pcvs_client_column_content($column, $post_id) {
    $keys = array('pcvs_company' => 'company', 'pcvs_email' => 'email', 'pcvs_phone' => 'phone');
    if (isset($keys[$column])) echo esc_html(get_post_meta($post_id, 'pcvs_client_' . $keys[$column], true));
}
add_action('manage_pcvs_client_posts_custom_column', 'pcvs_client_column_content', 10, 2);

function pcvs_filter_products_by_project($query) {
    if (!is_admin() || !$query->is_main_query() || $query->get('post_type') !== 'product_code' || empty($_GET['pcvs_project_id'])) return;
    $project_id = is_scalar($_GET['pcvs_project_id']) ? absint($_GET['pcvs_project_id']) : 0;
    $meta_query = $query->get('meta_query');
    $query->set('meta_query', array('relation' => 'AND', is_array($meta_query) ? $meta_query : array(), array('key' => 'project_id', 'value' => $project_id, 'compare' => '=')));
}
add_action('pre_get_posts', 'pcvs_filter_products_by_project');
