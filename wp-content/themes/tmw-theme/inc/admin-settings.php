<?php
/**
 * Admin Settings Page - Dynamic Subscription Management
 *
 * Tabs:
 * 1. General Settings - App URL, theme defaults, redirects
 * 2. Manage Tiers - Add/edit/delete subscription tiers
 * 3. Manage Limits - Add/edit/delete limit definitions
 * 4. Tier Values - Set limit values per tier
 *
 * @package flavor-starter-flavor
 */

if (!defined('ABSPATH')) {
    exit;
}

// =============================================================================
// REGISTER SETTINGS
// =============================================================================
add_action('admin_init', 'tmw_register_settings');

function tmw_register_settings() {
    register_setting('tmw_settings_group', 'tmw_settings', array(
        'type'              => 'array',
        'sanitize_callback' => 'tmw_sanitize_settings',
        'default'           => tmw_get_default_settings(),
    ));

    register_setting('tmw_tiers_group', 'tmw_tiers', array(
        'type'              => 'array',
        'sanitize_callback' => 'tmw_sanitize_tiers',
        'default'           => tmw_get_default_tiers(),
    ));

    register_setting('tmw_limits_group', 'tmw_limit_definitions', array(
        'type'              => 'array',
        'sanitize_callback' => 'tmw_sanitize_limit_definitions',
        'default'           => tmw_get_default_limit_definitions(),
    ));

    register_setting('tmw_tier_values_group', 'tmw_tier_values', array(
        'type'              => 'array',
        'sanitize_callback' => 'tmw_sanitize_tier_values',
        'default'           => tmw_get_default_tier_values(),
    ));
    
    // GarageMinder App Configuration
    register_setting('tmw_gm_config_group', 'tmw_gm_config', array(
        'type'              => 'array',
        'sanitize_callback' => 'tmw_sanitize_gm_config',
        'default'           => tmw_get_default_gm_config(),
    ));
}

// =============================================================================
// DEFAULT VALUES
// =============================================================================
function tmw_get_default_settings() {
    return array(
        'app_url'           => '',
        'default_theme'     => 'dark',
        'login_redirect'    => 'app',
        'logout_redirect'   => 'home',
        'membership_plugin' => 'simple-membership',
    );
}

function tmw_get_default_gm_config() {
    return array(
        // App Branding
        'app_name'                      => 'TrackMyWrench',
        'app_short_name'                => 'TrackMyWrench',
        'app_domain'                    => 'trackmywrench.com',
        'app_tagline'                   => '',
        'app_copyright_year'            => date('Y'),
        'app_version'                   => '2.4.1.1',
        
        // App Settings
        'dashboard_history_per_page'    => 10,
        'entry_max_attachments'         => 2,
        'entry_max_attachment_size_mb'  => 5,
        
        // File Storage
        'allowed_extensions'            => 'pdf,doc,docx,jpg,jpeg,png,gif,webp',
        'attachments_url_base'          => '/garage/download.php?id=',
        
        // Security
        'require_https'                 => false,
        'api_rate_limit_per_minute'     => 60,
        'session_timeout_minutes'       => 120,
        
        // WordPress Integration
        'enable_multi_user'             => true,
        'require_subscription'          => false,
        'custom_login_url'              => '/gm/',
        'custom_logout_url'             => '/gm/',
        'custom_register_url'           => '/register/',
        'custom_profile_url'            => '/my-profile/',
        'custom_subscribe_url'          => '',
        'logout_redirect_url'           => '/gm/',
        
        // Google Drive
        'google_drive_enabled'          => false,
        'google_client_id'              => '',
        'google_client_secret'          => '',
        'google_redirect_uri'           => '',
        'google_drive_folder_name'      => 'TrackMyWrench Attachments',
    );
}

function tmw_get_default_tiers() {
    return array(
        'free' => array(
            'name'          => 'Free',
            'description'   => 'Basic free access',
            'swpm_level_id' => 1,
            'is_free'       => true,
            'order'         => 1,
            'color'         => '#6b7280',
        ),
        'paid' => array(
            'name'          => 'Paid',
            'description'   => 'Full features for individuals',
            'swpm_level_id' => 2,
            'is_free'       => false,
            'order'         => 2,
            'color'         => '#3b82f6',
        ),
        'fleet' => array(
            'name'          => 'Fleet',
            'description'   => 'Unlimited for businesses',
            'swpm_level_id' => 3,
            'is_free'       => false,
            'order'         => 3,
            'color'         => '#8b5cf6',
        ),
    );
}

function tmw_get_default_limit_definitions() {
    return array(
        'max_vehicles' => array(
            'label'       => 'Maximum Vehicles',
            'type'        => 'number',
            'description' => 'How many vehicles user can add (-1 for unlimited)',
        ),
        'max_entries' => array(
            'label'       => 'Maximum Entries',
            'type'        => 'number',
            'description' => 'Total service entries allowed (-1 for unlimited)',
        ),
        'attachments_per_entry' => array(
            'label'       => 'Attachments per Entry',
            'type'        => 'number',
            'description' => 'File attachments per service entry',
        ),
        'max_templates' => array(
            'label'       => 'Maximum Templates',
            'type'        => 'number',
            'description' => 'Entry templates allowed (-1 for unlimited)',
        ),
        'recalls_enabled' => array(
            'label'       => 'Recall Alerts',
            'type'        => 'boolean',
            'description' => 'Can check vehicle recalls',
        ),
        'vehicle_photos' => array(
            'label'       => 'Vehicle Photos',
            'type'        => 'boolean',
            'description' => 'Can upload vehicle photos',
        ),
        'export_level' => array(
            'label'       => 'Export Level',
            'type'        => 'select',
            'options'     => array('none', 'basic', 'advanced'),
            'description' => 'Export: none, basic (CSV/PDF), advanced (bulk)',
        ),
        'api_access' => array(
            'label'       => 'API Access',
            'type'        => 'boolean',
            'description' => 'Access to REST API',
        ),
        'team_members' => array(
            'label'       => 'Team Members',
            'type'        => 'number',
            'description' => 'Additional team members allowed (0 = none)',
        ),
    );
}

function tmw_get_default_tier_values() {
    return array(
        'free' => array(
            'max_vehicles'          => 2,
            'max_entries'           => 50,
            'attachments_per_entry' => 0,
            'max_templates'         => 3,
            'recalls_enabled'       => false,
            'vehicle_photos'        => false,
            'export_level'          => 'none',
            'api_access'            => false,
            'team_members'          => 0,
        ),
        'paid' => array(
            'max_vehicles'          => 10,
            'max_entries'           => -1,
            'attachments_per_entry' => 2,
            'max_templates'         => 15,
            'recalls_enabled'       => true,
            'vehicle_photos'        => true,
            'export_level'          => 'basic',
            'api_access'            => false,
            'team_members'          => 0,
        ),
        'fleet' => array(
            'max_vehicles'          => -1,
            'max_entries'           => -1,
            'attachments_per_entry' => 5,
            'max_templates'         => -1,
            'recalls_enabled'       => true,
            'vehicle_photos'        => true,
            'export_level'          => 'advanced',
            'api_access'            => true,
            'team_members'          => 10,
        ),
    );
}

// =============================================================================
// GETTER FUNCTIONS
// =============================================================================
function tmw_get_tiers() {
    $tiers = get_option('tmw_tiers', array());
    if (empty($tiers)) {
        $tiers = tmw_get_default_tiers();
    }
    uasort($tiers, function($a, $b) {
        return ($a['order'] ?? 99) - ($b['order'] ?? 99);
    });
    return $tiers;
}

