import { AppSettingsProvider } from '@Root/Contexts/AppSettingsContext';
import { ExtensionV3InstallPage } from './Components/ExtensionV3InstallPage';
import { Router } from '@Services/Router';
import { getUrlPathAndQuery, isPathSafe } from '@Utilities/UrlUtils';
import { hideGlobalLoader } from '@Bem/ts/service/global-loader';
import { useSearchParams } from '@Utilities/Hooks/UseSearchParams/UseSearchParams';
import { useUAParser } from '@Utilities/Hooks/UseUAParser';
import React, { useEffect } from 'react';

export default function ExtensionInstallPageWrapper() {
    useEffect(() => {
        hideGlobalLoader();
    }, []);
    return (
        <AppSettingsProvider>
            <ExtensionInstallPage />
        </AppSettingsProvider>
    );
}

const backToParamName = 'BackTo';

function ExtensionInstallPage() {
    const { getParam } = useSearchParams();

    const backTo = getParam<null | string, string>(backToParamName, null);
    const { browser, engine } = useUAParser();

    function redirectWithDelay(path: string) {
        setTimeout(() => {
            document.location.href = path;
        }, 2000);
    }

    function navigateBack() {
        if (!backTo) {
            redirectWithDelay(Router.generate('aw_account_list'));
            return;
        }
        const decodedUrl = decodeURIComponent(backTo).trim();
        const isSafePath = isPathSafe(getUrlPathAndQuery(decodedUrl));
        if (isSafePath) {
            const urlAnchor = document.createElement('a');
            urlAnchor.href = decodedUrl;
            const safePath = urlAnchor.pathname + urlAnchor.search + urlAnchor.hash;
            redirectWithDelay(safePath);
        }
    }

    return (
        <ExtensionV3InstallPage browser={browser} engine={engine} navigateBack={navigateBack} />
    );
}
