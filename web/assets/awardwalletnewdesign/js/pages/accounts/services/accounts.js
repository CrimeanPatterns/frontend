define(['angular', 'lib/customizer', 'dateTimeDiff', 'pages/accounts/services/cache', 'routing'], function (angular, customizer, dateTimeDiff) {
	angular = angular && angular.__esModule ? angular.default : angular;

	var service = angular.module('accountsService', ['cacheService']);

	function Accounts($http, $q, $filter, di, accountInfoCache) {
		var defer = $q.defer();

		/**
		 * @type {(AccountData[]|Object.<string, AccountData>)}
		 */
		var accounts = {}, oldAccounts = {};
		/**
		 * @type {AccountData}
		 */
		var lastUpdated = {};
		var accountsLimit = false;
		var accountsCount = false;

		const ALLOW_VISIBLE_ATTR_LIMIT = 3;
		var hiddenCache = {
			accountsWithVisibleAttr : []
		};

		/**
		 *
		 * @param {AccountData[]}data
		 */
		function processAccounts(data) {
			var hideCoupons = [];

            angular.forEach(data, function (coupon) {
                if(coupon.TableName !== 'Coupon' || !coupon.ConnectedAccount)
                    return;

                var connectedAccount = data.filter(function (item) {
                    return item.TableName === 'Account' && +item.ID === +coupon.ConnectedAccount;
                });

                if (connectedAccount.length){
                    connectedAccount[0].ConnectedCoupons = connectedAccount[0].ConnectedCoupons || [];

                    if (!coupon.IsArchived) {
                        connectedAccount[0].ConnectedCoupons.push(processAccount(coupon));
                        hideCoupons.push(coupon.FID);
                    }
                }
			});

			angular.forEach(data, function (/** AccountData */ account) {
				if (angular.isObject(account) && Object.prototype.hasOwnProperty.call(account, 'FID')) {
					if (Object.prototype.hasOwnProperty.call(oldAccounts, account.FID)) {
						accounts[account.FID] = oldAccounts[account.FID];
					}

					if(hideCoupons.indexOf(account.FID) > -1)
						return;

					if (Object.prototype.hasOwnProperty.call(accounts, account.FID)) {
						account = processAccount(account);
						var SubAccountsArray = [];
						if (Object.prototype.hasOwnProperty.call(account, 'SubAccountsArray') && angular.isArray(account.SubAccountsArray)) {
							SubAccountsArray = account.SubAccountsArray;
							var accountCopy = Object.assign({}, account);
							delete accountCopy.SubAccountsArray;
							angular.merge(accounts[account.FID], accountCopy);
						} else {
							angular.merge(accounts[account.FID], account);
						}

						if (!Object.prototype.hasOwnProperty.call(account, 'Shares')) {
							accounts[account.FID].Shares = [];
						} else {
							accounts[account.FID].Shares = account.Shares;
						}
						if (Object.prototype.hasOwnProperty.call(accounts[account.FID], 'SubAccountsArray') &&
							angular.isArray(accounts[account.FID].SubAccountsArray) &&
							accounts[account.FID].SubAccountsArray.length
						) {
							var result = [];
							angular.forEach(accounts[account.FID].SubAccountsArray, function (subAccount) {
								var newSubAccount = SubAccountsArray.filter(function(sa) {return sa.SubAccountID == subAccount.SubAccountID;});
								if (angular.isArray(newSubAccount) && newSubAccount.length) {
									angular.merge(subAccount, newSubAccount[0]);
									result.push(subAccount);
								}
							});
							angular.forEach(SubAccountsArray, function (subAccount) {
								var existsSubAccount = result.filter(function(sa) {return sa.SubAccountID == subAccount.SubAccountID;});
								if (!(angular.isArray(existsSubAccount) && existsSubAccount.length)) {
									result.push(subAccount);
								}
							});
							accounts[account.FID].SubAccountsArray = result;
						} else {
							accounts[account.FID].SubAccountsArray = SubAccountsArray;
						}

                        for (let i in accounts[account.FID].SubAccountsArray) {
                            if (accounts[account.FID].SubAccountsArray[i].BalanceRawState === accounts[account.FID].SubAccountsArray[i].BalanceRaw) {
                                accounts[account.FID].SubAccountsArray[i].ChangedOverPeriodPositive = null;
                            } else {
                                accounts[account.FID].SubAccountsArray[i].BalanceRawState = accounts[account.FID].SubAccountsArray[i].BalanceRaw;
                            }
                        }

					} else {
						accounts[account.FID] = processAccount(account);
                        if (Object.prototype.hasOwnProperty.call(accounts[account.FID], 'SubAccountsArray') && angular.isArray(accounts[account.FID].SubAccountsArray)) {
                            for (let i in accounts[account.FID].SubAccountsArray) {
                                accounts[account.FID].SubAccountsArray[i].BalanceRawState = accounts[account.FID].SubAccountsArray[i].BalanceRaw;
                            }
                        }
					}
				}
			});
		}

		/**
		 *
		 * @param {AccountData} account
		 * @returns {AccountData}
		 */
		function processAccount(account) {
			var subaccount, k;
			// document kind
            if (account.TableName === 'Coupon' && +account.Kind === 11) {
                account.EditLink = Routing.generate('aw_document_edit', {documentId: account.ID});
                account.isDocument = true;
                account.document = account.CustomFields || {};
				account.document.isVaccineCard = Object.prototype.hasOwnProperty.call(account.document, 'vaccineCard');
				account.document.isInsuranceCard = Object.prototype.hasOwnProperty.call(account.document, 'insuranceCard');
				account.document.isVisa = Object.prototype.hasOwnProperty.call(account.document, 'visa');
				account.document.isDriversLicense = Object.prototype.hasOwnProperty.call(account.document, 'driversLicense');
				account.document.isPriorityPass = Object.prototype.hasOwnProperty.call(account.document, 'priorityPass');
				account.document.formattedName = account.DisplayNameFormated;

                if(account.document.passport){
                    account.document.passport.issueDate =  account.document.passport.issueDate && new Date( account.document.passport.issueDate.date);
				}else{
                    account.document.formattedName = account.DisplayNameFormated;

					if (account.document.isVaccineCard) {
						account.document.status = account.document.vaccineCard.status;
					} else if (account.document.isInsuranceCard) {
						account.document.status = account.document.insuranceCard.status;
					} else if (account.document.isVisa) {
						account.document.status = account.document.visa.status;
					} else if (account.document.isDriversLicense) {
						account.document.status = account.document.driversLicense.status;
					} else if (account.document.isPriorityPass) {
						account.document.status = account.document.priorityPass.status;
					}
				}
			}else if (account.TableName === 'Coupon') {
				account.EditLink = Routing.generate('aw_coupon_edit', {couponId: account.ID});
				account.extendedStatus = account.AccountStatus;
                if (account.LoginFieldFirst === account.LoginFieldLast) {
                    account.LoginFieldLast = '';
                } else if (account.LoginFieldLast && account.LoginFieldLast !== account.LoginFieldFirst) {
                    account.extendedStatus += ` (${account.LoginFieldLast})`;
                }
			}else{
				account.EditLink = Routing.generate('aw_account_edit', {accountId: account.ID});
			}
			account.RedirectLink = Routing.generate('aw_account_redirect', {ID: account.ID});
			if (account.LoginFieldLast) {
				account.LoginFieldLast = account.LoginFieldLast.substring(0, 100);
			}
			if (account.AccountOwner && 'my' !== account.AccountOwner)
                account.EditLink += (-1 === account.EditLink.indexOf('?') ? '?' : '&') + 'agentId=' + account.AccountOwner;

			if (account['SubAccountsArray']) {
				for (k = 0; k < account.SubAccountsArray.length; k++) {
					subaccount = account.SubAccountsArray[k];
					angular.extend(subaccount, processExpirationTips(subaccount));
					subaccount.RedirectLink = Routing.generate('aw_account_redirect', {
						ID: account.ID,
						SubAccountID: subaccount.SubAccountID
					});
					subaccount.updater = {
                        changeText   : Translator.trans('award.account.list.updating.changed', {
                            'lastBalance' : subaccount.LastBalance,
                            'balance'     : subaccount.Balance,
                            'lastChange'  : subaccount.LastChange,
	                        'changeClass' : subaccount.ChangedOverPeriodPositive ? 'up' : 'down'
                        }),
                        unchangeText : Translator.trans('award.account.list.updating.unchanged', {'balance' : subaccount.Balance})
					};
				}
			}
			angular.extend(account, processExpirationTips(account));
			account.Goal = parseInt(account.Goal);
			if (isNaN(account.Goal)) account.Goal = null;
			if (account.Goal) {
                var userLocale = $('a[data-target="select-language"]').attr('data-region') ||
                    $('a[data-target="select-language"]').attr('data-language') ||
                    null;

                if(userLocale){
                    var supportedLocales = Intl.NumberFormat.supportedLocalesOf(userLocale.substring(0, 2));
                    userLocale = supportedLocales.length ? supportedLocales[0] : null;
                }

                var formatter =  userLocale ? new Intl.NumberFormat(userLocale, {
                    maximumFractionDigits: 0
                }) : new Intl.NumberFormat();
				account.GoalTip = Translator.trans('award.account.list.goal.progress-tip', {
					'progress': account.GoalProgress,
					'goal': formatter.format(account.Goal)
				});
			}

			if (Object.prototype.hasOwnProperty.call(account, 'ExpirationDateTs') && account.ExpirationDateTs && true === account.ExpirationKnown && -1 === [1,3].indexOf(account.ExpirationStateType)) {
				let date = Object.prototype.hasOwnProperty.call(account, 'ExpirationDateYMD') && account.ExpirationDateYMD
					? new Date(Date.parse(account.ExpirationDateYMD.split('+')[0]))
					: new Date(1000 * account.ExpirationDateTs);
				account.ExpirationDate = dateTimeDiff.shortFormatViaDateTimes(new Date(), date);
				account.ExpirationDateTip = new Intl.DateTimeFormat(customizer.locales(), {year:'2-digit', month:'2-digit', day:'2-digit'}).format(date);
			}

			if (Object.prototype.hasOwnProperty.call(account, 'LastUpdatedDateTs') && account.LastUpdatedDateTs) {
                let now = new Date();
                let date = new Date(1000 * account.LastUpdatedDateTs);

                if (date.getTime() > now.getTime()) {
                    date = now;
                }

                account.LastUpdatedDate = {};
                account.LastUpdatedDate.date = new Intl.DateTimeFormat(customizer.locales(), {year:'2-digit', month:'2-digit', day:'2-digit'}).format(date);
                account.LastUpdatedDate.datetime = new Intl.DateTimeFormat(customizer.locales(), {year:'2-digit', month:'2-digit', day:'2-digit', hour: '2-digit', minute: '2-digit', second: '2-digit'}).format(date);
                account.LastUpdatedDate.ago = dateTimeDiff.shortFormatViaDateTimes(now, date);

                if (!Object.prototype.hasOwnProperty.call(lastUpdated, 'LastUpdatedDateTs')) {
                    self.setLastUpdated(account);
                } else if (account.LastUpdatedDateTs > lastUpdated.LastUpdatedDateTs) {
                    self.setLastUpdated(account);
                }
			}

            // clear cache
            accountInfoCache.clear(account);

			return account;
		}

		/**
		 *
		 * @param {AccountData} fields
		 * @returns {AccountData}
		 */
		function processExpirationTips(fields) {
			/** @type {AccountData} */
			var result = {};
			if (fields.ExpirationStateType && !fields.ExpirationStateTip) {
				switch (fields.ExpirationStateType) {
					case 1:
						result.ExpirationStateTip = Translator.trans(
							/** @Desc("Good news, the balance on this account does not expire!") */
							'award.account.list.expire.state.notexpire'
						);
						break;
					case 2:
						result.ExpirationStateTip = Translator.trans(
							/** @Desc("As of last account update the expiration date is looking good.") */
							'award.account.list.expire.state.far'
						);
						break;
					case 3:
						result.ExpirationStateTip = Translator.trans(
							/** @Desc("Unfortunately, the expiration date is unknown for this account.") */
							'award.account.list.expire.state.unknown'
						);
						break;
					case 4:
						result.ExpirationStateTip = Translator.trans(
							/** @Desc("Attention, please review your expiration date!") */
							'award.account.list.expire.state.soon'
						);
						break;
					case 5:
						result.ExpirationStateTip = Translator.trans(
							/** @Desc("The expiration date has passed and we are not able to get the new expiration date due to the fact that the account is not updating.") */
							'award.account.list.expire.state.expired-err'
						);
						break;
					case 6:
						result.ExpirationStateTip = Translator.trans(
							/** @Desc("The expiration date has passed and we are not updating it due to the fact that you set this date manually.") */
							'award.account.list.expire.state.expired-manual'
						);
						break;
					default:
						result.ExpirationStateTip = Translator.trans(
							/** @Desc("The expiration date has passed you may not be able to use these miles now.") */
							'award.account.list.expire.state.expired'
						);
						break;
				}
			}
			if (fields.ExpirationModeType && !fields.ExpirationModeTip) {
				switch (fields.ExpirationModeType) {
					case 1:
						result.ExpirationModeTip = Translator.trans(
							/** @Desc("This expiration date was set manually by you, for that reason AwardWallet is not updating this date.") */
							'award.account.list.expire.mode.manual'
						);
						break;
					case 2:
						result.ExpirationModeTip = Translator.trans(
							/** @Desc("You indicated that these %balance% don't expire, for that reason AwardWallet is not updating this date.") */
							'award.account.list.expire.mode.notexpire',
							{
								balance: 'miles/points'
							}
						);
						break;
					case 3:
						result.ExpirationModeTip = Translator.trans(
							/** @Desc("This expiration date was calculated by AwardWallet. Click the date to see the description of how we did it.") */
							'award.account.list.expire.mode.calc'
						);
						break;
					default:
						result.ExpirationModeTip = Translator.trans(
								/** @Desc("This date is not being updated automatically by %name%") */
								'award.account.list.expire.mode.warn',
								{
									name: 'AwardWallet'
								}
						);
						break;
				}
			}
			return result;
		}

		/**
		 * @class accountProvider
		 */
		var self = {
			/**
			 *
			 * @param {AccountData[]} data
			 * @param {boolean=} [partial=false]
			 */
			setAccounts: function (data, partial) {
				partial = partial || false;
				if (!partial) {
					var newAccounts = [];
					oldAccounts = accounts;
					accounts = {};
					angular.forEach(data, function (/** AccountData */ element) {
						if (angular.isObject(element) && Object.prototype.hasOwnProperty.call(element, 'FID')) {
							newAccounts.push(element.FID)
						}
					});
					angular.forEach(oldAccounts, function (/** AccountData */ element, FID) {
						if (newAccounts.indexOf(FID) === -1) {
							delete oldAccounts[FID];
						}
					});
				} else {
					oldAccounts = {};
				}
				processAccounts(data);
				//var updated = [];
				//angular.forEach(data, function (/** AccountData */ element) {
				//	updated.push(element.FID)
				//});
			},
			/**
			 * @returns {AccountData[]|Object.<string, AccountData>}
			 */
			getAccounts: function () {
				return accounts;
			},
			/**
			 * @param data
			 */
			setLastUpdated: function(data) {
				angular.extend(lastUpdated, data);
			},
			/**
			 * @returns {{}}
			 */
			getLastUpdated: function() {
				return lastUpdated;
			},
			setAccountsLimit: function(limit) {
				accountsLimit = limit;
			},
			getAccountsLimit: function() {
				return accountsLimit;
			},
			setAccountsCount: function(limit) {
				accountsCount = limit;
			},
			getAccountsCount: function() {
				return accountsCount;
			},
			/**
			 *
			 * @param id
			 * @param {AccountData} data
			 */
			setAccount: function(id, data) {
				accounts[id] = processAccount(data);
				//$rootScope.$broadcast('account.update');
			},
			/**
			 *
			 * @param id
			 * @returns {?AccountData}
			 */
			getAccount: function(id) {
				self.getAccounts();
				if (Object.prototype.hasOwnProperty.call(accounts, id)) {
					return accounts[id];
				}else{
                    var accountsArray = Object.keys(accounts).map(function (key) { return accounts[key];});

					var connected = accountsArray
						.filter(function(item){ return item.ConnectedCoupons && item.ConnectedCoupons.length;})
						.reduce(function(acc, item){
							item.ConnectedCoupons.forEach(function(item){acc.push(item)});
							return acc;
						}, [])
						.filter(function(item){ return item.FID === id});
					if(connected.length)
						return connected[0];
				}
				return null;
			},
			/**
			 *
			 * @param id
			 */
			removeAccount: function(id) {
				var account = self.getAccount(id);
				if (account) {
                    accountInfoCache.clear(account);
					delete accounts[id];
					//$rootScope.$broadcast('account.remove', [id]);
				}
			},
			/**
			 *
			 * @param ids
			 */
			removeAccounts: function(ids) {
				var removed = [];
				angular.forEach(ids, function (id) {
					var account = self.getAccount(id);
					if (account) {

						if(account.ConnectedCoupons && account.ConnectedCoupons.length){
                            account.ConnectedCoupons.forEach(function (data) {
                                self.setAccount(data.FID, data);
							})
						}

						if(account.TableName === "Coupon"){
                            var accountsArray = Object.keys(accounts).map(function (key) { return accounts[key];});

                            accountsArray
                                .filter(function(item){ return item.ConnectedCoupons && item.ConnectedCoupons.length;})
								.forEach(function(item){
									var connected = item.ConnectedCoupons.filter(function(item){return item.FID === account.FID});
									if(connected.length)
                                        item.ConnectedCoupons.splice(item.ConnectedCoupons.indexOf(connected[0]), 1);
								});
                        }

                        accountInfoCache.clear(account);
						delete accounts[id];
						removed.push(id);
					}
				});
			},
			/**
			 * @deprecated
			 * @returns {*}
			 */
			allNew: function () {
				var defer = $q.defer();
				defer.resolve();
				return defer.promise;
			},

			/**
			 * @deprecated
			 * @param id
			 * @returns {string}
			 */
			getUserName: function (id) {
				var user = di.get('agents').getAgent(id);
				if (user) return user.name;
				return '';
			},

			/**
			 * @deprecated
			 * @param {AccountData} account
			 * @param {SubAccount} subAccount
			 * @returns {*}
			 */
			isHiddenDate: function (account, subAccount) {
				if ((!account.ExpirationKnown && null === subAccount)
					|| (null !== subAccount && !subAccount.ExpirationKnown)
					|| (-1 !== hiddenCache.accountsWithVisibleAttr.indexOf(account.FID))
					|| di.get('user').isAwPlus()) {
					return false;
				}

				if (null === subAccount
					&& account.ExpirationKnown
					&& hiddenCache.accountsWithVisibleAttr.length < ALLOW_VISIBLE_ATTR_LIMIT
					&& -1 === hiddenCache.accountsWithVisibleAttr.indexOf(account.FID)) {
					hiddenCache.accountsWithVisibleAttr.push(account.FID);
					return false;
				}

				return true;
			},

			/**
			 * @deprecated
			 * @param {AccountData} account
			 * @returns {AccountData}
			 */
			processExpirationTips: function (account) {
				return processExpirationTips(account)
			},

			one: function (accountId, subAccountId, canceller) {
				const url = (subAccountId != null)
					? Routing.generate('aw_account_subaccountinfo', {id: accountId, sid: subAccountId})
					: Routing.generate('aw_account_accountinfo', {id: accountId});
                return $http.get(url, {timeout: canceller}).then(res => res.data);
			},

			remove: function (ids) {
				const post = [];
				angular.forEach(ids, function (id) {
					/** @type {AccountData} */
					const account = self.getAccount(id);
					if (account) {
						post.push({
							'id': account.ID,
							'isCoupon': account.TableName === "Coupon",
							'useragentid': account.AccountOwner
						})
					}
				});
				// todo fail!
				return $http.post(Routing.generate('aw_account_json_remove'), post).then(
					res => {
						const data = res.data;
						if (data.success) {
							self.removeAccounts(data.removed);
						}
						return data;
					}
				);
			},

			getProviderAutocomplete: function() {
				var ret = [];
				angular.forEach(self.getAccounts(), function (/** AccountData */ account) {
					if (ret.indexOf(account.DisplayName) == -1) {
						ret.push(account.DisplayName)
					}
				});
				return ret;
			},
			getOwnerAutocomplete: function() {
				var ret = [];
				angular.forEach( di.get('agents').getAgents(), function (/** UserData */ user) {
					if (ret.indexOf(user.name) == -1) {
						ret.push(user.name)
					}
				});
				return ret;
			},
			setFromLoader: function (accounts) {
				self.setAccounts(accounts);
			},
			requiredFromLoader: function () {
				return ['kinds', 'agents'];
			}
		};

		return self;
	}

	service.provider('Accounts',
		function () {
			var accounts;

			return {
				setAccounts: function(data) {
					accounts = data;
				},
				$get: [
					'$http', '$q', '$filter', 'DI', 'accountInfoCache',
					/**
					 * @lends Accounts
					 * @param $http
					 * @param $q
					 * @param $filter
					 * @param di
					 * @param accountInfoCache
					 */
					function ($http, $q, $filter, di, accountInfoCache) {
						var ret = new Accounts($http, $q, $filter, di, accountInfoCache);
						if (accounts) ret.setAccounts(accounts);
						return ret;
					}
				]
			};
		})
});

