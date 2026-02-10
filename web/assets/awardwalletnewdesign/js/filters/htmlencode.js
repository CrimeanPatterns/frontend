define(["angular"], function (angular) {
  angular = angular && angular.__esModule ? angular.default : angular;
  angular.module("htmlencode-mod", []).filter("htmlencode", function () {
    return function (text) {
      text = text && text.toString();
      return $("<div/>").text(text).html();
    };
  });
});
