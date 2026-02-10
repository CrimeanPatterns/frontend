angular.module('AwardWalletMobile').controller('AccountSecurityQuestionController', [
    '$scope',
    'Updater',
    'UpdateSecurityQuestionPopup',
    function ($scope, Updater, UpdateSecurityQuestionPopup) {
        var accountId = $scope.accountId;
        $scope.answer = '';
        $scope.submit = function () {
            if ($scope.answer && $scope.answer.length > 0) {
                Updater.doneQuestion(accountId, {
                    answer: $scope.answer,
                    question: $scope.data.question
                });
                UpdateSecurityQuestionPopup.close();
                Updater.nextPopup();
            }
        };

        $scope.close = function () {
            UpdateSecurityQuestionPopup.close();
            Updater.cancelQuestion(accountId);
            Updater.nextPopup();
        };
    }
]);