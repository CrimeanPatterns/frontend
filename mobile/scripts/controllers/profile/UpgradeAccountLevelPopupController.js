angular.module('AwardWalletMobile').controller('UpgradeAccountLevelPopupController', [
    '$scope',
    'UpgradeAccountLevelPopup',
    function ($scope, UpgradeAccountLevelPopup) {
        $scope.close = function () {
            UpgradeAccountLevelPopup.close();
        };
        $scope.open = function () {
            window.open('/user/pay', '_blank');
            UpgradeAccountLevelPopup.close();
        };
    }
]);