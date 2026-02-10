define(['angular', 'jquery-boot'], function (angular, $) {
	angular = angular && angular.__esModule ? angular.default : angular;

	angular.module("accountUpdaterModule", [])
		.controller('updaterQuestionCtrl', [
			'$http', '$timeout',
			'DI', 'dialogService',
			function ($http, $timeout,
			          di, dialogService) {
				var updaterQuestion = this;
				var Accounts = di.get('accounts');

				updaterQuestion.id = null;
				updaterQuestion.account = '';
				updaterQuestion.provider = '';
				updaterQuestion.question = '';
				updaterQuestion.answer = '';
				updaterQuestion.answerChange = function() {
					if ('' == $('#updater-question-answer').val()) {
						$('#updater-question-yes').attr('disabled', 'disabled').addClass('ui-button-disabled ui-state-disabled');
					} else {
						$('.popup-row-updater-error', '#updater-question').removeClass('popup-row-updater-error');
						$('#updater-question-yes').removeAttr('disabled').removeClass('ui-button-disabled ui-state-disabled');
					}
				};

				function action() {
					updaterQuestion.answer = '';

					var dialog = dialogService.get("updater-question");

					function answer() {
						if (!updaterQuestion.answer) {
							dialog.element.parent().effect("shake");
							return;
						}
						updaterQuestion.error = null;
						$('#updater-question-yes').addClass('loader').prop('disabled', true);
						di.get('updater').doneQuestion(updaterQuestion.id, {
							answer: updaterQuestion.answer,
							question: updaterQuestion.question
						});
						updaterQuestion.id = null;
						dialog.close();
						di.get('updater').nextPopup();
					}

					dialog.element.find('form').submit(function(e) {
						e.preventDefault();
						answer();
					});
					dialog.setOption("title", Translator.trans('award.account.popup.updater-question.title', {provider: updaterQuestion.provider}));
					dialog.setOption("open", function () {
						$timeout(function () {
							dialog.element.find('input').focus();
						});
					});
					dialog.setOption("close", function () {
						if (!updaterQuestion.id) return;
						di.get('updater').cancelQuestion(updaterQuestion.id);
						updaterQuestion.id = null;
						di.get('updater').nextPopup();
					});
					dialog.setOption("buttons", [
						{
							text: Translator.trans('alerts.btn.cancel'),
							click: function () {
								dialog.close();
							},
							'class': 'btn-silver',
							tabindex: -1
						},
						{
							text: Translator.trans('form.button.send'),
							click: answer,
							'class': 'btn-blue',
							id: 'updater-question-yes',
							disabled: 'disabled',
						}
					]);
					dialog.open();
				}

				di.get('updater').setQuestionAction(function (id, data) {
					if (updaterQuestion.id) return;
					updaterQuestion.id = id;
					updaterQuestion.provider = data.displayName;
					updaterQuestion.question = data.question;
					/** @var AccountData account */
					var account = Accounts.getAccount(id);
					updaterQuestion.account = account.LoginFieldFirst;
					if (account.ProgramMessage.Error !== data.question) {
						updaterQuestion.error = account.ProgramMessage.Error;
					}
					action();
				}, function () {
					dialogService.get("updater-question").close();
				});
			}
		])
		.controller('updaterPasswordCtrl', [
			'$http', '$timeout',
			'DI', 'dialogService',
			function ($http, $timeout,
					  di, dialogService) {
				var updaterPassword = this;

				updaterPassword.id = null;
				updaterPassword.provider = '';
				updaterPassword.title = '';
				updaterPassword.password = '';

				function action() {
					updaterPassword.password = '';

					var dialog = dialogService.get("updater-password");

					function setPassword() {
						if (!updaterPassword.password) {
							dialog.element.parent().effect("shake");
							return;
						}
						$('#updater-password-yes').addClass('loader').prop('disabled', true);
						di.get('updater').donePassword(updaterPassword.id, updaterPassword.password);
						updaterPassword.id = null;
						dialog.close();
						di.get('updater').nextPopup();
					}

					dialog.element.find('form').submit(function(e) {
						e.preventDefault();
						setPassword();
					});
					dialog.setOption("title", Translator.trans('award.account.popup.updater-password.title', {provider: updaterPassword.provider}));
					dialog.setOption("open", function () {
						$timeout(function () {
							dialog.element.find('input').focus();
						});
					});
					dialog.setOption("close", function () {
						if (!updaterPassword.id) return;
						di.get('updater').cancelPassword(updaterPassword.id);
						updaterPassword.id = null;
						di.get('updater').nextPopup();
					});
					dialog.setOption("buttons", [
						{
							text: Translator.trans('alerts.btn.cancel'),
							click: function () {
								dialog.close();
							},
							'class': 'btn-silver',
							tabindex: -1
						},
						{
							text: Translator.trans('form.button.send'),
							click: setPassword,
							'class': 'btn-blue',
							id: 'updater-password-yes'
						}
					]);
					dialog.open();
				}

				di.get('updater').setPasswordAction(function (id, data) {
					if (updaterPassword.id) return;
					updaterPassword.id = id;
					updaterPassword.provider = data.displayName;
					updaterPassword.title = data.label;
					action();
				}, function () {
					dialogService.get("updater-password").close();
				});
			}
		])
});