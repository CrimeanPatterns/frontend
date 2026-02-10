var Passengers = (function(InputStyle){
    var passengerList = '#passenger-list';
    return {
        init: function(){
            // number of passengers
            Passengers.processNumberPassengers();
            // reset templates
            $('select[id^="booking_request_Passengers_"][id$="_templates"]')
                .find('option:eq(0)')
                .prop('selected', true)
                .trigger("change");

            if (AddForm.byBooker()) {
                $('#passenger-list').children().each(function(){
                    $.proxy(Passengers.processPassenger, $(this))();
                });
                $('#passenger-list').find('input.select2').each(function(){
                    Passengers.processUseragent($(this), $(this).select2('data'));
                });
            }

            Passengers.event();

            $('a[data-id="tab1"]', '#booking-form-tabs').one('click', function() {
                $('#booking_request_NumberPassengers').change();/* wrong width when the element is hidden */
            });
        },
        event: function(){
            $('#booking_request_NumberPassengers').change(function(e) {
                Passengers.processNumberPassengers();
            });
            AddForm.form
                .on('save_form', function(event, data) {
                    var count=$('#passenger-list').children().length;
                    console.log("saving collection Passengers, rows: " + count);
                    data['minor']['Passengers'] = count;
                })
                .on('restore_form', function(event, data) {
                    Passengers.setPassengerCount(data['minor']['Passengers']|| $('#passenger-list').children().length);
                });

            if (AddForm.byBooker()) {
                $('#tab2')
                    .on('change', 'input.select2', function(event) {
                        var newPassengerCheckbox = $(event.target)
                                .closest('.inner-block')
                                .find('[name$="[new_member]"]'),
                            item = event.added;
                        if (item == null)
                            return;

                        Passengers.togglePassengerInfo($(event.target).closest('.inner-block'), false, true);
                        Passengers.setTemplate(event.target, item);
                        Passengers.processUseragent($(this), item);
                        if (newPassengerCheckbox.is(':checked'))
                            newPassengerCheckbox.click();
                    })
                    .on('change', '[name$="[new_member]"]', Passengers.processPassenger)
                    .on('change', '[name$="[new_member]"]', Passengers.checkMember)
                    .on('input change paste', '[name$="[FirstName]"], [name$="[LastName]"]', Passengers.checkMember);
            }
        },
        processUseragent: function(obj, item) {
            if (item == null || item['Connected'] == null)
                return;
            var message = (item.Connected == "0") ?
                Translator.trans('booking.request.add.form.passengers.can-edit', {'link': '/agent/editFamilyMember.php?ID='+item.UserAgentID}, 'booking')
                : Translator.trans('booking.request.add.form.passengers.can-not-edit', {}, 'booking');
            obj.siblings('.info').remove().end().parent().append('<span class="info">'+message+'</span>');
        },
        processPassenger: function(e) {
            var passenger = (e != null && e.target) ? $(e.target).closest('.inner-block') : this,
                isEvent = typeof(e) != 'undefined',
                newPassenger = passenger.find('[name$="[new_member]"]'),
                ua = passenger.find('[name$="[Useragent]"]');
            Passengers.togglePassengerInfo(
                passenger,
                ua.val() == "" && !newPassenger.is(':checked'),
                isEvent
            );
            if (isEvent && $(e.target).is(':checked')) {
                ua.val("").trigger('change');
                Passengers.setTemplate(this, []);
                passenger.find('input.select2').siblings('.info').remove();
            }
        },
        checkMember: function() {
            var passenger = $(this).closest('fieldset'),
                ff = passenger.find('[name$="[FirstName]"]'),
                lf = passenger.find('[name$="[LastName]"]'),
                fn = $.trim(ff.val()),
                ln = $.trim(lf.val()),
                text =  Translator.trans(/** @Desc("A traveler with the same name is already added in the list of Members. Please disregard, if this is a different person") */'booking.request.add.form.passengers.found', {}, 'booking'),
                req,
                cancel = function(){
                    req = passenger.data('request');
                    if (req != null && typeof(req['abort']) == 'function')
                        req.abort();
                },
                add = function(){
                    remove();
                    ff.closest('.row').addClass('warning').find('.message').text(text);
                    lf.closest('.row').addClass('warning').find('.message').text(text);
                },
                remove = function(){
                    ff.closest('.row').removeClass('warning').find('.message').empty();
                    lf.closest('.row').removeClass('warning').find('.message').empty();
                };
            cancel();
            if (fn == "" || ln == "" || !passenger.find('[name$="[new_member]"]').is(':checked')) {
                remove();
            } else {
                passenger.data('request', $.post(
                    Routing.generate('aw_booking_json_checkmember'),
                    {fn: fn, ln: ln},
                    function(data) {
                        if (data == "1")
                            add();
                        else
                            remove();
                    }
                ));
            }
        },
        setTemplate: function(obj, data) {
            if (typeof(data) == 'string')
                data = eval('('+data+')');
            var field = $(obj).closest('.inner-block');
            // first name
            field.find('input[name$="[FirstName]"]').val((data['FirstName'] != null)?data['FirstName']:"").trigger('change');
            // middle name
            field.find('input[name$="[MiddleName]"]').val((data['MiddleName'] != null)?data['MiddleName']:"").trigger('change');
            // last name
            field.find('input[name$="[LastName]"]').val((data['LastName'] != null)?data['LastName']:"").trigger('change');
            // birthday
            var bd = field.find('input.date[type="text"][name$="[Birthday]"]');
            var newDate = (data['Birthday'] != null)
                ? $.datepicker.parseDate(bd.datepicker('option', 'altFormat'), data['Birthday']) : "";
            bd.datepicker('setDate', newDate).trigger('change');
            // gender
            field.find('input[type="radio"][name$="[Gender]"]').each(function(){
                $(this).attr('checked', false).trigger('change');
                InputStyle.checks(this);
            });
            if (data['Gender'] != null) {
                $('input[type="radio"][name$="[Gender]"][value='+data['Gender']+']').click().click();
            }

            // nationality
            field.find('input[type="radio"][name$="[Nationality][choice]"]').each(function(){
                $(this).attr('checked', false).trigger('change');
                InputStyle.checks(this);
            });
            field.find('input[type="text"][name$="[Nationality][text]"]').removeAttr('required').val("").trigger('change')
                .closest('.two_choices_or_text_widget_container')
                .hide()
                .closest('.two-choices').nextAll('span.info').hide();
            if (data['Nationality'] != null) {
                if (data['Nationality'] == 'US') {
                    // TODO: double click???
                    field.find('input[type="radio"][name$="[Nationality][choice]"]').first().click().click();
                } else {
                    // TODO: double click???
                    field.find('input[type="radio"][name$="[Nationality][choice]"]').last().click().click();
                    field.find('input[type="text"][name$="[Nationality][text]"]').attr('required', true).val(data['Nationality']).trigger('change');
                }
            }
        },
        processNumberPassengers: function(){
            var value = $('#booking_request_NumberPassengers').val() || 1;
            Passengers.setPassengerCount(value);
            AddForm.form.trigger('form_change');
        },
        setPassengerCount: function(number){
            number=number||1;
            var count=$('#passenger-list').children().length;
            while(number<count){
                Passengers.removePassenger(count --);
            }
            while(number>count){
                Passengers.addNewPassenger();
                count++;
            }
            InputStyle.select('#booking_request_NumberPassengers');
        },
        addNewPassenger: function() {
            var passengerCollection = $('#passenger-list');
            var index = passengerCollection.children().length;
            var passengerForm = $(passengerCollection.attr('data-prototype').replace(/__name__/g, index));
            var passengerTitle = passengerForm.find('legend h3');
            passengerTitle.text(passengerTitle.text().replace(/#\d+/g, '#'+(++index)));
            passengerCollection.append(passengerForm);
            $('#booking_request_NumberPassengers').val(index);
            InputStyle.init(passengerForm);
            if (AddForm.byBooker())
                $.proxy(Passengers.processPassenger, passengerForm)();
        },
        removePassenger: function(i) {
            var passengerCollection = $('#passenger-list');
            var p = passengerCollection.find('fieldset');
            var count = p.length;
            i=(i>0?i:1)-1||count-1||1;
            p.slice(i).each(function(){
                passengerCollection.trigger('removed', $(this));
            }).remove();
            count = passengerCollection.find('fieldset').length;
            $('#booking_request_NumberPassengers').val(count);
        },
        togglePassengerInfo: function(passenger, disabled, clearErrors){
            clearErrors = (typeof(clearErrors) == 'undefined')?false:clearErrors;
            if (clearErrors)
                passenger.find('.row').removeClass('error');
            // first name
            passenger.find('input[name$="[FirstName]"]').prop('disabled', disabled);
            // middle name
            passenger.find('input[name$="[MiddleName]"]').prop('disabled', disabled);
            // last name
            passenger.find('input[name$="[LastName]"]').prop('disabled', disabled);
            // birthday
            var bd = passenger.find('input.date[type="text"][name$="[Birthday]"]');
            if (disabled)
                bd.datepicker('disable');
            else
                bd.datepicker('enable');
            // gender
            passenger.find('input[type="radio"][name$="[Gender]"]').each(function(){
                $(this).prop('disabled', disabled);
            });
            // nationality
            passenger.find('input[type="radio"][name$="[Nationality][choice]"]').each(function(){
                $(this).prop('disabled', disabled);
            });
            passenger.find('input[type="text"][name$="[Nationality][text]"]').prop('disabled', disabled);
        },
        check: function() {
            if (AddForm.byBooker()) {
                var ua, newuser, hasError = false;
                $("#passenger-list").children().each(function(i, elem){
                    ua = $(elem).find('input[name$="[Useragent]"]')
                    newuser = $(elem).find('input[name$="[new_member]"]')
                    if (ua.val() == "" && !newuser.is(':checked')) {
                        hasError = true;
                        ua.closest('div.row').addClass('error required').find('div.message').text(Translator.trans('notblank', {}, 'validators')).show();
                    }
                });
                if (hasError) {
                    var firstError = $("#passenger-list .row.error");
                    AddForm.activateElementTab(firstError);
                    formValidator.checkRequiredFields(AddForm.getActiveTab(), false);
                    formValidator.focus($("#passenger-list .row.error").first(), 250);
                    return false;
                }
                return formValidator.checkRequiredFields(AddForm.getActiveTab());
            }

            var cabinService = $('.prefer').find('span.checked');
            var errors = [];

            if(!cabinService.length){
                var id = $('.prefer').find('input[type="checkbox"]').attr('id');
                var message = Translator.trans('booking.request.add.form.passengers.cabin', {}, 'booking');
                var row = $('#'+id);
                row.closest('div.row').addClass('error required').find('div.message').text(message).show();
                errors.push(row);
            }

            $('#tab2').find('.date[name$="[Birthday]"]').each(function (i, elem) {
                var latest = new Date();
                latest.setDate(latest.getDate() - 1);

                var earliest = new Date();
                earliest.setFullYear(1910, 0, 1);

                var birthday = $(elem).datepicker("getDate");
                var message = null;

                if (birthday >= latest) {
                    message = Translator.trans('booking.birthday.max', {}, 'validators');
                }else if(birthday < earliest){
                    message = Translator.trans('booking.birthday.min', {}, 'validators');
                }

                if(message){
                    $(elem).closest('div.row').addClass('error required').find('div.message').text(message).show();
                    errors.push($(elem));
                }
            });

            if(errors.length){
                AddForm.activateElementTab(errors[0]);
                formValidator.focus(errors[0].closest('div.row').find('input'), 250);
            }

            return !errors.length;
        },

        select2Options: {
            ajax: {
                url: Routing.generate('aw_booking_json_searchmembers', {}, false),
                dataType: 'json',
                data: function (term, page) {
                    var exclude = [], e;
                    term = term.replace(/[^A-Za-z0-9А-Яа-я ]+/g, '');
                    $('input[name$="[Useragent]"]').each(function(){
                        e = $(this).val();
                        if (e != "" && e != 0)
                            exclude.push(e);
                    });
                    return {
                        query: term, // search term
                        excluding: exclude.join(',')
                    };
                },
                results: function(data, page) {
                    return {results: data};
                }
            }
        }
    };
})(InputStyle);