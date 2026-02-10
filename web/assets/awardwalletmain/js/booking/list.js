var selectWidth = "136";
document.write('<style type="text/css">input.booking-requests-select { display: none; } select.booking-requests-select { position: relative; width: ' + selectWidth + 'px; opacity: 0; filter: alpha(opacity=0); z-index: 5; } .disabled { opacity: 0.5; filter: alpha(opacity=50); }</style>');
var Custom = {
    init: function () {
        var span = Array(), textnode, option, active;

        inputs = document.getElementsByTagName("select");
        for (a = 0; a < inputs.length; a++) {
            if (inputs[a].className == "booking-requests-select") {
                option = inputs[a].getElementsByTagName("option");
                active = option[0].childNodes[0].nodeValue;
                textnode = document.createTextNode(active);
                for (b = 0; b < option.length; b++) {
                    if (option[b].selected == true) {
                        textnode = document.createTextNode(option[b].childNodes[0].nodeValue);
                    }
                }
                span[a] = document.createElement("span");
                span[a].className = "select";
                span[a].id = "select" + inputs[a].name;
                span[a].appendChild(textnode);
                inputs[a].parentNode.insertBefore(span[a], inputs[a]);
                if (!inputs[a].getAttribute("disabled")) {
                    inputs[a].onchange = Custom.choose;
                } else {
                    inputs[a].previousSibling.className = inputs[a].previousSibling.className += " disabled";
                }
            }
        }
        document.onmouseup = Custom.clear;
    },

    choose: function () {
        option = this.getElementsByTagName("option");
        for (d = 0; d < option.length; d++) {
            if (option[d].selected == true) {
                document.getElementById("select" + this.name).childNodes[0].nodeValue = option[d].childNodes[0].nodeValue;
            }
        }
    }
};

window.onload = Custom.init;

var AutoUpdateQueue = {
    table: null,
    url: null,
    working: false,
    timer: null,
    lastCheck: new Date(),
    init: function () {
        this.table = $('.js-queue-table');
        if (! this.table.length) return;
        this.url = this.table.data('update-url');
        if (! this.url) this.url = Routing.generate('aw_booking_json_getqueueupdates');
        this.start();
    },
    start: function () {
        var self = this;
        if (this.working == false) {
            this.working = true;
            this.timer = setTimeout(function () {self.update()}, 60*1000);
        }
    },
    stop: function () {
        if (this.working == true) {
            this.working = false;
            clearTimeout(this.timer);
            this.timer = null;
        }
        this.start();
    },
    update: function () {
        var self = this;
        $.ajax({
            url: self.url,
            data: {
                'lastCheck': self.lastCheck.toJSON()
            },
            success: function (data) {
                if ('queue' in data) {
                    var title = document.title;
                    title = title.replace(new RegExp('\\(\\d+\\)','i'), '(' + data.queue + ')');
                    document.title = title;
                    var link = $('#leftBar').find('a.item[href="/awardBooking/queue"] div');
                    if (link.length) {
                        title = link.html();
                        title = title.replace(new RegExp('\\(\\d+\\)','i'), '(' + data.queue + ')');
                        link.html(title);
                    }
                }
                if ('updates' in data) {
                    $.each(data.updates, function(i, requestid) {
                        self.table.find('tr[data-id="'+requestid+'"]').addClass('new');
                    });
                }
                self.lastCheck = new Date(data.currentCheck);
                self.stop();
            },
            fail: function () {
                self.stop();
            }
        });
    }
};

$(document).ready(function () {

    $('#find_btn').click(function () {
        var val = false;
        $('.booking-requests-text-fields').find('input:visible').each(function (e, ui) {
            if ($(ui).val() != '')
                val = true;
        });
        if (val)
            $('#filter').submit();
        return false;
    });


    $('.read-button').click(function () {
        var elem = $(this);
        var href = elem.attr('href');
        var isNew = elem.parents('tr').hasClass('new');
        $.ajax({
            url: href,
            data: {
                'readed': isNew
            },
            success: function (data) {
                if (data === 'success') {
                    if (isNew) {
                        elem.find('i').attr('class', 'old-icon-booking-read-message');
                        elem.parents('tr').removeClass('new');
                    } else {
                        elem.find('i').attr('class', 'old-icon-booking-message');
                        elem.parents('tr').addClass('new');
                    }
                }
            }
        });
        return false;
    });

    var filter = $("#filter");
    $("input").keydown(function (e) {
        if (e.keyCode == 13) {
            filter.submit();
        }
    });

    filter.find("select").change(function () {
        filter.submit();
    });

    filter.find("input.datepicker").change(function () {
        filter.submit();
    });

    // autoupdates
    AutoUpdateQueue.init();

});
