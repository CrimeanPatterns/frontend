angular.module('AwardWalletMobile').controller('AccountAddController', [
    '$q',
    '$scope',
    '$state',
    '$stateParams',
    'Provider',
    'Account',
    'UpdateAccountPopup',
    'AccountList',
    'CardStorage',
    'Card',
    'GlobalError',
    function ($q, $scope, $state, $stateParams, Provider, Account, UpdateAccountPopup, AccountList, CardStorage, Card, GlobalError) {
        var requestData = {providerId: $stateParams.providerId},
            postData = angular.extend({}, requestData),
            form = $q.defer(),
            formData = $stateParams.formData,
            card = $stateParams.scanData ? $stateParams.scanData.card : null;

        if ($stateParams.requestData) {
            angular.extend(requestData, {
                requestData: btoa(JSON.stringify($stateParams.requestData))
            });
        }

        $scope.view = {
            DisplayName: null,
            Kind: null,
            form: {
                fields: null,
                error: null,
                extension: null,
                interface: null,
                spinnerSubmit: false,
                submit: function () {

                }
            },
            changeProgram: function () {
                if ($stateParams.scanData && $stateParams.scanData.promises) {
                    $state.go('index.accounts.add', {
                        scanData: $stateParams.scanData,
                        formData: $scope.view.form.fields
                    });
                }
            }
        };

        if (requestData.providerId) {
            Provider.get(requestData, function (response) {
                var fields;
                $scope.spinnerLoadingPage = false;
                if (response.hasOwnProperty('error')) {
                    $scope.view.form.errors = [response.error];
                } else if (!response.hasOwnProperty('formData')) {
                    GlobalError.show(GlobalError.getHttpError('default'));
                } else {
                    fields = response.formData.children;
                    $scope.view.Kind = response.Kind;
                    $scope.view.DisplayName = response.DisplayName;
                    $scope.view.form.errors = response.formData.errors;
                    if (response.formData.jsFormInterface) {
                        $scope.view.form.interface = response.formData.jsFormInterface;
                    }
                    if (response.formData.jsProviderExtension) {
                        $scope.view.form.extension = response.formData.jsProviderExtension;
                    }
                    if (formData) {
                        for (var i = 0, length = fields.length; i < length; i++) {
                            if (['card_images', 'barcode'].indexOf(fields[i].type) > -1)
                                for (var j = 0, l = formData.length; j < l; j++) {
                                    if (fields[i].type === formData[j].type) {
                                        fields[i] = formData[j];
                                        break;
                                    }
                                }
                        }
                    }
                    $scope.view.form.fields = fields;
                    form.resolve();
                }
            }, function () {
                $scope.spinnerLoadingPage = false;
            });
        } else {
            form.resolve();
        }

        if (
            !$stateParams.formData &&
            $stateParams.scanData &&
            $stateParams.scanData.promises
        ) {

            form.promise.then(function () {
                var promises = $stateParams.scanData.promises,
                    barcode = $stateParams.scanData.barcode,
                    data;

                for (var k = 0, l = promises.length; k < l; k++) {
                    promises[k].then(resolve.bind(this, k, l - 1))
                }

                function resolve(i, length, recognizeData) {
                    var response = recognizeData.response,
                        field, j;

                    if (!data || response.Kind !== 'custom')
                        data = response;

                    if (
                        (data.Kind !== 'custom' || i === length) && $scope.view.form.fields === null
                    ) {
                        $scope.view.scan = true;
                        if (data.ProviderId) {
                            postData.providerId = data.ProviderId;
                            $stateParams.providerId = data.ProviderId;//trick for history back from agents-add route
                        }
                        if (data.formData && data.formData.children) {
                            $scope.view.DisplayName = data.DisplayName;
                            $scope.view.Kind = data.Kind;
                            $scope.view.form.fields = data.formData.children;
                        }
                    }

                    if ($scope.view.form.fields) {
                        for (j = 0; j < $scope.view.form.fields.length, field = $scope.view.form.fields[j]; j++) {
                            if (field.type === 'card_images') {
                                if (!field.card) {
                                    field.card = card;
                                }
                                for (var side in field.value) {
                                    if (
                                        field.value.hasOwnProperty(side) &&
                                        card.images.hasOwnProperty(side)
                                    ) {
                                        field.card.images[side].Label = field.value[side].Label;
                                    }
                                }
                                field.value[recognizeData.side].CardImageId = response[recognizeData.side].CardImageId;
                                field.value[recognizeData.side].FileName = response[recognizeData.side].FileName;
                            } else if (field.type === 'barcode' && barcode) {
                                field.value = barcode;
                            } else if (
                                !field.changed &&
                                data.ProviderId === response.ProviderId &&
                                response.formData.children[j] &&
                                field.type === response.formData.children[j].type
                            ) {
                                field.value = response.formData.children[j].value;
                            }
                        }
                    }
                }

            });

        }

        $scope.view.form.submit = function () {
            var data = {};
            angular.forEach($scope.view.form.fields, function (field) {
                if (field.mapped) {
                    if (field.type === 'choice' && field.hasOwnProperty('selectedOption')) {
                        data[field.name] = field.selectedOption.value;
                    } else {
                        data[field.name] = field.value;
                    }
                }
            });
            $scope.view.form.errors = null;
            $scope.view.form.spinnerSubmit = true;
            Provider.add(postData, data, function (response) {
                    var accountId, fields;
                    $scope.view.form.spinnerSubmit = false;
                    if (response.hasOwnProperty('account')) {
                        accountId = response.account.TableName[0].toLowerCase() + response.account.ID;
                        if (card) {
                            card.setAccountId(accountId);//save to LS
                            CardStorage.add(card);
                        }
                        AccountList.addAccount(response.account);
                        if (response.hasOwnProperty('needUpdate') && response.needUpdate === true) {
                            UpdateAccountPopup.open({
                                user: $scope.user,
                                isCordova: platform.cordova,
                                account: response.account,
                                hideModal: function () {
                                    UpdateAccountPopup.close();
                                    $state.go('index.accounts.account-details', {Id: accountId});
                                },
                                firstUpdate: true
                            });
                        } else {
                            $state.go('index.accounts.account-details', {Id: accountId});
                        }
                    }
                    if (response.hasOwnProperty('formData')) {
                        fields = response.formData.children;
                        if (response.formData.hasOwnProperty('errors'))
                            $scope.view.form.errors = response.formData.errors;
                        if (card) {
                            for (var i = 0, l = fields.length, field; i < l, field = fields[i]; i++) {
                                if (field.type === 'card_images') {
                                    field.card = card;
                                    break;
                                }
                            }
                        }
                        $scope.view.form.fields = fields;
                    }
                    if (response.hasOwnProperty('existingAccountId')) {
                        var params = {
                            displayName: response.displayName,
                            login: response.login,
                            name: response.name,
                            url: $state.href('index.accounts.account-details', {
                                Id: 'a' + response.existingAccountId
                            })
                        };
                        var message = Translator.trans(/** @Desc("<p>You are adding a new %displayName% account with the following logon name: %login%, however this account has already been added to name: %name% in your profile. You have two options: </p><p>1. Use a different logon name (a different account) </p><a href='%url%'><span>2. Edit the existing account which has already been added.</span><i class='icon-next-arrow'></i></a>") */ 'accounts-add.errors.existing-account', params, 'mobile');
                        $scope.view.form.errors = [message];
                    }
                    if (response.hasOwnProperty('error')) {
                        $scope.view.form.errors = [response.error];
                    }
                },
                function () {
                    $scope.view.form.spinnerSubmit = false;
                }
            );
        };

        $scope.$watch(function () {
            return $state.params.error;
        }, function (error) {
            if (error) {
                GlobalError.show(atob(error.replace(/_/g, '/')));
            }
        });

        $scope.$on('$destroy', function () {
            UpdateAccountPopup.close();
        });
    }
]);