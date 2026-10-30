<?php
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

/**
 * Renders the "How to Use" guide page.
 */
function pcvs_how_to_use_page_callback() {
    ?>
    <div class="wrap pcvs-guide-wrap">
        <h1><?php esc_html_e('How to Use Product Verification System', 'product-code-verification-system'); ?></h1>
        <p><?php esc_html_e('Owner: Vibrig', 'product-code-verification-system'); ?> &middot; <a href="https://vibrig.com/" target="_blank" rel="noopener noreferrer">vibrig.com</a></p>
        <div class="pcvs-guide-card">
            <h2><?php esc_html_e('Projects, Clients, and Services', 'product-code-verification-system'); ?></h2>
            <ol>
                <li><?php esc_html_e('Open Product Verification > Clients and add a client name, company, contact details, address, website, and notes. Publish the client to make it available in selectors.', 'product-code-verification-system'); ?></li>
                <li><?php esc_html_e('Open Product Verification > Projects, add a project, optionally assign its client, and publish it.', 'product-code-verification-system'); ?></li>
                <li><?php esc_html_e('Add or edit a product. Choose Product or Service as the item type, then select its project. Many items can use the same project.', 'product-code-verification-system'); ?></li>
                <li><?php esc_html_e('If the project has a client, the Client selector fills automatically. Otherwise, select a client manually. Changes to the project client apply to its items immediately.', 'product-code-verification-system'); ?></li>
                <li><?php esc_html_e('In Settings > Frontend Display Fields, reorder or hide Project and Client. Verification results show their names only; client contact details and notes remain in the admin area.', 'product-code-verification-system'); ?></li>
                <li><?php esc_html_e('Use the Products / services count in the Projects list to view the items assigned to a project.', 'product-code-verification-system'); ?></li>
            </ol>
        </div>

        <div class="pcvs-guide-card">
            <h2><?php esc_html_e('Step 1: Add a Product and Verification Codes', 'product-code-verification-system'); ?></h2>
            <p><?php esc_html_e('First, you need to add the products and the unique codes that will be verified.', 'product-code-verification-system'); ?></p>
            <ol>
                <li><?php esc_html_e('In the WordPress admin menu, go to "Product Verification" > "Add New Product". Open "Products" to manage existing entries.', 'product-code-verification-system'); ?></li>
                <li><?php esc_html_e('Enter a title for your product (e.g., "Premium Widget v2").', 'product-code-verification-system'); ?></li>
                <li><?php esc_html_e('Fill in any additional details like Description, Batch No., Manufacturing/Expiry Dates, and upload a Product Image or Certificate.', 'product-code-verification-system'); ?></li>
                <li><?php esc_html_e('In the "Product Codes" section, enter a unique code in the text field. You can also click "Generate Random Code" to create one automatically.', 'product-code-verification-system'); ?></li>
                <li><?php esc_html_e('Click "Publish" to save the product.', 'product-code-verification-system'); ?></li>
            </ol>
        </div>

        <div class="pcvs-guide-card">
            <h2><?php esc_html_e('Step 2: Display the Verification Form on Your Website', 'product-code-verification-system'); ?></h2>
            <p><?php esc_html_e('To allow your customers to verify their codes, you need to place the verification form on a page. This is done using a simple shortcode.', 'product-code-verification-system'); ?></p>
            
            <div class="pcvs-shortcode-display">
                <p><?php esc_html_e('Copy the shortcode below and paste it into any page, post, or text widget where you want the form to appear:', 'product-code-verification-system'); ?></p>
                <div class="pcvs-shortcode-wrapper">
                    <input type="text" id="pcvs-main-shortcode" value="[product_verify]" readonly>
                    <button class="button button-primary" id="pcvs-copy-btn">
                        <span class="dashicons dashicons-clipboard"></span>
                        <span class="copy-text"><?php esc_html_e('Copy', 'product-code-verification-system'); ?></span>
                    </button>
                </div>
            </div>
        </div>

        <div class="pcvs-guide-card">
            <h2><?php esc_html_e('Step 3: Configure Settings (Optional)', 'product-code-verification-system'); ?></h2>
            <p><?php esc_html_e('You can customize the messages shown to users after a verification attempt.', 'product-code-verification-system'); ?></p>
             <ol>
                <li><?php esc_html_e('Go to "Product Verification" > "Settings".', 'product-code-verification-system'); ?></li>
                <li><?php esc_html_e('Here you can change the "Genuine Message" and "Invalid Message".', 'product-code-verification-system'); ?></li>
                <li><?php esc_html_e('You can add your custom fields, remove it, you can also hide, or rename the fields that are displayed on a successful verification.', 'product-code-verification-system'); ?></li>
            </ol>
        </div>
        <div class="pcvs-guide-card">
            <h2><?php esc_html_e('Step 4: Use Advanced Options', 'product-code-verification-system'); ?></h2>
            <ol>
                <li><?php esc_html_e('In Settings, customize the verification heading, description, and button text. Empty values restore the default wording.', 'product-code-verification-system'); ?></li>
                <li><?php esc_html_e('Enable or disable invalid-code email alerts. Alerts use the Admin Email for Alerts address.', 'product-code-verification-system'); ?></li>
                <li><?php esc_html_e('Enable verification history to record genuine and invalid attempts. Optionally include visitor IP addresses and choose how many days to retain logs.', 'product-code-verification-system'); ?></li>
                <li><?php esc_html_e('Open Product Verification > Verification Logs to review attempts, export a CSV, or clear the history. History is limited to 5,000 entries.', 'product-code-verification-system'); ?></li>
                <li><?php esc_html_e('Use the Visible checkboxes in Frontend Display Fields to control details, product images, certificates, and invoices shown after verification.', 'product-code-verification-system'); ?></li>
            </ol>
        </div>
    </div>
    <style>
        .pcvs-guide-wrap .pcvs-guide-card {
            background: #fff;
            border: 1px solid #ccd0d4;
            padding: 20px;
            margin-top: 20px;
            border-radius: 4px;
        }
        .pcvs-guide-wrap ol {
            list-style-type: decimal;
            padding-left: 20px;
        }
        .pcvs-guide-wrap li {
            margin-bottom: 10px;
        }
        .pcvs-shortcode-wrapper {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .pcvs-shortcode-wrapper input[type="text"] {
            font-family: monospace;
            padding: 8px 12px;
            background: #f0f0f1;
            border: 1px solid #ccd0d4;
            border-radius: 4px;
            flex-grow: 1;
        }
        .pcvs-shortcode-wrapper .button .dashicons {
            vertical-align: middle;
            margin-right: 5px;
        }
    </style>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const copyButton = document.getElementById('pcvs-copy-btn');
            const shortcodeInput = document.getElementById('pcvs-main-shortcode');

            if (!copyButton || !shortcodeInput) return;

            copyButton.addEventListener('click', function() {
                shortcodeInput.select();
                document.execCommand('copy');

                const originalText = this.querySelector('.copy-text').textContent;
                this.querySelector('.copy-text').textContent = '<?php esc_js_e('Copied!', 'product-code-verification-system'); ?>';
                
                setTimeout(() => {
                    this.querySelector('.copy-text').textContent = originalText;
                }, 2000);
            });
        });
    </script>
    <?php
}
