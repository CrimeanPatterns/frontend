/* global AbstractFormExtension */

define(['common/abstractFormExtension'], function() {

    /**
     * @implements {FormInterface}
     * @constructor
     */
    function DesktopForm(){}

    /**
     * returns current form field value
     * @param {string} fieldName
     * @returns {(string|boolean|null)}
     */
    DesktopForm.prototype.getValue = function(fieldName) {
        var input = this.getInput(fieldName);
        if (null === input)
            return null;
        if ('checkbox' == input.get(0).type)
            return input.prop('checked');
        return input.val();
    };

    /**
     * @param {string} fieldName
     * @returns {(object|null)}
     */
    DesktopForm.prototype.getInput = function(fieldName){
        var $obj = $('#' + fieldName);
        if ($obj.length)
            return $obj;
        return null;
    };

    /**
     * set field value
     * @param {string} fieldName
     * @param {string} value
     * @param {string} [property="value", "label"] property name
     */
    DesktopForm.prototype.setValue = function(fieldName, value, property) {
    };

    /**
     * set field options
     * @param {string} fieldName
     * @param {array} options
     */
    DesktopForm.prototype.setOptions = function(fieldName, options) {
    };

    /**
     * show/hide field
     * @param {string} fieldName
     * @param {boolean} visible
     */
    DesktopForm.prototype.showField = function (fieldName, visible) {
        $('#row-'+fieldName).toggle(visible);
    };

    /**
     * mark field as required
     * @param {string} fieldName
     * @param {boolean} required
     */
    DesktopForm.prototype.requireField = function (fieldName, required) {
    };

    /**
     * set field caption
     * @param {string} fieldName
     * @param {string} caption
     */
    DesktopForm.prototype.setFieldCaption = function (fieldName, caption) {
        var label = $('label[for=' + fieldName + ']');
        if (typeof(label) !=='undefined') {
            var children = label.clone().children();
            label.text(caption);
            label.append(children);
        }
    };

    /**
     * @param {function} extensionFunc
     */
    DesktopForm.prototype.init = function(extensionFunc){
        $(document).ready(function(){
            var extension = new AbstractFormExtension();
            extensionFunc(extension);

            $('form, form[name=s]').on('change', 'input[type=text], input[type=checkbox], select', null, function(event){
                var fieldName = this.id;
                extension.onFieldChange(self, fieldName);
            });

            extension.onFormReady(self);
        });
    };

    var self = new DesktopForm();

    return self;

});