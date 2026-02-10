angular.module('AwardWalletMobile').factory('Splashscreen', [
    'btfModal',
    function (btfModal) {
        return btfModal({
            controller: function () {
            },
            controllerAs: 'Modal',
            templateUrl: 'templates/directives/popups/popup-splashscreen.html',
            uid: 'splashscreen'
        });
    }
]);