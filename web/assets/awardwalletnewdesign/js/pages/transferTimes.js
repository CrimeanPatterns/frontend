define([
    'angular-boot',
    'jquery-boot',
    'angular-ui-router',
    'directives/dialog',
    'filters/highlight',
    'filters/unsafe',
    'translator-boot',
    'lib/customizer',
    'directives/customizer'
], function(angular, $){
    angular = angular && angular.__esModule ? angular.default : angular;

    let operations;

    var app = angular.module("transferTimesApp", [
        'appConfig', 'unsafe-mod', 'dialog-directive', 'ui.router', 'highlight-mod', 'customizer-directive'
    ]);

    app.constant('Routes', {
        transferRoute: 1, // BalanceWatch::POINTS_SOURCE_TRANSFER
        purchaseRoute: 2 // BalanceWatch::POINTS_SOURCE_PURCHASE
    });

    app.run(function($rootScope) {
        $rootScope.transfersList = undefined;
        $rootScope.purchasesList = undefined;
    });

    // Конфигурация роутов
    app.config([
        '$stateProvider', '$urlRouterProvider', '$locationProvider',
        function ($stateProvider, $urlRouterProvider, $locationProvider) {
            $locationProvider.html5Mode({
                enabled: true,
                rewriteLinks: true
            });

            $stateProvider
            .state({
                name: 'mainState',
                url: '',
                controller: 'TransferTimesMainCtrl',
                abstract: true,
                controllerAs: 'main'
            })
            .state({
                name: 'mainState.mileTransferTimes',
                url: Routing.generate('aw_mile_transfer_times_index'),
                templateUrl: Routing.generate('aw_mile_transfer_index'),
                controller: 'TransferTimesTransferCtrl'
            })
            .state({
                name: 'mainState.mileTransferTimesFilter',
                url: Routing.generate('aw_mile_transfer_times_index') + '/:program',
                templateUrl: Routing.generate('aw_mile_transfer_index'),
                controller: 'TransferTimesTransferCtrl'
            })
            .state({
                name: 'mainState.mileTransferTimesLocale',
                url: '/{locale:[a-z][a-z]}' + Routing.generate('aw_mile_transfer_times_index'),
                templateUrl: Routing.generate('aw_mile_transfer_index'),
                controller: 'TransferTimesTransferCtrl'
            })
            .state({
                name: 'mainState.mileTransferTimesLocaleFilter',
                url: '/{locale:[a-z][a-z]}' + Routing.generate('aw_mile_transfer_times_index') + '/:program',
                templateUrl: Routing.generate('aw_mile_transfer_index'),
                controller: 'TransferTimesTransferCtrl'
            })
            .state({
                name: 'mainState.milePurchaseTimes',
                url: Routing.generate('aw_mile_purchase_times_index'),
                templateUrl: Routing.generate('aw_mile_purchase_index'),
                controller: 'TransferTimesPurchaseCtrl'
            })
            .state({
                name: 'mainState.milePurchaseTimesFilter',
                url: Routing.generate('aw_mile_purchase_times_index') + '/:program',
                templateUrl: Routing.generate('aw_mile_purchase_index'),
                controller: 'TransferTimesPurchaseCtrl'
            })
            .state({
                name: 'mainState.milePurchaseTimesLocale',
                url: '/{locale}' + Routing.generate('aw_mile_purchase_times_index'),
                templateUrl: Routing.generate('aw_mile_purchase_index'),
                controller: 'TransferTimesPurchaseCtrl'
            })
            .state({
                name: 'mainState.milePurchaseTimesLocaleFilter',
                url: '/{locale}' + Routing.generate('aw_mile_purchase_times_index') + '/:program',
                templateUrl: Routing.generate('aw_mile_purchase_index'),
                controller: 'TransferTimesPurchaseCtrl'
            })
            ;
        }
    ]);

    app.controller('TransferTimesMainCtrl', [
        '$scope', '$http', 'dialogService', 'Routes', '$rootScope',
                function ($scope, $http, dialogService, Routes, $rootScope) {

        this.status = {
            loading: false,
            loaded: false
        };

        this.initState = function () {
            this.status.loading = false;
            this.status.loaded = false;
        };

        this.isHasBonus = false;

        var main = this;
        this.loadOperations = function (ps) {
            main.status.loading = true;
            if ((ps == Routes.transferRoute && $scope.transfersList === undefined)
                || (ps == Routes.purchaseRoute && $scope.purchasesList === undefined)) {
                this.loadOperationsHttp(ps);
            } else if (ps == Routes.transferRoute) {
                $scope.operations = $rootScope.transfersList;
                $scope.main.status.loading = false;
            } else if (ps == Routes.purchaseRoute) {
                $scope.operations = $rootScope.purchasesList;
                main.status.loading = false;
            }
        }
        this.loadOperationsHttp = function (ps) {
            $http.post(
                Routing.generate('aw_mile_transfers' , { pointsSource: ps})
            ).then(function(res) {
                if (ps == Routes.transferRoute) {
                    $rootScope.transfersList = res.data;
                    $scope.operations = res.data;
                } else if (ps == Routes.purchaseRoute) {
                    $rootScope.purchasesList = res.data;
                    $scope.operations = res.data;
                    $scope.main.isHasBonus = $scope.operations.some(function(val) {
                        return val.Bonus != "";
                    });
                }
                main.status.loading = false;
                $scope.main.status.loading = false;
            }).catch(function(e) {
                main.createErrorDialog();
                main.status.loading = false;
                $scope.main.status.loading = false;
            });
        };

        this.createErrorDialog = function() {
            dialogService.fastCreate(
                Translator.trans("alerts.error"),
                Translator.trans("error_loading_data"),
                true,
                true,
                [{
                    text: Translator.trans('button.ok'),
                    click: function () {
                        $(this).dialog('close');
                    },
                    'class': 'btn-blue'
                }],
                500
            );
        };
    }]);

    app.controller('TransferTimesTransferCtrl', [
        '$scope', 'Routes', '$location',
        function ($scope, Routes, $location) {
            var programValue = '';

            $scope.main.status.loaded = true;
            $scope.main.loadOperations(Routes.transferRoute);

            $scope.programValue = function(newValue) {
                if (angular.isDefined(newValue)) {
                    programValue = newValue;

                    if(programValue.length > 0){
                        $location.search("program", encodeURIComponent(programValue));
                    }else{
                        $location.search("program", null);
                    }
                } else {
                    return programValue;
                }
            };

            $scope.programValue($location.search().program?.replace(/%20/g, ' ') || '');
            $scope.search = function (item) {
                if (programValue == undefined
                        || item.ProviderName.toLowerCase().indexOf(programValue.toLowerCase()) != -1
                        || item.ProviderFromName.toLowerCase().indexOf(programValue.toLowerCase()) != -1) {
                    return true;
                }
                return false;
            };
            $.ajax({
                type: 'POST',
                url: Routing.generate('aw_blog_visits_create'),
                data: JSON.stringify({'pageName': 'Transfer Times'}),
                dataType: 'json',
                contentType: 'application/json'
            });
    }]);

    app.controller('TransferTimesPurchaseCtrl', [
        '$scope', 'Routes', '$location',
        function ($scope, Routes, $location) {
            var programValue = '';

            $scope.main.status.loaded = true;
            $scope.main.loadOperations(Routes.purchaseRoute);

            $scope.programValue = function(newValue) {
                if (angular.isDefined(newValue)) {
                    programValue = newValue;

                    if(programValue.length > 0){
                        $location.search("program", encodeURIComponent(programValue));
                    }else{
                        $location.search("program", null);
                    }
                } else {
                    return programValue;
                }
            };

            $scope.programValue($location.search().program?.replace(/%20/g, ' ') || '');
            $scope.search = function (item) {
                if (programValue == undefined
                        || item.ProviderName.toLowerCase().indexOf(programValue.toLowerCase()) != -1
                ) {
                    return true;
                }
                return false;
            };
            $.ajax({
                type: 'POST',
                url: Routing.generate('aw_blog_visits_create'),
                data: JSON.stringify({'pageName': 'Purchase Times'}),
                dataType: 'json',
                contentType: 'application/json'
            });
    }]);
});
