import React, {useCallback, useEffect, useRef} from "react";
import './extension-v3-mobile.scss';
import './extension-check-requirements.scss';
import './icons.scss';
import {checkPermissions} from "./hooks/permissions";
import jQuery from 'jquery';
import {useExtensionInfo} from "@Bem/block/page/extension-v3-mobile/hooks";

function detectiOS18() {
    const ua = navigator.userAgent;

    // Check if it's an iOS device
    const isiOS = /iPad|iPhone|iPod/.test(ua);

    if (isiOS) {
        // Extract the iOS version
        const versionMatch = ua.match(/OS (\d+)_(\d+)_?(\d+)?/);

        if (versionMatch) {
            // @ts-ignore
            const majorVersion = parseInt(versionMatch[1], 10);
            return majorVersion >= 18;
        }
    }

    return false;
}

const isIOS18 = detectiOS18();

export const PageEnableExtension = () => {
    return <>
        <div className="item_wrap">
            <div className="item_number_wrap">
                <p className="item_number">1</p>
            </div>
            <div className="item_content">
                <p className="modal_text mb-11">Tap <span className={isIOS18 ? "extension_button_ios18" : "text_image"}></span> in the address bar
                    on this page, then choose <b className="modal_text_bold">Manage Extensions.</b></p>
                <p className="modal_small_text mb-18">If you're using an iPad, tap <span
                    className="extension_button"></span> in the address bar.</p>
                <span className={`browser_extension ${isIOS18 ? 'ios18' : ''}`}></span>
            </div>
        </div>
        <div className="item_wrap">
            <div className="item_number_wrap">
                <p className="item_number">2</p>
            </div>
            <div className="item_content">
                <p className="modal_text mb-20">Turn on <b className="modal_text_bold">AwardWallet</b>, then
                    tap <b className="modal_text_bold">Done.</b></p>
                <span className="manage_extension"></span>
            </div>
        </div>
    </>
}

export const PageRequestPermissions = () => {
    return <>
        <div className="item_wrap">
            <div className="item_number_wrap">
                <p className="item_number">3</p>
            </div>
            <div className="item_content">
                <p className="modal_text mb-7">Tap <span className={isIOS18 ? "extension_button_ios18" : "text_image"}></span> in the address bar on this page,
                    then choose <b className="modal_text_bold">AwardWallet Extension</b>.</p>
                <p className="modal_small_text mb-18">If you're using an iPad, tap <span
                    className="extension_button"></span> in the address bar.</p>
                <span className={`aw_extension ${isIOS18 ? 'ios18' : ''}`}></span>
            </div>
        </div>
        <div className="item_wrap">
            <div className="item_number_wrap">
                <p className="item_number">4</p>
            </div>
            <div className="item_content">
                <p className="modal_text mb-20">In the first pop-up, select <b className="modal_text_bold">Always
                    Allow</b>; if you prefer to single option, activate <b className="modal_text_bold">Allow
                    for One Day</b>.</p>
                <div className="access_popup_wrap">
                    <span className="access_popup_1"></span>
                    <span className="access_popup_2"></span>
                </div>
            </div>
        </div>
        <div className="item_wrap">
            <div className="item_number_wrap">
                <p className="item_number">5</p>
            </div>
            <div className="item_content">
                <p className="modal_text mb-20">In the second pop-up, select <b className={"modal_text_bold"}
                                                                                style={{color: '#F34141'}}>only</b> <b
                    className="modal_text_bold">Always
                    Allow on Every Website</b> to correct work of extension</p>
                <div className="access_popup_wrap">
                    <span className="access_popup_every_websites_1"></span>
                    <span className="access_popup_every_websites_2"></span>
                </div>
            </div>
        </div>
    </>
}

const IconArrowRight = () => {
    return (
        <span className="icon_arrow_right">
            <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12"
                 viewBox="0 0 12 12" fill="none">
                <g opacity="0.4">
                    <path
                        d="M10.3535 5.6465L6.8535 2.1465C6.65825 1.95125 6.34175 1.95125 6.1465 2.1465C5.95125 2.34175 5.95125 2.65825 6.1465 2.8535L8.793 5.5H2C1.724 5.5 1.5 5.724 1.5 6C1.5 6.276 1.724 6.5 2 6.5H8.793L6.1465 9.1465C5.95125 9.34175 5.95125 9.65825 6.1465 9.8535C6.24425 9.95125 6.372 10 6.5 10C6.628 10 6.75575 9.95125 6.8535 9.8535L10.3535 6.3535C10.5487 6.15825 10.5487 5.84175 10.3535 5.6465Z"
                        fill="#313745"/>
                </g>
            </svg>
        </span>
    )
}

