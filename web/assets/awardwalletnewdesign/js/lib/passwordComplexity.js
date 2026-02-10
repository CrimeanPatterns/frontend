let passwordComplexity;

(() => {
	function showPasswordNotice() {
		let frame = $(this).closest('table.inputFrame');
		if (frame.length === 0) {
			frame = $(this);
		}
		const div = $('#password-notice');
		div.prependTo(frame.parent());
		const left = frame.position().left + frame.width() + parsePosition(frame.css('padding-left')) + parsePosition(frame.css('padding-right')) + 5 - parsePosition(div.css('margin-left'));
		div
			.css('top', frame.position().top - parsePosition(div.css('margin-top')))
			.css('left', left).css('visibility', 'hidden').show();
		let height = 0;
		div.children().each((index, el) => {
			el = $(el);
			height += el.height() + parsePosition(el.css('margin-top')) + parsePosition(el.css('margin-bottom')) + parsePosition(el.css('padding-top')) + parsePosition(el.css('padding-bottom'));
		});
		div.css('height', height + parsePosition(div.css('padding-top')) + parsePosition(div.css('padding-bottom'))).css('visibility', 'visible');
	}

	function hidePasswordNotice() {
		$('#password-notice').hide();
	}

	function trackComplexity(value) {
		const checks = {
			'password-length': value.length >= 8 && lengthInUtf8Bytes(value) <= 72,
			'lower-case': value.match(/[a-z]/) != null,
			'upper-case': value.match(/[A-Z]/) != null,
			'special-char': value.match(/[^a-zA-Z\s]/) != null
		};
		if (self.getLoginCallback) {
			const login = self.getLoginCallback().toLowerCase();
			const email = self.getEmailCallback().replace(/@.*$/, '').toLowerCase();
			checks.login = (value.toLowerCase().indexOf(login) === -1 || login === '') && (value.toLowerCase().indexOf(email) === -1 || email === '');
		}
		$('#meet-login').toggle(self.getLoginCallback != null);

		const errors = [];
		$.each(checks, (key, match) => {
			const meetDiv = $('#meet-' + key);
			meetDiv.toggleClass('allowed', match);
			if (!match) {
				errors.push(meetDiv.text());
			}
		});

		return errors;
	}

	function parsePosition(pos){
		let result = parseInt(pos);
		if (isNaN(result)) {
			result = 0;
		}

		return result;
	}

	function lengthInUtf8Bytes(str) {
		// Matches only the 10.. bytes that are non-initial characters in a multi-byte sequence.
		const m = encodeURIComponent(str).match(/%[89ABab]/g);
		return str.length + (m ? m.length : 0);
	}

	const self = {
		passwordField: null,
		getLoginCallback: null,
		getEmailCallback: null,

		init: (passwordField, getLoginCallback, getEmailCallback) => {
			self.passwordField = passwordField;
			self.getLoginCallback = getLoginCallback;
			self.getEmailCallback = getEmailCallback;
			passwordField
				.on("focus", null, null, showPasswordNotice)
				.on("blur", null, null, hidePasswordNotice)
				.on("keypress paste change keydown focus input", null, null, () => {
					setTimeout(() => {
						trackComplexity(self.passwordField.val());
					}, 0);
				});
			trackComplexity(self.passwordField.val());
		},

		getErrors: () => {
			return trackComplexity(self.passwordField.val());
		}
	};

	if (typeof(define) !== 'undefined') {
		define(['jquery-boot', 'translator-boot'], () => {
			return self;
		});
	} else {
		passwordComplexity = self;
	}
})();

