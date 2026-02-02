<?php
/**
 * Stripe Settings Page
 *
 * @package TMW_Stripe_Subscriptions
 */

if (!defined('ABSPATH')) {
    exit;
}

$webhook_url = rest_url('tmw-stripe/v1/webhook');
?>

<div class="wrap">
    <h1><?php _e('Stripe Subscriptions Settings', 'tmw-stripe-subscriptions'); ?></h1>

    <div class="tmw-stripe-settings">
        <form id="tmw-stripe-settings-form">
        
        <!-- Connection Status -->
        <div class="tmw-stripe-status-card" style="background:#f9fafb;border:1px solid #e5e7eb;padding:16px;border-radius:6px;margin-bottom:24px;">
            <div style="display:flex;align-items:center;justify-content:space-between;">
                <div>
                    <strong><?php _e('Connection Status', 'tmw-stripe-subscriptions'); ?></strong><br>
                    <span id="tmw-stripe-status" style="color:<?php echo $is_configured ? '#22c55e' : '#ef4444'; ?>;">
                        <?php if ($is_configured) : ?>
                            ● <?php _e('Connected', 'tmw-stripe-subscriptions'); ?> (<?php echo esc_html(ucfirst($mode)); ?> mode)
                        <?php else : ?>
                            ● <?php _e('Not configured', 'tmw-stripe-subscriptions'); ?>
                        <?php endif; ?>
                    </span>
                </div>
                <button type="button" id="tmw-test-connection" class="button">
                    <?php _e('Test Connection', 'tmw-stripe-subscriptions'); ?>
                </button>
            </div>
        </div>

        <!-- API Mode -->
        <h3><?php _e('API Mode', 'tmw-stripe-subscriptions'); ?></h3>
        <table class="form-table">
            <tr>
                <th><label><?php _e('Mode', 'tmw-stripe-subscriptions'); ?></label></th>
                <td>
                    <label style="margin-right:20px;">
                        <input type="radio" name="mode" value="test" <?php checked($mode, 'test'); ?>>
                        <?php _e('Test', 'tmw-stripe-subscriptions'); ?>
                    </label>
                    <label>
                        <input type="radio" name="mode" value="live" <?php checked($mode, 'live'); ?>>
                        <?php _e('Live', 'tmw-stripe-subscriptions'); ?>
                    </label>
                    <p class="description"><?php _e('Use Test mode for development, Live mode for production.', 'tmw-stripe-subscriptions'); ?></p>
                </td>
            </tr>
        </table>

        <!-- Test API Keys -->
        <h3><?php _e('Test API Keys', 'tmw-stripe-subscriptions'); ?></h3>
        <table class="form-table">
            <tr>
                <th><label for="test_publishable_key"><?php _e('Publishable Key', 'tmw-stripe-subscriptions'); ?></label></th>
                <td>
                    <input type="text" id="test_publishable_key" name="test_publishable_key" 
                           value="<?php echo esc_attr($settings['test_publishable_key'] ?? ''); ?>" 
                           class="regular-text" placeholder="pk_test_...">
                </td>
            </tr>
            <tr>
                <th><label for="test_secret_key"><?php _e('Secret Key', 'tmw-stripe-subscriptions'); ?></label></th>
                <td>
                    <input type="password" id="test_secret_key" name="test_secret_key" 
                           value="<?php echo esc_attr($settings['test_secret_key'] ?? ''); ?>" 
                           class="regular-text" placeholder="sk_test_...">
                    <button type="button" class="button button-small tmw-toggle-password">👁</button>
                </td>
            </tr>
            <tr>
                <th><label for="test_webhook_secret"><?php _e('Webhook Secret', 'tmw-stripe-subscriptions'); ?></label></th>
                <td>
                    <input type="password" id="test_webhook_secret" name="test_webhook_secret" 
                           value="<?php echo esc_attr($settings['test_webhook_secret'] ?? ''); ?>" 
                           class="regular-text" placeholder="whsec_...">
                    <button type="button" class="button button-small tmw-toggle-password">👁</button>
                </td>
            </tr>
        </table>

        <!-- Live API Keys -->
        <h3><?php _e('Live API Keys', 'tmw-stripe-subscriptions'); ?></h3>
        <table class="form-table">
            <tr>
                <th><label for="live_publishable_key"><?php _e('Publishable Key', 'tmw-stripe-subscriptions'); ?></label></th>
                <td>
                    <input type="text" id="live_publishable_key" name="live_publishable_key" 
                           value="<?php echo esc_attr($settings['live_publishable_key'] ?? ''); ?>" 
                           class="regular-text" placeholder="pk_live_...">
                </td>
            </tr>
            <tr>
                <th><label for="live_secret_key"><?php _e('Secret Key', 'tmw-stripe-subscriptions'); ?></label></th>
                <td>
                    <input type="password" id="live_secret_key" name="live_secret_key" 
                           value="<?php echo esc_attr($settings['live_secret_key'] ?? ''); ?>" 
                           class="regular-text" placeholder="sk_live_...">
                    <button type="button" class="button button-small tmw-toggle-password">👁</button>
                </td>
            </tr>
            <tr>
                <th><label for="live_webhook_secret"><?php _e('Webhook Secret', 'tmw-stripe-subscriptions'); ?></label></th>
                <td>
                    <input type="password" id="live_webhook_secret" name="live_webhook_secret" 
                           value="<?php echo esc_attr($settings['live_webhook_secret'] ?? ''); ?>" 
                           class="regular-text" placeholder="whsec_...">
                    <button type="button" class="button button-small tmw-toggle-password">👁</button>
                </td>
            </tr>
        </table>

        <!-- Webhook URL -->
        <h3><?php _e('Webhook URL', 'tmw-stripe-subscriptions'); ?></h3>
        <table class="form-table">
            <tr>
                <th><label><?php _e('Endpoint URL', 'tmw-stripe-subscriptions'); ?></label></th>
                <td>
                    <code id="webhook-url" style="display:block;padding:10px;background:#f3f4f6;border-radius:4px;margin-bottom:8px;">
                        <?php echo esc_html($webhook_url); ?>
                    </code>
                    <button type="button" class="button button-small" id="copy-webhook-url">
                        <?php _e('Copy URL', 'tmw-stripe-subscriptions'); ?>
                    </button>
                    <p class="description">
                        <?php _e('Add this URL as a webhook endpoint in your Stripe Dashboard.', 'tmw-stripe-subscriptions'); ?><br>
                        <?php _e('Required events: checkout.session.completed, customer.subscription.*, invoice.payment_*', 'tmw-stripe-subscriptions'); ?>
                    </p>
                </td>
            </tr>
        </table>

        <!-- Checkout Settings -->
        <h3><?php _e('Checkout Settings', 'tmw-stripe-subscriptions'); ?></h3>
        <table class="form-table">
            <tr>
                <th><label for="success_url"><?php _e('Success URL', 'tmw-stripe-subscriptions'); ?></label></th>
                <td>
                    <input type="text" id="success_url" name="success_url" 
                           value="<?php echo esc_attr($settings['success_url'] ?? '/my-profile/'); ?>" 
                           class="regular-text" placeholder="/my-profile/">
                    <p class="description"><?php _e('Redirect here after successful checkout (relative URL).', 'tmw-stripe-subscriptions'); ?></p>
                </td>
            </tr>
            <tr>
                <th><label for="cancel_url"><?php _e('Cancel URL', 'tmw-stripe-subscriptions'); ?></label></th>
                <td>
                    <input type="text" id="cancel_url" name="cancel_url" 
                           value="<?php echo esc_attr($settings['cancel_url'] ?? '/pricing/'); ?>" 
                           class="regular-text" placeholder="/pricing/">
                    <p class="description"><?php _e('Redirect here if checkout is canceled.', 'tmw-stripe-subscriptions'); ?></p>
                </td>
            </tr>
        </table>

        <!-- Trial Period -->
        <h3><?php _e('Trial Period', 'tmw-stripe-subscriptions'); ?></h3>
        <table class="form-table">
            <tr>
                <th><label for="trial_enabled"><?php _e('Enable Trial', 'tmw-stripe-subscriptions'); ?></label></th>
                <td>
                    <label>
                        <input type="checkbox" id="trial_enabled" name="trial_enabled" value="1" 
                               <?php checked(!empty($settings['trial_enabled'])); ?>>
                        <?php _e('Enable free trial for new subscribers', 'tmw-stripe-subscriptions'); ?>
                    </label>
                    <p class="description"><?php _e('Trial is only offered once per user.', 'tmw-stripe-subscriptions'); ?></p>
                </td>
            </tr>
            <tr>
                <th><label for="trial_days"><?php _e('Trial Days', 'tmw-stripe-subscriptions'); ?></label></th>
                <td>
                    <input type="number" id="trial_days" name="trial_days" 
                           value="<?php echo esc_attr($settings['trial_days'] ?? 7); ?>" 
                           class="small-text" min="1" max="90">
                    <span><?php _e('days', 'tmw-stripe-subscriptions'); ?></span>
                </td>
            </tr>
        </table>

        <!-- Subscription Behavior -->
        <h3><?php _e('Subscription Behavior', 'tmw-stripe-subscriptions'); ?></h3>
        <table class="form-table">
            <tr>
                <th><label for="proration_behavior"><?php _e('Upgrade Proration', 'tmw-stripe-subscriptions'); ?></label></th>
                <td>
                    <select id="proration_behavior" name="proration_behavior">
                        <option value="always_invoice" <?php selected($settings['proration_behavior'] ?? '', 'always_invoice'); ?>>
                            <?php _e('Charge difference immediately', 'tmw-stripe-subscriptions'); ?>
                        </option>
                        <option value="create_prorations" <?php selected($settings['proration_behavior'] ?? '', 'create_prorations'); ?>>
                            <?php _e('Add to next invoice', 'tmw-stripe-subscriptions'); ?>
                        </option>
                        <option value="none" <?php selected($settings['proration_behavior'] ?? '', 'none'); ?>>
                            <?php _e('No proration', 'tmw-stripe-subscriptions'); ?>
                        </option>
                    </select>
                </td>
            </tr>
            <tr>
                <th><label for="cancel_at_period_end"><?php _e('Cancellation', 'tmw-stripe-subscriptions'); ?></label></th>
                <td>
                    <label>
                        <input type="checkbox" id="cancel_at_period_end" name="cancel_at_period_end" value="1" 
                               <?php checked(!empty($settings['cancel_at_period_end'])); ?>>
                        <?php _e('Keep access until end of billing period', 'tmw-stripe-subscriptions'); ?>
                    </label>
                    <p class="description"><?php _e('If unchecked, access ends immediately on cancellation.', 'tmw-stripe-subscriptions'); ?></p>
                </td>
            </tr>
        </table>

        <!-- New User Settings -->
        <h3><?php _e('New User Settings', 'tmw-stripe-subscriptions'); ?></h3>
        <table class="form-table">
            <tr>
                <th><label for="default_tier"><?php _e('Default Tier', 'tmw-stripe-subscriptions'); ?></label></th>
                <td>
                    <select id="default_tier" name="default_tier">
                        <?php
                        $tiers = function_exists('tmw_get_tiers') ? tmw_get_tiers() : array('free' => array('name' => 'Free'));
                        foreach ($tiers as $slug => $tier) :
                            if (!empty($tier['is_free'])) :
                        ?>
                            <option value="<?php echo esc_attr($slug); ?>" <?php selected($settings['default_tier'] ?? 'free', $slug); ?>>
                                <?php echo esc_html($tier['name'] ?? ucfirst($slug)); ?>
                            </option>
                        <?php 
                            endif;
                        endforeach; 
                        ?>
                    </select>
                    <p class="description"><?php _e('Tier assigned to new users upon registration.', 'tmw-stripe-subscriptions'); ?></p>
                </td>
            </tr>
            <tr>
                <th><label for="auto_create_customer"><?php _e('Auto-Create Customer', 'tmw-stripe-subscriptions'); ?></label></th>
                <td>
                    <label>
                        <input type="checkbox" id="auto_create_customer" name="auto_create_customer" value="1" 
                               <?php checked(!empty($settings['auto_create_customer'])); ?>>
                        <?php _e('Create Stripe customer on user registration', 'tmw-stripe-subscriptions'); ?>
                    </label>
                    <p class="description"><?php _e('If disabled, customer is created on first checkout.', 'tmw-stripe-subscriptions'); ?></p>
                </td>
            </tr>
        </table>

        <p class="submit">
            <button type="submit" class="button button-primary" id="tmw-save-stripe-settings">
                <?php _e('Save Settings', 'tmw-stripe-subscriptions'); ?>
            </button>
            <span id="tmw-stripe-save-status" class="tmw-save-status" style="margin-left:10px;"></span>
        </p>
    </form>
