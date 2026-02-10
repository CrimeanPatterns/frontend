define(['angular-boot', 'pages/accounts/services/pendingService', 'directives/dialog', 'filters/unsafe'], function (angular) {
    angular = angular && angular.__esModule ? angular.default : angular;

    angular.module('pending-directive', ['pendingService', 'dialog-directive', 'unsafe-mod'])
        .directive('pendingAccounts', function (pendingService, dialogService, $filter, $timeout) {
            return {
                link: function (scope) {
                    pendingService.get().then(function (data) {
                        scope.data = data;
                        pendingService.checkIdsTimout();
                    });

                    var dialog = dialogService.get("account-pending");
                    var dialogScope = angular.element(dialog.element).isolateScope();
                    var formScope = angular.element('form[name="pendingForm"]').scope();

                    dialogScope.pendings = {};

                    dialogScope.fillModel = function (field) {
                        if (field.value && field.type != 'password')
                            dialogScope.pendings[field.full_name] = field.value;
                        else
                            dialogScope.pendings[field.full_name] = '';
                    };

                    dialogScope.$watch('pendings', function () {
                        formScope.submitted = false;
                        $('#pending-save').attr('disabled', dialogScope.pendings.apply);
                    }, true);

                    dialogScope.submit = function (formScope, evt) {
                        evt.preventDefault();

                        formScope.submitted = true;
                        if (formScope.pendingForm.$valid) {
                            $('#pending-save').attr('disabled', true).addClass('loader');
                            pendingService.save(dialogScope.pendings, dialogScope.account.id).always(function (result) {
                                if (result.success) {
                                    window.location = Routing.generate('aw_account_edit', {accountId: dialogScope.account.id, autosubmit: true});
                                } else {
                                    $('#pending-save').attr('disabled', false).removeClass('loader')
                                }
                            }).fail(function () {
                                $('#pending-save').attr('disabled', false).removeClass('loader')
                            });
                        }
                    };

                    scope.$watch('data', function (nv) {
                        if (nv) {
                            $timeout(function () {
                                scope.accounts = $filter('filter')(scope.data, function (account) {
                                    return account.state != -100 && !pendingService.isStoredId(account.id);
                                });
                                if (scope.accounts.length) {
                                    dialogScope.account = scope.accounts[0];
                                    dialogScope.cnt = scope.accounts.length;

                                    dialogScope.account.title = Translator.trans('award.account.popup.pending.title', {'displayName': dialogScope.account.displayName});
                                    dialog.setOption('title', Translator.trans(
                                        /** @Desc("Set up new %displayName% account") */
                                        'pending.popup.title', {'displayName': $('<div/>').html(dialogScope.account.displayName).text()}));

                                    dialog.setOption('buttons', [
                                        {
                                            text: 'Delete',
                                            click: function () {
                                                $('#pending-delete').addClass('loader');
                                                var accounts = dialogScope.pendings.apply ? scope.accounts : [dialogScope.account];
                                                pendingService.remove(accounts).then(function (res) {
                                                    if (res.data.success) {
                                                        accounts.forEach(function (account) {
                                                            account.state = -100;
                                                        });
                                                    }
                                                    $('#pending-delete').removeClass('loader');
                                                    dialogScope.$applyAsync();
                                                });
                                            },
                                            'class': 'btn-silver f-left',
                                            id: "pending-delete"
                                        },
                                        {
                                            text: 'Skip',
                                            click: function () {
                                                var accounts = dialogScope.pendings.apply ? scope.accounts : [dialogScope.account];
                                                accounts.forEach(function (account) {
                                                    account.state = -100;
                                                    pendingService.storePendingId(account.id);
                                                });
                                                dialogScope.$applyAsync();
                                            },
                                            'class': 'btn-silver f-left'
                                        },
                                        {
                                            text: 'Save',
                                            id: 'pending-save',
                                            click: function () {
                                                $('form[name="pendingForm"]').submit();
                                            },
                                            'class': 'btn-blue'
                                        }
                                    ]);
                                    $timeout(function () {
                                        dialog.open();
                                    }, 1000);
                                } else {
                                    dialog.close();
                                }
                            }, 1);
                        }
                    }, true)
                }
            }
        })
});