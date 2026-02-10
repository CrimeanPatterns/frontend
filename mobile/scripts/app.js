angular.module('AwardWalletMobile', [
    'angular-fakestorage',
    'awTemplates',
    'boxuk.translation',
    'btford.modal',
    'ct.ui.router.extras.core',
    'ct.ui.router.extras.sticky',
    'i18nDynamic',
    'jmdobry.angular-cache',
    'ngAnimate',
    'ngCookies',
    'ngCordova',
    'ngFitText',
    'ngResource',
    'ngSanitize',
    'ngTarget',
    'ngTouch',
    'ng-q-timeout',
    'ui.router',
    'ui.router.history',
    'vcRecaptcha']
);
angular.module('AwardWalletMobile').constant('BaseUrl', window.BaseUrl);
angular.module('AwardWalletMobile').constant('ApiUrl', '/m/api');

angular.module('AwardWalletMobile').constant('StorageSettings', {
    maxAge: 21600000,
    deleteOnExpire: 'none',
    recycleFreq: 600000,
    storageMode: platform.cordova ? 'localStorage' : 'none',
    verifyIntegrity: true
});

angular.module('AwardWalletMobile').config([
    '$httpProvider',
    '$provide',
    '$compileProvider',
    function ($httpProvider, $provide, $compileProvider) {
        var counterXsrfErrors = 0;

        $provide.decorator('$sniffer', [
            '$delegate',
            function ($delegate) {
                if (!$delegate.transitions || !$delegate.animations) {
                    $delegate.transitions = typeof document.body.style.webkitTransition === 'string';
                    $delegate.animations = typeof document.body.style.webkitAnimation === 'string';
                }
                return $delegate;
            }
        ]);

        $httpProvider.defaults.xsrfCookieName = 'none';
        $httpProvider.defaults.xsrfHeaderName = 'none';

        $httpProvider.interceptors.push([
            '$q',
            '$rootScope',
            'SessionService',
            'PushStorage',
            'Translator',
            'BaseUrl',
            'ApiUrl',
            'GlobalError',
            '$injector',
            '$localStorage',
            '$cordovaDialogs',
            function ($q, $rootScope, SessionService, PushStorage, Translator, BaseUrl, ApiUrl, GlobalError, $injector, $localStorage, $cordovaDialogs) {
                var retries = 0,
                    waitBetweenErrors = 500;

                function onResponseError(httpConfig, time) {
                    var $timeout = $injector.get('$timeout'),
                        $http = $injector.get('$http');
                    waitBetweenErrors *= 2;
                    return $timeout(function () {
                        return $http(httpConfig);
                    }, time || waitBetweenErrors);
                }

                return {
                    'request': function (config) {
                        var xsrfToken = SessionService.getProperty('X-XSRF-TOKEN'),
                            url = platform.cordova ? BaseUrl : '';

                        if (!config.urlModified) {
                            if (
                                config.url.indexOf('/mobile') === 0 ||
                                config.url.indexOf('extension/extensionStats') > -1 ||
                                config.url.indexOf('account/receive') > -1 ||
                                config.url.indexOf('security') > -1 ||
                                config.url.indexOf('engine') > -1 && platform.cordova
                            ) {
                                config.url = url + config.url;
                                config.urlModified = true;
                            } else if (
                                config.url.indexOf('templates/') === -1 &&
                                config.url.indexOf('resources/languages/') === -1 &&
                                config.url.indexOf('/mobile') !== 0 &&
                                config.url.indexOf('engine') === -1 &&
                                config.url.indexOf('security') === -1 &&
                                config.url.indexOf('http') !== 0
                            ) {
                                config.url = url + ApiUrl + config.url;
                                config.urlModified = true;
                            }
                        }

                        if (xsrfToken) {
                            config.headers['X-XSRF-TOKEN'] = xsrfToken;
                        }

                        if (
                            platform.cordova &&
                            PushStorage.getProperty('deviceId')
                        ) {
                            config.headers['X-AW-DEVICE-ID'] = PushStorage.getProperty('deviceId');
                        }

                        if ($localStorage.getItem('DEBUG_TRANSLATION') === String(true)) {
                            config.headers['X-AW-Show-Translation-Keys'] = 1;
                        }

                        config.headers['Accept-Timezone'] = -new Date().getTimezoneOffset() / 60;
                        config.headers['Accept-Language'] = Translator.locale;

                        return config;
                    },
                    'response': function (response) {
                        var xsrfToken = response.headers('X-XSRF-TOKEN'),
                            needUpdate = response.headers('X-AW-VERSION');

                        if (xsrfToken) {
                            SessionService.setProperty('X-XSRF-TOKEN', xsrfToken);
                            counterXsrfErrors = 0;
                        }

                        if (
                            angular.isObject(response.data) &&
                            response.data.hasOwnProperty('translationKeys') &&
                            app.translations
                        ) {
                            angular.extend(app.translations, response.data.translationKeys);
                        }

                        if (
                            platform.cordova &&
                            needUpdate &&
                            response.data &&
                            response.data.error
                        ) {
                            $cordovaDialogs.confirm(response.data.error, Translator.trans(/** @Desc("Upgrade application") */ 'application.upgrade.title', {}, 'mobile'),
                                [
                                    Translator.trans(/** @Desc("Upgrade") */ 'application.upgrade.buttons.ok', {}, 'mobile'),
                                    Translator.trans('alerts.btn.cancel', {}, 'messages')
                                ]
                            ).then(function (button) {
                                if (button === 1) {
                                    if (platform.ios) {
                                        window.open('http://itunes.apple.com/us/app/awardwallet/id388442727?mt=8', '_system');
                                    } else if (platform.android) {
                                        window.open('market://details?id=com.itlogy.awardwallet', '_system');
                                    }
                                }
                            });
                            return $q.reject(response);
                        }

                        return response;
                    },
                    'responseError': function (rejection) {
                        if (
                            (
                                (
                                    [0, 500].indexOf(rejection.status) > -1 &&
                                    !rejection.headers('X-XSRF-FAILED')
                                ) ||
                                (
                                    rejection.status === 403 &&
                                    rejection.headers('X-AW-SECURE-TOKEN')
                                ) ||
                                (
                                    rejection.status === -1 &&
                                    typeof rejection.config.timeout === 'number'
                                )
                            ) && rejection.config.hasOwnProperty('retries')
                        ) {
                            if (retries < rejection.config.retries) {
                                retries++;
                                return onResponseError(rejection.config);
                            } else {
                                retries = 0;
                                waitBetweenErrors = 500;
                            }
                        }

                        if (rejection.hasOwnProperty('headers')) {
                            if (rejection.headers('X-XSRF-TOKEN')) {
                                SessionService.setProperty('X-XSRF-TOKEN', rejection.headers('X-XSRF-TOKEN'));
                            }

                            if (rejection.headers('X-XSRF-FAILED')) {
                                if (counterXsrfErrors < 3) {
                                    counterXsrfErrors++;
                                    return onResponseError(rejection.config, 0);
                                } else {
                                    counterXsrfErrors = 0;
                                }
                            }

                            if (
                                rejection.status === 403 &&
                                rejection.data &&
                                rejection.data.logout &&
                                SessionService.getProperty('authorized')
                            ) {
                                $rootScope.$broadcast('app:logout', {
                                    toState: $rootScope.$toState.name,
                                    toParams: $rootScope.$toParams
                                });
                            }
                        }

                        if (rejection.status === 304) {
                            return rejection;
                        } else if (
                            rejection.config &&
                            (
                                !rejection.config.hasOwnProperty('globalError') ||
                                rejection.config.globalError !== false
                            )
                        ) {
                            if (
                                rejection.status > 0 ||
                                (
                                    rejection.status === -1 &&
                                    (
                                        rejection.config.hasOwnProperty('timeout') &&
                                        (
                                            (
                                                rejection.config.timeout.hasOwnProperty('canceled') &&
                                                rejection.config.timeout.canceled !== true
                                            ) || !rejection.config.timeout.hasOwnProperty('canceled')
                                        )
                                    )
                                )
                            ) {
                                GlobalError.show(rejection.status);
                            }
                        }

                        return $q.reject(rejection);
                    }
                };
            }
        ]);

        if (platform.cordova) {
            $httpProvider.interceptors.push('secureTokenInterceptor');
        }

        $httpProvider.interceptors.push('Reauth');

        $httpProvider.defaults.headers.common['Content-Type'] = 'application/json;charset=utf-8';
        $httpProvider.defaults.headers.common['X-AW-VERSION'] = app.version;

        if (platform.cordova) {
            $httpProvider.defaults.headers.common['X-AW-PLATFORM'] = platform.ios ? 'ios' : 'android';
        }

        $compileProvider.aHrefSanitizationWhitelist(/^\s*(https?|file|tel|maps|geo):/);

        $provide.decorator('$exceptionHandler', function () {
            return function (exception) {
                TraceKit.report(exception);
                return (exception);
            }
        });
    }
]);
angular.module('AwardWalletMobile').run([
    '$cordovaKeyboard',
    '$cordovaPrivacyScreen',
    '$cordovaSplashscreen',
    '$localStorage',
    '$location',
    '$q',
    '$rootScope',
    '$state',
    '$stateParams',
    '$timeout',
    'AppAvailability',
    'Database',
    'GlobalError',
    'Pincode',
    'Translator',
    'UserSettings',
    'Booking',
    'MetaTagsService',
    function ($cordovaKeyboard, $cordovaPrivacyScreen, $cordovaSplashscreen, $localStorage, $location, $q, $rootScope, $state, $stateParams, $timeout, AppAvailability, Database, GlobalError, Pincode, Translator, UserSettings, Booking, MetaTagsService) {
        var privacyScreenPages = [
            'unauth.login',
            'unauth.registration',
            'index.accounts.account-edit',
            'index.accounts.account-add',
            'unauth.password-recovery.recovery'
        ];

        if (platform && platform.cordova) {
            $cordovaKeyboard.hideAccessoryBar(false);
            var $stateChangeSuccess = $q.defer();
            $stateChangeSuccess.promise.then(function () {
                var timer = $timeout(function () {
                    $cordovaSplashscreen.hide();
                    $timeout.cancel(timer);
                }, 1000);
            });
        } else if (platform.ios || platform.android) {
            AppAvailability.check();
        }

        $rootScope.$on('$stateChangeStart', function (event, toState, toParams, fromState, fromParams) {
            $rootScope.$toState = toState;
            $rootScope.$toParams = toParams;
            GlobalError.hide();

            if (platform.cordova) {

                if (
                    angular.isArray(window.browserStack) &&
                    window.browserStack.length > 0
                ) {
                    console.log("close iab");
                    var iab = window.browserStack.pop();
                    iab.close();
                    iab = undefined;
                }

                if (
                    platform.ios
                    && toState.name === "index.profile-edit"
                    && toParams.action && toParams.action === "useCoupon"
                ) {
                    event.preventDefault();
                }

                if (
                    [
                        'index.booking.request.details',
                        'index.booking.request.not-verified'
                    ].indexOf(toState.name) !== -1
                    && platform.ipad
                ) {
                    event.preventDefault();
                    Booking.openRequestExternal(toParams.Id);
                }
                if (
                    (
                        [
                            'unauth.login',
                            'unauth.registration',
                            'unauth.security-questions'
                        ].indexOf(fromState.name) > -1
                        && [toState.name, fromState.name].indexOf('index.pincode-info') === -1
                    )
                    && toState.name.indexOf('index.') > -1
                    && Pincode.get() === null
                    && Pincode.skipped() === false
                ) {
                    event.preventDefault();
                    $state.go('index.pincode-info', {
                        toState: toState.name,
                        toParams: toParams
                    });
                }
            }

            MetaTagsService.setDefaultTags({
                title: Translator.trans('meta.title', {}, 'messages'),
                description: Translator.trans('meta.description', {}, 'messages'),
            });
        });

        $rootScope.$on('$stateChangeError', function (event, toState, toParams, fromState, fromParams, error) {
            var state = 'unauth.login', backTo = true;
            if (error) {
                if (error.logout)
                    state = 'logout';
                if (error.hasOwnProperty('backTo'))
                    backTo = error.backTo;
            }
            event.preventDefault();
            if (backTo) {
                $state.go(state, {
                    toState: toState.name,
                    toParams: angular.extend({}, toParams, {backTo: true}),
                    toPath: window.location.pathname + window.location.search,
                });
            } else {
                $state.go(state, {});
            }
        });

        $rootScope.$on('$stateChangeSuccess', function (event, toState, toParams, fromState, fromParams) {
            const pageUrl = '/m' + $location.path();

            if (fromState.name !== toState.name) {
                $rootScope.$fromState = fromState;
                $rootScope.$fromParams = fromParams;
            }
            if (platform.cordova) {
                $stateChangeSuccess.resolve();
                if (platform.android) {
                    if (privacyScreenPages.indexOf(toState.name) > -1) {
                        $cordovaPrivacyScreen.enable();
                    } else {
                        $cordovaPrivacyScreen.disable();
                    }
                }
                if (
                    fromState.url == '^'
                    && toState.name.indexOf('index.') > -1/* == 'index.accounts'*/
                    && Pincode.get()
                ) {
                    Pincode.access();
                }
            }
            /*
            ga('send', 'pageview', pageUrl);
            if (!platform.cordova) {
                _hmt.push(['_trackPageview', pageUrl]);
            }
            */
        });

        $rootScope.$state = $state;

        $rootScope.languages = {
            en: {key: 'en'},
            ru: {key: 'ru'},
            pt: {key: 'pt'},
            es: {key: 'es'},
            de: {key: 'de'},
            fr: {key: 'fr'},
            'zh_TW': {key: 'zh_TW'},
            'zh_CN': {key: 'zh_CN'}
        };

        if (!UserSettings.get('language')) {
            if (Translator.locale in $rootScope.languages && !$rootScope.languages[Translator.locale].dev) {
                UserSettings.set('language', Translator.locale);
            } else {
                UserSettings.set('language', $rootScope.languages.en.key);
            }
        }

        Translator.locale = UserSettings.get('language');

        window.dataLayer = window.dataLayer || [];
        window.dataLayer.push({
            'appId': 'm',
            'appVersion': (app.version ?? ''),
            'appName': document.title,
        });
    }
]);
