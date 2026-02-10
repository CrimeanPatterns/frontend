var propertiesFormRequest = {
    required: {},
    url: '',
    form: null,
    formSubmitted: false,
    StatusField: null,
    CancelReasonField: null,
    UntilDateField: null,
    InternalStatusField: null,
    registerUrl: null,

    init: function () {
        $(document).ready(function () {
            propertiesFormRequest.form = $('.js-properties-form').find('form');
            propertiesFormRequest.StatusField = $('#booking_request_properties_Status');
            propertiesFormRequest.CancelReasonField = $('#booking_request_properties_CancelReason');
            propertiesFormRequest.UntilDateField = $('#booking_request_properties_UntilDate');
            propertiesFormRequest.InternalStatusField = $('#booking_request_properties_InternalStatus');
            propertiesFormRequest.AssignedField = $('#booking_request_properties_Assigned');
            propertiesFormRequest.StatusField.change(function () {
                propertiesFormRequest.CancelReasonField.closest('.row').hide();
                propertiesFormRequest.UntilDateField.closest('.row').hide();
                propertiesFormRequest.required['UntilDate'] = null;
                switch (parseInt($(this).val())) {
                    case 1: // processing
//                                propertiesFormRequest.required['FinalServiceFee'] = 'float';
//                                propertiesFormRequest.required['FinalTaxes'] = 'float';
//                                propertiesFormRequest.required['FeesPaidTo'] = 'required';
                        break;
                    case 3: // cancel
                        propertiesFormRequest.CancelReasonField.closest('.row').show();
                        break;
                    case 5: // future
                        propertiesFormRequest.UntilDateField.closest('.row').show();
//                                propertiesFormRequest.required['UntilDate'] = 'date-future';
                        break;
                }
            });

            propertiesFormRequest.UntilDateField.val(propertiesFormRequest.UntilDateField.attr('value'));
            propertiesFormRequest.AssignedField.val(propertiesFormRequest.AssignedField.find("option[selected]").val());
            propertiesFormRequest.AssignedField.change();
            propertiesFormRequest.CancelReasonField.val(propertiesFormRequest.CancelReasonField.find("option[selected]").val());
            propertiesFormRequest.CancelReasonField.change();
            propertiesFormRequest.InternalStatusField.val(propertiesFormRequest.InternalStatusField.find("option[selected]").val());
            propertiesFormRequest.InternalStatusField.change();
            propertiesFormRequest.StatusField.val(propertiesFormRequest.StatusField.find("option[selected]").val());
            propertiesFormRequest.StatusField.change();
            propertiesFormRequest.StatusField.change(function () {
                propertiesFormRequest.clearErrors();
            });
            $('#propertiesFormSubmitButton').click(propertiesFormRequest.submitClick);
        });
    },
    submitClick: function (event) {
        event.preventDefault();
        propertiesFormRequest.clearErrors();
        if (propertiesFormRequest.submitCheck()) {
            propertiesFormRequest.doSubmit();
        }
    },
    doSubmit: function () {
        if (!propertiesFormRequest.formSubmitted) {
            $.ajax({
                url: propertiesFormRequest.url,
                data: propertiesFormRequest.form.serialize(),
                type: 'POST',
                dataType: 'JSON',
                success: function (result) {
                    if (result.success == true) {
                        propertiesFormRequest.formSubmitted = true;
                        var indicator = $('#propertes-update-indicator');
                        indicator.fadeIn(200).effect('pulsate');
                        setTimeout(function () {
                            indicator.fadeOut(500);
                            propertiesFormRequest.formSubmitted = false;
                        }, 5000);
                        $('#commonMessages').find('.message-body').load(Routing.generate('aw_booking_message_getmessages', {
                            id: $('#requestView').data('id'),
                            internal: 'common'
                        }), function () {
                            $('#request-status').text(propertiesFormRequest.form.find('#booking_request_properties_Status option:selected').text());
                        });
                    } else {
                        for (var n in result.errors) {
                            var elem = $('#booking_request_properties_' + n);
                            elem.closest('div.row').addClass('error required').find('div.message').html(result.errors[n]).show();
                        }
                        propertiesFormRequest.formSubmitted = false;
                    }
                },
                beforeSend: function () {
                    $('#propertiesFormSubmitButton').addClass('loader');
                },
                complete: function () {
                    $('#propertiesFormSubmitButton').removeClass('loader');
                }
            });
        }
        return false;
    },
    clearErrors: function () {
        var elem = propertiesFormRequest.form.find('div.row.error select, div.row.error input, div.row.error textarea, div.row.error checkbox')
            .closest('div.row').removeClass('error required').find('div.message').text('').hide()
    },
    submitCheck: function () {
        var req = 0;
        var elem;
        for (var n in propertiesFormRequest.required) {
            elem = $('#booking_request_properties_' + n);
            switch (propertiesFormRequest.required[n]) {
                case 'required':
                    if (!elem.val()) {
                        req++;
                        elem.closest('div.row').addClass('error required').find('div.message').text('This field is required').show();
                    }
                    break;
                case 'float':
                    if (!(+(elem.val().replace(',', '.')))) {
                        req++;
                        elem.closest('div.row').addClass('error required').find('div.message').text('This field is required').show();
                    }
                    break;
                case 'date-future':
                    elem = $('#booking_request_properties_' + n + '_datepicker');
                    var inst = $.datepicker._getInst(elem.get(0));
                    try {
                        var date = $.datepicker.parseDate(elem.datepicker("option", "dateFormat"), elem.val(), $.datepicker._getFormatConfig(inst));
                    }
                    catch (err) {
                        req++;
                        elem.closest('div.row').addClass('error required').find('div.message').text('{{ "booking.request.properties.error.date_future"|trans({}, "booking") }}').show();
                        break;
                    }
                    if (date <= new Date()) {
                        req++;
                        elem.closest('div.row').addClass('error required').find('div.message').text('{{ "booking.request.properties.error.date_future"|trans({}, "booking") }}').show();
                    }
                    break;
            }
        }
        return (req == 0);
    }
};
