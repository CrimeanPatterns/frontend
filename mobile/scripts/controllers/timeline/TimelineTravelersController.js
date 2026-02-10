angular.module('AwardWalletMobile').controller('TimelineTravelersController', ['$scope', '$stateParams', '$state',
    function ($scope, $stateParams, $state) {
        if ($scope.travelers.length < 2) {
            $state.go('index.timeline.list', {Id: 'my'});
        }
    }
]);