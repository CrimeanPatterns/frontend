angular.module('AwardWalletMobile').factory('Card', [
    '$q',
    '$http',
    '$cordovaFileTransfer',
    'SessionService',
    'BaseUrl',
    'ApiUrl',
    'AccountList',
    function ($q, $http, $cordovaFileTransfer, SessionService, BaseUrl, ApiUrl, AccountList) {

        function cleanup(filePrefix) {
            var q = $q.defer();
            navigator.camera.cleanup(function () {
                q.resolve();
            }, function () {
                q.resolve();
            }, filePrefix);
            return q.promise;
        }

        function getLocalFile(fileName, filePath) {
            var q = $q.defer();
            if (platform.cordova) {
                navigator.camera.getImage(fileName, function (filePath) {
                    q.resolve(filePath)
                }, function (message) {
                    q.reject(message);
                });
            } else {
                q.resolve(filePath)
            }
            return q.promise;
        }

        function download(url, fileName) {
            var directory = platform.ios ? cordova.file.documentsDirectory : cordova.file.cacheDirectory;
            return $cordovaFileTransfer.download(url, directory + fileName, {}, true);
        }

        function capture(options, onCapture, onClose) {
            options = angular.extend({}, options, {
                onCapture: onCapture,
                onClose: onClose || angular.noop
            });
            navigator.camera.takePicture(options);
        }

        function removeImage(fileName) {
            var q = $q.defer();
            navigator.camera.removeImage(fileName, function () {
                q.resolve();
            }, function () {
                q.resolve();
            });
            return q.promise;
        }


        function upload(serverUrl, image) {
            var data = {
                kind: image.side,
                UUID: image.UUID || null,
                barcode: image.barcode || null
            };

            return $cordovaFileTransfer.upload(serverUrl, image.path, {
                fileName: image.name,
                headers: {
                    'X-XSRF-TOKEN': SessionService.getProperty('X-XSRF-TOKEN'),
                    'X-AW-VERSION': app.version,
                    'X-AW-PLATFORM': platform.ios ? 'ios' : 'android',
                    'Accept-Type': 'application/json, image/*'
                },
                params: {
                    data: JSON.stringify(data)
                }
            }, true);
        }

        function attach(serverUrl, imageId) {
            return $http({
                url: serverUrl,
                method: 'POST',
                timeout: 30000,
                globalError: false,
                data: {
                    CardImageId: imageId
                },
                retries: 5
            });
        }

        var Card = function Card(props) {
            var defaultProps = {
                    FileName: null,
                    Url: null,
                    localPath: null,
                    state: 0,
                    Label: null,
                    timeout: {
                        upload: null
                    }
                },
                _this = this;

            props = angular.extend({}, props || {
                accountId: null,
                subAccountId: null,
                images: {
                    Front: {},
                    Back: {}
                }
            });

            _this.images = {};
            _this.accountId = props.accountId;

            if (props.UUID)
                _this.UUID = props.UUID;

            if (props.subAccountId)
                _this.subAccountId = props.subAccountId;

            for (var key in props.images) {
                if (props.images.hasOwnProperty(key)) {
                    _this.images[key] = angular.extend({}, defaultProps, props.images[key]);
                    if (_this.images[key].FileName) {
                        (function process(side) {
                            var self = this;
                            if (self.state !== _this.states.REMOVED) {
                                getLocalFile(self.FileName, self.Url).then(function (filePath) {
                                    self.localPath = filePath;
                                    if (self.localPath && !self.Url && self.state === _this.states.PENDING) {
                                        _this.upload({side: side, name: self.FileName, path: self.localPath});
                                    } else {
                                        self.state = _this.states.RESOLVED;
                                    }
                                }, function () {
                                    self.localPath = null;
                                    cleanup(side).then(function () {
                                        if (self.Url && self.state !== _this.states.LOADING) {
                                            self.state = _this.states.LOADING;
                                            download(self.Url, self.FileName).then(function (file) {
                                                self.localPath = file.nativeURL;
                                                self.state = _this.states.RESOLVED;
                                            }, function (error) {
                                                if (
                                                    error &&
                                                    error.code === FileTransferError.FILE_NOT_FOUND_ERR
                                                ) {
                                                    self.localPath = null;
                                                    self.FileName = null;
                                                    self.Url = null;
                                                    self.state = _this.states.REJECTED;
                                                } else {
                                                    self.state = _this.states.PENDING;
                                                }
                                            });
                                        }
                                    });
                                });
                            } else {
                                _this.remove(side);
                            }
                        }).call(_this.images[key], key);
                    } else {
                        _this.images[key].localPath = null;
                        _this.images[key].FileName = null;
                        _this.images[key].Url = null;
                        _this.images[key].state = _this.states.PENDING;
                    }
                }
            }

            _this.getServerUrl = function () {
                var serverUrl;
                if (_this.accountId) {
                    _this.accountType = _this.accountId.charAt(0) === 'a' ? 'account' : 'coupon';
                    serverUrl = ['', 'cardImage', _this.accountType, _this.accountId.substr(1)].join('/');
                    if (_this.subAccountId) {
                        serverUrl = [serverUrl, _this.subAccountId].join('/');
                    }
                } else {
                    serverUrl = ['', 'cardImage', ''].join('/');
                }
                return serverUrl;
            };

            _this.setAccountId = function (accountId) {
                _this.accountId = accountId;
                _this.accountType = _this.accountId.charAt(0) === 'a' ? 'account' : 'coupon';
            };

            _this.upload = function (response, callback) {
                var self = _this.images[response.side],
                    serverUrl = _this.getServerUrl();

                self.state = _this.states.LOADING;

                $http({
                    url: serverUrl,
                    method: 'post',
                    globalError: false,
                    retries: 5
                }).then(function () {
                    if (self.localPath) { //if not removed
                        upload(BaseUrl + ApiUrl + serverUrl, response).then(function (data) {
                            var response = JSON.parse(data.response);
                            self.state = _this.states.RESOLVED;
                            if (response.account) {
                                AccountList.setAccount(response.account);
                            } else if (response.CardImageId && _this.accountId) {
                                attach(serverUrl, response.CardImageId);
                            }
                            if (callback)
                                callback(response);
                        }, function (error) {
                            self.state = _this.states.REJECTED;
                            if (error && error.code !== FileTransferError.CONNECTION_ERR) {
                                self.localPath = null;
                                self.Url = null;
                                self.FileName = null;
                                self.state = _this.states.REMOVED;
                                if (callback)
                                    callback(error);
                            } else {
                                clearTimeout(self.timeout.upload);
                                self.timeout.upload = setTimeout(function () {
                                    _this.upload(response, callback);
                                }, 5000);
                            }
                        });
                    }
                }, function (reject) {
                    clearTimeout(self.timeout.upload);
                    if (reject && reject.status !== 403) {
                        self.timeout.upload = setTimeout(function () {
                            _this.upload(response, callback);
                        }, 5000);
                    }
                });
            };

            _this.capture = function (options, onCapture, onClose) {
                capture(options, function (response) {
                    response.side = response.side.charAt(0).toUpperCase() + response.side.slice(1);
                    _this.images[response.side] = _this.images[response.side] || {};
                    _this.images[response.side].localPath = response.path;
                    _this.images[response.side].FileName = response.name;
                    if (!onCapture) {
                        _this.upload(response);
                    } else {
                        onCapture(response);
                    }
                }, onClose);
            };

            _this.cleanup = function () {
                for (var key in _this.images) {
                    if (_this.images.hasOwnProperty(key)) {
                        if (
                            _this.images[key].timeout &&
                            _this.images[key].timeout.upload
                        )
                            clearTimeout(_this.images[key].timeout.upload);
                        if (_this.images[key].FileName)
                            removeImage(_this.images[key].FileName);
                    }
                }
            };

            _this.remove = function (side) {
                var self = _this.images[side];
                if (self.timeout && self.timeout.upload)
                    clearTimeout(self.timeout.upload);
                removeImage(self.FileName).then(function () {
                    self.localPath = null;
                    self.Url = null;
                    self.FileName = null;
                    self.state = _this.states.REMOVED;
                });
                if (_this.accountId) {
                    $http({
                        url: _this.getServerUrl(),
                        method: 'DELETE',
                        timeout: 30000,
                        globalError: false,
                        data: {
                            kind: side
                        },
                        retries: 5
                    }).then(function (response) {
                        response = response.data;
                        self.state = _this.states.PENDING;
                        if (response.account) {
                            AccountList.setAccount(response.account);
                        }
                    });
                }
            };

            return _this;
        };

        Card.prototype.states = {
            PENDING: 1,
            LOADING: 2,
            RESOLVED: 3,
            REJECTED: 4,
            REMOVED: 5
        };

        return Card;
    }
]);