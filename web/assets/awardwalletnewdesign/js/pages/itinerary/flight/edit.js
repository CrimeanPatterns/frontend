define([
    'jquery-boot',
    'lib/customizer',
    'pages/itinerary/form',
    'routing'
], function($, customizer, ItineraryForm) {
    $(document).ready(function() {

        var options = {
            formContainer  : '#flight',
            template       : '#segmentTemplate',
            blockContainer : '#addedSegments',
            block          : '.segment',
            blockMapper    : [{
                'name'   : 'retriever',
                'mapper' : function($segment) {
                    $segment.data('retriever', function() {
                        var deferred    = $.Deferred(),
                            fieldsNames = ['airlineName', 'flightNumber', 'departureAirport', 'departureDate_date', 'departureDate_time', 'arrivalAirport', 'arrivalDate_date', 'arrivalDate_time'],
                            fields      = [],
                            data        = {};

                        fieldsNames.forEach(function(name, id) {
                            var field = $segment.find('input[id*=' + name + ']:not([id*=datepicker])');
                            fields.push({field : field, name : name});
                            data[name] = field.val();
                        });

                        $.ajax({
                            global : false,
                            method : 'GET',
                            url    : Routing.generate('flight_fill'),
                            data   : data
                        })
                            .done(function(response) {
                                $segment.data('resolved', true);
                                fields.forEach(function(el) {
                                    var value = response[el.name];
                                    if (['departureDate_date', 'arrivalDate_date'].indexOf(el.name) !== -1) {
                                        var flightDate = new Date(response[el.name]);
                                        el.field.closest('.row-date').find('input[datepicker]').datepicker('setDate', new Date(flightDate.getTime() + flightDate.getTimezoneOffset() * 60000));
                                    } else {
                                        el.field.val(value);
                                    }

                                    if (['departureAirport', 'arrivalAirport'].indexOf(el.name) !== -1 && response[el.name + 'Name']) {
                                        el.field.closest('.row').find('div.airport').text(response[el.name + 'Name']);
                                    }

                                    if (['departureDate_time', 'arrivalDate_time'].indexOf(el.name) !== -1) {
                                        el.field.closest('.time').show()
                                    }
                                });

                                $segment.find('.arrival-block').slideDown();
                                if ($segment.find('.error-manually').is(':visible')) {
                                    $segment.find('.error-manually').slideUp();
                                    $segment.find('.send-segment').slideDown();
                                }

                                deferred.resolve();
                            })
                            .fail(function(response) {
                                switch (response.status) {
                                    case 400:
                                        if (response.responseJSON['airlineName']) {
                                            $segment.find('.send-segment').slideUp();
                                            $segment.find('.error-manually').slideDown();
                                            $segment.find('.arrival-block').slideDown();
                                            $segment.find('.time').show();
                                            break;
                                        }
                                        fields.forEach(function(el) {
                                            if (response.responseJSON[el.name]) {
                                                var errorText = response.responseJSON[el.name];
                                                el.field.closest('.row').addClass('error').find('.error-message-description').text(errorText).closest('.error-message').show();
                                            }
                                        });
                                        break;

                                    case 404:
                                        $segment.find('.send-segment').slideUp();
                                        $segment.find('.error-manually').slideDown();
                                        $segment.find('.arrival-block').slideDown();
                                        $segment.find('.time').show();
                                        break;

                                    case 429:
                                        $segment.find('.send-segment').slideUp();
                                        $segment.find('.error-manually').slideUp();
                                        $segment.find('.error-fatal').slideDown();
                                        $segment.find('.arrival-block').slideDown();
                                        $segment.find('.time').show();
                                        break;
                                }

                                deferred.reject();
                            })
                            .always(function() {
                                $segment.find('button').removeClass('loader');
                                $segment.find('.loader').hide();
                                itinerary.dateTimeError();

                                // unmark error row after autofill retrieve btn
                                $('.row-time', options.formContainer).each(function() {
                                    var $rowDateTime = $(this).parent().closest('.row');
                                    if ($rowDateTime.hasClass('error')) {
                                        if (0 !== $('.row-time input', $rowDateTime).val().length && 0 !== $('.row-date input', $rowDateTime).val().length)
                                            $rowDateTime.removeClass('error').find('.error-message').hide();
                                        if (0 !== $('.row-date input', $rowDateTime).val().length)
                                            $('.row-date .error-message', $rowDateTime).hide();
                                        if (0 !== $('.row-time input', $rowDateTime).val().length)
                                            $('.row-time .error-message', $rowDateTime).hide();
                                    }
                                });
                                $('.row-arrivalAirport.error', options.formContainer).each(function() {
                                    if (0 !== $('input[name$="[arrivalAirport]"]', $(this)).val().length) {
                                        $(this).removeClass('error');
                                        $('.error-message', $(this)).hide();
                                    }
                                });
                            });

                        return deferred.promise();
                    });
                }
            }]
        };

        var itinerary = new ItineraryForm(options);

        $('body').on('init_fields', function (e) {
            $('input[id*="_airlineName"]:not(.ui-autocomplete-input)')
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
                        $.get(Routing.generate("find_airline", {query: request.term}), function (data) {
                            $(self.element).removeClass('loading-input');
                            response(data.map(function (item) {
                                return {label: item.name, value: item.code || ''};
                            }));
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
                            var itemValue = item.value.replace(regex, "<b>$1</b>");
                            return $('<li></li>')
                                .data("item.autocomplete", item)
                                .append($('<a></a>').html(itemLabel + ", " + itemValue))
                                .appendTo(ul);
                        };
                    }
                });

            $('input[id*="Airport"]:not(.ui-autocomplete-input)')
                .off('keydown keyup change')
                .on('keydown', function (e) {
                    if (
                        !$.trim($(e.target).val()) &&
                        (e.keyCode === 0 || e.keyCode === 32)
                    ) e.preventDefault();
                })
                .on('keyup change', function (e) {
                    if ($.trim($(e.target).val()).length < 3)
                        $('.input-text.airport', $(e.target).parent()).html('');
                })
                .autocomplete({
                    delay: 0,
                    minLength: 3,
                    source: function (request, response) {
                        if (request.term && request.term.length >= 3) {
                            var self = this;
                            $.get(Routing.generate("find_airport", {query: request.term}), function (data) {
                                $(self.element).removeClass('loading-input');
                                $(self.element).nextAll('.airport').text("");
                                $(self.element).data('list', data);
                                response(data.map(function (item) {
                                    return {label: item.airname, value: item.aircode, city: item.cityname, country: item.countryname};
                                }));
                            })
                        }
                    },
                    search: function (event, ui) {
                        if ($(event.target).val().length >= 3)
                            $(event.target).addClass('loading-input');
                        else
                            $(event.target).removeClass('loading-input');
                        $(event.target).nextAll('input').val("");
                    },
                    open: function (event, ui) {
                        $(event.target).removeClass('loading-input');
                        var aircode = $(event.target).val(), list = $(event.target).data('list');
                        if (3 === aircode.length && 'undefined' != typeof list) {
                            for (var i in list) {
                                if (list[i].aircode.toUpperCase() === aircode.toUpperCase())
                                    $(event.target).parent().find('.input-text.airport').text(list[i].airname);
                            }
                        }
                    },
                    create: function () {
                        $(this).data('ui-autocomplete')._renderItem = function (ul, item) {
                            console.log(item);
                            var regex = new RegExp("(" + this.element.val() + ")", "gi");
                            var itemLabel = item.label.replace(regex, "<b>$1</b>");
                            var city = item.city.replace(regex, "<b>$1</b>");
                            var itemValue = item.value.replace(regex, "<b>$1</b>");
                            var html = '<span class="silver">' + itemValue + '</span>' + itemLabel + '<span>' + city + ', ' + item.country + '</span>';
                            return $('<li></li>')
                                .data("item.autocomplete", item)
                                .append($('<a class="address-location"></a>').html(html))
                                .appendTo(ul);
                        };
                    },
                    select: function(event, ui) {
                        $(event.target).parent().find('.input-text.airport').text(ui.item.label);
                    }
                })
        });

        $(options.formContainer)
            .on('click', '.retrieve-btn', function(e) {
                e.preventDefault();
                if ('BUTTON' === e.target.nodeName) {
                    $(e.target).addClass('loader');
                } else {
                    $(e.target).closest(options.block).find('.loader').show();
                }

                $(e.target).closest(options.block).data('retriever')()
                    .then(function() {
                        console.log('retrieve done')
                    })
            });

        $('input[type="submit"]', options.formContainer)
            .off('click')
            .on('click', function(e) {
                e.preventDefault();

                if ($('.notes-error', '#notesEditor').length) {
                    $('html, body').animate({scrollTop: $('.notes-error', '#notesEditor').offset().top}, 500);
                    return false;
                }

                $(e.target).addClass('loader');
                $(e.target).attr('disabled', 'disabled');
                var resolvers = [];
                $(options.block).each(function(id, el) {
                    if (true != $(el).data('resolved'))
                        resolvers.push($(el).data('retriever')());
                });

                $.when.apply(this, resolvers)
                    .then(function() {
                        console.log('submit');
                    })
                    .fail(function() {
                        console.log('error');
                    })
                    .always(function() {
                        $(options.formContainer).submit();
                        $(e.target).removeClass('loader');
                        $(e.target).removeAttr('disabled');
                    });
            });
        $(options.formContainer).submit(function(){
            itinerary.dateTimeError();
        });

        itinerary.control();
        itinerary.init();

    });
});