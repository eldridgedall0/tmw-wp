<?php
/**
 * Template Helper Functions
 *
 * Helper functions for use in templates.
 *
 * @package flavor-starter-flavor
 */

if (!defined('ABSPATH')) {
    exit;
}

// =============================================================================
// ALERT/MESSAGE DISPLAY
// =============================================================================

/**
 * Display alert message
 *
 * @param string $message Message text
 * @param string $type Type: success, error, warning, info
 * @param bool $dismissible Whether alert can be dismissed
 */
function tmw_alert($message, $type = 'info', $dismissible = true) {
    $class = 'tmw-alert tmw-alert-' . esc_attr($type);
    if ($dismissible) {
        $class .= ' tmw-alert-dismissible';
    }
    ?>
    <div class="<?php echo $class; ?>" role="alert">
        <span class="tmw-alert-message"><?php echo wp_kses_post($message); ?></span>
        <?php if ($dismissible) : ?>
            <button type="button" class="tmw-alert-close" aria-label="<?php esc_attr_e('Close', 'flavor-starter-flavor'); ?>">
                <i class="fas fa-times"></i>
            </button>
        <?php endif; ?>
    </div>
    <?php
}

/**
 * Display flash messages from transients
 */
function tmw_display_flash_messages() {
    $user_id = get_current_user_id();
    
    // Check for success messages
    $success = get_transient('tmw_success_' . ($user_id ?: 'guest'));
    if ($success) {
        tmw_alert($success, 'success');
        delete_transient('tmw_success_' . ($user_id ?: 'guest'));
    }

    // Check for error messages
    $error = get_transient('tmw_error_' . ($user_id ?: 'guest'));
    if ($error) {
        tmw_alert($error, 'error');
        delete_transient('tmw_error_' . ($user_id ?: 'guest'));
    }

    // Check for info messages
    $info = get_transient('tmw_info_' . ($user_id ?: 'guest'));
    if ($info) {
        tmw_alert($info, 'info');
        delete_transient('tmw_info_' . ($user_id ?: 'guest'));
    }
}

/**
 * Set a flash message
 *
 * @param string $message
 * @param string $type success, error, warning, info
 */
function tmw_set_flash($message, $type = 'info') {
    $user_id = get_current_user_id();
    set_transient('tmw_' . $type . '_' . ($user_id ?: 'guest'), $message, 60);
}

// =============================================================================
// FORM HELPERS
// =============================================================================

/**
 * Output a form field
 *
 * @param array $args Field arguments
 */
function tmw_field($args) {
    $defaults = array(
        'type'        => 'text',
        'name'        => '',
        'id'          => '',
        'label'       => '',
        'value'       => '',
        'placeholder' => '',
        'required'    => false,
        'class'       => '',
        'autocomplete' => '',
        'icon'        => '',
        'help'        => '',
    );

    $args = wp_parse_args($args, $defaults);
    
    if (empty($args['id'])) {
        $args['id'] = $args['name'];
    }

    $input_class = 'tmw-input';
    if (!empty($args['class'])) {
        $input_class .= ' ' . $args['class'];
    }
    if (!empty($args['icon'])) {
        $input_class .= ' has-icon';
    }
    ?>
    <div class="tmw-field">
        <?php if (!empty($args['label'])) : ?>
            <label for="<?php echo esc_attr($args['id']); ?>" class="tmw-label">
                <?php echo esc_html($args['label']); ?>
                <?php if ($args['required']) : ?>
                    <span class="required">*</span>
                <?php endif; ?>
            </label>
        <?php endif; ?>
        
        <div class="tmw-input-wrap<?php echo !empty($args['icon']) ? ' has-icon' : ''; ?>">
            <?php if (!empty($args['icon'])) : ?>
                <span class="tmw-input-icon">
                    <i class="<?php echo esc_attr($args['icon']); ?>"></i>
                </span>
            <?php endif; ?>
            
            <?php if ($args['type'] === 'textarea') : ?>
                <textarea
                    name="<?php echo esc_attr($args['name']); ?>"
                    id="<?php echo esc_attr($args['id']); ?>"
                    class="<?php echo esc_attr($input_class); ?>"
                    placeholder="<?php echo esc_attr($args['placeholder']); ?>"
                    <?php echo $args['required'] ? 'required' : ''; ?>
                ><?php echo esc_textarea($args['value']); ?></textarea>
            <?php else : ?>
                <input
                    type="<?php echo esc_attr($args['type']); ?>"
                    name="<?php echo esc_attr($args['name']); ?>"
                    id="<?php echo esc_attr($args['id']); ?>"
                    class="<?php echo esc_attr($input_class); ?>"
                    value="<?php echo esc_attr($args['value']); ?>"
                    placeholder="<?php echo esc_attr($args['placeholder']); ?>"
                    <?php echo !empty($args['autocomplete']) ? 'autocomplete="' . esc_attr($args['autocomplete']) . '"' : ''; ?>
                    <?php echo $args['required'] ? 'required' : ''; ?>
                >
            <?php endif; ?>
            
            <?php if ($args['type'] === 'password') : ?>
                <button type="button" class="tmw-password-toggle" aria-label="<?php esc_attr_e('Toggle password visibility', 'flavor-starter-flavor'); ?>">
                    <i class="fas fa-eye"></i>
                </button>
            <?php endif; ?>
        </div>
        
        <?php if (!empty($args['help'])) : ?>
            <p class="tmw-field-help"><?php echo wp_kses_post($args['help']); ?></p>
        <?php endif; ?>
    </div>
    <?php
}

