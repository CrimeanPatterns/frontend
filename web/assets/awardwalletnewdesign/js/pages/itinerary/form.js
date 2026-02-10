define([
    'jquery-boot',
    'lib/customizer'
], function($, customizer) {

    var $owner = $('select[name$="[owner]"]');
    if ($owner.length) {
        $owner.change(function() {
            $(window).trigger('person.activate', $(this).val());
            let agentId = null;
            if (-1 === $(this).val().indexOf('_')) {
                let $person = $('.js-persons-menu').find('a[data-agentid="' + $(this).val() + '"]');
                1 === $person.length ? agentId = $person.data('id') : null;
            } else {
                agentId = $(this).val().split('_')[1] || false;
            }
            if (agentId) {
                let action = $owner.closest('form').attr('action');
                'undefined' === typeof action ? action = location.pathname + location.search : null;
                if (-1 === action.indexOf('?')) {
                    action += '?agentId=' + agentId;
                } else if (-1 !== action.indexOf('?agentId=')) {
                    action = action.replace(/\?agentId\=\d+/g, '?agentId=' + agentId);
                } else if (-1 === action.indexOf('&agentId=')) {
                    action += '&agentId=' + agentId;
                } else if (-1 !== action.indexOf('&agentId=')) {
                    action = action.replace(/\&agentId\=\d+/g, '&agentId=' + agentId);
                }
                $owner.closest('form').attr('action', action);
            }
        });
    }

    function getLocale() {
        return $('a[data-target="select-language"]').attr('data-region') || 'en';
    }

    function isHour12(locale) {
        return locale === 'en' || locale === 'us';
    }

    function parseDaytime(time) {
        const s = /(\d+):(\d+)(?:\s+(am|pm))?/i.exec(time);

        if (!s) {
            return null;
        }

        let [, hours, minutes, mode] = s;

        hours = parseInt(hours);
        minutes = parseInt(minutes);
        if (typeof mode !== 'undefined' && mode.toLowerCase() === 'pm' && hours !== 12) {
            hours += 12;
        }

        return [hours, minutes];
    }

    function getSegmentContainer(segmentSelector) {
        let container = segmentSelector.closest('.segment');

        if (!container.length) {
            container = segmentSelector.closest('form');
        }

        return container;
    }

    function timeRow(dateElem) {
        return dateElem.closest('.row').next();
    }

    function timeInput(timeRow) {
        return timeRow.find('input');
    }

    function getSegmentDateTime(dateElem) {
        const date = dateElem.datepicker('getDate');
        const time = parseDaytime(
            timeInput(
                timeRow(dateElem)
            ).val()
        );

        if (time) {
            if (time[0] > 0) {
                date.setHours(time[0]);
            }
            if (time[1] > 0) {
                date.setMinutes(time[1]);
            }
        }

        return date;
    }

    function getStartDateElem(segmentSelector) {
        return getSegmentContainer(segmentSelector)
            .find('input[id*="_departureDate_date_datepicker"], input[id*="_pickUpDate_date_datepicker"], input[id*="_startDate_date_datepicker"], input[id*="_checkInDate_date_datepicker"]').first();
    }

    function getEndDateElem(segmentSelector) {
        return getSegmentContainer(segmentSelector)
            .find('input[id*="_arrivalDate_date_datepicker"], input[id*="_dropOffDate_date_datepicker"], input[id*="_endDate_date_datepicker"], input[id*="_checkOutDate_date_datepicker"]').first();
    }

    function getStartSegmentDate(segmentSelector) {
        return getSegmentDateTime(getStartDateElem(segmentSelector));
    }

    function getEndSegmentDate(segmentSelector) {
        return getSegmentDateTime(getEndDateElem(segmentSelector));
    }

    function isStartSegmentDate(dateElem) {
        return dateElem.attr('id').match(/departureDate|pickUpDate|startDate|checkInDate/);
    }
    
    return function(options) {
        options || (options = {});

        var _this      = this,
            initialized = false,
            $container = $(options.formContainer || (options.formContainer = document)),
            isCruise = $container.attr('id') === 'cruise',
            $blocks;

        this.init = function() {
            _this.initSubmit();
            if (!initialized){
                _this.control();
                initialized = true;
            }
            if (options.block) {
                $blocks = $(options.block);
                this.initBlock();
            }
            $('body').trigger('init_fields');
            _this.initDatepickers();
            _this.initAutocomplete();

            return this;
        };

        this.initBlock = function() {
            if ($blocks.length) {
                $blocks.each(function(indx, el) {
                    var $block = $(el);
                    $block.find('.num').text(indx + 1);

                    if (options.blockMapper) {
                        for (var i in options.blockMapper){
                            options.blockMapper[i].mapper($block, indx);
                        }
                    }

                });
                $('.js-block-del', $container)[1 === $blocks.length ? 'hide' : 'show']();

                if ('undefined' !== typeof customizer)
                    customizer.initDatepickers();

            } else {
                $('.js-block-add', $container).trigger('click');
            }

            return this;
        };

        this.control = function() {
            if (options.block) {
                $container
                    .off('click.addBlock', '.js-block-add')
                    .off('click.delBlock', '.js-block-del')
                    .on('click.addBlock', '.js-block-add', function(e) {
                        console.log('add');
                        e.preventDefault();
                        var $tmpl = $(options.template);
                        if (!$tmpl.length)
                            return null;

                        var countIndx = $tmpl.data('indx')|0,
                            count     = 1 + $(options.block).length + countIndx,
                            $box      = $($tmpl.text().replace(/__name__/g, count)).hide();
                        
                        $(options.blockContainer).append($box);
                        $box.slideDown();
                        _this.init();
                        $tmpl.data('indx', 1 + countIndx);
                        _this.newSegmentCopy();
                    })
                    .on('click.delBlock', '.js-block-del', function(e) {
                        e.preventDefault();
                        $(e.target).parents(options.block).slideUp(function() {
                            $(this).remove();
                            _this.init();
                        });
                    });
            }

            return this;
        };
        
        this.dateTimeError = function() {
            $('div.row-time', $container).each(function() {
                var $rowTime = $(this);
                $rowTime.find('.error-message-description').css('padding-right', 10);
                $rowTime.hasClass('error') && $rowTime.is(':visible') ? $rowTime.removeClass('error').parent().closest('.row').addClass('error') : null;
            });
            $('div.row-date', $container).each(function() {
                var $rowDate = $(this), $rowTime = $(this).parent().closest('.row.error').find('.row.row-time');
                if ($rowDate.hasClass('error')) {
                    $rowDate.find('.error-message-description').css('padding-right', 0);
                    $rowDate.removeClass('error').parent().closest('.row').addClass('error');
                    $rowTime.find('.error-message-description').is(':visible') ? $rowDate.find('.info .info-description').html('&nbsp;') : $rowDate.find('.info .info-description').empty();
                }
            });

            return this;
        };

        this.validateTimezones = function () {
            var segments = $(options.blockContainer).find(options.block);
            var isValid = true;
            var errors = [];

            segments.each(function () {
                var timezones = $(this).find('.address-timezone');
                timezones.each(function () {
                    if ($(this).text()) return;

                    isValid = false;
                    errors.push(this);
                    $(this)
                        .closest('.row')
                        .addClass('error')
                        .find('.error-message')
                        .show()
                        .find('.error-message-description')
                        .text('Invalid address');
                })
            });

            if(errors.length)
                $('html, body').animate({
                    scrollTop: $(errors[0]).closest('.row').offset().top - 50
                }, 500);

            return isValid;
        };

        this.initAutocomplete = function () {
            console.log('init autocomplete');
            if(options.companyField){
                $('input[id*="_'+options.companyField.id+'"]:not(.ui-autocomplete-input)')
                    .off('keydown')
                    .on('keydown', function (e) {
                        if (
                            !$.trim($(e.target).val()) &&
                            (e.keyCode === 0 || e.keyCode === 32)
                        ) e.preventDefault();
                    })
                    .autocomplete({
                        delay: 500,
                        minLength: 2,
                        source: function (request, response) {
                            var self = this;
                            $.get(Routing.generate("aw_coupon_json_progs", {query: request.term}), function (data) {
                                $(self.element).removeClass('loading-input');
                                response(
                                    data
                                        .filter(function (item) {return +item.kind === options.companyField.kind})
                                        .map(function (item) {return {label: item.name, value: item.name};})
                                );
                            })
                        },
                        select: function (event, ui) {
                            event.preventDefault();
                            $(event.target).val(ui.item.label);
                        },
                        search: function (event, ui) {
                            if ($(event.target).val().length >= 2)
                                $(event.target).addClass('loading-input');
                            else
                                $(event.target).removeClass('loading-input');
                        },
                        open: function (event, ui) {
                            $(event.target).removeClass('loading-input')
                        },
                        create: function () {
                            $(this).data('ui-autocomplete')._renderItem = function (ul, item) {
                                var regex = new RegExp("(" + this.element.val() + ")", "gi");
                                var itemLabel = item.label.replace(regex, "<b>$1</b>");
                                return $('<li></li>')
                                    .data("item.autocomplete", item)
                                    .append($('<a></a>').html(itemLabel))
                                    .appendTo(ul);
                            };
                        }
                    });
            }

            if(options.autocompleteField) {

                $('input[id$="'+ options.autocompleteField + '"]:not(.ui-autocomplete-input)')
                    .each(function () {
                        if($(this).val())
                            $(this).data('fromPlacesApi', true);
                    })
                    .off('keydown')
                    .off('change')
                    .on('keydown', function (e) {
                        if (
                            !$.trim($(e.target).val()) &&
                            (e.keyCode === 0 || e.keyCode === 32)
                        )
                            e.preventDefault();
                        else
                            $(e.target).data('fromPlacesApi', false);
                    })
                    .autocomplete({
                        delay: 500,
                        minLength: 2,
                        search: function (event, ui) {
                            if ($(event.target).val().length >= 2)
                                $(event.target).addClass('loading-input');
                            else
                                $(event.target).removeClass('loading-input');
                        },
                        open: function (event, ui) {
                            $(event.target).removeClass('loading-input')
                        },
                        source: function (request, response) {
                            var self = this;
                            $(this).closest('.input-item').find('.address-timezone').text('');
                            $
                                .get(Routing.generate(options.autocompleteRoute || "google_geo_code", {
                                    query: encodeURIComponent(request.term)
                                }))
                                .done(function (data) {
                                    $(self.element).removeClass('loading-input');
                                    if(!data) return;
                                    response(data.map(function (item) {
                                        return {label: item.formatted_address, value: item.formatted_address, place_id:item.place_id};
                                    }));
                                })
                                .fail(function () {
                                    return [];
                                });
                        },
                        select: function (event, ui) {
                            event.preventDefault();
                            var input = $(event.target);
                            input.data('fromPlacesApi', true);

                            input
                                .val(ui.item.label)
                                .trigger('change');
                        },
                        create: function () {
                            $(this).data('ui-autocomplete')._renderItem = function (ul, item) {
                                var regex = new RegExp("(" + this.element.val() + ")", "gi");
                                var itemLabel = item.label.replace(regex, "<b>$1</b>");
                                return $('<li></li>')
                                    .data("item.autocomplete", item)
                                    .append($('<a></a>').html(itemLabel))
                                    .appendTo(ul);
                            };
                        }
                    })
                    .on('change', function (e) {
                        var submitBtn = $('button[type="submit"]');
                        submitBtn.attr('disabled', 'disabled');
                        var address = $.trim($(e.target).val());
                        var timezone = $(e.target).closest('.input-item').find('.address-timezone');
                        timezone.html('').hide();
                        if(!address || address.length < 4){
                            submitBtn.removeAttr('disabled');
                            $(e.target)
                                .closest('.row')
                                .addClass('error')
                                .find('.error-message')
                                .show()
                                .find('.error-message-description')
                                .text('Invalid address');
                            return;
                        }
                        $(e.target).addClass('loading-input');
                        $
                            .get(Routing.generate("geo_coder_query", {
                                query: encodeURIComponent(address)
                            }))
                            .done(function (data) {
                                var response = JSON.parse(data);
                                $(e.target).removeClass('loading-input');
                                submitBtn.removeAttr('disabled');
                                if(!response) return;
                                if(!$(e.target).data('fromPlacesApi')){
                                    $(e.target).val(response.foundaddress);
                                }

                                let text;
                                if (/^\+/.test(response.timeZoneAbbreviation)) {
                                    text = 'UTC' + response.timeZoneAbbreviation.toUpperCase();
                                } else {
                                    text = response.timeZoneAbbreviation.toUpperCase();
                                }
                                timezone
                                    .text(text)
                                    .show();
                            })
                            .fail(function () {
                                $(e.target).removeClass('loading-input');
                                submitBtn.removeAttr('disabled');
                            })
                    })
                    .trigger('change');
            }
        };

        this.initSubmit = function () {
            if(options.formContainer === '#flight')
                return;
            $('input[type="submit"]', options.formContainer)
                .off('click')
                .on('click', function(e) {
                    e.preventDefault();

                    if(!_this.validateTimezones()){
                        customizer.showErrors($(options.formContainer));
                        return;
                    }

                    if ($('.notes-error', '#notesEditor').length) {
                        $('html, body').animate({scrollTop: $('.notes-error', '#notesEditor').offset().top}, 500);
                        return false;
                    }

                    $(e.target).addClass('loader');
                    $(options.formContainer).submit();
                    console.log('submit')
                });
            $(options.formContainer).submit(function(){
                _this.dateTimeError();
            });
        };

        this.initDatepickers = function () {
            $('input[id*="_date_datepicker"]')
                .off('change blur')
                .on('change', function (e) {
                    const target = $(e.target);
                    if (!target.val()) return;

                    const isStart = isStartSegmentDate(target);
                    const locale = getLocale();
                    const hour12 = isHour12(locale);
                    const tr = timeRow(target);
                    const ti = timeInput(tr);
                    const startDate = getStartSegmentDate(target);
                    const endDate = getEndSegmentDate(target);
                    let time;

                    if (!$.trim(ti.val())) {
                        const date = isStart ? startDate : endDate;
                        if (isCruise) {
                            if (isStart) {
                                date.setHours(16);
                                date.setMinutes(0);
                                time = hour12 ? '4:00 pm' : '16:00';
                            } else {
                                date.setHours(7);
                                date.setMinutes(0);
                                time = hour12 ? '7:00 am' : '7:00';
                            }
                        } else {
                            date.setHours(11);
                            date.setMinutes(0);
                            time = hour12 ? '11:00 am' : '11:00';
                        }

                        if (!startDate || !endDate || startDate <= endDate) {
                            ti.val(time);
                        }
                    }

                    tr.show();
                    _this.dateTimeError();

                    if (isStart){
                        console.log('set arrival date');
                        const endDateElem = getEndDateElem(target);

                        endDateElem.datepicker("option", { defaultDate: startDate });
                        if (endDateElem.closest('form').attr('name') === 'taxi_ride') {
                            endDateElem.datepicker("setDate", startDate).trigger('change');
                        }
                    }
                });
        };

        this.newSegmentCopy = function () {
            var $segment = $(options.blockContainer).find(options.block).last();
            var locale = getLocale();
            var hour12 = isHour12(locale);
            console.log('new segment copy');
            console.log($segment);
            _this.initAutocomplete();

            var $prevSegment = $segment.prev();

            var fieldsToCopy = [
                ['input[id$="_carrier"]', 'input[id$="_carrier"]'],
                ['input[id$="_cruiseShip"]', 'input[id$="_cruiseShip"]'],
                ['input[id$="_departure'+options.autocompleteField+'"]', 'input[id$="_arrival'+options.autocompleteField+'"]'],
                ['input[id$="_departureStationCode"]', 'input[id$="_arrivalStationCode"]']
            ];

            fieldsToCopy.forEach(function (selectors) {
                var input = $segment.find(selectors[0]);

                if ($prevSegment.find(selectors[1]).data('fromPlacesApi')) {
                    input.data('fromPlacesApi', true);
                }

                input
                    .val($prevSegment.find(selectors[1]).val())
                    .trigger('change');
            });

            const endDate = getEndSegmentDate($prevSegment);

            if (endDate instanceof Date) {
                const newStartDate = new Date(endDate.getTime());
                let time;

                if (isCruise) {
                    newStartDate.setHours(16);
                    newStartDate.setMinutes(0);
                    if (newStartDate < endDate) {
                        newStartDate.setDate(newStartDate.getDate() + 1);
                    }
                    time = hour12 ? '4:00 pm' : '16:00';
                } else {
                    time = $prevSegment.find('input[id$="_arrivalDate_time"]').val();
                }

                setTimeout(function () {
                    $segment
                        .find('input[id$="_date_datepicker"]')
                        .each(function () {
                            $(this).datepicker("option", {defaultDate: newStartDate});
                            if ($(this).attr('id').match(/departure/)) {
                                $(this).datepicker("setDate", newStartDate).trigger('change');
                                $(this).closest('#row-departureDate').find("input[id$='_time']").val(time);
                            }
                        });
                }.bind(this), 100);
            }
        }
    };
});