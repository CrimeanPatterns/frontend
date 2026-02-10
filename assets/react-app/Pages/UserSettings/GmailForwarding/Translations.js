import { Translator } from '@Services/Translator';

Translator.trans(/** @Desc("Gmail Travel Confirmation Email Forwarding Instructions.") */ 'gmail.filter.title');

Translator.trans(
    /** @Desc("The easiest way to import all your trips into AwardWallet is to %link_on%link your mailbox%link_off% to your account. We
     are officially approved by Google, Microsoft, Yahoo, and AOL to do this. We are secure. However, if you
     are not willing to do that and are a Gmail user, please follow these easy steps to set up automatic
     forwarding of your travel confirmation emails into AwardWallet; you will need to use a desktop computer
     for this") */ 'gmail.filter.description',
    {
        link_on: '<a href="/gmail-forwarding" target="_blank">',
        link_off: '</a>',
    },
);

Translator.trans(
    /** @Desc("Please note that the %files% files that we provided in %link_on%step 8%link_off% get updated as we develop more parsers for new travel providers; we recommend that you update your filters from time to time by deleting the existing filter and repeating steps 8 - 13.") */ 'gmail.filter.note',
    {
        link_on: "<a href='#step8'>",
        link_off: '</a>',
        files: '',
    },
);

Translator.trans(
    /** @Desc("In the GMail interface, click the 'Settings' gear -> 'See all settings'.") */ 'gmail.filter.step1',
);

Translator.trans(/** @Desc("Go to the 'Forwarding and POP/IMAP' tab.") */ 'gmail.filter.step2');

Translator.trans(
    /** @Desc("Click the 'Forwarding and POP/IMAP' tab, and under the 'Forwarding:' heading, click the 'Add a forwarding address' button.") */ 'gmail.filter.step3',
);

Translator.trans(
    /** @Desc("In the 'Add a forwarding address' popup box, input %link_on%%email%%link_off% and click 'Next'.") */ 'gmail.filter.step4',
    {
        email: `${userLogin}+f@email.AwardWallet.com`,
        link_on: '<a href="mailto:test@test.com">',
        link_off: '</a>',
    },
);

Translator.trans(
    /** @Desc("Google may want to authenticate you; if so, please follow the prompts.") */ 'gmail.filter.step5',
);

Translator.trans(
    /** @Desc("Confirm that you want to set up forwarding by clicking 'Proceed'.") */ 'gmail.filter.step6',
);

Translator.trans(
    /** @Desc("Google will tell you that a confirmation link was sent to <a href='mailto:%userEmail%' target='_blank'>%userEmail%</a> to verify permission. Please note that AwardWallet will auto-approve such a request; you do not need to contact our support. Instead, give it a few minutes and refresh the page. Typically, this takes 0 - 3 minutes; however, if our servers are busy, it may take a few hours, so you may have to come back to finish this up later.") */ 'gmail.filter.step7',
    {
        userEmail: '',
    },
);

Translator.trans(
    /** @Desc("Download the %files% files. These files list the addresses from which we can parse travel confirmation emails. Feel free to inspect them in your text editor of choice. You will need to repeat steps 9 - 11 for each file separately.") */ 'gmail.filter.step8',
    {
        files: '',
    },
);

Translator.trans(
    /** @Desc("Go to the 'Filters and Blocked Addresses' tab, click 'Import filters' -> 'Choose File', and select the just downloaded 'gmailFilter.xml' file from your Downloads folder.") */ 'gmail.filter.step9',
);

Translator.trans(/** @Desc("Click 'Open File'.") */ 'gmail.filter.step10');

Translator.trans(/** @Desc("Scroll to the bottom and click 'Create filters'.") */ 'gmail.filter.step11');

Translator.trans(
    /** @Desc("Google may want to verify your identity again; if so, complete the verification.") */ 'gmail.filter.step12',
);

Translator.trans(
    /** @Desc("You are done; your AwardWallet filter has been created, and your travel plans will be built automatically for you going forward.") */ 'gmail.filter.step13',
);

Translator.trans(/** @Desc("Loading Error") */ 'alerts.loading-error');

Translator.trans(
    /** @Desc("This page didn’t load correctly. Try loading the page to fix this.") */ 'page.loaded.with.error',
);

Translator.trans(
    /** @Desc("If you wish to set this up for your family members, please (1) select the right person at the <a id='%link_id%' href='#'>top of this page</a> and (2) make sure you do this entire setup in their Google Mailbox, not yours.") */ 'gmail.filter.step4.family',
    {
        link_id: '',
    },
);

Translator.trans(
    /** @Desc("Please select the AwardWallet user you want to configure these filters for:") */ 'gmail.filter.select.family',
);

Translator.trans('account.label.owner');

Translator.trans(/** @Desc("AwardWallet User") */ 'gmail.filter.awardwallet.user');

Translator.trans(
    /** @Desc("Do you want to specify an alternate 'To' address?") */ 'gmail.filter.want.specify.alternate.address',
);

Translator.trans(
    /** @Desc("This is uncommon; You can use this if you use aliases or tags such as username+tag@gmail.com") */ 'gmail.filter.specify.address.description',
);

Translator.trans(/** @Desc("Alternate 'To:' address:") */ 'gmail.filter.alternative.address');

Translator.trans(/** @Desc("i.e. username+tag@gmail.com or an alias") */ 'gmail.filter.alternative.input.placeholder');
