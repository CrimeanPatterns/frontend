angular.module('AwardWalletMobile').controller('ProfileController', [
    '$rootScope',
    '$scope',
    '$q',
    '$filter',
    'inAppPurchase',
    'Translator',
    'SessionService',
    '$state',
    '$stateParams',
    '$http',
    'Database',
    'Splashscreen',
    '$cordovaDialogs',
    'Refresher',
    'UserSettings',
    function ($rootScope, $scope, $q, $filter, inAppPurchase, Translator, SessionService, $state, $stateParams, $http, Database, Splashscreen, $cordovaDialogs, Refresher, UserSettings) {

        var translations = [ /* Uses in native application */
            Translator.trans(/** @Desc("Select mailbox owner") */'mailboxes.select-owner', {}, 'messages'),
        ];

        var splashscreen = Splashscreen;

        function filterOverview(children) {
            var filtered = [], add;
            angular.forEach(children, function (field) {
                add = true;
                if (field.hasOwnProperty("attrs") && field.attrs.hasOwnProperty("setting")) {
                    if (
                        (platform.ios && ["sound", "vibrate"].indexOf(field.attrs.setting) !== -1)
                        || (field.attrs.setting == "vibrate" && !UserSettings.isVibrationSupported())
                    ) {
                        add = false;
                    }
                }
                if (add) this.push(field);
            }, filtered);
            return filtered;
        }

        if($stateParams.hasOwnProperty('needUpdate') && $stateParams['needUpdate'] == true){
            Refresher.setProperty('needRefresh', true);
            $stateParams['needUpdate'] = false;
        }

        $scope.user.overview = filterOverview($scope.user.overview);
        $scope.$on('database:updated', function() {
            $scope.user.overview = filterOverview($scope.user.overview);
        });
    }
]);
