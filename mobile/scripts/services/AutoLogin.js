angular.module('AwardWalletMobile').service('AutoLogin', [
    '$http',
    '$q',
    '$cordovaSafariWebView',
    function ($http, $q, $cordovaSafariWebView) {
        var ref = null;

        function getApi(accountId, type, flightStatus) {
            var defer = $q.defer();
            $http({
                method: 'GET',
                url: '/account/autologin/' + type + '/' + accountId + (flightStatus ? '' : '/1/1') + '?' + Math.random(),
                cache: false,
                timeout: 30000,
                globalError: false,
                retries: 3
            }).then(function (response) {
                if (typeof(response.data) == 'object') {
                    defer.resolve(response.data);
                } else {
                    try {
                        eval('(function(){var $=jQuery={};' + response.data + '})()');
                        defer.resolve(response.data);
                    } catch (e) {
                        console.log('Syntax error in api', e, response.data);
                        defer.reject();
                    }
                }
            }, function (response) {
                if (typeof(response.data) == 'object') {
                    defer.resolve(response.data);
                } else {
                    console.log('Error load api', response);
                    defer.reject();
                }
            });
            return defer.promise;
        }

        function showFlightStatus(provider, param) {
            if (!param)
                return;
            var providerCode = provider;
            getApi(providerCode, 'extension', true).then(function (flightStatusExtenstion) {
                try {
                    var plugin = {};
                    eval(flightStatusExtenstion);
                    if (typeof(plugin.flightStatus) != 'undefined' && typeof(plugin.flightStatus.match) != 'undefined') {
                        if (!plugin.flightStatus.match.test(param.flightNumber)) {
                            alert('Sorry, we are not able to show flight status for this flight number.');
                            return;
                        }
                    }
                    openBrowser({
                        url: plugin.flightStatus.url,
                        params: param,
                        data: flightStatusExtenstion,
                        providerCode: providerCode,
                        reload: plugin.flightStatus.reload,
                        action: 'flightStatus',
                        clearCache: plugin.clearCache,
                        kind: 'flightstatus',
                        userAgent: plugin.mobileUserAgent,
                        hosts: plugin.hosts
                    });
                } catch (e) {
                    alert('Sorry, we are not able to show flight status for this airline yet.');
                    console.log('Syntax error in api for ', providerCode, e);
                }
            }, function () {
                alert('Sorry, we are not able to show flight status for this airline yet.');
                console.log('Requested missing api for ', providerCode);
            });
        }


        function startExtensionAutologin(account) {
            if (account.Autologin.mobileExtension == true && isCordova) {
                var defer = $q.defer();
                getApi(account.ID, 'mobile').then(function (mobileExtension) {
                    if (!angular.isObject(mobileExtension)) {
                        defer.resolve();
                        try {
                            var plugin = {}, params = {};
                            eval(mobileExtension);
                            if (typeof(plugin.autologin) == 'undefined') {
                                startDesktopExtensionAutologin(account);
                            } else {
                                var startingUrl = plugin.autologin.url;
                                if (typeof(plugin.autologin.getStartingUrl) == 'function')
                                    startingUrl = plugin.autologin.getStartingUrl(params);
                                console.log('autologin', 'open browser from mobile extension, done');

                                openBrowser({
                                    url: startingUrl,
                                    params: params,
                                    data: mobileExtension,
                                    accountId: account.ID,
                                    providerCode: account.ProviderCode,
                                    reload: true,
                                    action: 'autologin',
                                    clearCache: plugin.clearCache,
                                    kind: 'autologin',
                                    userAgent: plugin.mobileUserAgent,
                                    hosts: plugin.hosts
                                });
                            }
                        } catch (e) {
                            console.log('Syntax error in api for ' + account.ProviderCode);
                            console.log('autologin', 'open browser from mobile login url, exception', e);
                            openBrowser({
                                url: account.Autologin.loginUrl,
                                accountId: account.ID,
                                reload: true
                            });
                        }
                    } else {
                        defer.resolve(mobileExtension);
                    }
                }, function () {
                    defer.resolve();
                    openBrowser({
                        url: account.Autologin.loginUrl,
                        accountId: account.ID,
                        reload: true
                    });
                });
                return defer.promise;
            } else {
                return startDesktopExtensionAutologin(account);
            }
        }

        function startDesktopExtensionAutologin(account) {
            var defer = $q.defer();
            if (account.Autologin.desktopExtension == true && isCordova) {
                getApi(account.ID, 'desktop').then(function (desktopExtension) {
                    if (typeof(desktopExtension) == 'string') {
                        defer.resolve();
                        try {
                            var plugin = {}, params = {};
                            eval(desktopExtension);
                            openBrowser({
                                url: plugin.getStartingUrl(params),
                                params: params,
                                data: desktopExtension,
                                accountId: account.ID,
                                providerCode: account.ProviderCode,
                                reload: true,
                                clearCache: plugin.clearCache,
                                kind: 'autologin',
                                userAgent: plugin.mobileUserAgent,
                                hosts: plugin.hosts
                            });
                        } catch (e) {
                            console.log('Syntax error in api for ' + account.ProviderCode);
                            console.log('autologin', 'open browser from mobile login url, exception');
                            openBrowser({
                                url: account.Autologin.loginUrl,
                                accountId: account.ID,
                                reload: true
                            });
                        }
                    } else {
                        defer.resolve(desktopExtension);
                    }
                }, function () {
                    defer.resolve();
                    openBrowser({
                        url: account.Autologin.loginUrl,
                        accountId: account.ID,
                        reload: true
                    });
                });
            } else {
                console.log('autologin', 'open browser from desktop extension login url, desktop extension disabled');
                openBrowser({
                    url: account.Autologin.loginUrl,
                    accountId: account.ID,
                    reload: true
                });
                defer.resolve();
            }
            return defer.promise;
        }

        function startExtensionUpdate(accountId) {
            var defer = $q.defer();
            getApi(accountId, 'desktop').then(function (desktopExtension) {
                if (typeof (desktopExtension) == 'string') {
                    try {
                        var plugin = {}, params = {};
                        eval(desktopExtension);
                        openBrowser({
                            url: plugin.getStartingUrl(params),
                            params: params,
                            data: desktopExtension,
                            accountId: accountId,
                            providerCode: params.providerCode,
                            reload: true,
                            clearCache: plugin.clearCache,
                            hideOnStart: plugin.hideOnStart,
                            update: true,
                            kind: 'update',
                            userAgent: plugin.mobileUserAgent,
                            hosts: plugin.hosts
                        }).then(function () {
                            defer.resolve();
                        }, function () {
                            defer.reject();
                        });
                    } catch (e) {
                        console.log('Syntax error in api for ' + accountId, e);
                        defer.reject();
                    }
                } else {
                    defer.reject(desktopExtension);
                }
            }, function () {
                defer.reject();
            });
            return defer.promise;
        }

        function openCashback(url) {
            $cordovaSafariWebView.open(url, function (result) {
                if (result.event == 'loaded') {
                    $cordovaSafariWebView.close();
                }
            }, angular.noop, {
                hidden: true,
                animated: false,
                transition: 'curl'
            });
        }

        function saveProperties(accountId, properties, errorMessage, errorCode) {
            var data = {accountId: accountId, properties: properties};
            if (errorMessage && errorMessage.length > 0) {
                data.errorMessage = errorMessage;
            }
            if (errorCode) {
                data.errorCode = errorCode;
            }
            return $http({
                method: 'POST',
                url: '/account/receive-from-browser',
                cache: false,
                timeout: 30000,
                data: data,
                globalError: false
            });
        }

        function saveLog(accountId, data) {
            var log = angular.extend({}, data);
            return $http({
                url: '/account/receive-browser-log',
                method: 'POST',
                data: {accountId: accountId, log: log},
                cache: false,
                timeout: 30000,
                globalError: false
            }).then(function () {
                log = [];
            }, function () {
                log = [];
            });
        }

        function submitStat(code, success, error, kind, accountId) {
            success = (success) ? 1 : 0;
            var errorCode = 2;
            if (typeof(error) == 'undefined' || success == 1) {
                errorCode = 1;
                error = "";
            }// if (typeof(error) == "undefined" || success == 1)
            else {
                if (typeof (error) == 'object') {
                    if (typeof error[1] != 'undefined') {
                        errorCode = error[1];
                    }
                    error = error[0];
                }
            }
            $http({
                url: '/extension/extensionStats.php',
                method: 'POST',
                data: {
                    providerCode: code,
                    success: success,
                    errorMessage: error,
                    errorCode: errorCode,
                    mobileKind: kind,
                    accountId: accountId
                },
                cache: false,
                timeout: 30000,
                globalError: false
            });
        }

        function URL(href) {
            var url = document.createElement('a');
            url.href = href;
            return url;
        }

        function parseQueryVariable(url) {
            var query = url && url.search ? url.search.substring(1) : '';
            var vars = query.split('&'), pair;
            var output = {};
            if (query && vars.length > 0) {
                for (var i = 0; i < vars.length; i++) {
                    pair = vars[i].split('=');
                    output[pair[0]] = decodeURIComponent(pair[1]);
                }
            }
            return output;
        }

        function close() {
            if (ref && ref.close) {
                ref.close();
            }
            ref = null;
        }

        function hide() {
            if (ref && ref.hide) {
                ref.hide();
            }
        }

        function show() {
            if (ref && ref.show) {
                ref.show();
            }
        }

        function openBrowser(properties) {
            var defaultProperties = {
                url: '',
                params: {},
                data: null,
                accountId: null,
                providerCode: null,
                reload: false,
                action: null,
                clearCache: false,
                hideOnStart: false,
                update: false,
                kind: null,
                userAgent: null
            };

            properties = angular.extend(defaultProperties, properties);

            var url = properties.url,
                params = properties.params,
                data = properties.data,
                accountId = properties.accountId,
                providerCode = properties.providerCode,
                reload = properties.reload,
                action = properties.action,
                clearcache = properties.clearCache,
                hidden = properties.hideOnStart,
                update = properties.update,
                kind = properties.kind,
                options = {
                    enableViewPortScale: true,
                    clearCache: clearcache,
                    hidden: hidden
                };

            if (properties.userAgent) {
                options.userAgent = properties.userAgent;
            }

            if (properties.hosts) {
                options.hosts = properties.hosts;
            }

            if (platform.cordova) {
                ref = window.open(url, '_blank', options);
            } else {
                ref = window.open(url, '_blank');
            }
            ref.opener = null;

            var defer = $q.defer();
            var reloading = false,
                step = 'start',
                command = '',
                stepHistory = [],
                checkInMobile = update || false,
                mobileLogs = [];

            if (checkInMobile) {
                mobileLogs.push({type: 'message', content: 'Mobile Log'});
                mobileLogs.push({type: 'message', content: 'App Version: ' + app.version});
                mobileLogs.push({
                    type: 'message',
                    content: 'Platform: ' + window.device.platform + ' ' + window.device.version
                });
            }

            var checkStep = function () {
                if (step === '') {
                    stepHistory = [];
                    return false;
                }
                var times = 0;
                for (var i = 0; i < stepHistory.length; i++) {
                    if (step === stepHistory[i]) {
                        times++;
                    }
                }
                if (times > 10) {
                    step = '';
                    return false;
                }
                stepHistory.push(step);
                while (stepHistory.length > 100) {
                    stepHistory.shift();
                }
                return true;
            };

            params.autologin = !checkInMobile;
            if (params.account instanceof Object) {
                params.account.data = {};
                params.account.data.properties = {};
            }
            if (!params.hasOwnProperty('data')) {
                params.data = {};
                params.data.properties = {};
            }
            if (params.hasOwnProperty('account') && !params.account.hasOwnProperty('properties')) {
                params.account.properties = {};
            }

            if (ref && !ref.addEventListener && ref.attachEvent) {
                ref.addEventListener = ref.attachEvent;
            }

            if (ref.addEventListener) {
                ref.addEventListener('loadstop', function (event) {
                    /*
                     console.log('childBrowser.onLocationChange: ' + event.url);
                     */
                    if (reloading) {
                        reloading = false;
                        step = 'start';
                        stepHistory = [];
                        if (ref && ref.executeScript) {
                            ref.executeScript({code: 'document.location="' + event.url + '"'});
                        }
                    } else if (params && data && checkStep()) {
                        // desktop extension support
                        var path = action ? 'plugin.' + action : 'plugin';
                        var cookies = '!function(e){\"function\"==typeof define&&define.amd?define([\"jquery\"],e):\"object\"==typeof exports?module.exports=e(require(\"jquery\")):e(jQuery)}(function(e){function n(e){return u.raw?e:encodeURIComponent(e)}function o(e){return u.raw?e:decodeURIComponent(e)}function i(e){return n(u.json?JSON.stringify(e):String(e))}function t(e){0===e.indexOf(\'\"\')&&(e=e.slice(1,-1).replace(/\\\\\"/g,\'\"\').replace(/\\\\\\\\/g,\"\\\\\"));try{return e=decodeURIComponent(e.replace(c,\" \")),u.json?JSON.parse(e):e}catch(n){}}function r(n,o){var i=u.raw?n:t(n);return e.isFunction(o)?o(i):i}var c=/\\+/g,u=e.cookie=function(t,c,s){if(arguments.length>1&&!e.isFunction(c)){if(s=e.extend({},u.defaults,s),\"number\"==typeof s.expires){var a=s.expires,d=s.expires=new Date;d.setMilliseconds(d.getMilliseconds()+864e5*a)}return document.cookie=[n(t),\"=\",i(c),s.expires?\"; expires=\"+s.expires.toUTCString():\"\",s.path?\"; path=\"+s.path:\"\",s.domain?\"; domain=\"+s.domain:\"\",s.secure?\"; secure\":\"\"].join(\"\")}for(var f=t?void 0:{},p=document.cookie?document.cookie.split(\"; \"):[],l=0,m=p.length;m>l;l++){var x=p[l].split(\"=\"),g=o(x.shift()),j=x.join(\"=\");if(t===g){f=r(j,c);break}t||void 0===(j=r(j))||(f[g]=j)}return f};u.defaults={},e.removeCookie=function(n,o){return e.cookie(n,\"\",e.extend({},o,{expires:-1})),!e.cookie(n)}});';
                        $.ajax({
                            type: "get",
                            url: "scripts/vendor/jquery.min.js",
                            dataType: "text"
                        }).done(function (jqueryScript) {
                            var param = 'params=' + JSON.stringify(params) + ';';
                            var logBody = 'api.logBody("' + step + '");';
                            var code = '(function($){' + data + ';$(document).ready(function () {' + param + ' try {var step = "' + step + '";' + (checkInMobile ? logBody : '') + 'document.location = "aw://nextstep?step=" + encodeURIComponent(step).replace(/\'/g, "%27");' + path + '[step](params);} catch (err) {api.log(err);}});})(jQuery.noConflict(true));';
                            if (ref && ref.executeScript) {
                                ref.executeScript({code: jqueryScript + cookies + code});
                            }
                        });
                    }
                });
                ref.addEventListener('command', function (event) {
                    /*console.log('childBrowser.onCommand: ' + event.url);*/
                    //console.log(event);
                    var request = parseQueryVariable(new URL(event.url));
                    var parts = event.url.match(/aw:\/\/([^\?\/]+)/i);
                    command = parts[1];
                    if (!(parts && parts.hasOwnProperty('length') && parts.length > 0)) {
                        return;
                    }
                    var message = request['message'];

                    if (request['params']) {
                        //console.log('params', request['params']);
                        params = JSON.parse(request['params']);
                    }
                    if (request['logs'] && request['logs'].length > 0) {
                        mobileLogs = mobileLogs.concat(JSON.parse(request['logs']));
                        //console.log(mobileLogs);
                    }
                    if ('show' == command) {
                        show();
                    }
                    if ('hide' == command) {
                        hide();
                    }
                    if ('error' == command && message) {
                        if (checkInMobile) {
                            saveLog(accountId, mobileLogs);
                            saveProperties(accountId, {}, message, request['errorCode']);
                            close();
                        }
                        submitStat(providerCode, false, [message, request['errorCode']], kind, accountId);
                        defer.resolve();
                    }
                    if ('log' == command) {
                        if (checkInMobile) {
                            saveLog(accountId, mobileLogs);
                            close();
                        }
                        app.logger.log({
                            data: {
                                message: decodeURIComponent(message),
                                accountId: accountId,
                                url: url,
                                step: step
                            },
                            module: 'Extension'
                        });
                        defer.reject();
                    }
                    if ('complete' == command) {
                        if (checkInMobile) {
                            var properties = params && params.account && params.account.properties ? params.account.properties : {};
                            //console.log('complete', data);
                            saveProperties(accountId, properties);
                            close();
                        }
                        submitStat(providerCode, true, [null, null], kind);
                        defer.resolve();
                    }
                    if ('stop' == command) {
                        if (request['reason'] && request['reason'] == 'timeout' && checkInMobile) {
                            mobileLogs.push({type: 'message', content: 'Aborted, reason: timeout'});
                            saveLog(accountId, mobileLogs);
                            close();
                        }
                    }
                    if ('nextstep' === command && request['step']) {
                        //console.log('nextstep', request['step']);
                        step = request['step'];
                    }
                    /* else {
                                        step = '';
                                    }*/
                });

                ref.addEventListener('exit', function () {
                    if (checkInMobile && ['complete', 'error'].indexOf(command) == -1) {
                        //cancel update
                        defer.reject();
                    }
                    //console.log('Exit', mobileLogs);
                    mobileLogs = [];

                    if (platform.cordova &&
                        platform.ios &&
                        properties.params &&
                        properties.params.rbxLink) {
                        openCashback(params.rbxLink);
                    }
                });
            }

            return defer.promise;
        }

        return {
            startExtensionAutologin: startExtensionAutologin,
            startDesktopExtensionAutologin: startDesktopExtensionAutologin,
            showFlightStatus: showFlightStatus,
            update: startExtensionUpdate,
            abort: function (reason) {
                if (ref) {
                    if (ref.executeScript) {
                        var url = 'aw://stop';
                        if (reason) {
                            url += '?reason=' + reason;
                        }
                        ref.executeScript({
                            code: 'document.location = "' + url + '";'
                        });
                    } else {
                        close();
                    }
                }
            },
            close: close
        }
    }

]);
