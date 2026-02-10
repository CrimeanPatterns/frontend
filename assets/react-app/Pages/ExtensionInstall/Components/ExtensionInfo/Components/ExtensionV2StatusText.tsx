import { Translator } from '@Services/Translator';
import React from 'react';
import classNames from 'classnames';
import classes from './ExtensionInfoPane.module.scss';

type ExtensionV2StatusTextProps = {
    isExtensionInstalled?: boolean;
    isSafari: boolean;
};

export function ExtensionV2StatusText({ isExtensionInstalled, isSafari }: ExtensionV2StatusTextProps) {
    if (isExtensionInstalled === undefined) {
        return null;
    }

    return isExtensionInstalled ? (
        <p className={classNames(classes.extensionInfoPaneInfo, classes.extensionInfoPaneInfoInstalled)}>
            {Translator.trans(/** @Desc("Installed") */ 'extension.installed')}
        </p>
    ) : (
        <p className={classNames(classes.extensionInfoPaneInfo, classes.extensionInfoPaneInfoUninstalled)}>
            {isSafari &&
                Translator.trans(/** @Desc("Not Installed or not updated") */ 'extension.not.installed.or.updated')}
            {!isSafari && Translator.trans('extension.not.installed')}
        </p>
    );
}
