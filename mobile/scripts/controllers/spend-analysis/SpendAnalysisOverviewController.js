angular.module('AwardWalletMobile').controller('SpendAnalysisOverviewController', [
    '$scope',
    '$state',
    'SpendAnalysis',
    'Form',
    function ($scope, $state, SpendAnalysis, Form) {
        $scope.reloadOverview = formData => {
            $scope.spinnerLoadingPage = true;

            SpendAnalysis.get(formData).then(spendAnalysisData => {
                const {form, analysis} = spendAnalysisData;

                $scope.spinnerLoadingPage = false;
                $scope.form = form;

                if (!formData && _.isObject(form) && _.isArray(form.children)) {
                    $scope.formData = Form.parseData(form.children);
                } else {
                    $scope.formData = formData;
                }

                $scope.data = analysis;
            }, () => {
                $state.go('index.accounts.list');
            });
        };

        $scope.reloadOverview();
    }
]);