/**
 * Output hidden nonce field
 *
 * @param string $action
 */
function tmw_nonce_field($action = 'tmw_form') {
    wp_nonce_field($action, 'tmw_nonce');
}

// =============================================================================
// BUTTON HELPERS
// =============================================================================

/**
 * Output a button
 *
 * @param array $args Button arguments
 */
function tmw_button($args) {
    $defaults = array(
        'text'    => __('Submit', 'flavor-starter-flavor'),
        'type'    => 'submit',
        'style'   => 'primary', // primary, secondary, ghost, danger
        'size'    => 'default', // small, default, large
        'icon'    => '',
        'href'    => '',
        'class'   => '',
        'loading' => false,
        'disabled' => false,
        'full_width' => false,
    );

    $args = wp_parse_args($args, $defaults);
    
    $classes = array('tmw-btn');
    $classes[] = 'tmw-btn-' . $args['style'];
    
    if ($args['size'] !== 'default') {
        $classes[] = 'tmw-btn-' . $args['size'];
    }
    if ($args['full_width']) {
        $classes[] = 'tmw-btn-full';
    }
    if ($args['loading']) {
        $classes[] = 'tmw-btn-loading';
    }
    if (!empty($args['class'])) {
        $classes[] = $args['class'];
    }

    $tag = !empty($args['href']) ? 'a' : 'button';
    ?>
    <<?php echo $tag; ?>
        <?php if ($tag === 'a') : ?>
            href="<?php echo esc_url($args['href']); ?>"
        <?php else : ?>
            type="<?php echo esc_attr($args['type']); ?>"
        <?php endif; ?>
        class="<?php echo esc_attr(implode(' ', $classes)); ?>"
        <?php echo $args['disabled'] ? 'disabled' : ''; ?>
    >
        <?php if (!empty($args['icon'])) : ?>
            <i class="<?php echo esc_attr($args['icon']); ?>"></i>
        <?php endif; ?>
        <span class="tmw-btn-text"><?php echo esc_html($args['text']); ?></span>
        <span class="tmw-btn-loader">
            <i class="fas fa-spinner fa-spin"></i>
        </span>
    </<?php echo $tag; ?>>
    <?php
}

// =============================================================================
// NAVIGATION HELPERS
// =============================================================================

/**
 * Get navigation menu items
 *
 * @param string $location Menu location
 * @return array Menu items
 */
function tmw_get_menu_items($location) {
    $locations = get_nav_menu_locations();
    
    if (!isset($locations[$location])) {
        return array();
    }

    $menu = wp_get_nav_menu_object($locations[$location]);
    
    if (!$menu) {
        return array();
    }

    return wp_get_nav_menu_items($menu->term_id);
}

/**
 * Output auth navigation links
 */
function tmw_auth_nav() {
    if (is_user_logged_in()) {
        $user = wp_get_current_user();
        ?>
        <div class="tmw-auth-nav tmw-auth-nav-logged-in">
            <a href="<?php echo esc_url(tmw_get_app_url()); ?>" class="tmw-btn tmw-btn-primary">
                <i class="fas fa-rocket"></i>
                <?php _e('Go to App', 'flavor-starter-flavor'); ?>
            </a>
            <div class="tmw-user-menu">
                <button type="button" class="tmw-user-menu-toggle">
                    <img src="<?php echo esc_url(get_avatar_url($user->ID, array('size' => 32))); ?>" 
                         alt="" class="tmw-avatar">
                    <span class="tmw-user-name"><?php echo esc_html($user->display_name); ?></span>
                    <i class="fas fa-chevron-down"></i>
                </button>
                <div class="tmw-user-dropdown">
                    <a href="<?php echo esc_url(tmw_get_page_url('my-profile')); ?>">
                        <i class="fas fa-user"></i>
                        <?php _e('My Profile', 'flavor-starter-flavor'); ?>
                    </a>
                    <a href="<?php echo esc_url(tmw_get_page_url('pricing')); ?>">
                        <i class="fas fa-crown"></i>
                        <?php _e('Subscription', 'flavor-starter-flavor'); ?>
                    </a>
                    <hr>
                    <a href="<?php echo esc_url(wp_logout_url(home_url('/'))); ?>">
                        <i class="fas fa-sign-out-alt"></i>
                        <?php _e('Sign Out', 'flavor-starter-flavor'); ?>
                    </a>
                </div>
            </div>
        </div>
        <?php
    } else {
        ?>
        <div class="tmw-auth-nav tmw-auth-nav-logged-out">
            <a href="<?php echo esc_url(tmw_get_page_url('login')); ?>" class="tmw-btn tmw-btn-ghost">
                <?php _e('Sign In', 'flavor-starter-flavor'); ?>
            </a>
            <a href="<?php echo esc_url(tmw_get_page_url('register')); ?>" class="tmw-btn tmw-btn-primary">
                <?php _e('Get Started', 'flavor-starter-flavor'); ?>
            </a>
        </div>
        <?php
    }
}

