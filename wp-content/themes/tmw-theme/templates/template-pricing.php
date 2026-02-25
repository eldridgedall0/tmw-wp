<?php
/**
 * Template Name: Pricing
 * Template Post Type: page
 *
 * @package flavor-starter-flavor
 */

$current_tier = is_user_logged_in() ? tmw_get_user_tier() : 'none';
$comparison = tmw_get_feature_comparison();

// Get tier data with pricing (uses tmw_get_tiers_with_pricing if available)
if (function_exists('tmw_get_tiers_with_pricing')) {
    $tiers = tmw_get_tiers_with_pricing();
} else {
    $tiers = tmw_get_tiers();
}
$free_tier = $tiers['free'] ?? array();
$pro_tier = $tiers['pro'] ?? array();
$master_tier = $tiers['master'] ?? array();

// Get prices from tier settings (with fallbacks)
$pro_price_monthly = $pro_tier['price_monthly'] ?? 9;
$pro_price_yearly = $pro_tier['price_yearly'] ?? 86;
$master_price_monthly = $master_tier['price_monthly'] ?? 29;
$master_price_yearly = $master_tier['price_yearly'] ?? 278;

// Get level IDs from settings for SWPM join URLs
$pro_level_id = tmw_get_level_mapping('pro_level_id', 2);
$master_level_id = tmw_get_level_mapping('master_level_id', 3);

get_header();
?>

