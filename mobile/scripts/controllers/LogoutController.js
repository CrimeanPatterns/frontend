angular.module('AwardWalletMobile').controller('LogoutController', [
    '$rootScope',
    '$state',
    '$stateParams',
    '$timeout',
    function ($rootScope, $state, $stateParams, $timeout) {
        $rootScope.$broadcast('app:logout');
        if ($stateParams.toState) {
            $rootScope.$on('app:storage:destroy', function () {
                $timeout(function () {
                    $state.go($stateParams.toState, $stateParams.toParams);
                });
            });
        }
    }
]);