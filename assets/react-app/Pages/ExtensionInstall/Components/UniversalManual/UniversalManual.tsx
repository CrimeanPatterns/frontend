import { IBrowser } from 'ua-parser-js';
import { Icon } from '@UI/Icon/Icon';
import { ManualStep } from '../ManualStep/ManualStep';
import { PrimaryButton } from '@UI/Buttons';
import { Translator } from '@Services/Translator';
import React, { useEffect, useState } from 'react';
import classes from './UniversalManual.module.scss';

type UniversalUniversalManualProps = {
    browser: IBrowser;
};
//@ts-expect-error TS doesn't know that window is extended in twig
const linkForChromium = `https://chrome.google.com/webstore/detail/awardwallet/${window.extension_id}`;
const linkForFirefox = `https://addons.mozilla.org/en-US/firefox/addon/awardwallet/`;

export function UniversalManual({ browser }: UniversalUniversalManualProps) {
    const [installationLink, setInstallationLink] = useState(linkForChromium);

    const onReloadClick = () => {
        window.location.reload();
    };

    useEffect(() => {
        if (browser.name === 'Firefox') {
            setInstallationLink(linkForFirefox);
            return;
        }

        setInstallationLink(linkForChromium);
    }, [browser.name]);

    return (
        <ul>
            <ManualStep
                stepNumber={1}
                text={Translator.trans(
                    /** @Desc("Please %highlight_on%click the button%highlight_off% below to install the AwardWallet browser extension:") */ 'extension.install.v2',
                    {
                        highlight_on: '<strong>',
                        highlight_off: '</strong>',
                    },
                )}
                extraContent={
                    <a
                        className={classes.universalInstructionInstallationLink}
                        href={installationLink}
                        target="_blank"
                        rel="noreferrer"
                    >
                        <Icon type="Download" color="primary" />
                        {Translator.trans('extension.button.install')}
                    </a>
                }
            />
            <ManualStep
                stepNumber={2}
                text={Translator.trans('reload-page', {
                    'strong-on': '<strong>',
                    'strong-off': '</strong>',
                }).replace(':', '')}
                extraContent={
                    <PrimaryButton
                        text={Translator.trans('reload')}
                        onClick={onReloadClick}
                        className={{ button: classes.universalInstructionReloadButton }}
                    />
                }
            />
        </ul>
    );
}
