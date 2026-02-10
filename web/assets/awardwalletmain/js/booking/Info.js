var Info = (function(){
    return {
        init: function(){
            if (AddForm.byBooker()) {
                $.proxy(Info.processContactInfo, $('#tab1 input[name$="[ContactName]"]').closest('.inner-block'))();
            }
            Info.event();
        },
        event: function(){
            if (AddForm.byBooker()) {
                $('#tab1')
                    .on('change', 'input.select2', function(event) {
                        var contactInfo = $(event.target).closest('.inner-block'),
                            item = event.added;
                        Info.processContactInfo(event);
                        Info.setTemplate(contactInfo, item);
                     });
            }
        },
        processContactInfo: function(e) {
            var contactInfo = (e != null && e.target) ? $(e.target).closest('.inner-block') : this,
                val = contactInfo.find('input[name$="[User]"]').val();
            Info.toggleContactInfo(contactInfo, val == "", e != null && e.target);
        },
        toggleContactInfo: function(contactInfo, disabled, clearErrors){
            clearErrors = (typeof(clearErrors) == 'undefined')?false:clearErrors;
            if (clearErrors)
                contactInfo.find('.row').removeClass('error');
            // full name
            contactInfo.find('input[name$="[ContactName]"]').prop('disabled', disabled);
            // email
            contactInfo.find('input[name$="[ContactEmail]"]').prop('disabled', disabled);
            // phone
            contactInfo.find('input[name$="[ContactPhone]"]').prop('disabled', disabled);
        },
        setTemplate: function(field, data) {
            // full name
            field.find('input[name$="[ContactName]"]').val((data['FullName'] != null)?data['FullName']:"").trigger('change');
            // email
            field.find('input[name$="[ContactEmail]"]').val((data['Email'] != null)?data['Email']:"").trigger('change');
            // phone
            field.find('input[name$="[ContactPhone]"]').val((data['Phone'] != null)?data['Phone']:"").trigger('change');
        },
        check: function() {
            var $registerInputs = $(".registerCredentials input").filter(function() {
                if(this.type == 'checkbox') return $(this).is(':checked');
                return $.trim(this.value) != "";
            });
            return $registerInputs.length != 5;
        },
        confirmFieldsCheck: function () {
            var email = $('input[name$="[Email]"]').val();
            var confirmEmail = $('input[name$="[ConfirmEmail]"]').val();
            var password = $('input[name$="[Password]"]').val();
            var confirmPassword = $('input[name$="[ConfirmPassword]"]').val();
            var message = null;
            var errors = [];

            if(email != confirmEmail){
                message = Translator.trans('user.email_equal', {}, 'validators');
                $('div#tab1').find('input[type="email"][name*="[User]"]').each(function () {
                    var id = $(this).attr('id');
                    var row = $('#'+id);
                    row.closest('div.row').addClass('error required').find('div.message').text(message).show();
                    errors.push(row);
                });
            }

            if(password !== confirmPassword){
                let confirmRow = null;
                message = Translator.trans('user.pass_equal', {}, 'validators');
                $('div#tab1').find('input[type="password"]').each(function () {
                    const id = $(this).attr('id');
                    const row = $('#'+id);
                    row.closest('div.row').addClass('error required').find('div.message').text(message).show();
                    confirmRow = row;
                });

                if(confirmRow)
                    errors.push(confirmRow);
            }

            if(errors.length){
                AddForm.activateElementTab(errors[0]);
                formValidator.focus(errors[0].closest('div.row').find('input'), 250);
            }

            return email === confirmEmail && password === confirmPassword;
        },
        userSelect2Options: {
            ajax: {
                url: Routing.generate('aw_booking_json_searchmembers', {}, false),
                dataType: 'json',
                data: function (term, page) {
                    term = term.replace(/[^A-Za-z0-9А-Яа-я ]+/g, '');
                    return {
                        query: term, // search term
                        step: 4
                    };
                },
                results: function(data, page) {
                    return {results: data};
                }
            }
        }
    };
})();