define(["angular-boot", "dateTimeDiff"], function (angular, dateTimeDiff) {
  angular = angular && angular.__esModule ? angular.default : angular;

  var timer,
    timeout = new Date().getTime(),
    index,
    countdown,
    start,
    end;
  var countdownList = [];
  angular.module("time-remaining", []).directive("timeRemaining", function () {
    return {
      restrict: "A",
      link: function (scope, element, attrs) {
        countdownList.push(element);
        var cbTimer = function () {
          if (new Date().getTime() - timeout > 990) {
            countdownList.forEach(function (el) {
              countdown = dateTimeDiff.getTimeRemaining(
                new Date(el.data("expire") * 1000),
                new Date(),
              );

              if (0 >= countdown.total) {
                el.text("00:00:00");
                if (-1 !== (index = countdownList.indexOf(el)))
                  countdownList.splice(index, 1);
              } else {
                el.text(
                  (countdown.days
                    ? countdown.days +
                      " " +
                      Translator.transChoice("days", countdown.days) +
                      " " +
                      Translator.trans("and.text") +
                      " "
                    : "") +
                    ("0" + countdown.hours).slice(-2) +
                    ":" +
                    ("0" + countdown.minutes).slice(-2) +
                    ":" +
                    ("0" + countdown.seconds).slice(-2),
                );
              }
            });
            timeout = new Date().getTime();
          }

          if (countdownList.length) {
            timer = requestAnimationFrame(cbTimer);
          }
        };
        cancelAnimationFrame(timer);
        timer = requestAnimationFrame(cbTimer);
      },
    };
  });
});
