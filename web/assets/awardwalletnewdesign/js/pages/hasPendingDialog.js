define(['jquery-boot', 'lib/dialog', 'translator-boot'], function ($, dialog) {
    var popup = dialog.fastCreate(
        Translator.trans(/** @Desc("New Loyalty Accounts Discovered in Your Mailbox.") */ 'new-discovered-accounts'),
        Translator.trans(/** @Desc("We've discovered new loyalty accounts in your mailbox that you can add to AwardWallet. You can review them now or later by clicking the plus sign by the &quot;Accounts&quot; menu at the top.") */ 'new-discovered-accounts-info'),
        true,
        true,
        [
            {
                text: Translator.trans('button.cancel'),
                click: function () {
                    $(this).dialog('close');
                },
                'class': 'btn-silver'
            },
            {
                text: Translator.trans(/** @Desc("Review Discovered Accounts") */ 'button.review-discovered-accounts'),
                click: function () {
                    location.href = Routing.generate('aw_select_provider')
                },
                'class': 'btn-blue'
            }
        ],
        600
    );
});