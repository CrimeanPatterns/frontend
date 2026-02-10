require(['jquery-boot', 'lib/customizer', 'lib/reauthenticator', 'centrifuge', 'lib/utils'], function($, customizer, reauth, Centrifuge, utils) {
    let $accountForm = $('#account-form');
    var $accountDisableAutologin = $('#account_disableclientpasswordaccess');

    if ($accountForm.data('id') !== '')
        $('#account_pass_password').change(function() {
            if ($(this).val() !== '') {
                var d = new Date();
                d.setTime(d.getTime() + (60 * 60 * 1000));
                document.cookie = 'pass' + $accountForm.data('id') + '=d;expires=' + d.toUTCString() + ';path=/';
            }
        });

    $('#account_owner').change(function() {
        $(window).trigger('person.activate', $(this).val());
        let agentId = null;
        if (-1 === $(this).val().indexOf('_')) {
            let $person = $('.js-persons-menu').find('a[data-agentid="' + $(this).val() + '"]');
            1 === $person.length ? agentId = $person.data('id') : null;
        } else {
            agentId = $(this).val().split('_')[1] || false;
        }
        if (agentId) {
            let action = $accountForm.attr('action');
            if (-1 === action.indexOf('?')) {
                action += '?agentId=' + agentId;
            } else if (-1 !== action.indexOf('?agentId=')) {
                action = action.replace(/\?agentId\=\d+/g, '?agentId=' + agentId);
            } else if (-1 === action.indexOf('&agentId=')) {
                action += '&agentId=' + agentId;
            } else if (-1 !== action.indexOf('&agentId=')) {
                action = action.replace(/\&agentId\=\d+/g, '&agentId=' + agentId);
            }
            $accountForm.attr('action', action);
        }
    });

    $accountDisableAutologin.change(function(e) {
        e.preventDefault();
        if (false === $(this).prop('checked')) {
            const accountId = $accountForm.data('id');

            reauth.reauthenticate(
                reauth.getEnableAutoLoginAction(accountId),
                () => {
                    $.post(Routing.generate('aw_account_autologin_enable', {accountId}), function(data) {
                        if (data.success) {
                            $accountDisableAutologin
                                .prop('checked', false)
                                .closest('.row').find('.info-description p').show();
                        }
                    });
                },
                () => {
                    $accountDisableAutologin.prop('checked', true);
                }
            );
        } else {
            $accountDisableAutologin.closest('.row').find('.info-description p').hide();
        }
    });

    const $currencyId = $('#account_currencyId');
    if ($currencyId.length) {
        const $balance = $('#account_balance');
        $accountForm.submit(function(e) {
            const balance = utils.reverseFormatNumber($balance.val().replace(/ /g, ''), customizer.locale);
            if ('' != $currencyId.val() && null !== balance) {
                $balance.val(balance);
            }
        });
    }

    if ($('#emailParse').length) {
        emailContentParse(customizer, Centrifuge);
    }
    balanceWatchEvents(customizer);
});

function reverseFormatNumber(val, locale) {
    val = val.replace(/\s/g, '');
    var group = new Intl.NumberFormat(locale).format(1111).replace(/1/g, '');
    if ('' == group) group = ',';
    var decimal = new Intl.NumberFormat(locale).format(1.1).replace(/1/g, '');
    if ('' == decimal) decimal = '.';
    var num = val.replace(new RegExp('\\' + group, 'g'), '');
    num = num.replace(new RegExp('\\' + decimal, 'g'), '.');
    return !isNaN(parseFloat(num)) && isFinite(num) ? num : null;
}

