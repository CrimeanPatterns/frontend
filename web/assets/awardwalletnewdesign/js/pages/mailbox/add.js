define(['jquery-boot', 'lib/dialog', 'pages/mailbox/request', 'lib/customizer', 'translator-boot', 'routing'], function ($, dialog, requester, customizer) {
    var AddMailbox =
        function () {
            function AddMailbox() {
                this.selectOwnerDialog = null;
                this.selectOwnerDeferred = null;
                this.owner = null;
                this.emailOwners = {};
                this.onSubmitAddForm = this.onSubmitAddForm.bind(this);
                this.selectFamilyMember = this.selectFamilyMember.bind(this);
            }

            var _proto = AddMailbox.prototype;

            _proto.setFamilyMembers = function setFamilyMembers(userFullName, familyMembers) {
                this.familyMembers = familyMembers;
                this.familyMembers.unshift({
                    useragentid: '',
                    fullName: userFullName
                });
            };

            _proto.setOwner = function setOwner(owner) {
                this.owner = owner;
            };

            _proto.setRedirectUrl = function setRedirectUrl(url) {
                this.redirectUrl = url;
            };

            _proto.subscribe = function subscribe() {
                var _this = this;

                $(document).on('submit', 'form[name="user_mailbox"]', this.onSubmitAddForm);
                $(document).on('click', '.add-mailbox-link', function (event) {
                    event.preventDefault();
                    var url = $(this).attr('href');

                    _this.selectFamilyMember().then(function (agentId) {
                        document.location.href = url + '?agentId=' + agentId;
                    });
                });
            };

            _proto.onSubmitAddForm = function onSubmitAddForm() {
                var _this = this;
                this.selectFamilyMember($('#user_mailbox_email').val()).then(function (agentId) {
                    var form = $('form[name="user_mailbox"]');
                    requester.request(Routing.generate('aw_usermailbox_add', {
                        agentId: agentId
                    }), 'post', {
                        'email': $('#user_mailbox_email').val(),
                        'password': $('#user_mailbox_password').is(':visible') ? $('#user_mailbox_password').val() : ''
                    }, {
                        timeout: 1000 * 60 * 2,
                        button: form.find('div.submit'),
                        before: function before() {
                            $('div.error-mailbox-login').remove();
                        },
                        success: function success(data) {
                            var unlock = true;

                            switch (data.status) {
                                case "redirect":
                                    document.location.href = data.url;
                                    unlock = false;
                                    break;

                                case "error":
                                    var message = $('div.row-email div[class="error-message"][data-type=serverError]');
                                    message.find('div.error-message-description').text(data.error);
                                    message.css('display', 'table-row');
                                    $('div.row-email').addClass('error');
                                    break;

                                case "ask_password":
                                    $('div.row-password').show(400, function () {
                                        $('#user_mailbox_password').focus();
                                    });
                                    break;

                                case "added":
                                    unlock = false;
                                    console.log('sending mailbox added event: imap');
                                    window.dataLayer = window.dataLayer || [];
                                    window.dataLayer.push({
                                        'event': 'user_mailbox_added',
                                        'addedType': 'imap',
                                        'eventCallback': function() {
                                            if (_this.redirectUrl) {
                                                document.location.href = _this.redirectUrl;
                                            } else {
                                                document.location.reload();
                                            }
                                        }
                                    });
                                    if (!customizer.isGtmLoaded()) {
                                        setTimeout(window.dataLayer.at(-1).eventCallback(), 3000);
                                    }
                                    break;
                            }

                            return unlock;
                        }
                    });
                });
                return false;
            };

            _proto.selectFamilyMember = function selectFamilyMember(email) {
                this.selectOwnerDeferred = $.Deferred();

                if (typeof(this.owner) === 'string') {
                    this.selectOwnerDeferred.resolve(this.owner);
                    return this.selectOwnerDeferred.promise();
                }

                if (this.familyMembers.length === 0) {
                    this.selectOwnerDeferred.resolve('');
                    return this.selectOwnerDeferred.promise();
                }

                if (email && email in this.emailOwners) {
                    this.selectOwnerDeferred.resolve(this.emailOwners[email]);
                    return this.selectOwnerDeferred.promise();
                }

                if (this.selectOwnerDialog === null) {
                    var _this = this;

                    this.selectOwnerDialog = dialog.fastCreate(
                        Translator.trans('mailbox_owner'),
                        "<div>\n" +
                        "            <label for=\"set-owner\">" + Translator.trans('mailbox_owner') + ":</label>\n" +
                        "            <div class=\"input\">\n" +
                        "                <div class=\"input-item\">\n" +
                        "                    <div class=\"styled-select\">\n" +
                        "                        <div>\n" +
                        "                        <select class=\"mailbox-owner\">\n" +
                                                    _this.familyMembers.map(function(familyMember) {
                                                        return "<option value=\"" + familyMember.useragentid + "\">" + familyMember.fullName + "</option>\n";
                                                    }).join() +
                        "                        </select>\n" +
                        "                        </div>\n" +
                        "                    </div>\n" +
                        "                </div>\n" +
                        "            </div>\n" +
                        "        </div>",
                        true,
                        false,
                        [
                            {
                                text: Translator.trans('button.ok'),
                                click: function click() {
                                    var agentId = $('.ui-dialog-content .mailbox-owner').val();

                                    if (email) {
                                        _this.emailOwners[email] = agentId;
                                    }

                                    $(this).dialog('close');

                                    _this.selectOwnerDeferred.resolve(agentId);
                                },
                                'class': 'btn-blue'
                            },
                            {
                                text: Translator.trans('button.cancel'),
                                click: function click() {
                                    $(this).dialog('close');
                                },
                                'class': 'btn-silver'
                            }
                        ],
                        500
                    );
                    this.selectOwnerDialog.setOption('close', null);
                }

                this.selectOwnerDialog.open();
                return this.selectOwnerDeferred.promise();
            };

            return AddMailbox;
        }();

    return new AddMailbox();
});
