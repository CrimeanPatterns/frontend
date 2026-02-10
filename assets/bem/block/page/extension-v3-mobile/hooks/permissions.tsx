import React, {useContext, useEffect, useRef, useState} from "react";
import _ from "lodash";
import {bridge} from "@Bem/block/page/extension-v3-mobile/bridge";

type ExtensionPermissions = {
    permissions: string[] | undefined,
    origins: string[] | undefined,
    hasPermissions: boolean,
    checkPermissions: (origins: string[]) => boolean
};

export const ExtensionPermissionsContext = React.createContext<ExtensionPermissions>({
    permissions: undefined,
    origins: undefined,
    hasPermissions: false,
    checkPermissions: (origins: string[]) => false,
});

type Permissions = Omit<ExtensionPermissions, 'hasPermissions' | 'checkPermissions'>;

export const checkPermissions = (origins: ExtensionPermissions['origins']) => {
    return (_.isArray(origins) && origins.some(origin => ["*://*/*", "https://*/*", "<all_urls>"].includes(origin)))
}

export const ExtensionPermissionsProvider: React.FunctionComponent<React.PropsWithChildren> = ({children}) => {
    const timeoutId = useRef<ReturnType<typeof setTimeout>>();
    const [permissions, setPermissions] = useState<Permissions>({
        permissions: undefined,
        origins: undefined
    });
    const checkRequirements = () => {
        clearTimeout(timeoutId.current)
        timeoutId.current = setTimeout(async () => {
            const response = await bridge.getExtensionInfo();
            let permissions: Permissions = {
                permissions: [],
                origins: [],
            }
            console.log('[ExtensionPermissionsProvider] checkRequirements', response);

            if (_.isObject(response.permissions)) {
                permissions = response.permissions;
            }

            setPermissions(permissions);

            if (!checkPermissions(permissions.origins)) {
                checkRequirements();
            }
        }, 3000);
    };

    useEffect(() => {
        checkRequirements();

        return () => {
            clearTimeout(timeoutId.current);
        }
    }, []);

    return <ExtensionPermissionsContext.Provider value={{
        ...permissions,
        checkPermissions,
        hasPermissions: checkPermissions(permissions.origins)
    }}>{children}</ExtensionPermissionsContext.Provider>
}

export const useExtensionPermissions = () => {
    const {permissions, origins, hasPermissions, checkPermissions} = useContext(ExtensionPermissionsContext);

    return {permissions, origins, hasPermissions, checkPermissions};
}
