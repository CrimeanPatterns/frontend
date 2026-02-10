/* global AbstractFormExtension */

define(['jquery-boot', 'translator-boot', 'common/abstractFormExtension'], function($) {
    let formFieldPrefix = 'account';
    let formSelector = '#account-form';

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
     * set field value
     * @param {string} fieldName
     * @param {string} value
     * @param {string} [property="value", "label"] property name
     */
    DesktopForm.prototype.setValue = function(fieldName, value, property) {
        const input    = this.getInput(fieldName);
        property = property || 'value';

        if (null === input)
            return;

        if (value instanceof Date) {
            const $fieldDatepicker = $('#' + formFieldPrefix + '_' + fieldName + '_datepicker');
            if ($fieldDatepicker.length) {
                $fieldDatepicker.datepicker('setDate', value);
            }
            value = value.getFullYear() + '-' + value.getMonth() + '-' + value.getDate();
        }

        if ('checkbox' == input.get(0).type) {
            input.prop('checked', !!value);
        } else if ('select-one' == input.get(0).type && -1 !== ['value', 'label'].indexOf(property)) {
            var propValue = input.find('[' + property + '="' + value + '"]');
            if (propValue.length) {
                input.find('[selected]').removeAttr('selected');
                propValue.attr('selected', 'selected');
            } else {
                input.val(value);
            }
        } else {
            input.val(value);
        }
    };

    /**
     * set field options
     * @param {string} fieldName
     * @param {array} options
     */
    DesktopForm.prototype.setOptions = function(fieldName, options) {
        var input       = this.getInput(fieldName),
            optionsHtml = '', selected;
        if (null === input)
            return;

        for (var i in options) {
            selected = 'undefined' != typeof options[i].selected ? ' selected="selected"' : '';
            optionsHtml += '<option value="' + options[i].value + '"' + selected + '>' + options[i].label + '</option>';
        }
        input.empty().html(optionsHtml);
    };

    /**
     * get select:options list
     * @param {string} fieldName
     */
    DesktopForm.prototype.getOptions = function(fieldName) {
        const select = this.getInput(fieldName);
        if (null === select) {
            return null;
        }
        let options = [];
        $('option[value]', select).each(function(i, option) {
            const $option = $(option);
            options.push({
                value: $option.attr('value'),
                label: $option.text(),
                selected: $option.prop('selected'),
            });
        });

        return options;
    };

    /**
     * show/hide field
     * @param {string} fieldName
     * @param {boolean} visible
     */
    DesktopForm.prototype.showField = function (fieldName, visible) {
        $(formSelector).find('.row-' + fieldName).toggle(visible);
    };

    /**
     * mark field as required
     * @param {string} fieldName
     * @param {boolean} required
     */
    DesktopForm.prototype.requireField = function (fieldName, required) {
        var input = this.getInput(fieldName);
        if (null === input)
            return;
        input.attr('required', required ? 'required' : null);
        $('label[for=' + formFieldPrefix + '_' + fieldName + '] span.required').toggle(required);
        if(!required)
            $(formSelector).find('.row-' + fieldName).removeClass('error').find('.error-message').hide();
    };

    /**
     * set field caption
     * @param {string} fieldName
     * @param {string} caption
     */
    DesktopForm.prototype.setFieldCaption = function (fieldName, caption) {
        var label = $('label[for=' + formFieldPrefix + '_' + fieldName + ']');
        var children = label.clone().children();
        label.text(caption);
        label.append(children);
    };

    /**
     * set field notice
     * @param {string} fieldName
     * @param {string} notice
     */
    DesktopForm.prototype.setFieldNotice = function(fieldName, notice) {
        var input = this.getInput(fieldName);
        if (input === null)
            return;
        var row = $('.info .info-description', formSelector + ' .row-' + fieldName);
        if (!row.length)
            return;
        row.text(notice);
    };

    /**
     * disable field
     * @param {string} fieldName
     * @param {boolean} disable
     */
    DesktopForm.prototype.disableField = function(fieldName, disable) {
        var input = this.getInput(fieldName);
        if (null === input)
            return;
        true === disable ? input.prop('disabled', true) : input.removeProp('disabled'); 
    };

    /**
     * @param {string} fieldName
     * @returns {(object|null)}
     */
    DesktopForm.prototype.getInput = function(fieldName){
        if (fieldName === 'pass') fieldName = 'pass_password';
        var $obj = $('#' + formFieldPrefix + '_' + fieldName);
        if ($obj.length)
            return $obj;

        if (!$obj.length && 'pass_password' === fieldName)
            $obj = $('#' + formFieldPrefix + '_pass');

        return $obj.length ? $obj : null;
    };

    /**
     * get Translator service
     * @returns {object}
     */
    DesktopForm.prototype.getTranslator = function() {
        return Translator;
    };

    /**
     * Navigate to url
     * @param {string} url
     **/
    DesktopForm.prototype.navigate = function(url) {
        document.location.replace(url);
    };

    /**
     * Show dialog
     * @param {DialogConfig} config
     */
    DesktopForm.prototype.showDialog = function(config) {
        var dialog = require('lib/dialog'), modal;

        function getButtonStyle(style) {
            var styles = {
                'positive' : 'btn-blue',
                'default'  : 'btn-silver'
            };
            if (Object.prototype.hasOwnProperty.call(styles, style))
                return styles[style];

            return styles['default'];
        }

        var buttons = config.buttons.map(function(button) {
            return {
                text  : button.text,
                click : function() {
                    modal.destroy();
                    button.onPress && button.onPress();
                },
                class : getButtonStyle(button.style)
            }
        });

        modal = dialog.fastCreate(config.title, '<p>' + config.message + '</p>', true, true, buttons, 450);
    };

    /**
     * @param {function} extensionFunc
     */
    DesktopForm.prototype.init = function(extensionFunc, options){
        if (undefined !== options) {
            formSelector = options.formSelector || formSelector;
            formFieldPrefix = options.formFieldPrefix || formFieldPrefix;
        }

        $(document).ready(function(){
            const extension = new AbstractFormExtension();
            extensionFunc(extension);

            $(formSelector).on('change', 'input[type=text], input[type=checkbox], select', null, function(event){
                const fieldName = this.id.replace(new RegExp('^' + formFieldPrefix + '_', 'g'), '');
                extension.onFieldChange(self, fieldName);
            });

            extension.onFormReady(self);
        });
    };

    var self = new DesktopForm();

    return self;

});