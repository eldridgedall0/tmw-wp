/**
 * WordPress Admin JavaScript
 *
 * @package flavor-starter-flavor
 */

(function($) {
    'use strict';

    $(document).ready(function() {
        initTabs();
        initToggles();
        initConfirmActions();
    });

    /**
     * Tab navigation (if not using WP native)
     */
    function initTabs() {
        $('.tmw-admin-tabs a').on('click', function(e) {
            e.preventDefault();
            
            var target = $(this).attr('href');
            
            // Update active tab
            $('.tmw-admin-tabs a').removeClass('nav-tab-active');
            $(this).addClass('nav-tab-active');
            
            // Show target content
            $('.tmw-tab-content').hide();
            $(target).show();
            
            // Update URL
            if (history.pushState) {
                history.pushState(null, null, $(this).attr('href'));
            }
        });
    }

    /**
     * Toggle switches
     */
    function initToggles() {
        $('.tmw-toggle input').on('change', function() {
            var isChecked = $(this).is(':checked');
            $(this).closest('.tmw-toggle').toggleClass('is-active', isChecked);
        });
    }

    /**
     * Confirm dangerous actions
     */
    function initConfirmActions() {
        $('[data-confirm]').on('click', function(e) {
            var message = $(this).data('confirm') || 'Are you sure?';
            if (!confirm(message)) {
                e.preventDefault();
                return false;
            }
        });
    }

    /**
     * Copy to clipboard helper
     */
    window.tmwCopyToClipboard = function(text, button) {
        navigator.clipboard.writeText(text).then(function() {
            var originalText = $(button).text();
            $(button).text('Copied!');
            setTimeout(function() {
                $(button).text(originalText);
            }, 2000);
        }).catch(function(err) {
            console.error('Copy failed:', err);
            alert('Failed to copy. Please copy manually.');
        });
    };

})(jQuery);
