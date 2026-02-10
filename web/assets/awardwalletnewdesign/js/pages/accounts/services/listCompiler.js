define([
	'angular',
	'dateTimeDiff'
], function (angular, dateTimeDiff) {
	angular = angular && angular.__esModule ? angular.default : angular;

	function ListCompiler(di, ficoService, limit) {
		var compiled = {}, oldCompiled = {};
		var owners = {};
		var couponCount = 0;

		var Row = {
			ID: null,
			type: null,
			fields: {},
			checker: {},
			updater: {},
			_search: [],
			_order: {},
			_preorder: {},
			_group: {},
			_checker: {}
		};

		function pad(a, b) {
			return (1e15 + a + "").slice(-b)
		}

		function buildRow(items) {
			var changed = false;
			if (!angular.isArray(items)) items = [items];
			angular.forEach(items, function (item) {
				switch (item.type) {
					case 'account':
						changed = buildAccountRow(item) || changed;
						break;
					case 'subaccount':
						changed = buildSubAccountRow(item) || changed;
						break;
					case 'coupon':
						changed = buildCouponRow(item) || changed;
						break;
				}
			});
			return changed;
		}

		function buildAccountRow(item) {
			var id = item.FID, changed = false, row;
			if (Object.prototype.hasOwnProperty.call(oldCompiled, id)) {
				compiled[id] = oldCompiled[id];
			}
			if (!Object.prototype.hasOwnProperty.call(compiled, id)) {
				row = angular.extend({}, Row);
				row.ID = id;
				row.type = 'account';
				row.fields = item;
				row.checker = di.get('checker').bind(id);
				row.updater = di.get('updater-elements').bind(id, item);
				row.updater = di.get('updater-element-decorator').decorate(row.updater);
				compiled[id] = row;
				changed = true;
			} else {
				row = compiled[id];
			}
			changed = buildKindRow(item.Kind) || changed;
			changed = buildUserRow(item.AccountOwner) || changed;
			changed = buildKindUserRow(item.Kind, item.AccountOwner) || changed;
			var _search = [
				item.DisplayName, item.LoginFieldFirst,
				(item.LoginFieldLast || '').substring(0, 50), item.AccountStatus,
				item.FamilyMemberName
			];
			var _order = {
				DisplayName: item.DisplayName,
				BalanceRaw: item.BalanceRaw * -1,
				ExpirationDateTs: item.ExpirationDateTs,
				ExpirationDateTsReverse: item.ExpirationDateTs == 10000000001 ? 0 : (item.ExpirationDateTs == 9999999999 ? 1 : item.ExpirationDateTs),
                LastUpdatedDateTs: item.LastUpdatedDateTs ?? 0,
                LastUpdatedDateTsReverse: item.LastUpdatedDateTs ?? 0,
			};
			var _preorder = {
				kindUser: getKindUserPreorder(item.Kind, item.AccountOwner) + '-!',
				kind: getKindPreorder(item.Kind) + '-!',
				user: getUserPreorder(item.AccountOwner) + '-!'
			};
			var _group = {
				Kind: item.Kind,
				AccountOwner: item.AccountOwner,
                FamilyMemberName: item.FamilyMemberName,
				IsActive: item.IsActive,
				IsArchived: item.IsArchived,
				LastChangeDateTs: item.LastChangeDateTs,
				ChangeCount: item.ChangeCount
			};
			var _checker = {
				errors: (item.ProgramMessage.Type == 5 && item.ErrorCode > 1 && !item.isCustom),
				weekAgo: ((item.LastUpdatedDateTs < (new Date().getTime() / 1000 - 604800)) || (item.CanReceiveEmail && !item.CanCheck)),
				expired: (item.ExpirationDateTs < (new Date().getTime() / 1000 + 7776000) && item.ExpirationDateTs > new Date().getTime() / 1000),
				hasTrips: (item.HasCurrentTrips)
			};
			row._order = _order;
			row._preorder = _preorder;
			row._checker = _checker;
			row._group = _group;
			row._search = _search;
			return changed || true;
		}

		function buildSubAccountRow(item) {
            var id = 'subaccount-' + item.SubAccountID, changed = false, row;

            if (Object.prototype.hasOwnProperty.call(oldCompiled, id)) {
                compiled[id] = oldCompiled[id];
            }

            if (!Object.prototype.hasOwnProperty.call(compiled, id)) {
                row = angular.extend({}, Row);
                row.ID = id;
                row.type = 'subaccount';
                row.fields = item;
                compiled[id] = row;
                changed = true;
            } else {
                row = compiled[id];
            }

            row._order = {
                DisplayName: item.DisplayName,
                BalanceRaw: item.BalanceRaw * -1,
                ExpirationDateTs: item.ExpirationDateTs,
                ExpirationDateTsReverse: item.ExpirationDateTs == 10000000001 ? 0 : (item.ExpirationDateTs == 9999999999 ? 1 : item.ExpirationDateTs)
            };

						const account = compiled[`a${item.AccountID}`];

						row._preorder = {
							...row._preorder,
							kindUser: getKindUserPreorder(account.fields.Kind, account.fields.AccountOwner) + '--!',
						}

						row._group = {
							...row._group,
							IsArchived: account.fields.IsArchived,
						}

            return changed || true;
		}

		function buildCouponRow(item) {
			var id = item.FID, changed = false, row;
			if (Object.prototype.hasOwnProperty.call(oldCompiled, id)) {
				compiled[id] = oldCompiled[id];
			}
			if (!Object.prototype.hasOwnProperty.call(compiled, id)) {
				row = angular.extend({}, Row);
				row.ID = id;
				row.type = 'coupon';
				row.fields = item;
				row.checker = di.get('checker').bind(id);
				compiled[id] = row;
				changed = true;
			} else {
				row = compiled[id];
			}
			changed = buildKindRow(item.Kind) || changed;

            if (!item.ConnectedAccount) {
                changed = buildUserRow(item.AccountOwner) || changed;
            }

			changed = buildKindUserRow(item.Kind, item.AccountOwner) || changed;
			var _search = [
				item.DisplayName, item.LoginFieldFirst,
				(item.LoginFieldLast || '').substring(0, 50), item.AccountStatus
			];
			var _order = {
				DisplayName: item.subtype === 'connected' ? item.extendedStatus : item.DisplayName,
				BalanceRaw: item.BalanceRaw * -1,
				ExpirationDateTs: item.ExpirationDateTs,
				ExpirationDateTsReverse: item.ExpirationDateTs == 10000000001 ? 0 : (item.ExpirationDateTs == 9999999999 ? 1 : item.ExpirationDateTs),
                LastUpdatedDateTs: 10000000001,
                LastUpdatedDateTsReverse: -10000000001
			};
			var _preorder = {
				kindUser: getKindUserPreorder(item.Kind, item.AccountOwner) + '-!',
				kind: getKindPreorder(item.Kind) + '-!',
				user: getUserPreorder(item.AccountOwner) + '-!'
			};
			var _group = {
				Kind: item.Kind,
				AccountOwner: item.AccountOwner,
				IsActive: item.IsActive,
				IsArchived: item.IsArchived,
				LastChangeDateTs: item.LastChangeDateTs,
				ChangeCount: item.ChangeCount
			};
			var _checker = {
				errors: false,
				weekAgo: false,
				expired: (item.ExpirationDateTs < (new Date().getTime() / 1000 + 7776000) && item.ExpirationDateTs > new Date().getTime() / 1000),
				hasTrips: false
			};
			row._order = _order;
			row._preorder = _preorder;
			row._checker = _checker;
			row._group = _group;
			row._search = _search;
			return changed || true;
		}

		function buildKindRow(kindId) {
			var id = 'kind-' + kindId,
				changed = false,
				row;
			if (!Object.prototype.hasOwnProperty.call(compiled, id)) {
				row = angular.extend({}, Row);
				row.ID = id;
				row.type = 'kind';
				row.fields = di.get('kinds').getKind(kindId);
				
				if (kindId === 6) {
					var accounts = di.get("accounts").getAccounts();
					var ficoAccounts = [];
		  
					Object.keys(accounts).forEach(function (key) {
					  var account = accounts[key];
		  
					  if (account.SubAccountsArray) {
						account.SubAccountsArray.forEach(function (subAccount) {
						  if (subAccount.FICO === true) {
							var accountId = account.ID;
                			var subAccountId = subAccount.SubAccountID;

							var now = new Date();
							var updatedTs = subAccount.FICOScoreUpdatedOnTs ?? account.LastUpdatedDateTs;
						
							var date = new Date(1000 * updatedTs);

							if (date.getTime() > now.getTime()) {
								date = now;
							}
						
							ficoAccounts.push({
							  balance: subAccount.Balance || "",
							  isChangePositive: subAccount.ChangedPositive || false,
							  balanceChangeNumber: subAccount.ChangeCount || 0,
							  name: subAccount.DisplayName || "",
							  lastUpdatedDate: dateTimeDiff.shortFormatViaDateTimes(now, date),
							  onUpdate: ficoService.getUpdateHandler(accountId, subAccountId),
							  isUpdating: ficoService.isAccountUpdating(accountId),
							  accountId,
							  ficoRanges: subAccount.ficoRanges,
							  isUpdateAvailable:
								(account.Access && account.Access.oneUpdate) || false,
							  account: account,
							});
						  }
						});
					  }
					});
		  
					row.updater = di.get("updater-elements").bind(id, {});
					row.updater = di
					  .get("updater-element-decorator")
					  .decorate(row.updater);
		  
					if (ficoAccounts.length > 0) {
					  row.ficoAccounts = ficoAccounts;
					}
				  }


				row.promo = di.get('promotion').getAdByKind(kindId);
				compiled[id] = row;
				changed = true;
			} else {
				row = compiled[id];
			}
			var _preorder = {
				kindUser: getKindPreorder(kindId) + '-*',
				kind: getKindPreorder(kindId),
				user: false
			};
			row._preorder = _preorder;
			return changed;
		}

		function buildUserRow(userId) {
			var id = 'user-' + userId,
				changed = false,
				row;
			if (!Object.prototype.hasOwnProperty.call(owners, userId)) {
				owners[userId] = 1;
			} else {
				owners[userId]++;
			}
			return changed;
		}

		function buildKindUserRow(kindId, userId) {
			var id = 'kind-user-' + kindId + '-' + userId,
				changed = false,
				row;
			if (!Object.prototype.hasOwnProperty.call(compiled, id)) {
				row = angular.extend({}, Row);
				row.ID = id;
				row.type = 'kind-user';
				row.fields = di.get('agents').getAgent(userId);
				compiled[id] = row;
				changed = true;
			} else {
				row = compiled[id];
			}
			var _preorder = {
				kindUser: getKindUserPreorder(kindId, userId),
				kind: false,
				user: false
			};

			row._preorder = _preorder;
			return changed;
		}

		function getKindPreorder(kindId) {
			var item = di.get('kinds').getKind(kindId) || {order: kindId};
			return pad(item.order, 3);
		}

		function getUserPreorder(userId) {
			var item = di.get('agents').getAgent(userId) || {order: userId};
			return pad(item.order, 10);
		}

		function getKindUserPreorder(kindId, userId) {
			return getKindPreorder(kindId) + '-' + getUserPreorder(userId);
		}

		var self = {
			compile: function (accounts, full) {
				full = full || true;
				if (full) {
					owners = {};
					var newAccounts = [];
					oldCompiled = compiled;
					compiled = {};
					angular.forEach(accounts, function (/** AccountData */ element) {
						newAccounts.push(element.FID)
					});
					angular.forEach(oldCompiled, function (/** AccountData */ element, FID) {
						if (newAccounts.indexOf(FID) === -1) {
							delete oldCompiled[FID];
						}
					});
				}
				var cnt = 0;
				couponCount = 0;
				angular.forEach(accounts, function (/** AccountData */ account) {
					if (!limit || (limit && cnt < limit)) {
						var items = [];
						account.type = account.TableName == 'Account' ? 'account' : 'coupon';
						//if (account.TableName != 'Account') couponCount++;
						items.push(account);
						if (account.SubAccountsArray) {
							angular.forEach(account.SubAccountsArray, function (subaccount) {
								subaccount.type = 'subaccount';
								couponCount++;
								items.push(subaccount);
							})
						}

                        if (account.ConnectedCoupons) {
                            angular.forEach(account.ConnectedCoupons, function (coupon) {
                                coupon.type = 'coupon';
                                coupon.subtype = 'connected';
                                items.push(coupon);
                            })
                        }

						buildRow(items);
						cnt++;
					}
				});
				di.get('counters').setOwners(owners);
				di.get('counters').setCoupons(couponCount);
				return compiled;
			},
			getList: function () {
				return compiled;
			},
			getCouponCount: function () {
				return couponCount;
			}
		};
		return self;
	}

	var service = angular.module('listCompilerService', ['ficoServiceModule']);

	service.provider('ListCompiler',
		function () {
			var limit = false;

			return {
				setElementsLimit: function (data) {
					limit = data;
				},
				$get: [
					'DI', 'ficoService',
					function (di, ficoService) {
						return new ListCompiler(di, ficoService, limit);
					}
				]
			};
		});


});
