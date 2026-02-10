/*
global
CAN_CHECK_VALUE,
CAN_CHECK_CONFIRMATION_YES_EXTENSION,
CAN_CHECK_CONFIRMATION_YES_EXTENSION_AND_SERVER,
PROVIDER_CODE,
PROVIDER_ID,
SELECTED_USER_ID,
FAMILY_MEMBER_ID,
CLIENT_ID
USE_EXTENSION_V3
CHANNEL
CENTRIFUGE_CONFIG
*/

define([
    'jquery-boot',
    'lib/design',
    'lib/customizer',
    'lib/progressBar',
    'extension-client/bundle',
    'centrifuge',
    'jqueryui',
    'browserext'
], function ($, design, customizer, ProgressBar, extensionV3bundle, Centrifuge) {

    const progress = new ProgressBar('.js-check-account .progress-bar-row span', '.js-check-account .progress-bar-row p');
    var extensionClient = new extensionV3bundle.DesktopExtensionInterface();

    function retrieveSuccess(redirectUrl){
        progress.animate(500, function(){
            document.location.href = redirectUrl;
        });
    }

    function retrieveError(message){
        progress.finish();

        if (message.length === 2 && message[1] === 6)
            message = Translator.trans('updater2.messages.fail.updater');

        $('.js-check-account').attr('style', 'display: none;');
        $('.js-check-overlay').hide();
        $('#submitFormBtn').removeClass('loader').removeAttr('disabled');
        $('#checkConfForm').prepend('<div class="error-message-blk small"><i class="icon-error-small-white"></i><p>'+message+'</p></div>');
        customizer.initDatepickers($('#checkConfForm'));
    }

    var checkWithExtensionV2 = false;
    var checkWithExtensionV3 = false;

    switch(CAN_CHECK_VALUE){
        case CAN_CHECK_CONFIRMATION_YES_EXTENSION:
            checkWithExtensionV2 = true;
            break;
        case CAN_CHECK_CONFIRMATION_YES_EXTENSION_AND_SERVER:
            if(browserExt.supportedBrowser() && !$.cookie('DBE')) {
                checkWithExtensionV2 = true;
            }
            break;
    }

    function requireExtensionV2()
    {
        browserExt.requireValidExtension();
    }

    if (USE_EXTENSION_V3 && browserExt.extensionV3supported() && !$.cookie('DBE')) {
        extensionClient.isInstalled().then((installed) => {
            if (!installed) {
                console.log('extension v3 is not installed but required');
                document.location.href = '/extension-install?BackTo=' + encodeURIComponent(document.location.href) + '&v3=true';

                return;
            }

            checkWithExtensionV3 = true;
            checkWithExtensionV2 = false;

            const client = new Centrifuge(CENTRIFUGE_CONFIG);
            client.on('connect', function () {
                console.log('centrifuge connected');

                const onMessage = function onMessage(message) {
                    console.log('got v3 session event')
                    extensionClient.connect(
                        message.data.token,
                        message.data.sessionId,
                        function(error) {
                            console.log('error v3 session event', error)
                        },
                        function() {
                            console.log('completed v3 session event')
                        }
                    );
                }
                client.subscribe(CHANNEL, onMessage);
            });
            client.connect();

        });
    } else if (checkWithExtensionV2) {
        requireExtensionV2()
    }

    $('#retrieve-form').on('submit', function(event) {
        $('#submitFormBtn').addClass('loader').attr('disabled', 'disabled');
        $('.error-message-blk').hide();
        $('.js-check-overlay').removeAttr('style');

        $('.js-check-account').removeAttr('style');
        progress.animate(15000, function(){});

        var form = $('#checkConfForm');

        if (checkWithExtensionV2 && !checkWithExtensionV3) {
            var fields = {
                login : 'qwerty',
                password : 'qwerty'
            };
            var name, m;
            // grab hidden fields here to collect datePicker values
            $('form[name=add] input[type=text], form[name=add] input[type=hidden]').each(function(){
                name = $(this).attr('name');
                if (name){
                    m = name.match(/\[(.+)\]/);
                    if (m) {
                        if (m[1] === '_token') {
                            return
                        }

                        fields[m[1]] = $(this).val();
                    }
                }
            });

            browserExt.pushOnReady(function() {
                browserExt.retrieveByConfNo(PROVIDER_CODE, fields, retrieveSuccess, retrieveError, 'new', SELECTED_USER_ID, FAMILY_MEMBER_ID, CLIENT_ID);
            });
        } else {
            $('#confirmation_type_browserExtensionAllowed').val(checkWithExtensionV3);
            $('#confirmation_type_channel').val(CHANNEL);
            $.ajax({
               url: document.location.href,
               type: 'POST',
               data: form.serialize(),
                success: function(response){
                    if(typeof(response.redirect) == "string") {
                        progress.animate(500, function(){
                            document.location.href = response.redirect;
                        });
                    }
                    else {
                        $('#retrieve-form').html(response.replace(/\0/g, '0').replace(/\\(.)/g, "$1"));
                        customizer.initDatepickers($('#checkConfForm'));
                        $('#mobile_new_account_Region').trigger('change');
                    }
                },
                complete: function(){
                    progress.finish();
                    $('.js-check-overlay').hide();
                    $('#submitFormBtn').removeClass('loader').removeAttr('disabled');
                }
            });
        }

        event.preventDefault();
    });

});
