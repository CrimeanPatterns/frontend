/**
 * Interface for account edit form control, overridden in mobile / desktop
 * @interface
 */
function FormInterface() {
}


/**
 * get Translator service
 *
 * @returns {object}
 */
FormInterface.prototype.getTranslator = function () {

};

/**
 * Navigate to url
 * @example
 * form.navigate("https://awardwallet.com/user/pay/balancewatch-credit")
 * @param {string} url
 **/
FormInterface.prototype.navigate = function (url) {

};


/**
 * @typedef DialogConfig
 * @type {Object}
 * @property {string} [title] title
 * @property {string} message
 * @property {Array.<DialogButton>} buttons
 */

/**
 * @typedef DialogButton
 * @type {Object}
 * @property {string} text
 * @property {callback} [onPress]
 * @property {string} [style="positive|negative"] button style
 */

/**
 * Show dialog
 * @example
 * form.showDialog({
 *     title: "Open profile",
 *     message: "Confirm open profile?",
 *     buttons: [
 *         {
 *             text: form.getTranslator().trans("cancel")
 *         },
 *         {
 *             text: form.getTranslator().trans("open"),
 *             onPress: function() {
 *                 form.navigate("https://awardwallet.com/profile");
 *             },
 *             style: "positive"
 *         }
 *     ]
 * })
 * @param {DialogConfig} config
 */
FormInterface.prototype.showDialog = function (config) {

};

/**
 * get input
 * @param {string} fieldName
 * @returns {(object|null)}
 */
FormInterface.prototype.getInput = function (fieldName) {

};

/**
 * set field value
 * @param {string} fieldName
 * @param {string} value
 * @param {string="value"} [property="value"|"label"] property name
 */
FormInterface.prototype.setValue = function (fieldName, value, property) {

};

/**
 * returns current form field value
 * @param {string} fieldName
 * @returns {(string|null)}
 */
FormInterface.prototype.getValue = function (fieldName) {

};

/**
 * show/hide field
 * @param {string} fieldName
 * @param {boolean} visible
 */
FormInterface.prototype.showField = function (fieldName, visible) {

};

/**
 * mark field as required
 * @param {string} fieldName
 * @param {boolean} required
 */
FormInterface.prototype.requireField = function (fieldName, required) {

};

/**
 * set field caption
 * @param {string} fieldName
 * @param {string} caption
 */
FormInterface.prototype.setFieldCaption = function (fieldName, caption) {

};

/**
 * set field notice
 * @param {string} fieldName
 * @param {string} notice
 */
FormInterface.prototype.setFieldNotice = function (fieldName, notice) {

};

/**
 * set field options
 * @param {string} fieldName
 * @param {array} options
 */
FormInterface.prototype.setOptions = function (fieldName, options) {

};

/**
 * get field options
 * @param {string} fieldName
 * @param {boolean} disabled
 */
FormInterface.prototype.disableField = function (fieldName, disabled) {

};