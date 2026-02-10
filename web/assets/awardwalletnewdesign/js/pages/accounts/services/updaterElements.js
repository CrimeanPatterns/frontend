define(['angular', 'jquery-boot'], function (angular, $) {
	angular = angular && angular.__esModule ? angular.default : angular;

	var service = angular.module('updaterElementsService', []);

	/**
	 * @constructor
	 * @class UpdaterElement
	 */
	function UpdaterElement(id, /** AccountData */ data) {
		/**
		 *
		 * @type {AccountData}
		 */
		var account = data || {};

		function updateAccount(/** AccountData */ data) {
			if (Object.prototype.hasOwnProperty.call(account, 'FID')) {
				account.Balance = data.Balance;
				account.LastChange = data.LastChange;
			} else {
				account = data;
			}
		}

		/**
		 * @class UpdaterElement
		 */
		var updating = {
			id: id,
			process: false,
			state: false,
			resultState: false,
			unchecking: false,
			checkInBrowser: Object.prototype.hasOwnProperty.call(account, 'CheckInBrowser') ? Math.round(account.CheckInBrowser) > 0 : false,
			internalState: false,
			internalData: false,
            extensionConnection: undefined,
			callbacks : {},
			result: {
				lastBalance: 0, balance: 0, lastChange: 0, trips: 0, failMessage: ''
			},
			setQueue: function () {
				updating.process = true;
				updating.state = updating.resultState = 'queue';
				updating.unchecking = false;
			},
			setChecking: function (duration) {
				updating.state = updating.resultState = 'checking';
			},
			setChanged: function (/** AccountData */ data) {
				updating.result.lastBalance = Object.prototype.hasOwnProperty.call(account, 'Balance') ? account.Balance : 0;
				updating.result.balance = data.Balance;
				updating.result.lastChange = data.LastChange;
				updateAccount(data);
				updating.state = updating.resultState = 'changed';
				updating.unchecking = true;
			},
			setUnchanged: function (/** AccountData */ data) {
				updating.result.lastBalance = Object.prototype.hasOwnProperty.call(account, 'Balance') ? account.Balance : 0;
				updateAccount(data);
				if (updating.state != 'changed') updating.state = updating.resultState = 'unchanged';
				updating.unchecking = true;
			},
			setTripsFound: function (/** AccountData */ data, trips) {
				updating.result.trips = trips;
				updateAccount(data);
				if (updating.state !== 'unchanged') {
					updating.state = updating.resultState = 'changed';
				}
				updating.unchecking = true;
			},
			setTripsNotFound: function (/** AccountData */ data) {
				updateAccount(data);
				if (updating.state != 'changed') updating.state = updating.resultState = 'unchanged';
				updating.unchecking = true;
			},
			setError: function (/** AccountData */ data) {
				updateAccount(data);
				updating.state = updating.resultState = 'error';
			},
			setDisabled: function (/** AccountData */ data) {
				updateAccount(data);
				updating.state = updating.resultState = 'disabled';
				updating.unchecking = true;
			},
			setFailed: function (message) {
				updating.result.failMessage = message;
				updating.state = updating.resultState = 'failed';
                if (updating.extensionConnection) {
                    updating.extensionConnection.disconnect();
                    updating.extensionConnection = undefined;
                }
			},
			setExtensionRequired: function () {
				updating.state = updating.resultState = 'failed';
			},
			setDone: function () {
				updating.process = false;
				updating.state = 'done';
			},
			setEnd: function () {
				updating.reset();
			},
			reset: function () {
				updating.process = false;
				updating.state = updating.resultState = false;
				updating.internalState = false;
				updating.internalData = false;
				updating.unchecking = false;
				updating.result.lastBalance = 0;
				updating.result.balance = 0 ;
				updating.result.lastChange = 0;
				updating.result.trips = 0;
				updating.result.failMessage = '';
                updating.extensionConnection = undefined;
			},
			done: function () {
				if (['failed', 'error', 'changed', 'unchanged', 'disabled'].indexOf(updating.state) != -1) updating.setDone();
			},
			setInternalState: function (state, data) {
				updating.internalState = state;
				updating.internalData = data;
			},
			getInternalState: function () {
				return updating.internalState;
			},
			getInternalData: function () {
				return updating.internalData;
            },
            setExtensionConnection: function(client) {
                updating.extensionConnection = client;
            },
            getExtensionConnection: function() {
                return updating.extensionConnection;
            },
            fireEventsCallback : function(type) {
                if (Array.isArray(updating.callbacks[type])) {
                    $.when($.each(updating.callbacks[type], function(i, cb) {
                        cb.call()
                    })).then(function() {
                        delete updating.callbacks[type];
                    });
                }
            },
            setEventCallback : function(type, callback) {
                if (!Array.isArray(updating.callbacks[type]))
                    updating.callbacks[type] = [];
                updating.callbacks[type].push(callback);
			}
		};
		return updating;
	}

	function UpdaterElementsCollection(elements) {
		/**
		 * @class UpdaterElementsCollection
		 */
		var self = {
			first: function() {
				return elements[0];
			},
			length: function() {
				return elements.length;
			},
            foreach: function(fn) {
                return angular.forEach(elements, fn);
            },
			countStates: function() {
				var ret = {
					queue: 0,
					checking: 0,
					changed: 0,
					unchanged: 0,
					disabled: 0,
					error: 0,
					failed: 0
				};
				angular.forEach(elements, function (element) {
					if (element.resultState && Object.prototype.hasOwnProperty.call(ret, element.resultState)) {
						var state = element.resultState;
						if (element.internalState == 'question' || element.internalState == 'password') state = 'checking';
						ret[state] ++;
					}
				});
				return ret;
			},
			findState: function(state) {
				return new UpdaterElementsCollection(elements.filter(function (element) {return element.state == state;}));
			},
			findInternalState: function(state) {
				return new UpdaterElementsCollection(elements.filter(function (element) {return element.internalState == state;}));
			},
			findQueueState: function() {
				return self.findState('queue');
			},
			findCheckingState: function() {
				return self.findState('checking');
			},
			findChangedState: function() {
				return self.findState('changed');
			},
			findUnchangedState: function() {
				return self.findState('unchanged');
			},
			findErrorState: function() {
				return self.findState('error');
			},
			findFailedState: function() {
				return self.findState('failed');
			},
			//findDoneState: function() {
			//	return self.findState('done');
			//},
			setInternalState: function(state) {
				angular.forEach(elements, function (element) {
					element.setInternalState(state);
				});
			},
			setState: function(state) {
				var action = 'set' + state.charAt(0).toUpperCase() + state.slice(1);
				angular.forEach(elements, function (element) {
					if (Object.prototype.hasOwnProperty.call(element, action)) {
						element[action]();
					}
				});
			},
			setQueue: function() {
				return self.setState('queue');
			},
			setChecking: function() {
				return self.setState('checking');
			},
			setChanged: function() {
				return self.setState('changed');
			},
			setUnchanged: function() {
				return self.setState('unchanged');
			},
			setError: function() {
				return self.setState('error');
			},
			setFailed: function() {
				return self.setState('failed');
			},
			setDone: function() {
				return self.setState('done');
			},
			setEnd: function() {
				return self.setState('end');
			},
			getElement: function (id) {
				var el = elements.filter(function (element) {return element.id == id;});
				if (el.length) {
					return el[0];
				}
				return false;
			},
			getIds: function () {
				var ret = [];
				angular.forEach(elements, function (element) {
					ret.push(element.id);
				});
				return ret;
			},
			isExtensionRequire: function() {
				var el = elements.filter(function (element) {return element.checkInBrowser;});
				return !!el.length;
			}
		};

		return self;
	}

	function UpdaterElements(persistent) {
		/**
		 *
		 * @type {Object.<(string|integer), UpdaterElement>|UpdaterElement[]}
		 */
		var elements = {};

		/**
		 * @class UpdaterElements
		 */
		var self = {
			/**
			 *
			 * @param id
			 * @param {AccountData=} data
			 * @returns {UpdaterElement}
			 */
			bind: function (id, data) {
				if (!Object.prototype.hasOwnProperty.call(elements, id)) {
					data = data || {};
					elements[id] = new UpdaterElement(id, data);
				}
				return elements[id];
			},
			getCollection: function (ids) {
				ids = ids || [];
				var ret = [];
				angular.forEach(ids, function (id) {
					ret.push(self.bind(id));
				});
				return new UpdaterElementsCollection(ret);
			},
			getUnchecking: function() {
				var ids = [];
				angular.forEach(elements, function (element, id) {
					if (element.unchecking == true) {
						ids.push(id);
					}
				});
				return ids;
			},
			isAllDone: function() {
				var done = true;
				angular.forEach(elements, function (element, id) {
					if (!(element.state == 'done' || element.state == false || element.state == '')) {
						done = false;
					}
				});
				return done;
			},
			reset: function() {
				elements = {};
			},
			resetService: function () {
				if (!persistent) {
					self.reset();
				}
			}
		};

		return self;
	}


	service.provider('UpdaterElements',
		function () {
			var persistent = true;

			return {
				setPersistent: function(data) {
					persistent = data;
				},
				$get: [
					function () {
						return new UpdaterElements(persistent);
					}
				]
			};
		})
});

