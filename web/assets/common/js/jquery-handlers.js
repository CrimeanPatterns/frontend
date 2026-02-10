define([], function() {
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
			require(['lib/errorDialog'], function (showErrorDialog) {
				showErrorDialog({
					status: jqXHR.status,
					data: jqXHR.responseJSON ? jqXHR.responseJSON : jqXHR.responseText,
					config: {
						method: ajaxSettings.type,
						url: ajaxSettings.url,
						data: decodeURI(ajaxSettings.data)
					}
				}, (typeof(ajaxSettings.disableErrorDialog) != 'undefined' && ajaxSettings.disableErrorDialog));
			});
		});

		// add CSRF header to ajax POST requests
		$(document).ajaxSend(function (elm, xhr, s) {
			if (window.csrf_token === null) {
				window.csrf_token = document.head.querySelector('meta[name="csrf-token"]').content;
			}
			xhr.setRequestHeader('X-XSRF-TOKEN', window.csrf_token);
		});


		$(document).ajaxSuccess(function(event, jqXHR, settings){
			const mailErrors = $.trim(jqXHR.getResponseHeader('x-aw-mail-failed'));
			const csrfCode = jqXHR.getResponseHeader('X-XSRF-TOKEN');

			if (mailErrors != '' && !settings.suppressErrors){
				require(['lib/mailErrorDialog'], function (showErrorDialog) {
					showErrorDialog(mailErrors);
				});
			}

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
					stack: e.stack,
					source:'Global'
				}, {
					disableErrorDialog: true
				});

				if (typeof (e.fileName) != 'undefined') {
					var a = document.createElement('a');
					a.href = e.fileName;
					if (a.href.indexOf('service-worker.js') >= 0 || (a.hostname && (a.hostname == window.location.hostname))) { // exclude external scripts
						require(['lib/errorDialog'], function (showErrorDialog) {
							showErrorDialog({
								status: 0,
								data: e.message + '<br\><br\>' + e.stack // only in debug mode
							});
						});
					}
				}
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
});
