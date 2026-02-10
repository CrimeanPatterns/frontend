angular.module('AwardWalletMobile').controller('StoreLocation', [
    '$scope',
    '$stateParams',
    '$filter',
    '$state',
    'Account',
    'btfModal',
    'UpdateAccountPopup',
    'UpdateSecurityQuestionPopup',
    'AccountList',
    'AccountStoreLocation',
    'GlobalError',
    '$cordovaDialogs',
    'SessionService',
    '$cordovaPush',
    function ($scope, $stateParams, $filter, $state, Account, btfModal, UpdateAccountPopup, UpdateSecurityQuestionPopup, AccountList, AccountStoreLocation, GlobalError, $cordovaDialogs, SessionService, $cordovaPush) {

        var requestData = {}, account;

        var translations = [ /* Uses in native application */
            Translator.trans(/** @Desc("Needed to deliver retail shopping loyalty cards (with barcodes) to your home screen when you come to the store.") */'location.always-usage', {}, 'mobile'),
            Translator.trans(/** @Desc("Needed to get your current location so that you can see where you are on the map.") */'location.when-in-use', {}, 'mobile'),
            Translator.trans(/** @Desc("You can enable access to the location service by checking “Always” in your settings under: Settings -> Location.") */'location.enable-service', {}, 'mobile'),
            Translator.trans(/** @Desc("This app does not have access to Location service") */'location.enable-service.title', {}, 'mobile'),

            Translator.trans(/** @Desc("Location Settings") */'location.android.location-settings.button', {}, 'mobile')
        ];

        function getFieldIndexByName(fields, name) {
            var index;
            for (var i = 0, l = fields.length; i < l; i++) {
                if (fields[i].name === name) {
                    index = i;
                    continue;
                }
            }
            return index;
        }

        if ($stateParams.locationId) {
            requestData.id = $stateParams.locationId;
            requestData.type = 'edit';
            requestData.subAction = null;
        }

        if ($stateParams.accountId) {
            account = AccountList.getAccount($stateParams.accountId);
            if (account && account['Access']['edit']) {
                if (!$stateParams.locationId) {
                    requestData.id = $stateParams.accountId.substr(1);
                    requestData.subId = $stateParams.subId;
                    if (account.TableName)
                        requestData.type = account.TableName.toLowerCase();
                }
            } else {
                $state.go('index.accounts.account-details', {Id: $stateParams.accountId, subId: $stateParams.subId});
                return;
            }
        }

        function submit(response) {
            $scope.spinnerSubmitForm = false;
            if (response.hasOwnProperty('account')) {
                if (platform.cordova) {
                    $cordovaPush.checkLocationPermission();
                }
                AccountList.setAccount(response.account);
                if ($stateParams.accountId) {
                    $scope.back('index.accounts.account-details', {
                        Id: $stateParams.accountId,
                        subId: $stateParams.subId
                    });
                } else {
                    $scope.back('index.accounts.account-details', {
                        Id: response.account.TableName[0].toLowerCase() + response.account.ID
                    });
                }
            }
            if (response.hasOwnProperty('warning') && response.warning) {
                $state.go('index.accounts.store-location.warning', $stateParams);
                return;
            }
            if (response.hasOwnProperty('formData')) {
                $scope.view.form.fields = response.formData.children;
                $scope.view.form.errors = response.formData.errors;
            }
            if (response.hasOwnProperty('error')) {
                $scope.view.form.errors = [response.error];
            }
            if (response.hasOwnProperty('locations') && response.locations instanceof Object) {
                SessionService.setProperty('locations-total', response.locations.total || 0);
                SessionService.setProperty('locations-tracked', response.locations.tracked || 0);
            }
        }

        function fail() {
            $scope.spinnerSubmitForm = false;
            $scope.spinnerRemove = false;
        }

        $scope.view = {
            access: account && $stateParams.locationId ? account.Access : {},
            stateParams: {Id: $stateParams.accountId, subId: $stateParams.subId},
            DisplayName: null,
            Login: null,
            Kind: null,
            form: {
                label: null,
                fields: null,
                error: null,
                extension: null,
                interface: null,
                spinnerSubmit: false,
                submit: function () {
                    var data = {};
                    var mapField = $scope.view.form.fields[getFieldIndexByName($scope.view.form.fields, 'map')];
                    angular.forEach($scope.view.form.fields, function (field) {
                        if (field.mapped) {
                            if (field.type === 'choice' && field.hasOwnProperty('selectedOption')) {
                                data[field.name] = field.selectedOption.value;
                            } else if (field.name === 'name') {
                                data[field.name] = mapField.attr.name;
                            } else if (field.name === 'lat') {
                                data[field.name] = mapField.attr.lat;
                            } else if (field.name === 'lng') {
                                data[field.name] = mapField.attr.lng;
                            } else {
                                data[field.name] = field.value;
                            }
                        }
                    });

                    $scope.spinnerSubmitForm = true;

                    if ($stateParams.locationId) {
                        AccountStoreLocation.getResource().save(requestData, data, submit, fail);
                    } else {
                        AccountStoreLocation.getResource().add(requestData, data, submit, fail);
                    }
                }
            },
            remove: function () {
                $cordovaDialogs.confirm(
                    Translator.trans(/** @Desc("Please confirm you want to delete this location") */'delete.location', {}, 'mobile'),
                    Translator.trans('alerts.text.confirm', {}, 'messages'),
                    [
                        Translator.trans('button.delete', {}, 'messages'),
                        Translator.trans('cancel', {}, 'messages')
                    ]
                ).then(function (button) {
                    if (button === 1) {
                        $scope.spinnerRemove = true;
                        AccountStoreLocation.getResource().remove({id: $stateParams.locationId}, {}, function (response) {
                            $scope.spinnerRemove = false;
                            submit(response);
                        }, fail);
                    }
                });
            }
        };

        $scope.spinnerLoadingPage = true;

        AccountStoreLocation.getResource().get(requestData, function (response) {
            var fields;
            $scope.spinnerLoadingPage = false;
            if (!response.hasOwnProperty('formData')) {
                GlobalError.show(GlobalError.getHttpError('default'));
            } else if (response.hasOwnProperty('error')) {
                $scope.view.form.errors = [response.error];
            } else {
                fields = response.formData.children;
                if (response.formData.jsFormInterface) {
                    $scope.view.form.interface = response.formData.jsFormInterface;
                }
                if (response.formData.jsProviderExtension) {
                    $scope.view.form.extension = response.formData.jsProviderExtension;
                }
                fields[getFieldIndexByName(fields, 'map')].attr = {
                    name: fields[getFieldIndexByName(fields, 'name')].value,
                    lat: fields[getFieldIndexByName(fields, 'lat')].value,
                    lng: fields[getFieldIndexByName(fields, 'lng')].value
                };
                $scope.view.DisplayName = response.DisplayName;
                $scope.view.Login = response.Login;
                $scope.view.Kind = response.Kind;
                $scope.view.form.fields = fields;
                $scope.view.form.errors = response.formData.errors;
                $scope.view.form.label = response.formData.submitLabel;
            }
        }, function (response) {
            $scope.spinnerLoadingPage = false;
        });
    }
]);