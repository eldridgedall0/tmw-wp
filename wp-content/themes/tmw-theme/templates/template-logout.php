<?php
/**
 * Template Name: Logout
 * Template Post Type: page
 *
 * @package flavor-starter-flavor
 */

get_header();
?>

<div class="tmw-auth-page">
    <div class="tmw-auth-container">
        <div class="tmw-auth-card tmw-logout-page">
            
            <div class="tmw-logout-icon">
                <i class="fas fa-check-circle"></i>
            </div>
            
            <h1 class="tmw-logout-title"><?php _e("You've been signed out", 'flavor-starter-flavor'); ?></h1>
            
            <p class="tmw-logout-text">
                <?php _e('Thank you for using TrackMyWrench. You have been successfully signed out of your account.', 'flavor-starter-flavor'); ?>
            </p>

            <div class="flex flex-col gap-3">
                <a href="<?php echo esc_url(tmw_get_page_url('login')); ?>" class="tmw-btn tmw-btn-primary tmw-btn-full">
                    <i class="fas fa-sign-in-alt"></i>
                    <?php _e('Sign In Again', 'flavor-starter-flavor'); ?>
                </a>
                
                <a href="<?php echo esc_url(home_url('/')); ?>" class="tmw-btn tmw-btn-secondary tmw-btn-full">
                    <i class="fas fa-home"></i>
                    <?php _e('Go to Homepage', 'flavor-starter-flavor'); ?>
                </a>
            </div>

        </div>
    </div>
</div>

<?php
get_footer();
