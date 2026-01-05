<?php
/**
 * Theme functions for garage-maintenance-auth
 */

if (!defined('ABSPATH')) {
  exit;
}

function gm_auth_enqueue_assets() {
  // Use the app's modular CSS bundle that imports the split files.
  wp_enqueue_style(
    'gm-app-bundle',
    get_template_directory_uri() . '/assets/css/style.css',
    array(),
    filemtime(get_template_directory() . '/assets/css/style.css')
  );

  // Small theme-specific additions
  wp_add_inline_style('gm-app-bundle', gm_auth_inline_css());

  wp_enqueue_script(
    'gm-auth-theme',
    get_template_directory_uri() . '/assets/js/theme.js',
    array(),
    filemtime(get_template_directory() . '/assets/js/theme.js'),
    true
  );
}
add_action('wp_enqueue_scripts', 'gm_auth_enqueue_assets');

function gm_auth_inline_css() {
  // Keep this minimal so the look stays close to the app.
  return <<<CSS
/* WordPress auth theme additions */
body {
  align-items: center;
  padding: 16px;
}

.gm-auth-wrap {
  width: 100%;
  max-width: 560px;
}

.gm-auth-card {
  background: radial-gradient(circle at top left, rgba(56,189,248,0.08), transparent 55%), var(--bg-card);
  border-radius: var(--radius-lg);
  border: 1px solid var(--border);
  box-shadow: var(--shadow-card);
  padding: 18px 18px 14px;
}

.gm-auth-header {
  display: flex;
  align-items: center;
  gap: 12px;
  margin-bottom: 14px;
  padding-bottom: 14px;
  border-bottom: 1px solid rgba(55, 65, 81, 0.5);
}

.gm-auth-logo {
  width: 44px;
  height: 44px;
  border-radius: 12px;
  background: rgba(56,189,248,0.12);
  display: grid;
  place-items: center;
  overflow: hidden;
}

.gm-auth-logo img {
  width: 44px;
  height: 44px;
  display: block;
}

.gm-auth-title h1 {
  margin: 0;
  font-size: 1.2rem;
  letter-spacing: 0.02em;
}

.gm-auth-title p {
  margin: 4px 0 0;
  font-size: 0.85rem;
  color: var(--text-muted);
}

.gm-auth-links {
  display: flex;
  flex-wrap: wrap;
  gap: 10px;
  justify-content: space-between;
  margin-top: 10px;
  font-size: 0.85rem;
}

.gm-auth-links a {
  color: var(--accent);
  text-decoration: none;
}

.gm-auth-links a:hover {
  text-decoration: underline;
}

.gm-alert {
  border-radius: 12px;
  border: 1px solid rgba(148, 163, 184, 0.25);
  background: rgba(148, 163, 184, 0.08);
  padding: 10px 12px;
  font-size: 0.9rem;
  color: var(--text-main);
  margin: 0 0 12px;
}

.gm-alert.error {
  border-color: rgba(248,113,113,0.6);
  background: rgba(248,113,113,0.12);
}

.gm-alert.success {
  border-color: rgba(34,197,94,0.5);
  background: rgba(34,197,94,0.12);
}

.gm-row {
  display: grid;
  gap: 12px;
}

.gm-field {
  display: flex;
  flex-direction: column;
  gap: 6px;
}

.gm-field label {
  font-size: 0.82rem;
  color: var(--text-muted);
}

.gm-field input {
  width: 100%;
  font-size: 0.95rem;
  color: var(--text-main);
  background: var(--bg-elevated);
  border-radius: var(--radius);
  border: 1px solid var(--border-light);
  padding: 10px 12px;
  outline: none;
  transition: all 0.2s ease;
}

.gm-field input:focus {
  border-color: var(--accent);
  box-shadow: 0 0 0 2px var(--accent-soft);
}

.gm-inline {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 10px;
}

.gm-inline .hint {
  font-size: 0.82rem;
  color: var(--text-muted);
}

.gm-inline button.small {
  padding: 6px 12px;
  font-size: 0.8rem;
}

.gm-actions {
  display: flex;
  flex-wrap: wrap;
  gap: 10px;
  justify-content: flex-end;
  margin-top: 12px;
}

.gm-btn {
  border-radius: 999px;
  border: none;
  padding: 10px 18px;
  font-size: 0.9rem;
  cursor: pointer;
  display: inline-flex;
  align-items: center;
  gap: 6px;
  transition: all 0.15s ease;
  white-space: nowrap;
}

.gm-btn-primary {
  background: linear-gradient(135deg, #38bdf8, #0ea5e9);
  color: #020617;
  box-shadow: 0 8px 20px rgba(56,189,248,0.45);
}

.gm-btn-primary:hover {
  transform: translateY(-2px);
  box-shadow: 0 12px 30px rgba(56,189,248,0.6);
}

.gm-btn-ghost {
  background: var(--bg-elevated);
  color: var(--text-muted);
  border: 1px solid var(--border-light);
}

.gm-btn-ghost:hover {
  background: var(--bg-hover);
  color: var(--text-main);
}

.gm-footer-note {
  margin-top: 14px;
  font-size: 0.78rem;
  color: var(--text-muted);
  text-align: center;
}

@media (max-width: 650px) {
  .gm-auth-wrap {
    max-width: 520px;
  }
}
CSS;
}

/**
 * Helper: Resolve page link by slug if it exists.
 */
function gm_auth_page_url_by_slug($slug) {
  $page = get_page_by_path($slug);
  if ($page) {
    return get_permalink($page->ID);
  }
  return '';
}

function gm_auth_register_templates($templates) {
  $templates['template-register.php'] = 'GM Registration';
  $templates['template-profile.php']  = 'GM My Profile';
  return $templates;
}
add_filter('theme_page_templates', 'gm_auth_register_templates');

/**
 * Process registration form.
 */
function gm_auth_handle_register_post() {
  if (!isset($_POST['gm_register_nonce'])) return;
  if (!wp_verify_nonce($_POST['gm_register_nonce'], 'gm_register')) return;

  $redirect = isset($_POST['gm_redirect']) ? esc_url_raw($_POST['gm_redirect']) : home_url('/');

  $username = isset($_POST['username']) ? sanitize_user($_POST['username']) : '';
  $email    = isset($_POST['email']) ? sanitize_email($_POST['email']) : '';
  $pass1    = isset($_POST['pass1']) ? (string) $_POST['pass1'] : '';
  $pass2    = isset($_POST['pass2']) ? (string) $_POST['pass2'] : '';

  $errors = new WP_Error();

  if ($username === '' || $email === '' || $pass1 === '' || $pass2 === '') {
    $errors->add('missing', 'Please fill out all fields.');
  }
  if ($pass1 !== $pass2) {
    $errors->add('pass_mismatch', 'Passwords do not match.');
  }
  if (username_exists($username)) {
    $errors->add('user_exists', 'That username is already taken.');
  }
  if (!is_email($email) || email_exists($email)) {
    $errors->add('email', 'Please use a valid email that is not already in use.');
  }

  // Respect site setting: Anyone can register
  if (!get_option('users_can_register')) {
    $errors->add('registration_disabled', 'Registration is currently disabled on this site. (Enable "Anyone can register" in Settings → General.)');
  }

  if ($errors->has_errors()) {
    set_transient('gm_register_errors', $errors->get_error_messages(), 60);
    wp_safe_redirect(add_query_arg('reg', 'error', $redirect));
    exit;
  }

  $user_id = wp_create_user($username, $pass1, $email);
  if (is_wp_error($user_id)) {
    set_transient('gm_register_errors', array($user_id->get_error_message()), 60);
    wp_safe_redirect(add_query_arg('reg', 'error', $redirect));
    exit;
  }

  // Auto-login after registration.
  wp_set_current_user($user_id);
  wp_set_auth_cookie($user_id);

  set_transient('gm_register_success', 'Account created — you are now signed in.', 60);
  wp_safe_redirect($redirect);
  exit;
}
add_action('init', 'gm_auth_handle_register_post');

/**
 * Process profile updates.
 */
function gm_auth_handle_profile_post() {
  if (!isset($_POST['gm_profile_nonce'])) return;
  if (!wp_verify_nonce($_POST['gm_profile_nonce'], 'gm_profile')) return;
  if (!is_user_logged_in()) {
    wp_safe_redirect(wp_login_url(home_url('/')));
    exit;
  }

  $user_id = get_current_user_id();
  $user = get_user_by('id', $user_id);

  $display_name = isset($_POST['display_name']) ? sanitize_text_field($_POST['display_name']) : '';
  $email        = isset($_POST['email']) ? sanitize_email($_POST['email']) : '';
  $pass1        = isset($_POST['pass1']) ? (string) $_POST['pass1'] : '';
  $pass2        = isset($_POST['pass2']) ? (string) $_POST['pass2'] : '';

  $errors = new WP_Error();

  if ($display_name === '') {
    $errors->add('missing_name', 'Display name is required.');
  }

  if ($email === '' || !is_email($email)) {
    $errors->add('bad_email', 'Please provide a valid email.');
  } else {
    $owner = email_exists($email);
    if ($owner && (int)$owner !== (int)$user_id) {
      $errors->add('email_taken', 'That email is already used by another account.');
    }
  }

  if ($pass1 !== '' || $pass2 !== '') {
    if ($pass1 !== $pass2) {
      $errors->add('pass_mismatch', 'Passwords do not match.');
    } elseif (strlen($pass1) < 8) {
      $errors->add('pass_short', 'Password must be at least 8 characters.');
    }
  }

  if ($errors->has_errors()) {
    set_transient('gm_profile_errors_' . $user_id, $errors->get_error_messages(), 60);
    wp_safe_redirect(add_query_arg('profile', 'error', wp_get_referer() ?: home_url('/')));
    exit;
  }

  wp_update_user(array(
    'ID' => $user_id,
    'display_name' => $display_name,
    'user_email' => $email,
  ));

  if ($pass1 !== '' && $pass1 === $pass2) {
    wp_set_password($pass1, $user_id);
    // Keep user logged in after password change:
    wp_set_auth_cookie($user_id, true);
  }

  set_transient('gm_profile_success_' . $user_id, 'Profile updated.', 60);
  wp_safe_redirect(add_query_arg('profile', 'success', wp_get_referer() ?: home_url('/')));
  exit;
}
add_action('init', 'gm_auth_handle_profile_post');



/* === GM AUTH SETTINGS === */
/**
 * Admin settings page: Garage Maintenance → Settings
 * - Garage Web App URL (post-login destination)
 * - Subscribe Page (where user goes if not subscribed)
 */

function gm_auth_get_setting($key, $default = '') {
  $opts = get_option('gm_auth_settings', array());
  return isset($opts[$key]) ? $opts[$key] : $default;
}

function gm_auth_register_settings() {
  register_setting('gm_auth_settings_group', 'gm_auth_settings', array(
    'type' => 'array',
    'sanitize_callback' => 'gm_auth_sanitize_settings',
    'default' => array(
      'app_url' => '',
      'subscribe_page_id' => 0,
    ),
  ));

  add_settings_section(
    'gm_auth_settings_main',
    'Auth Redirect & Subscription',
    function() {
      echo '<p>Configure where users go after login and where to send users who are not subscribed. Subscription checks are filterable for future integrations.</p>';
    },
    'gm-auth-settings'
  );

  add_settings_field(
    'gm_auth_app_url',
    'Garage Web App URL',
    'gm_auth_render_field_app_url',
    'gm-auth-settings',
    'gm_auth_settings_main'
  );

  add_settings_field(
    'gm_auth_subscribe_page',
    'Subscribe Page',
    'gm_auth_render_field_subscribe_page',
    'gm-auth-settings',
    'gm_auth_settings_main'
  );
}
add_action('admin_init', 'gm_auth_register_settings');

function gm_auth_sanitize_settings($input) {
  $out = array();
  $out['app_url'] = isset($input['app_url']) ? esc_url_raw(trim($input['app_url'])) : '';
  $out['subscribe_page_id'] = isset($input['subscribe_page_id']) ? absint($input['subscribe_page_id']) : 0;
  return $out;
}

function gm_auth_admin_menu() {
  add_menu_page(
    'Garage Maintenance',
    'Garage Maintenance',
    'manage_options',
    'gm-auth-settings',
    'gm_auth_render_settings_page',
    'dashicons-car',
    59
  );
  add_submenu_page(
    'gm-auth-settings',
    'Settings',
    'Settings',
    'manage_options',
    'gm-auth-settings',
    'gm_auth_render_settings_page'
  );
}
add_action('admin_menu', 'gm_auth_admin_menu');

function gm_auth_render_settings_page() {
  if (!current_user_can('manage_options')) return;
  ?>
  <div class="wrap">
    <h1>Garage Maintenance — Settings</h1>
    <form method="post" action="options.php">
      <?php
        settings_fields('gm_auth_settings_group');
        do_settings_sections('gm-auth-settings');
        submit_button();
      ?>
    </form>
    <hr />
    <h2>Subscription integration (future)</h2>
    <p>This theme is ready for subscription checks. Your future subscription plugin can hook into:</p>
    <ul>
      <li><code>gm_auth_user_has_subscription</code> filter</li>
      <li><code>gm_auth_login_redirect_url</code> filter</li>
    </ul>
  </div>
  <?php
}

function gm_auth_render_field_app_url() {
  $val = gm_auth_get_setting('app_url', '');
  echo '<input type="url" name="gm_auth_settings[app_url]" value="' . esc_attr($val) . '" class="regular-text" placeholder="https://yourdomain.com/garage/" />';
  echo '<p class="description">Where to send subscribed users after successful login.</p>';
}

function gm_auth_render_field_subscribe_page() {
  $selected = (int) gm_auth_get_setting('subscribe_page_id', 0);
  wp_dropdown_pages(array(
    'name' => 'gm_auth_settings[subscribe_page_id]',
    'selected' => $selected,
    'show_option_none' => '— Select a page —',
    'option_none_value' => 0,
  ));
  echo '<p class="description">Where to send users who are not subscribed.</p>';
}

function gm_auth_get_subscribe_url() {
  $pid = (int) gm_auth_get_setting('subscribe_page_id', 0);
  if ($pid > 0) {
    $url = get_permalink($pid);
    if ($url) return $url;
  }
  // fallback to home
  return home_url('/');
}

/**
 * Subscription check stub. Your future subscription plugin should implement this filter:
 * return true/false for a given user ID.
 */
function gm_auth_user_is_subscribed($user_id) {
  $default = true;
  return (bool) apply_filters('gm_auth_user_has_subscription', $default, (int) $user_id);
}

/**
 * Determine post-login redirect URL based on subscription status.
 * Filterable for future expansions.
 */
function gm_auth_get_login_redirect_url($user_id = 0) {
  $user_id = (int) $user_id;
  $app_url = gm_auth_get_setting('app_url', '');
  $subscribe_url = gm_auth_get_subscribe_url();

  $dest = $app_url ? $app_url : home_url('/');

  if ($user_id > 0 && !gm_auth_user_is_subscribed($user_id)) {
    $dest = $subscribe_url;
  }

  return esc_url_raw(apply_filters('gm_auth_login_redirect_url', $dest, $user_id));
}

/**
 * Enforce our redirect after successful login (core + front-page form).
 */
function gm_auth_login_redirect_filter($redirect_to, $requested_redirect_to, $user) {
  if (is_wp_error($user) || !($user instanceof WP_User)) {
    return $redirect_to;
  }

  // Always honor subscription check destination.
  return gm_auth_get_login_redirect_url($user->ID);
}
add_filter('login_redirect', 'gm_auth_login_redirect_filter', 10, 3);




/**
 * Subscription check used by the theme's login redirect logic.
 *
 * Seamless mode:
 * - If no Subscribe Page is selected in settings, we assume subscription gating is not enabled and return true.
 * - If a Subscribe Page IS selected, we run lightweight checks compatible with common subscription/membership plugins,
 *   plus role + user meta checks that mirror the Garage app defaults.
 *
 * You can still override everything in a future plugin by filtering: gm_auth_user_has_subscription
 */
function gm_auth_user_has_subscription_filter_impl($default, $user_id) {
  $opts = get_option('gm_auth_settings', array());
  $subscribe_pid = isset($opts['subscribe_page_id']) ? (int)$opts['subscribe_page_id'] : 0;

  // If no Subscribe Page is configured, don't enforce gating in the theme.
  if ($subscribe_pid <= 0) {
    return true;
  }

  $user = get_user_by('id', (int)$user_id);
  if (!$user) {
    return false;
  }

  // 0) Role check (mirrors garage app config default)
  $required_role = 'subscriber';
  if (!empty($user->roles) && in_array($required_role, (array)$user->roles, true)) {
    return true;
  }

  // 1) WooCommerce Subscriptions
  if (function_exists('wcs_user_has_subscription')) {
    if (wcs_user_has_subscription((int)$user_id, '', 'active')) {
      return true;
    }
  }

  // 2) Paid Memberships Pro
  if (function_exists('pmpro_hasMembershipLevel')) {
    if (pmpro_hasMembershipLevel(null, (int)$user_id)) {
      return true;
    }
  }

  // 3) MemberPress
  if (class_exists('MeprUser')) {
    $mepr_user = new MeprUser((int)$user_id);
    if (method_exists($mepr_user, 'is_active') && $mepr_user->is_active()) {
      return true;
    }
  }

  // 4) Restrict Content Pro
  if (function_exists('rcp_is_active')) {
    if (rcp_is_active((int)$user_id)) {
      return true;
    }
  }

  // 5) Ultimate Member (role-based or meta)
  if (function_exists('um_user')) {
    // UM may use roles/caps; fall through to meta checks below.
  }

  // 6) Custom user meta flag (mirrors garage app config default)
  $meta_key = 'garage_subscription_active';
  $status = get_user_meta((int)$user_id, $meta_key, true);
  if ($status === 'active' || $status === '1' || $status === 1 || $status === true) {
    return true;
  }

  // 7) Optional expiry meta
  $expiry = get_user_meta((int)$user_id, 'garage_subscription_expiry', true);
  if ($expiry && strtotime((string)$expiry) > time()) {
    return true;
  }

  return false;
}
add_filter('gm_auth_user_has_subscription', 'gm_auth_user_has_subscription_filter_impl', 20, 2);

