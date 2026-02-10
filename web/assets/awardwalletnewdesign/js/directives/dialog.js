define(['angular', 'jquery-boot', 'lib/dialog', 'jqueryui'], function (angular, $, dialog) {
    angular = angular && angular.__esModule ? angular.default : angular;

    angular.module('dialog-directive', [])
        .service('dialogService', [function(){
            return dialog;
        }])

        .directive('dialog', ['dialogService', function(dialogService) {
            'use strict';
            var options = $.unique(Object.keys($.ui.dialog.prototype.options));
            return {
                restrict: 'E',
                scope: options.reduce(function(acc, val) {
                        acc[val] = "&"; return acc;
                    }, {bindToScope: '='}),
                replace: true,
                transclude: true,
                template: '<div style="display:none" data-ng-transclude></div>',
                link: function (scope, element, attr, ctrl, transclude) {
                    var opts = options.reduce(function(acc, val) {
                        var value = scope[val] ? scope[val]() : undefined;
                        if (value !== undefined) {
                            acc[val] = value;
                        }
                        return acc;
                    }, {});
                    dialogService.createNamed(attr.id, element, opts);
                    if (scope.bindToScope) {
                        transclude(scope, function(clone, scope) {
                            element.html(clone);
                        });
                    }
                }
            };
        }]);
});