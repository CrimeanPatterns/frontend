define([
    'angular-boot',
    'jquery-boot',
    'translator-boot',
    'routing',
    'pages/accounts/main'
], function (angular, $) {
    angular = angular && angular.__esModule ? angular.default : angular;

    angular.module('accounts')
        .filter('displayName', function () {
            return function (text) {
                if (!text) return text;
                return text.replace(new RegExp('\\([^\\(\\)]+\\)', 'gi'), '<span>$&</span>');
            }
        })
        .filter('visibleProp', function () {
            return function (properties) {
                if (!properties) return [];
                var result = [];
                angular.forEach(properties, function(value) {
                    if (value.Visible == 1 || value.Visible == 3)
                        result.push(value);
                });
                return result;
            }
        })
        .filter('upgrade', function () {
            return function (text, show) {
                if (!show) return ''+text;
                return '<a href="' + Routing.generate('aw_users_pay')
                    + '" target="_blank">'+Translator.trans('please-upgrade')+'</a>';
            }
        })
});