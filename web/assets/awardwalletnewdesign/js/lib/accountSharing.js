define(['jquery-boot', 'jqueryui'], function ($) {
    $(function () {
        $(document).on('click', '.js-share-account-toggle', function (e) {
            e.preventDefault();
            var form = $(e.target).closest('form');
            if ($(form).find('.js-share-account-table').is(':visible')) {
                $(form).find('.js-share-account-toggle').first().show();
                $(form).find('.js-share-account-toggle').last().hide();
                $(form).find('.js-share-account-table').slideUp();
            } else {
                $(form).find('.js-share-account-toggle').first().hide();
                $(form).find('.js-share-account-toggle').last().show();
                $(form).find('.js-share-account-table').slideDown();
            }
        });

        $(document).on('change', '.js-useragent-select', function (e) {
            var $form = $(e.target).closest('form');
            var $selected = $(this).find('option:selected');
            if ($selected.data('shareable') === 'shareable') {
                $form.find('.js-share-account-list').show();
                $form.find('.js-share-account-list input[type="checkbox"]').removeAttr('disabled');
            } else {
                $form.find('.js-share-account-list').hide();
                $form.find('.js-share-account-list input[type="checkbox"]').attr('disabled', 'disabled');
            }

            var value = $(this).val();
            $(window).trigger('person.activate', value);
        });

	    $(document).on('dom_change', function () {
		    $('.js-useragent-select:visible').first().trigger('change');
	    });
	    $('.js-useragent-select:visible').first().trigger('change');
    });
});