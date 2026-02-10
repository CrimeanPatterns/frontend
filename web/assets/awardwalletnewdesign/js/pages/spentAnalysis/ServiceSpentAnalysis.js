define(['angular', 'jquery-boot', 'lib/customizer', 'routing'], function (angular, $, customizer) {
    angular = angular && angular.__esModule ? angular.default : angular;

    var service = angular.module('SpentAnalysisService', []);
    const numberFormat = new Intl.NumberFormat(customizer.locale, {
        style : 'decimal',
        useGrouping : true,
        minimumFractionDigits : 2,
        maximumFractionDigits : 2
    });

    function SpentAnalysis() {
        var owners = [];

        function init(data) {
            owners = data;
        }

        function getOwners() {
            return owners.ownersList;
        }

        function multiplierDiff(multiplier, potential) {
            var diff = parseFloat(potential) - parseFloat(multiplier);

            if (null === potential)
                return 'transp-like';

            else if (diff > 0)
                return 'pointer';

            // else if (diff > 3)
            //     return 'red pointer';
            // else if (diff > 2)
            //     return 'orangedark pointer';
            // else if (diff > 1)
            //     return 'orange pointer';
            // else if (diff > 0)
            //     return 'yellow pointer';

            return '';
        }

        function transactionRowCss(item, row, col) {
            var className = [];

            if (0 === col)
                className.push('date');

            if ('undefined' !== typeof item[item.length - 1].isEp) {
                if (5 === col)
                    className.push('points-cell');
                if ('undefined' != typeof item[col].multiplier)
                    className.push('balance-cell');
                if (col === item.length - 1 && 'undefined' !== typeof item[col].offerData) {
                    className.push('potential-cell');

                    if (('undefined' !== typeof item[col].unprocess && (true === item[col].unprocess || 1 == item[col].unprocess))
                        || ('undefined' !== typeof item[col].offerData.miles && (null === item[col].offerData.miles || 0 >= parseFloat(item[col].offerData.miles)))
                        || 0 === parseFloat(item[col].offerData.amount.replace(/[^\d\.]*/g, ''))
                    )
                        return className.join(' ');

                    if ('undefined' != typeof item[col].offerData.potential && 'undefined' != typeof item[col].offerData.multiplier) {
                        if (0 >= parseFloat(item[col - 1].value))
                            return className.join(' ');

                        var classResult = multiplierDiff(item[col].offerData.multiplier, item[col].offerData.potential);
                        if ('' !== classResult)
                            className.push(classResult);
                    }
                }
            }

            return className.join(' ');
        }

        function getOfferData(data) {
            if (('undefined' !== typeof data.unprocess && (true === data.unprocess || 1 == data.unprocess))
                || ('undefined' !== typeof data.offerData.potential && null === data.offerData.potential)
                || ('undefined' !== typeof data.offerData.miles && (null === data.offerData.miles || 0 >= parseFloat(data.offerData.miles)))
                || (0 === parseFloat(data.offerData.amount.replace(/[^\d\.]*/g, '')))
                || (Object.prototype.hasOwnProperty.call(data, 'merchantId') && 0 == data.merchantId)
            )
                return null;

            var multiplier = data.offerData.multiplier,
                potential = data.offerData.potential;

            var infoParams = {
                'strong_on': '<strong>',
                'strong_off': '</strong>',
                'up-counter-on': '<span class="up-counter">',
                'up-counter-off': '</span>',
                'merchant': data.offerData.description,
                'multiplier': (0 !== parseInt(multiplier) ? multiplier.toString().replace('x', '') + 'x' : ''),
                'amount': data.offerData.amount,
                'miles': data.offerData.miles,
                'description': data.offerData.description,
                'cardname': data.offerData.cardName,
                'category': data.offerData.category
            };
            var offerParams = {
                'up-counter-on': '<span class="up-counter">',
                'up-counter-off': '</span>',
                'blue-text-on': '<span class="blue-text">',
                'blue-text-off': '</span>',
                'potential': (0 !== parseInt(potential) ? potential.toString().replace('x', '') + 'x' : ''),
                'category': data.offerData.category
            };
            var offerParamsEveryPurchase = {
                'up-counter-on': '<span class="up-counter">',
                'up-counter-off': '</span>',
                'potential': (0 !== parseInt(potential) ? potential.toString().replace('x', '') + 'x' : '')
            };
            var infoParamsEveryPurchase = $.extend({}, infoParams);//Object.assign({}, infoParams);

            var infoCategory, infoEveryPurchase;
            if (Object.prototype.hasOwnProperty.call(data.offerData, 'cardName')) {
                infoCategory = Translator.trans(
                    /** @Desc("You spent %strong_on%%amount%%strong_off% at %strong_on%%description%%strong_off% and earned %strong_on%%miles%%strong_off% points %up-counter-on%%multiplier%%up-counter-off% using your %cardname% card. Based on the data we've collected and what other AwardWallet members have earned from %strong_on%%merchant%%strong_off%, which appears to be coded as %strong_on%%category%%strong_off%, you may be able to increase your rewards earning with this merchant by instead using a credit card that receives additional bonus points.") */
                    'credit-card.offer.popup.merchant.info', infoParams);
                infoEveryPurchase = Translator.trans(
                    /** @Desc("You spent %strong_on%%amount%%strong_off% at %strong_on%%description%%strong_off% and earned %strong_on%%miles%%strong_off% points %up-counter-on%%multiplier%%up-counter-off% using your %cardname%. Based on the data we've collected and what other AwardWallet members have earned with %strong_on%%merchant%%strong_off%, you may be able to increase your rewards earning by using a credit card that earns more than %up-counter-on%%multiplier%%up-counter-off% on all purchases.") */
                    'credit-card.offer.popup.merchant.info.everypurchase', infoParamsEveryPurchase);
            } else {
                infoCategory = Translator.trans(
                    /** @Desc("You spent %strong_on%%amount%%strong_off% at %strong_on%%description%%strong_off% and earned %strong_on%%miles%%strong_off% points %up-counter-on%%multiplier%%up-counter-off%. Based on the data we've collected and what other AwardWallet members have earned from %strong_on%%merchant%%strong_off%, which appears to be coded as %strong_on%%category%%strong_off%, you may be able to increase your rewards earning with this merchant by instead using a credit card that receives additional bonus points.") */
                    'credit-card.offer.popup.merchant.info-analysis', infoParams);
                infoEveryPurchase = Translator.trans(
                    /** @Desc("You spent %strong_on%%amount%%strong_off% at %strong_on%%description%%strong_off% and earned %strong_on%%miles%%strong_off% points %up-counter-on%%multiplier%%up-counter-off%. Based on the data we've collected and what other AwardWallet members have earned with %strong_on%%merchant%%strong_off%, you may be able to increase your rewards earning by using a credit card that earns more than %up-counter-on%%multiplier%%up-counter-off% on all purchases.") */
                    'credit-card.offer.popup.merchant.info-analysis.everypurchase', infoParamsEveryPurchase);
            }
            var offerCategory = Translator.trans(
                /** @Desc("How to Earn up to %up-counter-on%%potential%%up-counter-off% points per dollar at %blue-text-on%%category%%blue-text-off%*") */
                'credit-card.offer.popup.merchant.offer', offerParams
            );
            var offerEveryPurchase = Translator.trans(
                /** @Desc("How to Earn up to %up-counter-on%%potential%%up-counter-off% (or more) points per dollar on all purchases*") */
                'credit-card.offer.popup.merchant.offer.everypurchase', offerParamsEveryPurchase
            );

            return {
                title: getOfferTitle(data.offerData.merchantName),
                info: data.offerData.isEveryPurchaseCategory ? infoEveryPurchase : infoCategory,
                offer: data.offerData.isEveryPurchaseCategory ? offerEveryPurchase : offerCategory,
                blogUrl: data.offerData.blogUrl
            };
        }

        function getOfferTitle(merchant) {
            return Translator.trans(
                /** @Desc("Earning Potential at %red_on%%merchant%%red_off%") */
                'credit-card.offer.popup.merchant.title', {
                    'red_on': '<span class="mark-hightlight-title">',
                    'red_off': '</span>',
                    'merchant': merchant
                }
            );
        }

        /**
         * @class SpentAnalysisProvider
         */
        var self = {
            init: function (data) {
                return init(data);
            },
            state: {
                offerLoading: false
            },
            offerDialogContent: "",
            isEnable: function () {
                return ('undefined' !== typeof owners.ownersList && !angular.equals({}, owners.ownersList)) && getOwners().length > 0;
            },
            setFromLoader: function (data) {
                owners = data;
            },
            getOwners: function () {
                return getOwners();
            },
            getDateRanges: function () {
                return Object.prototype.hasOwnProperty.call(owners, 'dateRanges') ? owners.dateRanges : [];
            },
            getOfferData: function (data) {
                return getOfferData(data);
            },
            getOfferTitle: function (merchant) {
                return getOfferTitle(merchant);
            },
            getOfferCardFilter: function () {
                return Object.prototype.hasOwnProperty.call(owners, 'offerCardsFilter') ? owners.offerCardsFilter : [];
            },
            getFilteredAccounts: function (accounts, filter) {
                var isCondition, result = [];
                for (var i in accounts) {
                    isCondition = true;
                    for (var key in filter) {
                        if (!Object.prototype.hasOwnProperty.call(accounts[i], key) || accounts[i][key] != filter[key]) {
                            isCondition = false;
                            break;
                        }
                    }
                    if (isCondition)
                        result.push(accounts[i]);
                }
                return result;
            },
            getMultiplierCss: function (item) {
                var m = Object.prototype.hasOwnProperty.call(item, 'offerData') && Object.prototype.hasOwnProperty.call(item.offerData, 'multiplier') ? item.offerData.multiplier : (Object.prototype.hasOwnProperty.call(item, 'multiplier') ? item.multiplier : 0),
                    p = Object.prototype.hasOwnProperty.call(item, 'offerData') && Object.prototype.hasOwnProperty.call(item.offerData, 'potential') ? item.offerData.potential : (Object.prototype.hasOwnProperty.call(item, 'potential') ? item.potential : 0);
                return multiplierDiff(m, p);
            },
            transactionRowCss: function (item, row, col) {
                return transactionRowCss(item, row, col);
            }
        };

        return self;
    }

    service.directive('offerCardsFilter', function () {
        return {
            restrict: 'E',
            templateUrl: '/offerCardsFilter',
            scope: {
                initData: "=",
                checkModel: "=",
                stateOwners: '=',
                checkAction: "&",
                //allCardSelected: '=',
            },
            link: function (scope, element) {
                scope.toggleCardsChecked = function($event) {
                    $event.preventDefault();
                    $event.stopPropagation();
                    groupProviderCheckState();
                    $($event.target).closest('.analysis-cards').toggleClass('active');
                };

                var markPersonal = function () {
                    angular.forEach(scope.initData, function (value) {
                        angular.forEach(value.cardsList, function (value) {
                            scope.checkModel[value.creditCardId] = !value.isBusiness;
                        });
                    });
                };
                var markBusiness = function () {
                    angular.forEach(scope.initData, function (value) {
                        angular.forEach(value.cardsList, function (value) {
                            scope.checkModel[value.creditCardId] = value.isBusiness;
                        });
                    });
                };
                var markHave = function () {
                    const ownerId = $('#owner').val();
                    let haveCards = [];

                    for (let i in scope.stateOwners) {
                      if (scope.stateOwners[i].id === ownerId) {
                        haveCards = scope.stateOwners[i].haveCardsId;
                        break;
                      }
                    }

                    angular.forEach(scope.initData, function (value) {
                        angular.forEach(value.cardsList, function (value) {
                            scope.checkModel[value.creditCardId] = value.existedCard && haveCards.includes(parseInt(value.creditCardId));
                        });
                    });
                };
                var markAll = function () {
                    angular.forEach(scope.checkModel, function (value, key) {
                        scope.checkModel[key] = true;
                    });
                };

                scope.isExistsCardType = (type) => $('.icon-' + type + '-card:first', '#cardUsed').length ? true : false;

                scope.markCard = function ($event, markType) {
                    if (markType !== undefined) {
                        switch (markType) {
                            case 'personal-card':
                                markPersonal();
                                break;
                            case 'business-card':
                                markBusiness();
                                break;
                            case 'have-card':
                                markHave();
                                break;
                            case 'all':
                                markAll();
                                break;
                        }
                        scope.checkAction();
                        toggleAllCardSelected();
                    }

                    $event.preventDefault();
                    $event.stopPropagation();

                    if (undefined === markType || !$($event.target).parent().hasClass('analysis-cards-used-all')) {
                        var dropDown = element.find('.dropdown-submenu');
                        dropDown.toggle();

                        if (markType === undefined && dropDown.is(':visible')) {
                            $(document).one('click', function() {
                                scope.markCard($event, markType);
                            });
                        }
                    }
                };

                const toggleAllCardSelected = function() {
                    setTimeout(groupProviderCheckState, 50);
                    setTimeout(function() {
                        const cardsCount = $('input[name="cardId[]"]', '#cardUsed').length;
                        const selectedCards = $('input[name="cardId[]"]:checked', '#cardUsed');

                        scope.allCardSelected = cardsCount === selectedCards.length;
                        if (scope.allCardSelected) {
                            $.removeCookie('analyzerOfferCards2', { path: '/' });
                        } else {
                            let cardsId = $.map(selectedCards, (card) => parseInt($(card).val()));
                            $.cookie('analyzerOfferCards2', cardsId.join('.'), { expires: 90, path: '/' });
                        }
                    }, 500);
                };

                scope.$watchCollection('checkModel', toggleAllCardSelected);
                toggleAllCardSelected();

                scope.toggleGroupProvider = function(event) {
                    const $block = $(event.target).closest('.analysis-cards__block');
                    const isChecked = $(event.currentTarget).prop('checked');
                    $('input.js-cardId', $block).each(function() {
                        scope.checkModel[$(this).val()] = isChecked;
                    });
                    scope.checkAction();
                };

                function groupProviderCheckState() {
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
                }
            }

        };

    });

    service.directive('mileValueBox', ['$http', function ($http) {
        return {
            restrict: 'E',
            templateUrl: '/mileValueBox',
            scope: {
                initData: "=",
                // checkModel  : "=",
                changeAction: "&"
            },
            link: function (scope, element) {
                scope.providers = [...scope.initData];

                scope.isEdit = [];
                scope.isSaving = false;

                scope.isEditable = (providerId) => {
                    let item = scope.isEdit.find(({id}) => id === providerId);
                    if (!item) {
                        item = {id: providerId, isEdit: false};
                        scope.isEdit = [...scope.isEdit, item];
                    }

                    return item.isEdit;
                }

                scope.handleEdit = (event, providerId) => {
                    event.preventDefault();

                    const isEditable = scope.isEditable(providerId);
                    let item = scope.isEdit.find(({id}) => id === providerId)
                    item = {...item, isEdit: !isEditable}

                    scope.isEdit = [...scope.isEdit.filter(({id}) => id !== providerId), item];
                }

                scope.handleSave = (event, providerId) => {
                    event.preventDefault();
                    scope.isSaving = true;

                    const providerItem = scope.providers.find(({id}) => id === providerId);
                    providerItem.userValue = providerItem.showValue;
                    $http
                        .post(
                            Routing.generate('aw_points_miles_userset', {providerId}),
                            {value: parseFloat(providerItem.showValue) > 0 ? providerItem.showValue : null}
                        )
                        .then(({data}) => {
                            scope.providers = [...data.bankPointsShort]
                            if (data.success === true) {
                                scope.changeAction();
                            }
                        })
                        .catch()
                        .finally(() => {
                            scope.isSaving = false;
                            scope.handleEdit(event, providerId);
                        });
                };

                scope.showValue = (value, currency) => {
                    return numberFormat.format(value) + currency;
                };
            }

        };

    }]);

    service.directive('bestCards', ['$http', function ($http) {
        return {
            restrict: 'E',
            templateUrl: '/bestCards',
            scope: {
                bestCards: '=',
                loading: '=',
                isFixed: '=',
                isError: '=',
            },
            link: function (scope, element) {
            }
        };
    }]);

    service.provider('SpentAnalysis',
        function () {
            return {
                $get: [
                    function () {
                        return new SpentAnalysis();
                    }
                ]
            };
        });
});
