angular.module('AwardWalletMobile').controller('TimelinePhonesController', [
    '$scope',
    '$filter',
    function ($scope, $filter) {
        $scope.call = function (phone) {
            window.open('tel:' + phone.replace(/[^\d.]/g, ''), '_system');
        };
        var phones = [];
        var page = $scope.segment.menu.phones;
        if ($scope.segment.menu && page && page.groups) {
            page.groups.forEach(function (group) {
                var groupPhones = $filter('filter')(page.phones, {group: group.name}), orderPhones;
                if(group.order){
                    orderPhones = group.order;
                    if(orderPhones[0] && orderPhones[0] == 'geo'){
                        orderPhones[0] = function(phone){
                            if (($scope.user && $scope.user.country && phone.countryCode == $scope.user.country) || (page.ownerCountry && phone.countryCode == page.ownerCountry)) {
                                return 1;
                            }
                        }
                    }
                    groupPhones = $filter('orderBy')(groupPhones, orderPhones);
                    orderPhones = null;
                }
                phones = phones.concat(groupPhones);
                groupPhones = null;
            });
        }
        $scope.phones = phones;
    }
]);