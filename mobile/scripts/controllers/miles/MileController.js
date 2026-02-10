angular.module('AwardWalletMobile').controller('MileController', [
    '$scope',
    '$state',
    function ($scope, $state) {

        const isTransfer = $state.includes('^.transfer');
        const kind = isTransfer ? 'transfer' : 'purchase';

        $scope.list = null;
        $scope.view = {
            query: '',
            noResultsError: Translator.trans('no_results_found_with_criteria'),
            clear: () => {
                $scope.view.query = '';
                $scope.setInitialList();
            },
            search: (query) => {
                if (query && query.length >= 3) {
                    $scope.list = $scope.getList($scope.filterData(initialData, query));
                }else{
                    $scope.setInitialList();
                }
            }
        };

        let initialData = null;
        let initialList;

        $scope.setInitialList = () => {
            $scope.list = [...initialList];
        };

        $scope.getData(kind).then((response) => {
            let {data} = response;

            if (_.isArray(data)) {
                initialList = $scope.getList(data);
                initialData = data;
                $scope.setInitialList();
            }
        });
    }
]);