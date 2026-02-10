define(["angular-boot", "webpack-ts/shim/ngReact"], function (angular) {
  angular = angular && angular.__esModule ? angular.default : angular;

  angular.module("react-components", ['react']).directive("fico", [
    "reactDirective",
    function (reactDirective) {
      return reactDirective(
        require("webpack/react-app/Components/AccountsList/Fico/Fico").default,['ficoAccounts']
      );
    },
  ]);
});