function tmw_get_tier($slug) {
    $tiers = tmw_get_tiers();
    return isset($tiers[$slug]) ? $tiers[$slug] : null;
}

function tmw_get_limit_definitions() {
    $limits = get_option('tmw_limit_definitions', array());
    if (empty($limits)) {
        $limits = tmw_get_default_limit_definitions();
    }
    return $limits;
}

function tmw_get_tier_values() {
    $values = get_option('tmw_tier_values', array());
    if (empty($values)) {
        $values = tmw_get_default_tier_values();
    }
    return $values;
}

function tmw_get_tier_value($tier_slug, $limit_key) {
    $values = tmw_get_tier_values();
    if (isset($values[$tier_slug][$limit_key])) {
        return $values[$tier_slug][$limit_key];
    }
    $limits = tmw_get_limit_definitions();
    if (isset($limits[$limit_key])) {
        $type = $limits[$limit_key]['type'] ?? 'number';
        return $type === 'boolean' ? false : ($type === 'select' ? 'none' : 0);
    }
    return null;
}

// Backward compatibility with old level mapping
function tmw_get_level_mapping($key = null, $default = null) {
    $tiers = tmw_get_tiers();
    $mapping = array('fallback_tier' => 'free');
    
    foreach ($tiers as $slug => $tier) {
        $mapping[$slug . '_level_id'] = $tier['swpm_level_id'] ?? 0;
    }
    
    if ($key !== null) {
        return isset($mapping[$key]) ? $mapping[$key] : $default;
    }
    return $mapping;
}

// Get GarageMinder App Configuration
function tmw_get_gm_config($key = null, $default = null) {
    $config = get_option('tmw_gm_config', array());
    if (empty($config)) {
        $config = tmw_get_default_gm_config();
    }
    
    if ($key !== null) {
        return isset($config[$key]) ? $config[$key] : $default;
    }
    return $config;
}

// =============================================================================
// SANITIZATION
// =============================================================================
function tmw_sanitize_settings($input) {
    $sanitized = array();
    $sanitized['app_url'] = isset($input['app_url']) ? esc_url_raw(trim($input['app_url'])) : '';
    $sanitized['default_theme'] = isset($input['default_theme']) && in_array($input['default_theme'], array('dark', 'light')) 
        ? $input['default_theme'] : 'dark';
    $sanitized['login_redirect'] = isset($input['login_redirect']) ? sanitize_key($input['login_redirect']) : 'app';
    $sanitized['logout_redirect'] = isset($input['logout_redirect']) ? sanitize_key($input['logout_redirect']) : 'home';
    $sanitized['membership_plugin'] = isset($input['membership_plugin']) ? sanitize_key($input['membership_plugin']) : 'simple-membership';
    return $sanitized;
}

function tmw_sanitize_gm_config($input) {
    $sanitized = array();
    
    // App Branding
    $sanitized['app_name'] = isset($input['app_name']) ? sanitize_text_field($input['app_name']) : 'TrackMyWrench';
    $sanitized['app_short_name'] = isset($input['app_short_name']) ? sanitize_text_field($input['app_short_name']) : 'TrackMyWrench';
    $sanitized['app_domain'] = isset($input['app_domain']) ? sanitize_text_field($input['app_domain']) : 'trackmywrench.com';
    $sanitized['app_tagline'] = isset($input['app_tagline']) ? sanitize_text_field($input['app_tagline']) : '';
    $sanitized['app_copyright_year'] = isset($input['app_copyright_year']) ? sanitize_text_field($input['app_copyright_year']) : date('Y');
    $sanitized['app_version'] = isset($input['app_version']) ? sanitize_text_field($input['app_version']) : '2.4.1.1';
    
    // App Settings
    $sanitized['dashboard_history_per_page'] = isset($input['dashboard_history_per_page']) ? absint($input['dashboard_history_per_page']) : 10;
    $sanitized['entry_max_attachments'] = isset($input['entry_max_attachments']) ? absint($input['entry_max_attachments']) : 2;
    $sanitized['entry_max_attachment_size_mb'] = isset($input['entry_max_attachment_size_mb']) ? absint($input['entry_max_attachment_size_mb']) : 5;
    
    // File Storage
    $sanitized['allowed_extensions'] = isset($input['allowed_extensions']) ? sanitize_text_field($input['allowed_extensions']) : 'pdf,doc,docx,jpg,jpeg,png,gif,webp';
    $sanitized['attachments_url_base'] = isset($input['attachments_url_base']) ? sanitize_text_field($input['attachments_url_base']) : '/garage/download.php?id=';
    
    // Security
    $sanitized['require_https'] = !empty($input['require_https']);
    $sanitized['api_rate_limit_per_minute'] = isset($input['api_rate_limit_per_minute']) ? absint($input['api_rate_limit_per_minute']) : 60;
    $sanitized['session_timeout_minutes'] = isset($input['session_timeout_minutes']) ? absint($input['session_timeout_minutes']) : 120;
    
    // WordPress Integration
    $sanitized['enable_multi_user'] = !empty($input['enable_multi_user']);
    $sanitized['require_subscription'] = !empty($input['require_subscription']);
    $sanitized['custom_login_url'] = isset($input['custom_login_url']) ? sanitize_text_field($input['custom_login_url']) : '/gm/';
    $sanitized['custom_logout_url'] = isset($input['custom_logout_url']) ? sanitize_text_field($input['custom_logout_url']) : '/gm/';
    $sanitized['custom_register_url'] = isset($input['custom_register_url']) ? sanitize_text_field($input['custom_register_url']) : '/register/';
    $sanitized['custom_profile_url'] = isset($input['custom_profile_url']) ? sanitize_text_field($input['custom_profile_url']) : '/my-profile/';
    $sanitized['custom_subscribe_url'] = isset($input['custom_subscribe_url']) ? sanitize_text_field($input['custom_subscribe_url']) : '';
    $sanitized['logout_redirect_url'] = isset($input['logout_redirect_url']) ? sanitize_text_field($input['logout_redirect_url']) : '/gm/';
    
    // Google Drive
    $sanitized['google_drive_enabled'] = !empty($input['google_drive_enabled']);
    $sanitized['google_client_id'] = isset($input['google_client_id']) ? sanitize_text_field($input['google_client_id']) : '';
    $sanitized['google_client_secret'] = isset($input['google_client_secret']) ? sanitize_text_field($input['google_client_secret']) : '';
    $sanitized['google_redirect_uri'] = isset($input['google_redirect_uri']) ? esc_url_raw($input['google_redirect_uri']) : '';
    $sanitized['google_drive_folder_name'] = isset($input['google_drive_folder_name']) ? sanitize_text_field($input['google_drive_folder_name']) : 'TrackMyWrench Attachments';
    
    return $sanitized;
}

function tmw_sanitize_tiers($input) {
    $sanitized = array();
    if (!is_array($input)) return tmw_get_default_tiers();
    
    foreach ($input as $slug => $tier) {
        $slug = sanitize_key($slug);
        if (empty($slug)) continue;
        
        $sanitized[$slug] = array(
            'name'          => isset($tier['name']) ? sanitize_text_field($tier['name']) : ucfirst($slug),
            'description'   => isset($tier['description']) ? sanitize_text_field($tier['description']) : '',
            'swpm_level_id' => isset($tier['swpm_level_id']) ? absint($tier['swpm_level_id']) : 0,
            'is_free'       => !empty($tier['is_free']),
            'order'         => isset($tier['order']) ? absint($tier['order']) : 99,
            'color'         => isset($tier['color']) ? sanitize_hex_color($tier['color']) : '#6b7280',
        );
    }
    return $sanitized;
}

