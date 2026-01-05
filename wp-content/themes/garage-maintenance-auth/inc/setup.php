<?php
/**
 * Theme Setup
 *
 * @package flavor-starter-flavor
 */

if (!defined('ABSPATH')) {
    exit;
}

// =============================================================================
// CONTENT WIDTH
// =============================================================================
if (!isset($content_width)) {
    $content_width = 1200;
}

// =============================================================================
// WIDGETS
// =============================================================================
add_action('widgets_init', 'tmw_widgets_init');

function tmw_widgets_init() {
    register_sidebar(array(
        'name'          => __('Footer Column 1', 'flavor-starter-flavor'),
        'id'            => 'footer-1',
        'description'   => __('First footer column', 'flavor-starter-flavor'),
        'before_widget' => '<div id="%1$s" class="widget %2$s">',
        'after_widget'  => '</div>',
        'before_title'  => '<h4 class="widget-title">',
        'after_title'   => '</h4>',
    ));

    register_sidebar(array(
        'name'          => __('Footer Column 2', 'flavor-starter-flavor'),
        'id'            => 'footer-2',
        'description'   => __('Second footer column', 'flavor-starter-flavor'),
        'before_widget' => '<div id="%1$s" class="widget %2$s">',
        'after_widget'  => '</div>',
        'before_title'  => '<h4 class="widget-title">',
        'after_title'   => '</h4>',
    ));

    register_sidebar(array(
        'name'          => __('Footer Column 3', 'flavor-starter-flavor'),
        'id'            => 'footer-3',
        'description'   => __('Third footer column', 'flavor-starter-flavor'),
        'before_widget' => '<div id="%1$s" class="widget %2$s">',
        'after_widget'  => '</div>',
        'before_title'  => '<h4 class="widget-title">',
        'after_title'   => '</h4>',
    ));
}

// =============================================================================
// DISABLE EMOJIS (Performance)
// =============================================================================
add_action('init', 'tmw_disable_emojis');

function tmw_disable_emojis() {
    remove_action('wp_head', 'print_emoji_detection_script', 7);
    remove_action('admin_print_scripts', 'print_emoji_detection_script');
    remove_action('wp_print_styles', 'print_emoji_styles');
    remove_action('admin_print_styles', 'print_emoji_styles');
    remove_filter('the_content_feed', 'wp_staticize_emoji');
    remove_filter('comment_text_rss', 'wp_staticize_emoji');
    remove_filter('wp_mail', 'wp_staticize_emoji_for_email');
}

// =============================================================================
// REMOVE WORDPRESS GENERATOR TAG
// =============================================================================
remove_action('wp_head', 'wp_generator');

// =============================================================================
// EXCERPT LENGTH
// =============================================================================
add_filter('excerpt_length', function($length) {
    return 30;
}, 999);

add_filter('excerpt_more', function($more) {
    return '...';
});

// =============================================================================
// CUSTOM LOGIN LOGO (WordPress Admin)
// =============================================================================
add_action('login_enqueue_scripts', 'tmw_custom_login_logo');

function tmw_custom_login_logo() {
    $logo = TMW_THEME_URI . '/assets/images/logo.png';
    ?>
    <style type="text/css">
        #login h1 a, .login h1 a {
            background-image: url(<?php echo esc_url($logo); ?>);
            background-size: contain;
            background-repeat: no-repeat;
            width: 100px;
            height: 100px;
        }
    </style>
    <?php
}

// =============================================================================
// DISABLE GUTENBERG FOR PAGES WITH OUR TEMPLATES
// =============================================================================
add_filter('use_block_editor_for_post', 'tmw_disable_gutenberg_for_templates', 10, 2);

function tmw_disable_gutenberg_for_templates($use_block_editor, $post) {
    if ($post && $post->post_type === 'page') {
        $template = get_page_template_slug($post->ID);
        if (strpos($template, 'templates/template-') === 0) {
            return false;
        }
    }
    return $use_block_editor;
}
