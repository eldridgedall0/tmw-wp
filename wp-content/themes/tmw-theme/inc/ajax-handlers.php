<?php
/**
 * AJAX Handlers
 *
 * Handles AJAX requests for theme functionality.
 *
 * @package flavor-starter-flavor
 */

if (!defined('ABSPATH')) {
    exit;
}

// =============================================================================
// THEME MODE TOGGLE
// =============================================================================
add_action('wp_ajax_tmw_toggle_theme', 'tmw_ajax_toggle_theme');
add_action('wp_ajax_nopriv_tmw_toggle_theme', 'tmw_ajax_toggle_theme');

function tmw_ajax_toggle_theme() {
    check_ajax_referer('tmw_nonce', 'nonce');

    $mode = isset($_POST['mode']) ? sanitize_key($_POST['mode']) : 'dark';
    
    if (!in_array($mode, array('dark', 'light'))) {
        $mode = 'dark';
    }

    // Save to user meta if logged in
    if (is_user_logged_in()) {
        update_user_meta(get_current_user_id(), 'tmw_theme_mode', $mode);
    }

    wp_send_json_success(array(
        'mode' => $mode,
    ));
}

// =============================================================================
// FRONTEND LOGIN HANDLER
// =============================================================================
add_action('wp_ajax_nopriv_tmw_login', 'tmw_ajax_login');

function tmw_ajax_login() {
    check_ajax_referer('tmw_nonce', 'nonce');

    $username = isset($_POST['username']) ? sanitize_user($_POST['username']) : '';
    $password = isset($_POST['password']) ? $_POST['password'] : '';
    $remember = isset($_POST['remember']) && $_POST['remember'] === 'true';

    if (empty($username) || empty($password)) {
        wp_send_json_error(array(
            'message' => __('Please enter your username and password.', 'flavor-starter-flavor'),
        ));
    }

    $user = wp_authenticate($username, $password);

    if (is_wp_error($user)) {
        wp_send_json_error(array(
            'message' => __('Invalid username or password.', 'flavor-starter-flavor'),
        ));
    }

    // Log the user in
    wp_set_auth_cookie($user->ID, $remember);
    wp_set_current_user($user->ID);

    // Determine redirect
    $redirect_to = isset($_POST['redirect_to']) ? esc_url_raw($_POST['redirect_to']) : '';
    
    if (empty($redirect_to)) {
        $setting = tmw_get_setting('login_redirect', 'app');
        switch ($setting) {
            case 'app':
                $redirect_to = tmw_get_app_url();
                break;
            case 'profile':
                $redirect_to = tmw_get_page_url('my-profile');
                break;
            default:
                $redirect_to = home_url('/');
        }
    }

    wp_send_json_success(array(
        'redirect' => $redirect_to,
        'message'  => __('Login successful! Redirecting...', 'flavor-starter-flavor'),
    ));
}

// =============================================================================
// FRONTEND REGISTRATION HANDLER
// =============================================================================
add_action('wp_ajax_nopriv_tmw_register', 'tmw_ajax_register');

function tmw_ajax_register() {
    check_ajax_referer('tmw_nonce', 'nonce');

    // Check if registration is allowed
    if (!get_option('users_can_register')) {
        wp_send_json_error(array(
            'message' => __('Registration is currently disabled.', 'flavor-starter-flavor'),
        ));
    }

    $first_name = isset($_POST['first_name']) ? sanitize_text_field($_POST['first_name']) : '';
    $last_name  = isset($_POST['last_name']) ? sanitize_text_field($_POST['last_name']) : '';
    $email      = isset($_POST['email']) ? sanitize_email($_POST['email']) : '';
    $password   = isset($_POST['password']) ? $_POST['password'] : '';
    $password_confirm = isset($_POST['password_confirm']) ? $_POST['password_confirm'] : '';

    // Validation
    $errors = array();

    if (empty($first_name)) {
        $errors[] = __('Please enter your first name.', 'flavor-starter-flavor');
    }

    if (empty($last_name)) {
        $errors[] = __('Please enter your last name.', 'flavor-starter-flavor');
    }

    if (empty($email)) {
        $errors[] = __('Please enter your email address.', 'flavor-starter-flavor');
    } elseif (!is_email($email)) {
        $errors[] = __('Please enter a valid email address.', 'flavor-starter-flavor');
    } elseif (email_exists($email)) {
        $errors[] = __('An account with this email already exists.', 'flavor-starter-flavor');
    }

    if (empty($password)) {
        $errors[] = __('Please enter a password.', 'flavor-starter-flavor');
    } elseif (strlen($password) < 8) {
        $errors[] = __('Password must be at least 8 characters long.', 'flavor-starter-flavor');
    }

    if ($password !== $password_confirm) {
        $errors[] = __('Passwords do not match.', 'flavor-starter-flavor');
    }

    if (!empty($errors)) {
        wp_send_json_error(array(
            'message' => implode('<br>', $errors),
        ));
    }

    // Generate username from email (part before @)
    $username_base = sanitize_user(strstr($email, '@', true), true);
    $username = $username_base;
    $counter = 1;
    
    // Ensure unique username
    while (username_exists($username)) {
        $username = $username_base . $counter;
        $counter++;
    }

    // Create user
    $user_id = wp_create_user($username, $password, $email);

    if (is_wp_error($user_id)) {
        wp_send_json_error(array(
            'message' => $user_id->get_error_message(),
        ));
    }

    // Set default role
    $user = new WP_User($user_id);
    $user->set_role(get_option('default_role', 'subscriber'));

    // Update user profile with name
    wp_update_user(array(
        'ID'           => $user_id,
        'first_name'   => $first_name,
        'last_name'    => $last_name,
        'display_name' => trim($first_name . ' ' . $last_name),
    ));

    // Set default subscription tier to free
    update_user_meta($user_id, 'tmw_subscription_tier', 'free');
    update_user_meta($user_id, 'tmw_subscription_status', 'active');

    // Auto-login
    wp_set_auth_cookie($user_id, true);
    wp_set_current_user($user_id);

    // Fire action for Simple Membership or other plugins
    do_action('tmw_user_registered', $user_id);

    // Redirect to app
    $redirect = tmw_get_app_url();

    wp_send_json_success(array(
        'redirect' => $redirect,
        'message'  => __('Registration successful! Redirecting...', 'flavor-starter-flavor'),
    ));
}

