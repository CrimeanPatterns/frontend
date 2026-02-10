define(['angular', 'pages/accounts/controllers/accountUpdater'], function (angular) {
	angular = angular && angular.__esModule ? angular.default : angular;

	angular.module("accountListUpdaterModule", ['accountUpdaterModule'])
		.controller('updateProgressCtrl', [
			'$scope', '$element', '$document', 'DI',
			function ($scope, $element, $document, di) {
				var updateProgress = this;

				this.counters = {
					all: 0,
					checking: 0,
					error: 0,
					success: 0,
					disabled: 0,
					increased: 0,
					decreased: 0,
					increase: 0,
					decrease: 0,
					total: 0,
					trips: 0,
					progress: 0
				};

				this.view = {
					process: false,
					done: false,
					fail: false
				};

				function stat(data) {
					if (data.all < 1) return;
					updateProgress.counters.all = data.all;
					updateProgress.counters.error = data.error;
					updateProgress.counters.success = data.success;
					updateProgress.counters.disabled = data.disabled;
					updateProgress.counters.increased = data.increased;
					updateProgress.counters.decreased = data.decreased;
					updateProgress.counters.progress = data.progress;
					updateProgress.counters.totals = data.increased + data.decreased;
					updateProgress.counters.total = data.total == 0 ? '' : data.total;
					updateProgress.counters.trips = data.trips == 0 ? '' : data.trips;
					updateProgress.counters.decrease = data.decrease == 0 ? '' : data.decrease;
					updateProgress.counters.increase = data.increase == 0 ? '' : data.increase;
					updateProgress.counters.unchanged = data.success - updateProgress.counters.totals - updateProgress.counters.disabled;
					updateProgress.counters.progressStyle = {width: (data.progress < 1 ? 1 : data.progress) + '%'};
					$element.find("#update-action-process-text").html(Translator.transChoice('award.account.list.update.process',
						data.all,
						{accounts: data.all, current: data.checking}));
				}

				$scope.$watch(function () {
					return di.get('updater').getState();
				}, function (state) {
					if (!di.get('updater-manager').getState().active) return;
					var currentProcess = updateProgress.view.process, currentDone = updateProgress.view.done, currentFail = updateProgress.view.fail;
					updateProgress.view.done = state == 'done';
					updateProgress.view.fail = state == 'fail';
					if (!(updateProgress.view.fail && updateProgress.view.done)) {
						updateProgress.view.process = !!state;
					}
					if (updateProgress.view.process) {
						//stat(di.get('updater-results').getResults());
						var counters = di.get('updater-results').getResults();
						if (currentProcess == false && state == 'start') {
							$element.find("#update-action-process-text").html(Translator.transChoice('award.account.list.update.process',
								counters.all,
								{accounts: counters.all, current: counters.checking}));
							$document.scrollToElement($('#updater'), 100, 200);
						} else if (updateProgress.view.done && updateProgress.view.done != currentDone) {
							$element.find("#update-action-done-text").html(Translator.transChoice('award.account.list.update.done',
								counters.all,
								{accounts: counters.all}));
							updateProgress.view.process = false;
							$document.scrollToElement($('#updater'), 100, 200);
						} else if (updateProgress.view.fail && updateProgress.view.fail != currentFail) {
							$element.find("#update-action-fail-text").html(Translator.transChoice('award.account.list.update.fail',
								counters.all - counters.success - counters.error,
								{accounts: counters.all - counters.success - counters.error}));
							updateProgress.view.process = false;
							$document.scrollToElement($('#updater'), 100, 200);
						}
					}
				});

				$scope.$watch(function () {
					return di.get('updater-results').getResults();
				}, function (data) {
					stat(data);
				}, true);

			}
		])
		.controller('updaterAdvertiseCtrl', [
			'$scope', '$timeout', 'DI',
			function ($scope, $timeout, di) {
				var updaterAdvertise = this;

				updaterAdvertise.view = {
					show: false,
					content: '',
					id: '',
					display: function (data) {
						if (data.Content) {
							this.content = data.Content;
							this.id = data.SocialAdID;
							this.show = true;
							$timeout(function () {
								var links = $('.update-account-advertise a');
								links.each(function(index, link){
									link = $(link);
									link.attr('target', "_blank");
								});
								links.click(function(){
									$.ajax({
										url: Routing.generate('aw_advertise_click', {
											user: data.UserID,
											ad: data.SocialAdID
										})
									});
								});
							});
						}
					},
					hide: function () {
						this.show = false;
					}
				};

				$scope.$watch(function () {
						return di.get('updater-advertise').getData();
					},
					function (data, oldData) {
						if (data == null) {
							updaterAdvertise.view.hide();
						} else {
							updaterAdvertise.view.display(data);
						}
					}
				);

			}
		])

});