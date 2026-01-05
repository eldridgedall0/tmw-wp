<?php
/**
 * Template Name: Privacy Policy
 * Template Post Type: page
 *
 * @package flavor-starter-flavor
 */

get_header();
?>

<div class="tmw-section">
    <div class="tmw-container tmw-container-narrow">
        
        <header class="tmw-section-header" style="text-align: left;">
            <h1 class="tmw-section-title"><?php _e('Privacy Policy', 'flavor-starter-flavor'); ?></h1>
            <p class="text-muted"><?php printf(__('Last updated: %s', 'flavor-starter-flavor'), date_i18n(get_option('date_format'))); ?></p>
        </header>

        <div class="tmw-content-prose">
            
            <?php if (have_posts()) : while (have_posts()) : the_post(); ?>
                
                <?php if (get_the_content()) : ?>
                    <?php the_content(); ?>
                <?php else : ?>
                    
                    <!-- Default Privacy Content -->
                    <h2><?php _e('1. Information We Collect', 'flavor-starter-flavor'); ?></h2>
                    <p><?php _e('We collect information you provide directly, including:', 'flavor-starter-flavor'); ?></p>
                    <ul>
                        <li><?php _e('Account information (name, email, password)', 'flavor-starter-flavor'); ?></li>
                        <li><?php _e('Vehicle information (make, model, year, VIN)', 'flavor-starter-flavor'); ?></li>
                        <li><?php _e('Maintenance records and service history', 'flavor-starter-flavor'); ?></li>
                        <li><?php _e('Payment information (processed securely by Stripe)', 'flavor-starter-flavor'); ?></li>
                    </ul>

                    <h2><?php _e('2. How We Use Your Information', 'flavor-starter-flavor'); ?></h2>
                    <p><?php _e('We use the information we collect to:', 'flavor-starter-flavor'); ?></p>
                    <ul>
                        <li><?php _e('Provide, maintain, and improve the Service', 'flavor-starter-flavor'); ?></li>
                        <li><?php _e('Process transactions and send related information', 'flavor-starter-flavor'); ?></li>
                        <li><?php _e('Send service notifications and recall alerts', 'flavor-starter-flavor'); ?></li>
                        <li><?php _e('Respond to your comments and questions', 'flavor-starter-flavor'); ?></li>
                    </ul>

                    <h2><?php _e('3. Information Sharing', 'flavor-starter-flavor'); ?></h2>
                    <p><?php _e('We do not sell, trade, or rent your personal information to third parties. We may share information with:', 'flavor-starter-flavor'); ?></p>
                    <ul>
                        <li><?php _e('Service providers who assist in operating our platform', 'flavor-starter-flavor'); ?></li>
                        <li><?php _e('When required by law or to protect our rights', 'flavor-starter-flavor'); ?></li>
                    </ul>

                    <h2><?php _e('4. Data Security', 'flavor-starter-flavor'); ?></h2>
                    <p><?php _e('We implement industry-standard security measures to protect your data:', 'flavor-starter-flavor'); ?></p>
                    <ul>
                        <li><?php _e('SSL/TLS encryption for data in transit', 'flavor-starter-flavor'); ?></li>
                        <li><?php _e('Encrypted storage for sensitive data', 'flavor-starter-flavor'); ?></li>
                        <li><?php _e('Regular security audits and updates', 'flavor-starter-flavor'); ?></li>
                    </ul>

                    <h2><?php _e('5. Data Retention', 'flavor-starter-flavor'); ?></h2>
                    <p><?php _e('We retain your information for as long as your account is active or as needed to provide services. You may request deletion of your data at any time.', 'flavor-starter-flavor'); ?></p>

                    <h2><?php _e('6. Your Rights', 'flavor-starter-flavor'); ?></h2>
                    <p><?php _e('You have the right to:', 'flavor-starter-flavor'); ?></p>
                    <ul>
                        <li><?php _e('Access and export your data', 'flavor-starter-flavor'); ?></li>
                        <li><?php _e('Correct inaccurate information', 'flavor-starter-flavor'); ?></li>
                        <li><?php _e('Request deletion of your data', 'flavor-starter-flavor'); ?></li>
                        <li><?php _e('Opt out of marketing communications', 'flavor-starter-flavor'); ?></li>
                    </ul>

                    <h2><?php _e('7. Cookies', 'flavor-starter-flavor'); ?></h2>
                    <p><?php _e('We use cookies to maintain your session and remember your preferences. You can control cookies through your browser settings.', 'flavor-starter-flavor'); ?></p>

                    <h2><?php _e('8. Third-Party Services', 'flavor-starter-flavor'); ?></h2>
                    <p><?php _e('Our Service may contain links to third-party websites. We are not responsible for the privacy practices of these sites.', 'flavor-starter-flavor'); ?></p>

                    <h2><?php _e("9. Children's Privacy", 'flavor-starter-flavor'); ?></h2>
                    <p><?php _e('Our Service is not intended for children under 13. We do not knowingly collect information from children under 13.', 'flavor-starter-flavor'); ?></p>

                    <h2><?php _e('10. Changes to This Policy', 'flavor-starter-flavor'); ?></h2>
                    <p><?php _e('We may update this policy from time to time. We will notify you of any material changes via email or through the Service.', 'flavor-starter-flavor'); ?></p>

                    <h2><?php _e('11. Contact Us', 'flavor-starter-flavor'); ?></h2>
                    <p><?php _e('If you have questions about this Privacy Policy, please contact us at privacy@trackmywrench.com.', 'flavor-starter-flavor'); ?></p>

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
