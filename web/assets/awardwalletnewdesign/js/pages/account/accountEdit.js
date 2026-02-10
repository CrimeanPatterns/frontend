define([
    'angular-boot',
    'jquery-boot',
    'lib/autologinV3',
    'directives/dialog'
], function (angular, $, autologinV3) {
    angular = angular && angular.__esModule ? angular.default : angular;

    var app = angular.module('accountEditApp', [
        'dialog-directive'
    ]);
    
    $(function() {
        setTimeout(function() {
            var autologinElement = document.getElementById('autologin_link');
            
            if (autologinElement) {
                $(autologinElement).off('click.autologin').on('click.autologin', function(event) {
                    event.preventDefault();
                    
                    var href = this.href;
                    var targetURLMatch = href.match(/TargetURL=([^&]+)/);
                    var targetURL = targetURLMatch ? decodeURIComponent(targetURLMatch[1]) : null;

                    var signatureMatch = href.match(/Signature=([^&]+)/);
                    var signature = signatureMatch ? decodeURIComponent(signatureMatch[1]) : null;
                    
                    try {
                        var mainInjector = angular.element(document.body).injector();
                        var config = mainInjector.get('AccountEditConfig');
                        var dialogService = mainInjector.get('dialogService');
                        
                        if (config && typeof autologinV3 !== 'undefined') {
                            autologinV3.initAutologin(
                                config.accountId,
                                config.autologinPermission,
                                event,
                                dialogService,
                                { targetURL: targetURL, signature: signature }
                            );
                        }
                    } catch (e) {
                        console.error('Error getting services:', e);
                    }
                });
            } 
        }, 500);
    });

    return app;
});
