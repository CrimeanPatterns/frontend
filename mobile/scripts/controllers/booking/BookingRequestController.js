angular.module('AwardWalletMobile').controller('BookingRequestController', [
    '$q',
    '$scope',
    '$state',
    'RequestID',
    'Booking',
    '$cordovaListener',
    'Media',
    function ($q, $scope, $state, RequestID, Booking, $cordovaListener, Media) {
        if (RequestID) {
            var request = Booking.getRequest(RequestID);
            if (request) {
                $scope.request = request;
                function broadcast(requestId) {
                    request.messages = Booking.getRequestMessages(RequestID);
                    $scope.$broadcast('booking:chat:' + requestId + ':update', request.messages);
                }

                //if ($state.is('index.booking.request.details', {Id: RequestID})) {
                //if (request.statusCode !== -1) {
                $scope.onInfiniteLoad = function () {
                    var q = $q.defer();
                    if (request.messages && request.messages.length > 0 && request.messages[0].id !== 0) {
                        Booking.chunked(RequestID, request.messages[0].id).then(function () {
                            broadcast(RequestID);
                            q.resolve();
                        });
                    }
                    return q.promise;
                };
                $scope.addMessage = function (message) {
                    var q = $q.defer();
                    if (message && message.length > 0) {
                        Booking.addMessage(RequestID, message).then(function () {
                            broadcast(RequestID);
                            q.resolve();
                        }, function () {
                            q.reject();
                        });
                    }
                    return q.promise;
                };
                $scope.updateMessage = function (messageId, message) {
                    var q = $q.defer();
                    if (message && message.length > 0) {
                        Booking.editMessage(RequestID, messageId, message).then(function () {
                            request.messages = Booking.getRequestMessages(RequestID);
                            $scope.$broadcast('booking:chat:' + RequestID + ':update', request.messages);
                            q.resolve();
                        }, function () {
                            q.reject();
                        });
                    }
                    return q.promise;
                };
                $scope.deleteMessage = function (messageId) {
                    var q = $q.defer();
                    Booking.removeMessage(RequestID, messageId).then(function () {
                        request.messages = Booking.getRequestMessages(RequestID);
                        $scope.$broadcast('booking:chat:' + RequestID + ':update', request.messages);
                        q.resolve();
                    }, function () {
                        q.reject();
                    });
                    return q.promise;
                };
                Booking.sync(RequestID).then(function () {
                    broadcast(RequestID);
                });
                Booking.subscribe(RequestID, request.channels);
                $scope.$on('booking:chat:message', function (event, response) {
                    if (parseInt(RequestID) == parseInt(response.requestId)) {
                        broadcast(response.requestId);
                    }
                });
                $scope.$on('booking:chat:join', function (event, response) {
                    if (parseInt(RequestID) == parseInt(response.requestId)) {
                        if (response.new) {
                            Media.play('resources/sounds/online.mp3');
                        }
                        $scope.users = response.users;
                    }
                });
                $scope.$on('booking:chat:leave', function (event, response) {
                    if (parseInt(RequestID) == parseInt(response.requestId)) {
                        $scope.users = response.users;
                    }
                });
                var cordovaListeners = {
                    resume: function () {
                        Booking.subscribe(RequestID, request.channels);
                    },
                    pause: function () {
                        Booking.unsubscribe(request.channels);
                    }
                };
                $scope.$on('$destroy', function () {
                    Booking.unsubscribe(request.channels);
                    Booking.markAsRead(RequestID);
                    if (platform && platform.cordova) {
                        $cordovaListener.unbind('resume', cordovaListeners.resume);
                        $cordovaListener.unbind('pause', cordovaListeners.pause);
                    }
                });
                if (platform && platform.cordova) {
                    $cordovaListener.bind('resume', cordovaListeners.resume);
                    $cordovaListener.bind('pause', cordovaListeners.pause);
                }
                /* } else {
                 $state.go('index.booking.request.not-verified', {Id: RequestID});
                 }*/
                /*} else {
                 if (request.statusCode > -1) {
                 $state.go('index.booking.request.details', {Id: RequestID});
                 }
                 }*/
            } else {
                $state.go('index.booking.request.not-exists');
            }
        } else {
            $state.go('index.booking.list');
        }
    }
]);