define(['jquery-boot', 'centrifuge', 'lib/dialog', 'pages/mailbox/request', 'routing', 'jqueryui', 'awardwallet', 'translator-boot'], function ($, Centrifuge, dialog, requester) {
    var ListMailbox =
        function () {
            function ListMailbox() {
                this.container = null;
                this.optionsUrl = null;
            }

            var _proto = ListMailbox.prototype;

            _proto.init = function init(centrifuge_config, userId, container) {
                this.container = container;
                var client = new Centrifuge(centrifuge_config);
                client.on('connect', function () {
                    console.log('centrifuge connected');

                    var onMessage = function onMessage(message) {
                        console.log(message.data);
                        var data = message.data;
                        var row = $('tr.mailbox_row[data-id="' + data.id + '"]');
                        row.find('td.status i').attr('class', data.icon);
                        row.find('p').text(data.status);
                    };

                    var subscription = client.subscribe('$mailboxes_' + userId, onMessage);
                    subscription.history().then(function (message) {
                        console.log('history messages received', message);
                        $.each(message.data.reverse(), function (index, value) {
                            onMessage(value);
                        });
                        $.ajax({
                            url: Routing.generate('aw_usermailbox_send_progress'),
                            method: 'POST'
                        });
                    }, function (err) {
                        console.log('history call failed', err);
                    });
                });
                client.connect();
            };

            _proto.addFormShow = function addFormShow() {
                $('.scanner-buttons ul').removeClass('f-right');
                $('#add-another').hide();
                $('#done-continue').show();
                $('.scanner-bottom').show(400, function () {
                    $('#user_mailbox_email').focus();
                });
                var emptyList = this.container.find('tbody tr').length === 0;
                $('#skip-buttons').toggle(emptyList);
                $('#mailboxes').toggle(!emptyList);
                $('#mailboxes-buttons').toggle(!emptyList);
                $('#intro').toggle(emptyList);
            };

            _proto.deleteMailbox = function deleteMailbox(el) {
                var email = $(el).closest('tr').find('td.email').text();
                var element = $('<div>' + Translator.trans(
                    /** @Desc("Are you sure, you want to delete <span class='bold'>%email%</span> mailbox?") */
                    'scanner.want_delete', {
                        email: email
                    }) + '</div>');
                var options = {
                    autoOpen: true,
                    modal: true,
                    buttons: [{
                        text: Translator.trans('button.no'),
                        click: function click() {
                            $(this).dialog("close");
                        },
                        'class': 'btn-silver',
                        'tabIndex': -1
                    }, {
                        text: Translator.trans(
                            /** @Desc("Yes, delete this mailbox") */
                            'scanner.yes_delete'),
                        click: function click() {
                            var emailId = $(el).closest('tr[data-id]').data('id');
                            $(this).dialog("close");
                            requester.request(Routing.generate('aw_usermailbox_delete'), 'post', {
                                id: emailId
                            }, {
                                button: $(this),
                                success: function success(data) {
                                    document.location.reload();
                                    return false;
                                }
                            });
                        },
                        'class': 'btn-blue',
                        'tabIndex': -1
                    }],
                    width: 600,
                    height: 'auto',
                    title: Translator.trans(
                        /** @Desc("Delete mailbox") */
                        'scanner.delete_title'),
                    close: function close() {
                        $(this).dialog('destroy').remove();
                    }
                };
                element.dialog(options);
            };

            _proto.reauthMailbox = function reauthMailbox(el) {
                var email = $(el).closest('tr').find('td.email').text();
                var emailId = $(el).closest('tr[data-id]').data('id');
                var type = $(el).closest('tr[data-id]').data('type');
                if (type === 'imap') {
                    // askImapPassword(email, emailId);
                    return;
                }
                document.location.href = Routing.generate('aw_usermailbox_update_oauth', {'mailboxId': emailId});
            };

            return ListMailbox;
        }();

    return new ListMailbox();
});