/**
 * @typedef {Object} AccountData
 * @property {(number|string)} ID
 * @property {string} FID
 * @property {string} TableName
 * @property {string} Balance
 * @property {number} TotalBalance
 * @property {string} ErrorCode
 * @property {string} ProviderCode
 * @property {string} ExpirationDate
 * @property {string} ProviderID
 * @property {string} AutoLogin
 * @property {string} LoginURL
 * @property {integer} DisableClientPasswordAccess
 * @property {string} CanReceiveEmail
 * @property {string} AllianceAlias
 * @property {?string} UpdateDate
 * @property {string} DisplayName
 * @property {?string} LastChangeDate
 * @property {?string} LastChange
 * @property {string} DisplayName
 * @property {string} ChangeCount
 * @property {string} Goal
 * @property {string} EliteLevelsCount
 * @property {string} CheckInBrowser
 * @property {string} IsActiveTab
 * @property {string} StateBar
 * @property {integer} State
 * @property {integer} CanCheck
 * @property {integer} Kind
 * @property {integer} SavePassword
 * @property {integer} LastDurationWithoutPlans
 * @property {real} LastDurationWithPlans
 * @property {?integer} CanSavePassword
 * @property {(integer|string)} AccountOwner
 * @property {integer} SuccessCheckDateTs
 * @property {integer} LastUpdatedDateTs
 * @property {integer} UpdateDateTs
 * @property {integer} LastChangeDateTs
 * @property {integer} BalanceRaw
 * @property {integer} ExpirationDateTs
 * @property {integer} ExpirationStateType
 * @property {integer} ExpirationModeType
 * @property {string} ExpirationState
 * @property {string} ExpirationMode
 * @property {boolean} isCustom
 * @property {boolean} ExpirationKnown
 * @property {boolean} IsActive
 * @property {boolean} IsArchived
 * @property {string} AllianceIcon
 * @property {string} LoginFieldFirst
 * @property {string} LoginFieldLast
 * @property {string} DisplayNameFormated
 * @property {integer} StatusIndicators
 * @property {integer} GoalIndicators
 * @property {integer} GoalProgress
 * @property {*} ExpirationDetails
 * @property {ExpirationBlogPost} ExpirationBlogPost
 * @property {integer} ExpirationModeType
 * @property {AccountAccessData} Access
 * @property {AccountMessageData} ProgramMessage
 * @property {AccountData[]} SubAccountsArray
 * @property {AccountData[]} ConnectedCoupons
 * @property {?string} ConnectedAccount
 * @property {?string} AccountStatus
 * @property {?string} EditLink
 * @property {?string} RedirectLink
 * @property {?string} GoalTip
 * @property {?string} Elitism
 * @property {?string} EliteStatuses
 * @property {?string} ExpirationStateTip
 * @property {?string} ExpirationModeTip
 * @property {?boolean} HasCurrentTrips
 * @property {?boolean} IsShareable
 * @property {?boolean} isDocument
 * @property {Array} Shares
 * @property {?boolean} ChangedOverPeriodPositive
 * @property {?integer} BalanceWatchExpirationDate
 */

/**
 * @typedef {Object} AccountMessageData
 * @property {string} Type
 * @property {string} Title
 * @property {string} BeforeError
 * @property {string} Error
 * @property {string} Description
 * @property {string} DateInfo
 */

/**
 * @typedef {Object} AccountAccessData
 * @property {boolean} read_password
 * @property {boolean} read_number
 * @property {boolean} read_balance
 * @property {boolean} read_extproperties
 * @property {boolean} edit
 * @property {boolean} delete
 * @property {boolean} autologin
 * @property {boolean} update
 * @property {boolean} eligibleUpdate
 * @property {boolean} oneUpdate
 * @property {boolean} groupUpdate
 * @property {boolean} tripsUpdate
 * @property {boolean} editUpdate
 * @property {boolean} autologinExtension
 */

/**
 * @typedef {Object} ExpirationBlogPost данные для ссылки на пост в блоге об истечении баллов
 * @property {string} Text
 * @property {string|null} Link
 * @property {string} Image
 */
