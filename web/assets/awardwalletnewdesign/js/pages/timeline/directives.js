define([
    'angular-boot',
    'lib/utils',
    'lib/customizer',
    'dateTimeDiff',
    'pages/timeline/main'
], function (angular, utils, customizer, dateTimeDiff) {
    angular = angular && angular.__esModule ? angular.default : angular;

    angular.module('app')
        .directive('onFinishRender', ['$timeout', function ($timeout) {
            return {
                restrict: 'A',
                link: function (scope, element, attr) {
                    if (scope.$last === true) {
                        $timeout(function () {
                            scope.$emit(attr.onFinishRender ? attr.onFinishRender : 'ngRepeatFinished');
                        });
                    }
                }
            }
        }])
        .directive('onError', () => {
            return {
                restrict: 'A',
                link: function($scope, $element, $attr) {
                    $element.on('error', () => {
                        $element.attr('src', $attr.onError);
                    })
                }
            }
        })
        .directive('imageLazySrc', ['$document', 'scrollAndResizeListener', ($document, scrollAndResizeListener) => {
            const offsetFactor = 0.5;

            return {
                restrict: 'A',
                scope: {
                    imageLazySrc: '='
                },
                link($scope, $element, $attr) {
                    let listenerRemover;

                    function isInView(clientHeight, clientWidth) {
                        const imageRect = $element[0].getBoundingClientRect();
                        const offsetHeight = clientHeight * offsetFactor;
                        const offsetWidth = clientWidth * offsetFactor;

                        if (
                            (imageRect.top >= -offsetHeight && imageRect.bottom <= (clientHeight + offsetHeight))
                            &&
                            (imageRect.left >= -offsetWidth && imageRect.right <= (clientWidth + offsetWidth))
                        ) {
                            $element.attr('src', $scope.imageLazySrc);
                            listenerRemover();
                            $scope.$watch('imageLazySrc', val => {
                                if (val) {
                                    $element.attr('src', val);
                                }
                            });
                        }
                    }

                    listenerRemover = scrollAndResizeListener.addListener(isInView);
                    $element.on('$destroy', () => listenerRemover());

                    isInView(
                        $document[0].documentElement.clientHeight,
                        $document[0].documentElement.clientWidth
                    );
                }
            };
        }])
        .directive('wrapper', ['$timeout', function ($timeout) {
            return {
                restrict: 'A',
                link: function (scope, element) {
                    scope.$watch(() => {
                        return scope.segment.undroppable;
                    }, val => {
                        const el = element.parents('.wrapper');

                        if (val) {
                            if (!el.hasClass('undroppable')) {
                                el.addClass('undroppable')
                            }
                        } else {
                            if (el.hasClass('undroppable')) {
                                el.removeClass('undroppable')
                            }
                        }
                    });
                }
            }
        }])
        .directive('tripStart', function () {
            return {
                restrict: 'EA',
                scope: {
                    plans: '=',
                    segment: '='
                },
                link: function (scope, element) {
                    scope.$watch('segment', function () {
                        if (!scope.plans[scope.segment.planId] && window.showTooltips) {
                            scope.plans[scope.segment.planId] = scope.segment;
                            scope.plans[scope.segment.planId].needShowTooltips = true;
                            window.tripStart = element;
                        } else
                            scope.plans[scope.segment.planId] = scope.segment;
                    });
                }
            }
        })
        .directive('tripEnd', ['$timeout', function ($timeout) {
            return {
                restrict: 'EA',
                scope: {
                    plans: '=',
                    segment: '=',
                    segments: '='
                },
                link: function (scope, element) {
                    scope.$watch('plans', function (o) {
                        if (o && scope.plans[scope.segment.planId]) {
                            var plan = scope.plans[scope.segment.planId];
                            scope.segment.name = plan.name;

                            var startSegment = scope.segments.indexOf(plan),
                                endSegment = scope.segments.indexOf(scope.segment);

                            var points = [], planLastUpdate = 0;
                            for (var i = startSegment; i < endSegment; i++) {
                                var current = scope.segments[i];
                                if (current &&
                                    (!current.icon || ['fly', 'bus', 'boat', 'passage-boat', 'train'].indexOf(current.icon) > -1 || current.icon.indexOf('fly') > -1) &&
                                    current.type === 'segment' &&
                                    current.map &&
                                    current.map.points.length > 0 &&
                                    points.length < 10 // Максимальное количество точек на миникарте
                                ){
                                    if (current.map.points.length === 2) {
                                        points.push(current.map.points[0] + '-' + current.map.points[1]);
                                    } else {
                                        points.push(current.map.points[0]);
                                    }
                                }

                                if (!current || undefined === current.lastUpdated) {
                                    continue;
                                }

                                if (current.type === 'planStart') {
                                    planLastUpdate = current.lastUpdated;
                                }

                                if (current.type === 'segment' && planLastUpdate < current.lastUpdated) {
                                    planLastUpdate = current.lastUpdated;
                                }
                            }

                            if (points.length) {
                                plan.map = points;
                            }

                            if (Number.isInteger(planLastUpdate) && planLastUpdate > 0) {
                                plan.lastUpdated = dateTimeDiff.longFormatViaDates(new Date(), new Date(planLastUpdate * 1000));
                            }

                            if (plan.needShowTooltips) {
                                plan.needShowTooltips = window.showTooltips = false;
                                $timeout(function () {

                                    window.tripStart.find('[data-tip]').filter(function (id, el) {
                                        return !!$(el).prop('tooltip-initialized');
                                    }).tooltip('open');

                                    if (element.find('[data-tip]').prop('tooltip-initialized'))
                                        element.find('[data-tip]').tooltip('open');

                                    $(document).one('click', function () {
                                        try{
                                            $('[data-tip]').filter(function (id, el) {
                                                return !!$(el).prop('tooltip-initialized');
                                            }).tooltip('close');
                                            // eslint-disable-next-line no-empty
                                        }catch(e){}
                                    })
                                }, 100);
                            }
                        }
                    }, true);
                }
            }
        }])
        .directive('tripExpand', ['$timeout', '$state', function ($timeout, $state) {
            return {
                restrict: 'A',
                scope: {
                    segment: '=tripExpand'
                },
                link: function (scope, element) {
                    function close() {
                        $(element.next()).slideUp(300, function () {
                            scope.$apply(function () {
                                scope.segment.opened = false;
                                $state.params.openSegment = null;
                            });
                        })
                        if (undefined != scope.segment.dialogFlight) {
                            scope.segment.dialogFlight.close();
                        }
                    }

                    function open(duration) {
                        duration = duration || 300;
                        if (!scope.segment.details) {
                            scope.$apply(function () {
                                scope.segment.opened = false;
                                $state.params.openSegment = null;
                            });

                            return;
                        }

                        scope.$apply(function () {
                            scope.segment.opened = true;
                            $state.params.openSegment = scope.segment.id;
                        });
                        $timeout(function () {
                            $(element.next()).slideDown(duration, function () {
                                if (document.location.href.match(/\/print\//)) {
                                    utils.debounce(function () {
                                        window.print();
                                    }, 250);
                                }

                                if (!scope.segment.details.bookingLink) {
                                    return;
                                }

                                var row = $(element).closest('.trip-row');
                                row.find('.checkin-date, .checkout-date').attr('data-role', 'datepicker');
                                customizer.initDatepickers(row, function(){
                                    var checkinDatepicker = $(element).closest('.trip-row').find('input.checkin-date');
                                    var checkoutDatepicker = $(element).closest('.trip-row').find('input.checkout-date');

                                    var datepickerValue = checkinDatepicker.val();
                                    checkinDatepicker.datepicker('option', 'onSelect', function (date) {
                                        var selectedDate = checkinDatepicker.datepicker("getDate");
                                        selectedDate.setDate(selectedDate.getDate() + 1);
                                        console.log(checkoutDatepicker.datepicker('option', 'all'));

                                        var options = checkoutDatepicker.datepicker('option', 'all');
                                        options.minDate = selectedDate;

                                        checkoutDatepicker.datepicker(options);
                                        checkoutDatepicker.datepicker('setDate',selectedDate);
                                    });
                                });

                                var autocompleteInput = $(element).closest('.trip-row').find('input.airport-name:not(.ui-autocomplete-input)');
                                var autocompleteRequest;
                                var autoCompleteData;

                                autocompleteInput
                                    .off('keydown keyup change')
                                    .on('keydown', function (e) {
                                        if (
                                            !$.trim($(e.target).val()) &&
                                            (e.keyCode === 0 || e.keyCode === 32)
                                        ) e.preventDefault();
                                    })
                                    .on('keyup', function (e) {
                                        scope.segment.details.bookingLink.formFields.selectedIata = null;
                                        scope.segment.details.bookingLink.formFields.selectedDestination = null;
                                    })
                                    .on('blur', function (e) {
                                        if(autoCompleteData.length){
                                            autocompleteInput.val(autoCompleteData[0].value);
                                            scope.segment.details.bookingLink.formFields.selectedDestination = autoCompleteData[0].destination;
                                        }else{
                                            autocompleteInput.val('');
                                            scope.segment.details.bookingLink.formFields.selectedDestination = null;
                                        }
                                    })
                                    .autocomplete({
                                        delay: 200,
                                        minLength: 2,
                                        source: function (request, response) {
                                            if (request.term && request.term.length >= 3) {
                                                var self = this;

                                                if(autocompleteRequest)
                                                    autocompleteRequest.abort();

                                                autocompleteRequest = $.get(Routing.generate("google_geo_code", {query: request.term}), function (data) {
                                                    $(self.element).removeClass('loading-input');

                                                    var result = data.map(function (item) {
                                                        var country = item.address_components
                                                            .filter(function (component) {
                                                                return component.types.indexOf('country') > -1
                                                            });
                                                        var countryLong = country.length && country[0].long_name;

                                                        var city = item.address_components
                                                            .filter(function (component) {
                                                                return component.types.indexOf('locality') > -1
                                                            });
                                                        city = city.length && city[0].long_name;

                                                        return {label: item.formatted_address, value: city + ', ' + countryLong, destination: city};
                                                    });

                                                    if(!autocompleteInput.is(':focus')){
                                                        if(result.length){
                                                            autocompleteInput.val(result[0].value);
                                                            scope.segment.details.bookingLink.formFields.selectedDestination = result[0].destination;
                                                        }else{
                                                            scope.segment.details.bookingLink.formFields.selectedDestination = null;
                                                        }
                                                        scope.segment.details.bookingLink.formFields.selectedIata = null;
                                                    }

                                                    autoCompleteData = result;

                                                    response(result);
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
                                        },
                                        select: function(event, ui) {
                                            event.preventDefault();
                                            $(event.target).val(ui.item.value);
                                            scope.segment.details.bookingLink.formFields.selectedIata = null;
                                            scope.segment.details.bookingLink.formFields.selectedDestination = ui.item.destination;
                                        }
                                    })
                            });
                        }, 1);
                    }

                    if (scope.segment.opened) {
                        $timeout(function () {
                            open(0);
                        });
                    }

                    $(element)
                        .on('click', function () {
                            if (scope.segment.opened) {
                                close();
                            } else {
                                open();
                            }
                        });
                    if ($state.params.openSegment === scope.segment.id) {

                        $timeout(function () {
                            if ($state.is('timeline')) {
                                $('html, body').scrollTop($(element).offset().top - 50);
                            }
                        }, 100);
                        $timeout(function () {
                            $(element).trigger('click');
                            $(element).next().effect('highlight');
                        }, 200)

                    }
                }
            }
        }])
        .directive('ownerAutocomplete', ['$rootScope', function ($rootScope) {
            return {
                restrict: 'A',
                scope: {
                    ngData: '='
                },
                link: function (scope, elem, attrs) {
                    $rootScope.agentIsSet = false;
                    var NoResultsLabel = '<i class="icon-warning-small"></i>&nbsp; No members found';
                    elem.autocomplete({
                        minLength: 2,
                        source: function (request, response) {
                            const element = $(this.element).attr('class', 'loading-input');
                            const lastResponse = $.ajax({
                                url: Routing.generate('aw_business_members_dropdown_timeline', {q: request.term, add: true}),
                                method: 'POST',
                                success: function (data, status, xhr) {
                                    if ($.isEmptyObject(data)) {
                                        data = {
                                            label: NoResultsLabel
                                        };
                                    }
                                    if (lastResponse === xhr) {
                                        response(data);
                                        element.attr('class', 'clear-input');
                                    }
                                }
                            })
                        },
                        select: function (event, ui) {
                            if (ui.item.label === NoResultsLabel) {
                                scope.ngData = '';

                                return false;
                            }

                            $(event.target).val(ui.item.label);
                            scope.ngData = ui.item.value;
                            scope.$apply();
                            $rootScope.agentIsSet = true;
                            return false;
                        },
                        focus: function (event, ui) {
                            if (ui.item.label === NoResultsLabel) {
                                return false;
                            }
                            if (event.keyCode == 40 || event.keyCode == 38)
                                $(event.target).val(ui.item.label);
                            return false;
                        }
                    });
                }
            }
        }])
});