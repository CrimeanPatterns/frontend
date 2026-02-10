import { Translator } from '@Services/Translator';
import React from 'react';
import classNames from 'classnames';
import classes from './ExtensionInfoPane.module.scss';

type ExtensionV3StatusTextProps = {
    isExtensionInstalled?: boolean;
    isSafari: boolean;
    hasPermission?: boolean;
};

export function ExtensionV3StatusText({
    isExtensionInstalled,
    isSafari,
    hasPermission,
}: ExtensionV3StatusTextProps) {
    if (isExtensionInstalled === undefined) {
        return null;
    }

    if (!isExtensionInstalled) {
        return (
            <p className={classNames(classes.extensionInfoPaneInfo, classes.extensionInfoPaneInfoUninstalled)}>
                {isSafari &&
                    Translator.trans(/** @Desc("Not Installed or not updated") */ 'extension.not.installed.or.updated')}
                {!isSafari && Translator.trans('extension.not.installed')}
            </p>
        );
    }

    if (!isSafari) {
        return (
            <p className={classNames(classes.extensionInfoPaneInfo, classes.extensionInfoPaneInfoInstalled)}>
                {Translator.trans(/** @Desc("Installed") */ 'extension.installed')}
            </p>
        );
    }

    return hasPermission ? (
        <p className={classNames(classes.extensionInfoPaneInfo, classes.extensionInfoPaneInfoInstalled)}>
            {Translator.trans(/** @Desc("Installed") */ 'extension.installed')}
        </p>
    ) : (
        <p className={classNames(classes.extensionInfoPaneInfo, classes.extensionInfoPaneInfoUninstalled)}>
            {Translator.trans(/** @Desc("Installed, not enabled") */ 'extension.installed.not.enabled')}
        </p>
    );
}
