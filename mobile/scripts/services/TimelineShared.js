angular.module('AwardWalletMobile').service('TimelineShared', [
    '$filter',
    '$q',
    '$http',
    function ($filter, $q, $http) {
        var timeline = [];

        function getLength() {
            return timeline.length;
        }

        function setList(data) {
            if (!(data instanceof Object)) {
                throw new TypeError('TimelineShared.setList called on non-object');
            }
            if (data !== timeline) {
                timeline = data;
            }
        }

        function getList() {
            return timeline;
        }

        function getSegment(id) {
            if (id) {
               return $filter('filter')(timeline, {id: id}, true)[0];
            }
            return null;
        }

        function getShared(sharedKey) {
            var q = $q.defer();
            $http({
                method: 'GET',
                url: '/timeline/shared/' + sharedKey,
                timeout: 30000
            }).then(function (response) {
                if (response && response.data && angular.isArray(response.data)) {
                    timeline = response.data;
                    q.resolve();
                }else{
                    q.reject();
                }
            }, function(){
                q.reject();
            });
            return q.promise;
        }
        
        return {
            getList: getList,
            getLength: getLength,
            setList: setList,
            getSegment: getSegment,
            destroy: function () {
                timeline = [];
            },
            getShared: getShared
        };
    }
]);