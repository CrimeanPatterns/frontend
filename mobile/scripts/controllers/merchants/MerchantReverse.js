angular.module('AwardWalletMobile').controller('MerchantReverseController', [
    'Translator',
    '$scope',
    '$state',
    '$http',
    'SessionService',
    'Database',
    function (Translator, $scope, $state, $http, SessionService, Database) {
        var authorized = SessionService.getProperty('authorized');

        if (authorized) {
            var profile = Database.getProperty('profile');

            function loadFaq(id) {
                $scope.view.loading = true;
                $http({
                    url: '/faq',
                    method: 'POST',
                    timeout: 30000,
                    retry: 3,
                    data: [id]
                }).then(function (response) {
                    response = response.data;
                    $scope.view.loading = false;
                    if (angular.isArray(response)) {
                        $scope.view.faq = response;
                    }
                }, function (response) {
                    $scope.view.loading = false;
                });
            }

            function loadForm() {
                $scope.view.loading = true;
                $http({
                    url: '/merchant-reverse/form',
                    method: 'GET',
                    timeout: 30000,
                    retry: 3
                }).then(function (response) {
                    response = response.data;
                    $scope.view.loading = false;
                    if (angular.isArray(response.children)) {
                        $scope.view.form = response.children;
                        $scope.view.formInterface = response.jsFormInterface;
                        $scope.view.formExtension = response.jsProviderExtension;
                    }
                }, function (response) {
                    $scope.view.loading = false;
                });
            }

            function getOffer(id) {
                $scope.view.loading = true;
                $http({
                    url: '/merchant-reverse/offer/' + id,
                    method: 'GET',
                    timeout: 30000,
                    retry: 3
                }).then(function (response) {
                    response = response.data;
                    $scope.view.loading = false;
                    if (angular.isString(response)) {
                        $scope.view.offer = response;
                    }
                }, function (response) {
                    $scope.view.loading = false;
                });

            }

            $scope.view = {
                faq: null,
                form: null,
                loading: false,
                offer: null,
                upgrade: false/*profile.Free*/,
                openUpgrade: function () {
                    window.open(window.BaseUrl + '/user/pay', '_blank');
                },
                submit: function () {
                    var selectedOption = $scope.view.form[1].selectedOption;

                    $scope.view.offer = null;

                    if (selectedOption.value !== '') {
                        getOffer(selectedOption.value);
                    }
                }
            };

            // if (profile.Free) {
            //     loadFaq(21);
            // } else {
                loadForm();
            // }
        } else {
            $state.go('unauth.login');
        }
    }
]);