define(['jquery-boot', 'lib/dialog', 'jqueryui', 'routing', 'translator-boot', 'lib/dialog'], function ($, dialog) {
    /**
     * @typedef {Object} ReauthResponse
     * @property {string} action
     * @property {string} dialogTitle
     * @property {string} inputTitle
     * @property {string} inputType
     * @property {string} context
     * @property {boolean} resendAllowed
     */

    function log(msg, ...context) {
        console.log(`[reauth] ${msg}`, ...context);
    }

    function noop() {}

    /**
     * @param {string} inputTitle
     * @param {string} inputType
     * @returns {jQuery}
     */
    function getDialogElement(inputTitle, inputType) {
        return $('<div class="dialog"/>')
            .appendTo('body')
            .html(
                '<label>' + inputTitle + '</label>' +
                '<input type="' + inputType + '" name="input">' +
                '<div class="error-message-blk err m-top" style="display: none;"> ' +
                '   <i class="icon-warning-small"></i> <p></p> ' +
                '</div>'
            );
    }

    return {
        getChangeEmailAction: () => {
            return 'change-email';
        },
        getChangePasswordAction: () => {
            return 'change-pass';
        },
        getRevealAccountPasswordAction: accountId => {
            return `reveal-account-${accountId}-password`;
        },
        getDeleteAccountAction: () => {
            return 'delete-aw-account';
        },
        get2FactSetupAction: () => {
            return '2fact-setup';
        },
        get2FactCancelAction: () => {
            return '2fact-cancel';
        },
        getEnableAutoLoginAction: accountId => {
            return `enable-account-${accountId}-autologin`;
        },
        getBackupPasswordsAction: () => {
            return 'backup-passwords';
        },
        reauthenticate: (action, onSuccess, onFailure) => {
            log('start');

            /**
             * @param {ReauthResponse} data
             */
            const cb = data => {
                log('start data', data);
                if (data.action === 'authorized') {
                    (onSuccess || noop)();
                } else {
                    const elem = getDialogElement(data.inputTitle, data.inputType);
                    const findSubmitButton = () => elem.parents('.ui-dialog').find('#reauth-submit');
                    const findResendButton = () => elem.parents('.ui-dialog').find('#reauth-resend');
                    const findInput = () => elem.find('[name=input]');
                    const findError = () => elem.find('.err');
                    let resultCb = onFailure;

                    const buttons = [
                        {
                            text:  Translator.trans('form.button.submit'),
                            id: 'reauth-submit',
                            click: function() {
                                const button = findSubmitButton();
                                const input = findInput();

                                button.prop('disabled', true).addClass('loader');
                                $.post(Routing.generate('aw_reauth_verify'), {
                                    action,
                                    context: data.context,
                                    input: input.val()
                                })
                                    .done(response => {
                                        log('verify', response);

                                        if (response.success) {
                                            resultCb = onSuccess;
                                            dialog.get('reauth').close();
                                        } else if (response.error) {
                                            var err = findError();
                                            err.find('p').text(response.error);
                                            err.slideDown();
                                            input.select();
                                        }
                                    })
                                    .fail(() => (onFailure || noop)())
                                    .always(() => {
                                        button.removeClass('loader').removeAttr('disabled');
                                    });

                            },
                            'class': 'btn-blue'
                        }
                    ];

                    if (data.resendAllowed) {
                        buttons.unshift({
                            text:  Translator.trans('button.resend'),
                            id: 'reauth-resend',
                            click: function() {
                                const button = findResendButton();

                                button.prop('disabled', true).addClass('loader');
                                $.post(Routing.generate('aw_reauth_verify'), {
                                    action,
                                    context: data.context,
                                    intent: 'resend'
                                })
                                    .done(response => {
                                        log('resend', response);

                                        if (response.error) {
                                            var err = findError();
                                            err.find('p').text(response.error);
                                            err.slideDown();
                                        } else {
                                            findError().slideUp();
                                        }
                                    })
                                    .always(() => {
                                        button.removeClass('loader').removeAttr('disabled');
                                    });
                            },
                            'class': 'btn-silver'
                        });
                    }

                    dialog.createNamed(
                        'reauth',
                        elem,
                        {
                            width: 500,
                            height: 'auto',
                            autoOpen: true,
                            modal: true,
                            title: data.dialogTitle,
                            buttons,
                            open: function () {
                                findInput().val('').keyup(e => {
                                    findError().slideUp();

                                    const button = findSubmitButton();
                                    if (e.keyCode === $.ui.keyCode.ENTER && !button.prop('disabled')) {
                                        button.trigger('click');
                                    }
                                    if (e.target.value.length) {
                                        button.prop('disabled', false);
                                    } else {
                                        button.prop('disabled', true);
                                    }
                                }).trigger('keyup')
                                    .on('input blur', function(){
                                        $(this).trigger('keyup');
                                    });
                            },
                            close: function() {
                                dialog.get('reauth').destroy();
                                (resultCb || noop)();
                            }
                        }
                    );
                }
            };

            $.post(Routing.generate('aw_reauth_start'), {action}, cb).fail(() => (onFailure || noop)());
        }
    };
});
