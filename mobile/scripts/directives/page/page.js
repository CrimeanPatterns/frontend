angular.module('AwardWalletMobile').directive('page', function () {
    return {
        restrict: 'E',
        transclude: true,
        controller: function(){
            this.container = null;
            this.props ={
                $hasHeader: false,
                $hasFooter: false
            };
        },
        link: function(scope, element, attr, page, transclude){
            page.container = element[0];
            element.addClass('page');
            element.addClass('full-wrap');
            scope.$watch(function() {
                return (page.props.$hasHeader ? ' has-header' : '') +
                    (page.props.$hasFooter ? ' has-footer' : '');
            }, function(className, oldClassName) {
                element.removeClass(oldClassName);
                element.addClass(className);
            });
            transclude(scope, function (clone) {
                element.append(clone);
            });
        }
    }
});