function tmw_sanitize_limit_definitions($input) {
    $sanitized = array();
    if (!is_array($input)) return tmw_get_default_limit_definitions();
    
    foreach ($input as $key => $limit) {
        $key = sanitize_key($key);
        if (empty($key)) continue;
        
        $type = isset($limit['type']) && in_array($limit['type'], array('number', 'boolean', 'select')) 
            ? $limit['type'] : 'number';
        
        $sanitized[$key] = array(
            'label'       => isset($limit['label']) ? sanitize_text_field($limit['label']) : ucfirst(str_replace('_', ' ', $key)),
            'type'        => $type,
            'description' => isset($limit['description']) ? sanitize_text_field($limit['description']) : '',
        );
        
        if ($type === 'select' && isset($limit['options'])) {
            if (is_array($limit['options'])) {
                $sanitized[$key]['options'] = array_map('sanitize_text_field', $limit['options']);
            } else {
                $sanitized[$key]['options'] = array_map('trim', explode(',', sanitize_text_field($limit['options'])));
            }
        }
    }
    return $sanitized;
}

function tmw_sanitize_tier_values($input) {
    $sanitized = array();
    $tiers = tmw_get_tiers();
    $limits = tmw_get_limit_definitions();
    
    if (!is_array($input)) return tmw_get_default_tier_values();
    
    foreach ($tiers as $tier_slug => $tier) {
        $sanitized[$tier_slug] = array();
        
        foreach ($limits as $limit_key => $limit) {
            $value = isset($input[$tier_slug][$limit_key]) ? $input[$tier_slug][$limit_key] : null;
            
            switch ($limit['type']) {
                case 'boolean':
                    $sanitized[$tier_slug][$limit_key] = !empty($value);
                    break;
                case 'select':
                    $options = $limit['options'] ?? array();
                    $sanitized[$tier_slug][$limit_key] = in_array($value, $options) ? $value : ($options[0] ?? 'none');
                    break;
                default:
                    $sanitized[$tier_slug][$limit_key] = intval($value);
                    break;
            }
        }
    }
    return $sanitized;
}

// =============================================================================
// ADMIN MENU
// =============================================================================
add_action('admin_menu', 'tmw_admin_menu');

function tmw_admin_menu() {
    add_menu_page(
        __('TrackMyWrench', 'flavor-starter-flavor'),
        __('TrackMyWrench', 'flavor-starter-flavor'),
        'manage_options',
        'tmw-settings',
        'tmw_render_settings_page',
        'dashicons-car',
        59
    );
}

// =============================================================================
// AJAX HANDLERS
// =============================================================================
add_action('wp_ajax_tmw_save_tier', 'tmw_ajax_save_tier');
add_action('wp_ajax_tmw_delete_tier', 'tmw_ajax_delete_tier');
add_action('wp_ajax_tmw_save_limit', 'tmw_ajax_save_limit');
add_action('wp_ajax_tmw_delete_limit', 'tmw_ajax_delete_limit');
add_action('wp_ajax_tmw_save_tier_values', 'tmw_ajax_save_tier_values');

function tmw_ajax_save_tier() {
    check_ajax_referer('tmw_admin_nonce', 'nonce');
    if (!current_user_can('manage_options')) wp_send_json_error('Unauthorized');
    
    $slug = sanitize_key($_POST['slug'] ?? '');
    $original_slug = sanitize_key($_POST['original_slug'] ?? '');
    $data = $_POST['data'] ?? array();
    
    if (empty($slug)) wp_send_json_error('Tier slug is required');
    
    $tiers = tmw_get_tiers();
    $tier_values = tmw_get_tier_values();
    
    if ($original_slug && $original_slug !== $slug && isset($tiers[$original_slug])) {
        unset($tiers[$original_slug]);
        if (isset($tier_values[$original_slug])) {
            $tier_values[$slug] = $tier_values[$original_slug];
            unset($tier_values[$original_slug]);
        }
    }
    
    $tiers[$slug] = array(
        'name'                    => sanitize_text_field($data['name'] ?? ucfirst($slug)),
        'description'             => sanitize_text_field($data['description'] ?? ''),
        'swpm_level_id'           => absint($data['swpm_level_id'] ?? 0),
        'is_free'                 => !empty($data['is_free']),
        'order'                   => absint($data['order'] ?? count($tiers) + 1),
        'color'                   => sanitize_hex_color($data['color'] ?? '#6b7280') ?: '#6b7280',
    );
    
    // Allow plugins to extend tier data (for backwards compatibility)
    $tiers[$slug] = apply_filters('tmw_sanitize_tier_data', $tiers[$slug], $data, $slug);
    
    if (!isset($tier_values[$slug])) {
        $limits = tmw_get_limit_definitions();
        $tier_values[$slug] = array();
        foreach ($limits as $key => $limit) {
            $tier_values[$slug][$key] = $limit['type'] === 'boolean' ? false : ($limit['type'] === 'select' ? 'none' : 0);
        }
    }
    
    update_option('tmw_tiers', $tiers);
    update_option('tmw_tier_values', $tier_values);
    wp_send_json_success(array('slug' => $slug, 'tier' => $tiers[$slug]));
}

function tmw_ajax_delete_tier() {
    check_ajax_referer('tmw_admin_nonce', 'nonce');
    if (!current_user_can('manage_options')) wp_send_json_error('Unauthorized');
    
    $slug = sanitize_key($_POST['slug'] ?? '');
    if (empty($slug)) wp_send_json_error('Tier slug is required');
    
    $tiers = tmw_get_tiers();
    if (count($tiers) <= 1) wp_send_json_error('Cannot delete the last tier');
    
    unset($tiers[$slug]);
    update_option('tmw_tiers', $tiers);
    
    $tier_values = tmw_get_tier_values();
    unset($tier_values[$slug]);
    update_option('tmw_tier_values', $tier_values);
    
    wp_send_json_success();
}

function tmw_ajax_save_limit() {
    check_ajax_referer('tmw_admin_nonce', 'nonce');
    if (!current_user_can('manage_options')) wp_send_json_error('Unauthorized');
    
    $key = sanitize_key($_POST['key'] ?? '');
    $original_key = sanitize_key($_POST['original_key'] ?? '');
    $data = $_POST['data'] ?? array();
    
    if (empty($key)) wp_send_json_error('Limit key is required');
    
    $limits = tmw_get_limit_definitions();
    $tier_values = tmw_get_tier_values();
    
    if ($original_key && $original_key !== $key && isset($limits[$original_key])) {
        unset($limits[$original_key]);
        foreach ($tier_values as $tier_slug => &$values) {
            if (isset($values[$original_key])) {
                $values[$key] = $values[$original_key];
                unset($values[$original_key]);
            }
        }
    }
    
    $type = isset($data['type']) && in_array($data['type'], array('number', 'boolean', 'select')) 
        ? $data['type'] : 'number';
    
    $limits[$key] = array(
        'label'       => sanitize_text_field($data['label'] ?? ucfirst(str_replace('_', ' ', $key))),
        'type'        => $type,
        'description' => sanitize_text_field($data['description'] ?? ''),
    );
    
    if ($type === 'select') {
        $options = $data['options'] ?? 'none';
        $limits[$key]['options'] = is_array($options) 
            ? array_map('sanitize_text_field', $options)
            : array_map('trim', explode(',', sanitize_text_field($options)));
    }
    
    $default_value = $type === 'boolean' ? false : ($type === 'select' ? ($limits[$key]['options'][0] ?? 'none') : 0);
    foreach ($tier_values as $tier_slug => &$values) {
        if (!isset($values[$key])) $values[$key] = $default_value;
    }
    
    update_option('tmw_limit_definitions', $limits);
    update_option('tmw_tier_values', $tier_values);
    wp_send_json_success(array('key' => $key, 'limit' => $limits[$key]));
}

