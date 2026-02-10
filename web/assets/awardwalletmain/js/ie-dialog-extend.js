$(document).ready(function () {
    $.widget("ui.dialog", $.ui.dialog, {
        open: function () {
            $(this.uiButtonSet).children().last().addClass('slvzr-last-child');
            return this._super();
        }
    });
});