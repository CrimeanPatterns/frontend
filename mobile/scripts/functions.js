(function (window) {

    window.isCordova = (function () {
        return /^file:\/{3}[^\/]/i.test(window.location.href)
            && /ios|iphone|ipod|ipad|android/i.test(navigator.userAgent);
    })();
    window.isAndroid = (function () {
        return /Android/i.test(navigator.userAgent);
    })();
    window.isIOS = (function () {
        return /ios|iphone|ipad|ipod/i.test(navigator.userAgent);
    })();

    window.platform = {
        cordova: /^file:\/{3}[^\/]/i.test(window.location.href) && /ios|iphone|ipod|ipad|android/i.test(navigator.userAgent),
        ios: /ios|iphone|ipad|ipod/i.test(navigator.userAgent),
        iphone: /iphone/i.test(navigator.userAgent),
        ipad: /ipad/i.test(navigator.userAgent),
        android: /Android/i.test(navigator.userAgent)
    };

    if (window.debugMode === false) {
        window.console.log = function () {
        };
    }

    window.BaseUrl = /^file:\/{3}[^\/]/i.test(window.location.href) ? '%base_url%' : window.location.origin;

    /**
     * @typedef {Object} URLParts
     * @property {string} url - original url
     * @property {string} scheme - url scheme
     * @property {string} host - hostname from the url
     * @property {string} path - path component of the url
     * @property {Object} params - dictionary with query parameters; the ones that after ? character
     * @property {string} hash - content after # character
     */

    /**
     * @param {string} url
     * @return {URLParts}
     */
    window.parseUrl = function (url) {
        var el = document.createElement('a'), params = {}, qs, param;
        el.href = url;
        qs = (el.search[0] === '?' ? el.search.substr(1) : el.search).split('&');
        for (var i = 0; i < qs.length; i++) {
            param = qs[i].split('=');
            params[decodeURIComponent(param[0])] = decodeURIComponent(param[1] || '');
        }
        return {
            url: url,
            scheme: el.protocol.slice(0, -1),
            host: el.hostname,
            path: el.pathname,
            params: params,
            hash: el.hash[0] === '#' ? el.hash.substr(1) : el.hash
        };
    };

    /**
     * Generates a UUID string.
     * @returns {String} The generated UUID.
     * @example af8a8416-6e18-a307-bd9c-f2c947bbb3aa
     * @author Slavik Meltser (slavik@meltser.info).
     * @link http://slavik.meltser.info/?p=142
     */
    window.UUID = function uuid() {
        function _p8(s) {
            var p = (Math.random().toString(16) + "000000000").substr(2, 8);
            return s ? "-" + p.substr(0, 4) + "-" + p.substr(4, 4) : p;
        }

        return _p8() + _p8(true) + _p8(true) + _p8();
    };

    if (!window.__gCrWeb) window['__gCrWeb'] = {
        autofill: {
            extractForms: function () {
            }
        }
    };

    if (!Function.prototype.bind) {
        Function.prototype.bind = function (oThis) {
            if (typeof this !== "function") {
                // closest thing possible to the ECMAScript 5
                // internal IsCallable function
                throw new TypeError("Function.prototype.bind - what is trying to be bound is not callable");
            }
            var aArgs = Array.prototype.slice.call(arguments, 1),
                fToBind = this,
                fNOP = function () {
                },
                fBound = function () {
                    return fToBind.apply(this instanceof fNOP && oThis
                        ? this
                        : oThis,
                        aArgs.concat(Array.prototype.slice.call(arguments)));
                };

            fNOP.prototype = this.prototype;
            fBound.prototype = new fNOP();

            return fBound;
        };
    }

    window.requestAnimFrame = (function () {
        return window.requestAnimationFrame ||
            window.webkitRequestAnimationFrame ||
            window.mozRequestAnimationFrame ||
            window.oRequestAnimationFrame ||
            window.msRequestAnimationFrame ||
            function (/* function */ callback, /* DOMElement */ element) {
                window.setTimeout(callback, 1000 / 60);
            };
    })();
})(window);