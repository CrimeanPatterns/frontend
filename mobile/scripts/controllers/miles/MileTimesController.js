angular.module('AwardWalletMobile').controller('MileTimesController', [
    '$scope',
    '$http',
    '$state',
    function ($scope, $http, $state) {

        $scope.navigate = (stateName) => {
            $state.go(stateName, {}, {skipHistory: true});
        };

        $scope.getData = (kind = 'transfer') => {
            return $http.post(`/mile-transfers/data/${kind}`);
        };

        $scope.getList = (data) => {
            const list = [];

            data.forEach((item) => {
                const {program, to} = item;

                if (_.isArray(to)) {
                    list.push({kind: 'title', program});
                    list.push(...to.map((item) => ({...item, kind: 'row'})));
                }else{
                    list.push( {
                        ...item,
                        kind: 'row'
                    })
                }
            });

            return list;
        };

        $scope.filterData = (data, query) => {
            const result = [];

            if (_.isArray(data) && _.isEmpty(data) === false) {

                data.forEach(item => {
                    let row = {...item};
                    const {program, to} = row;

                    if (program.toLowerCase().includes(query.toLowerCase())) {
                        result.push(row);
                    } else if (_.isArray(to)) {
                        const filtered = $scope.filterData(to, query);

                        if (_.isArray(filtered) && _.isEmpty(filtered) === false) {
                            row.to = filtered;
                            result.push(row);
                        }
                    }
                });

                return result;
            } else {
                return result;
            }
        };

    }
]);
