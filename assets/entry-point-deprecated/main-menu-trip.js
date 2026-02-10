import $ from 'jquery-boot';
import 'routing';
import importCalendar from 'pages/timeline/calendarImport';

function updateHref() {
    $('a#list-visited-countries').attr(
        'href',
        Routing.generate('timeline_export_countries', {
            agentId: $('ul.persons li.active [data-id]').data('id'),
        }),
    );
}

$(function () {
    updateHref();

    $(window).on('person.activate', function (event, id) {
        updateHref();
    });

    var importCalendarLinkElem = document.getElementById('importCalendar');

    importCalendarLinkElem.addEventListener('click', function (e) {
        e.preventDefault();
        importCalendar();
    });
});
