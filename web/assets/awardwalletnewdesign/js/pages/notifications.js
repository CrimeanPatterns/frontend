define(['jquery-boot', 'lib/dialog', 'lib/design'], function ($, dialog) {
    var checkboxAllEmail = $("#desktop_notification_emailDisableAll"),
        selectorEmail = $('[id^=desktop_notification_email]:not(#desktop_notification_emailDisableAll)'),
        checkboxAllWpPush = $('#desktop_notification_wpDisableAll'),
        selectorWpPush = $('[id^=desktop_notification_wp]:not(#desktop_notification_wpDisableAll)'),
        checkboxAllMpPush = $('#desktop_notification_mpDisableAll'),
        selectorMpPush = $('[id^=desktop_notification_mp]:not(#desktop_notification_mpDisableAll)'),
        elem, hidden, isAlwaysDisabled;
    var methods = {
        disable: function(selector) {
            selector.each(function(){
                elem = $(this);
                isAlwaysDisabled = elem.data('always-disabled');

                if (!elem.prev('input[type=hidden]').length) {
                    elem.before('<input type="hidden"/>');
                }

                if (elem.is(':checkbox') && !elem.is(':checked')) {
                    elem.attr("disabled", "disabled");

                    return true;
                }
                hidden = elem.prev('input[type=hidden]');
                hidden.attr("name", elem.attr("name"));

                if (elem.is(':checkbox')) {
                    hidden.val(1);
                    elem.prop("checked", false);
                } else {
                    hidden.val(elem.val());
                    elem.val(0)
                }

                elem.removeAttr("name").attr("disabled", "disabled");
            });
        },
        enable: function(selector) {
            selector.each(function(){
                elem = $(this);
                isAlwaysDisabled = elem.data('always-disabled');
                hidden = elem.prev('input[type=hidden]');

                if (!hidden.length) return true;

                if (typeof elem.attr("name") == "undefined") {
                    elem.attr("name", hidden.attr("name"));
                    hidden.removeAttr("name");
                    if (elem.is(':checkbox')) {
                        elem.prop("checked", hidden.val() == 1);
                    } else {
                        elem.val(hidden.val())
                    }
                }

                if (!isAlwaysDisabled) {
                    elem.removeAttr('disabled');
                }
            });
        },
        toggle: function(selector, elements) {
            if (selector.is(":checked")) {
                methods.disable(elements);
            } else {
                methods.enable(elements);
            }
        }
    };
    $(function () {
        if (checkboxAllEmail.length > 0) {
            methods.toggle(checkboxAllEmail, selectorEmail);
            checkboxAllEmail.click(function () {
                methods.toggle(checkboxAllEmail, selectorEmail);
            });
        }
        if (checkboxAllWpPush.length > 0) {
            methods.toggle(checkboxAllWpPush, selectorWpPush);
            checkboxAllWpPush.click(function () {
                methods.toggle(checkboxAllWpPush, selectorWpPush);
            });
        }
        if (checkboxAllMpPush.length > 0) {
            methods.toggle(checkboxAllMpPush, selectorMpPush);
            checkboxAllMpPush.click(function () {
                methods.toggle(checkboxAllMpPush, selectorMpPush);
            });
        }
        $('.ajax-loader').hide();
        $('.page-content').show();
    });

    $('.unsubscribe').on('click', function (e) {
        var code = $(e.target).closest('tr').attr('data-code'),
            deviceName = $(e.target).closest('tr').find('td.browser-name').text().trim();
        var popup = dialog.fastCreate(
            Translator.trans('alerts.text.confirm'),
            ['Safari', 'Google Chrome', 'Firefox'].indexOf(deviceName) !== -1 ? Translator.trans(/** @Desc("Are you sure you wish to delete this browser from the list of browsers that can receive push notifications from us?") */'notifications.unsubscribe.popup.browser')
                : Translator.trans(/** @Desc("Are you sure you wish to delete this device from the list of devices that can receive push notifications from us?") */'notifications.unsubscribe.popup.mobile'),
            true,
            true,
            [
                {
                    text: Translator.trans('button.cancel'),
                    click: function () {
                        $(this).dialog('close');
                    },
                    'class': 'btn-silver'
                },
                {
                    text: Translator.trans('button.yes'),
                    click: function () {
                        var dialog = $('div.ui-dialog');
                        var popup = $(this);
                        dialog.find('button').attr('disabled', 'disabled');
                        dialog.find('button.btn-blue').addClass('loader');
                        $('body').off();
                        $.ajax({
                            url: Routing.generate('aw_mobile_device_unsubscribe', {code: code }),
                            type: 'POST',
                            success: function(){
                                $('body').on();
                                popup.dialog('close');
                                const $table = $(e.target).closest('table');
                                if (1 === $('tbody tr', $table).length)
                                    $('.notification-empty--' + $table.hide().data('device')).removeAttr('hidden');
                                $(e.target).closest('tr').remove();
                            }
                        });
                    },
                    'class': 'btn-blue'
                }
            ],
            500
        );
        e.preventDefault();
    });

    $('.test-notification').on('click', function (e) {
        var code = $(e.target).closest('tr').attr('data-code'),
            deviceName = $(e.target).closest('tr').find('td.browser-name').text().trim();

        var popupText = ['Safari', 'Google Chrome', 'Firefox'].indexOf(deviceName) != -1 ?
            Translator.trans(
                /** @Desc("
                 We just sent a test push notification to %deviceName%, if you have this browser opened
                 and you did not get the push notification, please make sure you are allowing push notifications
                 from AwardWallet, if this still doesn't work you can try deleting this browser from the list and
                 adding it again by loading %linkStart%this page%linkEnd%, after that you can come back here and try sending another test.
                 ") */
                'notifications.test-desktop.popup',
                {
                    deviceName: deviceName,
                    linkStart: '<a href="' + Routing.generate('aw_home') + '">',
                    linkEnd: '</a>'
                }
            ) :
            Translator.trans(
                /** @Desc("
                 We just sent a test push notification to your %deviceName%, if you have this device
                 and you did not get the push notification, please make sure you are allowing push notifications
                 from AwardWallet in your device settings, if this still doesn't work you can try deleting this
                 device from the list and adding it again by reinstalling the app and accepting the prompt to
                 receive push notifications.
                 ") */
                'notifications.test-mobile.popup',
                {deviceName: deviceName}
            );

        $.ajax({
            url: Routing.generate('aw_device_send_push', {code: code }),
            type: 'POST'
        });

        dialog.fastCreate(
            Translator.trans( /** @Desc("Test Notification Sent") */'notifications.test.sent'),
            popupText,
            true,
            true,
            [
                {
                    text: Translator.trans('button.ok'),
                    click: function () {
                        $(this).dialog('close');
                    },
                    'class': 'btn-blue'
                }
            ],
            500
        );
        e.preventDefault();
    });

    $('.test-email').on('click', function (e) {
        var popupText = Translator.trans(
                /** @Desc("
                 We just sent a test email notification to your profile address.") */
                'notifications.test-email.popup'
            );

        $.ajax({
            url: Routing.generate('aw_send_test_email'),
            type: 'POST'
        });

        dialog.fastCreate(
            Translator.trans( /** @Desc("Test Email Sent") */'notifications.test-email.sent'),
            popupText,
            true,
            true,
            [
                {
                    text: Translator.trans('button.ok'),
                    click: function () {
                        $(this).dialog('close');
                    },
                    'class': 'btn-blue'
                }
            ],
            500
        );
        e.preventDefault();
    });

    $('.set-alias').on('click', function(e) {
        var alias    = $(e.target).attr('data-alias');
        var setEvent = function() {
            var code  = $(e.target).closest('tr').attr('data-code'),
                alias = $('input[name="deviceAlias"]').val();

            $.ajax({
                url     : Routing.generate('aw_device_set_alias', {code : code}),
                success : function() {
                    $(e.target).closest('td.alias a').text(('' == alias.replace(/ /g, '') ? 'Set Alias' : alias));
                    $(e.target).closest('td.alias a').attr('data-alias', alias);
                    $(dlg.element).dialog('close');
                },
                type    : 'POST',
                data    : {
                    alias : alias
                }
            });
        };
        var dlg      = dialog.fastCreate(
            Translator.trans(/** @Desc("Set Alias") */ 'alias.popup.header'),
            '<label for="deviceAlias">Alias:</label><input type="text" value="' + encodeURIComponent(alias) + '" name="deviceAlias" autofocus>',
            true,
            true,
            [
                {
                    text    : Translator.trans('alerts.btn.cancel'),
                    click   : function() {
                        $(this).dialog("close");
                    },
                    'class' : 'btn-silver'
                },
                {
                    text    : Translator.trans(/** @Desc("Set") */'connections.button.set'),
                    click   : setEvent,
                    'class' : 'btn-blue'
                }
            ],
            500
        );
        e.preventDefault();

        var $deviceAlias = $('input[name="deviceAlias"]');
        $deviceAlias
            .off()
            .val(alias)
            .keypress(function(k) {
                if (13 == (k.keyCode || k.which))
                    setEvent();
            });
        $deviceAlias = $deviceAlias.get(0);
        if ($deviceAlias.setSelectionRange) {
            $deviceAlias.focus();
            $deviceAlias.setSelectionRange(alias.length, alias.length);
        } else if ($deviceAlias.createTextRange) {
            var range = $deviceAlias.createTextRange();
            range.collapse(true);
            range.moveEnd('character', alias.length);
            range.moveStart('character', alias.length);
            range.select();
        }
    });
});
