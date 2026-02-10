/**
 * @implements {FormInterface}
 * @constructor
 */
function MobileForm(form, extensionFunc) {
    /**
     * Form
     * @property _form
     * @private
     * @type {object}
     */
    var _form = form || {};
    /**
     * @param {object} form
     * @returns {object}
     */
    this.setForm = function (form) {
        _form = form;
        return _form;
    };
    /**
     * @returns {object}
     */
    this.getForm = function () {
        return _form;
    };

    this.mobile = true;

    var extension = new AbstractFormExtension(), _this = this;

    extensionFunc(extension);

    form.on('change', 'input[type=text], input[type=checkbox], select', null, function (event) {
        var fieldName = $(this).attr('name');
        extension.onFieldChange(_this, fieldName);
    });

    extension.onFormReady(_this);
}

/**
 * returns current form field value
 * @param {string} fieldName
 * @returns {string}
 */
MobileForm.prototype.getValue = function (fieldName) {
    var input = this.getInput(fieldName);
    var scope = angular.element(input.get(0)).scope();
    if (scope && scope.field) {
        if (scope.type === 'choice')
            return scope.field.selectedOption.value;
        return scope.field.value;
    }
};

/**
 * set field value
 * @param {string} fieldName
 * @param {string} value
 * @param {string} [property] property name
 */
MobileForm.prototype.setValue = function (fieldName, value, property) {
    var input = this.getInput(fieldName);
    var scope = angular.element(input.get(0)).scope();
    if (scope && typeof scope.$apply === 'function' && scope.field) {
        scope.$apply(function () {
            if (scope.type === 'choice') {
                property = property || 'value';
                if (['label', 'value'].indexOf(property) > -1 && scope.field.choices.length > 0) {
                    for (var i in scope.field.choices) {
                        if (scope.field.choices[i][property] === value)
                            scope.field.selectedOption = scope.field.choices[i];
                    }
                }
            } else {
                scope.field.value = value;
            }
        });
    }
};

/**
 * set field options
 * @param {string} fieldName
 * @param {array} options
 */
MobileForm.prototype.setOptions = function (fieldName, options) {
    var input = this.getInput(fieldName);
    var scope = angular.element(input.get(0)).scope();
    if (scope && typeof scope.$apply === 'function' && scope.field) {
        scope.$apply(function () {
            scope.field.choices = options;
            scope.field.selectedOption = null;
            for (var i in scope.field.choices) {
                if (scope.field.choices[i].selected === true) {
                    scope.field.selectedOption = scope.field.choices[i];
                }
            }
            if (!scope.field.selectedOption) {
                scope.field.selectedOption = scope.field.choices[0];
            }
        });
    }
};

/**
 * get field options
 * @param {string} fieldName
 */
MobileForm.prototype.getOptions = function (fieldName) {
    var input = this.getInput(fieldName);
    var scope = angular.element(input.get(0)).scope();
    if (scope && scope.field) {
        return scope.field.choices;
    }
};

/**
 * show/hide field
 * @param {string} fieldName
 * @param {boolean} visible
 */
MobileForm.prototype.showField = function (fieldName, visible) {
    var form = this.getForm(),
        field = form.find('.' + fieldName).closest('page-form-field');
    if (visible) {
        field.show();
    } else {
        field.hide();
    }
};

/**
 * mark field as required
 * @param {string} fieldName
 * @param {boolean} required
 */
MobileForm.prototype.requireField = function (fieldName, required) {
    var input = this.getInput(fieldName);
    var scope = angular.element(input.get(0)).scope();
    if (scope && typeof scope.$apply === 'function' && scope.field) {
        scope.$apply(function () {
            scope.field.required = required;
        });
    }
};

/**
 * @param {string} fieldName
 * @returns {object}
 */
MobileForm.prototype.getInput = function (fieldName) {
    var form = this.getForm();
    return form.find('[name=' + fieldName + ']');
};

/**
 * @param {string} fieldName
 * @returns {object}
 */
MobileForm.prototype.getField = function (fieldName) {
    var input = this.getInput(fieldName);
    var scope = angular.element(input.get(0)).scope();
    if (scope) {
        return scope.field;
    }
};

/**
 * disable field
 * @param {string} fieldName
 * @param {boolean} disable
 */
MobileForm.prototype.disableField = function (fieldName, disable) {
    var input = this.getInput(fieldName);
    var scope = angular.element(input.get(0)).scope();
    if (scope && typeof scope.$apply === 'function' && scope.field) {
        scope.$apply(function () {
            scope.field.disabled = disable;
        });
    }
};

/**
 * set field caption
 * @param {string} fieldName
 * @param {string} caption
 */
MobileForm.prototype.setFieldCaption = function (fieldName, caption) {
    var input = this.getInput(fieldName);
    var scope = angular.element(input.get(0)).scope();
    if (scope && typeof scope.$apply === 'function' && scope.field && scope.field.label) {
        scope.$apply(function () {
            scope.field.label = caption;
        });
    }
};

/**
 * set field notice
 * @param {string} fieldName
 * @param {string} notice
 */
MobileForm.prototype.setFieldNotice = function (fieldName, notice) {
    var input = this.getInput(fieldName);
    var scope = angular.element(input.get(0)).scope();
    if (scope && typeof scope.$apply === 'function' && scope.field && scope.field.attr) {
        scope.$apply(function () {
            scope.field.attr.notice = notice;
        });
    }
};

/**
 * set field type
 * @param {string} fieldName
 * @param {string} type
 */
MobileForm.prototype.setFieldType = function (fieldName, type) {
    var input = this.getInput(fieldName);
    input.prop('type', type);
};
