angular.module('AwardWalletMobile').controller('RootController', [
    'Translator',
    '$scope',
    '$state',
    '$stateParams',
    '$cordovaCookies',
    '$cordovaCache',
    '$history',
    '$location',
    '$rootScope',
    'UserService',
    'PushNotification',
    'Pincode',
    'CardStorage',
    'DeepLink',
    function (Translator, $scope, $state, $stateParams, $cordovaCookies, $cordovaCache, $history, $location, $rootScope, UserService, PushNotification, Pincode, CardStorage, DeepLink) {
        var date = new Date();

        $scope.back = function (path, params) {
            var history = $history.all();
            if (history.length > 1) {
                $history.back(params);
            } else if (path) {
                $state.go(path, params);
            } else {
                $location.path('/');
            }
        };

        $scope.isCordova = platform.cordova;
        $scope.desktopUrl = document.location.protocol + '//' + document.location.host.replace('m.', '') + '/?mobile=0';

        $scope.spinnerLogoutSpin = false;

        $scope.footer = {
            year: date.getFullYear(),
            version: app.version,
            links: {
                contactUs: function () {
                    var url = BaseUrl+'/contact';
                    if (platform.cordova)
                        url += '?fromapp=1&KeepDesktop=1';
                    window.open(url, '_blank');
                },
                blog: function () {
                    var url = BaseUrl + '/blog/';
                    if (platform.cordova)
                        url += '?fromapp=1&KeepDesktop=1';
                    window.open(url, '_system');
                }
            }
        };

        function logout(data) {
            UserService.logout().then(function () {
                if (!platform.cordova) {
                    $rootScope.$broadcast('app:storage:destroy');
                    $scope.spinnerLogoutSpin = false;
                    $state.go('unauth.login', data);
                }
            });
            if (platform.cordova) {
                CardStorage.cleanup();
                $cordovaCache.clear();
                $cordovaCookies.clear().then(function () {
                    $rootScope.$broadcast('app:storage:destroy');
                    PushNotification.cancelNotifications();
                    $scope.spinnerLogoutSpin = false;
                    $state.go('unauth.login', data);
                });
            }
        }

        $scope.$on('app:logout', function (event, data) {
            if (!$scope.spinnerLogoutSpin) {
                $scope.spinnerLogoutSpin = true;
                if (platform.cordova) {
                    PushNotification.unregister();
                    Pincode.unbind();
                }
                logout(data);
            }
        });

        if (platform.cordova) {
            DeepLink.subscribe();
        }
    }
]);
