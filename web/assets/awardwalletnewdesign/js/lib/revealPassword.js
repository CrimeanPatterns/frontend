define(['jquery-boot', 'lib/reauthenticator', 'lib/dialog', 'jqueryui', 'routing', 'translator-boot'], function ($, reauth, dialog) {
    return function (id) {
        $(function () {
            const field = $(id);
            const container = field.parents('.row');
            const link = container.find('a.reveal-pass');
            const accountId = container.parents('.main-form').data('id');

            function open() {
                reauth.reauthenticate(
                    reauth.getRevealAccountPasswordAction(accountId),
                    function() {
                        $.ajax({
                            url: Routing.generate('aw_get_pass', {accountId}),
                            method: 'post'
                        })
                            .always(data => {
                                if (data.success) {
                                    if (data.password === '') {
                                        const noPasswordDlg = dialog.fastCreate(
                                            Translator.trans('alerts.error'),
                                            Translator.trans(/** @Desc("Password is empty") */ 'password-is-empty'),
                                            true,
                                            true,
                                            [
                                                {
                                                    text: Translator.trans('alerts.btn.ok'),
                                                    'class': 'btn-blue',
                                                    click: function () {
                                                        noPasswordDlg.close();
                                                    }
                                                }
                                            ],
                                            500,
                                            null,
                                            'warning'
                                        );
                                    } else {
                                        field.attr('type', 'text').val(data.password);
                                        link.text(Translator.trans('aw.reveal-password.link.hide')).addClass('is-hide');
                                    }
                                }
                            });
                    }
                );
            }

            link.on('click', e => {
                e.preventDefault();
                if (link.hasClass('is-hide')) {
                    link.removeClass('is-hide').text(Translator.trans('aw.reveal-password.link.reveal'));
                    field.attr('type', 'password');
                } else {
                    open();
                }
            });
        });
    }
});