import React, {useEffect, useState} from 'react';
import {ExtensionCheckRequirements} from "./extension-check-requirements";
import {Communicator} from "./communicator";
import _ from "lodash";
import './extension-v3-mobile.scss';
import {useConfig} from "./hooks/useConfig";
import {bridge} from "./bridge";
import {
    CircleError,
    CircleSuccess,
    IconArrowDown, IconArrowUp,
    IconBalanceChanged,
    IconBalanceUnChanged
} from "./assets/icons";
import {useSearchParams} from "./hooks/location";
import {useAccounts} from "./hooks/useAccounts";
import {logger} from "./logger/logger";
import {useExtensionInfo} from "@Bem/block/page/extension-v3-mobile/hooks";

declare let browser: {
    runtime: {
        sendMessage: <T>(arg0: string, arg1: {
            command: string;
            sessionId: string;
        } | string, arg2?: (response: T) => void) => void;
        onMessageExternal: {
            addListener: (arg0: (message: unknown, sender: unknown, sendResponse: (...args: unknown[]) => unknown) => void) => void;
        };
    };
} | undefined;

let isSubscribedDocumentEvents = false;

function clearLatestState() {
    localStorage.clear();
}

async function migrateUpdaterSession(token: string) {
    return await (await fetch('/m/api/account/update2/migrate-events-channel', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json;charset=utf-8'
        },
        body: JSON.stringify({token})
    })).json() as { success: boolean };
}

