angular.module('AwardWalletMobile').factory('GlobalError', [
    '$rootScope',
    function ($rootScope) {
        var httpErrors = {
            '0': Translator.trans(/** @Desc("There is no internet connection available.") */ 'http-errors.connection', {}, 'mobile'),
            '403': Translator.trans(/** @Desc("Access denied.") */ 'http-errors.denied', {}, 'mobile'),
            '404': Translator.trans(/** @Desc("The page you requested was not found.") */ 'http-errors.not-found', {}, 'mobile'),
            '500': Translator.trans(/** @Desc("There has been an error on this page. This error was recorded and will be fixed as soon as possible.") */ 'http-errors.internal', {}, 'mobile'),
            'default': Translator.trans(/** @Desc("Error loading data") */ 'http-errors.default', {}, 'mobile')
        }, httpTranslationErrors = {
            '0': 'http-errors.connection',
            '403': 'http-errors.denied',
            '404': 'http-errors.not-found',
            '500': 'http-errors.internal',
            'default': 'http-errors.default'
        };

        function getHttpError(httpCode) {
            if (httpErrors.hasOwnProperty(httpCode)) {
                return Translator.trans(/** @Ignore */httpTranslationErrors[httpCode], {}, 'mobile');
            } else {
                return Translator.trans(/** @Ignore */httpTranslationErrors['default'], {}, 'mobile');
            }
        }

        return {
            show: function (msg) {
                if(msg && msg.hasOwnProperty('length') && msg.length > 3) {
                    $rootScope.$broadcast('globalError:show', msg);
                }else{
                    $rootScope.$broadcast('globalError:show', getHttpError(msg));
                }
            },
            hide: function(){
                $rootScope.$broadcast('globalError:hide');
            },
            getHttpError: getHttpError
        }
    }
]);