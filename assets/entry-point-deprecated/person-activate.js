import 'jquery-boot';
import RouterService from '@Bem/ts/service/router';

var countWithNull;
var showWithNull = false;

var isTimeline = [RouterService.generate('aw_timeline'), RouterService.generate('aw_timeline_html5')].includes(
    window.location.pathname,
);

if (isTimeline) {
    $(document).on('update.hidden.users', function () {
        countWithNull = 0;

        if (!showWithNull) {
            $('.js-persons-menu')
                .find('li')
                .each(function (id, el) {
                    if ($(el).find('.count').length) {
                        if (
                            $(el).find('.count').text() === '0' &&
                            !$(el).hasClass('active') &&
                            $(el).find('a[data-id=my]').length === 0
                        ) {
                            $(el).slideUp();
                            countWithNull++;
                        } else {
                            $(el).slideDown();
                        }
                    }
                });

            if (countWithNull) {
                $('#users_showmore').closest('li').slideDown();
            } else {
                $('#users_showmore').closest('li').slideUp();
            }
        }
    });

    $(document).on('click', '#users_showmore', function (e) {
        e.preventDefault();
        $('.js-persons-menu').find('li:hidden').slideDown();
        $('#users_showmore').closest('li').slideUp();
        showWithNull = true;
    });
}
$(window).on('person.activate', function (event, id) {
    let $persons = $('.js-persons-menu'),
        $person = null;
    if (!(id instanceof jQuery)) {
        if (-1 !== id.indexOf('_')) id = id.split('_')[1] || 'my';
        if ('' == id) id = 'my';
        $person = $persons.find('a[data-id="' + id + '"]');
        0 === $person.length ? ($person = $persons.find('a[data-agentid="' + id + '"]:first')) : null;
        0 === $person.length ? ($person = $persons.find('a[data-id="my"]')) : null;
    }
    if ($person instanceof jQuery) {
        $persons.children().removeClass('active');
        $persons.find('a span.count').removeClass('blue').addClass('silver');
        $person.parents('li').addClass('active');
        $person.find('span.count').removeClass('silver').addClass('blue');
        $(window).trigger('person.active', $($person).data('id'));
    }

    $(document).trigger('update.hidden.users');
});

$(window).on('persons.update', function (event, data) {
    var all = 0;
    for (var id in data) {
        var $count = $('.js-persons-menu')
            .find('a[data-id="' + id + '"]')
            .find('span.count');
        $count.text(data[id]);
        if (data[id] == 0) {
            $count.hide();
        } else {
            $count.show();
        }
        all += data[id];
    }

    // Accounts button counter
    // $('#account-btn-counter').text(all);

    // All button counter
    $('.js-persons-menu a.all span').text(all);
    $(document).trigger('update.hidden.users');
});
