angular.module('AwardWalletMobile').directive('detailsList', [function () {
    return {
        transclude: true,
        controller: function () {
            this.blocks = {};
        },
        link: function (scope, element, attrs, ctrl, transclude) {
            transclude(scope, function (clone) {
                element.append(clone);
            });
        }
    }
}]);
angular.module('AwardWalletMobile').directive('detailField', ['$http', '$templateCache', '$q', '$compile', '$parse', function ($http, $templateCache, $q, $compile, $parse) {
    return {
        require: '^^detailsList',
        scope: {
            type: '=',
            block: '=field',
            parent: '=',
            account: '='
        },
        link: function (scope, element, attrs, detailsListCtrl) {
            var getTemplate = function (templateUrl) {
                var deferred = $q.defer();
                var template = $templateCache.get(templateUrl);
                if (template) {
                    deferred.resolve(template);
                } else {
                    $http.get(templateUrl, {cache: $templateCache}).then(function (response) {
                        deferred.resolve(response);
                    }, function (reject) {
                        deferred.reject();
                    });
                }
                return deferred.promise;
            };

            scope.hasBlock = function (block) {
                return detailsListCtrl.blocks.hasOwnProperty(block);
            };

            scope.visible = function (exp, context) {
                return $parse(exp)(context);
            };

            function bind(obj, property, exp, context) {
                var fn = $parse(exp);
                scope.$watchCollection(function () {
                        return fn(context);
                    },
                    function updateFieldValue(value) {
                        obj[property] = value;
                    }
                );
            }

            if (scope.block.Val && scope.block.Val.Linked) {
                for (var prop in scope.block.Val) {
                    if (
                        scope.block.Val.hasOwnProperty(prop) &&
                        ['Linked', 'Custom'].indexOf(prop) === -1
                    ) {
                        bind(scope.block, prop, scope.block.Val[prop], scope.account);
                    }
                }
            }

            /**
             * @return {boolean}
             */
            function LastChangeDate(date) {
                var date1 = new Date(date), date2 = new Date();
                return (Math.abs(date2.getTime() - date1.getTime()) / 3600000) < 24;
            }

            if(scope.type === 'balance')
            {
                scope.block.lastChange = LastChangeDate(scope.account.LastChangeDate * 1000);
            }

            scope.$watch(function () {
                return scope.type;
            }, function (type) {
                getTemplate('templates/directives/details/' + type + '.html').then(function (html) {
                    detailsListCtrl.blocks[type] = detailsListCtrl.blocks[type] || 0;
                    detailsListCtrl.blocks[type]++;
                    element.html(html);
                    $compile(element.contents())(scope);
                });
            })
        }
    }
}])
;
