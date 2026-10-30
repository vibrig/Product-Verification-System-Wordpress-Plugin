<?php
if (!defined('ABSPATH')) {
    exit;
}

// Register Product Post Type
function pcvs_register_product_post_type() {
    $labels = array(
        'name' => __('Products', 'product-code-verification-system'),
        'singular_name' => __('Product', 'product-code-verification-system'),
        'menu_name' => __('Product Verification', 'product-code-verification-system'),
        'all_items' => __('Products', 'product-code-verification-system'),
        'search_items' => __('Search Products', 'product-code-verification-system'),
        'add_new' => __('Add Product', 'product-code-verification-system'),
        'add_new_item' => __('Add New Product', 'product-code-verification-system'),
        'edit_item' => __('Edit Product', 'product-code-verification-system'),
        'enter_title_here' => __('Enter Product Name Here', 'product-code-verification-system'),
    );
    $args = array(
        'labels' => $labels,
        'public' => false,
        'show_ui' => true,
        'show_in_menu' => true,
        'menu_position' => 5,
        'menu_icon' => 'dashicons-products',
        'supports' => array('title'),
        'capability_type' => 'post',
        'map_meta_cap' => true,
    );
    register_post_type('product_code', $args);
}
add_action('init', 'pcvs_register_product_post_type');

// Custom enter title here
function pcvs_custom_enter_title_here($title, $post) {
    if ($post->post_type === 'product_code') {
        $title = __('Enter Product Name Here', 'product-code-verification-system');
    }
    return $title;
}
add_filter('enter_title_here', 'pcvs_custom_enter_title_here', 10, 2);

// Add custom columns
function pcvs_add_product_columns($columns) {
    $new_columns = array();
    foreach ($columns as $key => $value) {
        if ($key === 'title') {
            $new_columns['product_info'] = __('Product Info', 'product-code-verification-system');
            $new_columns['codes_count'] = __('Codes Count', 'product-code-verification-system');
            $new_columns['pcvs_item_type'] = __('Type', 'product-code-verification-system');
            $new_columns['pcvs_project'] = __('Project', 'product-code-verification-system');
            $new_columns['pcvs_client'] = __('Client', 'product-code-verification-system');
        } else {
            $new_columns[$key] = $value;
        }
    }
    return $new_columns;
}
add_filter('manage_product_code_posts_columns', 'pcvs_add_product_columns');

// Display custom columns
function pcvs_display_product_columns($column, $post_id) {
    switch ($column) {
        case 'pcvs_item_type':
            echo get_post_meta($post_id, 'pcvs_item_type', true) === 'service' ? esc_html__('Service', 'product-code-verification-system') : esc_html__('Product', 'product-code-verification-system');
            break;
        case 'pcvs_project':
            $project_id = pcvs_valid_relation_id(get_post_meta($post_id, 'project_id', true), 'pcvs_project');
            echo $project_id ? esc_html(get_the_title($project_id)) : '&mdash;';
            break;
        case 'pcvs_client':
            $client_id = pcvs_product_client_id($post_id);
            echo $client_id ? esc_html(get_the_title($client_id)) : '&mdash;';
            break;
        case 'product_info':
            echo esc_html(get_the_title($post_id));
            break;
        case 'codes_count':
            $codes = get_post_meta($post_id, 'product_codes', true);
            echo esc_html(is_array($codes) ? count($codes) : 0);
            break;
    }
}
add_action('manage_product_code_posts_custom_column', 'pcvs_display_product_columns', 10, 2);
?>
