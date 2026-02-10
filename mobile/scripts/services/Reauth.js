angular.module('AwardWalletMobile').factory('Reauth', [
    '$q', 'Translator', '$injector',
    function ($q, Translator, $injector)
    {
        const HEADER_REAUTH_CONTEXT = 'x-aw-reauth-context';
        const HEADER_REAUTH_REQUIRED = 'x-aw-reauth-required';
        const HEADER_REAUTH_INPUT = 'x-aw-reauth-input';
        const HEADER_REAUTH_INTENT = 'x-aw-reauth-intent';
        const HEADER_REAUTH_ERROR = 'x-aw-reauth-error';
        const HEADER_REAUTH_SUCCESS = 'x-aw-reauth-success';

        const CONTEXT_PASSWORD = 'password';
        const CONTEXT_OTC = 'code';

        let error = null;
        let defer = null;

        function getDefer()
        {
            if (defer) {
                return defer;
            }

            return defer = $q.defer();
        }

        function resetDefer()
        {
            defer = null;
        }

        return {
            response: (response) => {
                if (response && response.headers(HEADER_REAUTH_SUCCESS)) {
                    error = null;
                    $injector.get('ReauthPopup').close();
                    getDefer().resolve(response);
                    resetDefer();
                }

                return response;
            },
            responseError: (rejection) => {
                if (rejection.headers(HEADER_REAUTH_ERROR)) {
                    error = rejection.headers(HEADER_REAUTH_ERROR);
                }

                if (rejection.headers(HEADER_REAUTH_REQUIRED)) {
                    const context = rejection.headers(HEADER_REAUTH_CONTEXT);
                    defer = getDefer();

                    if ([CONTEXT_PASSWORD, CONTEXT_OTC].includes(context)) {
                        error = null;
                        $injector.get('ReauthPopup').open({
                            label: context === CONTEXT_OTC ? rejection.headers(HEADER_REAUTH_REQUIRED) : Translator.trans('provide-aw-password'),
                            type: context,
                            onPress: ({input, resend = false}) => {
                                rejection.config.headers[HEADER_REAUTH_CONTEXT] = context;

                                if (input) {
                                    rejection.config.headers[HEADER_REAUTH_INPUT] = input;
                                    delete rejection.config.headers[HEADER_REAUTH_INTENT];
                                } else if (resend) {
                                    rejection.config.headers[HEADER_REAUTH_INTENT] = 'resend';
                                    delete rejection.config.headers[HEADER_REAUTH_INPUT];
                                }

                                $injector.get('$http')(rejection.config);
                            },
                            onClose: () => {
                                error = null;
                                defer.reject(rejection);
                            },
                            getError: () => {
                                return error;
                            }
                        });

                        return defer.promise;
                    }
                }

                return $q.reject(rejection);
            }
        };
    }
]);