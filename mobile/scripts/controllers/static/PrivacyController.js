angular.module('AwardWalletMobile').controller('PrivacyController', [
    '$scope',
    '$http',
    function ($scope, $http) {
        $scope.spinnerSpin = true;
        $http.get('/privacy', {timeout:30000}).then(function(response){
            $scope.spinnerSpin = false;
            if(response.hasOwnProperty('data') && response.data.length>0){
                $scope.page = response.data;
            }
        }, function(response){
            $scope.spinnerSpin = false;
        });
    }
]);