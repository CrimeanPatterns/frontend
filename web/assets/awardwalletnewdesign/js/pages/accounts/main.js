define([
    'angular-boot',
    'jquery-boot'
], function (angular, $) {
    angular = angular && angular.__esModule ? angular.default : angular;

    angular.module('accounts', ['tabs-directive', 'infinite-scroll', 'cacheService', 'auto-focus-directive']);
});