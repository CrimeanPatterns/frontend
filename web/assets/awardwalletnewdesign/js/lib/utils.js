define(function () {
    var utils = {};

    utils.getUrlParam = function (name, url) {
        if (!url) {
            url = window.location.href;
        }
        if (url && !(/^\/{1}[\w].+/).test(url)) {
            url = '/redirect_not_allowed';
            return url;
        }

        var results = new RegExp('[\\?&]' + name + '=([^&#]*)').exec(url);
        if (!results) {
            return 0;
        }
        return results[1] || 0;
    };

    utils.setCookie = function setCookie(name, value, expires, path, domain, secure) {
        document.cookie = name + "=" + escape(value) +
            ((expires) ? "; expires=" + expires : "") +
            ((path) ? "; path=" + path : "") +
            ((domain) ? "; domain=" + domain : "") +
            ((secure) ? "; secure" : "");
    };

    utils.getCookie = function getCookie(name) {
        var matches = document.cookie.match(new RegExp(
            "(?:^|; )" + name.replace(/([\.$?*|{}\(\)\[\]\\\/\+^])/g, '\\$1') + "=([^;]*)"
        ));
        return matches ? decodeURIComponent(matches[1]) : undefined;
    };

    utils.escape = function (text) {
        var entities = [
            ['apos', '\''],
            ['amp', '&'],
            ['lt', '<'],
            ['gt', '>']
        ];

        for (var i = 0, max = entities.length; i < max; ++i)
            text = text.replace(new RegExp('&' + entities[i][0] + ';', 'g'), entities[i][1]);

        return text;
    };

    utils.elementInViewport = function (el) {
        if (typeof jQuery === "function" && el instanceof jQuery) {
            el = el[0];
        }

        var rect = el.getBoundingClientRect();

        return (
            rect.top >= 0 &&
            rect.left >= 0 &&
            rect.bottom <= (window.innerHeight || document.documentElement.clientHeight) && /*or $(window).height() */
            rect.right <= (window.innerWidth || document.documentElement.clientWidth) /*or $(window).width() */
        );
    };

    var timeout;

    utils.cancelDebounce = function () {
        if (timeout) {
            clearTimeout(timeout);
        }
    }

    utils.debounce = function (func, wait) {
        utils.cancelDebounce();
        timeout = setTimeout(func, wait)
    };

    utils.getNumberFormatter = function(){
        var selector = $('a[data-target="select-language"]');
        var locale = 'en';
        var region = selector.attr('data-region');
        var lang = selector.attr('data-language');

        if(!region && lang && lang.length === 5)
            locale = lang.replace('_', '-');
        else if(region && lang){
            locale = region + '-' + lang.substring(0, 2);
        }else if(lang){
            locale = lang.substring(0, 2);
        }else{
            // fallback
            locale = 'en';
        }

        var supportedLocales = Intl.NumberFormat.supportedLocalesOf(locale);
        var userLocale = supportedLocales.length ? supportedLocales[0] : null;

        return userLocale ?
            new Intl.NumberFormat(userLocale, {maximumFractionDigits: 0}) :
            new Intl.NumberFormat();
    };

    utils.ucfirst = function(str) {
        return str.charAt(0).toUpperCase() + str.slice(1);
    };

    utils.digitFilter = function(event) {
        if (isNaN(String.fromCharCode(event.keyCode)) && '.' !== String.fromCharCode(event.keyCode)) {
            event.preventDefault();
        }
    };

    utils.reverseFormatNumber = function(value, locale) {
        value = value.replace(/\D,/g, '');
        if (undefined === locale) {
            locale = $('a[data-target="select-language"]').data('language') || $('html').attr('lang').substr(0, 2);
        }
        5 === locale.length ? locale = locale.substr(0, 2) : null;

        let group = new Intl.NumberFormat(locale).format(1111).replace(/1/g, '');
        if ('' == group) {
            group = ',';
        }

        let decimal = new Intl.NumberFormat(locale).format(1.1).replace(/1/g, '');
        if ('' == decimal) {
            decimal = '.';
        }

        let num = value.replace(new RegExp('\\' + group, 'g'), '');
        num = num.replace(new RegExp('\\' + decimal, 'g'), '.');

        return !isNaN(parseFloat(num)) && isFinite(num) ? num : null;
    };

    utils.documentScroll = function() {
        const $body = $('body');

        function getScrollbarWidth() {
            let $scr = $('#scrollbarIdentify');
            if (!$scr.length) {
                $scr = $('<div id="scrollbarIdentify" style="position: absolute;top:-1000px;left:-1000px;width: 100px;height: 50px;box-sizing:border-box;overflow-y: scroll;"><div style="width: 100%;height: 200px;"></div></div>');
                $body.append($scr);
            }

            return ($scr[0].offsetWidth - $scr[0].clientWidth);
        }

        return {
            lock: function() {
                const root = document.compatMode === 'BackCompat' ? document.body : document.documentElement;
                if (root.scrollHeight > root.clientHeight) {
                    const scrWidth = getScrollbarWidth();
                    $body.css({'overflow' : 'hidden', 'padding-right' : scrWidth});
                }
            },
            unlock: function() {
                $body.css({'overflow' : 'auto', 'padding-right' : '0'});
            }
        }
    };

    utils.formatFileSize = function(bytes, dp) {
        dp = dp || 1;
        const thresh = 1024;
        if (Math.abs(bytes) < thresh) {
            return bytes + ' B';
        }

        const units = ['kB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB'];
        const r = 10 ** dp;
        let u = -1;

        do {
            bytes /= thresh;
            ++u;
        } while (Math.round(Math.abs(bytes) * r) / r >= thresh && u < units.length - 1);


        return bytes.toFixed(dp) + ' ' + units[u];
    };

    utils.linkify = function(text) {
        const protocolPattern = /(\b(https?|ftp):\/\/[-A-Z0-9+&@#\/%?=~_|!:,.;]*[-A-Z0-9+&@#\/%=~_|])/gim;
        text = text.replace(protocolPattern, '<a href="$1" target="_blank">$1</a>');

        const wwwPattern = /(^|[^\/])(www\.[\S]+(\b|$))/gim;
        text = text.replace(wwwPattern, '$1<a href="http://$2" target="_blank">$2</a>');

        const mailPattern = /(([a-zA-Z0-9\-\_\.])+@[a-zA-Z\_]+?(\.[a-zA-Z]{2,6})+)/gim;
        text = text.replace(mailPattern, '<a href="mailto:$1">$1</a>');

        return text;
    };

    return utils;
});
