angular.module('AwardWalletMobile')
    .directive('spinner', [function () {
        return {
            restrict: 'EA',
            scope: {
                loading: '=',
                startActive: '=spinnerStartActive',
                color: '@'
            },
            template: '<div class="spinner-container" ng-show="startActive || loading"><div></div></div>',
            link: function (scope, element, attr) {
                React.render(React.createElement(React.addons.Spinner, {color: scope.color}), element[0].children[0].children[0]);
                scope.$on('$destroy', function () {
                    React.unmountComponentAtNode(element[0].children[0].children[0]);
                });
            }
        };
    }]);