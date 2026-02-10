(function (angular, document) {
    /**
     * @namespace NgCordova
     * @desc ng cordova factories and services.
     * Only available in mobile app
     */

    var ngCordova = angular.module('ngCordova', []);

    ngCordova.factory('$cordovaCookies', ['$q', '$window', function ($q, $window) {

        return {
            clear: function () {
                var q = $q.defer();

                $window.cookies.clear(
                    function () {
                        q.resolve();
                    });

                return q.promise;
            }
        };
    }]);

    ngCordova.factory('$cordovaListener', [function () {
        var events = ['deviceready',
            'pause',
            'resume',
            'backbutton',
            'menubutton',
            'searchbutton',
            'startcallbutton',
            'endcallbutton',
            'volumedownbutton',
            'volumeupbutton'];
        return {
            bind: function (event, fn) {
                if (events.indexOf(event) < 0) {
                    return Error(event + ' not allowed');
                }
                document.addEventListener(event, fn);
            },
            unbind: document.removeEventListener
        };
    }]);

    ngCordova.factory('$cordovaClipboard', ['$q', '$window', function ($q, $window) {

        return {
            copy: function (text) {
                var q = $q.defer();

                $window.cordova.plugins.clipboard.copy(text,
                    function () {
                        q.resolve();
                    }, function () {
                        q.reject();
                    });

                return q.promise;
            },

            paste: function () {
                var q = $q.defer();

                $window.cordova.plugins.clipboard.paste(function (text) {
                    q.resolve(text);
                }, function () {
                    q.reject();
                });

                return q.promise;
            }
        };
    }]);

    ngCordova.factory('$cordovaCache', ['$window', function ($window) {

        return {
            clear: function () {
                $window.cache.clear();
                return true;
            }
        };
    }]);

    ngCordova.factory('$cordovaDialogs', ['$q', '$window', function ($q, $window) {

        return {
            alert: function (message, title, buttonName) {
                var q = $q.defer();

                if (!$window.navigator.notification) {
                    $window.alert(message);
                    q.resolve();
                } else {
                    navigator.notification.alert(message, function () {
                        q.resolve();
                    }, title, buttonName);
                }

                return q.promise;
            },

            confirm: function (message, title, buttonLabels) {
                var q = $q.defer();

                if (!$window.navigator.notification) {
                    if ($window.confirm(message)) {
                        q.resolve(1);
                    } else {
                        q.resolve(2);
                    }
                } else {
                    navigator.notification.confirm(message, function (buttonIndex) {
                        q.resolve(buttonIndex);
                    }, title, buttonLabels);
                }

                return q.promise;
            },

            prompt: function (message, title, buttonLabels, defaultText) {
                var q = $q.defer();

                if (!$window.navigator.notification) {
                    var res = $window.prompt(message, defaultText);
                    if (res !== null) {
                        q.resolve({input1: res, buttonIndex: 1});
                    } else {
                        q.resolve({input1: res, buttonIndex: 2});
                    }
                } else {
                    navigator.notification.prompt(message, function (result) {
                        q.resolve(result);
                    }, title, buttonLabels, defaultText);
                }
                return q.promise;
            },

            beep: function (times) {
                return navigator.notification.beep(times);
            }
        };
    }]);

    ngCordova.factory('$cordovaPush', ['$q', '$window', '$rootScope', '$timeout',
        /**
         * @param $q
         * @param $window
         * @param $rootScope
         * @param $timeout
         */
        function ($q, $window, $rootScope, $timeout) {

            /**
             * @namespace NgCordova.PushNotification
             */

            /**
             * Triggered Notification
             *
             * @typedef {Object} NgCordova.PushNotification.TriggeredNotification
             * @property {NgCordova.PushNotification.LocalNotification} notification - notification data
             * @property {?(NgCordova.PushNotification.CalendarTrigger|NgCordova.PushNotification.GeoLocationTrigger)} trigger - The trigger that will or did cause the notification to be delivered.
             *           No trigger means deliver now.
             *
             */

            /**
             * Local Notification
             *
             * @typedef {Object} NgCordova.PushNotification.LocalNotification
             * @property {Number} [badge] - The application badge number. nil means no change. 0 to hide (iOS only)
             * @property {String} title - The title of the notification.
             * @property {String} body - The body of the notification.
             * @property {Boolean} forceShow - Show notification when app in foreground.
             * @property {String} threadIdentifier - It will be used to visually group notifications together.
             * @property {String} categoryIdentifier - The identifier for a registered category that will be used to determine the appropriate actions to display for the notification.
             * @property {Object} userInfo - The user information dictionary stores any additional objects.
             *
             */

            /**
             * Notification category
             *
             * @typedef {Object} NgCordova.PushNotification.NotificationCategory
             * @property {String} categoryIdentifier - The identifier for a category.
             * @property {NgCordova.PushNotification.ActionButtonBundle} actions - Action buttons.
             *
             */

            /**
             * Action buttons bundle
             *
             * @typedef {Object} NgCordova.PushNotification.ActionButtonBundle
             * @property {NgCordova.PushNotification.ActionButton} yes - first button
             * @property {NgCordova.PushNotification.ActionButton} no - second button
             * @property {NgCordova.PushNotification.ActionButton} maybe - third button
             *
             */

            /**
             * Action button for category
             *
             * @typedef {Object} NgCordova.PushNotification.ActionButton
             * @property {String} callback - Callback.
             * @property {String} title - Title of the button.
             * @property {Boolean} foreground - Whether or not to bring your app to the foreground.
             * @property {Boolean} destructive - Color button as warning.
             *
             */

            /**
             * Geofence
             *
             * @typedef {Object} NgCordova.PushNotification.Geofence
             * @property {Number} lat - geofence latitude
             * @property {Number} lng - geofence longitude
             * @property {Number} radius - geofence radius
             */

            /**
             * Calendar datetime trigger
             * Can be scheduled on the device to notify based on date and time values.
             *
             * @typedef {Object} NgCordova.PushNotification.CalendarTrigger
             * @property {String} type="datetime" - Trigger type.
             * @property {Number} dateTime - Date and time in timestamp format.
             * @property {?Number} interval - Time interval in seconds. Activating the repetition of the notification.
             *
             */

            /**
             * Geo location trigger
             * Can be scheduled on the device to notify based on location.
             *
             * @typedef {Object} NgCordova.PushNotification.GeoLocationTrigger
             * @property {String} type="geofence" - Trigger type.
             * @property {NgCordova.PushNotification.Geofence} geofence
             */

            /**
             * @typedef {Object} NgCordova.PushNotification.Options
             * @property {AndroidOptions} android - Android notification settings
             * @property {IosOptions} ios - iOS notification settings
             */

            /**
             * @typedef {Object} NgCordova.PushNotification.AndroidOptions
             * @property {string} senderID - Maps to the project number in the Google Developer Console.
             * @property {string} [icon] - Optional. The name of a drawable resource to use as the small-icon. The name should not include the extension.
             * @property {string} [iconColor] - Optional. Sets the background color of the small icon on Android 5.0 and greater. [Supported Formats]{@link http://developer.android.com/reference/android/graphics/Color.html#parseColor(java.lang.String)}
             * @property {Boolean} [sound=true] - Optional. If true it plays the sound specified in the push data or the default system sound.
             * @property {Boolean} [vibrate=true] - Optional. If true the device vibrates on receipt of notification.
             * @property {Boolean} [clearNotifications=true] - Optional. If true the app clears all pending notifications when it is closed.
             * @property {Boolean} [forceShow=false] - Optional. Controls the behavior of the notification when app is in foreground.
             * If true and app is in foreground, it will show a notification in the notification drawer, the same way as when the
             * app is in background (and on('notification') callback will be called only when the user clicks the notification).
             * When false and app is in foreground, the on('notification') callback will be called immediately.
             */

            /**
             * @typedef {Object} NgCordova.PushNotification.IosOptions
             * @property {Boolean} [alert=false] - Optional. If true the device shows an alert on receipt of notification. Note: the value you set this option to the first time you call the init method will be how the application always acts.
             * Once this is set programmatically in the init method it can only be changed manually by the user in Settings>Notifications>App Name. This is normal iOS behaviour.
             * @property {Boolean} [badge=false] - Optional. If true the device sets the badge number on receipt of notification. Note: the value you set this option to the first time you call the init method will be how the
             * application always acts. Once this is set programmatically in the init method it can only be changed manually by the user in Settings>Notifications>App Name. This is normal iOS behaviour.
             * @property {Boolean} [sound=false] - Optional. If true the device plays a sound on receipt of notification. Note: the value you set this option to the first time you call the init method will be
             * how the application always acts. Once this is set programmatically in the init method it can only be changed manually by the user in Settings>Notifications>App Name. This is normal iOS behaviour.
             * @property {Boolean} [clearBadge=false] - Optional. If true the badge will be cleared on app startup.
             * @property {Object} [categories] - Optional. The data required in order to enabled Action Buttons for iOS. See [Action Buttons on iOS]{@link https://github.com/phonegap/phonegap-plugin-push/blob/master/docs/PAYLOAD.md#action-buttons-1} for more details.
             */

            /**
             * @class
             * @name NgCordova.PushNotification.Executor
             * @classdesc Communicates with the push notification plugin
             */

            /**
             * @method
             * @name NgCordova.PushNotification.Executor#register
             * @param {NgCordova.PushNotification.Options} options
             */

            /**
             * @method
             * @name NgCordova.PushNotification.Executor#setNotificationSettings
             * @description only for Android
             * @param {function} successCallback - called in case success
             * @param {function} errorCallback - called in case fail
             * @param {NgCordova.PushNotification.Options} options - only sound and vibrate
             */

            /**
             * @method
             * @name NgCordova.PushNotification.Executor#unregister
             * @param {function} successCallback - called in case success
             * @param {function} [errorCallback] - called in case fail
             */

            /**
             * @method
             * @description only iOS
             * @name NgCordova.PushNotification.Executor#createCategories
             * @param {function} successCallback - called in case success
             * @param {function} errorCallback - called in case fail
             * @param {NgCordova.PushNotification.NotificationCategory[]} categories
             */

            /**
             * @method
             * @description Subscribing to events
             * @name NgCordova.PushNotification.Executor#on
             * @param {string} eventName - Name of the event to listen to (notification, registration, error)
             * @param {function} callback - Is called when the event is triggered.
             */

            /**
             * @method
             * @description Removes a previously registered callback for an event.
             * @name NgCordova.PushNotification.Executor#off
             * @param {string} eventName - Name of the event type. The possible event names are the same as for the "on" function.
             * @param {function} callback - The same callback used to register with "on"
             */

            /**
             * @method
             * @description iOS only. Tells the OS that you are done processing a background push notification.
             * @name NgCordova.PushNotification.Executor#finish
             * @param {function} successCallback - Is called when the api successfully completes background push processing.
             * @param {function} errorCallback - Is called when the api encounters an error while processing and completing the background push.
             * @param {string} id - Tells the OS which background process is complete.
             */

            /**
             * @method
             * @description Checks whether the push notification permission has been granted.
             * @name NgCordova.PushNotification.Executor#hasPermission
             * @param {function} successCallback - Is called when the api successfully retrieves the details on the permission.
             * @param {function} errorCallback - called in case fail
             */

            /**
             * @method
             * @description Create local notification
             * @name NgCordova.PushNotification.Executor#createNotification
             * @param {function} successCallback - called in case success
             * @param {function} errorCallback - called in case fail
             * @param {NgCordova.PushNotification.TriggeredNotification[]} notification
             */

            /**
             * @method
             * @description Cancel created local notifications
             * @name NgCordova.PushNotification.Executor#cancelNotifications
             * @param {function} successCallback - called in case success
             * @param {function} errorCallback - called in case fail
             */

            if (typeof $window["PushNotification"] !== "undefined") {
                /**
                 * @type {NgCordova.PushNotification.Executor}
                 */
                var push = new $window.PushNotification();
                push.on('notification', function (notification) {
                    $timeout(function () {
                        $rootScope.$broadcast('$cordovaPush:notificationReceived', notification);
                    });
                });
            }

            return {
                register: function (options) {
                    var q = $q.defer();

                    var onRegister = function (data) {
                        q.resolve({
                            token: data.registrationId,
                            type: data.type || "ios"
                        });
                        onComplete();
                    };
                    var onError = function (e) {
                        q.reject(e.message);
                        onComplete();
                    };
                    var onComplete = function () {
                        push.off('registration', onRegister);
                        push.off('error', onError);
                    };

                    push.on('registration', onRegister);
                    push.on('error', onError);

                    push.register(options);

                    return q.promise;
                },

                unregister: function () {
                    var q = $q.defer();

                    push.unregister(function (result) {
                        q.resolve(result);
                    }, function (error) {
                        q.resolve(error);
                    });

                    return q.promise;
                },

                setNotificationSettings: function (options) {
                    var q = $q.defer();
                    if (platform.ios) {
                        q.reject();
                    } else {
                        push.setNotificationSettings(function (result) {
                            q.resolve(result);
                        }, function (error) {
                            q.reject(error);
                        }, options);
                    }
                    return q.promise;
                },

                createNotifications: function (notifications) {
                    var q = $q.defer();
                    push.createNotifications(
                        function (result) {
                            q.resolve(result);
                        },
                        function (error) {
                            q.reject(error);
                        },
                        notifications
                    );
                    return q.promise;
                },

                cancelNotifications: function () {
                    var q = $q.defer();
                    push.cancelNotifications(
                        function (result) {
                            q.resolve(result);
                        },
                        function (error) {
                            q.reject(error);
                        }
                    );
                    return q.promise;
                },

                hasPermission: function () {
                    var q = $q.defer();
                    push.hasPermission(
                        function (result) {
                            q.resolve(result);
                        },
                        function (error) {
                            q.reject(error);
                        }
                    );
                    return q.promise;
                },


                checkLocationPermission: function () {
                    var q = $q.defer();
                    push.checkLocationPermission(
                        function (result) {
                            q.resolve(result);
                        },
                        function (error) {
                            q.reject(error);
                        }
                    );
                    return q.promise;
                },

                createCategories: function (categories) {
                    var q = $q.defer();

                    push.createCategories(function (result) {
                        q.resolve(result);
                    }, function (error) {
                        q.resolve(error);
                    }, categories);

                    return q.promise;
                }
            };
        }]);

    ngCordova.factory('$cordovaVibration', [function () {
        return {
            vibrate: function (times) {
                return navigator.notification.vibrate(times);
            },
            vibrateWithPattern: function (pattern, repeat) {
                return navigator.notification.vibrateWithPattern(pattern, repeat);
            },
            cancelVibration: function () {
                return navigator.notification.cancelVibration();
            },
            click: function () {
                return navigator.notification.play();
            }
        };
    }]);

    ngCordova.factory('$cordovaTouchID', ['$q', function ($q) {
        return {
            checkSupport: function () {
                var defer = $q.defer();
                if (!window.cordova) {
                    defer.reject("$cordovaTouchID not supported without cordova.js");
                } else {
                    touchid.checkSupport(function (value) {
                        defer.resolve(value);
                    }, function (err) {
                        defer.reject(err);
                    });
                }

                return defer.promise;
            },

            authenticate: function (auth_reason_text) {
                var defer = $q.defer();
                if (!window.cordova) {
                    defer.reject("$cordovaTouchID not supported without cordova.js");
                } else {
                    touchid.authenticate(function (value) {
                        defer.resolve(value);
                    }, function (err) {
                        defer.reject(err);
                    }, auth_reason_text);
                }

                return defer.promise;
            }
        };
    }]);

    ngCordova.factory('$cordovaLaunchNavigator', ['$q', function ($q) {
        return {
            navigate: function (dst, arr, options) {
                var defer = $q.defer();
                if (!window.cordova || !launchnavigator) {
                    defer.reject("$cordovaLaunchNavigator not supported without cordova.js");
                } else {
                    launchnavigator.navigate(dst, arr, function () {
                        defer.resolve();
                    }, function (err) {
                        defer.reject(err);
                    }, options);
                }

                return defer.promise;
            }
        };
    }]);

    ngCordova.factory('$cordovaCustomScheme', ['$q', function ($q) {
        return {
            handle: function () {
                var defer = $q.defer();
                if (!window.cordova) {
                    defer.reject("$cordovaCustomScheme not supported without cordova.js");
                } else {
                    window.handleOpenURL = function (url) {
                        defer.resolve(url);
                    };
                }
                return defer.promise;
            }
        };
    }]);
    ngCordova.factory('$cordovaSplashscreen', [function () {

        return {
            hide: function () {
                return navigator.splashscreen.hide();
            },

            show: function () {
                return navigator.splashscreen.show();
            }
        };

    }]);
    ngCordova.factory('$cordovaKeyboard', ['$rootScope', function ($rootScope) {

        var keyboardShowEvent = function () {
            $rootScope.$evalAsync(function () {
                $rootScope.$broadcast('$cordovaKeyboard:show');
            });
        };

        var keyboardHideEvent = function () {
            $rootScope.$evalAsync(function () {
                $rootScope.$broadcast('$cordovaKeyboard:hide');
            });
        };

        document.addEventListener('deviceready', function () {
            if (cordova.plugins.Keyboard) {
                document.addEventListener('touchend', function (event) {
                    var nodeName = event.target.nodeName.toLowerCase();
                    if (['input', 'textarea', 'select'].indexOf(nodeName) == -1 && cordova.plugins.Keyboard.isVisible) {
                        cordova.plugins.Keyboard.close();
                        cordova.plugins.Keyboard.hideKeyboardAccessoryBar(false);
                    }
                }, false);
            }
        });

        return {
            hideAccessoryBar: function (bool) {
                return cordova.plugins.Keyboard.hideKeyboardAccessoryBar(bool);
            },

            close: function () {
                return cordova.plugins.Keyboard.close();
            },

            show: function () {
                return cordova.plugins.Keyboard.show();
            },

            disableScroll: function (bool) {
                return cordova.plugins.Keyboard.disableScroll(bool);
            },

            isVisible: function () {
                return cordova.plugins.Keyboard.isVisible;
            },

            clearShowWatch: function () {
                document.removeEventListener('native.keyboardshow', keyboardShowEvent);
                $rootScope.$$listeners['$cordovaKeyboard:show'] = [];
            },

            clearHideWatch: function () {
                document.removeEventListener('native.keyboardhide', keyboardHideEvent);
                $rootScope.$$listeners['$cordovaKeyboard:hide'] = [];
            }
        };
    }]);

    ngCordova.factory('$cordovaPrivacyScreen', [function () {

        return {
            enable: function () {
                return PrivacyScreen.enable();
            },

            disable: function () {
                return PrivacyScreen.disable();
            }
        };

    }]);

    ngCordova.factory('$cordova3DTouch', ['$q', function ($q) {
        var quickActions = [];
        var quickActionHandler = {};

        var createQuickActionHandler = function (quickActionHandler) {
            return function (payload) {
                for (var key in quickActionHandler) {
                    if (payload.type === key) {
                        quickActionHandler[key]();
                    }
                }
            };
        };

        return {
            /*
             * Checks if Cordova 3D touch is present and loaded
             *
             * @return   promise
             */
            isAvailable: function () {
                var deferred = $q.defer();
                if (!window.cordova) {
                    deferred.reject('Not supported in browser');
                } else {
                    if (!window.ThreeDeeTouch) {
                        deferred.reject('Could not find 3D touch plugin');
                    } else {
                        window.ThreeDeeTouch.isAvailable(function (value) {
                            deferred.resolve(value);
                        }, function (err) {
                            deferred.reject(err);
                        });
                    }
                }

                return deferred.promise;
            },

            /*
             * Add a quick action to menu
             *
             * @param    string type
             * @param    string title
             * @param    string iconType (optional)
             * @param    string subtitle (optional)
             * @param    function callback (optional)
             * @return   promise
             */
            addQuickAction: function (type, title, iconType, iconTemplate, subtitle, callback) {
                var deferred = $q.defer();

                var quickAction = {
                    type: type,
                    title: title,
                    subtitle: subtitle
                };

                if (iconType) {
                    quickAction.iconType = iconType;
                }

                if (iconTemplate) {
                    quickAction.iconTemplate = iconTemplate;
                }

                this.isAvailable().then(function () {
                        quickActions.push(quickAction);
                        quickActionHandler[type] = callback;
                        window.ThreeDeeTouch.configureQuickActions(quickActions);
                        window.ThreeDeeTouch.onHomeIconPressed = createQuickActionHandler(quickActionHandler);
                        deferred.resolve(quickActions);
                    },
                    function (err) {
                        deferred.reject(err);
                    });

                return deferred.promise;
            },

            /*
             * Add a quick action handler. Used for static quick actions
             *
             * @param    string type
             * @param    function callback
             * @return   promise
             */
            addQuickActionHandler: function (type, callback) {
                var deferred = $q.defer();

                this.isAvailable().then(function () {
                        quickActionHandler[type] = callback;
                        window.ThreeDeeTouch.onHomeIconPressed = createQuickActionHandler(quickActionHandler);
                        deferred.resolve(true);
                    },
                    function (err) {
                        deferred.reject(err);
                    });

                return deferred.promise;
            },
            /*
             * Add a global action handler.
             *
             * @param    string type
             * @param    function callback
             * @return   promise
             */
            addGlobalActionHandler: function (callback) {
                var deferred = $q.defer();

                this.isAvailable().then(function () {
                        window.ThreeDeeTouch.onHomeIconPressed = callback;
                        deferred.resolve(true);
                    },
                    function (err) {
                        deferred.reject(err);
                    });

                return deferred.promise;
            },
            /*
             * Enable link preview popup when force touch is appled to link elements
             *
             * @return   bool
             */
            enableLinkPreview: function () {
                var deferred = $q.defer();

                this.isAvailable().then(function () {
                        window.ThreeDeeTouch.enableLinkPreview();
                        deferred.resolve(true);
                    },
                    function (err) {
                        deferred.reject(err);
                    });

                return deferred.promise;
            },

            /*
             * Add a hanlder function for force touch events,
             *
             * @param    function callback
             * @return   promise
             */
            addForceTouchHandler: function (callback) {
                var deferred = $q.defer();

                this.isAvailable().then(function () {
                        window.ThreeDeeTouch.watchForceTouches(callback);
                        deferred.resolve(true);
                    },
                    function (err) {
                        deferred.reject(err);
                    });

                return deferred.promise;
            }
        };
    }]);
    ngCordova.factory('$cordovaSafariWebView', [function () {

        var options = {
            hidden: false, // default false. You can use this to load cookies etc in the background (see issue #1 for details).
            animated: false, // default true, note that 'hide' will reuse this preference (the 'Done' button will always animate though)
            transition: 'curl' // (this only works in iOS 9.1/9.2 and lower) unless animated is false you can choose from: curl, flip, fade, slide (default)
            // enterReaderModeIfAvailable: false,  // default false
            // tintColor: "#00ffff", // default is ios blue
            // barColor: "#0000ff", // on iOS 10+ you can change the background color as well
            // controlTintColor: "#ffffff" // on iOS 10+ you can override the default tintColor
        };

        return {
            open: function (url, callback, error, options) {
                SafariViewController.isAvailable(function (available) {
                    if (available) {
                        SafariViewController.show(angular.extend({}, options, {url: url}), callback, error);
                    }
                })
            },

            close: function () {
                SafariViewController.hide();
            }
        };

    }]);
    ngCordova.factory('$cordovaFileTransfer', ['$q', '$timeout', function ($q, $timeout) {
        return {
            download: function (source, filePath, options, trustAllHosts) {
                var q = $q.defer();
                var ft = new FileTransfer();
                var uri = (options && options.encodeURI === false) ? source : encodeURI(source);

                if (options && options.timeout !== undefined && options.timeout !== null) {
                    $timeout(function () {
                        ft.abort();
                    }, options.timeout);
                    options.timeout = null;
                }

                ft.onprogress = function (progress) {
                    q.notify(progress);
                };

                q.promise.abort = function () {
                    ft.abort();
                };

                ft.download(uri, filePath, q.resolve, q.reject, trustAllHosts, options);
                return q.promise;
            },

            upload: function (server, filePath, options, trustAllHosts) {
                var q = $q.defer();
                var ft = new FileTransfer();
                var uri = (options && options.encodeURI === false) ? server : encodeURI(server);

                if (options && options.timeout !== undefined && options.timeout !== null) {
                    $timeout(function () {
                        ft.abort();
                    }, options.timeout);
                    options.timeout = null;
                }

                ft.onprogress = function (progress) {
                    q.notify(progress);
                };

                q.promise.abort = function () {
                    ft.abort();
                };

                ft.upload(filePath, uri, q.resolve, q.reject, options, trustAllHosts);
                return q.promise;
            }
        };
    }]);

    ngCordova.factory('$cordovaUniversalLink', [function () {
        return {
            subscribe: function (eventsNames, callback) {
                universalLinks.subscribe(eventsNames, callback);
            },

            unsubscribe: function (eventsNames) {
                universalLinks.unsubscribe(eventsNames);
            }
        };
    }]);

    ngCordova.service('NewMedia', ['$q', '$interval', function ($q, $interval) {
        var q, q2, q3, mediaStatus = null, mediaPosition = -1, mediaTimer, mediaDuration = -1;

        function setTimer(media) {
            if (angular.isDefined(mediaTimer)) {
                return;
            }

            mediaTimer = $interval(function () {
                if (mediaDuration < 0) {
                    mediaDuration = media.getDuration();
                    if (q && mediaDuration > 0) {
                        q.notify({duration: mediaDuration});
                    }
                }

                media.getCurrentPosition(
                    // success callback
                    function (position) {
                        if (position > -1) {
                            mediaPosition = position;
                        }
                    },
                    // error callback
                    function (e) {
                        console.log('Error getting pos=' + e);
                    });

                if (q) {
                    q.notify({position: mediaPosition});
                }

            }, 1000);
        }

        function clearTimer() {
            if (angular.isDefined(mediaTimer)) {
                $interval.cancel(mediaTimer);
                mediaTimer = undefined;
            }
        }

        function resetValues() {
            mediaPosition = -1;
            mediaDuration = -1;
        }

        function NewMedia(src) {
            this.media = new Media(src,
                function (success) {
                    clearTimer();
                    resetValues();
                    q.resolve(success);
                }, function (error) {
                    clearTimer();
                    resetValues();
                    q.reject(error);
                }, function (status) {
                    mediaStatus = status;
                    q.notify({status: mediaStatus});
                });
        }

        // iOS quirks :
        // -  myMedia.play({ numberOfLoops: 2 }) -> looping
        // -  myMedia.play({ playAudioWhenScreenIsLocked : false })
        NewMedia.prototype.play = function (options) {
            q = $q.defer();

            if (typeof options !== 'object') {
                options = {};
            }

            this.media.play(options);

            setTimer(this.media);

            return q.promise;
        };

        NewMedia.prototype.pause = function () {
            clearTimer();
            this.media.pause();
        };

        NewMedia.prototype.stop = function () {
            this.media.stop();
        };

        NewMedia.prototype.release = function () {
            this.media.release();
            this.media = undefined;
        };

        NewMedia.prototype.seekTo = function (timing) {
            this.media.seekTo(timing);
        };

        NewMedia.prototype.setVolume = function (volume) {
            this.media.setVolume(volume);
        };

        NewMedia.prototype.startRecord = function () {
            this.media.startRecord();
        };

        NewMedia.prototype.stopRecord = function () {
            this.media.stopRecord();
        };

        NewMedia.prototype.currentTime = function () {
            q2 = $q.defer();
            this.media.getCurrentPosition(function (position) {
                q2.resolve(position);
            });
            return q2.promise;
        };

        NewMedia.prototype.getDuration = function () {
            q3 = $q.defer();
            this.media.getDuration(function (duration) {
                q3.resolve(duration);
            });
            return q3.promise;
        };

        return NewMedia;

    }]);

    ngCordova.factory('$cordovaMedia', ['NewMedia', function (NewMedia) {
        return {
            newMedia: function (src) {
                return new NewMedia(src);
            }
        };
    }]);

})(angular, document);