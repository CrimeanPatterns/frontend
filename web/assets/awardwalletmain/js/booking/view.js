var bookingView = function () {

    var self = {
        programs: null,
        programRows: null
    };

    self.updateMessages = function () {
        var messContainer = $('#messages');
        if(messContainer.length){
            messContainer.load(messContainer.data('target'))
        }
    };

    self.addCustom = function (event) {
        event.preventDefault();
        var row = $('#add-custom-template').clone().attr('id', '');
        self.programRows.append(row);
        InputStyle.init(row);
    };

    self.removeCustom = function (event) {
        event.preventDefault();
        var row = $(this).closest('tr');
        row.remove();
    };

    self.clarifyCustom = function (event) {
        event.preventDefault();
        var row = $(this).closest('tr');
        row.find('input[type=checkbox]').removeAttr('disabled').attr('checked', 'checked');
        var text = row.find('span.name').text();
        var input = $('#clarify-template').children().clone();
        input.filter('div.text').html(text);
        row.find('td:eq(1)').empty().append(input);
        InputStyle.init(row);
    };

    self.sendSharingRequest = function (event) {
        event.preventDefault();
        self.programs.find('.select').click();
        var selected = self.programRows.find('tr:has(input:checked)');
        var accounts = [];
        selected.each(function (idx, el) {
            accounts.push({
                providerId: $(el).closest('tr').find('select').val(),
                rowId: $(el).closest('tr').data('id')
            });
        });
        if (accounts.length == 0) {
            jAlert({content: 'You must choose at least 1 account for sharing'});
            return;
        }
        $('#send-sharing-request').addClass('loader');
        $.ajax({
            url: Routing.generate('aw_booking_view_clarify', {id: requestId}), // requestId defined in template
            type: 'POST',
            data: {"accounts": accounts},
            success: function (html) {
                self.programRows.html(html);
                InputStyle.init(self.programRows);
                $('#commonMessages').find('.message-body').load(Routing.generate('aw_booking_message_getmessages', {id: requestId, internal: 'common'}));
            }
        })
            .always(function () {
                $('#send-sharing-request').removeClass('loader');
            });
    };

    $(document).ready(function () {
        self.programs = $('#lp-table');
        self.programRows = self.programs.find('tbody');
        self.updateMessages();
        InputStyle.init(self.programRows);
        $('.js-request-another-account-for-sharing').click(self.addCustom);
        self.programRows.on('click', '.delete-added-custom', null, self.removeCustom);
        self.programRows.on('click', '.clarify', null, self.clarifyCustom);
        $('#send-sharing-request').click(self.sendSharingRequest);
    });

    return self;

}();

