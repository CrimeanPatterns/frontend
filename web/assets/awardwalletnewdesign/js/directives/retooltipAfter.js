define(["angular-boot", "lib/customizer"], function (angular, customizer) {
  var timer;
  angular = angular && angular.__esModule ? angular.default : angular;
  angular.module("retooltip-after-directive", []).directive("retooltipAfter", [
    "retooltipAfter",
    function ($timeout) {
      return {
        restrict: "A",
        scope: true,
        link: function (scope, el, attrs) {
          if (timer) {
            $timeout.cancel(timer);
          }

          timer = $timeout(function () {
            customizer.initTooltips();
          }, 100);
        },
      };
    },
  ]);
});
