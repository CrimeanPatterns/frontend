angular.module('AwardWalletMobile').service('AppAvailability', [
    '$rootScope',
    '$state',
    function ($rootScope, $state) {
        return {
            check: function () {
                var unbind = $rootScope.$on('$stateChangeStart', function (event, toState, toParams) {
                    if (
                        toState && !toState.abstract
                    ) {

                        var state = {
                            name: toState.name,
                            params: angular.extend({}, toParams, {reload: true}) || {},
                            props: toState.props || null
                        };

                        var appArgument = ' app-argument=' + app.scheme + btoa(JSON.stringify({
                                //state: state,
                                href: $state.href(state.name, state.params, state.props)
                            }));
                        var appId = 'app-id=388442727';
                        document.querySelector('[name=apple-itunes-app]').setAttribute('content', [appId, appArgument].join(','));
                        unbind();
                    }
                });
            }
        }
    }]);