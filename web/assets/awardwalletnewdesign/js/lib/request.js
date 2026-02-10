define(['jquery-boot', 'lib/dialog', 'jqueryui'], function ($, dlg) {
    return function (id) {
        $(function () {
            var container = $(id).parents('.row'),
                link = $(container).find('a.reveal-pass'),
                accountId = $(container).parents('.main-form').data('id');

            dlg.createNamed('reveal-password-dialog', $(container).find('.dialog'), {
                title: 'Request password',
                autoOpen: false,
                modal: true,
                width: 400,
                buttons: [
                    {
                        text: 'OK',
                        id: 'reveal-password-ok',
                        'class': 'btn-silver',
                        click: function () {
                            dlg.get('reveal-password-dialog').close();
                        }
                    },
                    {
                        text: 'Request',
                        id: 'reveal-password-request',
                        'class': 'btn-blue',
                        click: function () {
                            var el = dlg.get('reveal-password-dialog').element;
                            var button = $('#reveal-password-request');
                            var input = el.find('input');

                            button.prop('disabled', true).addClass('loader');
                            $.post('/account/checkUserPassword.php', {
                                AccountID: accountId,
                                Password: input.val()
                            }).always(function (data) {
                                button.removeClass('loader').removeAttr('disabled');
                                el.parents('.ui-dialog').find('.ui-dialog-content').html(data.Error);
                            });
                        }
                    }
                ],
                open: function () {
                    var el = dlg.get('reveal-password-dialog').element;
                    el.find('input').val('').keyup(function (e) {
                        el.parents('.ui-dialog').find('.err').slideUp();
                        var button = el.parents('.ui-dialog').find('button#reveal-password-request');
                        if (e.keyCode === $.ui.keyCode.ENTER && !button.prop('disabled')) {
                            button.trigger('click');
                        }
                    }).trigger('keyup');
                },
                close: function () {
                    dlg.get('reveal-password-dialog').element.parents('.ui-dialog').find('.err').hide();
                }
            });

            link.on('click', function (e) {
                e.preventDefault();
                dlg.get('reveal-password-dialog').open();
            });
        });
    };
});