angular.module('AwardWalletMobile').factory('BarcodePopup', [
    'btfModal',
    function (btfModal) {
        return btfModal({
            controller: 'BarcodePopupController',
            templateUrl: 'templates/directives/popups/barcode.html',
            uid: 'barcodePopup'
        });
    }
]);

angular.module('AwardWalletMobile').controller('BarcodePopupController', [
    '$scope',
    '$cordovaDialogs',
    'Barcode',
    function ($scope, $cordovaDialogs, Barcode) {
        var translations = {
                'title': Translator.trans('delete.barcode', {}, 'mobile'),
                'confirm': Translator.trans('alerts.text.confirm', {}, 'messages'),
                'delete': Translator.trans('button.delete', {}, 'messages'),
                'cancel': Translator.trans('cancel', {}, 'messages')
            },
            barcode = {
                text: null,
                format: null,
                scan: function () {
                    Barcode.scan().then(function (response) {
                        barcode.text = response.text;
                        barcode.format = response.format;
                    });
                },
                remove: function () {
                    $cordovaDialogs.confirm(
                        translations.title,
                        translations.confirm,
                        [
                            translations.delete,
                            translations.cancel
                        ]
                    ).then(function (button) {
                        if (button === 1) {
                            barcode.text = null;
                            barcode.format = null;
                            $scope.close();
                        }
                    });
                }
            };

        $scope.close = function() {
            $scope.hideModal({
                text: barcode.text,
                format: barcode.format
            });
        };

        $scope.barcode = angular.extend(barcode, $scope.barcode);
    }
]);