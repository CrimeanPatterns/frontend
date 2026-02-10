define(['jquery-boot', 'jqueryui', 'angular-boot', 'translator-boot'], function ($) {
    $(document).on('focus', '.js-useragent-autocomplete:not(.ui-autocomplete-input):not(.autocomplete-data-choices)', function (e) {

		var url = $(this).data('url') || Routing.generate('aw_account_get_owners'),
            NoResultsLabel =  Translator.trans('award.account.list.empty-autocomplete');

		$(this).autocomplete({
			minLength: 2,
			source: function (request, response) {
				$('.js-useragent-autocomplete').prev().val('');
				var element = $(this.element).addClass('loading-input');
				$.ajax({
					url: url,
					data: {q: request.term, add: true},
					method: 'GET',
					success: function (data) {
						element.removeClass('loading-input');
						if (data && angular.isArray(data) && data.length) {
							response(data);
						} else {
							response([{label: NoResultsLabel, value: ''}]);
						}
					},
					error: function (data) {
						element.removeClass('loading-input');
						response(request.term);
					}
				})
			},
			select: function (event, ui) {
                if (ui.item.forwardEmail) {
                    var emails = $('.userEmail').filter(function(id, item){return $(item).find('nobr').length == 0});
                    emails.each(function(id, item){
                        $(item).text(ui.item.forwardEmail);
                    })
                }

				if (ui.item.value) {
					$(event.target).val(ui.item.label);
					$(event.target).prev().val(ui.item.value);
					$(event.target).prev().trigger('input');
				}
				return false;
			},
			focus: function(event, ui) {
				if (event.keyCode == 40 || event.keyCode == 38)
					if (ui.item.value)
						$(event.target).val(ui.item.label);
				return false;
			}

		}).click(function () {
			$(this).select();
		}).blur(function (event) {
			if ($(event.target).val() == '') {
				$(event.target).val($(event.target).data('empty'));
				$(event.target).prev().val('');
				$(event.target).prev().trigger('input');
			}
		}).trigger('autocompleteselect');

        $.ui.autocomplete.prototype._renderItem = function (ul, item) {
            const regex = new RegExp("(" + this.element.val().replace(/[^A-Za-z0-9А-Яа-я ]+/g, '') + ")", "gi");
            let html = item.label.replace(regex, "<b>$1</b>");

            if(item.email && !item.extra)
                html += ", <b>" + item.email + "</b>";
	        if ('undefined' !== typeof item.count)
                html += " (" + item.count + ")";
            if (item.extra){
            	const extra = $('<div/>').text(item.extra).html();
				html += " (sub-account of " + extra + (item.email ? ", <b>" + item.email + "</b>": '') + ")";
			}
            return $("<li></li>")
                .data("item.autocomplete", item)
                .append($("<a></a>").html(item.label === NoResultsLabel ? item.label  : html))
                .appendTo(ul);
        };
    });

    $('.autocomplete-data-choices').each(function() {
        $(this).autocomplete({
            minLength: 0,
            source: JSON.parse($(this).attr('data-choices')),
        });
        $(this).autocomplete('instance')._renderItem = function(ul, item) {
            const regex = new RegExp("(" + this.element.val().replace(/[^A-Za-z0-9А-Яа-я]+/g, '') + ")", "gi");
            const html = $('<div/>').text(item.label).html().replace(regex, "<b>$1</b>");

            return $('<li></li>').data('item.autocomplete', item).append($('<a></a>').html(html)).appendTo(ul);
        };
        $(this).bind('focus', function() {
            $(this).autocomplete('search');
        });
    });

});
