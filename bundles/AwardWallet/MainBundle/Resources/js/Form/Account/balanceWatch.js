/**
 * @param {AbstractFormExtension} extension
 */
function addFormExtension(extension) {
    var fieldBalanceWatchToggle = function(form, state) {
        var i, balanceWatchFields = ['PointsSource', 'TransferRequestDate'];
        if (state) {
            for (i in balanceWatchFields) {
                form.showField(balanceWatchFields[i], true);
                form.requireField(balanceWatchFields[i], true);
            }
            return;
        }

        form.setValue('BalanceWatch', false, 'checked');
        balanceWatchFields = balanceWatchFields.concat(['TransferFromProvider', 'ExpectedPoints']);
        for (i in balanceWatchFields) {
            form.showField(balanceWatchFields[i], false);
            form.requireField(balanceWatchFields[i], false);
        }
    };

    extension.onFieldChange = function(form, fieldName) {
        if (null === form.getInput('BalanceWatch'))
            return;

        switch (fieldName) {
            case 'disabled':
                if (form.getValue(fieldName)) {
                    fieldBalanceWatchToggle(form, false);
                }

                break;
            case 'savepassword':
                if ('2' === form.getValue(fieldName) && 'true' !== form.getValue('IsBalanceWatchDisabled')) {
                    fieldBalanceWatchToggle(form, false);
                }

                break;
            case 'BalanceWatch':
                if (false === form.getValue(fieldName))
                    return fieldBalanceWatchToggle(form, false);

                var dialogButtonsClose = function(buttons) {
                    buttons || (buttons = []);
                    buttons.unshift({
                        'text'    : form.getTranslator().trans('button.close'),
                        'style'   : 'negative'
                    });
                    return buttons;
                };

                if ('false' === form.getValue('IsBalanceWatchAccountCanCheck')) {
                    fieldBalanceWatchToggle(form, false);
                    return form.showDialog({
                        title   : form.getTranslator().trans('account.balancewatch.no-available'),
                        message : form.getTranslator().trans('account.balancewatch.not-available-not-cancheck'),
                        buttons : dialogButtonsClose()
                    });
                }
                if ('false' === form.getValue('IsBalanceWatchAwPlus')) {
                    fieldBalanceWatchToggle(form, false);
                    return form.showDialog({
                        title   : form.getTranslator().trans('please-upgrade'),
                        message : form.getTranslator().trans('account.balancewatch.awplus-upgrade'),
                        buttons : dialogButtonsClose([{
                            'text'    : form.getTranslator().trans(/** @Desc("Upgrade") */'button.upgrade'),
                            'onPress' : function() {
                                form.navigate(form.getValue('URL_PayAwPlus'));
                            },
                            'style'   : 'positive'
                        }])
                    });
                }
                if ('0' === form.getValue('IsBalanceWatchCredits')) {
                    fieldBalanceWatchToggle(form, false);
                    if ('true' === form.getValue('IsBusiness')) {
                        return form.showDialog({
                            title   : form.getTranslator().trans('account.balancewatch.credits-no-available-label'),
                            message : form.getTranslator().trans('account.balancewatch.credits-no-available-notice-business'),
                            buttons : dialogButtonsClose([{
                                'text'    : form.getTranslator().trans('add-funds'),
                                'onPress' : function() {
                                    form.navigate(form.getValue('URL_PayCredit'));
                                },
                                'style'   : 'positive'
                            }])
                        });
                    }
                    return form.showDialog({
                        title   : form.getTranslator().trans('account.balancewatch.credits-no-available-label'),
                        message : form.getTranslator().trans('account.balancewatch.credits-no-available-notice'),
                        buttons : dialogButtonsClose([{
                            'text'    : form.getTranslator().trans('buy'),
                            'onPress' : function() {
                                form.navigate(form.getValue('URL_PayCredit'));
                            },
                            'style'   : 'positive'
                        }])
                    });
                }
                if ('2' === form.getValue('savepassword') && 'true' !== form.getValue('IsBalanceWatchLocalPasswordExclude')) {
                    fieldBalanceWatchToggle(form, false);
                    return form.showDialog({
                        title   : form.getTranslator().trans('account.balancewatch.not-available'),
                        message : form.getTranslator().trans('account.balancewatch.not-available-password-local'),
                        buttons : dialogButtonsClose()
                    });
                }
                if (true === form.getValue('disabled')) {
                    fieldBalanceWatchToggle(form, false);
                    return form.showDialog({
                        title   : form.getTranslator().trans('account.balancewatch.no-available'),
                        message : form.getTranslator().trans('account.balancewatch.not-available-account-disabled'),
                        buttons : dialogButtonsClose()
                    });
                }
                if ('1' !== form.getValue('IsBalanceWatchAccountError')) {
                    fieldBalanceWatchToggle(form, false);
                    return form.showDialog({
                        title   : form.getTranslator().trans('account.balancewatch.no-available'),
                        message : form.getTranslator().trans('account.balancewatch.not-available-account-error'),
                        buttons : dialogButtonsClose()
                    });
                }

                extension.onFieldChange(form, 'PointsSource');
                fieldBalanceWatchToggle(form, true);
                break;
            case 'TransferFromProvider':
            case 'TransferProviderCurrency':
                extension.onFieldChange(form, 'PointsSource');

                break;
            case 'PointsSource':
                let expectedNumberCaption,
                    currency = form.getValue('TransferProviderCurrency');
                if ('1' === form.getValue(fieldName)) {
                    form.showField('TransferFromProvider', true);
                    form.requireField('TransferFromProvider', true);
                    expectedNumberCaption = form.getTranslator().trans('account.balancewatch.expected-number-miles', {'currency' : form.getValue('TransferProviderCurrency')});
                    form.setFieldCaption('ExpectedPoints', expectedNumberCaption);
                    form.setFieldNotice('ExpectedPoints', form.getTranslator().trans('account.balancewatch.expected-number-miles-notice', {'expectedNumberCaption' : expectedNumberCaption}));
                    form.showField('ExpectedPoints', true);
                    form.setFieldCaption('TransferRequestDate', form.getTranslator().trans('account.balancewatch.transfer-requested'));

                } else if ('2' === form.getValue(fieldName)) {
                    form.showField('TransferFromProvider', false);
                    form.requireField('TransferFromProvider', false);
                    expectedNumberCaption = form.getTranslator().trans('account.balancewatch.points-purchase', {'currency' : currency.charAt(0).toUpperCase() + currency.slice(1)});
                    form.setFieldCaption('ExpectedPoints', expectedNumberCaption);
                    form.setFieldNotice('ExpectedPoints', form.getTranslator().trans('account.balancewatch.expected-number-miles-notice', {'expectedNumberCaption' : expectedNumberCaption}));
                    form.showField('ExpectedPoints', true);
                    form.setFieldCaption('TransferRequestDate', form.getTranslator().trans('account.balancewatch.purchase-requested'));

                } else if ('3' === form.getValue(fieldName)) {
                    expectedNumberCaption = form.getTranslator().trans('account.balancewatch.points-purchase', {'currency' : currency.charAt(0).toUpperCase() + currency.slice(1)});
                    form.showField('TransferFromProvider', false);
                    form.setFieldNotice('ExpectedPoints', form.getTranslator().trans('account.balancewatch.expected-number-miles-notice', {'expectedNumberCaption' : expectedNumberCaption}));
                    form.setFieldNotice('ExpectedPoints', '');
                    form.showField('ExpectedPoints', true);
                    form.setFieldCaption('TransferRequestDate', form.getTranslator().trans('applied'));
                }

                break;
        }
    };

    extension.onFormReady = function(form) {
        if (null === form.getInput('BalanceWatch'))
            return;

        fieldBalanceWatchToggle(form, form.getValue('BalanceWatch'));
        extension.onFieldChange(form, 'savepassword');
        extension.onFieldChange(form, 'PointsSource');
    };

}
