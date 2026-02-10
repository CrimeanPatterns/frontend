angular.module('AwardWalletMobile').controller('BarcodeScanController', [
    '$scope',
    'Barcode',
    'BarcodePopup',
    function ($scope, Barcode, BarcodePopup) {
        return {
            scan: function () {
                if (platform.cordova) {
                    Barcode.scan().then(function (response) {
                        if (
                            $scope.field &&
                            $scope.field.value &&
                            response.text &&
                            response.format
                        ) {
                            $scope.field.value = {};
                            $scope.field.value.text = response.text;
                            $scope.field.value.format = response.format;
                        }
                    });
                }
            },
            show: function () {
                if (
                    $scope.field &&
                    $scope.field.value &&
                    platform.cordova
                ) {
                    BarcodePopup.open({
                        barcode: $scope.field.value,
                        hideModal: function (barcode) {
                            $scope.field.value = barcode;
                            BarcodePopup.close();
                        }
                    });
                }
            }
        }
    }
]);