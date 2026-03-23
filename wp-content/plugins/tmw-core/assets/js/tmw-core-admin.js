/**
 * TMW Core — Admin JS
 * Handles trusted device revocation from the WP Admin panel.
 */
(function ($) {
    'use strict';

    var cfg = window.tmwCoreAdmin || {};

    $(document).on('click', '.tmw-admin-revoke-device', function () {
        if (!confirm(cfg.i18n.confirmRevokeDevice)) return;

        var $btn     = $(this).prop('disabled', true).text('Revoking…');
        var deviceId = $btn.data('device-id');
        var userId   = $btn.data('user-id');
        var $row     = $btn.closest('tr');

        $.post(cfg.ajaxurl, {
            action:    'tmw_core_admin_revoke_device',
            nonce:     cfg.nonce,
            device_id: deviceId,
            user_id:   userId
        })
        .done(function (res) {
            if (res.success) {
                $row.fadeOut(300, function () { $row.remove(); });
            } else {
                alert(res.data && res.data.message ? res.data.message : cfg.i18n.error);
                $btn.prop('disabled', false).text('Revoke');
            }
        })
        .fail(function () {
            alert(cfg.i18n.error);
            $btn.prop('disabled', false).text('Revoke');
        });
    });

})(jQuery);
