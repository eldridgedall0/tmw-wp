<?php
/**
 * Admin Settings Page
 *
 * Three tabs:
 * 1. General Settings - App URL, theme defaults, redirects
 * 2. Level Mapping - Simple Membership level IDs to tier names
 * 3. Subscription Limits - Feature limits per tier
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
    // General settings
    register_setting('tmw_settings_group', 'tmw_settings', array(
        'type'              => 'array',
        'sanitize_callback' => 'tmw_sanitize_settings',
        'default'           => tmw_get_default_settings(),
    ));

    // Level mapping
    register_setting('tmw_level_mapping_group', 'tmw_level_mapping', array(
        'type'              => 'array',
        'sanitize_callback' => 'tmw_sanitize_level_mapping',
        'default'           => tmw_get_default_level_mapping(),
    ));

    // Subscription limits
    register_setting('tmw_subscription_group', 'tmw_subscription_settings', array(
        'type'              => 'array',
        'sanitize_callback' => 'tmw_sanitize_subscription_settings',
        'default'           => tmw_get_default_subscription_settings(),
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
        'enable_smp'        => true,
        'membership_plugin' => 'simple-membership',
    );
}

function tmw_get_default_level_mapping() {
    return array(
        'free_level_id'  => 1,
        'paid_level_id'  => 2,
        'fleet_level_id' => 3,
        'fallback_tier'  => 'free',
    );
}

function tmw_get_default_subscription_settings() {
    return array(
        // Free tier
        'free_max_vehicles'       => 2,
        'free_max_entries'        => 50,
        'free_attachments_per_entry' => 0,
        'free_recalls_enabled'    => false,
        'free_export_level'       => 'none',
        'free_max_templates'      => 3,
        'free_vehicle_photos'     => false,
        'free_api_access'         => false,
        'free_team_members'       => 0,

        // Paid tier
        'paid_max_vehicles'       => 10,
        'paid_max_entries'        => -1, // unlimited
        'paid_attachments_per_entry' => 2,
        'paid_recalls_enabled'    => true,
        'paid_export_level'       => 'basic',
        'paid_max_templates'      => 15,
        'paid_vehicle_photos'     => true,
        'paid_api_access'         => false,
        'paid_team_members'       => 0,

        // Fleet tier
        'fleet_max_vehicles'       => -1, // unlimited
        'fleet_max_entries'        => -1,
        'fleet_attachments_per_entry' => 5,
        'fleet_recalls_enabled'    => true,
        'fleet_export_level'       => 'advanced',
        'fleet_max_templates'      => -1,
        'fleet_vehicle_photos'     => true,
        'fleet_api_access'         => true,
        'fleet_team_members'       => 10,
    );
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
    $sanitized['enable_smp'] = !empty($input['enable_smp']);
    $sanitized['membership_plugin'] = isset($input['membership_plugin']) ? sanitize_key($input['membership_plugin']) : 'simple-membership';
    
    return $sanitized;
}

function tmw_sanitize_level_mapping($input) {
    $sanitized = array();
    
    $sanitized['free_level_id'] = isset($input['free_level_id']) ? absint($input['free_level_id']) : 1;
    $sanitized['paid_level_id'] = isset($input['paid_level_id']) ? absint($input['paid_level_id']) : 2;
    $sanitized['fleet_level_id'] = isset($input['fleet_level_id']) ? absint($input['fleet_level_id']) : 3;
    $sanitized['fallback_tier'] = isset($input['fallback_tier']) && in_array($input['fallback_tier'], array('free', 'paid', 'fleet', 'none'))
        ? $input['fallback_tier'] : 'free';
    
    return $sanitized;
}

function tmw_sanitize_subscription_settings($input) {
    $sanitized = array();
    $tiers = array('free', 'paid', 'fleet');
    
    foreach ($tiers as $tier) {
        $sanitized[$tier . '_max_vehicles'] = isset($input[$tier . '_max_vehicles']) ? intval($input[$tier . '_max_vehicles']) : 2;
        $sanitized[$tier . '_max_entries'] = isset($input[$tier . '_max_entries']) ? intval($input[$tier . '_max_entries']) : 50;
        $sanitized[$tier . '_attachments_per_entry'] = isset($input[$tier . '_attachments_per_entry']) ? absint($input[$tier . '_attachments_per_entry']) : 0;
        $sanitized[$tier . '_recalls_enabled'] = !empty($input[$tier . '_recalls_enabled']);
        $sanitized[$tier . '_export_level'] = isset($input[$tier . '_export_level']) ? sanitize_key($input[$tier . '_export_level']) : 'none';
        $sanitized[$tier . '_max_templates'] = isset($input[$tier . '_max_templates']) ? intval($input[$tier . '_max_templates']) : 3;
        $sanitized[$tier . '_vehicle_photos'] = !empty($input[$tier . '_vehicle_photos']);
        $sanitized[$tier . '_api_access'] = !empty($input[$tier . '_api_access']);
        $sanitized[$tier . '_team_members'] = isset($input[$tier . '_team_members']) ? absint($input[$tier . '_team_members']) : 0;
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

    add_submenu_page(
        'tmw-settings',
        __('Settings', 'flavor-starter-flavor'),
        __('Settings', 'flavor-starter-flavor'),
        'manage_options',
        'tmw-settings',
        'tmw_render_settings_page'
    );
}

// =============================================================================
// RENDER SETTINGS PAGE
// =============================================================================
function tmw_render_settings_page() {
    if (!current_user_can('manage_options')) {
        return;
    }

    $active_tab = isset($_GET['tab']) ? sanitize_key($_GET['tab']) : 'general';
    ?>
    <div class="wrap tmw-admin-wrap">
        <h1><?php echo esc_html(get_admin_page_title()); ?></h1>

        <nav class="nav-tab-wrapper">
            <a href="?page=tmw-settings&tab=general" 
               class="nav-tab <?php echo $active_tab === 'general' ? 'nav-tab-active' : ''; ?>">
                <?php _e('General', 'flavor-starter-flavor'); ?>
            </a>
            <a href="?page=tmw-settings&tab=levels" 
               class="nav-tab <?php echo $active_tab === 'levels' ? 'nav-tab-active' : ''; ?>">
                <?php _e('Level Mapping', 'flavor-starter-flavor'); ?>
            </a>
            <a href="?page=tmw-settings&tab=limits" 
               class="nav-tab <?php echo $active_tab === 'limits' ? 'nav-tab-active' : ''; ?>">
                <?php _e('Subscription Limits', 'flavor-starter-flavor'); ?>
            </a>
        </nav>

        <div class="tmw-settings-content">
            <?php
            switch ($active_tab) {
                case 'levels':
                    tmw_render_levels_tab();
                    break;
                case 'limits':
                    tmw_render_limits_tab();
                    break;
                default:
                    tmw_render_general_tab();
                    break;
            }
            ?>
        </div>
    </div>
    <?php
}

// =============================================================================
// GENERAL TAB
// =============================================================================
function tmw_render_general_tab() {
    $settings = wp_parse_args(get_option('tmw_settings', array()), tmw_get_default_settings());
    ?>
    <form method="post" action="options.php">
        <?php settings_fields('tmw_settings_group'); ?>
        
        <table class="form-table">
            <tr>
                <th scope="row">
                    <label for="app_url"><?php _e('GarageMinder App URL', 'flavor-starter-flavor'); ?></label>
                </th>
                <td>
                    <input type="url" id="app_url" name="tmw_settings[app_url]" 
                           value="<?php echo esc_attr($settings['app_url']); ?>" 
                           class="regular-text" 
                           placeholder="https://app.trackmywrench.com/">
                    <p class="description">
                        <?php _e('Full URL to the GarageMinder app. Supports subdomain (app.trackmywrench.com) or subdirectory (/garage/).', 'flavor-starter-flavor'); ?>
                    </p>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="default_theme"><?php _e('Default Theme', 'flavor-starter-flavor'); ?></label>
                </th>
                <td>
                    <select id="default_theme" name="tmw_settings[default_theme]">
                        <option value="dark" <?php selected($settings['default_theme'], 'dark'); ?>>
                            <?php _e('Dark', 'flavor-starter-flavor'); ?>
                        </option>
                        <option value="light" <?php selected($settings['default_theme'], 'light'); ?>>
                            <?php _e('Light', 'flavor-starter-flavor'); ?>
                        </option>
                    </select>
                    <p class="description">
                        <?php _e('Default theme for new visitors. Users can toggle and their preference will be saved.', 'flavor-starter-flavor'); ?>
                    </p>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="login_redirect"><?php _e('After Login Redirect', 'flavor-starter-flavor'); ?></label>
                </th>
                <td>
                    <select id="login_redirect" name="tmw_settings[login_redirect]">
                        <option value="app" <?php selected($settings['login_redirect'], 'app'); ?>>
                            <?php _e('GarageMinder App', 'flavor-starter-flavor'); ?>
                        </option>
                        <option value="profile" <?php selected($settings['login_redirect'], 'profile'); ?>>
                            <?php _e('My Profile', 'flavor-starter-flavor'); ?>
                        </option>
                        <option value="home" <?php selected($settings['login_redirect'], 'home'); ?>>
                            <?php _e('Home Page', 'flavor-starter-flavor'); ?>
                        </option>
                    </select>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="logout_redirect"><?php _e('After Logout Redirect', 'flavor-starter-flavor'); ?></label>
                </th>
                <td>
                    <select id="logout_redirect" name="tmw_settings[logout_redirect]">
                        <option value="home" <?php selected($settings['logout_redirect'], 'home'); ?>>
                            <?php _e('Home Page', 'flavor-starter-flavor'); ?>
                        </option>
                        <option value="login" <?php selected($settings['logout_redirect'], 'login'); ?>>
                            <?php _e('Login Page', 'flavor-starter-flavor'); ?>
                        </option>
                    </select>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="membership_plugin"><?php _e('Membership Plugin', 'flavor-starter-flavor'); ?></label>
                </th>
                <td>
                    <select id="membership_plugin" name="tmw_settings[membership_plugin]">
                        <option value="simple-membership" <?php selected($settings['membership_plugin'], 'simple-membership'); ?>>
                            <?php _e('Simple Membership', 'flavor-starter-flavor'); ?>
                        </option>
                        <option value="woocommerce" <?php selected($settings['membership_plugin'], 'woocommerce'); ?>>
                            <?php _e('WooCommerce Subscriptions', 'flavor-starter-flavor'); ?>
                        </option>
                        <option value="pmpro" <?php selected($settings['membership_plugin'], 'pmpro'); ?>>
                            <?php _e('Paid Memberships Pro', 'flavor-starter-flavor'); ?>
                        </option>
                        <option value="memberpress" <?php selected($settings['membership_plugin'], 'memberpress'); ?>>
                            <?php _e('MemberPress', 'flavor-starter-flavor'); ?>
                        </option>
                        <option value="user-meta" <?php selected($settings['membership_plugin'], 'user-meta'); ?>>
                            <?php _e('User Meta (Manual)', 'flavor-starter-flavor'); ?>
                        </option>
                    </select>
                    <p class="description">
                        <?php _e('Select the membership plugin you are using. This theme will adapt its integration accordingly.', 'flavor-starter-flavor'); ?>
                    </p>
                </td>
            </tr>
        </table>

        <?php submit_button(); ?>
    </form>
    <?php
}

// =============================================================================
// LEVELS TAB
// =============================================================================
function tmw_render_levels_tab() {
    $mapping = wp_parse_args(get_option('tmw_level_mapping', array()), tmw_get_default_level_mapping());
    ?>
    <form method="post" action="options.php">
        <?php settings_fields('tmw_level_mapping_group'); ?>
        
        <div class="tmw-notice tmw-notice-info">
            <p>
                <strong><?php _e('Simple Membership Level IDs', 'flavor-starter-flavor'); ?></strong><br>
                <?php _e('Find these in WP Admin → Simple Membership → Membership Levels. Each level has an ID number.', 'flavor-starter-flavor'); ?>
            </p>
        </div>

        <table class="form-table">
            <tr>
                <th scope="row">
                    <label for="free_level_id"><?php _e('Free Tier Level ID', 'flavor-starter-flavor'); ?></label>
                </th>
                <td>
                    <input type="number" id="free_level_id" name="tmw_level_mapping[free_level_id]" 
                           value="<?php echo esc_attr($mapping['free_level_id']); ?>" 
                           class="small-text" min="0">
                    <p class="description">
                        <?php _e('Simple Membership level ID for Free tier users.', 'flavor-starter-flavor'); ?>
                    </p>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="paid_level_id"><?php _e('Paid Tier Level ID', 'flavor-starter-flavor'); ?></label>
                </th>
                <td>
                    <input type="number" id="paid_level_id" name="tmw_level_mapping[paid_level_id]" 
                           value="<?php echo esc_attr($mapping['paid_level_id']); ?>" 
                           class="small-text" min="0">
                    <p class="description">
                        <?php _e('Simple Membership level ID for Paid tier users.', 'flavor-starter-flavor'); ?>
                    </p>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="fleet_level_id"><?php _e('Fleet Tier Level ID', 'flavor-starter-flavor'); ?></label>
                </th>
                <td>
                    <input type="number" id="fleet_level_id" name="tmw_level_mapping[fleet_level_id]" 
                           value="<?php echo esc_attr($mapping['fleet_level_id']); ?>" 
                           class="small-text" min="0">
                    <p class="description">
                        <?php _e('Simple Membership level ID for Fleet tier users.', 'flavor-starter-flavor'); ?>
                    </p>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="fallback_tier"><?php _e('Fallback Tier', 'flavor-starter-flavor'); ?></label>
                </th>
                <td>
                    <select id="fallback_tier" name="tmw_level_mapping[fallback_tier]">
                        <option value="free" <?php selected($mapping['fallback_tier'], 'free'); ?>>
                            <?php _e('Free', 'flavor-starter-flavor'); ?>
                        </option>
                        <option value="none" <?php selected($mapping['fallback_tier'], 'none'); ?>>
                            <?php _e('No Access', 'flavor-starter-flavor'); ?>
                        </option>
                    </select>
                    <p class="description">
                        <?php _e('What tier to assign if user level doesn\'t match any mapping.', 'flavor-starter-flavor'); ?>
                    </p>
                </td>
            </tr>
        </table>

        <?php submit_button(); ?>
    </form>
    <?php
}

// =============================================================================
// LIMITS TAB
// =============================================================================
function tmw_render_limits_tab() {
    $settings = wp_parse_args(get_option('tmw_subscription_settings', array()), tmw_get_default_subscription_settings());
    ?>
    <form method="post" action="options.php">
        <?php settings_fields('tmw_subscription_group'); ?>
        
        <div class="tmw-notice tmw-notice-info">
            <p>
                <?php _e('Use -1 for unlimited. These limits are read by the GarageMinder app to enforce features.', 'flavor-starter-flavor'); ?>
            </p>
        </div>

        <div class="tmw-limits-grid">
            <?php
            $tiers = array(
                'free'  => __('Free Tier', 'flavor-starter-flavor'),
                'paid'  => __('Paid Tier', 'flavor-starter-flavor'),
                'fleet' => __('Fleet Tier', 'flavor-starter-flavor'),
            );

            foreach ($tiers as $tier => $label) :
            ?>
            <div class="tmw-tier-card">
                <h3><?php echo esc_html($label); ?></h3>
                
                <table class="form-table">
                    <tr>
                        <th><?php _e('Max Vehicles', 'flavor-starter-flavor'); ?></th>
                        <td>
                            <input type="number" name="tmw_subscription_settings[<?php echo $tier; ?>_max_vehicles]" 
                                   value="<?php echo esc_attr($settings[$tier . '_max_vehicles']); ?>" 
                                   class="small-text" min="-1">
                        </td>
                    </tr>
                    <tr>
                        <th><?php _e('Max Total Entries', 'flavor-starter-flavor'); ?></th>
                        <td>
                            <input type="number" name="tmw_subscription_settings[<?php echo $tier; ?>_max_entries]" 
                                   value="<?php echo esc_attr($settings[$tier . '_max_entries']); ?>" 
                                   class="small-text" min="-1">
                        </td>
                    </tr>
                    <tr>
                        <th><?php _e('Attachments/Entry', 'flavor-starter-flavor'); ?></th>
                        <td>
                            <input type="number" name="tmw_subscription_settings[<?php echo $tier; ?>_attachments_per_entry]" 
                                   value="<?php echo esc_attr($settings[$tier . '_attachments_per_entry']); ?>" 
                                   class="small-text" min="0">
                        </td>
                    </tr>
                    <tr>
                        <th><?php _e('Recall Checks', 'flavor-starter-flavor'); ?></th>
                        <td>
                            <label>
                                <input type="checkbox" name="tmw_subscription_settings[<?php echo $tier; ?>_recalls_enabled]" 
                                       value="1" <?php checked($settings[$tier . '_recalls_enabled']); ?>>
                                <?php _e('Enabled', 'flavor-starter-flavor'); ?>
                            </label>
                        </td>
                    </tr>
                    <tr>
                        <th><?php _e('Export Level', 'flavor-starter-flavor'); ?></th>
                        <td>
                            <select name="tmw_subscription_settings[<?php echo $tier; ?>_export_level]">
                                <option value="none" <?php selected($settings[$tier . '_export_level'], 'none'); ?>>
                                    <?php _e('None', 'flavor-starter-flavor'); ?>
                                </option>
                                <option value="basic" <?php selected($settings[$tier . '_export_level'], 'basic'); ?>>
                                    <?php _e('Basic (CSV/PDF)', 'flavor-starter-flavor'); ?>
                                </option>
                                <option value="advanced" <?php selected($settings[$tier . '_export_level'], 'advanced'); ?>>
                                    <?php _e('Advanced (+ Bulk)', 'flavor-starter-flavor'); ?>
                                </option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th><?php _e('Max Templates', 'flavor-starter-flavor'); ?></th>
                        <td>
                            <input type="number" name="tmw_subscription_settings[<?php echo $tier; ?>_max_templates]" 
                                   value="<?php echo esc_attr($settings[$tier . '_max_templates']); ?>" 
                                   class="small-text" min="-1">
                        </td>
                    </tr>
                    <tr>
                        <th><?php _e('Vehicle Photos', 'flavor-starter-flavor'); ?></th>
                        <td>
                            <label>
                                <input type="checkbox" name="tmw_subscription_settings[<?php echo $tier; ?>_vehicle_photos]" 
                                       value="1" <?php checked($settings[$tier . '_vehicle_photos']); ?>>
                                <?php _e('Enabled', 'flavor-starter-flavor'); ?>
                            </label>
                        </td>
                    </tr>
                    <tr>
                        <th><?php _e('API Access', 'flavor-starter-flavor'); ?></th>
                        <td>
                            <label>
                                <input type="checkbox" name="tmw_subscription_settings[<?php echo $tier; ?>_api_access]" 
                                       value="1" <?php checked($settings[$tier . '_api_access']); ?>>
                                <?php _e('Enabled', 'flavor-starter-flavor'); ?>
                            </label>
                        </td>
                    </tr>
                    <tr>
                        <th><?php _e('Team Members', 'flavor-starter-flavor'); ?></th>
                        <td>
                            <input type="number" name="tmw_subscription_settings[<?php echo $tier; ?>_team_members]" 
                                   value="<?php echo esc_attr($settings[$tier . '_team_members']); ?>" 
                                   class="small-text" min="0">
                            <?php if ($tier === 'fleet') : ?>
                                <span class="description"><?php _e('(Coming Soon)', 'flavor-starter-flavor'); ?></span>
                            <?php endif; ?>
                        </td>
                    </tr>
                </table>
            </div>
            <?php endforeach; ?>
        </div>

        <?php submit_button(); ?>
    </form>

    <style>
    .tmw-limits-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
        gap: 20px;
        margin-top: 20px;
    }
    .tmw-tier-card {
        background: #fff;
        border: 1px solid #ccd0d4;
        border-radius: 4px;
        padding: 15px;
    }
    .tmw-tier-card h3 {
        margin-top: 0;
        padding-bottom: 10px;
        border-bottom: 1px solid #eee;
    }
    .tmw-tier-card .form-table th,
    .tmw-tier-card .form-table td {
        padding: 8px 0;
    }
    .tmw-notice {
        background: #f0f6fc;
        border-left: 4px solid #0073aa;
        padding: 12px;
        margin: 15px 0;
    }
    </style>
    <?php
}
