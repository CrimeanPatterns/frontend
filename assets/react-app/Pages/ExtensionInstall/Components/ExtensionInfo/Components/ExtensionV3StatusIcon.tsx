import { Loader } from '@UI/Icon/Loader/Loader';
import InstalledIcon from '../../../Assets/extension-installed-icon.svg';
import React from 'react';
import UninstalledIcon from '../../../Assets/extension-uninstalled-icon.svg';
import classes from './ExtensionInfoPane.module.scss';

type ExtensionV3StatusIconProps = {
    isExtensionInstalled?: boolean;
    isSafari: boolean;
    hasPermission?: boolean;
};

export function ExtensionV3StatusIcon({
    isExtensionInstalled,
    hasPermission,
    isSafari,
}: ExtensionV3StatusIconProps) {
    if (isExtensionInstalled === undefined) {
        return <Loader color="primary" />;
    }

    if (!isExtensionInstalled) {
        return <UninstalledIcon className={classes.extensionInfoPaneIconStatus} />;
    }

    if (!isSafari) {
        return <InstalledIcon className={classes.extensionInfoPaneIconStatus} />;
    }

    return hasPermission ? (
        <InstalledIcon className={classes.extensionInfoPaneIconStatus} />
    ) : (
        <UninstalledIcon className={classes.extensionInfoPaneIconStatus} />
    );
}
