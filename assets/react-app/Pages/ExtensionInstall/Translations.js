import Translator from '@Services/Translator';

Translator.trans('extension.required');

Translator.trans(
    /** @Desc("You are about to install the AwardWallet browser extension") */ 'extension.install.about.install',
);

Translator.trans('extension.browser-version');
Translator.trans(/** @Desc("AwardWallet Extension %version%") */ 'extension.version.name', {
    version: '',
});
Translator.trans(/** @Desc("Installed") */ 'extension.installed');
Translator.trans('extension.not.installed');
Translator.trans('extension.button.install');

Translator.trans(
    /** @Desc("Please %highlight_on%click the button%highlight_off% below to install the AwardWallet browser extension:") */ 'extension.install.v2',
    {
        highlight_on: '',
        highlight_off: '',
    },
);
Translator.trans('reload-page');

Translator.trans(/** @Desc("Not Installed or not updated") */ 'extension.not.installed.or.updated');

Translator.trans(/** @Desc("Installed, not enabled") */ 'extension.installed.not.enabled');
Translator.trans('error.award.account.other.title');

Translator.trans(
    /** @Desc("On a Mac, the AwardWallet Browser Helper for Safari is installed via App Store. Please %highlight_on%click the button below%highlight_off% to launch the Apple App Store:") */ 'extension.install.safari.step1',
    {
        highlight_on: '',
        highlight_off: '',
    },
);
Translator.trans(
    /** @Desc("%highlight_on%Download%highlight_off% and %highlight_on%Open%highlight_off% the AwardWallet App, after %highlight_on%follow the steps%highlight_off% on the screen to enable the Safari browser extension:") */ 'extension.install.safari.step2',
    {
        highlight_on: '',
        highlight_off: '',
    },
);
Translator.trans(
    /** @Desc("%highlight_on%Press the 'Quit and Open Safari Settings...'%highlight_off% button to open Safari Extensions.") */ 'extension.install.safari.step3',
    {
        highlight_on: '',
        highlight_off: '',
    },
);
Translator.trans(
    /** @Desc("Check the checkbox by the AwardWallet browser extension to enable it.") */ 'extension.install.safari.step4',
);
Translator.trans(
    /** @Desc("You may need to %highlight_on%authenticate yourself%highlight_off% to do this.") */ 'extension.install.safari.step5',
    {
        highlight_on: '',
        highlight_off: '',
    },
);
Translator.trans(/** @Desc("Click the AwardWallet icon in the toolbar.") */ 'extension.install.safari.step6');
Translator.trans(
    /** @Desc("Select the last option: %highlight_on%'Always Allow on Every Website.'%highlight_off%") */ 'extension.install.safari.step7',
    {
        highlight_on: '',
        highlight_off: '',
    },
);
Translator.trans(
    /** @Desc("Confirm your choice by clicking %highlight_on%'Always Allow on Every Website'%highlight_off% again.") */ 'extension.install.safari.step8',
    {
        highlight_on: '',
        highlight_off: '',
    },
);

Translator.trans(/** @Desc("Open %place_holder%") */ 'extension.install.button.open', {
    place_holder: '',
});
