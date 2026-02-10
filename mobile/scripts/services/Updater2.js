angular.module('AwardWalletMobile').factory('UpdaterSession', ['$http', '$timeout', 'AccountUpdater', function ($http, $timeout, AccountUpdater) {
    /**
     * @constant
     */
    var constants = {
        failCount: 3,
        updateTimeout: 3000,
        failTimeout: 600 * 1000
    };

    var request = {
        ids: [],
        tickCallback: null,
        failCallback: null,
        trips: false
    };
    var messages = [];

    var session = {
        /**
         * Updater state
         * false, 'start', 'update', 'fail', 'done'
         */
        state: false,
        /**
         * Session Key
         */
        key: null,
        /**
         * Unique start key -- disable parallel start
         */
        startKey: null,
        /**
         * Event queue index
         */
        queueIndex: null,
        /**
         * HTTP error counter
         */
        errorCount: 0,
        /**
         * Update timer
         */
        updateTimer: null,
        /**
         * Fail timer
         */
        failTimer: null
    };

    function resetSession() {
        $timeout.cancel(session.updateTimer);
        $timeout.cancel(session.failTimer);
        request = {
            ids: [],
            tickCallback: null,
            failCallback: null,
            trips: false
        };
        messages = [];
        session = {
            state: false,
            key: null,
            startKey: null,
            queueIndex: null,
            errorCount: 0,
            updateTimer: null,
            failTimer: null
        };
    }

    function start() {
        if (session.state) return;
        session.state = 'start';
        AccountUpdater.getResource().start({
            accounts: request.ids,
            trips: request.trips,
            extensionAvailable: isCordova,
            startKey: session.startKey
        }, function (data, status) {
            if (!session.state || session.state != 'start') return;
            if (data.startKey != session.startKey) return;
            session.startKey = null;

            session.key = data.key || false;
            if (session.key == false) {
                fail(data, 'updater fail');
            } else {
                session.state = 'update';
                session.queueIndex = request.tickCallback(data.events) || session.queueIndex;

                session.updateTimer = $timeout(function () {
                    update();
                }, constants.updateTimeout, false);

                session.failTimer = $timeout(function() {
                    fail(false, 'timeout');
                }, constants.failTimeout);
            }
        }, function (data, status) {
            session.errorCount++;
            if (session.errorCount > constants.failCount) {
                fail(data, status);
            } else {
                session.updateTimer = $timeout(function () {
                    session.state = false;
                    start();
                }, constants.updateTimeout);
            }
        });
    }

    function update() {
        if (session.key === null) return;
        if (!session.state) return;
        if (session.state == 'fail') return;
        var sendMessages = [];
        Array.prototype.push.apply(sendMessages, messages);
        AccountUpdater.getResource().getEvents({key: session.key, eventIndex: session.queueIndex}, {
            messages: sendMessages
        }, function (response, status) {
            var data = JSON.parse(angular.toJson(response));

            if (!session.state || session.state != 'update') return;

            var changed = Object.keys(data || {}).length;

            session.queueIndex = request.tickCallback(data) || session.queueIndex;

            //console.log('session.queueIndex', session.queueIndex);

            session.updateTimer = $timeout(function () {
                update();
            }, constants.updateTimeout);

            messages = messages.filter(function (message) {
                return sendMessages.indexOf(message) === -1;
            });

            if (changed > 0) {
                $timeout.cancel(session.failTimer);
                session.failTimer = $timeout(function () {
                    fail(false, 'timeout');
                }, constants.failTimeout);
            }

        }, function (data, status) {
            session.errorCount++;
            if (session.errorCount > constants.failCount) {
                fail(data, status);
            } else {
                session.updateTimer = $timeout(function () {
                    update();
                }, constants.updateTimeout);
            }
        });
    }

    function fail(data, status) {
        $timeout.cancel(session.updateTimer);
        $timeout.cancel(session.failTimer);
        session.updateTimer = null;
        session.failTimer = null;
        session.state = 'fail';
        request.failCallback(session.state, status);
    }

    function done() {
        $timeout.cancel(session.updateTimer);
        $timeout.cancel(session.failTimer);
        session.updateTimer = null;
        session.failTimer = null;
        session.state = 'done';
    }

    var self = {
        start: function (ids, tick, fail, trips) {
            if (self.isUpdating()) return;
            if (!angular.isArray(ids)) return;
            if (!(ids.length)) return;
            trips = trips || false;
            resetSession();
            session.startKey = Math.round((Math.random() + 1)* 1000000);
            ids = ids.map(function (id) {
                return parseInt(id.replace(/[^0-9]/g, ''));
            });
            request.ids = ids;
            request.tickCallback = tick;
            request.failCallback = fail;
            request.trips = trips;
            start();
        },
        done: function () {
            done();
        },
        stop: function () {
            done();
            session.state = false;
        },
        isUpdating: function () {
            return !!session.state;
        },
        sendMessage: function (action, id, data) {
            id = parseInt(id.replace(/[^0-9]/g, ''));
            if (!id) return;
            messages.push({
                action: action,
                id: id,
                data: data
            });
        },
        add: function (ids) {
            if (!angular.isArray(ids)) return;
            if (!(ids.length)) return;
            ids = ids.map(function (id) {
                return parseInt(id.replace(/[^0-9]/g, ''));
            });
            angular.forEach(ids, function (id) {
                self.sendMessage('add', id);
            });
        },
        setAnswer: function (id, answer) {
            self.sendMessage('setAnswer', id, answer);
        },
        setPassword: function (id, password) {
            self.sendMessage('setPassword', id, password);
        },
        getState: function () {
            return session.state;
        },
        getKey: function () {
            return session.key;
        }
    };

    return self;
}]);

