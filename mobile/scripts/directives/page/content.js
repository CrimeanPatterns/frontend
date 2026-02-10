angular.module('AwardWalletMobile').service('PageContent', function () {
    var props = {
            enabled: false
        },
        _this = {
            set: function (properties) {
                angular.extend(props, properties);
            },
            enabled: function () {
                return props.enabled;
            },
            getProps: function () {
                return props;
            }
        };
    return _this;
});
angular.module('AwardWalletMobile').directive('pageContent', ['PageContent', 'SideMenu', 'Refresher', function (PageContent, SideMenu, Refresher) {
    return {
        restrict: 'E',
        transclude: true,
        require: ['pageContent', '^page'],
        controller: function () {
            this.props = {
                $hasRefresher: false
            };
        },
        link: function (scope, element, attrs, ctrls, transclude) {
            var $element = $(element[0]),
                controller = ctrls[0],
                timeoutId = -1;

            $element.attr('class', ['content', $element.attr('class')].join(' '));

            transclude(scope, function (clone) {
                element.append(clone);
            });

            $element.on('scroll.content', function (event) {
                if (timeoutId != -1) {
                    clearTimeout(timeoutId);
                }
                PageContent.set({enabled: true});
                timeoutId = setTimeout(function () {
                    PageContent.set({enabled: false});
                }, 300);
            });

            scope.$watch(function () {
                return (controller.props.$hasRefresher ? ' has-refresher' : '')
            }, function (className, oldClassName) {
                $element.removeClass(oldClassName);
                $element.addClass(className);
            });

            scope.$on('$destroy', function () {
                $element.off('scroll.content');
                PageContent.set({enabled: false});
            });
        }
    }
}]);