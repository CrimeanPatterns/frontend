function ToolTip(context, options) {
    let tooltip,
        selector = '[data-role="tooltip"]';

    if (undefined !== context) {
        tooltip = $(context).find(selector).addBack(selector);
    } else {
        tooltip = $(selector);
    }

    tooltip
        .tooltip({
            tooltipClass: 'custom-tooltip-styling',
            position: {
                my: 'center bottom',
                at: 'center top',
                collision: 'flipfit flip',
                using: function (position, feedback) {
                    $(this).css(position);
                    $('<div>')
                        .addClass('arrow')
                        .addClass(feedback.vertical)
                        .css({
                            marginLeft: (feedback.target.left - feedback.element.left - 6 - 7 + feedback.target.width / 2),
                            width: 0
                        })
                        .appendTo(this);
                },
                ...options
            }
        })
        .removeAttr('data-role')
        .off('focusin focusout')
        .prop('tooltip-initialized', true);
}

export default ToolTip;