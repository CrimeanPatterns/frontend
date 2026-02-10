define(["angular-boot"], function (angular) {
  angular = angular && angular.__esModule ? angular.default : angular;

  angular.module("ellipsis", []).directive("simpleEllipsis", [
    "$timeout",
    "$window",
    function ($timeout, $window) {
      return {
        restrict: "A",
        scope: {
          loaded: "=simpleEllipsis",
        },
        link: function (scope, element, attrs) {
          var columnElement = element.children().eq(0);
          var originalText;

          var applyEllipsis = function () {
            originalText = originalText || columnElement.text();
            columnElement.text(originalText);
            while (columnElement.height() > element.height()) {
              columnElement.text(function (index, text) {
                return text.replace(/[^A-Za-zА-Яа-я]*\s(\S)*$/, "...");
              });
            }
          };

          scope.$watch("loaded", function () {
            $timeout(applyEllipsis, 300);
          });
          angular.element($window).bind("resize", applyEllipsis);
        },
      };
    },
  ]);
});
