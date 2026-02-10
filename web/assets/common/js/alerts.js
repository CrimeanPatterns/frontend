$(function () {
    window.jError = function (options) {
        var settings = {
            error: 'error',
            type: 'error',
            content: Translator.trans(/**@Desc("There has been an error on this page. This error was recorded and will be fixed as soon as possible.")*/'alerts.text.error'),
            title: ''
        };
        settings.content += '<img src="/ajax_error.gif?message=error" width="1" height="1">';
        settings = $.extend(settings, options);
        switch (settings.error) {
            case "timeout":
                settings.content = Translator.trans(/**@Desc("It looks like your request has timed out. Please try again. If you get this error again you can try refreshing the page. If you get stuck, feel free to <a href='https://awardwallet.com/contact.php'>contact us</a>.")*/'alerts.text.error.timeout');
                settings.title = Translator.trans(/**@Desc("Operation Timed Out")*/'alerts.title.error.timeout');
                settings.content += '<img src="/ajax_error.gif?message=timeout" width="1" height="1">';
                break;
            case "parsererror":
                settings.content = Translator.trans(/**@Desc("An invalid response was received from the server. Please try again. If you get this error again you can try refreshing the page. If you get stuck, feel free to <a href='https://awardwallet.com/contact.php'>contact us</a>.")*/'alerts.text.error.parsererror');
                settings.title = Translator.trans(/**@Desc("Server Error Occurred")*/'alerts.title.error.parsererror');
                settings.content += '<img src="/ajax_error.gif?message=parsererror" width="1" height="1">';
                break;
            case "abort":
                settings.content = Translator.trans(/**@Desc("Your current request was aborted. If this was not intentional you can try again. If you get stuck, feel free to <a href='https://awardwallet.com/contact.php'>contact us</a>.")*/'alerts.text.error.abort');
                settings.title = Translator.trans(/**@Desc("Operation Aborted")*/'alerts.title.error.abort');
                settings.content += '<img src="/ajax_error.gif?message=abort" width="1" height="1">';
                break;
            case "error":
            default:
                break
        }
        jAlert(settings);
    };

    window.jAlert = function (options) {
        var settings = {
            type: 'info',
            title: '',
            modal: true,
            width: 400,
            content: '',
            html: $("<div/>"),
            buttons: [
                {
                    text: Translator.trans(/**@Desc("Ok")*/'alerts.btn.ok'),
                    click: function () {
                        $(this).dialog('close');
                    },
					'class': 'btn-blue'
                }
            ]
        };

        // Check if dialog is open
        if ($('.ui-dialog').is(":visible"))
            return;

        settings.create = function (e, ui) {
            $(e.target).closest('.ui-dialog').find('.ui-dialog-title').prepend('<i class="icon-' + settings.type + '"></i>');
            $(e.target).prev('.ui-dialog-titlebar').addClass('alert-' + settings.type + '-header');
            $(e.target).next('.ui-dialog-buttonpane').addClass('alert-' + settings.type + '-bottom');
        };

        if (options.content) {
            settings = $.extend(settings, options);
        } else {
            settings.content = options;
        }

        if (settings.title == '') {
            if (settings.type === 'info')
                settings.title = Translator.trans(/**@Desc("Information")*/'alerts.info');
            else if (settings.type === 'error')
                settings.title = Translator.trans(/**@Desc("Error")*/'alerts.error');
            else if (settings.type === 'success')
                settings.title = Translator.trans(/**@Desc("Success")*/'alerts.success');
            else if (settings.type === 'warning')
                settings.title = Translator.trans(/**@Desc("Warning")*/'alerts.warning');
            else
                settings.title = Translator.trans(/**@Desc("Error")*/'alerts.error');
        }

        var el = settings.html;
        el.addClass('alert-' + settings.type).html(settings.content);
        $("body").append(el);
        $(el).dialog(settings);
        return el;
    };

    window.jConfirm = function (question, callback) {
        return jAlert({
            content: question,
            title: Translator.trans(/**@Desc("Please confirm")*/'alerts.text.confirm'),
            buttons: [
                {
                    text: Translator.trans(/**@Desc("Cancel")*/ 'alerts.btn.cancel'),
                    click: function () {
                        $(this).dialog('close');
                        return false;
                    },
                    'class': 'btn-silver'
                },
                {
                    text: Translator.trans(/**@Desc("Ok")*/'alerts.btn.ok'),
                    click: function () {
                        $(this).dialog('close');
                        callback();
                    },
					'class': 'btn-blue'
                }
            ]
        });
    };

    window.jPrompt = function (question, callback) {
        var el = $("<input/>").css('width', '100%');
        return jAlert({
            title: question,
            content: el,
            buttons: [
                {
                    text: Translator.trans(/**@Desc("Ok")*/'alerts.btn.ok'),
                    click: function () {
                        $(this).dialog('close');
                        callback($(el).val());
                    },
					'class': 'btn-blue'
                }
            ]
        })
    };

    window.jAjaxErrorHandler = function (jqXHR, textStatus, errorThrown) {
        if (typeof($.browser) != 'undefined' && $.browser.webkit && textStatus === 'error' && !jqXHR.getAllResponseHeaders()) textStatus = 'abort'; // chrome throw "error" on user refresh
        /* "success", "notmodified", "error", "timeout", "abort", or "parsererror" */
//        if ($.inArray(textStatus, ["timeout", "abort", "parsererror"]) >= 0) return;
        if (textStatus === "abort") return;

        if (jqXHR.responseText === 'unauthorized') {
            try {
                if (window.parent != window){
                    parent.location.href = '/security/unauthorized.php?BackTo=' + encodeURI(parent.location.href);
                    return;
                }
                // eslint-disable-next-line no-empty
            } catch(e){}
            location.href = '/security/unauthorized.php?BackTo=' + encodeURI(location.href);
            return;
        }
        var options = {error: textStatus};
        if(typeof(window.debugMode) != 'undefined' && window.debugMode)
            options.content = '[ajax error: ' + jqXHR.status + ' ' + textStatus + ']\n\n' + jqXHR.responseText;
        window.jError(options);
    };

});