<?php
/**
 * Plugin Name: Single Session Limiter
 * Plugin URI: https://github.com/starmidopro/Single-Session-Limiter
 * Description: Limits users to a single active session. When a user logs in, any existing sessions are ended. Includes admin settings for role-based configuration and responsive styling.
 * Version: 1.0
 * Author: Mohamed gamal
 * License: GPL v2 or later
 * License URI: http://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: single-session-limiter
 * Domain Path: /languages
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define constants
define('SSL_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('SSL_PLUGIN_URL', plugin_dir_url(__FILE__));

// Activation hook
register_activation_hook(__FILE__, 'ssl_activate');
function ssl_activate() {
    // Set default options
    add_option('ssl_enabled_roles', array('subscriber')); // Default to subscriber role
}

// Deactivation hook
register_deactivation_hook(__FILE__, 'ssl_deactivate');
function ssl_deactivate() {
    // Remove all user meta for valid sessions
    $users = get_users();
    foreach ($users as $user) {
        delete_user_meta($user->ID, 'valid_session_token');
    }
}

// Hook into wp_login to set new session token
add_action('wp_login', 'ssl_set_session_token', 10, 2);
function ssl_set_session_token($user_login, $user) {
    // Check if this role is enabled for session limiting
    $enabled_roles = get_option('ssl_enabled_roles', array());
    $user_roles = $user->roles;
    $intersect = array_intersect($user_roles, $enabled_roles);
    if (empty($intersect)) {
        return; // Skip if not for this role
    }
    
    // Generate a unique session token
    $token = wp_generate_password(32, false);
    
    // Update user meta with new valid token
    update_user_meta($user->ID, 'valid_session_token', $token);
    
    // Set a session cookie for the token
    setcookie('ssl_session_token', $token, 0, '/', COOKIE_DOMAIN, is_ssl(), true);
}

// Hook into init to check session validity
add_action('init', 'ssl_check_session_validity');
function ssl_check_session_validity() {
    if (!is_user_logged_in()) {
        return;
    }
    
    $user_id = get_current_user_id();
    
    // Check if this user's role is enabled
    $enabled_roles = get_option('ssl_enabled_roles', array());
    $user = wp_get_current_user();
    $user_roles = $user->roles;
    $intersect = array_intersect($user_roles, $enabled_roles);
    if (empty($intersect)) {
        return; // Skip check
    }
    
    $valid_token = get_user_meta($user_id, 'valid_session_token', true);
    $current_token = isset($_COOKIE['ssl_session_token']) ? $_COOKIE['ssl_session_token'] : '';
    
    if (!$valid_token || $current_token !== $valid_token) {
        // Invalid session, force logout
        wp_logout();
        // Redirect to login page with a message
        wp_safe_redirect(wp_login_url() . '?session_expired=1');
        exit;
    }
}

// Add admin menu
add_action('admin_menu', 'ssl_add_admin_menu');
function ssl_add_admin_menu() {
    add_menu_page(
        __('Single Session Limiter', 'single-session-limiter'),
        __('Session Limiter', 'single-session-limiter'),
        'manage_options',
        'single-session-limiter',
        'ssl_admin_page',
        'dashicons-lock',
        30
    );
}

// Admin page content
function ssl_admin_page() {
    if (isset($_POST['submit'])) {
        check_admin_referer('ssl_settings');
        
        $enabled_roles = isset($_POST['ssl_enabled_roles']) ? array_map('sanitize_text_field', $_POST['ssl_enabled_roles']) : array();
        update_option('ssl_enabled_roles', $enabled_roles);
        
        echo '<div class="notice notice-success"><p>' . __('Settings saved.', 'single-session-limiter') . '</p></div>';
    }
    
    $enabled_roles = get_option('ssl_enabled_roles', array());
    $all_roles = wp_roles()->roles;
    ?>
    <div class="wrap">
        <h1><?php _e('Single Session Limiter Settings', 'single-session-limiter'); ?></h1>
        <form method="post" action="">
            <?php wp_nonce_field('ssl_settings'); ?>
            <table class="form-table">
                <tr>
                    <th scope="row"><?php _e('Enabled Roles', 'single-session-limiter'); ?></th>
                    <td>
                        <?php if (!empty($all_roles)) : ?>
                            <div class="ssl-checkbox-group">
                                <?php foreach ($all_roles as $role_key => $role) : ?>
                                    <label class="ssl-checkbox-label">
                                        <input type="checkbox" name="ssl_enabled_roles[]" value="<?php echo esc_attr($role_key); ?>" <?php checked(in_array($role_key, $enabled_roles)); ?> />
                                        <?php echo esc_html($role['name']); ?>
                                    </label>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                        <p class="description"><?php _e('Select user roles for which single session limiting should be applied. Users in these roles can have only one active session; logging in from another device/browser will end the previous session.', 'single-session-limiter'); ?></p>
                    </td>
                </tr>
            </table>
            <?php submit_button(); ?>
        </form>
        <hr>
        <h2><?php _e('Active Tokens', 'single-session-limiter'); ?></h2>
        <p><?php _e('Below is a list of users with active session tokens. You can manually expire a session by deleting the token (note: this will force the user to log in again).', 'single-session-limiter'); ?></p>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th><?php _e('User ID', 'single-session-limiter'); ?></th>
                    <th><?php _e('Username', 'single-session-limiter'); ?></th>
                    <th><?php _e('Role', 'single-session-limiter'); ?></th>
                    <th><?php _e('Session Token', 'single-session-limiter'); ?></th>
                    <th><?php _e('Actions', 'single-session-limiter'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php
                $users_with_tokens = get_users(array('meta_key' => 'valid_session_token', 'meta_value' => '', 'meta_compare' => '!='));
                if (!empty($users_with_tokens)) {
                    foreach ($users_with_tokens as $user) {
                        $token = get_user_meta($user->ID, 'valid_session_token', true);
                        $role_names = implode(', ', $user->roles);
                        ?>
                        <tr>
                            <td><?php echo esc_html($user->ID); ?></td>
                            <td><?php echo esc_html($user->user_login); ?></td>
                            <td><?php echo esc_html($role_names); ?></td>
                            <td><code><?php echo esc_html($token); ?></code></td>
                            <td>
                                <a href="<?php echo wp_nonce_url(admin_url('admin.php?page=single-session-limiter&action=expire&user_id=' . $user->ID), 'expire_session_' . $user->ID); ?>" class="button button-secondary"><?php _e('Expire Session', 'single-session-limiter'); ?></a>
                            </td>
                        </tr>
                        <?php
                    }
                } else {
                    ?>
                    <tr>
                        <td colspan="5"><?php _e('No active sessions found.', 'single-session-limiter'); ?></td>
                    </tr>
                    <?php
                }
                ?>
            </tbody>
        </table>
    </div>
    <?php
    
    // Handle expire action
    if (isset($_GET['action']) && $_GET['action'] == 'expire' && isset($_GET['user_id'])) {
        $user_id = intval($_GET['user_id']);
        check_admin_referer('expire_session_' . $user_id);
        
        if (delete_user_meta($user_id, 'valid_session_token')) {
            echo '<div class="notice notice-success"><p>' . __('Session expired for user.', 'single-session-limiter') . '</p></div>';
        } else {
            echo '<div class="notice notice-error"><p>' . __('Failed to expire session.', 'single-session-limiter') . '</p></div>';
        }
    }
}



?>
