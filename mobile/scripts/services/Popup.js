angular.module('AwardWalletMobile').factory('Popup', [
    'btfModal',
    function (btfModal) {
        return btfModal({
            controller: function () {},
            controllerAs: 'Modal',
            templateUrl: 'templates/directives/popups/popup.html'
        });
    }
]);

angular.module('AwardWalletMobile').factory('UpgradeAccountLevelPopup', [
    'btfModal',
    function (btfModal) {
        return btfModal({
            controller: 'UpgradeAccountLevelPopupController',
            controllerAs: 'Modal',
            templateUrl: 'templates/directives/popups/upgrade-account-level.html',
            uid: 'upgradeAccountLevel'
        });
    }
]);

angular.module('AwardWalletMobile').factory('ScanMailboxQuestionPopup', [
    'btfModal',
    function (btfModal) {
        return btfModal({
            controller: 'ScanMailboxQuestionPopupController',
            controllerAs: 'Modal',
            templateUrl: 'templates/directives/popups/scan-mailbox-question.html'
        });
    }
]);

angular.module('AwardWalletMobile').factory('ReauthPopup', [
    'btfModal',
    function (btfModal) {
        return btfModal({
            controller: 'ReauthPopupController',
            controllerAs: 'Modal',
            templateUrl: 'templates/directives/popups/reauth.html'
        });
    }
]);