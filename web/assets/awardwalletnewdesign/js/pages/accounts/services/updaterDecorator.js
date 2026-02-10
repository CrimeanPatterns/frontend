define(['angular'], function (angular) {
	angular = angular && angular.__esModule ? angular.default : angular;

	var service = angular.module('updaterDecoratorService', []);

	/**
	 * @constructor
	 * @class UpdaterElementDecorator
	 */
	function UpdaterElementDecorator(isTrips, $interval, $timeout) {
		/**
		 * @class UpdaterElementDecorator
		 */
		var decorator = {
			decorate: function (updating) {
				angular.merge(updating, {
					visualState: false,
					progress: 0,
					progressTimer: null,
					progressTimeout: 500,
					progressIncrement: 0,
					progressSlower: 1,
					progressStyle: {width: '0%'},
					progressText: '0%',
					changeClass: '',
					changeText: '',
					unchangeText: '',
					failedText: '',
					disabledText: '',
					startProgress: function () {
						if (!updating.progressTimer && updating.progress < 99 && updating.progress) {
							updating.progressTimer = $interval(function () {
								if (updating.progressTimer) {
									updating.progress += updating.progressIncrement / updating.progressSlower;
									if (updating.progress > 99) updating.progress = 99;
									if (updating.progress > 40 && updating.progressSlower == 1) updating.progressSlower = 2;
									if (updating.progress > 60 && updating.progressSlower == 2) updating.progressSlower = 4;
									if (updating.progress > 80 && updating.progressSlower == 4) updating.progressSlower = 8;
									updating.progressStyle = {width: updating.progress + '%'};
									updating.progressText = Math.floor(updating.progress) + '%';
								}
							}, updating.progressTimeout)
						}
					},
					pauseProgress: function () {
						if (updating.progressTimer) {
							$interval.cancel(updating.progressTimer);
							updating.progressTimer = null;
						}
					},
					endProgress: function (callback) {
						$timeout(function() {});
						if (updating.progressTimer) {
							$interval.cancel(updating.progressTimer);
							updating.progressTimer = null;
						}
						if (callback) {
							if (updating.progress < 95 && updating.progress) {
								updating.progressIncrement = 15;
								updating.progressTimer = $interval(function () {
									if (updating.progressTimer) {
										updating.progress += updating.progressIncrement;
										if (updating.progress >= 100) {
											updating.progress = 100;
											$interval.cancel(updating.progressTimer);
											updating.progressTimer = null;
											callback();
                                            updating.fireEventsCallback(updating.visualState);
										}
										updating.progressStyle = {width: updating.progress + '%'};
										updating.progressText = Math.floor(updating.progress) + '%';
									}
								}, updating.progressTimeout)
							} else {
								updating.progress = 100;
								updating.progressStyle = {width: updating.progress + '%'};
								updating.progressText = Math.floor(updating.progress) + '%';
								callback();
                               updating.fireEventsCallback(updating.visualState);
							}
						}
					}
				});

				var decoratedSetQueue = updating.setQueue;
				updating.setQueue = function () {
					decoratedSetQueue();
					updating.visualState = 'queue';
					updating.progress = 1;
					updating.progressStyle = {width: updating.progress + '%'};
					updating.progressText = Math.floor(updating.progress) + '%';
					updating.progressDuration = 30;
					updating.progressIncrement = 100 / (updating.progressDuration * 1.1) / (1000 / updating.progressTimeout);
					updating.progressSlower = 1;
					updating.changeClass = '';
					updating.changeText = '';
					updating.unchangeText = '';
				};

				var decoratedSetChecking = updating.setChecking;
				updating.setChecking = function (duration) {
					decoratedSetChecking(duration);
					duration = duration || 30;
					updating.progressDuration = duration;
					updating.progressIncrement = 100 / (updating.progressDuration * 1.1) / (1000 / updating.progressTimeout);
					updating.visualState = 'checking';
					updating.startProgress();
				};

				var decoratedSetChanged = updating.setChanged;
				updating.setChanged = function (/** AccountData */ data) {
					decoratedSetChanged(data);
					if (isTrips) return;
					updating.changeText = Translator.trans('award.account.list.updating.changed',
						{lastBalance: updating.result.lastBalance, balance: data.Balance, lastChange: data.LastChange, changeClass: data.ChangedOverPeriodPositive ? 'green' : 'blue' });
					updating.changeClass = data.ChangedOverPeriodPositive ? 'icon-green-up' : 'icon-blue-down';
					updating.endProgress(function () {
						updating.visualState = 'changed';
					});
				};

				var decoratedSetUnchanged = updating.setUnchanged;
				updating.setUnchanged = function (/** AccountData */ data) {
					decoratedSetUnchanged(data);
					if (isTrips) return;
					updating.unchangeText = Translator.trans('award.account.list.updating.unchanged',
						{balance: data.Balance});
					updating.endProgress(function () {
						updating.visualState = 'unchanged';
					});
				};

				var decoratedSetDisabled = updating.setDisabled;
				updating.setDisabled = function (/** AccountData */ data) {
					decoratedSetDisabled(data);
					if (data.Access.edit) {
						updating.disabledText = Translator.trans('award.account.list.updating.disabled.2',
							{url: Routing.generate('aw_account_edit', {accountId: data.ID})});
					} else {
						updating.disabledText = Translator.trans('award.account.list.updating.disabled.3');
					}
					updating.endProgress();
					updating.visualState = 'disabled';
					updating.progress = 1;
					updating.progressStyle = {width: updating.progress + '%'};
					updating.progressText = Math.floor(updating.progress) + '%';
				};

				var decoratedSetTripsFound = updating.setTripsFound;
				updating.setTripsFound = function (/** AccountData */ data, trips) {
					decoratedSetTripsFound(data, trips);
					if (!isTrips) return;
					updating.changeText = Translator.transChoice('award.account.list.updating.trips-found', trips,
						{trips: trips });
					updating.changeClass = 'icon-green-up';
					updating.endProgress(function () {
						updating.visualState = 'changed';
					});
				};
				var decoratedSetTripsNotFound = updating.setTripsNotFound;
				updating.setTripsNotFound = function (/** AccountData */ data) {
					decoratedSetTripsNotFound(data);
					if (!isTrips) return;
					updating.unchangeText = Translator.trans('award.account.list.updating.trips-not-found');
					updating.endProgress(function () {
						updating.visualState = 'unchanged';
					});
				};

				var decoratedSetError = updating.setError;
				updating.setError = function (/** AccountData */ data) {
					decoratedSetError(data);
					updating.endProgress(function () {
						updating.visualState = 'error';
					});
				};

				var decoratedSetExtensionRequired = updating.setExtensionRequired;
				updating.setExtensionRequired = function (event) {
                    decoratedSetExtensionRequired(event);
                    const {buttonName, buttonLink, version} = event;
                    updating.visualState = 'extensionRequired';
                    if (buttonName && buttonName.length > 0) {
                        updating.visualParams = {buttonName, buttonLink, version};
                    }
                };

				var decoratedSetFailed = updating.setFailed;
				updating.setFailed = function (message) {
					decoratedSetFailed(message);
					updating.failedText = message || Translator.trans('award.account.list.updating.failed');
					updating.endProgress();
					updating.visualState = 'failed';
				};

				var decoratedSetDone = updating.setDone;
				updating.setDone = function () {
					decoratedSetDone();
					updating.endProgress();
					updating.progress = 0;
					updating.visualState = 'done';
				};

				var decoratedReset = updating.reset;
				updating.reset = function () {
					decoratedReset();
					updating.endProgress();
					updating.progress = 0;
					updating.visualState = false;
				};

				var decoratedSetInternalState = updating.setInternalState;
				updating.setInternalState = function (state, data) {
					decoratedSetInternalState(state, data);
					if (state == 'password') updating.setPause();
					if (state == 'question') updating.setInstantError();
				};

				updating.setInstantError = function () {
					updating.endProgress();
					updating.visualState = 'error';
					updating.progress = 1;
					updating.progressStyle = {width: updating.progress + '%'};
					updating.progressText = Math.floor(updating.progress) + '%';
				};

				updating.setPause = function () {
					updating.pauseProgress();
				};

				return updating;
			}
		};
		return decorator;
	}

	service.provider('UpdaterElementDecorator',
		function () {
			var trips = false;

			return {
				setTrips: function(data) {
					trips = data;
				},
				$get: [
					'$interval', '$timeout',
					function ($interval, $timeout) {
						return new UpdaterElementDecorator(trips, $interval, $timeout);
					}
				]
			};
		});
});

