<?php
/**
 * Template Name: Terms of Service
 * Template Post Type: page
 *
 * @package flavor-starter-flavor
 */

get_header();
?>

<div class="tmw-section">
    <div class="tmw-container tmw-container-narrow">
        
        <header class="tmw-section-header" style="text-align: left;">
            <h1 class="tmw-section-title"><?php _e('Terms of Service', 'flavor-starter-flavor'); ?></h1>
            <p class="text-muted"><?php printf(__('Last updated: %s', 'flavor-starter-flavor'), date_i18n(get_option('date_format'))); ?></p>
        </header>

        <div class="tmw-content-prose">
            
            <?php if (have_posts()) : while (have_posts()) : the_post(); ?>
                
                <?php if (get_the_content()) : ?>
                    <?php the_content(); ?>
                <?php else : ?>
                    
                    <!-- Default Terms Content -->
                    <h2><?php _e('1. Acceptance of Terms', 'flavor-starter-flavor'); ?></h2>
                    <p><?php _e('By accessing and using TrackMyWrench ("the Service"), you accept and agree to be bound by the terms and provision of this agreement.', 'flavor-starter-flavor'); ?></p>

                    <h2><?php _e('2. Description of Service', 'flavor-starter-flavor'); ?></h2>
                    <p><?php _e('TrackMyWrench provides a vehicle maintenance tracking platform that allows users to record service history, track maintenance schedules, and manage vehicle records.', 'flavor-starter-flavor'); ?></p>

                    <h2><?php _e('3. User Accounts', 'flavor-starter-flavor'); ?></h2>
                    <p><?php _e('You are responsible for maintaining the confidentiality of your account and password. You agree to accept responsibility for all activities that occur under your account.', 'flavor-starter-flavor'); ?></p>

                    <h2><?php _e('4. User Content', 'flavor-starter-flavor'); ?></h2>
                    <p><?php _e('You retain all rights to any content you submit, post or display on or through the Service. By submitting content, you grant us a license to use, modify, and display such content in connection with the Service.', 'flavor-starter-flavor'); ?></p>

                    <h2><?php _e('5. Subscriptions and Payments', 'flavor-starter-flavor'); ?></h2>
                    <p><?php _e('Some features of the Service require a paid subscription. By subscribing, you agree to pay the applicable fees. Subscriptions automatically renew unless cancelled.', 'flavor-starter-flavor'); ?></p>

                    <h2><?php _e('6. Cancellation and Refunds', 'flavor-starter-flavor'); ?></h2>
                    <p><?php _e('You may cancel your subscription at any time. Refunds are available within 30 days of initial purchase. After 30 days, no refunds will be provided for partial subscription periods.', 'flavor-starter-flavor'); ?></p>

                    <h2><?php _e('7. Prohibited Uses', 'flavor-starter-flavor'); ?></h2>
                    <p><?php _e('You may not use the Service for any illegal purpose, to violate any laws, to infringe on intellectual property rights, or to transmit malicious code.', 'flavor-starter-flavor'); ?></p>

                    <h2><?php _e('8. Disclaimer of Warranties', 'flavor-starter-flavor'); ?></h2>
                    <p><?php _e('The Service is provided "as is" without warranties of any kind. We do not guarantee that the Service will be uninterrupted, secure, or error-free.', 'flavor-starter-flavor'); ?></p>

                    <h2><?php _e('9. Limitation of Liability', 'flavor-starter-flavor'); ?></h2>
                    <p><?php _e('In no event shall TrackMyWrench be liable for any indirect, incidental, special, consequential, or punitive damages resulting from your use of the Service.', 'flavor-starter-flavor'); ?></p>

                    <h2><?php _e('10. Changes to Terms', 'flavor-starter-flavor'); ?></h2>
                    <p><?php _e('We reserve the right to modify these terms at any time. We will notify users of any material changes via email or through the Service.', 'flavor-starter-flavor'); ?></p>

                    <h2><?php _e('11. Contact Us', 'flavor-starter-flavor'); ?></h2>
                    <p><?php _e('If you have any questions about these Terms, please contact us at support@trackmywrench.com.', 'flavor-starter-flavor'); ?></p>

                <?php endif; ?>

            <?php endwhile; endif; ?>

        </div>

        <div class="mt-8 text-center">
            <a href="<?php echo esc_url(home_url('/')); ?>" class="tmw-btn tmw-btn-secondary">
                <i class="fas fa-arrow-left"></i>
                <?php _e('Back to Home', 'flavor-starter-flavor'); ?>
            </a>
        </div>

    </div>
</div>

<?php
get_footer();
