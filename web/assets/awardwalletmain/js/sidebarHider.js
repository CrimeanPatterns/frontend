$(document).ready(function () {
    var leftbar = $('#leftBar');
    var closer = $('#menu-closer');
    var working = false;

    if (leftbar.length) {
        var content = $('div#content');

        function showSidebar() {
            if (working) return;
            working = true;
            window.setTimeout(function () {working = false;}, 100);
            leftbar.show();
            content.css('padding-left', '321px');
            closer.css('margin-left', '301px').removeClass('close-menu').addClass('open-menu');
        }

        function hideSidebar() {
            if (working) return;
            working = true;
            window.setTimeout(function () {working = false;}, 100);
            leftbar.hide();
            content.css('padding-left', '25px');
            closer.css('margin-left', '0').removeClass('open-menu').addClass('close-menu');
        }

        if (document.location.href.indexOf('awardBooking') < 0) {
            $(window).resize(function () {
                if ($(this).width() <= 1350) {
                    hideSidebar();
                } else {
                    showSidebar();
                }
            }).resize();
        } else {
            hideSidebar();
        }


        closer.click(function () {
            if (leftbar.is(':visible')) {
                hideSidebar();
            } else {
                showSidebar();
            }
        });
    } else {
        closer.detach();
    }


});

