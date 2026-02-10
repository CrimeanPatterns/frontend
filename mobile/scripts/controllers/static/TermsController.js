angular.module('AwardWalletMobile').controller('TermsController', [
    '$scope',
    '$http',
    function ($scope, $http) {
        $scope.spinnerSpin = true;
        $http.get('/terms', {timeout:30000}).then(function(response){
            $scope.spinnerSpin = false;
            if(response.hasOwnProperty('data') && response.data.length>0){
                $scope.page = response.data;
            }
        }, function(response){
            $scope.spinnerSpin = false;
        });
    }
]);