define([
    'angular-boot', 'jquery-boot',
    'lib/customizer',
    'lib/utils',
    'routing',
    'ng-infinite-scroll',
    'directives/extendedDialog',
    'angular-scroll',
    'filters/highlight',
    'filters/unsafe',
    'translator-boot',
    'directives/autoFocus'
], function (angular, $, customizer, utils) {
    angular = angular && angular.__esModule ? angular.default : angular;

    angular
        .module('accountAddPage-ctrl', ['infinite-scroll', 'duScroll', 'highlight-mod', 'unsafe-mod', 'extendedDialog', 'auto-focus-directive'])
        .filter('search', function () {
            return function (data, q) {
                var result = [];
                data.forEach(function (el) {
                    if (el.filter.toLowerCase().indexOf(q.toLowerCase()) != -1)
                        result.push(el);
                    else {
                        if (el.KeyWords) {
                            var parts = el.KeyWords.toLowerCase().split(',');
                            for (var i = 0; i < parts.length; i++)
                                parts[i] = parts[i].trim();

                            if (parts.indexOf(q.toLowerCase()) != -1)
                                result.push(el);
                        }
                    }
                });
                return result;
            }
        })
        .controller('selectProviderCtrl', [
            '$scope',
            '$rootScope',
            '$filter',
            '$window',
            '$q',
            '$http',
            '$timeout',
            function ($scope, $rootScope, $filter, $window, $q, $http, $timeout) {
                var checkStatusProgramCanceller = $q.defer();

                function cancelCheckStatus() {
                    utils.cancelDebounce();
                    checkStatusProgramCanceller.resolve();
                    $scope.checkStatusProgramResult = null;
                }

                $scope.matchClass = 'green';
                $scope.caseSens = false;
                $scope.business = window.business;
                $scope.selectedClient = $window.selectedClient;
                $scope.checkStatusProgramResult = null;
                $scope.defaultUser = window.location.search.split('agentId=')[1] || 'my';
                $scope.isLoading = false;
                $scope.isWait = false;

                var scrollRows = 30,
                    initProviderList = function (n, o) {
                        // if (!$scope.emptyQuery())
                        //     sortProvider($scope.currentTab, true);
                        // else
                        //     sortProvider($scope.currentTab, false);

                        var kind = $scope.providerKindQuery || '';

                        var filtered = $filter('filter')($scope.providers, {Kind: kind}, false);
                        filtered = $filter('search')(filtered, $scope.providerQuery);

                        $scope.filtered = $filter('orderBy')(filtered, $scope.sort);

                        $scope.shown = [];
                        cancelCheckStatus();
                        $scope.loadMore();
                        if ($scope.shown.length === 0) {
                            $scope.isLoading = true;
                            utils.debounce(function () {
                                checkStatusProgramCanceller.resolve();
                                checkStatusProgramCanceller = $q.defer();
                                $scope.checkStatusProgramResult = null;
                                $http.post(Routing.generate('aw_contactus_checkstatus'), {msg: $scope.providerQuery}, { timeout: checkStatusProgramCanceller.promise })
                                    .then(function({data}) {
                                        $timeout(() => {
                                            $scope.$apply(() => $scope.checkStatusProgramResult = typeof(data) === 'string' && data !== '' ? data : false);
                                            $scope.isLoading = $scope.isWait = false;
                                        });
                                    }, function(){});
                            }, 500);
                        } else {
                            $scope.isWait = false;
                        }
                    };
                    // sortProvider = function (tab, orderByCorp) {
                    //     $scope.sort = ["-Popularity", "+Down", "-OldPopularity"];
                    //     if (orderByCorp != null && orderByCorp)
                    //         $scope.sort.unshift(($scope.business) ? "-Corporate" : "+Corporate");
                    // };

                $scope.$watch('providerKindQuery', initProviderList);
                $scope.$watch('providerQuery', initProviderList);
                $scope.providerQuery = '';
                $scope.providerKindQuery = null;
                $scope.providers = [];
                $scope.sort = ["-Popularity", "-OldPopularity"];
                $scope.selected = {
                    showPopup: false
                };

                $scope.setProviderTabs = function () {
                    var providerTabs = window.providersTabsData;
                    if (!providerTabs) {
                        return;
                    }
                    $scope.providerTabs = providerTabs;
                    let defaultTab;

                    if (window.location.hash) {
                        const hash = window.location.hash.substring(1);

                        if (hash.match(/^\d+$/)) {
                            defaultTab = providerTabs.find(tab => tab.kind === parseInt(hash));
                        }
                    }

                    $scope.defaultTab = defaultTab || providerTabs[0]
                    $scope.activateProviderTab($scope.defaultTab);
                };

                $scope.activateProviderTab = function (tab) {
                    $scope.isWait = true;
                    if ($scope.currentTab == tab) return;
                    $scope.currentTab = tab;
                    if (tab != $scope.defaultTab)
                        $scope.providerQuery = '';
                    $scope.providerKindQuery = tab.kind;
                };

                $scope.setProviders = function () {
                    var providers = window.providersData;
                    angular.forEach(providers, function (value, key) {
                        value['Popularity'] = parseInt(value['Popularity'] || 0);
                        value['OldPopularity'] = parseInt(value['OldPopularity'] || 0);
                        if (value['DisplayName'].toUpperCase().indexOf(value['Name'].toUpperCase()) !== -1)
                            value['Name'] = '';
                        value['filter'] = value['DisplayName'] + ':' + value['Name'];
                    });
                    $scope.providers = providers;
                    if ($scope.providerTabs && $scope.providerTabs.length) {
                        var tabs = $scope.providerTabs;
                        $scope.providerTabs = [];
                        angular.forEach(tabs, function (tab) {
                            var kindProviders = $filter('filter')(
                                providers,
                                {Kind: tab.kind},
                                false
                            );
                            if (kindProviders.length) {
                                $scope.providerTabs.push(tab);
                            }
                        });
                    }
                    initProviderList();
                };

                $scope.loadMore = function () {
                    var start = $scope.shown.length,
                        end = $scope.filtered.length;
                    for (var i = start; i < (start + scrollRows) && i < end; i++) {
                        $scope.shown.push($scope.filtered[i]);
                    }
                };
                $scope.emptyQuery = function () {
                    return typeof($scope.providerQuery) == 'undefined' || $scope.providerQuery == '';
                };

                $scope.focus = function () {
                    $('.search input').focus();
                };

                $scope.nAccounts = function (n) {
                    return Translator.transChoice('accounts.n', n, {accounts: n})
                };
                $scope.Routing = Routing;

                $scope.countObjects = function(object) {
                    return Object.keys(object).length;
                };

                $scope.retrieveTrip = function (provider) {
                    // todo fail!
                    $.ajax({
                        url: '/trips/data/retrieve/' + provider.ProviderID + ($scope.selectedClient ? '?agentId=' + $scope.selectedClient : ''),
                        success: function (data) {
                            $scope.selected.data = data;
                            $scope.$apply();
                        },
                        type: 'GET'
                    });

                    provider.DisplayName = $('<div />').html(provider.DisplayName).text();

                    $scope.selected = {
                        provider: provider,
                        showPopup: true,
                        disabled: false,
                        update: function (account) {
                            var id = account.ID,
                                form = $('#retrieve-form');
                            form.find('input.js-account').val(id);
                            form.find('input.js-agentid').val($scope.selectedClient);
                            form.submit();
                            this.disabled = account.loading = true;
                        },
                        updateAll: function () {
                            var ids = [];
                            var form = $('#retrieve-form');
                            var input = form.find('input.js-account').clone();
                            angular.forEach($scope.selected.data.accounts, function (account) {
                                form.find('input.js-account:last').val(account.ID);
                                form.append(input.clone());
                            });
                            form.find('input.js-account:last').remove();
                            form.find('input.js-agentid').val($scope.selectedClient);
                            form.submit();
                            this.disabled = true;
                        }
                    };
                };

                $scope.redirectToReview = function (provider) {
                    return Routing.generate('aw_loyalty_program_rating' , {loyaltyProgramName: provider.href});
                };

                $scope.setProviderTabs();
                $scope.setProviders();
                $scope.focus();
            }])

        .controller('discoveredAccountsCtrl', [
            '$scope', '$http', '$timeout',
            function ($scope, $http, $timeout) {
                $timeout(() => {
                    $('.text-ellipsis').attr('data-role', 'tooltip');
                    customizer.initTooltips(undefined, {
                        my: "center bottom",
                        at: "center top"
                    });
                });

                var discovered = this;
                // window.DiscoveredAccounts = this;

                this.discoveredAcconts = window.discoveredAcconts;
                this.state = {
                    selected: {},
                    isLoading: false,
                    selectAll: false
                }

                this.getSetupLink = (accountId) => {
                    const link = '/account/edit/'+ accountId;
                    if (this.discoveredAcconts.length > 1)
                        return `${link}?backTo=${Routing.generate('aw_select_provider')}`

                    return link;
                }

                this.toggleSelectAll = (e) => {
                    const value = !this.state.selectAll;
                    this.discoveredAcconts.map(({accountId}) => { this.state.selected[accountId] = value; })
                }

                this.removeAccounts = (e) => {
                    const toRemove = this.discoveredAcconts.filter(({accountId}) => (this.state.selected[accountId] && this.state.selected[accountId] === true))
                    const query = toRemove.map(({accountId, isCoupon, useragentid}) => ({
                        id: accountId,
                        isCoupon,
                        useragentid
                    }))

                    this.state.isLoading = true;
                    $http
                        .post(
                            Routing.generate('aw_account_json_remove'), JSON.stringify(query)
                        )
                        .then(() => {
                            this.discoveredAcconts = this.discoveredAcconts.filter(({accountId}) => (!this.state.selected[accountId] || this.state.selected[accountId] === false));
                            this.state.selected = {};
                            if (this.discoveredAcconts.length < 1) window.location.reload();
                        })
                        .catch((e) => {
                            console.error(e);
                        })
                        .finally(() => {
                            this.state.isLoading = false;
                        })

                }

                this.disableRemoveButton = () => {
                    const items = Object.keys(this.state.selected).filter((key) => this.state.selected[key] === true);
                    return items.length === 0;
                }
            }]);
});
