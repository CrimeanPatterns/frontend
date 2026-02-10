/**
 * @implements {FormInterface}
 * @param {HTMLElement} form
 * @param {Array.<{function}>} extensions
 * @constructor
 */
function MobileForm(form, extensions) {
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

    var formExtensions = [],
        _this = this;

    if (typeof extensions === 'function') {
        extensions = [extensions];
    }

    extensions.map(function (extensionFunc) {
        var formExtension = new AbstractFormExtension();

        extensionFunc(formExtension);

        formExtensions.push(formExtension);
    });

    form.on('change', 'input[type=text], input[type=checkbox], input[type=hidden], select', null, function (event) {
        var fieldName = $(this).attr('name');

        formExtensions.map(function (formExtension) {
            if (typeof formExtension.onFieldChange !== 'undefined') {
                formExtension.onFieldChange(_this, fieldName);
            }
        });
    });

    formExtensions.map(function (formExtension) {
        if (typeof formExtension.onFormReady !== 'undefined') {
            formExtension.onFormReady(_this);
        }
    });
}

/**
 * returns current form field value
 * @param {string} fieldName
 * @returns {(string|null)}
 */
MobileForm.prototype.getValue = function (fieldName) {
    var input = this.getInput(fieldName);
    if (input === null)
        return null;
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
    if (input === null)
        return;
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

function isEmpty(data) {
    const type = typeof(data);

    if (type === 'number' || type === 'boolean') {
        return false;
    }

    if (type === 'undefined' || data === null) {
        return true;
    }

    if (typeof(data.length) !== 'undefined') {
        return data.length === 0;
    }

    return false;
}

/**
 * set field options
 * @param {string} fieldName
 * @param {array} options
 */
MobileForm.prototype.setOptions = function (fieldName, options) {
    var input = this.getInput(fieldName);
    if (input === null)
        return;
    var scope = angular.element(input.get(0)).scope();
    if (scope && typeof scope.$apply === 'function' && scope.field) {
        scope.$apply(function () {
            scope.field.choices = options;

            var oldValue;

            if (scope.field.selectedOption) {
                oldValue = scope.field.selectedOption.value;
            }

            var isEmptyValue = isEmpty(oldValue),
                foundSimilar = false;

            scope.field.selectedOption = null;
            for (var i in scope.field.choices) {
                if (
                    scope.field.choices[i].selected === true
                    || ( !foundSimilar && (scope.field.choices[i].value === oldValue || (isEmptyValue && isEmpty(scope.field.choices[i].value))) )
                ) {
                    scope.field.selectedOption = scope.field.choices[i];
                    foundSimilar = true;
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
 * @param {(string|null)} fieldName
 */
MobileForm.prototype.getOptions = function (fieldName) {
    var input = this.getInput(fieldName);
    if (input === null)
        return null;
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
        field = form.find('.' + fieldName.toLowerCase()).closest('page-form-field');
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
    if (input === null)
        return;
    var scope = angular.element(input.get(0)).scope();
    if (scope && typeof scope.$apply === 'function' && scope.field) {
        scope.$apply(function () {
            scope.field.required = required;
        });
    }
};

/**
 * @param {string} fieldName
 * @returns {(object|null)}
 */
MobileForm.prototype.getInput = function (fieldName) {
    var form = this.getForm();
    var obj = form.find('[name="' + fieldName + '"]');
    if (typeof obj[0] === 'undefined')
        return null;
    return obj;
};

/**
 * @param {string} fieldName
 * @returns {(object|null)}
 */
MobileForm.prototype.getField = function (fieldName) {
    var input = this.getInput(fieldName);
    if (input === null)
        return null;
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
    if (input === null)
        return;
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
    if (input === null)
        return;
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
    if (input === null)
        return;
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
    if (input === null)
        return;
    input.prop('type', type);
};

MobileForm.prototype.getTranslator = function () {
    return Translator;
};

MobileForm.prototype.navigate = function (url) {
    window.open(url, '_blank');
};

MobileForm.prototype.showDialog = function (config) {
    var customPopup = btfModal({
        controller: angular.noop,
        templateUrl: 'templates/directives/popups/custom-popup.html',
        uid: 'formPopup'
    });
    customPopup.open({
        config: config,
        hideModal: function() {
            return customPopup.close();
        },
        noop: angular.noop
    });
};

/**
 * Submit form (only mobile)
 */
MobileForm.prototype.submit = function () {
    var form = this.getForm();

    form.submit();
};