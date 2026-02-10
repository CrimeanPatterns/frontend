angular.module('AwardWalletMobile').service('PushCacheStorage', [
    '$angularCacheFactory',
    '$rootScope',
    'StorageSettings',
    function ($angularCacheFactory, $rootScope, StorageSettings) {
        return $angularCacheFactory('PushStorage', angular.extend({}, StorageSettings, {
            recycleFreq: 86400000,
            onExpire: function (key, value) {
                if (key == 'data') {
                    $rootScope.$broadcast('push:expired');
                }
            }
        }));
    }
]);
angular.module('AwardWalletMobile').service('PushStorage', [
    'PushCacheStorage',
    '$rootScope',
    function (PushCacheStorage, $rootScope) {
        var properties = {
            id: null,
            type: null,
            registered: false,
            date: null,
            deviceId: null,
            disabled: false
        };
        var defaultProp = angular.copy(properties);
        var cache = PushCacheStorage.get('data');
        PushCacheStorage.put('data', angular.extend(properties, cache));

        var obj = {
            getProperty: function (name) {
                return properties[name];
            },
            setProperty: function (name, value) {
                properties[name] = value;
                PushCacheStorage.put('data', properties);
                return properties[name];
            },
            setProperties: function (props) {
                angular.extend(properties, props);
                PushCacheStorage.put('data', properties);
                return properties;
            },
            destroy: function () {
                properties = angular.copy(defaultProp);
                PushCacheStorage.remove('data');
                return true;
            }
        };

        $rootScope.$on('app:storage:destroy', obj.destroy);

        return obj;
    }
]);
angular.module('AwardWalletMobile').service('PushNotification', [
    '$rootScope',
    '$q',
    '$http',
    '$timeout',
    '$cordovaPush',
    'PushStorage',
    'UserSettings',
    function ($rootScope, $q, $http, $timeout, $cordovaPush, PushStorage, UserSettings) {
        var config = {
            android: {},
            ios: {}
        };

        if (platform.cordova && platform.android) {
            config.android = {
                senderID: '23758098931',
                sound: UserSettings.isSoundEnabled()
            };
            if (UserSettings.isVibrationSupported()) {
                config.android.vibrate = UserSettings.get('vibrate');
            }
        }

        if (platform.cordova && platform.ios) {
            config.ios = {
                alert: true,
                badge: true,
                sound: UserSettings.isSoundEnabled(),
                categories: [
                    {
                        categoryIdentifier: 'barcode'
                    },
                    {
                        categoryIdentifier: 'qrcode'
                    }
                ]
            };
        }

        console.log('PushNotification, default config', config);
        var _this = {
            hasOption: function (option, platform) {
                return typeof config[platform][option] !== "undefined";
            },
            getOption: function (option, platform) {
                return config[platform][option];
            },
            extendOptions: function (options, platform) {
                angular.extend(config[platform], options);
                return _this.setNotificationSettings(config[platform]);
            },
            registerDevice: function (data) {
                $http({
                    method: 'post',
                    url: '/push/register',
                    timeout: 30000,
                    data: data,
                    globalError: false,
                    retries: 3
                }).then(function (response) {
                    if (response.data.hasOwnProperty('deviceId')) {
                        PushStorage.setProperties({
                            id: data.id,
                            type: data.type,
                            registered: true,
                            date: new Date,
                            deviceId: response.data.deviceId,
                            disabled: false
                        });
                    }
                    console.log('Register device, success', response);
                }, function (response) {
                    PushStorage.setProperties({
                        id: data.id,
                        type: data.type,
                        registered: false,
                        date: new Date,
                        deviceId: null,
                        disabled: false
                    });
                    console.log('Register device, fail', response);
                });
            },
            unregisterDevice: function (data) {
                return $http({
                    method: 'post',
                    url: '/push/unregister',
                    timeout: 30000,
                    data: data,
                    globalError: false,
                    retries: 3
                });
            },
            register: function () {
                var defer = $q.defer();
                $cordovaPush.register(config[app.platform]).then(function (data) {
                    console.log('PushNotification:REGISTERED', data.token);

                    defer.resolve(data.token);

                    _this.registerDevice({
                        id: data.token,
                        type: data.type
                    });
                }, function (err) {
                    defer.reject();
                    console.error('PushNotification:REGISTER_ERROR', err);
                });

            var unbind = $rootScope.$on('$cordovaPush:notificationReceived', function (event, notification) {
                console.log('PushNotification:RECEIVED', JSON.stringify(notification));
                if (platform.cordova && platform.android && notification.event == 'registered') {
                    _this.registerDevice({
                        id: notification.regid,
                        type: notification.type || 'android'
                    });
                    defer.resolve(notification.regid)
                }
                unbind();
            });

                return defer.promise;
            },
            unregister: function () {
                var q = $q.defer();
                if (
                    PushStorage.getProperty('id') &&
                    PushStorage.getProperty('type')
                ) {
                    $cordovaPush.unregister(config).then(function () {
                        _this.unregisterDevice({
                            id: PushStorage.getProperty('id'),
                            type: PushStorage.getProperty('type')
                        }).then(function () {
                            q.resolve();
                        }, function () {
                            q.resolve();
                        });
                    });
                } else {
                    q.resolve();
                }
                return q.promise;
            },
            setNotificationSettings: function (options) {
                return $cordovaPush.setNotificationSettings(options);
            },
            disabled: function () {
                return PushStorage.getProperty('disabled');
            },
            disable: function () {
                PushStorage.setProperty('disabled', true);
                return _this.unregister();
            },
            enable: function () {
                PushStorage.setProperty('disabled', false);
                return _this.register();
            },

            /**
             * Create notification categories (iOS only)
             *
             * @param {NgCordova.PushNotification.NotificationCategory[]} categories
             *
             */
            createCategories: function (categories) {
                $cordovaPush.createCategories(categories);
            },

            /**
             * Create local notification
             *
             * @param {NgCordova.PushNotification.LocalNotification} notification
             * @param {?(
             *      NgCordova.PushNotification.CalendarTrigger|NgCordova.PushNotification.GeoLocationTrigger
             * )} trigger - The trigger that will or did cause the notification to be delivered. No trigger means deliver now.
             *
             * @returns {Promise}
             */
            createNotification: function (notification, trigger) {
                return $cordovaPush.createNotifications([{notification: notification, trigger: trigger}]);
            },

            /**
             * Create local notifications
             *
             * @param {NgCordova.PushNotification.TriggeredNotification[]} notifications
             *
             * @returns {Promise}
             */
            createNotifications: function (notifications) {
                return $cordovaPush.createNotifications(notifications);
            },

            /**
             * Cancel created local notifications
             */
            cancelNotifications: function () {
                return $cordovaPush.cancelNotifications();
            }
        };

        if (platform.cordova) {
            _this.setNotificationSettings(config[app.platform]);
        }

        return _this;
    }]);