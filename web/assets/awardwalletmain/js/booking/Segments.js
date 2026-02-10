var Segments = (function(Translator, formValidator){
    return {
        init: function(){
            Segments.processSegments(true, false, true);
            var manager = new CollectionManager(
                'Segments',
                '#segment-list',
                $('#segment-list').attr('data-prototype'),
                '#add-destination',
                '.del-block'
            );
            manager.animationShow = {
                type: 'show',
                duration: 0
            };
            manager.animationHide = {
                type: 'hide',
                duration: 0
            };
            manager.beforeAdd = function(a) {
                return Segments.check();
            };
            Segments.event();
            manager.init();
        },
        event: function(){
            $('#segment-list').on('after_added', function(event, data) {
                $('input:radio[name$="Flex]"]').off("click");
                $('input:radio[name$="Flex]"]').on('click', function () {
                    Segments.initSliders(this, true, true);
                });
                Segments.processSegments(typeof(data['obj']) == 'undefined' || data.obj._restoreForm === false, null, true);
            })
            .on('removed', function(event, data) {
                $('input:radio[name$="Flex]"]').off("click");
                $('input:radio[name$="Flex]"]').on('click', function () {
                    Segments.initSliders(this, true, true);
                });
                Segments.processSegments(typeof(data['obj']) == 'undefined' || data.obj._restoreForm === false, null, false);
            });
            $("#segment-list fieldset:eq(0)").on('click', 'input[name$="[RoundTrip]"]', null, function(e) {
                Segments.processSegments(true, true);
            });
            $('input:radio[name$="Flex]"]').on('click', function () {
                Segments.initSliders(this, true, true);
            });
            $('input[name$="[RoundTripDaysIdeal]"]').on('change keyup', function () {
                if (/\D/g.test(this.value)) {
                    this.value = this.value.replace(/\D/g, '');
                }
                if(this.value > 3650) this.value = 3650;
                if(this.value != '' && this.value < 1) this.value = 1;

                Segments.initSliders(this);
            });
            AddForm.form
                .on('save_form', function(event, data) {
                    // save type of trip
                    data['minor']['TypeTrip'] = $('#segment-list').children(':first').find(':radio:checked').val();
                })
                .on('restore_form', function(event, data) {
                    $('input:radio[name$="Flex]"]').off("click");
                    if (typeof(data['minor']['TypeTrip']) != 'undefined') {
                        $('#segment-list').children(':first').find('[value="'+data['minor']['TypeTrip']+'"]:radio').find(':not([id*="Flex"])').prop('checked', true).trigger('click');
                    }
                    Segments.processSegments(true, false);
                })
                .on('post_restore', function(event, data) {
                    $('input:radio[name$="Flex]"]').on('click', function () {
                        Segments.initSliders(this, true, true);
                    }).each(function () {
                        Segments.initSliders(this, false, false);
                    });
                });
        },

        processSegments: function(fill, autoadd, newSegment) {
            var type = $('#segment-list').children(':first').find(':radio:checked').val(),
                isRound = (type == 1),
                isOneWay = (type == 2),
                number, element, title, prev = null,
                total = (isRound||isOneWay) ? 1 : $('#segment-list').children().length;
            if (typeof(fill) == 'undefined') fill = true;
            if (typeof(autoadd) == 'undefined') autoadd = false;
            $('#segment-list').children().each(function(idx, el){
                element = $(el);
                if ((isRound||isOneWay) && prev != null) {element.remove();return true;}
                number = parseInt(idx)+1;
                // title
                if (isRound || isOneWay)
                    title = Translator.trans('booking.request.add.form.segment.title_single', {}, 'booking');
                else
                    title = Translator.trans('booking.request.add.form.segment.title', {'number': number}, 'booking');
                element.find('legend h3').text(title);

                var e1 = element.find('[name$="[ReturnDateIdeal]"]').closest('.row'),
                    e2 = element.find('[name$="[RoundTrip]"]:first').closest('.trip-details'),
                    e3 = element.find('[name$="[ReturnDateFlex]"]').closest('.row'),
                    e4 = element.find('#ReturnDate_slider').closest('.row'),
                    e5 = element.find('.round-trip-days');
                if (isRound) {
                    e1.show();
                    e3.show();
                    e4.show();
                    e5.show();
                } else {
                    if (fill) {
                        e1.find('.date').datepicker("setDate", null).removeData('changed').trigger('change').end().hide()
                            .next().find('.date').datepicker("setDate", null).removeData('changed').trigger('change').end().hide();
                        e3.hide();
                        e4.hide();
                        e5.hide();
                    }
                }
                if (number == 1) e2.show();
                else e2.hide();

                // delete
                if (!isRound && !isOneWay && number > 1) {
                    element.find('.del-block').show();
                } else {
                    element.find('.del-block').hide();
                }

                if ((total-1) == idx) {
                    if (fill && prev != null && element.find('[name$="[Dep]"]').val() == "" && prev.find('[name$="[Arr]"]').val() != "")
                        element.find('[name$="[Dep]"]').val(prev.find('[name$="[Arr]"]').val()).trigger('change');
                    if (fill
                        && element.find('div.row:not(.error)').find('.date[name$="[DepDateIdeal]"]').val() == ""
                        && element.find('div.row:not(.error)').find('.date[name$="[DepDateFrom]"]').val() == ""
                        && element.find('div.row:not(.error)').find('.date[name$="[DepDateTo]"]').val() == "")
                    {
                        var depDateIdeal, prevDepDateIdeal;
                        if (prev == null) {
                            if (typeof(element.find('.date[name$="[DepDateIdeal]"]').data('changed')) == "undefined") {
                                depDateIdeal = new Date();
                                depDateIdeal.setDate(depDateIdeal.getDate() + 2);
                            } else
                                depDateIdeal = null;
                        } else {
                            prevDepDateIdeal = prev.find('.date[name$="[DepDateIdeal]"]').datepicker("getDate");
                            depDateIdeal = (prevDepDateIdeal) ? new Date(prevDepDateIdeal.getTime() + 7 * 3600*24*1000) : null;
                        }
                        if (depDateIdeal != null) {
                            element.find('.date[name$="[DepDateIdeal]"]').datepicker("setDate", depDateIdeal).trigger('change');
                            Segments.flexRange(element.find('.date[name$="[DepDateIdeal]"]'));
                        }
                    }
                    if (fill && isRound) {
                        var returnDateIdealElement = element.find('div.row:not(.error)').find('.date[name$="[ReturnDateIdeal]"]');
                        if (returnDateIdealElement && returnDateIdealElement.val() == "")
                        {
                            var returnDateIdeal,
                                prevDateIdeal = element.find('.date[name$="[DepDateIdeal]"]').datepicker("getDate");
                            if (prevDateIdeal) {
                                returnDateIdeal = new Date(prevDateIdeal.getTime() + 7 * 3600*24*1000);
                                returnDateIdealElement.datepicker("setDate", returnDateIdeal).trigger('change');
                                Segments.flexRange(returnDateIdealElement);
                            }
                        }
                    }
                }

                if(!Segments.initComplete){
                    Segments.initSliders(element.find('div.row').find('.date[name$="[DepDateIdeal]"]'));
                }
                else if (
                    (idx === ($('#segment-list').children().length - 1) && newSegment) || AddForm.editMode
                ) {
                    //new segment
                    Segments.initSliders(element.find('div.row').find('.date[name$="[DepDateIdeal]"]'), null, true);
                }else if(newSegment === undefined){
                    Segments.initSliders(element.find('div.row').find('.date[name$="[DepDateIdeal]"]'));
                }

                prev = element;
            });
            Segments.initComplete = true;
            // add
            title = Translator.trans('booking.request.add.form.segment.title', {'number': parseInt($('#segment-list').children().length)+1}, 'booking');
            if (!isRound && !isOneWay && total < 50) {
                $('#add-destination').closest('fieldset')
                    .find('legend h3').text(title)
                    .end()
                    .show();
            } else {
                $('#add-destination').closest('fieldset').hide();
            }
            if(fill)
                AddForm.form.trigger('form_change');
            if(autoadd) {
                if (type == 0 && total == 1) {
                    var errors = Segments.check(true);
                    if (errors.length == 0) {
                        $('#add-destination').trigger('click');
                    }
                }
            }
        },
        check: function(returnError) {
            var a,
                errors = [],
                type = $('#segment-list').children(':first').find(':radio:checked').val();
            if (typeof(returnError) == 'undefined') returnError = false;
            $("#segment-list").children().each(function(i, elem){
                var depFlex = $(elem).find('input:radio[name$="[DepDateFlex]"]:checked');
                var returnFlex = $('input:radio[name$="[ReturnDateFlex]"]:checked');
                var daysFlex = $('input:radio[name$="[RoundTripDaysFlex]"]:checked');
                var daysIdeal = +$('input[name$="[RoundTripDaysIdeal]"]').val();

                if(!+depFlex.val()){
                    $(elem).find('[name$="[DepDateFrom]"]').val('');
                    $(elem).find('[name$="[DepDateTo]"]').val('');
                }

                if(!+returnFlex.val()){
                    $(elem).find('[name$="[ReturnDateFrom]"]').val('');
                    $(elem).find('[name$="[ReturnDateTo]"]').val('');
                }

                if(!+daysFlex.val() || !daysIdeal){
                    $(elem).find('input[name$="[RoundTripDaysFrom]"]').val('');
                    $(elem).find('input[name$="[RoundTripDaysTo]"]').val('');
                }

                a = {
                    from: {
                        name: $.trim($(elem).find('[name$="[Dep]"]').next('label').contents(':not(span)').text()),
                        value: $.trim($(elem).find('[name$="[Dep]"]').val()),
                        id: $(elem).find('[name$="[Dep]"]').attr('id')
                    },
                    to: {
                        name: $.trim($(elem).find('[name$="[Arr]"]').next('label').contents(':not(span)').text()),
                        value: $.trim($(elem).find('[name$="[Arr]"]').val()),
                        id: $(elem).find('[name$="[Arr]"]').attr('id')
                    },
                    depIdeal: {
                        name: $.trim($(elem).find('[name$="[DepDateIdeal]"]').parent().next('label').contents(':not(span)').text()),
                        value: $.trim($(elem).find('[name$="[DepDateIdeal]"]').val()),
                        id: $(elem).find('[name$="[DepDateIdeal]"]').attr('id')
                    },
                    depFrom: {
                        name: $.trim($(elem).find('[name$="[DepDateFrom]"]').parent().next('label').contents(':not(span)').text()),
                        value: $.trim($(elem).find('[name$="[DepDateFrom]"]').val()),
                        id: $(elem).find('[name$="[DepDateFrom]"]').attr('id')
                    },
                    depTo: {
                        name: $.trim($(elem).find('[name$="[DepDateTo]"]').parent().next('label').contents(':not(span)').text()),
                        value: $.trim($(elem).find('[name$="[DepDateTo]"]').val()),
                        id: $(elem).find('[name$="[DepDateTo]"]').attr('id')
                    },
                    returnIdeal: {
                        name: $.trim($(elem).find('[name$="[ReturnDateIdeal]"]').parent().next('label').contents(':not(span)').text()),
                        value: $.trim($(elem).find('[name$="[ReturnDateIdeal]"]').val()),
                        id: $(elem).find('[name$="[ReturnDateIdeal]"]').attr('id')
                    },
                    returnFrom: {
                        name: $.trim($(elem).find('[name$="[ReturnDateFrom]"]').parent().next('label').contents(':not(span)').text()),
                        value: $.trim($(elem).find('[name$="[ReturnDateFrom]"]').val()),
                        id: $(elem).find('[name$="[ReturnDateFrom]"]').attr('id')
                    },
                    returnTo: {
                        name: $.trim($(elem).find('[name$="[ReturnDateTo]"]').parent().next('label').contents(':not(span)').text()),
                        value: $.trim($(elem).find('[name$="[ReturnDateTo]"]').val()),
                        id: $(elem).find('[name$="[ReturnDateTo]"]').attr('id')
                    }
                };
                $.each([ 'from', 'to' ], function( index, value ) {
                    if (a[value].value == "") {
                        errors.push({
                            text: Translator.trans('notblank', {}, 'validators'),
                            details: a[value]
                        });
                    }
                });
                if (a.depIdeal.value == "" && (a.depFrom.value == "" || a.depTo.value == "")) {
                    errors.push({
                        text: Translator.trans('notblank', {}, 'validators'),
                        details: a.depIdeal
                    });
                }
                if (i == 0 && type == 1) {
                    if (a.returnIdeal.value == "" && (a.returnFrom.value == "" || a.returnTo.value == "")) {
                        errors.push({
                            text: Translator.trans('notblank', {}, 'validators'),
                            details: a.returnIdeal
                        });
                    }
                }
                if(a.from.value.length < 3){
                    errors.push({
                        text: 'This value is too short. It should have 3 characters or more.',
                        details: a.from
                    });
                }
                if(a.to.value.length < 3){
                    errors.push({
                        text: 'This value is too short. It should have 3 characters or more.',
                        details: a.to
                    });
                }
                if(!depFlex.length){
                    errors.push({
                        text: Translator.trans(/** @Desc("You must choose if you have date flexibility") */ 'booking.date_flexibility', {}, 'validators'),
                        details: {
                            name: $.trim($(elem).find('[name$="[DepDateFlex]"]').parent().next('label').contents(':not(span)').text()),
                            value: $.trim($(elem).find('[name$="[DepDateFlex]"]').val()),
                            id: $(elem).find('[name$="[DepDateFlex]"]').attr('id')
                        }
                    });
                }
                if(!returnFlex.length && type == 1){
                    errors.push({
                        text: Translator.trans('booking.date_flexibility', {}, 'validators'),
                        details: {
                            name: $.trim($(elem).find('[name$="[ReturnDateFlex]"]').parent().next('label').contents(':not(span)').text()),
                            value: $.trim($(elem).find('[name$="[ReturnDateFlex]"]').val()),
                            id: $(elem).find('[name$="[ReturnDateFlex]"]').attr('id')
                        }
                    });
                }
                if(!daysFlex.length && type == 1 && daysIdeal){
                    errors.push({
                        text: Translator.trans('booking.date_flexibility', {}, 'validators'),
                        details: {
                            name: $.trim($(elem).find('[name$="[RoundTripDaysFlex]"]').parent().next('label').contents(':not(span)').text()),
                            value: $.trim($(elem).find('[name$="[RoundTripDaysFlex]"]').val()),
                            id: $(elem).find('[name$="[RoundTripDaysFlex]"]').attr('id')
                        }
                    });
                }
            });
            if (returnError)
                return errors;
            if (errors.length === 0) return true;
            var firstError = null;
            $.each(errors, function( index, value ) {
                $('#'+value.details.id).closest('div.row').addClass('error required').find('div.message').text(value.text).show();
                if (firstError == null)
                    firstError = value;
            });
            AddForm.activateElementTab($('#'+firstError.details.id));
            formValidator.focus($('#'+firstError.details.id).closest('div.row').find('input'), 250);
            return false;
        },
        dateIdealChanged: function(val, elem) {
            Segments.flexRange($(this));
            $(this).trigger('change');
        },
        dateFromChanged: function(val, elem) {
            $(this).data('changed', true).trigger('change');
        },
        dateToChanged: function(val, elem) {
            $(this).data('changed', true).trigger('change');
        },
        flexRange: function(field) {
            var rangeRow = field.closest('.row').next(),
                e = rangeRow.find('.date:eq(0)'),
                l = rangeRow.find('.date:eq(1)'),
                date = field.datepicker('getDate');
            if (!date)
                return;
            if (typeof(e.data('changed')) != "undefined" || typeof(l.data('changed')) != "undefined")
                return;

            e.datepicker("setDate", new Date(date.getTime())).trigger('change');
            l.datepicker("setDate", new Date(date.getTime())).trigger('change');

            Segments.initSliders(field);
        },
        initComplete: false,
        initSliders: function (field, keepValues, newSegment) {
            console.log('init sliders');
            var element = $(field).closest('fieldset');
            var isRound = $('#segment-list').children(':first').find(':radio:checked').val() == 1;
            var changedFieldName = $(field).attr('name').match(/\[([A-Z][a-z]+)\w+]$/)[1];
            var fieldNames = ['Dep', 'Return'];

            if (!Segments.initComplete) {
                fieldNames = ['Dep', 'Return'];
            } else if ($(field).filter('input[name*="[Return"]').length) {
                fieldNames = ['Return'];
            } else if ($(field).filter('input[name*="[Dep"]').length) {
                fieldNames = ['Dep'];
            } else if($(field).filter('input[name*="RoundTripDays"]').length) {
                fieldNames = [];
            }

            fieldNames.forEach(function (name) {
                var slider = $(element).find('#'+name+'Date_slider');
                var dateIdeal = $(element).find('.date[name$="['+name+'DateIdeal]"]').datepicker("getDate");
                var dateFlex = +$(element).find('input:radio[name$="['+name+'DateFlex]"]:checked').val();

                if (!dateIdeal) {
                    dateIdeal = new Date();
                }

                var dateFrom = $(element).find('.date[name$="['+name+'DateFrom]"]');
                var dateTo = $(element).find('.date[name$="['+name+'DateTo]"]');

                var sliderRangeMin = new Date(dateIdeal.getTime() - 1 * 3600 * 24 * 1000);
                var sliderRangeMax = new Date(dateIdeal.getTime() + 1 * 3600 * 24 * 1000);
                var sliderStart = dateFrom.datepicker('getDate') || new Date(dateIdeal.getTime());
                var sliderEnd = dateTo.datepicker('getDate') || new Date(dateIdeal.getTime());

                if (newSegment && !AddForm.editMode) {
                    sliderEnd = new Date(dateIdeal.getTime());
                }

                if (slider[0].noUiSlider) {
                    if (keepValues || changedFieldName + 'Date_slider' !== slider.attr('id')) {
                        var values = slider[0].noUiSlider.get();
                        sliderStart = new Date(+values[0]);
                        sliderEnd = new Date(+values[1]);
                    }
                    slider[0].noUiSlider.off();
                    slider[0].noUiSlider.destroy();
                }

                if (sliderStart.getTime() === sliderEnd.getTime()) {
                    sliderStart = new Date(sliderRangeMin);
                    sliderEnd = new Date(sliderRangeMax);
                }

                noUiSlider.create(slider[0], {
                    start: [
                        sliderStart.getTime(),
                        sliderEnd.getTime()
                    ],
                    connect: true,
                    range: {
                        min: sliderRangeMin.getTime(),
                        max: sliderRangeMax.getTime()
                    },
                    step: 3600 * 24 * 1000,
                    margin: 3600 * 24 * 1000,
                    animate: true,
                    pips: {
                        mode: 'count',
                        values: 3,
                        stepped: true,
                        format: {
                            to: function (timestamp) {
                                return $.datepicker.formatDate("D", new Date(+timestamp));
                            },
                            from: function () {
                                return '';
                            }
                        }
                    }
                });

                var leftHandle = $(slider).find('.noUi-handle-lower');
                var rightHandle = $(slider).find('.noUi-handle-upper');

                if (!dateFlex || (!isRound && $(slider).attr('id') !== 'DepDate_slider')) {
                    slider[0].setAttribute('disabled', 'disabled');
                    $(slider).closest('.row').slideUp();
                } else {
                    slider[0].removeAttribute('disabled');
                    $(slider).closest('.row').slideDown();
                }

                slider[0].noUiSlider.on('end', function() {
                    var values = slider[0].noUiSlider.get();

                    if (+values[0] > dateIdeal.getTime()) {
                        values[0] = dateIdeal.getTime();
                    }

                    if (+values[1] < dateIdeal.getTime()) {
                        values[1] = dateIdeal.getTime();
                    }

                    slider[0].noUiSlider.set(values);
                });

                slider[0].noUiSlider.on('update', function(){
                    var values = slider[0].noUiSlider.get();
                    var earliest = new Date(+values[0]);
                    var latest = new Date(+values[1]);

                    var earliestFormatted = $.datepicker.formatDate("DD,<br/> mm/dd/yy", earliest);
                    var latestFormatted = $.datepicker.formatDate("DD,<br/> mm/dd/yy", latest);

                    leftHandle.html('<span>'+ earliestFormatted + '</span>');
                    rightHandle.html('<span>'+ latestFormatted + '</span>');
                    dateFrom.datepicker("setDate", earliest).trigger('change');
                    dateTo.datepicker("setDate", latest).trigger('change');
                });
            });

            var daysSlider = $(element).find('#RoundTripDays_slider');
            var daysIdeal = +$(element).find('input[name$="[RoundTripDaysIdeal]"]').val();
            var daysFlex = +$('input:radio[name$="[RoundTripDaysFlex]"]:checked').val();

            if (!daysIdeal) {
                $('.round-trip-days.has-slider').slideUp();
            } else {
                $('.round-trip-days.has-slider').slideDown();
            }

            var sliderStart = daysIdeal;
            var sliderEnd = daysIdeal;

            if (changedFieldName !== 'Round') {
                sliderStart = +$(element).find('input[name$="[RoundTripDaysFrom]"]').val() || daysIdeal;
                sliderEnd = +$(element).find('input[name$="[RoundTripDaysTo]"]').val() || daysIdeal;
            }

            if (daysSlider[0].noUiSlider) {
                if(keepValues || changedFieldName + 'TripDays_slider' !== daysSlider.attr('id')){
                    var values = daysSlider[0].noUiSlider.get();
                    sliderStart = +values[0];
                    sliderEnd = +values[1];
                }
                daysSlider[0].noUiSlider.destroy();
            }

            noUiSlider.create(daysSlider[0], {
                start: [
                    sliderStart,
                    sliderEnd
                ],
                connect: true,
                range: {
                    min: daysIdeal > 10 ? daysIdeal - 10 : 1,
                    max: daysIdeal + 10
                },
                step: 1,
                margin: 0,
                animate: true,
                pips: {
                    mode: 'count',
                    values: daysIdeal > 10 ? 22 : daysIdeal + 11,
                    density: 4,
                    stepped: true,
                    format: {
                        to: function () {
                            return '';
                        },
                        from: function () {
                            return '';
                        }
                    }
                }
            });

            var leftHandle = $(daysSlider).find('.noUi-handle-lower');
            var rightHandle = $(daysSlider).find('.noUi-handle-upper');

            daysSlider[0].noUiSlider.on('update', function(){
                var values = daysSlider[0].noUiSlider.get();
                var sliderInfo = $(element).find('.slider-info');

                if(values[0] === values[1]){
                    leftHandle.html('<span style="right: -60px;text-align: center;">Number of days,<br/> ' + parseInt(values[0]) + '</span>');
                    rightHandle.html('');
                    sliderInfo.css("visibility","hidden");
                }else {
                    leftHandle.html('<span>' + parseInt(values[0]) + '</span>');
                    rightHandle.html('<span>' + parseInt(values[1]) + '</span>');
                    sliderInfo.css("visibility","visible");
                }

                $(element).find('input[name$="[RoundTripDaysFrom]"]').val(+values[0]);
                $(element).find('input[name$="[RoundTripDaysTo]"]').val(+values[1]);
            });

            daysSlider[0].noUiSlider.on('end', function(){
                var values = daysSlider[0].noUiSlider.get();

                if(+values[0] > daysIdeal)
                    values[0] = daysIdeal;

                if(+values[1] < daysIdeal)
                    values[1] = daysIdeal;

                daysSlider[0].noUiSlider.set(values);
            });

            if(!daysFlex || !isRound || !daysIdeal){
                daysSlider[0].setAttribute('disabled', 'disabled');
                $(daysSlider).closest('.row').slideUp();
                if(!isRound) $('.round-trip-days.has-slider').hide();
            }else{
                daysSlider[0].removeAttribute('disabled');
                $(daysSlider).closest('.row').slideDown();
            }
        }
    };
})(Translator, formValidator);