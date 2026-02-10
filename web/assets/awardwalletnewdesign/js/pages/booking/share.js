define(['jquery-boot', 'lib/dialog', 'translator-boot'], function ($, dialogService) {
    var needPasswords = [];
    var form;

    $(document).ready(function () {

        $(document).on('click', '.js-share-program', function(event) {
            event.preventDefault();
            var row = $(this).closest('tr');
            var selected = row.find('select > option:selected');
            if (!selected.is('[value]'))
                return;
            window.location.href = selected.data('url');
            return false;
        });

        $(document).on('change', '.js-share-program-select', function(event) {
            var row = $(this).closest('tr');
            var button = row.find('.js-share-program');
            if ($(this).find(':selected').is('[value]'))
                button.show();
            else
                button.hide();
            return false;
        });

    });

    $(document).on('save_local_passwords', function(e, accounts, formView) {
        form = formView;
        // todo fail!
        $.ajax({
            url: '/account/savePasswordsToDb.php',
            type: 'POST',
            data: { accounts: accounts },
            dataType: 'json',
            success: function(response){
                var missing = [];
                $.each(response, function(i, v){
                    missing.push(v.AccountID);
                });
                $.each(accounts.split(","), function(i, aid) {
                    if ($.inArray(parseInt(aid), missing) === -1) {
                        $('table.main tr[data-id="'+aid+'"] .old-icon-check').show();
                    }
                });
                needPasswords = response;
                askOnePassword();
            }
        });
    }).on('keypress', '#form_password', function(event){
        console.log(event.keyCode);
        if (event.keyCode == 13) $('#send-form').trigger('click');
        else if (event.keyCode == 27) $('#cancel-form').trigger('click');
    });

    function askOnePassword(){
        if (needPasswords.length > 0){
            var account = needPasswords.pop();
            setTimeout(function(){
                askAccountPassword(
                    account.AccountID, account.ProviderName, account.Login, account.UserName,
                    function(id){
                        $('table.main tr[data-id="'+id+'"] .old-icon-check').show();
                        askOnePassword();
                    },
                    askOnePassword,
                    true
                );
            }, 500);
        }
    }

    function askAccountPassword(accountId, providerName, login, userName, onSuccess, onCancel, toDatabase){
        var dialog = dialogService.fastCreate(
            Translator.trans(/** @Desc("Missing password for %providerName%") */'account.label.enter.password.for.account.title', {
                providerName: providerName
            }),
            $(form).html().replace('%label%', Translator.trans('account.label.enter.password.for.account', {
                Account: login,
                UserName: userName
            })),
            true,
            false,
            [
                {
                    id: 'send-form',
                    text: Translator.trans('button.ok'),
                    click: function () {
                        var pass = $('#form_password');
                        if ($.trim(pass.val()) == ""){
                            pass.val("").focus();
                            return;
                        }
                        var params = {};
                        params["AccountID"] = accountId;
                        params["Password"] = $.trim(pass.val());
                        params["ToDatabase"] = toDatabase;
                        // todo fail!
                        $.ajax({
                            url: '/account/saveAccountPassword.php',
                            type: 'POST',
                            data: params,
                            success: function(response){
                                if (response != 'OK'){
                                    alert(response);
                                } else {
                                    onSuccess(accountId);
                                }
                            }
                        });
                        $(this).dialog('close');
                    },
                    'class': 'btn-blue'
                },
                {
                    id: 'cancel-form',
                    text: Translator.trans('button.cancel'),
                    click: function () {
                        $(this).dialog('close');
                        onCancel();
                    },
                    'class': 'btn-silver'
                }
            ],
            500
        );
        dialog.setOption("open", function(){
            setTimeout(function(){
                $('#form_password').focus();
            }, 500);
        });
        dialog.setOption("close", function(){
            dialog.destroy();
            onCancel();
        });
        dialog.open();
    }
});