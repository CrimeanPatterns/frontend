angular.module('AwardWalletMobile').service('Timeline', [
    '$rootScope',
    '$q',
    '$http',
    function ($rootScope, $q, $http) {
        var timeline = [], segmentsMap = {}, travelers = [], travelersIndex = {}, totals = 0, tempList = {};

        function getLength() {
            return timeline.length;
        }

        function setList(data) {
            if (!(data instanceof Object)) {
                throw new TypeError('Timeline.setList called on non-object');
            }
            if (data !== timeline) {
                timeline = data;
                buildMap();
                $rootScope.$broadcast('timeline:update');
            }
        }

        function getList(userAgentId) {
            var traveler = getTraveler(userAgentId);
            if (traveler) {
                return angular.copy(timeline[traveler.index]);
            } else {
                return null;
            }
        }

        function getSegment(id) {
            if (id && tempList[id]) {
                return tempList[id];
            }
            if (id && segmentsMap.hasOwnProperty(id)) {
                var segment = segmentsMap[id];
                return timeline[segment.parent].items[segment.index];
            }
            return null;
        }

        function buildMap() {
            var now = Date.now();
            segmentsMap = {};
            travelers = [];
            travelersIndex = {};
            totals = 0;
            for (var j = 0, k = timeline.length, parent; j < k, parent = timeline[j]; j++) {
                var traveler = {
                    index: j,
                    userAgentId: parent.userAgentId,
                    name: parent.name,
                    familyName: parent.familyName,
                    futureSegments: 0
                };
                for (var i = 0, l = timeline[j].items.length, segment; i < l, segment = timeline[j].items[i]; i++) {
                    if (segment.startDate && segment.startDate.ts && ['date', 'planStart', 'planEnd', 'layover'].indexOf(segment.type) === -1) {
                        var date = segment.startDate.ts * 1000;
                        if (new Date(date).getTime() > now) {
                            if (!traveler.firstFutureSegment) {
                                traveler.firstFutureSegment = segment.id;
                            }
                            traveler.futureSegments++;
                            totals++;
                        }
                    }
                    segmentsMap[segment.id] = {index: i, parent: j};
                }

                travelers.push(traveler);
                /* object userAgentId */
                travelersIndex[parent.userAgentId] = travelers.length - 1;
                /*travelers Order*/
            }
        }

        function setSegments(userAgentId, segments) {
            if (segments instanceof Array) {
                var traveler = getTraveler(userAgentId);
                Array.prototype.unshift.apply(timeline[traveler.index].items, segments);
                buildMap();
            } else {
                throw new TypeError('Timeline.setSegments called on non-array');
            }
        }

        function saveTemp(segments) {
            if (segments instanceof Array) {
                for (var i = 0, l = segments.length, segment; i < l, segment = segments[i]; i++) {
                    tempList[segment['id']] = segment;
                }
            } else {
                throw new TypeError('Timeline.setSegments called on non-array');
            }
        }

        function getTraveler(userAgentId) {
            if (travelersIndex.hasOwnProperty(userAgentId)) {
                return travelers[travelersIndex[userAgentId]];
            } else {
                return null;
            }
        }

        function chunked(userAgentId, timestamp) {
            var defer = $q.defer();
            if (timestamp) {
                $http(
                    {
                        method: 'get',
                        url: '/timeline/chunk/' + timestamp + '/' + userAgentId,
                        timeout: 30000
                    }).then(function (response) {
                    if (angular.isObject(response.data) && response.data.items) {
                        saveTemp(response.data.items);
                        defer.resolve(response.data);
                    }
                }, function () {
                    defer.reject();
                })
            } else {
                defer.reject();
            }
            return defer.promise;
        }

        function getSegmentsInRange(startDate, endDate, exceptType, userAgentId) {
            var list = getList(userAgentId || 'my'), output = [], now = Date.now();
            exceptType = ['date', 'planStart', 'planEnd'].concat(exceptType);
            if (
                list &&
                list.items &&
                list.items.length > 0
            )
                for (var i = 0, segments = list.items, l = segments.length, segment; i < l, segment = segments[i]; i++) {
                    if (
                        segment.startDate &&
                        segment.startDate.ts &&
                        exceptType.indexOf(segment.type) === -1
                    ) {
                        var segmentStartDate = new Date(segment.startDate.ts * 1000), segmentEndDate;
                        if (segment.endDate && segment.endDate.ts) {
                            segmentEndDate = new Date(segment.endDate.ts * 1000);
                        }
                        if (
                            (!segmentEndDate && segmentStartDate >= startDate) ||
                            (segmentEndDate && segmentEndDate >= now && segmentStartDate <= endDate)
                        ) {
                            output.push(segment);
                        }
                    }
                }
            return output;
        }

        function confirmChanged(segmentType, segmentId) {
            return $http({
                method: 'POST',
                url: '/timeline/confirm-changes/' + segmentType + "." + segmentId,
                timeout: 30000,
                globalError: false
            });
        }

        var _this = {
            getList: getList,
            getLength: getLength,
            setList: setList,
            getSegment: getSegment,
            setSegments: setSegments,
            getTravelers: function () {
                return angular.copy(travelers);
            },
            getTraveler: getTraveler,
            getFutureTrips: function () {
                return totals;
            },
            chunked: chunked,
            clean: function () {
                tempList = {};
            },
            destroy: function () {
                timeline = [];
                segmentsMap = {};
                travelers = [];
                travelersIndex = {};
                totals = 0;
                tempList = {};
            },
            getSegmentsInRange: getSegmentsInRange,
            confirmChanged: confirmChanged
        };

        $rootScope.$on('app:storage:destroy', _this.destroy);

        return _this;
    }
]);