export const ExtensionUpdate = ({sessionId, extensionInfo}: {
    sessionId: string,
    extensionInfo: ReturnType<typeof useExtensionInfo>
}) => {
    const {searchParams, setSearchParams} = useSearchParams();
    const [isUpdating, setUpdating] = useState(true);
    const [isMigrated, setMigrated] = useState<boolean | undefined>(undefined);
    const historyStartIndex = parseInt(String(searchParams.get('historyStartIndex') || 0), 10);
    const extensionConfig = useConfig<{
        clientInfo: Record<'string', unknown>,
        channel: string,
        event: { token: string }
    }>(sessionId, extensionInfo);
    const {
        accounts,
        currentAccount,
        currentAccountId: currentQueueAccountId,
        setCurrentAccountId: setCurrentQueueAccountId,
        addAccount,
        updateAccount
    } = useAccounts(sessionId);

    useEffect(() => {
        const lastQueueAccountId = searchParams.get('currentQueueAccountId');
        console.log('check restore state', {isMigrated, lastQueueAccountId})
        if (isMigrated === false && lastQueueAccountId) {
            const currentAccount = JSON.parse(localStorage.getItem(sessionId) || '{}');
            console.log('restore state', currentAccount, currentAccount.accountId);

            if (currentAccount && currentAccount.accountId && String(currentAccount.accountId) === lastQueueAccountId) {
                const {accountId, ...rest} = currentAccount;
                addAccount(parseInt(currentAccount.accountId, 10), rest);
                setCurrentQueueAccountId(parseInt(currentAccount.accountId, 10));
                setUpdating(_.isEmpty(currentAccount.error) && _.isEmpty(currentAccount.status));
            }
        }

        if (isMigrated) {
            // remove latest state, update started normally
            clearLatestState();
        }
    }, [isMigrated]);

    // update URL params when current account changes
    useEffect(() => {
        if (currentQueueAccountId > -1) {
            setSearchParams({
                currentQueueAccountId: String(currentQueueAccountId)
            });
        }
    }, [currentQueueAccountId]);

    useEffect(() => {
        (async () => {
            if (_.isObject(extensionConfig)) {
                const {event: {token}} = extensionConfig;
                try {
                    logger.log('migrate updater session start');
                    const response = await migrateUpdaterSession(token)

                    logger.log('migrate updater session success: ' + response.success);
                    setMigrated(response.success);
                } catch (e) {
                    logger.log('migrate updater session error: ' + e);
                }
            }
            if (extensionConfig === false) {
                setMigrated(false);
            }
        })();
    }, [extensionConfig]);

    useEffect(() => {
        if (_.isObject(extensionConfig) && isMigrated) {
            const {clientInfo: config, channel} = extensionConfig;
            let extensionCommunicator: Communicator;
            let connection: { disconnect: any; };
            const tickCallback = (events: any[]): number => {
                let lastIndex = 0;
                events.forEach((event, index) => {
                    const {
                        accountId,
                        type,
                        accountData,
                        increased,
                        change,
                    } = event;

                    switch (type) {
                        case 'extension_v3':
                            console.log('extension_v3', event);
                            const {
                                connectionToken,
                                sessionId,
                                displayName,
                                login,
                                providerCode
                            } = event;
                            searchParams.set('historyStartIndex', String(index + 1))
                            logger.setSessionId(sessionId);
                            setCurrentQueueAccountId(accountId);
                            addAccount(accountId, {
                                displayName,
                                login,
                                providerCode
                            });
                            setUpdating(true);
                            logger.log('start update with extension v3, accountId: ' + accountId);
                            bridge.connect(connectionToken, sessionId, (error) => {
                                logger.log('extension error', error)
                            }, () => {
                                logger.log('extension complete')
                            }).then(result => {
                                connection = result;
                            });
                            break;
                        case 'switch_from_browser':
                            extensionCommunicator.disconnect();
                            setUpdating(false);
                            logger.log('switch_from_browser', event);
                            break;
                        case 'fail':
                            if (connection) {
                                connection.disconnect();
                            }
                            updateAccount(accountId, {
                                error: event.message
                            });
                            break;
                        case 'updated':
                            updateAccount(accountId, {
                                status: type,
                                balance: accountData.Balance
                            });
                            break;
                        case 'changed':
                            updateAccount(accountId, {
                                status: type,
                                increased,
                                change,
                                balance: accountData.Balance,
                                lastChange: accountData.LastChange
                            });
                            break;
                        case 'error':
                            console.log('set error', {event});
                            updateAccount(accountId, {
                                error: event.accountData.Notice?.Message
                            });
                            break;
                        default:
                            console.log('unhandled event', event);
                            break;
                    }
                    lastIndex = index;
                });

                return lastIndex
            }

            logger.log('connect to updater centrifuge, for retrieve accounts events');
            extensionCommunicator = new Communicator({config, historyStartIndex}, tickCallback);

            extensionCommunicator.connect(channel);
        }

    }, [extensionConfig, isMigrated]);

    const isExtensionComplete = searchParams.get('complete');

    useEffect(() => {
        // returns on disappeared awardwallet tab from provider tab
        console.log('check returns on disappeared awardwallet tab from provider tab')
        console.log({isMigrated, isExtensionComplete})
        if (isMigrated === false && isExtensionComplete === String(true)) {
            console.log('setUpdating', false);
            setUpdating(false);
        }
    }, [isExtensionComplete, isMigrated])

    const isError = currentAccount && _.isString(currentAccount.error);

    return (
        <>
            <div className="content_wrap">
                <span className="background_image"></span>
                <div className="icon_container">
                    <span className="icon-logo">
                    </span>
                </div>
                {
                    _.isObject(currentAccount) &&
                    <>
                        <div className="info_container">
                            <div className="provider_wrap mb-26">
                                <div id="provider_logo" className={"image_logo " + currentAccount.providerCode}></div>
                                <div className="spinner_wrap_absolute white">
                                    {isUpdating && <span className="spinner"></span>}
                                    {!isUpdating && !isError && <CircleSuccess/>}
                                    {!isUpdating && isError && <CircleError/>}
                                </div>
                            </div>
                            <span className="info_text_wrap">
                                    {isUpdating && <p id="title"
                                                      className="large_text mb-30">Your {currentAccount.displayName} account
                                        is
                                        being updated</p>}
                                {!isUpdating && <p id="title"
                                                   className="large_text mb-30">Your {currentAccount.displayName} account
                                    update completed</p>}
                                {isUpdating &&
                                    <p className="small_text mb-20">Please do not close this page until the update is
                                        complete.</p>}
                                {currentAccount.error &&
                                    <div className={"error mb-20"}><p className="small_text">{currentAccount.error}</p>
                                    </div>}
                                <div className="user_info_wrap">
                                        <span className="icon mr-6">
                                            <svg width="10" height="12" viewBox="0 0 10 12" fill="none"
                                                 xmlns="http://www.w3.org/2000/svg">
                                                <path
                                                    d="M2.25777 2.71564C2.25777 1.278 3.54814 0 4.9997 0C6.45125 0 7.74162 1.278 7.74162 2.71564C7.74162 4.15328 6.45125 5.43128 4.9997 5.43128C3.54814 5.43128 2.25777 4.31321 2.25777 2.71564ZM0 9.10504C0 7.82704 0.645034 6.54934 1.77422 5.59092C2.41926 6.86892 3.70963 7.6674 5 7.6674C6.29037 7.6674 7.41925 6.86862 8.22577 5.59092C9.35466 6.54934 10 7.98698 10 9.10504C10 11.3415 7.74192 11.9803 5.0003 11.9803C2.25838 12.14 0 11.3415 0 9.10504Z"
                                                    fill="#5C6373"/>
                                            </svg>
                                        </span>
                                        <p id="login" className="user_text">{currentAccount.login}</p>
                                    </div>
                                </span>
                        </div>
                        <div className="status_block_container">
                            {isUpdating && <div className="status_wrap">
                                <div className="spinner_wrap mr-16">
                                    <span className="spinner"></span>
                                </div>
                                <div className="small_text">Collecting the necessary information...</div>
                            </div>}
                            {
                                !isUpdating && _.isString(currentAccount.status) && <>
                                    <div className="divider"></div>
                                    <div className="account_update_result">
                                        <div className="update_result_status_text">
                                    <span className={"update_result_status_icon"}>
                                        {currentAccount.status === 'updated' ? <IconBalanceUnChanged/> :
                                            <IconBalanceChanged/>}
                                    </span>
                                            {currentAccount.status === 'updated' ? 'Balance unchanged' : 'Balance changed'}
                                        </div>
                                        <div className="update_result_value">
                                            <div className="update_result_amount middle_text">
                                                {currentAccount.balance}
                                            </div>
                                            {
                                                _.isNumber(currentAccount.change) &&
                                                <div className="update_result_amount_changed">{currentAccount.lastChange}
                                                    <span className="update_result_amount_changed_icon">
                                                {currentAccount.change < 0 ? <IconArrowDown/> : <IconArrowUp/>}
                                            </span>
                                                </div>
                                            }
                                        </div>
                                    </div>
                                </>
                            }
                        </div>
                    </>
                }
                {
                    _.isObject(currentAccount) === false &&
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
                                <div className="status_wrap">
                                    <p className="skeleton-box mb-30" style={{width: "100%", height: "30px"}}></p>
                                </div>
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
                            if (blurred && !isUpdating) {
                                logger.log('webpage blurred and updating is complete, close webpage');
                                browser?.runtime.sendMessage("com.awardwallet.iphone.WebExtension (J3M2LK2HFC)", 'closeTab');
                            } else if (blurred) {
                                logger.log('webpage blurred');
                            }
                        });
                        isSubscribedDocumentEvents = true;
                    }
                    logger.log('return to awardwallet pressed');
                    let redirectUrl = "awardwallet://";
                    const accountsCount = Object.keys(accounts).length;
                    if (accountsCount == 1) {
                        redirectUrl += encodeURIComponent(document.location.origin + '/m/account/details/a' + currentQueueAccountId)
                    }
                    document.location.href = redirectUrl;
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
