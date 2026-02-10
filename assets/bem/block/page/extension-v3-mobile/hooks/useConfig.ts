import {useEffect, useState} from "react";
import _ from "lodash";
import {useExtensionPermissions} from "./permissions";
import {useExtensionInfo} from "@Bem/block/page/extension-v3-mobile/hooks/index";
import {ExtensionInfo} from "@awardwallet/extension-client";
import {logger} from "@Bem/block/page/extension-v3-mobile/logger/logger";

declare let browser: {
    runtime: {
        sendMessage: <T>(arg0: string, arg1: {
            command: 'getConfig';
            sessionId: string;
        } | {
            command: 'saveExtensionInfo';
            extensionInfo: string;
        } | string, arg2?: (response: T) => void) => void;
    };
} | undefined;

function getConfig(sessionId: string) {
    return new Promise<{ clientInfo: any; channel: string; sessionToken: string; } | boolean>(((resolve) => {
        browser?.runtime.sendMessage<{ [key: string]: unknown }>("com.awardwallet.iphone.WebExtension (J3M2LK2HFC)", {
            command: 'getConfig',
            sessionId,
        }, function (response) {
            console.log('getConfig', response);
            // @ts-ignore
            const config = JSON.parse(response.config);

            if (typeof config === 'object') {
                return resolve(config);
            }

            return resolve(false);
        });
    }));
}

function saveExtensionInfo(extensionInfo: ReturnType<typeof useExtensionInfo>) {
    browser?.runtime.sendMessage<{ [key: string]: unknown }>("com.awardwallet.iphone.WebExtension (J3M2LK2HFC)", {
        command: 'saveExtensionInfo',
        extensionInfo: JSON.stringify(extensionInfo),
    }, function (response) {
        console.log('saveExtensionInfo', response);
    });
}

export const useConfig = <T>(sessionId: string, extensionInfo: ReturnType<typeof useExtensionInfo>): T | undefined | boolean => {
    const [extensionConfig, setExtensionConfig] = useState<any>(undefined);

    useEffect(() => {
        if (extensionInfo) {
            if (extensionInfo.installed) {
                saveExtensionInfo(extensionInfo);
            }
            (async (extensionInfo) => {
                const {hasPermissions} = extensionInfo;
                if (hasPermissions) {
                    if (sessionId) {
                        const extensionResponse = await getConfig(sessionId);

                        if (_.isObject(extensionResponse)) {
                            logger.log('set extension config');
                            setExtensionConfig(extensionResponse);
                        } else {
                            logger.log('no extension config by sessionId: ' + sessionId);
                        }
                    }
                }
            })(extensionInfo);
        }
    }, [extensionInfo])


    return extensionConfig;
}