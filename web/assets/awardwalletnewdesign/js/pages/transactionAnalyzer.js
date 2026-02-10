define([
  'angular-boot',
  'jquery-boot',
  'lib/customizer',
  'lib/dialog',
  'lib/design',
  'filters/unsafe',
  'directives/customizer',
  'directives/dialog',
  'ng-infinite-scroll',
  'pages/spentAnalysis/ServiceSpentAnalysis'
], function (angular, $, customizer, libDialog) {
  angular = angular && angular.__esModule ? angular.default : angular;

  var Data;

  var app = angular.module("transactionAnalyzerApp", [
    'appConfig', 'unsafe-mod', 'infinite-scroll', 'unsafe-mod',
    'customizer-directive', 'dialog-directive', 'SpentAnalysisService'
  ]);

  app.config([
    '$injector',
    function ($injector) {
      if ($injector.has('Data')) {
        Data = $injector.get('Data');
      }
    }
  ]);

  let cachePopupData = {};
  var main;
  app.controller('mainCtrl', ['$scope', '$http', '$timeout', 'dialogService', 'SpentAnalysis', function ($scope, $http, $timeout, dialogService, SpentAnalysis) {

    this.datesRangeList = [
      {value: 1, title: 'This Month'},
      {value: 3, title: 'Last Month'},
      {value: 2, title: 'This Quarter'},
      {value: 4, title: 'Last Quarter'},
      {value: 5, title: 'This Year'},
      {value: 6, title: 'Last Year'},
      {value: 0, title: 'All transactions'},
    ]

    this.state = {
      isLoading: true,
      isLoaded: false,
      isLoadingCategories: true,
      transactions: [],
      cardsFilter: [],
      totals: {},
      categories: {},
      owners: [],
      providers: [],
      userCards: [],
      multipliers: [1,2,3,4,5],
      showFilters: {
        dateRange: false,
        creditCard: false,
        category: false,
        amount: false,
        multiplier: false,
        potential: false
      },
      showFiltersOverlay: false,
      filters: {
        description: "",
        datesRange: 5,
        startDate: null,
        endDate: null,
        subAccGroups: {
          all: true
        },
        subAccIds: {},
        multipliers: {
          1: true,
          2: true,
          3: true,
          4: true,
          5: true,
        },
        multipliersAll: true,
        potential: {
          1: true,
          2: true,
          3: true,
          4: true,
        },
        potentialAll: true,
        amountLess: null,
        amountGreater: null,
        amountExactly: null,
        amountAny: true,
        categories: {},
        categoriesAll: true
      },
      nextPageToken: null,
      isLastPageLoaded: false,
      reloading: false,
      selectedUser: null,
      fixedTotals: false,
      isBusiness: false,
      userAgentId: false,
      requestQueue: {
          data: null,
          totals: null,
          totalsFirst: null,
      },
    };

    main = this;
    window.TransactionAnalyzer = this;

    if ((typeof (Data) == 'object') && (Data !== null)) {
      console.log(Data);
      this.state = {...this.state, ...Data, isLoading: false, isLoaded: true};
      this.state.filters.datesRange = Data.defaultRange;
      this.state.isBusiness = 'number' === typeof Data.userAgentId && Data.userAgentId > 0;

      const isOfferCardsState = 0 === this.state.offerCardsState.length;
      angular.forEach(this.state.offerCardsFilter, function (value) {
        angular.forEach(value.cardsList, function (value) {
          main.state.cardsFilter[value.creditCardId] =
              isOfferCardsState || -1 !== main.state.offerCardsState.indexOf(value.creditCardId);
        });
      });
    }

    this.buildCardsFilterToRequest = function () {
      let checked = $.map($('input[name="cardId[]"]:checked', '#cardUsed'), (card) => parseInt($(card).val()));
      var result = [];
      angular.forEach(this.state.cardsFilter, function (value, key) {
        if (-1 !== checked.indexOf(key)) {
          result.push(key);
        }
      });

      return result;
    };

    this.handleDescriptionChange = (value) => {
        main.hideFixedPopupBestCards();
        this.state.filters.description = value;
        this.reloadRows();
    }

    this.handleFilterChange = (e, type, value) => {
      if (typeof e.preventDefault === 'function') {
        e.preventDefault();
      }
      if (type === 'datesRange') {
        this.state.filters.startDate = null;
        this.state.filters.endDate = null;
      }

      this.state.filters[type] = value;
    }

    this.handleCategoryFilterSelectOne = (categoryName) => {
        main.hideFixedPopupBestCards();
      this.state.filters.categoriesAll = false;
      Object.keys(this.state.filters.categories).forEach(category => this.state.filters.categories[category] = false);
      this.state.filters.categories[categoryName] = true;
      this.reloadRows();
    }

    this.handleFilterCategories = (all = false) => {
      if (all) {
        this.state.filters.categories = {};
        this.state.categories.forEach(category => {
          this.state.filters.categories[category] = this.state.filters.categoriesAll;
        })

        return;
      }

      let value = true;
      this.state.categories.forEach(category => {
        value = value && this.state.filters.categories[category];
      })
      this.state.filters.categoriesAll = value;
    }

    this.handleFilterMultiplier = (all = false) => {
      if (all) {
        this.state.filters.multipliers = {};
        this.state.multipliers.forEach(multiplier => {
          this.state.filters.multipliers[multiplier] = this.state.filters.multipliersAll;
        })

        return;
      }

      let value = true;
      this.state.multipliers.forEach(multiplier => {
        value = value && this.state.filters.multipliers[multiplier];
      })
      this.state.filters.multipliersAll = value;
    }

    this.handleFilterPotential = (all = false) => {
      if (all) {
        Object.keys(this.state.filters.potential).forEach(multiplier => {
          this.state.filters.potential[multiplier] = this.state.filters.potentialAll;
        })

        return;
      }

      let value = true;
      Object.keys(this.state.filters.potential).forEach(multiplier => {
        value = value && this.state.filters.potential[multiplier];
      })
      this.state.filters.potentialAll = value;
    }

    this.handleFilterAmountTitle = () => {
      if (this.state.filters.amountAny === true)
        return "Any";

      if (this.state.filters.amountGreater)
        return `Greater than $${this.state.filters.amountGreater}`;

      if (this.state.filters.amountLess)
        return `Less than $${this.state.filters.amountLess}`;

      if (this.state.filters.amountExactly)
        return `Exact Amount $${this.state.filters.amountExactly}`;
    }

    this.handleFilterAmount = (type) => {
      if (type === 'any') {
        this.state.filters.amountGreater = null;
        this.state.filters.amountExactly = null;
        this.state.filters.amountLess = null;
        this.state.filters.amountAny = true;
        return;
      }

      this.state.filters.amountAny = false;
      let value = 0;
      if (type === 'less') {
        this.state.filters.amountGreater = null;
        this.state.filters.amountExactly = null;
        value = this.state.filters.amountLess;
      }
      if (type === 'greater') {
        this.state.filters.amountLess = null;
        this.state.filters.amountExactly = null;
        value = this.state.filters.amountGreater;
      }
      if (type === 'exactly') {
        this.state.filters.amountLess = null;
        this.state.filters.amountGreater = null;
        value = this.state.filters.amountExactly;
      }

      if (!value)
        this.state.filters.amountAny = true;

    }

    this.getDatesRangeTitle = () => {
      const result = this.datesRangeList.find(({value}) => value === parseInt(this.state.filters.datesRange));
      return result?.title;
    }

    this.isDatesSelected = () => {
      return !!this.state.filters.startDate && !!main.state.filters.endDate;
    }

    this.buildAvailableCards = (initValue = true, subAccId = null) => {
      const owner = this.state.owners.find(({id}) => id === this.state.selectedUser);
      const cards = owner?.cards;
      const valueToSet = subAccId ? !initValue : initValue;

      this.state.userCards = this.state.providers
        .map(({id, displayName}) => {
          return {
            providerId: id,
            displayName,
            cards: cards.filter(card => parseInt(card.providerId) === parseInt(id))
          }
        })
        .filter(provider => provider.cards.length > 0);

      const subAccGroups = {all: valueToSet};
      this.state.providers.forEach(({id}) => {
        subAccGroups[id] = valueToSet;
      });
      this.state.filters.subAccGroups = subAccGroups;

      this.state.filters.subAccIds = {};
      cards.forEach(({subAccountId}) => {
        this.state.filters.subAccIds[subAccountId] = valueToSet;
      })

      if (subAccId)
        this.state.filters.subAccIds[subAccId] = initValue;

      console.log('subaccs after build', this.state.filters.subAccIds);
    }

    this.handleCreditCardFilterChange = () => {
      let allProvidersChecked = true;
      this.state.userCards.forEach(provider => {
        let minus = false,
          allChecked = true,
          allUnchecked = true;

        provider.cards.forEach(card => {
          allChecked = allChecked && this.state.filters.subAccIds[card.subAccountId];
          allUnchecked = allUnchecked && !this.state.filters.subAccIds[card.subAccountId];
        })
        this.state.filters.subAccGroups[provider.providerId] = allChecked;
        allProvidersChecked = allProvidersChecked && allChecked;
      })
      this.state.filters.subAccGroups['all'] = allProvidersChecked;
      // console.log('model value', this.state.filters.subAccIds[id]);
      // console.log('user cards', this.state.userCards);
    }

    this.handleCreditCardGroup = (providerId) => {
      const value = this.state.filters.subAccGroups[providerId]
      if (providerId === 'all') {
        this.buildAvailableCards(this.state.filters.subAccGroups[providerId]);
        return;
      }

      const provider = this.state.userCards.find(provider => provider.providerId === providerId);
      provider.cards.forEach(card => {
        this.state.filters.subAccIds[card.subAccountId] = value;
      })

      this.handleCreditCardFilterChange();
    }

    this.handleCreditCardFilterSelectOne = (subAccountId) => {
        main.hideFixedPopupBestCards();
        this.buildAvailableCards(false);
      this.state.filters.subAccIds[subAccountId] = true;
      this.handleCreditCardFilterChange();
      this.reloadRows();
    }

    this.creditCardFilterTitle = () => {
      if (this.state.filters.subAccGroups['all'])
        return 'All';

      let selected = 0;
      let selectedId = null
      Object.keys(this.state.filters.subAccIds).forEach(subAccId => {
        const value = this.state.filters.subAccIds[subAccId]
        if (value) {
          selected = selected + 1;
          selectedId = subAccId;
        }
      });

      if (selected > 1)
        return "Multiple Options Selected"

      let selectedName = "-";
      this.state.userCards.forEach(provider => {
        provider.cards.forEach(card => {
          if (card.subAccountId == selectedId)
            selectedName = card.creditCardName;
        })
      })

      return selectedName
    }

    this.categoryFilterTitle = () => {
      let selected = 0;
      let selectedCategory = "-";
      Object.keys(this.state.filters.categories).forEach(category => {
        const value = this.state.filters.categories[category]
        if (value) {
          selected = selected + 1;
          selectedCategory = category;
        }
      });

      if (this.state.filters.categoriesAll && selected > 1)
        return 'All';

      if (selected > 1)
        return "Multiple Options Selected"

      return selectedCategory
    }

    this.filterMultiplierTitle = () => {
      let selected = 0;
      let selectedMultiplier = "-";
      Object.keys(this.state.filters.multipliers).forEach(multiplier => {
        const value = this.state.filters.multipliers[multiplier]
        if (value) {
          selected = selected + 1;
          selectedMultiplier = multiplier+'x';
        }
      });

      if (this.state.filters.multipliersAll && selected > 1)
        return 'All';

      if (selected > 1)
        return "Multiple Options Selected"

      return selectedMultiplier
    }

    this.filterPotentialTitle = () => {
      let selected = 0;
      let selectedMultiplier = "-";
      Object.keys(this.state.filters.potential).forEach(multiplier => {
        const value = this.state.filters.potential[multiplier]
        if (value) {
          selected = selected + 1;
          selectedMultiplier = multiplier+'x difference';
        }
      });

      if (this.state.filters.potentialAll && selected > 1)
        return 'All';

      if (selected > 1)
        return "Multiple Options Selected"

      return selectedMultiplier
    }

    this.toggleFilter = (filter) => {
      const currentValue = this.state.showFilters[filter];
      this.state.showFiltersOverlay = !currentValue;

      if (!currentValue) {
        this.state.showFilters = {
          dateRange: false,
          creditCard: false,
          category: false,
          amount: false,
          multiplier: false,
          potential: false
        };
      }

      this.state.showFilters[filter] = !currentValue;
      if (filter === 'all')
        this.state.showFiltersOverlay = false;
    }

    this.buildRequest = (withNextPageToken = false) => {
      const request = {
        agent: this.state.selectedUser,
        descriptionFilter: this.state.filters.description,
        offerFilterIds: Object.keys(this.state.cardsFilter).filter(cardId => true === this.state.cardsFilter[cardId]),
        offerCardsCount: Object.keys(this.state.cardsFilter).length,
        subAccIds: Object.keys(this.state.filters.subAccIds).filter(subAccId => this.state.filters.subAccIds[subAccId] === true),
        nextPage: withNextPageToken ? this.state.nextPageToken : null,
        withPotential: true,
        requestTimeStamp: Date.now(),
      };

      if (this.isDatesSelected()) {
        request.startDate = this.state.filters.startDate;
        request.endDate = this.state.filters.endDate;
      } else {
        request.datesRange = this.state.filters.datesRange;
      }

      if (!this.state.filters.categoriesAll) {
        request.categories = Object.keys(this.state.filters.categories).filter(category => this.state.filters.categories[category] === true);
      }
      if (!this.state.filters.multipliersAll) {
        request.multipliers = Object.keys(this.state.filters.multipliers).filter(multiplier => this.state.filters.multipliers[multiplier] === true);
      }
      if (!this.state.filters.potentialAll) {
        request.potential = Object.keys(this.state.filters.potential).filter(multiplier => this.state.filters.potential[multiplier] === true);
      }

      if (!this.state.filters.amountAny) {
        request.amountLess = this.state.filters.amountLess
        request.amountGreater = this.state.filters.amountGreater
        request.amountExactly = this.state.filters.amountExactly
      }

      if (this.state.isBusiness) {
          request.agentId = this.state.userAgentId;
      }

      return request;
    }

      this.changeOfferCards = function() {
          $timeout(() => {
              const request = this.buildRequest();

              if (0 === request.offerFilterIds.length) {
                  libDialog.fastCreate('Error', 'For this report to work, at least one card must be selected', true, true, [{
                      'text': Translator.trans('button.ok'),
                      'click': function() {
                          $(this).dialog("close");
                      },
                      'class': 'btn-silver'
                  }], 500, 300, 'error');
                  return;
              }

              this.reloadRows();
          }, 50);
      };

    this.reloadRows = function () {
      if (this.state.reloading) {
        return;
      }

      this.state.reloading = true;
      var request = this.buildRequest();

      this.state.requestQueue.data = request.requestTimeStamp;
      $http.get(
        Routing.generate(this.state.isBusiness ? 'aw_transactions_business_data' : 'aw_transactions_data', request)
      ).then(
        res => {
          if (res.data.responseTimeStamp !== this.state.requestQueue.data) {
              return;
          }
          const data = res.data;
          this.state = {...this.state, ...data};
          $timeout(() => {this.totalsAlign; main.fillCashEq(true);}, 50);
        }
      ).finally(
        () => {
          main.state.reloading = false;
        }
      );

      this.loadTotals();
    };

    this.loadTotals = (isFirstLoad = false) => {
      this.state.totals.isLoading = true;
      var request = this.buildRequest();

      this.state.requestQueue.totals = request.requestTimeStamp;
      $http
        .get(Routing.generate(this.state.isBusiness ? 'aw_transactions_business_totals' : 'aw_transactions_totals', request))
        .then(res => {
          if (res.data.responseTimeStamp !== this.state.requestQueue.totals) {
              return;
          }
          const data = res.data;
          this.state.totals = {isLoading: false, ...data};
        })
        .finally(() => {
          this.state.totals.isLoading = false;
        })

      if (isFirstLoad) {
        this.state.requestQueue.totalsFirst = request.requestTimeStamp;
        this.state.isLoadingCategories = true;
        $http
          .get(
            Routing.generate(this.state.isBusiness ? 'aw_transactions_business_totals' : 'aw_transactions_totals', {...request, datesRange: 0, withPotential: false})
          )
          .then(res => {
            if (res.data.responseTimeStamp !== this.state.requestQueue.totalsFirst) {
                return;
            }
            this.state.categories = res.data.categories;
            this.state.filters.categories = {};
            this.state.categories.forEach(category => {
              this.state.filters.categories[category] = true;
            })
            this.state.filters.categoriesAll = true;
          })
          .finally(() => {
            this.state.isLoadingCategories = false;
          })

      }
    }

    this.totalsAlign = () => {
      if ($('.page .item').innerHeight() > document.body.offsetHeight) {
        if (window.innerHeight + window.pageYOffset + 45 >= document.body.scrollHeight) {
          if (this.state.fixedTotals === false) this.state.fixedTotals = true;
        } else {
          if (this.state.fixedTotals === true) this.state.fixedTotals = false;
        }
      } else {
        if (this.state.fixedTotals === false) this.state.fixedTotals = true;
      }
    }

    this.loadMore = () => {
      this.totalsAlign();

      if (this.state.isLoading) {
        return;
      }

      if (this.state.isLastPageLoaded) {
        return;
      }

      this.state.isLoading = true;
      var request = this.buildRequest(true);

      $http.get(
        Routing.generate(this.state.isBusiness ? 'aw_transactions_business_data' : 'aw_transactions_data', request)
      ).then(
        res => {
          const data = res.data;
          const transactions = [...this.state.transactions, ...data.transactions]
          this.state = {...this.state, ...data, transactions};
        }
      ).finally(
        () => main.state.isLoading = false
      );
    }

    this.showOfferPopup = function (uuid, merchant) {
      window.console.log('Loading offer dialog...');

      var dialog = dialogService.get('credit-card-offer-popup');
      dialog.element.parent().find('.ui-dialog-title').html(SpentAnalysis.getOfferTitle(merchant));

      dialog.setOption('buttons', [
        {
          text: 'OK',
          click: function () {
            dialog.close();
          },
          'class': 'btn-blue'
        }
      ]);

      main.state.offerLoading = true;
      $http.post(
        Routing.generate('aw_spent_analysis_transaction_offer'),
        $.param({
          source: "transaction-history&mid=web",
          uuid: uuid,
          offerFilterIds: Object.keys(main.state.cardsFilter).filter(cardId => true === main.state.cardsFilter[cardId]),
        }),
        {headers: {'Content-Type': 'application/x-www-form-urlencoded'}}
      ).then(
        res => {
          main.state.offerLoading = false;
          main.offerDialogContent = res.data;
          setTimeout(() => {
            $(dialog.element).closest('.ui-dialog').css('width', '96%');
            $(window).trigger('resize.dialog');
            customizer.initTooltips($(dialog.element));
          }, 100);
        }
      ).catch(
        () => {
          main.state.offerLoading = false;
          dialog.close();
        }
      );

      dialog.open();
      $(dialog.element).closest('.ui-dialog').css({'position':'fixed', 'left': '2%', 'top': '80px', 'width': '96%'});
    };

    this.exportCsv = () => {
      document.location.href = Routing.generate(this.state.isBusiness ? 'aw_transactions_business_export_csv' : 'aw_transactions_export_csv', this.buildRequest())
    }

      this.getCacheKey = function() {
          let state = main.buildRequest();
          delete state.nextPage;

          let jsonState = JSON.stringify(state);
          let hash = 0;
          for (let i = 0; i < jsonState.length; i++) {
              const char = jsonState.charCodeAt(i);
              hash = ((hash << 5) - hash) + char;
              hash = hash & hash;
          }

          return hash;
      }

      this.fetchCardsOffer = function($eqCol) {
          main.popup.isError = false;
          const uuid = $eqCol.data('uuid');
          if (undefined === uuid || null === uuid) {
            return;
          }

          const cacheKey = 'bc_' + uuid;
          main.popup.cacheKey = cacheKey;

          if (undefined !== cachePopupData[cacheKey]) {
              main.popup.loading = false;
              main.popup.$layer.removeClass('loading');
              $timeout(function() {
                  $scope.$apply(function() {
                      main.popup.bestCards = cachePopupData[cacheKey].data.bestCards;
                      setTimeout(() => main.showPopupBestCards($eqCol), 100);
                  });
              });

              return;
          }

          this.showLoading($eqCol);
          if (true === $eqCol.data('loading')) {
              return;
          }

          $eqCol.data('loading', true);
          $http.post(
              Routing.generate('aw_spent_analysis_transaction_cards_offer'),
              $.param({
                  source: 'transaction-history&mid=web',
                  uuid: uuid,
                  offerFilterIds: Object.keys(main.state.cardsFilter).filter(cardId => true === main.state.cardsFilter[cardId]),
              }),
              {headers: {'Content-Type': 'application/x-www-form-urlencoded'}}
          ).then(
              res => {
                  cachePopupData[cacheKey] = {
                      data: res.data,
                      eqCol: $eqCol,
                  };
                  $eqCol.data('loading', false);
                  if (main.popup.cacheKey === cacheKey) {
                      main.popup.bestCards = res.data.bestCards;
                      main.showPopupBestCards($eqCol);
                      setTimeout(() => main.showPopupBestCards($eqCol), 100);
                  }
              },
              err => {
                  main.popup.bestCards = [];
                  main.popup.isError = true;
                  main.showPopupBestCards($eqCol);
                  main.popup.loading = false;
              }
          ).catch(() => {
              main.popup.loading = false;
          });
      };

      this.showLoading = function($eqCol) {
          const pos = $eqCol.offset();

          $timeout(function() {
              $scope.$apply(function() {
                  main.popup.bestCards = [];
                  main.popup.loading = true;
              });
          });

          $timeout(function() {
              main.popup.$layer
                  .addClass('loading')
                  .css({
                      'left': pos.left - main.popup.$layer.width() + 45,
                      'top': pos.top - main.popup.$layer.height() - 12 - 53,
                  })
                  .addClass('bestcards-popup--showed');
          }, 10);
      };

      this.showPopupBestCards = function($eqCol) {
          const pos = $eqCol.offset();
          const $row = $eqCol.parent();
          const current = {
              miles: $row.find('.transactions-points-miles').text(),
              multiplier: $row.find('.transactions-points-multiplier').text(),
              pointName: $row.find('.transactions-point-name').text(),
              cashEq: $row.find('.transactions-points-value').text(),
              eqType: null,
              fillCircle: $row.find('svg circle').get(0).style.strokeDashoffset,
          };
          const eqTypes = ['low', 'small', 'medium', 'high'];
          for (let i in eqTypes) {
              $row.hasClass('transactions-diff-eq--' + eqTypes[i]) ? current.eqType = eqTypes[i] : null;
          }

          const $head = main.popup.$layer.find('.bestcards-head');
          $head.find('svg circle').get(0).style.strokeDashoffset = current.fillCircle;
          $head
              .prop('className', 'bestcards-head bestcards-row bestcards-diff-eq--' + current.eqType)
              .find('.bestcards-miles span').text(current.miles).end()
              .find('.bestcards-multiplier span').text(current.multiplier).end()
              .find('.bestcards-point-name').text(current.pointName).end()
              .find('.bestcards-cash-box strong').text(current.cashEq).end();

          $timeout(function() {
              main.popup.$layer
                  .removeClass('loading')
                  .css({
                      'left': pos.left - main.popup.$layer.width() + 45,
                      'top': pos.top - main.popup.$layer.height() - 12 - 53,
                  })
                  .addClass('bestcards-popup--showed');

              main.popup.loading = false;
          }, 10);

      };

      this.hidePopupBestCards = function(timeout) {
          clearTimeout(main.popup.$layer.data('timeout'));

          main.popup.$layer.data('timeout', setTimeout(() => {
              if (main.popup.$layer.hasClass('loading')) {
                  //return;
              }

              main.popup.$layer.removeClass('bestcards-popup--showed');
              clearTimeout(main.popup.$layer.data('timeout'));
          }, timeout || 1500));
      };

      this.showFixedPopupBestCards = function(e) {
          clearTimeout(main.popup.chartHover);
          clearTimeout(main.popup.$layer.data('timeout'));

          let $row = $(this).closest('.transactions-row');
          if ($row.hasClass('transactions-status-casheq-empty')) {
              return;
          }
          $row.addClass('transactions-row--fixed');
          main.fetchCardsOffer($row.find('.transactions-eq'));

          $timeout(function() {
              $scope.$apply(function() {
                  main.popup.isFixed = true;
                  main.popup.$layer.addClass('bestcards-popup--fix-showed');
              });
          });
      }

      this.hideFixedPopupBestCards = function() {
          $('.transactions-row--fixed', $('#transactionsWrap')).removeClass('transactions-row--fixed');
          main.popup.isFixed = false;
          main.popup.$layer.removeClass('bestcards-popup--fix-showed');

          $timeout(function() {
              $scope.$apply(function() {
                  main.popup.isFixed = false;
                  main.popup.$layer.removeClass('bestcards-popup--fix-showed');
                  main.hidePopupBestCards(10);
              });
          });
      }

      this.showOfferFromPopup = function() {
          if (null === main.popup.cacheKey) {
              return;
          }
          const merchant = cachePopupData[main.popup.cacheKey].data.transaction.description;

          var dialog = dialogService.get('credit-card-offer-popup');
          dialog.element.parent().find('.ui-dialog-title').html(SpentAnalysis.getOfferTitle(merchant));

          dialog.setOption('buttons', [
              {
                  text: 'OK',
                  click: function() {
                      dialog.close();
                  },
                  'class': 'btn-blue'
              }
          ]);

          $timeout(function() {
              $scope.$apply(function() {
                  main.state.offerLoading = false;
                  main.offerDialogContent = cachePopupData[main.popup.cacheKey].data.offersHtml;
              });
          });

          setTimeout(() => {
              $(dialog.element).closest('.ui-dialog').css('width', '96%');
              $(window).trigger('resize.dialog');
              customizer.initTooltips($(dialog.element));
          }, 100);

          dialog.open();
          $(dialog.element).closest('.ui-dialog').css({
              'position': 'fixed',
              'left': '2%',
              'top': '80px',
              'width': '96%'
          });
      };

      main.popup = {
          cacheKey: null,
          $layer: $('#bestcardsPopup'),
          loading: false,
          isFixed: false,
          isError: false,
          activeUuid: null,
          chartHover: null,
      };

      main.fillCashEq = function(isRedraw) {
          let stepTimeout = 100;
          $('.transactions-row' + (isRedraw ? '' : ':not(.transactions-row--set)')).each(function(i, row) {
              setTimeout(function() {
                  let value = 0;
                  const $row = $(row);
                  const $chart = $row.find('.transactions-points-chart');
                  $row.addClass('transactions-row--set');

                  const miles = parseFloat($chart.data('miles'));
                  const potentialMiles = parseFloat($chart.data('potential-miles'));
                  value = Math.abs((miles / potentialMiles) * 100).toFixed(1);
                  value = parseFloat(value);

                  if ($row.hasClass('transactions-diff-eq--small')) {
                      value = 75;
                  } else if ($row.hasClass('transactions-diff-eq--medium')) {
                      value = 50;
                  } else if ($row.hasClass('transactions-diff-eq--high')) {
                      value = 25;
                  }

                  if ($row.hasClass('transactions-points-chart--profit')
                      || $row.hasClass('transactions-diff-eq--low')
                      || miles >= potentialMiles
                      || value > 100
                  ) {
                      value = 100;
                  }

                  $row.find('svg circle').get(0).style.strokeDashoffset = 119 - (119 * (value / 100));

              }, (isRedraw ? 0 : (stepTimeout += 150)));

              $(this).find('.transactions-eq').click(main.showFixedPopupBestCards);
          });
      };

  }]);


    app.directive('onFinishRender', function($rootScope) {
        return {
            restrict: 'A',
            link: function(scope, element, attr) {
                if (!scope.$last) {
                    return;
                }

                main.fillCashEq(false);

                main.popup.$layer = $('#bestcardsPopup');
                main.popup.$layer
                    .mouseover(function() {
                        clearTimeout(main.popup.$layer.data('timeout'));
                        main.popup.$layer.addClass('bestcards-popup--showed');
                    })
                    .mouseout(function() {
                        main.hidePopupBestCards();
                    });
                main.popup.$layer.on('click', '.bestcards-footer a', function(e) {
                    e.preventDefault();
                    main.showOfferFromPopup();
                });
            }
        };
    });


    (function earningPotentialPopup() {

        $('#transactionsWrap')
            .on('mouseenter', '.transactions-points-chart', function(e) {
                const uuid = $(this).parent().data('uuid');
                if (uuid === main.popup.activeUuid) {
                    return;
                } else {
                    //main.hidePopupBestCards(490);
                }

                clearTimeout(main.popup.chartHover);
                main.popup.chartHover = setTimeout(() => {
                    const $colWrap = $(this).closest('.transactions-eq');
                    clearTimeout(main.popup.$layer.data('timeout'));
                    main.fetchCardsOffer($colWrap);
                }, 600);

                main.popup.activeUuid = uuid;
            })
            .on('mouseleave', '.transactions-points-chart', function() {
                //const uuid = $(this).parent().data('uuid');

                clearTimeout(main.popup.chartHover);
                main.popup.chartHover = setTimeout(() => {
                    main.popup.activeUuid = null;
                    main.hidePopupBestCards();
                }, 100);
            });
    })();

});
