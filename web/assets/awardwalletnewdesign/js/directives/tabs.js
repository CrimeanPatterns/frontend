define(["angular", "jquery-boot", "filters/unsafe"], function (angular, $) {
  angular = angular && angular.__esModule ? angular.default : angular;

  /**
   * @typedef {Object} Tabs
   */

  /**
   * @typedef {Object} Pane
   * @property {string} title
   * @property {string} paneId
   * @property {boolean} enable
   */

  angular
    .module("tabs-directive", ["unsafe-mod"])
    .directive("tabs", function () {
      return {
        restrict: "AE",
        transclude: true,
        scope: {
          id: "=",
        },
        /**
         * @param {Tabs} $scope
         * @param $timeout
         * @param $rootScope
         */
        controller: [
          "$scope",
          "$timeout",
          "$rootScope",
          function ($scope, $timeout, $rootScope) {
            var ctrl = this;
            $scope.panes = [];
            $scope.showTabs = true;
            $scope.select = function (pane) {
              angular.forEach($scope.panes, function (pane) {
                pane.selected = false;
              });
              pane.selected = true;
              $timeout(function () {
                var eventName = "tabs.select";
                if ($scope.id) {
                  eventName = eventName + "." + $scope.id;
                }
                $rootScope.$broadcast(eventName, pane);
              });
            };

            this.addPane = function (pane) {
              $scope.panes.push(pane);
            };
            this.getPane = function (id) {
              for (var i = 0; i < $scope.panes.length; i++) {
                if ($scope.panes[i].paneId == id) return $scope.panes[i];
              }
              return null;
            };

            var eventName = "tabs.update";
            if ($scope.id) {
              eventName = eventName + "." + $scope.id;
            }
            $scope.$on(eventName, function (event, callback) {
              $timeout(function () {
                callback(ctrl, $scope);
              });
            });
          },
        ],
        template:
          '<div class="item">' +
          '<ul data-ng-cloak class="tabs-navigation small" data-ng-if="showTabs">' +
          '<li data-ng-repeat="pane in panes" data-ng-if="pane.enable == 1">' +
          '<a href="" data-ng-class="{active: pane.selected}" data-ng-click="select(pane)" data-ng-bind-html="pane.title | unsafe"></a>' +
          "</li>" +
          "</ul>" +
          '<div class="tabs-content" data-ng-transclude></div>' +
          "</div>",
      };
    })
    .directive("pane", function () {
      return {
        require: "^tabs",
        restrict: "AE",
        transclude: true,
        scope: {
          title: "@",
          paneId: "@",
          enable: "@",
        },
        /**
         *
         * @param {Pane} scope
         * @param element
         * @param attrs
         * @param tabsCtrl
         */
        link: function (scope, element, attrs, tabsCtrl) {
          tabsCtrl.addPane(scope);
        },
        template: '<div data-ng-show="selected" data-ng-transclude></div>',
      };
    });
});
