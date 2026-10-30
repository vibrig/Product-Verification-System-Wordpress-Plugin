<?php
// Standalone regression tests with WordPress stubs: php tests/updates.test.php
if (PHP_SAPI !== 'cli') exit;
error_reporting(E_ALL);
set_error_handler(function ($severity, $message, $file, $line) { throw new ErrorException($message, 0, $severity, $file, $line); });
define('ABSPATH', __DIR__);
define('PCVS_VERSION', '1.9.2');
define('PCVS_PLUGIN_FILE', '/plugins/custom-folder/product-code-verification-system.php');
define('MINUTE_IN_SECONDS', 60);
define('HOUR_IN_SECONDS', 3600);
$options = array();
$cache = array();
$requests = array();
$responses = array();
$permissions = true;
class WP_Error {
    public $code;
    private $message;
    public function __construct($code, $message) { $this->code = $code; $this->message = $message; }
    public function get_error_message() { return $this->message; }
}
function expect($condition, $message) { if (!$condition) throw new Exception($message); }
function __($value, $domain = '') { return $value; }
function esc_html($value) { return htmlspecialchars((string) $value, ENT_QUOTES); }
function esc_attr($value) { return esc_html($value); }
function esc_textarea($value) { return esc_html($value); }
function esc_url($value) { return $value; }
function esc_html__($value, $domain = '') { return esc_html($value); }
function esc_attr__($value, $domain = '') { return esc_attr($value); }
function esc_html_e($value, $domain = '') { echo esc_html($value); }
function esc_attr_e($value, $domain = '') { echo esc_attr($value); }
function esc_js($value) { return addslashes($value); }
function add_action(...$args) {}
function add_filter(...$args) {}
function is_wp_error($value) { return $value instanceof WP_Error; }
function get_option($key, $default = false) { return $GLOBALS['options'][$key] ?? $default; }
function update_option($key, $value, $autoload = null) { $GLOBALS['options'][$key] = $value; }
function delete_option($key) { unset($GLOBALS['options'][$key]); }
function get_transient($key) { return $GLOBALS['cache'][$key] ?? false; }
function set_transient($key, $value, $ttl) { $GLOBALS['cache'][$key] = $value; $GLOBALS['ttl'] = $ttl; }
function delete_transient($key) { unset($GLOBALS['cache'][$key]); }
function delete_site_transient($key) {}
function current_user_can(...$args) { return $GLOBALS['permissions']; }
function plugin_basename($path) { return 'custom-folder/product-code-verification-system.php'; }
function admin_url($path) { return 'https://site.test/wp-admin/' . $path; }
function self_admin_url($path) { return admin_url($path); }
function wp_nonce_url($url, $nonce) { return $url . '&_wpnonce=valid'; }
function wp_verify_nonce($nonce, $action) { return $nonce === 'valid'; }
function wp_unslash($value) { return $value; }
function sanitize_text_field($value) { return trim(strip_tags($value)); }
function sanitize_textarea_field($value) { return trim(strip_tags($value)); }
function sanitize_email($value) { return filter_var($value, FILTER_SANITIZE_EMAIL); }
function absint($value) { return abs((int) $value); }
function add_settings_error(...$args) {}
function settings_errors(...$args) {}
function wp_nonce_field(...$args) {}
function submit_button() { echo '<button>Save Changes</button>'; }
function checked($a, $b = true) { if ($a == $b) echo 'checked'; }
function wp_date($format, $timestamp) { return date('Y-m-d', $timestamp); }
function wpautop($value) { return '<p>' . $value . '</p>'; }
function trailingslashit($value) { return rtrim($value, '/') . '/'; }
function untrailingslashit($value) { return rtrim($value, '/'); }
function wp_tempnam($name) { return tempnam(sys_get_temp_dir(), 'pcvs'); }
function wp_delete_file($path) { unlink($path); }
function wp_parse_url($url, $component) { return parse_url($url, $component); }
function wp_remote_retrieve_response_code($response) { return $response['status']; }
function wp_remote_retrieve_body($response) { return $response['body'] ?? ''; }
function wp_remote_retrieve_header($response, $key) { return $response[$key] ?? ''; }
function wp_safe_remote_get($url, $args) {
    $GLOBALS['requests'][] = array($url, $args);
    $response = array_shift($GLOBALS['responses']);
    if (!$response) throw new Exception('Unexpected HTTP request');
    if (isset($args['filename'])) file_put_contents($args['filename'], $response['body'] ?? '');
    return $response;
}
require __DIR__ . '/../includes/settings.php';
require __DIR__ . '/../includes/updates.php';
require __DIR__ . '/../includes/admin-menus.php';

$release = array('tag_name' => 'v1.9.3', 'assets' => array(array('id' => 123, 'name' => 'Product-Verification-System-1.9.3.zip')), 'body' => '<script>unsafe</script>');
$parsed = pcvs_parse_release($release);
expect($parsed['version'] === '1.9.3', 'Parse stable release');
expect(str_ends_with($parsed['package'], '/assets/123'), 'Use fixed GitHub asset endpoint');
$bad = $release;
$bad['prerelease'] = true;
expect(is_wp_error(pcvs_parse_release($bad)), 'Ignore prereleases');
$bad = $release;
$bad['assets'][0]['name'] = 'unrelated.zip';
expect(is_wp_error(pcvs_parse_release($bad)), 'Reject unrelated packages');
$bad = $release;
$bad['assets'][0]['name'] = 'Product-Verification-System-v1.9.3.zip';
expect(!is_wp_error(pcvs_parse_release($bad)), 'Accept version-prefixed package name');

