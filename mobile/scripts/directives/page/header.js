angular.module('AwardWalletMobile').directive('pageHeader', function () {
    return {
        restrict: 'E',
        templateUrl: 'templates/directives/page/header.html',
        transclude: true,
        require: '^page',
        link: function(scope, elements, attrs, page){
            page.props.$hasHeader = true;
            scope.$on('$destroy', function(){
                page.props.$hasHeader = false;
            });
        }
    }
});
