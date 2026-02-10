import { IBrowser, IEngine } from 'ua-parser-js';
import { SafariGrantPermissionManual } from '../SafariGrantPermissionManual/SafariGrantPermissionManual';
import { SafariManual } from '../SafariManual/SafariManual';
import { UniversalManual } from '../UniversalManual/UniversalManual';
import {useExtensionInfo} from "@Bem/block/page/extension-v3-mobile/hooks";
import React, { useEffect, useRef, useState } from 'react';

enum ManualName {
    Universal,
    Safari,
    SafariGrantPermissions,
}

type InstallExtensionV3ManualProps = {
    browser: IBrowser;
    engine: IEngine;
    extensionInfo: ReturnType<typeof useExtensionInfo>;
};

export function InstallExtensionV3Manual({ browser, engine, extensionInfo }: InstallExtensionV3ManualProps) {
    const [isManualShown, setIsManualShown] = useState(false);
    const [shownManualName, setShownManualName] = useState<ManualName | null>(null);
    const isSafari = useRef(browser.name === 'Safari' || false).current;


    useEffect(() => {
        if (extensionInfo) {
            if (extensionInfo.installed && !isSafari) {
                setIsManualShown(false);
                return;
            }

            if (isSafari && extensionInfo.installed && extensionInfo.hasPermissions) {
                setIsManualShown(false);
                return;
            }

            setIsManualShown(true);

            if (engine.name === 'Blink' || browser.name === 'Firefox') {
                setShownManualName(ManualName.Universal);
                return;
            }

            if (!extensionInfo.installed) {
                setShownManualName(ManualName.Safari);
                return;
            }

            if (!extensionInfo.hasPermissions) {
                setShownManualName(ManualName.SafariGrantPermissions);
                return;
            }
            return;
        }

        setIsManualShown(false);
    }, [browser, engine, extensionInfo]);

    if (!isManualShown) {
        return null;
    }
    return (
        <>
            {shownManualName === ManualName.Universal && <UniversalManual browser={browser} />}
            {shownManualName === ManualName.Safari && <SafariManual />}
            {shownManualName === ManualName.SafariGrantPermissions && <SafariGrantPermissionManual />}
        </>
    );
}
