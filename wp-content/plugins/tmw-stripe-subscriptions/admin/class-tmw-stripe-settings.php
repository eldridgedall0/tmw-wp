<?php
/**
 * TMW Stripe Settings Integration
 *
 * Integrates with TMW Theme Settings to show/hide Stripe fields
 * based on membership plugin selection.
 *
 * @package TMW_Stripe_Subscriptions
 */

if (!defined('ABSPATH')) {
    exit;
}

class TMW_Stripe_Settings {

    /**
     * Initialize hooks
     */
    public function __construct() {
        // Add admin footer script to toggle Stripe fields visibility
        add_action('admin_footer', array($this, 'render_toggle_script'));
    }

    /**
     * Render JavaScript to toggle Stripe fields based on membership plugin setting
     */
    public function render_toggle_script() {
        // Only on TMW settings page
        $screen = get_current_screen();
        if (!$screen || strpos($screen->id, 'tmw-settings') === false) {
            return;
        }
        ?>
        <script>
        jQuery(document).ready(function($) {
            // Toggle Stripe fields visibility based on membership plugin selection
            function toggleStripeFields() {
                var plugin = $('#membership_plugin').val();
                if (plugin === 'stripe') {
                    $('.tmw-stripe-field').show();
                } else {
                    $('.tmw-stripe-field').hide();
                }
            }

            // Initial toggle
            toggleStripeFields();

            // Toggle on change
            $('#membership_plugin').on('change', toggleStripeFields);
        });
        </script>
        <?php
    }
}
