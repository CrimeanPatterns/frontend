angular.module('AwardWalletMobile').controller('BookingController', [
    '$scope',
    '$rootScope',
    '$state',
    'Booking',
    function ($scope, $rootScope, $state, Booking) {
        $scope.requests = Booking.getRequests();
        $scope.$on('booking:update', function () {
            $scope.requests = Booking.getRequests();
        });
        Booking.connect();
        $scope.booking = {
            desktop: {
                add: function () {
                    var url = BaseUrl + '/awardBooking/add';
                    if (platform.cordova)
                        url += '?fromapp=1&KeepDesktop=1';
                    window.open(url, '_blank');
                }
            },
            add: function () {
                if (platform.cordova && platform.ipad) {
                    $scope.booking.desktop.add();
                } else {
                    $state.go('index.booking.new-request');
                }
            },
            edit: function (requestId) {
                var url = BaseUrl + '/awardBooking/edit/' + requestId;
                if (platform.cordova)
                    url += '?fromapp=1&KeepDesktop=1';
                window.open(url, '_blank');
            },
            email: {
                resend: function (requestId) {
                    $scope.booking.email.spinnerLoading = true;
                    Booking.resend(requestId).then(function () {
                        $scope.booking.email.spinnerLoading = false;
                        $scope.booking.email.submitted = true;
                    }, function () {
                        $scope.booking.email.spinnerLoading = false;
                    });
                },
                spinnerLoading: false,
                submitted: false
            }
        }
    }
]);