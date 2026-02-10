import {createRoot} from "react-dom/client";
import {ExtensionUpdate} from "./extension-update";
import React from "react";
import {ExtensionAutologin} from "./extension-autologin";
import {useExtensionInfo} from "@Bem/block/page/extension-v3-mobile/hooks";
import { Logger } from "./logger";

const domContainer = document.getElementById('container');
// @ts-ignore
const root = createRoot(domContainer)

root.render(React.createElement(() => {
    const params = new URLSearchParams(document.location.search);
    const isAutologin = !!(params.get('autologin'));
    const sessionId = params.get('sessionId') as string;
    const extensionInfo = useExtensionInfo(true);

    return (
        <>
            {isAutologin ? <ExtensionAutologin sessionId={sessionId} extensionInfo={extensionInfo}/> :
                <ExtensionUpdate extensionInfo={extensionInfo} sessionId={sessionId}/>
            }
            <Logger extensionInfo={extensionInfo}/>
        </>
    )
}))
