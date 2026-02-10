define(['jquery-boot', 'routing'], function ($) {

	// Autocomplete custom program name and coupon name
	$(document).on('focus', '.cp-autocomplete:not(.ui-autocomplete-input):not(.autocomplete-data-choices)', function (e) {
		var $el = $(this);
		$el.autocomplete({
			source: function (request, response) {
                var term = request.term.replace(/[^A-Za-z0-9А-Яа-я\.\-' ]+/g, '');
				var data = {};
				if (undefined !== typeof $el.data('request-fields')) {
					data.requestFields = $el.data('request-fields');
				}
                if (undefined !== typeof $el.data('active')) {
                    data.active = $el.data('active');
                }
				// if (term.length > 6) return;
				$.ajax({
					url: Routing.generate('aw_coupon_json_progs', {query: term}),
					data: data,
					dataType: "json",
					success: function (data) {
						response(data);
					}
				});
			},
			select: function (event, ui) {
				var requestFields = $(event.target).data('request-fields');
				if ('undefined' !== typeof requestFields) {
					requestFields = requestFields.split(',');
					for (var i in requestFields) {
						if (undefined !== typeof ui.item[requestFields[i]])
							$(event.target).data('field_' + requestFields[i], ui.item['field_' + requestFields[i]]);
					}
				}
				$(event.target).val(ui.item.value);
				$(event.target).data('providerId', ui.item.id).trigger('change');
                var $kinds = $('#add_coupon_kind,#providercoupon_kind,#account_kind');
                if ($kinds.length > 0) {
                    $kinds.each(function() {
                        $(this).hasClass("select2-hidden-accessible") ? $(this).select2('val', ui.item.kind) : $(this).val(ui.item.kind);
                    });
                }
			},
			minLength: 2
		});

		// Autocomplete highlighting
		$.ui.autocomplete.prototype._renderItem = function (ul, item) {
			var regex = new RegExp("(" + this.element.val().replace(/[^A-Za-z0-9А-Яа-я]+/g, '') + ")", "gi"),
				html = $('<div/>').text(item.label).html().replace(regex, "<b>$1</b>");
			return $("<li></li>")
					.data("item.autocomplete", item)
					.append($("<a></a>").html(html))
					.appendTo(ul);
		};

	});

});