function tmw_ajax_delete_limit() {
    check_ajax_referer('tmw_admin_nonce', 'nonce');
    if (!current_user_can('manage_options')) wp_send_json_error('Unauthorized');
    
    $key = sanitize_key($_POST['key'] ?? '');
    if (empty($key)) wp_send_json_error('Limit key is required');
    
    $limits = tmw_get_limit_definitions();
    unset($limits[$key]);
    update_option('tmw_limit_definitions', $limits);
    
    $tier_values = tmw_get_tier_values();
    foreach ($tier_values as $tier_slug => &$values) {
        unset($values[$key]);
    }
    update_option('tmw_tier_values', $tier_values);
    
    wp_send_json_success();
}

function tmw_ajax_save_tier_values() {
    check_ajax_referer('tmw_admin_nonce', 'nonce');
    if (!current_user_can('manage_options')) wp_send_json_error('Unauthorized');
    
    $values = $_POST['values'] ?? array();
    $sanitized = tmw_sanitize_tier_values($values);
    update_option('tmw_tier_values', $sanitized);
    wp_send_json_success();
}

// =============================================================================
// RENDER SETTINGS PAGE
// =============================================================================
function tmw_render_settings_page() {
    if (!current_user_can('manage_options')) return;

    $active_tab = isset($_GET['tab']) ? sanitize_key($_GET['tab']) : 'general';
    ?>
    <div class="wrap tmw-admin-wrap">
        <h1><?php echo esc_html(get_admin_page_title()); ?></h1>

        <nav class="nav-tab-wrapper">
            <a href="?page=tmw-settings&tab=general" class="nav-tab <?php echo $active_tab === 'general' ? 'nav-tab-active' : ''; ?>"><?php _e('General', 'flavor-starter-flavor'); ?></a>
            <a href="?page=tmw-settings&tab=tiers" class="nav-tab <?php echo $active_tab === 'tiers' ? 'nav-tab-active' : ''; ?>"><?php _e('Manage Tiers', 'flavor-starter-flavor'); ?></a>
            <a href="?page=tmw-settings&tab=limits" class="nav-tab <?php echo $active_tab === 'limits' ? 'nav-tab-active' : ''; ?>"><?php _e('Manage Limits', 'flavor-starter-flavor'); ?></a>
            <a href="?page=tmw-settings&tab=values" class="nav-tab <?php echo $active_tab === 'values' ? 'nav-tab-active' : ''; ?>"><?php _e('Tier Values', 'flavor-starter-flavor'); ?></a>
        </nav>

        <div class="tmw-settings-content">
            <?php
            switch ($active_tab) {
                case 'tiers': tmw_render_tiers_tab(); break;
                case 'limits': tmw_render_limits_tab(); break;
                case 'values': tmw_render_values_tab(); break;
                default: tmw_render_general_tab(); break;
            }
            ?>
        </div>
    </div>
    <?php tmw_render_admin_scripts();
}

