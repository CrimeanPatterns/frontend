define(['angular-boot', 'jquery-boot', 'directives/autoFocus', 'webpack-ts/shim/ngReact'], function () {
    angular
        .module('app', ['appConfig', 'react', 'ui.router', 'extendedDialog', 'customizer-directive', 'auto-focus-directive'])
        .config(['$stateProvider', '$urlRouterProvider', '$locationProvider', function ($stateProvider, $urlRouterProvider, $locationProvider) {
            $locationProvider.html5Mode({
                enabled: true,
                rewriteLinks: true
            });

            $stateProvider
                .state({
                    name: 'timeline',
                    url: '/:agentId?before&openSegment&showDeleted&shownStart&openSegmentDate',
                    params: {
                        showDeleted: '0'
                    }
                })
                .state({
                    name: 'shared',
                    url: '/shared/{code}'
                })
                .state({
                    name: 'itineraries',
                    url: '/itineraries/{itIds}?agentId'
                })
                .state({
                    name: 'shared-plan',
                    url: '/shared-plan/{code}'
                });
            $urlRouterProvider.otherwise('/');

            $urlRouterProvider.when('/{agentId}/itineraries/{itIds}', ($state, $match) => {
                const params = {
                    itIds: $match.itIds,
                };

                if($match.agentId){
                    params.agentId = $match.agentId;
                }
                $state.go('itineraries', params);
            });
        }])
        .factory('httpInterceptor', ['$q', '$rootScope', function($q, $rootScope) {
            var loadingCount = 0;
            return {
                request       : function(config) {
                    window.$httpLoading = true;
                    return config || $q.when(config);
                },
                response      : function(response) {
                    if (loadingCount-- < 1) {
                        window.$httpLoading = false;
                    }
                    return response || $q.when(response);
                },
                responseError : function(response) {
                    if (loadingCount-- < 1) {
                        window.$httpLoading = false;
                    }
                    return $q.reject(response);
                }
            };
        }])
        .config(['$httpProvider', function($httpProvider) {
            $httpProvider.interceptors.push('httpInterceptor');
        }])
        .directive('notes', ['reactDirective', function(reactDirective) {
            return reactDirective(require('webpack/js-deprecated/component-deprecated/timeline/Notes').default);
        }])
        .directive('priceMonitoringButton', ['reactDirective', function(reactDirective) {
            return reactDirective(require('webpack/react-app/Components/Timeline/PriceMonitoringButton/PriceMonitoringButton').default);
        }])
});