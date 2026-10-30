<?php
if (!defined('ABSPATH')) {
    exit;
}

define('PCVS_RELEASE_API', 'https://api.github.com/repos/vibrig/Product-Verification-System-Wordpress-Plugin/releases/latest');
define('PCVS_RELEASE_PAGE', 'https://github.com/vibrig/Product-Verification-System-Wordpress-Plugin/releases');

function pcvs_update_headers($download = false) {
    $headers = array(
        'Accept' => $download ? 'application/octet-stream' : 'application/vnd.github+json',
        'User-Agent' => 'Vibrig-Product-Verification-System/' . PCVS_VERSION,
        'X-GitHub-Api-Version' => '2022-11-28',
    );
    $token = get_option('pcvs_github_token', '');
    if (is_string($token) && $token !== '') {
        $headers['Authorization'] = 'Bearer ' . $token;
    }
    return $headers;
}

function pcvs_parse_release($data) {
    if (!is_array($data) || !empty($data['draft']) || !empty($data['prerelease'])) {
        return new WP_Error('pcvs_release', __('No stable release is available.', 'product-code-verification-system'));
    }
    $version = ltrim((string) ($data['tag_name'] ?? ''), 'vV');
    if (!preg_match('/^\d+\.\d+\.\d+$/', $version)) {
        return new WP_Error('pcvs_version', __('The release version is invalid.', 'product-code-verification-system'));
    }
    $expected = array('productverificationsystem' . $version . '.zip', 'productverificationsystemv' . $version . '.zip');
    foreach (($data['assets'] ?? array()) as $asset) {
        if (!is_array($asset)) continue;
        $name = preg_replace('/[^a-z0-9.]/', '', strtolower((string) ($asset['name'] ?? '')));
        if (in_array($name, $expected, true) && !empty($asset['id']) && ctype_digit((string) $asset['id'])) {
            return array(
                'version' => $version,
                'package' => 'https://api.github.com/repos/vibrig/Product-Verification-System-Wordpress-Plugin/releases/assets/' . $asset['id'],
                'url' => PCVS_RELEASE_PAGE . '/tag/' . rawurlencode($data['tag_name']),
                'notes' => is_string($data['body'] ?? null) ? $data['body'] : '',
            );
        }
    }
    return new WP_Error('pcvs_package', __('The latest release does not include a matching plugin ZIP package.', 'product-code-verification-system'));
}

function pcvs_get_release($force = false) {
    $cached = get_transient('pcvs_release_check');
    if (!$force && is_array($cached)) {
        return !empty($cached['error']) ? new WP_Error('pcvs_update_check', $cached['error']) : $cached['release'];
    }
    $response = wp_safe_remote_get(PCVS_RELEASE_API, array('timeout' => 15, 'redirection' => 0, 'headers' => pcvs_update_headers(), 'limit_response_size' => 1048576));
    $status = is_wp_error($response) ? 0 : wp_remote_retrieve_response_code($response);
    if (is_wp_error($response) || $status !== 200) {
        $message = __('Could not check for updates. Try again later.', 'product-code-verification-system');
        if (in_array($status, array(401, 403, 404), true)) {
            $message = __('GitHub access failed. For a private repository, save a token with read access to repository contents. A rate limit may also require waiting before retrying.', 'product-code-verification-system');
        }
        $release = new WP_Error('pcvs_update_check', $message);
    } else {
        $release = pcvs_parse_release(json_decode(wp_remote_retrieve_body($response), true));
    }
    set_transient('pcvs_release_check', array(
        'checked_at' => time(),
        'error' => is_wp_error($release) ? $release->get_error_message() : '',
        'release' => is_wp_error($release) ? null : $release,
    ), is_wp_error($release) ? 10 * MINUTE_IN_SECONDS : 6 * HOUR_IN_SECONDS);
    return $release;
}

