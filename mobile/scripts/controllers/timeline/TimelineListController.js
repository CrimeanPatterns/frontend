angular.module('AwardWalletMobile').controller('TimelineListController', ['travelerId', '$scope', '$state', 'Timeline',
    function (travelerId, $scope, $state, Timeline) {
        var needMore = false;
        var displayLimit = 25, pageSize = 5, startIndex = 0, originalLength = 0, pastTravelOpened = false;

        function getList() {
            return Timeline.getList(travelerId);
        }

        function getStartIndex(list, date) {
            var date1 = new Date(date.getFullYear(), date.getMonth(), date.getDate(), 0, 0, 0, 0).getTime(),
                startIndex = null,
                hasFuture = false;
            for (var i = list.length - 1, item; i >= 0, item = list[i]; i--) {
                var startDate = new Date(item.startDate.ts * 1000).getTime();
                hasFuture = hasFuture || startDate >= date1;
                if (
                    (startDate >= date1) ||
                    item.breakAfter == false
                ) {
                    startIndex = i;
                }

                if (
                    startIndex &&
                    !(startDate >= date1) &&
                    (!item.hasOwnProperty('breakAfter') || item.breakAfter == true)
                ) {
                    break;
                }
            }
            return startIndex !== null && hasFuture ? startIndex : list.length;
        }

        function pastTravel() {
            var counter = 1, items = [];
            if (list.items.length > 0) {
                for (var i = list.items.length - 1, item; i >= 0, item = list.items[i]; i--) {
                    items.push(item);
                    list.items.splice(i, 1);
                    counter++;
                    if (
                        counter > pageSize &&
                        (item.type == 'planStart' || item.type == 'date') &&
                        (i > 0 && list.items[i - 1].type != 'planStart')
                    ) {
                        break;
                    }
                }
                items.reverse();
                Array.prototype.unshift.apply($scope.view.list, items);
                items = null;
                item = null;
            }
        }

        var list = getList();

        if (!list || !list.items) {
            list = {};
            list.items = [];
            list.needMore = false;
            list.itineraryForwardEmail = false;
        } else {
            startIndex = getStartIndex(list.items, new Date());
            originalLength = list.items.length;
            needMore = list.needMore;
        }

        $scope.view = {
            itineraryForwardEmail: list.itineraryForwardEmail,
            loading: false,
            displayLimit: displayLimit,
            list: list.items.splice(startIndex),
            pastTravel: function () {
                var lastSegment = $scope.view.list[1];
                pastTravelOpened = true;
                if (list.items.length < 1 && needMore) {
                    $scope.view.loading = true;
                    Timeline.chunked(travelerId, lastSegment.startDate.ts).then(function (data) {
                        list.items = data.items;
                        needMore = data.needMore;
                        pastTravel();
                        $scope.view.loading = false;
                    });
                } else {
                    pastTravel();
                }
                $scope.view.showPastTravel = needMore || list.items.length > 0;
            },
            progressiveLoad: function () {
                if (($scope.view.list.length - 1) >= $scope.view.displayLimit)
                    $scope.view.displayLimit += 5;
            },
            showPastTravel: needMore || list.items.length > 0,
            urls: {
                adding: $state.href('index.accounts.add'),
                updating: $state.href('index.accounts.list')
            }
        };

        $scope.$on('timeline:update', function () {
            var newList = getList();
            if (newList && newList.items) {
                var newListLength = newList.items.length, cutDate = new Date();
                if (!pastTravelOpened) {
                    var startIndex = getStartIndex(newList.items, cutDate);
                    $scope.view.list = newList.items.splice(startIndex);
                } else {
                    if ($scope.view.list.length > 0) {
                        var item = $scope.view.list[1], length = $scope.view.list.length - originalLength;
                        $scope.view.list = $scope.view.list.splice(length <= 0 ? originalLength : length);
                        if ($scope.view.list.length > 0) {
                            item = $scope.view.list[$scope.view.list.length - 1];
                        }
                        cutDate = new Date(item.startDate.ts * 1000);
                        item = length = null;
                    }
                    $scope.view.list = $scope.view.list.concat(newList.items.splice(getStartIndex(newList.items, cutDate)));
                }
                originalLength = newListLength;
                list.items = newList.items;
                needMore = newList.needMore;
                cutDate = null;
                newListLength = null;
            }
        });

        $scope.$on('$destroy', function () {
            Timeline.clean();
        });
    }
]);