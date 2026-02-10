import Translator from '@Services/Translator';

Translator.trans(/**@Desc("Saying Farewell? Let's Do It Right.")*/ 'user.delete.title');
Translator.trans('user.delete.info1');
Translator.trans(
    /**@Desc("At AwardWallet, we value your experience and are always here to assist you. If you encountered any
issues or have feedback, please don't hesitate to %link_on%send us a note%link_off%. We would appreciate it if you could let us know why you decided to leave AwardWallet.")*/ 'user.delete.info3',
    {
        link_on: '<a href="/contact" target="_blank">',
        link_off: '</a>',
    },
);
Translator.trans(/**@Desc("Data Deleted:")*/ 'user.delete.term.title1');
Translator.trans(
    /**@Desc("Upon account deletion, we will remove your personal information, loyalty accounts, travel plans, transaction history, and any other data associated with your account.")*/ 'user.delete.term1',
);
Translator.trans(/**@Desc("Data Retained:")*/ 'user.delete.term.title2');
Translator.trans(
    /**@Desc("For legal and audit purposes, we may retain anonymized meta data records without any personal identifiers.")*/ 'user.delete.term2',
);
Translator.trans(/**@Desc("Retention Period:")*/ 'user.delete.term.title3');
Translator.trans(
    /**@Desc("Any retained data will be kept for a period of at least 1 year following account deletion.")*/ 'user.delete.term3',
);
Translator.trans('alerts.warning');
Translator.trans('user.delete.popup-text2');
Translator.trans('alerts.btn.ok');
Translator.trans('user.delete.deleted');
Translator.trans(/**@Desc("Please Enter Your Feedback:")*/ 'user.delete.enter.feedback');
Translator.trans('notblank', undefined, 'validators');
Translator.trans('user.delete.personal');
Translator.trans('user.delete.business');
Translator.trans('user.delete.confirm-text');

Translator.trans(
    /**@Desc("By clicking 'Delete My Account', you acknowledge that you understand the data deletion and retention policies. Your account and associated data will be permanently removed according to these terms.")*/ 'user.delete.button.description',
);

//user.delete.button.describe doesn't add
//user.delete.info3 brake link
