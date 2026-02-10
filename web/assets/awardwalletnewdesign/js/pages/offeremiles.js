define(['jquery-boot', 'lib/customizer', 'jqueryui', 'select2'], function ($, customizer) {
    customizer.initSelects2();
    var getData = function() {
        return {
            'firstName': $('.main-form #first-name').val(),
            'lastName': $('.main-form #last-name').val(),
            'login': $('.main-form #email').val(),
            'password': $('.main-form #password').val(),
            'email': $('.main-form #email').val(),
            'zip': $('.main-form #zip-postal').val(),
            'gender': $('.main-form #gender').val(),
            'birthday': $('.main-form #birthday_id').val()
        };
    };
    var addError = function(text, field) {
        field.closest('.row')
            .find('.error-message-description').text(text).end()
            .addClass('error');
    };
    var cleanErrors = function() {
        $('.main-form').find('.row.error').removeClass('error');
    };
    var send = function(){
        browserExt.autoRegistration(128, 0, getData(), 156, false);
    };
    var checkErrors = function() {
        cleanErrors();
        var data = getData(),
            val, field;
        // first name
        val = $.trim(data['firstName']);
        field = $('.main-form #first-name');
        if (val == "") {
            addError('First Name is required.', field);
        } else {
            if (!val.match(/^[a-z\s\'\-\.]+$/i))
                addError('First Name must be either alphabetical characters, spaces, single quotes, dashes or periods.', field);
            else if (val.length > 30) {
                addError('First Name can not be greater than 30 characters.', field);
            }
        }
        // last name
        val = $.trim(data['lastName']);
        field = $('.main-form #last-name');
        if (val == "") {
            addError('Last Name is required.', field);
        } else {
            if (!val.match(/^[a-z\s\'\-\.]+$/i))
                addError('Last Name must be either alphabetical characters, spaces, single quotes, dashes or periods.', field);
            else if (val.length > 30) {
                addError('Last Name can not be greater than 30 characters.', field);
            }
        }
        // email
        val = $.trim(data['email']);
        field = $('.main-form #email');
        if (val == "") {
            addError('Email is required.', field);
        } else {
            if (!val.match(/^[_a-zA-Z\d\-\+\.]+@([_a-zA-Z\d\-]+(\.[_a-zA-Z\d\-]+)+)$/i))
                addError('E-mail Address is an invalid format.', field);
        }
        // zip
        val = $.trim(data['zip']);
        field = $('.main-form #zip-postal');
        if (val == "") {
            addError('Zip/Postal Code is required.', field);
        } else {
            if (!val.match(/^[0-9]{5}$/i))
                addError('Zip/Postal Code must be a valid 5 digit zipcode, no spaces and no alpha characters.', field);
        }
        // gender
        val = $.trim(data['gender']);
        field = $('.main-form #gender');
        if (val == "") {
            addError('Gender is required.', field);
        }
        // birthday
        val = $.trim(data['birthday']);
        field = $('#birthday_id_datepicker');
        if (val == "") {
            addError('Birthday is required.', field);
        } else {
            var parts = val.split('-');
            var text = 'Birthday is an invalid format.';
            if (parts.length != 3)
                addError(text, field);
            else {
                if (!parts[0].match(/^[0-9]+$/i) || parseInt(parts[0]) < 1899 || parseInt(parts[0]) > new Date().getFullYear())
                    addError(text, field);
                else if (!parts[1].match(/^[0-9]+$/i) || parseInt(parts[1]) < 1 || parseInt(parts[1]) > 12)
                    addError(text, field);
                else if (!parts[2].match(/^[0-9]+$/i) || parseInt(parts[2]) < 1 || parseInt(parts[2]) > 31)
                    addError(text, field);
            }
        }
        // password
        val = $.trim(data['password']);
        field = $('.main-form #password');
        if (val == "") {
            addError('Password is required.', field);
        } else {
            if (!val.match(/^[a-z0-9]+$/i))
                addError('Password must be either alphabetical characters or numeric numbers.', field);
            else if (val.length > 20)
                addError('Password can not be greater than 20 characters.', field);
            else if (val.length < 4)
                addError('Password can not be less than 4 characters.', field);
        }

        return $('.main-form .row.error').length > 0 ? false : true;
    };
    return {
        submitCallback: function(){},
        init: function() {
            var obj = this;
            $(function() {
                $('.e-miles form').submit(function(e){
                    e.preventDefault();
                    if (checkErrors()) {
                        obj.submitCallback();
                        send();
                    } else {
                        $('.e-miles .row.error').find('input[type=text], select').first().focus();
                    }
                    return false;
                });
                $('.main-form').on('change keyup paste', 'input, select', function(){
                    $(this).closest('.row').removeClass('error');
                });
            });
        }
    };
});