// =============================================================================
// FORGOT PASSWORD HANDLER
// =============================================================================
add_action('wp_ajax_nopriv_tmw_forgot_password', 'tmw_ajax_forgot_password');

function tmw_ajax_forgot_password() {
    check_ajax_referer('tmw_nonce', 'nonce');

    // Accept either 'email' or 'user_login' field name
    $user_login = '';
    if (isset($_POST['email']) && !empty($_POST['email'])) {
        $user_login = sanitize_text_field($_POST['email']);
    } elseif (isset($_POST['user_login']) && !empty($_POST['user_login'])) {
        $user_login = sanitize_text_field($_POST['user_login']);
    }

    if (empty($user_login)) {
        wp_send_json_error(array(
            'message' => __('Please enter your email address.', 'flavor-starter-flavor'),
        ));
    }

    // Get user
    if (is_email($user_login)) {
        $user = get_user_by('email', $user_login);
    } else {
        $user = get_user_by('login', $user_login);
    }

    if (!$user) {
        // Don't reveal if user exists
        wp_send_json_success(array(
            'message' => __('If an account exists with that username or email, you will receive a password reset link.', 'flavor-starter-flavor'),
        ));
    }

    // Generate reset key
    $key = get_password_reset_key($user);

    if (is_wp_error($key)) {
        wp_send_json_error(array(
            'message' => __('Unable to generate password reset link. Please try again later.', 'flavor-starter-flavor'),
        ));
    }

    // Build reset URL
    $reset_url = tmw_get_page_url('reset-password');
    if (!$reset_url) {
        $reset_url = network_site_url("wp-login.php?action=rp&key=$key&login=" . rawurlencode($user->user_login), 'login');
    } else {
        $reset_url = add_query_arg(array(
            'key'   => $key,
            'login' => rawurlencode($user->user_login),
        ), $reset_url);
    }

    // Send email
    $site_name = get_bloginfo('name');
    $message = sprintf(__('Someone has requested a password reset for the following account:', 'flavor-starter-flavor')) . "\r\n\r\n";
    $message .= sprintf(__('Site: %s', 'flavor-starter-flavor'), $site_name) . "\r\n";
    $message .= sprintf(__('Username: %s', 'flavor-starter-flavor'), $user->user_login) . "\r\n\r\n";
    $message .= __('If this was a mistake, ignore this email and nothing will happen.', 'flavor-starter-flavor') . "\r\n\r\n";
    $message .= __('To reset your password, visit the following address:', 'flavor-starter-flavor') . "\r\n\r\n";
    $message .= $reset_url . "\r\n";

    $subject = sprintf(__('[%s] Password Reset', 'flavor-starter-flavor'), $site_name);

    $sent = wp_mail($user->user_email, $subject, $message);

    wp_send_json_success(array(
        'message' => __('If an account exists with that username or email, you will receive a password reset link.', 'flavor-starter-flavor'),
    ));
}

// =============================================================================
// RESET PASSWORD HANDLER
// =============================================================================
add_action('wp_ajax_nopriv_tmw_reset_password', 'tmw_ajax_reset_password');

