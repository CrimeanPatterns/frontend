angular.module('AwardWalletMobile').directive('storeLocation', [
    '$window',
    '$q',
    '$timeout',
    function ($window, $q, $timeout) {
        var counter = 0,
            prefix = 'gmap_',
            map;
        var REGEXP_LATLNG = /^([-+]?\d{1,2}[.]\d+),\s*([-+]?\d{1,3}[.]\d+)$/;
        var ZOOM_LEVEL = {
            WORLD: 1,
            LANDMASS: 5,
            CITY: 10,
            STREETS: 15,
            BUILDINGS: 20
        };

        return {
            restrict: 'E',
            scope: {
                field: '='
            },
            templateUrl: 'templates/directives/store-location.html',
            link: function (scope, element, attrs) {
                var placesService,
                    autocompleteService,
                    geocoder;
                var options = {
                        zoom: ZOOM_LEVEL.WORLD,
                        zoomControl: true,
                        zoomControlOptions: {
                            style: 2//google.maps.ZoomControlStyle.LARGE
                        },
                        center: {
                            lat: 0,
                            lng: 0
                        },
                        mapTypeControl: false,
                        streetViewControl: false,
                        rotateControl: false,
                        fullscreenControl: false,
                        clickableIcons: false,
                        styles: [
                            {
                                "featureType": "landscape.natural",
                                "elementType": "geometry",
                                "stylers": [
                                    {
                                        "color": "#dde2e3"
                                    },
                                    {
                                        "visibility": "on"
                                    }
                                ]
                            },
                            {
                                "featureType": "poi.park",
                                "elementType": "all",
                                "stylers": [
                                    {
                                        "color": "#c6e8b3"
                                    },
                                    {
                                        "visibility": "on"
                                    }
                                ]
                            },
                            {
                                "featureType": "poi.park",
                                "elementType": "geometry.fill",
                                "stylers": [
                                    {
                                        "color": "#c6e8b3"
                                    },
                                    {
                                        "visibility": "on"
                                    }
                                ]
                            },
                            {
                                "featureType": "road",
                                "elementType": "geometry.fill",
                                "stylers": [
                                    {
                                        "visibility": "on"
                                    }
                                ]
                            },
                            {
                                "featureType": "road",
                                "elementType": "geometry.stroke",
                                "stylers": [
                                    {
                                        "visibility": "off"
                                    }
                                ]
                            },
                            {
                                "featureType": "road",
                                "elementType": "labels",
                                "stylers": [
                                    {
                                        "visibility": "on"
                                    }
                                ]
                            },
                            {
                                "featureType": "road",
                                "elementType": "labels.text.fill",
                                "stylers": [
                                    {
                                        "visibility": "on"
                                    }
                                ]
                            },
                            {
                                "featureType": "road",
                                "elementType": "labels.text.stroke",
                                "stylers": [
                                    {
                                        "visibility": "on"
                                    }
                                ]
                            },
                            {
                                "featureType": "road.highway",
                                "elementType": "geometry.fill",
                                "stylers": [
                                    {
                                        "color": "#c1d1d6"
                                    },
                                    {
                                        "visibility": "on"
                                    }
                                ]
                            },
                            {
                                "featureType": "road.highway",
                                "elementType": "geometry.stroke",
                                "stylers": [
                                    {
                                        "color": "#a9b8bd"
                                    },
                                    {
                                        "visibility": "on"
                                    }
                                ]
                            },
                            {
                                "featureType": "road.local",
                                "elementType": "all",
                                "stylers": [
                                    {
                                        "color": "#f8fbfc"
                                    }
                                ]
                            },
                            {
                                "featureType": "road.local",
                                "elementType": "labels.text",
                                "stylers": [
                                    {
                                        "color": "#979a9c"
                                    },
                                    {
                                        "visibility": "on"
                                    },
                                    {
                                        "weight": 0.5
                                    }
                                ]
                            },
                            {
                                "featureType": "road.local",
                                "elementType": "labels.text.fill",
                                "stylers": [
                                    {
                                        "visibility": "on"
                                    },
                                    {
                                        "color": "#827e7e"
                                    }
                                ]
                            },
                            {
                                "featureType": "road.local",
                                "elementType": "labels.text.stroke",
                                "stylers": [
                                    {
                                        "color": "#3b3c3c"
                                    },
                                    {
                                        "visibility": "off"
                                    }
                                ]
                            },
                            {
                                "featureType": "water",
                                "elementType": "geometry.fill",
                                "stylers": [
                                    {
                                        "color": "#a6cbe3"
                                    },
                                    {
                                        "visibility": "on"
                                    }
                                ]
                            }
                        ]
                    },
                    currentLocationBounds;

                if (!platform.cordova) {
                    options.gestureHandling = 'cooperative';
                }
                var currentLocationDiv = document.createElement('div'),
                    currentLocationButton = document.createElement('div'),
                    currentLocationIcon = document.createElement('i');
                currentLocationDiv.className = 'gmap__location';
                currentLocationIcon.className = 'icon-current-location';
                currentLocationIcon.index = 1;
                currentLocationDiv.appendChild(currentLocationIcon);
                currentLocationButton.appendChild(currentLocationDiv);

                //scope.field.changed = false;
                scope.field.places = [];
                scope.field.onChange = function (value) {
                    if (REGEXP_LATLNG.test(value) && value) {
                        var coords = value.split(',');
                        changeMapLocation({lat: coords[0], lng: coords[1]}, true);
                        //scope.field.changed = true;
                    } else if (autocompleteService && value) {
                        autocompleteService.getPlacePredictions({
                            input: value,
                            bounds: currentLocationBounds
                        }, function (predictions, status) {
                            if (status === google.maps.places.PlacesServiceStatus.OK) {
                                scope.$applyAsync(function () {
                                    scope.field.places = predictions.map(function (predict) {
                                        if (
                                            predict.structured_formatting &&
                                            predict.structured_formatting.main_text_matched_substrings &&
                                            predict.structured_formatting.main_text_matched_substrings[0]
                                        ) {
                                            var matchedStr = predict.structured_formatting.main_text_matched_substrings[0];
                                            var textArray = predict.structured_formatting.main_text.split('');
                                            textArray.splice(matchedStr.offset, 0, '<mark>');
                                            textArray.splice(matchedStr.offset + matchedStr.length + 1, 0, '</mark>');
                                            var output = [];
                                            output.push(textArray.join(''));
                                            if (predict.structured_formatting.secondary_text)
                                                output.push('<span class="silver-text">' + predict.structured_formatting.secondary_text + '</span>');
                                            predict.formatted_description = output.join(', ');
                                        }
                                        return predict;
                                    });
                                });
                            } else {
                                scope.field.close();
                            }
                        });
                    } else {
                        scope.field.close();
                    }
                };

                scope.field.input = function (predict) {
                    if (placesService) {
                        getAddressLocation(predict.place_id).then(function (details) {
                            scope.field.value = '';
                            scope.field.placeholder = details.formatted_address;
                            //scope.field.changed = true;
                            if (details && details.geometry && details.geometry.location)
                                changeMapLocation(angular.extend(details.geometry.location.toJSON(), {name: scope.field.placeholder}), true);
                        });
                        $('.row-submit').get(0).scrollIntoView();//hack
                    }
                    scope.field.close();
                };

                scope.field.close = function () {
                    if (scope.field.places.length > 0) {
                        scope.$applyAsync(function () {
                            scope.field.places = [];
                        });
                    }
                };

                scope.field.blur = function () {
                    $timeout(function () {
                        scope.field.close();
                    }, 300);
                };

                function changeMapLocation(position, center) {
                    position.lat = parseFloat(parseFloat(position.lat).toFixed(6));
                    position.lng = parseFloat(parseFloat(position.lng).toFixed(6));

                    var location = new google.maps.LatLng(position.lat, position.lng);

                    if (center === void 0 || center) {
                        map.setCenter(location);
                        map.setZoom(ZOOM_LEVEL.STREETS + 2)
                    }

                    scope.$applyAsync(function () {
                        scope.field.attr.lat = position.lat;
                        scope.field.attr.lng = position.lng;
                        if (!position.name) {
                            scope.field.attr.name = scope.field.attr.lat + ', ' + scope.field.attr.lng;
                        } else {
                            scope.field.attr.name = position.name;
                        }
                    });

                    return position;
                }

                function getAddressLocation(placeId) {
                    var q = $q.defer();
                    placesService.getDetails(
                        {
                            placeId: placeId
                        },
                        function (details, status) {
                            if (status === google.maps.places.PlacesServiceStatus.OK)
                                q.resolve(details);
                            else
                                q.reject(details);
                        }
                    );
                    return q.promise;
                }

                function geocode(position) {
                    if (geocoder) {
                        geocoder.geocode({'location': position}, function (results, status) {
                            if (status === 'OK') {
                                if (results[0]) {
                                    scope.$applyAsync(function () {
                                        scope.field.placeholder = results[0].formatted_address;
                                        scope.field.attr.name = scope.field.placeholder;
                                    });
                                    return;
                                }
                            }
                            scope.$applyAsync(function () {
                                scope.field.placeholder = position.lat + ', ' + position.lng;
                            });
                        });
                    } else {
                        scope.$applyAsync(function () {
                            scope.field.placeholder = position.lat + ', ' + position.lng;
                        });
                    }
                }

                var initialize = function (mapOptions) {
                    autocompleteService = new google.maps.places.AutocompleteService();
                    placesService = new google.maps.places.PlacesService(document.createElement('input'));
                    geocoder = new google.maps.Geocoder;

                    map = new google.maps.Map(element[0].querySelector('.gmap'), mapOptions);
                    map.controls[google.maps.ControlPosition.TOP_RIGHT].push(currentLocationButton);

                    google.maps.event.addListener(map, 'dragend', function (event) {
                        var location = map.getCenter().toJSON(),
                            position = changeMapLocation(location, false);

                        geocode(position);
                    });

                    if (
                        scope.field.attr &&
                        scope.field.attr.lat &&
                        scope.field.attr.lng
                    ) {
                        changeMapLocation(scope.field.attr);
                        map.setZoom(ZOOM_LEVEL.STREETS + 2);
                        scope.$applyAsync(function () {
                            scope.field.placeholder = scope.field.attr.name;
                        });
                    }

                    function setCurrentPosition(position) {
                        var location = changeMapLocation(position, true);
                        map.setZoom(ZOOM_LEVEL.STREETS + 2);
                        geocode(location);
                    }

                    if (navigator.geolocation) {
                        navigator.geolocation.getCurrentPosition(function (position) {
                            var geolocation = {
                                lat: position.coords.latitude,
                                lng: position.coords.longitude
                            }, circle = new google.maps.Circle({
                                center: geolocation,
                                radius: position.coords.accuracy
                            });

                            if (
                                !(
                                    scope.field.attr &&
                                    scope.field.attr.lat &&
                                    scope.field.attr.lng
                                )
                            ) {
                                setCurrentPosition(geolocation)
                            }

                            currentLocationBounds = circle.getBounds();
                        });

                        currentLocationButton.addEventListener('click', function () {
                            currentLocationIcon.className = 'spinner';
                            navigator.geolocation.getCurrentPosition(function (position) {
                                currentLocationIcon.className = 'icon-current-location';
                                setCurrentPosition({
                                    lat: position.coords.latitude,
                                    lng: position.coords.longitude
                                });
                            }, function() {
                                currentLocationIcon.className = 'icon-current-location';
                            });
                        }, function () {
                            currentLocationIcon.className = 'icon-current-location';
                        });
                    } else {
                        currentLocationButton.style.display = 'none';
                    }
                };

                function injectGoogle(options) {
                    var cbId = prefix + counter;
                    $window[cbId] = function () {
                        initialize(options);
                    };
                    var wf = document.createElement('script');
                    wf.src = app.googleMapsEndpoints[0] + '&callback=' + cbId;
                    wf.type = 'text/javascript';
                    wf.async = true;
                    var s = document.getElementsByTagName('script')[0];
                    s.parentNode.insertBefore(wf, s);
                    ++counter;
                }

                if ($window.google && $window.google.maps) {
                    initialize(options);
                } else {
                    injectGoogle(options);
                }
            }
        };
    }]);