// =============================================================================
// TAB RENDERS
// =============================================================================
function tmw_render_general_tab() {
    $settings = wp_parse_args(get_option('tmw_settings', array()), tmw_get_default_settings());
    $gm_config = wp_parse_args(get_option('tmw_gm_config', array()), tmw_get_default_gm_config());
    ?>
    
    <h2>WordPress Settings</h2>
    <form method="post" action="options.php">
        <?php settings_fields('tmw_settings_group'); ?>
        <table class="form-table">
            <tr>
                <th><label for="app_url"><?php _e('GarageMinder App URL', 'flavor-starter-flavor'); ?></label></th>
                <td>
                    <input type="url" id="app_url" name="tmw_settings[app_url]" value="<?php echo esc_attr($settings['app_url']); ?>" class="regular-text" placeholder="https://app.trackmywrench.com/">
                    <p class="description"><?php _e('Full URL to the GarageMinder app.', 'flavor-starter-flavor'); ?></p>
                </td>
            </tr>
            <tr>
                <th><label for="default_theme"><?php _e('Default Theme', 'flavor-starter-flavor'); ?></label></th>
                <td>
                    <select id="default_theme" name="tmw_settings[default_theme]">
                        <option value="dark" <?php selected($settings['default_theme'], 'dark'); ?>>Dark</option>
                        <option value="light" <?php selected($settings['default_theme'], 'light'); ?>>Light</option>
                    </select>
                </td>
            </tr>
            <tr>
                <th><label for="login_redirect"><?php _e('After Login Redirect', 'flavor-starter-flavor'); ?></label></th>
                <td>
                    <select id="login_redirect" name="tmw_settings[login_redirect]">
                        <option value="app" <?php selected($settings['login_redirect'], 'app'); ?>>GarageMinder App</option>
                        <option value="profile" <?php selected($settings['login_redirect'], 'profile'); ?>>Profile Page</option>
                        <option value="home" <?php selected($settings['login_redirect'], 'home'); ?>>Home Page</option>
                    </select>
                </td>
            </tr>
            <tr>
                <th><label for="membership_plugin"><?php _e('Membership Plugin', 'flavor-starter-flavor'); ?></label></th>
                <td>
                    <select id="membership_plugin" name="tmw_settings[membership_plugin]">
                        <option value="simple-membership" <?php selected($settings['membership_plugin'], 'simple-membership'); ?>>Simple Membership</option>
                        <option value="stripe" <?php selected($settings['membership_plugin'], 'stripe'); ?>>Stripe Subscriptions</option>
                        <option value="user-meta" <?php selected($settings['membership_plugin'], 'user-meta'); ?>>User Meta Only</option>
                    </select>
                    <p class="description">
                        <?php _e('Select your subscription/membership plugin. Stripe requires the TMW Stripe Subscriptions plugin.', 'flavor-starter-flavor'); ?>
                    </p>
                </td>
            </tr>
        </table>
        <?php submit_button(); ?>
    </form>
    
    <hr style="margin: 40px 0;">
    
    <h2>GarageMinder App Configuration</h2>
    <p class="description" style="margin-bottom: 20px;">Configure the GarageMinder app settings. These settings override the values in config.php.</p>
    
    <form method="post" action="options.php">
        <?php settings_fields('tmw_gm_config_group'); ?>
        
        <!-- App Branding Section -->
        <h3 class="tmw-section-title" style="cursor: pointer; padding: 10px; background: #f5f5f5; margin: 20px 0 0 0;" onclick="jQuery('#gm-branding-section').slideToggle()">
            <span class="dashicons dashicons-arrow-down-alt2" style="margin-top: 2px;"></span> App Branding
        </h3>
        <table class="form-table" id="gm-branding-section">
            <tr>
                <th><label for="app_name">App Name</label></th>
                <td>
                    <input type="text" id="app_name" name="tmw_gm_config[app_name]" value="<?php echo esc_attr($gm_config['app_name']); ?>" class="regular-text">
                    <p class="description">Full app name (used in exports, disclaimers, copyright)</p>
                </td>
            </tr>
            <tr>
                <th><label for="app_short_name">App Short Name</label></th>
                <td>
                    <input type="text" id="app_short_name" name="tmw_gm_config[app_short_name]" value="<?php echo esc_attr($gm_config['app_short_name']); ?>" class="regular-text">
                    <p class="description">Short name for PWA/mobile app title</p>
                </td>
            </tr>
            <tr>
                <th><label for="app_domain">App Domain</label></th>
                <td>
                    <input type="text" id="app_domain" name="tmw_gm_config[app_domain]" value="<?php echo esc_attr($gm_config['app_domain']); ?>" class="regular-text">
                    <p class="description">Domain for branding/alt text</p>
                </td>
            </tr>
            <tr>
                <th><label for="app_tagline">App Tagline</label></th>
                <td>
                    <input type="text" id="app_tagline" name="tmw_gm_config[app_tagline]" value="<?php echo esc_attr($gm_config['app_tagline']); ?>" class="regular-text">
                    <p class="description">Optional tagline</p>
                </td>
            </tr>
            <tr>
                <th><label for="app_copyright_year">Copyright Year</label></th>
                <td>
                    <input type="text" id="app_copyright_year" name="tmw_gm_config[app_copyright_year]" value="<?php echo esc_attr($gm_config['app_copyright_year']); ?>" class="small-text">
                    <p class="description">Copyright year in footer</p>
                </td>
            </tr>
            <tr>
                <th><label for="app_version">App Version</label></th>
                <td>
                    <input type="text" id="app_version" name="tmw_gm_config[app_version]" value="<?php echo esc_attr($gm_config['app_version']); ?>" class="small-text">
                    <p class="description">Version displayed in footer</p>
                </td>
            </tr>
        </table>
        
        <!-- App Settings Section -->
        <h3 class="tmw-section-title" style="cursor: pointer; padding: 10px; background: #f5f5f5; margin: 20px 0 0 0;" onclick="jQuery('#gm-settings-section').slideToggle()">
            <span class="dashicons dashicons-arrow-down-alt2" style="margin-top: 2px;"></span> App Settings
        </h3>
        <table class="form-table" id="gm-settings-section" style="display: none;">
            <tr>
                <th><label for="dashboard_history_per_page">Dashboard History Per Page</label></th>
                <td>
                    <input type="number" id="dashboard_history_per_page" name="tmw_gm_config[dashboard_history_per_page]" value="<?php echo esc_attr($gm_config['dashboard_history_per_page']); ?>" class="small-text" min="1">
                    <p class="description">Entries per page in Vehicle Overview</p>
                </td>
            </tr>
            <tr>
                <th><label for="entry_max_attachments">Max Attachments Per Entry</label></th>
                <td>
                    <input type="number" id="entry_max_attachments" name="tmw_gm_config[entry_max_attachments]" value="<?php echo esc_attr($gm_config['entry_max_attachments']); ?>" class="small-text" min="0">
                    <p class="description">Maximum attachments per service entry</p>
                </td>
            </tr>
            <tr>
                <th><label for="entry_max_attachment_size_mb">Max Attachment Size (MB)</label></th>
                <td>
                    <input type="number" id="entry_max_attachment_size_mb" name="tmw_gm_config[entry_max_attachment_size_mb]" value="<?php echo esc_attr($gm_config['entry_max_attachment_size_mb']); ?>" class="small-text" min="1">
                    <p class="description">Maximum size per attachment file in MB</p>
                </td>
            </tr>
        </table>
        
        <!-- File Storage Section -->
        <h3 class="tmw-section-title" style="cursor: pointer; padding: 10px; background: #f5f5f5; margin: 20px 0 0 0;" onclick="jQuery('#gm-storage-section').slideToggle()">
            <span class="dashicons dashicons-arrow-down-alt2" style="margin-top: 2px;"></span> File Storage
        </h3>
        <table class="form-table" id="gm-storage-section" style="display: none;">
            <tr>
                <th><label for="allowed_extensions">Allowed File Extensions</label></th>
                <td>
                    <input type="text" id="allowed_extensions" name="tmw_gm_config[allowed_extensions]" value="<?php echo esc_attr($gm_config['allowed_extensions']); ?>" class="large-text">
                    <p class="description">Comma-separated list (e.g., pdf,doc,docx,jpg,jpeg,png,gif,webp)</p>
                </td>
            </tr>
            <tr>
                <th><label for="attachments_url_base">Attachments URL Base</label></th>
                <td>
                    <input type="text" id="attachments_url_base" name="tmw_gm_config[attachments_url_base]" value="<?php echo esc_attr($gm_config['attachments_url_base']); ?>" class="regular-text">
                    <p class="description">Base URL for attachment downloads</p>
                </td>
            </tr>
        </table>
        
        <!-- Security Section -->
        <h3 class="tmw-section-title" style="cursor: pointer; padding: 10px; background: #f5f5f5; margin: 20px 0 0 0;" onclick="jQuery('#gm-security-section').slideToggle()">
            <span class="dashicons dashicons-arrow-down-alt2" style="margin-top: 2px;"></span> Security
        </h3>
        <table class="form-table" id="gm-security-section" style="display: none;">
            <tr>
                <th><label for="require_https">Require HTTPS</label></th>
                <td>
                    <label>
                        <input type="checkbox" id="require_https" name="tmw_gm_config[require_https]" value="1" <?php checked($gm_config['require_https']); ?>>
                        Force HTTPS in production
                    </label>
                </td>
            </tr>
            <tr>
                <th><label for="api_rate_limit_per_minute">API Rate Limit</label></th>
                <td>
                    <input type="number" id="api_rate_limit_per_minute" name="tmw_gm_config[api_rate_limit_per_minute]" value="<?php echo esc_attr($gm_config['api_rate_limit_per_minute']); ?>" class="small-text" min="1">
                    <p class="description">Max API calls per user per minute</p>
                </td>
            </tr>
            <tr>
                <th><label for="session_timeout_minutes">Session Timeout (Minutes)</label></th>
                <td>
                    <input type="number" id="session_timeout_minutes" name="tmw_gm_config[session_timeout_minutes]" value="<?php echo esc_attr($gm_config['session_timeout_minutes']); ?>" class="small-text" min="1">
                    <p class="description">Session timeout in minutes</p>
                </td>
            </tr>
        </table>
        
        <!-- WordPress Integration Section -->
        <h3 class="tmw-section-title" style="cursor: pointer; padding: 10px; background: #f5f5f5; margin: 20px 0 0 0;" onclick="jQuery('#gm-wp-section').slideToggle()">
            <span class="dashicons dashicons-arrow-down-alt2" style="margin-top: 2px;"></span> WordPress Integration
        </h3>
        <table class="form-table" id="gm-wp-section" style="display: none;">
            <tr>
                <th><label for="enable_multi_user">Enable Multi-User</label></th>
                <td>
                    <label>
                        <input type="checkbox" id="enable_multi_user" name="tmw_gm_config[enable_multi_user]" value="1" <?php checked($gm_config['enable_multi_user']); ?>>
                        Enable WordPress authentication
                    </label>
                </td>
            </tr>
            <tr>
                <th><label for="require_subscription">Require Subscription</label></th>
                <td>
                    <label>
                        <input type="checkbox" id="require_subscription" name="tmw_gm_config[require_subscription]" value="1" <?php checked($gm_config['require_subscription']); ?>>
                        Require active subscription to access app
                    </label>
                </td>
            </tr>
            <tr>
                <th><label for="custom_login_url">Custom Login URL</label></th>
                <td>
                    <input type="text" id="custom_login_url" name="tmw_gm_config[custom_login_url]" value="<?php echo esc_attr($gm_config['custom_login_url']); ?>" class="regular-text">
                    <p class="description">e.g., /login/ or /my-account/</p>
                </td>
            </tr>
            <tr>
                <th><label for="custom_logout_url">Custom Logout URL</label></th>
                <td>
                    <input type="text" id="custom_logout_url" name="tmw_gm_config[custom_logout_url]" value="<?php echo esc_attr($gm_config['custom_logout_url']); ?>" class="regular-text">
                    <p class="description">Leave empty to use wp_logout_url()</p>
                </td>
            </tr>
            <tr>
                <th><label for="custom_register_url">Custom Register URL</label></th>
                <td>
                    <input type="text" id="custom_register_url" name="tmw_gm_config[custom_register_url]" value="<?php echo esc_attr($gm_config['custom_register_url']); ?>" class="regular-text">
                    <p class="description">e.g., /register/ or /signup/</p>
                </td>
            </tr>
            <tr>
                <th><label for="custom_profile_url">Custom Profile URL</label></th>
                <td>
                    <input type="text" id="custom_profile_url" name="tmw_gm_config[custom_profile_url]" value="<?php echo esc_attr($gm_config['custom_profile_url']); ?>" class="regular-text">
                    <p class="description">e.g., /my-account/ or /profile/</p>
                </td>
            </tr>
            <tr>
                <th><label for="custom_subscribe_url">Custom Subscribe URL</label></th>
                <td>
                    <input type="text" id="custom_subscribe_url" name="tmw_gm_config[custom_subscribe_url]" value="<?php echo esc_attr($gm_config['custom_subscribe_url']); ?>" class="regular-text">
                    <p class="description">e.g., /pricing/ or /subscribe/</p>
                </td>
            </tr>
            <tr>
                <th><label for="logout_redirect_url">Logout Redirect URL</label></th>
                <td>
                    <input type="text" id="logout_redirect_url" name="tmw_gm_config[logout_redirect_url]" value="<?php echo esc_attr($gm_config['logout_redirect_url']); ?>" class="regular-text">
                    <p class="description">Where to redirect after logout</p>
                </td>
            </tr>
        </table>
        
        <!-- Google Drive Section -->
        <h3 class="tmw-section-title" style="cursor: pointer; padding: 10px; background: #f5f5f5; margin: 20px 0 0 0;" onclick="jQuery('#gm-gdrive-section').slideToggle()">
            <span class="dashicons dashicons-arrow-down-alt2" style="margin-top: 2px;"></span> Google Drive Integration
        </h3>
        <table class="form-table" id="gm-gdrive-section" style="display: none;">
            <tr>
                <th><label for="google_drive_enabled">Enable Google Drive</label></th>
                <td>
                    <label>
                        <input type="checkbox" id="google_drive_enabled" name="tmw_gm_config[google_drive_enabled]" value="1" <?php checked($gm_config['google_drive_enabled']); ?>>
                        Enable Google Drive for file attachments
                    </label>
                </td>
            </tr>
            <tr>
                <th><label for="google_client_id">Google Client ID</label></th>
                <td>
                    <input type="text" id="google_client_id" name="tmw_gm_config[google_client_id]" value="<?php echo esc_attr($gm_config['google_client_id']); ?>" class="large-text">
                </td>
            </tr>
            <tr>
                <th><label for="google_client_secret">Google Client Secret</label></th>
                <td>
                    <input type="text" id="google_client_secret" name="tmw_gm_config[google_client_secret]" value="<?php echo esc_attr($gm_config['google_client_secret']); ?>" class="large-text">
                </td>
            </tr>
            <tr>
                <th><label for="google_redirect_uri">Google Redirect URI</label></th>
                <td>
                    <input type="url" id="google_redirect_uri" name="tmw_gm_config[google_redirect_uri]" value="<?php echo esc_attr($gm_config['google_redirect_uri']); ?>" class="large-text">
                    <p class="description">OAuth callback URL</p>
                </td>
            </tr>
            <tr>
                <th><label for="google_drive_folder_name">Google Drive Folder Name</label></th>
                <td>
                    <input type="text" id="google_drive_folder_name" name="tmw_gm_config[google_drive_folder_name]" value="<?php echo esc_attr($gm_config['google_drive_folder_name']); ?>" class="regular-text">
                    <p class="description">Name of folder to store attachments</p>
                </td>
            </tr>
        </table>
        
        <?php submit_button('Save GarageMinder Configuration'); ?>
    </form>
    
    <script>
    jQuery(document).ready(function($) {
        // Toggle section visibility
        $('.tmw-section-title').on('click', function() {
            $(this).find('.dashicons').toggleClass('dashicons-arrow-down-alt2 dashicons-arrow-up-alt2');
        });
    });
    </script>
    <?php
}

