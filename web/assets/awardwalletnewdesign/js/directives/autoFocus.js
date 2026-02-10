define(['angular-boot'], function (angular) {
    angular = angular && angular.__esModule ? angular.default : angular;

    angular.module('auto-focus-directive', [])
        .directive('autoFocus', ['$timeout', function ($timeout) {
            return {
                link: function (scope, element, attrs) {
                    var timeout = attrs.focusTimeout || 0;
                    $timeout(function () {
                        element.focus();
                        if (element[0].nodeName == 'INPUT')
                            element.select();
                    }, timeout)
                }
            }
        }])
});