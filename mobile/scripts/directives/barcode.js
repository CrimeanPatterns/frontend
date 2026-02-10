angular.module('AwardWalletMobile').directive('barcode', function () {
    var innerWidth = function (el) {
        var element = window.getComputedStyle(el, '');
        return parseInt(element.width) - parseInt(element.paddingLeft) - parseInt(element.paddingRight);
    };
    return {
        restrict: 'E',
        scope: {
            barcodeData: '=',
            barcodeType: '='
        },
        link: function (scope, element, attrs) {
            var parent = element.parent()[0];
            scope.$watchCollection(function () {
                return [scope.barcodeData, scope.barcodeType];
            }, function (arr) {
                var barcodeData = arr[0],
                    barcodeType = arr[1];
                var barWidth = innerWidth(parent) * .8,
                    barHeight = 60;
                var barcode;

                barWidth = barWidth > 500 ? 500 : barWidth;

                if (['QR_CODE', 'DATA_MATRIX'].indexOf(barcodeType) > -1) {
                    barWidth = 200;
                    if (barcodeData.length > 500)
                        barWidth = 250;
                    if (barcodeData.length > 900)
                        barWidth = 300;
                    barHeight = barWidth;
                }

                if (['PDF_417', 'PDF417'].indexOf(arr[1]) > -1) {
                    barHeight = 80;
                }

                barcode = Barcode(barcodeData , barcodeType, {
                    barWidth: barWidth,
                    barHeight: barHeight,
                    showHRI: false
                });

                if(barcode){
                    element[0].innerHTML = '';
                    element.append(barcode);
                }
            })
        }
    };
});