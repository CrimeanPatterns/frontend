define(['angular-boot', 'jquery-boot', 'directives/dialog', 'routing', 'lib/customizer'], function () {
    'use strict';

    angular.module('contactUsPage-ctrl', ['dialog-directive'])
        .controller('contactCtrl', ['$scope', 'dialogService', '$http', '$window', function($scope, dialogService, $http, $window){
            var tag = $('[data-ng-controller="contactCtrl"]');
            var vars = {
                form: $("#contactUsForm"),
                sendAnyways: false,
                submitted: false,
                isTicketBookingRequest: tag.data('is-ticket-booking-request'),
                bookingRequestCount: tag.data('booking-request-count'),
                bookingRequestLink: tag.data('booking-request-link'),
                rememberStackSearch: [],
                rememberStackOther: []
            };
            var methods = {
                goBooking: function() {
                    if (vars.bookingRequestCount == 1) {
                        window.location.href = vars.bookingRequestLink.replace('{text}', encodeURIComponent($('[name="contact_us_auth[message]"]').val()));
                    } else if (vars.bookingRequestCount > 1) {
                        window.open(vars.bookingRequestLink);
                    } else if (vars.bookingRequestCount == 0) {
                        window.open(vars.bookingRequestLink);
                    }
                },
                disableForm: function() {
                    vars.form.find("input, textarea, button").attr('disabled', 'disabled');
                },
                enableForm: function() {
                    vars.form.find("input, textarea, button").removeAttr('disabled');
                },
                openBookingDialog: function() {
                    var dialog = dialogService.get('detectBookingRequest');
                    var buttons = [];
                    var onClick = function(){
                        $(this).dialog("close");
                        methods.goBooking();
                    };
                    if (vars.bookingRequestCount == 0) {
                        buttons.push({
                            text: Translator.trans('booking.links.new_request', {}, 'booking'),
                            click: onClick,
                            'class': 'btn-silver'
                        });
                    } else {
                        buttons.push({
                            text: Translator.trans('booking.go.post', {}, 'booking'),
                            click: onClick,
                            'class': 'btn-silver'
                        });
                    }
                    buttons.push({
                        text: Translator.trans('contactus.send-to-support-anyways.button', {}, 'contactus'),
                        click: function(){
                            $(this).dialog("close");
                            vars.isTicketBookingRequest = 0;
                            vars.form.submit();
                        },
                        'class': 'btn-blue'
                    });
                    dialog.setOption("buttons", buttons);
                    dialog.setOption("open", function(){
                        methods.addRememberStack($('#detectBookingRequest .rememberData').text());
                        vars.isTicketBookingRequest = 0;
                    });
                    dialog.open();
                },
                openBookingNoticeDialog: function() {
                    var dialog = dialogService.get('bookingNotice');
                    dialog.setOption("buttons", [{
                        text: Translator.trans('close.btn', {}, 'messages'),
                        click: function() { $(this).dialog("close"); },
                        'class': 'btn-blue'
                    }]);
                    dialog.setOption("open", function(){
                        methods.disableForm();
                        methods.addRememberStack($('#bookingNotice .rememberData').text());
                    });
                    dialog.open();
                },
                processRequestType: function(type) {
                    if (type == 'Award Ticket Booking Requests') {
                        methods.openBookingNoticeDialog();
                        return false;
                    } else {
                        methods.enableForm();
                        return true;
                    }
                },
                addRememberStack: function(str, s) {
                    var maxCount = 50,
                        stack;
                    stack = (typeof s == 'undefined') ? vars.rememberStackOther : vars.rememberStackSearch;
                    str = $('<div/>').text(str).html();
                    str.replace('|', '');
                    stack.unshift(str);
                    if (stack.length > maxCount)
                        stack.pop();
                    if (typeof s == 'undefined')
                        vars.rememberStackOther = stack;
                    else
                        vars.rememberStackSearch = stack;
                },
                showProgress: function() {
                    var popup = $('#programSearch').parent();
                    popup.find('.ajax-loader').show();
                    popup.find('.ui-dialog-buttonpane').hide();
                    popup.find('.ui-dialog-titlebar').hide();
                    $('#programSearchResult').hide();
                },
                showResult: function() {
                    var popup = $('#programSearch').parent();
                    popup.find('.ajax-loader').hide();
                    popup.find('.ui-dialog-buttonpane').show();
                    popup.find('.ui-dialog-titlebar').show();
                    $('#programSearchResult').show();
                },
                submit: function() {
                    if (vars.submitted) return false;
                    vars.submitted = true;

                    const requestType = vars.form.find('select[name="contact_us_auth[requesttype]"], select[name="contact_us_unauth[requesttype]"]').val();
                    window.dataLayer = window.dataLayer || [];
                    window.dataLayer.push({'event': 'form_contactus', 'requestType': requestType});

                    let extensionVersion = localStorage.getItem('extension_version');
                    if ('string' === typeof extensionVersion) {
                        $('input[name$="[extensionVersion]"]').val(extensionVersion);
                    }
                    vars.form.append('<input name="__allow__" value="1" type="hidden" />').submit();
                }
            };

            $('select[name="contact_us_auth[requesttype]"], select[name="contact_us_unauth[requesttype]"]').change(function() {
                methods.processRequestType($(this).val());
            });

            vars.form.submit(function() {
                var dialog = dialogService.get('programSearch'),
                    form = vars.form;
                if (form.find('input[name="__allow__"]').length) {
                    $('input[name*=shownData]').val($.merge(vars.rememberStackSearch, vars.rememberStackOther).join('|'));
                    form.find(':submit').attr('disabled', 'disabled');
                    return true;
                }

                if (vars.isTicketBookingRequest == 1 && !vars.sendAnyways) {
                    methods.openBookingDialog();
                    return false;
                }

                var t = methods.processRequestType(form.find('select[name="contact_us_auth[requesttype]"], select[name="contact_us_unauth[requesttype]"]').val());
                if (!t)
                    return false;

                dialog.setOption("title", Translator.trans('contactus.view-ready-answers.text', {}, 'contactus'));
                dialog.setOption("open", function(){
                    $('#programSearchResult').empty();
                    methods.disableForm();
                    $.post(Routing.generate('aw_contactus_programsearch', {'_format': 'json'}), {msg: form.find("textarea[name*='[message]']").val()},
                        function(data) {
                            methods.enableForm();
                            vars.rememberStackSearch = [];
                            if (data.content === '' || data.error !== '') {
                                methods.submit();
                            } else {
                                $('#programSearchResult').append(data.content);
                                dialog.setOption("width", 800);
                                methods.showResult();
                                dialog.setOption("position", {
                                    my: "center",
                                    at: "center",
                                    of: $window
                                });
                                $($(data.content).filter('.result-item').add($(data.content).find('.result-item')).get().reverse()).each(function() {
                                    methods.addRememberStack($(this).text(), true);
                                });
                            }
                        }).fail(function(){
                            methods.enableForm();
                            dialog.close();
                        });
                });
                dialog.setOption("buttons", [
                    {
                        text: Translator.trans('contactus.edit-my-message.button', {}, 'contactus'),
                        click: function() { $(this).dialog("close"); },
                        'class': 'btn-silver'
                    },
                    {
                        text: Translator.trans('contactus.send-anyways.button', {}, 'contactus'),
                        click: function() { methods.submit(); },
                        'class': 'btn-blue'
                    }
                ]);

                methods.showProgress();
                dialog.setOption("width", 300);
                dialog.open();
                return false;
            });

        }]);
});
