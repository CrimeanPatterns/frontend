define([
    'angular-boot', 'translator-boot', 'filters/unsafe'
], function (angular) {
    'use strict';
    angular = angular && angular.__esModule ? angular.default : angular;

    angular
        .module('userPayPage-ctrl', ['unsafe-mod'])
        .controller('userPayPageCtrl', [
            '$scope',
            function ($scope) {
                $scope.model = window.paymentOptions;
                $scope.model.user_pay.credits = 0;
                $scope.model.discount = 0;
                $scope.model.giftBalanceWatchCredit = true;

                if($scope.model.user_pay.recurringAmount){
                    $scope.model.back = true;
                }

                if($scope.model.discountAmount && $scope.model.giveAWPlus){
                    $scope.model.discount = $scope.model.discountAmount;
                }

                $scope.removeAwPlus = function () {
                    $scope.model.giveAWPlus = false;
                    $scope.model.giftBalanceWatchCredit = false;
                };

                $scope.$watchGroup(['model.user_pay.onecard', 'model.giveAWPlus'], function () {

                    if($scope.model.discountAmount && $scope.model.giveAWPlus){
                        $scope.model.discount = $scope.model.discountAmount;
                    }else{
                        $scope.model.discount = 0;
                    }

                    $scope.model.total = $scope.model.user_pay.onecard * $scope.model.price.onecard - $scope.model.discount + ($scope.model.giveAWPlus ? $scope.model.price.awplus : 0);

                    if($scope.model.back){
                        $scope.model.back = false;
                        return;
                    }
                    $scope.model.user_pay.recurringAmount = $scope.model.total;
                });

                $scope.creditsText =  Translator.trans(/** @Desc("No AwardWallet OneCard credits will be issued at the time of each recurring payment, but you can always buy them separately") */ 'user-pay.info.no-recurring-credits');

            }
            ])
            .directive('convertToNumber', function() {
                return {
                    require: 'ngModel',
                    link: function (scope, element, attrs, ngModel) {
                        ngModel.$parsers.push(function(val) {
                            return parseInt(val, 10);
                        });
                        ngModel.$formatters.push(function (val) {
                            return '' + val;
                        });
                    }
                };
            })
        .controller('userPayBalanceWatchCreditCtrl', [
            '$scope',
            function($scope) {
                $scope.model = window.paymentOptions;
                $scope.model.discount = 0;
                $scope.model.user_pay.balanceWatchCredit = 1;

                if ($scope.model.user_pay.recurringAmount) {
                    $scope.model.back = true;
                }

                var price = $scope.model.user_pay.countPrice[$scope.model.user_pay.balanceWatchCredit];
                $scope.$watchGroup(['model.user_pay.balanceWatchCredit', 'model.user_pay.balanceWatchCreditAmount'], function() {
                    var quantity = $scope.model.user_pay.balanceWatchCredit;
                    $scope.model.user_pay.balanceWatchCreditAmount = quantity * price;
                    $scope.model.discount = 0;

                    if ('undefined' !== typeof $scope.model.user_pay.countPrice[quantity]) {
                        $scope.model.discount = $scope.model.user_pay.balanceWatchCreditAmount - $scope.model.user_pay.countPrice[quantity];
                    }

                    $scope.model.total = $scope.model.user_pay.balanceWatchCreditAmount - $scope.model.discount;
                });

            }
        ])
        .controller('giftAWPlusCtrl', [
            '$scope', function($scope) {
                $scope.model = window.paymentOptions;
            }
        ]);

});