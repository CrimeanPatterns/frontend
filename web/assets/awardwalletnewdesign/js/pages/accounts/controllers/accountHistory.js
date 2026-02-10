define([
	'angular-boot', 'jquery-boot', 'lib/customizer',
	'angular-ui-router',
	'lib/design', 'translator-boot',
	'filters/highlight', 'filters/unsafe', 'filters/htmlencode',
	'directives/customizer', 'directives/dialog', 'angular-scroll',

	'pages/accounts/services/di',
	'pages/accounts/services/checker',
	'pages/accounts/services/listPager',
	'pages/accounts/services/listChecker',
	'pages/accounts/services/listActions',
	'pages/spentAnalysis/ServiceSpentAnalysis'
], function (angular, $, customizer) {
	angular = angular && angular.__esModule ? angular.default : angular;

	var HistoryConfig = {
		accountId: 0,
		total: 0,
		perPage: 100
	};

	var HistoryData = null;

	var HistoryServices = {
		checker: 'Checker',
		pager: 'ListPager'
	};

	// hack for inline note edit
	var _NoteText = '';

	var app = angular.module("accountHistoryApp", [
		'appConfig', 'ui.router', 'duScroll',
		'customizer-directive', 'dialog-directive', 'highlight-mod', 'unsafe-mod', 'htmlencode-mod',

		'diService',
		'checkerService',
		'listPagerService',
		'listActionsService',
		'listCheckerService',
		'SpentAnalysisService'
	]);

	app.config([
		'$injector', 
		function ($injector) {
			if ($injector.has('HistoryConfig')) {
				HistoryConfig = $.extend(HistoryConfig, $injector.get('HistoryConfig'));
			}
		}
	]);

	app.config([
		'$stateProvider', '$urlRouterProvider',
		function ($stateProvider, $urlRouterProvider) {
			$stateProvider
				.state('list', {
					url: '/?page'
				});
			$urlRouterProvider.otherwise("/");
		}
	]);

	app.config([
		'$injector',
		function ($injector) {
			if ($injector.has('HistoryData')) {
				var data = $injector.get('HistoryData');
				if ((typeof (data) == 'object') && (data != null)) {
					HistoryData = $.extend(HistoryData, $injector.get('HistoryData'));
				}
			}
		}
	]);

	app.config([
		'$injector',
		function ($injector) {
			var filters;
		}
	]);

	app.value('HistoryConfig', HistoryConfig);

	// Запуск сервисов
	app.run([
		'$injector', 'DI',
		function ($injector, di) {
			var keys = Object.keys(HistoryServices);
			keys.reverse();
			angular.forEach(keys, function (serviceId) {
				var service = HistoryServices[serviceId];
				if ($injector.has(service)) {
					di.set(serviceId, $injector.get(service));
				} else if (service !== undefined) {
					di.set(serviceId, service);
				}
			});
			di.set('actions-manager', $injector.get('ListActions'));
			di.set('checker-manager', $injector.get('ListChecker'));
		}
	]);

	app
	.controller('mainCtrl', [
		'$scope', '$state', '$stateParams', '$http', '$document', '$timeout', '$window',
		'DI', 'dialogService', '$log', 'SpentAnalysis', '$transitions',
		function (
			$scope,
			$state,
			$stateParams,
			$http,
			$document,
			$timeout,
			$window,
			di,
			dialogService,
			$log,
			SpentAnalysis,
			$transitions
		) {

			var main = this;

            main.$log = $log;

			main.historyItems = {};
			main.historyColumns = {};
			main.historyExtra = {};
			main.historyMiles = false;
			main.balanceCell = [];
			main.checker = {};
			main.ccOfferData = {};
			main.total = HistoryConfig.total;

			main.view = {
				getEditLink: function (k) {
					return Routing.generate('aw_account_history_edit', {'uuid': main.historyExtra[k]['uuid']});
				},
				selectOne: function (event, k) {
					di.get('checker-manager').checkOne(main.historyExtra[k].uuid, event);
				},
				removeItem: function(k) {
					di.get('actions-manager').go('deleteAction', [main.historyExtra[k].uuid]);
				},
				editNote: function(k) {
					_NoteText = main.historyExtra[k].note;
					di.get('actions-manager').go('noteAction', [main.historyExtra[k].uuid]);
				}
			};

			main.getMilesValue = function(item) {
				var milesRow = item[item.length-1];

				if(typeof milesRow.isEp !== 'undefined')
                    milesRow = item[item.length-2];

				return milesRow.value;
			};

			main.showCcOfferPopup = function(data) {
                if (null === (main.ccOfferData = SpentAnalysis.getOfferData(data)))
                	return false;

                var dialog = dialogService.get('credit-card-offer-popup');
                dialog.element.parent().find('.ui-dialog-title').html(main.ccOfferData.title);
                window.dialog = dialog;

                dialog.setOption('buttons', [
                    {
                        text: 'OK',
                        click: function () {
                            dialog.close();
                        },
                        'class': 'btn-blue'
                    }
                ]);

                dialog.open()
			};

			main.uploadHistory = {
                file: null,
                success: null,
				upload : function () {
					$('#uploadHistory').trigger('click');
            	}
        	};

            $scope.historyCellClassName = function(item, row, col) {
            	return SpentAnalysis.transactionRowCss(item, row, col);
            };

            $scope.$watch('main.uploadHistory.file.name', function (oV, nV) {
                if(oV === nV || !main.uploadHistory.file) return;

                var file = main.uploadHistory.file;
                var fd = new FormData();
                var fileName = main.uploadHistory.file.name;

                fd.append('historyFile', file);

                $('.transaction-info.provider').fadeOut({
                    complete: function () {
                        var progressBar = $('.progress-bar');
                        progressBar.fadeIn();
                        progressBar.find('.progress-bar-row p').text(fileName);
                        progressBar.find('.progress-bar-row span').animate({
                            width:'100%'
                        },{
                            easing: 'linear',
                            duration: 2000,
                            complete: function () {
                                progressBar.fadeOut();
                            }
                        });
                    }
                });

                $http.post('/account/history/upload/' + HistoryConfig.accountId, fd, {
                    transformRequest: angular.identity,
                    headers: {'Content-Type': undefined}
                })
                    .then(
                    	res => {
							const data = res.data;
							main.uploadHistory.success = data.success;
							if(!data.success){
								$timeout(() => main.uploadHistory.success = null, 3000);
							}
                    	}
                    )
                    .catch(
						() => {
                            main.uploadHistory.success = false;
                            $timeout(() => main.uploadHistory.success = null, 3000);
                            dialog.close();
						}
					);
            });

            $scope.$watch('main.uploadHistory.success', function (oV, nV) {
                if(oV == nV) return;
                if(main.uploadHistory.success === null)
                    $('.transaction-info.error').fadeOut({
                        complete: function () {
                            $('.transaction-info.provider').fadeIn();
                        }
                    });
                else
                    $('.progress-bar').fadeOut({
                        complete: function () {
                            if(main.uploadHistory.success)
                                $('.transaction-info.success').fadeIn();
                            else
                                $('.transaction-info.error').fadeIn();
                        }
                    });
            });

			main.state = {
				loading: false,
				loaded: false,
				page: 0
			};

			main.tripLink = function(tripId) {
				return Routing.generate('aw_timeline_show_trip', {'tripId': tripId});
			};

			if ((typeof (HistoryData) == 'object') && (HistoryData != null)) {
				main.historyItems = angular.copy(HistoryData.data);
				main.historyColumns = angular.copy(HistoryData.columns);
				main.balanceCell = angular.copy(HistoryData.balance_cell);
				main.historyExtra = angular.copy(HistoryData.extra);
				main.historyMiles = angular.copy(!!HistoryData.miles);
				main.state.loaded = true;
				main.state.page = 1;
			}
			main.stateParams = $stateParams;
			di.get('pager').setPages(Math.ceil(main.total / HistoryConfig.perPage));
			di.get('pager').setPage(parseInt(typeof (main.stateParams.page) === 'undefined' ? 1 : main.stateParams.page));
			main.pager = di.get('pager').getPager();


			var first = true;
            $transitions.onSuccess({}, function () {
				if (first) {
					$("[data-ng-cloak2]").removeAttr('data-ng-cloak2');
					postLoad();
					// fixme by script loading order
					first = false;
				}
				di.get('pager').setPage(parseInt(typeof (main.stateParams.page) === 'undefined' ? 1 : main.stateParams.page));
				if (di.get('pager').getPage() != main.state.page) {
					main.state.page = di.get('pager').getPage();
					load(di.get('pager').getPage());
					// new total from loaded data
					di.get('pager').setPages(Math.ceil(main.total / HistoryConfig.perPage));
					// page may change in pager service
					if (di.get('pager').getPage() != main.state.page) {
						$timeout(function() {
							$state.go('list', {page: di.get('pager').getPage()});
						})
					}
				}
			});

			main.setActivePage = function(page) {
				$state.go('list', {page: page});
				$document.scrollToElement($('.main-table'), 100, 100);
			};

			$(window).on('transactions.reload', function() {
				load(main.state.page);
				di.get('pager').setPages(Math.ceil(main.total / HistoryConfig.perPage));
				if (di.get('pager').getPage() != main.state.page) {
					$timeout(function() {
						$state.go('list', {page: di.get('pager').getPage()});
					})
				}
			});


			function load(page) {
				main.state.loading = true;
				$http.get(
					Routing.generate(
						'aw_account_getaccounthistory',
						{
							id: HistoryConfig.accountId,
							offset: (page - 1) * HistoryConfig.perPage,
							limit: HistoryConfig.perPage,
                            subAccountId: HistoryConfig.subAccountId,
							extra: true
						}
					)
				).then(
                    res => {
                    	const data = res.data;
						if (main.state.loaded === false) {
							main.historyColumns = angular.copy(data.columns);
                            main.balanceCell = angular.copy(data.balance_cell);
                            main.historyMiles = angular.copy(!!data.miles);
							main.state.loaded = true;
						}
						main.historyItems = angular.copy(data.data);
						main.historyExtra = angular.copy(data.extra);
						postLoad();
						if (main.total !== data.total) {
							main.total = data.total;
						}
					}
				).finally(
					() => main.state.loading = false
				);
			}

			function postLoad() {
				di.get('checker-manager').resetService();
				var checker = {};
				var actions = {};
				main.checker = {};

				angular.forEach(main.historyExtra, function (row, id) {
					main.checker[id] = di.get('checker').bind(row.uuid);

					var actionsTest = di.get('actions-manager').testElement({fields:row});
					angular.forEach(actionsTest, function (val, action) {
						if (!Object.prototype.hasOwnProperty.call(actions, action)) {
							actions[action] = [];
						}
						if (val) actions[action].push(row.uuid);
					});

					var checkerTest = di.get('checker-manager').testElement(row);
					angular.forEach(checkerTest, function (val, type) {
						if (!Object.prototype.hasOwnProperty.call(checker, type)) {
							checker[type] = [];
						}
						if (val) checker[type].push(row.uuid);
					});
				});
				di.get('actions-manager').setItems(actions);
				di.get('checker-manager').setIndex(checker);

			}
		}])
	.controller('checkerCtrl', [
		'DI',
		function (di) {
			var ListChecker = di.get('checker-manager');

			var checker = this;

			checker.view = ListChecker.getState();
			checker.checks = ListChecker.getCheckState();
			checker.toggleAll = function() {
				if (checker.view.checked) {
					checker.resetAll();
				} else {
					checker.checkAll();
				}
			};
			checker.checkAll = ListChecker.checkAll;
			checker.resetAll = ListChecker.resetAll;
			checker.select = ListChecker.select;
		}])
	.controller('actionsCtrl', [
		'$scope',
		'DI',
		function ($scope, di) {
			var ListChecker = di.get('checker-manager'),
				ListActions = di.get('actions-manager');

			var actions = this;

			actions.view = ListActions.getState();
			actions.state = ListActions.getActionsState();
			actions.action = function(action) {
				console.log(action);
				ListActions.go(action, ListChecker.getChecked());
			};

		}])
	.controller('deleteActionCtrl', [
		'$rootScope', '$http', 'DI', 'dialogService',
		function ($rootScope, $http, di, dialogService) {
			var ListActions = di.get('actions-manager');

			var deleteAction = this;

			function action(checked) {
				var dialog = dialogService.get("delete-action");
				dialog.element.find("#delete-action-text").html(Translator.transChoice('history-transaction.popup.delete', checked.length, {transactions: checked.length}));
				dialog.setOption("buttons", [
					{
						'text': Translator.trans('button.no'),
						'click': function () {
							$(this).dialog("close");
						},
						'class': 'btn-silver',
						tabindex: -1
					},
					{
						'text': Translator.trans('button.yes'),
						'click': function () {
							$('#delete-action-yes').addClass('loader').prop('disabled', true);
							$http.post(
								Routing.generate('aw_account_history_json_remove'),
								checked
							).then(
								() => {
									$(window).trigger('transactions.reload');
									dialog.close();
								}
							).catch(
								() => dialog.close()
							);
						},
						'class': 'btn-blue',
						'id': 'delete-action-yes'
					}
				]);
				dialog.open();
			}

			function test(extra) {
				return extra.custom;
			}

			ListActions.setAction('deleteAction', action, test, {group: 'delete'});
		}])
	.controller('noteActionCtrl', [
		'$rootScope', '$http', '$timeout', 'DI', 'dialogService',
		function ($rootScope, $http, $timeout, di, dialogService) {
			var ListActions = di.get('actions-manager');

			var noteAction = this;

			function action(checked) {
				noteAction.note = null;

				if (checked.length == 1 && _NoteText != '') noteAction.note = _NoteText;
				// init
				var dialog = dialogService.get("note-action"),
					dialogText = dialog.element.find("#note-action-text"),
					dialogForm = dialog.element.find('form').first();

				function setNote() {
					$('#note-action-yes').addClass('loader').prop('disabled', true);
					$http.post(
						Routing.generate('aw_account_history_json_note'),
						{ids: checked, note: noteAction.note}
					).then(
						() => {
                            $(window).trigger('transactions.reload');
                            dialog.close();
						}
					).catch(
						() => dialog.close()
					);
					_NoteText = '';
				}

				dialogForm.submit(function(e) {
					e.preventDefault();
					setNote();
				});

				dialog.setOption("open", function () {
					$timeout(function () {
						$('#note-action-note').focus();
					})
				});

				// start
				dialogText.html(Translator.transChoice(/** @Desc("You've selected %span_on%1 transaction%span_off%, please provide a note you wish to set for this transaction|You've selected %span_on%%transactions% transactions%span_off%, please provide a note you wish to set for all of these transactions") */'history-transaction.note', checked.length, {
					transactions: checked.length,
					span_on: '<span class="bold">',
					span_off: '</span>',
				}));
				dialog.setOption("buttons", [
					{
						text: Translator.trans('form.button.cancel'),
						click: function () {
							$(this).dialog("close");
						},
						'class': 'btn-silver',
						tabindex: -1
					},
					{
						text: Translator.trans('form.button.save'),
						click: setNote,
						'class': 'btn-blue',
						id: 'note-action-yes'
					}
				]);
				dialog.open();

			}

			ListActions.setAction('noteAction', action, false, {group: 'note'});
		}])
        .directive('fileModel', ['$parse', '$timeout', function ($parse, $timeout) {
            return {
                restrict: 'A',
                link: function(scope, element, attrs) {
                    var model = $parse(attrs.fileModel);
                    var modelSetter = model.assign;

                    element.bind('change', function(){
                        $timeout(function() {
                            scope.$apply(function(){
                                modelSetter(scope, element[0].files[0]);
                            });
                        });
                    });
                }
            };
        }])
	;




});
