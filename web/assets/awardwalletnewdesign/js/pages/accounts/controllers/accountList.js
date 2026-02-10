define([
    'angular-boot',
    'jquery-boot',
    'lib/customizer',
    'angular-ui-router',
    'lib/design',
    'cookie',
    'lib/accountAutocomplete',
    'angular-scroll',
    /*'angular-hotkeys',*/ 'translator-boot',
    'ng-infinite-scroll',
    'filters/highlight',
    'filters/unsafe',
    'filters/htmlencode',
    'directives/customizer',
    'directives/dialog',
    'directives/retooltipAfter',
    'directives/ellipsis',
    'directives/timeRemaining',

    'pages/accounts/controllers/accountListUpdater',
    'pages/accounts/controllers/accountListElements',
    'pages/accounts/controllers/accountListActions',
    'pages/accounts/directives/pendingDialog',
    'pages/accounts/directives/reactComponents',
    'pages/accounts/services/details',
    'pages/accounts/services/di',
    'pages/accounts/services/loader',
    'pages/accounts/services/accounts',
    'pages/accounts/services/user',
    'pages/accounts/services/promotion',
    'pages/accounts/services/ficoService',
    'pages/accounts/services/kinds',
    'pages/accounts/services/agents',
    'pages/accounts/services/updater',
    'pages/spentAnalysis/ServiceSpentAnalysis',
    'pages/accounts/services/updaterElements',
    'pages/accounts/services/updaterDecorator',
    'pages/accounts/services/updaterAdvertise',
    'pages/accounts/services/listUpdater',
    'pages/accounts/services/checker',
    'pages/accounts/services/listChecker',
    'pages/accounts/services/listActions',
    'pages/accounts/services/listCompiler',
    'pages/accounts/services/listCounters',
    'pages/accounts/services/listPager',
    'pages/accounts/services/listManager',
    'pages/accounts/services/listExtender',
    'pages/accounts/services/listFiltrator',
    'pages/accounts/services/listFilters',
    'pages/accounts/services/listSorter',
], function (angular, $, customizer) {
    angular = angular && angular.__esModule ? angular.default : angular;

    // Конфигурация списка
    // Устанавливается путём вызова angular.module('accountListApp').constant('ListConfig', {...}) до angular.bootstrap(...)
    var ListConfig = {
        // Флаг, включающий режим бизнес-списка (с пагинацией вместо infinite scroll).
        isBusiness: false,
        // Флаг, включающий режим списка с постоянной загрузкой с сервера.
        // Действует только на бизнес.
        isPartial: null,
        // Обновление аккаунтов с trips
        isTrips: false,
        // Лимит количества аккаунтов для обычного пользователя.
        // Действует только на пользователя.
        userAccountsLimit: null,
    };

    // Данные для предзагрузки списка аккаунтов
    // Устанавливается путём вызова angular.module('accountListApp').constant('ListData', {...}) до angular.bootstrap(...)
    var ListData = {
        accounts: null,
        kinds: null,
        agents: null,
        user: null,
    };

    // Список сервисов
    // инициализируются в обратном порядке
    var ListServices = {
        compiler: 'ListCompiler',
        extender: 'ListExtender',
        sorter: 'ListSorter',
        filtrator: 'ListFiltrator',
        checker: 'Checker',
        pager: {},
        counters: {},
        updater: 'Updater',
        'updater-elements': 'UpdaterElements',
        'updater-element-decorator': 'UpdaterElementDecorator',
        'updater-results': 'UpdaterResults',
        'updater-advertise': 'UpdaterAdvertise',
        loader: 'Loader',
        promotion: 'Promotion',
        kinds: 'Kinds',
        agents: 'Agents',
        user: 'User',
        accounts: 'Accounts',
    };

    var hasStorage = (function () {
        try {
            localStorage.setItem('testLocalStorage', 'testLocalStorage');
            localStorage.removeItem('testLocalStorage');
            return true;
        } catch (exception) {
            return false;
        }
    })();

    if (!hasStorage) {
        var localStorage = {
            setItem: function (key, value) {
                $.cookie(key, value, { expires: 365 * 30 });
            },
            removeItem: function (key) {
                $.removeCookie(key);
            },
            getItem: function (key) {
                return $.cookie(key);
            },
        };
    }

    var app = angular.module('accountListApp', [
        'appConfig',
        'ui.router',
        'duScroll' /*'cfp.hotkeys',*/,
        'customizer-directive',
        'dialog-directive',
        'highlight-mod',
        'unsafe-mod',
        'htmlencode-mod',
        'retooltip-after-directive',
        'time-remaining',
        'infinite-scroll',
        'ellipsis',

        'diService',
        'accounts',
        'pending-directive',
        'react-components',
        'loaderService',
        'accountsService',
        'agentsService',
        'SpentAnalysisService',
        'kindsService',
        'userService',
        'ficoServiceModule',
        'PromotionService',
        'updaterService',
        'updaterElementsService',
        'updaterDecoratorService',
        'updaterAdvertiseService',
        'listUpdaterService',
        'checkerService',
        'listCheckerService',
        'listActionsService',
        'listCountersService',
        'listFiltersService',
        'listPagerService',
        'listCompilerService',
        'listManagerService',
        'listExtenderService',
        'listSorterService',
        'listFiltratorService',

        'accountListActionsModule',
        'accountListElementsModule',
        'accountListUpdaterModule',
    ]);
    // Лимит аккаунтов в списке пользователя, после которого показывается предупреждение
    // Используется, если не задано userAccountsLimit
    app.constant('UserListLimit', 200);

    // Инициализация параметров
    app.config([
        '$injector',
        'UserListLimit',
        function ($injector, userLimit) {
            if ($injector.has('ListConfig')) {
                ListConfig = $.extend(ListConfig, $injector.get('ListConfig'));
                if (ListConfig.isBusiness) {
                    // userAccountsLimit не работает в бизнесе
                    if (ListConfig.userAccountsLimit !== null) {
                        ListConfig.userAccountsLimit = null;
                    }
                } else {
                    // isPartial не работает в пользователе
                    if (ListConfig.isPartial !== null) {
                        ListConfig.isPartial = null;
                    }
                    if (ListConfig.userAccountsLimit === null) {
                        ListConfig.userAccountsLimit = userLimit;
                    }
                }
            }
        },
    ]);

    // Конфигурация роутов
    app.config([
        '$stateProvider',
        '$urlRouterProvider',
        '$locationProvider',
        function ($stateProvider, $urlRouterProvider, $locationProvider) {
            $locationProvider.html5Mode({
                enabled: true,
                rewriteLinks: true,
            });

            if (ListConfig.isBusiness) {
                $stateProvider.state({
                    name: 'list',
                    url: '/?recent&agentId&page&errors&account&subaccount&coupon&filterProgram&filterOwner&filterAccount&filterStatus&filterBalance&filterExpire&filterLastUpdate',
                });
                $urlRouterProvider.otherwise('/');
            } else {
                $stateProvider.state({
                    name: 'list',
                    url: '/?recent&agentId&ungroup&account&subaccount&coupon&errors&sharedWith&update&archive',
                });
                if (localStorage.getItem('list.ungroup') === 'on') {
                    $urlRouterProvider.otherwise('/?ungroup=on');
                } else {
                    $urlRouterProvider.otherwise('/');
                }
            }
        },
    ]);

    // Загрузка данных сервисов
    app.config([
        '$injector',
        'AccountsProvider',
        'KindsProvider',
        'AgentsProvider',
        'UserProvider',
        'SpentAnalysisProvider',
        'LoaderProvider',
        'PromotionProvider',
        function (
            $injector,
            AccountsProvider,
            KindsProvider,
            AgentsProvider,
            UserProvider,
            SpentAnalysisProvider,
            LoaderProvider,
            PromotionProvider,
        ) {
            if ($injector.has('ListData')) {
                var data = $injector.get('ListData');
                if (typeof data == 'object' && data != null) {
                    ListData = $.extend(ListData, $injector.get('ListData'));
                    LoaderProvider.setData(ListData);
                }
                LoaderProvider.setBusinessMode(ListConfig.isBusiness);
                LoaderProvider.setResolvers(['accounts', 'kinds', 'agents', 'user']);
            }

            if ($injector.has('AdsData')) {
                var ads = $injector.get('AdsData');
                if (typeof ads == 'object' && ads != null) {
                    PromotionProvider.setData(ads);
                }
            }
        },
    ]);

    // Конфигурация сервисов
    app.config([
        '$injector',
        'ListSorterProvider',
        'ListFiltratorProvider',
        'ListManagerProvider',
        'ListCompilerProvider',
        'UpdaterProvider',
        'UpdaterElementDecoratorProvider',
        'ListExtenderProvider',
        function (
            $injector,
            ListSorterProvider,
            ListFiltratorProvider,
            ListManagerProvider,
            ListCompilerProvider,
            UpdaterProvider,
            UpdaterElementDecoratorProvider,
            ListExtenderProvider,
        ) {
            var filters;
            if (ListConfig.isBusiness) {
                ListManagerProvider.setBusinessMode(true);
                if (ListConfig.isPartial) {
                    //ListServices['sorter'] = 'DummyListSorter';
                    //ListServices['filtrator'] = 'DummyListFiltrator';
                    ListManagerProvider.setPartialMode(true);
                } else {
                    ListSorterProvider.setGroupMode(false);
                }
                ListServices['counters'] = 'ListCounters';
                ListServices['pager'] = 'ListPager';
                ListServices['filters'] = 'ListFilters';

                filters = [
                    'filtrator.connectedCoupons',
                    'filtrator.updater',
                    'filtrator.agent',
                    'filtrator.recent',
                    'filtrator.search',
                    'filtrator.error',
                    'filtrator.program',
                    'filtrator.owner',
                    'filtrator.account',
                    'filtrator.status',
                    'filtrator.balance',
                    // 'filtrator.cashequivalent',
                    'filtrator.expire',
                    'filtrator.lastupdate',
                    'filtrator.totals', //
                    'filtrator.pager',
                    'filtrator.checker',
                ];
                ListServices['filtrator.connectedCoupons'] = 'ListFiltratorConnectedCoupon';
                ListServices['filtrator.updater'] = 'ListFiltratorByUpdater';
                ListServices['filtrator.agent'] = 'ListFiltratorByAgent';
                ListServices['filtrator.recent'] = 'ListFiltratorByRecent';
                ListServices['filtrator.search'] = 'ListFiltratorBySearch';
                ListServices['filtrator.error'] = 'ListFiltratorByError';
                ListServices['filtrator.program'] = 'ListFiltratorByProgram';
                ListServices['filtrator.owner'] = 'ListFiltratorByOwner';
                ListServices['filtrator.account'] = 'ListFiltratorByAccount';
                ListServices['filtrator.status'] = 'ListFiltratorByStatus';
                ListServices['filtrator.balance'] = 'ListFiltratorByBalance';
                // ListServices['filtrator.cashequivalent'] = 'ListFiltratorByCashequivalent';
                ListServices['filtrator.expire'] = 'ListFiltratorByExpire';
                ListServices['filtrator.lastupdate'] = 'ListFiltratorByLastUpdate';
                ListServices['filtrator.checker'] = 'ListFiltratorChecker';
                ListServices['filtrator.totals'] = 'ListFiltratorTotals';
                ListServices['filtrator.pager'] = 'ListFiltratorPager';
                ListFiltratorProvider.setFilters(filters);
                ListFiltratorProvider.setPersistent(false);
            } else {
                ListSorterProvider.setGroupMode(true);
                ListServices['counters'] = 'ListCounters';

                filters = [
                    'filtrator.connectedCoupons',
                    'filtrator.reset',
                    'filtrator.updater',
                    'filtrator.hideArchive',
                    'filtrator.archive',
                    'filtrator.agent',
                    'filtrator.recent',
                    'filtrator.search',
                    'filtrator.sharedWith',
                ];
                ListServices['filtrator.connectedCoupons'] = 'ListFiltratorConnectedCoupon';
                ListServices['filtrator.reset'] = 'ListFiltratorReset';
                ListServices['filtrator.updater'] = 'ListFiltratorByUpdater';
                ListServices['filtrator.hideArchive'] = 'ListFiltratorByHideArchive';
                ListServices['filtrator.archive'] = 'ListFiltratorByArchive';
                ListServices['filtrator.agent'] = 'ListFiltratorByAgent';
                ListServices['filtrator.recent'] = 'ListFiltratorByRecent';
                ListServices['filtrator.search'] = 'ListFiltratorBySearch';
                ListServices['filtrator.sharedWith'] = 'ListFiltratorBySharedWith';
                if (ListConfig.userAccountsLimit) {
                    ListServices['filtrator.limit'] = 'ListFiltratorLimit';
                    filters.push('filtrator.limit');
                }
                ListServices['filtrator.checker'] = 'ListFiltratorChecker';
                filters.push('filtrator.checker');
                ListServices['filtrator.totals'] = 'ListFiltratorTotals';
                filters.push('filtrator.totals');
                ListServices['filtrator.more'] = 'ListFiltratorMore';
                filters.push('filtrator.more');
                ListFiltratorProvider.setFilters(filters);
            }
            if (ListConfig.isTrips) {
                UpdaterProvider.setTrips(true);
                UpdaterElementDecoratorProvider.setTrips(true);
            }
            ListServices['extender.decorate'] = 'ListExtenderDecorator';
            ListExtenderProvider.setExtenders(['extender.decorate']);
        },
    ]);

    app.value('ListConfig', ListConfig);

    // Запуск сервисов
    app.run([
        '$injector',
        'DI',
        function ($injector, di) {
            var keys = Object.keys(ListServices);
            keys.reverse(); // Обратная сортировка, для инициации service.sub ранее service
            angular.forEach(keys, function (serviceId) {
                var service = ListServices[serviceId];
                if ($injector.has(service)) {
                    di.set(serviceId, $injector.get(service));
                } else if (service !== undefined) {
                    di.set(serviceId, service);
                }
            });
            // Менеджеры всегда инициализируется последним
            di.set('element-updater-manager', $injector.get('ElementUpdater'));
            di.set('updater-manager', $injector.get('ListUpdater'));
            di.set('actions-manager', $injector.get('ListActions'));
            di.set('checker-manager', $injector.get('ListChecker'));
            di.set('manager', $injector.get('ListManager'));

            di.get('checker-manager').setCheck(
                'errors',
                function (item) {
                    return item.ProgramMessage.Type == 5 && item.ErrorCode > 1 && !item.isCustom;
                },
                {
                    icon: 'icon-silver-error-d',
                    text: Translator.trans('award.account.list.menu.select.error'),
                },
            );
            di.get('checker-manager').setCheck(
                'weekAgo',
                function (item) {
                    let lastChangeDateTs = ~~item.LastUpdatedDateTs;

                    return lastChangeDateTs < new Date().getTime() / 1000 - 604800;
                },
                {
                    icon: 'icon-not-updated',
                    text: Translator.trans('award.account.list.menu.select.old'),
                },
            );
            di.get('checker-manager').setCheck(
                'monthAgo',
                function (item) {
                    let lastChangeDateTs = ~~item.LastUpdatedDateTs;

                    return lastChangeDateTs < new Date().getTime() / 1000 - 2592000;
                },
                {
                    icon: 'icon-not-updated',
                    text: Translator.trans(
                        /** @Desc("Updated more than 1 month ago") */ 'award.account.list.menu.select.old.month',
                    ),
                },
            );
            di.get('checker-manager').setCheck(
                'expired',
                function (item) {
                    return (
                        item.ExpirationDateTs < new Date().getTime() / 1000 + 7776000 &&
                        item.ExpirationDateTs > new Date().getTime() / 1000
                    );
                },
                {
                    icon: 'icon-silver-warning',
                    text: Translator.trans('award.account.list.menu.select.expiring'),
                },
            );
            di.get('checker-manager').setCheck(
                'hasTrips',
                function (item) {
                    return item.HasCurrentTrips;
                },
                {
                    icon: 'icon-travel-plans',
                    text: Translator.trans('award.account.list.menu.select.trips'),
                },
            );

            di.get('checker-manager').setCheck(
                'backgroundCheck',
                function (item) {
                    return item.BackgroundCheck == '0';
                },
                {
                    icon: 'icon-updating-turned-off',
                    text: Translator.trans(
                        /** @Desc("Accounts with background updating turned off") */ 'award.account.list.menu.select.background-check',
                    ),
                },
            );

            // Частные случаи, перенести в конфиг
            if (ListConfig.userAccountsLimit) {
                di.get('filtrator.limit').setValue(ListConfig.userAccountsLimit);
            }
            if (ListConfig.isBusiness) {
                di.get('filtrator.pager').setPerPage(100);
                $(document).on('focus', '#filterOwner:not(.ui-autocomplete-input)', function (e) {
                    var url = $(this).data('url') || Routing.generate('aw_account_get_owners');
                    $(this).autocomplete({
                        minLength: 2,
                        source: function (request, response) {
                            var element = $(this.element).addClass('loading-input');
                            $.ajax({
                                url: url,
                                data: { q: request.term, full: true },
                                method: 'POST',
                                success: function (data) {
                                    element.removeClass('loading-input');
                                    if (data && angular.isArray(data) && data.length) {
                                        response(
                                            data.map(function (a) {
                                                return {
                                                    label: a.label + ' (' + a.count + ')',
                                                    value: a.name,
                                                };
                                            }),
                                        );
                                    } else {
                                        response([
                                            {
                                                label: Translator.trans(
                                                    /** @Desc("No results found") */ 'award.account.list.empty-autocomplete',
                                                ),
                                                value: '',
                                            },
                                        ]);
                                    }
                                },
                                error: function (data) {
                                    element.removeClass('loading-input');
                                },
                            });
                        },
                        select: function (event, ui) {
                            if (ui.item.value) $(event.target).val(ui.item.value);
                            $(event.target).trigger('input');
                            return false;
                        },
                    });
                });
                $(document).on('focus', '#filterProgram:not(.ui-autocomplete-input)', function (e) {
                    var url = $(this).data('url') || Routing.generate('aw_account_get_programs');
                    $(this).autocomplete({
                        minLength: 2,
                        source: function (request, response) {
                            var element = $(this.element).addClass('loading-input');
                            $.ajax({
                                url: url,
                                data: { q: request.term },
                                method: 'POST',
                                success: function (data) {
                                    element.removeClass('loading-input');
                                    if (data && angular.isArray(data) && data.length) {
                                        response(
                                            data.map(function (a) {
                                                return { label: a.label, value: a.label };
                                            }),
                                        );
                                    } else {
                                        response([
                                            {
                                                label: Translator.trans('award.account.list.empty-autocomplete'),
                                                value: '',
                                            },
                                        ]);
                                    }
                                },
                                error: function (data) {
                                    element.removeClass('loading-input');
                                },
                            });
                        },
                        select: function (event, ui) {
                            if (ui.item.value) $(event.target).val(ui.item.label);
                            $(event.target).trigger('input');
                            return false;
                        },
                    });
                });
                $.ui.autocomplete.prototype._renderItem = function (ul, item) {
                    var regex = new RegExp('(' + this.element.val().replace(/[^A-Za-z0-9А-Яа-я]+/g, '') + ')', 'gi'),
                        html = $('<div/>').text(item.label).html().replace(regex, '<b>$1</b>');
                    return $('<li></li>').data('item.autocomplete', item).append($('<a></a>').html(html)).appendTo(ul);
                };
            }
        },
    ]);

    app.controller('mainCtrl', [
        '$scope',
        '$state',
        '$stateParams',
        '$document',
        '$timeout',
        '$window',
        'DI',
        'detailsService',
        'dialogService',
        '$transitions',
        /**
         *
         * @param $scope
         * @param $state
         * @param {*} $stateParams
         * @param $document
         * @param $timeout
         * @param $window
         * @param di
         * @param detailsService
         * @param dialogService
         */
        function (
            $scope,
            $state,
            $stateParams,
            $document,
            $timeout,
            $window,
            di,
            detailsService,
            dialogService,
            $transitions,
        ) {
            var ListManager = di.get('manager');

            var main = this;

            this.view = {
                lastUpdated: {},
                grouped: true,
                showErrors: true,
                agentTitle: '',
                showInactive: false,
                showSubaccounts: true,
                showLastUpdateAgo: false,
                toggleInactive: function () {
                    this.showInactive = !this.showInactive;
                    localStorage.setItem('list.showInactive', this.showInactive ? 'on' : 'off');
                    //ListManager.setFilter({inactiveCoupon: this.showInactive });
                },
                toggleSubaccounts: function () {
                    this.showSubaccounts = !this.showSubaccounts;
                    localStorage.setItem('list.showSubaccounts', this.showSubaccounts ? 'on' : 'off');
                },
                toggleLastUpdate: function () {
                    this.showLastUpdateAgo = !this.showLastUpdateAgo;
                    localStorage.setItem('list.showLastUpdateAgo', this.showLastUpdateAgo ? 'on' : 'off');
                },
                scrollToAccount: function (element, duration, offset) {
                    duration = duration || 1000;
                    offset = offset || 400;
                    return $document.scrollToElement(element, offset, duration);
                },
                toggleErrors: function () {
                    this.showErrors = !this.showErrors;
                    localStorage.setItem('list.showErrors', this.showErrors ? 'on' : 'off');
                },
                showDetails: function (id) {
                    showDetails(id);
                },
                removeItem: function (item) {
                    di.get('actions-manager').go('deleteAction', [item.FID], true);
                },
                alliancePopup: function (alias, $event) {
                    var alliances = {
                        skyteam: 'SkyTeam',
                        staralliance: 'Star Alliance',
                        oneworld: 'Oneworld',
                    };

                    $event.preventDefault();
                    $event.stopPropagation();
                    if (!Object.prototype.hasOwnProperty.call(alliances, alias)) return false;

                    var allianceName = alliances[alias];
                    var allianceIconSrc =
                        '/assets/awardwalletnewdesign/img/alliances/old/' +
                        alias +
                        (main.devicePixelRatio && main.devicePixelRatio == 2 ? '@2x.png' : '.png');

                    var dialog = dialogService.fastCreate(
                        allianceName,
                        '<div class="ajax-loader"><div class="loading"></div></div>',
                        true,
                        false,
                        [
                            {
                                text: Translator.trans('button.ok'),
                                click: function () {
                                    $(this).dialog('close');
                                },
                                class: 'btn-blue',
                            },
                        ],
                        700,
                        'auto',
                        $('<img src="' + allianceIconSrc + '" height="30" width="25" alt="" />'),
                    );
                    dialog.element.load(
                        Routing.generate('aw_account_list_alliance_info', {
                            alias: alias,
                            scale: main.devicePixelRatio,
                        }),
                        function () {
                            customizer.initTooltips(dialog.element);
                        },
                    );
                },
            };
            this.view.showErrors =
                localStorage.getItem('list.showErrors') === 'on' || localStorage.getItem('list.showErrors') == null;
            this.view.showInactive =
                localStorage.getItem('list.showInactive') === 'on' || localStorage.getItem('list.showInactive') == null;
            this.view.showSubaccounts =
                localStorage.getItem('list.showSubaccounts') === 'on' ||
                localStorage.getItem('list.showSubaccounts') == null;
            this.view.showLastUpdateAgo = localStorage.getItem('list.showLastUpdateAgo') === 'on';

            main.stateParams = $stateParams;
            main.routeState = $state;
            main.state = ListManager.getState();
            main.counters = di.get('counters').getCounters();
            main.totals = di.get('counters').getTotals();
            main.accountsLimit = ListConfig.userAccountsLimit;
            main.user = di.get('user');
            main.devicePixelRatio = $window.devicePixelRatio;

            const formatterCurrency = new Intl.NumberFormat(customizer.locale, {
                style: 'currency',
                currency: 'USD',
            });
            main.formatCurrency = function (value, decimal) {
                if (undefined === value) {
                    return '';
                }
                value = Math.floor(value);
                if (undefined !== decimal && 0 == decimal) {
                    return new Intl.NumberFormat(customizer.locale, {
                        style: 'currency',
                        currency: 'USD',
                        maximumFractionDigits: 0,
                    }).format(value);
                }
                return formatterCurrency.format(value);
            };

            main.view.lastUpdated = di.get('accounts').getLastUpdated();

            var first = true;

            $scope.$on('accountList.loadDetails', function (event, id) {
                showDetails(id);
            });

            $transitions.onSuccess({}, function (transition) {
                if (first) {
                    $('[data-ng-cloak2]').removeAttr('data-ng-cloak2');
                    $(document).trigger('person.activate', main.stateParams.agentId || 'all');
                    showDetails();

                    if (main.stateParams.update) {
                        var startUpdate = function () {
                            var accountId = main.stateParams.update;
                            main.stateParams.update = undefined;
                            var element = $('#a' + accountId);
                            if (element.length) {
                                $timeout(function () {
                                    main.view.scrollToAccount(element).then(function () {
                                        $timeout(function () {
                                            di.get('element-updater-manager').start('a' + accountId);
                                        });
                                    });
                                }, 1000);
                            }
                        };

                        if (!main.state.loaded) {
                            $timeout(startUpdate, 300);
                        } else {
                            startUpdate();
                        }
                    }
                    first = false;
                }

                if (main.stateParams.sharedWith) {
                    const agents = di.get('agents').getPossibleShares();
                    main.sharedAgent = agents.filter(function (agent) {
                        return agent.ID === +main.stateParams.sharedWith;
                    })[0];
                }

                main.view.grouped =
                    main.stateParams.ungroup === undefined
                        ? localStorage.getItem('list.ungroup') !== 'on'
                        : main.stateParams.ungroup != 'on';
                if (ListConfig.isTrips) {
                    ListManager.setFilters({
                        active: false,
                        agentId: false,
                        recent: false,
                        errors: false,
                    });
                    ListManager.setGrouped(false);
                } else {
                    ListManager.setFilters({
                        hideArchive: !main.stateParams.archive,
                        archive: main.stateParams.archive === 'on',
                        agentId: main.stateParams.agentId,
                        recent: main.stateParams.recent || false,
                        errors: main.stateParams.errors || false,
                        filterProgram: main.stateParams.filterProgram,
                        filterOwner: main.stateParams.filterOwner,
                        filterAccount: main.stateParams.filterAccount,
                        filterStatus: main.stateParams.filterStatus,
                        filterBalance: main.stateParams.filterBalance,
                        // filterCashequivalent: main.stateParams.filterCashequivalent,
                        filterExpire: main.stateParams.filterExpire,
                        filterLastUpdate: main.stateParams.filterLastUpdate,
                        sharedWith: main.sharedAgent && main.stateParams.sharedWith,
                    });
                    if (di.has('filters')) {
                        var filters = di.get('filters').getFilters();
                        filters.program = main.stateParams.filterProgram;
                        filters.owner = main.stateParams.filterOwner;
                        filters.account = main.stateParams.filterAccount;
                        filters.status = main.stateParams.filterStatus;
                        filters.balance = main.stateParams.filterBalance;
                        // filters.cashequivalent = main.stateParams.filterCashequivalent;
                        filters.sharedWith = main.stateParams.sharedWith;

                        let d;
                        if (main.stateParams.filterExpire) {
                            d = new Date(main.stateParams.filterExpire * 1000);
                            filters.expire = $.datepicker.formatDate(
                                $('#filterExpire_datepicker').datepicker('option', 'dateFormat'),
                                d,
                            );
                        } else {
                            filters.expire = null;
                        }
                        if (main.stateParams.filterLastUpdate) {
                            d = new Date(main.stateParams.filterLastUpdate * 1000);
                            filters.lastupdate = $.datepicker.formatDate(
                                $('#filterLastUpdate_datepicker').datepicker('option', 'dateFormat'),
                                d,
                            );
                        } else {
                            filters.lastupdate = null;
                        }
                    }
                    if (typeof main.stateParams.page !== 'undefined' && di.has('pager')) {
                        ListManager.setPage(main.stateParams.page);
                    }

                    var counters = di.get('counters').getCounters();

                    ListManager.setGrouped(main.view.grouped);
                    $(window).trigger('person.activate', main.stateParams.agentId || 'all');
                    $(window).trigger('persons.update', di.get('counters').getOwnerCounters());
                }
                ListManager.build();
                if (!ListConfig.isTrips) {
                    $(window).trigger('totalAccounts.update', di.get('counters').getTotals().total);
                }

                $('.last-update', '#ng-app').css('visibility', 'visible');
            });
            ListManager.setOrder(
                $.cookie('account_sort_order') || 'DisplayName',
                $.cookie('account_sort_reverse') === 'true',
            );
            ListManager.init().then(function () {
                if (ListConfig.isTrips) {
                    $timeout(function () {
                        if (!di.get('updater').isUpdating()) {
                            di.get('checker-manager').checkAll();
                            di.get('actions-manager').go('updateAction');
                        }
                    }, 100);
                } else {
                    var counters = di.get('counters').getCounters();

                    $(window).trigger('person.activate', main.stateParams.agentId || 'all');
                    $(window).trigger('persons.update', di.get('counters').getOwnerCounters());
                    $(window).trigger('totalAccounts.update', counters.actives + counters.archives);

                    var e = $("[data-ng-model='search.search']");
                    $timeout(function () {
                        e.blur().focus();
                    });
                }
            });

            this.setOptions = function (options) {
                angular.forEach(options, function (value, key) {
                    if (value === null || value === undefined || value === false) {
                        value = 'off';
                    }
                    localStorage.setItem('list.' + key, value);
                });
                angular.merge(options, { page: 1 });
                $state.go('list', options);
            };

            function showDetails(id) {
                if (!main.state.loaded) {
                    $timeout(function () {
                        showDetails(id);
                    }, 300);
                } else {
                    let subaccountId = null;

                    if (!id && (main.stateParams.account || main.stateParams.coupon)) {
                        const sanitizeId = function (id) {
                            id = id.replace(/[^0-9]/g, '');

                            return parseInt(id);
                        };

                        if (main.stateParams.subaccount) {
                            subaccountId = sanitizeId(main.stateParams.subaccount);
                        }

                        if (main.stateParams.account) {
                            id = 'a' + sanitizeId(main.stateParams.account);
                        } else {
                            id = 'c' + sanitizeId(main.stateParams.coupon);
                        }
                    }

                    if (id) {
                        /**
                         * @type {?AccountData}
                         */
                        var account = di.get('accounts').getAccount(id);

                        if (!account && id.substr(0, 1) === 'c') {
                            var accounts = di.get('accounts').getAccounts();
                            Object.keys(accounts)
                                .map(function (key) {
                                    return accounts[key];
                                })
                                .filter(function (item) {
                                    return item.ConnectedCoupons.length;
                                })
                                .forEach(function (item) {
                                    item.ConnectedCoupons.forEach(function (coupon) {
                                        if (coupon.FID === id) account = coupon;
                                    });
                                });
                        }

                        if (account) {
                            $timeout(function () {
                                var isCoupon = account.TableName === 'Coupon';
                                var element;

                                if (subaccountId) {
                                    let subaccount = account.SubAccountsArray
                                        ? account.SubAccountsArray.find(function (sub) {
                                              return sub.SubAccountID == subaccountId;
                                          })
                                        : null;

                                    if (subaccount) {
                                        element = $('#s' + subaccountId);
                                    }
                                }

                                if (isCoupon) {
                                    element = $('#emb-' + account.FID);
                                }

                                if (!element || !element.length) {
                                    element = $('#' + account.FID);
                                }

                                if (element.length) {
                                    main.view.scrollToAccount(element).then(function () {
                                        var details = element.find('.show-details');
                                        if (details.length && details.is(':visible')) {
                                            $timeout(function () {
                                                details.first().trigger('click');
                                            });
                                        } else {
                                            detailsService.open(null, account.FID, true);
                                        }
                                    });
                                } else {
                                    detailsService.open(null, account.FID, true);
                                }
                            });
                        }
                    }
                }
            }

            window.reloadPageContent = function () {
                // fixme
                //accountProviderFactory.reload().then(function () {
                //	accountListProviderFactory.init();
                //});
            };
        },
    ]);

    /*
     * Стек вызова:
     * Загрузка данных
     *   Прогресс загрузки
     *   Падение при загрузке
     * Конвертация данных в строки, сохранение строк
     *   Связывание данных
     *   Расширение строк стеком экстеншенов
     *     Чекбокс
     *     Апдейтер
     *     АвардПлюс
     *     Хайдер
     * Копирование строк в массив для вывода
     *   Сортировка данных
     *     Стек сортировок
     *   Фильтрация данных
     *     Снятие хайда
     *     Стек фильтров
     *     Пагинация
     *     Ограничения
     *     Лоад море
     * Вывод строк
     */

    $('.js-toggle-active').click(function (event) {
        event.preventDefault();
        if ($(this).parents('.credit-card.active').length) {
            $('.js-toggle-active').first().show();
            $('.js-toggle-active').last().hide();
        } else {
            $('.js-toggle-active').first().hide();
            $('.js-toggle-active').last().show();
        }
        $(this).parents('.credit-card').toggleClass('active');
    });
});
