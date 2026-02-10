angular.module('AwardWalletMobile').controller('TimelineSharedController', ['$rootScope', '$scope', 'TimelineShared', '$state',
    function ($rootScope, $scope, TimelineShared, $state) {
        $scope.timeline = TimelineShared.getList();
        var prevPage = $rootScope.$fromState;
        $scope.prevPage = function () {
            if (prevPage && prevPage.name) {
                $state.go(prevPage.name);
            }else{
                $state.go('index.accounts.list');
            }
        };
        $scope.$on('$destroy', function () {
            TimelineShared.destroy();
        });
    }
]);