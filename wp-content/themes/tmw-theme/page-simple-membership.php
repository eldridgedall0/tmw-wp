<?php
/**
 * Template Name: Simple Membership Page
 * 
 * Template for Simple Membership Plugin pages:
 * - Login
 * - Registration
 * - Profile
 * - Password Reset
 * - Membership Join
 *
 * @package tmw-theme
 */

get_header();

// Determine page type for custom styling
$page_slug = get_post_field('post_name', get_post());
$page_type = '';

// Map common SWPM page slugs to types
$swpm_page_types = array(
    'login'              => 'login',
    'membership-login'   => 'login',
    'register'           => 'register',
    'registration'       => 'register',
    'membership-join'    => 'join',
    'join'               => 'join',
    'profile'            => 'profile',
    'membership-profile' => 'profile',
    'edit-profile'       => 'profile',
    'password-reset'     => 'reset',
    'reset-password'     => 'reset',
);

if (isset($swpm_page_types[$page_slug])) {
    $page_type = $swpm_page_types[$page_slug];
}

// Get page icons
$page_icons = array(
    'login'    => '<svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"/><polyline points="10 17 15 12 10 7"/><line x1="15" y1="12" x2="3" y2="12"/></svg>',
    'register' => '<svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="8.5" cy="7" r="4"/><line x1="20" y1="8" x2="20" y2="14"/><line x1="23" y1="11" x2="17" y2="11"/></svg>',
    'join'     => '<svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="1" y="4" width="22" height="16" rx="2" ry="2"/><line x1="1" y1="10" x2="23" y2="10"/></svg>',
    'profile'  => '<svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>',
    'reset'    => '<svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 9.9-1"/></svg>',
);

// Content width class based on page type
$content_width = 'narrow'; // Default
if (in_array($page_type, array('login', 'reset'))) {
    $content_width = 'extra-narrow';
} elseif ($page_type === 'join') {
    $content_width = ''; // Full width for pricing/join pages
}
?>

<div class="tmw-page tmw-swpm-page tmw-swpm-<?php echo esc_attr($page_type ?: 'default'); ?>">
    <div class="tmw-page-content <?php echo esc_attr($content_width); ?>">
        
        <?php while (have_posts()) : the_post(); ?>
        
        <header class="tmw-page-header">
            <?php if ($page_type && isset($page_icons[$page_type])) : ?>
            <div class="page-icon" style="color: var(--tmw-primary); margin-bottom: 16px;">
                <?php echo $page_icons[$page_type]; ?>
            </div>
            <?php endif; ?>
            
            <h1><?php the_title(); ?></h1>
            
            <?php if (has_excerpt()) : ?>
            <p class="page-description"><?php echo get_the_excerpt(); ?></p>
            <?php else : 
                // Default descriptions for SWPM pages
                $default_descriptions = array(
                    'login'    => __('Sign in to access your account and manage your vehicles.', 'tmw-theme'),
                    'register' => __('Create your free account to start tracking your vehicle maintenance.', 'tmw-theme'),
                    'join'     => __('Choose the plan that works best for you.', 'tmw-theme'),
                    'profile'  => __('Manage your account settings and subscription.', 'tmw-theme'),
                    'reset'    => __('Enter your email to receive a password reset link.', 'tmw-theme'),
                );
                if ($page_type && isset($default_descriptions[$page_type])) :
            ?>
            <p class="page-description"><?php echo esc_html($default_descriptions[$page_type]); ?></p>
            <?php 
                endif;
            endif; 
            ?>
        </header>

        <div class="tmw-page-body swpm-page-wrapper <?php echo $page_type === 'join' ? 'transparent' : ''; ?>">
            <?php the_content(); ?>
            
            <?php 
            // Add helpful links below SWPM forms
            if ($page_type === 'login') : 
            ?>
            <div class="swpm-login-links">
                <p><?php _e("Don't have an account?", 'tmw-theme'); ?> 
                   <a href="<?php echo esc_url(home_url('/register/')); ?>"><?php _e('Sign up for free', 'tmw-theme'); ?></a>
                </p>
            </div>
            <?php elseif ($page_type === 'register') : ?>
            <div class="swpm-registration-links">
                <p><?php _e('Already have an account?', 'tmw-theme'); ?> 
                   <a href="<?php echo esc_url(home_url('/login/')); ?>"><?php _e('Sign in', 'tmw-theme'); ?></a>
                </p>
            </div>
            <?php elseif ($page_type === 'reset') : ?>
            <div class="swpm-login-links">
                <p><?php _e('Remember your password?', 'tmw-theme'); ?> 
                   <a href="<?php echo esc_url(home_url('/login/')); ?>"><?php _e('Back to login', 'tmw-theme'); ?></a>
                </p>
            </div>
            <?php endif; ?>
        </div>

        <?php endwhile; ?>

    </div>
</div>

<?php get_footer(); ?>
