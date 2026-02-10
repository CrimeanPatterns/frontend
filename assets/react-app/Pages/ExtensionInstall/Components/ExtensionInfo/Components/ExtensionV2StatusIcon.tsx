import { Loader } from '@UI/Icon/Loader/Loader';
import InstalledIcon from '../../../Assets/extension-installed-icon.svg';
import React from 'react';
import UninstalledIcon from '../../../Assets/extension-uninstalled-icon.svg';
import classes from './ExtensionInfoPane.module.scss';

type ExtensionV2StatusIconProps = {
    isExtensionInstalled?: boolean;
};

export function ExtensionV2StatusIcon({ isExtensionInstalled }: ExtensionV2StatusIconProps) {
    if (isExtensionInstalled === undefined) {
        return <Loader color="primary" />;
    }
    return isExtensionInstalled ? (
        <InstalledIcon className={classes.extensionInfoPaneIconStatus} />
    ) : (
        <UninstalledIcon className={classes.extensionInfoPaneIconStatus} />
    );
}
