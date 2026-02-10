define(['centrifuge', 'sockjs', 'jquery-boot', 'routing'], function (fuge, sockjs, $) {
    window.SockJS = sockjs;

    var messageFormRequest = {
        required: {},
        url: '',
        form: null,
        formSubmitted: null,
        PostField: null,
        ColorField: null,
        InternalField: null,
        TextIncludeField: null,
        InfoMessageField: null,
        ActionMessageField: null,
        registerUrl: null,
        channels: null,
        socksConfig: null,
        contactUid: null,
        contactName: null,
        isFromMobileApp: false,

        init: function () {
            messageFormRequest.form = $('.js-message-form').find('form');
            messageFormRequest.PostField = $('#booking_request_message_Post');
            messageFormRequest.ColorField = $('#booking_request_message_Color');
            messageFormRequest.InternalField = $('#booking_request_message_Internal');
            messageFormRequest.TextIncludeField = $('#booking_request_message_TextInclude');
            messageFormRequest.InfoMessageField = $('#booking_request_message_InfoMessage');
            messageFormRequest.ActionMessageField = $('#booking_request_message_ActionMessage');

            messageFormRequest.addMessageBlock = $('#addMessageForm').slideDown();
            messageFormRequest.messagesBlock = $('#messagesBlock');
            messageFormRequest.internalMessagesBlock = $('#internalMessages');
            messageFormRequest.submitButton = $('#messageFormSubmitButton').removeAttr('disabled');
            messageFormRequest.requestId = $('#requestView').data('id');

            messageFormRequest.InternalField.click(messageFormRequest.checkboxChange);
            messageFormRequest.TextIncludeField.click(messageFormRequest.checkboxChange);
            messageFormRequest.form.on('submit', messageFormRequest.submit);
            messageFormRequest.checkboxChange();
            messageFormRequest.onlineRow = $('<li><div class="status-block"><span class="online"></span></div></li>');
            messageFormRequest.onlineAudio = $('#online-audio');
            messageFormRequest.newMessageAudio = $('#new-msg-audio');
            messageFormRequest.isFromMobileApp = $.cookie()['fromMobileApp'] === '1';

            var processed;
            $(document).on('click', '.js-message-delete', function (event) {
                event.preventDefault();
                var $this = $(this);
                var url = $this.data('url');

                $('#delete_message_popup').dialog({
                    title: Translator.trans(/** @Desc("Delete Message") */ 'booking.delete-message', null, 'booking'),
                    width: '450',
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
                            "text": Translator.trans('no', null, 'booking'),
                            "class": "btn-silver",
                            "click": function () {
                                $(this).dialog("close");
                            }
                        },
                        {
                            "text": Translator.trans(/** @Desc("Yes, Delete") */ 'booking.yes-delete', null, 'booking'),
                            "class": "btn-blue",
                            "click": function () {
                                var $dialog = $(this);
                                $(this).parents('.ui-dialog').find('.ui-dialog-buttonset button').eq(1).addClass('loader');
                                $.get(url, function (results) {
                                    if (results.success == true) {
                                        $this.closest('table.message-block').slideUp(1000);
                                    } else {
                                        $dialog.dialog("close");
                                        jAlert(results);
                                    }
                                })
                                    .fail(function (response, error) {
                                        $dialog.dialog("close");
                                    })
                                    .always(function () {
                                        $dialog.dialog("close");
                                    });
                            }
                        }
                    ]
                });
                return false;
            });
            $(document).on('click', '.js-message-edit', function (event) {
                event.preventDefault();
                if ($('#messageEditFormSubmitButton').length) return false;
                if (processed) return;
                processed = true;

                $('.js-message-edit').css("opacity", .5);
                var $this = $(this);
                var url = $this.data('url');
                $.get(url, function (results) {
                    if (results.success == true) {
                        $this.closest('table.message-block').find('.message-text').hide();
                        $this.closest('table.message-block').find('td.message').append($('<div class="message-text js-edit-message-text"></div>').html(results.form));
                        PrettifySelect();
                        messageEditFormRequest.init($this.closest('table.message-block'));
                    }
                }).always(function () {
                    processed = false;
                }).fail(function (response, error) {
                    $('.js-message-edit').css("opacity", 1);
                });
                return false;
            });

            messageFormRequest.users = {};

            var messagesCallbacks = {
                "message": function (mess) {
                    var info = JSON.parse(messageFormRequest.socksConfig.info);
                    if (
                        mess.data.internal && info.isBooker && mess.data.uid != messageFormRequest.socksConfig.user ||
                        !mess.data.internal && mess.data.uid != messageFormRequest.socksConfig.user
                    ) {
                        messageFormRequest.reloadMessagesThread(mess.data.internal);

                        if ((mess.data.notify && mess.data.internal && info.isBooker) || (mess.data.notify && !mess.data.internal)) {
                            if (!messageFormRequest.isFromMobileApp) {
                                messageFormRequest.newMessageAudio[0].play();
                            }

                            var title = document.title;
                            var replacer = '**********';
                            var flash = setInterval(function () {
                                document.title = (document.title == replacer) ? title : replacer;
                            }, 1000);

                            $(window).one('focus', function () {
                                clearInterval(flash);
                                document.title = title;
                            });

                            setTimeout(function () {
                                clearInterval(flash);
                                document.title = title;
                            }, 10000);
                        }
                    }
                },
            };

            var onlineCallbacks = {
                "join": function (mess) {
                    if (mess.data.user != messageFormRequest.socksConfig.user && !mess.data.default_info.impersonated)
                        messageFormRequest.onlineAudio[0].play();

                    messageFormRequest.updatePresenceUsers();
                },
                "leave": function () {
                    messageFormRequest.updatePresenceUsers();
                }
            };

            messageFormRequest.client = new fuge(messageFormRequest.socksConfig);
            messageFormRequest.subscriptionOnline = messageFormRequest.client.subscribe(messageFormRequest.channels.$abrequestonline, onlineCallbacks);
            messageFormRequest.client.subscribe(messageFormRequest.channels.$abrequestmessages, messagesCallbacks);
            messageFormRequest.client.subscribe(messageFormRequest.channels.$booker);
            messageFormRequest.client.connect();

        },
        checkboxChange: function () {
            var internal = messageFormRequest.InternalField.prop('checked') ? true : false;
            if (internal) {
                messageFormRequest.TextIncludeField.prop('disabled', true);
                messageFormRequest.TextIncludeField.next('span').addClass('disabled');
                messageFormRequest.InfoMessageField.prop('disabled', true);
                messageFormRequest.InfoMessageField.next('span').addClass('disabled');
                messageFormRequest.ActionMessageField.prop('disabled', true);
                messageFormRequest.ActionMessageField.next('span').addClass('disabled');
            } else {
                messageFormRequest.TextIncludeField.prop('disabled', false);
                messageFormRequest.TextIncludeField.next('span').removeClass('disabled');
                var textInclude = messageFormRequest.TextIncludeField.prop('checked') ? true : false;
                if (textInclude) {
                    messageFormRequest.InfoMessageField.prop('disabled', false);
                    messageFormRequest.InfoMessageField.next('span').removeClass('disabled');
                    messageFormRequest.ActionMessageField.prop('disabled', true);
                    messageFormRequest.ActionMessageField.next('span').addClass('disabled');
                    messageFormRequest.ActionMessageField.prop('checked', false);
                    messageFormRequest.ActionMessageField.siblings('span').removeClass('checked');
                } else {
                    messageFormRequest.InfoMessageField.prop('disabled', true);
                    messageFormRequest.InfoMessageField.next('span').addClass('disabled');
                    messageFormRequest.InfoMessageField.prop('checked', false);
                    messageFormRequest.InfoMessageField.siblings('span').removeClass('checked');
                    messageFormRequest.ActionMessageField.prop('disabled', false);
                    messageFormRequest.ActionMessageField.next('span').removeClass('disabled');
                }
            }
        },
        formReset: function () {
            if (typeof CKEDITOR != 'undefined') {
                CKEDITOR.instances.booking_request_message_Post.setData('');
            } else {
                messageFormRequest.PostField.val('');
            }
            messageFormRequest.PostField.html('');
            if (messageFormRequest.ColorField.length) {
                messageFormRequest.ColorField.val('');
                messageFormRequest.ColorField.siblings('.prettyfied-select').find('.prettyfied-select-box').html('<span></span>');
            }
            if (messageFormRequest.InternalField.length) {
                messageFormRequest.InternalField.prop('checked', true);
                messageFormRequest.InternalField.trigger('click');
                messageFormRequest.TextIncludeField.prop('checked', false);
                messageFormRequest.TextIncludeField.siblings('span').removeClass('checked');
                messageFormRequest.InfoMessageField.prop('checked', false);
                messageFormRequest.InfoMessageField.siblings('span').removeClass('checked');
                messageFormRequest.ActionMessageField.prop('checked', false);
                messageFormRequest.ActionMessageField.siblings('span').removeClass('checked');
//                messageFormRequest.ColorField.siblings('.checked').removeClass('checked');
                messageFormRequest.checkboxChange();
            }
        },
        submit: function (e) {
            e.preventDefault();

            if (typeof CKEDITOR != 'undefined') {
                for (var instance in CKEDITOR.instances)
                    CKEDITOR.instances[instance].updateElement();
            }

            messageFormRequest.submitButton.prop('disabled', true).addClass('loader');
            $.post(messageFormRequest.form.attr('action'), messageFormRequest.form.serialize(), function () {
                messageFormRequest.reloadMessagesThread($('#booking_request_message_Internal').is(':checked'), function () {
                    messageFormRequest.submitButton.prop('disabled', false).removeClass('loader').removeAttr('disabled');
                    messageFormRequest.formReset();
                });
            });
        },
        disableSubmit: function () {
            $('#messageFormSubmitButton').addClass('loader');
            if (typeof CKEDITOR == 'undefined') {
                messageFormRequest.PostField.attr('readonly', true);
            }
            messageFormRequest.formSubmitted = 1;
        },
        enableSubmit: function () {
            $('#messageFormSubmitButton').removeClass('loader');
            messageFormRequest.PostField.attr('readonly', false);
            messageFormRequest.formSubmitted = null;
        },
        clearErrors: function () {
            var elem = messageFormRequest.form.find('div.row.error select, div.row.error input, div.row.error textarea, div.row.error .htmleditor, div.row.error checkbox')
                .closest('div.row').removeClass('error required').find('div.message').text('').hide()
        },
        reloadMessagesThread: function (isInternal, finishCallback) {
            var loadBlock = isInternal ? messageFormRequest.internalMessagesBlock.find('.message-body') : messageFormRequest.messagesBlock;

            $.ajax({
                url: Routing.generate('aw_booking_message_getmessages', {
                    'id': messageFormRequest.requestId,
                    'internal': isInternal ? 'internal' : 'common',
                    'withContainer': 0
                }),
                success: function (messages) {
                    var changed = null;
                    $.each(messages, function (index, message) {
                        var existing = $('#' + index);
                        if (existing.length) {
                            if (existing.data('last-updated') != message.lastUpdated) {
                                existing.replaceWith(message.html);
                                changed = existing;
                            }
                        }
                        else {
                            loadBlock.append(message.html);
                            changed = $('#' + index);
                        }
                    });

                    loadBlock.children('table').each(function () {
                        if (typeof(messages[this.id]) == 'undefined')
                            $(this).remove();
                    });

                    if (changed) {
                        if (isInternal) { messageFormRequest.internalMessagesBlock.show(); }
//                        $('html, body').scrollTo(changed, 500, {offset: {top: -100}});
                        changed.scrollintoview({viewPadding: {y: 55}});
                    }

                    if (finishCallback) finishCallback();
                }
            });
        },
        updatePresenceUsers: function () {
            messageFormRequest.subscriptionOnline.presence().then(function (mess) {
                $('.user_status').remove();
                var users = [];
                for (var el in mess.data) {
                    var user = mess.data[el];
                    if (!user.default_info.impersonated && user.user != messageFormRequest.socksConfig.user) {
                        if (user.user == messageFormRequest.contactUid) {
                            users[user.user] = messageFormRequest.contactName;
                        } else {
                            users[user.user] = user.default_info.username;
                        }
                    }
                }

                users.forEach(function (user) {
                    user = $("<span />").text(user).html();
                    var line = $('<li class="user_status"><div class="status-block"><span class="online"></span>_user</div></li>'.replace(/_user/, user));
                    $('#status-ul').prepend(line);
                });
            });
        }
    };

    var messageEditFormRequest = {
        required: {},
        url: '',
        id: 0,
        container: null,
        form: null,
        formSubmitted: null,
        PostField: null,
        ColorField: null,
        InternalField: null,
        registerUrl: null,

        init: function ($container) {
            var self = this;
            $('.js-message-edit').css("opacity", .5);
            this.container = $container;
            this.id = $container.attr('id');
            this.form = $container.find('form');
            this.url = $container.find('form').attr('action');
            this.PostField = $container.find('#booking_request_edit_message_Post');
            this.ColorField = $container.find('#booking_request_edit_message_Color');
            this.InternalField = $container.find('#booking_edit_request_message_Internal');
            this.Button = $container.find('#messageEditFormSubmitButton');
            this.Cancel = $container.find('#messageEditFormCancelButton');
            if (typeof CKEDITOR != 'undefined')
                CKEDITOR.instances.booking_request_edit_message_Post.focus();
            else
                this.form.find('#booking_request_edit_message_Post').focus();
            this.Button.click(function (event) {
                self.submitClick(event);
            });
            this.Cancel.click(function (event) {
                self.cancelClick(event);
            });
        },
        submitClick: function (event) {
            event.preventDefault();
            this.clearErrors();
            this.doSubmit();
        },
        cancelClick: function (event) {
            event.preventDefault();
            this.container.find('.js-edit-message-text').remove();
            this.container.find('.message-text').show();
            $('.js-message-edit').css("opacity", 1);
        },
        doSubmit: function () {
            if (this.formSubmitted == null) {
                if (typeof CKEDITOR != 'undefined')
                    this.PostField.html(CKEDITOR.instances.booking_request_edit_message_Post.getData());
                var Data = this.form.serialize();
                var self = this;
                $.post(self.url,
                    Data
                    , function (results) {
                        if (results.success == true) {
                            if (typeof CKEDITOR != 'undefined')
                                CKEDITOR.instances.booking_request_edit_message_Post.destroy();
                            self.container.replaceWith(results.message);
                            $('.js-message-edit').css("opacity", 1);
                            $('html, body').scrollTo($('#' + self.id), 500);
                        } else {
                            for (var n in results.errors) {
                                var elem = $('#booking_request_edit_message_' + n);
                                elem.closest('div.row').addClass('error required').find('div.message').html(results.errors[n]).show();
                            }
                        }
                        self.enableSubmit();
                    });
                this.disableSubmit();
            }
            return false;
        },
        disableSubmit: function () {
            this.Button.addClass('loader');
            this.formSubmitted = window.setTimeout(this.enableSubmit, 10000);
        },
        enableSubmit: function () {
            var self = this;
            this.Button.removeClass('loader');
            window.clearTimeout(self.formSubmitted);
            this.formSubmitted = null;
        },
        clearErrors: function () {
            var elem = this.form.find('div.row.error select, div.row.error input, div.row.error textarea, div.row.error .htmleditor, div.row.error checkbox')
                .closest('div.row').removeClass('error required').find('div.message').text('').hide()
        }
    };

    return messageFormRequest;
});
