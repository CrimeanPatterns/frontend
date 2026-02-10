define([
	'angular',  'jquery-boot', 'lunr'
], function (angular, $, lunr) {
	angular = angular && angular.__esModule ? angular.default : angular;

	/**
	 * @lends ListManager
	 */
	function ListManager($q, $timeout, di, business, partial) {
		var Accounts = di.get('accounts'),
			Loader = di.get('loader'),
			//Agents = di.get('agents'),
			Compiler = di.get('compiler'),
			Extender = di.get('extender'),
			Sorter = di.get('sorter'),
			Filtrator = di.get('filtrator'),
			Checker = di.get('checker-manager'),
			UpdaterElements = di.get('updater-elements'),
			listData = {},
			shared = {
				elements:  {
					data: [],
					search: ''
				},
				state: {
					loaded: false,
					loading: false,
					loadMore: false,
					loadMoreHeight: 0,
					updating: false,
					toolbar: true,
					pager: true,
					agentTitle: ''
				}
			}
			;

		function compile(data) {
			listData = Compiler.compile(data);
			// Добавляем в список ссылки на сервисы
			angular.forEach(listData, function (row) {
				Extender.extend(row, row.type);
			});
		}

		function build(required) {
			required = required || true;
			// Сортируем и фильтруем
			var index = Object.keys(listData);
            var ebmeddedAccounsMap, ebmeddedAccounsIndex;
			index = Sorter.sort(listData, index, required);
			index = Filtrator.filter(listData, index, required);
			// Формируем список отображаемых
			shared.elements.data = [];

			angular.forEach(index, function (id) {
				shared.elements.data.push(listData[id]);
                ebmeddedAccounsMap = {};

                if (listData[id].fields.ConnectedCoupons) {
                    angular.forEach(listData[id].fields.ConnectedCoupons || [], function (coupon) {
                        ebmeddedAccounsMap[coupon.FID] = listData[coupon.FID];
                    });
                }

                if (listData[id].fields.SubAccountsArray) {
                    angular.forEach(listData[id].fields.SubAccountsArray || [], function (subAccount) {
                        ebmeddedAccounsMap['subaccount-' + subAccount.SubAccountID] = listData['subaccount-' + subAccount.SubAccountID];
                    });
                }

                ebmeddedAccounsIndex = Sorter.sort(ebmeddedAccounsMap, Object.keys(ebmeddedAccounsMap), required, true);

                if (ebmeddedAccounsIndex.length) {
                    shared.elements.data[shared.elements.data.length - 1].fields.EmbeddedAccountsList = [];

                    angular.forEach(ebmeddedAccounsIndex, function (accId) {
                        shared.elements.data[shared.elements.data.length - 1].fields.EmbeddedAccountsList.push(ebmeddedAccounsMap[accId].fields);
                    });
                }
			});

			
			
			
			var agentId = Filtrator.getValue('agentId');
			var total = 0;
			var balance = 0;
			
			angular.forEach(listData, function (row) {
				if (
                    row.type == 'account'
                    || (
                        row.type == 'coupon'
                        && (
                            row.fields.ConnectedAccount === null
                            || listData['a' + row.fields.ConnectedAccount] === undefined
                        )
                    )
                ) {
					if(!agentId || (di.get('agents').getAgent(agentId) && di.get('agents').getAgent(agentId).ID === row.fields.AccountOwner)){
						total++;
						balance += (parseFloat(row.fields.TotalBalance) > 0 && row.fields.Balance) ? parseFloat(row.fields.TotalBalance) : 0;
					}
				}
			})
			
			di.get('counters').setTotals({
				total: total,
				balance: balance,
			});
			
			
			if (typeof (agentId) != 'undefined') {
				var user = di.get('agents').getAgent(agentId);
				if (user) {
					shared.state.agentTitle = Translator.trans('award.account.list.reset.agent', {userName: user.name});
				} else {
					shared.state.agentTitle = Translator.trans('award.account.list.reset.agent-none');
				}
			}
/*
			console.log(index);
			var hidden = [];
			angular.forEach(index, function (id) {
				if (listData[id].hidden) hidden.push(id);
			});
			console.log(hidden);
*/
		}

		function buildPartial(defer) {
			shared.state.loading = true;
			/*
			 const ORDERBY_CARRIER 		= 1;
			 const ORDERBY_PROGRAM 		= 2;
			 const ORDERBY_BALANCE 		= 3;
			 const ORDERBY_EXPIRATION 	= 4;
			 const ORDERBY_LASTUPDATE 	= 5;

			 const ORDERBY_CARRIER_DESC 		= 11;
			 const ORDERBY_PROGRAM_DESC 		= 12;
			 const ORDERBY_BALANCE_DESC 		= 13;
			 const ORDERBY_EXPIRATION_DESC 		= 14;
			 const ORDERBY_LASTUPDATE_DESC 		= 15;
			*/
			var order = Sorter.getColumn() == 'DisplayName' ? 2 :
				(Sorter.getColumn() == 'BalanceRaw' ? 3 :
					(Sorter.getColumn() == 'ExpirationDateTs' ? 4 : 5));
			if (Sorter.getReverse()) order += 10;
			Loader.setOptions('accounts', {
				page: Filtrator.getValue('page'),
				perPage: di.get('filtrator.pager').getPerPage(),
				order: order,
				agentId: Filtrator.getValue('agentId'),
				filterError: Filtrator.getValue('errors'),
				filterRecent: Filtrator.getValue('recent'),
				filterProgram: Filtrator.getValue('filterProgram'),
				filterOwner: Filtrator.getValue('filterOwner'),
				filterAccount: Filtrator.getValue('filterAccount'),
				filterStatus: Filtrator.getValue('filterStatus'),
				filterBalance: Filtrator.getValue('filterBalance'),
				// filterCashequivalent: Filtrator.getValue('filterCashequivalent'),
				filterExpire: Filtrator.getValue('filterExpire'),
				filterLastUpdate: Filtrator.getValue('filterLastUpdate')
			});
			$timeout(function () {
				Loader.load(['accounts']).then(function () {
					Checker.resetService();
					UpdaterElements.resetService();
					compile(Accounts.getAccounts());
					di.get('pager').setPages(Math.ceil(di.get('counters').getTotals().viewTotal / di.get('filtrator.pager').getPerPage()));
					var index = Object.keys(listData);
					index = Filtrator.filterOne(listData, index, 'checker');
					index = Filtrator.filterOne(listData, index, 'update');
					index = Filtrator.filterOne(listData, index, 'totals');
					shared.elements.data = [];
					angular.forEach(index, function (id) {
						shared.elements.data.push(listData[id])
					});
					var agentId = Filtrator.getValue('agentId');
					if (typeof (agentId) != 'undefined') {
						var user = di.get('agents').getAgent(agentId);
						if (user) {
							shared.state.agentTitle = Translator.trans(/** @Desc("Accounts owned by <span class='red'>%userName%</span>") */'award.account.list.reset.agent', {userName: user.name});
						} else {
							shared.state.agentTitle = Translator.trans(/** @Desc("Unknown owner") */'award.account.list.reset.agent-none');
						}
					}
					$timeout(function () {
						shared.state.loading = false;
					}, 50);
					shared.state.loaded = true;
					if (defer) { defer.resolve(); }
				});
			}, 50);
		}

		/**
		 * @class ListManager
		 */
		var self = {
			init: function() {
				shared.state.loaded = false;
				//console.log('init');
				// Загружаем список.
				var defer = $q.defer();
				if (!partial) {
					Loader.get('accounts').then(function () {
						// Очистка чекбоксов и
						// todo remove to extenders
						Checker.resetService();
						UpdaterElements.resetService();
						// Компиляция данных для списка
						compile(Accounts.getAccounts());
						// Сборка
						build(true);
						defer.resolve();
						var cnt = Object.keys(Accounts.getAccounts()).length;
						if (business || cnt <= 50) {
                            self.buildSearchIndex();
							shared.state.loaded = true;
						} else {
							$timeout(function () {self.preloadMore(cnt);}, 200)
						}
					});
				} else {
					di.get('counters').setTotals({total: 1});
					defer.resolve()
				}
				return defer.promise;
			},
			updateAccounts: function(accounts) {
				if (!partial) {
					Accounts.setAccounts(accounts, true);
					compile(Accounts.getAccounts());
					build(true);
				} else {
					Loader.clear();
					buildPartial();
				}
			},
			removeAccounts: function(ids) {
				if (!partial) {
					Accounts.removeAccounts(ids);
					compile(Accounts.getAccounts());
					build(true);
				} else {
					Loader.clear();
					buildPartial();
				}
			},
			build: function (required) {
				required = required || false;
				if (!partial) {
					build(required);
				} else {
					buildPartial();
				}
			},
			setFilter: function(filter, value, preservePage) {
				preservePage = preservePage || false;
				Filtrator.setValue(filter, value);
				Sorter.setSearch(Filtrator.getValue('search'));
				Checker.cleanLastCheck();
				if (!preservePage) self.setPage(1);
			},
			setFilters: function(filtersValue, reset) {
				reset = !!reset;
				if (reset) {
					Filtrator.reset();
				}
				var allowed = Filtrator.getParameters();
				angular.forEach(filtersValue, function (value, param) {
					if (allowed.indexOf(param) !== -1) {
						Filtrator.setValue(param, value);
					}
				});
				if (!business) {
					Filtrator.setValue('init', self.getGrouped() ? ((Filtrator.getValue('agentId') || Filtrator.getValue('search')) ? 'kind' : 'kindUser') : false);
					Sorter.setOwner(Filtrator.getValue('agentId'));
				}
				Sorter.setSearch(Filtrator.getValue('search'));
				Checker.cleanLastCheck();
				//self.setPage(1);
			},
			setOrder: function(column, reversed) {
				Sorter.setOrder(column, reversed);
			},
			setGrouped: function(grouped) {
				Sorter.setUngroup(!grouped);
				if (!business) {
					Filtrator.setValue('init', self.getGrouped() ? ((Filtrator.getValue('agentId') || Filtrator.getValue('search')) ? 'kind' : 'kindUser') : false);
				}
			},
			getGrouped: function() {
				return !Sorter.getUngroup();
			},
			setSearch: function(search) {
				self.setFilter('search', search);
				if (!business) {
					Filtrator.setValue('init', self.getGrouped() ? ((Filtrator.getValue('agentId') || Filtrator.getValue('search')) ? 'kind' : 'kindUser') : false);
				}
			},
			getSearch: function() {
				return Filtrator.getValue('search');
			},
			setPage: function(page) {
				if (Filtrator.getParameters().indexOf('page') != -1) {
					Filtrator.setValue('page', page);
					di.get('pager').setPage(page);
				}
			},
			getPage: function() {
				if (Filtrator.getParameters().indexOf('page') != -1) {
					return Filtrator.getValue('page');
				}
			},
			setUpdate: function(ids) {
				if (ids) {
					shared.state.pager = false;
					shared.state.toolbar = false;
					shared.state.updating = true;
				} else {
					shared.state.pager = true;
					shared.state.toolbar = true;
					shared.state.updating = false;
				}
				self.setFilter('update', ids, true);
			},
			getUpdate: function() {
				return Filtrator.getValue('update');
			},
			getGroupType: function () {
				return true;
				//if (Filtrator.getValue('agentId')) {
				//	return false;
				//}
				//if (Filtrator.getValue('update')) {
				//	return true;
				//}
				//if (self.getGrouped() && Filtrator.getValue('search')) {
				//	return true;
				//}
				//if (!self.getGrouped()) {
				//	return true;
				//}
				//return false;
			},
			getElements: function() {
				return shared.elements;
			},
			getState: function() {
				return shared.state;
			},
			getOrder: function() {
				return Sorter.getOrder();
			},
			getPager: function() {
				return di.get('pager').getPager();
			},
			loadMore: function () {
				if (business) return;
				if (!shared.state.loadMore && Filtrator.getValue('more') < Object.keys(listData).length) {
					shared.state.loadMore = true;
					shared.state.loadMoreHeight = 36 * (Object.keys(listData).length - Filtrator.getValue('more') > 20 ? 20 : Object.keys(listData).length - Filtrator.getValue('more'));
					$timeout(function () {
						Filtrator.setValue('more', Filtrator.getValue('more') + 20);
						build(true);
						shared.state.loadMore = false;
					}, 0);
				}
			},
			preloadMore: function (n) {
				if (business) return;
				if (!shared.state.loadMore && Filtrator.getValue('more') < n) {
					shared.state.loadMore = true;
					Filtrator.setValue('more', Filtrator.getValue('more') + 50);
					build(true);
					shared.state.loadMore = false;
					if (Filtrator.getValue('more') < n) {
						$timeout(function () {
							self.preloadMore(n)
						}, 200)
					} else {
						self.buildSearchIndex();
						shared.state.loaded = true;
                    }
				}
			},
            buildSearchIndex: function() {

				var keys = Object.keys(listData);

				var kinds = keys
                    .reduce(function (acc, key) {
                        var row = listData[key];
                    	if(row.type === 'kind'){
                    		acc[row.fields.ID] = row.fields.name;
						}
						return acc;
                    }, {});

                var preparedAccounts = keys
					.reduce(function (acc, key) {
                        var row = listData[key];
                        if(row.type == 'account' || row.type == 'subaccount' || row.type == 'coupon'){
	                        let subaccountsName = [], connectedsName = [];
	                        if ('account' === row.type) {
		                        if ('undefined' !== typeof row.fields.SubAccountsArray && row.fields.SubAccountsArray.length) {
			                        for (let i in row.fields.SubAccountsArray) {
				                        subaccountsName.push(row.fields.SubAccountsArray[i].DisplayName);
			                        }
		                        }
		                        if ('undefined' !== typeof row.fields.ConnectedCoupons && row.fields.ConnectedCoupons.length) {
			                        for (let i in row.fields.ConnectedCoupons) {
				                        connectedsName.push(row.fields.ConnectedCoupons[i].LoginFieldFirst + ' ' + row.fields.ConnectedCoupons[i].AccountStatus);
			                        }
		                        }
	                        }
                        	acc.push({
                                id: row.ID,
                                owner: row._decorate ? row._decorate.userName : null,
                                program: row.fields.DisplayName,
                                type: row.type,
                                login: row.fields.LoginFieldFirst,
                                status: row.fields.AccountStatus,
                                alliance: row.fields.AllianceAlias,
                                kind: kinds[row.fields.Kind],
                                passport: (row.fields.document && row.fields.document.passport)? row.fields.document.passport.number + '; ' + row.fields.document.passport.name : '',
		                        subaccountsName: subaccountsName.join('; '),
		                        connectedsName: connectedsName.join('; '),
                            });
						}
						return acc;
                    }, []);

                var trimmerEnRuZh = function (token) {
                    return token.str
						.replace(/[()]/, '')
                        .replace(/^[^\wа-яёА-ЯЁ\u0021-\u007E\u00A1-\u00AC\u00AE-\u00FF\u4E00-\u9FA5\u0E01-\u0E5B-]+/, '')
                        .replace(/[^\wа-яёА-ЯЁ\u0021-\u007E\u00A1-\u00AC\u00AE-\u00FF\u4E00-\u9FA5\u0E01-\u0E5B-]+$/, '');
                };

				var index = lunr(function () {
                    this.pipeline.reset();
                    this.pipeline.add(trimmerEnRuZh);

					this.field('owner', {
						boost: 10
					});
					this.field('program');
					this.field('login');
					this.field('status');
					this.field('type');
					this.field('alliance');
					this.field('kind');
					this.field('passport');
					this.field('subaccountsName');
					this.field('connectedsName');

					preparedAccounts.forEach(function (account) {
						this.add(account)
					}, this)
				});

				Filtrator.setIndex(index);
            }
		};

		return self;
	}

	var service = angular.module('listManagerService', []);

	service.provider('ListManager',
		function () {
			var business = false, partial = false;

			return {
				setBusinessMode: function (mode) {
					business = mode;
				},
				setPartialMode: function (mode) {
					partial = mode;
				},
				$get: [
					'$q', '$timeout', 'DI',
					/**
					 * @param $q
					 * @param $timeout
					 * @param di
					 */
					function ($q, $timeout, di) {
						return new ListManager($q, $timeout, di, business, partial);
					}
				]
			};
		})

});
