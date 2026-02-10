angular.module('AwardWalletMobile').service('SpendAnalysis', [
    '$http',
    '$q',
    function ($http, $q) {
        return {
            /**
             * Return spend analysis data {analysis, form}
             */
            get(formData) {
                const q = $q.defer();

                $http({
                    method: 'post',
                    url: '/account/spent-analysis/merchants/data',
                    data: formData,
                    timeout: 30000
                }).then(response => q.resolve(response.data), () => q.reject());

                return q.promise;
            },
            /**
             * Get merchant details
             */
            getByMerchant(merchant, formData) {
                const q = $q.defer();

                $http({
                    method: 'post',
                    url: '/account/spent-analysis/merchants/transactions/' + merchant,
                    data: formData,
                    timeout: 30000
                }).then(response => q.resolve(response.data), () => q.reject());

                return q.promise;
            }
        };
    }
]);