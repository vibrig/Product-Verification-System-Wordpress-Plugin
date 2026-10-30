<?php
/**
 * Plugin Name: Product Verification System
 * Plugin URI: https://vibrig.com/
 * Description: Verify products and documents with unique codes, customizable results, and optional verification logs. Owned by Vibrig.
 * Version: 1.9.1
 * Author: Vibrig
 * Author URI: https://vibrig.com/
 * License: GPL-2.0+
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: product-code-verification-system
 * Domain Path: /languages
 */

if (!defined('ABSPATH')) {
    exit;
}

// Define plugin path and URL constants
define('PCVS_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('PCVS_PLUGIN_URL', plugin_dir_url(__FILE__));
define('PCVS_VERSION', '1.9.1');

// Load core functionality
require_once PCVS_PLUGIN_DIR . 'includes/post-type.php';
require_once PCVS_PLUGIN_DIR . 'includes/settings.php';
require_once PCVS_PLUGIN_DIR . 'includes/admin-menus.php';
require_once PCVS_PLUGIN_DIR . 'includes/relationships.php';
require_once PCVS_PLUGIN_DIR . 'includes/product-meta.php';
require_once PCVS_PLUGIN_DIR . 'includes/verification-logs.php';
require_once PCVS_PLUGIN_DIR . 'includes/ajax-handlers.php';
require_once PCVS_PLUGIN_DIR . 'includes/shortcode.php';
require_once PCVS_PLUGIN_DIR . 'includes/how-to-use.php';

// Enable shortcodes in text widgets
add_filter('widget_text', 'do_shortcode');
add_filter('widget_text_content', 'do_shortcode');

// Enqueue admin styles and scripts
function pcvs_enqueue_admin_assets($hook) {
    $screen = get_current_screen();
    if ($screen && (in_array($screen->post_type, array('product_code', 'pcvs_project', 'pcvs_client'), true) || strpos($screen->id, 'pcvs-') !== false)) {
        wp_enqueue_style('jquery-ui-css', 'https://code.jquery.com/ui/1.13.2/themes/base/jquery-ui.css', array(), '1.13.2');
        wp_enqueue_style('pcvs-admin-styles', PCVS_PLUGIN_URL . 'assets/css/admin-styles.css', array(), PCVS_VERSION);
        
        wp_enqueue_script('jquery-ui-datepicker');
        wp_enqueue_script('pcvs-admin-scripts', PCVS_PLUGIN_URL . 'assets/js/admin-scripts.js', array('jquery', 'jquery-ui-datepicker'), PCVS_VERSION, true);
        
        wp_localize_script('pcvs-admin-scripts', 'pcvs_vars', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('pcvs_nonce'),
        ));

        if ($screen->post_type === 'product_code' && ($hook === 'post.php' || $hook === 'post-new.php')) {
            wp_enqueue_media();
        }
    }
}
add_action('admin_enqueue_scripts', 'pcvs_enqueue_admin_assets');

// Enqueue frontend assets (will be called conditionally by the shortcode)
function pcvs_enqueue_frontend_assets() {
    wp_enqueue_style('pcvs-frontend-styles', PCVS_PLUGIN_URL . 'assets/css/frontend-styles.css', array(), PCVS_VERSION);
    wp_enqueue_script('pcvs-frontend-scripts', PCVS_PLUGIN_URL . 'assets/js/frontend-scripts.js', array('jquery'), PCVS_VERSION, true);
    
    wp_localize_script('pcvs-frontend-scripts', 'pcvs_vars', array(
        'ajax_url' => admin_url('admin-ajax.php'),
        'strings' => array(
            'emptyCode' => __('Please enter a product or document code.', 'product-code-verification-system'),
            'verifying' => __('Verifying...', 'product-code-verification-system'),
            'error' => __('Unable to verify the code. Please try again.', 'product-code-verification-system'),
            'active' => __('Active', 'product-code-verification-system'),
            'expired' => __('Expired', 'product-code-verification-system'),
            'imageAlt' => __('Product or document image', 'product-code-verification-system'),
            'certificate' => __('View / download certificate', 'product-code-verification-system'),
            'bill' => __('View / download invoice', 'product-code-verification-system'),
        ),
    ));
}

// Plugin action links
function pcvs_plugin_action_links($links) {
    $settings_link = '<a href="' . esc_url(admin_url('edit.php?post_type=product_code&page=pcvs-settings')) . '">' . esc_html__('Settings', 'product-code-verification-system') . '</a>';
    array_unshift($links, $settings_link);
    return $links;
}
add_filter('plugin_action_links_' . plugin_basename(__FILE__), 'pcvs_plugin_action_links');
