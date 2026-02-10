$(function () {
    window.csrf_token = null;

    // show error dialogs on ajax errors
    $(document).ajaxError(function (event, jqXHR, ajaxSettings) {
        // retry with fresh CSRF token, if we got CSRF error from server
        const canCsrfRetry = typeof(ajaxSettings.csrfRetry) === 'undefined';
        const csrfCode = jqXHR.getResponseHeader('X-XSRF-TOKEN');
        const csrfFailed = jqXHR.getResponseHeader('X-XSRF-FAILED') === 'true';

        if (csrfCode) {
            window.csrf_token = csrfCode;
        }

        if (jqXHR.status === 403 && csrfFailed && canCsrfRetry) {
            console.log('retrying with fresh CSRF, should receive on in headers');
            // mark request as retry
            ajaxSettings.csrfRetry = true;
            $.ajax(ajaxSettings);

            return;
        }
        if (ajaxSettings.disableAwErrorHandler) {
            return;
        }
        if (jqXHR.responseText === 'unauthorized') {
            try {
                if (window.parent != window){
                    parent.location.href = '/security/unauthorized.php?BackTo=' + encodeURI(parent.location.href);
                    return;
                }
                // eslint-disable-next-line no-empty
            } catch(e){}
            location.href = '/security/unauthorized.php?BackTo=' + encodeURI(location.href);
        }
    });

    // add CSRF header to ajax POST requests
    $(document).ajaxSend(function (elm, xhr, s) {
        if (window.csrf_token === null) {
            window.csrf_token = document.head.querySelector('meta[name="csrf-token"]').content;
        }
        xhr.setRequestHeader('X-XSRF-TOKEN', window.csrf_token);
    });


    $(document).ajaxSuccess(function(event, jqXHR, settings){
        const csrfCode = jqXHR.getResponseHeader('X-XSRF-TOKEN');

        if (csrfCode) {
            window.csrf_token = csrfCode;
        }
    });

    window.onerrorCounter = 0;
    window.onerrorHandler = function(e) {
        window.onerrorCounter++;
        if (window.onerrorCounter < 10) {
            $.post('/js_error', {
                error: e.message,
                file: e.fileName,
                line: e.lineNumber,
                column: e.columnNumber,
                stack: e.stack
            }, {
                disableErrorDialog: true
            });
        }
    };
    window.onerror = function(message, file, line, column, e) {
        if (e && typeof(e) == 'object') {
            window.onerrorHandler(e)
        } else {
            window.onerrorHandler({
                message: message,
                fileName: file,
                lineNumber: line,
                columnNumber: column,
                stack: null
            })
        }
        return false;
    }

});

