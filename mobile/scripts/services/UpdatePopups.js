angular.module('AwardWalletMobile').factory('UpdateAccountPopup', [
    'btfModal',
    function (btfModal) {
        return btfModal({
            controller: 'AccountUpdateController',
            controllerAs: 'Modal',
            templateUrl: 'templates/directives/popups/popup-update-account.html',
            uid: 'updateAccountPopup'
        });
    }
]);

angular.module('AwardWalletMobile').factory('UpdateSecurityQuestionPopup', [
    'btfModal',
    function (btfModal) {
        return btfModal({
            controller: 'AccountSecurityQuestionController',
            controllerAs: 'Modal',
            templateUrl: 'templates/directives/popups/popup-security-question.html',
            uid: 'updateSecurityQuestion'
        });
    }
]);

angular.module('AwardWalletMobile').factory('UpdateLocalPasswordPopup', [
    'btfModal',
    function (btfModal) {
        return btfModal({
            controller: 'AccountLocalPasswordController',
            controllerAs: 'Modal',
            templateUrl: 'templates/directives/popups/popup-local-password.html',
            uid: 'updateLocalPassword'
        });
    }
]);

angular.module('AwardWalletMobile').factory('AutoLoginLocalPasswordPopup', [
    'btfModal',
    function (btfModal) {
        return btfModal({
            controller: 'AutoLoginLocalPasswordController',
            controllerAs: 'Modal',
            templateUrl: 'templates/directives/popups/popup-autologin-local-password.html',
            uid: 'updateLocalPassword'
        });
    }
]);