$(function () {
    var payment_dialog = $("#payment_popup");
    var errorHandler = function (response) {
        complete();
        payment_dialog.dialog('close');
    };
    var complete = function () {
        payment_dialog.siblings('.ui-dialog-buttonpane').find('.ui-dialog-buttonset button').eq(1).removeClass('loader');
    };
    payment_dialog.dialog({
        title: Translator.trans('payment.dialog.title', {}, 'booking'),
        autoOpen: false,
        width: 450,
        height: 400,
        modal: true,
        resizable: false,
        draggable: false,
        create: function (event, ui) {
            var button = $('#payment_popup').next('div').find('button:last');
            if ($('#payment_popup [name=type]:checked').length == 0)
                button.button("option", "disabled", true);
            else
                button.button("option", "disabled", false);
        },
        buttons: [
            {
                text: 'Cancel',
                click: function () {
                    payment_dialog.dialog('close');
                },
                'class': 'btn-silver'
            },
            {
                text: 'Continue',
                'class': 'btn-blue',
                click: function () {
                    var button = payment_dialog.siblings('.ui-dialog-buttonpane').find('.ui-dialog-buttonset button').eq(1);
                    if (button.hasClass('loader'))
                        return;
                    button.addClass('loader');
                    if ($('#pay_by_check').is(':checked'))
                        $.ajax({
                            url: Routing.generate('aw_booking_payment_by_check', {id: requestId}), // requestId defined in template
                            type: 'POST',
                            success: function (response) {
                                if (response == 'OK') {
                                    payment_dialog.dialog('close');
                                    document.location.href = document.location.href.replace(/[\?#].*|$/, '?_=' + Date.now());
                                } else {
                                    errorHandler(response);
                                }
                            },
                            error: function (xhr, status, error) {
                                errorHandler('General error ' + status + '. ' + error);
                            }
                        });
                    else
                        $.ajax({
                            url: Routing.generate('aw_booking_payment_by_cc', {id: requestId}), // requestId defined in template
                            type: 'POST',
                            success: function (response) {
                                if (response.status == 'ok')
                                    document.location.href = response.url;
                                else
                                    errorHandler(response.message);
                            },
                            error: complete
                        });
                }
            }
        ]
    });
    var common = $('#commonMessages');

    common.on('click', 'a.invoice_proceed', function (e) {
        e.preventDefault();
        if (payment_dialog.data('check-disabled')) {
            $.ajax({
                url: Routing.generate('aw_booking_payment_by_cc', {id: requestId}), // requestId defined in template
                type: 'POST',
                success: function (response) {
                    if (response.status == 'ok')
                        document.location.href = response.url;
                    else
                        errorHandler(response.message);
                }
            });
        } else {
            payment_dialog.dialog('open');
        }
    });

    payment_dialog.find('input[type="radio"]').change(function () {
        var sliderCheck = payment_dialog.find('.check-text');
        var sliderCcDisabled = payment_dialog.find('.cc-disabled-text');
        var ccDisabled = payment_dialog.data('credit-card-disabled');
        if ($('#pay_by_check').is(':checked'))
            sliderCheck.slideDown(200);
        else
            sliderCheck.slideUp(200);
        if (!$('#pay_by_check').is(':checked') && ccDisabled)
            sliderCcDisabled.slideDown(200);
        else
            sliderCcDisabled.slideUp(200);
    });

    var keeper = new FormKeeper('booking_request_new', false);
    keeper.clear();

//
//    function setcookie(name, value, expires, path, domain, secure) {
//        document.cookie = name + "=" + escape(value) +
//            ((expires) ? "; expires=" + (new Date(expires)) : "") +
//            ((path) ? "; path=" + path : "") +
//            ((domain) ? "; domain=" + domain : "") +
//            ((secure) ? "; secure" : "");
//    }
    var not_verified_dialog = $('#not_verified_popup');
    if (not_verified_dialog.length) {
        not_verified_dialog.dialog({
            title: Translator.trans('not-verified.popup.title', {}, /** @Desc("Request successfully submitted") */ 'booking'),
            width: 600,
            autoOpen: true,
            modal: true,
            draggable: false,
            closeOnEscape: !!not_verified_dialog.data('closable'),
            dialogClass: !!not_verified_dialog.data('closable') ? '' : 'no-close',
            beforeClose: function( event, ui ) {
                return !!not_verified_dialog.data('closable');
            },
            buttons: [
                {
                    text: Translator.trans('booking.edit.request', {}, 'booking'),
                    "class": "btn-silver",
                    click: function () {
                        window.location.href = Routing.generate('aw_booking_add_edit', {id: not_verified_dialog.data('id')});
                    }
                },
                {
                    text: Translator.trans('not-verified.popup.resend', {}, /** @Desc("Send Another Confirmation Email") */ 'booking'),
                    style: "width: 300px;",
                    "class": "btn-blue",
                    click: function () {
                        window.location.href = Routing.generate('aw_booking_view_resend_email', {id: not_verified_dialog.data('id')});
                    }
                }
            ]
        });
        //not_verified_dialog.dialog().open();
    }

    require(['lib/reauthenticator', 'lib/dialog'], function (reauth, dialog) {
        $(document).on('click', '.js-reveal-password', function (event) {
            event.preventDefault();

            var accountId = $(this).data('accountId');
            var url = $(this).data('url');

            reauth.reauthenticate(
                reauth.getRevealAccountPasswordAction(accountId),
                function() {
                    $.post(url, function (results) {
                        if (results.success === true) {
                            if (results.password) {
                                var popup = $('#reveal_password_popup');
                                dialog.createNamed('revealPass', popup, {
                                    title: Translator.trans('reveal-password.dialog.copy.title', {}, 'booking'),
                                    width: '450',
                                    modal: true,
                                    draggable: false,
                                    autoOpen: true,
                                    show: {
                                        effect: "fade",
                                        duration: 300
                                    },
                                    hide: {
                                        effect: "fade",
                                        duration: 300
                                    },
                                    buttons: [
                                        {
                                            id: 'btn-copy-pass',
                                            text: 'Copy password',
                                            class: 'btn btn-silver',
                                            click: function() {
                                                dialog.get('revealPass').close();
                                            },
                                            'data-clipboard-text': results.password
                                        }
                                    ],
                                    open: function () {
                                        popup.find('.copy-pass-message').html(results.message);
                                        new Clipboard('#btn-copy-pass');
                                    }
                                });

                            } else {
                                jAlert({content: results.message, type: 'warning', title: Translator.trans('reveal-password.dialog.error.title', {}, 'booking')});
                            }
                        }
                    });
                }
            );
            return false;
        });
    });
    $(document).on('change', '#payment_popup [name=type]', function (event) {
        var disable = false,
            ccDisabled = $('#payment_popup').data('credit-card-disabled');
        if (ccDisabled && $('#payment_popup').find(':checked').first().is('#pay_by_cc'))
            disable = true;
        $('#payment_popup').next('div').find('button:last').button("option", "disabled", disable);
    });

    $(document).on('click', '#add-trip-btn', function(event) {
        event.preventDefault();

        const addTripPopup = $('#add-trip-popup');
        const requestId = $('#requestView').data('id');

        if (addTripPopup.length) {
            addTripPopup.dialog({
                title: Translator.trans('add-trip', {}, 'trips'),
                width: 600,
                autoOpen: true,
                modal: true,
                draggable: false,
                buttons: [
                    {
                        text: Translator.trans('button.cancel', {}, 'messages'),
                        click: function () {
                            addTripPopup.dialog('close');
                        },
                        'class': 'btn-silver'
                    },
                    {
                        id: 'add-trip-submit',
                        text: Translator.trans('button.add', {}, 'messages'),
                        "class": "btn-blue",
                        style: "width: 130px;",
                        click: function () {
                            const button = $('#add-trip-submit');

                            button.attr('disabled', 'disabled').addClass('loader');
                            $.ajax({
                                url: Routing.generate('aw_booking_timeline_addplan', {id: requestId}),
                                method: 'post',
                                data: {
                                    url: $('input#trip-url-input').val()
                                },
                                success: function ({success, error, iframe}) {
                                    button.removeAttr('disabled').removeClass('loader');

                                    if (success) {
                                        addTripPopup
                                            .find('.row').removeClass('error')
                                            .find('.error-message').hide()
                                            .find('.error-message-description').empty();

                                        $('input#trip-url-input').val('');
                                        addTripPopup.dialog('close');
                                        $('#requestView').data('travel-plan', iframe);
                                        processTimeline(true);
                                    } else if (error) {
                                        addTripPopup
                                            .find('.row').addClass('error')
                                            .find('.error-message').show()
                                            .find('.error-message-description').html(error);
                                    }
                                },
                                error: function (xhr, status, error) {
                                    button.removeAttr('disabled').removeClass('loader');
                                    errorHandler('General error ' + status + '. ' + error);
                                }
                            });
                        }
                    }
                ]
            });
        }
    });

    $(document).on('click', '.js-account-balance-error', function (event) {
        event.preventDefault();
        var $this = $(this);
        var id = $this.parents('tr').data('id');
        var error = $this.data('error');
        var is_booker = $this.parents('tr.miles-row').find('input').length;

        if (is_booker) {
            var dialog = $('<div>' +
            Translator.trans(/** @Desc("Last time we tried updating this account it got the following error") */ 'booking.share.balance-error.popup-text', {}, 'booking') + ':' +
            '<br><br>' +
            error +
            '<br><br>' +
            Translator.trans(/** @Desc("You may need to inform the user that he or she needs to update this account username or password in order for you to be able to auto-login to this account. The user would need to do that from the following URL") */ 'booking.share.balance-error.popup-booker-text', {}, 'booking') + ':' +
            '<br><br>' +
            '<a target="_blank" href="https://awardwallet.com/account/edit.php?ID=' + id + '">https://awardwallet.com/account/edit.php?ID=' + id + '</a>' +
            '</div>');
        } else {
            var dialog = $('<div>' +
            Translator.trans('booking.share.balance-error.popup-text', {}, 'booking') + ':' +
            '<br><br>' +
            error +
            '<br><br>' +
            Translator.trans(/** @Desc("You can try updating this account credentials via this page in order to fix this problem") */ 'booking.share.balance-error.popup-user-text', {}, 'booking') + ':' +
            '<br><br>' +
            '<a target="_blank" href="https://awardwallet.com/account/edit.php?ID=' + id + '">https://awardwallet.com/account/edit.php?ID=' + id + '</a>' +
            '</div>');
        }

        dialog.dialog({
            title: Translator.trans(/** @Desc("Account Error") */ 'booking.share.balance-error.popup-title', {}, 'booking'),
            width: '800',
            modal: true,
            draggable: false,
            show: {
                effect: "fade",
                duration: 300
            },
            hide: {
                effect: "fade",
                duration: 300
            },
            buttons: [
                {
                    "text": "OK",
                    "class": "btn-blue",
                    "click": function () {
                        $(this).dialog("close");
                    }
                }
            ],
            close: function () {
                $(this).dialog("destroy");
            }
        });
        return false;
    });

    var cancel_dialog = $('#cancel_popup');
    cancel_dialog.dialog({
        title: Translator.trans('cancel_request.popup.title', {}, /** @Desc("Booking request cancelation") */ 'booking'),
        width: 600,
        autoOpen: false,
        modal: true,
        buttons: [
            {
                text: "No",
                click: function () {
                    cancel_dialog.dialog('close');
                },
                "class": "btn-silver"
            },
            {
                text: 'Yes, cancel this booking request',
                style: "width: 250px;",
                "class": "btn-blue",
                click: function () {
                    var button = $('#request-cancel-btn');
                    $(this).parents('.ui-dialog').find('button').eq(2).addClass('loader');
                    $.ajax({
                        url: button.attr('data-href'),
                        method: 'post',
                        success: function () {
                            window.location.href = Routing.generate('aw_booking_view_index', {id: button.data('id')});
                        },
                        error: function (xhr, status, error) {
                            errorHandler('General error ' + status + '. ' + error);
                        }
                    });
                }
            }
        ]
    });

    $('#request-cancel-btn').click(function (e) {
        e.preventDefault();
        cancel_dialog.dialog('open');
    });

    $('#request-repost-btn').click(function (e) {
        e.preventDefault();
        var button = $(this);
        button.addClass('loader');
        $.ajax({
            url: button.attr('data-href'),
            method: 'post',
            success: function () {
                window.location.href = Routing.generate('aw_booking_view_index', {id: button.data('id')});
            },
            error: function (xhr, status, error) {
                errorHandler('General error ' + status + '. ' + error);
            }
        })
    });

    $('.page').addClass('loaded');

    var params = {};
    var parts = document.location.search.substring(1).split('&');
    for (var i = 0; i < parts.length; i++) {
        var nv = parts[i].split('=');
        if (!nv[0]) continue;
        params[nv[0]] = nv[1] || true;
    }

    var postForm = $('#booking_request_message_Post'),
        scrollTo = $('#create-message');

    var readyCkeditor = function() {
        var d = $.Deferred();
        if (typeof CKEDITOR == 'undefined') {
            d.reject();
        } else {
            var message = CKEDITOR.instances['booking_request_message_Post'];
            if (message['instanceReady']) {
                d.resolveWith(null, [message]);
            } else {
                message.on("instanceReady", function (event) {
                    d.resolveWith(null, [message]);
                });
            }
        }
        return d.promise();
    };
    var setResponse = function($val) {
        readyCkeditor()
            .done(function(inst){
                $('html, body').animate({
                    scrollTop: scrollTo.offset().top
                }, {
                    complete: function(){
                        if ($val) inst.setData($val);
                        inst.ui.editor.focus();
                    }
                });
            })
            .fail(function(){
                $('html, body').animate({
                    scrollTop: scrollTo.offset().top
                }, {
                    complete: function(){
                        if ($val) postForm.val($val);
                        postForm.focus();
                    }
                });
            });
    };


    if (typeof(params['s']) !== 'undefined' && params['s'] !== '') {
        setResponse(params['s']);
    } else if (typeof(params['invoice']) !== 'undefined' && params['invoice'] !== '') {
        var button = $('#message_' + params['invoice'] + ' .invoice_proceed');
        if (button.length === 1) {
            setTimeout(function () {
                $.scrollTo(button, 0, {
                    onAfter: function () {
                        button.click();
                    }
                });
            }, 300);
        }
    } else if (typeof(params['new']) !== 'undefined') {
        const isAuthor = $('#requestView').data('is-author');
        let unread;

        if (isAuthor) {
            unread = $('.message-block.inbox.new:first');
        } else {
            unread = $('.message-block.send.new:first');
        }
        if (unread.length === 1) {
            setTimeout(function () {
                $('html, body').animate({
                    scrollTop: unread.offset().top - unread.height()
                });
            }, 300);
        }
    } else if (window.location.hash === '#respond-block') {
        setResponse(null);
    }

    function processTimeline(scroll = false) {
        const requestElem = $('#requestView');
        const travelPlanLink = requestElem.data('travel-plan');
        const canAddPlan = requestElem.data('can-add-plan');

        if (travelPlanLink) {
            let timelineContainer = $('.timeline-container');

            if (timelineContainer.length === 0) {
                let removeButton = '';

                if (canAddPlan) {
                    removeButton = '' +
                        '<div id="remove-plan-blk" class="booker-nav-blk">\n' +
                        '    <ul>\n' +
                        '        <li><a href="javascript:void(0);" id="remove-plan-btn" class="btn-silver"><i class="old-icon-trash"></i></a></li>\n' +
                        '    </ul>\n' +
                        '</div>';
                }

                $('#respond-block').after(
                    '<div class="booker-block timeline-container">\n' +
                    '   <div class="booker-title">\n' +
                    '       <span>' + Translator.trans('booking.request.add.form.segment.title_single', {}, 'booking') + '</span>\n' +
                            removeButton +
                    '   </div>\n' +
                    '   <div>\n' +
                    '       <iframe id="travel-plan-iframe" class="autoResizable" data-body="main-body" style="border: 0; width: 100%"></iframe>\n' +
                    '   </div>\n' +
                    '</div>'
                );

                $('#remove-plan-btn').click(function() {

                    $.ajax({
                        url: Routing.generate('aw_booking_timeline_removeplan', {id: requestElem.data('id')}),
                        method: 'post',
                        success: function ({success, error}) {
                            $('#requestView').data('travel-plan', null);
                            processTimeline();
                        }
                    });
                });

                timelineContainer = $('.timeline-container');
            }

            timelineContainer.find('iframe').attr('src', travelPlanLink + '?iframe=1');
            $('#travel-plan-iframe').on('load', function() {
                if (scroll) {
                    setTimeout(function() {
                        timelineContainer[0].scrollIntoView({block: "start", behavior: "smooth"});
                    }, 500);
                }
            });

            $('#add-trip-btn').remove();
        } else {
            $('.timeline-container').remove();

            if (canAddPlan) {
                $('#status-ul').prepend(
                    '<li>\n' +
                    '   <a href="#" id="add-trip-btn" class="btn-silver">\n' +
                            Translator.trans('add-trip', {}, 'trips') +
                    '   </a>\n' +
                    '</li>'
                );
            }
        }
    }

    processTimeline();
});

