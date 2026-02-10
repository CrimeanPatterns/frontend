/* jshint esversion: 6 */
// eslint-disable-next-line no-redeclare
/* global console, Routing, jQuery, Translator */

(function() {

    if ('undefined' === typeof Routing) {
        var Routing = {
            generate: function(route, params) {
                switch (route) {
                    case 'aw_push_safari_get_user_token':
                        return '/safari/get-user-token';
                    case 'aw_push_safari_save_token':
                        return '/safari/save-device-token/' + params.deviceToken;
                    case 'aw_push_chrome_subscribe':
                        return '/webPush/chrome/subscribe';
                    case 'aw_notifications_check_browser':
                        return '/user/notifications/check/browser?v=' + params.v;
                    case 'aw_mobile_device_unsubscribe':
                        return '/user/notifications/disable/' + params.code;
                    default:
                        throw 'Route is missing';
                }
            }
        };
    }

    const getWebPush = function() {
        function isIntegerInString(str) {
            return /^\d+$/.test(str);
        }

        const browser = (() => {
            let isChrome = -1 !== navigator.userAgent.indexOf('Chrome');
            let isFirefox = -1 !== navigator.userAgent.indexOf('Firefox');
            let isSafari = -1 !== navigator.userAgent.indexOf('Safari') && !isChrome;

            return {
                name: isSafari ? 'safari' : 'chrome',
                isChrome,
                isSafari,
                isFirefox
            };
        })();

        var webPush = {
            VAPID_PUBLIC_KEY_UINT: null,
            userId: null,
            isStaff: false,
            retries: 0,
            Id: null,
            safariUserToken: null,
            page: null,
            swFileVersion: 7, // + version in blog footer file !SW
            showPopupTimeout: 10000,
            timeoutDismissInDays: 180,
            ignoreDismiss: false,
            dontShowPopup: false,
            forceSave: false,

            /**
             *
             * @param vapidPublicKey string
             * @param webPushId string
             * @param userId int
             * @param isStaff bool
             * @param options object
             */
            init: function (vapidPublicKey, webPushId, userId, isStaff, options) {
                if (typeof userId !== 'number') {
                    userId = isIntegerInString(userId) ? Number(userId) : null;
                }

                webPush.userId = userId;
                webPush.isStaff = isStaff;
                webPush.VAPID_PUBLIC_KEY_UINT = webPush.urlBase64ToUint8Array(vapidPublicKey);
                webPush.Id = webPushId;
                webPush.page = webPush.getPageLocation();
                webPush.setOptions(options);

                /*
                if (!isStaff && !(Object.prototype.hasOwnProperty.call(webPush, 'ignoreStaff') && true === webPush.ignoreStaff)) {
                    console.log('PushManager disabled !staff');
                    return;
                }
                */

                if (webPush.isDisabled()) {
                    console.log('[SW] WebPush is disabled');
                    return;
                }

                if (webPush.isDismiss() && !(Object.prototype.hasOwnProperty.call(webPush, 'ignoreDismiss') && true === webPush.ignoreDismiss)) {
                    return (console.log('PushManager dismissed'));
                }

                browser.isSafari
                    ? webPush.initSafari()
                    : webPush.initChrome();
            },

            loadUserId: function () {
                const tokenUser = localStorage.getItem('web_token_user');

                // there was a stored bug when 'undefined' (string)  was stored as userid
                // leading to a bug when anon/ user was not able to resubscribe
                if (
                    (null === tokenUser)
                    || (typeof tokenUser === 'undefined')
                    || (tokenUser === 'undefined')
                    || (tokenUser === 'null')
                ) {
                    return null;
                }

                return Number(tokenUser);
            },

            isDisabled: function() {
                if ('undefined' === typeof Notification || 'denied' === Notification.permission) {
                    console.warn('[SW] Notifications denied');
                    return true;
                }

                if (!('serviceWorker' in navigator)) {
                    console.log('[SW] isn`t supported');
                    return true;
                }

                if ('http:' === document.location.protocol) {
                    console.log('[SW] http, will not subscribe to chrome pushes');
                    return true;
                }

                if (browser.isChrome) {
                    if (!('PushManager' in window)) {
                        console.log('[SW] PushManager isn`t supported');
                        return true;
                    }

                    if (!('showNotification' in ServiceWorkerRegistration.prototype)) {
                        console.warn('[SW] showNotification not supported.');
                        return true;
                    }
                }

                const userId = webPush.loadUserId();

                if (
                    null !== userId
                    && (userId !== webPush.userId)
                ) {
                    console.log('[SW] not valid user');
                    return true;
                }

                return false;
            },

            clearDismiss: function() {
                localStorage.removeItem('push_dismiss_blog');
                localStorage.removeItem('push_dismiss_date');
                webPush.removeCookie('push_dismiss_blog');
                webPush.removeCookie('push_dismiss_date');
            },

            isDismiss: function() {
                const checkDismiss = function (key) {
                    let expireDate = localStorage.getItem(key);
                    if (expireDate) {
                        if (parseInt(expireDate) > Date.now()) {
                            console.log('[SW] localStorage dismissed');
                            return true;
                        }
                        localStorage.removeItem(key);
                    }

                    expireDate = webPush.getCookie(key);
                    if (expireDate) {
                        if (parseInt(expireDate) > Date.now()) {
                            console.log('[SW] cookie dismissed');
                            return true;
                        }
                        webPush.removeCookie(key);
                    }

                    return false;
                };

                if ('blog' === webPush.page) {
                    return checkDismiss('push_dismiss_blog');
                }

                return checkDismiss('push_dismiss_date');
            },

            initChrome: function () {
                navigator.serviceWorker.register('/service-worker.js?v=' + webPush.swFileVersion)
                    .then(function (sw) {
                        console.log('[SW] Service worker registered');
                        sw.update();

                        webPush.initSubscription();
                    }, function (e) {
                        console.error('[SW] Oups...', e);
                    });
            },

            initSafari: function () {
                console.log('[Safari] init');
                let pResult = window.safari.pushNotification.permission(webPush.Id);
                console.log('[Safari] current permissions: ' + JSON.stringify(pResult));

                if (pResult.permission === 'default') {
                    if (webPush.safariUserToken === null) {
                        jQuery.ajax({
                            url: Routing.generate('aw_push_safari_get_user_token'),
                            success: function (userToken) {
                                if (userToken === false) {
                                    console.log('[Safari] impersonated, will not subscribe');
                                    return;
                                }
                                webPush.safariUserToken = userToken;
                                webPush.initSafari();
                            }
                        });
                        return;
                    }

                    if (webPush.safariUserToken === false) {
                        console.log('[Safari] impersonated, will not subscribe');
                        return;
                    }

                    if (webPush.ignoreDismiss && !webPush.dontShowPopup) {
                        webPush.enableNotifications();

                    } else if (!webPush.dontShowPopup) {
                        setTimeout(webPush.showPushPopup, webPush.showPopupTimeout);
                    }

                    return;
                }

                if (pResult.permission === 'granted'
                    && (
                        webPush.loadUserId() !== webPush.userId
                        || localStorage.getItem('web_token_day') != webPush.getDay()
                        || webPush.forceSave
                    )
                ) {
                    // resend token, if it was not sent
                    webPush.saveSafariToken(pResult.deviceToken);
                }
            },

            initSubscription: function () {
                navigator.serviceWorker.ready.then(function (serviceWorkerRegistration) {
                    // Do we already have a push message subscription?
                    serviceWorkerRegistration.pushManager.getSubscription()
                        .then(function (subscription) {

                            const existSubscription = () => {
                                console.log("[SW] We already have subscription.");

                                if (webPush.isVAPIDSubscription(subscription)) {
                                    if (webPush.isValidVAPIDSubscription(subscription)) {
                                        console.log("[SW] Existing subscription is valid VAPID.");
                                    } else {
                                        console.log("[SW] Existing subscription is old VAPID.");
                                    }
                                } else {
                                    console.log("[SW] Existing subscription is non-VAPID.");
                                }

                                if (
                                    !webPush.isValidVAPIDSubscription(subscription) &&
                                    (0 === webPush.retries)
                                ) {
                                    webPush.retries++;
                                    console.log("[SW] invalid subscription, resubscribing...");
                                    webPush.resubscribe(subscription);

                                    return;
                                }

                                if (!localStorage.getItem("web_token_resubscribed")) {
                                    if (webPush.retries > 0) {
                                        console.log('[SW] cycle detected');
                                        return;
                                    }
                                    console.log('[SW] resubscribe requested');
                                    webPush.retries++;
                                    webPush.resubscribe(subscription);
                                } else {
                                    console.log('[SW] pre subscribe', webPush.retries);
                                    if (webPush.loadUserId() !== webPush.userId
                                        || localStorage.getItem("web_token_day") != webPush.getDay()) {
                                        console.log('[SW] subscribe chrome');
                                        webPush.subscribeChrome(serviceWorkerRegistration, subscription);
                                    } else if (0 === webPush.retries) {
                                        const doneCallback = (response) => {
                                            if (null === response.found) {
                                                return;
                                            }

                                            if (false === response.found) {
                                                console.log('[SW] No detected RE subscription');
                                                setTimeout(() => webPush.subscribeChrome(serviceWorkerRegistration, subscription), 1000);
                                            }
                                        };
                                        if (undefined !== webPush.checkBrowserResponse) {
                                            doneCallback(webPush.checkBrowserResponse);
                                        } else {
                                            jQuery.ajax(Routing.generate('aw_notifications_check_browser', { v: 2 }))
                                                .done(doneCallback);
                                        }
                                    }
                                }
                            };

                            const popSubscription = () => {
                                console.log("[SW] No subscription detected, subscribing...", webPush.ignoreDismiss, webPush.dontShowPopup);
                                if ('granted' === Notification.permission && null !== subscription) {
                                    console.log('[SW] popup resubscribing');
                                    existSubscription();

                                } else if ('granted' === Notification.permission || (webPush.ignoreDismiss && !webPush.dontShowPopup)) {
                                    console.log('[SW] pop enableNotifications');
                                    webPush.enableNotifications({serviceWorkerRegistration});

                                } else if (!webPush.dontShowPopup) {
                                    console.log('[SW] set show popup');
                                    setTimeout(() => webPush.showPushPopup({serviceWorkerRegistration}), webPush.showPopupTimeout);
                                }
                            };

                            if (!subscription) {
                                popSubscription();
                            } else {
                                const isCheckRecently = webPush.getCookie('notificationsCheckBrowser');
                                if ('true' !== isCheckRecently) {
                                    webPush.setCookie('notificationsCheckBrowser', 'true', 10800, '/');

                                    if (webPush.userId === null) {
                                        console.log('[SW] anonymous user');
                                        existSubscription();
                                    } else {
                                        jQuery.ajax(Routing.generate('aw_notifications_check_browser', { v: 1 }))
                                            .done(function(response) {
                                                webPush.checkBrowserResponse = response;
                                                if (null === response.found) {
                                                    return;
                                                }

                                                if (false === response.found) {
                                                    console.log('[SW] No detected browser subscription');
                                                    popSubscription();
                                                } else {
                                                    console.log('[SW] exists subscription');
                                                    existSubscription();
                                                }
                                            });
                                    }
                                }
                            }
                        })
                        .catch(function (err) {
                            console.warn('[SW] Error during getSubscription()', err);
                        });
                });
            },

            resubscribe: function (subscription) {
                subscription.unsubscribe().then(function (event) {
                    console.log('[SW] unsubscribed', event);
                    setTimeout(webPush.initChrome, 3000);
                }).catch(function (error) {
                    console.log('[SW] Error unsubscribing', error);
                });
            },

            isVAPIDSubscription: function (subscription) {
                if (null === subscription
                    || !('options' in subscription)
                    || !subscription.options.applicationServerKey
                ) {
                    return false;
                }

                var serverKey = new Uint8Array(subscription.options.applicationServerKey);

                return serverKey.length === 65;
            },

            isValidVAPIDSubscription: function (subscription) {
                if (!webPush.isVAPIDSubscription(subscription)) {
                    return false;
                }

                var serverKey = new Uint8Array(subscription.options.applicationServerKey);

                return serverKey.toString() === webPush.VAPID_PUBLIC_KEY_UINT.toString();
            },

            showChromeDialog: function(serviceWorkerRegistration) {
                console.log('[SW] showChromeDialog()');
                let subscriptionOptions = {
                    userVisibleOnly: true,
                    applicationServerKey: webPush.VAPID_PUBLIC_KEY_UINT
                };

                serviceWorkerRegistration.pushManager.subscribe(subscriptionOptions)
                    .then((pushSubscription) => {
                        console.log('[SW] subscribe ok', pushSubscription.endpoint);
                        webPush.subscribeChrome(serviceWorkerRegistration, pushSubscription);
                    }, (error) => {
                        console.error('[SW] subscribe error', error);
                    })
                    .catch((error) => {
                        console.error('[SW] subscribe catch', error, Notification.permission);
                    });
            },

            showSafariDialog: function(){
                var url = window.location.protocol + '//' + window.location.host + '/safari';
                console.log("[Safari] request permissions from " + url + ' for user ' + webPush.userId);
                window.safari.pushNotification.requestPermission(url, webPush.Id, {"token": webPush.safariUserToken}, function (result) {
                    console.log('[Safari] ' + JSON.stringify(result));
                    if (result.permission === 'granted')
                        webPush.saveSafariToken(result.deviceToken);
                });
            },

            saveSafariToken: function (token) {
                localStorage.removeItem('web_token_user');
                jQuery.ajax({
                    url: Routing.generate('aw_push_safari_save_token', {'deviceToken': token}),
                    type: 'POST',
                    success: function () {
                        localStorage.setItem('web_token_user', webPush.userId);
                        localStorage.setItem("web_token_day", webPush.getDay());
                        webPush.reloadOnNotificationsPage();
                    }
                });
            },

            reloadOnNotificationsPage: function() {
                console.log('reloadOnNotificationsPage');
                if (jQuery('#enablePushNotifications').length) {
                    console.log('reloadOnNotificationsPage length');
                    jQuery('#enablePushNotifications').hide();
                    jQuery('#refreshPageNotifications').removeAttr('hidden');
                    console.log('reloadOnNotificationsPage reload');
                    location.reload();
                }
            },

            subscribeChrome: function (serviceWorkerRegistration, subscription) {
                if (!subscription) {
                    console.log('[SW] !subscription');
                    return;
                }

                if (webPush.isVAPIDSubscription(subscription)) {
                    console.log('[SW] saving VAPID subscription...');
                } else {
                    console.log('[SW] saving non-VAPID subscription...');
                }

                var key = subscription.getKey('p256dh');
                var token = subscription.getKey('auth');

                localStorage.removeItem('web_token_user');
                jQuery.ajax({
                    url: Routing.generate('aw_push_chrome_subscribe'),
                    type: 'POST',
                    dataType: 'json',
                    data: {
                        'resource': 'awardwallet',
                        'endpoint': webPush.getEndpoint(subscription),
                        'key': key ? btoa(String.fromCharCode.apply(null, new Uint8Array(key))) : null,
                        'token': token ? btoa(String.fromCharCode.apply(null, new Uint8Array(token))) : null,
                        'vapid': webPush.isVAPIDSubscription(subscription)
                    },
                    success: function (resubscribe) {
                        console.log('[SW] subscription saved');
                        localStorage.setItem('web_token_user', webPush.userId);
                        localStorage.setItem("web_token_day", webPush.getDay());
                        localStorage.setItem("web_token_resubscribed", true);
                        webPush.reloadOnNotificationsPage();
                    }
                });
            },

            getEndpoint: function (subscription) {
                var endpoint = subscription.endpoint;
                var subscriptionId = subscription.subscriptionId;

                // fix Chrome < 45
                if (subscriptionId && endpoint.indexOf(subscriptionId) === -1) {
                    endpoint += '/' + subscriptionId;
                }

                return endpoint;
            },

            getDay: function () {
                var d = new Date();
                return d.getDate();
            },

            forceResubscribe: function(){
                localStorage.setItem("web_token_day", "");
            },

            urlBase64ToUint8Array: function (base64String) {
                var padding = '='.repeat((4 - base64String.length % 4) % 4);
                var base64 = (base64String + padding)
                    .replace(/\-/g, '+')
                    .replace(/_/g, '/');

                var rawData = window.atob(base64);
                var outputArray = new Uint8Array(rawData.length);

                for (var i = 0; i < rawData.length; ++i) {
                    outputArray[i] = rawData.charCodeAt(i);
                }

                return outputArray;
            },

            getPageLocation: function() {
                if (0 === location.pathname.indexOf('/account/list')) {
                    return 'accountlist';
                }
                if (0 === location.pathname.indexOf('/timeline')) {
                    return 'timeline';
                }
                if (0 === location.pathname.indexOf('/blog')) {
                    return 'blog';
                }

                return null;
            },

            enableNotifications: function(params) {
                browser.isSafari
                    ? webPush.showSafariDialog()
                    : webPush.showChromeDialog(params.serviceWorkerRegistration);
            },
            showPushPopup: function(params) {
                let text, cancel, textSign, textEnable;
                console.log('push category: ' + webPush.page);
                if ('blog' === webPush.page) {
                    text = "Don't miss out on breaking news about miles and points!";
                    cancel = "No thanks, I don't need to keep up with news on miles and points.";
                    textSign = "You can change or cancel this any time.";
                    textEnable = "Enable notifications";
                } else {
                    textSign = Translator.trans(/** @Desc("You can change or cancel this any time.") */'you-can-change-anytime');
                    textEnable = Translator.trans(/** @Desc("Enable notifications") */'enable-notifications');

                    switch (webPush.page) {
                        case 'timeline':
                            text = Translator.trans(/** @Desc("Don't miss out on important updates about your travel plans. We will send you a notification if your flight is delayed or canceled.") */'dont-miss-updates-travel-plans');
                            cancel = Translator.trans(/** @Desc("No thanks, I don't need to be notified if my flight is delayed or canceled.") */'cancel-notified-travel');
                            break;
                        //case 'accountlist':
                        default:
                            text = Translator.trans(/** @Desc("Don't miss out on important updates about your rewards! We will send you a notification if we detect that your miles or points are about to expire.") */'dont-miss-updates-your-rewards');
                            cancel = Translator.trans(/** @Desc("No thanks, I don't need to be notified about expiring miles") */'cancel-notified-expiring-miles');
                            break;
                    }
                }

                let tpl = `
                    <div id="pushDialog" class="push-modal push-browser-${browser.name}">${text} ${textSign}
                        <div class="push-modal__bottom">
                            <a href="" class="blue-link">${cancel}</a>
                            <button class="btn-blue">${textEnable}</button>
                        </div>
                    </div><div id="pushOverlay" class="push_overlay"></div>
                `;
                tpl = jQuery(tpl);
                tpl.find('.btn-blue').click(() => {
                    webPush.closeDialog(false);
                    webPush.enableNotifications(params);
                    return false;
                }).end()
                    .find('.blue-link').click(() => webPush.closeDialog(true)).end();
                console.log('showing push dialog');
                jQuery('body').append(tpl);
            },
            closeDialog: function(isDismiss) {
                jQuery('#pushDialog, #pushOverlay').remove();

                if (isDismiss) {
                    // + unsubscribe file in blog
                    const dismissKey = 'blog' === webPush.page ? 'push_dismiss_blog' : 'push_dismiss_date';
                    const dismissDate = Date.now() + (webPush.timeoutDismissInDays * 86400 * 1000);
                    localStorage.setItem(dismissKey, `${dismissDate}`);
                    if (null === localStorage.getItem(dismissKey) || false === Object.prototype.hasOwnProperty.call(localStorage, dismissKey)) {
                        webPush.setCookie(dismissKey, `${dismissDate}`, 86400 * webPush.timeoutDismissInDays, '/');
                    }
                }

                return false;
            },

            setOptions: function(options){
                if (undefined === options || typeof options !== 'object') {
                    return;
                }
                if (Object.prototype.hasOwnProperty.call(options, 'showPopupTimeout')) {
                    webPush.showPopupTimeout = parseInt(options.showPopupTimeout);
                }
                if (Object.prototype.hasOwnProperty.call(options, 'ignoreStaff')) {
                    webPush.ignoreStaff = options.ignoreStaff;
                }
                if (Object.prototype.hasOwnProperty.call(options, 'ignoreDismiss')) {
                    webPush.ignoreDismiss = options.ignoreDismiss;
                }
                if (Object.prototype.hasOwnProperty.call(options, 'dontShowPopup')) {
                    webPush.dontShowPopup = options.dontShowPopup;
                }
            },
            setCookie: function(name, value, expires, path) {
                document.cookie = `${name}=${value}; path=${path}; max-age=${expires};`;
            },
            getCookie: function(name) {
                const matches = document.cookie.match(new RegExp(
                    "(?:^|; )" + name.replace(/([\.$?*|{}\(\)\[\]\\\/\+^])/g, '\\$1') + "=([^;]*)"
                ));
                return matches ? decodeURIComponent(matches[1]) : undefined;
            },
            removeCookie: function(name) {
                document.cookie = `${name}=; path=/; max-age=0;`;
            },
        };

        return webPush;
    };

    if ( typeof define === "function" && define.amd ) {
        define(['jquery', 'routing'], getWebPush());
    } else if ('function' === typeof window.defineWebPush) {
        window.defineWebPush(getWebPush(), {vapidPublicKey : 'BGy9SswXhzY8tksRqhAPFIQRq0EUpEtUvYRfIYOHzWFznTh7fNjo8Hm-mEd7NER9M0iuC2fwEBEL75Rn4psHojM', webPushId : 'web.com.awardwallet'});
    } else {
        getWebPush().init("BGy9SswXhzY8tksRqhAPFIQRq0EUpEtUvYRfIYOHzWFznTh7fNjo8Hm-mEd7NER9M0iuC2fwEBEL75Rn4psHojM");
    }

})();
