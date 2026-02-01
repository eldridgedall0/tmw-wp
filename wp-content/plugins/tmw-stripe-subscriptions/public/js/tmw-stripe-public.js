/**
 * TMW Stripe Public JavaScript
 *
 * Handles checkout button clicks and portal redirects.
 *
 * @package TMW_Stripe_Subscriptions
 */

(function($) {
    'use strict';

    /**
     * Initialize Stripe handlers
     */
    function init() {
        // Subscribe buttons (checkout)
        $(document).on('click', '.tmw-subscribe-btn, .tmw-stripe-checkout-btn', handleCheckout);

        // Manage subscription buttons (portal)
        $(document).on('click', '.tmw-stripe-portal-btn, .tmw-manage-subscription-btn', handlePortal);

        // Handle pricing period toggle
        $(document).on('change', '.tmw-pricing-toggle input, .tmw-billing-period', handlePeriodChange);

        // Initialize pricing toggle state
        initPricingToggle();
    }

    /**
     * Handle checkout button click
     */
    function handleCheckout(e) {
        e.preventDefault();

        var $btn = $(this);
        var tier = $btn.data('tier');
        var period = $btn.data('period') || getCurrentPeriod();

        // Check if logged in
        if (!tmwStripe.isLoggedIn) {
            // Store intended action and redirect to login
            sessionStorage.setItem('tmw_checkout_tier', tier);
            sessionStorage.setItem('tmw_checkout_period', period);
            window.location.href = tmwStripe.loginUrl + '?redirect_to=' + encodeURIComponent(window.location.href);
            return;
        }

        // Disable button and show loading
        var originalText = $btn.html();
        $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> ' + tmwStripe.strings.processing);

        // Create checkout session
        $.ajax({
            url: tmwStripe.ajaxUrl,
            method: 'POST',
            data: {
                action: 'tmw_stripe_checkout',
                nonce: tmwStripe.checkoutNonce,
                tier: tier,
                period: period
            },
            success: function(response) {
                if (response.success && response.data.url) {
                    $btn.html('<i class="fas fa-external-link-alt"></i> ' + tmwStripe.strings.redirecting);
                    window.location.href = response.data.url;
                } else {
                    showError($btn, response.data.message || tmwStripe.strings.error);
                    $btn.prop('disabled', false).html(originalText);
                }
            },
            error: function() {
                showError($btn, tmwStripe.strings.error);
                $btn.prop('disabled', false).html(originalText);
            }
        });
    }

    /**
     * Handle portal button click
     */
    function handlePortal(e) {
        e.preventDefault();

        var $btn = $(this);

        // Check if logged in
        if (!tmwStripe.isLoggedIn) {
            window.location.href = tmwStripe.loginUrl;
            return;
        }

        // Disable button and show loading
        var originalText = $btn.html();
        $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> ' + tmwStripe.strings.processing);

        // Create portal session
        $.ajax({
            url: tmwStripe.ajaxUrl,
            method: 'POST',
            data: {
                action: 'tmw_stripe_portal',
                nonce: tmwStripe.portalNonce
            },
            success: function(response) {
                if (response.success && response.data.url) {
                    $btn.html('<i class="fas fa-external-link-alt"></i> ' + tmwStripe.strings.redirecting);
                    window.location.href = response.data.url;
                } else {
                    showError($btn, response.data.message || tmwStripe.strings.error);
                    $btn.prop('disabled', false).html(originalText);
                }
            },
            error: function() {
                showError($btn, tmwStripe.strings.error);
                $btn.prop('disabled', false).html(originalText);
            }
        });
    }

    /**
     * Handle pricing period toggle
     */
    function handlePeriodChange() {
        var period = getCurrentPeriod();

        // Update all subscribe buttons
        $('.tmw-subscribe-btn, .tmw-stripe-checkout-btn').each(function() {
            $(this).data('period', period);
        });

        // Update price displays
        updatePriceDisplays(period);

        // Update period labels
        updatePeriodLabels(period);
    }

    /**
     * Get current billing period from toggle
     */
    function getCurrentPeriod() {
        var $toggle = $('.tmw-pricing-switch, .tmw-billing-toggle');

        if ($toggle.length) {
            return $toggle.is(':checked') ? 'yearly' : 'monthly';
        }

        // Check for radio buttons
        var $radio = $('input[name="billing_period"]:checked, input[name="tmw_period"]:checked');
        if ($radio.length) {
            return $radio.val();
        }

        // Check for active label
        var $activeLabel = $('.tmw-pricing-toggle-label.active');
        if ($activeLabel.length) {
            return $activeLabel.data('period') || 'monthly';
        }

        return 'monthly';
    }

    /**
     * Initialize pricing toggle state
     */
    function initPricingToggle() {
        // Check if coming back from login with stored checkout intent
        var storedTier = sessionStorage.getItem('tmw_checkout_tier');
        var storedPeriod = sessionStorage.getItem('tmw_checkout_period');

        if (storedTier && tmwStripe.isLoggedIn) {
            sessionStorage.removeItem('tmw_checkout_tier');
            sessionStorage.removeItem('tmw_checkout_period');

            // Auto-trigger checkout
            var $btn = $('.tmw-subscribe-btn[data-tier="' + storedTier + '"]').first();
            if ($btn.length) {
                if (storedPeriod) {
                    $btn.data('period', storedPeriod);
                }
                $btn.trigger('click');
            }
        }

        // Set initial period on buttons
        handlePeriodChange();
    }

    /**
     * Update price displays based on period
     */
    function updatePriceDisplays(period) {
        $('.tmw-pricing-amount, .tmw-price-amount').each(function() {
            var $el = $(this);
            var monthly = $el.data('price-monthly');
            var yearly = $el.data('price-yearly');

            if (monthly !== undefined && yearly !== undefined) {
                var price = period === 'yearly' ? yearly : monthly;
                $el.text(price);
            }
        });

        // Update period text
        $('.tmw-pricing-period, .tmw-price-period').each(function() {
            var $el = $(this);
            if (period === 'yearly') {
                $el.text('/year');
            } else {
                $el.text('/month');
            }
        });
    }

    /**
     * Update period toggle labels
     */
    function updatePeriodLabels(period) {
        $('.tmw-pricing-toggle-label').removeClass('active');
        $('.tmw-pricing-toggle-label[data-period="' + period + '"]').addClass('active');
    }

    /**
     * Show error message
     */
    function showError($btn, message) {
        // Try to find or create error container
        var $container = $btn.closest('.tmw-pricing-card, .tmw-subscription-card, .tmw-card');
        var $error = $container.find('.tmw-stripe-error');

        if (!$error.length) {
            $error = $('<div class="tmw-stripe-error tmw-alert tmw-alert-error" style="margin-top:10px;"></div>');
            $btn.after($error);
        }

        $error.html('<i class="fas fa-exclamation-circle"></i> ' + message).show();

        // Auto-hide after 5 seconds
        setTimeout(function() {
            $error.fadeOut();
        }, 5000);
    }

    /**
     * Handle checkout success/cancel URL params
     */
    function handleCheckoutResult() {
        var params = new URLSearchParams(window.location.search);

        if (params.get('checkout') === 'success') {
            // Show success message
            showSuccessMessage();

            // Clean URL
            cleanUrl();
        } else if (params.get('checkout') === 'canceled') {
            // Show canceled message
            showCanceledMessage();

            // Clean URL
            cleanUrl();
        }
    }

    /**
     * Show success message after checkout
     */
    function showSuccessMessage() {
        var $container = $('.tmw-profile-section, .tmw-subscription-card, .tmw-container').first();

        if ($container.length) {
            var $alert = $('<div class="tmw-alert tmw-alert-success tmw-alert-dismissible" style="margin-bottom:20px;">' +
                '<i class="fas fa-check-circle"></i> ' +
                '<span>Your subscription has been activated successfully!</span>' +
                '<button type="button" class="tmw-alert-close" onclick="this.parentElement.remove();">' +
                '<i class="fas fa-times"></i></button>' +
                '</div>');

            $container.prepend($alert);
        }
    }

    /**
     * Show canceled message after checkout
     */
    function showCanceledMessage() {
        var $container = $('.tmw-pricing-page, .tmw-container').first();

        if ($container.length) {
            var $alert = $('<div class="tmw-alert tmw-alert-info tmw-alert-dismissible" style="margin-bottom:20px;">' +
                '<i class="fas fa-info-circle"></i> ' +
                '<span>Checkout was canceled. You can try again when you\'re ready.</span>' +
                '<button type="button" class="tmw-alert-close" onclick="this.parentElement.remove();">' +
                '<i class="fas fa-times"></i></button>' +
                '</div>');

            $container.prepend($alert);
        }
    }

    /**
     * Clean checkout params from URL
     */
    function cleanUrl() {
        var url = new URL(window.location.href);
        url.searchParams.delete('checkout');
        url.searchParams.delete('session_id');
        window.history.replaceState({}, document.title, url.toString());
    }

    // Initialize on document ready
    $(document).ready(function() {
        init();
        handleCheckoutResult();
    });

})(jQuery);
