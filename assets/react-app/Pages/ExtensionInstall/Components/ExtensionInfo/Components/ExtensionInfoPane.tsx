import { Translator } from '@Services/Translator';
import React, { ReactNode } from 'react';
import classes from './ExtensionInfoPane.module.scss';

type ExtensionInfoPaneProps = {
    statusIcon: ReactNode;
    statusText: ReactNode;
};

export function ExtensionInfoPane({  statusIcon, statusText }: ExtensionInfoPaneProps) {
    return (
        <div className={classes.extensionInfoPane}>
            <div className={classes.extensionInfoPaneIconContainer}>{statusIcon}</div>
            <span className={classes.extensionInfoPaneDescription}>
                {Translator.trans(/** @Desc("AwardWallet Extension %version%") */ 'extension.version.name', {
                    version: 'V3',
                })}
            </span>
            {statusText}
        </div>
    );
}
