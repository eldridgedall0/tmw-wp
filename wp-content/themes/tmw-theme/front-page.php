<?php
/**
 * Front Page Template
 *
 * The landing page with hero, features, pricing, and CTA sections.
 *
 * @package flavor-starter-flavor
 */

get_header();
?>

<!-- Hero Section -->
<section class="tmw-hero tmw-section-lg">
    <div class="tmw-container">
        <div class="tmw-hero-content">
            <h1 class="tmw-hero-title">
                <?php _e('Track Your Vehicle Maintenance', 'flavor-starter-flavor'); ?>
                <span><?php _e('The Smart Way', 'flavor-starter-flavor'); ?></span>
            </h1>
            
            <p class="tmw-hero-subtitle">
                <?php _e('Never miss an oil change, tire rotation, or scheduled service again. Keep all your vehicle records in one secure place.', 'flavor-starter-flavor'); ?>
            </p>
            
            <div class="tmw-hero-actions">
                <?php if (is_user_logged_in()) : ?>
                    <a href="<?php echo esc_url(tmw_get_app_url()); ?>" class="tmw-btn tmw-btn-primary tmw-btn-large">
                        <i class="fas fa-rocket"></i>
                        <?php _e('Go to App', 'flavor-starter-flavor'); ?>
                    </a>
                <?php else : ?>
                    <a href="<?php echo esc_url(tmw_get_page_url('register')); ?>" class="tmw-btn tmw-btn-primary tmw-btn-large">
                        <?php _e('Start Free', 'flavor-starter-flavor'); ?>
                    </a>
                    <a href="#features" class="tmw-btn tmw-btn-secondary tmw-btn-large">
                        <?php _e('Learn More', 'flavor-starter-flavor'); ?>
                    </a>
                <?php endif; ?>
            </div>

            <div class="tmw-hero-stats">
                <div class="tmw-hero-stat">
                    <div class="tmw-hero-stat-value">10K+</div>
                    <div class="tmw-hero-stat-label"><?php _e('Active Users', 'flavor-starter-flavor'); ?></div>
                </div>
                <div class="tmw-hero-stat">
                    <div class="tmw-hero-stat-value">50K+</div>
                    <div class="tmw-hero-stat-label"><?php _e('Vehicles Tracked', 'flavor-starter-flavor'); ?></div>
                </div>
                <div class="tmw-hero-stat">
                    <div class="tmw-hero-stat-value">500K+</div>
                    <div class="tmw-hero-stat-label"><?php _e('Service Records', 'flavor-starter-flavor'); ?></div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Features Section -->
<section id="features" class="tmw-features tmw-section">
    <div class="tmw-container">
        <header class="tmw-section-header">
            <h2 class="tmw-section-title"><?php _e('Everything You Need', 'flavor-starter-flavor'); ?></h2>
            <p class="tmw-section-subtitle">
                <?php _e('Powerful features to keep your vehicles running smoothly and your records organized.', 'flavor-starter-flavor'); ?>
            </p>
        </header>

        <div class="tmw-features-grid">
            
            <div class="tmw-feature-card">
                <div class="tmw-feature-icon">
                    <i class="fas fa-car"></i>
                </div>
                <h3 class="tmw-feature-title"><?php _e('Multiple Vehicles', 'flavor-starter-flavor'); ?></h3>
                <p class="tmw-feature-text">
                    <?php _e('Track maintenance for all your vehicles in one place. Cars, trucks, motorcycles, and more.', 'flavor-starter-flavor'); ?>
                </p>
            </div>

            <div class="tmw-feature-card">
                <div class="tmw-feature-icon">
                    <i class="fas fa-bell"></i>
                </div>
                <h3 class="tmw-feature-title"><?php _e('Recall Alerts', 'flavor-starter-flavor'); ?></h3>
                <p class="tmw-feature-text">
                    <?php _e('Automatic notifications when recalls are issued for your vehicles. Stay safe on the road.', 'flavor-starter-flavor'); ?>
                </p>
            </div>

            <div class="tmw-feature-card">
                <div class="tmw-feature-icon">
                    <i class="fas fa-file-alt"></i>
                </div>
                <h3 class="tmw-feature-title"><?php _e('Service History', 'flavor-starter-flavor'); ?></h3>
                <p class="tmw-feature-text">
                    <?php _e('Complete records of all maintenance and repairs. Attach receipts and documents.', 'flavor-starter-flavor'); ?>
                </p>
            </div>

            <div class="tmw-feature-card">
                <div class="tmw-feature-icon">
                    <i class="fas fa-download"></i>
                </div>
                <h3 class="tmw-feature-title"><?php _e('Export Reports', 'flavor-starter-flavor'); ?></h3>
                <p class="tmw-feature-text">
                    <?php _e('Generate professional PDF reports for insurance, sales, or personal records.', 'flavor-starter-flavor'); ?>
                </p>
            </div>

        </div>
    </div>
