/**
 * TMW Core — Frontend JS
 *
 * Handles trusted device interactions on the profile page.
 * The login form's trust_device checkbox is processed server-side via
 * the wp_login action hook — no JS interception needed because the
 * theme's forms.js uses native fetch() with FormData, which naturally
 * includes the checkbox field when it is checked.
 */
(function ($) {
    'use strict';

    var cfg = window.tmwCore || {};

    // =========================================================================
    // PROFILE — Revoke a single device
    // =========================================================================
    $(document).on('click', '.tmw-revoke-device-btn', function () {
        if (!confirm(cfg.i18n.confirmRevoke)) return;

        var $btn     = $(this).addClass('tmw-btn-loading').html('<i class="fas fa-spinner fa-spin"></i> ' + cfg.i18n.removing);
        var deviceId = $btn.data('device-id');

        $.post(cfg.ajaxurl, {
            action:    'tmw_core_revoke_device',
            nonce:     cfg.nonce,
            device_id: deviceId
        })
        .done(function (res) {
            if (res.success) {
                var $row = $btn.closest('.tmw-device-row');
                $row.fadeOut(300, function () {
                    $row.remove();
                    // If no devices remain, reload to show empty state
                    if ($('.tmw-device-row').length === 0) {
                        location.reload();
                    }
                });
            } else {
                alert(res.data && res.data.message ? res.data.message : cfg.i18n.error);
                $btn.removeClass('tmw-btn-loading').html('<i class="fas fa-trash-alt"></i> Remove');
            }
        })
        .fail(function () {
            alert(cfg.i18n.error);
            $btn.removeClass('tmw-btn-loading').html('<i class="fas fa-trash-alt"></i> Remove');
        });
    });

    // =========================================================================
    // PROFILE — Revoke all devices
    // =========================================================================
    $(document).on('click', '#tmw-revoke-all-devices-btn', function () {
        if (!confirm(cfg.i18n.confirmRevokeAll)) return;

        var $btn = $(this).addClass('tmw-btn-loading');

        $.post(cfg.ajaxurl, {
            action: 'tmw_core_revoke_all_devices',
            nonce:  cfg.nonce
        })
        .done(function (res) {
            if (res.success) {
                location.reload();
            } else {
                alert(res.data && res.data.message ? res.data.message : cfg.i18n.error);
                $btn.removeClass('tmw-btn-loading');
            }
        })
        .fail(function () {
            alert(cfg.i18n.error);
            $btn.removeClass('tmw-btn-loading');
        });
    });

    // =========================================================================
    // PROFILE — Trust this device (button on profile page)
    // =========================================================================
    $(document).on('click', '#tmw-trust-this-device-btn', function () {
        var $btn = $(this).addClass('tmw-btn-loading').text(cfg.i18n.trusting);

        $.post(cfg.ajaxurl, {
            action: 'tmw_core_trust_device',
            nonce:  cfg.nonce
        })
        .done(function (res) {
            if (res.success) {
                $btn.text(cfg.i18n.reloading);
                setTimeout(function () { location.reload(); }, 800);
            } else {
                alert(res.data && res.data.message ? res.data.message : cfg.i18n.error);
                $btn.removeClass('tmw-btn-loading').html('<i class="fas fa-plus-circle"></i> Trust This Device');
            }
        })
        .fail(function () {
            alert(cfg.i18n.error);
            $btn.removeClass('tmw-btn-loading').html('<i class="fas fa-plus-circle"></i> Trust This Device');
        });
    });

})(jQuery);
