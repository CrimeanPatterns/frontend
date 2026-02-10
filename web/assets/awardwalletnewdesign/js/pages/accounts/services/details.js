/* global ActiveXObject */

define([
    'angular-boot',
    'jquery-boot',
    'lib/customizer',
    'dateTimeDiff',
    'directives/tabs',
    'ng-infinite-scroll',
    'pages/accounts/main',
    'pages/accounts/filters',
    'pages/accounts/uiPosition',
    'pages/accounts/services/cache',
    'pages/spentAnalysis/ServiceSpentAnalysis',
    'directives/dialog',
    'jquery-slim',
], function (angular, $, customizer, dateTimeDiff) {
    angular = angular && angular.__esModule ? angular.default : angular;

    /**
     * @typedef {Object} detailsService
     */
    angular
        .module('accounts')
        .service(
            'detailsService',
            /**
             * @this detailsService
             * @param $q
             * @param accountInfoCache
             * @param $timeout
             * @param $rootScope
             * @param DI
             */
            [
                '$q',
                'accountInfoCache',
                '$timeout',
                '$rootScope',
                'DI',
                function ($q, accountInfoCache, $timeout, $rootScope, DI) {
                    var service = this,
                        canceller,
                        timerOpen = null,
                        timerClose = null,
                        popup = $('#accountDetails'),
                        popupArrow = popup.find('.arrow-popup'),
                        popupPosition = 'left+10 center',
                        targetPosition = 'right center',
                        minHeight = popup.css('min-height'),
                        container = $(window),
                        collisionCallback = 'customFit',
                        currentTarget = null,
                        cacheInfo = accountInfoCache.get();
                    var methods = {
                        /**
                         * @param {object} a
                         * @param {object} b
                         */
                        using: function (a, b) {
                            var arrowHeight = popupArrow.outerHeight();
                            var popupMargin = parseInt(b.element.element.css('margin-top'));
                            var popupBorderTop = parseInt(b.element.element.css('border-top-width'));
                            var targetCenter = b.target.top + b.target.height / 2;
                            var arrowOffset =
                                targetCenter -
                                (a.top + b.element.element.parent().offset().top) -
                                arrowHeight / 2 -
                                popupBorderTop -
                                popupMargin;
                            if (arrowOffset + arrowHeight > b.element.element.height())
                                arrowOffset = b.element.element.height() - arrowHeight;
                            if (arrowOffset < 0) arrowOffset = 0;
                            $(this).css(a);
                            popupArrow.css({ top: arrowOffset + 'px' });
                        },
                        /**
                         * @returns {boolean}
                         */
                        popupVisible: function () {
                            return service.show;
                        },
                        /**
                         * @returns {boolean}
                         */
                        sameTarget: function (target) {
                            return currentTarget != null && target[0] === currentTarget[0];
                        },
                        showPopup: function () {
                            popup.css({
                                position: 'absolute',
                                left: '-10000px',
                            });
                            service.show = true;
                        },
                        hidePopup: function () {
                            service.show = false;
                        },
                        /**
                         * @param {string} id
                         * @returns {Promise}
                         */
                        loadAccountInfo: function (id) {
                            const defer = $q.defer();
                            const shown = new Date().getTime();
                            let cache = cacheInfo.get(id);

                            canceller = $q.defer();
                            if (typeof cache !== 'undefined') {
                                cache.shown = shown;
                                defer.resolve(cache);
                            } else {
                                if (id === 'totals') {
                                    const data = {
                                        DisplayName: 'Totals',
                                        TotalsTab: true,
                                    };
                                    cacheInfo.put(id, data);
                                    cache = cacheInfo.get(id);
                                    cache.shown = shown;
                                    defer.resolve(cache);
                                } else {
                                    const accountId = id.split('-')[0];
                                    const subAccountId = id.split('-')[1];
                                    DI.get('accounts')
                                        .one(accountId, subAccountId || null, canceller.promise)
                                        .then(function (data) {
                                            cacheInfo.put(id, data);

                                            for (let value in data.Properties) {
                                                const property = data.Properties[value];
                                                if (property.SortIndex)
                                                    property.SortIndex = parseInt(property.SortIndex);
                                            }

                                            defer.resolve(data);
                                        });
                                }
                            }

                            return defer.promise;
                        },
                        /**
                         * @param {string} cacheId
                         * @returns {boolean}
                         */
                        hasCache: function (cacheId) {
                            return typeof cacheInfo.get(cacheId) != 'undefined';
                        },

                        lockScroll: function () {
                            if (!service.mouseInside || methods.isActiveTabScrollable()) {
                                methods.unlockScroll();
                                return;
                            }
                            var root = document.compatMode === 'BackCompat' ? document.body : document.documentElement;
                            if (root.scrollHeight > root.clientHeight) {
                                if (this.isIeMetroMode()) {
                                    $('body').css({ overflow: 'hidden' });
                                } else {
                                    var scrWidth = this.getScrollbarWidth();
                                    $('body').css({
                                        overflow: 'hidden',
                                        'padding-right': scrWidth,
                                        'background-color': '#f0f1f2',
                                    });
                                    $('#headerSite, .nav-row.scrolled').css('padding-right', scrWidth);
                                }
                            }
                        },

                        unlockScroll: function () {
                            if (service.mouseInside && !methods.isActiveTabScrollable()) {
                                methods.lockScroll();
                                return;
                            }
                            $('body').css({
                                overflow: 'auto',
                                'padding-right': '0',
                                'background-color': 'initial',
                            });
                            $('#headerSite, .nav-row.scrolled').css('padding-right', 0);
                        },

                        isIeMetroMode: function () {
                            if ('undefined' !== typeof this._isIeMetroMode) return this._isIeMetroMode;

                            var isActiveX = null;
                            try {
                                isActiveX = !!new ActiveXObject('htmlfile');
                            } catch (e) {
                                isActiveX = false;
                            }

                            if (
                                false === isActiveX &&
                                'Win64' === navigator.platform &&
                                -1 !== navigator.appVersion.indexOf('x64;') &&
                                -1 !== navigator.appVersion.indexOf('rv:11.')
                            ) {
                                return (this._isIeMetroMode = true);
                            }
                            return (this._isIeMetroMode = false);
                        },
                        getScrollbarWidth: function () {
                            var $scr = $('#scrollbarIdentify');
                            if (!$scr.length) {
                                $scr = $(
                                    '<div id="scrollbarIdentify" style="position: absolute;top:-1000px;left:-1000px;width: 100px;height: 50px;box-sizing:border-box;overflow-x: scroll;"><div style="width: 100%;height: 200px;"></div></div>',
                                );
                                $('body').append($scr);
                            }
                            /*
                    var $inn = $('>div', $scr);
                    $scr.css('overflow-x', 'hidden');
                    var wNoScroll = $inn.innerWidth();
                    $scr.css('overflow-x', 'auto');
                    var wScroll = $inn.innerWidth();

                    return (wNoScroll - wScroll);
                    */
                            return $scr[0].offsetWidth - $scr[0].clientWidth;
                        },
                        isActiveTabScrollable: function () {
                            return (
                                popup.find('[data-pane-id][data-scrollable="true"] [data-ng-transclude]:visible')
                                    .length > 0
                            );
                        },
                    };

                    this.mouseInside = false;
                    this.show = false;
                    this.showLoader = true;
                    this.showContent = false;
                    this.hold = false;
                    this.arrow = true;
                    this._methods = methods;

                    /**
                     * @param {?object} event
                     * @param {string} id
                     * @param {boolean} withHold
                     * @param {?number} timeout
                     */
                    this.open = function (event, id, withHold, timeout, tab) {
                        timeout = timeout || 0;
                        $timeout.cancel(timerClose);
                        if (timeout > 0) {
                            timerOpen = $timeout(function () {
                                service.open(event, id, withHold);
                            }, timeout);
                            return;
                        }
                        var target;
                        if (event) {
                            target = $(event.target)
                                .closest('.sub-account-row-block, .account-row, .account-totals')
                                .find('.account-details > a');
                            service.arrow = true;
                        } else {
                            target = $('.content .main-ui-view .account-row:visible .show-details')
                                .first()
                                .closest('a');
                            if (!target.length) {
                                target = $('#details-anchor');
                                if (!target.length) {
                                    $('.content .main-ui-view').before(
                                        '<div id="details-anchor" style="height: 0; width: 8%"></div>',
                                    );
                                    target = $('#details-anchor');
                                }
                            }
                            service.arrow = false;
                        }
                        if (service.hold && !withHold) {
                            return;
                        }
                        if (!methods.sameTarget(target) && methods.popupVisible()) {
                            service.close(true);
                            $timeout(function () {
                                service.open(event, id, withHold);
                            });
                            return;
                        }

                        if (withHold) service.hold = true;
                        if (methods.sameTarget(target) && service.show) {
                            return;
                        }
                        currentTarget = target;
                        service.beforeContentLoaded(id);
                        if (!methods.popupVisible()) methods.showPopup();

                        $timeout(function () {
                            methods.loadAccountInfo(id).then(function (data) {
                                $rootScope.$broadcast('account.details.loaded', data, tab);
                            });
                        });
                        popup.on('mouseenter.scroll', function () {
                            service.mouseInside = true;
                            methods.lockScroll();
                        });
                        popup.on('mouseleave.scroll', function () {
                            service.mouseInside = false;
                            methods.unlockScroll();
                        });
                    };

                    /**
                     * @param {boolean} hard
                     * @param {?number} timeout
                     */
                    this.close = function (hard, timeout) {
                        timeout = timeout || 0;
                        $timeout.cancel(timerOpen);
                        if (timeout > 0) {
                            timerClose = $timeout(function () {
                                service.close(hard);
                            }, timeout);
                            return;
                        }
                        if (service.hold && !hard) return;
                        if (hard) service.hold = false;
                        if (methods.popupVisible()) methods.hidePopup();
                        popup.off('mouseenter.scroll mouseleave.scroll');
                        if (canceller) canceller.resolve();
                        $rootScope.$broadcast('account.details.close');
                    };

                    this.cancelOpen = function () {
                        $timeout.cancel(timerOpen);
                    };
                    this.cancelClose = function () {
                        $timeout.cancel(timerClose);
                    };
                    this.showDetailsFader = function () {
                        return true;
                    };

                    /**
                     * @param {object} target
                     */
                    this.position = function (target) {
                        target = target || currentTarget;
                        popup.position({
                            my: popupPosition,
                            at: targetPosition,
                            of: target,
                            using: methods.using,
                            within: container,
                            collision: collisionCallback,
                        });
                    };

                    /**
                     * @param accountId
                     */
                    this.beforeContentLoaded = function (accountId) {
                        if (methods.hasCache(accountId) && currentTarget.data('height')) {
                            popup.css({
                                'min-height': currentTarget.data('height').outer,
                            });
                            popup.find('.tabs-content').css({
                                'min-height': currentTarget.data('height').inner,
                            });
                        } else {
                            popup.css({
                                'min-height': minHeight,
                            });
                            popup.find('.tabs-content').css({
                                'min-height': '0px',
                            });
                        }
                        service.showLoader = !methods.hasCache(accountId);
                        service.showContent = false;
                    };

                    this.contentLoaded = function () {
                        service.showLoader = false;
                        service.showContent = true;
                    };

                    /**
                     * @param {Pane} pane
                     */
                    this.calcHeight = function (pane) {
                        var div = popup.find('.tabs-content');
                        var divHeight = div.outerHeight();
                        var screenHeight = screen.height;
                        var paneHeight = popup.find('[data-pane-id=' + pane.paneId + ']').outerHeight();

                        if (divHeight > parseInt(div.css('min-height'))) {
                            if (currentTarget && pane.paneId === 'details') {
                                currentTarget.data('height', {
                                    outer: popup.outerHeight(),
                                    inner: divHeight,
                                });
                            }
                            div.css({
                                'min-height': divHeight + 'px',
                            });
                        } else if (divHeight > screenHeight) {
                            if (currentTarget && pane.paneId === 'details') {
                                currentTarget.data('height', {
                                    outer: screenHeight,
                                    inner: paneHeight,
                                });
                            }
                            div.css({
                                'min-height': paneHeight + 'px',
                            });
                        }
                    };

                    this.lockScroll = function () {
                        methods.lockScroll();
                    };
                    this.unlockScroll = function () {
                        methods.unlockScroll();
                    };

                    this.getPopup = function () {
                        return popup;
                    };
                },
            ],
        )

        .controller(
            'detailsCtrl',
            /**
             * @param $scope
             * @param $timeout
             * @param detailsService
             * @param {object} accountProviderFactory
             */
            [
                '$scope',
                '$timeout',
                'detailsService',
                'DI',
                function ($scope, $timeout, detailsService, DI) {
                    this.accountInfo = {};
                    this.popup = detailsService;

                    var ct = this;
                    $scope.$on('account.details.loaded', function (event, data, openTab) {
                        const clientDateTransform = function (key, account, isYMD = false) {
                            const dateTimeFormatOptions = isYMD
                                ? { month: 'long', day: 'numeric', year: 'numeric' }
                                : {
                                      weekday: 'long',
                                      month: 'long',
                                      day: 'numeric',
                                      year: 'numeric',
                                      hour: 'numeric',
                                      minute: 'numeric',
                                  };
                            const dateFormat = new Intl.DateTimeFormat(customizer.locales(), dateTimeFormatOptions);
                            if (Object.prototype.hasOwnProperty.call(account, key + 'Ts') && account[key + 'Ts'] > 0) {
                                let date =
                                    isYMD && Object.prototype.hasOwnProperty.call(account, key + 'YMD')
                                        ? new Date(Date.parse(account[key + 'YMD'].split('+')[0]))
                                        : new Date(1000 * account[key + 'Ts']);
                                account[key + 'Frendly'] = dateTimeDiff.longFormatViaDateTimes(new Date(), date);
                                account[key] = dateFormat.format(date);
                            }
                        };
                        clientDateTransform('LastChangeDate', data);
                        clientDateTransform('SuccessCheckDate', data);
                        clientDateTransform('LastUpdatedDate', data);

                        ct.accountInfo = data;
                        ct.accountInfo.isDocument = ct.accountInfo.Kind === 11;
                        ct.accountInfo.document = ct.accountInfo.CustomFields || {};
                        ct.accountInfo.isPassport = ct.accountInfo.document.passport;
                        if (ct.accountInfo.document) {
                            ct.accountInfo.isVaccineCard = ct.accountInfo.document.vaccineCard || false;
                            ct.accountInfo.isInsuranceCard = ct.accountInfo.document.insuranceCard || false;
                            ct.accountInfo.isVisa = ct.accountInfo.document.visa || false;
                            ct.accountInfo.isDriversLicense = ct.accountInfo.document.driversLicense || false;
                            ct.accountInfo.isPriorityPass = ct.accountInfo.document.priorityPass || false;
                        }
                        ct.accountInfo.isMultipleImages =
                            ct.accountInfo.isPassport || ct.accountInfo.isVaccineCard || ct.accountInfo.isVisa;

                        if (ct.accountInfo.document.passport) {
                            const issueDate = ct.accountInfo.document.passport.issueDate;
                            const expirationDate = ct.accountInfo.document.passport.expirationDate;
                            ct.accountInfo.document.passport.issueDate =
                                issueDate && issueDate.date ? new Date(issueDate.date) : issueDate;
                            ct.accountInfo.document.passport.expirationDate =
                                expirationDate && expirationDate.date ? new Date(expirationDate.date) : expirationDate;
                        }
                        if (
                            true === ct.accountInfo.ExpirationKnown &&
                            -1 === [1, 3].indexOf(ct.accountInfo.ExpirationStateType)
                        )
                            clientDateTransform(
                                'ExpirationDate',
                                ct.accountInfo,
                                !Object.prototype.hasOwnProperty.call(ct.accountInfo, 'SubAccountID'),
                            );

                        detailsService.show = true;
                        $scope.$broadcast(
                            'tabs.update.details',
                            /**
                             * @param {object} ctrl
                             * @param {Tabs} tabs
                             */
                            function (ctrl, tabs) {
                                var panesCount;
                                angular.extend(data, DI.get('accounts').processExpirationTips(data));

                                // main pane
                                ctrl.getPane('details').enable = data.TotalsTab != null ? 0 : 1;

                                // balance chart
                                ctrl.getPane('chart').enable = data.BalanceChartTab != null ? 1 : 0;

                                // elite levels
                                ctrl.getPane('elitelevel').enable = data.EliteLevelTab != null ? 1 : 0;

                                // card image
                                ctrl.getPane('cardimage').enable =
                                    $().slim('supported') && data.CardImageTab != null ? 1 : 0;

                                // promotions
                                ctrl.getPane('promotions').enable =
                                    data.PromotionsTab != null || data.PromotionsBlogPost ? 1 : 0;

                                // detected credit cards
                                ctrl.getPane('detectedcreditcard').enable = data.DetectedCreditCardTab != null ? 1 : 0;

                                // comment
                                ctrl.getPane('comments').enable =
                                    data.comment != null && data.comment != '' && data.comment.length > 35 ? 1 : 0;

                                // history
                                ctrl.getPane('history').enable =
                                    data.HistoryTab != null || data.CanUploadHistory ? 1 : 0;

                                // totals
                                ctrl.getPane('totals').enable = data.TotalsTab != null ? 1 : 0;

                                panesCount =
                                    1 +
                                    ctrl.getPane('chart').enable +
                                    ctrl.getPane('elitelevel').enable +
                                    ctrl.getPane('promotions').enable +
                                    +ctrl.getPane('detectedcreditcard').enable +
                                    ctrl.getPane('comments').enable +
                                    ctrl.getPane('history').enable +
                                    ctrl.getPane('cardimage').enable;
                                tabs.showTabs = panesCount > 1;

                                // select first enabled pane
                                for (var k in tabs.panes) {
                                    if (tabs.panes[k].enable) {
                                        tabs.select(tabs.panes[k]);
                                        break;
                                    }
                                }
                                if (typeof openTab !== 'undefined') {
                                    var openPane = ctrl.getPane(openTab);
                                    if (openPane && openPane.enable) {
                                        tabs.select(openPane);
                                    }
                                }

                                $timeout(function () {
                                    detailsService.contentLoaded();
                                });

                                $scope.isTabEnabled = function (name) {
                                    for (let i in tabs.panes) {
                                        if (tabs.panes[i].paneId === name) {
                                            return tabs.panes[i].enable;
                                        }
                                    }
                                    return false;
                                };
                                $scope.tabSelect = function (name) {
                                    for (let i in tabs.panes) {
                                        if (tabs.panes[i].enable && tabs.panes[i].paneId === name) {
                                            tabs.select(tabs.panes[i]);
                                            break;
                                        }
                                    }
                                };
                            },
                        );
                    });

                    $scope.$on(
                        'tabs.select.details',
                        /**
                         * @param event
                         * @param {Pane} pane
                         */
                        function (event, pane) {
                            $timeout(function () {
                                detailsService.unlockScroll();
                                detailsService.calcHeight(pane);
                                ct.tabHeight = detailsService.getPopup().find('.tabs-content').height();
                                customizer.initTooltips();
                                customizer.dateUtc();
                            });
                        },
                    );

                    $scope.ratingUrl = function (name) {
                        return Routing.generate('aw_loyalty_program_rating', {
                            loyaltyProgramName: name,
                        });
                    };

                    var navIsActive, cwidth, old_cwidth;
                    $(window)
                        .on('scroll.accountDetails', function () {
                            if (detailsService.show /* && $('.nav-row').hasClass('active') != navIsActive*/) {
                                detailsService.position();
                                navIsActive = $('.nav-row').hasClass('active');
                            }
                        })
                        .on('resize.accountDetails', function () {
                            cwidth = $(window).width();
                            if (detailsService.show && (cwidth == null || cwidth != old_cwidth)) {
                                detailsService.position();
                                old_cwidth = cwidth;
                            }
                        });
                },
            ],
        )
        .controller('elitelevelCtrl', [
            '$scope',
            '$element',
            function ($scope, $element) {
                var makeSize = function (el, type) {
                    type = parseInt(type);
                    switch (type) {
                        case 0:
                        case 7:
                            var caption = el.children[0];
                            if (
                                el.offsetWidth - caption.offsetWidth < 3 ||
                                el.offsetHeight - caption.offsetHeight < 2
                            ) {
                                caption.style.display = 'none';
                            }
                            break;
                        case 1:
                        case 2:
                        case 3:
                        case 4:
                        case 5:
                            el.style.background = "url('/images/levels/reached" + type + ".png') no-repeat";
                            el.style.backgroundSize = '100% 100%';
                            var elName = el.children[0];
                            if (elName.offsetLeft < 10) {
                                elName.style.top = el.offsetHeight / 2 - elName.offsetHeight / 2 + 'px';
                                elName.style.right = '5px';
                            } else {
                                elName.style.top = el.offsetTop + el.offsetHeight / 2 - elName.offsetHeight / 2 + 'px';
                                elName.style.left = el.offsetLeft + el.offsetWidth - elName.offsetWidth - 5 + 'px';
                            }
                            if (type > 2) elName.style.color = 'white';
                            break;
                    }
                };
                $scope.$on('tabs.select.details', function (event, pane) {
                    if (pane.paneId != 'elitelevel') return;
                    var tableEl = $element.find('[data-account-id]');
                    var tabID = tableEl.attr('data-account-id');
                    if (!tableEl.data('rendered')) {
                        var tableWidth = tableEl.innerWidth();
                        var levelsCount = tableEl.data('levelsCount');
                        $('table.' + tabID + '_elite_chart td.' + tabID + '_eliteCell').each(function (e) {
                            var gap = 1;
                            if ($(this).hasClass('hasDelimiter')) gap += 9 + 2; // 9
                            if ($(this).hasClass('hasGroup')) gap += 10 + 1; // 10 - img width, 2 - grouping borders

                            $(this).width(Math.floor(($(this).data('initialLength') / levelsCount) * tableWidth) - gap);
                        });

                        var types = [
                            'progress',
                            'reached_1',
                            'reached_2',
                            'reached_3',
                            'reached_4',
                            'reached_5',
                            'delimiter',
                            'empty',
                            'group',
                        ];
                        angular.forEach(types, function (type, i) {
                            var els = $('.' + tabID + '_' + type).get();
                            var j = 0;
                            while (els[j] != undefined) {
                                makeSize(els[j], i);
                                j++;
                            }
                        });
                        tableEl.data('rendered', 1);
                        customizer.initAll("[data-pane-id='elitelevel']");
                    }
                });
            },
        ])
        .controller('accountHistoryCtrl', [
            '$scope',
            '$http',
            'dialogService',
            'SpentAnalysis',
            function ($scope, $http, dialogService, SpentAnalysis) {
                var eof = false,
                    clear = function () {
                        $scope.historyItems = [];
                        $scope.historyColumns = [];
                        $scope.historyExtra = [];
                        $scope.balanceCell = [];
                        $scope.historyBusy = false;
                        $scope.nextPageToken = null;
                        $scope.offerLoading = false;
                        $scope.offerDialogContent = null;
                    };
                clear();
                $scope.$on('account.details.loaded', function (event, data) {
                    clear();
                    $scope.accountInfo = data;
                });
                $scope.$on('tabs.select.details', function (event, pane) {
                    if (pane.paneId != 'history') return;
                    $scope.accountInfo.editHistory = $scope.accountInfo.SubAccountID
                        ? Routing.generate('aw_subaccount_history_view', {
                              accountId: $scope.accountInfo.ID,
                              subAccountId: $scope.accountInfo.SubAccountID,
                          })
                        : Routing.generate('aw_account_v2_history_view', {
                              accountId: $scope.accountInfo.ID,
                          });
                    if (!$scope.accountInfo.HistoryTab) return;
                    $scope.historyItems = angular.copy($scope.accountInfo.HistoryTab.historyRows);
                    $scope.historyColumns = angular.copy($scope.accountInfo.HistoryTab.historyColumns);
                    $scope.historyExtra = angular.copy($scope.accountInfo.HistoryTab.extra);
                    $scope.balanceCell = angular.copy($scope.accountInfo.HistoryTab.balance_cell);
                    $scope.historyMiles = angular.copy(!!$scope.accountInfo.HistoryTab.miles);
                    $scope.historyBusy = false;
                    $scope.nextPageToken = angular.copy($scope.accountInfo.HistoryTab.nextPageToken);

                    $('#history-container').removeClass('history-box-toggle');
                    $scope.accountInfo.historyBoxToggle = function () {
                        $('#history-container').removeClass('history-box-toggle');
                    };
                    $scope.tripLink = function (tripId) {
                        return Routing.generate('aw_timeline_show_trip', {
                            tripId: tripId,
                        });
                    };
                    $scope.historyCellClassName = function (item, row, col) {
                        return SpentAnalysis.transactionRowCss(item, row, col);
                    };
                    $scope.historyCcOffer = function (data) {
                        if (null === ($scope.ccOfferData = SpentAnalysis.getOfferData(data))) return false;
                        $('#history-container').addClass('history-box-toggle');
                    };

                    $scope.getMilesValue = function (item) {
                        var milesRow = item[item.length - 1];

                        if (typeof milesRow.isEp !== 'undefined') milesRow = item[item.length - 2];

                        return milesRow.value;
                    };

                    eof = false;
                    $('#history-container').scrollTop(0);
                });
                $scope.nextPage = function () {
                    if (
                        $scope.historyBusy ||
                        eof ||
                        $scope.accountInfo == null ||
                        angular.equals({}, $scope.accountInfo)
                    )
                        return;
                    $scope.historyBusy = true;

                    var requestParams = {
                        limit: 20,
                        accountId: $scope.accountInfo.ID,
                        nextPage: $scope.nextPageToken,
                    };

                    if ($scope.accountInfo.SubAccountID) requestParams.subAccountId = $scope.accountInfo.SubAccountID;

                    $http
                        .get(Routing.generate('aw_subaccount_history_data', requestParams))
                        .then((res) => res.data)
                        .then((res) => {
                            const items = res.historyRows;

                            if (angular.isArray(items)) {
                                if (items.length === 0) {
                                    eof = true;
                                } else {
                                    for (let i = 0; i < items.length; i++) {
                                        $scope.historyItems.push(items[i]);
                                    }
                                }
                            } else {
                                eof = true;
                            }
                            $scope.nextPageToken = res.nextPageToken;
                        })
                        .finally(() => ($scope.historyBusy = false));
                };

                $scope.showOfferPopup = function (uuid, merchant) {
                    window.console.log('Loading offer dialog...');

                    var dialog = dialogService.get('credit-card-offer-popup');
                    dialog.element.parent().find('.ui-dialog-title').html(SpentAnalysis.getOfferTitle(merchant));

                    dialog.setOption('buttons', [
                        {
                            text: 'OK',
                            click: function () {
                                dialog.close();
                            },
                            class: 'btn-blue',
                        },
                    ]);

                    $scope.offerLoading = true;
                    $http
                        .post(
                            Routing.generate('aw_spent_analysis_transaction_offer'),
                            $.param({
                                source: 'transaction-history&mid=web',
                                uuid: uuid,
                            }),
                            {
                                headers: {
                                    'Content-Type': 'application/x-www-form-urlencoded',
                                },
                            },
                        )
                        .then((res) => {
                            $scope.offerLoading = false;
                            $scope.offerDialogContent = res.data;
                        })
                        .catch(() => {
                            $scope.offerLoading = false;
                            dialog.close();
                        });

                    dialog.open();
                };
            },
        ])
        .controller('chartCtrl', [
            '$scope',
            function ($scope) {
                $scope.$on('account.details.loaded', function (event, data) {
                    $scope.chartUrl = null;
                    $scope.chartLoader = true;
                    $scope.accountInfo = data;
                });
                $scope.$on('tabs.select.details', function (event, pane) {
                    if (pane.paneId != 'chart' || $scope.chartUrl) return;
                    var pref = '';
                    if (window.devicePixelRatio && window.devicePixelRatio == 2) {
                        pref = '&retina=1';
                    }
                    var img = new Image();
                    img.onload = function () {
                        $scope.chartUrl = img.src;
                        $scope.chartLoader = false;
                        $scope.$apply();
                    };
                    img.src =
                        Routing.generate('aw_account_balancechart') + '?' + $scope.accountInfo.BalanceChartTab + pref;
                });
            },
        ])
        .controller('listTotalsCtrl', [
            '$scope',
            'DI',
            function ($scope, di) {
                $scope.$on('tabs.select.details', function (event, pane) {
                    if (pane.paneId != 'totals') return;
                    $scope.view = {};
                    $scope.kinds = di.get('kinds').getOrderedKinds();
                    $scope.totals = di.get('counters').getTotals();
                    var cnt = 0,
                        id = 0;
                    angular.forEach($scope.totals.byOwner, function (data, idx) {
                        if (data.viewCount) {
                            id = idx;
                            cnt++;
                        }
                    });
                    if (cnt > 1) {
                        $scope.title = Translator.trans(
                            /** @Desc("Multiple Users (%users%)") */ 'account.details.totals.header',
                            { users: cnt },
                        );
                    } else {
                        $scope.title = di.get('accounts').getUserName(id);
                    }
                    $scope.view.getItems = function (id, number) {
                        return di.get('kinds').getItems(id, number);
                    };

                    var userLocale =
                        $('a[data-target="select-language"]').data('language') ||
                        $('html').attr('lang').substr(0, 2) ||
                        null;

                    if (userLocale) {
                        var supportedLocales = Intl.NumberFormat.supportedLocalesOf(userLocale.substring(0, 2));
                        userLocale = supportedLocales.length ? supportedLocales[0] : null;
                    }

                    var formatter = userLocale
                        ? new Intl.NumberFormat(userLocale, {
                              maximumFractionDigits: 0,
                          })
                        : new Intl.NumberFormat();

                    $scope.numberToLocaleString = function (balance) {
                        if (undefined === balance) {
                            return '';
                        }
                        return formatter.format(balance);
                    };

                    const formatterCurrency = new Intl.NumberFormat(userLocale, {
                        style: 'currency',
                        currency: 'USD',
                    });
                    $scope.formatCurrency = function (value, decimal) {
                        if (undefined === value) {
                            return '';
                        }
                        value = Math.floor(value);
                        if (undefined !== decimal && 0 == decimal) {
                            return new Intl.NumberFormat(userLocale, {
                                style: 'currency',
                                currency: 'USD',
                                maximumFractionDigits: 0,
                            }).format(value);
                        }
                        return formatterCurrency.format(value);
                    };

                    $scope.writeTotals = function (totals) {
                        let text = [];
                        if (totals.totals.accounts > 0) {
                            text.push(
                                Translator.transChoice('accounts.n', totals.totals.accounts, {
                                    accounts: totals.totals.accounts,
                                }),
                            );
                        }
                        if (totals.totals.coupons > 0) {
                            text.push(totals.totals.coupons + ' coupons');
                        }
                        if (totals.totals.documents) {
                            text.push(totals.totals.documents + ' documents');
                        }

                        return text.join('<br>');
                    };
                });
            },
        ])
        .controller('cardimageCtrl', [
            '$scope',
            '$element',
            'detailsService',
            '$timeout',
            function ($scope, $element, detailsService, $timeout) {
                $scope.$on('account.details.loaded', function (event, data) {
                    $scope.cardImage = data.CardImageTab;
                    $scope.accountInfo = data;
                });

                $scope.$on('account.details.close', function (event, data) {
                    $element.css('opacity', 0);
                });

                $scope.$on('tabs.select.details', function (event, pane) {
                    if ('cardimage' !== pane.paneId || $scope.cardImage.isCreditCard) return;

                    $scope.$cardImage = $('.slim', $element);

                    detailsService.hold = true;
                    detailsService.position();
                    const cacheId =
                        $scope.accountInfo.FID +
                        (undefined === $scope.accountInfo.SubAccountID ? '' : 'sub' + $scope.accountInfo.SubAccountID);
                    const state = $scope.$cardImage.slim('isAttachedTo');
                    const isMultipleImages = $scope.accountInfo.isMultipleImages;

                    $scope.cards = {};
                    $scope.cards[cacheId] = isMultipleImages
                        ? $scope.accountInfo.DocumentImages || []
                        : $scope.accountInfo.CardImages;
                    $scope.documentImages = isMultipleImages ? [...$scope.cards[cacheId], {}] : [];
                    if (2 === state.length || undefined !== state[0]) {
                        if ($element.data('cacheId') === cacheId) {
                            $element.css('opacity', 1);
                        } else {
                            $scope.$cardImage.slim('destroy');
                            $scope.$cardImage.find('img').removeAttr('src');
                        }
                    }

                    let imageUrl;

                    if (isMultipleImages) {
                        imageUrl = Routing.generate('aw_document_image_coupon_handle', {
                            couponId: $scope.accountInfo.ID,
                        });
                    } else if ('Coupon' === $scope.accountInfo.TableName) {
                        imageUrl = Routing.generate('aw_card_image_coupon_handle', {
                            couponId: $scope.accountInfo.ID,
                        });
                    } else {
                        imageUrl = $scope.accountInfo.SubAccountID
                            ? Routing.generate('aw_card_image_subaccount_handle', {
                                  accountId: $scope.accountInfo.ID,
                                  subAccountId: $scope.accountInfo.SubAccountID,
                              })
                            : Routing.generate('aw_card_image_account_handle', {
                                  accountId: $scope.accountInfo.ID,
                              });
                    }

                    $timeout(() => init(), 50);

                    let initSrc = true;

                    if (true !== $element.data('init')) {
                        $element.on('click', '.cardimage-show', function () {
                            let src = $(this).parent().find('>img').attr('src');
                            let isActions = $('.slim-btn-group', $(this).parent()).is(':visible');
                            if (isActions && 'string' === typeof src) {
                                $('body').append(
                                    $(`
                            <div id="cardimage-view" class="cardimage-view">
                                <div class="cardimage-box" onclick="jQuery('#cardimage-view').remove();$('body').removeClass('overflow-hidden');"><div><img class="cardimage-src" alt="" src="${src}"></div></div>
                            </div>
                            `),
                                );
                                $('body').addClass('overflow-hidden');
                            } else {
                                $(this).parent().find('.slim-file-hopper').trigger('click');
                            }
                        });
                        $element.data('init', true);
                    }

                    function init() {
                        $scope.$cardImage = $('.slim', $element);

                        const subAccountId =
                            undefined !== $scope.accountInfo.SubAccountID ? $scope.accountInfo.SubAccountID : null;

                        angular.forEach($scope.cards[cacheId], function (data, key) {
                            const container = $($scope.$cardImage[key]);

                            if (container.data('shouldDestroy')) {
                                container.slim('destroy');
                                container.data('shouldDestroy', false);
                            }

                            if (
                                (isMultipleImages && !container.data('imageId')) ||
                                (subAccountId && subAccountId === data.SubAccountID) ||
                                (null === subAccountId && null === data.SubAccountID)
                            ) {
                                $scope.$cardImage.find('.js-cardimage-kind' + key).attr(
                                    'src',
                                    isMultipleImages
                                        ? Routing.generate('aw_document_image_download', {
                                              documentImageId: data.DocumentImageID,
                                          })
                                        : Routing.generate('aw_card_image_download', {
                                              cardImageId: data.CardImageID,
                                          }),
                                );
                            }

                            if (isMultipleImages) {
                                container.data('imageId', data.DocumentImageID);
                            }
                        });
                        let allowEdit = $scope.$cardImage.closest('[data-pane]').hasClass('cardimage-edit');
                        $scope.$cardImage.each(function (idx, cropper) {
                            const $box = $(this);
                            const place = $box.hasClass('left') ? 'Front' : 'Back';
                            const options = {
                                edit: allowEdit,
                                push: allowEdit,
                                download: allowEdit,
                                instantEdit: allowEdit,
                                minSize: {
                                    width: 100,
                                    height: 50,
                                },
                                meta: {
                                    token: $scope.cardImage.csrf,
                                },
                                ratio: isMultipleImages ? 'free' : '16:10',
                                forceType: 'jpg',
                                size: '1280,1280',
                                maxFileSize: 5 * 1024 * 1024,
                                service: isMultipleImages
                                    ? imageUrl + ($(cropper).data('imageId') ? `?id=${$(cropper).data('imageId')}` : '')
                                    : imageUrl + '?kind=' + place,
                                defaultInputName: 'card',
                                didUpload: function (error, slim, data) {
                                    if (data.success && data.CardImages) {
                                        $scope.cards[cacheId] = data.CardImages;
                                        if (isMultipleImages) {
                                            initSrc = true;
                                            $scope.accountInfo.DocumentImages = data.CardImages;

                                            //
                                            const existingImageIds = $scope.documentImages.map(
                                                (_) => _.DocumentImageID,
                                            );

                                            const newImage = data.CardImages.find(
                                                (_) => !existingImageIds.includes(_.DocumentImageID),
                                            );

                                            if (newImage) {
                                                $scope.documentImages.forEach((image, idx, arr) => {
                                                    if (!image.DocumentImageID)
                                                        arr[idx].DocumentImageID = newImage.DocumentImageID;
                                                });
                                                $scope.documentImages.push({});
                                                $(cropper).data('shouldDestroy', true);
                                            }

                                            $scope.$apply();
                                            $timeout(() => init(), 0);
                                        } else {
                                            if ('undefined' !== typeof data.CardImageId) {
                                                $('>img', $box).attr(
                                                    'src',
                                                    Routing.generate('aw_card_image_download', {
                                                        cardImageId: data.CardImageId,
                                                    }),
                                                );
                                            }
                                            $scope.accountInfo.CardImages = data.CardImages;
                                        }
                                    }
                                },
                                didTransform: function (data, slim) {
                                    slim._data.input.name = slim._data.input.file.name =
                                        (isMultipleImages
                                            ? `Passport_${cacheId.substring(1)}_${++idx}`
                                            : place + cacheId) +
                                        '.' +
                                        data.input.type.split('/')[1];
                                },
                                didReceiveServerError: function (error, defaultError) {
                                    return 'Invalid image format/data';
                                },
                                willSave: function willSave(data, cb) {
                                    cb(data);
                                    $('img.out', $box).css('opacity', 0);
                                    $('img.in', $box).css('opacity', 1);
                                },
                                willRequest: function (xhr) {
                                    xhr.setRequestHeader('X-CSRF-TOKEN', $scope.cardImage.csrf);
                                },
                                willRemove: function (c, cb) {
                                    const $box = isMultipleImages
                                        ? $(cropper)
                                        : $($scope.$cardImage['Front' === place ? 0 : 1]);
                                    const $confirmBox = $('.delete-popup', $box);
                                    $('.slim-btn', $box).css('display', 'none');
                                    $confirmBox
                                        .find('.js-cancel')
                                        .on('click', function () {
                                            $confirmBox.hide().find('button').off('click');
                                            $('.slim-btn', $box).css('display', 'block');
                                            return false;
                                        })
                                        .end()
                                        .find('.js-confirm')
                                        .on('click', function () {
                                            $confirmBox.addClass('wait');
                                            $.ajax({
                                                url: isMultipleImages
                                                    ? `${imageUrl}?id=${$(cropper).data('imageId')}`
                                                    : imageUrl + '?kind=' + place,
                                                type: 'DELETE',
                                                beforeSend: function (xhr) {
                                                    xhr.setRequestHeader('X-CSRF-TOKEN', $scope.cardImage.csrf);
                                                },
                                            })
                                                .done(function (data) {
                                                    if (data.success) {
                                                        cb();
                                                        if (isMultipleImages) {
                                                            initSrc = false;
                                                            const imageId = $(cropper).data('imageId');
                                                            $(cropper).slim('destroy');
                                                            $scope.cards[cacheId] = $scope.cards[cacheId].filter(
                                                                (_) => imageId !== _.DocumentImageID,
                                                            );
                                                            $scope.accountInfo.DocumentImages = $scope.cards[cacheId];
                                                            $scope.documentImages = $scope.documentImages.filter(
                                                                (_) => imageId !== _.DocumentImageID,
                                                            );
                                                            $scope.documentImages.splice(-1, 1);
                                                            $scope.documentImages.push({});
                                                            $scope.$apply();
                                                            $timeout(() => init(), 50);
                                                        } else {
                                                            delete $scope.cards[cacheId]['Front' == place ? 1 : 2];
                                                        }
                                                    }
                                                })
                                                .always(function (data) {
                                                    $confirmBox.hide().find('button').off('click');
                                                    setTimeout(function () {
                                                        $('.slim-btn', $box).css('display', 'block');
                                                        $confirmBox.removeClass('wait');
                                                    }, 500);
                                                });
                                        });
                                    $confirmBox.show();
                                    return true;
                                },
                                label:
                                    '<p>' +
                                    Translator.trans('card-pictures.' + place.toLowerCase() + '.title') +
                                    '</p>',
                                labelLoading: '<p>' + Translator.trans('card-pictures.loading-image') + '</p>',
                                statusFileType: '<p>' + Translator.trans('card-pictures.error.file-type') + '</p>',
                                statusFileSize: '<p>' + Translator.trans('card-pictures.error.big-file') + '</p>',
                                statusNoSupport:
                                    '<p>' + Translator.trans('card-pictures.error.crop-not-support') + '</p>',
                                statusImageTooSmall:
                                    '<p>' + Translator.trans('card-pictures.error.small-image') + '</p>',
                                statusContentLength:
                                    '<span class="slim-upload-status-icon"></span> ' +
                                    Translator.trans('card-pictures.error.big-content'),
                                statusUnknownResponse:
                                    '<span class="slim-upload-status-icon"></span> ' +
                                    Translator.trans('card-pictures.error.unknown'),
                                statusUploadSuccess:
                                    '<span class="slim-upload-status-icon"></span> ' +
                                    Translator.trans('card-pictures.status.saved'),
                                buttonConfirmLabel: Translator.trans('card-pictures.label.confirm'),
                                buttonCancelLabel: Translator.trans('card-pictures.label.cancel'),
                                buttonRotateLabel: Translator.trans('card-pictures.label.rotate'),
                                buttonRemoveLabel: Translator.trans('card-pictures.label.remove'),
                                buttonEditLabel: Translator.trans('card-pictures.label.edit'),
                                buttonDownloadLabel: Translator.trans('card-pictures.label.download'),
                                buttonConfirmTitle: Translator.trans('card-pictures.label.confirm'),
                                buttonCancelTitle: Translator.trans('card-pictures.label.cancel'),
                                buttonRotateTitle: Translator.trans('card-pictures.label.rotate'),
                                buttonRemoveTitle: Translator.trans('card-pictures.label.remove'),
                                buttonEditTitle: Translator.trans('card-pictures.label.edit'),
                                buttonDownloadTitle: Translator.trans('card-pictures.label.download'),
                            };

                            $(this).slim(options);
                            $('.slim-label > p', $(this)).attr(
                                'data-label',
                                Translator.trans('card-pictures.label.add-image'),
                            );
                        });
                        customizer.initTooltips($scope.$cardImage.find('[title]').attr('data-role', 'tooltip'));
                        $element.css('opacity', 1);
                        $element.data('cacheId', cacheId);
                    }
                });
            },
        ]);
});
