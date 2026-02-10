angular.module('AwardWalletMobile').controller('TimelineController', ['$scope', '$stateParams', '$state', 'Timeline',
    function ($scope, $stateParams, $state, Timeline) {
        $scope.travelers = Timeline.getTravelers();
        $scope.traveler = Timeline.getTraveler($stateParams.Id);
        if (!$scope.traveler && !$stateParams.hasOwnProperty('SharedKey')) {
            $state.go('index.timeline.travelers');
        }
        $scope.$on('timeline:update', function () {
            $scope.travelers = Timeline.getTravelers();
            $scope.traveler = Timeline.getTraveler($stateParams.Id);
        });
    }
]);