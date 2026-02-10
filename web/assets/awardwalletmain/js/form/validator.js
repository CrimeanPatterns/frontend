var formValidator = {

	checkRequiredFields: function(container, focus) {
		var inputs = container.find('input[required], select[required], textarea[required], checkbox[required]')
            .not(':disabled')
            .filter(AddForm.notHidden)
            .filter(AddForm.elementEmpty);
        focus = (typeof(focus) == 'undefined') ? true : focus;
		if (inputs.length > 0) {
			inputs.closest('div.row').addClass('error required').find('div.message').text(Translator.trans('notblank', {}, 'validators')).show();
			inputs.closest('.error-able').addClass('error required');
            if (focus) {
                var first = inputs.first();
                AddForm.activateElementTab(first);
                formValidator.focus(first, 250);
            }
			return false;
		}
		// check date pickers
		inputs = container.find('input.date[required]').not(':disabled');
		for (var n = 0; n < inputs.length; n++) {
			var input = inputs.eq(n);
			var inst = $.datepicker._getInst(input.get(0));
            if ($.trim(input.val()) == "") {
                input.closest('div.row').addClass('error').find('div.message').text(Translator.trans('notblank', {}, 'validators')).show();
                if (focus) {
                    AddForm.activateElementTab(input);
                    formValidator.focus(input, 250);
                }
                return false;
            }
		}
		return true;
	},

    displayErrors: function (form) {
        var errors = form.find('div.row.error');
        if (errors.length == 0) errors = form.find('.error');
        if (errors.length > 0) {
            var error = errors.first();
            AddForm.activateElementTab(error);
            var element = error.find('input[type=text], input[type=email], select, textarea, checkbox').first();
            if (element.length > 0)
                setTimeout(function () {
                    formValidator.focus(element, 250);
                }, 500);
        }
    },

    focus: function(element, duration) {
        var top = -20,
            header = $('header');
        if (header && header.css('position') == 'fixed') {
            top -= header.outerHeight();
        }
        $.scrollTo(element.closest(':visible'), duration, {
            offset: {top: top},
            onAfter: function() {
                if (element.is(':visible'))
                    element.focus();
            }
        });

    }

}