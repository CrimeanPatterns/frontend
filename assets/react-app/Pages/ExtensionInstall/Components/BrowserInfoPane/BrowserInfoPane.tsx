import { IBrowser, IEngine } from 'ua-parser-js';
import { Translator } from '@Services/Translator';
import BlinkIcon from '../../Assets/blink-engine-icon.svg';
import FireFoxIcon from '../../Assets/firefox-icon.svg';
import React, { useRef } from 'react';
import SafariIcon from '../../Assets/safari-icon.svg';
import classes from './BrowserInfoPane.module.scss';

type BrowserInfoProps = {
    browser: IBrowser;
    engine: IEngine;
};

const BrowserIcon = {
    Blink: <BlinkIcon className={classes.browserInfoPaneIcon} />,
    Firefox: <FireFoxIcon className={classes.browserInfoPaneIcon} />,
    Safari: <SafariIcon className={classes.browserInfoPaneIcon} />,
};

export function BrowserInfoPane({ browser, engine }: BrowserInfoProps) {
    const extractedBrowserVersion = useRef(extractFirstTwoPartsOfVersion(browser.version || '')).current;
    const BrowserIcon = useRef(getBrowserIcon(engine, browser)).current;

    return (
        <div className={classes.browserInfoPane}>
            {BrowserIcon}
            <span className={classes.browserInfoPaneDescription}>
                {Translator.trans('extension.browser-version').replace(':', '')}
            </span>
            <p className={classes.browserInfoPaneBrowserInfo}>
                {`${browser.name}  ${extractedBrowserVersion ? `version ${extractedBrowserVersion}` : ''}`}{' '}
            </p>
        </div>
    );
}

function extractFirstTwoPartsOfVersion(version: string): string | undefined {
    const match = version.match(/^(\d+\.\d+)/);
    return match?.[1] ?? undefined;
}

function getBrowserIcon(engine: IEngine, browser: IBrowser) {
    if (engine.name === 'Blink') {
        return BrowserIcon[engine.name];
    }
    if (browser.name === 'Firefox') {
        return BrowserIcon['Firefox'];
    }
    if (browser.name === 'Safari') {
        return BrowserIcon['Safari'];
    }

    return <></>;
}
