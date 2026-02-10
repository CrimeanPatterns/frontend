define([
    'lib/customizer',
    'angular-boot',
    'jquery-boot',
    'filters/unsafe'
], function (customizer, angular, $) {
    angular = angular && angular.__esModule ? angular.default : angular;

    var InitData;

    var app = angular.module("skyScannerDealsApp", [
        'appConfig', 'unsafe-mod'
    ]);


    app.config([
        '$injector',
        function ($injector) {
            if ($injector.has('InitData')) {
                InitData = $injector.get('InitData');
            }
        }
    ]);

    app.controller('flightDealsCtrl', ['$scope', '$http', function ($scope, $http) {

        this.state = {
            isLoading: true,
            list: null,
            currentPage: null,
            origin: null,
            isLastPage: false,
        };

        var ctrl = this;

        if ((typeof (InitData) == 'object') && (InitData != null)) {
            this.state = {...InitData, loading: false};
        }

        this.handleChangeOrigin = (origin) => {
            this.state = {...this.state, origin};
            this.updateList(1);
        };

        this.handleNextPage = event => {
            event.preventDefault();
            this.updateList(this.state.currentPage + 1);
        };

        this.handlePrevPage = event => {
            event.preventDefault();
            this.updateList(this.state.currentPage - 1);
        };

        this.updateList = (page) => {
            this.state = {...this.state, isLoading: true};
            $http.post(
                Routing.generate('aw_promotions_flight_deals'),
                $.param({
                    page,
                    origin: this.state.origin,
                }),
                { headers: {'Content-Type': 'application/x-www-form-urlencoded'} }
            )
                .then(response => response.data)
                .then(({list, currentPage, isLastPage}) => {
                    this.state = { ...this.state, list, currentPage, isLastPage, isLoading: false };
                })
                .catch(function (e) {
                    this.state = {...this.state, isLoading: false};
                });
        };

        $('#origin_airport').autocomplete({
            minLength: 3,
            delay: 500,
            source: function (request, response) {
                if (request.term && request.term.length >= 3) {
                    var self = this;
                    $.get(Routing.generate("find_airport", {query: request.term}), function (data) {
                        $(self.element).removeClass('loading-input');
                        $(self.element).data('list', data);
                        response(data.map(function (item) {
                            return {
                                label: item.airname,
                                value: item.aircode,
                                city: item.cityname,
                                country: item.countryname
                            };
                        }));
                    })
                }
            },
            create: function () {
                $(this).data('ui-autocomplete')._renderItem = function (ul, item) {
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
            select: function (event, ui) {
                ctrl.handleChangeOrigin(ui.item.value);
            },
            search: function (event, ui) {
                if ($(event.target).val().length >= 3) {
                    $(event.target).addClass('loading-input');
                } else {
                    $(event.target).removeClass('loading-input');
                }
            },

        }).off('blur');

    }]);
});
