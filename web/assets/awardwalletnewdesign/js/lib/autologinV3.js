define([
    'jquery-boot',
    'extension-client/bundle',
    'browserext',
    'translator-boot'
], function ($, bundle) {

    function AutologinV3Service() {
        this.extensionClient = null;
        
        if (typeof bundle !== 'undefined' && bundle.DesktopExtensionInterface) {
            this.extensionClient = new bundle.DesktopExtensionInterface();
        }
    }

    

    AutologinV3Service.prototype = {
        initAutologin: function(accountId, permission, event, dialogService, fetchParams) {
            if (!permission) {
                return false;
            }

            if (!this.extensionClient) {
                console.error('Extension client not available');
                return false;
            }
            
            console.log('trying to use extension v3');
            var autologinV3Connection = null;
            var self = this;
            
            if(dialogService !== undefined){
                this.showAutologinDialog(dialogService, autologinV3Connection);
            }

            this.extensionClient.isInstalled().then(function(installed) {
                if (!installed) {
                    console.log('extension v3 is not installed, will install');
                    document.location.href = Routing.generate('aw_extension_install', {
                        BackTo: encodeURIComponent(document.location.href)
                    })
                    return;
                }

                const routeParams = {
                    accountId: accountId
                };

                if (fetchParams) {
                    routeParams.targetURL = fetchParams.targetURL;
                    routeParams.signature = fetchParams.signature;
                }

                console.log('using extension v3');
                $.ajax({
                    url: Routing.generate('aw_account_get_autologin_connection', routeParams),
                    method: 'POST',
                }).done(function(response) {
                    console.log(response);
                    
                    if (response.askLocalPassword) {
                        // missing local password
                        console.log('asking local password');
                        $('#autologin-popup').dialog('close');
                        if(dialogService !== undefined){
                            dialogService.fastCreate(
                                Translator.trans('status.error-occurred'),
                                response.error,
                                true,
                                true,
                                [
                                    {
                                        text: Translator.trans('ok.button'),
                                        'class': 'btn-blue',
                                        click: function () {
                                            $(this).dialog('close');
                                        }
                                    }
                                ],
                                500,
                                null,
                                'error'
                            );
                        }
                        
                        return;
                    }
                    
                    self.extensionClient.connect(
                        response.browserExtensionConnectionToken,
                        response.browserExtensionSessionId,
                        function(message) {
                            console.log('extension v3 error', message);
                            $('#autologin-popup').dialog('close');
                        },
                        function(message) {
                            console.log('autologin complete', message);
                            $('#autologin-popup').dialog('close');
                        }
                    ).then(function(result) {
                        autologinV3Connection = result;
                    });
                });
            });

            event.preventDefault();
            return true;
        },

        showAutologinDialog: function(dialogService, autologinV3Connection) {
            var dialog = dialogService.get("autologin-popup");
            dialog.setOption("close", function () {
                browserExt.cancel();
            });
            dialog.setOption("title", Translator.trans('button.autologin'));
            dialog.setOption("buttons", [
                {
                    'text': Translator.trans('alerts.btn.cancel'),
                    'click': function () {
                        browserExt.cancel();
                        if (autologinV3Connection) {
                            autologinV3Connection.disconnect();
                            autologinV3Connection = null;
                        }
                        $(this).dialog("close");
                    },
                    'class': 'btn-blue',
                    tabindex: -1
                }
            ]);
            dialog.open();
        }
    };

    var autologinV3Service = new AutologinV3Service();

    return autologinV3Service;
});