function tmw_render_tiers_tab() {
    $tiers = tmw_get_tiers();
    ?>
    <p class="description" style="margin-bottom:20px;"><?php _e('Define subscription tiers. Each tier maps to a Simple Membership level ID.', 'flavor-starter-flavor'); ?></p>
    
    <table class="wp-list-table widefat fixed striped" id="tmw-tiers-table">
        <thead>
            <tr>
                <th style="width:60px;">Order</th>
                <th style="width:100px;">Slug</th>
                <th>Name</th>
                <th style="width:80px;">SWPM ID</th>
                <th style="width:60px;">Free?</th>
                <th style="width:60px;">Color</th>
                <th style="width:80px;">Price</th>
                <th style="width:120px;">Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($tiers as $slug => $tier) : ?>
            <tr data-slug="<?php echo esc_attr($slug); ?>"
                data-name="<?php echo esc_attr($tier['name']); ?>"
                data-description="<?php echo esc_attr($tier['description'] ?? ''); ?>"
                data-swpm-level-id="<?php echo esc_attr($tier['swpm_level_id'] ?? 0); ?>"
                data-is-free="<?php echo $tier['is_free'] ? '1' : '0'; ?>"
                data-order="<?php echo esc_attr($tier['order'] ?? 1); ?>"
                data-color="<?php echo esc_attr($tier['color'] ?? '#6b7280'); ?>"
                data-price-monthly="<?php echo esc_attr($tier['price_monthly'] ?? 0); ?>"
                data-price-yearly="<?php echo esc_attr($tier['price_yearly'] ?? 0); ?>"
                data-stripe-price-monthly="<?php echo esc_attr($tier['stripe_price_id_monthly'] ?? ''); ?>"
                data-stripe-price-yearly="<?php echo esc_attr($tier['stripe_price_id_yearly'] ?? ''); ?>"
                data-stripe-product-id="<?php echo esc_attr($tier['stripe_product_id'] ?? ''); ?>">
                <td><input type="number" class="small-text tier-order" value="<?php echo esc_attr($tier['order']); ?>" min="1" style="width:50px;"></td>
                <td><code><?php echo esc_html($slug); ?></code></td>
                <td><strong><?php echo esc_html($tier['name']); ?></strong><br><small><?php echo esc_html($tier['description']); ?></small></td>
                <td><?php echo esc_html($tier['swpm_level_id']); ?></td>
                <td><?php echo $tier['is_free'] ? '✓' : '—'; ?></td>
                <td><span style="display:inline-block;width:20px;height:20px;background:<?php echo esc_attr($tier['color']); ?>;border-radius:3px;"></span></td>
                <td><?php 
                    $price = $tier['price_monthly'] ?? 0;
                    echo $price > 0 ? '$' . number_format($price, 2) : '—';
                ?></td>
                <td>
                    <button type="button" class="button button-small tmw-edit-tier">Edit</button>
                    <button type="button" class="button button-small button-link-delete tmw-delete-tier">Delete</button>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    
    <p style="margin-top:20px;"><button type="button" class="button button-primary" id="tmw-add-tier"><span class="dashicons dashicons-plus-alt" style="margin-top:3px;"></span> Add New Tier</button></p>
    
    <div id="tmw-tier-modal" class="tmw-modal" style="display:none;">
        <div class="tmw-modal-content">
            <h2 id="tmw-tier-modal-title">Add Tier</h2>
            <table class="form-table">
                <tr><th><label for="tier-slug">Slug</label></th><td><input type="text" id="tier-slug" class="regular-text" pattern="[a-z0-9_-]+" required><p class="description">Lowercase, underscores (e.g., enterprise)</p></td></tr>
                <tr><th><label for="tier-name">Display Name</label></th><td><input type="text" id="tier-name" class="regular-text" required></td></tr>
                <tr><th><label for="tier-description">Description</label></th><td><input type="text" id="tier-description" class="regular-text"></td></tr>
                <tr><th><label for="tier-swpm-level">SWPM Level ID</label></th><td><input type="number" id="tier-swpm-level" class="small-text" min="0" value="0"></td></tr>
                <tr><th><label for="tier-is-free">Is Free Tier?</label></th><td><label><input type="checkbox" id="tier-is-free"> This is a free/no-cost tier</label></td></tr>
                <tr><th><label for="tier-order">Display Order</label></th><td><input type="number" id="tier-order" class="small-text" min="1" value="1"></td></tr>
                <tr><th><label for="tier-color">Badge Color</label></th><td><input type="color" id="tier-color" value="#6b7280"></td></tr>
				<?php do_action('tmw_tier_modal_fields'); ?>
            </table>
            <input type="hidden" id="tier-original-slug" value="">
            <p class="tmw-modal-buttons">
                <button type="button" class="button button-primary" id="tmw-save-tier">Save Tier</button>
                <button type="button" class="button tmw-modal-close">Cancel</button>
            </p>
        </div>
    </div>
    <?php
}

