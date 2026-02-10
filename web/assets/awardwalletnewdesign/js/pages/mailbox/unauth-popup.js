define(['jquery-boot', 'lib/dialog', 'routing', 'translator-boot'], function ($, dialog) {

    return function popup(mailboxId, email) {
        var element = $('<div>' + Translator.trans(
            /** @Desc("We've lost the connection to your mailbox ""%bold_on%%email%%bold_off%"" please re-authenticate your mailbox now. Alternatively, if this mailbox no longer exists you can %link_on%delete it%link_off% from AwardWallet.") */
            'mailbox_lost_connection_popup.v2', {
                email: email,
                bold_on: "<span class='bold'>",
                bold_off: "</span>",
                link_on: "<a href='" + Routing.generate('aw_usermailbox_view') + "'>",
                link_off: "</a>"
            }) + '</div>');
        var options = {
            autoOpen: true,
            modal: true,
            buttons: [{
                text: Translator.trans(/** @Desc("Skip") */ 'skip'),
                click: function click() {
                    $(this).dialog("close");
                },
                'class': 'btn-silver',
                'tabIndex': -1
            }, {
                text: Translator.trans(
                    /** @Desc("Re-authenticate Mailbox") */
                    're_authenticate_mailbox'),
                click: function click() {
                    $(this).dialog("close");
                    document.location.href = Routing.generate('aw_usermailbox_update_oauth', {'mailboxId': mailboxId});
                },
                'class': 'btn-blue',
                'tabIndex': -1
            }],
            width: 600,
            height: 'auto',
            title: Translator.trans(
                /** @Desc("Mailbox Connection Lost") */
                'mailbox_connection_lost_title'),
            close: function close() {
                $(this).dialog('destroy').remove();
            }
        };
        element.dialog(options);
    };

});
