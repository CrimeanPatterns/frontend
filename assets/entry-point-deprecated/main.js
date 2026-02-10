import '../less-deprecated/main.less';
/*eslint no-unused-vars: "jqueryui"*/
import jqueryui from 'jqueryui'; // .menu()

(function main() {
    toggleSidebarVisible();
    initDropdowns($('body'));
})();

function toggleSidebarVisible() {
    $(window).resize(function() {
        let sizeWindow = $('body').width();
        if (sizeWindow < 1024) {
            $('.main-body').addClass('small-desktop');
        } else {
            $('.main-body').removeClass('small-desktop');
        }
        if ($('.main-body').hasClass('manual-hidden')) return;
        if (sizeWindow < 1024) {
            $('.main-body').addClass('hide-menu');
        } else {
            $('.main-body').removeClass('hide-menu');
        }
    });

    const menuClose = document.querySelector('.menu-close');
    if (menuClose) {
        const menuBody = document.querySelector('.main-body');
        const sizeWindow = $('body').width();
        if (sizeWindow < 1024) {
            menuBody.classList.add('small-desktop');
            menuBody.classList.toggle('hide-menu');
        }
        menuClose.onclick = () => {
            menuBody.classList.toggle('hide-menu');
            menuBody.classList.add('manual-hidden');
        };
    }
}

function initDropdowns(area, options) {
    options = options || {};
    const selector = '[data-role="dropdown"]';
    const dropdown = undefined != area
        ? $(area).find(selector).addBack(selector)
        : $(selector)
    const ofParentSelector = options.ofParent || 'li';

    dropdown.each(function(id, el) {
        $(el)
            .removeAttr('data-role')
            .menu()
            .hide()
            .on('menu.hide', function(e) {
                $(e.target).hide(200);
            });
        $('[data-target=' + $(el).data('id') + ']').on('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            $('.ui-menu:visible').not('[data-id="' + $(this).data('target') + '"]').trigger('menu.hide');
            $(el).toggle(0, function() {
                $(el).position({
                    my: options?.position?.my || 'left top',
                    at: "left bottom",
                    of: $(e.target).parents(ofParentSelector).find('.rel-this'),
                    collision: "fit"
                });
            });
        });
    });
    $(document).on('click', function(e) {
        $('.ui-menu:visible').trigger('menu.hide');
    });
};

function autoCompleteRenderItem(renderFunction = null) {
    if (null === renderFunction) {
        renderFunction = function(ul, item) {
            const regex = new RegExp('(' + this.element.val().replace(/[^A-Za-z0-9А-Яа-я]+/g, '') + ')', 'gi'),
                html = $('<div/>').text(item.label).html().replace(regex, '<b>$1</b>');
            return $('<li></li>')
                .data('item.autocomplete', item)
                .append($('<a></a>').html(html))
                .appendTo(ul);
        };
    }

    $.ui.autocomplete.prototype._renderItem = renderFunction;
}

export default { initDropdowns, autoCompleteRenderItem };
