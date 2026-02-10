angular.module('AwardWalletMobile').controller('AboutController', [
    '$scope',
    '$http',
    '$rootScope',
    'SessionService',
    function ($scope, $http, $rootScope, SessionService) {
        $scope.authorized = SessionService.getProperty('authorized');
        $scope.spinnerSpin = true;
        $http.get('/about', {timeout:30000}).then(function(response){
            $scope.spinnerSpin = false;
            if(response.hasOwnProperty('data') && response.data.length > 0){
                $scope.page = response.data;
            }
        }, function(response){
            $scope.spinnerSpin = false;
        });
    }
]);