function balanceWatchEvents(customizer) {
    var $balanceWatch = $('#account_BalanceWatch');
    if (!$balanceWatch.length)
        return;

    var $accountForm = $('#account-form');
    $balanceWatch.change(function() {
        $(this).prop('checked') ? $('.row-form-bw', $accountForm).removeClass('hidden') : $('.row-form-bw', $accountForm).addClass('hidden');
    }).trigger('change');

    const $rowProviderRegion = $('.row-SourceProgramRegion');
    $('#account_TransferFromProvider')
        .change(function() {
            if ('undefined' !== typeof $(this).data('field_currency') && '' !== $(this).data('field_currency')) {
                $('#account_TransferProviderCurrency').val($(this).data('field_currency'));
            }
        })
        .on('keyup', function(e) {
            if ('' === $(this).val()) {
                $rowProviderRegion.addClass('hidden');
            }
        })
        .on('autocompleteselect', function(event, ui) {
            const region = ui.item.field_regions_value || null;
            const regions = ui.item.field_regions_options || null;
            if (null !== region && null === regions) {
                $('#account_SourceProgramRegion').removeAttr('required').html(`<option selected value="${region}">${region}</option>`);
                return;
            }
            if (null === regions) {
                $('#account_SourceProgramRegion').removeAttr('required').html('<option selected value=""></option>');
                return;
            }

            let options = '', emptyOption = '';
            for (let i in regions) {
                if ('' == i) {
                    emptyOption = `<option value="">${regions[i]}</option>`;
                } else {
                    options += `<option value="${regions[i]}">${regions[i]}</option>`;
                }
            }

            $rowProviderRegion.find('select').attr('required', 'required').html(emptyOption + options);
            $rowProviderRegion.removeClass('hidden');
        });
    if ($rowProviderRegion.hasClass('error')) {
        $rowProviderRegion.removeClass('hidden');
    }

    var $expectedPoints = $('#account_ExpectedPoints');
    if ('number' !== $expectedPoints.attr('type')) {
        $accountForm.submit(function() {
            if (!$balanceWatch.prop('checked'))
                return;
            var expectValue = reverseFormatNumber($expectedPoints.val(), customizer.locales());
            if ((null === expectValue && $expectedPoints.val().length)
                || (null !== expectValue && (0 > expectValue || expectValue > 1000000000))) {
                $('.row-ExpectedPoints', $accountForm).addClass('error');
                if (!$('.error-message', '.row-ExpectedPoints').length)
                    $('.row-ExpectedPoints').append('<div class="error-message" data-type="valueMissing" style="display: table-row;"><div class="warning"><i class="icon-warning-small"></i></div><div class="error-message-description"></div></div>');
                $('.error-message', '.row-ExpectedPoints').show().find('.error-message-description').text(Translator.trans('pattern', {}, 'validators'));
                return false;
            }
            if (null !== expectValue)
                $expectedPoints.val(expectValue);
        });
        $expectedPoints.change(function() {
            var expectValue = reverseFormatNumber($(this).val(), customizer.locales());
            if (null !== expectValue) $(this).val(Intl.NumberFormat(customizer.locales()).format(expectValue));
        });
        $expectedPoints.trigger('change');
    }
}