function tmw_ajax_reset_password() {
    check_ajax_referer('tmw_nonce', 'nonce');

    $key      = isset($_POST['key']) ? sanitize_text_field($_POST['key']) : '';
    $login    = isset($_POST['login']) ? sanitize_user($_POST['login']) : '';
    $password = isset($_POST['password']) ? $_POST['password'] : '';
    $password_confirm = isset($_POST['password_confirm']) ? $_POST['password_confirm'] : '';

    // Validate passwords
    if (empty($password) || strlen($password) < 8) {
        wp_send_json_error(array(
            'message' => __('Password must be at least 8 characters long.', 'flavor-starter-flavor'),
        ));
    }

    if ($password !== $password_confirm) {
        wp_send_json_error(array(
            'message' => __('Passwords do not match.', 'flavor-starter-flavor'),
        ));
    }

    // Verify key
    $user = check_password_reset_key($key, $login);

    if (is_wp_error($user)) {
        wp_send_json_error(array(
            'message' => __('This password reset link is invalid or has expired. Please request a new one.', 'flavor-starter-flavor'),
        ));
    }

    // Reset password
    reset_password($user, $password);

    wp_send_json_success(array(
        'message'  => __('Your password has been reset. You can now log in.', 'flavor-starter-flavor'),
        'redirect' => tmw_get_page_url('login'),
    ));
}

// =============================================================================
// UPDATE PROFILE HANDLER
// =============================================================================
add_action('wp_ajax_tmw_update_profile', 'tmw_ajax_update_profile');

function tmw_ajax_update_profile() {
    check_ajax_referer('tmw_nonce', 'nonce');

    if (!is_user_logged_in()) {
        wp_send_json_error(array(
            'message' => __('You must be logged in.', 'flavor-starter-flavor'),
        ));
    }

    $user_id = get_current_user_id();
    $current_user = wp_get_current_user();

    $display_name = isset($_POST['display_name']) ? sanitize_text_field($_POST['display_name']) : '';
    $email        = isset($_POST['email']) ? sanitize_email($_POST['email']) : '';
    $first_name   = isset($_POST['first_name']) ? sanitize_text_field($_POST['first_name']) : '';
    $last_name    = isset($_POST['last_name']) ? sanitize_text_field($_POST['last_name']) : '';

    $errors = array();

    // Validate email
    if (empty($email) || !is_email($email)) {
        $errors[] = __('Please enter a valid email address.', 'flavor-starter-flavor');
    } elseif ($email !== $current_user->user_email) {
        $existing = email_exists($email);
        if ($existing && $existing !== $user_id) {
            $errors[] = __('This email is already in use.', 'flavor-starter-flavor');
        }
    }

    if (!empty($errors)) {
        wp_send_json_error(array(
            'message' => implode('<br>', $errors),
        ));
    }

    // Update user
    $result = wp_update_user(array(
        'ID'           => $user_id,
        'display_name' => $display_name,
        'user_email'   => $email,
        'first_name'   => $first_name,
        'last_name'    => $last_name,
    ));

    if (is_wp_error($result)) {
        wp_send_json_error(array(
            'message' => $result->get_error_message(),
        ));
    }

    wp_send_json_success(array(
        'message' => __('Profile updated successfully.', 'flavor-starter-flavor'),
    ));
}

// =============================================================================
// CHANGE PASSWORD HANDLER
// =============================================================================
add_action('wp_ajax_tmw_change_password', 'tmw_ajax_change_password');

function tmw_ajax_change_password() {
    check_ajax_referer('tmw_nonce', 'nonce');

    if (!is_user_logged_in()) {
        wp_send_json_error(array(
            'message' => __('You must be logged in.', 'flavor-starter-flavor'),
        ));
    }

    $user_id = get_current_user_id();
    $user = wp_get_current_user();

    $current_password = isset($_POST['current_password']) ? $_POST['current_password'] : '';
    $new_password     = isset($_POST['new_password']) ? $_POST['new_password'] : '';
    $confirm_password = isset($_POST['confirm_password']) ? $_POST['confirm_password'] : '';

    // Verify current password
    if (!wp_check_password($current_password, $user->user_pass, $user_id)) {
        wp_send_json_error(array(
            'message' => __('Current password is incorrect.', 'flavor-starter-flavor'),
        ));
    }

    // Validate new password
    if (strlen($new_password) < 8) {
        wp_send_json_error(array(
            'message' => __('New password must be at least 8 characters long.', 'flavor-starter-flavor'),
        ));
    }

    if ($new_password !== $confirm_password) {
        wp_send_json_error(array(
            'message' => __('New passwords do not match.', 'flavor-starter-flavor'),
        ));
    }

    // Update password
    wp_set_password($new_password, $user_id);

    // Re-authenticate to keep user logged in
    wp_set_auth_cookie($user_id, true);

    wp_send_json_success(array(
        'message' => __('Password changed successfully.', 'flavor-starter-flavor'),
    ));
}
