/**
 * TMW Core — Frontend JS
 *
 * Handles trusted device interactions on the profile page.
 *
 * The login form's trust_device checkbox is handled entirely server-side:
 * the theme's forms.js submits via fetch()/FormData, PHP reads trust_device
 * from $_POST inside the set_current_user hook, and trusts the device there.
 * No client-side interception is needed or used.
 */
(function ($) {
    'use strict';

    // Defensive: if the localised config object didn't load for any reason,
    // provide safe defaults so buttons still attempt the AJAX call.
    var cfg = window.tmwCore || {};
    cfg.ajaxurl = cfg.ajaxurl || (typeof ajaxurl !== 'undefined' ? ajaxurl : '/wp-admin/admin-ajax.php');
    cfg.nonce   = cfg.nonce   || '';
    cfg.i18n    = cfg.i18n    || {};
    cfg.i18n.confirmRevoke    = cfg.i18n.confirmRevoke    || 'Remove this trusted device?';
    cfg.i18n.confirmRevokeAll = cfg.i18n.confirmRevokeAll || 'Remove all trusted devices?';
    cfg.i18n.removing         = cfg.i18n.removing         || 'Removing…';
    cfg.i18n.trusting         = cfg.i18n.trusting         || 'Trusting device…';
    cfg.i18n.reloading        = cfg.i18n.reloading        || 'Done! Reloading…';
    cfg.i18n.error            = cfg.i18n.error            || 'Something went wrong. Please try again.';

    // =========================================================================
    // PROFILE — Revoke a single device
    // =========================================================================
    $(document).on('click', '.tmw-revoke-device-btn', function () {
        if (!confirm(cfg.i18n.confirmRevoke)) return;

        var $btn     = $(this);
        var deviceId = $btn.data('device-id');
        var origHtml = $btn.html();

        $btn.addClass('tmw-btn-loading')
            .html('<i class="fas fa-spinner fa-spin"></i> ' + cfg.i18n.removing)
            .prop('disabled', true);

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
                    if ($('.tmw-device-row').length === 0) {
                        location.reload();
                    }
                });
            } else {
                alert(res.data && res.data.message ? res.data.message : cfg.i18n.error);
                $btn.removeClass('tmw-btn-loading').html(origHtml).prop('disabled', false);
            }
        })
        .fail(function () {
            alert(cfg.i18n.error);
            $btn.removeClass('tmw-btn-loading').html(origHtml).prop('disabled', false);
        });
    });

    // =========================================================================
    // PROFILE — Revoke all devices
    // =========================================================================
    $(document).on('click', '#tmw-revoke-all-devices-btn', function () {
        if (!confirm(cfg.i18n.confirmRevokeAll)) return;

        var $btn     = $(this);
        var origHtml = $btn.html();

        $btn.addClass('tmw-btn-loading').prop('disabled', true);

        $.post(cfg.ajaxurl, {
            action: 'tmw_core_revoke_all_devices',
            nonce:  cfg.nonce
        })
        .done(function (res) {
            if (res.success) {
                location.reload();
            } else {
                alert(res.data && res.data.message ? res.data.message : cfg.i18n.error);
                $btn.removeClass('tmw-btn-loading').html(origHtml).prop('disabled', false);
            }
        })
        .fail(function () {
            alert(cfg.i18n.error);
            $btn.removeClass('tmw-btn-loading').html(origHtml).prop('disabled', false);
        });
    });

    // =========================================================================
    // PROFILE — Trust this device (button on profile page)
    // =========================================================================
    $(document).on('click', '#tmw-trust-this-device-btn', function () {
        var $btn     = $(this);
        var origHtml = $btn.html();

        $btn.addClass('tmw-btn-loading')
            .text(cfg.i18n.trusting)
            .prop('disabled', true);

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
                $btn.removeClass('tmw-btn-loading').html(origHtml).prop('disabled', false);
            }
        })
        .fail(function () {
            alert(cfg.i18n.error);
            $btn.removeClass('tmw-btn-loading').html(origHtml).prop('disabled', false);
        });
    });

})(jQuery);
