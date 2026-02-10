define(["angular-boot"], function (angular) {
  angular = angular && angular.__esModule ? angular.default : angular;
  angular.module("highlight-mod", []).filter("highlight", function () {
    return function (text, search, caseSensitive, className) {
      if (text && (search || angular.isNumber(search))) {
        text = text.toString();
        search = $("<div/>").text(search.toString()).html();
        if (caseSensitive) {
          return text
            .split(search)
            .join('<span class="' + className + '">' + search + "</span>");
        } else {
          search = search.replace(/([.*+?^=!:${}()|\[\]\/\\])/g, "\\$1");
          var ret = text.replace(new RegExp(search, "gi"), "#$&#");
          ret = ret.replace(new RegExp("<.*?>", "gi"), function (str) {
            return str.replace(/#/g, "");
          });
          ret = ret.replace(
            new RegExp("#(.*?)#", "gi"),
            '<span class="' + className + '">$1</span>',
          );
          return ret;
        }
      } else {
        return text;
      }
    };
  });
});
