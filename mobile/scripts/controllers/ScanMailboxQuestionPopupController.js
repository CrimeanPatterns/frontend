angular.module('AwardWalletMobile').controller('ScanMailboxQuestionPopupController', [
    '$scope',
    'ScanMailboxQuestionPopup',
    function ($scope, ScanMailboxQuestionPopup) {
        $scope.close = function () {
            ScanMailboxQuestionPopup.close();
        };
    }
]);