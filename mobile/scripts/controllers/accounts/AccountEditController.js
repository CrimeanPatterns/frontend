angular.module('AwardWalletMobile').controller('AccountEditController', [
    '$scope',
    '$stateParams',
    '$filter',
    '$state',
    'Account',
    'btfModal',
    'UpdateAccountPopup',
    'UpdateSecurityQuestionPopup',
    'AccountList',
    'GlobalError',
    function ($scope, $stateParams, $filter, $state, Account, btfModal, UpdateAccountPopup, UpdateSecurityQuestionPopup, AccountList, GlobalError) {
        var account = AccountList.getAccount($stateParams['Id']),
            formData = $stateParams.formData;

        if (account && account['Access']['edit']) {

            $scope.view = {
                account: account,
                form: {},
                accountId: $stateParams['Id']
            };

            var requestData = {
                accountId: account['ID']
            }, postData = {};

            requestData.type = account.TableName.toLowerCase();

            if(['passport', 'traveler-number'].includes(account.Type)) {
                requestData.type = 'document';
            }

            postData = angular.extend({}, requestData);

            if ($stateParams['requestData']) {
                angular.extend(requestData, {requestData: btoa(JSON.stringify($stateParams['requestData']))});
            }

            $scope.spinnerLoadingPage = true;

            Account.getResource().query(requestData, function (response) {
                var fields;
                $scope.spinnerLoadingPage = false;
                if (!response.hasOwnProperty('formData')) {
                    GlobalError.show(GlobalError.getHttpError('default'));
                } else if (response.hasOwnProperty('error')) {
                    $scope.view['form']['errors'] = [response.error];
                } else {
                    fields = response['formData']['children'];
                    $scope.view['form']['errors'] = response['formData']['errors'];
                    if (response['formData']['jsFormInterface']) {
                        $scope.view['form']['interface'] = response['formData']['jsFormInterface'];
                    }
                    if (response['formData']['jsProviderExtension']) {
                        var extensions;
                        if (typeof response['formData']['jsProviderExtension'] === 'string') {
                            extensions = [response['formData']['jsProviderExtension']];
                        } else {
                            extensions = response['formData']['jsProviderExtension'];
                        }
                        extensions.push(function(extension) {
                            extension.onFieldChange = function(form, fieldName) {
                                if (fieldName === 'TransferFromProvider') {
                                    var field = form.getField('TransferFromProvider');
                                    if (
                                        field &&
                                        field.row &&
                                        field.row.additionalData &&
                                        field.row.additionalData.currency
                                    ) {
                                        form.setValue('TransferProviderCurrency', field.row.additionalData.currency);
                                    }
                                }
                            };
                        });

                        $scope.view['form']['extension'] = extensions;
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
                    $scope.view['form']['fields'] = fields;
                }
            }, function (response) {
                $scope.spinnerLoadingPage = false;
            });

            $scope.submit = function () {
                var data = {};
                $scope.view['error'] = '';
                angular.forEach($scope.view['form']['fields'], function (field) {
                    if (field.mapped) {
                        if (field['type'] === 'choice' && field.selectedOption) {
                            data[field['name']] = field.selectedOption['value'];
                        } else if (!(field['type'] === 'passwordEdit' && field['changed'] !== true))
                            data[field['name']] = field['value'];
                    }
                });
                $scope.spinnerSubmitForm = true;
                Account.getResource().save(postData, data, function (response) {
                    $scope.spinnerSubmitForm = false;
                    if (response && response.account) {
                        AccountList.setAccount(response.account);
                        if (response.hasOwnProperty('needUpdate') && response['needUpdate'] === true) {
                            UpdateAccountPopup.open({
                                isCordova: platform.cordova,
                                user: $scope.user,
                                account: response.account,
                                hideModal: function () {
                                    UpdateAccountPopup.close();
                                    $state.go('index.accounts.account-details', {Id: response.account.TableName[0].toLowerCase() + response.account.ID});
                                }
                            });
                        } else {
                            $state.go('index.accounts.account-details', {Id: response.account.TableName[0].toLowerCase() + response.account.ID});
                        }
                    }
                    if (response.hasOwnProperty('formData')) {
                        $scope.view['form']['fields'] = response['formData']['children'];
                        $scope.view['form']['errors'] = response['formData']['errors'];
                    }
                    if (response.hasOwnProperty('existingAccountId')) {
                        var params = {
                            displayName: response['displayName'],
                            login: response['login'],
                            name: response['name'],
                            url: $state.href('index.accounts.account-details', {Id: 'a' + response['existingAccountId']})
                        };
                        var message = Translator.trans('accounts-add.errors.existing-account', params, 'mobile');
                        $scope.view['form']['errors'] = [message];
                    }
                    if (response.hasOwnProperty('error')) {
                        $scope.view['form']['errors'] = [response.error];
                    }
                }, function (response) {
                    $scope.spinnerSubmitForm = false;
                });
            };

            $scope.$watch(function () {
                return $state.params.error;
            }, function (error) {
                if (error) {
                    GlobalError.show(atob(error.replace(/_/g, '/')));
                }
            });

        } else {
            $state.go('index.accounts.list');
        }

        $scope.$on('$destroy', function () {
            UpdateAccountPopup.close();
            UpdateSecurityQuestionPopup.close();
        });

    }
]);