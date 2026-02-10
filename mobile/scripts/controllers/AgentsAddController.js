angular.module('AwardWalletMobile').controller('AgentsAddController', [
    '$scope',
    '$q',
    '$state',
    '$stateParams',
    '$http',
    'GlobalError',
    'Form',
    function ($scope, $q, $state, $stateParams, $http, GlobalError, Form) {
        var formLink = $stateParams.formLink;

        if (formLink) {
            $scope.form = {
                link: formLink,
                buttons: {
                    submit: Translator.trans('buttons.save', {}, 'mobile')
                },
                interface: null,
                extension: null,
                fields: [],
                errors: [],
                title: $stateParams.formTitle,
                submit: function () {
                    var data = Form.parseData($scope.form.fields);
                    $scope.spinnerSubmitForm = true;
                    $http({url: formLink, method: 'POST', timeout: 30000, data: data}).then(function (response) {
                        response = response.data;
                        $scope.spinnerSubmitForm = false;
                        if (response) {
                            if (response.hasOwnProperty('success')) {
                                $scope.back('index.accounts.list', angular.extend({}, $stateParams, {requestData: response.result}));
                            }
                            if (response.hasOwnProperty('submitLabel')) {
                                $scope.form.buttons.submit = response.submitLabel;
                            }
                            if (response.hasOwnProperty('jsFormInterface')) {
                                $scope.form.interface = response.jsFormInterface;
                            }
                            if (response.hasOwnProperty('jsProviderExtension')) {
                                $scope.form.extension = response.jsProviderExtension;
                            }
                            if (response.hasOwnProperty('children')) {
                                $scope.form.fields = response.children;
                                $scope.form.errors = response.errors;
                            }
                            if (response.hasOwnProperty('error')) {
                                $scope.form.errors = [response.error];
                            }
                        }
                    }, function (response) {
                        $scope.spinnerSubmitForm = false;
                    });
                }
            };
            $scope.spinnerLoadingPage = true;
            $http({url: formLink, method: 'GET', timeout: 30000}).then(function (response) {
                $scope.spinnerLoadingPage = false;
                response = response.data;
                if (response) {
                    if (!response.hasOwnProperty('children')) {
                        GlobalError.show(GlobalError.getHttpError('default'));
                    }
                    if (response.hasOwnProperty('submitLabel')) {
                        $scope.form.buttons.submit = response.submitLabel;
                    }
                    if (response.hasOwnProperty('jsFormInterface')) {
                        $scope.form.interface = response.jsFormInterface;
                    }
                    if (response.hasOwnProperty('jsProviderExtension')) {
                        $scope.form.extension = response.jsProviderExtension;
                    }
                    if (response.hasOwnProperty('children')) {
                        $scope.form.fields = response.children;
                        $scope.form.errors = response.errors;
                    }
                    if (response.hasOwnProperty('error')) {
                        $scope.form.errors = [response.error];
                    }
                }
            }, function (response) {
                $scope.spinnerLoadingPage = false;
            });
        } else {
            $scope.back('index.accounts.list');
        }
    }
]);