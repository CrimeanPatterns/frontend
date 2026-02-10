import $ from 'jquery-boot';
import addMailbox from 'pages/mailbox/add';
import listMailbox from 'pages/mailbox/list';
import request from 'pages/mailbox/request';
import 'lib/customizer';
import 'select2';
import 'lib/design';

$(function () {
    const businessUserFullName = window.userMailboxBusinessUserFullName;
    const familyMembers = window.userMailboxFamilyMembers;
    const centrifugeConfig = window.userMailboxCentrifugeConfig;
    const userId = window.userMailboxUserId;

    var container = $('.scanner');

    request.setContainer(container);
    addMailbox.setFamilyMembers(businessUserFullName, familyMembers);
    addMailbox.subscribe();

    listMailbox.init(centrifugeConfig, userId, container);

    $('#mailboxes').on('click', 'a.action:has(.icon-delete)', function () {
        listMailbox.deleteMailbox(this);
    });
    $('#mailboxes').on('click', 'a.action:has(.icon-reauth)', function () {
        listMailbox.reauthMailbox(this);
    });
    $('#add-another').on('click', function () {
        listMailbox.addFormShow();
    });

    var deleteMailboxId = location.search.match(/delete-mailbox=(\d+)/);

    if (deleteMailboxId && deleteMailboxId[1]) {
        var mailboxRow = $('tr.mailbox_row[data-id="' + deleteMailboxId[1] + '"]');

        if (mailboxRow.length) {
            listMailbox.deleteMailbox(mailboxRow.find('a.action:has(.icon-delete)'));
        }
    }
});