function pcvs_update_transient($transient) {
    if (!is_object($transient)) return $transient;
    $release = pcvs_get_release();
    if (is_wp_error($release)) return $transient;
    $plugin = plugin_basename(PCVS_PLUGIN_FILE);
    $item = (object) array(
        'id' => 'https://github.com/vibrig/Product-Verification-System-Wordpress-Plugin',
        'slug' => 'product-verification-system',
        'plugin' => $plugin,
        'new_version' => $release['version'],
        'url' => $release['url'],
        'package' => $release['package'],
    );
    if (version_compare($release['version'], PCVS_VERSION, '>')) {
        $transient->response[$plugin] = $item;
        unset($transient->no_update[$plugin]);
    } else {
        unset($transient->response[$plugin]);
        $transient->no_update[$plugin] = $item;
    }
    return $transient;
}
add_filter('pre_set_site_transient_update_plugins', 'pcvs_update_transient');

function pcvs_update_now_url() {
    return wp_nonce_url(self_admin_url('update.php?action=upgrade-plugin&plugin=' . urlencode(plugin_basename(PCVS_PLUGIN_FILE))), 'upgrade-plugin_' . plugin_basename(PCVS_PLUGIN_FILE));
}

function pcvs_check_updates_action() {
    if (!current_user_can('update_plugins')) wp_die(esc_html__('Unauthorized.', 'product-code-verification-system'));
    check_admin_referer('pcvs_check_updates');
    pcvs_get_release(true);
    delete_site_transient('update_plugins');
    wp_update_plugins();
    wp_safe_redirect(admin_url('edit.php?post_type=product_code&page=pcvs-settings&tab=general'));
    exit;
}
add_action('admin_post_pcvs_check_updates', 'pcvs_check_updates_action');

function pcvs_save_update_settings() {
    if (!current_user_can('update_plugins')) return;
    $changed = false;
    if (!empty($_POST['pcvs_remove_github_token'])) {
        delete_option('pcvs_github_token');
        $changed = true;
    } elseif (isset($_POST['pcvs_github_token']) && is_string($_POST['pcvs_github_token'])) {
        $token = trim(wp_unslash($_POST['pcvs_github_token']));
        if ($token !== '' && preg_match('/^[A-Za-z0-9_]+$/', $token)) {
            update_option('pcvs_github_token', $token, false);
            $changed = true;
        } elseif ($token !== '') {
            add_settings_error('pcvs_messages', 'pcvs_token_invalid', __('The GitHub token format is invalid. The saved token was not changed.', 'product-code-verification-system'));
        }
    }
    if ($changed) {
        delete_transient('pcvs_release_check');
        delete_site_transient('update_plugins');
    }
}

