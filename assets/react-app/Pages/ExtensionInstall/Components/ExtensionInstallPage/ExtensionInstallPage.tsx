import { BrowserInfoPane } from '../BrowserInfoPane/BrowserInfoPane';
import { IBrowser, IEngine } from 'ua-parser-js';
import { Translator } from '@Services/Translator';
import React, { ReactNode } from 'react';
import classes from './ExtensionInstallPage.module.scss';

type ExtensionInstallPageProps = {
    engine: IEngine;
    browser: IBrowser;
    extensionInfoPane: ReactNode;
    installationManual: ReactNode;
};

export function ExtensionInstallPage({
    engine,
    browser,
    extensionInfoPane,
    installationManual,
}: ExtensionInstallPageProps) {
    return (
        <div className={classes.extensionInstallPage}>
            <section className={classes.extensionInstallPageHero}>
                <h1 className={classes.extensionInstallPageTitle}>{Translator.trans('extension.required')}</h1>
                <div className={classes.extensionInstallPageInfoBlock}>
                    <p className={classes.extensionInstallPageDescription}>
                        {Translator.trans(
                            /** @Desc("You are about to install the AwardWallet browser extension") */ 'extension.install.about.install',
                        )}
                    </p>
                    <BrowserInfoPane browser={browser} engine={engine} />
                    {extensionInfoPane}
                </div>
            </section>
            <section>{installationManual}</section>
        </div>
    );
}
