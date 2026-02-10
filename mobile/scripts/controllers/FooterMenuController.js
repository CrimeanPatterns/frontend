angular.module('AwardWalletMobile').controller('FooterMenuController', [
    '$scope',
    '$rootScope',
    'BaseUrl',
    function ($scope, $rootScope, BaseUrl) {
        var date = new Date();
        $scope.year = date.getFullYear();
        $scope.version = app.version;
        $scope.openContactUs = function(){
            var url = BaseUrl+'/contact';
            if (platform.cordova)
                url += '?fromapp=1&KeepDesktop=1';
            window.open(url, '_blank');
        };
    }
]);