/**
 * TMW Stripe Admin JavaScript
 *
 * Handles admin settings, tier fields show/hide, and AJAX operations.
 *
 * @package TMW_Stripe_Subscriptions
 */

(function($) {
    'use strict';

    /**
     * Initialize admin handlers
     */
    function init() {
        // Stripe settings form
        initStripeSettingsForm();

        // Tier Stripe fields show/hide
        initTierFieldsToggle();

        // Password visibility toggles
        initPasswordToggles();

        // Copy webhook URL
        initCopyWebhookUrl();

        // Test connection
        initTestConnection();
    }

    /**
     * Initialize Stripe settings form
     */
    function initStripeSettingsForm() {
        $('#tmw-stripe-settings-form').on('submit', function(e) {
            e.preventDefault();
            saveStripeSettings($(this));
        });
    }

    /**
     * Save Stripe settings via AJAX
     */
    function saveStripeSettings($form) {
        var $btn = $form.find('button[type="submit"]');
        var $status = $form.find('.tmw-save-status');
        var originalText = $btn.text();

        $btn.prop('disabled', true).text(tmwStripeAdmin.strings.saving);
        $status.removeClass('success error').text('');

        $.ajax({
            url: tmwStripeAdmin.ajaxUrl,
            method: 'POST',
            data: {
                action: 'tmw_save_stripe_settings',
                nonce: tmwStripeAdmin.nonce,
                ...$form.serializeArray().reduce((obj, item) => {
                    obj[item.name] = item.value;
                    return obj;
                }, {})
            },
            success: function(response) {
                if (response.success) {
                    $status.addClass('success').text(tmwStripeAdmin.strings.saved);
                } else {
                    $status.addClass('error').text(response.data.message || tmwStripeAdmin.strings.error);
                }
            },
            error: function() {
                $status.addClass('error').text(tmwStripeAdmin.strings.error);
            },
            complete: function() {
                $btn.prop('disabled', false).text(originalText);
                setTimeout(function() {
                    $status.text('');
                }, 3000);
            }
        });
    }

    /**
     * Initialize tier Stripe fields toggle
     * Shows/hides Stripe fields based on membership_plugin setting
     */
    function initTierFieldsToggle() {
        var $membershipPlugin = $('#membership_plugin');

        if (!$membershipPlugin.length) {
            return;
        }

        function toggleStripeFields() {
            var plugin = $membershipPlugin.val();
            var $stripeFields = $('.tmw-stripe-tier-fields, #tmw-stripe-tier-fields');
            var $swpmFields = $('.tmw-swpm-tier-field, .tmw-field-swpm-level-id');

            if (plugin === 'stripe') {
                $stripeFields.show();
                $swpmFields.hide();
            } else {
                $stripeFields.hide();
                $swpmFields.show();
            }
        }

        // Initial state
        toggleStripeFields();

        // On change
        $membershipPlugin.on('change', toggleStripeFields);

        // When tier modal opens
        $(document).on('tmw:tier-modal-open', toggleStripeFields);

        // Fallback: monitor modal visibility
        var observer = new MutationObserver(function(mutations) {
            mutations.forEach(function(mutation) {
                if (mutation.target.classList && mutation.target.classList.contains('tmw-modal')) {
                    setTimeout(toggleStripeFields, 50);
                }
            });
        });

        var modalContainer = document.querySelector('.tmw-modal-container, #tmw-tier-modal');
        if (modalContainer) {
            observer.observe(modalContainer, { attributes: true, attributeFilter: ['style', 'class'] });
        }
    }

    /**
     * Initialize password visibility toggles
     */
    function initPasswordToggles() {
        $(document).on('click', '.tmw-toggle-password', function() {
            var $input = $(this).siblings('input');
            var type = $input.attr('type') === 'password' ? 'text' : 'password';
            $input.attr('type', type);
            $(this).find('i').toggleClass('fa-eye fa-eye-slash');
        });
    }

    /**
     * Initialize copy webhook URL button
     */
    function initCopyWebhookUrl() {
        $('#copy-webhook-url').on('click', function() {
            var $btn = $(this);
            var url = $('#webhook-url').text().trim();

            navigator.clipboard.writeText(url).then(function() {
                var originalText = $btn.text();
                $btn.text('Copied!');
                setTimeout(function() {
                    $btn.text(originalText);
                }, 2000);
            }).catch(function() {
                // Fallback for older browsers
                var $temp = $('<input>');
                $('body').append($temp);
                $temp.val(url).select();
                document.execCommand('copy');
                $temp.remove();

                var originalText = $btn.text();
                $btn.text('Copied!');
                setTimeout(function() {
                    $btn.text(originalText);
                }, 2000);
            });
        });
    }

    /**
     * Initialize test connection button
     */
    function initTestConnection() {
        $('#tmw-test-connection').on('click', function() {
            var $btn = $(this);
            var $status = $('#tmw-stripe-status');
            var originalText = $btn.text();

            $btn.prop('disabled', true).text(tmwStripeAdmin.strings.testing);

            $.ajax({
                url: tmwStripeAdmin.ajaxUrl,
                method: 'POST',
                data: {
                    action: 'tmw_test_stripe_connection',
                    nonce: tmwStripeAdmin.nonce
                },
                success: function(response) {
                    if (response.success) {
                        $status
                            .removeClass('status-error')
                            .addClass('status-connected')
                            .html('● ' + tmwStripeAdmin.strings.connected + ' (' + capitalize(response.data.mode) + ' mode)');
                    } else {
                        $status
                            .removeClass('status-connected')
                            .addClass('status-error')
                            .html('● ' + response.data.message);
                    }
                },
                error: function() {
                    $status
                        .removeClass('status-connected')
                        .addClass('status-error')
                        .html('● ' + tmwStripeAdmin.strings.connectionFail);
                },
                complete: function() {
                    $btn.prop('disabled', false).text(originalText);
                }
            });
        });
    }

    /**
     * Capitalize first letter
     */
    function capitalize(str) {
        return str.charAt(0).toUpperCase() + str.slice(1);
    }

    /**
     * Extend tier save functionality to include Stripe fields
     */
    function extendTierSave() {
        // Hook into existing tier save
        $(document).on('tmw:before-tier-save', function(e, data) {
            // Add Stripe fields to data
            data.stripe_price_id_monthly = $('#tier-stripe-price-monthly').val() || '';
            data.stripe_price_id_yearly = $('#tier-stripe-price-yearly').val() || '';
            data.stripe_product_id = $('#tier-stripe-product-id').val() || '';
        });

        // Populate Stripe fields when editing
        $(document).on('tmw:tier-edit', function(e, tierData) {
            $('#tier-stripe-price-monthly').val(tierData.stripe_price_id_monthly || '');
            $('#tier-stripe-price-yearly').val(tierData.stripe_price_id_yearly || '');
            $('#tier-stripe-product-id').val(tierData.stripe_product_id || '');
        });

        // Clear Stripe fields when adding new tier
        $(document).on('tmw:tier-new', function() {
            $('#tier-stripe-price-monthly').val('');
            $('#tier-stripe-price-yearly').val('');
            $('#tier-stripe-product-id').val('');
        });
    }

    // Initialize on document ready
    $(document).ready(function() {
        init();
        extendTierSave();
    });

})(jQuery);
