angular.module('AwardWalletMobile').service('DeepLink', [
    '$cordovaUniversalLink',
    '$state',
    '$q',
    function($cordovaUniversalLink, $state, $q) {

        var scheme, host, rules;

        function compileJsonRules(jsonRules) {
            var compiled = {};
            function compileHandler(data) {
                if (!data.hasOwnProperty('location')) data.location = null;
                if (!data.hasOwnProperty('state')) data.state = null;
                if (!data.hasOwnProperty('choose')) data.choose = null;
                if (data.state) {
                    if (!data.state.hasOwnProperty('param')) data.state.param = [];
                    if (!angular.isArray(data.state.param) && angular.isObject(data.state.param)) {
                        data.state.param = [data.state.param];
                    }
                }
                if (data.choose) {
                    if (!angular.isArray(data.choose.when) && angular.isObject(data.choose.when)) {
                        data.choose.when = [data.choose.when];
                    }
                    var handler = [];
                    angular.forEach(data.choose.when, function(when) {
                        handler.push(compileHandler(when));
                    });
                    data.choose.when = handler;
                    if (data.choose.otherwise) {
                        data.choose.otherwise = compileHandler(data.choose.otherwise);
                    }
                }
                return data;
            }
            angular.forEach(jsonRules, function(jsonRule) {
                if (!jsonRule.hasOwnProperty('route')) jsonRule.route = [];
                if (!jsonRule.hasOwnProperty('exclude-platform')) jsonRule['exclude-platform'] = null;
                if (!angular.isArray(jsonRule.route) && angular.isObject(jsonRule.route)) jsonRule.route = [jsonRule.route];

                if (!jsonRule.hasOwnProperty('regex')) jsonRule.regex = [];
                if (!angular.isArray(jsonRule.regex) && angular.isObject(jsonRule.regex)) jsonRule.regex = [jsonRule.regex];

                angular.forEach(jsonRule.regex, function(regex) {
                    if (!regex.hasOwnProperty('url-part')) regex['url-part'] = "path";
                    if (!regex.hasOwnProperty('set')) regex.set = [];
                    if (!angular.isArray(regex.set) && angular.isObject(regex.set)) regex.set = [regex.set];
                });

                jsonRule.handler = compileHandler(jsonRule.handler);

                if (
                    jsonRule.handler.location
                    || jsonRule.handler.state
                    || jsonRule.handler.choose
                ) compiled[jsonRule.event] = jsonRule;
            });
            return compiled;
        }

        /**
         * @return {URLParts}
         */
        function compileURLParts(parts) {
            ['url', 'scheme', 'host', 'path', 'hash'].forEach(function(item){
                if (typeof parts[item] !== "string") parts[item] = "";
            });
            if (typeof parts.params !== "object") parts.params = {};
            return parts;
        }

        function loadConfig() {
            var q = $q.defer();

            $.getJSON("resources/urlRules.json", function( data ) {
                if (
                    data.hasOwnProperty("universal-links")
                    && data["universal-links"].hasOwnProperty("host")
                    && data["universal-links"].host.hasOwnProperty("name")
                    && data["universal-links"].host.hasOwnProperty("scheme")
                    && data["universal-links"].host.hasOwnProperty("path")
                ) {
                    scheme = data["universal-links"].host.scheme;
                    host = data["universal-links"].host.name;
                    rules = data["universal-links"].host.path;
                    if (!angular.isArray(rules)) {
                        rules = [rules];
                    }
                    rules = compileJsonRules(rules);
                    console.log("compiled url rules", rules);
                    q.resolve();
                } else {
                    q.reject();
                }
            });

            return q.promise;
        }

        /**
         * @typedef {Object} URLRule
         * @property {string} url
         * @property {string} event - event name
         * @property {?string} exclude-platform
         * @property {string[]} route - route names
         * @property {URLRuleRegex[]} regex
         * @property {URLRuleHandler} handler
         */

        /**
         * @typedef {Object} URLRuleRegex
         * @property {string} pattern
         * @property {string} url-part
         * @property {URLRuleRegexSet[]} set
         */

        /**
         * @typedef {Object} URLRuleRegexSet
         * @property {string} name
         * @property {string} key
         */

        /**
         * @typedef {Object} URLRuleHandler
         * @property {?URLRuleHandlerLocation} location
         * @property {?URLRuleHandlerState} state
         * @property {?URLRuleHandlerChoose} choose
         */

        /**
         * @typedef {Object} URLRuleHandlerLocation
         * @property {string} url-part
         */

        /**
         * @typedef {Object} URLRuleHandlerState
         * @property {string} route
         * @property {URLRuleHandlerStateParam[]} param
         */

        /**
         * @typedef {Object} URLRuleHandlerStateParam
         * @property {string} name
         * @property {string} value
         */

        /**
         * @typedef {Object} URLRuleHandlerChoose
         * @property {URLRuleHandlerChooseWhen[]} when
         * @property {?URLRuleHandler} otherwise
         */

        /**
         * @typedef {Object} URLRuleHandlerChooseWhen
         * @property {string} var
         * @property {string} pattern
         * @property {?URLRuleHandlerLocation} location
         * @property {?URLRuleHandlerState} state
         * @property {?URLRuleHandlerChoose} choose
         */

        /**
         * @typedef {Object} CheckRuleResult
         * @property {Boolean} result
         * @property {Object} matched
         */

        /**
         * @param {URLRule} rule
         * @param {URLParts} parts
         * @return {CheckRuleResult}
         */
        function checkRule(rule, parts) {
            console.log("check rule", rule, parts);
            var match, matched = true, params = {};
            if (
                platform.android && rule['exclude-platform'] === 'android'
                || platform.ios && rule['exclude-platform'] === 'ios'
            ) {
                console.log("exclude-platform", rule['exclude-platform']);
                matched = false;
            }
            if (matched) {
                angular.forEach(
                    rule.regex,
                    /**
                     * @param {URLRuleRegex} regex
                     */
                    function (regex) {
                        if (!matched) return;
                        match = parts[regex['url-part']].match(new RegExp(regex.pattern));
                        matched = !!match;
                        if (matched) {
                            angular.forEach(
                                regex.set,
                                /**
                                 * @param {URLRuleRegexSet} s
                                 */
                                function (s) {
                                    params[s.name] = match[s.key] || "";
                                }
                            );
                        }
                        console.log("regex match", regex, matched);
                    }
                );
            }

            return {
                result: matched,
                matched: matched ? params : {}
            };
        }

        /**
         * @param {(URLRuleHandler|URLRuleHandlerChooseWhen)} handler
         * @param {URLParts} parts
         * @param {Object} params
         */
        function handle(handler, parts, params) {
            params = params || {};
            console.log("handle", handler, parts, params);
            if (handler.location) {
                console.log("change document.location");
                document.location[handler.location['url-part']] = parts[handler.location['url-part']];
            } else if (handler.state) {
                var p = {}, value;
                angular.forEach(
                    handler.state.param,
                    /**
                     * @param {URLRuleHandlerStateParam} param
                     */
                    function(param) {
                        value = param.value;
                        angular.forEach(params, function(paramValue, paramName) {
                            value = value.replace("{{"+paramName+"}}", paramValue);
                        });
                        if (typeof value === "string" && value.length === 0) value = null;
                        p[param.name] = value;
                    }
                );
                console.log("change state", handler.state.route, p);
                $state.go(handler.state.route, p);
            } else if (handler.choose) {
                /**
                 * @type {URLRuleHandlerChooseWhen}
                 */
                var when;
                for (var i = 0; i < handler.choose.when.length; i++) {
                    when = handler.choose.when[i];
                    if (typeof params[when.var] === "string" && params[when.var].match(new RegExp(when.pattern))) {
                        return handle(when, parts, params);
                    }
                }
                if (handler.choose.otherwise) {
                    return handle(handler.choose.otherwise, parts, params);
                }
            }
        }

        /**
         * @param {string} eventName
         * @param {URLParts} parts
         * @return {Boolean}
         */
        function tryHandle(eventName, parts) {
            var _rules = {},
                /**
                 * @type {CheckRuleResult}
                 */
                matched;
            parts = compileURLParts(parts);
            if (parts.scheme !== scheme || parts.host !== host) return false;

            if (eventName) {
                if (rules.hasOwnProperty(eventName)) {
                    _rules[eventName] = rules[eventName];
                } else {
                    return false;
                }
            } else {
                _rules = rules;
            }

            for (var name in _rules) {
                matched = checkRule(_rules[name], parts);
                if (matched.result) {
                    handle(_rules[name].handler, parts, matched.matched);
                    return true;
                }
            }
            return false;
        }

        var deepLink = {
            subscribe: function() {
                if (!rules) return loadConfig().then(deepLink.subscribe);
                console.log("universal link subscribe", Object.keys(rules));
                $cordovaUniversalLink.subscribe(Object.keys(rules), function(eventName, parts) {
                    console.log("universal link fire event", eventName);
                    tryHandle(eventName, parts);
                });
            },
            unsubscribe: function() {
                if (!rules) return loadConfig().then(deepLink.unsubscribe);
                console.log("universal link unsubscribe", Object.keys(rules));
                $cordovaUniversalLink.unsubscribe(Object.keys(rules));
            },
            /**
             * @param {string} url
             */
            tryHandleUrl: function(url) {
                if (!rules) {
                    loadConfig().then(function(){
                        deepLink.tryHandleUrl(url)
                    });
                }
                return tryHandle(null, parseUrl(url));
            }
        };

        return deepLink;
    }
]);