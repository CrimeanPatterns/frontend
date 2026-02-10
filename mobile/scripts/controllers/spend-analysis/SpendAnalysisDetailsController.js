angular.module('AwardWalletMobile').controller('SpendAnalysisDetailsController', [
    '$scope',
    '$state',
    'SpendAnalysis',
    '$stateParams',
    function ($scope, $state, SpendAnalysis, $stateParams) {
        const {formData, merchant, title} = $stateParams;

        if (_.isEmpty(formData) || _.isEmpty(merchant) || _.isEmpty(title)) {
            return $state.go('index.spend-analysis.overview');
        }

        $scope.title = title;
        $scope.spinnerLoadingPage = true;

        SpendAnalysis.getByMerchant(merchant, formData).then(spendAnalysisData => {
            const {rows} = spendAnalysisData;

            $scope.spinnerLoadingPage = false;

            $scope.rows = rows;
        }, () => {
            $scope.spinnerLoadingPage = false;
        });
    }
]);