function tmw_render_limits_tab() {
    $limits = tmw_get_limit_definitions();
    ?>
    <p class="description" style="margin-bottom:20px;"><?php _e('Define limit types. Each limit can have different values per tier.', 'flavor-starter-flavor'); ?></p>
    
    <table class="wp-list-table widefat fixed striped" id="tmw-limits-table">
        <thead>
            <tr>
                <th style="width:150px;">Key</th>
                <th>Label</th>
                <th style="width:100px;">Type</th>
                <th>Description</th>
                <th style="width:120px;">Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($limits as $key => $limit) : ?>
            <tr data-key="<?php echo esc_attr($key); ?>">
                <td><code><?php echo esc_html($key); ?></code></td>
                <td><strong><?php echo esc_html($limit['label']); ?></strong></td>
                <td><?php echo esc_html(ucfirst($limit['type'])); if ($limit['type'] === 'select' && !empty($limit['options'])) echo '<br><small>' . esc_html(implode(', ', $limit['options'])) . '</small>'; ?></td>
                <td><small><?php echo esc_html($limit['description']); ?></small></td>
                <td>
                    <button type="button" class="button button-small tmw-edit-limit">Edit</button>
                    <button type="button" class="button button-small button-link-delete tmw-delete-limit">Delete</button>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    
    <p style="margin-top:20px;"><button type="button" class="button button-primary" id="tmw-add-limit"><span class="dashicons dashicons-plus-alt" style="margin-top:3px;"></span> Add New Limit</button></p>
    
    <div id="tmw-limit-modal" class="tmw-modal" style="display:none;">
        <div class="tmw-modal-content">
            <h2 id="tmw-limit-modal-title">Add Limit</h2>
            <table class="form-table">
                <tr><th><label for="limit-key">Key</label></th><td><input type="text" id="limit-key" class="regular-text" pattern="[a-z0-9_]+" required><p class="description">Lowercase, underscores (e.g., max_vehicles)</p></td></tr>
                <tr><th><label for="limit-label">Label</label></th><td><input type="text" id="limit-label" class="regular-text" required></td></tr>
                <tr><th><label for="limit-type">Type</label></th><td><select id="limit-type"><option value="number">Number</option><option value="boolean">Boolean (Yes/No)</option><option value="select">Select (Dropdown)</option></select></td></tr>
                <tr id="limit-options-row" style="display:none;"><th><label for="limit-options">Options</label></th><td><input type="text" id="limit-options" class="regular-text" placeholder="none, basic, advanced"><p class="description">Comma-separated values</p></td></tr>
                <tr><th><label for="limit-description">Description</label></th><td><input type="text" id="limit-description" class="large-text"></td></tr>
            </table>
            <input type="hidden" id="limit-original-key" value="">
            <p class="tmw-modal-buttons">
                <button type="button" class="button button-primary" id="tmw-save-limit">Save Limit</button>
                <button type="button" class="button tmw-modal-close">Cancel</button>
            </p>
        </div>
    </div>
    <?php
}

function tmw_render_values_tab() {
    $tiers = tmw_get_tiers();
    $limits = tmw_get_limit_definitions();
    $values = tmw_get_tier_values();
    ?>
    <p class="description" style="margin-bottom:20px;"><?php _e('Set limit values for each tier. Use -1 for unlimited (number fields).', 'flavor-starter-flavor'); ?></p>
    
    <form id="tmw-values-form">
        <table class="wp-list-table widefat fixed striped" id="tmw-values-table">
            <thead>
                <tr>
                    <th style="width:200px;">Limit</th>
                    <?php foreach ($tiers as $slug => $tier) : ?>
                    <th style="background:<?php echo esc_attr($tier['color']); ?>15;"><span style="color:<?php echo esc_attr($tier['color']); ?>;font-weight:600;"><?php echo esc_html($tier['name']); ?></span></th>
                    <?php endforeach; ?>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($limits as $limit_key => $limit) : ?>
                <tr>
                    <td><strong><?php echo esc_html($limit['label']); ?></strong><br><small class="description"><?php echo esc_html($limit['description']); ?></small></td>
                    <?php foreach ($tiers as $tier_slug => $tier) : $value = $values[$tier_slug][$limit_key] ?? null; ?>
                    <td>
                        <?php if ($limit['type'] === 'boolean') : ?>
                            <label><input type="checkbox" name="values[<?php echo esc_attr($tier_slug); ?>][<?php echo esc_attr($limit_key); ?>]" value="1" <?php checked($value); ?>> Enabled</label>
                        <?php elseif ($limit['type'] === 'select') : ?>
                            <select name="values[<?php echo esc_attr($tier_slug); ?>][<?php echo esc_attr($limit_key); ?>]">
                                <?php foreach (($limit['options'] ?? array()) as $opt) : ?>
                                <option value="<?php echo esc_attr($opt); ?>" <?php selected($value, $opt); ?>><?php echo esc_html(ucfirst($opt)); ?></option>
                                <?php endforeach; ?>
                            </select>
                        <?php else : ?>
                            <input type="number" name="values[<?php echo esc_attr($tier_slug); ?>][<?php echo esc_attr($limit_key); ?>]" value="<?php echo esc_attr($value); ?>" class="small-text" min="-1" style="width:70px;"> <span class="description">(-1=∞)</span>
                        <?php endif; ?>
                    </td>
                    <?php endforeach; ?>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <p style="margin-top:20px;"><button type="submit" class="button button-primary" id="tmw-save-values">Save All Values</button> <span id="tmw-values-status" style="margin-left:10px;"></span></p>
    </form>
    <?php
}

