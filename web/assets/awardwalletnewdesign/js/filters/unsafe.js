define(["angular"], function (angular) {
  angular = angular && angular.__esModule ? angular.default : angular;
  angular.module("unsafe-mod", []).filter("unsafe", [
    "$sce",
    function ($sce) {
      return function (val) {
        return $sce.trustAsHtml(val);
      };
    },
  ]);
});
