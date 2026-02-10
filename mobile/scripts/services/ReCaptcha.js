angular.module('AwardWalletMobile').factory('ReCaptcha', ['$timeout', '$window', '$document', '$q', function($timeout, $window, $document, $q) {
    const GOOGLE_RECAPTCHA = 'google_recaptcha';
    const CLOUDFLARE_TURNSTILE = 'cloudflare_turnstile';
    var deferred = $q.defer(),
        promise = deferred.promise,
        recaptcha,
        recaptchaVendor,
        instances = {};


    function onRecaptchaLoaded(vendor) {
        console.log(`[reCaptcha] loaded ${vendor}`, $window.grecaptcha);
        recaptcha = instances[vendor] = $window.grecaptcha;
        recaptchaVendor = vendor;
        $window.grecaptcha = undefined;


        if ($window['grecaptcha-google']) {
            console.log('[reCaptcha] restore google recaptcha');
            $window.grecaptcha = $window['grecaptcha-google'];
            $window['grecaptcha-google'] = undefined;
        }

        deferred.resolve(recaptcha);
    }

    function validateRecaptcha() {
        if (!recaptcha) {
            throw new Error('reCaptcha has not been loaded yet.');
        }
    }

    function load(vendor, url) {
        console.log(`[reCaptcha] load ${vendor}: ${url}`);

        if (instances[vendor]) {
            console.log('[reCaptcha] already loaded');

            if (vendor !== recaptchaVendor) {
                console.log('[reCaptcha] switch to ' + vendor);
                recaptcha = instances[vendor];
                recaptchaVendor = vendor;
            }

            return $q.when(recaptcha);
        } else if ($window.grecaptcha) {
            console.log('[reCaptcha] conflict with google recaptcha');
            $window['grecaptcha-google'] = $window.grecaptcha;
            $window.grecaptcha = undefined;
        }

        var script = $window.document.createElement('script');
        script.async = true;
        script.defer = true;
        script.src = url;
        var functionName = url.match(/onload=([^&]+)/)[1];

        if (!functionName) {
            throw new Error('reCaptcha onload parameter is not specified.');
        } else {
            console.log('[reCaptcha] onload=' + functionName);
            $window[functionName] = onRecaptchaLoaded.bind(null, vendor);
        }

        $document.find('body')[0].appendChild(script);
    }

    function getRecaptcha() {
        if (recaptcha) {
            return $q.when(recaptcha);
        }

        return promise;
    }

    function isValidResponse(response, vendor) {
        if (vendor === GOOGLE_RECAPTCHA) {
            return !!response;
        } else if (vendor === CLOUDFLARE_TURNSTILE) {
            return typeof response === 'string';
        }

        return false;
    }

    return {
        create: function (element, url, vendor, options) {
            console.log(`[reCaptcha] create ${vendor}: ${url}`, options);
            options = options || {};
            load(vendor, url);

            return getRecaptcha().then(function (recaptcha) {
                console.log('[reCaptcha] render');

                return recaptcha.render(element, options);
            });
        },

        reload: function (widgetId) {
            validateRecaptcha();
            console.log(`[reCaptcha] reload ${recaptchaVendor} widget {${widgetId ? widgetId : 'last'}}`);

            recaptcha.reset(widgetId);
        },

        execute: function (widgetId) {
            validateRecaptcha();
            console.log(`[reCaptcha] execute ${recaptchaVendor} widget {${widgetId ? widgetId : 'last'}}`);

            recaptcha.execute(widgetId);
        },

        getResponse: function (widgetId) {
            validateRecaptcha();
            var response = recaptcha.getResponse(widgetId);

            if (!isValidResponse(response, recaptchaVendor)) {
                response = undefined;
            }

            console.log(`[reCaptcha] getResponse ${recaptchaVendor} widget {${widgetId ? widgetId : 'last'}}`, response);

            return response;
        },

        remove: function (widgetId) {
            validateRecaptcha();

            if (recaptchaVendor === CLOUDFLARE_TURNSTILE) {
                console.log(`[reCaptcha] remove ${CLOUDFLARE_TURNSTILE} widget {${widgetId ? widgetId : 'last'}}`);
                recaptcha.remove(widgetId);
            }
        },

        isValidResponse: isValidResponse
    };
}]);