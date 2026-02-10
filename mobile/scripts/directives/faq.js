angular.module('AwardWalletMobile').directive('faq', [function () {
    return {
        restrict: 'E',
        scope: {
            data: '='
        },
        template: '<div class="faq-wrap" ng-bind-html="html"></div>',
        link: function (scope, element) {
            var data = scope.data, html = '';

            data.forEach(function (row) {
                html += '<div class="question" id="' + row.id + '"><div class="question__arrow"></div>';
                html += row.question;
                html += '</div>';
                html += '<div class="answer">';
                html += row.answer;
                html += '</div>';

            });

            element.bind('click', function (event) {

                if (event.target && event.target.href) {
                    window.open(event.target.href, '_blank');
                    event.preventDefault();
                    event.stopPropagation();
                }
            });

            scope.html = html;
        }
    };
}]);