angular.module('AwardWalletMobile').service('UpdaterResults', [function () {
    var counters = {
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
        progress: 0,
        updated: 0
    };
    var defaultCounters = angular.copy(counters);
    return {
        reset: function () {
            counters = angular.copy(defaultCounters);
        },
        setValue: function (counter, value) {
            counters[counter] = value;
        },
        getValue: function (counter) {
            return counters[counter];
        },
        incrementValue: function (counter, value) {
            value = value || 1;
            counters[counter] += value;
        },
        decrementValue: function (counter, value) {
            value = value || 1;
            counters[counter] -= value;
        },
        getResults: function () {
            return counters;
        }
    };
}]);

angular.module('AwardWalletMobile').service('Updater', ['UpdaterSession', 'UpdaterResults', 'UpdaterElements', 'AutoLogin', function (Session, Result, UpdaterElements, AutoLogin) {

    var elements = null,
        trips = [],
        accounts = {},
        stateCallback = null,
        changedCallback = null,
        questionAction = null,
        passwordAction = null,
        questionActionCancel = null,
        passwordActionCancel = null;

    function tick(events) {
        events = events || [];
        var lastIndex = false;
        angular.forEach(events, function (event, index) {
            var item = null;
            var originAccountId = event.accountId;
            if (event.accountId) {
                event.accountId = 'a' + event.accountId;
                item = elements.getElement(event.accountId);
                if (item.getInternalState() == 'extension' && event.type != 'extension') {
                    item.setInternalState(null);
                    AutoLogin.abort('timeout');
                }
            }
            switch (event.type) {
                case 'debug':
                    if (event.accountId) {
                        console.log(event.accountId + ': ' + event.message);
                    } else {
                        console.log(event.message);
                    }
                    break;
                case 'start_progress':
                    item.setChecking(event.expectedDuration);
                    break;
                case 'trips_found':
                    item.setTripsFound(event.trips);
                    angular.forEach(event.tripIds, function (id) {
                        trips.push(id);
                    });
                    break;
                case 'trips_not_found':
                    item.setTripsNotFound();
                    break;
                case 'updated':
                    item.setUnchanged(event.accountData);
                    accounts[event.accountId] = event.accountData;
                    break;
                case 'changed':
                    item.setChanged(event.accountData);
                    accounts[event.accountId] = event.accountData;
                    if (event.increased) {
                        Result.incrementValue('increase', event.change);
                        Result.incrementValue('increased');
                    } else {
                        Result.incrementValue('decrease', event.change);
                        Result.incrementValue('decreased');
                    }
                    Result.setValue('total', Result.getValue('decrease') + Result.getValue('increase'));
                    break;
                case 'error':
                    item.setError(event.accountData);
                    accounts[event.accountId] = event.accountData;
                    break;
                case 'disabled':
                    item.setDisabled(event.accountData);
                    accounts[event.accountId] = event.accountData;
                    Result.incrementValue('disabled');
                    break;
                case 'fail':
                    item.setFailed(event.message);
                    break;
                case 'extension':
                    var extChecking = elements.findInternalState('extension');
                    if (extChecking.length()) {
                        if (event.accountId != extChecking.first().id) {
                            item.setFailed();
                        }
                    } else {
                        item.setChecking(event.expectedDuration);
                        item.setInternalState('extension');
                        AutoLogin.update(originAccountId).then(function () {
                        }, function () {
                            item.setFailed(Translator.trans('updater2.messages.fail.cannot-check'));
                        });
                    }
                    break;
                case 'local_password':
                    item.setInternalState('password', {
                        label: event.label,
                        displayName: event.displayName
                    });
                    break;
                case 'question':
                    item.setInternalState('question', {
                        question: event.question,
                        displayName: event.displayName
                    });
                    break;
            }
            lastIndex = index;
        });
        if (events) {
            if (accounts && Object.keys(accounts).length && changedCallback) {
                changedCallback(accounts);
                accounts = {};
            }
            stat();
            nextPopup();
        }
        return lastIndex;
    }

    function fail(state, status) {
        // state = (start|update)
        elements.findCheckingState().setFailed();
        elements.findQueueState().setFailed();
        elements.setInternalState(false);
        if (questionActionCancel) questionActionCancel();
        if (passwordActionCancel) passwordActionCancel();
        if (stateCallback) stateCallback('fail');
        if(status == 'timeout') {
            AutoLogin.abort(status);
        }
        stat();
    }

    function stat() {
        var counts = elements.countStates();
        Result.setValue('trips', trips.length);
        Result.setValue('error', counts.error + counts.failed);
        var success = counts.changed + counts.unchanged + counts.disabled;
        if (success > Result.getValue('all') - Result.getValue('error')) {
            success = Result.getValue('all') - Result.getValue('error');
        }
        Result.setValue('success', success);
        Result.setValue('checking', counts.checking + success + Result.getValue('error'));
        Result.setValue('updated', success + Result.getValue('error'));
        Result.setValue('progress', (success + Result.getValue('error')) / Result.getValue('all') * 100);
        if (Result.getValue('progress') == 100) {
            if (stateCallback) stateCallback('done');
            self.stop();
        }
    }

    function nextPopup() {
        var item;
        if (elements.findInternalState('password').length()) {
            item = elements.findInternalState('password').first();
            if (passwordAction) {
                passwordAction(item.id, item.getInternalData());
            } else {
                self.cancelPassword(item.id);
                nextPopup();
            }
        } else if (elements.findInternalState('question').length()) {
            item = elements.findInternalState('question').first();
            if (questionAction) {
                questionAction(item.id, item.getInternalData());
            } else {
                self.cancelQuestion(item.id);
                nextPopup();
            }
        }
    }

    var self = {
        start: function (ids, stateFunc, changedFunc) {
            if (Session.isUpdating()) {
                throw new Error('Updating session in progress');
            }
            elements = UpdaterElements.getCollection(ids);
            elements.setQueue();
            Result.reset();
            Result.setValue('all', elements.length());
            trips = [];
            accounts = {};
            stateCallback = stateFunc;
            changedCallback = changedFunc;
            Session.start(ids, tick, fail, true);
            //console.log('start', elements);
        },
        stop: function () {
            Session.done();
            //console.log('stop', elements);
            elements.findQueueState().setDone();
            elements.findCheckingState().setDone();
            elements.setInternalState(false);
        },
        end: function () {
            Session.stop();
            Result.reset();
            //console.log('end', elements);
            trips = [];
            accounts = {};
            stateCallback = null;
            changedCallback = null;
            elements.setEnd();
            AutoLogin.close();
        },
        getCounters: function () {
            return Result.getResults();
        },
        getAccounts: function () {
            return accounts;
        },
        getAccount: function (id) {
            return accounts[id];
        },
        getCollection: function () {
            return elements;
        },
        getTrips: function () {
            return trips;
        },
        isUpdating: function () {
            return Session.isUpdating();
        },
        getState: function () {
            return Session.getState();
        },
        getKey: function () {
            return Session.getKey();
        },
        cancelQuestion: function (id) {
            if (!self.isUpdating()) return;
            var item = elements.getElement(id);
            if (item && item.getInternalState() == 'question') {
                item.setInternalState(false);
                item.setError();
            }
            stat();
        },
        cancelPassword: function (id) {
            if (!self.isUpdating()) return;
            var item = elements.getElement(id);
            if (item && item.getInternalState() == 'password') {
                item.setInternalState(false);
                item.setFailed(Translator.trans('updater2.messages.fail.password-missing'));
            }
            stat();
        },
        doneQuestion: function (id, answer) {
            if (!self.isUpdating()) return;
            var item = elements.getElement(id);
            if (item && item.getInternalState() == 'question') {
                item.setInternalState(false);
                item.setQueue();
                Session.setAnswer(id, answer);
            }
            stat();
        },
        donePassword: function (id, password) {
            if (!self.isUpdating()) return;
            var item = elements.getElement(id);
            if (item && item.getInternalState() == 'password') {
                item.setInternalState(false);
                item.setQueue();
                Session.setPassword(id, password);
            }
            stat();
        },
        setQuestionAction: function (action, cancel) {
            questionAction = action;
            questionActionCancel = cancel;
        },
        setPasswordAction: function (action, cancel) {
            passwordAction = action;
            passwordActionCancel = cancel;
        },
        nextPopup: function () {
            if (!self.isUpdating()) return;
            nextPopup();
        }
    };
    return self;
}]);