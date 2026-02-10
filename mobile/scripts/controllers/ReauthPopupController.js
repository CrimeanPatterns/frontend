angular.module('AwardWalletMobile').controller('ReauthPopupController', [
    '$scope',
    'ReauthPopup',
    function ($scope, ReauthPopup) {
        $scope.error = null;
        $scope.input = '';

        const onPress = $scope.onPress || angular.noop;
        const onClose = $scope.onClose || angular.noop;

        $scope.$watch(() => {
            return $scope.getError();
        }, error => {
            $scope.error = error;
        });
        $scope.submit = function () {
            if ($scope.input && $scope.input.length > 0) {
                onPress({input: $scope.input});
            }
        };
        $scope.resend = function () {
            onPress({resend: true});
        };

        $scope.close = function () {
            ReauthPopup.close();
            onClose();
        };
    }
]);