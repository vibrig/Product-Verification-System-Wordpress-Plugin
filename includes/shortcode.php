<?php
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Renders the product verification form via shortcode.
 * Shortcode: [product_verify]
 */
function pcvs_verify_shortcode($atts) {
    pcvs_enqueue_frontend_assets();
    $settings = pcvs_get_advanced_settings();

    ob_start();
    ?>
    <div class="pcvs-verify-wrap">
        <div class="pcvs-verify-icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"><path d="M12 3 4 6v6c0 5 8 9 8 9s8-4 8-9V6l-8-3Z"/><path d="m8 12 3 3 5-6"/></svg></div>
        <h2 class="pcvs-verify-title"><?php echo esc_html($settings['form_title']); ?></h2>
        <p class="pcvs-verify-desc" id="pcvs-code-help"><?php echo esc_html($settings['form_description']); ?></p>
        
        <form id="pcvs-verify-form" class="pcvs-verify-form">
            <?php wp_nonce_field('pcvs_verify_action', 'pcvs_nonce'); ?>
            <label class="pcvs-code-label" for="pcvs-code"><?php esc_html_e('Verification code', 'product-code-verification-system'); ?></label>
            <input type="text" id="pcvs-code" name="code" placeholder="<?php esc_attr_e('Enter your unique code', 'product-code-verification-system'); ?>" aria-describedby="pcvs-code-help" autocapitalize="off" spellcheck="false" required class="pcvs-verify-input">
            
            <button type="submit" class="pcvs-verify-btn"><?php echo esc_html($settings['button_text']); ?></button>
        </form>
        
        <div id="pcvs-result" class="pcvs-result-container" role="status" aria-live="polite" aria-atomic="true"></div>
    </div>
    <?php
    return ob_get_clean();
}
add_shortcode('product_verify', 'pcvs_verify_shortcode');
