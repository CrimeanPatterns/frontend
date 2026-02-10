angular.module('AwardWalletMobile').controller('PinInfoController', [
    '$scope',
    '$state',
    '$stateParams',
    'Pincode',
    function ($scope, $state, $stateParams, Pincode) {
        if (!platform.cordova) {
            $scope.back();
        }

        function redirect() {
            if ($stateParams.toState) {
                $state.go($stateParams.toState, $stateParams.toParams);
            } else {
                $state.go('index.accounts.list');
            }
        }

        $scope.buttons = {
            cancel: function () {
                Pincode.skip();
                redirect();
            },
            setup: function () {
                Pincode.setup(redirect);
            }
        };
    }
]);
angular.module('AwardWalletMobile').controller('PinSetupController', [
    '$rootScope',
    '$scope',
    '$state',
    '$timeout',
    '$cordovaVibration',
    '$cordovaPrivacyScreen',
    function ($rootScope, $scope, $state, $timeout, $cordovaVibration, $cordovaPrivacyScreen) {
        var fingerprint = Translator.trans(/** @Desc("Confirm fingerprint to unlock") */'fingerprint.confirm', {}, 'mobile');
        var titles = [
            Translator.trans(/** @Desc("PIN Code set up") */'pincode.setup.title', {}, 'mobile'),
            Translator.trans(/** @Desc("Re-enter PIN") */'pincode.title.re-enter', {}, 'mobile'),
            Translator.trans(/** @Desc("Enter your PIN Code") */'pincode.title.current', {}, 'mobile')
        ];
        var attempts = 0, pin = null;

        if(platform.cordova)
        {
            if (platform.android)
                $cordovaPrivacyScreen.enable();
        }

        $scope.popup = {
            title: $scope.pin ? titles[2] : titles[0],
            pin: '',
            buttons: {
                cancel: function (pin) {
                    $scope.$close(pin);
                },
                input: function (num) {
                    $cordovaVibration.click();
                    if ($scope.popup.pin.length < 5 && num > -1) {
                        $scope.popup.pin += num;
                        if ($scope.popup.pin.length == 4) {
                            if (!$scope.pin) {//First setup
                                if (!pin) {
                                    pin = $scope.popup.pin;
                                    $timeout(function () {
                                        $scope.popup.pin = '';
                                        $scope.popup.title = titles[1];
                                    }, 100);
                                } else if (pin == $scope.popup.pin) {
                                    $timeout(function () {
                                        $scope.popup.buttons.cancel(pin);
                                    }, 100);
                                } else {
                                    pin = null;
                                    $timeout(function () {
                                        $scope.popup.pin = '';
                                        $scope.popup.title = titles[0];
                                    }, 100);
                                    $cordovaVibration.vibrate(300);
                                }
                            } else {//Edit
                                if ($scope.popup.pin != $scope.pin) {
                                    $timeout(function () {
                                        $scope.popup.pin = '';
                                        $scope.popup.title = titles[2];
                                    }, 100);
                                    $cordovaVibration.vibrate(300);
                                    attempts += 1;
                                    //Failed attempts
                                    $scope.popup.error = Translator.transChoice('pincode.failed', attempts, {num: attempts}, 'mobile');
                                    if (attempts > 2) {
                                        //Logout
                                        $rootScope.$broadcast('app:logout');
                                    }
                                } else {
                                    $scope.pin = '';
                                    $timeout(function () {
                                        $scope.popup.pin = '';
                                        $scope.popup.error = '';
                                        $scope.popup.title = titles[0];
                                    }, 100);
                                }
                            }
                        }
                    }
                    if (num == -1 && $scope.popup.pin.length > 0) {
                        $scope.popup.pin = $scope.popup.pin.slice(0, -1);
                    }
                }
            }
        };

        $scope.$on('$destroy', function () {
            if (platform.cordova && platform.android)
                $cordovaPrivacyScreen.disable();
        });
    }
]);
angular.module('AwardWalletMobile').controller('PinAccessController', [
    '$rootScope',
    '$scope',
    '$timeout',
    '$cordovaVibration',
    '$cordovaPrivacyScreen',
    function ($rootScope, $scope, $timeout, $cordovaVibration, $cordovaPrivacyScreen) {
        var attempts = 0;

        if (platform.cordova && platform.android)
            $cordovaPrivacyScreen.enable();

        $scope.popup = {
            title: Translator.trans('pincode.title.current', {}, 'mobile'),
            error: '',
            pin: '',
            buttons: {
                cancel: function (pin) {
                    $scope.$close(pin);
                },
                input: function (num) {
                    $cordovaVibration.click();
                    if ($scope.popup.pin.length < 5 && num > -1) {
                        $scope.popup.pin += num;
                        if ($scope.popup.pin.length == 4) {
                            if ($scope.popup.pin != $scope.pin) {
                                $timeout(function () {
                                    $scope.popup.pin = '';
                                }, 100);
                                $cordovaVibration.vibrate(300);
                                attempts += 1;
                                $scope.popup.error = Translator.transChoice(/** @Desc("%num% Failed PIN Code attempt|%num% Failed PIN code attempts") */'pincode.failed', attempts, {num: attempts}, 'mobile');
                                if (attempts > 2) {
                                    //Logout
                                    $rootScope.$broadcast('app:logout');
                                }
                            } else {
                                $timeout(function () {
                                    $scope.$allow();
                                }, 100);
                            }
                        }
                    }
                    if (num == -1 && $scope.popup.pin.length > 0) {
                        $scope.popup.pin = $scope.popup.pin.slice(0, -1);
                    }
                }
            }
        };
        $scope.$on('$destroy', function () {
            if (platform.cordova && platform.android)
                $cordovaPrivacyScreen.disable();
        });
    }
]);