// =============================================================================
// ADMIN SCRIPTS
// =============================================================================
function tmw_render_admin_scripts() {
    ?>
    <style>
    .tmw-admin-wrap{max-width:1200px}.tmw-settings-content{background:#fff;padding:20px;border:1px solid #ccd0d4;margin-top:-1px}
    .tmw-modal{position:fixed;top:0;left:0;right:0;bottom:0;background:rgba(0,0,0,0.7);z-index:100000;display:flex;align-items:center;justify-content:center}
    .tmw-modal-content{background:#fff;padding:20px 30px;border-radius:4px;max-width:600px;width:100%;max-height:90vh;overflow-y:auto}
    .tmw-modal-buttons{margin-top:20px;padding-top:20px;border-top:1px solid #ddd}
    #tmw-values-table select{width:100%}.tmw-success{color:#46b450}.tmw-error{color:#dc3232}
    </style>
    <script>
    jQuery(document).ready(function($){
        var nonce='<?php echo wp_create_nonce('tmw_admin_nonce'); ?>';
        function openModal(id){$(id).fadeIn(200)}
        function closeModal(){$('.tmw-modal').fadeOut(200)}
        $('.tmw-modal-close,.tmw-modal').on('click',function(e){if(e.target===this)closeModal()});
        
        // TIERS
        $('#tmw-add-tier').on('click',function(){
            $('#tmw-tier-modal-title').text('Add New Tier');
            $('#tier-slug').val('').prop('readonly',false);
            $('#tier-name,#tier-description').val('');
            $('#tier-swpm-level').val(0);$('#tier-is-free').prop('checked',false);
            $('#tier-order').val($('#tmw-tiers-table tbody tr').length+1);
            $('#tier-color').val('#6b7280');$('#tier-original-slug').val('');
            // Clear pricing fields
            $('#tier-price-monthly').val(0);
            $('#tier-price-yearly').val(0);
            // Clear Stripe fields
            $('#tier-stripe-price-monthly').val('');
            $('#tier-stripe-price-yearly').val('');
            $('#tier-stripe-product-id').val('');
            openModal('#tmw-tier-modal');
        });
        $(document).on('click','.tmw-edit-tier',function(){
            var $r=$(this).closest('tr'),s=$r.data('slug');
            $('#tmw-tier-modal-title').text('Edit Tier');
            $('#tier-slug').val(s).prop('readonly',true);
            // Read from data attributes
            $('#tier-name').val($r.data('name'));
            $('#tier-description').val($r.data('description'));
            $('#tier-swpm-level').val($r.data('swpm-level-id'));
            $('#tier-is-free').prop('checked',$r.data('is-free')==='1'||$r.data('is-free')===1);
            $('#tier-order').val($r.data('order'));
            $('#tier-color').val($r.data('color'));
            // Pricing fields
            $('#tier-price-monthly').val($r.data('price-monthly')||0);
            $('#tier-price-yearly').val($r.data('price-yearly')||0);
            // Stripe fields
            $('#tier-stripe-price-monthly').val($r.data('stripe-price-monthly')||'');
            $('#tier-stripe-price-yearly').val($r.data('stripe-price-yearly')||'');
            $('#tier-stripe-product-id').val($r.data('stripe-product-id')||'');
            $('#tier-original-slug').val(s);openModal('#tmw-tier-modal');
        });
        $('#tmw-save-tier').on('click',function(){
            var s=$('#tier-slug').val().toLowerCase().replace(/[^a-z0-9_-]/g,'');
            if(!s){alert('Slug required');return}
            $.post(ajaxurl,{action:'tmw_save_tier',nonce:nonce,slug:s,original_slug:$('#tier-original-slug').val(),
                data:{
                    name:$('#tier-name').val(),
                    description:$('#tier-description').val(),
                    swpm_level_id:$('#tier-swpm-level').val(),
                    is_free:$('#tier-is-free').is(':checked')?1:0,
                    order:$('#tier-order').val(),
                    color:$('#tier-color').val(),
                    price_monthly:$('#tier-price-monthly').val(),
                    price_yearly:$('#tier-price-yearly').val(),
                    stripe_price_id_monthly:$('#tier-stripe-price-monthly').val(),
                    stripe_price_id_yearly:$('#tier-stripe-price-yearly').val(),
                    stripe_product_id:$('#tier-stripe-product-id').val()
                }
            },function(r){if(r.success)location.reload();else alert(r.data||'Error')});
        });
        $(document).on('click','.tmw-delete-tier',function(){
            if(!confirm('Delete this tier?'))return;
            $.post(ajaxurl,{action:'tmw_delete_tier',nonce:nonce,slug:$(this).closest('tr').data('slug')},function(r){if(r.success)location.reload();else alert(r.data||'Error')});
        });
        
        // LIMITS
        $('#limit-type').on('change',function(){$('#limit-options-row').toggle($(this).val()==='select')});
        $('#tmw-add-limit').on('click',function(){
            $('#tmw-limit-modal-title').text('Add New Limit');
            $('#limit-key').val('').prop('readonly',false);
            $('#limit-label,#limit-description,#limit-options').val('');
            $('#limit-type').val('number').trigger('change');
            $('#limit-original-key').val('');openModal('#tmw-limit-modal');
        });
        $(document).on('click','.tmw-edit-limit',function(){
            var $r=$(this).closest('tr'),k=$r.data('key'),t=$r.find('td:eq(2)').text().trim().split('\n')[0].toLowerCase();
            $('#tmw-limit-modal-title').text('Edit Limit');
            $('#limit-key').val(k).prop('readonly',true);
            $('#limit-label').val($r.find('td:eq(1) strong').text());
            $('#limit-type').val(t).trigger('change');
            if(t==='select')$('#limit-options').val($r.find('td:eq(2) small').text());
            $('#limit-description').val($r.find('td:eq(3) small').text());
            $('#limit-original-key').val(k);openModal('#tmw-limit-modal');
        });
        $('#tmw-save-limit').on('click',function(){
            var k=$('#limit-key').val().toLowerCase().replace(/[^a-z0-9_]/g,'');
            if(!k){alert('Key required');return}
            $.post(ajaxurl,{action:'tmw_save_limit',nonce:nonce,key:k,original_key:$('#limit-original-key').val(),
                data:{label:$('#limit-label').val(),type:$('#limit-type').val(),options:$('#limit-options').val(),description:$('#limit-description').val()}
            },function(r){if(r.success)location.reload();else alert(r.data||'Error')});
        });
        $(document).on('click','.tmw-delete-limit',function(){
            if(!confirm('Delete this limit?'))return;
            $.post(ajaxurl,{action:'tmw_delete_limit',nonce:nonce,key:$(this).closest('tr').data('key')},function(r){if(r.success)location.reload();else alert(r.data||'Error')});
        });
        
        // VALUES
        $('#tmw-values-form').on('submit',function(e){
            e.preventDefault();var $s=$('#tmw-values-status');
            $s.text('Saving...').removeClass('tmw-success tmw-error');
            var v={};$(this).find('input,select').each(function(){
                var m=this.name.match(/values\[([^\]]+)\]\[([^\]]+)\]/);
                if(m){if(!v[m[1]])v[m[1]]={};v[m[1]][m[2]]=this.type==='checkbox'?(this.checked?1:0):$(this).val()}
            });
            $.post(ajaxurl,{action:'tmw_save_tier_values',nonce:nonce,values:v},function(r){
                $s.text(r.success?'Saved!':'Error').addClass(r.success?'tmw-success':'tmw-error');
                setTimeout(function(){$s.text('')},3000);
            });
        });
        
        function rgbToHex(rgb){if(!rgb||rgb==='transparent')return'#6b7280';var m=rgb.match(/^rgb\((\d+),\s*(\d+),\s*(\d+)\)$/);
            if(!m)return rgb;return'#'+[m[1],m[2],m[3]].map(function(x){return('0'+parseInt(x).toString(16)).slice(-2)}).join('')}
    });
    </script>
    <?php
}