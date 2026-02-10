define(['jquery-boot', 'routing', 'jqueryui', 'translator-boot'], function ($) {
	var stateIdElement = $('#billing_address_stateid').parents('.input-item');
	var textReplacer = $('<input autocomplete="off" type="text" id="billing_address_stateid" name="billing_address[stateid]" /><input type="hidden" name="billing_state_is_text" value="1" />');


	$('#billing_address_countryid').on('change paste', function () {
		var selectedCountry = $(this).find('option:selected').val();
		var selectedStateName;
		if (stateIdElement.find('option:selected').length > 0) {
			selectedStateName = stateIdElement.find('option:selected').text();
		} else {
			selectedStateName = stateIdElement.find('input[id]').val();
		}
		var element;

		stateIdElement.append('<div style="position: absolute; left: 305px; top: 8px" class="loader"></div>');

		// todo fail!
		$.ajax({
			url: Routing.generate('aw_billing_get_states'),
			data: {
				countryId: selectedCountry
			},
			success: function (data) {
				if (data.success && data.states.length) {
					element = $('<div class="styled-select"><select id="billing_address_stateid" name="billing_address[stateid]"></select></div>');

                    $.each(data.states, function (index, state) {
						var option = $('<option value="' + state.id + '">' + state.name + '</option>');
						if (selectedStateName == state.name)
							option.prop('selected', true);
						element.find('select').append(option);
					});
				} else {
					textReplacer.first().val(selectedStateName);
					element = textReplacer;
				}
				$(stateIdElement).html(element);
			}
		});
	}).trigger('change');


	// Select billing
	$('#change-billing-popup')
		.dialog({
			width: 820,
			title: Translator.trans(/** @Desc("Saved data") */ 'saved.data'),
			autoOpen: false,
			modal: true,
			buttons: [
				{
					text: Translator.trans('form.button.cancel'),
					'class': 'btn-blue',
					click: function () {
						$(this).dialog('close');
					}
				}
			],
			open: function () {
				$(this).parent().find('.ui-dialog-buttonset').find('button').focus();
			}
		})
		.on('click', '.use-this-address', function (e) {
			e.preventDefault();
			$(e.target).addClass('loader');
			// todo fail!
			$.get(Routing.generate('aw_billing_set', {id: $(this).data('id')})).always(function (data) {
				$(e.target).removeClass('loader');
				$('#billing-address').html(data);
				$('#change-billing-popup').dialog('close');
			});
		})
		.on('click', '.delete-address', function (e) {
			e.preventDefault();
			// todo fail!
			$.post(Routing.generate('aw_billing_delete', {id: $(this).data('id')})).always(function (data) {
				if (data.success)
					$(e.target).parents('.col').fadeOut(300, function () {
						var id = $(this).find('button.use-this-address').data('id');
						var addressId = $('#billing-address').find('input[type=hidden]').val();
						var addressBlock = $('.add-block');
						$(this).remove();

						if (addressBlock.find('button.use-this-address').length == 0) {
							$('#change-billing-popup').dialog('close');
							window.location.reload();
						} else {
							var lastAddressId = addressBlock.find('button.use-this-address').last().data('id');
							if (id == addressId) {
								// todo fail!
								$.get(Routing.generate('aw_billing_set', {id: lastAddressId})).always(function (data) {
									$('#billing-address').html(data);
								});
							}
						}
					});
			});
		});

	$('#billing-address').on('click', '#change-billing', function (e) {
		e.preventDefault();
		$('#change-billing-popup').dialog('open');
	});
});