$responses[] = array('status' => 200, 'body' => json_encode($release));
$options['pcvs_github_token'] = 'github_pat_TEST';
$fetched = pcvs_get_release();
expect($fetched['version'] === '1.9.3', 'Fetch release');
expect(count($requests) === 1 && $requests[0][1]['headers']['Authorization'] === 'Bearer github_pat_TEST', 'Authenticated metadata request');
pcvs_get_release();
expect(count($requests) === 1, 'Cache successful checks');
$transient = pcvs_update_transient((object) array('response' => array(), 'no_update' => array()));
expect(isset($transient->response[plugin_basename(PCVS_PLUGIN_FILE)]), 'Integrate native update notification');
$cache['pcvs_release_check']['release']['version'] = '1.9.2';
$transient = pcvs_update_transient($transient);
expect(empty($transient->response) && !empty($transient->no_update), 'No update for same version');
delete_transient('pcvs_release_check');
$responses[] = array('status' => 404);
expect(is_wp_error(pcvs_get_release()), 'Report private repository access failure');
expect($GLOBALS['ttl'] === 600, 'Short error cache');
$cache['pcvs_release_check'] = array('release' => $parsed, 'checked_at' => time(), 'error' => '');
$info = pcvs_plugin_information(false, 'plugin_information', (object) array('slug' => 'product-verification-system'));
expect(!str_contains($info->sections['changelog'], '<script>'), 'Escape release notes');

// Saving a section must not reset the other section's configuration.
$fields = pcvs_get_default_fields();
$fields['code']['visible'] = false;
$options['pcvs_fields'] = $fields;
$_GET = array('tab' => 'general');
$_POST = array('pcvs_settings_nonce' => 'valid', 'genuine_message' => 'Genuine', 'invalid_message' => 'Missing', 'admin_email' => 'test@example.org', 'pcvs_advanced' => array('form_title' => 'Custom heading'));
ob_start(); pcvs_settings_page_callback(); $html = ob_get_clean();
expect($options['pcvs_fields'] === $fields, 'General save preserves fields');
expect(!str_contains($html, 'id="pcvs-fields-manager"'), 'General tab omits field manager');
$general = $options['pcvs_advanced_settings'];
$_GET = array('tab' => 'fields');
$_POST = array('pcvs_settings_nonce' => 'valid', 'pcvs_fields' => array('key' => array('code'), 'label' => array('Code'), 'visible' => array('code' => 1), 'core' => array('1')));
ob_start(); pcvs_settings_page_callback(); $html = ob_get_clean();
expect($options['pcvs_advanced_settings'] === $general && $options['pcvs_genuine_message'] === 'Genuine', 'Field save preserves general settings');
expect(str_contains($html, 'id="pcvs-fields-manager"') && !str_contains($html, 'id="pcvs-github-token"'), 'Field tab only shows field controls');
$_POST = array('pcvs_github_token' => '');
pcvs_save_update_settings();
expect($options['pcvs_github_token'] === 'github_pat_TEST', 'Blank password preserves token');

// Download tokens go to the API only; redirected downloads omit Authorization.
$requests = array();
$responses = array(array('status' => 302, 'location' => 'https://release-assets.githubusercontent.com/package.zip'), array('status' => 200, 'body' => "PK\x03\x04test"));
$file = pcvs_download_update(false, $parsed['package'], null);
expect(is_string($file) && is_file($file), 'Download update ZIP');
expect(isset($requests[0][1]['headers']['Authorization']) && !isset($requests[1][1]['headers']['Authorization']), 'Never forward token to asset host');
unlink($file);
$responses = array(array('status' => 302, 'location' => 'https://evil.test/package.zip'));
expect(is_wp_error(pcvs_download_update(false, $parsed['package'], null)), 'Reject untrusted redirect');
$responses = array(array('status' => 200, 'body' => '{"metadata":true}'));
expect(is_wp_error(pcvs_download_update(false, $parsed['package'], null)), 'Reject JSON instead of ZIP');
expect(pcvs_download_update(false, 'https://other.test/plugin.zip', null) === false, 'Do not intercept other plugins');

// Simulate WordPress filesystem operations on a root-level release package.
$wp_filesystem = new class {
    public $files = array('/upgrade/extract/product-code-verification-system.php' => true, '/upgrade/extract/includes' => true);
    public function is_file($path) { return !empty($this->files[$path]); }
    public function exists($path) { return isset($this->files[rtrim($path, '/')]); }
    public function mkdir($path) { $this->files[rtrim($path, '/')] = true; return true; }
    public function dirlist($path, ...$args) { return array('product-code-verification-system.php' => array(), 'includes' => array(), 'custom-folder' => array()); }
    public function move($source, $destination, $overwrite) { unset($this->files[$source]); $this->files[$destination] = true; return true; }
};
$source = pcvs_update_source('/upgrade/extract/', '/upgrade/extract/', null, array('plugin' => plugin_basename(PCVS_PLUGIN_FILE)));
expect($source === '/upgrade/extract/custom-folder/', 'Preserve installed plugin directory');
expect($wp_filesystem->is_file($source . 'product-code-verification-system.php'), 'Nest root-level package correctly');
echo "PASS: release selection, native notifications, HTTP caching/errors, tab-save isolation, token retention/privacy, ZIP validation, and root-level archive handling.\n";
