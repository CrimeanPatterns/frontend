angular.module('AwardWalletMobile').controller('SpendAnalysisSettingsController', [
    '$scope',
    '$state',
    '$stateParams',
    'Form',
    function ($scope, $state, $stateParams, Form) {
        const {form} = $stateParams;

        if (_.isEmpty(form)) {
            return $state.go('index.spend-analysis.overview');
        }

        $scope.form = {
            "interface": null,
            "extension": null,
            fields: [],
            errors: [],
            submit() {
                $scope.reloadOverview(Form.parseData($scope.form.fields));
            }
        };

        if (form.hasOwnProperty('jsFormInterface')) {
            $scope.form.interface = form.jsFormInterface;
        }
        if (form.hasOwnProperty('jsProviderExtension')) {
            $scope.form.extension = form.jsProviderExtension;
        }
        if (form.hasOwnProperty('children')) {
            $scope.form.fields = form.children;
            $scope.form.errors = form.errors;
        }
        if (form.hasOwnProperty('error')) {
            $scope.form.errors = [form.error];
        }
    }
]);