define(['angular-boot'], function (angular) {
    angular = angular && angular.__esModule ? angular.default : angular;

    angular.module('ficoServiceModule', [])
        .factory('ficoService', ['$q', '$timeout', 'DI', function($q, $timeout, di) {
            
            var updateHandlers = {};
            var updatingAccounts = {};

            function createUpdateHandler(accountId) {
                return function() {
                    if (di.get('updater').isDone()) {
                        var accountElement = angular.element(document.getElementById('a' + accountId));
                
                        if (accountElement.length) {
                            var elementScope = accountElement.scope();
                            if (elementScope && elementScope.element) {
                                elementScope.element.view.update();
                                
                                if (!elementScope.$$phase) {
                                    elementScope.$apply();
                                }
                            }
                        }

                        updatingAccounts[accountId] = true;
                        
                        return true;
                    }
                    
                    return false;
                };
            }
            
            return {
                getUpdateHandler: function(accountId, subAccountId) {
                    if (!updateHandlers[accountId]) {
                        updateHandlers[accountId] = createUpdateHandler(accountId, subAccountId);
                    }
                    return updateHandlers[accountId];
                },
                
                isAccountUpdating: function(accountId) {
                    return !!updatingAccounts[accountId];
                },
                
                setAccountUpdating: function(accountId, isUpdating) {
                    updatingAccounts[accountId] = isUpdating;
                },
                
            };
        }]);
});