function emailContentParse(customizer, Centrifuge) {
    const $emailParse = $('#emailParse');

    const $accountForm = $('#account-form'),
        $emailWrap = $('#emailWrap'),
        $emailFiles = $('.email-files', $emailParse),
        $emailFrame = $('iframe', $emailParse).get(0).contentWindow.document;
    let $emailContent = false, percentIndex = 0, dataFiles = [], inited = false;
    if ('complete' === $emailFrame.readyState) {
        $($emailFrame.body).html('<div class="edit-area" contentEditable="true" style="min-height:40px;" onclick="editFocus()" onblur="editBlur()"></div><style type="text/css">.edit-area {color: #fff;overflow: hidden;box-shadow: none !important;outline: none;} .edit-area > * {opacity: 0;color: #fff;} body{margin: 0;}</style>');
        var script = document.createElement('script');
        script.innerHTML = 'function editFocus() { window.parent.document.getElementById("emailBody").className="email-body email-focused"; } function editBlur() { window.parent.document.getElementById("emailBody").className="email-body"; } ';
        $emailFrame.body.appendChild(script);
        $emailContent = $($emailFrame.body);
    }

    const init = function() {
        $accountForm.prepend($emailWrap);
        if (0 === $('.js-update-overlay', $accountForm).length)
            $accountForm.prepend('<div class="overlay js-update-overlay" style="display: none;z-index: 11;"></div>');
        inited = true;
    };

    $emailContent.on('paste', function(e) {
        if (!inited) init();
        if (dataFiles.length > 0) clearFileStack();

        setTimeout(() => {
            dataFiles.push($('.edit-area', $(this)).html());
            $emailFiles.append('<div data-id="' + dataFiles.length + '"><i class="icon-html"></i><a href="#remove"><i class="icon-close-dark"></i></a></div>');
            $('.edit-area', $(this)).empty();
            $emailSubmit.text('Process').prop('disabled', false);
        }, 100);
    });

    $emailFiles.on('click', 'a[href="#remove"]', function(e) {
        delete dataFiles[$(this).parent().data('id') - 1];
        $(this).parent().remove();
        clearFileStack();
        return false;
    });

    const accountId = $accountForm.data('id'),
        $progressPercent = $('.progress-bar-row p', $emailWrap),
        $progressBar = $('.progress-bar-row span', $emailWrap);

    const clearFileStack = function() {
        $('.email-files', $emailParse).empty();
        dataFiles = [];
        $emailSubmit.text('Process').prop('disabled', true);
    };
    const resetProcess = function(withoutBtn) {
        clearTimeout(processUpdate);
        $('.update, .email-error, .email-success', $emailWrap).hide();
        $progressPercent.text('0%');
        $progressBar.css({'transition' : 'none', 'width' : '0%'});
        $progressBar.css('transition', 'width .3s linear');
        if (true !== withoutBtn)
            $emailSubmit.text('Process').prop('disabled', false);
        $('.email-done button', $emailWrap).prop('disabled', false);

        return (percentIndex = 0);
    };

    let processUpdate;
    const loadingProcess = function() {
        if (++percentIndex > 100) {
            resetProcess();
            $('.email-error', $emailWrap).show().find('.error-message-blk p').text(Translator.trans('alerts.title.error.timeout'));
            return;
        }

        $progressPercent.text(percentIndex + '%');
        $progressBar.css('width', percentIndex + '%');
        processUpdate = setTimeout(loadingProcess, 250);
    };

    const $emailSubmit = $('.email-submit .btn-blue', $emailParse);
    $emailSubmit.click(function() {
        if (0 === dataFiles.length)
            return false;

        $('html, body').stop().animate({scrollTop : 0}, 500);
        resetProcess(true);
        $emailSubmit.prop('disabled', true).text(Translator.trans('please-wait'));
        $('.update', $emailWrap).show();
        $('.js-update-overlay', $accountForm).show();
        $emailWrap.show();
        loadingProcess();

        let blob = new Blob([dataFiles[0]], {type : 'plain/text'});
        let formData = new FormData();
        formData.append('content', blob, 'page.html');

        $.ajax({
            url         : Routing.generate('aw_account_email_parse', {accountId : accountId}),
            method      : 'POST',
            data        : formData,
            dataType    : 'json',
            processData : false,
            contentType : false
        }).done(function(response) {
            clearFileStack();
            if (true !== response.success) {
                resetProcess();
                $('.email-error', $emailWrap).show().find('.error-message-blk p').text(Translator.trans('updater2.messages.fail.updater'));
                return
            }
            processCallback('AccountEmailParse_' + accountId + '_' + response.requestId, response.centrifugeOptions);
        });
    });
    $('.email-done button', $emailWrap).click(function() {
        if ($('.email-error', $emailWrap).is(':visible')) {
            $('.js-update-overlay', $accountForm).hide();
            $emailWrap.hide();
            $('html, body').stop().animate({scrollTop : $emailParse.offset().top - 125}, 500);
            return resetProcess(true);
        }
        return location.replace(Routing.generate('aw_account_list') + '/?account=' + accountId);
    });

    function processCallback(channelKey, options) {
        const client = new Centrifuge(options);
        client.on('connect', function() {
            const eventCallback = function(response) {
                if (null === response.data.status)
                    return null;

                resetProcess();
                if ('success' === response.data.status) {
                    let account = response.data.account;
                    $('.email-success .balance', $emailWrap).text(Intl.NumberFormat(customizer.locales()).format(account.balance));
                    if ('changed' === account.type) {
                        $('.email-success h5', $emailWrap).text(Translator.trans('award.account.form.success.text'));
                        $('.email-account-lastbalance', $emailWrap).text(Intl.NumberFormat(customizer.locales()).format(account.lastBalance));
                        if (true === account.increased) {
                            $('#email-account-icon-changed').attr('class', 'icon-green-up-b');
                            $('#email-account-balance-changed').attr('class', 'green').text('+' + Intl.NumberFormat(customizer.locales()).format(account.changed));
                        } else {
                            $('#email-account-icon-changed').attr('class', 'icon-blue-down-b');
                            $('#email-account-balance-changed').attr('class', 'blue').text(Intl.NumberFormat(customizer.locales()).format(account.changed));
                        }
                        $('#account_balance').val(account.balance);
                    } else {
                        $('#email-account-icon-changed').attr('class', 'icon-green-check-b');
                        $('.email-success h5', $emailWrap).text(Translator.trans('award.account.form.unchanged.text'));
                    }
                    $('.email-success', $emailWrap).show();
                    return clearFileStack();
                }

                $('.email-error', $emailWrap).show().find('.error-message-blk p').text(Translator.trans(/** @Desc("We were unable to parse out the results of the page you sent, perhaps you didn't fully select the page or you copied the wrong page.") */'unable-parse-page-you-sent'));
            };

            const subscription = client.subscribe(channelKey, eventCallback);
            subscription.history()
                .then((message) => {
                    $.each(message.data.reverse(), (index, value) => {
                        eventCallback(value);
                    });
                });
        });

        client.connect();
    }
}