if (typeof(define) == 'undefined') {
    var passwordComplexity;
}

(function () {
    var popupTimeoutId;

    function showPasswordNotice(event) {
        $('#password-notice').show();
    }

    function hidePasswordNotice(event) {
        $('#password-notice').hide();
    }

    function trackComplexity(value) {
        var checks = {
            'password-length': value.length >= 8 && lengthInUtf8Bytes(value) <= 72,
            'lower-case': value.match(/[a-z]/) != null,
            'upper-case': value.match(/[A-Z]/) != null,
            'special-char': value.match(/[^a-zA-Z\s]/) != null
        };
        if (self.getLoginCallback) {
            var login = self.getLoginCallback().toLowerCase();
            var email = self.getEmailCallback();
            email = email.replace(/@.*$/, '').toLowerCase();
            checks.login = (value.toLowerCase().indexOf(login) == -1 || login == '') && (value.toLowerCase().indexOf(email) == -1 || email == '');
        }
        $('#meet-login').toggle(self.getLoginCallback != null);

        var errors = [], meetDiv;
        $.each(checks, function (key, match) {
            meetDiv = $('#meet-' + key);
            meetDiv.toggleClass("allowed", match);
            if (!match)
                errors.push(meetDiv.text());
        });
        if ($('#password-notice').is(':not(:visible)') && errors.length > 0) {
            showPasswordNotice();
        }
        clearTimeout(popupTimeoutId);
        if (errors.length < 1) {
            popupTimeoutId = setTimeout(function () {
                hidePasswordNotice()
            }, 500);
        }
        return errors;
    }

    function parsePosition(pos) {
        var result = parseInt(pos);
        if (isNaN(result))
            result = 0;
        return result;
    }

    function lengthInUtf8Bytes(str) {
        // Matches only the 10.. bytes that are non-initial characters in a multi-byte sequence.
        var m = encodeURIComponent(str).match(/%[89ABab]/g);
        return str.length + (m ? m.length : 0);
    }

    var self = {

        passwordField: null,
        getLoginCallback: null,
        getEmailCallback: null,

        init: function (passwordField, getLoginCallback, getEmailCallback) {
            if (passwordField && passwordField.length > 0) {
                self.passwordField = passwordField;
                self.getLoginCallback = getLoginCallback;
                self.getEmailCallback = getEmailCallback;
                passwordField
                    .on("blur", null, null, hidePasswordNotice)
                    .on("input paste change focus", null, null, function () {
                        trackComplexity(self.passwordField.val());
                    });
                trackComplexity(self.passwordField.val());
            }
        },

        getErrors: function () {
            return trackComplexity(self.passwordField.val());
        },

        getErrorPrefix: function () {
            return Translator.trans(/** @Desc("Password does not meet complexity requirements") */ "password.does-not-meet-requirements");
        },

        destroy: function () {
            if (self.passwordField) {
                self.passwordField.off('input paste change');
            }
            self.passwordField = null;
            self.getLoginCallback = null;
            self.getEmailCallback = null;
        }

    };

    if (typeof(define) != 'undefined')
        define(['jquery-boot'], function () {
            return self;
        });
    else
        passwordComplexity = self;

})();