</section>

<!-- Pricing Section -->
<section id="pricing" class="tmw-pricing-section tmw-section">
    <div class="tmw-container">
        <header class="tmw-section-header">
            <h2 class="tmw-section-title"><?php _e('Simple, Transparent Pricing', 'flavor-starter-flavor'); ?></h2>
            <p class="tmw-section-subtitle">
                <?php _e('Start free and upgrade as you grow. No hidden fees.', 'flavor-starter-flavor'); ?>
            </p>
        </header>

        <!-- Pricing Toggle -->
        <div class="tmw-pricing-toggle">
            <span class="tmw-pricing-toggle-label active" data-period="monthly"><?php _e('Monthly', 'flavor-starter-flavor'); ?></span>
            <button type="button" class="tmw-pricing-switch" aria-label="<?php esc_attr_e('Toggle annual pricing', 'flavor-starter-flavor'); ?>"></button>
            <span class="tmw-pricing-toggle-label" data-period="yearly"><?php _e('Yearly', 'flavor-starter-flavor'); ?></span>
            <span class="tmw-pricing-save"><?php _e('Save 20%', 'flavor-starter-flavor'); ?></span>
        </div>

        <div class="tmw-pricing-grid">
            
            <?php
            $current_tier = is_user_logged_in() ? tmw_get_user_tier() : 'none';
            $free_limits = tmw_get_tier_limits('free');
            $paid_limits = tmw_get_tier_limits('paid');
            $fleet_limits = tmw_get_tier_limits('fleet');
            ?>

            <!-- Free Tier -->
            <div class="tmw-pricing-card tmw-pricing-free <?php echo $current_tier === 'free' ? 'tmw-pricing-current' : ''; ?>">
                <div class="tmw-pricing-header">
                    <h3 class="tmw-pricing-name"><?php _e('Free', 'flavor-starter-flavor'); ?></h3>
                    <div class="tmw-pricing-price">
                        <span class="tmw-pricing-currency">$</span>
                        <span class="tmw-pricing-amount" data-price-monthly="0" data-price-yearly="0">0</span>
                        <span class="tmw-pricing-period">/month</span>
                    </div>
                </div>
                <ul class="tmw-pricing-features">
                    <li><i class="fas fa-check"></i> <?php printf(__('%d Vehicles', 'flavor-starter-flavor'), $free_limits['max_vehicles']); ?></li>
                    <li><i class="fas fa-check"></i> <?php printf(__('%d Total Entries', 'flavor-starter-flavor'), $free_limits['max_entries']); ?></li>
                    <li><i class="fas fa-check"></i> <?php printf(__('%d Templates', 'flavor-starter-flavor'), $free_limits['max_templates']); ?></li>
                    <li class="disabled"><i class="fas fa-times"></i> <?php _e('Attachments', 'flavor-starter-flavor'); ?></li>
                    <li class="disabled"><i class="fas fa-times"></i> <?php _e('Recall Alerts', 'flavor-starter-flavor'); ?></li>
                    <li class="disabled"><i class="fas fa-times"></i> <?php _e('Export Reports', 'flavor-starter-flavor'); ?></li>
                </ul>
                <div class="tmw-pricing-footer">
                    <?php if ($current_tier === 'free') : ?>
                        <span class="tmw-btn tmw-btn-ghost tmw-btn-full" disabled><?php _e('Current Plan', 'flavor-starter-flavor'); ?></span>
                    <?php else : ?>
                        <a href="<?php echo esc_url(tmw_get_page_url('register')); ?>" class="tmw-btn tmw-btn-secondary tmw-btn-full">
                            <?php _e('Get Started', 'flavor-starter-flavor'); ?>
                        </a>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Paid Tier -->
            <div class="tmw-pricing-card tmw-pricing-paid tmw-pricing-popular <?php echo $current_tier === 'paid' ? 'tmw-pricing-current' : ''; ?>">
                <div class="tmw-pricing-badge"><?php _e('Most Popular', 'flavor-starter-flavor'); ?></div>
                <div class="tmw-pricing-header">
                    <h3 class="tmw-pricing-name"><?php _e('Paid', 'flavor-starter-flavor'); ?></h3>
                    <div class="tmw-pricing-price">
                        <span class="tmw-pricing-currency">$</span>
                        <span class="tmw-pricing-amount" data-price-monthly="9" data-price-yearly="86">9</span>
                        <span class="tmw-pricing-period">/month</span>
                    </div>
                </div>
                <ul class="tmw-pricing-features">
                    <li><i class="fas fa-check"></i> <?php printf(__('%d Vehicles', 'flavor-starter-flavor'), $paid_limits['max_vehicles']); ?></li>
                    <li><i class="fas fa-check"></i> <?php _e('Unlimited Entries', 'flavor-starter-flavor'); ?></li>
                    <li><i class="fas fa-check"></i> <?php printf(__('%d Attachments/Entry', 'flavor-starter-flavor'), $paid_limits['attachments_per_entry']); ?></li>
                    <li><i class="fas fa-check"></i> <?php _e('Recall Alerts', 'flavor-starter-flavor'); ?></li>
                    <li><i class="fas fa-check"></i> <?php _e('CSV & PDF Export', 'flavor-starter-flavor'); ?></li>
                    <li><i class="fas fa-check"></i> <?php _e('Email Support', 'flavor-starter-flavor'); ?></li>
                </ul>
                <div class="tmw-pricing-footer">
                    <?php if ($current_tier === 'paid') : ?>
                        <span class="tmw-btn tmw-btn-ghost tmw-btn-full" disabled><?php _e('Current Plan', 'flavor-starter-flavor'); ?></span>
                    <?php else : ?>
                        <a href="<?php echo esc_url(tmw_get_page_url('pricing')); ?>" class="tmw-btn tmw-btn-primary tmw-btn-full">
                            <?php _e('Upgrade Now', 'flavor-starter-flavor'); ?>
                        </a>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Fleet Tier -->
            <div class="tmw-pricing-card tmw-pricing-fleet <?php echo $current_tier === 'fleet' ? 'tmw-pricing-current' : ''; ?>">
                <div class="tmw-pricing-header">
                    <h3 class="tmw-pricing-name"><?php _e('Fleet', 'flavor-starter-flavor'); ?></h3>
                    <div class="tmw-pricing-price">
                        <span class="tmw-pricing-currency">$</span>
                        <span class="tmw-pricing-amount" data-price-monthly="29" data-price-yearly="278">29</span>
                        <span class="tmw-pricing-period">/month</span>
                    </div>
                </div>
                <ul class="tmw-pricing-features">
                    <li><i class="fas fa-check"></i> <?php _e('Unlimited Vehicles', 'flavor-starter-flavor'); ?></li>
                    <li><i class="fas fa-check"></i> <?php _e('Unlimited Everything', 'flavor-starter-flavor'); ?></li>
                    <li><i class="fas fa-check"></i> <?php printf(__('%d Attachments/Entry', 'flavor-starter-flavor'), $fleet_limits['attachments_per_entry']); ?></li>
                    <li><i class="fas fa-check"></i> <?php _e('Bulk Export', 'flavor-starter-flavor'); ?></li>
                    <li><i class="fas fa-check"></i> <?php _e('API Access', 'flavor-starter-flavor'); ?></li>
                    <li>
                        <i class="fas fa-check"></i> <?php _e('Team Members', 'flavor-starter-flavor'); ?>
                        <span class="tmw-feature-badge"><?php _e('Coming Soon', 'flavor-starter-flavor'); ?></span>
                    </li>
                </ul>
                <div class="tmw-pricing-footer">
                    <?php if ($current_tier === 'fleet') : ?>
                        <span class="tmw-btn tmw-btn-ghost tmw-btn-full" disabled><?php _e('Current Plan', 'flavor-starter-flavor'); ?></span>
                    <?php else : ?>
                        <a href="<?php echo esc_url(tmw_get_page_url('pricing')); ?>" class="tmw-btn tmw-btn-secondary tmw-btn-full">
                            <?php _e('Go Fleet', 'flavor-starter-flavor'); ?>
                        </a>
                    <?php endif; ?>
                </div>
            </div>

        </div>
    </div>
</section>

<!-- CTA Section -->
<section class="tmw-cta tmw-section">
    <div class="tmw-container">
        <div class="tmw-cta-content">
            <h2 class="tmw-cta-title"><?php _e('Ready to Get Started?', 'flavor-starter-flavor'); ?></h2>
            <p class="tmw-cta-text">
                <?php _e('Join thousands of vehicle owners who trust TrackMyWrench to keep their maintenance records organized.', 'flavor-starter-flavor'); ?>
            </p>
            <?php if (is_user_logged_in()) : ?>
                <a href="<?php echo esc_url(tmw_get_app_url()); ?>" class="tmw-btn tmw-btn-primary tmw-btn-large">
                    <?php _e('Open App', 'flavor-starter-flavor'); ?>
                </a>
            <?php else : ?>
                <a href="<?php echo esc_url(tmw_get_page_url('register')); ?>" class="tmw-btn tmw-btn-primary tmw-btn-large">
                    <?php _e('Create Free Account', 'flavor-starter-flavor'); ?>
                </a>
            <?php endif; ?>
        </div>
    </div>
</section>

<?php
get_footer();
