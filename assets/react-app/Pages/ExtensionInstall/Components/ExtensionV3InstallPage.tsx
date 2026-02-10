import { ExtensionInstallPage } from './ExtensionInstallPage/ExtensionInstallPage';
import { ExtensionV3InfoPane } from './ExtensionInfo/ExtensionV3InfoPane';
import { IBrowser, IEngine } from 'ua-parser-js';
import { InstallExtensionV3Manual } from './InstallExtensionManual/InstallExtensionV3Manual';
import {useExtensionInfo} from "@Bem/block/page/extension-v3-mobile/hooks";
import { useUpdateEffect } from '@Utilities/Hooks/UseUpdateEffect';
import React, { useRef } from 'react';
import _ from "lodash";

type ExtensionV3InstallPageProps = {
    browser: IBrowser;
    engine: IEngine;
    navigateBack: () => void;
};

export function ExtensionV3InstallPage({ browser, engine, navigateBack }: ExtensionV3InstallPageProps) {
    const extensionInfo = useExtensionInfo(true);
    const isSafari = useRef(browser.name === 'Safari' || false).current;

    useUpdateEffect(() => {
        if (_.isObject(extensionInfo)) {
            if (extensionInfo.installed && isSafari && extensionInfo.hasPermissions) {
                console.log('v3 installed');
                navigateBack();
                return;
            }

            if (extensionInfo.installed && !isSafari) {
                console.log('v3 installed');
                navigateBack();
                return;
            }
        }
    }, [extensionInfo]);

    return (
        <ExtensionInstallPage
            browser={browser}
            engine={engine}
            extensionInfoPane={<ExtensionV3InfoPane extensionInfo={extensionInfo} browser={browser} />}
            installationManual={
                <InstallExtensionV3Manual browser={browser} engine={engine} extensionInfo={extensionInfo} />
            }
        />
    );
}
