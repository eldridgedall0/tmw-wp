<?php
/**
 * Enqueue Page Template Styles
 * 
 * Add this to your theme's functions.php or inc/enqueue.php
 *
 * @package tmw-theme
 */

/**
 * Enqueue page template styles
 */
function tmw_enqueue_page_styles() {
    // Main page template CSS
    wp_enqueue_style(
        'tmw-pages',
        get_template_directory_uri() . '/assets/css/pages.css',
        array(),
        '1.0.0'
    );
    
    // Only load on Simple Membership pages for better performance
    if (is_page_template('page-simple-membership.php')) {
        // Add any SWPM-specific JS if needed
        wp_add_inline_script('jquery', '
            jQuery(document).ready(function($) {
                // Auto-focus first input in SWPM forms
                $(".swpm-login-widget-form input:visible:first, .swpm-registration-widget-form input:visible:first").focus();
                
                // Add loading state to submit buttons
                $(".swpm-login-widget-form, .swpm-registration-widget-form, .swpm-pw-reset-widget-form").on("submit", function() {
                    var $btn = $(this).find("input[type=submit], button[type=submit]");
                    $btn.prop("disabled", true).val("Please wait...");
                });
            });
        ');
    }
}
add_action('wp_enqueue_scripts', 'tmw_enqueue_page_styles');

/**
 * Add body classes for page templates
 */
function tmw_page_body_classes($classes) {
    if (is_page_template('page-simple-membership.php')) {
        $classes[] = 'tmw-swpm-page';
        
        // Add specific page type class
        $page_slug = get_post_field('post_name', get_post());
        $swpm_types = array(
            'login' => 'tmw-login-page',
            'membership-login' => 'tmw-login-page',
            'register' => 'tmw-register-page',
            'registration' => 'tmw-register-page',
            'membership-join' => 'tmw-join-page',
            'profile' => 'tmw-profile-page',
            'membership-profile' => 'tmw-profile-page',
            'password-reset' => 'tmw-reset-page',
        );
        
        if (isset($swpm_types[$page_slug])) {
            $classes[] = $swpm_types[$page_slug];
        }
    }
    
    return $classes;
}
add_filter('body_class', 'tmw_page_body_classes');
