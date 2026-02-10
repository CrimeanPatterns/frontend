import React, {useEffect, useState} from 'react';
import {ExtensionCheckRequirements} from "./extension-check-requirements";
import {DesktopExtensionInterface} from "@awardwallet/extension-client/dist/DesktopExtensionInterface";
import _ from "lodash";
import './extension-v3-mobile.scss';
import {useConfig} from "./hooks/useConfig";
import {useExtensionInfo} from "@Bem/block/page/extension-v3-mobile/hooks";

declare let browser: {
    runtime: {
        sendMessage: <T>(arg0: string, arg1: {
            command: string;
            sessionId: string;
        } | string, arg2?: (response: T) => void) => void;
    };
} | undefined;

let isSubscribedDocumentEvents = false;

const bridge = new DesktopExtensionInterface()
const sendRequestCloseTab = () => {
    browser?.runtime.sendMessage("com.awardwallet.iphone.WebExtension (J3M2LK2HFC)", 'closeTab');
}
export const ExtensionAutologin = ({sessionId, extensionInfo}: {
    sessionId: string,
    extensionInfo: ReturnType<typeof useExtensionInfo>
}) => {
    const [isProcessing, setProcessing] = useState(true);
    const extensionConfig = useConfig<{
        browserExtensionConnectionToken: string,
        browserExtensionSessionId: string,
        DisplayName: string,
        ProviderCode: string,
        Login: string,
    }>(sessionId, extensionInfo);
    const [account, setAccount] = useState<{
        providerCode: string;
        login: string;
        displayName: string;
    }>();

    const onComplete = () => {
        setProcessing(false);
        sendRequestCloseTab();
    };

    useEffect(() => {
        if (_.isObject(extensionConfig)) {
            const {DisplayName, ProviderCode, Login, browserExtensionSessionId, browserExtensionConnectionToken} = extensionConfig;
            setAccount({
                providerCode: ProviderCode,
                displayName: DisplayName,
                login: Login
            });
            bridge.connect(browserExtensionConnectionToken, browserExtensionSessionId, onComplete, onComplete);
        }
    }, [extensionConfig])

    return (
        <>
            <div className="content_wrap">
                <span className="background_image"></span>
                <div className="icon_container">
                    <span className="icon-logo">
                    </span>
                </div>
                {
                    _.isObject(account) &&
                    <>
                        <div className="info_container">
                            <div className="provider_wrap mb-26">
                                <div id="provider_logo" className={"image_logo " + account.providerCode}></div>
                                {isProcessing && <div className="spinner_wrap_absolute white">
                                    <span className="spinner"></span>
                                </div>}
                            </div>
                            <span className="info_text_wrap">
                                {isProcessing &&
                                    <p className="small_text mb-20">Please do not close this page until the auto-login is
                                        complete.</p>}
                                <div className="user_info_wrap">
                                        <span className="icon mr-6">
                                            <svg width="10" height="12" viewBox="0 0 10 12" fill="none"
                                                 xmlns="http://www.w3.org/2000/svg">
                                                <path
                                                    d="M2.25777 2.71564C2.25777 1.278 3.54814 0 4.9997 0C6.45125 0 7.74162 1.278 7.74162 2.71564C7.74162 4.15328 6.45125 5.43128 4.9997 5.43128C3.54814 5.43128 2.25777 4.31321 2.25777 2.71564ZM0 9.10504C0 7.82704 0.645034 6.54934 1.77422 5.59092C2.41926 6.86892 3.70963 7.6674 5 7.6674C6.29037 7.6674 7.41925 6.86862 8.22577 5.59092C9.35466 6.54934 10 7.98698 10 9.10504C10 11.3415 7.74192 11.9803 5.0003 11.9803C2.25838 12.14 0 11.3415 0 9.10504Z"
                                                    fill="#5C6373"/>
                                            </svg>
                                        </span>
                                        <p id="login" className="user_text">{account.login}</p>
                                    </div>
                                </span>
                        </div>
                        <div className="status_block_container" style={{width: '80%'}}>
                        </div>
                    </>
                }
                {
                    _.isObject(account) === false &&
                    (
                        <>
                            <div className="info_container" style={{width: '80%'}}>
                                <div className="provider_wrap mb-26" style={{width: '100%'}}>
                                    <div className={"skeleton-box"} style={{width: "50px", height: "50px"}}/>
                                    <span className="info_text_wrap">
                                        <p className="skeleton-box mb-30" style={{width: "100%", height: "100px"}}></p>
                                    </span>
                                </div>
                            </div>
                            <div className="status_block_container" style={{width: '80%'}}>

                            </div>
                        </>
                    )
                }
                <button className={"home_aw"} onClick={() => {
                    if (!isSubscribedDocumentEvents) {
                        let blurred = false;
                        window.addEventListener("blur", () => {
                            blurred = true;
                        });
                        window.addEventListener("focus", () => {
                            blurred = false;
                        });
                        document.addEventListener("visibilitychange", function (e) {
                            if (blurred) {
                                sendRequestCloseTab();
                            }
                        });
                        isSubscribedDocumentEvents = true;
                    }
                    document.location.href = "awardwallet://";
                }}>
                    <span className={"aw_app_icon"}/>
                    Return to AwardWallet
                    <span className={"external_icon"}/>
                </button>
            </div>
            {!extensionConfig && <ExtensionCheckRequirements extensionInfo={extensionInfo}/>}
        </>
    )
}
