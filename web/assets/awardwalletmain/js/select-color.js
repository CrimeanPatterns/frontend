var PrettifySelect = function () {
    var select = $('select.prettyfied');

    var selectBoxContainer = $('<div>', {
        'width': select.outerWidth(),
        'class': 'prettyfied-select',
        'html': '<div class="prettyfied-select-box"><span></span></div>'
    });

    var dropDown = $('<ul>', {'class': 'dropDown'});
    var selectBox = selectBoxContainer.find('.prettyfied-select-box');

    select.find('option').each(function (i) {
        var option = $(this);

        if (!option.data('html-text')) {
            return true;
        }
        var li = $('<li>', {
            'html': '<span>' + option.data('html-text') + '</span><div class="color-' + option.data('color') + '"></div>'
        });

        li.click(function () {
            selectBox.html('<span>' + option.text() + '</span><div class="color-' + option.data('color') + '"></div>');
            dropDown.trigger('hide');

            select.val(option.val());

            return false;
        }).hover(function () {
            $(this).addClass('hover');
        }, function () {
            $(this).removeClass('hover');
        });

        dropDown.append(li);
    });
    if (select.find('option:selected').length) {
        var option = select.find('option:selected').one();
        selectBox.html('<span>' + option.text() + '</span><div class="color-' + option.data('color') + '"></div>');
    }

    selectBoxContainer.append(dropDown.hide());
    select.hide().after(selectBoxContainer);
    select.removeClass('prettyfied');

    dropDown.bind('show',function () {
        if (dropDown.is(':animated')) {
            return false;
        }
        selectBox.addClass('expanded');
        dropDown.slideDown();
    }).bind('hide',function () {
        if (dropDown.is(':animated')) {
            return false;
        }
        selectBox.removeClass('expanded');
        dropDown.addClass('is-hidden');
        dropDown.slideUp(function () {
            dropDown.removeClass('is-hidden');
        });
    }).bind('toggle', function () {
        if (selectBox.hasClass('expanded')) {
            dropDown.trigger('hide');
        }
        else dropDown.trigger('show');
    });

    selectBox.click(function () {
        dropDown.trigger('toggle');
        return false;
    });

    $(document).click(function () {
        dropDown.trigger('hide');
    });
};

$(document).ready(function() {
    PrettifySelect();
});