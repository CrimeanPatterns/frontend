angular.module('AwardWalletMobile').factory('UpdaterElements', [function () {
    /**
     * @class UpdaterElement
     */
    function UpdaterElement(id, /** AccountData */ data) {
        /**
         *
         * @type {AccountData}
         */
        var account = data || {};

        var updating = {
            id: id,
            process: false,
            state: false,
            unchecking: false,
            checkInBrowser: account.hasOwnProperty('CheckInBrowser') ? Math.round(account.CheckInBrowser) > 0 : false,
            internalState: false,
            internalData: false,
            result: {
                lastBalance: 0, balance: 0, lastChange: 0, trips: 0, failMessage: '', lastChangeRaw: 0, notice:{}
            },
            progressDuration: 0,
            setQueue: function () {
                updating.process = true;
                updating.state = 'queue';
                updating.unchecking = false;
            },
            setChecking: function (duration) {
                updating.progressDuration = duration;
                updating.state = 'checking';
            },
            setChanged: function (/** AccountData */ data) {
                updating.result.lastBalance = data.LastBalance;
                updating.result.balance = data.Balance;
                updating.result.lastChange = data.LastChange;
                updating.result.lastChangeRaw = data.LastChangeRaw;
                angular.merge(account, data);
                updating.state = 'changed';
                updating.unchecking = true;
            },
            setUnchanged: function (/** AccountData */ data) {
                if (updating.state != 'changed') updating.state = 'unchanged';
                updating.result.balance = data.Balance;
                angular.merge(account, data);
                updating.unchecking = true;
            },
            setTripsFound: function (trips) {
                updating.result.trips = trips;
                if (updating.state != 'unchanged')
                    updating.state = 'changed';
                updating.unchecking = true;
            },
            setTripsNotFound: function () {
                if (updating.state != 'changed') updating.state = 'unchanged';
                updating.unchecking = true;
            },
            setError: function (/** AccountData */ data) {
                angular.merge(account, data);
                if(data && data.Notice){
                    updating.result.notice = data.Notice;
                }
                updating.state = 'error';
            },
            setDisabled: function (/** AccountData */ data) {
                angular.merge(account, data);
                if(data && data.Disabled){
                    updating.result.notice = data.Disabled;
                }
                updating.state = 'disabled';
                updating.unchecking = true;
            },
            setFailed: function (message) {
                message = message || Translator.trans('award.account.list.updating.failed');
                updating.result.failMessage = message;
                updating.state = 'failed';
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
                updating.state = false;
                updating.internalState = false;
                updating.internalData = false;
                updating.unchecking = false;
                updating.progressDuration = 0;
                updating.result.lastBalance = 0;
                updating.result.balance = 0;
                updating.result.lastChange = 0;
                updating.result.trips = 0;
                updating.result.failMessage = '';
                updating.result.lastChangeRaw = 0;
                updating.result.notice = {};
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
            }
        };
        return updating;
    }

    /**
     * @class UpdaterElementsCollection
     */
    function UpdaterElementsCollection(elements) {

        var self = {
            first: function () {
                return elements[0];
            },
            length: function () {
                return elements.length;
            },
            countStates: function () {
                var ret = {
                    queue: 0,
                    checking: 0,
                    changed: 0,
                    unchanged: 0,
                    disabled: 0,
                    error: 0,
                    failed: 0,
                    done: 0
                };
                angular.forEach(elements, function (element) {
                    if (element.state && ret.hasOwnProperty(element.state)) {
                        var state = element.state;
                        if (element.internalState == 'question' || element.internalState == 'password') state = 'checking';
                        ret[state]++;
                    }
                });
                return ret;
            },
            findState: function (state) {
                return new UpdaterElementsCollection(elements.filter(function (element) {
                    return element.state == state;
                }));
            },
            findInternalState: function (state) {
                return new UpdaterElementsCollection(elements.filter(function (element) {
                    return element.internalState == state;
                }));
            },
            findUpdated: function () {
                var updated = {};
                elements.forEach(function (element) {
                    if (['changed', 'unchanged', 'error', 'disabled', 'failed'].indexOf(element.state) > -1) {
                        if (!updated[element.state])
                            updated[element.state] = [];
                        updated[element.state].push(element);
                    }
                });
                return updated;
            },
            findQueueState: function () {
                return self.findState('queue');
            },
            findCheckingState: function () {
                return self.findState('checking');
            },
            findChangedState: function () {
                return self.findState('changed');
            },
            findUnchangedState: function () {
                return self.findState('unchanged');
            },
            findErrorState: function () {
                return self.findState('error');
            },
            findFailedState: function () {
                return self.findState('failed');
            },
            //findDoneState: function() {
            //	return self.findState('done');
            //},
            setInternalState: function (state) {
                angular.forEach(elements, function (element) {
                    element.setInternalState(state);
                });
            },
            setState: function (state) {
                var action = 'set' + state.charAt(0).toUpperCase() + state.slice(1);
                angular.forEach(elements, function (element) {
                    if (element.hasOwnProperty(action)) {
                        element[action]();
                    }
                });
            },
            setQueue: function () {
                return self.setState('queue');
            },
            setChecking: function () {
                return self.setState('checking');
            },
            setChanged: function () {
                return self.setState('changed');
            },
            setUnchanged: function () {
                return self.setState('unchanged');
            },
            setError: function () {
                return self.setState('error');
            },
            setFailed: function () {
                return self.setState('failed');
            },
            setDone: function () {
                return self.setState('done');
            },
            setEnd: function () {
                return self.setState('end');
            },
            getElement: function (id) {
                var el = elements.filter(function (element) {
                    return element.id == id;
                });
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
            isExtensionRequire: function () {
                var el = elements.filter(function (element) {
                    return element.checkInBrowser;
                });
                return !!el.length;

            }
        };

        return self;
    }

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
            if (!elements.hasOwnProperty(id)) {
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
        getUnchecking: function () {
            var ids = [];
            angular.forEach(elements, function (element, id) {
                if (element.unchecking == true) {
                    ids.push(id);
                }
            });
            return ids;
        },
        isAllDone: function () {
            var done = true;
            angular.forEach(elements, function (element, id) {
                if (!(element.state == 'done' || element.state == false || element.state == '')) {
                    done = false;
                }
            });
            return done;
        },
        reset: function () {
            elements = {};
        },
        resetService: function () {
            self.reset();
        }
    };

    return self;
}]);