</div>
</div><!-- .wrap -->

<script>
jQuery(document).ready(function($) {
    // Toggle password visibility
    $('.tmw-toggle-password').on('click', function() {
        var $input = $(this).prev('input');
        var type = $input.attr('type') === 'password' ? 'text' : 'password';
        $input.attr('type', type);
        $(this).text(type === 'password' ? '👁' : '🔒');
    });

    // Copy webhook URL
    $('#copy-webhook-url').on('click', function() {
        var url = $('#webhook-url').text().trim();
        navigator.clipboard.writeText(url).then(function() {
            $('#copy-webhook-url').text('<?php _e('Copied!', 'tmw-stripe-subscriptions'); ?>');
            setTimeout(function() {
                $('#copy-webhook-url').text('<?php _e('Copy URL', 'tmw-stripe-subscriptions'); ?>');
            }, 2000);
        });
    });

    // Test connection
    $('#tmw-test-connection').on('click', function() {
        var $btn = $(this);
        var $status = $('#tmw-stripe-status');
        
        $btn.prop('disabled', true).text('<?php _e('Testing...', 'tmw-stripe-subscriptions'); ?>');
        
        $.post(ajaxurl, {
            action: 'tmw_test_stripe_connection',
            nonce: tmwStripeAdmin.nonce
        }, function(response) {
            if (response.success) {
                $status.css('color', '#22c55e').html('● <?php _e('Connected', 'tmw-stripe-subscriptions'); ?> (' + response.data.mode.charAt(0).toUpperCase() + response.data.mode.slice(1) + ' mode)');
            } else {
                $status.css('color', '#ef4444').html('● ' + response.data.message);
            }
        }).fail(function() {
            $status.css('color', '#ef4444').html('● <?php _e('Connection failed', 'tmw-stripe-subscriptions'); ?>');
        }).always(function() {
            $btn.prop('disabled', false).text('<?php _e('Test Connection', 'tmw-stripe-subscriptions'); ?>');
        });
    });

    // Save settings
    $('#tmw-stripe-settings-form').on('submit', function(e) {
        e.preventDefault();
        
        var $btn = $('#tmw-save-stripe-settings');
        var $status = $('#tmw-stripe-save-status');
        var formData = $(this).serialize();
        
        $btn.prop('disabled', true).text('<?php _e('Saving...', 'tmw-stripe-subscriptions'); ?>');
        $status.text('');
        
        $.post(ajaxurl, {
            action: 'tmw_save_stripe_settings',
            nonce: tmwStripeAdmin.nonce,
            ...Object.fromEntries(new FormData(this))
        }, function(response) {
            if (response.success) {
                $status.css('color', '#22c55e').text('<?php _e('Saved!', 'tmw-stripe-subscriptions'); ?>');
            } else {
                $status.css('color', '#ef4444').text(response.data.message || '<?php _e('Error', 'tmw-stripe-subscriptions'); ?>');
            }
        }).fail(function() {
            $status.css('color', '#ef4444').text('<?php _e('Error saving settings', 'tmw-stripe-subscriptions'); ?>');
        }).always(function() {
            $btn.prop('disabled', false).text('<?php _e('Save Settings', 'tmw-stripe-subscriptions'); ?>');
            setTimeout(function() { $status.text(''); }, 3000);
        });
    });
});
</script>