<div class="tmw-pricing-page">
    <div class="tmw-container">

        <!-- Header -->
        <header class="tmw-section-header">
            <h1 class="tmw-section-title"><?php _e('Choose Your Plan', 'flavor-starter-flavor'); ?></h1>
            <p class="tmw-section-subtitle">
                <?php _e('Start free, upgrade when you need more. All plans include core features.', 'flavor-starter-flavor'); ?>
            </p>
        </header>

        <!-- Pricing Toggle -->
        <div class="tmw-pricing-toggle">
            <span class="tmw-pricing-toggle-label active" data-period="monthly"><?php _e('Monthly', 'flavor-starter-flavor'); ?></span>
            <button type="button" class="tmw-pricing-switch" aria-label="<?php esc_attr_e('Toggle annual pricing', 'flavor-starter-flavor'); ?>"></button>
            <span class="tmw-pricing-toggle-label" data-period="yearly"><?php _e('Yearly', 'flavor-starter-flavor'); ?></span>
            <span class="tmw-pricing-save"><?php _e('Save 20%', 'flavor-starter-flavor'); ?></span>
        </div>

        <!-- Pricing Cards -->
        <div class="tmw-pricing-grid">
            
            <?php
            $free_limits = tmw_get_tier_limits('free');
            $pro_limits = tmw_get_tier_limits('pro');
            $master_limits = tmw_get_tier_limits('master');
            ?>

            <!-- Free Tier -->
            <div class="tmw-pricing-card <?php echo $current_tier === 'free' ? 'tmw-pricing-current' : ''; ?>">
                <div class="tmw-pricing-header">
                    <h3 class="tmw-pricing-name"><?php echo esc_html($free_tier['name'] ?? __('Free', 'flavor-starter-flavor')); ?></h3>
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
                    <li class="disabled"><i class="fas fa-times"></i> <?php _e('No Attachments', 'flavor-starter-flavor'); ?></li>
                    <li class="disabled"><i class="fas fa-times"></i> <?php _e('No Recall Alerts', 'flavor-starter-flavor'); ?></li>
                    <li class="disabled"><i class="fas fa-times"></i> <?php _e('No Export', 'flavor-starter-flavor'); ?></li>
                </ul>
                <div class="tmw-pricing-footer">
                    <?php if ($current_tier === 'free') : ?>
                        <span class="tmw-btn tmw-btn-secondary tmw-btn-full" disabled><?php _e('Current Plan', 'flavor-starter-flavor'); ?></span>
                    <?php elseif (is_user_logged_in()) : ?>
                        <span class="tmw-btn tmw-btn-secondary tmw-btn-full" disabled><?php _e('Free Plan', 'flavor-starter-flavor'); ?></span>
                    <?php else : ?>
                        <a href="<?php echo esc_url(tmw_get_page_url('register')); ?>" class="tmw-btn tmw-btn-secondary tmw-btn-full">
                            <?php _e('Get Started', 'flavor-starter-flavor'); ?>
                        </a>
                    <?php endif; ?>
                </div>
            </div>

            <!-- pro Tier -->
            <div class="tmw-pricing-card tmw-pricing-popular <?php echo $current_tier === 'pro' ? 'tmw-pricing-current' : ''; ?>">
                <div class="tmw-pricing-badge"><?php _e('Most Popular', 'flavor-starter-flavor'); ?></div>
                <div class="tmw-pricing-header">
                    <h3 class="tmw-pricing-name"><?php echo esc_html($pro_tier['name'] ?? __('pro', 'flavor-starter-flavor')); ?></h3>
                    <div class="tmw-pricing-price">
                        <span class="tmw-pricing-currency">$</span>
                        <span class="tmw-pricing-amount" data-price-monthly="<?php echo esc_attr($pro_price_monthly); ?>" data-price-yearly="<?php echo esc_attr($pro_price_yearly); ?>"><?php echo esc_html($pro_price_monthly); ?></span>
                        <span class="tmw-pricing-period">/month</span>
                    </div>
                </div>
                <ul class="tmw-pricing-features">
                    <li><i class="fas fa-check"></i> <?php printf(__('%d Vehicles', 'flavor-starter-flavor'), $pro_limits['max_vehicles']); ?></li>
                    <li><i class="fas fa-check"></i> <?php _e('Unlimited Entries', 'flavor-starter-flavor'); ?></li>
                    <li><i class="fas fa-check"></i> <?php printf(__('%d Templates', 'flavor-starter-flavor'), $pro_limits['max_templates']); ?></li>
                    <li><i class="fas fa-check"></i> <?php printf(__('%d Attachments/Entry', 'flavor-starter-flavor'), $pro_limits['attachments_per_entry']); ?></li>
                    <li><i class="fas fa-check"></i> <?php _e('Recall Alerts', 'flavor-starter-flavor'); ?></li>
                    <li><i class="fas fa-check"></i> <?php _e('CSV & PDF Export', 'flavor-starter-flavor'); ?></li>
                </ul>
                <div class="tmw-pricing-footer">
                    <?php if ($current_tier === 'pro') : ?>
                        <span class="tmw-btn tmw-btn-primary tmw-btn-full" disabled><?php _e('Current Plan', 'flavor-starter-flavor'); ?></span>
                    <?php elseif ($current_tier === 'master') : ?>
                        <span class="tmw-btn tmw-btn-secondary tmw-btn-full" disabled><?php _e('Downgrade', 'flavor-starter-flavor'); ?></span>
                    <?php elseif (tmw_is_stripe_active()) : ?>
                        <button type="button" 
                                class="tmw-btn tmw-btn-primary tmw-btn-full tmw-subscribe-btn" 
                                data-tier="pro"
                                data-period="monthly">
                            <?php _e('Subscribe Now', 'flavor-starter-flavor'); ?>
                        </button>
                    <?php else : ?>
                        <a href="<?php echo esc_url(tmw_get_swpm_join_url($pro_level_id)); ?>" class="tmw-btn tmw-btn-primary tmw-btn-full">
                            <?php _e('Subscribe Now', 'flavor-starter-flavor'); ?>
                        </a>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Master Tier -->
            <div class="tmw-pricing-card <?php echo $current_tier === 'master' ? 'tmw-pricing-current' : ''; ?>">
                <div class="tmw-pricing-header">
                    <h3 class="tmw-pricing-name"><?php echo esc_html($master_tier['name'] ?? __('Master', 'flavor-starter-flavor')); ?></h3>
                    <div class="tmw-pricing-price">
                        <span class="tmw-pricing-currency">$</span>
                        <span class="tmw-pricing-amount" data-price-monthly="<?php echo esc_attr($master_price_monthly); ?>" data-price-yearly="<?php echo esc_attr($master_price_yearly); ?>"><?php echo esc_html($master_price_monthly); ?></span>
                        <span class="tmw-pricing-period">/month</span>
                    </div>
                </div>
                <ul class="tmw-pricing-features">
                    <li><i class="fas fa-check"></i> <?php _e('Unlimited Vehicles', 'flavor-starter-flavor'); ?></li>
                    <li><i class="fas fa-check"></i> <?php _e('Unlimited Entries', 'flavor-starter-flavor'); ?></li>
                    <li><i class="fas fa-check"></i> <?php _e('Unlimited Templates', 'flavor-starter-flavor'); ?></li>
                    <li><i class="fas fa-check"></i> <?php printf(__('%d Attachments/Entry', 'flavor-starter-flavor'), $master_limits['attachments_per_entry']); ?></li>
                    <li><i class="fas fa-check"></i> <?php _e('Bulk Export + API', 'flavor-starter-flavor'); ?></li>
                    <li>
                        <i class="fas fa-check"></i> <?php _e('Team Members', 'flavor-starter-flavor'); ?>
                        <span class="tmw-feature-badge"><?php _e('Soon', 'flavor-starter-flavor'); ?></span>
                    </li>
                </ul>
                <div class="tmw-pricing-footer">
                    <?php if ($current_tier === 'master') : ?>
                        <span class="tmw-btn tmw-btn-secondary tmw-btn-full" disabled><?php _e('Current Plan', 'flavor-starter-flavor'); ?></span>
                    <?php elseif (tmw_is_stripe_active()) : ?>
                        <button type="button" 
                                class="tmw-btn tmw-btn-secondary tmw-btn-full tmw-subscribe-btn" 
                                data-tier="master"
                                data-period="monthly">
                            <?php _e('Go Master', 'flavor-starter-flavor'); ?>
                        </button>
                    <?php else : ?>
                        <a href="<?php echo esc_url(tmw_get_swpm_join_url($master_level_id)); ?>" class="tmw-btn tmw-btn-secondary tmw-btn-full">
                            <?php _e('Go Master', 'flavor-starter-flavor'); ?>
                        </a>
                    <?php endif; ?>
                </div>
            </div>

        </div>

        <!-- Feature Comparison Table -->
        <div class="tmw-pricing-table-wrap">
            <table class="tmw-pricing-table">
                <thead>
                    <tr>
                        <th><?php _e('Feature', 'flavor-starter-flavor'); ?></th>
                        <th><?php _e('Free', 'flavor-starter-flavor'); ?></th>
                        <th class="tmw-pricing-table-popular"><?php _e('pro', 'flavor-starter-flavor'); ?></th>
                        <th><?php _e('Master', 'flavor-starter-flavor'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($comparison as $feature) : ?>
                    <tr>
                        <td><?php echo esc_html($feature['name']); ?></td>
                        <td>
                            <?php if (is_bool($feature['free'])) : ?>
                                <i class="fas fa-<?php echo $feature['free'] ? 'check text-success' : 'times text-muted'; ?>"></i>
                            <?php else : ?>
                                <?php echo wp_kses_post($feature['free']); ?>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if (is_bool($feature['pro'])) : ?>
                                <i class="fas fa-<?php echo $feature['pro'] ? 'check text-success' : 'times text-muted'; ?>"></i>
                            <?php else : ?>
                                <?php echo wp_kses_post($feature['pro']); ?>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if (is_bool($feature['master'])) : ?>
                                <i class="fas fa-<?php echo $feature['master'] ? 'check text-success' : 'times text-muted'; ?>"></i>
                            <?php else : ?>
                                <?php echo wp_kses_post($feature['master']); ?>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Money Back Guarantee -->
        <div class="tmw-guarantee">
            <div class="tmw-guarantee-icon">
                <i class="fas fa-shield-alt"></i>
            </div>
            <h3 class="tmw-guarantee-title"><?php _e('30-Day Money Back Guarantee', 'flavor-starter-flavor'); ?></h3>
            <p class="tmw-guarantee-text">
                <?php _e("Not happy? We'll refund your payment within the first 30 days, no questions asked.", 'flavor-starter-flavor'); ?>
            </p>
        </div>

        <!-- FAQ Section -->
        <section class="tmw-faq-section">
            <header class="tmw-section-header">
                <h2 class="tmw-section-title"><?php _e('Frequently Asked Questions', 'flavor-starter-flavor'); ?></h2>
            </header>

            <div class="tmw-faq-list">
                
                <div class="tmw-faq-item">
                    <button class="tmw-faq-question">
                        <?php _e('Can I change plans later?', 'flavor-starter-flavor'); ?>
                        <i class="fas fa-chevron-down"></i>
                    </button>
                    <div class="tmw-faq-answer">
                        <?php _e('Yes! You can upgrade or downgrade your plan at any time. When upgrading, you\'ll be prorated for the remainder of your billing cycle. When downgrading, your new plan will take effect at the start of your next billing cycle.', 'flavor-starter-flavor'); ?>
                    </div>
                </div>

                <div class="tmw-faq-item">
                    <button class="tmw-faq-question">
                        <?php _e('What payment methods do you accept?', 'flavor-starter-flavor'); ?>
                        <i class="fas fa-chevron-down"></i>
                    </button>
                    <div class="tmw-faq-answer">
                        <?php _e('We accept all major credit cards (Visa, Mastercard, American Express, Discover) through our secure payment processor, Stripe.', 'flavor-starter-flavor'); ?>
                    </div>
                </div>

                <div class="tmw-faq-item">
                    <button class="tmw-faq-question">
                        <?php _e('Is my data secure?', 'flavor-starter-flavor'); ?>
                        <i class="fas fa-chevron-down"></i>
                    </button>
                    <div class="tmw-faq-answer">
                        <?php _e('Absolutely. We use industry-standard encryption to protect your data both in transit and at rest. Your maintenance records are stored securely and are only accessible by you.', 'flavor-starter-flavor'); ?>
                    </div>
                </div>

                <div class="tmw-faq-item">
                    <button class="tmw-faq-question">
                        <?php _e('Can I cancel anytime?', 'flavor-starter-flavor'); ?>
                        <i class="fas fa-chevron-down"></i>
                    </button>
                    <div class="tmw-faq-answer">
                        <?php _e('Yes, you can cancel your subscription at any time. Your access will continue until the end of your current billing period. Your data will be retained and accessible if you decide to resubscribe.', 'flavor-starter-flavor'); ?>
                    </div>
                </div>

            </div>
        </section>

    </div>
</div>

<?php if (tmw_is_stripe_active()) : ?>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Update Stripe subscribe buttons when pricing toggle changes
    var toggle = document.querySelector('.tmw-pricing-switch');
    if (toggle) {
        toggle.addEventListener('click', function() {
            // Wait for toggle animation/state change
            setTimeout(function() {
                var isYearly = toggle.classList.contains('active') || toggle.getAttribute('aria-pressed') === 'true';
                var period = isYearly ? 'yearly' : 'monthly';
                document.querySelectorAll('.tmw-subscribe-btn').forEach(function(btn) {
                    btn.dataset.period = period;
                });
            }, 50);
        });
    }
    
    // Also handle the toggle labels
    document.querySelectorAll('.tmw-pricing-toggle-label').forEach(function(label) {
        label.addEventListener('click', function() {
            var period = this.dataset.period || 'monthly';
            document.querySelectorAll('.tmw-subscribe-btn').forEach(function(btn) {
                btn.dataset.period = period;
            });
        });
    });
});
</script>
<?php endif; ?>

<?php
get_footer();