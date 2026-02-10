define([
        'lib/design', 'jquery-boot', 'lib/dialog'],
    function (design, $, dialog) {
        'use strict';

        $('#verifyEmail').on('click', function (e) {
            $.ajax({
                url: Routing.generate('aw_email_verify_send'),
                type: 'POST',
                success: function (data) {
                    dialog.fastCreate(
                        Translator.trans(/** @Desc("Verify Email") */'email.verify_popup.title'),
                        Translator.trans(/** @Desc("We have sent you a message to the email address you specified in your profile. Please follow the link in that message to verify your email.") */'email.verify_popup.content'),
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
                }
            })
        });

        $('#tripitDisconnect').on('click', function (e) {
            e.preventDefault();
            dialog.fastCreate(
                Translator.trans(/** @Desc("Disconnect TripIt Account") */ 'profile.tripit_popup.title'),
                Translator.trans(/** @Desc("Are you sure you want to disconnect your TripIt account?") */ 'profile.tripit_popup.content'),
                true,
                true,
                [
                    {
                        'text': Translator.trans('button.no'),
                        'click': function () {
                            $(this).dialog('close');
                        },
                        'class': 'btn-silver'
                    },
                    {
                        'text': Translator.trans(/** @Desc("Yes, Disconnect") */ 'button.yes-disconnect'),
                        'click': function () {
                            document.location.href = Routing.generate('aw_timeline_tripit_disconnect');
                        },
                        'class': 'btn-blue'
                    }
                ],
                500
            );
        });

        $('.js-idclear').click(function(e) {
            e.preventDefault();
            var $btn = $(this),
                $row = $(this).closest('tr');
            if ($btn.prop('disabled'))
                return;
            $btn.addClass('loader').prop('disabled', true);
            var data = {}, rtid, sid;
            if ('undefined' !== typeof (rtid = $row.data('rtid'))) data.rtid = rtid;
            if ('undefined' !== typeof (sid = $row.data('sid'))) data.sid = sid;
            $.ajax({
                type     : 'POST',
                url      : Routing.generate('aw_profile_idclear'),
                data     : data,
                success  : function(data, status) {
                    if (undefined != typeof data.error) {
                        //if (1 === $('tr', $row.parent()).length)
                          //  document.location.href = Routing.generate('aw_users_logout');
                        $row.remove();
                    }
                },
                complete : function() {
                    $btn.removeClass('loader');
                }
            });
        });
        $('.caption-row span', '.session-list').click(function() {
            $(this).next('p').toggleClass('hidden');
        });
        $('#sessionSignouts').click(function() {
            $(this).attr('disabled', 'disabled');
            $.post(Routing.generate('aw_user_sessions_signouts'), function(data) {
                if (data.success) {
                    var currentSess = $('span:contains(' + Translator.trans('session.my') + ')', '.session-list-many');
                    if (0 === currentSess.length)
                        return location.reload(true);
                    if ('undefined' !== typeof window['signOuts'])
                        window['signOuts']();
                    $('tr', '.session-list-many').not(currentSess.closest('tr')).remove();
                }
            }, 'json');
        });

        var awplusUpgradePopup = function() {
            var dialog  = require('lib/dialog'),
                content = '<p>' + Translator.trans('account.balancewatch.awplus-upgrade') + '</p><p><br>' + Translator.trans('award.account.popup.need-upgrade.p2') + '</p>';
            var dlg = dialog.fastCreate(Translator.trans('please-upgrade'), content, true, true, [
                {
                    id      : 'btnUpgrade',
                    text    : Translator.trans('button.ok'),
                    'class' : 'btn-blue',
                    click   : function() {
                        document.location.replace(Routing.generate('aw_users_pay'));
                    }
                },
                {
                    id      : 'btnClose',
                    text    : Translator.trans('button.cancel'),
                    'class' : 'btn-silver',
                    click   : function() {
                        dlg.destroy();
                    }
                }
            ], 450);

            return false;
        };

        $('#buyBalanceWatchCredit').click(function(e) {
            if (1 == $('#accountInfo').data('level')) {
                return awplusUpgradePopup();
            }

            return true;
        });

        if (location.hash.length && 'balancewatch-credit-upgrade' == location.hash.replace('#', '')) {
            awplusUpgradePopup();
        }

        $('#accountInfo').on('click', 'a[data-oauth-unlink]', function(e) {
            e.preventDefault();

            const button = $(this);
            const icon = button.find('i[class]');

            if (icon.hasClass('loader')) {
                return;
            }

            const id = button.data('oauth-unlink');

            icon.addClass('loader');
            $.get(Routing.generate('aw_usermailbox_oauth_unlink', {id}))
                .done(({success, error}) => {
                    if (success) {
                        let tr = button.parents('tr.oauth-row');

                        if (tr.length > 0) {
                            const rows = $('#accountInfo tr.oauth-row:not(:animated)').not(tr);

                            if (rows.length === 0) {
                                tr = $('#accountInfo tr.oauth-caption, #accountInfo tr.oauth-row');
                            }
                            tr.fadeOut('slow', () => {
                                tr.remove();
                            });
                        } else {
                            icon.removeClass('loader');
                        }
                    } else {
                        icon.removeClass('loader');
                        if (error === 'setPass') {
                            dialog.fastCreate(
                                Translator.trans('alerts.warning'),
                                Translator.trans(/** @Desc("Before you revoke the connection, please set the password for your AwardWallet account.") */'warning.before-unlink-last-oauth'),
                                true,
                                true,
                                [
                                    {
                                        text: Translator.trans('button.cancel'),
                                        'class': 'btn-silver',
                                        click: function() {
                                            $(this).dialog('close');
                                        }
                                    },
                                    {
                                        text: Translator.trans(/** @Desc("Set password") */'button.set-aw-password'),
                                        click: function () {
                                            $(this).dialog('close');
                                            document.location.href = Routing.generate('aw_profile_change_password');
                                        },
                                        'class': 'btn-blue'
                                    }
                                ],
                                500
                            );
                        }
                    }
                })
                .fail(() => {
                    icon.removeClass('loader');
                });
        });
    });
