import {checkPermissions} from './permissions';
import {bridge} from "@Bem/block/page/extension-v3-mobile/bridge";
import {useEffect, useRef, useState} from "react";
import {ExtensionInfo} from "@awardwallet/extension-client";

type ExtensionInfoResponse = Partial<ReturnType<ExtensionInfo["toObject"]>> & { installed: boolean };
type ExtensionInfoReturnType = ExtensionInfoResponse & { hasPermissions: boolean };

export const useExtensionInfo = (waitPermissions: boolean = false): ExtensionInfoReturnType | undefined => {
    const timeoutId = useRef<ReturnType<typeof setTimeout>>();
    const [extensionInfo, setExtensionInfo] = useState<ExtensionInfoResponse | undefined>(undefined);

    const getExtensionInfo = () => {
        clearTimeout(timeoutId.current)
        timeoutId.current = setTimeout(async () => {
            const response = await bridge.getExtensionInfo();

            setExtensionInfo(response);

            if (response.installed) {
                if (!waitPermissions || (waitPermissions && checkPermissions(response.permissions?.origins))) {
                    clearTimeout(timeoutId.current);
                }
            } else {
                getExtensionInfo();
            }
        }, 3000);
    };

    useEffect(() => {
        getExtensionInfo();
        return () => {
            clearTimeout(timeoutId.current);
        }
    }, [])

    if (extensionInfo) {
        return {...extensionInfo, hasPermissions: checkPermissions(extensionInfo?.permissions?.origins)}
    }

    return undefined;
}
