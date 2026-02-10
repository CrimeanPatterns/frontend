angular.module('AwardWalletMobile').controller('TimelineAlternativeFlightsController', [
    '$scope',
    '$filter',
    'AutoLogin',
    function ($scope, $filter, AutoLogin) {
        if ($scope.segment) {
            $scope.flights = $scope.segment.menu.alternativeFlights;
            $scope.isObject = function (obj) {
                return Object.prototype.toString.call(obj) === '[object Object]';
            };
            $scope.isArray = function (arr) {
                return Object.prototype.toString.call(arr) === '[object Array]';
            };
            $scope.showAlternativeFlights = function (trip, dates) {
                var params = {Trip: trip, Dates: []};
                if (angular.isArray(dates)) {
                    angular.forEach(dates, function (date) {
                        if(date.ts) {
                            params.Dates.push(date.ts);
                        }else{
                            params.Dates.push($filter('fmt')(date.fmt) / 1000);
                        }
                    });
                }else{
                    if(dates.ts) {
                        params.Dates.push(dates.ts);
                    }else{
                        params.Dates.push($filter('fmt')(dates.fmt) / 1000);
                    }
                }
                AutoLogin.showFlightStatus('kayak', params);
            };
        }
    }
]);