const PageSettings = () => {
    return <>
        <div className="item_wrap">
            <div className="item_number_wrap">
                <p className="item_number">1</p>
            </div>
            <div className="item_content">
                <p className="modal_text">Open your device’s Settings</p>
            </div>
        </div>
        <div className="flex">
            <span className="settings_step_1"></span>
        </div>
        <div className="item_wrap">
            <p className="modal_step_hint">Tap on Safari <IconArrowRight/> Tap on Extensions <IconArrowRight/> Tap
                on AwardWallet to go to the detail page for the extension</p>
        </div>
        <div className="item_wrap">
            <div className="item_number_wrap">
                <p className="item_number">2</p>
            </div>
            <div className="item_content">
                <p className="modal_text">Extension Details</p>
            </div>
        </div>
        <div className="flex">
            <span className="settings_step_2"></span>
        </div>
        <div className="item_wrap">
            <ul className="steps_to_enable">
                <li>
                    <div>&nbsp;</div>
                    <p>Turn on the AwardWallet toggle</p>
                </li>
                <li>
                    <div>&nbsp;</div>
                    <p>Configure permissions at the bottom of the screen by tapping on Awardwallet.com and Other
                        Websites </p>
                </li>
                <li>
                    <div>&nbsp;</div>
                    <p>Then tap on Allow so that the extension can automatically run;</p>
                </li>
            </ul>
        </div>
    </>
}


export const ExtensionCheckRequirements = ({extensionInfo}: { extensionInfo: ReturnType<typeof useExtensionInfo> }) => {
    const scrollable = useRef<HTMLDivElement>(null);
    const timeoutId = useRef<ReturnType<typeof setTimeout>>();

    const runAnimation = useCallback(() => {
        clearTimeout(timeoutId.current);
        timeoutId.current = setTimeout(() => {
            if (scrollable.current) {
                const scrollView = jQuery(scrollable.current);
                const {scrollHeight, clientHeight, scrollTop} = scrollable.current;
                const totalDuration = 25;
                const totalDistance = scrollHeight - clientHeight;
                const distance = totalDistance - scrollTop;
                const duration = (distance * totalDuration) / totalDistance;

                scrollView.animate({scrollTop: totalDistance}, duration * 1000, 'linear', function () {
                    clearTimeout(timeoutId.current);
                    timeoutId.current = setTimeout(() => {
                        scrollView.animate({scrollTop: 0}, duration * 1000, 'linear', runAnimation);
                    }, 3000);
                });
            }
        }, 15000);
    }, [scrollable.current]);

    useEffect(() => {
        jQuery(document).bind('scroll mousedown wheel DOMMouseScroll mousewheel keyup touchmove', function (e) {
            // @ts-ignore
            jQuery(scrollable.current).stop();
            runAnimation();
        });

        runAnimation();

    }, []);

    if (!extensionInfo) {
        return null;
    }

    const {permissions, hasPermissions} = extensionInfo;

    if (!hasPermissions) {
        const hasHostPermissions = permissions?.origins.includes(`*://*.${document.location.hostname}/*`);
        const hasOtherWebsitesPermission = hasHostPermissions && checkPermissions(permissions?.origins);

        return (
            <div className="modal_wrap">
                <span className="backdrop"></span>
                <div className="modal">
                    <div className="modal_content_header"/>
                    <div className="modal_content">
                        <div className="scrollable" ref={scrollable}>
                            <div className="modal_title_wrap">
                                <p className="large_text">Please, set up AwardWallet extension to continue</p>
                            </div>
                            {
                                (!hasOtherWebsitesPermission && hasHostPermissions) ?
                                    <PageSettings/> :
                                    <>
                                        <PageEnableExtension/>
                                        <PageRequestPermissions/>
                                    </>
                            }
                        </div>
                    </div>
                </div>
            </div>
        )
    }

    return null;
}
