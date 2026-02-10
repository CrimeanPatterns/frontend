angular.module('AwardWalletMobile').controller('IndexController', [
    '$q',
    '$scope',
    '$rootScope',
    '$state',
    '$stateParams',
    '$timeout',
    '$cordovaListener',
    'Database',
    'SessionService',
    'AccountList',
    'UserService',
    'PushNotification',
    'PushStorage',
    'Splashscreen',
    'Timeline',
    'Booking',
    'Translator',
    'Pincode',
    'inAppPurchase',
    '$cordova3DTouch',
    'UserSettings',
    'Media',
    'CardStorage',
    'i18nLanguage',
    'Centrifuge',
    function ($q, $scope, $rootScope, $state, $stateParams, $timeout, $cordovaListener, Database, SessionService, AccountList, UserService, PushNotification, PushStorage, Splashscreen, Timeline, Booking, Translator, Pincode, inAppPurchase, $cordova3DTouch, UserSettings, Media, CardStorage, i18nLanguage, Centrifuge) {

        var splashscreen = Splashscreen;

        $scope.providerIcons = {
            0: 'icon-custom-account',
            1: 'icon-fly',
            2: 'icon-hotel',
            3: 'icon-car',
            4: 'icon-train',
            5: 'icon-clear-note',
            6: 'icon-credit-card',
            7: 'icon-shop',
            8: 'icon-eat',
            9: 'icon-note',
            10: 'icon-boat',
            11: 'icon-documents',
            12: 'icon-parking',
            'passport': 'icon-passport',
            'traveler-number': 'icon-travel-number',
            'custom': 'icon-custom-account',
            'coupon': 'icon-voucher'
        };

        var redirectsCb = [], queueCb = [], redirectTimeoutId;

        $scope.logout = function () {
            $rootScope.$broadcast('app:logout');
        };

        $scope.reload = function () {
            return Database.update(false).then(function () {
                return $q.resolve()
            });
        };

        var update = function update(skipEvent) {
            var constants = Database.getProperty('constants'),
                accounts = Database.getProperty('accounts'),
                booking = Database.getProperty('booking'),
                profile = Database.getProperty('profile'),
                timeline = Database.getProperty('timeline'),
                translationKeys = Database.getProperty('translationKeys'),
                blog = Database.getProperty('blog'),
                providerKinds = constants.providerKinds,
                providerKindsObject = {};

            angular.forEach(providerKinds, function (provider, index) {
                provider.index = index;
                providerKindsObject[provider.KindID] = provider;
            });

            SessionService.setProperty('userId', profile.UserID);

            UserSettings.loadSettings(profile.UserID);

            if (profile.hasOwnProperty('centrifugeConfig') && profile.centrifugeConfig instanceof Object) {
                Centrifuge.configure(profile.centrifugeConfig);
            }

            if (profile.hasOwnProperty('impersonate') && profile.impersonate) {
                SessionService.setProperty('developer', true);
            }

            if (profile.hasOwnProperty('settings') && profile.settings instanceof Object) {
                UserSettings.extend(profile.settings);
            }

            if (profile.hasOwnProperty('locations') && profile.locations instanceof Object) {
                SessionService.setProperty('locations-total', profile.locations.total || 0);
                SessionService.setProperty('locations-tracked', profile.locations.tracked || 0);
            }

            if (accounts instanceof Object) {
                AccountList.setAccounts(accounts);
            }

            if (timeline instanceof Object) {
                Timeline.setList(timeline);
            }

            if (booking instanceof Object && booking.requests) {
                Booking.setRequests(booking.requests, skipEvent);
                if (booking.channel) {
                    Booking.setUserMessagesChannel(booking.channel);
                }
            }

            if (translationKeys && app.translations) {
                angular.extend(app.translations, translationKeys);
            }

            if (profile.language) {
                Translator.locale = profile.language;
                i18nLanguage.setLocale(Translator.locale);
                if (UserSettings.get('language') !== profile.language) {
                    if (
                        $state.current.name !== 'index.profile-edit' &&
                        typeof UserSettings.get('language') !== 'undefined'
                    )
                        $state.go($state.current.name, $state.params, {
                            reload: true,
                            inherit: false,
                            notify: true
                        });
                    UserSettings.set('language', profile.language);
                }
                UserSettings.set('locale', profile.locale);
            }

            AccountList.setProviderKinds(providerKindsObject);

            angular.extend($scope, {
                programsCount: AccountList.getLength(),
                bookingCount: booking.hasOwnProperty('dashboard') ? booking.dashboard.active : 0,
                timelineCount: Timeline.getFutureTrips(),
                providerKinds: providerKindsObject,
                providerKindsDisplay: providerKinds,
                user: profile,
                blog: blog
            });

            if (platform.cordova && profile.products) {
                inAppPurchase.register(profile.products);
                if (platform.ios && profile.restore) {
                    if (Pincode.get()) {
                        var unbindFn = $rootScope.$on('pincode:unlock', function () {
                            inAppPurchase.restore();
                            unbindFn();
                        });
                    }else{
                        inAppPurchase.restore();
                    }
                }
            }

            if (profile.googleMapsEndpoints) {
                app.googleMapsEndpoints = profile.googleMapsEndpoints;
            }
        };

        $scope.$on('database:updated', function (event, skipEvent) {
            update(skipEvent);
        });

        $scope.$on('accountList:update', function () {
            Database.setProperty('accounts', AccountList.getAccounts());
            $scope.programsCount = AccountList.getLength();

            if (platform.cordova) {
                $q.all(queueCb).then(function () {
                    PushNotification.cancelNotifications().then(function () {
                        var notifications = AccountList.getAccountsNotifications();
                        if (notifications && notifications.length > 0 && UserSettings.isMpEnabled('mpRetailCards')) {
                            PushNotification.createNotifications(notifications);
                        }
                    });
                });
            }
        });

        $scope.$on('booking:update', function () {
            var data = Database.getProperty('booking'), temp = [], requests = Booking.getRequests();
            for (var i = 0, l = requests.sort.length; i < l; i++) {
                temp.push(requests.requests[requests.sort[i]]);
            }
            data.requests = temp;
            Database.setProperty('booking', data);
            temp = [];
        });

        $scope.$on('timeline:update', function () {
            $scope.timelineCount = Timeline.getFutureTrips();
        });

        $scope.$on('database:expire', function () {
            Database.update(false, false);
        });

        update.call(this, true);

        $scope.$on('booking:chat:message', function (event, data) {
            if (
                !(data && data.requestId && data.hasOwnProperty('messageId')) || // data integrity checks
                (data.action !== 'add') || // skip changes and removals
                (data.uid === data.ownerId) || // show notifications only from other users
                !!data.messageExists // message have been existed before socket event, notification should have been created before
            ) {
                return;
            }

            var message = Booking.getRequestMessage(data.requestId, data.messageId);

            if (!message) {
                return;
            }

            if (data.notify) {
                Media.play('resources/sounds/newMessage.mp3');
            }
        });

        function openBooking() {
            var bookingUnread = Booking.getUnread();
            if (bookingUnread) {
                $state.go('index.booking.request.details', {Id: bookingUnread});
                return true;
            }
            return false;
        }

        redirectsCb.push(openBooking);

        if (platform.cordova) {
            /*
             Cards
             */
            $scope.$watch(function () {
                return CardStorage.storage;
            }, function () {
                CardStorage.save();
            }, true);
            /*
             Push Notifications
             */

            if (PushNotification.disabled() === false) {
                queueCb.push(PushNotification.register());
            }

            $scope.$on('userSettings:update', function (event) {
                var changed = {}, promises = [];
                if (
                    PushNotification.hasOption('sound', app.platform) &&
                    UserSettings.isSoundEnabled() !== PushNotification.getOption('sound', app.platform)
                ) {
                    changed['sound'] = UserSettings.isSoundEnabled();
                }
                if (
                    PushNotification.hasOption('vibrate', app.platform) &&
                    UserSettings.isVibrationSupported() &&
                    UserSettings.get('vibrate') !== PushNotification.getOption('vibrate', app.platform)
                ) {
                    changed['vibrate'] = UserSettings.get('vibrate');
                }

                if (Object.keys(changed).length > 0) {
                    promises.push(PushNotification.extendOptions(changed, app.platform));
                }

                $q.all(promises).then(function () {
                    if (UserSettings.isMpDisableAll()) {
                        PushNotification.cancelNotifications();
                    }
                });
            });

            var applicationState = {
                state: 'started',
                createTransitionTo: function (newState) {
                    return function () {
                        applicationState.state = newState;
                    };
                }
            };

            $cordovaListener.bind('pause', applicationState.createTransitionTo('paused'));
            $cordovaListener.bind('resume', function () {
                if ((new Date().getTime()) - (parseInt(SessionService.getProperty('timestamp')) * 1000) >= 1000 * 60 * 5 /** 5 minutes */) {
                    $scope.$evalAsync(function () {
                        $scope.$broadcast('database:expire');
                    });
                }
                applicationState.createTransitionTo('resumed');
            });

            var unbind = $scope.$on('$stateChangeSuccess', function (event, toState, toParams, fromState) {
                if (fromState.name.indexOf('unauth') === -1) {
                    $scope.$evalAsync(function () {
                        $scope.$broadcast('database:expire');
                    });
                }
                unbind();
            });

            $scope.$on('$cordovaPush:notificationReceived', function (event, data) {
                data = data.payload || data.additionalData || data;
                if (!data.hasOwnProperty('foreground') || (!(+data.foreground) && data.foreground !== '1')) {
                    if (
                        parseInt(data._ts) > parseInt(SessionService.getProperty('timestamp')) &&
                        //if open external link or booking messages do not reload data
                        !data.hasOwnProperty('ex') &&
                        !data.hasOwnProperty('bm') &&
                        !data.hasOwnProperty('tel')
                    ) {
                        splashscreen.open();
                        Database.update().then(function () {
                            splashscreen.close();
                            onNotification(data);
                        });
                    } else {
                        onNotification(data);
                    }
                }
            });

            function onNotification(data) {
                var params = [], gaEventData = {
                    eventCategory: null,
                    eventAction: 'open',
                    eventLabel: null
                };
                clearTimeout(redirectTimeoutId);

                if (data.hasOwnProperty('a')) {
                    params = data.a.split('.');
                    $state.transitionTo('index.accounts.account-details', {Id: params[0], subId: params[1]}, {
                        reload: false,
                        inherit: false,
                        notify: true
                    });
                    gaEventData.eventCategory = 'Push - Rewards';
                }

                if (data.hasOwnProperty('tl')) {
                    if (data.tl instanceof Array) {
                        params = data.tl[0].split('.');
                    } else {
                        params = data.tl.split('.');
                    }
                    $state.transitionTo('index.timeline.segment.details', {
                        Id: params[0],
                        Segment: params[1],
                        SubId: params[2]
                    }, {
                        reload: false,
                        inherit: false,
                        notify: true
                    });
                    gaEventData.eventCategory = 'Push - Travel';
                }

                if (data.hasOwnProperty('tel')) {
                    window.open(['tel:', data.tel].join(''), '_system');
                }

                if (data.hasOwnProperty('bm')) {
                    var bookingParams = data.bm.split('.'),
                        requestId = +bookingParams[0],
                        messageId = +bookingParams[1];

                    $state.transitionTo('index.booking.request.details', {
                        Id: requestId
                    }, {
                        reload: (data.source === "remote") && // data reload after offline push notification ...
                        !Booking.getRequestMessage(requestId, messageId), // ... and message doesn't exists in model
                        inherit: false,
                        notify: true
                    });

                    gaEventData.eventCategory = 'Push - Booking';
                }

                if (data.hasOwnProperty('ex')) {
                    //open external links from push notification
                    window.open(data.ex, '_system');
                    gaEventData.eventCategory = 'Push - Blog';
                    gaEventData.eventLabel = data.ex;
                }

                if (gaEventData.eventCategory)
                    ga('send', 'event', gaEventData);
            }

            $scope.$on('push:expired', PushNotification.register);
            Pincode.bind();

            if (platform.ios) {
                $cordova3DTouch.addGlobalActionHandler(function (payload) {
                    clearTimeout(redirectTimeoutId);
                    payload = JSON.parse(payload.type);
                    $state.go(payload.state, payload.params);
                });
            }

            function openTimeline() {
                var traveler = Timeline.getTraveler('my'), today = new Date(), segments, segment, params;
                if (traveler && traveler.futureSegments > 0) {
                    segments = Timeline.getSegmentsInRange(today, new Date(today.getTime() + 1000 * 60 * 60 * 24 * 7/* 7 days */), ['checkout', 'dropoff', 'layover']);
                    if (segments && segments.length > 0) {
                        segment = segments[0];
                        params = segment.id.split('.');
                        $state.go('index.timeline.segment.details', {Segment: params[0], SubId: params[1]});
                        if (
                            segment.type == 'checkin' &&
                            segment.menu.boardingPassUrl &&
                            new Date(segment.startDate.ts * 1000).getTime() - Date.now() <= 1000 * 60 * 60/* 1 hour */
                        ) {
                            window.open(segment.menu.boardingPassUrl, '_blank');
                        }
                        return true;
                    }
                }
                return false;
            }

            redirectsCb.push(openTimeline);
        }else{
            if (window.hasOwnProperty('__REACT_NATIVE__') && window.__REACT_NATIVE__){
                window.ReactNativeWebView && window.ReactNativeWebView.postMessage('pageLoaded');
                try {
                    window.webkit.messageHandlers.reactNative.postMessage('pageLoaded');
                } catch (error) {

                }
            }
        }

        if ($state.is('index.accounts.list') && !$stateParams.backTo) {
            redirectTimeoutId = setTimeout(function run() {
                for (var cb in redirectsCb) {
                    if (redirectsCb.hasOwnProperty(cb) && redirectsCb[cb]())
                        break;
                }
            }, 500);
        }
    }
]);
