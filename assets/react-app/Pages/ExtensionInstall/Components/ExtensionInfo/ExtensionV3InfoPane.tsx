import { ExtensionInfoPane } from './Components/ExtensionInfoPane';
import { ExtensionV3StatusIcon } from './Components/ExtensionV3StatusIcon';
import { ExtensionV3StatusText } from './Components/ExtensionV3StatusText';
import { IBrowser } from 'ua-parser-js';
import {useExtensionInfo} from "@Bem/block/page/extension-v3-mobile/hooks";
import React, { useRef } from 'react';

type ExtensionInfoPaneProps = {
    extensionInfo: ReturnType<typeof useExtensionInfo>;
    browser: IBrowser;
};

export function ExtensionV3InfoPane({ extensionInfo, browser }: ExtensionInfoPaneProps) {
    const isSafari = useRef(browser.name === 'Safari').current;

    return (
        <ExtensionInfoPane
            statusIcon={
                <ExtensionV3StatusIcon
                    isExtensionInstalled={extensionInfo?.installed}
                    isSafari={isSafari}
                    hasPermission={extensionInfo?.hasPermissions}
                />
            }
            statusText={
                <ExtensionV3StatusText
                    isSafari={isSafari}
                    isExtensionInstalled={extensionInfo?.installed}
                    hasPermission={extensionInfo?.hasPermissions}
                />
            }
        />
    );
}
