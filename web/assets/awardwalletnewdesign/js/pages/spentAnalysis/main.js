define([
    'jquery-boot',
    'centrifuge',
    'angular-boot',
    'lib/customizer',
    'lib/dialog',
    'lib/design',
    'chartjs', 'chartjs-plugin-datalabels',
    'translator-boot',
    'angular-ui-router', 'angular-scroll', 'ng-infinite-scroll',
    'filters/unsafe',
    'directives/dialog', 'directives/customizer',
    'pages/accounts/services/di',
    'pages/spentAnalysis/ServiceSpentAnalysis'
], function($, Centrifuge, angular, customizer, libDialog) {
    angular = angular && angular.__esModule ? angular.default : angular;

    var data = {},
        cards = {},
        user = {};

    // var mode = window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches;
    var mode = $('html').hasClass('dark-mode');

    var app = angular.module("spentAnalysisApp", [
        'appConfig', 'diService', 'ui.router', 'duScroll', 'infinite-scroll', 'customizer-directive', 'dialog-directive', 'unsafe-mod',
        'SpentAnalysisService'
    ]);

    app.config([
        '$injector', '$stateProvider', '$urlRouterProvider', '$locationProvider',
        function($injector, $stateProvider, $urlRouterProvider, $locationProvider) {
            $locationProvider.html5Mode({
                enabled      : true,
                rewriteLinks : false,
                requireBase  : false,
                hashPrefix   : '!'
            });

            if ($injector.has('user')) {
                user = $.extend(user, $injector.get('user'));
            }
            if ($injector.has('cards')) {
                cards = $injector.get('cards');
            }
            if ($injector.has('data')) {
                data = $.extend(data, $injector.get('data'));
            }
        }
    ]);

    Chart.Legend.prototype.afterFit = function() {
        this.height = this.height + 13;
    };

    var tools = {
        lockScroll : function() {
            var root = document.compatMode == 'BackCompat' ? document.body : document.documentElement;
            if (root.scrollHeight > root.clientHeight) {
                if (this.isIeMetroMode()) {
                    $('body').css({'overflow' : 'hidden'});
                } else {
                    var scrWidth = this.getScrollbarWidth();
                    $('body').css({'overflow' : 'hidden', 'padding-right' : scrWidth, 'background-color' : '#f0f1f2'});
                    $('#headerSite, .nav-row.scrolled').css('padding-right', scrWidth);
                }
            }
        },

        unlockScroll : function() {
            $('body').css({'overflow' : 'auto', 'padding-right' : '0', 'background-color' : 'initial'});
            $('#headerSite, .nav-row.scrolled').css('padding-right', 0);
        },

        isIeMetroMode     : function() {
            if ('undefined' !== typeof this._isIeMetroMode)
                return this._isIeMetroMode;

            var isActiveX = null;
            try {
                isActiveX = !!new window.ActiveXObject('htmlfile');
            } catch (e) {
                isActiveX = false;
            }

            if (false === isActiveX && 'Win64' === navigator.platform && -1 !== navigator.appVersion.indexOf('x64;') && -1 !== navigator.appVersion.indexOf('rv:11.')) {
                return (this._isIeMetroMode = true);
            }
            return (this._isIeMetroMode = false);
        },
        getScrollbarWidth : function() {
            var $scr = $('#scrollbarIdentify');
            if (!$scr.length) {
                $scr = $('<div id="scrollbarIdentify" style="position: absolute;top:-1000px;left:-1000px;width: 100px;height: 50px;box-sizing:border-box;overflow-x: scroll;"><div style="width: 100%;height: 200px;"></div></div>');
                $('body').append($scr);
            }

            return ($scr[0].offsetWidth - $scr[0].clientWidth);
        }
    };

    app.controller('SpentAnalysisCtrl', [
        '$scope', '$location', '$timeout', '$http', 'DI', 'dialogService', 'SpentAnalysis', '$transitions',
        function($scope, $location, $timeout, $http, di, dialogService, SpentAnalysis, $transitions) {
            SpentAnalysis.init(cards);

            var hashParams = {},
                transactions = [],
                reportData = [],
                cacheKey = 0,
                prevCacheKey = -1;

            var selfController = this,
                ownersList = SpentAnalysis.getOwners(),
                offerCardFilter = SpentAnalysis.getOfferCardFilter();

            function offerCardFilterProcess(offerCardFilter) {
                let isEmptyOffers = true;
                let cookieOfferCards = $.cookie('analyzerOfferCards2');
                let urlOfferCards = customizer.getQueryParameter('offerFilterIds');
                if ('null' !== urlOfferCards && 'undefined' !== typeof urlOfferCards && '' !== urlOfferCards && -1 === urlOfferCards.indexOf('object')) {
                    urlOfferCards = urlOfferCards.split(',').map((el) => parseInt(el));
                    isEmptyOffers = urlOfferCards.length === 0;
                }
                if ($.isArray(urlOfferCards) && urlOfferCards.length > 0) {
                    cookieOfferCards = urlOfferCards;
                } else if ('undefined' !== typeof cookieOfferCards && '' !== cookieOfferCards && -1 === cookieOfferCards.indexOf('object')) {
                    cookieOfferCards = cookieOfferCards.split('.').map((el) => parseInt(el));
                    isEmptyOffers = cookieOfferCards.length === 0;
                }

                for (let i in offerCardFilter) {
                    for (let j in offerCardFilter[i].cardsList) {
                        offerCardFilter[i].cardsList[j].checked = isEmptyOffers || -1 !== cookieOfferCards.indexOf(parseInt(offerCardFilter[i].cardsList[j].creditCardId));
                    }
                }

                return offerCardFilter;
            }

            var $spentOwner = $('#spentOwner'),
                $selectAllOwners = $('#select_all_owners'),
                $spentDateRange = $('#spentDateRange'),
                $spentContent = $('#spentContent');

            $scope.selectAllOwners = false;
            $scope.user = user;
            $scope.merchantTitle = '';
            $scope.merchantTitleSign = '';
            $scope.ownersList = ownersList;
            $scope.dateRanges = SpentAnalysis.getDateRanges();
            $scope.reportItems = [];
            $scope.offerCardFilter = offerCardFilterProcess(offerCardFilter);
            $scope.SpentAnalysis = SpentAnalysis;
            $scope.initialized = false;
            $scope.allCardSelected = true;

            selfController.loading = {
                isVisible: function() {
                    return $('> .ajax-loader', $spentContent).is(':visible');
                },
                show: function() {
                    $('> .ajax-loader', $spentContent).removeClass('ng-hide');
                },
                hide: function() {
                    $('> .ajax-loader', $spentContent).addClass('ng-hide');
                }
            };

            $selectAllOwners.change(function (event) {
                event.stopPropagation();
                changeOwner(event, $spentOwner.val(), !$scope.selectAllOwners);
            });

            $spentOwner.change(function(event) {
                changeOwner(event, $(this).val(), $scope.selectAllOwners);
            });

            const awaitCentrifugeData = (centrifuge_config, channel) => {
                const client = new Centrifuge(centrifuge_config);
                client.on('connect', function () {
                    console.log('centrifuge connected');
                });

                const subscription = client.subscribe(channel, (message) => {
                    const data = message.data;
                    console.log('transactions', message.data);
                    if (data.type === 'results') {
                        if (Object.prototype.hasOwnProperty.call(data, 'results') && Array.isArray(data.results)) {
                            transactions = data.results;
                            return selfController.fillForm();
                        }

                        selfController.dataNotFound(true);
                        return;
                    }
                    console.log('UNKNOWN MESSAGE TYPE');
                });
                client.connect();
            }

            var changeOwner = function(event, agentId, selectAllOwners) {
                var subId = [], cards = [], cardId = [];

                if ('' === agentId)
                    agentId = null;
                for (let i in ownersList) {
                    if ((agentId == ownersList[i].userAgentId || selectAllOwners) && angular.isArray(ownersList[i].availableCards)) {
                        if (selectAllOwners)
                            cards = [...cards, ...ownersList[i].availableCards.map(item => ({...item, owner: ownersList[i].name}))];
                        else if (agentId == ownersList[i].userAgentId)
                            cards = ownersList[i].availableCards.map(item => ({...item, owner: ownersList[i].name}));

                        for (let j in ownersList[i].availableCards)
                            subId.push(ownersList[i].availableCards[j].subAccountId);
                    }
                }

                for (let i in offerCardFilter) {
                    for (let j in offerCardFilter[i].cardsList) {
                        cardId.push(offerCardFilter[i].cardsList[j].creditCardId);
                    }
                }

                $timeout(function() {
                    $scope.$apply(function() {
                        $scope.cards = cards;
                        console.log('cards', $scope.cards);
                    });
                });

                if (!cards.length) {
                    selfController.dataNotFound(true);
                    return false;
                }

                selfController.loading.show();
                if ('undefined' === typeof event.isTrigger) {
                    return $.ajax({
                        cache    : true,
                        url      : Routing.generate('aw_spent_analysis_transactions_exists'),
                        data     : {
                            ids   : subId,
                            range : $spentDateRange.val()
                        },
                        method   : 'POST',
                        dataType : 'json',
                        success  : function(data) {
                            awaitCentrifugeData(data.centrifuge_config, data.channel);
                        },
                        error    : function(response) {
                            switch (response.status) {
                                case 404:
                                case 500:
                                    selfController.dataNotFound(true);
                                    break;
                            }
                        },
                        complete : function() {
                            //selfController.loading.hide();
                        }
                    });
                }

                if (Object.prototype.hasOwnProperty.call(hashParams, 'cards')) {
                    var hashcards = hashParams.cards.toString().split(',').map(function(e) {
                            return e | 0
                        }),
                        $spentCards = $('#spentCreditCards');
                    $('input', $spentCards).prop('checked', false);
                    for (let i in subId) {
                        if (-1 !== $.inArray(subId[i], hashcards)) {
                            $('input[value="' + subId[i] + '"]', $spentCards).prop('checked', true);
                        } else {
                            $('input[value="' + subId[i] + '"]', $spentCards).prop('checked', false);
                            delete subId[i];
                        }
                    }
                }

                if (Object.prototype.hasOwnProperty.call(hashParams, 'offerFilterIds')) {
                    var filterIds = hashParams.offerFilterIds.toString().split(',').map(function(e) {
                            return e | 0
                        }),
                        $cardUsed = $('#cardUsed');
                    $('input:not(.js-change-ignore)', $cardUsed).prop('checked', false);
                    for (let i in cardId) {
                        if (-1 !== $.inArray(cardId[i], filterIds)) {
                            $('input[value="' + cardId[i] + '"]', $cardUsed).prop('checked', true);
                        } else {
                            $('input[value="' + cardId[i] + '"]', $cardUsed).prop('checked', false);
                            delete cardId[i];
                        }
                    }
                }

                return selfController.fetchReport(subId, cardId);
            }

            $spentDateRange.change(function() {
                if (!angular.isArray(transactions))
                    return false;

                var $card, dateSelected = $(this).val() | 0;
                for (let i in transactions) {
                    if (transactions[i].dateRange === dateSelected) {
                        for (let j in transactions[i].cardsInfo) {
                            $card = $('input', '.js-spent-card' + transactions[i].cardsInfo[j].subAccountId);
                            if (true === transactions[i].cardsInfo[j].noTransactions) {
                                $card.prop('checked', false).attr('disabled', 'disabled');
                            } else {
                                $card.prop('checked', true).removeAttr('disabled');
                            }
                        }
                    }
                }

                return selfController.fetchReport();
            });

            selfController.fillForm = function() {
                if (!angular.isArray(transactions))
                    return false;

                var i, j, isAnyone, $dateOption;
                for (let i in transactions) {
                    isAnyone = false;
                    for (j in transactions[i].cardsInfo) {
                        if (false === transactions[i].cardsInfo[j].noTransactions) {
                            isAnyone = true;
                            break;
                        }
                    }

                    $dateOption = $('option[value="' + transactions[i].dateRange + '"]', $spentDateRange);
                    isAnyone ? $dateOption.removeAttr('hidden') : $dateOption.attr('hidden', 'hidden');
                }
                $spentDateRange.trigger('change');
            };

            selfController.forceFetchReport = function(ids, offerIds) {
                const cacheKey = selfController.getHashParams();
                if (reportData[cacheKey]) {
                    reportData[cacheKey] = null;
                }

                selfController.fetchReport();
            }

            selfController.fetchReport = function(ids, offerIds) {
                console.log('fetch report', { selectAllOwners: $scope.selectAllOwners, ownersList });

                var firstInit = (ids && ids.length > 0);
                ids || (ids = []);
                if (0 === ids.length) {
                    $('input:checked', $('#spentCreditCards')).each(function() {
                        ids.push($(this).val());
                    });
                }
                offerIds || (offerIds = []);
                if (0 === offerIds.length) {
                    $('.js-cardId:checked', $('#cardUsed')).each(function() {
                        offerIds.push($(this).val());
                    });
                }

                if (!ids.length)
                    return selfController.dataNotFound(true);

                if ('' === hashParams.cards)
                    hashParams.cards = ids.join(',');
                if ('' === hashParams.offerFilterIds)
                    hashParams.offerFilterIds = offerIds.join(',');

                cacheKey = selfController.getHashParams();
                if (prevCacheKey === cacheKey) {
                    //	return;
                }
                if (reportData[cacheKey] && !firstInit)
                    return selfController.fillReport();

                selfController.loading.show();
                return $.ajax({
                    cache    : true,
                    url      : Routing.generate('aw_spent_analysis_merchants_data'),
                    data     : {
                        ids            : (function() {
                            var tmp = [], i = 0, j = 0;
                            for (i in ids)
                                if ('' != ids[i])
                                    tmp.push(ids[i]);

                            if (0 === tmp.length) {
                                var agentId = $spentOwner.val();
                                if ('' === agentId)
                                    agentId = null;
                                for (i in ownersList) {
                                    if (agentId == ownersList[i].userAgentId && angular.isArray(ownersList[i].availableCards)) {
                                        for (j in ownersList[i].availableCards)
                                            tmp.push(ownersList[i].availableCards[j].subAccountId);
                                        break;
                                    }
                                }
                            }
                            return tmp;
                        })(),
                        range          : $spentDateRange.val(),
                        limit          : hashParams.limit,
                        offerFilterIds : (function() {
                            var tmp = [];
                            $('.js-cardId:checked', '#cardUsed').each(function() {
                                tmp.push($(this).val());
                            });
                            return tmp;
                        })()
                    },
                    method   : 'POST',
                    dataType : 'json',
                    success  : function(data) {
                        if (0 === data.rows.length && true === firstInit && 2 == $spentDateRange.val()) {
                            $spentDateRange.val(4);
                            return selfController.fetchReport();
                        }

                        prevCacheKey = cacheKey;
                        reportData[cacheKey] = data;
                        selfController.fillReport();
                    },
                    error    : function(response) {
                        switch (response.status) {
                            case 404:
                            case 500:
                                selfController.dataNotFound(true);
                                break;
                        }
                        selfController.loading.hide();
                    }
                });
            };

            selfController.getDiffHours = function() {
                if (data.lastSuccessCheck) {
                    var timeDiff = Math.abs((new Date()).getTime() - (new Date(1000 * data.lastSuccessCheck)).getTime());
                    return Math.ceil(timeDiff / (1000 * 3600));
                }
                return null;
            };
            selfController.fillReport = function() {
                if (!reportData && !reportData[cacheKey] || !reportData[cacheKey].rows || !angular.isArray(reportData[cacheKey].rows) || 0 === reportData[cacheKey].rows.length)
                    return selfController.dataNotFound(true);

                $timeout(function() {
                    $scope.$apply(function() {
                        var dateFormat = new Intl.DateTimeFormat(customizer.locales(), {day : 'numeric', month : 'long', year : 'numeric'});

                        $scope.merchantTitle = Translator.trans(
                            /** @Desc("%date_range%Merchant Spend Analysis for %owner_name%") */
                            'spent-analysis.merchant.spend-analysis-for', {
                                'date_range' : $('option[value="' + $spentDateRange.val() + '"]', $spentDateRange).text() + ' ',
                                'owner_name' : $('option[value="' + $spentOwner.val() + '"]', $spentOwner).text()
                            });

                        var startDate = reportData[cacheKey].startDate.split('-'),
                            endDate = reportData[cacheKey].endDate.split('-');

                        var merchantTitleSign = Translator.trans(
                            /** @Desc("Transactions analyzed for this report posted between %date_start%, and %date_end%") */
                            'spent-analysis.actual-transactions.analyzed-between', {
                                'date_start' : dateFormat.format(new Date(Date.UTC(startDate[0], startDate[1] - 1, startDate[2], 12, 0, 0))),
                                'date_end'   : dateFormat.format(new Date(Date.UTC(endDate[0], endDate[1] - 1, endDate[2], 12, 0, 0)))
                            });

                        var diffHours = selfController.getDiffHours();
                        if (diffHours > 24) {
                            merchantTitleSign += ', ' + Translator.trans(
                                /** @Desc("%link_update_on%update your accounts%link_update_off% to retrieve the latest transactions") */
                                'spent-analysis.actual-transactions.analyzed-between.update', {
                                    'link_update_on'  : '<a class="blue-link" href="' + Routing.generate('aw_account_list') + '/#credit+cards">',
                                    'link_update_off' : '</a>'
                                });
                        }

                        $scope.merchantTitleSign = merchantTitleSign;
                        $scope.reportItems = selfController.dataCompatibilityMode(reportData[cacheKey].rows);
                        selfController.chartDraw();
                    });
                }, 50);

                $timeout(function() {
                    $('input', '#spentCreditCards').prop('checked', false);
                    for (var i in reportData[cacheKey].ids)
                        $('input[value="' + reportData[cacheKey].ids[i] + '"]', '#spentCreditCards').prop('checked', true);

                    selfController.setHashParams();
                }, 100);

                return selfController.dataNotFound(false);
            };
            selfController.dataNotFound = function(toggle) {
                if (toggle) {
                    $('.cardspend-data', $spentContent).attr('hidden', 'hidden');
                    $('.cardspend-error', $spentContent).removeAttr('hidden');

                    var lastUpdated = '', diffHours = selfController.getDiffHours();
                    if (null !== diffHours && diffHours > 24) {
                        lastUpdated = Translator.trans(
                            /** @Desc("Your credit card accounts were last updated %count_day% days ago or more.") */
                            'spent-analysis.account.last-updated.days', {'count_day' : ~~(diffHours / 24)});
                    }
                    $scope.$apply(function() {
                        $scope.errorText = Translator.trans(
                            /** @Desc("AwardWallet has not detected any transactions to be analyzed based on the report criteria supplied. %accounts_last_updated% Please %link_update_on%update your credit card accounts%link_update_off% now to retrieve the most recent transactions. Please return to this report tomorrow to view your updated report") */
                            'spent-analysis.transactions.not-detected', {
                                'link_update_on'        : '<a href="/account/list/#credit+cards">',
                                'link_update_off'       : '</a>',
                                'accounts_last_updated' : lastUpdated
                            });
                    });

                } else {
                    $('.cardspend-data', $spentContent).removeAttr('hidden');
                    $('.cardspend-error', $spentContent).attr('hidden', 'hidden');
                }

                return selfController.loading.hide();
            };

            selfController.dataCompatibilityMode = function(items) {
                var data = [];
                for (var i in items) {
                    data.push({
                        merchantId: items[i].merchantId,
                        transactions: items[i].transactions,
                        isEp: true,
                        isProfit: items[i].isProfit,
                        multiplier: items[i].multiplier,
                        postingDate: Object.prototype.hasOwnProperty.call(items[i], "postingDate") ? items[i].postingDate : null,
                        creditCardName: Object.prototype.hasOwnProperty.call(items[i], "creditCardName") ? items[i].creditCardName : null,
                        subAccountId: items[i].subAccountId,
                        formatted: items[i].formatted,
                        offerData: {
                            uuid: items[i].UUID,
                            merchantName: items[i].merchantName,
                            category: items[i].category,
                            description: items[i].merchantName,
                            blogUrl: items[i].blogUrl,
                            isEveryPurchaseCategory: Object.prototype.hasOwnProperty.call(items[i], "isEveryPurchaseCategory") ? items[i].isEveryPurchaseCategory : false,
                            multiplier: items[i].multiplier,
                            amountRaw: items[i].amount,
                            milesRaw: items[i].miles,
                            milesValue: items[i].milesValue,
                            minValue: items[i].minValue,
                            maxValue: items[i].maxValue,
                            potential: Object.prototype.hasOwnProperty.call(items[i], "potential") ? items[i].potential : null,
                            potentialMiles: items[i].potentialMiles,
                            potentialMilesValue: items[i].potentialMilesValue,
                            potentialMinValue: items[i].potentialMinValue,
                            potentialMaxValue: items[i].potentialMaxValue,
                            earningPotentialColor: items[i].earningPotentialColor,

                            amount: items[i].amountFormatted,
                            miles: items[i].milesFormatted,
                            potentialMilesFormatted: items[i].potentialMilesFormatted,
                            potentialMilesValueFormatted: items[i].potentialMilesValueFormatted,
                            milesValueFormatted: items[i].milesValueFormatted,
                        },
                        type: "miles",
                    });
                }

                return data;
            };

            $scope.multiplierCss = function(item) {
                if (0 === item.merchantId)
                    return `potential-cell ${item.offerData.earningPotentialColor} ` + SpentAnalysis.getMultiplierCss(item).replace('pointer', '');

                if (Object.prototype.hasOwnProperty.call(item, 'potential') && item.multiplier === item.potential)
                    return 'potential-cell transp-like';

                return `potential-cell ${item.offerData.earningPotentialColor} ` + SpentAnalysis.getMultiplierCss(item);
            };

            $scope.transactionOfferData = function(item) {
                if (item.offerData.uuid == undefined || item.offerData.potential == undefined) {
                    return;
                }
                window.console.log('Loading offer dialog...');

                var dialog = dialogService.get('credit-card-offer-popup');
                dialog.element.parent().find('.ui-dialog-title').html(SpentAnalysis.getOfferTitle(item.offerData.merchantName));

                dialog.setOption('buttons', [
                    {
                        text    : 'OK',
                        click   : function() {
                            dialog.close();
                        },
                        'class' : 'btn-blue'
                    }
                ]);

                SpentAnalysis.state.offerLoading = true;
                $http.post(
                    Routing.generate('aw_spent_analysis_transaction_offer'),
                    $.param({
                        source         : "spend-analysis&mid=web&source=aw_app",
                        uuid           : item.offerData.uuid,
                        amount         : item.offerData.amountRaw,
                        miles          : item.offerData.milesRaw,
                        offerFilterIds : (function() {
                            var tmp = [];
                            $('.js-cardId:checked', '#cardUsed').each(function() {
                                tmp.push(parseInt($(this).val()));
                            });
                            return tmp;
                        })()
                    }),
                    {headers : {'Content-Type' : 'application/x-www-form-urlencoded'}}
                ).then(
                    res => {
                        SpentAnalysis.state.offerLoading = false;
                        selfController.offerDialogContent = res.data;
                        setTimeout(() => {
                            customizer.initTooltips($(dialog.element));
                            if (undefined !== window['pageOnLoad']) {
                                window.pageOnLoad();
                            }
                        }, 500);
                    }
                ).catch(
                    () => {
                        SpentAnalysis.state.offerLoading = false;
                        dialog.close();
                    }
                );

                dialog.open();
                $(dialog.element).closest('.ui-dialog').css({'position':'fixed', 'left': '2%', 'top': '80px', 'width': '96%'});
            };

            $scope.getSubAccountOwner = (subAccountId) => {
                const result = $scope.cards.find(card => subAccountId == card.subAccountId);
                console.log("subacc item", { subAccountId, result });
                return result?.owner;
            };

            selfController.chartDraw = function() {
                if ('undefined' === typeof Chart) {
                    if ('undefined' === typeof this.chartClassInit)
                        this.chartClassInit = 0;
                    $('#spentChartCanvas').hide();
                    if (++this.chartClassInit < 6) {
                        $timeout(function() {
                            $('#spentChartCanvas').show();
                            selfController.chartDraw();
                        }, 1000);
                    }
                    return;
                }

                const datalabels = {
                    labels : {
                        value : {
                            align : 'bottom',
                            anchor : 'end',
                            color : function(ctx) {
                                if (0 === ctx.datasetIndex) {
                                    return mode ? 'rgb(9, 132, 255)' : 'rgb(70, 130, 195)';
                                }
                                return mode ? 'rgb(48, 209, 120)' : 'rgb(75, 190, 160)';
                            },
                            font : {
                                size : 13,
                                weight : 'bold'
                            },
                            formatter : function(value, ctx) {
                                return '$' + Intl.NumberFormat(customizer.locales(), {'minimumFractionDigits' : 2, 'maximumFractionDigits' : 2}).format(value);
                            },
                            offset : -42,
                        },
                        cost : {
                            align : 'bottom',
                            anchor : 'end',
                            color : function(ctx) {
                                if (0 === ctx.datasetIndex) {
                                    return mode ? 'rgb(9, 132, 255)' : 'rgb(70, 130, 195)';
                                }
                                return mode ? 'rgb(48, 209, 120)' : 'rgb(75, 190, 160)';
                            },
                            font : {
                                size : 13,
                                weight : 'bold'
                            },
                            formatter : function(value, ctx) {
                                let val = ctx.dataset.mileValueFormatted[ctx.dataIndex] + '';
                                if ('.' === val.substr(-2)[0] || ',' === val.substr(-2)[0])
                                    val += '0';
                                return val;
                            },
                            offset : -22,
                        }
                    }
                }

                var chartData = {
                    labels   : [],
                    datasets : [{
                        label           : 'Earned $',
                        backgroundColor : mode ? 'rgb(9, 132, 255)' : 'rgb(70, 130, 195)',
                        borderWidth     : 0,
                        data            : [],
                        mileValueFormatted : [],
                        datalabels : datalabels,
                    }, {
                        label           : 'Potential $',
                        backgroundColor : mode ? 'rgb(48, 209, 120)' : 'rgb(75, 190, 160)',
                        borderWidth     : 0,
                        data            : [],
                        mileValueFormatted : [],
                        datalabels : datalabels,
                    }]
                };

                var i = 0, limit = ($location.search().chart | 0);
                2 > limit || 10 < limit ? limit = 6 : null;
                for (var key in reportData[cacheKey].rows) {
                    if (++i === limit) {
                        var otherKey = reportData[cacheKey].rows.length - 2;
                        if (otherKey == i)
                            break;
                        if ('undefined' !== typeof reportData[cacheKey].rows[4] && reportData[cacheKey].rows[4].potentialMiles < reportData[cacheKey].rows[otherKey].potentialMiles)
                            key = otherKey;
                    }

                    chartData.labels.push(reportData[cacheKey].rows[key].merchantName);
                    // chartData.datasets[0].data.push(reportData[cacheKey].rows[key].miles);
                    chartData.datasets[0].data.push(reportData[cacheKey].rows[key].milesValue.toFixed(2));
                    chartData.datasets[0].mileValueFormatted.push(reportData[cacheKey].rows[key].milesFormatted)
                    // chartData.datasets[1].data.push(reportData[cacheKey].rows[key].potentialMiles);
                    chartData.datasets[1].data.push(reportData[cacheKey].rows[key].potentialMilesValue.toFixed(2));
                    chartData.datasets[1].mileValueFormatted.push(reportData[cacheKey].rows[key].potentialMilesFormatted);

                    if (i >= limit)
                        break;
                }

                if (window.spentChart && 'function' === typeof window.spentChart['destroy'])
                    window.spentChart.destroy();
                window.spentChart = new Chart(document.getElementById('spentChartCanvas').getContext('2d'), {
                    type    : 'bar',
                    data    : chartData,
                    options : {
                        responsive : true,
                        legend     : {
                            position : 'top'
                        },
                        tooltips   : {
                            mode            : 'index',
                            callbacks       : {
                                title : function(tooltipItems, data) {
                                    return data.labels[tooltipItems[0].index];
                                },
                                label : function(tooltipItem, data) {
                                    return ' ' + chartData.datasets[tooltipItem.datasetIndex].label + Intl.NumberFormat(customizer.locales(), {'minimumFractionDigits' : 2, 'maximumFractionDigits' : 2}).format(chartData.datasets[tooltipItem.datasetIndex].data[tooltipItem.index]);
                                }
                            },
                            footerFontStyle : 'normal'
                        },
                        layout     : {
                            padding : {
                                left   : 0,
                                right  : 0,
                                top    : 10,
                                bottom : 0
                            }
                        },
                        scales     : {
                            xAxes : [{
                                stacked     : false,
                                beginAtZero : true,
                                ticks       : {
                                    stepSize : 0,
                                    min      : 0,
                                    autoSkip : false,
                                    callback : function(value) {
                                        if (value.length > 16)
                                            return value.substr(0, 16) + '…';
                                        return value;
                                    }
                                }
                            }],
                            yAxes : [{
                                ticks : {
                                    callback : function(label) {
                                        return Intl.NumberFormat(customizer.locales()).format(label);
                                    }
                                }
                            }]
                        }
                    }
                });
            };


            $(window).on('hashchange', function(event) {
                var params = $location.search();
                if (Object.prototype.hasOwnProperty.call(params, 'chart')) {
                    selfController.chartDraw();
                }
            });

            selfController.hashParse = function() {
                var setFromHash = false,
                    params = $location.search();
                if (Object.prototype.hasOwnProperty.call(params, 'limit')) {
                    hashParams.limit = params.limit;
                }
                if (Object.prototype.hasOwnProperty.call(params, 'cards')) {
                    hashParams.cards = params.cards;
                }
                if (Object.prototype.hasOwnProperty.call(params, 'offerFilterIds')) {
                    hashParams.offerFilterIds = params.offerFilterIds;
                }
                if (Object.prototype.hasOwnProperty.call(params, 'date') && $('option[value="' + params.date + '"]', $spentDateRange).length) {
                    hashParams.date = params.date;
                    $spentDateRange.val(params.date);
                    setFromHash = true;
                }
                if (Object.prototype.hasOwnProperty.call(params, 'owner') && $('option[value="' + params.owner + '"]', $spentOwner).length) {
                    hashParams.owner = params.owner;
                    $spentOwner.val(params.owner);
                    $spentOwner.trigger('change');
                    setFromHash = true;
                }

                return setFromHash;
            };
            selfController.setHashParams = function() {
                var _cards = hashParams.cards,
                    _filterIds = hashParams.offerFilterIds,
                    _limit = hashParams.limit ?? 15;
                hashParams = {
                    'owner'          : $spentOwner.val(),
                    'date'           : $spentDateRange.val(),
                    'limit'          : _limit,
                    'cards'          : function() {
                        var ids = [];
                        $('input:checked', $('#spentCreditCards')).each(function() {
                            ids.push($(this).val());
                        });
                        return ids.join(',');
                    }(),
                    'offerFilterIds' : (function() {
                        var tmp = [];
                        $('.js-cardId:checked', '#cardUsed').each(function() {
                            tmp.push($(this).val());
                        });
                        return tmp.join(',');
                    })()
                };
                if ('' === hashParams.cards && '' !== _cards)
                    hashParams.cards = _cards;
                if ('' === hashParams.cards || ',' === hashParams.cards)
                    delete hashParams.cards;

                //if ('' === hashParams.offerFilterIds && '' !== _filterIds)
                //    hashParams.offerFilterIds = _filterIds;
                if ('' === hashParams.offerFilterIds || ',' === hashParams.offerFilterIds)
                    delete hashParams.offerFilterIds;

                $location.search(hashParams);
            };
            $spentContent
                .on('change', 'input,select', function(event) {
                    if (0 === $('.js-cardId:checked', '#cardUsed').length) {
                        $(event.currentTarget).prop('checked', true);
                        libDialog.fastCreate('Error', 'For this report to work, at least one card must be selected', true, true, [{
                            'text': Translator.trans('button.ok'),
                            'click': function() {
                                $(this).dialog("close");
                            },
                            'class': 'btn-silver'
                        }], 500, 300, 'error');
                        return;
                    }
                    if ($(this).hasClass('js-change-ignore'))
                        return;

                    selfController.setHashParams();
                    if ('INPUT' === event.currentTarget.nodeName) {
                        if (!$(event.currentTarget).hasClass('js-groupProvider')) {
                            selfController.fetchReport();
                        }
                        selfController.cardUsed.toggleAllCardSelected(event);
                    }
                });

            selfController.getHashParams = function() {
                selfController.setHashParams();
                var param = '';
                for (let i in hashParams)
                    param += i + '_' + hashParams[i] + '|';
                if (0 === param.length) return '';

                var hash = 0;
                for (let i = 0; i < param.length; i++) {
                    const char = param.charCodeAt(i);
                    hash = ((hash << 5) - hash) + char;
                    hash = hash & hash;
                }
                return hash;
            };

            selfController.popup = {
                cacheData           : [],
                loading             : true,
                show                : false,
                hold                : false,
                timerOpen           : null,
                timerClose          : null,
                currentTarget       : null,
                $popup              : $('#analysisPopup'),
                $arrowPopup         : $('#analysisPopup .arrow-popup'),
                popupVisible        : function() {
                    return this.$popup.is(':visible');
                },
                showPopup           : function() {
                    this.$popup.show();
                    this.show = true;
                },
                hidePopup           : function() {
                    this.$popup.hide();
                    this.show = false;
                },
                sameTarget          : function(target) {
                    return this.currentTarget != null && target[0] === this.currentTarget[0];
                },
                open                : function(event, item, withHold, timeout, tab) {
                    timeout = timeout || 0;
                    this.cancelClose();//$timeout.cancel(this.timerClose);
                    if (timeout > 0) {
                        return this.timerOpen = $timeout(function() {
                            selfController.popup.open(event, item, withHold);
                        }, timeout);
                    }

                    var target;
                    if (event) {
                        target = $(event.target).closest('tr').find('.account-details > .show-details');
                    } else {
                        target = $('.account-details:first .show-details', $spentContent);
                    }

                    if (this.hold && !withHold) {
                        return;
                    }
                    if (!this.sameTarget(target) && this.popupVisible()) {
                        selfController.popup.close(true);
                        return $timeout(function() {
                            selfController.popup.open(event, item, withHold);
                        });
                    }

                    if (withHold)
                        this.hold = true;
                    if (this.sameTarget(target) && this.popupVisible()) {
                        return;
                    }
                    this.currentTarget = target;
                    this.beforeContentLoaded(item);

                    if (!this.popupVisible())
                        this.showPopup();

                    $timeout(function() {
                        selfController.popup
                            .loadMerchantInfo(item)
                            .then(function(data) {
                                $scope.$apply(function() {
                                    $scope.popup.loading = false;
                                    $scope.merchantItems = data;
                                    console.log('merchantItems', $scope.merchantItems);
                                    /*$timeout(function() {
                                        $('.merchant-content', selfController.popup.$popup).stop().animate({scrollTop : 0}, 200, 'swing');
                                    }, 100);*/
                                });
                            });
                    });
                    this.$popup
                        .on('mouseenter.scroll', function() {
                            tools.lockScroll();
                        })
                        .on('mouseleave.scroll', function() {
                            tools.unlockScroll();
                        });

                    return this.position();
                },
                close               : function(hard, timeout) {
                    timeout = timeout || 0;
                    this.cancelOpen();//$timeout.cancel(this.timerOpen);
                    if (timeout > 0) {
                        return this.timerClose = $timeout(function() {
                            selfController.popup.close(hard);
                        }, timeout);
                    }
                    if (this.hold && !hard) return;
                    if (hard)
                        this.hold = false;
                    if (this.popupVisible())
                        this.hidePopup();
                    this.$popup.off('mouseenter.scroll mouseleave.scroll');
                    return;
                },
                cancelClose         : function() {
                    $timeout.cancel(this.timerClose);
                },
                cancelOpen          : function() {
                    $timeout.cancel(this.timerOpen);
                },
                position            : function(target) {
                    this.$popup
                        .position({
                            my        : 'left+10 center',
                            at        : 'right center',
                            of        : (target || this.currentTarget),
                            using     : function(a, b) {
                                var arrowHeight = selfController.popup.$arrowPopup.outerHeight();
                                var popupMargin = parseInt(b.element.element.css('margin-top'));
                                var popupBorderTop = parseInt(b.element.element.css('border-top-width'));
                                var targetCenter = b.target.top + (b.target.height / 2);
                                var arrowOffset = targetCenter - (a.top + b.element.element.parent().offset().top) - (arrowHeight / 2) - popupBorderTop - popupMargin;
                                if ((arrowOffset + arrowHeight) > b.element.element.height())
                                    arrowOffset = b.element.element.height() - arrowHeight;
                                if (arrowOffset < 0)
                                    arrowOffset = 0;
                                $(this).css(a);
                                selfController.popup.$arrowPopup.css({top : arrowOffset + "px"});
                            },
                            collision : 'customFit'
                        })
                },
                beforeContentLoaded : function(item) {
                    $scope.popup.loading = true;
                    $scope.merchantTitleItems = {
                        name     : item.offerData.merchantName,
                        category : item.offerData.category
                    };
                    $scope.merchantItems = [];
                },
                loadMerchantInfo    : function(item) {
                    var cacheKey = selfController.getHashParams() + '_m' + item.merchantId,
                        deferred = $.Deferred(),
                        post = {
                            'merchant'       : item.merchantId,
                            'range'          : $spentDateRange.val(),
                            'ids'            : function() {
                                var ids = [];
                                $('input:checked', $('#spentCreditCards')).each(function() {
                                    ids.push($(this).val());
                                });
                                return ids;
                            }(),
                            'offerFilterIds' : (function() {
                                var tmp = [];
                                $('.js-cardId:checked', '#cardUsed').each(function() {
                                    tmp.push($(this).val());
                                });
                                return tmp;
                            })()
                        };

                    if (typeof this.cacheData[cacheKey] !== 'undefined') {
                        deferred.resolve(this.cacheData[cacheKey]);
                    } else {

                        $.ajax({
                            cache    : true,
                            global   : false,
                            method   : 'POST',
                            url      : Routing.generate('aw_spent_analysis_merchants_transactions'),
                            data     : post,
                            dataType : 'json'
                        })
                            .done(function(data) {
                                var tmp;
                                for (var i in data.rows) {
                                    if (-1 !== data.rows[i].postingDate.indexOf('-')) {
                                        tmp = data.rows[i].postingDate.split('-');
                                        data.rows[i].postingDate = tmp[1] + '/' + tmp[2] + '/' + tmp[0];
                                    }
                                }
                                selfController.popup.cacheData[cacheKey] = selfController.dataCompatibilityMode(data.rows);
                                deferred.resolve(selfController.popup.cacheData[cacheKey]);
                            })
                            .fail(function() {
                                $scope.popup.loading = false;
                                deferred.reject();
                            });
                    }

                    return deferred.promise();
                }
            };
            $scope.popup = selfController.popup;

            let cardExists = null;
            selfController.isExistsCardType = (type) => null === cardExists ? true : cardExists[type];

            selfController.cardUsed = {
                toggleBox : function($event) {
                    $event.preventDefault();
                    $event.stopPropagation();
                    this.groupProviderCheckState();
                    $($event.target).closest('.analysis-cards').toggleClass('active');
                },
                markCard  : function($event, markType) {
                    $event.preventDefault();
                    $event.stopPropagation();

                    markType || (markType = null);
                    var $box = $('#cardUsed');
                    var $dropDown = $('.dropdown-submenu', $box);

                    if (null === markType) {
                        if ($dropDown.is(':visible')) {
                            $dropDown.hide();
                        } else {
                            $dropDown.show();
                            $(document).one('click', function() {
                                $dropDown.hide();
                            });
                        }
                        return;
                    }

                    if ('all' === markType) {
                        $('.js-cardId', $box).prop('checked', true);
                        $scope.allCardSelected = true;
                    } else if (0 === markType.toString().indexOf('icon-')) {
                        $('.js-cardId', $box).prop('checked', false);
                        $('.js-cardId-label', $box).each(function() {
                            if ($('.' + markType + ':visible', $(this)).length)
                                $('.js-cardId', $(this).parent()).prop('checked', true);
                        });
                    }

                    selfController.fetchReport();
                    $dropDown.hide();
                    this.toggleAllCardSelected();
                    return false;
                },
                toggleAllCardSelected: function(event) {
                    if (undefined !== event && $(event.currentTarget).hasClass('js-groupProvider')) {
                        this.toggleGroupProvider(event);
                    } else {
                        setTimeout(selfController.cardUsed.groupProviderCheckState, 50);
                    }
                    setTimeout(function() {
                        const cardsCount = $('input[name="cardId[]"]', '#cardUsed').length;
                        const selectedCards = $('input[name="cardId[]"]:checked', '#cardUsed');

                        $scope.allCardSelected = cardsCount === selectedCards.length;
                        if ($scope.allCardSelected) {
                            $.removeCookie('analyzerOfferCards2', { path: '/' });
                        } else {
                            let cardsId = $.map(selectedCards, (card) => parseInt($(card).val()));
                            $.cookie('analyzerOfferCards2', cardsId.join('.'), { expires: 90, path: '/' });
                        }
                    }, 500);
                },
                groupProviderCheckState: function() {
                    $('.analysis-cards__block').each(function() {
                        let isGroupChecked = true;
                        $('.js-cardId', $(this)).each(function() {
                            if (!$(this).prop('checked')) {
                                isGroupChecked = false;
                                return;
                            }
                        });
                        $('.js-groupProvider', $(this)).prop('checked', isGroupChecked);
                    });
                },
                toggleGroupProvider: function(event) {
                    const $block = $(event.target).closest('.analysis-cards__block');
                    const isChecked = $(event.currentTarget).prop('checked');
                    let $el = null;
                    $('input.js-cardId', $block).each(function() {
                        $el = $(this).prop('checked', isChecked);
                    });
                    $el.change();
                }
            };
            selfController.cardUsed.toggleAllCardSelected();

            $timeout(function() {
                if (!$scope.initialized) {
                    if (!selfController.hashParse())
                        $spentOwner.trigger('change');
                    $scope.initialized = true;
                }

                $timeout(() => {
                    const $cardUsed = $('#cardUsed');
                    cardExists = {
                        'personal': $('.icon-personal-card', $cardUsed).length > 0,
                        'business': $('.icon-business-card', $cardUsed).length > 0,
                        'have': $('.icon-have-card', $cardUsed).length > 0,
                    };
                }, 1000);

                /*ga('send', {
                    hitType       : 'event',
                    eventCategory : 'Content',
                    eventAction   : 'view.CardSpendAnalysis',
                    eventLabel    : transition.params('to').url
                });*/

                $('.last-update', '#ng-app').css('visibility', 'hidden');
            }, 1000);

        }]);

});
