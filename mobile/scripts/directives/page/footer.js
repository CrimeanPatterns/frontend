angular.module('AwardWalletMobile').directive('pageFooter', function () {
    return {
        restrict: 'E',
        templateUrl: 'templates/directives/page/footer.html',
        transclude: true,
        require: ['^page'],
        link: function (scope, element, attrs, ctrls) {
            var page = ctrls[0];
            page.props.$hasFooter = true;
            scope.$on('$destroy', function () {
                page.props.$hasFooter = false;
            });
        }
    }
});
angular.module('AwardWalletMobile').directive('userMenu', function () {
    return {
        restrict: 'E',
        templateUrl: 'templates/directives/page/usermenu.html',
        transclude: true,
        require: ['userMenu'],
        controller: function () {
            var _this = this;
            this.props = {
                more: false,
                itemsCounter: 0
            };
            this.more = function () {
                _this.props.more = !_this.props.more;
            };
        },
        link: function (scope, element, attrs, ctrls) {
            var userMenu = ctrls[0], els = element.children(), $element = $(els[0]);
            scope.$watch(function () {
                var className = '';
                if (userMenu.props.itemsCounter > 5) {
                    className += ' full-height';
                }
                if (userMenu.props.more) {
                    className += ' show';
                }
                return className;
            }, function (className, oldClassName) {
                $element.removeClass(oldClassName);
                $element.addClass(className);
            });
            scope.more = userMenu.more;
            scope.props = userMenu.props;
        }
    }
});
angular.module('AwardWalletMobile').directive('userMenuItem', function () {
    return {
        restrict: 'E',
        transclude: true,
        require: '^?userMenu',
        link: function (scope, element, attrs, userMenu, transclude) {
            var $element = $(element[0]), innerElement = $('<li/>');
            userMenu.props.itemsCounter++;
            transclude(scope, function (clone) {
                innerElement.append(clone);
                element.append(innerElement);
            });
            scope.$on('$destroy', function(){
                userMenu.props.itemsCounter--;
            });
        }
    }
});