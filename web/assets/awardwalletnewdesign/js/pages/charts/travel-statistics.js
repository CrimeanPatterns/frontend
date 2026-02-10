define([
    'angular-boot',
    'jquery-boot',
    'lib/utils',
    'lib/customizer',
    'chartjs', 'chartjs-plugin-watermark', 'chartjs-plugin-datalabels',
    'translator-boot',
    'angular-ui-router',
    'directives/dialog', 'directives/customizer',
    'filters/unsafe',
    'routing'
], function(angular, $, utils, customizer, Chart) {
    'use strict';

    angular = angular && angular.__esModule ? angular.default : angular;

    const isDark = !$('html').hasClass('light-mode') && ($('html').hasClass('dark-mode') || (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches));
    const $mainBox = $('#mainBox');
    const user = {
        isAuth : !!$mainBox.data('userAuth'),
        isAwPlus : !!$mainBox.data('userAwplus'),
    };

    const numberFormat = utils.getNumberFormatter();
    const dayFormat = (0 === (new Date()).getMonth() ? {month : 'short', day : 'numeric', year : 'numeric'} : {month : 'short', day : 'numeric'});
    const dateFormatDay = new Intl.DateTimeFormat(customizer.locales(), dayFormat);
    const dateFormatMonth = new Intl.DateTimeFormat(customizer.locales(), {month : 'short', year : 'numeric'});
    const queryValueSeparator = ';';

    function showRestrictionPopup(dialogService, type) {
        let dialog, content,
            buttons = [{
                text : Translator.trans('button.close'),
                click : () => {
                    dialog.close();
                },
                'class' : 'btn-silver'
            }];
        if (0 === type) {
            content = Translator.trans('must-have-awplus.login-see-data');
            buttons.push({
                text : Translator.trans('trips.segment.login.btn'),
                click : () => {
                    location.replace(Routing.generate('aw_login'));
                },
                'class' : 'btn-blue'
            });
        } else {
            content = Translator.trans('must-have-awplus.upgrade-see-data');
            buttons.push({
                text : Translator.trans('upgrade-now'),
                click : () => {
                    location.replace(Routing.generate('aw_users_pay', {ref : 216}));
                },
                'class' : 'btn-blue'
            });
        }

        dialog = dialogService.fastCreate(Translator.trans('awplus-is-required'), content, true, true, buttons, 600);
        $('input', '#providerToggle').prop('checked', false);
    }

    let isAllowHashChange = false;
    function setHashParams($location, params) {
        if (isAllowHashChange) {
            $location.search(Object.assign($location.$$search, params));
        }
    }

    const watermarkOption = {
        image : '/images/email/newdesign/logo_dark.png',
        x : 15,
        y : 70,
        width : 200,
        height : 25,
        opacity : 0.3,
        alignX : 'right',
        alignY : 'top',
        alignToChartArea : false,
    };

    angular
        .module('chartsPage', ['ui.router', 'customizer-directive', 'dialog-directive', 'unsafe-mod',])
        .config([
            '$injector', '$stateProvider', '$urlRouterProvider', '$locationProvider',
            function($injector, $stateProvider, $urlRouterProvider, $locationProvider) {
                $locationProvider.html5Mode({
                    enabled : true,
                    rewriteLinks : false,
                    requireBase : false,
                    hashPrefix : '!'
                });
            }
        ])
        .controller('travelCtrl', [
            '$scope', '$location', '$timeout', 'dialogService',
            function($scope, $location, $timeout, dialogService) {
                const cache = {};
                const $chartSwitch = $('#chartSwitch');
                const $providerToggleList = $('#providerToggleList');
                $scope.isLoading = true;

                let numberReservationsOptions = {
                    dateType : 'months',
                    type : {
                        flights : true,
                        hotels : false,
                        rentedCars : false,
                    },
                    providerType : null,
                    providersList : '',
                };

                const handlerOptions = {
                    allowDateType : ['days', 'months'],
                    allowProviderType : ['flights', 'hotels', 'rentedCars'],
                    fetch : function() {
                        numberReservationsOptions.dateType = $('input[name="dateType"]:checked', $chartSwitch).val();
                        Object.assign(numberReservationsOptions.type, {
                            flights : $('#typeFlights').is(':checked') ? true : false,
                            hotels : $('#typeHotels').is(':checked') ? true : false,
                            rentedCars : $('#typeRentedCars').is(':checked') ? true : false,
                        });

                        let isByProviders = $('input[name="providerType"]:checked', $chartSwitch);
                        numberReservationsOptions.providerType = isByProviders.length ? isByProviders.val() : null;

                        return numberReservationsOptions;
                    },
                    setOptionsFromQuery : function() {
                        let queryParams = $location.$$search;
                        if (Object.prototype.hasOwnProperty.call(queryParams, 'numberReservations')) {
                            let parts = queryParams.numberReservations.split(queryValueSeparator);
                            if (-1 !== handlerOptions.allowDateType.indexOf(parts[0])) {
                                numberReservationsOptions.dateType = parts[0];
                            }
                            if ('string' === typeof parts[1]) {
                                numberReservationsOptions.type.flights = !!~~parts[1].charAt(0);
                                numberReservationsOptions.type.hotels = !!~~parts[1].charAt(1);
                                numberReservationsOptions.type.rentedCars = !!~~parts[1].charAt(2);
                            }
                            if ('string' === typeof parts[2] && -1 !== handlerOptions.allowProviderType.indexOf(parts[2])) {
                                numberReservationsOptions.providerType = parts[2];
                            }
                            if ('string' === typeof parts[3] && '' !== parts[3]) {
                                numberReservationsOptions.providersList = parts[3];
                            }
                        }
                    },
                    hashUpdate : function() {
                        let params = handlerOptions.fetch();
                        let hashParams = {
                            'dateType' : params.dateType,
                            'type' : (~~params.type.flights) + '' + (~~params.type.hotels) + '' + (~~params.type.rentedCars),
                            'providerType' : null === params.providerType ? '' : params.providerType,
                            'providersList' : '',
                        };

                        if (null !== params.providerType) {
                            let $list = $('input', $providerToggleList);
                            if (0 === $list.length && '' !== numberReservationsOptions.providersList) {
                                hashParams.providersList = numberReservationsOptions.providersList;
                            } else {
                                $list.each(function() {
                                    hashParams.providersList += '' + ($(this).is(':checked') ? 1 : 0);
                                });
                            }
                        }

                        $scope.$apply(function() {
                            setHashParams($location, {
                                'numberReservations' : Object.values(hashParams).join(queryValueSeparator),
                            });
                        });

                        return hashParams;
                    },
                    setElementsValue : function() {
                        'days' === numberReservationsOptions.dateType ? $('#dateTypeDay').prop('checked', true) : $('#dateTypeMonth').prop('checked', true);
                        for (let typeKey in numberReservationsOptions.type) {
                            $('#type' + utils.ucfirst(typeKey)).prop('checked', numberReservationsOptions.type[typeKey]);
                        }
                        if (0 === $('input:checked', '#typeToggle').length) {
                            if (null === numberReservationsOptions.providerType) {
                                $('#typeFlights').prop('checked', (numberReservationsOptions.type.flights = true));
                            } else {
                                $('#providerType' + utils.ucfirst(numberReservationsOptions.providerType), $chartSwitch).prop('checked', true);
                            }
                        }
                    },
                    getCacheKey : function() {
                        let hashParams = handlerOptions.hashUpdate();
                        delete hashParams.providersList;

                        let param = '';
                        for (let i in hashParams) {
                            param += i + '_' + hashParams[i] + queryValueSeparator;
                        }
                        if (0 === param.length) {
                            return '';
                        }

                        let hash = 0;
                        for (let i = 0; i < param.length; i++) {
                            const char = param.charCodeAt(i);
                            hash = ((hash << 5) - hash) + char;
                            hash = hash & hash;
                        }

                        return '' + hash;
                    },
                };

                function updateData(data = null) {
                    if (null !== data) {
                        return createChart(data);
                    }

                    return $.post(Routing.generate('aw_charts_travel_trends_numberReservation_data'), handlerOptions.fetch(), function(response) {
                        cache[handlerOptions.getCacheKey()] = response.chart;
                        if ($('input[name="providerType"]:checked', $chartSwitch).length) {
                            $providerToggleList.removeAttr('hidden');
                        }
                        createChart(response.chart);
                    }, 'json');
                }

                function createChart(chartOptions) {
                    if (window.chart && 'function' === typeof window.chart['destroy']) {
                        window.chart.destroy();
                    }

                    let params = handlerOptions.fetch();
                    let factorUsers = ('days' === params.dateType ? 10000 : 1000);

                    let currentOptions = {
                        type : 'bar',
                        options : {
                            responsive : true,
                            legend : false,
                            title : {
                                display : true,
                                text : [
                                    Translator.transChoice(
                                        'avg-number-reservations-per-users.v2',
                                        factorUsers,
                                        {
                                            'number' : numberFormat.format(factorUsers),
                                        }
                                    ),
                                    ' '
                                ],
                                fontSize : 26,
                                fontColor : '#999'
                            },
                            scales : {
                                xAxes : [{
                                    ticks : {
                                        callback : function(value) {
                                            value += 'T00:00:00';
                                            return 'months' === params.dateType ? dateFormatMonth.format(new Date(value)) : dateFormatDay.format(new Date(value));
                                        }
                                    }
                                }],
                                yAxes : [{
                                    ticks : {
                                        beginAtZero : true,
                                        callback : function(value) {
                                            return numberFormat.format(value);
                                        }
                                    }
                                }]
                            },
                            watermark : watermarkOption,
                            tooltips : {
                                mode : 'index',
                                xPadding : 10,
                                yPadding : 10,
                                callbacks : {
                                    title : function(tooltipItems, data) {
                                        return false;// data.labels[tooltipItems[0].index];
                                    },
                                    label : function(tooltipItem, data) {
                                        return ' ' + data.datasets[tooltipItem.datasetIndex].label + ': ' + numberFormat.format(data.datasets[tooltipItem.datasetIndex].data[tooltipItem.index]);
                                    }
                                },
                                footerFontStyle : 'normal'
                            },
                            plugins : {
                                datalabels : {
                                    color : 'white',
                                    formatter : function(value) {
                                        return numberFormat.format(value);
                                    }
                                }
                            }
                        }
                    };
                    chartOptions = $.extend({}, chartOptions, currentOptions);

                    window.chart = new Chart('chart', chartOptions);

                    if ($('input[name="providerType"]:checked', $chartSwitch).length) {
                        let html = '', index = 0, checked;
                        chartOptions.data.datasets.forEach(function(item) {

                            html += '<br><input class="checkbox" id="provider_' + index + '" type="checkbox" value="' + index + '" ' + (item.hidden ? '' : 'checked') + '><label class="label' + (item.hidden ? ' disabled' : '') + '" for="provider_' + index + '" data-index="' + index + '"><s class="boxColor" style="background-color: ' + item.backgroundColor + '"></s> ' + item.label + '</label>';
                            ++index;
                        });
                        $providerToggleList.empty().html(html);
                    }

                    $timeout(function() {
                        $scope.$apply(function() {
                            $scope.isLoading = false;
                            window.chart.update();
                        });
                    });
                }

                $scope.typeToggle = ($event, dataIndex) => {
                    isAllowHashChange = true;
                    let $input = $($event.target).prev();
                    let isChecked = $input.is(':checked');
                    $($event.currentTarget).toggleClass('disabled', isChecked);
                    window.chart.data.datasets[dataIndex].hidden = isChecked;
                    window.chart.getDatasetMeta(dataIndex).hidden = isChecked;
                    window.chart.update();
                };

                $('input[name="dateType"],input[name="providerType"],#typeToggle input', $chartSwitch).change(function(e) {
                    let isFromProviderSwitch = $('input[name="providerType"]:checked', $chartSwitch).length;
                    if (!isFromProviderSwitch && 'typeToggle' === $(this).parent().attr('id')) {
                        $providerToggleList.empty();
                        handlerOptions.hashUpdate();
                        return null;
                    }

                    if ('providerType' === $(this).attr('name')) {
                        if (!user.isAuth) {
                            return showRestrictionPopup(dialogService, 0);
                        } else if (!user.isAwPlus) {
                            return showRestrictionPopup(dialogService, 1);
                        }

                        $('input', '#typeToggle').prop('checked', false);
                        $providerToggleList.removeAttr('hidden');

                    } else if ('typeToggle' === $(this).parent().attr('id')) {
                        $('input', '#providerToggle').prop('checked', false);
                        $providerToggleList.empty().attr('hidden', 'hidden');
                    }

                    let cacheKey = handlerOptions.getCacheKey();
                    if (Object.prototype.hasOwnProperty.call(cache, cacheKey)) {
                        return updateData(cache[cacheKey]);
                    }

                    $scope.$apply(function() {
                        $scope.isLoading = true;
                    });

                    if (null !== numberReservationsOptions.providerType) {
                        numberReservationsOptions.providersList = '';
                    }
                    handlerOptions.hashUpdate();
                    return updateData();
                });

                $providerToggleList.on('click', 'label', function(event) {
                    return $scope.typeToggle(event, $(this).data('index'));
                });
                $providerToggleList.on('change', 'input', function(event) {
                    handlerOptions.hashUpdate();
                });

                handlerOptions.setOptionsFromQuery();
                handlerOptions.setElementsValue();

                return updateData();
            }])
        .controller('cancelledCtrl', [
            '$scope', '$location', '$timeout',
            function($scope, $location, $timeout) {
                const cache = {};
                const $chartSwitch = $('#cancelledChartSwitch');
                $scope.isLoading4 = true;

                let cancelledOptions = {
                    type : {
                        flights : true,
                        hotels : false,
                        rentedCars : false,
                    }
                };

                const handlerOptions = {
                    allowDateType : ['months'],
                    allowProviderType : ['flights', 'hotels', 'rentedCars'],
                    fetch : function() {
                        Object.assign(cancelledOptions.type, {
                            flights : $('#cancelledTypeFlights').is(':checked') ? true : false,
                            hotels : $('#cancelledTypeHotels').is(':checked') ? true : false,
                            rentedCars : $('#cancelledTypeRentedCars').is(':checked') ? true : false,
                        });

                        return cancelledOptions;
                    },
                    setOptionsFromQuery : function() {
                        let queryParams = $location.$$search;
                        if (Object.prototype.hasOwnProperty.call(queryParams, 'cancelled')) {
                            let parts = queryParams.cancelled.split(queryValueSeparator);
                            if ('string' === typeof parts[0]) {
                                cancelledOptions.type.flights = !!~~parts[0].charAt(0);
                                cancelledOptions.type.hotels = !!~~parts[0].charAt(1);
                                cancelledOptions.type.rentedCars = !!~~parts[0].charAt(2);
                            }
                        }
                    },
                    hashUpdate : function() {
                        let params = handlerOptions.fetch();
                        let hashParams = {
                            'type' : (~~params.type.flights) + '' + (~~params.type.hotels) + '' + (~~params.type.rentedCars),
                        };

                        $scope.$apply(function() {
                            setHashParams($location, {
                                'cancelled' : Object.values(hashParams).join(queryValueSeparator),
                            });
                        });

                        return hashParams;
                    },
                    setElementsValue : function() {
                        for (let typeKey in cancelledOptions.type) {
                            $('#cancelledType' + utils.ucfirst(typeKey)).prop('checked', cancelledOptions.type[typeKey]);
                        }
                    },
                    getCacheKey : function() {
                        let hashParams = handlerOptions.hashUpdate();

                        let param = '';
                        for (let i in hashParams) {
                            param += i + '_' + hashParams[i] + queryValueSeparator;
                        }
                        if (0 === param.length) {
                            return '';
                        }

                        let hash = 0;
                        for (let i = 0; i < param.length; i++) {
                            const char = param.charCodeAt(i);
                            hash = ((hash << 5) - hash) + char;
                            hash = hash & hash;
                        }

                        return '' + hash;
                    },
                };

                function updateData(data = null) {
                    if (null !== data) {
                        return createChart(data);
                    }

                    return $.post(Routing.generate('aw_charts_travel_trends_cancelled_data'), handlerOptions.fetch(), function(response) {
                        cache[handlerOptions.getCacheKey()] = response.chart;
                        createChart(response.chart);
                    }, 'json');
                }

                function createChart(chartOptions) {
                    if (window.cancelledChart && 'function' === typeof window.cancelledChart['destroy']) {
                        window.cancelledChart.destroy();
                    }

                    let params = handlerOptions.fetch();
                    let factorUsers = 1000;

                    let currentOptions = {
                        type : 'bar',
                        options : {
                            responsive : true,
                            legend : false,
                            title : {
                                display : true,
                                text : [
                                    Translator.transChoice(
                                        'avg-number-cancellations.v2',
                                        factorUsers,
                                        {
                                            'number' : numberFormat.format(factorUsers),
                                        }
                                    ),
                                    ' '
                                ],
                                fontSize : 26,
                                fontColor : '#999'
                            },
                            scales : {
                                xAxes : [{
                                    ticks : {
                                        callback : function(value) {
                                            value += 'T00:00:00';
                                            return dateFormatMonth.format(new Date(value));
                                        }
                                    }
                                }],
                                yAxes : [{
                                    ticks : {
                                        beginAtZero : true,
                                        callback : function(value) {
                                            return numberFormat.format(value);
                                        }
                                    }
                                }]
                            },
                            watermark : watermarkOption,
                            tooltips : {
                                mode : 'index',
                                xPadding : 10,
                                yPadding : 10,
                                callbacks : {
                                    title : function(tooltipItems, data) {
                                        return false;
                                    },
                                    label : function(tooltipItem, data) {
                                        return ' ' + data.datasets[tooltipItem.datasetIndex].label + ': ' + numberFormat.format(data.datasets[tooltipItem.datasetIndex].data[tooltipItem.index]);
                                    }
                                },
                                footerFontStyle : 'normal'
                            },
                            plugins : {
                                datalabels : {
                                    color : 'white',
                                    formatter : function(value) {
                                        return numberFormat.format(value);
                                    }
                                }
                            }
                        }
                    };
                    chartOptions = $.extend({}, chartOptions, currentOptions);

                    window.cancelledChart = new Chart('cancelledChart', chartOptions);

                    $timeout(function() {
                        $scope.$apply(function() {
                            $scope.isLoading4 = false;
                            window.cancelledChart.update();
                        });
                    });
                }

                $scope.typeToggle = ($event, dataIndex) => {
                    isAllowHashChange = true;
                    let $input = $($event.target).prev();
                    let isChecked = $input.is(':checked');
                    $($event.currentTarget).toggleClass('disabled', isChecked);
                    window.cancelledChart.data.datasets[dataIndex].hidden = isChecked;
                    window.cancelledChart.getDatasetMeta(dataIndex).hidden = isChecked;
                    window.cancelledChart.update();
                };

                $('#cancelledTypeToggle input', $chartSwitch).change(function(e) {
                    if ('cancelledTypeToggle' === $(this).parent().attr('id')) {
                        handlerOptions.hashUpdate();
                        return null;
                    }
                    
                    let cacheKey = handlerOptions.getCacheKey();
                    if (Object.prototype.hasOwnProperty.call(cache, cacheKey)) {
                        return updateData(cache[cacheKey]);
                    }

                    $scope.$apply(function() {
                        $scope.isLoading4 = true;
                    });

                    handlerOptions.hashUpdate();
                    return updateData();
                });

                handlerOptions.setOptionsFromQuery();
                handlerOptions.setElementsValue();

                return updateData();
            }])
        .controller('longhaulCtrl', [
            '$scope', '$location', '$timeout', 'dialogService',
            function($scope, $location, $timeout, dialogService) {
                let longhaulChart = document.getElementById('longhaulChart').getContext('2d');
                const longHaulOptions = {
                    dateType : 'months',
                    long : true,
                    short : true,
                    stack : false,
                };
                $scope.isLoading2 = true;

                const handlerOptions = {
                    allowDateType : [/*'days',*/ 'months'],
                    fetch : function() {
                        longHaulOptions.dateType = $('input[name="dateType"]:checked', '#chartSwitch').val();
                        longHaulOptions.long = $('#longHaulFlights').is(':checked');
                        longHaulOptions.short = $('#shortHaulFlights').is(':checked');
                        longHaulOptions.stack = $('#flightHaulStacked').is(':checked');

                        return longHaulOptions;
                    },
                    setOptionsFromQuery : function() {
                        let queryParams = $location.$$search;
                        if (Object.prototype.hasOwnProperty.call(queryParams, 'flightsHaul')) {
                            let parts = queryParams.flightsHaul.split(queryValueSeparator);
                            if (-1 !== handlerOptions.allowDateType.indexOf(parts[0])) {
                                longHaulOptions.dateType = parts[0];
                            }
                            if ('string' === typeof parts[1]) {
                                longHaulOptions.long = !!~~parts[1].charAt(0);
                                longHaulOptions.short = !!~~parts[1].charAt(1);
                            }
                            if ('string' === typeof parts[2]) {
                                longHaulOptions.stack = !!~~parts[2].charAt(1);
                            }
                        }
                    },
                    hashUpdate : function() {
                        let params = handlerOptions.fetch();
                        let hashParams = {
                            'dateType' : params.dateType,
                            'longshort' : (~~params.long) + '' + (~~params.short),
                            'stack' : ~~params.stack,
                        };

                        $scope.$apply(function() {
                            setHashParams($location, {
                                'flightsHaul' : Object.values(hashParams).join(queryValueSeparator),
                            });
                        });

                        return hashParams;
                    },
                    setElementsValue : function() {
                        $('#longHaulFlights').prop('checked', longHaulOptions.long);
                        $('#shortHaulFlights').prop('checked', longHaulOptions.short);
                        $('#flightHaulStacked').prop('checked', longHaulOptions.stack);
                    },
                };

                $scope.typeToggle = ($event, dataIndex) => {
                    isAllowHashChange = true;
                    let $input = $($event.target).prev();
                    let isChecked = $input.is(':checked');
                    $($event.currentTarget).toggleClass('disabled', isChecked);
                    window.longHaultChart.data.datasets[dataIndex].hidden = isChecked;
                    window.longHaultChart.getDatasetMeta(dataIndex).hidden = isChecked;
                    window.longHaultChart.update();
                };

                $scope.stackedToggle = function() {
                    let isStack = window.longHaultChart.options.scales.xAxes[0].stacked;
                    window.longHaultChart.options.scales.xAxes[0].stacked = !isStack;
                    window.longHaultChart.options.scales.yAxes[0].stacked = !isStack;
                    if (isStack) {
                        window.longHaultChart.options.plugins.datalabels.offset = -5;
                        if (!isDark) {
                            window.longHaultChart.options.plugins.datalabels.color = '#333';
                        }
                    } else {
                        window.longHaultChart.options.plugins.datalabels.offset = -18;
                        window.longHaultChart.options.plugins.datalabels.color = 'white';
                    }
                    window.longHaultChart.update();
                }

                function createChart(chartOptions) {
                    let currentOptions = {
                        type : 'bar',
                        options : {
                            responsive : true,
                            legend : false,
                            title : {
                                display : true,
                                text : [Translator.trans('longshort-haul-flights-per-users', {'number' : numberFormat.format(1000)}), ''],
                                fontSize : 26,
                                fontColor : '#999'
                            },
                            tooltips : {
                                mode : 'index',
                                xPadding : 10,
                                yPadding : 10,
                                //intersect : false,
                                callbacks : {
                                    title : function(tooltipItems, data) {
                                        return false;
                                    },
                                    label : function(tooltipItem, data) {
                                        return ' ' + data.datasets[tooltipItem.datasetIndex].label + ': ' + numberFormat.format(data.datasets[tooltipItem.datasetIndex].data[tooltipItem.index]);
                                    }
                                },
                                footerFontStyle : 'normal'
                            },
                            scales : {
                                xAxes : [{
                                    stacked : longHaulOptions.stack,
                                    ticks : {
                                        callback : function(value) {
                                            value += 'T00:00:00';
                                            return dateFormatMonth.format(new Date(value));
                                        }
                                    }
                                }],
                                yAxes : [{
                                    stacked : longHaulOptions.stack,
                                    beginAtZero : true,
                                }]
                            },
                            watermark : watermarkOption,
                            plugins : {
                                datalabels : {
                                    color : isDark ? 'white' : '#333',
                                    align : 'top',
                                    anchor : 'end',
                                    offset : -5,
                                    formatter : function(value) {
                                        return numberFormat.format(value);
                                    }
                                }
                            }
                        }
                    };

                    chartOptions = $.extend({}, chartOptions, currentOptions);
                    window.longHaultChart = new Chart(longhaulChart, chartOptions);

                    $timeout(function() {
                        $scope.$apply(function() {
                            $scope.isLoading2 = false;
                        });
                    });
                    $timeout(function() {
                        handlerOptions.hashUpdate();
                    });
                }

                function updateData() {
                    $.post(Routing.generate('aw_charts_travel_trends_longHaul_data'), handlerOptions.fetch(), function(response) {
                        createChart(response.chart);
                    }, 'json');
                }

                $('input', '#longHaulSwitch').change(function() {
                    handlerOptions.hashUpdate();
                });

                handlerOptions.setOptionsFromQuery();
                handlerOptions.setElementsValue();

                return updateData();
            }])
        .controller('earningRedemptionCtrl', [
            '$scope', '$location', '$timeout', 'dialogService',
            function($scope, $location, $timeout, dialogService) {
                let earningRedemptionChart = document.getElementById('earningRedemptionChart').getContext('2d');
                const earningRedemptionOptions = {
                    type : 'banks',
                };
                $scope.isLoading3 = true;

                const handlerOptions = {
                    allowType : ['banks', 'hotels', 'airlines'],
                    fetch : function() {
                        earningRedemptionOptions.type = $('input[name="earningRedemption"]:checked', '#earningRedemptionSwitch').val();

                        return earningRedemptionOptions;
                    },
                    setOptionsFromQuery : function() {
                        let queryParams = $location.$$search;
                        if (Object.prototype.hasOwnProperty.call(queryParams, 'earningRedemption')) {
                            let parts = queryParams.earningRedemption.split(queryValueSeparator);
                            if (-1 !== handlerOptions.allowType.indexOf(parts[0])) {
                                earningRedemptionOptions.type = parts[0];
                            }
                        }
                    },
                    hashUpdate : function() {
                        let params = handlerOptions.fetch();
                        let hashParams = {
                            type : params.type,
                        };

                        $scope.$apply(function() {
                            setHashParams($location, {
                                'earningRedemption' : Object.values(hashParams).join(queryValueSeparator),
                            });
                        });

                        return hashParams;
                    },
                    setElementsValue : function() {
                        $('input[name="earningRedemption"][value="' + earningRedemptionOptions.type + '"]').prop('checked', true);
                    },
                };

                $('input', '#earningRedemptionSwitch').change(function() {
                    isAllowHashChange = true;
                    $(this).toggleClass('disabled', $(this).prop('checked'));
                    for (let i = 0; i < 6; i++) {
                        window.earningRedemptionChart.data.datasets[i].hidden = true;
                        window.earningRedemptionChart.getDatasetMeta(i).hidden = true;
                    }
                    let dataIndex = parseInt($(this).data('index'));
                    window.earningRedemptionChart.data.datasets[dataIndex].hidden = false;
                    window.earningRedemptionChart.data.datasets[1 + dataIndex].hidden = false;
                    window.earningRedemptionChart.getDatasetMeta(dataIndex).hidden = false;
                    window.earningRedemptionChart.getDatasetMeta(1 + dataIndex).hidden = false;
                    window.earningRedemptionChart.update();
                    handlerOptions.hashUpdate();
                });

                function createChart(chartOptions) {
                    let currentOptions = {
                        type : 'bar',
                        options : {
                            responsive : true,
                            legend : false,
                            title : {
                                display : true,
                                text : [Translator.trans('mile-point-earning-redemption'), ''],
                                fontSize : 26,
                                fontColor : '#999'
                            },
                            tooltips : {
                                mode : 'index',
                                callbacks : {
                                    title : function(tooltipItems, data) {
                                        return false;
                                    },
                                    label : function(tooltipItem, data) {
                                        let isRedemptionCol = -1 !== [1, 3, 5].indexOf(tooltipItem.datasetIndex);
                                        return ` ${data.datasets[tooltipItem.datasetIndex].label}: ${isRedemptionCol ? '(' : ''}${data.datasets[tooltipItem.datasetIndex].dataLabel[tooltipItem.index]}${isRedemptionCol ? ')' : ''}`;
                                    }
                                },
                                footerFontStyle : 'normal'
                            },
                            scales : {
                                xAxes : [{
                                    //stacked : true,
                                    ticks : {
                                        callback : function(value) {
                                            value += 'T00:00:00';
                                            return dateFormatMonth.format(new Date(value));
                                        }
                                    }
                                }],
                                yAxes : [{
                                    //stacked : true,
                                    beginAtZero : true,
                                    ticks : {
                                        callback : function(value) {
                                            value += '';
                                            let length = value.length;
                                            if ('000000' === value.substr(-6)) {
                                                return value.substr(0, length - 6) + 'M';
                                            }
                                            if ('000' === value.substr(-3)) {
                                                return value.substr(0, length - 3) + 'K';
                                            }

                                            return value;
                                        }
                                    }
                                }]
                            },
                            watermark : watermarkOption,
                            plugins : {
                                datalabels : {
                                    color : function(ct) {
                                        return -1 !== [0, 2, 4].indexOf(ct.datasetIndex) ? '#109617' : '#316395';
                                    },
                                    //clamp : true,
                                    anchor : 'end',
                                    align : 'top',
                                    //offset : 5,
                                    rotation : -45,
                                    formatter : function(value, ct) {
                                        let isRedemptionCol = -1 !== [1, 3, 5].indexOf(ct.datasetIndex);
                                        return '   ' + (isRedemptionCol ? '(' : '') + ct.dataset.dataLabel[ct.dataIndex] + (isRedemptionCol ? ')' : '');
                                    }
                                }
                            }
                        }
                    };

                    chartOptions = $.extend({}, chartOptions, currentOptions);
                    window.earningRedemptionChart = new Chart(earningRedemptionChart, chartOptions);

                    $timeout(function() {
                        $scope.$apply(function() {
                            $scope.isLoading3 = false;
                        });
                    });
                }

                function updateData() {
                    $.post(Routing.generate('aw_charts_travel_trends_earningRedemption_data'), handlerOptions.fetch(), function(response) {
                        createChart(response.chart);
                    }, 'json');
                }

                handlerOptions.setOptionsFromQuery();
                handlerOptions.setElementsValue();

                return updateData();
            }])
        .controller('topTravelCtrl', [
            '$scope', '$location', '$timeout', 'dialogService',
            function($scope, $location, $timeout, dialogService) {
                const $topTravelHotels = $('#topTravelHotels');
                const $topTravelRental = $('#topTravelRental');
                const $topFlightRoutes = $('#topFlightRoutes');

                const topHotelsOptions = {
                    year : (new Date()).getFullYear(),
                    continent : 0,
                    country : '',
                };
                const topRentalOptions = {
                    year : (new Date()).getFullYear(),
                    continent : 0,
                    country : '',
                };
                const topRoutesOptions = {
                    year : (new Date()).getFullYear(),
                    type : 'long',
                };

                const handlerOptions = {
                    fetch : function() {
                        topHotelsOptions.year = $('input[name="topHotelsYear"]:checked', $topTravelHotels).val();
                        topHotelsOptions.continent = $('input[name="topHotelsSetContinent-' + topHotelsOptions.year + '"]:checked', $topTravelHotels).val().split('-')[1];
                        let $hotelCountryCity = $('#topHotelsCitys-' + topHotelsOptions.year + '-' + topHotelsOptions.continent);
                        topHotelsOptions.country = '' === $hotelCountryCity.val() ? '' : $hotelCountryCity.val().split('-')[2];

                        topRentalOptions.year = $('input[name="topRentedYear"]:checked', $topTravelRental).val();
                        topRentalOptions.continent = $('input[name="topRentedSetContinent-' + topRentalOptions.year + '"]:checked', $topTravelRental).val().split('-')[1];
                        let $rentedCountryCity = $('#topRentedCitys-' + topRentalOptions.year + '-' + topRentalOptions.continent);
                        topRentalOptions.country = '' === $rentedCountryCity.val() ? '' : $rentedCountryCity.val().split('-')[2];

                        topRoutesOptions.year = $('input[name="topFlightRoutesYear"]:checked', $topFlightRoutes).val();
                        topRoutesOptions.type = $('input[name="topFlightRoutesType"]:checked', $topFlightRoutes).val();

                        return {topHotelsOptions, topRentalOptions, topRoutesOptions};
                    },
                    setOptionsFromQuery : function() {
                        let queryParams = $location.$$search;

                        if (Object.prototype.hasOwnProperty.call(queryParams, 'topHotels')) {
                            let parts = queryParams.topHotels.split(queryValueSeparator);
                            if ('string' === typeof parts[0] && $('#topHotelsYear' + parts[0]).length) {
                                topHotelsOptions.year = parts[0];
                            }
                            if ('string' === typeof parts[1] && $('#topHotelsContinent-' + topHotelsOptions.year + '-' + parts[1]).length) {
                                topHotelsOptions.continent = parts[1];
                            }
                            if ('string' === typeof parts[2] && '' !== parts[2] && $('#topHotelsCitys-' + topHotelsOptions.year + '-' + topHotelsOptions.continent).length) {
                                topHotelsOptions.country = parts[2];
                            }
                        }

                        if (Object.prototype.hasOwnProperty.call(queryParams, 'topRental')) {
                            let parts = queryParams.topRental.split(queryValueSeparator);
                            if ('string' === typeof parts[0] && $('#topRentedYear' + parts[0]).length) {
                                topRentalOptions.year = parts[0];
                            }
                            if ('string' === typeof parts[1] && $('#topRentedContinent-' + topRentalOptions.year + '-' + parts[1]).length) {
                                topRentalOptions.continent = parts[1];
                            }
                            if ('string' === typeof parts[2] && '' !== parts[2] && $('#topRentedCitys-' + topRentalOptions.year + '-' + topRentalOptions.continent).length) {
                                topRentalOptions.country = parts[2];
                            }
                        }

                        if (Object.prototype.hasOwnProperty.call(queryParams, 'topRoutes')) {
                            let parts = queryParams.topRoutes.split(queryValueSeparator);
                            if ('string' === typeof parts[0] && $('#topFlightRoutesYear' + parts[0]).length) {
                                topRoutesOptions.year = parts[0];
                            }
                            if ('string' === typeof parts[1] && $('#topFlightRoutesType' + utils.ucfirst(parts[1])).length) {
                                topRoutesOptions.type = parts[1];
                            }
                        }
                    },
                    hashUpdate : function() {
                        let params = handlerOptions.fetch();

                        let hotelHashParams = {
                            year : params.topHotelsOptions.year,
                            continent : params.topHotelsOptions.continent,
                            country : params.topHotelsOptions.country,
                        };
                        let rentalHashParams = {
                            year : params.topRentalOptions.year,
                            continent : params.topRentalOptions.continent,
                            country : params.topRentalOptions.country,
                        };
                        let routesHashParams = {
                            year : params.topRoutesOptions.year,
                            type : params.topRoutesOptions.type,
                        };

                        $scope.$apply(function() {
                            setHashParams($location, {
                                'topHotels' : Object.values(hotelHashParams).join(queryValueSeparator),
                                'topRental' : Object.values(rentalHashParams).join(queryValueSeparator),
                                'topRoutes' : Object.values(routesHashParams).join(queryValueSeparator),
                            });
                        });

                    },
                    setElementsValue : function() {
                        $('#topHotelsYear' + topHotelsOptions.year).prop('checked', true).trigger('change');
                        $('#topHotelsContinent-' + topHotelsOptions.year + '-' + topHotelsOptions.continent).prop('checked', true).trigger('change');
                        if ('' !== topHotelsOptions.country) {
                            $('#topHotelsCitys-' + topHotelsOptions.year + '-' + topHotelsOptions.continent).val(topHotelsOptions.year + '-' + topHotelsOptions.continent + '-' + topHotelsOptions.country).trigger('change');
                        }

                        $('#topRentedYear' + topRentalOptions.year).prop('checked', true).trigger('change');
                        $('#topRentedContinent-' + topRentalOptions.year + '-' + topRentalOptions.continent).prop('checked', true).trigger('change');
                        if ('' !== topRentalOptions.country) {
                            $('#topRentedCitys-' + topRentalOptions.year + '-' + topRentalOptions.continent).val(topRentalOptions.year + '-' + topRentalOptions.continent + '-' + topRentalOptions.country).trigger('change');
                        }

                        $('#topFlightRoutesYear' + topRoutesOptions.year).prop('checked', true).trigger('change');
                        $('#topFlightRoutesType' + utils.ucfirst(topRoutesOptions.type)).prop('checked', true).trigger('change');
                    },
                };


                $('input[name="topHotelsYear"]', $topTravelHotels).change(function() {
                    $('.topTravels-box--active', $topTravelHotels).removeClass('topTravels-box--active');
                    $('#topHotels-' + $(this).val() + '-0', $topTravelHotels).addClass('topTravels-box--active');
                    $('.topTravel-switch--active', $topTravelHotels).removeClass('topTravel-switch--active');
                    $('.topTravel-switch-' + $(this).val() + '-0', $topTravelHotels).addClass('topTravel-switch--active');

                    $('input[name="topHotelsSetContinent-' + $(this).val() + '"]:checked', $topTravelHotels).trigger('change');
                });

                $('input[name^="topHotelsSetContinent-"]', $topTravelHotels).change(function() {
                    $('.topTravel-switch select', $topTravelHotels).val('');
                    $('.toptravel-city--active', $topTravelHotels).removeClass('toptravel-city--active');
                    $('.topTravels-box--active', $topTravelHotels).removeClass('topTravels-box--active');
                    $('#topHotels-' + $(this).val(), $topTravelHotels).addClass('topTravels-box--active');
                    $('.topTravel-counrys-list--active', $topTravelHotels).removeClass('topTravel-counrys-list--active');
                    $('.topTravel-counrys-list-' + $(this).val(), $topTravelHotels).addClass('topTravel-counrys-list--active');

                    $('.topTravel-countrys-list--active', $topTravelHotels).removeClass('topTravel-countrys-list--active');
                    $('.topTravel-countrys-list-' + $(this).val(), $topTravelHotels).addClass('topTravel-countrys-list--active');
                });

                $('select[name="countryCity"]', $topTravelHotels).change(function() {
                    $('.topTravels-box--active', $topTravelHotels).removeClass('topTravels-box--active');
                    $('.toptravel-city--active', $topTravelHotels).removeClass('toptravel-city--active');
                    if ('' === $(this).val()) {
                        let year = $('input[name="topHotelsYear"]:checked', $topTravelHotels).val();
                        return $('input[name="topHotelsSetContinent-' + year + '"]:checked', $topTravelHotels).trigger('change');
                    }
                    $('.toptravel-city-' + $(this).val(), $topTravelHotels).addClass('toptravel-city--active');
                });


                $('input[name="topRentedYear"]', $topTravelRental).change(function() {
                    $('.topTravels-box--active', $topTravelRental).removeClass('topTravels-box--active');
                    $('#topRented-' + $(this).val() + '-0', $topTravelRental).addClass('topTravels-box--active');
                    $('.topTravel-switch--active', $topTravelRental).removeClass('topTravel-switch--active');
                    $('.topTravel-switch-' + $(this).val() + '-0', $topTravelRental).addClass('topTravel-switch--active');

                    $('input[name="topRentedSetContinent-' + $(this).val() + '"]:checked', $topTravelRental).trigger('change');
                });

                $('input[name^="topRentedSetContinent-"]', $topTravelRental).change(function() {
                    $('.topTravel-switch select', $topTravelRental).val('');
                    $('.toptravel-city--active', $topTravelRental).removeClass('toptravel-city--active');
                    $('.topTravels-box--active', $topTravelRental).removeClass('topTravels-box--active');
                    $('#topRented-' + $(this).val(), $topTravelRental).addClass('topTravels-box--active');
                    $('.topTravel-counrys-list--active', $topTravelRental).removeClass('topTravel-counrys-list--active');
                    $('.topTravel-counrys-list-' + $(this).val(), $topTravelRental).addClass('topTravel-counrys-list--active');

                    $('.topTravel-countrys-list--active', $topTravelRental).removeClass('topTravel-countrys-list--active');
                    $('.topTravel-countrys-list-' + $(this).val(), $topTravelRental).addClass('topTravel-countrys-list--active');
                });

                $('select[name="countryCity"]', $topTravelRental).change(function() {
                    $('.topTravels-box--active', $topTravelRental).removeClass('topTravels-box--active');
                    $('.toptravel-city--active', $topTravelRental).removeClass('toptravel-city--active');
                    if ('' === $(this).val()) {
                        let year = $('input[name="topRentedYear"]:checked', $topTravelRental).val();
                        return $('input[name="topRentedContinent-' + year + '"]:checked', $topTravelRental).trigger('change');
                    }
                    $('.toptravel-city-' + $(this).val(), $topTravelRental).addClass('toptravel-city--active');
                });


                $('input[name="topFlightRoutesYear"]', $topFlightRoutes).change(function() {
                    let type = $('input[name="topFlightRoutesType"]:checked', $topFlightRoutes).val();
                    $('.topTravels-box--active', $topFlightRoutes).removeClass('topTravels-box--active');
                    $('#topFlightRoutes' + $(this).val() + type, $topFlightRoutes).addClass('topTravels-box--active');
                });
                $('input[name="topFlightRoutesType"]', $topFlightRoutes).change(function() {
                    let year = $('input[name="topFlightRoutesYear"]:checked', $topFlightRoutes).val();
                    $('.topTravels-box--active', $topFlightRoutes).removeClass('topTravels-box--active');
                    $('#topFlightRoutes' + year + $(this).val(), $topFlightRoutes).addClass('topTravels-box--active');
                });

                handlerOptions.setOptionsFromQuery();
                handlerOptions.setElementsValue();

                $('input,select', '#topTravels').change(function() {
                    isAllowHashChange = true;
                    handlerOptions.hashUpdate();
                });
            }]);

    $(document).ready(function() {
        angular.bootstrap(document, ['chartsPage']);
    });
});