function pcvs_render_update_settings() {
    ?>
    <h2><?php esc_html_e('Plugin Updates', 'product-code-verification-system'); ?></h2>
    <p><?php printf(esc_html__('Installed version: %s', 'product-code-verification-system'), esc_html(PCVS_VERSION)); ?></p>
    <?php if (!current_user_can('update_plugins')) : ?>
        <p><?php esc_html_e('A WordPress administrator with plugin-update permission can check and install updates.', 'product-code-verification-system'); ?></p>
    <?php return; endif;
    $release = pcvs_get_release();
    $cache = get_transient('pcvs_release_check');
    ?>
    <table class="form-table" role="presentation">
        <tr>
            <th><label for="pcvs-github-token"><?php esc_html_e('GitHub access token', 'product-code-verification-system'); ?></label></th>
            <td>
                <input type="password" id="pcvs-github-token" name="pcvs_github_token" value="" autocomplete="new-password" class="regular-text" placeholder="<?php echo get_option('pcvs_github_token', '') ? esc_attr__('Token saved — leave empty to keep', 'product-code-verification-system') : esc_attr__('Optional for public releases', 'product-code-verification-system'); ?>">
                <p class="description"><?php esc_html_e('Private releases require a GitHub token with Contents: read access to vibrig/Product-Verification-System-Wordpress-Plugin. Save changes before checking again. The token is stored on this WordPress site and is never displayed.', 'product-code-verification-system'); ?></p>
                <?php if (get_option('pcvs_github_token', '')) : ?>
                    <label><input type="checkbox" name="pcvs_remove_github_token" value="1"> <?php esc_html_e('Remove saved token', 'product-code-verification-system'); ?></label>
                <?php endif; ?>
            </td>
        </tr>
    </table>
    <p><?php
        if (is_wp_error($release)) {
            echo esc_html($release->get_error_message());
        } elseif (version_compare($release['version'], PCVS_VERSION, '>')) {
            printf(esc_html__('Version %s is available.', 'product-code-verification-system'), esc_html($release['version']));
        } else {
            esc_html_e('No newer version is available.', 'product-code-verification-system');
        }
    ?></p>
    <?php if (!empty($cache['checked_at'])) : ?>
        <p class="description"><?php printf(esc_html__('Last checked: %s. Automatic checks are cached for six hours.', 'product-code-verification-system'), esc_html(wp_date(get_option('date_format') . ' ' . get_option('time_format'), $cache['checked_at']))); ?></p>
    <?php endif; ?>
    <p>
        <a class="button" href="<?php echo esc_url(wp_nonce_url(admin_url('admin-post.php?action=pcvs_check_updates'), 'pcvs_check_updates')); ?>"><?php esc_html_e('Check for updates', 'product-code-verification-system'); ?></a>
        <?php if (!is_wp_error($release) && version_compare($release['version'], PCVS_VERSION, '>')) : ?>
            <a class="button button-primary" href="<?php echo esc_url(pcvs_update_now_url()); ?>"><?php esc_html_e('Update now', 'product-code-verification-system'); ?></a>
        <?php endif; ?>
        <a href="<?php echo esc_url(PCVS_RELEASE_PAGE); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e('View releases', 'product-code-verification-system'); ?></a>
    </p>
    <?php
}

function pcvs_update_notice() {
    if (!current_user_can('update_plugins')) return;
    $release = pcvs_get_release();
    if (is_wp_error($release) || !version_compare($release['version'], PCVS_VERSION, '>')) return;
    ?>
    <div class="notice notice-info"><p>
        <?php printf(esc_html__('Product Verification System %s is available.', 'product-code-verification-system'), esc_html($release['version'])); ?>
        <a href="<?php echo esc_url(pcvs_update_now_url()); ?>"><?php esc_html_e('Update now', 'product-code-verification-system'); ?></a>
    </p></div>
    <?php
}
add_action('admin_notices', 'pcvs_update_notice');
add_action('network_admin_notices', 'pcvs_update_notice');

function pcvs_plugin_information($result, $action, $args) {
    if ($action !== 'plugin_information' || !is_object($args) || ($args->slug ?? '') !== 'product-verification-system') return $result;
    $release = pcvs_get_release();
    if (is_wp_error($release)) return $result;
    return (object) array(
        'name' => 'Product Verification System',
        'slug' => 'product-verification-system',
        'version' => $release['version'],
        'author' => '<a href="https://vibrig.com/">Vibrig</a>',
        'homepage' => 'https://vibrig.com/',
        'download_link' => $release['package'],
        'sections' => array('description' => esc_html__('Verify products and documents using unique codes.', 'product-code-verification-system'), 'changelog' => wpautop(esc_html($release['notes']))),
    );
}
add_filter('plugins_api', 'pcvs_plugin_information', 20, 3);