// =============================================================================
// PRICING HELPERS
// =============================================================================

/**
 * Output pricing card
 *
 * @param array $args Card arguments
 */
function tmw_pricing_card($args) {
    $defaults = array(
        'tier'       => 'free',
        'name'       => '',
        'price'      => '0',
        'period'     => 'month',
        'popular'    => false,
        'features'   => array(),
        'cta_text'   => __('Get Started', 'flavor-starter-flavor'),
        'cta_url'    => '#',
        'current'    => false,
    );

    $args = wp_parse_args($args, $defaults);
    $limits = tmw_get_tier_limits($args['tier']);
    
    $classes = array('tmw-pricing-card', 'tmw-pricing-' . $args['tier']);
    if ($args['popular']) {
        $classes[] = 'tmw-pricing-popular';
    }
    if ($args['current']) {
        $classes[] = 'tmw-pricing-current';
    }
    ?>
    <div class="<?php echo esc_attr(implode(' ', $classes)); ?>">
        <?php if ($args['popular']) : ?>
            <div class="tmw-pricing-badge"><?php _e('Most Popular', 'flavor-starter-flavor'); ?></div>
        <?php endif; ?>
        
        <div class="tmw-pricing-header">
            <h3 class="tmw-pricing-name"><?php echo esc_html($args['name']); ?></h3>
            <div class="tmw-pricing-price">
                <span class="tmw-pricing-currency">$</span>
                <span class="tmw-pricing-amount"><?php echo esc_html($args['price']); ?></span>
                <span class="tmw-pricing-period">/<?php echo esc_html($args['period']); ?></span>
            </div>
        </div>
        
        <ul class="tmw-pricing-features">
            <?php foreach ($args['features'] as $feature) : ?>
                <li class="<?php echo !empty($feature['disabled']) ? 'disabled' : ''; ?>">
                    <i class="fas <?php echo !empty($feature['disabled']) ? 'fa-times' : 'fa-check'; ?>"></i>
                    <?php echo esc_html($feature['text']); ?>
                    <?php if (!empty($feature['badge'])) : ?>
                        <span class="tmw-feature-badge"><?php echo esc_html($feature['badge']); ?></span>
                    <?php endif; ?>
                </li>
            <?php endforeach; ?>
        </ul>
        
        <div class="tmw-pricing-footer">
            <?php if ($args['current']) : ?>
                <span class="tmw-btn tmw-btn-ghost tmw-btn-full" disabled>
                    <?php _e('Current Plan', 'flavor-starter-flavor'); ?>
                </span>
            <?php else : ?>
                <a href="<?php echo esc_url($args['cta_url']); ?>" class="tmw-btn tmw-btn-primary tmw-btn-full">
                    <?php echo esc_html($args['cta_text']); ?>
                </a>
            <?php endif; ?>
        </div>
    </div>
    <?php
}

// =============================================================================
// LOGO HELPER
// =============================================================================

/**
 * Output site logo
 *
 * @param array $args Logo arguments
 */
function tmw_logo($args = array()) {
    $defaults = array(
        'class' => '',
        'link'  => true,
    );
    $args = wp_parse_args($args, $defaults);

    $logo_id = get_theme_mod('custom_logo');
    $logo_url = $logo_id ? wp_get_attachment_image_url($logo_id, 'full') : TMW_THEME_URI . '/assets/images/logo.png';
    $site_name = get_bloginfo('name');
    
    $class = 'tmw-logo' . (!empty($args['class']) ? ' ' . $args['class'] : '');
    
    if ($args['link']) {
        echo '<a href="' . esc_url(home_url('/')) . '" class="' . esc_attr($class) . '">';
    } else {
        echo '<span class="' . esc_attr($class) . '">';
    }
    
    echo '<img src="' . esc_url($logo_url) . '" alt="' . esc_attr($site_name) . '" class="tmw-logo-img">';
    echo '<span class="tmw-logo-text">' . esc_html($site_name) . '</span>';
    
    if ($args['link']) {
        echo '</a>';
    } else {
        echo '</span>';
    }
}

// =============================================================================
// THEME TOGGLE
// =============================================================================

/**
 * Output theme toggle button
 */
function tmw_theme_toggle() {
    ?>
    <button type="button" class="tmw-theme-toggle" 
            aria-label="<?php esc_attr_e('Toggle dark/light mode', 'flavor-starter-flavor'); ?>"
            title="<?php esc_attr_e('Toggle theme', 'flavor-starter-flavor'); ?>">
        <i class="fas fa-sun tmw-icon-light"></i>
        <i class="fas fa-moon tmw-icon-dark"></i>
    </button>
    <?php
}
