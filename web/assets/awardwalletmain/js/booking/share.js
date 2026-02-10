var needPasswords = [];

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

function askOnePassword(){
    if(needPasswords.length > 0){
        var account = needPasswords.pop();
        askAccountPassword(
            account.AccountID, account.ProviderName, account.Login, account.UserName,
            askOnePassword,
            askOnePassword,
            true
        );
    }
}

function saveLocalPasswords(accounts){
    $.ajax({
        url: '/account/savePasswordsToDb.php',
        type: 'POST',
        data: { accounts: accounts },
        dataType: 'json',
        success: function(response){
            needPasswords = response;
            askOnePassword();
        }
    });
}
