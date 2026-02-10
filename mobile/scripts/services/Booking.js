angular.module('AwardWalletMobile').service('Booking', [
    '$rootScope',
    '$q',
    '$http',
    'GlobalError',
    'Centrifuge',
    function ($rootScope, $q, $http, GlobalError, Centrifuge) {
        var requests = {}, requestsArray = [], userMessagesChannel = null, subscriptions = {}, onlineCallbacks = {};

        function setRequests(data, skipEvent) {
            var temp = {};
            if (!(data instanceof Array)) {
                throw new TypeError('Booking.setRequests called on non-array');
            }
            if (data) {
                requestsArray = [];
                for (var i = 0, l = data.length, tempRequest; i < l, tempRequest = data[i]; i++) {
                    requestsArray.push(tempRequest.id);
                    temp[tempRequest.id] = tempRequest;
                }
                if (Object.keys(requests).length > 0) {
                    for (var requestId in requests) {
                        if (requests.hasOwnProperty(requestId) && temp.hasOwnProperty(requestId)) {
                            merge(requestId, temp[requestId].messages, false, false, !skipEvent);
                            temp[requestId].messages = requests[requestId].messages;
                        }
                    }
                }
                requests = temp;

                if (!skipEvent)
                    $rootScope.$broadcast('booking:update');
            }
        }

        function getRequests() {
            return {sort: requestsArray, requests: requests};
        }

        function getRequest(requestId) {
            if (requestId && requests.hasOwnProperty(requestId)) {
                return angular.extend({}, requests[requestId]);
            }
            return null;
        }

        function getRequestMessages(requestId) {
            if (requestId && requests.hasOwnProperty(requestId)) {
                return requests[requestId].messages
            }
        }

        function sync(requestId, messages, range) {
            var q = $q.defer(), markAsRead = !messages && !range, messages = messages || {}, range = range || [];
            if (requestId && requests.hasOwnProperty(requestId)) {
                if ((Object.keys(messages).length == 0 && range.length == 0) && requests[requestId].messages && requests[requestId].messages.length > 0) {
                    for (var i = 0, l = requests[requestId].messages.length; i < l; i++) {
                        if (i == 0 || i == l - 1) {
                            range.push(requests[requestId].messages[i].id)
                        }
                        messages[requests[requestId].messages[i].id] = requests[requestId].messages[i].internalDate || null;
                    }
                }
                $http(
                    {
                        method: 'post',
                        url: '/booking/request/' + requestId + '/sync/' + (markAsRead ? '1' : '0'),
                        timeout: 30000,
                        data: {messages: messages}
                    }).then(function (response) {
                    if (response.data && angular.isObject(response.data) && response.data.success) {
                        merge(requestId, response.data.messages, true, range);
                        q.resolve(response.data.messages);
                    } else {
                        q.reject();
                    }
                }, function () {
                    q.reject();
                });
            } else {
                q.reject();
            }
            return q.promise;
        }

        function chunked(requestId, messageId) {
            var q = $q.defer();
            $http(
                {
                    method: 'get',
                    url: '/booking/request/' + requestId + '/chunk/' + messageId,
                    timeout: 30000
                }).then(function (response) {
                if (response.data && angular.isObject(response.data) && response.data.messages) {
                    merge(requestId, response.data.messages);
                    q.resolve();
                } else {
                    q.reject();
                }
            }, function () {
                q.reject();
            });
            return q.promise;
        }

        function merge(requestId, messages, removeNonExsist, range, skipEvent) {
            var request, notify = false;
            if (requests.hasOwnProperty(requestId)) {
                request = requests[requestId];
                if (removeNonExsist && range) {
                    for (var i = 0, removeMessage = true; i < request.messages.length; i++) {
                        removeMessage = true;
                        if (messages.length > 0) {
                            for (var j = 0, k = messages.length; j < k; j++) {
                                if (request.messages[i].id == messages[j].id) {
                                    removeMessage = false;
                                }
                            }
                        }
                        if (removeMessage) {
                            //remove if message.id in range [firstMessageId, lastMessageId]
                            if ((range[0] && !range[1] && request.messages[i].id == range[0]) || (request.messages[i].id >= range[0] && request.messages[i].id <= range[1])) {
                                notify = true;
                                //remove
                                request.messages.splice(i, 1);
                                i--;
                            }
                        }
                    }
                }
                if (messages.length > 0) {
                    for (i = 0; i < messages.length; i++) {
                        for (j = 0, k = request.messages.length; j < k; j++) {
                            if (messages[i].id == request.messages[j].id) {
                                if (
                                    !messages[i].internalDate || // some messages are unversioned
                                    (messages[i].internalDate > request.messages[j].internalDate)
                                ) {
                                    request.messages[j] = messages[i];
                                    notify = true;
                                }
                            } else {
                                if (messages[i].body && messages[i].body.length > 0) {
                                    //get position to insert message
                                    if (messages[i].id > request.messages[j].id && (j == k - 1 || (j < k - 1 && messages[i].id < request.messages[j + 1].id))) {
                                        request.messages.splice(j + 1, 0, messages[i]); //insert after
                                        notify = true;
                                    } else if (messages[i].id < request.messages[j].id && (j == 0 || (j > 0 && messages[i].id > request.messages[j - 1].id))) {
                                        request.messages.splice(j, 0, messages[i]); //insert before
                                        notify = true;
                                    }
                                }
                            }
                        }
                        if (!request.newMessage && messages[i].readed == false)
                            request.newMessage = true;

                    }
                    if (request.messages[request.messages.length - 1].requestUpdateDate)
                        request.lastUpdateDate = request.messages[request.messages.length - 1].requestUpdateDate;
                }
                if (!skipEvent && notify)
                    $rootScope.$broadcast('booking:update');
            }
        }

        function removeMessage(requestId, messageId) {
            var q = $q.defer();
            if (requestId && messageId && requests.hasOwnProperty(requestId)) {
                $http({
                    method: 'delete',
                    url: '/booking/request/' + requestId + '/message/delete/' + messageId,
                    timeout: 30000
                }).then(function (response) {
                    if (response && response.data && response.data.success) {
                        merge(requestId, [], true, [messageId]);
                        q.resolve();
                    } else {
                        GlobalError.show(Translator.trans('message.delete.fail', {}, 'mobile'));
                        q.reject();
                    }
                }, function () {
                    GlobalError.show(Translator.trans(/** @Desc("Error occurred. The message has not been deleted.") */'message.delete.fail', {}, 'mobile'));
                    q.reject();
                });
            } else {
                q.reject();
            }
            return q.promise;
        }

        function addMessage(requestId, message) {
            var q = $q.defer();
            if (requestId && message) {
                $http({
                    method: 'put',
                    url: '/booking/request/' + requestId + '/message/add',
                    data: {message: message},
                    timeout: 30000,
                    globalError: false
                }).then(function (response) {
                    if (response && response.data && response.data.success) {
                        response.data.message.new = true;
                        merge(requestId, [response.data.message]);
                        q.resolve();
                    } else {
                        GlobalError.show(Translator.trans('message.send.fail', {}, 'mobile'));
                        q.reject();
                    }
                }, function () {
                    GlobalError.show(Translator.trans(/** @Desc("Error occurred. The message has not been sent.") */ 'message.send.fail', {}, 'mobile'));
                    q.reject();
                });
            } else {
                q.reject();
            }
            return q.promise;
        }

        function editMessage(requestId, messageId, message) {
            var q = $q.defer();
            if (requestId && messageId && message && requests.hasOwnProperty(requestId)) {
                $http({
                    method: 'post',
                    url: '/booking/request/' + requestId + '/message/edit/' + messageId,
                    data: {message: message},
                    timeout: 30000
                }).then(function (response) {
                    if (response && response.data && response.data.success) {
                        merge(requestId, [response.data.message]);
                        q.resolve();
                    } else {
                        GlobalError.show(Translator.trans('message.update.fail', {}, 'mobile'));
                        q.reject();
                    }
                }, function () {
                    GlobalError.show(Translator.trans(/** @Desc("Error occurred. The message has not been updated.") */'message.update.fail', {}, 'mobile'));
                    q.reject();
                });
            } else {
                q.reject();
            }
            return q.promise;
        }

        function updatePresence(action, subscription, requestId, message) {
            var clientInfo = Centrifuge.getClientInfo();
            var request = getRequest(requestId);
            var joined = message.data.user;

            subscription.presence().then(function (response) {
                var users = [];
                response = response.data;
                for (var key in response) {
                    if (response.hasOwnProperty(key)) {
                        var user = response[key];
                        var username;

                        if (request && user.user === request.contactUid) {
                            username = request.contactName;
                        } else {
                            username = user.default_info.username
                        }

                        if (!user.default_info.impersonated && (clientInfo && user.user !== clientInfo.user && users.indexOf(username) === -1))
                            users.push(username);
                    }
                }
                if (action == 'leave' || (action == 'join' && users.length > 0)) {
                    $rootScope.$evalAsync(function() {
                        $rootScope.$broadcast('booking:chat:' + action, {
                            requestId: requestId,
                            users: users,
                            new: joined !== clientInfo.user
                        });
                    });
                }
            });
        }

        function subscribe(requestId, channels) {
            if (!onlineCallbacks.hasOwnProperty(channels.$abrequestonline)) {
                onlineCallbacks[channels.$abrequestonline] = {
                    join: function(mess) {
                        updatePresence('join', this, requestId, mess);
                    },
                    leave: function(mess) {
                        updatePresence('leave', this, requestId, mess);
                    },
                };
            }
            subscriptions[channels.$booker] = Centrifuge.getConnection().subscribe(channels.$booker);
            subscriptions[channels.$abrequestonline] = Centrifuge.getConnection().subscribe(channels.$abrequestonline, onlineCallbacks[channels.$abrequestonline]);
        }

        function unsubscribe(channels) {
            if (channels) {
                if (subscriptions.hasOwnProperty(channels.$abrequestonline)) {
                    subscriptions[channels.$abrequestonline].unsubscribe();
                }
                if (subscriptions.hasOwnProperty(channels.$booker)) {
                    subscriptions[channels.$booker].unsubscribe();
                }
            } else {
                for (var channelName in subscriptions) {
                    if (subscriptions.hasOwnProperty(channelName)) {
                        subscriptions[channelName].unsubscribe();
                    }
                }
            }
        }

        function connect() {
            Centrifuge.connect();
            if (!subscriptions.userMessages) {
                subscriptions.userMessages = Centrifuge.getConnection().subscribe(userMessagesChannel, function (response) {
                    response = response.data;
                    if (response && response.requestId) {
                        var messages = {},
                            isMessageExists = !!getRequestMessage(response.requestId, response.messageId);
                        messages[response.messageId] = 0;
                        sync(response.requestId, messages, [response.messageId]).then(function () {
                            var clientInfo = Centrifuge.getClientInfo();

                            if (!clientInfo) {
                                return;
                            }

                            $rootScope.$broadcast('booking:chat:message', angular.extend({}, response, {
                                messageExists: isMessageExists,
                                ownerId: parseInt(clientInfo.user)
                            }));
                            if (response.mobileDataReload) {
                                $rootScope.$broadcast('database:expire');
                            }
                        });
                    }
                });
            }
        }

        function markAsRead(requestId) {
            var notify = false;
            if (requestId && requests.hasOwnProperty(requestId) && requests[requestId].messages && requests[requestId].messages.length > 0) {
                for (var i = 0, l = requests[requestId].messages.length; i < l; i++) {
                    if (requests[requestId].messages[i].readed == false) {
                        notify = true;
                    }
                    requests[requestId].messages[i].readed = true;
                }
                requests[requestId].newMessage = false;
                if (notify)
                    $rootScope.$broadcast('booking:update');
            }
        }

        function setUserMessagesChannel(channel) {
            userMessagesChannel = channel;
        }

        function getRequestMessage(requestId, messageId) {
            var messages = getRequestMessages(requestId);

            if (!(messages && messages.length)) {
                return;
            }

            // TODO: hashmap
            for (var i = 0; i < messages.length; i++) {
                if (messages[i].id == messageId) {
                    return messages[i];
                }
            }
        }

        function openRequestExternal(requestId) {
            var url = BaseUrl + '/awardBooking/view/' + requestId;
            if (platform.cordova)
                url += '?fromapp=1&KeepDesktop=1';
            var ref = window.open(url, '_blank');
            markAsRead(requestId);
            ref.addEventListener('close', function () {
                markAsRead(requestId);
            });
        }

        function resend(requestId) {
            var q = $q.defer();
            if (requestId && requests.hasOwnProperty(requestId)) {
                $http({
                    method: 'post',
                    url: '/booking/request/' + requestId + '/resend',
                    timeout: 30000
                }).then(function (response) {
                    if (response && response.data && response.data.success) {
                        q.resolve();
                    } else {
                        q.reject();
                    }
                }, function () {
                    q.reject();
                });
            } else {
                q.reject();
            }
            return q.promise;
        }

        var _this = {
            getRequest: getRequest,
            getRequests: getRequests,
            setRequests: setRequests,
            setUserMessagesChannel: setUserMessagesChannel,
            getRequestMessages: getRequestMessages,
            getRequestMessage: getRequestMessage,
            openRequestExternal: openRequestExternal,
            sync: sync,
            chunked: chunked,
            removeMessage: removeMessage,
            addMessage: addMessage,
            editMessage: editMessage,
            subscribe: subscribe,
            unsubscribe: unsubscribe,
            connect: connect,
            markAsRead: markAsRead,
            resend: resend,
            getUnread: function () {
                var requestId;
                if (requestsArray && requestsArray.length > 0) {
                    for (var i = 0, l = requestsArray.length; i < l; i++) {
                        if (requests[requestsArray[i]] && requests[requestsArray[i]].newMessage) {
                            requestId = requests[requestsArray[i]].id;
                            break;
                        }
                    }
                }
                return requestId;
            },
            destroy: function () {
                unsubscribe();

                requests = {};
                requestsArray = [];
                userMessagesChannel = null;
                subscriptions = {},
                onlineCallbacks = {};
            }
        };

        $rootScope.$on('app:storage:destroy', _this.destroy);

        return _this;
    }
]);