function pcvs_download_update($reply, $package, $upgrader, $hook_extra = array()) {
    if ($reply !== false || !is_string($package) || !preg_match('~^https://api\.github\.com/repos/vibrig/Product-Verification-System-Wordpress-Plugin/releases/assets/\d+$~', $package)) return $reply;
    $filename = wp_tempnam('pcvs-update.zip');
    if (!$filename) return new WP_Error('pcvs_temp', __('Could not create an update download file.', 'product-code-verification-system'));
    // Do not forward the GitHub token to the redirected asset-storage host.
    $response = wp_safe_remote_get($package, array('timeout' => 60, 'redirection' => 0, 'headers' => pcvs_update_headers(true), 'stream' => true, 'filename' => $filename));
    $status = is_wp_error($response) ? 0 : wp_remote_retrieve_response_code($response);
    if (in_array($status, array(301, 302, 303, 307, 308), true)) {
        $location = wp_remote_retrieve_header($response, 'location');
        $host = is_string($location) ? wp_parse_url($location, PHP_URL_HOST) : '';
        if (is_string($host) && preg_match('/(^|\.)githubusercontent\.com$/i', $host) && wp_parse_url($location, PHP_URL_SCHEME) === 'https') {
            $response = wp_safe_remote_get($location, array('timeout' => 60, 'redirection' => 3, 'stream' => true, 'filename' => $filename));
            $status = is_wp_error($response) ? 0 : wp_remote_retrieve_response_code($response);
        }
    }
    $signature = is_file($filename) ? file_get_contents($filename, false, null, 0, 4) : '';
    if (is_wp_error($response) || $status !== 200 || $signature !== "PK\x03\x04") {
        wp_delete_file($filename);
        return new WP_Error('pcvs_download', __('The plugin update could not be downloaded as a ZIP. Check GitHub access and try again.', 'product-code-verification-system'));
    }
    return $filename;
}
add_filter('upgrader_pre_download', 'pcvs_download_update', 10, 4);

function pcvs_update_source($source, $remote_source, $upgrader, $hook_extra = array()) {
    if (($hook_extra['plugin'] ?? '') !== plugin_basename(PCVS_PLUGIN_FILE) || is_wp_error($source)) return $source;
    global $wp_filesystem;
    if (!$wp_filesystem || !$wp_filesystem->is_file(trailingslashit($source) . 'product-code-verification-system.php')) {
        return new WP_Error('pcvs_archive', __('The update ZIP does not contain the plugin main file.', 'product-code-verification-system'));
    }
    // Root-level release archives must preserve the site's existing plugin directory.
    $folder = dirname(plugin_basename(PCVS_PLUGIN_FILE));
    if ($folder === '.' || strpos($folder, '/') !== false || strpos($folder, '\\') !== false) return $source;
    $destination = trailingslashit($remote_source) . $folder . '/';
    if (untrailingslashit($source) !== untrailingslashit($destination)) {
        if (untrailingslashit($source) === untrailingslashit($remote_source)) {
            // A root-level ZIP needs its contents moved inside a named directory.
            if ($wp_filesystem->exists($destination) || !$wp_filesystem->mkdir($destination)) {
                return new WP_Error('pcvs_archive_folder', __('Could not create the plugin update folder.', 'product-code-verification-system'));
            }
            $entries = $wp_filesystem->dirlist($source, false, false);
            if (!is_array($entries)) return new WP_Error('pcvs_archive_list', __('Could not read the extracted update files.', 'product-code-verification-system'));
            foreach ($entries as $name => $entry) {
                if ($name === $folder) continue;
                if (!$wp_filesystem->move(trailingslashit($source) . $name, $destination . $name, true)) {
                    return new WP_Error('pcvs_archive_move', __('Could not prepare the plugin update files.', 'product-code-verification-system'));
                }
            }
        } elseif (!$wp_filesystem->move($source, $destination, true)) {
            return new WP_Error('pcvs_archive_move', __('Could not prepare the plugin update folder.', 'product-code-verification-system'));
        }
    }
    return $destination;
}
add_filter('upgrader_source_selection', 'pcvs_update_source', 10, 4);

function pcvs_updates_complete($upgrader, $options) {
    if (($options['type'] ?? '') !== 'plugin' || ($options['action'] ?? '') !== 'update') return;
    $plugins = $options['plugins'] ?? array($options['plugin'] ?? '');
    if (in_array(plugin_basename(PCVS_PLUGIN_FILE), $plugins, true)) delete_transient('pcvs_release_check');
}
add_action('upgrader_process_complete', 'pcvs_updates_complete', 10, 2);
