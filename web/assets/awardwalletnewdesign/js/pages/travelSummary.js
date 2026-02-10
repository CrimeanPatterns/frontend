/*
global
whenRecaptchaLoaded,
renderRecaptcha,
whenRecaptchaSolved
*/

define([
    'lib/customizer',
    'angular-boot',
    'jquery-boot',
    'directives/dialog',
    'directives/customizer',
    'filters/unsafe'
], function (customizer, angular, $) {
    angular = angular && angular.__esModule ? angular.default : angular;

    let Data;
    let StatTabs;

    function createPopupClass() {
        function Popup(content) {
            this.containerDiv = document.createElement('div');
            this.containerDiv.classList.add('popup-container');
            this.containerDiv.style.visibility = 'hidden';
            if (content) {
                this.containerDiv.appendChild(content);
            }
            window.google.maps.OverlayView.preventMapHitsAndGesturesFrom(this.containerDiv);
        }

        Popup.prototype = Object.create(window.google.maps.OverlayView.prototype);

        Popup.prototype.hide = function() {
            this.containerDiv.style.visibility = 'hidden';
        };
        Popup.prototype.show = function() {
            this.containerDiv.style.visibility = 'visible';
        };

        /** Called when the popup is added to the map. */
        Popup.prototype.onAdd = function() {
            this.getPanes().floatPane.appendChild(this.containerDiv);
        };

        Popup.prototype.setPosition = function(position) {
            this.position = position;
            this.draw();
        }

        Popup.prototype.draw = function() {
            if (!this.position) return;

            const popupPosition = this.getProjection().fromLatLngToDivPixel(this.position);
            this.containerDiv.style.left = popupPosition.x + 'px';
            this.containerDiv.style.top = popupPosition.y + 'px';
        };

        return Popup;
    }

    const app = angular.module("travelSummaryApp", [
        'appConfig', 'unsafe-mod', 'dialog-directive', 'customizer-directive'
    ]);

    app.config([
        '$injector',
        function ($injector) {
            if ($injector.has('data')) {
                Data = $injector.get('data');
            }
            if ($injector.has('statTabs')) {
                StatTabs = $injector.get('statTabs');
            }
        }
    ]);

    app.controller('mainCtrl', [
        '$scope', '$http', '$timeout', 'dialogService', function ($scope, $http, $timeout, dialogService) {
            const PERIOD_YEAR_TO_DATE = 1;
            const PERIOD_LAST_YEAR = 2;
            const PERIOD_LAST_3_YEARS = 3;
            const PERIOD_LAST_5_YEARS = 4;
            const PERIOD_LAST_10_YEARS = 5;
            const PERIOD_ALL_TIME = 10;

            var main = this;
            window.TravelSummary = this;
            /**
             * @property {Object} dep coordinates of the point of departure
             * @property {Object} arr coordinates of the point of arrival
             */
            main.currentRoute = null;

            this.state = {
                isLoading: true,
                noData: false,
                activeTab: 'airports',
                isLoaded: false,
                currentPeriod: null,
                currentUser: null,
                showMapPopup: false
            };
            this.map;
            this.popup;
            this.statTabs;
            this.exportVisitedCountriesLink;

            main.directionsService = new window.google.maps.DirectionsService();
            main.directionsRenderer = new window.google.maps.DirectionsRenderer({
                suppressInfoWindows: true,
                suppressMarkers: true
            });

            if ((typeof (Data) === 'object') && (Data !== null)) {
                this.state = {...this.state, ...Data, isLoading: false, isLoaded: true};
            }
            if ((StatTabs !== null)) {
                this.statTabs = {...StatTabs};
            }

            this.getStatTabs = () => {
                return Object.keys(this.statTabs);
            }

            this.init = () => {
                if (this.state.noData) return;

                const Popup = createPopupClass();
                this.popup = new Popup(document.getElementById('map-modal'));
                this.drawRoutesMap();
                this.drawRegionsMap();
            }

            this.drawRegionsMap = function() {
                $('svg path').css('fill', 'rgb(255,255,255)');

                if (!this.state.countries || !this.state.countries.length) return;

                const { value: countriesMax } = this.state.countries[0];
                const { value: countriesMin } = this.state.countries[this.state.countries.length - 1];

                let r, g, b;
                this.state.countries.forEach(({key, title, value}) => {
                    if (countriesMax > countriesMin) {
                        r = this.transformValue(value, countriesMin, countriesMax, 255 * 0.32, 38);
                        g = this.transformValue(value, countriesMin, countriesMax, 255 * 0.62, 74);
                        b = this.transformValue(value, countriesMin, countriesMax, 255, 119);
                    } else {
                        [r, g, b] = [38, 74, 119];
                    }

                    $(`path#${key}`).css('fill', `rgb(${r},${g},${b})`);
                })
            }

            this.drawRoutesMap = function() {
                let lat, lng;
                if (this.state.airports.length > 0) {
                    lat = this.state.airports[0].payload.lat;
                    lng = this.state.airports[0].payload.lng;
                } else {
                    lat = this.state.reservations[0].payload.lat;
                    lng = this.state.reservations[0].payload.lng;
                }

                this.map = new window.google.maps.Map(
                    document.getElementById('map_div'),
                    {
                        zoom: 4,
                        center: {lat: parseFloat(lat), lng: parseFloat(lng)},
                        mapTypeControl: false,
                        streetViewControl: false,
                        rotateControl: false,
                        fullscreenControl: false,
                        styles: [
                            {
                                "featureType": "landscape",
                                "elementType": "geometry.fill",
                                "stylers": [
                                    {"color": "#c7daed"}
                                ]
                            },
                            {
                                "featureType": "landscape",
                                "elementType": "labels.text.fill",
                                "stylers": [
                                    {"color": "#264a77"}
                                ]
                            },
                            {
                                "featureType": "landscape",
                                "elementType": "geometry.stroke",
                                "stylers": [
                                    {"color": "#fff"}
                                ]
                            }
                        ]
                    }
                );

                this.popup.setMap(this.map);
                this.directionsRenderer.setMap(this.map);

                const markers = this.state.airports.map((markerItem) => {
                    const {payload: {lat, lng}, title} = markerItem;
                    let icon = 'icon/travel-summary/markers/' + markerItem.payload.category + '.svg';

                    const marker = new window.google.maps.Marker({
                        position: {
                            lat: parseFloat(lat),
                            lng: parseFloat(lng)
                        },
                        title,
                        icon: {
                            url: '/assets/awardwalletnewdesign/img/' + icon,
                            scaledSize: new window.google.maps.Size(45, 45),
                        },
                        // map: this.map
                    });
                    marker.addListener('click', () => {
                        this.handleMapMarkerClick(markerItem);
                    });

                    return marker;
                });

                const reservations = this.state.reservations.map((markerItem) => {
                    const {payload: {lat, lng}, title} = markerItem;
                    let icon = 'icon/travel-summary/markers/' + markerItem.payload.category + '.svg';

                    const marker = new window.google.maps.Marker({
                        position: {
                            lat: parseFloat(lat),
                            lng: parseFloat(lng)
                        },
                        title,
                        icon: {
                            url: '/assets/awardwalletnewdesign/img/' + icon,
                            scaledSize: new window.google.maps.Size(45, 45)
                        }
                    });
                    marker.addListener('click', () => {
                        this.handleMapMarkerClick(markerItem);
                    });

                    return marker;
                });

                const markerCluster = new window.MarkerClusterer(
                    this.map,
                    markers.concat(reservations),
                    {
                        imagePath: 'https://developers.google.com/maps/documentation/javascript/examples/markerclusterer/m'
                    }
                );
                if (this.state.routes && this.state.routes.length > 0) {
                    let i = 0;
                    const interval = setInterval(() => {

                        const {arr, dep} = this.state.routes[i];
                        const flightPath = new window.google.maps.Polyline({
                            path: [
                                {lat: parseFloat(dep.lat), lng: parseFloat(dep.lng)},
                                {lat: parseFloat(arr.lat), lng: parseFloat(arr.lng)}
                            ],
                            geodesic: true,
                            strokeColor: '#f00',
                            strokeOpacity: 0.8,
                            strokeWeight: 1,
                            map: this.map
                        });

                        i++;
                        if (i >= this.state.routes.length) clearInterval(interval);
                    }, 50)
                }
                
            }

            /**
             * Draw the route on a map.
             *
             * @param {Object} direction route for the trip
             * @param {Object} direction.dep geographic coordinates of the point of departure
             * @param {Object} direction.arr geographic coordinates of the point of arrival
             * @param {Array} direction.waypoints list of waypoints
             */
            this.renderRoute = function (direction) {
                let waypoints = [];
                direction.waypoints.forEach(function (waypoint, i) {
                    waypoints[i] = {
                        location: new window.google.maps.LatLng({lat: parseFloat(waypoint.lat), lng: parseFloat(waypoint.lng)}),
                        stopover: true
                    };
                });

                let request = {
                    origin: new window.google.maps.LatLng({lat: parseFloat(direction.dep.lat), lng: parseFloat(direction.dep.lng)}),
                    destination: new window.google.maps.LatLng({lat: parseFloat(direction.arr.lat), lng: parseFloat(direction.arr.lng)}),
                    travelMode: window.google.maps.TravelMode.DRIVING
                };
                if (waypoints.length > 0) {
                    request.waypoints = waypoints;
                }

                if (
                    (main.currentRoute === null) ||
                    (main.currentRoute.dep.lat !== direction.dep.lat && main.currentRoute.dep.lng !== direction.dep.lng &&
                    main.currentRoute.arr.lat !== direction.arr.lat && main.currentRoute.arr.lng !== direction.arr.lng)
                ) {
                    main.currentRoute = {dep: direction.dep, arr: direction.arr};
                    main.directionsService.route(request, function(result, status) {
                        if (status === window.google.maps.DirectionsStatus.OK) {
                            main.directionsRenderer.setDirections(result);
                        }
                    });
                }
            }

            this.handleTabChange = function(e, activeTab) {
                this.state = {...this.state, activeTab}
                e.preventDefault();
            }

            this.handlePopupClose = function(e) {
                main.currentRoute = null;
                main.directionsRenderer.setDirections({routes: []});
                this.popup.hide();
                e.preventDefault();
            }

            /**
             * Get the content for the duration of the trip segment.
             *
             * @param {string} category
             * @param {Object} segment
             * @param {(string|null)} segment.duration
             * @returns {string}
             */
            this.getDurationText = function(category, segment) {
                let term = null;
                switch (category) {
                    case 'hotel':
                    case 'parking':
                        term = segment.duration;
                        break;
                    case 'rental':
                        term = (segment.prefix === 'PU') ?
                            Translator.trans(/** @Desc("rental pick up for %duration%") */ 'travel-summary.modal.rental-pick-up', {duration: segment.duration}) :
                            Translator.trans(/** @Desc("rental drop off") */ 'travel-summary.modal.rental-drop-off');
                        break;
                    case 'restaurant':
                    case 'meeting':
                    case 'show':
                    case 'event':
                    case 'conference':
                    case 'rave':
                        term = (segment.duration !== null) ?
                            Translator.trans(/** @Desc("for %duration%") */ 'travel-summary.modal.event', {duration: segment.duration}) :
                            '';
                        break;
                }

                return (term !== null) ? term : '';
            }

            /**
             * Get a comparison of the number of flights, bus trips, train trips, etc., compared to the previous year.
             *
             * @param {number} previousValue
             * @param {number} year
             * @param {(string|null)} formattedValue
             * @returns {string}
             */
            this.getComparedToText = function(previousValue, year, formattedValue = null) {
                if (previousValue !== 0) {
                    let sign = previousValue > 0 ? '+' : '';
                    if (formattedValue !== null) {
                        previousValue = formattedValue;
                    }

                    return sign + previousValue + ' ' + Translator.trans(/** @Desc("compared to") */ 'trips.compared-to', {}, 'trips') + ' ' + year;
                } else {
                    return Translator.trans(/** @Desc("same as") */ 'same-as', {}, 'trips') + ' ' + year;
                }
            }

            this.currentPeriod = function (val) {
                if (typeof(val) === 'undefined') {
                    return this.state.currentPeriod;
                }

                if (!this.state.isAwPlus && ![PERIOD_YEAR_TO_DATE, PERIOD_LAST_YEAR].includes(parseInt(val))) {
                    this.createAwPlusDialog();
                } else {
                    this.state.currentPeriod = val;
                    this.handleChange();
                }
            }

            this.handleChange = function() {
                this.state.isLoading = true;
                main.currentRoute = null;
                main.directionsRenderer.setDirections({routes: []});
                if(history) {
                    history.pushState(
                        {},
                        '',
                        Routing.generate('aw_travel_summary_period_agent', {
                            period: this.state.currentPeriod,
                            agentId: this.state.currentUser
                        }));
                }
                $http.get(
                    Routing.generate('aw_travel_summary_data', { period: this.state.currentPeriod, agentId: this.state.currentUser })
                ).then(
                    res => {
                        const data = res.data;
                        this.state = {...this.state, ...data, isLoading: false};
                        this.init();
                        this.updateExportVisitedCountriesLink(this.state.currentUser, this.state.currentPeriod);
                    }
                ).finally(
                    () => { this.state.isLoading = false; }
                );

            }

            this.updateExportVisitedCountriesLink = function(agentId, period) {
                const now = new Date();
                let after;
                let before = null;

                if (agentId === '') {
                    agentId = null;
                }

                const utc = (year) => Date.UTC(year, 0, 1) / 1000;

                if (parseInt(period) === PERIOD_LAST_YEAR) {
                    before = utc(now.getFullYear());
                }

                let mainPeriods = {
                    [PERIOD_YEAR_TO_DATE]: utc(now.getFullYear()),
                    [PERIOD_LAST_YEAR]: utc(now.getFullYear() - 1),
                    [PERIOD_LAST_3_YEARS]: utc(now.getFullYear() - 3),
                    [PERIOD_LAST_5_YEARS]: utc(now.getFullYear() - 5),
                    [PERIOD_LAST_10_YEARS]: utc(now.getFullYear() - 10),
                    [PERIOD_ALL_TIME]: utc(2004)
                };
                if (period in mainPeriods) {
                    after = mainPeriods[period];
                } else if (period.length === 4 && parseInt(period) < now.getFullYear() - 1 && parseInt(period) >= 2004) {
                    after = utc(parseInt(period));
                    before = utc(parseInt(period) + 1);
                } else {
                    after = mainPeriods[PERIOD_YEAR_TO_DATE];
                }

                this.exportVisitedCountriesLink = Routing.generate('timeline_export_countries', {
                    agentId, after, before
                });
            };

            this.handleMapMarkerClick = function(markerItem) {
                const {payload: {lat, lng}} = markerItem;
                this.state = {...this.state, activeAirport: {...markerItem}, showMapPopup: true, airport: (markerItem.payload.airportTitle !== undefined), category: markerItem.payload.category}
                $scope.$apply()
                this.popup.setPosition(new window.google.maps.LatLng(parseFloat(lat), parseFloat(lng)));
                this.popup.show();

                if (
                    ['rental', 'bus', 'train', 'ferry', 'transfer'].includes(markerItem.payload.category) &&
                    markerItem.payload.directions.length > 0
                ) {
                    let direction = markerItem.payload.directions[0];
                    this.renderRoute(direction);
                }
            }

            this.transformValue = function(value, sourceMin, sourceMax, destMin, destMax) {
                return (value - sourceMin) / (sourceMax - sourceMin) * (destMax - destMin) + destMin
            }

            this.createAwPlusDialog = function() {
                dialogService.fastCreate(
                    Translator.trans('please-upgrade'),
                    Translator.trans(
                      /** @Desc("Unfortunately, this feature is only available to AwardWallet Plus members, please see the %link_on%difference between AwardWallet Free and AwardWallet Plus accounts%link_off%") */
                      'aw-plus-feature-diff-faq',
                      {
                          link_on: '<a href=\'/faqs#21\' target=\'_blank\'>',
                          link_off: '</a>'
                      }
                    ),
                    true,
                    true,
                    [
                        {
                            text: Translator.trans('button.close'),
                            click: function () {
                                $(this).dialog('close');
                            },
                            'class': 'btn-silver'
                        },
                        {
                            text: Translator.trans("upgrade-now"),
                            click: function () {
                                window.open(Routing.generate('aw_users_pay', { ref: 215 }), '_blank');
                                $(this).dialog('close');
                            },
                            'class': 'btn-blue'
                        }
                    ],
                    500
                );
            };
            this.showMailboxPopup = function() {
                if (parseInt(this.state.mailboxes) > 0) {
                    return
                }

                dialogService.fastCreate(
                    Translator.trans(/** @Desc("Link Your Mailbox") */ 'link-your-mailbox'),
                    Translator.trans(/** @Desc("To view your travel summary, please link your mailbox, so that we can import your trips automatically.") */ 'link-your-mailbox-travel-summary', {}, 'trips'),
                    true,
                    true,
                    [
                        {
                            text: Translator.trans('button.close'),
                            click: function () {
                                $(this).dialog('close');
                            },
                            'class': 'btn-silver'
                        },
                        {
                            text: Translator.trans(/** @Desc("Link Mailbox") */ 'link-mailbox'),
                            click: function () {
                                window.open(Routing.generate('aw_usermailbox_view'), '_blank');
                                $(this).dialog('close');
                            },
                            'class': 'btn-blue'
                        }
                    ],
                    500
                );
            };

            this.updateExportVisitedCountriesLink(this.state.currentUser, this.state.currentPeriod);
        }
    ]);
});
