angular.module('AwardWalletMobile').service('PincodePopup', [
    'btfModal',
    function (btfModal) {
        return btfModal({
            controller: 'PinSetupController',
            controllerAs: 'Popup',
            templateUrl: 'templates/directives/pincode/popup-access.html',
            uid: 'pincodePopup'
        });
    }
]);
angular.module('AwardWalletMobile').service('PincodeAccessPopup', [
    'btfModal',
    function (btfModal) {
        return btfModal({
            controller: 'PinAccessController',
            controllerAs: 'Popup',
            templateUrl: 'templates/directives/pincode/popup-access.html',
            uid: 'pincodePopup'
        });
    }
]);
angular.module('AwardWalletMobile').service('Pincode', [
    'PincodePopup',
    'PincodeAccessPopup',
    'SessionService',
    '$cordovaListener',
    '$cordovaTouchID',
    '$q',
    '$timeout',
    '$rootScope',
    '$state',
    function (PincodePopup, PincodeAccessPopup, SessionService, $cordovaListener, $cordovaTouchID, $q, $timeout, $rootScope, $state) {
        var _this, timeoutId;

        var onPause = function () {
            SessionService.setProperty('pause', new Date().getTime());
        };

        var onResume = function () {
            var time = SessionService.getProperty('pause'), now = new Date().getTime();
            var diff = Math.floor(((Math.abs(now - time)) / 1000) / 60);
            if (_this.get() && diff >= 30) {
                _this.access();
            }
        };

        _this = {
            set: function (pin) {
                SessionService.setProperty('pincode', pin);
                return pin;
            },
            get: function () {
                return SessionService.getProperty('pincode');
            },
            skip: function () {
                SessionService.setProperty('pincode-skipped', true);
                return true;
            },
            skipped: function () {
                return SessionService.getProperty('pincode-skipped');
            },
            remove: function () {
                var q = $q.defer();
                PincodeAccessPopup.open({
                    buttons: {
                        cancel: Translator.trans('cancel', {}, 'messages')
                    },
                    pin: _this.get(),
                    $close: function () {
                        PincodeAccessPopup.close();
                        q.reject();
                    },
                    $allow: function () {
                        _this.set(null);
                        PincodeAccessPopup.close();
                        q.resolve();
                    }
                });
                return q.promise;
            },
            access: function () {
                var q = $q.defer();

                $rootScope.$broadcast('pincode:lock');

                function allow() {
                    $rootScope.$broadcast('pincode:unlock');
                    PincodeAccessPopup.close();
                    q.resolve();
                }

                PincodeAccessPopup.open({
                    buttons: {
                        cancel: Translator.trans(/** @Desc("Forgot PIN") */'pincode.button.forgot', {}, 'mobile')
                    },
                    pin: _this.get(),
                    $close: function () {
                        $rootScope.$broadcast('pincode:cancel');
                        $rootScope.$broadcast('app:logout');
                        $timeout(function () {
                            PincodeAccessPopup.close();
                        }, 100, false);
                        q.reject();
                    },
                    $allow: allow
                });
                if (platform.cordova && platform.ios) {
                    timeoutId = $timeout(function () {
                        if ($state.is('unauth.login') === false) {
                            $cordovaTouchID.checkSupport().then(function () {
                                $cordovaTouchID.authenticate(Translator.trans(/** @Desc("Please authenticate via TouchID to proceed") */'pincode.touchid', {}, 'mobile')).then(allow, function () {
                                    // error
                                });
                            }, function (error) {
                                // TouchID not supported
                            });
                        }
                    }, 1200, false);
                }
                return q.promise;
            },
            setup: function (callback) {
                PincodePopup.open({
                    buttons: {
                        cancel: Translator.trans('cancel', {}, 'messages')
                    },
                    pin: _this.get(),
                    $close: function (pin) {
                        if (!pin) {
                            _this.skip();
                        } else {
                            _this.set(pin);
                        }
                        PincodePopup.close();
                        if (callback)
                            callback();
                    }
                });
            },
            unbind: function () {
                $timeout.cancel(timeoutId);
                $cordovaListener.unbind('resume', onResume);
                $cordovaListener.unbind('pause', onPause);
            },
            bind: function () {
                $cordovaListener.bind('pause', onPause);
                $cordovaListener.bind('resume', onResume);
            },
            active: function () {
                return PincodeAccessPopup.active();
            }
        };
        if (platform.cordova) {
            $rootScope.$on('app:storage:destroy', _this.unbind);
        }
        return _this;
    }
]);