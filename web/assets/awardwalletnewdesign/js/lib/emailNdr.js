define(['jquery-boot', 'lib/dialog', 'translator-boot'], function($, dialog){
    var popup = $('#emailNdr'),
        emailField = popup.find('input'),
        button = popup.find('button');
    var clear = function(){
        emailField.closest('.row').removeClass('error').find('.error-message').hide();
    };
    emailField.change(function(){
        clear();
    });
    var d = dialog.createNamed('emailNdr', popup, {
        autoOpen: false,
        modal: true,
        minWidth: 540,
        buttons: [
            {
                text: Translator.trans(/** @Desc("Update Email Address") */"ndr.popup.btn.update-email"),
                "class": "btn-blue",
                click: function() {
                    clear();
                    if ($.trim(emailField.val()) == "") {
                        emailField.closest('.row').addClass('error').find('#not_blank_error').show();
                        return;
                    }
                    emailField.attr('disabled', 'disabled');
                    button.addClass('disabled');
                    // todo fail!
                    $.ajax({
                        url: '/user/uncheckNdr.php',
                        type: 'POST',
                        data: {email: emailField.val()},
                        success: function(response){
                            emailField.removeAttr('disabled');
                            button.removeClass('disabled');
                            if (response != 'OK'){
                                emailField.closest('.row').addClass('error').find('#invalid_error').show();
                                return;
                            }
                            window.location.reload(true);
                        }
                    });
                }
            }
        ]
    });
    setTimeout(function(){
        d.open();
    }, 1000);
});