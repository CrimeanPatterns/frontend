/* global reloadPageContent */

define([
    'angular-boot',
    'jquery-boot',
    'lib/reauthenticator',
    'lib/design',
    /*'angular-hotkeys', */ 'translator-boot',
    'filters/highlight',
    'filters/unsafe',
    'directives/customizer',
    'directives/dialog',
    'directives/autoFocus',
    'webpack-ts/shim/ngReact',
], function (angular, $, reauth) {
    angular = angular && angular.__esModule ? angular.default : angular;

    angular
        .module('accountListActionsModule', [
            'appConfig',
            'react',
            'ui.router' /*'cfp.hotkeys',*/,
            'customizer-directive',
            'dialog-directive',
            'auto-focus-directive',
        ])
        .constant('constants', {
            maxDates: 3,
            savePasswordDatabase: 1,
            savePasswordLocally: 2,
        })
        .controller('checkerCtrl', [
            'DI',
            function (di) {
                var ListChecker = di.get('checker-manager');

                var checker = this;

                checker.view = ListChecker.getState();
                checker.checks = ListChecker.getCheckState();
                checker.toggleAll = function () {
                    if (checker.view.checked) {
                        checker.resetAll();
                    } else {
                        checker.checkAll();
                    }
                };
                checker.checkAll = ListChecker.checkAll;
                checker.resetAll = ListChecker.resetAll;
                checker.select = ListChecker.select;
            },
        ])
        .controller('actionsCtrl', [
            '$scope',
            'DI',
            function ($scope, di) {
                var ListChecker = di.get('checker-manager'),
                    ListActions = di.get('actions-manager');

                var actions = this;

                actions.view = ListActions.getState();
                actions.state = ListActions.getActionsState();
                actions.action = function (action) {
                    ListActions.go(action, ListChecker.getChecked());
                };
            },
        ])
        .controller('optionsCtrl', [
            'DI',
            'dialogService',
            function (di, dialogService) {
                var User = di.get('user'),
                    ListManager = di.get('manager');

                var options = this;

                options.view = {
                    needPaymentPopup: function (event) {
                        if (!User.isAwPlus()) {
                            event.preventDefault();
                            var dialog = dialogService.get('need-upgrade'),
                                url = Routing.generate('aw_users_pay', {
                                    BackTo: '/account/list',
                                });
                            dialog.setOption('buttons', [
                                {
                                    text: Translator.trans('form.button.cancel'),
                                    click: function () {
                                        $(this).dialog('close');
                                    },
                                    class: 'btn-silver',
                                    tabindex: -1,
                                },
                                {
                                    text: Translator.trans('button.ok'),
                                    click: function () {
                                        window.location.href = url;
                                    },
                                    class: 'btn-blue',
                                },
                            ]);
                            dialog.open();
                        }
                    },
                    reload: function () {
                        reloadPageContent();
                    },
                };

                if (true === $('#need-upgrade').data('popup-open')) {
                    options.view.needPaymentPopup($.Event('click'));
                }
            },
        ])
        .controller('searchCtrl', [
            '$location',
            '$timeout',
            '$document',
            'DI',
            '$scope',
            '$transitions',
            '$sce' /*'hotkeys',*/,
            function ($location, $timeout, $document, di, $scope, $transitions, $sce /*, hotkeys*/) {
                var ListManager = di.get('manager');

                var search = this;
                var hash = $location.hash();
                var firstLoad = true;
                search.search = '';
                $scope.hintsArray = [];
                $scope.hintsDefault = [];

                const DROPDOWN_LIST = 'ul[data-id="list-accounts"]';
                const INPUT_SEARCH = 'input.search-row';

                $(document).on('click', function () {
                    if (!$(INPUT_SEARCH).is(':focus')) {
                        $(DROPDOWN_LIST).hide();
                    }
                });

                $(INPUT_SEARCH).on('click keypress', function () {
                    if (firstLoad) {
                        firstLoad = false;
                        search.accountMenuToggle();
                    }
                });

                /**
                 * Controller initialization.
                 *
                 * @param {Object} data
                 * @param {array} data.custom user filters.
                 * @param {array} data.default dynamic filters (default).
                 */
                $scope.init = function (data) {
                    $scope.hintsDefault = data.default;
                    $scope.hintsArray = data.custom.concat(data.default);
                };

                if (hash) {
                    search.search = decodeURIComponent(hash.replace(/\+/g, '%20'));
                    ListManager.setSearch(this.search);
                    ListManager.build();
                }

                $transitions.onSuccess({ to: 'list' }, () => {
                    const hash = $location.hash();
                    const query = decodeURIComponent(hash.replace(/\+/g, '%20'));
                    if (query && query !== search.search) {
                        search.search = query;
                        ListManager.setSearch(query);
                        ListManager.build();
                    }
                });

                search.reset = function () {
                    search.search = '';
                    $location.hash(null);
                    ListManager.setSearch(search.search);
                    ListManager.build();
                };

                $scope.$on('search.reset', search.reset);

                search.change = function () {
                    if (this.search) {
                        $document.scrollToElement($('.main-ui-view'), 100, 100);
                    }
                    ListManager.setSearch(this.search);
                    ListManager.build();
                };

                search.keypress = function () {
                    if (search.val().length > 0) {
                        search.addClass('focus');
                    } else {
                        search.removeClass('focus');
                    }
                };

                search.accountMenuToggle = function () {
                    if ($(INPUT_SEARCH).is(':focus') && !firstLoad) {
                        $(DROPDOWN_LIST).show();
                    } else {
                        $(DROPDOWN_LIST).hide();
                    }
                };

                /**
                 * @param {string} query search filter.
                 */
                search.accountMenuSearch = function (query) {
                    $(INPUT_SEARCH).val(query);
                    $(INPUT_SEARCH).trigger('change');
                };

                search.accountMenuUpdate = function () {
                    if ($(INPUT_SEARCH).val() !== '' && !$scope.hintsArray.includes($(INPUT_SEARCH).val())) {
                        $.ajax({
                            type: 'POST',
                            url: '/account/search-hints',
                            data: JSON.stringify({ value: $(INPUT_SEARCH).val() }),
                            dataType: 'json',
                            contentType: 'application/json',
                            success: function (response) {
                                $scope.hintsArray = response.data.concat($scope.hintsDefault);
                            },
                        });
                    }
                };

                /**
                 * @param {string} value link content.
                 * @returns {string}
                 */
                search.formatContent = function (value) {
                    return $sce.trustAsHtml(
                        value.replace(/([&()\-<->]|\bor\b|\band\b)/gi, '<span class="highlight">$&</span>'),
                    );
                };

                //function hotkeysInit() {
                //	var commandFCallback = function (event) {
                //		event.preventDefault();
                //		var e = $("[data-ng-model='search.search']");
                //		$timeout(function () {
                //			e.blur().focus().select();
                //		});
                //	};
                //	hotkeys.add({
                //		combo: 'command+f',
                //		callback: commandFCallback,
                //		allowIn: ['input']
                //	});
                //	hotkeys.add({
                //		combo: 'ctrl+f',
                //		callback: commandFCallback,
                //		allowIn: ['input']
                //	});
                //}

                //hotkeysInit();
            },
        ])
        .controller('deleteActionCtrl', [
            '$rootScope',
            'DI',
            'dialogService',
            function ($rootScope, di, dialogService) {
                var Accounts = di.get('accounts'),
                    ListManager = di.get('manager'),
                    ListActions = di.get('actions-manager');

                var deleteAction = this;

                function action(checked) {
                    var dialog = dialogService.get('delete-action');
                    dialog.element.find('#delete-action-text').html(
                        Translator.transChoice('award.account.popup.delete', checked.length, {
                            accounts: checked.length,
                        }),
                    );
                    dialog.setOption('buttons', [
                        {
                            text: Translator.trans('button.no'),
                            click: function () {
                                $(this).dialog('close');
                            },
                            class: 'btn-silver',
                            tabindex: -1,
                        },
                        {
                            text: Translator.trans('button.yes'),
                            click: function () {
                                $('#delete-action-yes').addClass('loader').prop('disabled', true);
                                // todo fail!
                                Accounts.remove(checked).then(function (data) {
                                    var counters = di.get('counters').getCounters();

                                    ListManager.removeAccounts();
                                    $(window).trigger('persons.update', di.get('counters').getOwnerCounters());
                                    $(window).trigger('totalAccounts.update', di.get('counters').getTotals().total);
                                    //$rootScope.$apply();
                                    dialog.close();
                                });
                            },
                            class: 'btn-blue',
                            id: 'delete-action-yes',
                        },
                    ]);
                    dialog.open();
                }

                ListActions.setAction('deleteAction', action, false, {
                    icon: 'icon-delete',
                    text: Translator.trans('award.account.list.delete-selected'),
                    group: 'delete',
                });
            },
        ])
        .controller('setStorageActionCtrl', [
            '$rootScope',
            '$timeout',
            'DI',
            'dialogService',
            'constants',
            function ($rootScope, $timeout, di, dialogService, constants) {
                var Accounts = di.get('accounts'),
                    ListActions = di.get('actions-manager');

                var setStorageAction = this;

                function action(checked) {
                    var savePassword = null;
                    angular.forEach(checked, function (id) {
                        var account = Accounts.getAccount(id);
                        if (account) {
                            if (savePassword == null || savePassword == account.SavePassword)
                                savePassword = account.SavePassword;
                            else savePassword = -1;
                        }
                    });
                    switch (savePassword) {
                        case constants.savePasswordDatabase:
                            setStorageAction.storage = 'database';
                            break;
                        case constants.savePasswordLocally:
                            setStorageAction.storage = 'local';
                            break;
                        default:
                            setStorageAction.storage = null;
                    }

                    // init
                    var dialog = dialogService.get('set-storage-action'),
                        dialogText = dialog.element.find('#set-storage-action-text'),
                        dialogForm = dialog.element.find('form').first();

                    function setStorage() {
                        if (!setStorageAction.storage) {
                            dialog.element.parent().effect('shake');
                            return;
                        }
                        $('#set-storage-action-yes').addClass('loader').prop('disabled', true);
                        $.ajax({
                            url: Routing.generate('aw_account_store_passwords'),
                            type: 'POST',
                            data: { accounts: checked, storage: setStorageAction.storage },
                            success: function (updated) {
                                di.get('manager').updateAccounts(updated);
                                $rootScope.$apply();
                                dialog.close();
                            },
                        }).fail(function () {
                            dialog.close();
                        });
                    }

                    dialogForm.submit(function (e) {
                        e.preventDefault();
                        setStorage();
                    });

                    dialog.setOption('open', function () {
                        $timeout(function () {
                            $('#set-storage-action-save-database').focus();
                        });
                    });

                    // start
                    dialogText.html(
                        Translator.transChoice('award.account.popup.set-storage', checked.length, {
                            accounts: checked.length,
                        }),
                    );
                    dialog.setOption('buttons', [
                        {
                            text: Translator.trans('alerts.btn.cancel'),
                            click: function () {
                                $(this).dialog('close');
                            },
                            class: 'btn-silver',
                            tabindex: -1,
                        },
                        {
                            text: Translator.trans('form.button.save'),
                            click: setStorage,
                            class: 'btn-blue',
                            id: 'set-storage-action-yes',
                        },
                    ]);
                    dialog.open();
                }

                /**
                 *
                 * @param {AccountData} account
                 * @returns {boolean}
                 */
                function test(account) {
                    return (
                        account.TableName == 'Account' &&
                        !account.isCustom &&
                        account.Access.edit &&
                        account.CanSavePassword == null
                    );
                }
                ListActions.setAction('setStorage', action, test, {
                    icon: 'icon-change-password',
                    text: Translator.trans('award.account.list.menu.actions.passwords'),
                });
            },
        ])
        .controller('setGoalActionCtrl', [
            '$rootScope',
            '$timeout',
            'DI',
            'dialogService',
            function ($rootScope, $timeout, di, dialogService) {
                var Accounts = di.get('accounts'),
                    ListActions = di.get('actions-manager');

                var setGoalAction = this;

                function action(checked) {
                    setGoalAction.goal = null;

                    // init
                    var dialog = dialogService.get('set-goal-action'),
                        dialogText = dialog.element.find('#set-goal-action-text'),
                        dialogForm = dialog.element.find('form').first();

                    function setGoal() {
                        $('#set-goal-action-yes').addClass('loader').prop('disabled', true);
                        $.ajax({
                            url: Routing.generate('aw_account_json_setgoal'),
                            type: 'POST',
                            data: { form: { accounts: checked, goal: setGoalAction.goal } },
                            success: function (data) {
                                di.get('manager').updateAccounts(data.accounts);
                                $rootScope.$apply();
                                dialog.close();
                            },
                        }).fail(function () {
                            dialog.close();
                        });
                    }

                    dialogForm.submit(function (e) {
                        e.preventDefault();
                        setGoal();
                    });

                    dialog.setOption('open', function () {
                        $timeout(function () {
                            $('#set-goal-action-goal').focus();
                        });
                    });

                    // start
                    dialogText.html(
                        Translator.transChoice('award.account.popup.set-goal', checked.length, {
                            accounts: checked.length,
                        }),
                    );
                    dialog.setOption('buttons', [
                        {
                            text: Translator.trans('form.button.cancel'),
                            click: function () {
                                $(this).dialog('close');
                            },
                            class: 'btn-silver',
                            tabindex: -1,
                        },
                        {
                            text: Translator.trans('form.button.save'),
                            click: setGoal,
                            class: 'btn-blue',
                            id: 'set-goal-action-yes',
                        },
                    ]);
                    dialog.open();
                }

                /**
                 *
                 * @param {AccountData} account
                 * @returns {boolean}
                 */
                function test(account) {
                    return account.TableName == 'Account' && account.Access.edit;
                }

                ListActions.setAction('setGoal', action, test, {
                    icon: 'icon-set-account-goals',
                    text: Translator.trans('award.account.list.menu.actions.goals'),
                });
            },
        ])

        .controller('enableActionCtrl', [
            '$rootScope',
            'DI',
            'dialogService',
            function ($rootScope, di, dialogService) {
                var ListActions = di.get('actions-manager');
                $rootScope.disabled = true;

                function action(checked) {
                    var dialog = dialogService.get('enable-action');
                    dialog.setOption('buttons', [
                        {
                            text: Translator.trans('button.no'),
                            click: function () {
                                $(this).dialog('close');
                            },
                            class: 'btn-silver',
                            tabindex: -1,
                        },
                        {
                            text: Translator.trans('button.yes'),
                            click: function (e) {
                                var btn = $(e.target).parents('.ui-dialog-buttonset').find('button.btn-blue');
                                btn.addClass('loader').prop('disabled', true);
                                $.post(
                                    Routing.generate('aw_account_json_enable_disable'),
                                    { ids: checked, disabled: $rootScope.disabled },
                                    function (resp) {
                                        di.get('manager').updateAccounts(resp.accounts);
                                        $rootScope.$apply();
                                        dialog.close();
                                    },
                                );
                            },
                            class: 'btn-blue',
                        },
                    ]);
                    dialog.open();
                }

                function testEnable(account) {
                    return account.Disabled && account.Access.edit;
                }

                function testDisable(account) {
                    return !account.isCustom && !account.Disabled && account.Access.edit;
                }

                di.get('checker-manager').setCheck(
                    'disabled',
                    function (item) {
                        return item.Disabled;
                    },
                    {
                        icon: 'icon-disable',
                        text: Translator.trans(/** @Desc("Disabled accounts") */ 'disabled.accounts'),
                    },
                );

                ListActions.setAction(
                    'disable',
                    function (checkedAccounts) {
                        $rootScope.disabled = true;
                        action(checkedAccounts);
                    },
                    testDisable,
                    {
                        icon: 'icon-disable',
                        text: Translator.trans(/** @Desc("Disable accounts") */ 'disable.accounts'),
                    },
                );

                di.get('checker-manager').setCheck(
                    'enabled',
                    function (item) {
                        return !item.Disabled;
                    },
                    {
                        icon: 'icon-checkbox',
                        text: Translator.trans(/** @Desc("Enabled accounts") */ 'enabled.accounts'),
                    },
                );

                ListActions.setAction(
                    'enable',
                    function (checkedAccounts) {
                        $rootScope.disabled = false;
                        action(checkedAccounts);
                    },
                    testEnable,
                    {
                        icon: 'icon-checkbox',
                        text: Translator.trans(/** @Desc("Enable accounts") */ 'enable.accounts'),
                    },
                );

            },
        ])
        .controller('disableBackgroundUpdatingActionCtrl', [
            '$rootScope',
            'DI',
            'dialogService',
            function ($rootScope, di, dialogService) {
                var ListActions = di.get('actions-manager');
                $rootScope.disableBackgrounUpdating = true;

                function action(checked) {
                    $.post(
                        Routing.generate('aw_account_json_enable_disable_background_updating'),
                        { ids: checked, disabled: $rootScope.disableBackgrounUpdating },
                        function (resp) {
                            di.get('manager').updateAccounts(resp.accounts);
                            $rootScope.$apply();
                        },
                    );
                }

                function testEnableBackgroundUpdating(account) {
                    return account.DisableBackgroundUpdating && account.Access.edit;
                }

                function testDisableBackgroundUpdating(account) {
                    return !account.isCustom && !account.DisableBackgroundUpdating && account.Access.edit;
                }

                ListActions.setAction(
                    'disable_background_updating',
                    function (checkedAccounts) {
                        $rootScope.disableBackgrounUpdating = true;
                        action(checkedAccounts);
                    },
                    testDisableBackgroundUpdating,
                    {
                        icon: 'icon-disable',
                        text: Translator.trans(/** @Desc("Disable background updating") */ 'disable_background_updating_action'),
                    },
                );

                ListActions.setAction(
                    'aw_account_json_enable_disable_background_updating',
                    function (checkedAccounts) {
                        $rootScope.disableBackgrounUpdating = false;
                        action(checkedAccounts);
                    },
                    testEnableBackgroundUpdating,
                    {
                        icon: 'icon-checkbox',
                        text: Translator.trans(/** @Desc("Enable background updating") */ 'enable_background_updating_action'),
                    },
                );

            },
        ])
        .controller('setArchiveActionCtrl', [
            '$rootScope',
            '$timeout',
            'DI',
            'dialogService',
            'ListConfig',
            '$stateParams',
            function ($rootScope, $timeout, di, dialogService, config, $stateParams) {
                if (config.isBusiness) return;

                let ListActions = di.get('actions-manager');
                let setArchiveAction = this;
                setArchiveAction.archived = 0;

                di.get('checker-manager').setCheck(
                    'inactiveAccounts',
                    function (item) {
                        return ['account', 'subaccount', 'coupon'].includes(item.type) && !item.IsActive;
                    },
                    {
                        icon: 'icon-inactive-account',
                        text: Translator.trans(
                            /** @Desc("Inactive Accounts") */ 'award.account.list.menu.select.inactive',
                        ),
                    },
                );

                function action(checked) {
                    let elements = !$stateParams.archive
                        ? {
                              title: Translator.transChoice(
                                  /** @Desc("Confirm Account Archival") */ 'award.account.popup.set-archive.title',
                                  checked.length,
                                  { accounts: checked.length },
                              ),
                              text: Translator.transChoice('award.account.popup.set-archive', checked.length, {
                                  accounts: checked.length,
                              }),
                              buttonCancel: Translator.trans('form.button.cancel'),
                              buttonSave: Translator.trans(
                                  /** @Desc("Archive Accounts") */ 'form.button.confirm-archive',
                              ),
                          }
                        : {
                              title: Translator.transChoice(
                                  /** @Desc("Account Reactivation") */ 'award.account.popup.set-active.title',
                                  checked.length,
                                  { accounts: checked.length },
                              ),
                              text: Translator.transChoice('award.account.popup.set-active', checked.length, {
                                  accounts: checked.length,
                              }),
                              buttonCancel: Translator.trans('form.button.cancel'),
                              buttonSave: Translator.trans(
                                  /** @Desc("Confirm Reactivation") */ 'form.button.confirm-reactivation',
                              ),
                          };
                    setArchiveAction.archived = !$stateParams.archive ? '1' : '0';

                    // Initialization
                    let dialog = dialogService.get('set-archive-action'),
                        dialogText = dialog.element.find('#set-archive-action-text'),
                        dialogForm = dialog.element.find('form').first();

                    /**
                     * Отправляет ajax-запрос на обновление параметра "isArchived".
                     */
                    function setArchive() {
                        $('#set-archive-action-yes').addClass('loader').prop('disabled', true);
                        $.ajax({
                            type: 'POST',
                            url: Routing.generate('aw_acount_json_addarchiveaccount'),
                            data: {
                                form: {
                                    accounts: checked,
                                    isArchived: setArchiveAction.archived,
                                },
                            },
                            success: function (response) {
                                di.get('manager').updateAccounts(response.accounts);
                                $rootScope.$apply();
                                dialog.close();
                            },
                        }).fail(function () {
                            dialog.close();
                        });
                    }

                    dialogForm.submit(function (event) {
                        event.preventDefault();
                        setArchive();
                    });

                    dialog.setOption('title', elements.title);
                    dialogText.html(elements.text);
                    dialog.setOption('buttons', [
                        {
                            text: elements.buttonCancel,
                            click: function () {
                                $(this).dialog('close');
                            },
                            class: 'btn-silver',
                            tabindex: -1,
                        },
                        {
                            text: elements.buttonSave,
                            click: setArchive,
                            class: 'btn-blue',
                            id: 'set-archive-action-yes',
                        },
                    ]);
                    dialog.open();
                }

                /**
                 * @param {AccountData} account
                 * @returns {boolean}
                 */
                function test(account) {
                    return (
                        ['Account', 'Coupon'].includes(account.TableName) &&
                        account.Access.edit &&
                        !$stateParams.archive
                    );
                }

                function testUnarchive(account) {
                    return (
                        ['Account', 'Coupon'].includes(account.TableName) && account.Access.edit && $stateParams.archive
                    );
                }

                ListActions.setAction('setArchived', action, test, {
                    icon: 'icon-move-to-archived-accounts',
                    text: Translator.trans(
                        /** @Desc("Archive Accounts") */ 'award.account.list.menu.actions.archive-accounts',
                    ),
                });
                ListActions.setAction('setUnarchived', action, testUnarchive, {
                    icon: 'icon-move-to-archived-accounts',
                    text: Translator.trans(
                        /** @Desc("Move to Active Accounts") */ 'award.account.list.menu.actions.active-accounts',
                    ),
                });
            },
        ])
        .controller('setOwnerActionCtrl', [
            '$rootScope',
            '$timeout',
            'DI',
            'dialogService',
            '$filter',
            function ($rootScope, $timeout, di, dialogService, $filter) {
                var Accounts = di.get('accounts'),
                    Agents = di.get('agents'),
                    ListActions = di.get('actions-manager');

                var setOwnerAction = this;
                setOwnerAction.possibleOwners = $filter('orderBy')(Agents.getPossibleOwners(), 'order');

                function action(checked) {
                    if (undefined !== setOwnerAction.owner) $('#set-owner-action-owner').val('');
                    $('.js-useragent-autocomplete').val('');

                    var post = [];
                    angular.forEach(checked, function (id) {
                        /** @type {AccountData} */
                        var account = Accounts.getAccount(id);
                        if (account) {
                            post.push([account.ID, account.TableName]);
                        }
                    });
                    setOwnerAction.possibleOwners = $filter('orderBy')(Agents.getPossibleOwners(), 'order');

                    // init
                    var dialog = dialogService.get('set-owner-action'),
                        dialogText = dialog.element.find('#set-owner-action-text'),
                        dialogForm = dialog.element.find('form').first();
                    var row = dialog.element.find('.row'),
                        error = row.find('.error-message-description');
                    row.removeClass('error');
                    error.parent().hide();

                    function setOwner() {
                        if (!setOwnerAction.owner) {
                            dialog.element.parent().effect('shake');
                            return;
                        }
                        $('#set-owner-action-yes').addClass('loader').prop('disabled', true);
                        $.ajax({
                            url: Routing.generate('aw_account_assign_owner'),
                            type: 'POST',
                            data: { accounts: post, newOwner: setOwnerAction.owner },
                            success: function (data) {
                                // fixme
                                var newAccounts = [];
                                var removeIds = [];
                                angular.forEach(data.accounts, function (a) {
                                    var account = Accounts.getAccount(a.FID);
                                    if (account) {
                                        if (parseInt(a.owner) == 0 && a.owner !== 'my') {
                                            removeIds.push(a.FID);
                                        } else {
                                            account.AccountOwner = a.owner === 'my' ? a.owner : parseInt(a.owner);
                                            newAccounts.push(account);
                                        }
                                    }
                                });

                                var counters = di.get('counters').getCounters();

                                di.get('manager').updateAccounts(newAccounts);
                                di.get('manager').removeAccounts(removeIds);
                                $(window).trigger('persons.update', di.get('counters').getOwnerCounters());
                                $(window).trigger('totalAccounts.update', di.get('counters').getTotals().total);
                                $rootScope.$apply();
                                if (typeof data.error != 'undefined') {
                                    row.addClass('error');
                                    error.text(data.error).show().parent().show();
                                    $('#set-owner-action-yes').removeClass('loader').prop('disabled', false);
                                    dialog.element.parent().effect('shake');
                                    return;
                                } else {
                                    row.removeClass('error');
                                    error.parent().hide();
                                }
                                dialog.close();
                            },
                        }).fail(function () {
                            dialog.close();
                        });
                    }

                    dialogForm.submit(function (e) {
                        e.preventDefault();
                        setOwner();
                    });

                    dialog.setOption('open', function () {
                        $timeout(function () {
                            $('#set-owner-action-owner').focus();
                        });
                    });

                    dialogText.html(
                        Translator.transChoice('award.account.popup.set-owner', checked.length, {
                            accounts: checked.length,
                        }),
                    );
                    dialog.setOption('buttons', [
                        {
                            text: Translator.trans('alerts.btn.cancel'),
                            click: function () {
                                $(this).dialog('close');
                            },
                            class: 'btn-silver',
                            tabindex: -1,
                        },
                        {
                            text: Translator.trans('form.button.save'),
                            click: setOwner,
                            class: 'btn-blue',
                            id: 'set-owner-action-yes',
                        },
                    ]);
                    dialog.open();
                }

                /**
                 *
                 * @param {AccountData} account
                 * @returns {boolean}
                 */
                function test(account) {
                    return account.Access.edit;
                }

                ListActions.setAction('setOwner', action, test, {
                    icon: 'icon-user',
                    text: Translator.trans('award.account.list.menu.actions.change-owner'),
                });
            },
        ])
        .controller('shareActionCtrl', [
            '$rootScope',
            '$timeout',
            'DI',
            'dialogService',
            'ListConfig',
            function ($rootScope, $timeout, di, dialogService, config) {
                if (config.isBusiness) return;

                var Accounts = di.get('accounts'),
                    Agents = di.get('agents'),
                    ListActions = di.get('actions-manager');

                var shareAction = this;

                shareAction.data = {};
                shareAction.type = {};
                shareAction.possibleShares = Agents.getPossibleShares();

                angular.forEach(shareAction.possibleShares, function (/** AgentData */ agent) {
                    di.get('checker-manager').setCheck(
                        'sharedTo' + agent.ID,
                        function (item) {
                            return (
                                Object.prototype.hasOwnProperty.call(item, 'Shares') &&
                                angular.isArray(item.Shares) &&
                                item.Shares.indexOf(agent.ID) !== -1
                            );
                        },
                        {
                            icon: 'icon-user',
                            text: Translator.trans(
                                /** @Desc("Shared with %name%")*/ 'award.account.list.menu.select.share-to',
                                { name: agent.name },
                            ),
                        },
                    );
                });

                shareAction.change = function (id, $event) {
                    $event.preventDefault();
                    $event.stopPropagation();
                    shareAction.data[id]++;
                    if (shareAction.type[id] == 1) shareAction.data[id]++;
                    if (shareAction.data[id] > 2) shareAction.data[id] = 0;
                };

                function action(checked) {
                    var post = [];
                    shareAction.data = {};
                    shareAction.count = 0;
                    angular.forEach(shareAction.possibleShares, function (user) {
                        shareAction.data[user.ID] = -1;
                    });
                    angular.forEach(checked, function (id) {
                        /** @type {AccountData} */
                        var account = Accounts.getAccount(id);
                        if (account) {
                            post.push([account.ID, account.TableName]);
                            angular.forEach(shareAction.possibleShares, function (user) {
                                if (angular.isArray(account.Shares) && account.Shares.indexOf(user.ID) !== -1) {
                                    if (shareAction.data[user.ID] == -1) {
                                        shareAction.data[user.ID] = 2;
                                    } else if (shareAction.data[user.ID] != 2) {
                                        shareAction.data[user.ID] = 1;
                                    }
                                } else {
                                    if (shareAction.data[user.ID] == -1) {
                                        shareAction.data[user.ID] = 0;
                                    } else if (shareAction.data[user.ID] != 0) {
                                        shareAction.data[user.ID] = 1;
                                    }
                                }
                            });
                        }
                    });
                    angular.forEach(shareAction.possibleShares, function (user) {
                        shareAction.type[user.ID] = 0;
                        if (shareAction.data[user.ID] != 1) shareAction.type[user.ID] = 1;
                    });
                    // init
                    var dialog = dialogService.get('share-action'),
                        dialogText = dialog.element.find('#share-action-text'),
                        dialogForm = dialog.element.find('form').first();

                    function share() {
                        $('#share-action-yes').addClass('loader').prop('disabled', true);

                        $.ajax({
                            url: Routing.generate('aw_account_share'),
                            type: 'POST',
                            data: { accounts: post, shares: shareAction.data },
                            success: function (data) {
                                if (data.success) {
                                    di.get('manager').updateAccounts(data.accounts);
                                    $rootScope.$apply();
                                }
                                dialog.close();
                            },
                        }).fail(function () {
                            dialog.close();
                        });
                    }

                    dialogForm.submit(function (e) {
                        e.preventDefault();
                        share();
                    });

                    dialogText.html(
                        Translator.transChoice('award.account.popup.share', checked.length, {
                            accounts: checked.length,
                        }),
                    );
                    dialog.setOption('buttons', [
                        {
                            text: Translator.trans('alerts.btn.cancel'),
                            click: function () {
                                $(this).dialog('close');
                            },
                            class: 'btn-silver',
                            tabindex: -1,
                        },
                        {
                            text: Translator.trans('form.button.save'),
                            click: share,
                            class: 'btn-blue',
                            id: 'share-action-yes',
                        },
                    ]);
                    dialog.open();
                }

                /**
                 *
                 * @param {AccountData} account
                 * @returns {boolean}
                 */
                function test(account) {
                    return account.IsShareable;
                }

                ListActions.setAction('share', action, test, {
                    icon: 'icon-user',
                    text: Translator.trans(/** @Desc("Share accounts") */ 'award.account.list.menu.actions.share'),
                });
            },
        ])
        .controller('backupActionCtrl', [
            '$rootScope',
            '$timeout',
            'DI',
            'dialogService',
            'ListConfig',
            function ($rootScope, $timeout, di, dialogService, config) {
                if (config.isBusiness) return;

                var Accounts = di.get('accounts'),
                    ListActions = di.get('actions-manager');

                var backupAction = this;

                backupAction.ids = [];
                backupAction.token = '';
                backupAction.stage = 1;
                backupAction.cannot = [];

                function action(checked) {
                    backupAction.ids = checked;
                    backupAction.token = '';
                    backupAction.stage = 1;
                    backupAction.cannot = [];

                    stage1();
                }

                function stage1() {
                    backupAction.stage = 1;

                    reauth.reauthenticate(reauth.getBackupPasswordsAction(), function () {
                        $rootScope.$apply();
                        $.ajax({
                            url: Routing.generate('aw_account_backup_passwords'),
                            type: 'POST',
                            data: { accounts: backupAction.ids },
                            success: function (data) {
                                if (data.success) {
                                    backupAction.cannot = [];
                                    var popup = dialogService.get('backup-action').element;
                                    var isBackup = false;
                                    angular.forEach(data.accounts, function (hasPassword, FID) {
                                        var account = Accounts.getAccount(FID);
                                        if (hasPassword !== true) {
                                            if (hasPassword !== false) {
                                                backupAction.cannot.push({
                                                    title: account.DisplayName,
                                                    login: account.LoginFieldFirst,
                                                    error: hasPassword,
                                                });
                                            }
                                        } else {
                                            isBackup = true;
                                        }
                                    });
                                    backupAction.token = data.token;
                                    if (backupAction.cannot.length) {
                                        popup
                                            .find('#backup-action-text-cannot')
                                            .html(
                                                Translator.transChoice(
                                                    'award.account.popup.backup.cannot',
                                                    backupAction.cannot.length,
                                                    { accounts: backupAction.cannot.length },
                                                ),
                                            );
                                    }
                                    if (isBackup) {
                                        popup
                                            .find('#backup-action-text')
                                            .html(
                                                Translator.transChoice(
                                                    'award.account.popup.backup',
                                                    backupAction.ids.length,
                                                    { accounts: backupAction.ids.length },
                                                ),
                                            );
                                        stage2();
                                    } else {
                                        stage3();
                                    }
                                } else {
                                    stage1();
                                }
                            },
                        });
                    });
                }

                function stage2() {
                    backupAction.stage = 2;
                    $rootScope.$apply();
                    var dialog = dialogService.get('backup-action');
                    dialog.setOption('buttons', [
                        {
                            text: Translator.trans('alerts.btn.cancel'),
                            click: function () {
                                dialog.close();
                            },
                            class: 'btn-silver',
                            tabindex: -1,
                        },
                        {
                            text: Translator.trans(/** Desc("Download") */ 'form.button.download'),
                            click: function () {
                                var popup = dialog.element;

                                popup.find('#backup-action-download-accounts').val(backupAction.ids.join(','));
                                popup.find('#backup-action-download-csrf').val(backupAction.token);
                                popup.find('#backup-action-download-form').submit();
                                $('#backup-action-yes').addClass('loader').prop('disabled', true);
                                $timeout(function () {
                                    dialog.close();
                                }, 3000);
                            },
                            class: 'btn-blue',
                            id: 'backup-action-yes',
                        },
                    ]);
                    dialog.open();
                }

                function stage3() {
                    backupAction.stage = 3;
                    $rootScope.$apply();
                    var dialog = dialogService.get('backup-action');
                    dialog.setOption('buttons', [
                        {
                            text: Translator.trans(/** Desc("Close") */ 'form.button.close'),
                            click: function () {
                                dialog.close();
                            },
                            class: 'btn-blue',
                            id: 'backup-action-yes',
                        },
                    ]);
                    dialog.open();
                }

                /**
                 *
                 * @param {AccountData} account
                 * @returns {boolean}
                 */
                function test(account) {
                    return account.Access.edit && account.TableName === 'Account' && account.ProviderID;
                }

                ListActions.setAction('backup', action, test, {
                    icon: 'icon-backup-local-password',
                    text: Translator.trans('award.account.list.menu.actions.backup'),
                });
            },
        ])
        .controller('restoreActionCtrl', [
            '$rootScope',
            '$timeout',
            'DI',
            'dialogService',
            'ListConfig',
            function ($rootScope, $timeout, di, dialogService, config) {
                if (config.isBusiness) return;

                var Accounts = di.get('accounts'),
                    Agents = di.get('agents'),
                    ListActions = di.get('actions-manager');

                var restoreAction = this;

                restoreAction.stage = 1;
                restoreAction.file = null;
                restoreAction.restored = [];
                restoreAction.cannot = [];
                restoreAction.error = '';
                restoreAction.ids = [];
                restoreAction.token = '';
                restoreAction.failTimer = null;

                $(document).on('change', '#restore-action-file', function () {
                    restoreAction.file = $('#restore-action-file').val();
                });

                window.restoreActionCallback = stage3;

                function action(checked) {
                    restoreAction.file = null;
                    restoreAction.stage = 1;
                    restoreAction.restored = [];
                    restoreAction.cannot = [];
                    restoreAction.ids = checked;
                    restoreAction.error = '';
                    restoreAction.failTimer = null;

                    var dialog = dialogService.get('restore-action');
                    dialog.element.find('#restore-action-text').html(
                        Translator.transChoice('award.account.popup.restore', checked.length, {
                            accounts: checked.length,
                        }),
                    );
                    dialog.element.find('.file-name').text('');

                    function restore() {
                        if (restoreAction.stage != 1) return;
                        if (!restoreAction.file) {
                            dialog.element.parent().effect('shake');
                            return;
                        }

                        $('#restore-action-yes').addClass('loader').prop('disabled', true);
                        dialog.element.find('#restore-action-download-accounts').val(restoreAction.ids.join(','));
                        dialog.element.find('#restore-action-download-csrf').val(restoreAction.token);
                        dialog.element.find('#restore-action-form').submit();
                        restoreAction.stage = 2;
                        restoreAction.failTimer = $timeout(fail, 10000);
                        $rootScope.$apply();
                    }

                    dialog.setOption('buttons', [
                        {
                            text: Translator.trans('alerts.btn.cancel'),
                            click: function () {
                                $(this).dialog('close');
                            },
                            class: 'btn-silver',
                            tabindex: -1,
                        },
                        {
                            text: Translator.trans(/** Desc("Restore") */ 'form.button.restore'),
                            click: restore,
                            class: 'btn-blue',
                            id: 'restore-action-yes',
                        },
                    ]);
                    dialog.open();
                }

                function stage3(data) {
                    $timeout.cancel(restoreAction.failTimer);
                    var dialog = dialogService.get('restore-action');
                    if (!data.success) {
                        restoreAction.stage = 1;
                        restoreAction.error = data.error;
                        $('#restore-action-yes').removeClass('loader').prop('disabled', false);
                    } else {
                        restoreAction.stage = 3;
                        restoreAction.restored = [];
                        angular.forEach(data.accounts, function (restoredPassword, FID) {
                            var account = Accounts.getAccount(FID);
                            if (restoredPassword === true) {
                                restoreAction.restored.push({
                                    title: account.DisplayName + ' - ' + account.LoginFieldFirst,
                                });
                            } else {
                                restoreAction.cannot.push({
                                    title: account.DisplayName,
                                    login: account.LoginFieldFirst,
                                });
                            }
                        });
                        dialog.close();
                        if (restoreAction.restored.length) {
                            dialog.element
                                .find('#restore-action-text-ok')
                                .html(
                                    Translator.transChoice(
                                        'award.account.popup.restore.ok',
                                        restoreAction.restored.length,
                                        { accounts: restoreAction.restored.length },
                                    ),
                                );
                        }
                        dialog.setOption('buttons', [
                            {
                                text: Translator.trans('button.ok'),
                                click: function () {
                                    $(this).dialog('close');
                                },
                                class: 'btn-blue',
                                id: 'restore-action-yes',
                            },
                        ]);
                        dialog.open();
                    }
                    $rootScope.$apply();
                }

                function fail() {
                    if (restoreAction.stage == 3) return;
                    restoreAction.stage = 1;
                    restoreAction.error = Translator.trans(
                        /** Desc("Restore failure") */ 'award.account.popup.restore.fail',
                    );
                    $('#restore-action-yes').removeClass('loader').prop('disabled', false);
                    $rootScope.$apply();
                }

                /**
                 *
                 * @param {AccountData} account
                 * @returns {boolean}
                 */
                function test(account) {
                    return account.Access.edit && account.TableName == 'Account' && account.ProviderID;
                }

                ListActions.setAction('restore', action, test, {
                    icon: 'icon-restore-local-password',
                    text: Translator.trans('award.account.list.menu.actions.restore'),
                });
            },
        ])
        .controller('updateAllCtrl', [
            'DI',
            '$scope',
            '$stateParams',
            function (di, $scope, $stateParams) {
                const ListManager = di.get('manager');
                const Checker = di.get('checker');

                var filteredAllAccountsID = [];
                var filteredOutdatedAccountsID = [];

                function getAllAccountsID(accounts) {
                    return accounts.reduce((prevValue, currentValue) => {
                        if (
                            currentValue.fields.TableName == 'Account' &&
                            !currentValue.fields.Disabled &&
                            currentValue.fields.Access?.oneUpdate
                        ) {
                            prevValue.push(currentValue.ID);
                        }

                        return prevValue;
                    }, []);
                }

                function getOutdatedAccountsID(accounts) {
                    return accounts.reduce((prevValue, currentValue) => {
                        const lastChangeDateTs = currentValue.fields?.LastUpdatedDateTs || 0;

                        if (
                            currentValue.fields.TableName == 'Account' &&
                            !currentValue.fields.Disabled &&
                            currentValue.fields.Access.oneUpdate &&
                            // One month - 30 * 24 * 60 * 60
                            lastChangeDateTs < new Date().getTime() / 1000 - 30 * 24 * 60 * 60
                        ) {
                            prevValue.push(currentValue.ID);
                        }

                        return prevValue;
                    }, []);
                }

                $scope.isModalOpen = false;

                $scope.onClose = () => {
                    $scope.isModalOpen = false;
                };

                $scope.onUpdateAllAccounts = () => {
                    Checker.check(filteredAllAccountsID);
                    di.get('actions-manager').go('updateAction');
                    $scope.isModalOpen = false;
                };

                $scope.onUpdateOutdatedAccounts = () => {
                    Checker.check(filteredOutdatedAccountsID);
                    di.get('actions-manager').go('updateAction');
                    $scope.isModalOpen = false;
                };

                $scope.allAccountsCount = 0;
                $scope.outdatedAccountsCount = 0;

                this.go = function () {
                    var accounts = ListManager.getElements().data;

                    filteredAllAccountsID = di
                        .get('actions-manager')
                        .getActionItems('updateAction', getAllAccountsID(accounts));
                    
                if (!di.get('updater').isUpdatingState()) {
                        filteredOutdatedAccountsID = di
                            .get('actions-manager')
                            .getActionItems('updateAction', getOutdatedAccountsID(accounts));

                        $scope.allAccountsCount = filteredAllAccountsID.length;
                        $scope.outdatedAccountsCount = filteredOutdatedAccountsID.length;

                        $scope.isModalOpen = true;
                    }
                };
            },
        ])
        .controller('updateActionCtrl', [
            'DI',
            '$injector',
            'ListConfig',
            '$rootScope',
            '$document',
            '$timeout',
            function (di, $injector, ListConfig, $rootScope, $document, $timeout) {
                var updateAction = this;
                var NextUrl;

                if ($injector.has('NextUrl')) {
                    NextUrl = $injector.get('NextUrl');
                } else {
                    NextUrl = Routing.generate('aw_timeline');
                }

                changedAccounts();
                $rootScope.$on('account.changesConfirmed', changedAccounts);

                function changedAccounts() {
                    // changed accounts
                    var accounts = di.get('accounts').getAccounts();
                    updateAction.pendingChanges = {
                        accounts: [],
                        total: 0,
                        increased: { count: 0, amount: 0 },
                        decreased: { count: 0, amount: 0 },
                    };

                    Object.keys(accounts).forEach(function (id) {
                        var account = accounts[id];
                        if (!account.ChangesConfirmed) {
                            updateAction.pendingChanges.accounts.push(account);
                            updateAction.pendingChanges.total += Math.floor(account.LastChangeRaw);

                            if (account.ChangedOverPeriodPositive) {
                                updateAction.pendingChanges.increased.count++;
                                updateAction.pendingChanges.increased.amount += Math.floor(account.LastChangeRaw);
                            } else {
                                updateAction.pendingChanges.decreased.count++;
                                updateAction.pendingChanges.decreased.amount += Math.floor(account.LastChangeRaw);
                            }
                        }
                    });

                    updateAction.pendingChanges.info = Translator.trans(
                        /** @Desc("You have unconfirmed changes on %count% of your accounts.") */ 'award.account.list.changes.info',
                        {
                            count: '<span>' + updateAction.pendingChanges.accounts.length + '</span>',
                        },
                    );
                }

                updateAction.confirmAllChanges = function () {
                    var accounts = di.get('accounts').getAccounts();
                    var ids = updateAction.pendingChanges.accounts.map(function (item) {
                        return item.ID;
                    });

                    updateAction.pendingChanges.accounts.forEach(function (account) {
                        accounts[account.FID].ChangesConfirmed = true;
                    });
                    updateAction.pendingChanges.accounts = [];

                    $.ajax({
                        url: Routing.generate('aw_account_json_confirm_changes'),
                        type: 'POST',
                        data: { ids: ids },
                    })
                        .done(function () {})
                        .fail(function () {
                            // fail
                        });
                };

                updateAction.scrollToUnconfirmed = function () {
                    $rootScope.$broadcast('search.reset');

                    $timeout(function () {
                        //scroll to next unconfirmed account if available
                        var accounts = di.get('accounts').getAccounts();
                        var nextElements = [];
                        Object.keys(accounts).forEach(function (id) {
                            var account = accounts[id];
                            var row = $('#' + id);
                            if (!account.ChangesConfirmed && row.length) {
                                nextElements.push(row);
                            }
                        });

                        if (nextElements.length) {
                            //sort elements by top offset
                            nextElements.sort(function (a, b) {
                                return a.offset().top - b.offset().top;
                            });

                            // scroll to visible unconfirmed account
                            $document.scrollToElement(nextElements[0], 350, 300);
                        }
                    }, 100);
                };

                updateAction.state = di.get('updater-manager').getState();
                updateAction.stop = di.get('updater-manager').stop;
                updateAction.done = di.get('updater-manager').end;
                updateAction.next = function (cnt) {
                    if (cnt) {
                        var trips = di.get('updater').getTrips().join(',');
                        var url = NextUrl.replace(/#.*$/, '');
                        window.location.href = url.replace(/\/$/, '') + '/itineraries/' + trips;
                    } else {
                        window.location.href = NextUrl;
                    }
                };

                function action(checked) {
                    if (di.get('updater').isDone()) {
                        di.get('updater-manager').start(checked);
                    }
                }

                /**
                 *
                 * @param {AccountData} account
                 * @returns {boolean}
                 */
                function test(account) {
                    if (ListConfig.isTrips) return account.Access.tripsUpdate;
                    else return account.Access.groupUpdate;
                }

                di.get('actions-manager').setAction('updateAction', action, test, {
                    icon: 'icon-dark-refresh',
                    text: Translator.trans('award.account.list.update-selected'),
                    group: 'update',
                });
            },
        ])
        .controller('upgradeNotifyPopup', [
            'DI',
            '$scope',
            function (DI, $scope) {
                $scope.upgradeNotifyPopupClose = function () {
                    $.post(Routing.generate('aw_options_user_upgrade_popup_skip'));
                };
            },
        ])
        .directive('confirmation', [
            'reactDirective',
            function (reactDirective) {
                return reactDirective(require('webpack/react-app/Components/AccountsList/UpdateAllPopover/UpdateAllPopover').default);
            },
        ]);
});
