define(['angular', 'centrifuge', 'extension-client/bundle', 'browserext'], function (angular, Centrifuge, bundle) {
    angular = angular && angular.__esModule ? angular.default : angular;

    var extensionClient = new bundle.DesktopExtensionInterface();

    var service = angular.module('updaterService', []);

    var updater3k =
        (typeof window.updater_3k_enabled === 'boolean' && window.updater_3k_enabled) ||
        localStorage.getItem('updater_3k');
    if (updater3k) {
        console.log('updater 3000 enabled');
    }

    // Класс взаимодействия с сервером
    // Поддерживает сессию, вызывает tickCallback с событиями
    // Если падает, вызывает failCallback
    // ВАЖНО: сам не останавливается (может только упасть), требуется явный вызов stop
    function UpdaterSession($http, $timeout) {
        var extensionReady = null;
        if (window.browserExt.supportedBrowser()) {
            $timeout(function () {
                if (extensionReady === null) {
                    extensionReady = false;
                }
            }, 20000);
            window.browserExt.pushOnReady(function () {
                if (extensionReady === null) {
                    extensionReady = true;
                }
            });
        } else {
            extensionReady = false;
        }

        /**
         * @constant
         */
        var constants = {
            failCount: 3,
            updateTimeout: 3000,
            failTimeout: 630 * 1000,
        };

        var request = {
            ids: [],
            tickCallback: null,
            failCallback: null,
            source: 'one',
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
            failTimer: null,
            /**
             * Centrifuge subscription
             */
            subscription: null,
            /**
             * Centrifuge connection
             */
            connection: null,
            messagesBuffer: [],
            gotHistory: false,
            client: null,
        };

        function resetSession() {
            console.log('resetting updater session');
            $timeout.cancel(session.updateTimer);
            $timeout.cancel(session.failTimer);
            request = {
                ids: [],
                tickCallback: null,
                failCallback: null,
                source: 'one',
            };
            messages = [];
            unsubscribe();
            session = {
                state: false,
                key: null,
                startKey: null,
                queueIndex: null,
                errorCount: 0,
                updateTimer: null,
                failTimer: null,
                connection: session.connection,
                subscription: null,
                messagesBuffer: [],
                gotHistory: false,
                client: session.client,
            };
        }

        function start(waitForExtension) {
            if (session.state) return;

            if (waitForExtension && extensionReady === null) {
                session.updateTimer = $timeout(function () {
                    start(waitForExtension);
                }, 500);
                return;
            }

            function logV3Error(error) {
                const img = new Image();
                img.src =
                    '/ajax_error.gif?message=' +
                    encodeURIComponent('Updater.ExtensionV3Info.Error: ' + error) +
                    '&logLevel=INFO';
                img.width = '1px';
                img.height = '1px';
                document.head.appendChild(img);
            }

            session.state = 'start';

            async function waitExtensionV3Info() {
                try {
                    const resp = await extensionClient.getExtensionInfo();

                    if (Object.prototype.hasOwnProperty.call(resp, 'installed')) {
                        return resp;
                    } else {
                        logV3Error('invalid response object: ' + JSON.stringify(resp));

                        return { installed: false };
                    }
                } catch (e) {
                    logV3Error('extension info error: ' + e.message);

                    return { installed: false };
                }
            }

            Promise.all([getConnection(), waitExtensionV3Info()]).then(function ([client, extensionV3Info]) {
                console.log('extensionV3Info', extensionV3Info);
                var route = 'aw_account_updater_start';
                if (updater3k) {
                    route = 'aw_account_async_updater_start';
                }
                $http
                    .post(
                        Routing.generate(route),
                        {
                            accounts: request.ids,
                            source: request.source,
                            supportedBrowser: window.browserExt.supportedBrowser(),
                            extensionAvailable: window.browserExt.available(),
                            extensionV3Installed: extensionV3Info.installed,
                            extensionV3Supported: extensionV3Info.installed || window.browserExt.extensionV3supported(),
                            startKey: session.startKey,
                            client: client,
                        },
                        {
                            disableErrorDialog: true,
                        },
                    )
                    .then((res) => {
                        const data = res.data;
                        if (!session.state || session.state !== 'start') return;
                        if (data.startKey !== session.startKey) return;
                        session.startKey = null;

                        session.key = data.key || false;
                        if (session.key === false) {
                            fail(data, 'updater fail');
                        } else {
                            session.state = 'update';
                            console.log('start events', data.events);
                            session.queueIndex = request.tickCallback(data.events) || session.queueIndex;

                            if (updater3k) {
                                openChannel(client, data.socketInfo);
                            } else {
                                session.updateTimer = $timeout(function () {
                                    update();
                                }, constants.updateTimeout);
                            }

                            resetFailTimer();
                        }
                    })
                    .catch(function (res, status) {
                        console.log('error', res);
                        const data = res.data;
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
            });
        }

        function onMessage(message) {
            if (session.gotHistory) {
                var events = [];
                $.each(message.data, function (index, value) {
                    events.push(value[1]);
                });
                console.log(events);
                session.queueIndex = request.tickCallback(events) || session.queueIndex;
                if (events.length > 0) {
                    resetFailTimer();
                }
            } else {
                session.messagesBuffer = session.messagesBuffer.concat(message.data);
            }
        }

        function openChannel(client, config) {
            console.log('subscribing to ' + config.channel);
            session.subscription = session.connection.subscribe(config.channel, onMessage);
            session.subscription.history().then(function (message) {
                console.log('history messages received', message);
                session.gotHistory = true;
                $.each(message.data, function (index, channelHistory) {
                    if (channelHistory.channel === config.channel) {
                        session.messagesBuffer = session.messagesBuffer.concat(channelHistory.data.reverse());
                    }
                });
                if (session.messagesBuffer.length > 0) {
                    session.messagesBuffer.sort(function (a, b) {
                        return a[0] - b[0];
                    });
                    onMessage({ data: session.messagesBuffer });
                    session.messagesBuffer = [];
                }
            });
        }

        function getConnection() {
            var deferred = jQuery.Deferred();

            if (!updater3k) {
                // is there shortcut to resolve in one line ?
                deferred.resolve();
                return deferred.promise();
            }

            if (session.connection) {
                console.log('already connected to centrifuge');
                // fixes issue with reconnection when vpn changed state
                session.connection.disconnect();
                session.connection.connect();
                deferred.resolve(session.client);
            } else {
                console.log(
                    'connecting to centrifuge; SockJS:' + typeof SockJS + ', winSockJS:' + typeof window.SockJS,
                );
                session.connection = new Centrifuge(window.centrifuge_config); // centrifuge_config defined in updater.html.twig
                session.connection.on('connect', function (context) {
                    console.log('connected to centrifuge');
                    session.client = context.client;
                    deferred.resolve(context.client);
                });
                session.connection.on('disconnect', function (context) {
                    console.log('centrifuge disconnected', context);
                });
                session.connection.connect();
            }
            return deferred.promise();
        }

        function update() {
            if (session.key === null) return;
            if (!session.state) return;
            if (session.state == 'fail') return;
            var sendMessages = [];
            Array.prototype.push.apply(sendMessages, messages);
            var route = Routing.generate('aw_account_updater_progress', {
                key: session.key,
                eventIndex: session.queueIndex,
            });
            if (updater3k) {
                route = Routing.generate('aw_account_async_updater_progress', {
                    key: session.key,
                });
            }
            $http
                .post(
                    route,
                    {
                        messages: sendMessages,
                    },
                    {
                        disableErrorDialog: true,
                    },
                )
                .then(function (res) {
                    const data = res.data;
                    if (!session.state || session.state !== 'update') return;

                    const changed = Object.keys(data || {}).length;

                    session.queueIndex = request.tickCallback(data) || session.queueIndex;

                    if (!updater3k) {
                        session.updateTimer = $timeout(function () {
                            update();
                        }, constants.updateTimeout);
                    }

                    messages = messages.filter(function (message) {
                        return sendMessages.indexOf(message) === -1;
                    });

                    if (changed > 0) {
                        resetFailTimer();
                    }
                })
                .catch(function (res) {
                    const data = res.data;
                    const status = res.status;
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

        function resetFailTimer() {
            $timeout.cancel(session.failTimer);
            session.failTimer = $timeout(function () {
                fail(false, 'timeout');
            }, constants.failTimeout);
        }

        function fail(data, status) {
            var state = session.state;
            $timeout.cancel(session.updateTimer);
            $timeout.cancel(session.failTimer);
            session.updateTimer = null;
            session.failTimer = null;
            session.state = 'fail';
            session.key = null;
            request.failCallback(state);
        }

        function done() {
            $timeout.cancel(session.updateTimer);
            $timeout.cancel(session.failTimer);
            session.updateTimer = null;
            session.failTimer = null;
            session.state = 'done';
            session.key = null;
            unsubscribe();
        }

        function unsubscribe() {
            if (session.subscription) {
                console.log('unsubscribing from channel');
                session.subscription.unsubscribe();
                session.subscription.removeAllListeners();
                session.subscription = null;
            }
        }

        var self = {
            start: function (ids, tick, fail, source, waitForExtension) {
                if (!self.isDone()) return;
                if (!angular.isArray(ids)) return;
                if (!ids.length) return;
                source = source || 'one'; // edit, group, one, trips
                resetSession();
                session.startKey = Math.round((Math.random() + 1) * 1000000);
                ids = ids.map(function (id) {
                    return parseInt(id.replace(/[^0-9]/g, ''));
                });
                request.ids = ids;
                request.tickCallback = tick;
                request.failCallback = fail;
                request.source = source;
                start(waitForExtension);
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
            isUpdatingState: function () {
                return session.state === 'start' && session.state === 'update';
            },
            isDone: function () {
                return !session.state || session.state === 'done';
            },
            sendMessage: function (action, id, data) {
                id = parseInt(id.replace(/[^0-9]/g, ''));
                if (!id) return;
                messages.push({
                    action: action,
                    id: id,
                    data: data,
                });
            },
            add: function (ids) {
                if (!angular.isArray(ids)) return;
                if (!ids.length) return;
                ids = ids.map(function (id) {
                    return parseInt(id.replace(/[^0-9]/g, ''));
                });
                angular.forEach(ids, function (id) {
                    self.sendMessage('add', id);
                });
                if (updater3k) {
                    update();
                }
            },
            setAnswer: function (id, answer) {
                self.sendMessage('setAnswer', id, answer);
                if (updater3k) {
                    update();
                }
            },
            setPassword: function (id, password) {
                self.sendMessage('setPassword', id, password);
                if (updater3k) {
                    update();
                }
            },
            getState: function () {
                return session.state;
            },
            getKey: function () {
                return session.key;
            },
        };

        return self;
    }

    // Статистика
    function UpdaterResults() {
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
        };

        var self = {
            reset: function () {
                angular.forEach(counters, function (val, counter) {
                    self.setValue(counter, 0);
                });
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
            },
        };
        return self;
    }

    function Updater($rootScope, UpdaterElements, Session, Result, Advertise, isTrips, di) {
        isTrips = !!isTrips;

        var elements = null,
            trips = [],
            accounts = [],
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
                        item.setTripsFound(event.accountData, event.trips);
                        angular.forEach(event.tripIds, function (id) {
                            trips.push(id);
                        });
                        break;
                    case 'trips_not_found':
                        item.setTripsNotFound(event.accountData);
                        break;
                    case 'updated':
                        item.setUnchanged(event.accountData);
                        accounts.push(event.accountData);
                        break;
                    case 'changed':
                        item.setChanged(event.accountData);
                        accounts.push(event.accountData);
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
                        item.setEventCallback(event.type, function () {
                            hasAssign(event, event.accountData, {
                                ErrorCode: 'errorCode',
                                ErrorMessage: 'errorMessage',
                            });
                            changedCallback([event.accountData]);
                        });
                        item.setError(event.accountData);
                        accounts.push(event.accountData);
                        break;
                    case 'disabled':
                        item.setDisabled(event.accountData);
                        accounts.push(event.accountData);
                        Result.incrementValue('disabled');
                        break;
                    case 'fail':
                        if (event.code === -3) {
                            item.setEventCallback('error', function () {
                                hasAssign(event, event.accountData, { ErrorCode: 'code', ErrorMessage: 'message' });
                                changedCallback([event.accountData]);
                            });
                            if (-3 === di.get('accounts').getAccount(event.accountId).ErrorCode)
                                event.accountData.ErrorCode = -3;
                            item.setError(event.accountData);
                            accounts.push(event.accountData);
                        } else {
                            item.setFailed(event.message);
                        }
                        break;
                    case 'extension_required':
                        item.setExtensionRequired(event);
                        // todo last $broadcast!!!
                        $rootScope.$broadcast('accountUpdater.extensionRequired', event);
                        break;
                    case 'extension':
                        var extChecking = elements.findInternalState('extension');
                        if (extChecking.length()) {
                            if (event.accountId != extChecking.first().id) {
                                item.setFailed();
                            }
                        } else {
                            item.setInternalState('extension');
                            item.setChecking(event.expectedDuration);
                            if (browserExt.available()) {
                                browserExt.checkAccount(
                                    originAccountId,
                                    function (params) {},
                                    event.checkIts,
                                    event.providerCode,
                                    function () {
                                        item.setFailed(Translator.trans('updater2.messages.fail.access'));
                                    },
                                );
                            }
                        }
                        break;
                    case 'extension_v3': {
                        extensionClient.connect(
                            event.connectionToken,
                            event.sessionId,
                            function (message) {
                                console.log('extension v3 error', message);
                                item.setFailed();
                            },
                            function () {
                                console.log('extension v3 complete');
                            }
                        ).then(result => {
                            item.setExtensionConnection(result);
                        });
                        item.setInternalState('extension');
                        item.setChecking(event.expectedDuration);
                        break;
                    }
                    case 'local_password':
                        item.setInternalState('password', {
                            label: event.label,
                            displayName: event.displayName,
                        });
                        break;
                    case 'question':
                        item.setInternalState('question', {
                            question: event.question,
                            displayName: event.displayName,
                        });
                        break;
                }
                lastIndex = index;
            });
            if (events) {
                if (accounts.length && changedCallback) {
                    changedCallback(accounts);
                    accounts = [];
                }
                stat();
                nextPopup();
            }
            if (Advertise) {
                Advertise.tick(elements.findCheckingState().getIds());
            }
            return lastIndex;
        }

        function hasAssign(source, destination, keys) {
            for (var i in keys) {
                Object.prototype.hasOwnProperty.call(source, keys[i]) ? (destination[i] = source[keys[i]]) : null;
            }
        }

        function fail(state) {
            // state = (start|update)
            elements.findCheckingState().setFailed();
            elements.findQueueState().setFailed();
            elements.setInternalState(false);
            if (questionActionCancel) questionActionCancel();
            if (passwordActionCancel) passwordActionCancel();
            if (stateCallback) stateCallback('fail');
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
            Result.setValue('progress', ((success + Result.getValue('error')) / Result.getValue('all')) * 100);
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
            start: function (ids, stateFunc, changedFunc, source) {
                if (!Session.isDone()) {
                    throw new Error('Updating session in progress');
                }
                console.log('start update');
                source = source || 'one';
                elements = UpdaterElements.getCollection(ids);
                elements.setQueue();
                Result.reset();
                Result.setValue('all', elements.length());
                trips = [];
                accounts = [];
                stateCallback = stateFunc;
                changedCallback = changedFunc;
                Session.start(ids, tick, fail, source, elements.isExtensionRequire());
            },
            startOne: function (ids, stateFunc, changedFunc) {
                self.start(ids, stateFunc, changedFunc, 'one');
            },
            startGroup: function (ids, stateFunc, changedFunc) {
                self.start(ids, stateFunc, changedFunc, 'group');
            },
            startGroupOrTrips: function (ids, stateFunc, changedFunc) {
                self.start(ids, stateFunc, changedFunc, isTrips ? 'trips' : 'group');
            },
            startTrips: function (ids, stateFunc, changedFunc) {
                self.start(ids, stateFunc, changedFunc, 'trips');
            },
            startEdit: function (ids, stateFunc, changedFunc) {
                self.start(ids, stateFunc, changedFunc, 'edit');
            },
            stop: function () {
                Session.done();
                var extChecking = elements.findInternalState('extension');
                if (extChecking.length()) {
                    extChecking.foreach((item) => {
                        const connection = item.getExtensionConnection();
                        if (connection) {
                            connection.disconnect();
                            item.setExtensionConnection(undefined);
                        }
                    });
                }
                elements.findQueueState().setDone();
                elements.findCheckingState().setDone();
                elements.setInternalState(false);
            },
            end: function () {
                if (
                    elements.getIds().length === 1 &&
                    (elements.first().resultState.indexOf('changed') >= 0 ||
                        elements.first().resultState.indexOf('error') >= 0)
                ) {
                    var showDetailsId = elements.getIds()[0];
                }
                Session.stop();
                trips = [];
                accounts = [];
                stateCallback = null;
                changedCallback = null;
                elements.setEnd();
                elements = null;

                if (showDetailsId) {
                    $rootScope.$broadcast('accountList.loadDetails', showDetailsId);
                }
            },
            getCounters: function () {
                return Result.getResults();
            },
            getAccounts: function () {
                return accounts;
            },
            getTrips: function () {
                return trips;
            },
            isUpdating: function () {
                return Session.isUpdating();
            },
            isUpdatingState: function () {
                return Session.isUpdatingState();
            },
            isDone: function () {
                return Session.isDone();
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
                    //item.setError();
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
            },
        };
        return self;
    }

    service.provider('Updater', function () {
        var trips = false;

        return {
            setTrips: function (data) {
                trips = data;
            },
            $get: [
                '$rootScope',
                '$http',
                '$timeout',
                'DI',
                function ($rootScope, $http, $timeout, di) {
                    var Session = new UpdaterSession($http, $timeout),
                        Results = di.get('updater-results'),
                        Elements = di.get('updater-elements'),
                        Advertise = null;
                    if (di.has('updater-advertise')) Advertise = di.get('updater-advertise');
                    return new Updater($rootScope, Elements, Session, Results, Advertise, trips, di);
                },
            ],
        };
    });

    service.provider('UpdaterResults', function () {
        return {
            $get: [
                function () {
                    return new UpdaterResults();
                },
            ],
        };
    });
});
