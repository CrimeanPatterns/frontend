define([
	'angular', 'lib/globalizer'
], function (angular, globalizer) {
	angular = angular && angular.__esModule ? angular.default : angular;

	function ListFiltrator(di, filters, persistent) {
		var services = {};
		var params = {};

		angular.forEach(filters, function (filterServiceId) {
			var service = di.get(filterServiceId);
			if (!service || !Object.prototype.hasOwnProperty.call(service, 'getParamName')) {
				throw new Error('Filtrator service ' + filterServiceId + ' must have parameter');
			}
			services[filterServiceId] = service;
			params[service.getParamName()] = filterServiceId;
			if (Object.prototype.hasOwnProperty.call(service, 'setMode')) {
				service.setMode(persistent);
			}
		});
		var changed = false;
		var self = {
			setValue: function(param, value) {
				if (self.getValue(param) != value) {
					self.getService(param).setValue(value);
					changed = true;
				}
			},
			getValue: function(param) {
				return self.getService(param).getValue();
			},
			setIndex: function (index) {
                services['filtrator.search'].setIndex(index);
            },
			getValues: function() {
				var ret = {};
				angular.forEach(params, function (param) {
					ret[param] = self.getValue(param);
				});
				return ret;
			},
			setValues: function(params) {
				angular.forEach(params, function (value, param) {
					self.setValue(param, value);
				});
			},
			reset: function () {
				angular.forEach(params, function (v, param) {
					self.setValue(param, null);
				});
			},
			getService: function(param) {
				if (!Object.prototype.hasOwnProperty.call(params, param)) {
					throw new Error('Required filtrator service parameter ' + param);
				}
				var serviceId = params[param];
				if (!Object.prototype.hasOwnProperty.call(services, serviceId)) {
					throw new Error('Required filtrator service ' + serviceId);
				}
				return services[serviceId];
			},
			getParameters: function() {
				return Object.keys(params);
			},
			/**
			 *
			 * @param {Object} data Массив данных для фильтрации
			 * @param {Array} index Индекс массива данных
			 * @param {boolean} required Требуется ли перефильтрация
			 * @returns {Array}
			 */
			filter: function(data, index, required) {
				required = required || changed;
				if (!required) return index;
				changed = false;
				angular.forEach(services, function (service) {
					index = service.go(data, index);
				});
				return index;
			},
			filterOne: function(data, index, param) {
				index = self.getService(param).go(data, index);
				return index;
			}
		};
		return self;
	}


	var service = angular.module('listFiltratorService', []);

	service.provider('ListFiltrator',
		function () {
			// Массив фильтров
			var filters = [];
			var persistent = true;

			return {
				setFilters: function(f) {
					filters = f;
				},
				setPersistent: function(mode) {
					persistent = mode;
				},
				$get: [
					'DI',
					/**
					 * @param di
					 */
					function (di) {
						return new ListFiltrator(di, filters, persistent);
					}
				]
			};
		});

	service.provider('DummyListFiltrator',
		function () {
			return {
				$get: [
					'DI',
					/**
					 * @param di
					 */
					function (di) {
						return new ListFiltrator(di, []);
					}
				]
			};
		});

	service.factory('ListFiltratorByAgent', [
		'DI',
		function (di) {
			var paramName = 'agentId';
			var agent = null;
			var persistent = true;

			return {
				getParamName: function() { return paramName; },
				getValue: function() { return agent; },
				setValue: function(data) { agent = data; },
				setMode: function(data) { persistent = data; },
				go: function (data, index) {
					var actives = 0, archives = 0;
					var removeIdx = [];

					if (!agent) {
						angular.forEach(index, function (rowId, id) {
							var row = data[rowId];
							if (row.type == 'account' || row.type == 'coupon') {
									actives += !row._group.IsArchived ? 1 : 0;
									archives += row._group.IsArchived ? 1 : 0;
							}
						});
					} else {
						angular.forEach(index, function (rowId, id) {
							var row = data[rowId];
							if (row.type == 'account' || row.type == 'subaccount' || row.type == 'coupon') {
								var result = !(row._group.AccountOwner == agent);

								if (row._group.AccountOwner == agent) {
										actives += !row._group.IsArchived ? 1 : 0;
										archives += row._group.IsArchived ? 1 : 0;
								}
								if (persistent) {
									row.hidden = result || row.hidden;
								} else {
									if (result) {
										removeIdx.push(rowId);
									}
								}
							}
						});
					}

                    di.get('counters').setAccounts(actives);
					di.get('counters').setActives(actives);
					di.get('counters').setArchives(archives);

					if (!persistent) {
						index = index.filter(function (id) {return removeIdx.indexOf(id) == -1})
					}
					return index;
				}
			};
		}]);

	service.factory('ListFiltratorConnectedCoupon', [
		'DI',
		function (di) {
			var paramName = 'connectedCoupon';

			return {
				getParamName: function() { return paramName; },
				go: function (data, index) {
					var removeIdx = [];
					

					angular.forEach(index, function (rowId) {
						var row = data[rowId];
						if (row.type === 'coupon' && row.fields.ConnectedAccount && data['a' + row.fields.ConnectedAccount] !== undefined) {
							removeIdx.push(rowId);
						}
					});
					index = index.filter(function (id) {return removeIdx.indexOf(id) == -1})
					
					return index;
				}
			};
		}]);


	service.factory('ListFiltratorBySearch',
		function ($location) {
			var paramName = 'search';
			var search = '';
			var fields = ['DisplayName', 'LoginFieldFirst', 'LoginFieldLast', 'AccountStatus', 'FamilyMemberName', 'decorate.userName'];
			var persistent = true;
			var searchIndex = null;

			return {
				getParamName: function() { return paramName; },
				getValue: function() { return search; },
				setValue: function(data) { search = (data || '').toLowerCase(); },
				setMode: function(data) { persistent = data; },
				setIndex: function (index) { searchIndex = index },
				search: function (query) {
                    var terms = query
                        .replace(/[ ]{2,}/g, ' ')
						.replace(/([ ]{2,}| or | and )/g, ' ');
                    var multiple = query.match(/(^|)\w+ or /g);

                    var termsArr = terms.split(/[- ]/);
                    var termsLength = terms.split(/[- ]/).length;
                    if(multiple)
                    	termsLength -= multiple.length;


					if(terms.length < 1)
						return [];

                    return searchIndex
						.search(
                            terms
                                .replace(/(\S+)/g, '$1*')
						)
						.filter(function(result) {
							var found = Object.keys(result.matchData.metadata);
							var termCount = 0;

                            found.forEach(function (key) {
								var value = result.matchData.metadata[key];
								if(value.alliance && terms.length < 3){
									delete result.matchData.metadata[key];
								}
                            });

                            termsArr.forEach(function (term) {
                            	term = term.split(':');
                            	var regex = new RegExp('^' + (term[1] || term[0]));
                                for (var i = 0; i < found.length; i++) {
                                    if(found[i].match(regex)){
                                        termCount++;
                                        break;
									}
                                }
                            });

                        	return Object.keys(result.matchData.metadata).length && termCount === termsLength;
                    	})
						.map(function (item) {
                            return item.ref;
                        });
                },
            	filterByBalance: function (query, results, data) {
                    var balanceFilter = query.match(/(^| )balance ([<=>])[ ]?([0-9., ]+)( |$)/);
                    if(balanceFilter){
                        var amount = globalizer.numberParser(balanceFilter[3]);
                        var sign = balanceFilter[2] === '=' ? '==' : balanceFilter[2];
                        return results.filter(function (idx) {
                            var row = data[idx];
                            if(row.type === 'coupon'){
								var balance = row.fields.Balance && globalizer.numberParser(row.fields.Balance.replace(/[^\d.,]/g, ''));
                                return !!balance && eval(balance + sign + amount);
							}else if(row.type === 'account' || row.type === 'subaccount') {
                                return eval(row.fields.BalanceRaw + sign + amount);
                            }else{
                                return false;
							}
                        });
                    }
					return results;
                },
				filterByExpiration: function (query, results, data) {
                    var expirationFilter = query.match(/(^| )(expire|expiration) in ([0-9]+)[ ]?(month[s]?|year[s]?)( |$)/);
                    if(expirationFilter){
                        var expirationTs = new Date();
                        var todayTs = Math.round(+new Date() / 1000);
                        if(expirationFilter[4].indexOf('month') > -1){
                            expirationTs.setMonth(expirationTs.getMonth() + parseInt(expirationFilter[3]));
                        }else{
                            expirationTs.setMonth(expirationTs.getMonth() + 12 * parseInt(expirationFilter[3]));
                        }
                        expirationTs = Math.round(+expirationTs / 1000);
                        return results.filter(function (idx) {
                            var row = data[idx];
                            if (row.type === 'account' || row.type === 'subaccount' || row.type === 'coupon') {
                                return row.fields.ExpirationKnown && row.fields.ExpirationDateTs > todayTs && row.fields.ExpirationDateTs < expirationTs
                            }
                            return false;
                        });
                    }
                    return results;
                },
				go: function (data, index) {
					if (!search || /:$/.test(search)) return index;

					var terms = search;
                    var searchResults = null;

                    if(terms){
                        searchResults = [];
                        var parens = terms.match(/\(.[^()]+\)( and | or )?/g);

                        if(parens && parens.length){
							var base = terms.replace(/\(.[^()]+\)|\) or |\) and /g, '');
                            var parensResults = [];
                            parens.forEach(function (item, idx) {
                                // console.log(++idx + ' parens is: ', item);
								var parensResult = [];
                            	item.split(' or ').forEach(function (query) {
                            		query = query.replace(/[()]/g, '');
                            		var results = this.search(
										(query)
                                            .replace(/(^| )balance ([<=>])[ ]?([0-9.,]+)( |$)/, ' ')
                                            .replace(/(^| )(expire|expiration) in ([0-9]+)[ ]?(month[s]?|year[s]?)( |$)/, ' ')
                                    );
                                    results = this.filterByBalance(query, results, data);
                                    results = this.filterByExpiration(query, results, data);
									parensResult = parensResult.concat(results);
                                }.bind(this));

                            	if(idx === 0 || parens[--idx].match(/\) or /)){
                                    parensResults = parensResults.concat(parensResult);
								}else{
                                    parensResults = parensResults.filter(function (item) {
                                        return parensResult.indexOf(item) !== -1;
                                    });
								}
                            }.bind(this));

							var baseResults = base.replace(/or|and/g, '').trim() ? this.search(base) : [];

							if(base.match(/ or /))
                                searchResults = parensResults.concat(baseResults);
							else
                                searchResults = parensResults.filter(function (item) {
                                    return !baseResults.length || baseResults.indexOf(item) !== -1;
                                });
						}else{
							terms
								.replace(/[()]/g, '')
								.split(' or ')
								.forEach(function (query, idx) {
									var searchQuery = query
                                        .replace(/(^| )balance ([<=>])[ ]?([0-9.,]+)( |$)/, ' ')
                                        .replace(/(^| )(expire|expiration) in ([0-9]+)[ ]?(month[s]?|year[s]?)( |$)/, ' ');
									var results = this.search(searchQuery);
									if(!searchQuery.trim())
                                        results = index;
									results = this.filterByBalance(query, results, data);
									results = this.filterByExpiration(query, results, data);
									searchResults = searchResults.concat(results);
                            	}.bind(this));
						}
					}

					var removeIdx = [];

                    if(searchResults.length){
                    	var normalizedSearch = encodeURIComponent(search).replace(/%20/g, '+');
                    	$location.hash(normalizedSearch)
					}

					angular.forEach(index, function (rowId, id) {
						var row = data[rowId];
						if (row.type === 'account' || row.type === 'subaccount' || row.type === 'coupon') {
							var result = false;

							if(searchResults && searchResults.indexOf(rowId) > -1)
                                result = true;

							if(terms && row.fields.KeyWords){
                                var parts = row.fields.KeyWords.split(',').map(function (value) {
                                    return value.toLowerCase().trim();
                                });

                                if(parts.indexOf(terms.toLowerCase()) > -1)
                                	result = true;
							}

							if (persistent) {
								row.hidden = !result || row.hidden;
							} else {
								if (!result) {
									removeIdx.push(rowId);
								}
							}
						}
					});
					if (!persistent) {
						index = index.filter(function (id) {return removeIdx.indexOf(id) == -1})
					}
					return index;
				}
			};
		});

	service.factory('ListFiltratorByRecent',
		function () {
			var paramName = 'recent';
			var recentDays = null;
			var persistent = true;

			return {
				getParamName: function() { return paramName; },
				getValue: function() { return recentDays; },
				setValue: function(data) { recentDays = data; },
				setMode: function(data) { persistent = data; },
				go: function (data, index) {
					if (!recentDays) return index;
					var recent = new Date().getTime() / 1000 - recentDays * 86400;
					var removeIdx = [];
					angular.forEach(index, function (rowId, id) {
						var row = data[rowId];
						if (row.type == 'account' || row.type == 'subaccount' || row.type == 'coupon') {
							var result = !(row._group.LastChangeDateTs > recent && row._group.ChangeCount > 0);
							if (persistent) {
								row.hidden = result || row.hidden;
							} else {
								if (result) {
									removeIdx.push(rowId);
								}
							}
						}
					});
					if (!persistent) {
						index = index.filter(function (id) {return removeIdx.indexOf(id) == -1})
					}
					return index;
				}
			};
		});

	service.factory('ListFiltratorReset', [
		function () {
			var paramName = 'init';
			var groupType = null;

			return {
				getParamName: function() { return paramName; },
				getValue: function() { return groupType; },
				setValue: function(data) { groupType = data; },
				go: function (data, index) {
					angular.forEach(index, function (rowId, id) {
						var row = data[rowId];

						if (row.type == 'account' || row.type == 'subaccount' || row.type == 'coupon') {
							row.hidden = false;
							// todo remove to extenders
							if (!Object.prototype.hasOwnProperty.call(row._preorder, 'onPage')) {
								row._preorder.onPage = false;
							}
						} else {
							if (groupType) {
								row.hidden = row._preorder[groupType] === false;
							} else {
								row.hidden = true;
							}
						}
					});
					return index;
				}
			}
		}]);

	service.factory('ListFiltratorByUpdater',
		function () {
			var paramName = 'update';
			var updateIds = null;
			var persistent = true;

			return {
				getParamName: function() { return paramName; },
				getValue: function() { return updateIds; },
				setValue: function(data) { updateIds = data; },
				setMode: function(data) { persistent = data; },
				go: function (data, index) {
					if (!updateIds) return index;
					var removeIdx = [];
					angular.forEach(index, function (rowId, id) {
						var row = data[rowId];
						if (row.type == 'account' || row.type == 'subaccount' || row.type == 'coupon') {
							var result = (updateIds.indexOf(row.ID) == -1);
							if (persistent) {
								row.hidden = result;
							} else {
								if (result) {
									removeIdx.push(rowId);
								}
							}
						} else {
							if (persistent) {
								row.hidden = true;
							} else {
								removeIdx.push(rowId);
							}
						}
					});
					if (!persistent) {
						index = index.filter(function (id) {return removeIdx.indexOf(id) == -1})
					}
					return index;
				}
			};
		});

	service.factory('ListFiltratorByArchive',
		function () {
			let paramName = 'archive';
			let isArchived = null;
			let persistent = true;

			return {
				getParamName: function() { return paramName; },
				getValue: function() { return isArchived; },
				setValue: function(data) { isArchived = data; },
				setMode: function(data) { persistent = data; },
				go: function(data, index) {
					if (!isArchived) return index;
					let removeIdx = [];
					angular.forEach(index, function (rowId, id) {
						let row = data[rowId];
						if (['account', 'subaccount', 'coupon'].includes(row.type)) {
							let result = !row._group.IsArchived;
							if (persistent) {
								row.hidden = result;
							} else {
								if (result) {
									removeIdx.push(rowId);
								}
							}
						}
					});
					if (!persistent) {
						index = index.filter(function (id) { return removeIdx.indexOf(id) == -1; })
					}
					return index;
				}
			};
		});

	service.factory('ListFiltratorByHideArchive',
		function () {
			let paramName = 'init';
			let groupType = null;
			let persistent = true;

			return {
				getParamName: function() { return paramName; },
				getValue: function() { return groupType; },
				setValue: function(data) { groupType = data; },
				setMode: function(data) { persistent = data; },
				go: function(data, index) {
					let removeIdx = [];
					angular.forEach(index, function (rowId, id) {
						let row = data[rowId];
						if (['account', 'subaccount', 'coupon'].includes(row.type)) {
							let result = (Object.keys(row._group).length > 0) && row._group.IsArchived;
							if (persistent) {
								row.hidden = result || row.hidden;
							} else {
								if (result) {
									removeIdx.push(rowId);
								}
							}
						} else {
							if (groupType) {
								row.hidden = row._preorder[groupType] === false;
							} else {
								row.hidden = true;
							}
						}
					});
					if (!persistent) {
						index = index.filter(function (id) { return removeIdx.indexOf(id) == -1; })
					}
					return index;
				}
			};
		});

	service.factory('ListFiltratorLimit',
		function () {
			var paramName = 'limit';
			var limit = null;
			var persistent = true;

			return {
				getParamName: function() { return paramName; },
				getValue: function() { return limit; },
				setValue: function(data) { limit = data; },
				setMode: function(data) { persistent = data; },
				go: function (data, index) {
					if (!limit) return index;
					var cnt = 0;
					var removeIdx = [];
					angular.forEach(index, function (rowId, id) {
						var row = data[rowId];
						if (row.type == 'account' || row.type == 'subaccount' || row.type == 'coupon') {
							if (row.type != 'subaccount' && !row.hidden) {
								cnt++;
							}
							var result = (cnt > limit);
							if (persistent) {
								row.hidden = result || row.hidden;
							} else {
								if (result) {
									removeIdx.push(rowId);
								}
							}
						}
					});
					if (!persistent) {
						index = index.filter(function (id) {return removeIdx.indexOf(id) == -1})
					}
					return index;
				}
			};
		});

	service.factory('ListFiltratorPager', [
		'DI', 'ListConfig',
		function (di, ListConfig) {
			var paramName = 'page';
			var page = 1;
			var persistent = true;
			var perPage = ListConfig.perPage || 20;

			return {
				getParamName: function() { return paramName; },
				getValue: function() { return page; },
				setValue: function(data) { page = data; },
				setMode: function(data) { persistent = data; },
				setPerPage: function(data) { perPage = data; },
				getPerPage: function(data) { return perPage; },
				go: function (data, index) {
					di.get('pager').setPages(Math.ceil(di.get('counters').getTotals().viewTotal / perPage));
					di.get('pager').setPage(page);
					var realPage = di.get('pager').getPage();
					var removeIdx = [];
					var cnt = 0, start = (realPage - 1) * perPage, end = start + perPage;
					angular.forEach(index, function (rowId, id) {
						var row = data[rowId];
						if (row.type == 'account' || row.type == 'subaccount' || row.type == 'coupon') {
							if (row.type != 'subaccount' && !row.hidden) {
								cnt++;
							}
							var result = !(cnt > start && cnt <= end);
							if (persistent) {
								row.hidden = result || row.hidden;
							} else {
								if (result) {
									removeIdx.push(rowId);
								}
							}
						} else {
							if (!persistent) {
								removeIdx.push(rowId);
							}
						}
					});

					if (!persistent) {
						index = index.filter(function (id) {return removeIdx.indexOf(id) == -1})
					}
					return index;
				}
			};
		}]);

	service.factory('ListFiltratorTotals', [
		'DI',
		function (di) {
			var paramName = 'totals';
			var value = null;
			var persistent = true;

			return {
				getParamName: function() { return paramName; },
				getValue: function() { return value; },
				setValue: function(data) { },
				setMode: function(data) { persistent = data; },
				go: function (data, index) {
					if (persistent) {
						var prevLevel = 3,
							prevHidden = true;

						var isKindHidden = true;
						var isUserKindHidden = true;

						for (var i = index.length - 1; i >= 0; i--) {
							var row = data[index[i]];
							var level = (row.type == 'account' || row.type == 'subaccount' || row.type == 'coupon') ? 3 : (row.type == 'kind-user' ? 2 : 1);
							if (level == prevLevel) {
								if (prevHidden == true) {
									prevHidden = row.hidden;
								}
								if(!row.hidden){
									isUserKindHidden = false;
									isKindHidden = false;
								}
							} else if (level < prevLevel) {
								if(level === 2 && isUserKindHidden){
									row.hidden = true;
								}
								if(level === 1 && isKindHidden){
									row.hidden = true;
								}
								prevLevel = level;
							} else {
								if(prevLevel === 1){
									isKindHidden = row.hidden;
									isUserKindHidden = row.hidden;
								}
								if(prevLevel === 2){
									isUserKindHidden = row.hidden;
								}
								prevLevel = level;
								prevHidden = row.hidden;
							}
						}
					}

					var empty = true, viewCount = 0, viewBalance = 0, byKind = {}, byOwner = {},
						totals = {
							accounts: 0,
							coupons: 0,
							documents: 0,
							sumTotalChangePoints: 0,
						},
						mileValue = {
							accounts: 0,
							sumTotalCashEquivalent: 0,
							sumTotalChangeCashEquivalent: 0,
						};

                    function countBalanceTotal(row) {
                        return (parseFloat(row.fields.TotalBalance) > 0 && row.fields.Balance) ? parseFloat(row.fields.TotalBalance) : 0;
                    }

					angular.forEach(index, function (rowId, id) {
						var row = data[rowId];
						if (row.type === 'account' || row.type === 'coupon') {
							if (!row.hidden || !persistent) {
								viewCount++;
								viewBalance += countBalanceTotal(row);

								if (null === row.fields.ConnectedAccount) {
									if ('account' === row.type) {
										totals.accounts++;
									} else if ('coupon' === row.type) {
										if (11 === row.fields.Kind) {
											totals.documents++;
										} else {
											totals.coupons++;
										}
									}
								}

								if (row.fields?.TotalBalanceChange !== undefined) {
									totals.sumTotalChangePoints += parseFloat(row.fields?.TotalBalanceChange);
								}

								let cashEquivalentValue = 0;

								if (undefined !== row.fields?.TotalUSDCashRaw) {
									cashEquivalentValue = parseFloat(row.fields?.TotalUSDCashRaw);
									mileValue.accounts++;
									mileValue.sumTotalCashEquivalent += cashEquivalentValue;
									if (undefined !== row.fields?.TotalUSDCashChange) {
										mileValue.sumTotalChangeCashEquivalent += parseFloat(row.fields?.TotalUSDCashChange);
									}
								}

								if (undefined !== row.fields?.ConnectedCoupons) {
									angular.forEach(row.fields?.ConnectedCoupons, function (coupon) {
										if (undefined !== coupon.TotalBalanceChange) {
											totals.sumTotalChangePoints += parseFloat(coupon.TotalBalanceChange);
										}

										if (undefined !== coupon?.TotalUSDCashRaw) {
											cashEquivalentValue += parseFloat(coupon?.TotalUSDCashRaw);
											mileValue.coupons++;
											mileValue.sumTotalCashEquivalent += parseFloat(coupon?.TotalUSDCashRaw);
											if (undefined !== coupon?.TotalUSDCashChange) {
												mileValue.sumTotalChangeCashEquivalent += parseFloat(coupon?.TotalUSDCashChange);
											}
										}
									});
								}

								if (!Object.prototype.hasOwnProperty.call(byKind, row._group.Kind)) {
									byKind[row._group.Kind] = {
										viewCount: 0,
										viewBalance: 0,
										cashEquivalent: 0,
									};
								}

								byKind[row._group.Kind].viewCount++;
								byKind[row._group.Kind].viewBalance += countBalanceTotal(row);
								byKind[row._group.Kind].cashEquivalent += cashEquivalentValue;

								if (!Object.prototype.hasOwnProperty.call(byOwner, row._group.AccountOwner)) {
									byOwner[row._group.AccountOwner] = {
										viewCount: 0,
										viewBalance: 0,
										cashEquivalent: 0,
									};
								}
								byOwner[row._group.AccountOwner].viewCount++;
								byOwner[row._group.AccountOwner].viewBalance += countBalanceTotal(row);
								byOwner[row._group.AccountOwner].cashEquivalent += cashEquivalentValue;
							}
						}
						if (empty && !row.hidden && persistent) {
							empty = false;
							row.firstRow = true;
						} else {
							row.firstRow = false;
						}
					});

                    for (let i in byKind) {
						byKind[i].viewBalance = Math.round(parseFloat(byKind[i].viewBalance));
						byKind[i].cashEquivalent = (parseFloat(byKind[i].cashEquivalent).toFixed(2));
					}
                    for (let i in byOwner) {
						byOwner[i].viewBalance = Math.round(parseFloat(byOwner[i].viewBalance));
						byOwner[i].cashEquivalent = (parseFloat(byOwner[i].cashEquivalent).toFixed(2));
					}
					mileValue.sumTotalCashEquivalent = mileValue.sumTotalCashEquivalent.toFixed(2);
					mileValue.sumTotalChangeCashEquivalent = mileValue.sumTotalChangeCashEquivalent.toFixed(2);

					di.get('counters').setTotals({
						viewTotal: viewCount,
						viewBalance: viewBalance,
						byKind: byKind,
						byOwner: byOwner,
						mileValue: mileValue,
						totals: totals, 
					});
					
					return index;
				}
			}
		}]);
	service.factory('ListFiltratorChecker', [
		'DI',
		function (di) {
			var paramName = 'checker';
			var value = null;

			return {
				getParamName: function() { return paramName; },
				getValue: function() { return value; },
				setValue: function(data) { },
				go: function (data, index) {

					var checker = {};
					var actions = {};

					angular.forEach(index, function (rowId, id) {
						var row = data[rowId];
						if (row.type == 'account' || row.type == 'coupon') {
							if (!row.hidden) {
								var actionsTest = di.get('actions-manager').testElement(row);
								angular.forEach(actionsTest, function (val, action) {
									if (!Object.prototype.hasOwnProperty.call(actions, action)) {
										actions[action] = [];
									}
									if (val) actions[action].push(row.ID);
								});

								var checkerTest = di.get('checker-manager').testElement(row);
								angular.forEach(checkerTest, function (val, type) {
									if (!Object.prototype.hasOwnProperty.call(checker, type)) {
										checker[type] = [];
									}
									if (val) checker[type].push(row.ID);
								});
							}
						}
					});
					di.get('actions-manager').setItems(actions);
					di.get('checker-manager').setIndex(checker);

					return index;
				}
			}
		}]);

	service.factory('ListFiltratorMore', [

		function () {
			var paramName = 'more';
			var loaded = 50;

			return {
				getParamName: function() { return paramName; },
				getValue: function() { return loaded; },
				setValue: function(data) { loaded = data; },
				go: function (data, index) {
					var currentPage = 0;
					var len = index.length;
					angular.forEach(index, function (rowId, id) {
						var row = data[rowId];
						if ((loaded > 0) && (loaded < len)) {
							if (!row._preorder.onPage) {
								if ((currentPage < loaded) && (!row.hidden)) {
									row._preorder.onPage = true;
									if (row.type == 'account' || row.type == 'coupon') {
										currentPage++;
									}
								}
							} else {
								if (!row.hidden) {
									if (row.type == 'account' || row.type == 'coupon') {
										currentPage++;
									}
								}
							}
						} else {
							if (!row._preorder.onPage) { row._preorder.onPage = true; }
						}
					});
					return index.filter(function (a) {return data[a]._preorder.onPage;});
				}
			}
		}]);

	service.factory('ListFiltratorByError',
		function () {
			var paramName = 'errors';
			var isError = null;
			var persistent = true;

			return {
				getParamName: function() { return paramName; },
				getValue: function() { return isError; },
				setValue: function(data) { isError = data; },
				setMode: function(data) { persistent = data; },
				go: function (data, index) {
					if (!isError) return index;
					var removeIdx = [];
					angular.forEach(index, function (rowId, id) {
						var row = data[rowId];
						if (row.type == 'account' || row.type == 'coupon') {
							var result = !(row._checker.errors);
							if (persistent) {
								row.hidden = result || row.hidden;
							} else {
								if (result) {
									removeIdx.push(rowId);
								}
							}
						}
					});
					if (!persistent) {
						index = index.filter(function (id) {return removeIdx.indexOf(id) == -1})
					}
					return index;
				}
			};
		});

	service.factory('ListFiltratorByProgram',
		function () {
			var paramName = 'filterProgram';
			var program = null;
			var persistent = true;

			return {
				getParamName: function() { return paramName; },
				getValue: function() { return program; },
				setValue: function(data) { program = data; },
				setMode: function(data) { persistent = data; },
				go: function (data, index) {
					if (!program) return index;
					var removeIdx = [];
					angular.forEach(index, function (rowId, id) {
						var row = data[rowId];
						if (row.type == 'account' || row.type == 'coupon') {
							var result = !(('' + row.fields.DisplayName).toLowerCase().indexOf(program.toLowerCase()) > -1);
							if (persistent) {
								row.hidden = result || row.hidden;
							} else {
								if (result) {
									removeIdx.push(rowId);
								}
							}
						}
					});
					if (!persistent) {
						index = index.filter(function (id) {return removeIdx.indexOf(id) == -1})
					}
					return index;
				}
			};
		});

	service.factory('ListFiltratorByOwner', [
		'DI',
		function (di) {
			var paramName = 'filterOwner';
			var value = null;
			var persistent = true;

			return {
				getParamName: function() { return paramName; },
				getValue: function() { return value; },
				setValue: function(data) { value = data; },
				setMode: function(data) { persistent = data; },
				go: function (data, index) {
					if (!value) return index;
					var removeIdx = [];
					angular.forEach(index, function (rowId, id) {
						var row = data[rowId];
						if (row.type == 'account' || row.type == 'coupon') {

							var result =
                                !(('' + di.get('accounts').getUserName(row.fields.AccountOwner)).toLowerCase().indexOf(value.toLowerCase()) > -1) &&
                                !(row.fields.FamilyMemberName && row.fields.FamilyMemberName.toLowerCase().indexOf(value.toLowerCase()) > -1);

                            // todo fix DI there
                            if (persistent) {
								row.hidden = result || row.hidden;
							} else {
								if (result) {
									removeIdx.push(rowId);
								}
							}
						}
					});
					if (!persistent) {
						index = index.filter(function (id) {return removeIdx.indexOf(id) == -1})
					}
					return index;
				}
			};
		}]);

	service.factory('ListFiltratorByAccount',
		function () {
			var paramName = 'filterAccount';
			var value = null;
			var persistent = true;

			return {
				getParamName: function() { return paramName; },
				getValue: function() { return value; },
				setValue: function(data) { value = data; },
				setMode: function(data) { persistent = data; },
				go: function (data, index) {
					if (!value) return index;
					var removeIdx = [];
					angular.forEach(index, function (rowId, id) {
						var row = data[rowId];
						if (row.type == 'account' || row.type == 'coupon') {
							var result = !(('' + row.fields.LoginFieldFirst).toLowerCase().indexOf(value.toLowerCase()) > -1) &&
								(!row.fields.document || !row.fields.document.passport || !(('' + row.fields.document.passport.number).toLowerCase().indexOf(value.toLowerCase()) > -1));
							if (persistent) {
								row.hidden = result || row.hidden;
							} else {
								if (result) {
									removeIdx.push(rowId);
								}
							}
						}
					});
					if (!persistent) {
						index = index.filter(function (id) {return removeIdx.indexOf(id) == -1})
					}
					return index;
				}
			};
		});

	service.factory('ListFiltratorByStatus',
		function () {
			var paramName = 'filterStatus';
			var value = null;
			var persistent = true;

			return {
				getParamName: function() { return paramName; },
				getValue: function() { return value; },
				setValue: function(data) { value = data; },
				setMode: function(data) { persistent = data; },
				go: function (data, index) {
					if (!value) return index;
					var removeIdx = [];
					angular.forEach(index, function (rowId, id) {
						var row = data[rowId];
						if (row.type == 'account' || row.type == 'coupon') {
							var result = !(('' + row.fields.AccountStatus).toLowerCase().indexOf(value.toLowerCase()) > -1);
							if (persistent) {
								row.hidden = result || row.hidden;
							} else {
								if (result) {
									removeIdx.push(rowId);
								}
							}
						}
					});
					if (!persistent) {
						index = index.filter(function (id) {return removeIdx.indexOf(id) == -1})
					}
					return index;
				}
			};
		});

	service.factory('ListFiltratorByBalance',
		function () {
			var paramName = 'filterBalance';
			var value = null;
			var persistent = true;

			return {
				getParamName: function() { return paramName; },
				getValue: function() { return value; },
				setValue: function(data) { value = data; },
				setMode: function(data) { persistent = data; },
				go: function (data, index) {
					if (!value) return index;
					var removeIdx = [];
					angular.forEach(index, function (rowId, id) {
						var row = data[rowId];
						if (row.type == 'account' || row.type == 'coupon') {
							var result = !(row.fields.BalanceRaw >= value);
							if (persistent) {
								row.hidden = result || row.hidden;
							} else {
								if (result) {
									removeIdx.push(rowId);
								}
							}
						}
					});
					if (!persistent) {
						index = index.filter(function (id) {return removeIdx.indexOf(id) == -1})
					}
					return index;
				}
			};
		});

	// service.factory('ListFiltratorByCashequivalent',
	// 	function() {
	// 		var paramName = 'filterCashequivalent';
	// 		var value = null;
	// 		var persistent = true;
	//
	// 		return {
	// 			getParamName: function() {
	// 				return paramName;
	// 			},
	// 			getValue: function() {
	// 				return value;
	// 			},
	// 			setValue: function(data) {
	// 				value = data;
	// 			},
	// 			setMode: function(data) {
	// 				persistent = data;
	// 			},
	// 			go: function(data, index) {
	// 				if (!value) return index;
	// 				var removeIdx = [];
	//
	// 				angular.forEach(index, function(rowId, id) {
	// 					var row = data[rowId];
	// 					if (row.type === 'account' || row.type === 'coupon') {
	// 						var result =
	// 							!(
	// 								(undefined !== row.fields?.MileValue?.approximate?.raw && row.fields?.MileValue?.approximate?.raw >= value)
	// 								|| (undefined !== row.fields?.USDCashRaw && row.fields?.USDCashRaw >= value)
	// 							);
	// 						if (persistent) {
	// 							row.hidden = result || row.hidden;
	// 						} else {
	// 							if (result) {
	// 								removeIdx.push(rowId);
	// 							}
	// 						}
	// 					}
	// 				});
	// 				if (!persistent) {
	// 					index = index.filter(function(id) {
	// 						return removeIdx.indexOf(id) == -1
	// 					});
	// 				}
	// 				return index;
	// 			}
	// 		};
	// 	});

	service.factory('ListFiltratorByExpire',
		function () {
			var paramName = 'filterExpire';
			var value = null;
			var persistent = true;

			return {
				getParamName: function() { return paramName; },
				getValue: function() { return value; },
				setValue: function(data) { value = data; },
				setMode: function(data) { persistent = data; },
				go: function (data, index) {
					if (!value) return index;
					var removeIdx = [];
					angular.forEach(index, function (rowId, id) {
						var row = data[rowId];
						if (row.type == 'account' || row.type == 'coupon') {
							var result = !(row.fields.ExpirationDateTs <= value);
							if (persistent) {
								row.hidden = result || row.hidden;
							} else {
								if (result) {
									removeIdx.push(rowId);
								}
							}
						}
					});
					if (!persistent) {
						index = index.filter(function (id) {return removeIdx.indexOf(id) == -1})
					}
					return index;
				}
			};
		});

	service.factory('ListFiltratorByLastUpdate',
		function () {
			var paramName = 'filterLastUpdate';
			var value = null;
			var persistent = true;

			return {
				getParamName: function() { return paramName; },
				getValue: function() { return value; },
				setValue: function(data) { value = data; },
				setMode: function(data) { persistent = data; },
				go: function (data, index) {
					if (!value) return index;
					var removeIdx = [];
					angular.forEach(index, function (rowId, id) {
						var row = data[rowId];
						if (row.type == 'account' || row.type == 'coupon') {
							var result = !(row.fields.LastUpdatedDateTs <= value);
							if (persistent) {
								row.hidden = result || row.hidden;
							} else {
								if (result) {
									removeIdx.push(rowId);
								}
							}
						}
					});
					if (!persistent) {
						index = index.filter(function (id) {return removeIdx.indexOf(id) == -1})
					}
					return index;
				}
			};
		});

    service.factory('ListFiltratorBySharedWith',
        function () {
            var paramName = 'sharedWith';
            var value = null;
            var persistent = true;

            return {
                getParamName: function() { return paramName; },
                getValue: function() { return value; },
                setValue: function(data) { value = data; },
                setMode: function(data) { persistent = data; },
                go: function (data, index) {
                    if (!value) return index;
                    var removeIdx = [];
                    angular.forEach(index, function (rowId, id) {
                        var row = data[rowId];
                        if (row.type == 'account' || row.type == 'coupon') {
                            var result = (
                                !row.fields.Shares ||
                                (row.fields.Shares.indexOf(+value) === -1)
                            );
                            if (persistent) {
                                row.hidden = result || row.hidden;
                            } else {
                                if (result) {
                                    removeIdx.push(rowId);
                                }
                            }
                        }
                    });
                    if (!persistent) {
                        index = index.filter(function (id) {return removeIdx.indexOf(id) === -1})
                    }
                    return index;
                }
            };
        });

});
