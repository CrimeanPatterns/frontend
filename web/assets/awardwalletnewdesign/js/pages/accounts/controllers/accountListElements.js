define([
	'angular-boot', 'jquery-boot', 'lib/utils', 'extension-client/bundle', 'lib/autologinV3',
	'pages/accounts/services/details'
], function (angular, $, utils, bundle, autologinV3) {
	angular = angular && angular.__esModule ? angular.default : angular;

    var weHaveV2Extension = false;

    browserExt.pushOnReady(function(){
        console.log('we have v2 extension');
        weHaveV2Extension  =true;
    });

    var extensionClient = new bundle.DesktopExtensionInterface();

    function getUserLocale() {
        const input = $('a[data-target="select-language"]');
        if ("undefined" === typeof input
            || null === input
            || 0 === input.length
        ) {
            return $("html").attr("lang").substr(0, 2);
        }
        const userRegion = input.attr("data-region");
        const userLanguage = input.attr("data-language");

        if (userLanguage && userRegion) {
            return userLanguage.substring(0, 2) + "_" + userRegion.substring(0, 2);
        } else if (userRegion || userLanguage) {
            return userRegion || userLanguage;
        } else {
            return null;
        }
    }

    angular.module("accountListElementsModule", [
		'appConfig', 'duScroll',
		'customizer-directive', 'dialog-directive', 'highlight-mod', 'unsafe-mod',
		'ficoServiceModule'
	])
		.controller('listCtrl', [
			'$scope', '$state',
			'DI', 'ListConfig', 'detailsService',

			function ($scope, $state,
			          di, config, detailsService) {
				const ListManager = di.get('manager');

				const list = this;

				let userLocale = getUserLocale();

                if(userLocale){
                    const supportedLocales = Intl.NumberFormat.supportedLocalesOf(userLocale.substring(0, 2));
                    userLocale = supportedLocales.length ? supportedLocales[0] : null;
                }

                const formatter = userLocale ? new Intl.NumberFormat(userLocale, {
                    maximumFractionDigits: 0
                }) : new Intl.NumberFormat();

                list.numberToLocaleString = function (balance) {
					return formatter.format(balance);
                };

				const formatterCurrency = new Intl.NumberFormat(userLocale, { style: 'currency', currency: 'USD' });
				list.formatCurrency = function(value, decimal) {
					if (undefined === value) {
						return '';
					}
					value = Math.floor(value);
					if (undefined !== decimal && 0 == decimal) {
						return new Intl.NumberFormat(userLocale, { style: 'currency', currency: 'USD', maximumFractionDigits: 0 }).format(value);
					}
					return formatterCurrency.format(value);
				};

                list.elements = [];
				list.totals = [];

				list.details = detailsService;

				list.updating = di.get('updater-manager').getState();

				// todo move to main
				list.totals = di.get('counters').getTotals();

				this.view = {
					order: 'DisplayName',
					reverse: false,
					showOwner: true,
					showErrors: true,
					search: false,
					sort: function (order) {
						this.reverse = (this.order == order) ? !this.reverse : false;
						this.order = order;
						$.cookie('account_sort_order', this.order);
						$.cookie('account_sort_reverse', this.reverse);
						ListManager.setOrder(this.order, this.reverse);
						ListManager.build();
					},
					getItems: function() {
						return Translator.transChoice(/** @Desc("{0}accounts|{1}account|[2,Inf]accounts") */ 'accounts.items', list.totals.viewTotal);
					}
				};

				var order = ListManager.getOrder();
				this.view.order = order.column;
				this.view.reverse = order.reverse;
				list.isBusiness = config.isBusiness;

				if (!config.isBusiness) {
					$scope.$watch(function () {
							return ListManager.getGroupType();
						},
						function (data, oldData) {
							list.view.showOwner = data;
						});
					list.loadMore = ListManager.loadMore;
				} else {
					list.pager = ListManager.getPager();
					list.setActivePage = function(page) {
						//ListManager.setPage(page);
						//ListManager.build();
						di.get('filters').restore();
                        di.get('element-updater-manager').stop();
                        di.get('element-updater-manager').end();
						$state.go('list', {page: page});
					};
					list.filters = di.get('filters').getFilters();
					list.clearFilters = function () {
						di.get('filters').clear();
						list.goFilters();
					};
					list.hasFilters = function(){
						return $.trim(list.filters.program) != ''
							|| $.trim(list.filters.owner) != ''
							|| $.trim(list.filters.account) != ''
							|| $.trim(list.filters.status) != ''
							|| $.trim(list.filters.balance) != ''
							// || $.trim(list.filters.cashequivalent) != ''
							|| $.trim(list.filters.expire) != ''
							|| $.trim(list.filters.lastupdate) != '';
					};
					list.goFilters = function () {
						var data = list.filters;
						di.get('filters').store();
						$state.go('list', {
							page: 1,
							filterProgram: data.program,
							filterOwner: data.owner,
							filterFamilyMemberName: data.owner,
							filterAccount: data.account,
							filterStatus: data.status,
							filterBalance: data.balance ? parseInt(data.balance) : null,
							// filterCashequivalent: data.cashequivalent ? parseInt(data.cashequivalent) : null,
							filterExpire: data.expire
                                ? Math.floor(Date.UTC(
                                    $('#filterExpire_datepicker').datepicker('getDate').getFullYear(),
                                    $('#filterExpire_datepicker').datepicker('getDate').getMonth(),
                                    $('#filterExpire_datepicker').datepicker('getDate').getDate()
                                ) / 1000)
                                : null,
							filterLastUpdate: data.lastupdate
                                ? Math.floor(Date.UTC(
                                    $('#filterLastUpdate_datepicker').datepicker('getDate').getFullYear(),
                                    $('#filterLastUpdate_datepicker').datepicker('getDate').getMonth(),
                                    $('#filterLastUpdate_datepicker').datepicker('getDate').getDate()
                                ) / 1000)
                                : null
						});
					};
					list.keyPress = function(event){
						if(event.keyCode == 13)
							list.goFilters();
					};
				}

				$scope.$watch(function () {
						return di.get('manager').getSearch();
					},
					function (data, oldData) {
						list.view.search = !!data;
					});

				list.elements = ListManager.getElements();

			}])
		.controller('elementCtrl', [
			'$scope', '$sce',
			'DI', 'dialogService', 'detailsService', '$document', '$timeout', '$state', 'ListConfig', '$http', 'ListData', 'ficoService',
            /**
             *
             * @param $scope
             * @param $sce
             * @param di
             * @param dialogService
             * @param detailsService
             * @param $document
             * @param $timeout
             */
			function ($scope, $sce,
			          di, dialogService, detailsService, $document, $timeout, $state, config, $http, ListData, ficoService) {
				var element = this;

				$scope.$watch('element.row.fields.ChangesConfirmed', function(){
                    element.changesConfirmed = element.row.fields.ChangesConfirmed;
				});

				element.row = $scope.$parent.row;
				/**
				 * depth matters! ng-repeat / ng-if
				 * @type {integer}
				 */
				element.id = element.row.ID;
				/**
				 * @type {AccountData}
				 */
				element.account = element.row.fields;

				element.changesConfirmed = element.row.fields.ChangesConfirmed;

				element.check = element.row.checker;

				element.updater = element.row.updater;

				element.updating = di.get('element-updater-manager').getState();

				/**
				 *
				 * @type {(boolean|string)}
				 */
				element.search = false;
				/**
				 * @type {Object.<string, string>}
				 */
				element.decorate = element.row._decorate;

				element.details = detailsService;

				element.show = true;

				element.mileValueSet = function(account) {

					function mileValueSave() {
						const $field = $('#customAverageValue');
						let value = utils.reverseFormatNumber($field.val());
						if (null !== value) {
							value = parseFloat(value).toFixed(2);
						}

						$field.prop('readonly', true);
						$http.post(Routing.generate('aw_points_miles_userset'), {
							'providerId': account.ProviderID,
							'accountId': element.account.ID,
							'source': 'cashEquivalent',
							'value': value,
						})
							.then((response) => {
								if (response.data.success) {
                                    element.account.USDCashMileValue = true;
                                    element.account.MileValue = response.data.MileValue;
								} else if ('string' === typeof response.data.error) {
									dialogService.fastCreate(Translator.trans('status.error-occurred'), response.data.error, false, true, [], 500);
								}
							});
						return true;
					}

                    if (undefined === account.ProviderCurrency) {
						account.ProviderCurrency = Translator.transChoice('award.account.list.totals.points', 1).toLowerCase();
						account.ProviderCurrencies = Translator.transChoice('award.account.list.totals.points', 2).toLowerCase();
					}

					const valueOf = Translator.trans('value-of', {
						'name': account.DisplayName,
						'currency': account.ProviderCurrency,
					});
					const mvDialog = dialogService.fastCreate(
						utils.escape(account.DisplayName),
						Translator.trans(
							/** @Desc("To calculate the value of your %providerName% %currencies%, please specify what a single %providerName% %currency% is worth to you in US cents.") */'calculate-value-your-provider',
							{
								'providerName': utils.escape(account.DisplayName),
								'currency': account.ProviderCurrency,
								'currencies': account.ProviderCurrencies,
							}
						) + `<div class="milevalue-set-wrap">
								<div class="mv-label">${valueOf}:</div>
								<div class="curr-block">
									<input id="customAverageValue" type="text" value="">
									<div class="curr-block__val">¢</div>
								</div>
                            </div>`,
						true,
						false,
						[
							{
								text: Translator.trans('form.button.save'),
								click: function() {
									mileValueSave();
									$(this).dialog('close');
								},
								'class': 'btn-blue'
							}
						],
						555
					);

					const viewOrSetMV = Translator.trans(/** @Desc("View or set other %currency% values") */'view-or-set-milevalue', {
						'currency': account.ProviderCurrency,
					});
					$(mvDialog.element).closest('.ui-dialog').addClass('mileValue-set-dialog')
						.find('.ui-dialog-buttonset')
						.prepend(`<div class="dialog-footer"><b><a class="blue-link" href="${Routing.generate('aw_points_miles_values')}">${viewOrSetMV} <i class="icon-double-arrow-right-blue"></i></a></b></div>`)
						.end()
						.find('#customAverageValue')
						.keypress(utils.digitFilter)
						.keypress(function(event) {
							if (13 === event.keyCode) {
								mileValueSave();
								mvDialog.close();
							}
						});

				};

				$scope.$watch(function () {
					if (config.isBusiness) {
						let backTo = Routing.generate('aw_account_list') + '/?' + ('Account' === element.account.TableName ? 'account' : 'coupon') + '=' + element.account.ID;
						let filters = di.get('filters').getFilters();
						for (let i in filters)
							if (filters[i] && filters[i].length)
								backTo += '&filter' + (i[0].toUpperCase() + i.slice(1)) + '=' + filters[i];

						element.account.EditLinkTmp = element.account.EditLink
							+ (-1 === element.account.EditLink.indexOf('?') ? '?' : '&')
							+ 'backTo=' + encodeURIComponent(backTo);
					}
						return di.get('manager').getSearch();
					},
					function (data, oldData) {
						element.search = data;
					});

				this.view = {
					showError: false,
					updating: false,
					updateInProgress: function(){
						return !di.get('updater').isDone();
					},
					selectOne: function (event) {
                        $timeout(function () {
                            di.get('checker-manager').checkOne(element.id, event);
                        });
					},
					// todo move to extenders
					isHiddenDate: function (subAccount = null) {
						return di.get('accounts').isHiddenDate(element.account, subAccount);
					},
					errorInfo: function ($event) {
						$event.preventDefault();
						$event.stopPropagation();
						dialogService.fastCreate(
							Translator.trans(/** @Desc("Hide Errors") */'account.hide-errors.popup.title'),
							Translator.trans(/** @Desc("If you wish to hide all of your account errors from the list please select &quot;Hide Errors&quot; from the &quot;Views&quot; menu at the top.") */'account.hide-errors.popup.1')
							+ '<br/><br/>' +
							Translator.trans(
								/** @Desc("If you wish to just get rid of this one error you can (a) try to fix this account by <a href='%editLink%' target='_blank'>updating your credentials</a> or (b) you can <a href='%editLink%' target='_blank'>mark this account as &quot;Disabled&quot;</a>.") */
								'account.hide-errors.popup.2',
								{
									editLink: element.account.EditLink
								})
							,
							true,
							false,
							[
								{
									text: Translator.trans('button.ok'),
									click: function () {
										$(this).dialog('close');
									},
									'class': 'btn-blue'
								}
							],
							500
						);
					},

					autologin: function ($event) {
                        if(
                            !browserExt.supportedBrowser()
                            || browserExt.showInfoBrowser()
                        ){
                            // allow to open link in new window
                            return;
                        }


						if (autologinV3.initAutologin(element.account.ID, element.account.Access && element.account.Access.autologinExtensionV3, $event, dialogService)) {
            				return;
        				}

                        function showAutologinDialog()
                        {
                            var dialog = dialogService.get("autologin-popup");
                            dialog.setOption("close", function () {
                                browserExt.cancel();
                            });
                            dialog.setOption("title", Translator.trans('button.autologin'));
                            dialog.setOption("buttons", [
                                {
                                    'text': Translator.trans('alerts.btn.cancel'),
                                    'click': function () {
                                        browserExt.cancel();
                                        $(this).dialog("close");
                                    },
                                    'class': 'btn-blue',
                                    tabindex: -1
                                }
                            ]);
                            dialog.open();
                        }

                        function autoLoginV2ById(id) {
                            showAutologinDialog();
                            browserExt.requireValidExtension();
                            browserExt.pushOnReady(function () {
                                browserExt.autoLoginAccountById(
                                    id,
                                    function () { // onComplete
                                        $('#autologin-popup').dialog('close');
                                    },
                                    function () { // onError
                                        $('#autologin-popup').dialog('close');
                                    }
                                    //function(data){ // onRequirePassword
                                    //	$('#autologin-popup').dialog('close');
                                    //}
                                );
                            });
                        }

                        if (weHaveV2Extension) {
                            autoLoginV2ById(element.account.ID);
                            $event.preventDefault();
                        }
                        // otherwise just redirect
					},

					// todo move to extenders
					showPopupExpiration: function (kind) {
                        if ('undefined' !== typeof kind && 11 === parseInt(kind)) {
                            let date = Object.prototype.hasOwnProperty.call(element.account, 'ExpirationDateYMD') && element.account.ExpirationDateYMD
                                ? new Date(Date.parse(element.account.ExpirationDateYMD.split('+')[0]))
                                : new Date(1000 * element.account.ExpirationDateTs);

                            element.account.ExpirationDetails = Translator.trans(/** @Desc("You've indicated that this passport expires on %date%") */'indicated-that-passport-expires', {
                                'date': new Intl.DateTimeFormat(getUserLocale().substring(0, 2), {dateStyle: 'long'}).format(date),
                            });
                            element.account.ExpirationBlogPost = {
                                Text: element.account.ExpirationBlogPost.title,
                                Image: element.account.ExpirationBlogPost.imageURL,
                                Link: element.account.ExpirationBlogPost.postURL,
                            };
                        }

						let dialog = dialogService.get('expiration-warning-action'),
							dialogText = dialog.element.find('#expiration-warning-text'),
							dialogLink = dialog.element.children('a.blog-post-link');

						dialog.setOption('title', Translator.trans(/** @Desc("Expiration warning") */ 'account.expiration.popup.title'));
						dialogText.html(element.account.ExpirationDetails);

						if (Object.prototype.hasOwnProperty.call(element.account, 'ExpirationBlogPost')) {
							let blogPost = element.account.ExpirationBlogPost;
							dialogLink.attr('href', blogPost.Link);
							dialogLink.children('.link-text').html(blogPost.Text);

							if (blogPost.Image === null) {
								dialogLink.children('.link-image').css('background-image', 'none');
								dialogLink.filter('#expiration-link-text').show();
								dialogLink.filter('#expiration-link-image').hide();
							} else {
								dialogLink.children('.link-image').css('background-image', 'url(\'' + blogPost.Image + '\')');
								dialogLink.filter('#expiration-link-text').hide();
								dialogLink.filter('#expiration-link-image').show();
							}
						} else {
							dialogLink.hide();
						}

						dialog.setOption('buttons', [
							{
								'text': Translator.trans('button.ok'),
								'click': function () {
									$(this).dialog('close');
								},
								'class': 'btn-blue'
							}
						]);
						dialog.open(true);
					},
					doneUpdate: function (params) {
						di.get('updater-manager').elementDone(element.id);
						di.get('element-updater-manager').elementDone(element.id);
						this.confirmChanges();
                        element.updater.state = false;
                        for (var i in element.account.SubAccountsArray) {
                        	if (element.account.SubAccountsArray[i].LastBalanceRaw && element.account.SubAccountsArray[i].StateBar && -1 !== ['inc', 'dec'].indexOf(element.account.SubAccountsArray[i].StateBar))
		                        element.account.SubAccountsArray[i].ChangedOverPeriodPositive = element.account.SubAccountsArray[i].BalanceRaw > element.account.SubAccountsArray[i].LastBalanceRaw;
                        }
                        if (typeof params === 'object' && Object.prototype.hasOwnProperty.call(params, 'buttonLink')) {
                            const {buttonLink} = params;
                            var link = buttonLink;

                            if (link.indexOf(document.location.origin) !== 0) {
                                link = new URL(`${document.location.origin}${buttonLink}`);
                                if (!link.searchParams.has('BackTo')) {
                                    link.searchParams.append('BackTo', document.location.href.replace(document.location.origin, ''));
                                }
                            }
                            document.location.href = link;
                        }
                    },
					cancelUpdate: function () {
						di.get('element-updater-manager').stop();
						di.get('element-updater-manager').end();
					},
					update: function () {
						if (di.get('updater').isDone()) {
							di.get('element-updater-manager').start(element.id);
						}
					},
					confirmChanges: function(scrollToNext){
                        element.row.fields.ChangesConfirmed = true;

                        if(window.impersonated)
                        	return;

                        $.ajax({
                            url: Routing.generate('aw_account_json_confirm_changes'),
                            type: 'POST',
                            data: {ids: [element.id]}
                        });

                        if(!scrollToNext) return;

                        // update summary
                        $scope.$emit('account.changesConfirmed');

                        //scroll to next unconfirmed account if available
						var accounts = di.get('accounts').getAccounts();
                        var nextElements = [];
                        Object.keys(accounts).forEach(function(id){
                            var account = accounts[id];
                            var row = $('#' + id);
                            if(
                            	!account.ChangesConfirmed &&
								account.ID !== element.id &&
                                row.length
							){
                                nextElements.push(row);
							}
                        });

						if(nextElements.length){
							var currentOffset = $('#' + element.id).offset().top;

                            //sort elements by top offset
                            nextElements.sort(function (a, b) {
                                return a.offset().top - b.offset().top;
							});

                            // scroll to lower unconfirmed account if available
                            for(var i=0; i<nextElements.length; i++){
                                var next = nextElements[i];
                                if(next.offset().top > currentOffset){
                                    $document.scrollToElement(next, 350, 300);
                                    return;
                                }
							}

                            // scroll to visible unconfirmed account if lower accounts not found
                            $document.scrollToElement(nextElements[0], 350, 300);
						}

					}
				};

			}])
			.controller('kindRowCtrl', [
				'$scope', '$sce', 'DI', 'dialogService', '$timeout', 'ficoService',
				function ($scope, $sce, di, dialogService, $timeout, ficoService) {
					var kindRow = this;
			
					kindRow.row = $scope.$parent.row;
					kindRow.id = kindRow.row.ID;
					kindRow.shouldShow = true;
					
					function updateVisibility() {
						var hasSearch = di.get('manager').getSearch();
						var recentFilter = di.get('filtrator') ? di.get('filtrator').getValue('recent') : null;
						var currentAgentId = di.get('filtrator') ? di.get('filtrator').getValue('agentId') : null;
						
						kindRow.shouldShow = !hasSearch && !recentFilter;

						if (kindRow.row.ficoAccounts && kindRow.row.ficoAccounts.length > 0) {
							kindRow.visibleFicoAccounts = [...kindRow.row.ficoAccounts];

							if (!currentAgentId || currentAgentId === 'my') {
								kindRow.visibleFicoAccounts = kindRow.row.ficoAccounts.filter(function(account) {
									return account.account && account.account.AccountOwner === 'my';
								});
							} else {
								kindRow.visibleFicoAccounts = kindRow.row.ficoAccounts.filter(function(account) {
									return account.account && account.account.AccountOwner === Number(currentAgentId);
								});
							}
							
						} else {
							kindRow.visibleFicoAccounts = [];
						}
					}

					$scope.$watch(function () {
						return di.get('manager').getSearch();
					}, function () {
						updateVisibility();
					});

					$scope.$watch(function () {
						if (!di.get('filtrator')) return null;
						return di.get('filtrator').getValue('agentId');
					}, function () {
						updateVisibility();
					});

					$scope.$watch(function () {
						if (!di.get('filtrator')) return null;
						return di.get('filtrator').getValue('recent');
					}, function () {
						updateVisibility();
					});
					
					kindRow.updateFicoState = function() {
						if (kindRow.row.ficoAccounts && kindRow.row.ficoAccounts.length > 0) {
							var anyChanged = false;
							
							kindRow.row.ficoAccounts.forEach(function(account) {
								var accountId = account.accountId;
								
								var isUpdating = ficoService.isAccountUpdating(accountId);
								
								if (account.isUpdating !== isUpdating) {
									account.isUpdating = isUpdating;
									anyChanged = true;
								}
							});
							
							if (anyChanged) {
								var newFicoAccounts = kindRow.row.ficoAccounts.map(function(account) {
									return Object.assign({}, account);
								});
								
								kindRow.row.ficoAccounts = newFicoAccounts;
								
								if (!$scope.$$phase) {
									kindRow.row.ficoAccounts.forEach((account) =>{
										console.log('Account balance: ',account.balance, 'is updating:', account.isUpdating);

									})
									
									
									$scope.$apply();
								}
							}
						}
					};

					
					$scope.$watch(function() {
						if (!kindRow.row.ficoAccounts || !kindRow.row.ficoAccounts.length) {
							return null;
						}
						
						var statusStr = '';
						
						kindRow.row.ficoAccounts.forEach(function(account) {
							var accountId = account.accountId;
							var accountEl = document.getElementById('a' + accountId);
							
							if (accountEl) {
								var scope = angular.element(accountEl).scope();
								if (scope && scope.element && scope.element.updater) {
									var visualState = scope.element.updater.visualState || '';
									var generalState = scope.element.updater.state || false;
									
									var isAccountUpdating = 
										visualState === 'queue' || 
										visualState === 'checking' || 
										generalState === true || 
										generalState === 'queue' || 
										generalState === 'checking';
									
									ficoService.setAccountUpdating(accountId, isAccountUpdating);
									
									statusStr += accountId + ':' + visualState + ',' + generalState + ';';
								}
							}
						});
					
						
						return statusStr;
					}, function(newStatus, oldStatus) {
						if (newStatus !== oldStatus) {
							$timeout(function() {
								kindRow.updateFicoState();
							});
						}
					});	
				}
			]);

});
