"use strict";
(self["webpackChunkAwardWallet"] = self["webpackChunkAwardWallet"] || []).push([["new-timeline"],{

/***/ "./assets/entry-point-deprecated/timeline/new-index.js":
/*!*************************************************************!*\
  !*** ./assets/entry-point-deprecated/timeline/new-index.js ***!
  \*************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _web_assets_awardwalletnewdesign_less_pages_trips_less__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ../../../web/assets/awardwalletnewdesign/less/pages/trips.less */ "./web/assets/awardwalletnewdesign/less/pages/trips.less");
/* harmony import */ var _bem_ts_starter__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ../../bem/ts/starter */ "./assets/bem/ts/starter.ts");
/* harmony import */ var _less_deprecated_timeline_less__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ../../less-deprecated/timeline.less */ "./assets/less-deprecated/timeline.less");
/* harmony import */ var react_dom__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! react-dom */ "./node_modules/react-dom/index.js");
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! react */ "./node_modules/react/index.js");
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_4___default = /*#__PURE__*/__webpack_require__.n(react__WEBPACK_IMPORTED_MODULE_4__);
/* harmony import */ var _js_deprecated_component_deprecated_timeline__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! ../../js-deprecated/component-deprecated/timeline */ "./assets/js-deprecated/component-deprecated/timeline/index.js");






var appElement = document.getElementById('react-app');
var height = document.getElementsByClassName('page')[0].offsetHeight;
var allowShowDeletedSegments = appElement.dataset.allowShowDeleted === 'true';
(0,react_dom__WEBPACK_IMPORTED_MODULE_3__.render)( /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_4___default().createElement((react__WEBPACK_IMPORTED_MODULE_4___default().StrictMode), null, /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_4___default().createElement(_js_deprecated_component_deprecated_timeline__WEBPACK_IMPORTED_MODULE_5__["default"], {
  containerHeight: height,
  allowShowDeletedSegments: allowShowDeletedSegments
})), appElement);

/***/ }),

/***/ "./assets/js-deprecated/component-deprecated/timeline/EmptyList.js":
/*!*************************************************************************!*\
  !*** ./assets/js-deprecated/component-deprecated/timeline/EmptyList.js ***!
  \*************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var prop_types__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! prop-types */ "./node_modules/prop-types/index.js");
/* harmony import */ var prop_types__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(prop_types__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! react */ "./node_modules/react/index.js");
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(react__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _bem_ts_service_translator__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ../../../bem/ts/service/translator */ "./assets/bem/ts/service/translator.ts");



var EmptyList = function EmptyList(_ref) {
  var _ref$message = _ref.message,
    message = _ref$message === void 0 ? null : _ref$message;
  if (!message) {
    message = _bem_ts_service_translator__WEBPACK_IMPORTED_MODULE_1__["default"].trans('trips.no-trips.text');
  }
  return /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("div", {
    className: "no-result"
  }, /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("div", {
    className: "no-result-item"
  }, /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("i", {
    className: "icon-warning-small"
  }), /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("p", null, message)));
};
EmptyList.propTypes = {
  message: (prop_types__WEBPACK_IMPORTED_MODULE_2___default().string)
};
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (EmptyList);

/***/ }),

/***/ "./assets/js-deprecated/component-deprecated/timeline/MailboxOffer.js":
/*!****************************************************************************!*\
  !*** ./assets/js-deprecated/component-deprecated/timeline/MailboxOffer.js ***!
  \****************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var prop_types__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! prop-types */ "./node_modules/prop-types/index.js");
/* harmony import */ var prop_types__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(prop_types__WEBPACK_IMPORTED_MODULE_3__);
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! react */ "./node_modules/react/index.js");
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(react__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _bem_ts_service_router__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ../../../bem/ts/service/router */ "./assets/bem/ts/service/router.ts");
/* harmony import */ var _bem_ts_service_translator__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ../../../bem/ts/service/translator */ "./assets/bem/ts/service/translator.ts");




var MailboxOffer = function MailboxOffer(_ref) {
  var forwardingEmail = _ref.forwardingEmail;
  return /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("div", {
    className: "trip-info",
    dangerouslySetInnerHTML: {
      __html: _bem_ts_service_translator__WEBPACK_IMPORTED_MODULE_2__["default"].trans('scanner.link_mailbox_or_forward', {
        'link_on': "<a href=\"".concat(_bem_ts_service_router__WEBPACK_IMPORTED_MODULE_1__["default"].generate('aw_usermailbox_view'), "\" class=\"blue-link\">"),
        'link_off': '</a>',
        'email': "<span class=\"user-email\">".concat(forwardingEmail, "</span>")
      })
    }
  });
};
MailboxOffer.propTypes = {
  forwardingEmail: (prop_types__WEBPACK_IMPORTED_MODULE_3___default().string).isRequired
};
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (MailboxOffer);

/***/ }),

/***/ "./assets/js-deprecated/component-deprecated/timeline/PastSegmentsLoaderLink.js":
/*!**************************************************************************************!*\
  !*** ./assets/js-deprecated/component-deprecated/timeline/PastSegmentsLoaderLink.js ***!
  \**************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var prop_types__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! prop-types */ "./node_modules/prop-types/index.js");
/* harmony import */ var prop_types__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(prop_types__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! react */ "./node_modules/react/index.js");
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(react__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _bem_ts_service_translator__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ../../../bem/ts/service/translator */ "./assets/bem/ts/service/translator.ts");



var PastSegmentsLoaderLink = function PastSegmentsLoaderLink(_ref) {
  var _ref$loading = _ref.loading,
    loading = _ref$loading === void 0 ? false : _ref$loading;
  return /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement((react__WEBPACK_IMPORTED_MODULE_0___default().Fragment), null, /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("a", {
    href: "#",
    className: "past-travel"
  }, /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("i", {
    className: "icon-double-arrow-up-dark"
  }), !loading && /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("span", null, _bem_ts_service_translator__WEBPACK_IMPORTED_MODULE_1__["default"].trans('timeline.past.travel'))), loading && /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("a", {
    href: "",
    className: "past-travel"
  }, /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("div", {
    className: "loader"
  })));
};
PastSegmentsLoaderLink.propTypes = {
  loading: (prop_types__WEBPACK_IMPORTED_MODULE_2___default().bool)
};
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (PastSegmentsLoaderLink);

/***/ }),

/***/ "./assets/js-deprecated/component-deprecated/timeline/ShowDeletedSegmentsLink.js":
/*!***************************************************************************************!*\
  !*** ./assets/js-deprecated/component-deprecated/timeline/ShowDeletedSegmentsLink.js ***!
  \***************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var prop_types__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! prop-types */ "./node_modules/prop-types/index.js");
/* harmony import */ var prop_types__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(prop_types__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! react */ "./node_modules/react/index.js");
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(react__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _bem_ts_service_translator__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ../../../bem/ts/service/translator */ "./assets/bem/ts/service/translator.ts");



var ShowDeletedSegmentsLink = function ShowDeletedSegmentsLink(_ref) {
  var reverse = _ref.reverse;
  return /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("a", {
    href: "#",
    className: "deleted f-right"
  }, !reverse && /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("span", null, _bem_ts_service_translator__WEBPACK_IMPORTED_MODULE_1__["default"].trans('show.deleted.segments')), reverse && /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("span", null, _bem_ts_service_translator__WEBPACK_IMPORTED_MODULE_1__["default"].trans('hide.deleted.segments')));
};
ShowDeletedSegmentsLink.propTypes = {
  reverse: (prop_types__WEBPACK_IMPORTED_MODULE_2___default().bool)
};
ShowDeletedSegmentsLink.defaultProps = {
  reverse: false
};
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (ShowDeletedSegmentsLink);

/***/ }),

/***/ "./assets/js-deprecated/component-deprecated/timeline/index.js":
/*!*********************************************************************!*\
  !*** ./assets/js-deprecated/component-deprecated/timeline/index.js ***!
  \*********************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var core_js_modules_es_symbol_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! core-js/modules/es.symbol.js */ "./node_modules/core-js/modules/es.symbol.js");
/* harmony import */ var core_js_modules_es_symbol_js__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_symbol_js__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var core_js_modules_es_symbol_description_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! core-js/modules/es.symbol.description.js */ "./node_modules/core-js/modules/es.symbol.description.js");
/* harmony import */ var core_js_modules_es_symbol_description_js__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_symbol_description_js__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var core_js_modules_es_object_to_string_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! core-js/modules/es.object.to-string.js */ "./node_modules/core-js/modules/es.object.to-string.js");
/* harmony import */ var core_js_modules_es_object_to_string_js__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_object_to_string_js__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var core_js_modules_es_symbol_iterator_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! core-js/modules/es.symbol.iterator.js */ "./node_modules/core-js/modules/es.symbol.iterator.js");
/* harmony import */ var core_js_modules_es_symbol_iterator_js__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_symbol_iterator_js__WEBPACK_IMPORTED_MODULE_3__);
/* harmony import */ var core_js_modules_es_array_iterator_js__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! core-js/modules/es.array.iterator.js */ "./node_modules/core-js/modules/es.array.iterator.js");
/* harmony import */ var core_js_modules_es_array_iterator_js__WEBPACK_IMPORTED_MODULE_4___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_array_iterator_js__WEBPACK_IMPORTED_MODULE_4__);
/* harmony import */ var core_js_modules_es_string_iterator_js__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! core-js/modules/es.string.iterator.js */ "./node_modules/core-js/modules/es.string.iterator.js");
/* harmony import */ var core_js_modules_es_string_iterator_js__WEBPACK_IMPORTED_MODULE_5___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_string_iterator_js__WEBPACK_IMPORTED_MODULE_5__);
/* harmony import */ var core_js_modules_web_dom_collections_iterator_js__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! core-js/modules/web.dom-collections.iterator.js */ "./node_modules/core-js/modules/web.dom-collections.iterator.js");
/* harmony import */ var core_js_modules_web_dom_collections_iterator_js__WEBPACK_IMPORTED_MODULE_6___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_web_dom_collections_iterator_js__WEBPACK_IMPORTED_MODULE_6__);
/* harmony import */ var core_js_modules_es_array_slice_js__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__(/*! core-js/modules/es.array.slice.js */ "./node_modules/core-js/modules/es.array.slice.js");
/* harmony import */ var core_js_modules_es_array_slice_js__WEBPACK_IMPORTED_MODULE_7___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_array_slice_js__WEBPACK_IMPORTED_MODULE_7__);
/* harmony import */ var core_js_modules_es_regexp_to_string_js__WEBPACK_IMPORTED_MODULE_8__ = __webpack_require__(/*! core-js/modules/es.regexp.to-string.js */ "./node_modules/core-js/modules/es.regexp.to-string.js");
/* harmony import */ var core_js_modules_es_regexp_to_string_js__WEBPACK_IMPORTED_MODULE_8___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_regexp_to_string_js__WEBPACK_IMPORTED_MODULE_8__);
/* harmony import */ var core_js_modules_es_function_name_js__WEBPACK_IMPORTED_MODULE_9__ = __webpack_require__(/*! core-js/modules/es.function.name.js */ "./node_modules/core-js/modules/es.function.name.js");
/* harmony import */ var core_js_modules_es_function_name_js__WEBPACK_IMPORTED_MODULE_9___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_function_name_js__WEBPACK_IMPORTED_MODULE_9__);
/* harmony import */ var core_js_modules_es_array_from_js__WEBPACK_IMPORTED_MODULE_10__ = __webpack_require__(/*! core-js/modules/es.array.from.js */ "./node_modules/core-js/modules/es.array.from.js");
/* harmony import */ var core_js_modules_es_array_from_js__WEBPACK_IMPORTED_MODULE_10___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_array_from_js__WEBPACK_IMPORTED_MODULE_10__);
/* harmony import */ var core_js_modules_es_regexp_exec_js__WEBPACK_IMPORTED_MODULE_11__ = __webpack_require__(/*! core-js/modules/es.regexp.exec.js */ "./node_modules/core-js/modules/es.regexp.exec.js");
/* harmony import */ var core_js_modules_es_regexp_exec_js__WEBPACK_IMPORTED_MODULE_11___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_regexp_exec_js__WEBPACK_IMPORTED_MODULE_11__);
/* harmony import */ var core_js_modules_es_promise_js__WEBPACK_IMPORTED_MODULE_12__ = __webpack_require__(/*! core-js/modules/es.promise.js */ "./node_modules/core-js/modules/es.promise.js");
/* harmony import */ var core_js_modules_es_promise_js__WEBPACK_IMPORTED_MODULE_12___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_promise_js__WEBPACK_IMPORTED_MODULE_12__);
/* harmony import */ var core_js_modules_es_object_keys_js__WEBPACK_IMPORTED_MODULE_13__ = __webpack_require__(/*! core-js/modules/es.object.keys.js */ "./node_modules/core-js/modules/es.object.keys.js");
/* harmony import */ var core_js_modules_es_object_keys_js__WEBPACK_IMPORTED_MODULE_13___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_object_keys_js__WEBPACK_IMPORTED_MODULE_13__);
/* harmony import */ var core_js_modules_es_symbol_to_primitive_js__WEBPACK_IMPORTED_MODULE_14__ = __webpack_require__(/*! core-js/modules/es.symbol.to-primitive.js */ "./node_modules/core-js/modules/es.symbol.to-primitive.js");
/* harmony import */ var core_js_modules_es_symbol_to_primitive_js__WEBPACK_IMPORTED_MODULE_14___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_symbol_to_primitive_js__WEBPACK_IMPORTED_MODULE_14__);
/* harmony import */ var core_js_modules_es_date_to_primitive_js__WEBPACK_IMPORTED_MODULE_15__ = __webpack_require__(/*! core-js/modules/es.date.to-primitive.js */ "./node_modules/core-js/modules/es.date.to-primitive.js");
/* harmony import */ var core_js_modules_es_date_to_primitive_js__WEBPACK_IMPORTED_MODULE_15___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_date_to_primitive_js__WEBPACK_IMPORTED_MODULE_15__);
/* harmony import */ var core_js_modules_es_number_constructor_js__WEBPACK_IMPORTED_MODULE_16__ = __webpack_require__(/*! core-js/modules/es.number.constructor.js */ "./node_modules/core-js/modules/es.number.constructor.js");
/* harmony import */ var core_js_modules_es_number_constructor_js__WEBPACK_IMPORTED_MODULE_16___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_number_constructor_js__WEBPACK_IMPORTED_MODULE_16__);
/* harmony import */ var core_js_modules_es_array_filter_js__WEBPACK_IMPORTED_MODULE_17__ = __webpack_require__(/*! core-js/modules/es.array.filter.js */ "./node_modules/core-js/modules/es.array.filter.js");
/* harmony import */ var core_js_modules_es_array_filter_js__WEBPACK_IMPORTED_MODULE_17___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_array_filter_js__WEBPACK_IMPORTED_MODULE_17__);
/* harmony import */ var core_js_modules_es_object_get_own_property_descriptor_js__WEBPACK_IMPORTED_MODULE_18__ = __webpack_require__(/*! core-js/modules/es.object.get-own-property-descriptor.js */ "./node_modules/core-js/modules/es.object.get-own-property-descriptor.js");
/* harmony import */ var core_js_modules_es_object_get_own_property_descriptor_js__WEBPACK_IMPORTED_MODULE_18___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_object_get_own_property_descriptor_js__WEBPACK_IMPORTED_MODULE_18__);
/* harmony import */ var core_js_modules_web_dom_collections_for_each_js__WEBPACK_IMPORTED_MODULE_19__ = __webpack_require__(/*! core-js/modules/web.dom-collections.for-each.js */ "./node_modules/core-js/modules/web.dom-collections.for-each.js");
/* harmony import */ var core_js_modules_web_dom_collections_for_each_js__WEBPACK_IMPORTED_MODULE_19___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_web_dom_collections_for_each_js__WEBPACK_IMPORTED_MODULE_19__);
/* harmony import */ var core_js_modules_es_object_get_own_property_descriptors_js__WEBPACK_IMPORTED_MODULE_20__ = __webpack_require__(/*! core-js/modules/es.object.get-own-property-descriptors.js */ "./node_modules/core-js/modules/es.object.get-own-property-descriptors.js");
/* harmony import */ var core_js_modules_es_object_get_own_property_descriptors_js__WEBPACK_IMPORTED_MODULE_20___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_object_get_own_property_descriptors_js__WEBPACK_IMPORTED_MODULE_20__);
/* harmony import */ var core_js_modules_es_object_assign_js__WEBPACK_IMPORTED_MODULE_21__ = __webpack_require__(/*! core-js/modules/es.object.assign.js */ "./node_modules/core-js/modules/es.object.assign.js");
/* harmony import */ var core_js_modules_es_object_assign_js__WEBPACK_IMPORTED_MODULE_21___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_object_assign_js__WEBPACK_IMPORTED_MODULE_21__);
/* harmony import */ var core_js_modules_es_symbol_async_iterator_js__WEBPACK_IMPORTED_MODULE_22__ = __webpack_require__(/*! core-js/modules/es.symbol.async-iterator.js */ "./node_modules/core-js/modules/es.symbol.async-iterator.js");
/* harmony import */ var core_js_modules_es_symbol_async_iterator_js__WEBPACK_IMPORTED_MODULE_22___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_symbol_async_iterator_js__WEBPACK_IMPORTED_MODULE_22__);
/* harmony import */ var core_js_modules_es_symbol_to_string_tag_js__WEBPACK_IMPORTED_MODULE_23__ = __webpack_require__(/*! core-js/modules/es.symbol.to-string-tag.js */ "./node_modules/core-js/modules/es.symbol.to-string-tag.js");
/* harmony import */ var core_js_modules_es_symbol_to_string_tag_js__WEBPACK_IMPORTED_MODULE_23___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_symbol_to_string_tag_js__WEBPACK_IMPORTED_MODULE_23__);
/* harmony import */ var core_js_modules_es_json_to_string_tag_js__WEBPACK_IMPORTED_MODULE_24__ = __webpack_require__(/*! core-js/modules/es.json.to-string-tag.js */ "./node_modules/core-js/modules/es.json.to-string-tag.js");
/* harmony import */ var core_js_modules_es_json_to_string_tag_js__WEBPACK_IMPORTED_MODULE_24___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_json_to_string_tag_js__WEBPACK_IMPORTED_MODULE_24__);
/* harmony import */ var core_js_modules_es_math_to_string_tag_js__WEBPACK_IMPORTED_MODULE_25__ = __webpack_require__(/*! core-js/modules/es.math.to-string-tag.js */ "./node_modules/core-js/modules/es.math.to-string-tag.js");
/* harmony import */ var core_js_modules_es_math_to_string_tag_js__WEBPACK_IMPORTED_MODULE_25___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_math_to_string_tag_js__WEBPACK_IMPORTED_MODULE_25__);
/* harmony import */ var core_js_modules_es_object_get_prototype_of_js__WEBPACK_IMPORTED_MODULE_26__ = __webpack_require__(/*! core-js/modules/es.object.get-prototype-of.js */ "./node_modules/core-js/modules/es.object.get-prototype-of.js");
/* harmony import */ var core_js_modules_es_object_get_prototype_of_js__WEBPACK_IMPORTED_MODULE_26___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_object_get_prototype_of_js__WEBPACK_IMPORTED_MODULE_26__);
/* harmony import */ var _infiniteList__WEBPACK_IMPORTED_MODULE_27__ = __webpack_require__(/*! ../infiniteList */ "./assets/js-deprecated/component-deprecated/infiniteList/index.ts");
/* harmony import */ var _bem_ts_service_axios__WEBPACK_IMPORTED_MODULE_28__ = __webpack_require__(/*! ../../../bem/ts/service/axios */ "./assets/bem/ts/service/axios/index.js");
/* harmony import */ var _EmptyList__WEBPACK_IMPORTED_MODULE_29__ = __webpack_require__(/*! ./EmptyList */ "./assets/js-deprecated/component-deprecated/timeline/EmptyList.js");
/* harmony import */ var _MailboxOffer__WEBPACK_IMPORTED_MODULE_30__ = __webpack_require__(/*! ./MailboxOffer */ "./assets/js-deprecated/component-deprecated/timeline/MailboxOffer.js");
/* harmony import */ var _PastSegmentsLoaderLink__WEBPACK_IMPORTED_MODULE_31__ = __webpack_require__(/*! ./PastSegmentsLoaderLink */ "./assets/js-deprecated/component-deprecated/timeline/PastSegmentsLoaderLink.js");
/* harmony import */ var prop_types__WEBPACK_IMPORTED_MODULE_38__ = __webpack_require__(/*! prop-types */ "./node_modules/prop-types/index.js");
/* harmony import */ var prop_types__WEBPACK_IMPORTED_MODULE_38___default = /*#__PURE__*/__webpack_require__.n(prop_types__WEBPACK_IMPORTED_MODULE_38__);
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_32__ = __webpack_require__(/*! react */ "./node_modules/react/index.js");
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_32___default = /*#__PURE__*/__webpack_require__.n(react__WEBPACK_IMPORTED_MODULE_32__);
/* harmony import */ var _bem_ts_service_router__WEBPACK_IMPORTED_MODULE_33__ = __webpack_require__(/*! ../../../bem/ts/service/router */ "./assets/bem/ts/service/router.ts");
/* harmony import */ var _segment__WEBPACK_IMPORTED_MODULE_34__ = __webpack_require__(/*! ./segment */ "./assets/js-deprecated/component-deprecated/timeline/segment/index.js");
/* harmony import */ var _ShowDeletedSegmentsLink__WEBPACK_IMPORTED_MODULE_35__ = __webpack_require__(/*! ./ShowDeletedSegmentsLink */ "./assets/js-deprecated/component-deprecated/timeline/ShowDeletedSegmentsLink.js");
/* harmony import */ var _Spinner__WEBPACK_IMPORTED_MODULE_36__ = __webpack_require__(/*! ../Spinner */ "./assets/js-deprecated/component-deprecated/Spinner.tsx");
/* harmony import */ var lodash__WEBPACK_IMPORTED_MODULE_37__ = __webpack_require__(/*! lodash */ "./node_modules/lodash/lodash.js");
/* harmony import */ var lodash__WEBPACK_IMPORTED_MODULE_37___default = /*#__PURE__*/__webpack_require__.n(lodash__WEBPACK_IMPORTED_MODULE_37__);
function _typeof(o) { "@babel/helpers - typeof"; return _typeof = "function" == typeof Symbol && "symbol" == typeof Symbol.iterator ? function (o) { return typeof o; } : function (o) { return o && "function" == typeof Symbol && o.constructor === Symbol && o !== Symbol.prototype ? "symbol" : typeof o; }, _typeof(o); }



























var _excluded = ["type"],
  _excluded2 = ["children"];
function _regeneratorRuntime() { "use strict"; /*! regenerator-runtime -- Copyright (c) 2014-present, Facebook, Inc. -- license (MIT): https://github.com/facebook/regenerator/blob/main/LICENSE */ _regeneratorRuntime = function _regeneratorRuntime() { return e; }; var t, e = {}, r = Object.prototype, n = r.hasOwnProperty, o = Object.defineProperty || function (t, e, r) { t[e] = r.value; }, i = "function" == typeof Symbol ? Symbol : {}, a = i.iterator || "@@iterator", c = i.asyncIterator || "@@asyncIterator", u = i.toStringTag || "@@toStringTag"; function define(t, e, r) { return Object.defineProperty(t, e, { value: r, enumerable: !0, configurable: !0, writable: !0 }), t[e]; } try { define({}, ""); } catch (t) { define = function define(t, e, r) { return t[e] = r; }; } function wrap(t, e, r, n) { var i = e && e.prototype instanceof Generator ? e : Generator, a = Object.create(i.prototype), c = new Context(n || []); return o(a, "_invoke", { value: makeInvokeMethod(t, r, c) }), a; } function tryCatch(t, e, r) { try { return { type: "normal", arg: t.call(e, r) }; } catch (t) { return { type: "throw", arg: t }; } } e.wrap = wrap; var h = "suspendedStart", l = "suspendedYield", f = "executing", s = "completed", y = {}; function Generator() {} function GeneratorFunction() {} function GeneratorFunctionPrototype() {} var p = {}; define(p, a, function () { return this; }); var d = Object.getPrototypeOf, v = d && d(d(values([]))); v && v !== r && n.call(v, a) && (p = v); var g = GeneratorFunctionPrototype.prototype = Generator.prototype = Object.create(p); function defineIteratorMethods(t) { ["next", "throw", "return"].forEach(function (e) { define(t, e, function (t) { return this._invoke(e, t); }); }); } function AsyncIterator(t, e) { function invoke(r, o, i, a) { var c = tryCatch(t[r], t, o); if ("throw" !== c.type) { var u = c.arg, h = u.value; return h && "object" == _typeof(h) && n.call(h, "__await") ? e.resolve(h.__await).then(function (t) { invoke("next", t, i, a); }, function (t) { invoke("throw", t, i, a); }) : e.resolve(h).then(function (t) { u.value = t, i(u); }, function (t) { return invoke("throw", t, i, a); }); } a(c.arg); } var r; o(this, "_invoke", { value: function value(t, n) { function callInvokeWithMethodAndArg() { return new e(function (e, r) { invoke(t, n, e, r); }); } return r = r ? r.then(callInvokeWithMethodAndArg, callInvokeWithMethodAndArg) : callInvokeWithMethodAndArg(); } }); } function makeInvokeMethod(e, r, n) { var o = h; return function (i, a) { if (o === f) throw new Error("Generator is already running"); if (o === s) { if ("throw" === i) throw a; return { value: t, done: !0 }; } for (n.method = i, n.arg = a;;) { var c = n.delegate; if (c) { var u = maybeInvokeDelegate(c, n); if (u) { if (u === y) continue; return u; } } if ("next" === n.method) n.sent = n._sent = n.arg;else if ("throw" === n.method) { if (o === h) throw o = s, n.arg; n.dispatchException(n.arg); } else "return" === n.method && n.abrupt("return", n.arg); o = f; var p = tryCatch(e, r, n); if ("normal" === p.type) { if (o = n.done ? s : l, p.arg === y) continue; return { value: p.arg, done: n.done }; } "throw" === p.type && (o = s, n.method = "throw", n.arg = p.arg); } }; } function maybeInvokeDelegate(e, r) { var n = r.method, o = e.iterator[n]; if (o === t) return r.delegate = null, "throw" === n && e.iterator.return && (r.method = "return", r.arg = t, maybeInvokeDelegate(e, r), "throw" === r.method) || "return" !== n && (r.method = "throw", r.arg = new TypeError("The iterator does not provide a '" + n + "' method")), y; var i = tryCatch(o, e.iterator, r.arg); if ("throw" === i.type) return r.method = "throw", r.arg = i.arg, r.delegate = null, y; var a = i.arg; return a ? a.done ? (r[e.resultName] = a.value, r.next = e.nextLoc, "return" !== r.method && (r.method = "next", r.arg = t), r.delegate = null, y) : a : (r.method = "throw", r.arg = new TypeError("iterator result is not an object"), r.delegate = null, y); } function pushTryEntry(t) { var e = { tryLoc: t[0] }; 1 in t && (e.catchLoc = t[1]), 2 in t && (e.finallyLoc = t[2], e.afterLoc = t[3]), this.tryEntries.push(e); } function resetTryEntry(t) { var e = t.completion || {}; e.type = "normal", delete e.arg, t.completion = e; } function Context(t) { this.tryEntries = [{ tryLoc: "root" }], t.forEach(pushTryEntry, this), this.reset(!0); } function values(e) { if (e || "" === e) { var r = e[a]; if (r) return r.call(e); if ("function" == typeof e.next) return e; if (!isNaN(e.length)) { var o = -1, i = function next() { for (; ++o < e.length;) if (n.call(e, o)) return next.value = e[o], next.done = !1, next; return next.value = t, next.done = !0, next; }; return i.next = i; } } throw new TypeError(_typeof(e) + " is not iterable"); } return GeneratorFunction.prototype = GeneratorFunctionPrototype, o(g, "constructor", { value: GeneratorFunctionPrototype, configurable: !0 }), o(GeneratorFunctionPrototype, "constructor", { value: GeneratorFunction, configurable: !0 }), GeneratorFunction.displayName = define(GeneratorFunctionPrototype, u, "GeneratorFunction"), e.isGeneratorFunction = function (t) { var e = "function" == typeof t && t.constructor; return !!e && (e === GeneratorFunction || "GeneratorFunction" === (e.displayName || e.name)); }, e.mark = function (t) { return Object.setPrototypeOf ? Object.setPrototypeOf(t, GeneratorFunctionPrototype) : (t.__proto__ = GeneratorFunctionPrototype, define(t, u, "GeneratorFunction")), t.prototype = Object.create(g), t; }, e.awrap = function (t) { return { __await: t }; }, defineIteratorMethods(AsyncIterator.prototype), define(AsyncIterator.prototype, c, function () { return this; }), e.AsyncIterator = AsyncIterator, e.async = function (t, r, n, o, i) { void 0 === i && (i = Promise); var a = new AsyncIterator(wrap(t, r, n, o), i); return e.isGeneratorFunction(r) ? a : a.next().then(function (t) { return t.done ? t.value : a.next(); }); }, defineIteratorMethods(g), define(g, u, "Generator"), define(g, a, function () { return this; }), define(g, "toString", function () { return "[object Generator]"; }), e.keys = function (t) { var e = Object(t), r = []; for (var n in e) r.push(n); return r.reverse(), function next() { for (; r.length;) { var t = r.pop(); if (t in e) return next.value = t, next.done = !1, next; } return next.done = !0, next; }; }, e.values = values, Context.prototype = { constructor: Context, reset: function reset(e) { if (this.prev = 0, this.next = 0, this.sent = this._sent = t, this.done = !1, this.delegate = null, this.method = "next", this.arg = t, this.tryEntries.forEach(resetTryEntry), !e) for (var r in this) "t" === r.charAt(0) && n.call(this, r) && !isNaN(+r.slice(1)) && (this[r] = t); }, stop: function stop() { this.done = !0; var t = this.tryEntries[0].completion; if ("throw" === t.type) throw t.arg; return this.rval; }, dispatchException: function dispatchException(e) { if (this.done) throw e; var r = this; function handle(n, o) { return a.type = "throw", a.arg = e, r.next = n, o && (r.method = "next", r.arg = t), !!o; } for (var o = this.tryEntries.length - 1; o >= 0; --o) { var i = this.tryEntries[o], a = i.completion; if ("root" === i.tryLoc) return handle("end"); if (i.tryLoc <= this.prev) { var c = n.call(i, "catchLoc"), u = n.call(i, "finallyLoc"); if (c && u) { if (this.prev < i.catchLoc) return handle(i.catchLoc, !0); if (this.prev < i.finallyLoc) return handle(i.finallyLoc); } else if (c) { if (this.prev < i.catchLoc) return handle(i.catchLoc, !0); } else { if (!u) throw new Error("try statement without catch or finally"); if (this.prev < i.finallyLoc) return handle(i.finallyLoc); } } } }, abrupt: function abrupt(t, e) { for (var r = this.tryEntries.length - 1; r >= 0; --r) { var o = this.tryEntries[r]; if (o.tryLoc <= this.prev && n.call(o, "finallyLoc") && this.prev < o.finallyLoc) { var i = o; break; } } i && ("break" === t || "continue" === t) && i.tryLoc <= e && e <= i.finallyLoc && (i = null); var a = i ? i.completion : {}; return a.type = t, a.arg = e, i ? (this.method = "next", this.next = i.finallyLoc, y) : this.complete(a); }, complete: function complete(t, e) { if ("throw" === t.type) throw t.arg; return "break" === t.type || "continue" === t.type ? this.next = t.arg : "return" === t.type ? (this.rval = this.arg = t.arg, this.method = "return", this.next = "end") : "normal" === t.type && e && (this.next = e), y; }, finish: function finish(t) { for (var e = this.tryEntries.length - 1; e >= 0; --e) { var r = this.tryEntries[e]; if (r.finallyLoc === t) return this.complete(r.completion, r.afterLoc), resetTryEntry(r), y; } }, catch: function _catch(t) { for (var e = this.tryEntries.length - 1; e >= 0; --e) { var r = this.tryEntries[e]; if (r.tryLoc === t) { var n = r.completion; if ("throw" === n.type) { var o = n.arg; resetTryEntry(r); } return o; } } throw new Error("illegal catch attempt"); }, delegateYield: function delegateYield(e, r, n) { return this.delegate = { iterator: values(e), resultName: r, nextLoc: n }, "next" === this.method && (this.arg = t), y; } }, e; }
function _extends() { _extends = Object.assign ? Object.assign.bind() : function (target) { for (var i = 1; i < arguments.length; i++) { var source = arguments[i]; for (var key in source) { if (Object.prototype.hasOwnProperty.call(source, key)) { target[key] = source[key]; } } } return target; }; return _extends.apply(this, arguments); }
function ownKeys(e, r) { var t = Object.keys(e); if (Object.getOwnPropertySymbols) { var o = Object.getOwnPropertySymbols(e); r && (o = o.filter(function (r) { return Object.getOwnPropertyDescriptor(e, r).enumerable; })), t.push.apply(t, o); } return t; }
function _objectSpread(e) { for (var r = 1; r < arguments.length; r++) { var t = null != arguments[r] ? arguments[r] : {}; r % 2 ? ownKeys(Object(t), !0).forEach(function (r) { _defineProperty(e, r, t[r]); }) : Object.getOwnPropertyDescriptors ? Object.defineProperties(e, Object.getOwnPropertyDescriptors(t)) : ownKeys(Object(t)).forEach(function (r) { Object.defineProperty(e, r, Object.getOwnPropertyDescriptor(t, r)); }); } return e; }
function _defineProperty(obj, key, value) { key = _toPropertyKey(key); if (key in obj) { Object.defineProperty(obj, key, { value: value, enumerable: true, configurable: true, writable: true }); } else { obj[key] = value; } return obj; }
function _toPropertyKey(arg) { var key = _toPrimitive(arg, "string"); return _typeof(key) === "symbol" ? key : String(key); }
function _toPrimitive(input, hint) { if (_typeof(input) !== "object" || input === null) return input; var prim = input[Symbol.toPrimitive]; if (prim !== undefined) { var res = prim.call(input, hint || "default"); if (_typeof(res) !== "object") return res; throw new TypeError("@@toPrimitive must return a primitive value."); } return (hint === "string" ? String : Number)(input); }
function _objectWithoutProperties(source, excluded) { if (source == null) return {}; var target = _objectWithoutPropertiesLoose(source, excluded); var key, i; if (Object.getOwnPropertySymbols) { var sourceSymbolKeys = Object.getOwnPropertySymbols(source); for (i = 0; i < sourceSymbolKeys.length; i++) { key = sourceSymbolKeys[i]; if (excluded.indexOf(key) >= 0) continue; if (!Object.prototype.propertyIsEnumerable.call(source, key)) continue; target[key] = source[key]; } } return target; }
function _objectWithoutPropertiesLoose(source, excluded) { if (source == null) return {}; var target = {}; var sourceKeys = Object.keys(source); var key, i; for (i = 0; i < sourceKeys.length; i++) { key = sourceKeys[i]; if (excluded.indexOf(key) >= 0) continue; target[key] = source[key]; } return target; }
function asyncGeneratorStep(gen, resolve, reject, _next, _throw, key, arg) { try { var info = gen[key](arg); var value = info.value; } catch (error) { reject(error); return; } if (info.done) { resolve(value); } else { Promise.resolve(value).then(_next, _throw); } }
function _asyncToGenerator(fn) { return function () { var self = this, args = arguments; return new Promise(function (resolve, reject) { var gen = fn.apply(self, args); function _next(value) { asyncGeneratorStep(gen, resolve, reject, _next, _throw, "next", value); } function _throw(err) { asyncGeneratorStep(gen, resolve, reject, _next, _throw, "throw", err); } _next(undefined); }); }; }
function _slicedToArray(arr, i) { return _arrayWithHoles(arr) || _iterableToArrayLimit(arr, i) || _unsupportedIterableToArray(arr, i) || _nonIterableRest(); }
function _nonIterableRest() { throw new TypeError("Invalid attempt to destructure non-iterable instance.\nIn order to be iterable, non-array objects must have a [Symbol.iterator]() method."); }
function _unsupportedIterableToArray(o, minLen) { if (!o) return; if (typeof o === "string") return _arrayLikeToArray(o, minLen); var n = Object.prototype.toString.call(o).slice(8, -1); if (n === "Object" && o.constructor) n = o.constructor.name; if (n === "Map" || n === "Set") return Array.from(o); if (n === "Arguments" || /^(?:Ui|I)nt(?:8|16|32)(?:Clamped)?Array$/.test(n)) return _arrayLikeToArray(o, minLen); }
function _arrayLikeToArray(arr, len) { if (len == null || len > arr.length) len = arr.length; for (var i = 0, arr2 = new Array(len); i < len; i++) arr2[i] = arr[i]; return arr2; }
function _iterableToArrayLimit(r, l) { var t = null == r ? null : "undefined" != typeof Symbol && r[Symbol.iterator] || r["@@iterator"]; if (null != t) { var e, n, i, u, a = [], f = !0, o = !1; try { if (i = (t = t.call(r)).next, 0 === l) { if (Object(t) !== t) return; f = !1; } else for (; !(f = (e = i.call(t)).done) && (a.push(e.value), a.length !== l); f = !0); } catch (r) { o = !0, n = r; } finally { try { if (!f && null != t.return && (u = t.return(), Object(u) !== u)) return; } finally { if (o) throw n; } } return a; } }
function _arrayWithHoles(arr) { if (Array.isArray(arr)) return arr; }












var TimelineApp = function TimelineApp(_ref) {
  var containerHeight = _ref.containerHeight,
    allowShowDeletedSegments = _ref.allowShowDeletedSegments;
  var _useState = (0,react__WEBPACK_IMPORTED_MODULE_32__.useState)(null),
    _useState2 = _slicedToArray(_useState, 2),
    forwardingEmail = _useState2[0],
    setForwardingEmail = _useState2[1];
  var _useState3 = (0,react__WEBPACK_IMPORTED_MODULE_32__.useState)(true),
    _useState4 = _slicedToArray(_useState3, 2),
    showDeleted = _useState4[0],
    setShowDeleted = _useState4[1];
  var _useState5 = (0,react__WEBPACK_IMPORTED_MODULE_32__.useState)([]),
    _useState6 = _slicedToArray(_useState5, 2),
    segments = _useState6[0],
    setSegments = _useState6[1];
  var _useState7 = (0,react__WEBPACK_IMPORTED_MODULE_32__.useState)(true),
    _useState8 = _slicedToArray(_useState7, 2),
    loadingApp = _useState8[0],
    setLoadingApp = _useState8[1];
  var emptyList = segments.length === 0;
  function loadMore() {
    return _loadMore.apply(this, arguments);
  }
  function _loadMore() {
    _loadMore = _asyncToGenerator( /*#__PURE__*/_regeneratorRuntime().mark(function _callee() {
      var params, before, data, response, _data, newSegments, _forwardingEmail;
      return _regeneratorRuntime().wrap(function _callee$(_context) {
        while (1) switch (_context.prev = _context.next) {
          case 0:
            params = {
              showDeleted: showDeleted ? 1 : 0
            };
            before = lodash__WEBPACK_IMPORTED_MODULE_37___default().get(lodash__WEBPACK_IMPORTED_MODULE_37___default().last(segments), 'startDate', null);
            if (!lodash__WEBPACK_IMPORTED_MODULE_37___default().isNull(before)) {
              params.before = before;
            }
            _context.prev = 3;
            _context.next = 6;
            return _bem_ts_service_axios__WEBPACK_IMPORTED_MODULE_28__["default"].get(_bem_ts_service_router__WEBPACK_IMPORTED_MODULE_33__["default"].generate('aw_timeline_data', params));
          case 6:
            response = _context.sent;
            data = response.data;
            // eslint-disable-next-line
            _context.next = 12;
            break;
          case 10:
            _context.prev = 10;
            _context.t0 = _context["catch"](3);
          case 12:
            if (data) {
              _data = data, newSegments = _data.segments, _forwardingEmail = _data.forwardingEmail;
              setSegments(function (segments) {
                return lodash__WEBPACK_IMPORTED_MODULE_37___default().unionBy(segments, newSegments ? newSegments : [], function (segment) {
                  return segment.id;
                });
              });
              setForwardingEmail(_forwardingEmail);
              setLoadingApp(false);
            }
          case 13:
          case "end":
            return _context.stop();
        }
      }, _callee, null, [[3, 10]]);
    }));
    return _loadMore.apply(this, arguments);
  }
  function itemKey(index) {
    return segments[index].id;
  }
  function SegmentRenderer(index, ref) {
    var segmentData = segments[index];
    // eslint-disable-next-line react/prop-types
    var segmentType = segmentData.type,
      segmentProps = _objectWithoutProperties(segmentData, _excluded);
    if (lodash__WEBPACK_IMPORTED_MODULE_37___default().has(_segment__WEBPACK_IMPORTED_MODULE_34__["default"], segmentType)) {
      return /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_32___default().createElement(_segment__WEBPACK_IMPORTED_MODULE_34__["default"][segmentType], _objectSpread(_objectSpread({}, segmentProps), {}, {
        ref: ref
      }));
    }
    return /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_32___default().createElement(_segment__WEBPACK_IMPORTED_MODULE_34__["default"].segment, _objectSpread(_objectSpread({}, segmentProps), {}, {
      ref: ref
    }));
  }
  var TimelineContainer = /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_32___default().forwardRef(function (_ref2, ref) {
    var children = _ref2.children,
      props = _objectWithoutProperties(_ref2, _excluded2);
    return /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_32___default().createElement((react__WEBPACK_IMPORTED_MODULE_32___default().Fragment), null, !lodash__WEBPACK_IMPORTED_MODULE_37___default().isEmpty(forwardingEmail) && /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_32___default().createElement(_MailboxOffer__WEBPACK_IMPORTED_MODULE_30__["default"], {
      forwardingEmail: forwardingEmail
    }), /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_32___default().createElement(_PastSegmentsLoaderLink__WEBPACK_IMPORTED_MODULE_31__["default"], null), allowShowDeletedSegments && /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_32___default().createElement(_ShowDeletedSegmentsLink__WEBPACK_IMPORTED_MODULE_35__["default"], {
      reverse: showDeleted
    }), /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_32___default().createElement("div", _extends({
      ref: ref
    }, props, {
      className: 'trip-list'
    }), children));
  });
  TimelineContainer.displayName = 'TimelineContainer';
  TimelineContainer.propTypes = {
    children: (prop_types__WEBPACK_IMPORTED_MODULE_38___default().node)
  };
  (0,react__WEBPACK_IMPORTED_MODULE_32__.useEffect)(function () {
    loadMore();
    // eslint-disable-next-line
  }, []);
  if (loadingApp) {
    return /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_32___default().createElement("div", {
      className: "trip"
    }, /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_32___default().createElement(_Spinner__WEBPACK_IMPORTED_MODULE_36__["default"], null));
  }
  return /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_32___default().createElement("div", {
    className: "trip",
    style: {
      height: containerHeight
    }
  }, !emptyList && /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_32___default().createElement(_infiniteList__WEBPACK_IMPORTED_MODULE_27__.LazyList, {
    itemCount: segments.length,
    loadMore: loadMore,
    height: containerHeight,
    listProps: {
      itemKey: itemKey,
      innerElementType: TimelineContainer,
      style: {
        overflowY: 'scroll'
      },
      innerStyle: {
        width: 'inherit'
      }
    }
  }, SegmentRenderer), emptyList && /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_32___default().createElement(_EmptyList__WEBPACK_IMPORTED_MODULE_29__["default"], null));
};
TimelineApp.propTypes = {
  containerHeight: (prop_types__WEBPACK_IMPORTED_MODULE_38___default().number).isRequired,
  allowShowDeletedSegments: (prop_types__WEBPACK_IMPORTED_MODULE_38___default().bool)
};
TimelineApp.defaultProps = {
  allowShowDeletedSegments: false
};
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (TimelineApp);

/***/ }),

/***/ "./assets/js-deprecated/component-deprecated/timeline/segment/Date.js":
/*!****************************************************************************!*\
  !*** ./assets/js-deprecated/component-deprecated/timeline/segment/Date.js ***!
  \****************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var core_js_modules_es_array_slice_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! core-js/modules/es.array.slice.js */ "./node_modules/core-js/modules/es.array.slice.js");
/* harmony import */ var core_js_modules_es_array_slice_js__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_array_slice_js__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var lodash__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! lodash */ "./node_modules/lodash/lodash.js");
/* harmony import */ var lodash__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(lodash__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _bem_ts_service_date_time_diff__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ../../../../bem/ts/service/date-time-diff */ "./assets/bem/ts/service/date-time-diff.ts");
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! react */ "./node_modules/react/index.js");
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(react__WEBPACK_IMPORTED_MODULE_3__);
/* harmony import */ var classnames__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! classnames */ "./node_modules/classnames/index.js");
/* harmony import */ var classnames__WEBPACK_IMPORTED_MODULE_4___default = /*#__PURE__*/__webpack_require__.n(classnames__WEBPACK_IMPORTED_MODULE_4__);






var DateSegment = /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_3___default().forwardRef(function (props, ref) {
  var id = props.id,
    startDate = props.startDate,
    localDate = props.localDate,
    localDateISO = props.localDateISO;
  function getRelativeDate() {
    return _bem_ts_service_date_time_diff__WEBPACK_IMPORTED_MODULE_2__["default"].longFormatViaDates(new Date(), new Date(localDateISO));
  }
  function getDaysNumberFromToday() {
    var diff = Math.abs(new Date(startDate * 1000) - new Date());
    return Math.floor(diff / 1000 / 60 / 60 / 24);
  }
  function capitalize(str) {
    return str.charAt(0).toUpperCase() + str.slice(1);
  }
  function getDateBlock() {
    var relativeDate = getRelativeDate();
    if (getDaysNumberFromToday() <= 30) {
      return /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_3___default().createElement((react__WEBPACK_IMPORTED_MODULE_3___default().Fragment), null, capitalize(relativeDate), /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_3___default().createElement("span", {
        className: "date"
      }, capitalize(localDate)));
    }
    return /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_3___default().createElement((react__WEBPACK_IMPORTED_MODULE_3___default().Fragment), null, capitalize(localDate), /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_3___default().createElement("span", {
      className: "date"
    }, capitalize(relativeDate)));
  }
  var className = classnames__WEBPACK_IMPORTED_MODULE_4___default()({
    'trip-blk': true,
    disable: function () {
      var dayStart = new Date();
      dayStart.setHours(0, 0, 0, 0);
      return startDate <= dayStart / 1000;
    }()
  });
  return /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_3___default().createElement("div", {
    className: className,
    ref: ref,
    id: id
  }, /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_3___default().createElement("div", {
    "data-id": id,
    className: "date-blk"
  }, /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_3___default().createElement("div", null, getDateBlock())));
});
DateSegment.displayName = 'Date';
DateSegment.propTypes = {
  // id: PropTypes.string.isRequired,
  // startDate: PropTypes.number.isRequired, // unix timestamp
  // endDate: PropTypes.number.isRequired, // unix timestamp
  // startTimezone: PropTypes.string.isRequired,
  // breakAfter: PropTypes.bool.isRequired, // can we set past/future breakpoint after this item?
  //
  // localDate: PropTypes.string.isRequired,
  // localDateISO: PropTypes.string.isRequired,
  // localDateTimeISO: PropTypes.string.isRequired,
  // createPlan: PropTypes.bool.isRequired,
};
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (DateSegment);

/***/ }),

/***/ "./assets/js-deprecated/component-deprecated/timeline/segment/PlanEnd.js":
/*!*******************************************************************************!*\
  !*** ./assets/js-deprecated/component-deprecated/timeline/segment/PlanEnd.js ***!
  \*******************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! react */ "./node_modules/react/index.js");
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(react__WEBPACK_IMPORTED_MODULE_0__);


var PlanEnd = /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().forwardRef(function (props, ref) {
  var id = props.id;
  return /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("div", null);
});
PlanEnd.displayName = 'PlanEnd';
PlanEnd.propTypes = {
  // id: PropTypes.string.isRequired,
  // startDate: PropTypes.number.isRequired, // unix timestamp
  // endDate: PropTypes.number.isRequired, // unix timestamp
  // startTimezone: PropTypes.string.isRequired,
  // breakAfter: PropTypes.bool.isRequired, // can we set past/future breakpoint after this item?
  //
  // name: PropTypes.string.isRequired,
  // planId: PropTypes.number.isRequired,
  // localDate: PropTypes.string.isRequired,
  // canEdit: PropTypes.bool.isRequired,
};
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (PlanEnd);

/***/ }),

/***/ "./assets/js-deprecated/component-deprecated/timeline/segment/PlanStart.js":
/*!*********************************************************************************!*\
  !*** ./assets/js-deprecated/component-deprecated/timeline/segment/PlanStart.js ***!
  \*********************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! react */ "./node_modules/react/index.js");
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(react__WEBPACK_IMPORTED_MODULE_0__);


var PlanStart = /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().forwardRef(function (props, ref) {
  var id = props.id;
  return /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("div", null);
});
PlanStart.displayName = 'PlanStart';
PlanStart.propTypes = {
  // id: PropTypes.string.isRequired,
  // startDate: PropTypes.number.isRequired, // unix timestamp
  // endDate: PropTypes.number.isRequired, // unix timestamp
  // breakAfter: PropTypes.bool.isRequired, // can we set past/future breakpoint after this item?
  //
  // name: PropTypes.string.isRequired,
  // planId: PropTypes.number.isRequired,
  // canEdit: PropTypes.bool.isRequired,
  // map: PropTypes.shape({
  //     points: PropTypes.arrayOf(PropTypes.string),
  //     arrTime: PropTypes.string,
  // }).isRequired,
  // localDate: PropTypes.string.isRequired,
  // lastUpdated: PropTypes.number,
  // shareCode: PropTypes.string,
};
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (PlanStart);

/***/ }),

/***/ "./assets/js-deprecated/component-deprecated/timeline/segment/Segment.js":
/*!*******************************************************************************!*\
  !*** ./assets/js-deprecated/component-deprecated/timeline/segment/Segment.js ***!
  \*******************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var core_js_modules_es_array_map_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! core-js/modules/es.array.map.js */ "./node_modules/core-js/modules/es.array.map.js");
/* harmony import */ var core_js_modules_es_array_map_js__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_array_map_js__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var core_js_modules_es_symbol_to_primitive_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! core-js/modules/es.symbol.to-primitive.js */ "./node_modules/core-js/modules/es.symbol.to-primitive.js");
/* harmony import */ var core_js_modules_es_symbol_to_primitive_js__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_symbol_to_primitive_js__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var core_js_modules_es_date_to_primitive_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! core-js/modules/es.date.to-primitive.js */ "./node_modules/core-js/modules/es.date.to-primitive.js");
/* harmony import */ var core_js_modules_es_date_to_primitive_js__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_date_to_primitive_js__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var core_js_modules_es_symbol_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! core-js/modules/es.symbol.js */ "./node_modules/core-js/modules/es.symbol.js");
/* harmony import */ var core_js_modules_es_symbol_js__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_symbol_js__WEBPACK_IMPORTED_MODULE_3__);
/* harmony import */ var core_js_modules_es_symbol_description_js__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! core-js/modules/es.symbol.description.js */ "./node_modules/core-js/modules/es.symbol.description.js");
/* harmony import */ var core_js_modules_es_symbol_description_js__WEBPACK_IMPORTED_MODULE_4___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_symbol_description_js__WEBPACK_IMPORTED_MODULE_4__);
/* harmony import */ var core_js_modules_es_object_to_string_js__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! core-js/modules/es.object.to-string.js */ "./node_modules/core-js/modules/es.object.to-string.js");
/* harmony import */ var core_js_modules_es_object_to_string_js__WEBPACK_IMPORTED_MODULE_5___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_object_to_string_js__WEBPACK_IMPORTED_MODULE_5__);
/* harmony import */ var core_js_modules_es_number_constructor_js__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! core-js/modules/es.number.constructor.js */ "./node_modules/core-js/modules/es.number.constructor.js");
/* harmony import */ var core_js_modules_es_number_constructor_js__WEBPACK_IMPORTED_MODULE_6___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_number_constructor_js__WEBPACK_IMPORTED_MODULE_6__);
/* harmony import */ var core_js_modules_es_symbol_iterator_js__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__(/*! core-js/modules/es.symbol.iterator.js */ "./node_modules/core-js/modules/es.symbol.iterator.js");
/* harmony import */ var core_js_modules_es_symbol_iterator_js__WEBPACK_IMPORTED_MODULE_7___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_symbol_iterator_js__WEBPACK_IMPORTED_MODULE_7__);
/* harmony import */ var core_js_modules_es_array_iterator_js__WEBPACK_IMPORTED_MODULE_8__ = __webpack_require__(/*! core-js/modules/es.array.iterator.js */ "./node_modules/core-js/modules/es.array.iterator.js");
/* harmony import */ var core_js_modules_es_array_iterator_js__WEBPACK_IMPORTED_MODULE_8___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_array_iterator_js__WEBPACK_IMPORTED_MODULE_8__);
/* harmony import */ var core_js_modules_es_string_iterator_js__WEBPACK_IMPORTED_MODULE_9__ = __webpack_require__(/*! core-js/modules/es.string.iterator.js */ "./node_modules/core-js/modules/es.string.iterator.js");
/* harmony import */ var core_js_modules_es_string_iterator_js__WEBPACK_IMPORTED_MODULE_9___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_string_iterator_js__WEBPACK_IMPORTED_MODULE_9__);
/* harmony import */ var core_js_modules_web_dom_collections_iterator_js__WEBPACK_IMPORTED_MODULE_10__ = __webpack_require__(/*! core-js/modules/web.dom-collections.iterator.js */ "./node_modules/core-js/modules/web.dom-collections.iterator.js");
/* harmony import */ var core_js_modules_web_dom_collections_iterator_js__WEBPACK_IMPORTED_MODULE_10___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_web_dom_collections_iterator_js__WEBPACK_IMPORTED_MODULE_10__);
/* harmony import */ var prop_types__WEBPACK_IMPORTED_MODULE_15__ = __webpack_require__(/*! prop-types */ "./node_modules/prop-types/index.js");
/* harmony import */ var prop_types__WEBPACK_IMPORTED_MODULE_15___default = /*#__PURE__*/__webpack_require__.n(prop_types__WEBPACK_IMPORTED_MODULE_15__);
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_11__ = __webpack_require__(/*! react */ "./node_modules/react/index.js");
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_11___default = /*#__PURE__*/__webpack_require__.n(react__WEBPACK_IMPORTED_MODULE_11__);
/* harmony import */ var _bem_ts_service_translator__WEBPACK_IMPORTED_MODULE_12__ = __webpack_require__(/*! ../../../../bem/ts/service/translator */ "./assets/bem/ts/service/translator.ts");
/* harmony import */ var lodash__WEBPACK_IMPORTED_MODULE_13__ = __webpack_require__(/*! lodash */ "./node_modules/lodash/lodash.js");
/* harmony import */ var lodash__WEBPACK_IMPORTED_MODULE_13___default = /*#__PURE__*/__webpack_require__.n(lodash__WEBPACK_IMPORTED_MODULE_13__);
/* harmony import */ var classnames__WEBPACK_IMPORTED_MODULE_14__ = __webpack_require__(/*! classnames */ "./node_modules/classnames/index.js");
/* harmony import */ var classnames__WEBPACK_IMPORTED_MODULE_14___default = /*#__PURE__*/__webpack_require__.n(classnames__WEBPACK_IMPORTED_MODULE_14__);
function _typeof(o) { "@babel/helpers - typeof"; return _typeof = "function" == typeof Symbol && "symbol" == typeof Symbol.iterator ? function (o) { return typeof o; } : function (o) { return o && "function" == typeof Symbol && o.constructor === Symbol && o !== Symbol.prototype ? "symbol" : typeof o; }, _typeof(o); }
function _defineProperty(obj, key, value) { key = _toPropertyKey(key); if (key in obj) { Object.defineProperty(obj, key, { value: value, enumerable: true, configurable: true, writable: true }); } else { obj[key] = value; } return obj; }
function _toPropertyKey(arg) { var key = _toPrimitive(arg, "string"); return _typeof(key) === "symbol" ? key : String(key); }
function _toPrimitive(input, hint) { if (_typeof(input) !== "object" || input === null) return input; var prim = input[Symbol.toPrimitive]; if (prim !== undefined) { var res = prim.call(input, hint || "default"); if (_typeof(res) !== "object") return res; throw new TypeError("@@toPrimitive must return a primitive value."); } return (hint === "string" ? String : Number)(input); }
















var Segment = /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_11___default().forwardRef(function (props, ref) {
  var _classNames;
  var id = props.id,
    icon = props.icon,
    changed = props.changed,
    endDate = props.endDate,
    details = props.details,
    _props$deleted = props.deleted,
    deleted = _props$deleted === void 0 ? false : _props$deleted,
    startTimezone = props.startTimezone,
    prevTime = props.prevTime,
    localTime = props.localTime,
    title = props.title,
    confNo = props.confNo,
    map = props.map;
  var className = classnames__WEBPACK_IMPORTED_MODULE_14___default()({
    disable: endDate <= Date.now() / 1000,
    'no-hand': !details,
    'deleted-segment': deleted
  });
  var tripRowClassIcon = icon.split(' ').shift();
  var tripRowClass = classnames__WEBPACK_IMPORTED_MODULE_14___default()((_classNames = {
    'trip-row': true
  }, _defineProperty(_classNames, 'trip--' + tripRowClassIcon, true), _defineProperty(_classNames, "error", changed), _defineProperty(_classNames, "active", false), _classNames));
  function getLocalTime(time) {
    var parts = time.split(' ');
    if (parts.length > 1) {
      return /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_11___default().createElement((react__WEBPACK_IMPORTED_MODULE_11___default().Fragment), null, parts[0], /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_11___default().createElement("span", null, parts[1]));
    }
    return time;
  }
  return /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_11___default().createElement("div", {
    className: className,
    ref: ref
  }, deleted && /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_11___default().createElement("div", {
    className: "deleted-message"
  }, /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_11___default().createElement("span", null, _bem_ts_service_translator__WEBPACK_IMPORTED_MODULE_12__["default"].trans('segment.deleted'))), /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_11___default().createElement("div", {
    className: tripRowClass
  }, /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_11___default().createElement("div", {
    className: "time"
  }, /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_11___default().createElement("div", {
    className: "time-zone"
  }, startTimezone), /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_11___default().createElement("div", {
    className: "time-item"
  }, lodash__WEBPACK_IMPORTED_MODULE_13___default().isString(prevTime) && changed && /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_11___default().createElement("p", {
    className: "old-time"
  }, prevTime), /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_11___default().createElement("p", null, getLocalTime(localTime)))), /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_11___default().createElement("div", {
    className: classnames__WEBPACK_IMPORTED_MODULE_14___default()(_defineProperty({
      'trip-title': true
    }, icon, true)),
    "data-id": id
  }, /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_11___default().createElement("div", {
    className: "item"
  }, /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_11___default().createElement("div", {
    className: "arrow"
  }, /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_11___default().createElement("i", {
    className: "icon-silver-arrow-down"
  })), /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_11___default().createElement("div", {
    className: "prev"
  }, /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_11___default().createElement("div", {
    className: "prev-item"
  }, /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_11___default().createElement("i", {
    className: classnames__WEBPACK_IMPORTED_MODULE_14___default()(_defineProperty({}, 'icon-' + icon, true))
  }))), /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_11___default().createElement("div", {
    className: "title"
  }, /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_11___default().createElement("h3", {
    dangerouslySetInnerHTML: {
      __html: title
    }
  })), /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_11___default().createElement("div", {
    className: "number"
  }, lodash__WEBPACK_IMPORTED_MODULE_13___default().isString(confNo) && /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_11___default().createElement((react__WEBPACK_IMPORTED_MODULE_11___default().Fragment), null, _bem_ts_service_translator__WEBPACK_IMPORTED_MODULE_12__["default"].trans('timeline.section.conf'), " ", confNo)), lodash__WEBPACK_IMPORTED_MODULE_13___default().isArray(map) && /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_11___default().createElement("div", {
    className: "map"
  }, /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_11___default().createElement("img", {
    style: {
      width: '44px',
      height: '44px'
    },
    src: "/trips/gcmap.php?code=45.4420641%2C%2013.5237425&size=88x88",
    alt: "map"
  }))))));
});
Segment.displayName = 'Segment';
Segment.propTypes = {
  id: (prop_types__WEBPACK_IMPORTED_MODULE_15___default().string).isRequired,
  startDate: (prop_types__WEBPACK_IMPORTED_MODULE_15___default().number).isRequired,
  // unix timestamp
  endDate: (prop_types__WEBPACK_IMPORTED_MODULE_15___default().number).isRequired,
  // unix timestamp
  startTimezone: (prop_types__WEBPACK_IMPORTED_MODULE_15___default().string).isRequired,
  breakAfter: (prop_types__WEBPACK_IMPORTED_MODULE_15___default().bool).isRequired,
  // can we set past/future breakpoint after this item?

  icon: (prop_types__WEBPACK_IMPORTED_MODULE_15___default().string).isRequired,
  localTime: (prop_types__WEBPACK_IMPORTED_MODULE_15___default().string).isRequired,
  localDate: (prop_types__WEBPACK_IMPORTED_MODULE_15___default().number).isRequired,
  localDateISO: (prop_types__WEBPACK_IMPORTED_MODULE_15___default().string).isRequired,
  localDateTimeISO: (prop_types__WEBPACK_IMPORTED_MODULE_15___default().string).isRequired,
  map: prop_types__WEBPACK_IMPORTED_MODULE_15___default().shape({
    points: prop_types__WEBPACK_IMPORTED_MODULE_15___default().arrayOf((prop_types__WEBPACK_IMPORTED_MODULE_15___default().string)),
    arrTime: prop_types__WEBPACK_IMPORTED_MODULE_15___default().oneOfType([(prop_types__WEBPACK_IMPORTED_MODULE_15___default().string), (prop_types__WEBPACK_IMPORTED_MODULE_15___default().bool)])
  }),
  details: prop_types__WEBPACK_IMPORTED_MODULE_15___default().shape({
    accountId: (prop_types__WEBPACK_IMPORTED_MODULE_15___default().number),
    agentId: (prop_types__WEBPACK_IMPORTED_MODULE_15___default().number),
    refreshLink: (prop_types__WEBPACK_IMPORTED_MODULE_15___default().string),
    autoLoginLink: (prop_types__WEBPACK_IMPORTED_MODULE_15___default().string),
    bookingLink: prop_types__WEBPACK_IMPORTED_MODULE_15___default().shape({
      info: (prop_types__WEBPACK_IMPORTED_MODULE_15___default().string).isRequired,
      url: (prop_types__WEBPACK_IMPORTED_MODULE_15___default().string).isRequired,
      formFields: prop_types__WEBPACK_IMPORTED_MODULE_15___default().shape({
        destination: (prop_types__WEBPACK_IMPORTED_MODULE_15___default().string).isRequired,
        checkinDate: (prop_types__WEBPACK_IMPORTED_MODULE_15___default().string).isRequired,
        checkoutDate: (prop_types__WEBPACK_IMPORTED_MODULE_15___default().string).isRequired,
        url: (prop_types__WEBPACK_IMPORTED_MODULE_15___default().string).isRequired
      }).isRequired
    }),
    canEdit: (prop_types__WEBPACK_IMPORTED_MODULE_15___default().bool),
    canCheck: (prop_types__WEBPACK_IMPORTED_MODULE_15___default().bool),
    canAutoLogin: (prop_types__WEBPACK_IMPORTED_MODULE_15___default().bool),
    Status: (prop_types__WEBPACK_IMPORTED_MODULE_15___default().string),
    shareCode: (prop_types__WEBPACK_IMPORTED_MODULE_15___default().string),
    monitoredStatus: (prop_types__WEBPACK_IMPORTED_MODULE_15___default().string),
    columns: prop_types__WEBPACK_IMPORTED_MODULE_15___default().arrayOf(prop_types__WEBPACK_IMPORTED_MODULE_15___default().oneOfType([prop_types__WEBPACK_IMPORTED_MODULE_15___default().shape({
      type: 'arrow'
    }), prop_types__WEBPACK_IMPORTED_MODULE_15___default().shape({
      type: 'info',
      rows: prop_types__WEBPACK_IMPORTED_MODULE_15___default().arrayOf(prop_types__WEBPACK_IMPORTED_MODULE_15___default().oneOfType([prop_types__WEBPACK_IMPORTED_MODULE_15___default().shape({
        type: 'arrow'
      }), prop_types__WEBPACK_IMPORTED_MODULE_15___default().shape({
        type: 'checkin',
        date: (prop_types__WEBPACK_IMPORTED_MODULE_15___default().string).isRequired,
        nights: (prop_types__WEBPACK_IMPORTED_MODULE_15___default().number).isRequired
      }), prop_types__WEBPACK_IMPORTED_MODULE_15___default().shape({
        type: 'datetime',
        date: (prop_types__WEBPACK_IMPORTED_MODULE_15___default().string).isRequired,
        time: (prop_types__WEBPACK_IMPORTED_MODULE_15___default().string).isRequired,
        prevTime: (prop_types__WEBPACK_IMPORTED_MODULE_15___default().string),
        prevDate: (prop_types__WEBPACK_IMPORTED_MODULE_15___default().string),
        timestamp: (prop_types__WEBPACK_IMPORTED_MODULE_15___default().number),
        timezone: (prop_types__WEBPACK_IMPORTED_MODULE_15___default().string),
        formattedDate: (prop_types__WEBPACK_IMPORTED_MODULE_15___default().string),
        arrivalDay: (prop_types__WEBPACK_IMPORTED_MODULE_15___default().string)
      }), prop_types__WEBPACK_IMPORTED_MODULE_15___default().shape({
        type: 'text',
        text: (prop_types__WEBPACK_IMPORTED_MODULE_15___default().string).isRequired,
        geo: prop_types__WEBPACK_IMPORTED_MODULE_15___default().shape({
          country: (prop_types__WEBPACK_IMPORTED_MODULE_15___default().string),
          state: (prop_types__WEBPACK_IMPORTED_MODULE_15___default().string),
          city: (prop_types__WEBPACK_IMPORTED_MODULE_15___default().string)
        })
      }), prop_types__WEBPACK_IMPORTED_MODULE_15___default().shape({
        type: 'pairs',
        pairs: prop_types__WEBPACK_IMPORTED_MODULE_15___default().arrayOf((prop_types__WEBPACK_IMPORTED_MODULE_15___default().object)).isRequired
      }), prop_types__WEBPACK_IMPORTED_MODULE_15___default().shape({
        type: 'pair',
        name: 'Guests',
        value: (prop_types__WEBPACK_IMPORTED_MODULE_15___default().number).isRequired
      }), prop_types__WEBPACK_IMPORTED_MODULE_15___default().shape({
        type: 'parkingStart',
        date: (prop_types__WEBPACK_IMPORTED_MODULE_15___default().string).isRequired,
        days: (prop_types__WEBPACK_IMPORTED_MODULE_15___default().number).isRequired
      }), prop_types__WEBPACK_IMPORTED_MODULE_15___default().shape({
        type: 'pickup',
        date: (prop_types__WEBPACK_IMPORTED_MODULE_15___default().string).isRequired,
        days: (prop_types__WEBPACK_IMPORTED_MODULE_15___default().number).isRequired
      }), prop_types__WEBPACK_IMPORTED_MODULE_15___default().shape({
        type: 'pickup.taxi',
        date: (prop_types__WEBPACK_IMPORTED_MODULE_15___default().string).isRequired,
        time: (prop_types__WEBPACK_IMPORTED_MODULE_15___default().string).isRequired
      }), prop_types__WEBPACK_IMPORTED_MODULE_15___default().shape({
        type: 'dropoff',
        date: (prop_types__WEBPACK_IMPORTED_MODULE_15___default().string).isRequired,
        time: (prop_types__WEBPACK_IMPORTED_MODULE_15___default().string).isRequired
      }), prop_types__WEBPACK_IMPORTED_MODULE_15___default().shape({
        type: 'airport',
        text: prop_types__WEBPACK_IMPORTED_MODULE_15___default().shape({
          place: (prop_types__WEBPACK_IMPORTED_MODULE_15___default().string).isRequired,
          code: (prop_types__WEBPACK_IMPORTED_MODULE_15___default().string).isRequired
        }).isRequired
      })]))
    })])),
    Fax: (prop_types__WEBPACK_IMPORTED_MODULE_15___default().string),
    GuestCount: (prop_types__WEBPACK_IMPORTED_MODULE_15___default().number),
    KidsCount: (prop_types__WEBPACK_IMPORTED_MODULE_15___default().number),
    Rooms: (prop_types__WEBPACK_IMPORTED_MODULE_15___default().number),
    RoomLongDescriptions: (prop_types__WEBPACK_IMPORTED_MODULE_15___default().string),
    RoomShortDescriptions: (prop_types__WEBPACK_IMPORTED_MODULE_15___default().string),
    RoomRate: (prop_types__WEBPACK_IMPORTED_MODULE_15___default().string),
    RoomRateDescription: (prop_types__WEBPACK_IMPORTED_MODULE_15___default().string),
    TravelerNames: (prop_types__WEBPACK_IMPORTED_MODULE_15___default().string),
    CancellationPolicy: (prop_types__WEBPACK_IMPORTED_MODULE_15___default().string),
    CarDescription: (prop_types__WEBPACK_IMPORTED_MODULE_15___default().string),
    LicensePlate: (prop_types__WEBPACK_IMPORTED_MODULE_15___default().string),
    SpotNumber: (prop_types__WEBPACK_IMPORTED_MODULE_15___default().string),
    CarModel: (prop_types__WEBPACK_IMPORTED_MODULE_15___default().string),
    CarType: (prop_types__WEBPACK_IMPORTED_MODULE_15___default().string),
    PickUpFax: (prop_types__WEBPACK_IMPORTED_MODULE_15___default().string),
    DropOffFax: (prop_types__WEBPACK_IMPORTED_MODULE_15___default().string),
    DinerName: (prop_types__WEBPACK_IMPORTED_MODULE_15___default().string),
    CruiseName: (prop_types__WEBPACK_IMPORTED_MODULE_15___default().string),
    Deck: (prop_types__WEBPACK_IMPORTED_MODULE_15___default().string),
    CabinNumber: (prop_types__WEBPACK_IMPORTED_MODULE_15___default().string),
    ShipCode: (prop_types__WEBPACK_IMPORTED_MODULE_15___default().string),
    ShipName: (prop_types__WEBPACK_IMPORTED_MODULE_15___default().string),
    ShipCabinClass: (prop_types__WEBPACK_IMPORTED_MODULE_15___default().string),
    Smoking: (prop_types__WEBPACK_IMPORTED_MODULE_15___default().string),
    Stops: (prop_types__WEBPACK_IMPORTED_MODULE_15___default().number),
    ServiceClasses: (prop_types__WEBPACK_IMPORTED_MODULE_15___default().string),
    ServiceName: (prop_types__WEBPACK_IMPORTED_MODULE_15___default().string),
    CarNumber: (prop_types__WEBPACK_IMPORTED_MODULE_15___default().string),
    AdultsCount: (prop_types__WEBPACK_IMPORTED_MODULE_15___default().number),
    Aircraft: (prop_types__WEBPACK_IMPORTED_MODULE_15___default().string),
    TicketNumbers: (prop_types__WEBPACK_IMPORTED_MODULE_15___default().string),
    TravelledMiles: (prop_types__WEBPACK_IMPORTED_MODULE_15___default().string),
    Meal: (prop_types__WEBPACK_IMPORTED_MODULE_15___default().string),
    BookingClass: (prop_types__WEBPACK_IMPORTED_MODULE_15___default().string),
    CabinClass: (prop_types__WEBPACK_IMPORTED_MODULE_15___default().string),
    phone: (prop_types__WEBPACK_IMPORTED_MODULE_15___default().string)
  }),
  origins: prop_types__WEBPACK_IMPORTED_MODULE_15___default().shape({
    auto: prop_types__WEBPACK_IMPORTED_MODULE_15___default().arrayOf(prop_types__WEBPACK_IMPORTED_MODULE_15___default().oneOfType([prop_types__WEBPACK_IMPORTED_MODULE_15___default().shape({
      type: 'account',
      accountId: (prop_types__WEBPACK_IMPORTED_MODULE_15___default().number).isRequired,
      provider: (prop_types__WEBPACK_IMPORTED_MODULE_15___default().string).isRequired,
      accountNumber: (prop_types__WEBPACK_IMPORTED_MODULE_15___default().string).isRequired,
      owner: (prop_types__WEBPACK_IMPORTED_MODULE_15___default().string).isRequired
    }), prop_types__WEBPACK_IMPORTED_MODULE_15___default().shape({
      type: 'confNumber',
      provider: (prop_types__WEBPACK_IMPORTED_MODULE_15___default().string).isRequired,
      confNumber: (prop_types__WEBPACK_IMPORTED_MODULE_15___default().string).isRequired
    }), prop_types__WEBPACK_IMPORTED_MODULE_15___default().shape({
      type: 'email',
      from: (prop_types__WEBPACK_IMPORTED_MODULE_15___default().number).isRequired,
      email: (prop_types__WEBPACK_IMPORTED_MODULE_15___default().string)
    })])),
    manual: (prop_types__WEBPACK_IMPORTED_MODULE_15___default().bool)
  }),
  confNo: (prop_types__WEBPACK_IMPORTED_MODULE_15___default().string),
  group: (prop_types__WEBPACK_IMPORTED_MODULE_15___default().string),
  changed: (prop_types__WEBPACK_IMPORTED_MODULE_15___default().bool),
  deleted: (prop_types__WEBPACK_IMPORTED_MODULE_15___default().bool),
  lastSync: (prop_types__WEBPACK_IMPORTED_MODULE_15___default().number),
  lastUpdated: (prop_types__WEBPACK_IMPORTED_MODULE_15___default().number),
  title: (prop_types__WEBPACK_IMPORTED_MODULE_15___default().string),
  prevTime: (prop_types__WEBPACK_IMPORTED_MODULE_15___default().string),
  segments: (prop_types__WEBPACK_IMPORTED_MODULE_15___default().number)
};
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (Segment);

/***/ }),

/***/ "./assets/js-deprecated/component-deprecated/timeline/segment/index.js":
/*!*****************************************************************************!*\
  !*** ./assets/js-deprecated/component-deprecated/timeline/segment/index.js ***!
  \*****************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _Date__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./Date */ "./assets/js-deprecated/component-deprecated/timeline/segment/Date.js");
/* harmony import */ var _PlanEnd__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./PlanEnd */ "./assets/js-deprecated/component-deprecated/timeline/segment/PlanEnd.js");
/* harmony import */ var _PlanStart__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./PlanStart */ "./assets/js-deprecated/component-deprecated/timeline/segment/PlanStart.js");
/* harmony import */ var _Segment__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ./Segment */ "./assets/js-deprecated/component-deprecated/timeline/segment/Segment.js");




/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = ({
  'date': _Date__WEBPACK_IMPORTED_MODULE_0__["default"],
  'planStart': _PlanStart__WEBPACK_IMPORTED_MODULE_2__["default"],
  'planEnd': _PlanEnd__WEBPACK_IMPORTED_MODULE_1__["default"],
  'segment': _Segment__WEBPACK_IMPORTED_MODULE_3__["default"]
});

/***/ }),

/***/ "./assets/bem/ts/hook/useForceUpdate.ts":
/*!**********************************************!*\
  !*** ./assets/bem/ts/hook/useForceUpdate.ts ***!
  \**********************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* binding */ useForceUpdate)
/* harmony export */ });
/* harmony import */ var core_js_modules_es_symbol_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! core-js/modules/es.symbol.js */ "./node_modules/core-js/modules/es.symbol.js");
/* harmony import */ var core_js_modules_es_symbol_js__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_symbol_js__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var core_js_modules_es_symbol_description_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! core-js/modules/es.symbol.description.js */ "./node_modules/core-js/modules/es.symbol.description.js");
/* harmony import */ var core_js_modules_es_symbol_description_js__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_symbol_description_js__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var core_js_modules_es_object_to_string_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! core-js/modules/es.object.to-string.js */ "./node_modules/core-js/modules/es.object.to-string.js");
/* harmony import */ var core_js_modules_es_object_to_string_js__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_object_to_string_js__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var core_js_modules_es_symbol_iterator_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! core-js/modules/es.symbol.iterator.js */ "./node_modules/core-js/modules/es.symbol.iterator.js");
/* harmony import */ var core_js_modules_es_symbol_iterator_js__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_symbol_iterator_js__WEBPACK_IMPORTED_MODULE_3__);
/* harmony import */ var core_js_modules_es_array_iterator_js__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! core-js/modules/es.array.iterator.js */ "./node_modules/core-js/modules/es.array.iterator.js");
/* harmony import */ var core_js_modules_es_array_iterator_js__WEBPACK_IMPORTED_MODULE_4___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_array_iterator_js__WEBPACK_IMPORTED_MODULE_4__);
/* harmony import */ var core_js_modules_es_string_iterator_js__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! core-js/modules/es.string.iterator.js */ "./node_modules/core-js/modules/es.string.iterator.js");
/* harmony import */ var core_js_modules_es_string_iterator_js__WEBPACK_IMPORTED_MODULE_5___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_string_iterator_js__WEBPACK_IMPORTED_MODULE_5__);
/* harmony import */ var core_js_modules_web_dom_collections_iterator_js__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! core-js/modules/web.dom-collections.iterator.js */ "./node_modules/core-js/modules/web.dom-collections.iterator.js");
/* harmony import */ var core_js_modules_web_dom_collections_iterator_js__WEBPACK_IMPORTED_MODULE_6___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_web_dom_collections_iterator_js__WEBPACK_IMPORTED_MODULE_6__);
/* harmony import */ var core_js_modules_es_array_slice_js__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__(/*! core-js/modules/es.array.slice.js */ "./node_modules/core-js/modules/es.array.slice.js");
/* harmony import */ var core_js_modules_es_array_slice_js__WEBPACK_IMPORTED_MODULE_7___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_array_slice_js__WEBPACK_IMPORTED_MODULE_7__);
/* harmony import */ var core_js_modules_es_regexp_to_string_js__WEBPACK_IMPORTED_MODULE_8__ = __webpack_require__(/*! core-js/modules/es.regexp.to-string.js */ "./node_modules/core-js/modules/es.regexp.to-string.js");
/* harmony import */ var core_js_modules_es_regexp_to_string_js__WEBPACK_IMPORTED_MODULE_8___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_regexp_to_string_js__WEBPACK_IMPORTED_MODULE_8__);
/* harmony import */ var core_js_modules_es_function_name_js__WEBPACK_IMPORTED_MODULE_9__ = __webpack_require__(/*! core-js/modules/es.function.name.js */ "./node_modules/core-js/modules/es.function.name.js");
/* harmony import */ var core_js_modules_es_function_name_js__WEBPACK_IMPORTED_MODULE_9___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_function_name_js__WEBPACK_IMPORTED_MODULE_9__);
/* harmony import */ var core_js_modules_es_array_from_js__WEBPACK_IMPORTED_MODULE_10__ = __webpack_require__(/*! core-js/modules/es.array.from.js */ "./node_modules/core-js/modules/es.array.from.js");
/* harmony import */ var core_js_modules_es_array_from_js__WEBPACK_IMPORTED_MODULE_10___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_array_from_js__WEBPACK_IMPORTED_MODULE_10__);
/* harmony import */ var core_js_modules_es_regexp_exec_js__WEBPACK_IMPORTED_MODULE_11__ = __webpack_require__(/*! core-js/modules/es.regexp.exec.js */ "./node_modules/core-js/modules/es.regexp.exec.js");
/* harmony import */ var core_js_modules_es_regexp_exec_js__WEBPACK_IMPORTED_MODULE_11___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_regexp_exec_js__WEBPACK_IMPORTED_MODULE_11__);
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_12__ = __webpack_require__(/*! react */ "./node_modules/react/index.js");
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_12___default = /*#__PURE__*/__webpack_require__.n(react__WEBPACK_IMPORTED_MODULE_12__);












function _slicedToArray(arr, i) { return _arrayWithHoles(arr) || _iterableToArrayLimit(arr, i) || _unsupportedIterableToArray(arr, i) || _nonIterableRest(); }
function _nonIterableRest() { throw new TypeError("Invalid attempt to destructure non-iterable instance.\nIn order to be iterable, non-array objects must have a [Symbol.iterator]() method."); }
function _unsupportedIterableToArray(o, minLen) { if (!o) return; if (typeof o === "string") return _arrayLikeToArray(o, minLen); var n = Object.prototype.toString.call(o).slice(8, -1); if (n === "Object" && o.constructor) n = o.constructor.name; if (n === "Map" || n === "Set") return Array.from(o); if (n === "Arguments" || /^(?:Ui|I)nt(?:8|16|32)(?:Clamped)?Array$/.test(n)) return _arrayLikeToArray(o, minLen); }
function _arrayLikeToArray(arr, len) { if (len == null || len > arr.length) len = arr.length; for (var i = 0, arr2 = new Array(len); i < len; i++) arr2[i] = arr[i]; return arr2; }
function _iterableToArrayLimit(r, l) { var t = null == r ? null : "undefined" != typeof Symbol && r[Symbol.iterator] || r["@@iterator"]; if (null != t) { var e, n, i, u, a = [], f = !0, o = !1; try { if (i = (t = t.call(r)).next, 0 === l) { if (Object(t) !== t) return; f = !1; } else for (; !(f = (e = i.call(t)).done) && (a.push(e.value), a.length !== l); f = !0); } catch (r) { o = !0, n = r; } finally { try { if (!f && null != t.return && (u = t.return(), Object(u) !== u)) return; } finally { if (o) throw n; } } return a; } }
function _arrayWithHoles(arr) { if (Array.isArray(arr)) return arr; }

function useForceUpdate() {
  var _useState = (0,react__WEBPACK_IMPORTED_MODULE_12__.useState)(0),
    _useState2 = _slicedToArray(_useState, 2),
    setCount = _useState2[1];
  return function () {
    setCount(function (prevCount) {
      return prevCount + 1;
    });
  };
}

/***/ }),

/***/ "./assets/bem/ts/hook/useStateWithCallback.ts":
/*!****************************************************!*\
  !*** ./assets/bem/ts/hook/useStateWithCallback.ts ***!
  \****************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* binding */ useStateWithCallback)
/* harmony export */ });
/* harmony import */ var core_js_modules_es_symbol_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! core-js/modules/es.symbol.js */ "./node_modules/core-js/modules/es.symbol.js");
/* harmony import */ var core_js_modules_es_symbol_js__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_symbol_js__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var core_js_modules_es_symbol_description_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! core-js/modules/es.symbol.description.js */ "./node_modules/core-js/modules/es.symbol.description.js");
/* harmony import */ var core_js_modules_es_symbol_description_js__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_symbol_description_js__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var core_js_modules_es_object_to_string_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! core-js/modules/es.object.to-string.js */ "./node_modules/core-js/modules/es.object.to-string.js");
/* harmony import */ var core_js_modules_es_object_to_string_js__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_object_to_string_js__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var core_js_modules_es_symbol_iterator_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! core-js/modules/es.symbol.iterator.js */ "./node_modules/core-js/modules/es.symbol.iterator.js");
/* harmony import */ var core_js_modules_es_symbol_iterator_js__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_symbol_iterator_js__WEBPACK_IMPORTED_MODULE_3__);
/* harmony import */ var core_js_modules_es_array_iterator_js__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! core-js/modules/es.array.iterator.js */ "./node_modules/core-js/modules/es.array.iterator.js");
/* harmony import */ var core_js_modules_es_array_iterator_js__WEBPACK_IMPORTED_MODULE_4___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_array_iterator_js__WEBPACK_IMPORTED_MODULE_4__);
/* harmony import */ var core_js_modules_es_string_iterator_js__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! core-js/modules/es.string.iterator.js */ "./node_modules/core-js/modules/es.string.iterator.js");
/* harmony import */ var core_js_modules_es_string_iterator_js__WEBPACK_IMPORTED_MODULE_5___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_string_iterator_js__WEBPACK_IMPORTED_MODULE_5__);
/* harmony import */ var core_js_modules_web_dom_collections_iterator_js__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! core-js/modules/web.dom-collections.iterator.js */ "./node_modules/core-js/modules/web.dom-collections.iterator.js");
/* harmony import */ var core_js_modules_web_dom_collections_iterator_js__WEBPACK_IMPORTED_MODULE_6___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_web_dom_collections_iterator_js__WEBPACK_IMPORTED_MODULE_6__);
/* harmony import */ var core_js_modules_es_array_slice_js__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__(/*! core-js/modules/es.array.slice.js */ "./node_modules/core-js/modules/es.array.slice.js");
/* harmony import */ var core_js_modules_es_array_slice_js__WEBPACK_IMPORTED_MODULE_7___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_array_slice_js__WEBPACK_IMPORTED_MODULE_7__);
/* harmony import */ var core_js_modules_es_regexp_to_string_js__WEBPACK_IMPORTED_MODULE_8__ = __webpack_require__(/*! core-js/modules/es.regexp.to-string.js */ "./node_modules/core-js/modules/es.regexp.to-string.js");
/* harmony import */ var core_js_modules_es_regexp_to_string_js__WEBPACK_IMPORTED_MODULE_8___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_regexp_to_string_js__WEBPACK_IMPORTED_MODULE_8__);
/* harmony import */ var core_js_modules_es_function_name_js__WEBPACK_IMPORTED_MODULE_9__ = __webpack_require__(/*! core-js/modules/es.function.name.js */ "./node_modules/core-js/modules/es.function.name.js");
/* harmony import */ var core_js_modules_es_function_name_js__WEBPACK_IMPORTED_MODULE_9___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_function_name_js__WEBPACK_IMPORTED_MODULE_9__);
/* harmony import */ var core_js_modules_es_array_from_js__WEBPACK_IMPORTED_MODULE_10__ = __webpack_require__(/*! core-js/modules/es.array.from.js */ "./node_modules/core-js/modules/es.array.from.js");
/* harmony import */ var core_js_modules_es_array_from_js__WEBPACK_IMPORTED_MODULE_10___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_array_from_js__WEBPACK_IMPORTED_MODULE_10__);
/* harmony import */ var core_js_modules_es_regexp_exec_js__WEBPACK_IMPORTED_MODULE_11__ = __webpack_require__(/*! core-js/modules/es.regexp.exec.js */ "./node_modules/core-js/modules/es.regexp.exec.js");
/* harmony import */ var core_js_modules_es_regexp_exec_js__WEBPACK_IMPORTED_MODULE_11___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_regexp_exec_js__WEBPACK_IMPORTED_MODULE_11__);
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_12__ = __webpack_require__(/*! react */ "./node_modules/react/index.js");
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_12___default = /*#__PURE__*/__webpack_require__.n(react__WEBPACK_IMPORTED_MODULE_12__);












function _slicedToArray(arr, i) { return _arrayWithHoles(arr) || _iterableToArrayLimit(arr, i) || _unsupportedIterableToArray(arr, i) || _nonIterableRest(); }
function _nonIterableRest() { throw new TypeError("Invalid attempt to destructure non-iterable instance.\nIn order to be iterable, non-array objects must have a [Symbol.iterator]() method."); }
function _unsupportedIterableToArray(o, minLen) { if (!o) return; if (typeof o === "string") return _arrayLikeToArray(o, minLen); var n = Object.prototype.toString.call(o).slice(8, -1); if (n === "Object" && o.constructor) n = o.constructor.name; if (n === "Map" || n === "Set") return Array.from(o); if (n === "Arguments" || /^(?:Ui|I)nt(?:8|16|32)(?:Clamped)?Array$/.test(n)) return _arrayLikeToArray(o, minLen); }
function _arrayLikeToArray(arr, len) { if (len == null || len > arr.length) len = arr.length; for (var i = 0, arr2 = new Array(len); i < len; i++) arr2[i] = arr[i]; return arr2; }
function _iterableToArrayLimit(r, l) { var t = null == r ? null : "undefined" != typeof Symbol && r[Symbol.iterator] || r["@@iterator"]; if (null != t) { var e, n, i, u, a = [], f = !0, o = !1; try { if (i = (t = t.call(r)).next, 0 === l) { if (Object(t) !== t) return; f = !1; } else for (; !(f = (e = i.call(t)).done) && (a.push(e.value), a.length !== l); f = !0); } catch (r) { o = !0, n = r; } finally { try { if (!f && null != t.return && (u = t.return(), Object(u) !== u)) return; } finally { if (o) throw n; } } return a; } }
function _arrayWithHoles(arr) { if (Array.isArray(arr)) return arr; }

function useStateWithCallback(initialValue) {
  var callbackRef = (0,react__WEBPACK_IMPORTED_MODULE_12__.useRef)(null);
  var _useState = (0,react__WEBPACK_IMPORTED_MODULE_12__.useState)(initialValue),
    _useState2 = _slicedToArray(_useState, 2),
    value = _useState2[0],
    setValue = _useState2[1];
  (0,react__WEBPACK_IMPORTED_MODULE_12__.useEffect)(function () {
    if (callbackRef.current) {
      callbackRef.current(value);
      callbackRef.current = null;
    }
  }, [value]);
  var setValueWithCallback = function setValueWithCallback(newValue, callback) {
    if (callback) {
      callbackRef.current = callback;
    }
    setValue(newValue);
  };
  return [value, setValueWithCallback];
}

/***/ }),

/***/ "./assets/bem/ts/service/date-time-diff.ts":
/*!*************************************************!*\
  !*** ./assets/bem/ts/service/date-time-diff.ts ***!
  \*************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var core_js_modules_es_object_to_string_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! core-js/modules/es.object.to-string.js */ "./node_modules/core-js/modules/es.object.to-string.js");
/* harmony import */ var core_js_modules_es_object_to_string_js__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_object_to_string_js__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var core_js_modules_es_regexp_to_string_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! core-js/modules/es.regexp.to-string.js */ "./node_modules/core-js/modules/es.regexp.to-string.js");
/* harmony import */ var core_js_modules_es_regexp_to_string_js__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_regexp_to_string_js__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _env__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./env */ "./assets/bem/ts/service/env.ts");
/* harmony import */ var lodash__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! lodash */ "./node_modules/lodash/lodash.js");
/* harmony import */ var lodash__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(lodash__WEBPACK_IMPORTED_MODULE_3__);
/* harmony import */ var date_time_diff__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! date-time-diff */ "./web/assets/common/vendors/date-time-diff/lib/date-time-diff.js");
/* harmony import */ var date_time_diff__WEBPACK_IMPORTED_MODULE_4___default = /*#__PURE__*/__webpack_require__.n(date_time_diff__WEBPACK_IMPORTED_MODULE_4__);
/* harmony import */ var _translator__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! ./translator */ "./assets/bem/ts/service/translator.ts");






function getFormatter() {
  var opts = (0,_env__WEBPACK_IMPORTED_MODULE_2__.extractOptions)();
  try {
    return Intl.NumberFormat(opts.locale);
  } catch (e) {
    if (e instanceof RangeError) {
      return Intl.NumberFormat(opts.defaultLocale);
    } else {
      return null;
    }
  }
}
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (new (date_time_diff__WEBPACK_IMPORTED_MODULE_4___default())(_translator__WEBPACK_IMPORTED_MODULE_5__["default"], function (number) {
  var formatter = getFormatter();
  if ((0,lodash__WEBPACK_IMPORTED_MODULE_3__.isNull)(formatter)) {
    return number.toString();
  }
  return formatter.format(number);
}));

/***/ }),

/***/ "./assets/bem/ts/service/on-ready.ts":
/*!*******************************************!*\
  !*** ./assets/bem/ts/service/on-ready.ts ***!
  \*******************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* binding */ onReady)
/* harmony export */ });
function onReady(callback) {
  if (document.readyState === 'loading') {
    // The DOM is not yet ready.
    document.addEventListener('DOMContentLoaded', callback);
  } else {
    // The DOM is already ready.
    callback();
  }
}

/***/ }),

/***/ "./assets/bem/ts/starter.ts":
/*!**********************************!*\
  !*** ./assets/bem/ts/starter.ts ***!
  \**********************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony import */ var core_js_modules_es_array_iterator_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! core-js/modules/es.array.iterator.js */ "./node_modules/core-js/modules/es.array.iterator.js");
/* harmony import */ var core_js_modules_es_array_iterator_js__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_array_iterator_js__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var core_js_modules_es_object_to_string_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! core-js/modules/es.object.to-string.js */ "./node_modules/core-js/modules/es.object.to-string.js");
/* harmony import */ var core_js_modules_es_object_to_string_js__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_object_to_string_js__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var core_js_modules_es_promise_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! core-js/modules/es.promise.js */ "./node_modules/core-js/modules/es.promise.js");
/* harmony import */ var core_js_modules_es_promise_js__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_promise_js__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var core_js_modules_es_string_iterator_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! core-js/modules/es.string.iterator.js */ "./node_modules/core-js/modules/es.string.iterator.js");
/* harmony import */ var core_js_modules_es_string_iterator_js__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_string_iterator_js__WEBPACK_IMPORTED_MODULE_3__);
/* harmony import */ var core_js_modules_web_dom_collections_iterator_js__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! core-js/modules/web.dom-collections.iterator.js */ "./node_modules/core-js/modules/web.dom-collections.iterator.js");
/* harmony import */ var core_js_modules_web_dom_collections_iterator_js__WEBPACK_IMPORTED_MODULE_4___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_web_dom_collections_iterator_js__WEBPACK_IMPORTED_MODULE_4__);
/* harmony import */ var _service_env__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! ./service/env */ "./assets/bem/ts/service/env.ts");
/* harmony import */ var _service_on_ready__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! ./service/on-ready */ "./assets/bem/ts/service/on-ready.ts");







(0,_service_on_ready__WEBPACK_IMPORTED_MODULE_6__["default"])(function () {
  var opts = (0,_service_env__WEBPACK_IMPORTED_MODULE_5__.extractOptions)();
  if (opts.enabledTransHelper || opts.hasRoleTranslator) {
    console.log('init transhelper');
    Promise.all(/*! import() */[__webpack_require__.e("vendors-node_modules_core-js_modules_es_object_keys_js-node_modules_core-js_modules_es_regexp-599444"), __webpack_require__.e("vendors-node_modules_core-js_modules_es_array_find_js-node_modules_core-js_modules_es_array_f-112b41"), __webpack_require__.e("web_assets_common_vendors_jquery_dist_jquery_js"), __webpack_require__.e("assets_bem_ts_service_transHelper_js-node_modules_core-js_internals_string-trim_js-node_modul-c40bef")]).then(__webpack_require__.bind(__webpack_require__, /*! ./service/transHelper */ "./assets/bem/ts/service/transHelper.js")).then(function (_ref) {
      var init = _ref.default;
      init();
    }, function () {
      console.error('transhelper failed to load');
    });
  }
});

/***/ }),

/***/ "./assets/js-deprecated/component-deprecated/Spinner.tsx":
/*!***************************************************************!*\
  !*** ./assets/js-deprecated/component-deprecated/Spinner.tsx ***!
  \***************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! react */ "./node_modules/react/index.js");
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(react__WEBPACK_IMPORTED_MODULE_0__);

var Spinner = function Spinner() {
  return /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("div", {
    className: "ajax-loader"
  }, /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("div", {
    className: "loading"
  }));
};
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (Spinner);

/***/ }),

/***/ "./assets/js-deprecated/component-deprecated/infiniteList/LazyList.tsx":
/*!*****************************************************************************!*\
  !*** ./assets/js-deprecated/component-deprecated/infiniteList/LazyList.tsx ***!
  \*****************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* binding */ LazyList)
/* harmony export */ });
/* harmony import */ var core_js_modules_es_object_to_string_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! core-js/modules/es.object.to-string.js */ "./node_modules/core-js/modules/es.object.to-string.js");
/* harmony import */ var core_js_modules_es_object_to_string_js__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_object_to_string_js__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var core_js_modules_web_dom_collections_for_each_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! core-js/modules/web.dom-collections.for-each.js */ "./node_modules/core-js/modules/web.dom-collections.for-each.js");
/* harmony import */ var core_js_modules_web_dom_collections_for_each_js__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_web_dom_collections_for_each_js__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var core_js_modules_es_symbol_to_primitive_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! core-js/modules/es.symbol.to-primitive.js */ "./node_modules/core-js/modules/es.symbol.to-primitive.js");
/* harmony import */ var core_js_modules_es_symbol_to_primitive_js__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_symbol_to_primitive_js__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var core_js_modules_es_date_to_primitive_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! core-js/modules/es.date.to-primitive.js */ "./node_modules/core-js/modules/es.date.to-primitive.js");
/* harmony import */ var core_js_modules_es_date_to_primitive_js__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_date_to_primitive_js__WEBPACK_IMPORTED_MODULE_3__);
/* harmony import */ var core_js_modules_es_symbol_js__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! core-js/modules/es.symbol.js */ "./node_modules/core-js/modules/es.symbol.js");
/* harmony import */ var core_js_modules_es_symbol_js__WEBPACK_IMPORTED_MODULE_4___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_symbol_js__WEBPACK_IMPORTED_MODULE_4__);
/* harmony import */ var core_js_modules_es_symbol_description_js__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! core-js/modules/es.symbol.description.js */ "./node_modules/core-js/modules/es.symbol.description.js");
/* harmony import */ var core_js_modules_es_symbol_description_js__WEBPACK_IMPORTED_MODULE_5___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_symbol_description_js__WEBPACK_IMPORTED_MODULE_5__);
/* harmony import */ var core_js_modules_es_number_constructor_js__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! core-js/modules/es.number.constructor.js */ "./node_modules/core-js/modules/es.number.constructor.js");
/* harmony import */ var core_js_modules_es_number_constructor_js__WEBPACK_IMPORTED_MODULE_6___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_number_constructor_js__WEBPACK_IMPORTED_MODULE_6__);
/* harmony import */ var core_js_modules_es_object_keys_js__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__(/*! core-js/modules/es.object.keys.js */ "./node_modules/core-js/modules/es.object.keys.js");
/* harmony import */ var core_js_modules_es_object_keys_js__WEBPACK_IMPORTED_MODULE_7___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_object_keys_js__WEBPACK_IMPORTED_MODULE_7__);
/* harmony import */ var core_js_modules_es_array_filter_js__WEBPACK_IMPORTED_MODULE_8__ = __webpack_require__(/*! core-js/modules/es.array.filter.js */ "./node_modules/core-js/modules/es.array.filter.js");
/* harmony import */ var core_js_modules_es_array_filter_js__WEBPACK_IMPORTED_MODULE_8___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_array_filter_js__WEBPACK_IMPORTED_MODULE_8__);
/* harmony import */ var core_js_modules_es_object_get_own_property_descriptor_js__WEBPACK_IMPORTED_MODULE_9__ = __webpack_require__(/*! core-js/modules/es.object.get-own-property-descriptor.js */ "./node_modules/core-js/modules/es.object.get-own-property-descriptor.js");
/* harmony import */ var core_js_modules_es_object_get_own_property_descriptor_js__WEBPACK_IMPORTED_MODULE_9___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_object_get_own_property_descriptor_js__WEBPACK_IMPORTED_MODULE_9__);
/* harmony import */ var core_js_modules_es_object_get_own_property_descriptors_js__WEBPACK_IMPORTED_MODULE_10__ = __webpack_require__(/*! core-js/modules/es.object.get-own-property-descriptors.js */ "./node_modules/core-js/modules/es.object.get-own-property-descriptors.js");
/* harmony import */ var core_js_modules_es_object_get_own_property_descriptors_js__WEBPACK_IMPORTED_MODULE_10___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_object_get_own_property_descriptors_js__WEBPACK_IMPORTED_MODULE_10__);
/* harmony import */ var core_js_modules_es_symbol_iterator_js__WEBPACK_IMPORTED_MODULE_11__ = __webpack_require__(/*! core-js/modules/es.symbol.iterator.js */ "./node_modules/core-js/modules/es.symbol.iterator.js");
/* harmony import */ var core_js_modules_es_symbol_iterator_js__WEBPACK_IMPORTED_MODULE_11___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_symbol_iterator_js__WEBPACK_IMPORTED_MODULE_11__);
/* harmony import */ var core_js_modules_es_array_iterator_js__WEBPACK_IMPORTED_MODULE_12__ = __webpack_require__(/*! core-js/modules/es.array.iterator.js */ "./node_modules/core-js/modules/es.array.iterator.js");
/* harmony import */ var core_js_modules_es_array_iterator_js__WEBPACK_IMPORTED_MODULE_12___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_array_iterator_js__WEBPACK_IMPORTED_MODULE_12__);
/* harmony import */ var core_js_modules_es_string_iterator_js__WEBPACK_IMPORTED_MODULE_13__ = __webpack_require__(/*! core-js/modules/es.string.iterator.js */ "./node_modules/core-js/modules/es.string.iterator.js");
/* harmony import */ var core_js_modules_es_string_iterator_js__WEBPACK_IMPORTED_MODULE_13___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_string_iterator_js__WEBPACK_IMPORTED_MODULE_13__);
/* harmony import */ var core_js_modules_web_dom_collections_iterator_js__WEBPACK_IMPORTED_MODULE_14__ = __webpack_require__(/*! core-js/modules/web.dom-collections.iterator.js */ "./node_modules/core-js/modules/web.dom-collections.iterator.js");
/* harmony import */ var core_js_modules_web_dom_collections_iterator_js__WEBPACK_IMPORTED_MODULE_14___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_web_dom_collections_iterator_js__WEBPACK_IMPORTED_MODULE_14__);
/* harmony import */ var _List__WEBPACK_IMPORTED_MODULE_15__ = __webpack_require__(/*! ./List */ "./assets/js-deprecated/component-deprecated/infiniteList/List.tsx");
/* harmony import */ var _Loader__WEBPACK_IMPORTED_MODULE_16__ = __webpack_require__(/*! ./Loader */ "./assets/js-deprecated/component-deprecated/infiniteList/Loader.tsx");
/* harmony import */ var react_measure__WEBPACK_IMPORTED_MODULE_17__ = __webpack_require__(/*! react-measure */ "./node_modules/react-measure/dist/index.esm.js");
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_18__ = __webpack_require__(/*! react */ "./node_modules/react/index.js");
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_18___default = /*#__PURE__*/__webpack_require__.n(react__WEBPACK_IMPORTED_MODULE_18__);
function _typeof(o) { "@babel/helpers - typeof"; return _typeof = "function" == typeof Symbol && "symbol" == typeof Symbol.iterator ? function (o) { return typeof o; } : function (o) { return o && "function" == typeof Symbol && o.constructor === Symbol && o !== Symbol.prototype ? "symbol" : typeof o; }, _typeof(o); }
function ownKeys(e, r) { var t = Object.keys(e); if (Object.getOwnPropertySymbols) { var o = Object.getOwnPropertySymbols(e); r && (o = o.filter(function (r) { return Object.getOwnPropertyDescriptor(e, r).enumerable; })), t.push.apply(t, o); } return t; }
function _objectSpread(e) { for (var r = 1; r < arguments.length; r++) { var t = null != arguments[r] ? arguments[r] : {}; r % 2 ? ownKeys(Object(t), !0).forEach(function (r) { _defineProperty(e, r, t[r]); }) : Object.getOwnPropertyDescriptors ? Object.defineProperties(e, Object.getOwnPropertyDescriptors(t)) : ownKeys(Object(t)).forEach(function (r) { Object.defineProperty(e, r, Object.getOwnPropertyDescriptor(t, r)); }); } return e; }
function _defineProperty(obj, key, value) { key = _toPropertyKey(key); if (key in obj) { Object.defineProperty(obj, key, { value: value, enumerable: true, configurable: true, writable: true }); } else { obj[key] = value; } return obj; }
function _toPropertyKey(arg) { var key = _toPrimitive(arg, "string"); return _typeof(key) === "symbol" ? key : String(key); }
function _toPrimitive(input, hint) { if (_typeof(input) !== "object" || input === null) return input; var prim = input[Symbol.toPrimitive]; if (prim !== undefined) { var res = prim.call(input, hint || "default"); if (_typeof(res) !== "object") return res; throw new TypeError("@@toPrimitive must return a primitive value."); } return (hint === "string" ? String : Number)(input); }



















var mergeRefs = function mergeRefs() {
  for (var _len = arguments.length, refs = new Array(_len), _key = 0; _key < _len; _key++) {
    refs[_key] = arguments[_key];
  }
  return function (incomingRef) {
    refs.forEach(function (ref) {
      if (typeof ref === 'function') {
        ref(incomingRef);
      } else if (ref) {
        ref.current = incomingRef;
      }
    });
  };
};
function LazyList(props) {
  var children = props.children,
    itemCount = props.itemCount,
    loadMore = props.loadMore,
    listProps = props.listProps,
    height = props.height;
  var itemSizes = (0,react__WEBPACK_IMPORTED_MODULE_18__.useRef)({});
  var listRef = (0,react__WEBPACK_IMPORTED_MODULE_18__.useRef)();
  var getItemSize = function getItemSize(index) {
    return itemSizes.current[index] || 50;
  };
  var handleItemResize = function handleItemResize(index, _ref) {
    var _bounds$height, _margin$top, _margin$bottom;
    var bounds = _ref.bounds,
      margin = _ref.margin;
    itemSizes.current[index] = ((_bounds$height = bounds === null || bounds === void 0 ? void 0 : bounds.height) !== null && _bounds$height !== void 0 ? _bounds$height : 0) + ((_margin$top = margin === null || margin === void 0 ? void 0 : margin.top) !== null && _margin$top !== void 0 ? _margin$top : 0) + ((_margin$bottom = margin === null || margin === void 0 ? void 0 : margin.bottom) !== null && _margin$bottom !== void 0 ? _margin$bottom : 0);
    if (listRef.current) {
      listRef.current.resetAfterIndex(index, false);
    }
  };
  var Row = function Row(_ref2) {
    var index = _ref2.index,
      style = _ref2.style;
    return /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_18___default().createElement("div", {
      style: style
    }, /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_18___default().createElement(react_measure__WEBPACK_IMPORTED_MODULE_17__["default"], {
      bounds: true,
      margin: true,
      onResize: function onResize(resizeData) {
        handleItemResize(index, resizeData);
      }
    }, function (_ref3) {
      var measureRef = _ref3.measureRef;
      return children(index, measureRef);
    }));
  };
  return /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_18___default().createElement(_Loader__WEBPACK_IMPORTED_MODULE_16__["default"], {
    isItemLoaded: function isItemLoaded(index) {
      return index < itemCount;
    },
    itemCount: itemCount + 1,
    loadMoreItems: loadMore
  }, function (_ref4) {
    var onItemsRendered = _ref4.onItemsRendered,
      ref = _ref4.ref;
    var refs = [ref, listRef];
    return /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_18___default().createElement(_List__WEBPACK_IMPORTED_MODULE_15__["default"], _objectSpread(_objectSpread({}, listProps), {}, {
      listRef: mergeRefs.apply(void 0, refs),
      onItemsRendered: onItemsRendered,
      itemCount: itemCount,
      itemSize: getItemSize,
      height: height,
      width: 'auto'
    }), Row);
  });
}

/***/ }),

/***/ "./assets/js-deprecated/component-deprecated/infiniteList/List.tsx":
/*!*************************************************************************!*\
  !*** ./assets/js-deprecated/component-deprecated/infiniteList/List.tsx ***!
  \*************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* binding */ List)
/* harmony export */ });
/* harmony import */ var core_js_modules_es_symbol_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! core-js/modules/es.symbol.js */ "./node_modules/core-js/modules/es.symbol.js");
/* harmony import */ var core_js_modules_es_symbol_js__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_symbol_js__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var core_js_modules_es_symbol_description_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! core-js/modules/es.symbol.description.js */ "./node_modules/core-js/modules/es.symbol.description.js");
/* harmony import */ var core_js_modules_es_symbol_description_js__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_symbol_description_js__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var core_js_modules_es_object_to_string_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! core-js/modules/es.object.to-string.js */ "./node_modules/core-js/modules/es.object.to-string.js");
/* harmony import */ var core_js_modules_es_object_to_string_js__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_object_to_string_js__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var core_js_modules_es_symbol_iterator_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! core-js/modules/es.symbol.iterator.js */ "./node_modules/core-js/modules/es.symbol.iterator.js");
/* harmony import */ var core_js_modules_es_symbol_iterator_js__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_symbol_iterator_js__WEBPACK_IMPORTED_MODULE_3__);
/* harmony import */ var core_js_modules_es_array_iterator_js__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! core-js/modules/es.array.iterator.js */ "./node_modules/core-js/modules/es.array.iterator.js");
/* harmony import */ var core_js_modules_es_array_iterator_js__WEBPACK_IMPORTED_MODULE_4___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_array_iterator_js__WEBPACK_IMPORTED_MODULE_4__);
/* harmony import */ var core_js_modules_es_string_iterator_js__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! core-js/modules/es.string.iterator.js */ "./node_modules/core-js/modules/es.string.iterator.js");
/* harmony import */ var core_js_modules_es_string_iterator_js__WEBPACK_IMPORTED_MODULE_5___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_string_iterator_js__WEBPACK_IMPORTED_MODULE_5__);
/* harmony import */ var core_js_modules_web_dom_collections_iterator_js__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! core-js/modules/web.dom-collections.iterator.js */ "./node_modules/core-js/modules/web.dom-collections.iterator.js");
/* harmony import */ var core_js_modules_web_dom_collections_iterator_js__WEBPACK_IMPORTED_MODULE_6___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_web_dom_collections_iterator_js__WEBPACK_IMPORTED_MODULE_6__);
/* harmony import */ var core_js_modules_es_array_slice_js__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__(/*! core-js/modules/es.array.slice.js */ "./node_modules/core-js/modules/es.array.slice.js");
/* harmony import */ var core_js_modules_es_array_slice_js__WEBPACK_IMPORTED_MODULE_7___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_array_slice_js__WEBPACK_IMPORTED_MODULE_7__);
/* harmony import */ var core_js_modules_es_regexp_to_string_js__WEBPACK_IMPORTED_MODULE_8__ = __webpack_require__(/*! core-js/modules/es.regexp.to-string.js */ "./node_modules/core-js/modules/es.regexp.to-string.js");
/* harmony import */ var core_js_modules_es_regexp_to_string_js__WEBPACK_IMPORTED_MODULE_8___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_regexp_to_string_js__WEBPACK_IMPORTED_MODULE_8__);
/* harmony import */ var core_js_modules_es_function_name_js__WEBPACK_IMPORTED_MODULE_9__ = __webpack_require__(/*! core-js/modules/es.function.name.js */ "./node_modules/core-js/modules/es.function.name.js");
/* harmony import */ var core_js_modules_es_function_name_js__WEBPACK_IMPORTED_MODULE_9___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_function_name_js__WEBPACK_IMPORTED_MODULE_9__);
/* harmony import */ var core_js_modules_es_array_from_js__WEBPACK_IMPORTED_MODULE_10__ = __webpack_require__(/*! core-js/modules/es.array.from.js */ "./node_modules/core-js/modules/es.array.from.js");
/* harmony import */ var core_js_modules_es_array_from_js__WEBPACK_IMPORTED_MODULE_10___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_array_from_js__WEBPACK_IMPORTED_MODULE_10__);
/* harmony import */ var core_js_modules_es_regexp_exec_js__WEBPACK_IMPORTED_MODULE_11__ = __webpack_require__(/*! core-js/modules/es.regexp.exec.js */ "./node_modules/core-js/modules/es.regexp.exec.js");
/* harmony import */ var core_js_modules_es_regexp_exec_js__WEBPACK_IMPORTED_MODULE_11___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_regexp_exec_js__WEBPACK_IMPORTED_MODULE_11__);
/* harmony import */ var core_js_modules_es_symbol_to_primitive_js__WEBPACK_IMPORTED_MODULE_12__ = __webpack_require__(/*! core-js/modules/es.symbol.to-primitive.js */ "./node_modules/core-js/modules/es.symbol.to-primitive.js");
/* harmony import */ var core_js_modules_es_symbol_to_primitive_js__WEBPACK_IMPORTED_MODULE_12___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_symbol_to_primitive_js__WEBPACK_IMPORTED_MODULE_12__);
/* harmony import */ var core_js_modules_es_date_to_primitive_js__WEBPACK_IMPORTED_MODULE_13__ = __webpack_require__(/*! core-js/modules/es.date.to-primitive.js */ "./node_modules/core-js/modules/es.date.to-primitive.js");
/* harmony import */ var core_js_modules_es_date_to_primitive_js__WEBPACK_IMPORTED_MODULE_13___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_date_to_primitive_js__WEBPACK_IMPORTED_MODULE_13__);
/* harmony import */ var core_js_modules_es_number_constructor_js__WEBPACK_IMPORTED_MODULE_14__ = __webpack_require__(/*! core-js/modules/es.number.constructor.js */ "./node_modules/core-js/modules/es.number.constructor.js");
/* harmony import */ var core_js_modules_es_number_constructor_js__WEBPACK_IMPORTED_MODULE_14___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_number_constructor_js__WEBPACK_IMPORTED_MODULE_14__);
/* harmony import */ var core_js_modules_es_object_keys_js__WEBPACK_IMPORTED_MODULE_15__ = __webpack_require__(/*! core-js/modules/es.object.keys.js */ "./node_modules/core-js/modules/es.object.keys.js");
/* harmony import */ var core_js_modules_es_object_keys_js__WEBPACK_IMPORTED_MODULE_15___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_object_keys_js__WEBPACK_IMPORTED_MODULE_15__);
/* harmony import */ var core_js_modules_es_array_filter_js__WEBPACK_IMPORTED_MODULE_16__ = __webpack_require__(/*! core-js/modules/es.array.filter.js */ "./node_modules/core-js/modules/es.array.filter.js");
/* harmony import */ var core_js_modules_es_array_filter_js__WEBPACK_IMPORTED_MODULE_16___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_array_filter_js__WEBPACK_IMPORTED_MODULE_16__);
/* harmony import */ var core_js_modules_es_object_get_own_property_descriptor_js__WEBPACK_IMPORTED_MODULE_17__ = __webpack_require__(/*! core-js/modules/es.object.get-own-property-descriptor.js */ "./node_modules/core-js/modules/es.object.get-own-property-descriptor.js");
/* harmony import */ var core_js_modules_es_object_get_own_property_descriptor_js__WEBPACK_IMPORTED_MODULE_17___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_object_get_own_property_descriptor_js__WEBPACK_IMPORTED_MODULE_17__);
/* harmony import */ var core_js_modules_web_dom_collections_for_each_js__WEBPACK_IMPORTED_MODULE_18__ = __webpack_require__(/*! core-js/modules/web.dom-collections.for-each.js */ "./node_modules/core-js/modules/web.dom-collections.for-each.js");
/* harmony import */ var core_js_modules_web_dom_collections_for_each_js__WEBPACK_IMPORTED_MODULE_18___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_web_dom_collections_for_each_js__WEBPACK_IMPORTED_MODULE_18__);
/* harmony import */ var core_js_modules_es_object_get_own_property_descriptors_js__WEBPACK_IMPORTED_MODULE_19__ = __webpack_require__(/*! core-js/modules/es.object.get-own-property-descriptors.js */ "./node_modules/core-js/modules/es.object.get-own-property-descriptors.js");
/* harmony import */ var core_js_modules_es_object_get_own_property_descriptors_js__WEBPACK_IMPORTED_MODULE_19___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_object_get_own_property_descriptors_js__WEBPACK_IMPORTED_MODULE_19__);
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_20__ = __webpack_require__(/*! react */ "./node_modules/react/index.js");
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_20___default = /*#__PURE__*/__webpack_require__.n(react__WEBPACK_IMPORTED_MODULE_20__);
/* harmony import */ var memoize_one__WEBPACK_IMPORTED_MODULE_23__ = __webpack_require__(/*! memoize-one */ "./node_modules/memoize-one/dist/memoize-one.esm.js");
/* harmony import */ var _bem_ts_hook_useForceUpdate__WEBPACK_IMPORTED_MODULE_21__ = __webpack_require__(/*! ../../../bem/ts/hook/useForceUpdate */ "./assets/bem/ts/hook/useForceUpdate.ts");
/* harmony import */ var _bem_ts_hook_useStateWithCallback__WEBPACK_IMPORTED_MODULE_22__ = __webpack_require__(/*! ../../../bem/ts/hook/useStateWithCallback */ "./assets/bem/ts/hook/useStateWithCallback.ts");




















function ownKeys(e, r) { var t = Object.keys(e); if (Object.getOwnPropertySymbols) { var o = Object.getOwnPropertySymbols(e); r && (o = o.filter(function (r) { return Object.getOwnPropertyDescriptor(e, r).enumerable; })), t.push.apply(t, o); } return t; }
function _objectSpread(e) { for (var r = 1; r < arguments.length; r++) { var t = null != arguments[r] ? arguments[r] : {}; r % 2 ? ownKeys(Object(t), !0).forEach(function (r) { _defineProperty(e, r, t[r]); }) : Object.getOwnPropertyDescriptors ? Object.defineProperties(e, Object.getOwnPropertyDescriptors(t)) : ownKeys(Object(t)).forEach(function (r) { Object.defineProperty(e, r, Object.getOwnPropertyDescriptor(t, r)); }); } return e; }
function _defineProperty(obj, key, value) { key = _toPropertyKey(key); if (key in obj) { Object.defineProperty(obj, key, { value: value, enumerable: true, configurable: true, writable: true }); } else { obj[key] = value; } return obj; }
function _toPropertyKey(arg) { var key = _toPrimitive(arg, "string"); return _typeof(key) === "symbol" ? key : String(key); }
function _toPrimitive(input, hint) { if (_typeof(input) !== "object" || input === null) return input; var prim = input[Symbol.toPrimitive]; if (prim !== undefined) { var res = prim.call(input, hint || "default"); if (_typeof(res) !== "object") return res; throw new TypeError("@@toPrimitive must return a primitive value."); } return (hint === "string" ? String : Number)(input); }
function _slicedToArray(arr, i) { return _arrayWithHoles(arr) || _iterableToArrayLimit(arr, i) || _unsupportedIterableToArray(arr, i) || _nonIterableRest(); }
function _nonIterableRest() { throw new TypeError("Invalid attempt to destructure non-iterable instance.\nIn order to be iterable, non-array objects must have a [Symbol.iterator]() method."); }
function _unsupportedIterableToArray(o, minLen) { if (!o) return; if (typeof o === "string") return _arrayLikeToArray(o, minLen); var n = Object.prototype.toString.call(o).slice(8, -1); if (n === "Object" && o.constructor) n = o.constructor.name; if (n === "Map" || n === "Set") return Array.from(o); if (n === "Arguments" || /^(?:Ui|I)nt(?:8|16|32)(?:Clamped)?Array$/.test(n)) return _arrayLikeToArray(o, minLen); }
function _arrayLikeToArray(arr, len) { if (len == null || len > arr.length) len = arr.length; for (var i = 0, arr2 = new Array(len); i < len; i++) arr2[i] = arr[i]; return arr2; }
function _iterableToArrayLimit(r, l) { var t = null == r ? null : "undefined" != typeof Symbol && r[Symbol.iterator] || r["@@iterator"]; if (null != t) { var e, n, i, u, a = [], f = !0, o = !1; try { if (i = (t = t.call(r)).next, 0 === l) { if (Object(t) !== t) return; f = !1; } else for (; !(f = (e = i.call(t)).done) && (a.push(e.value), a.length !== l); f = !0); } catch (r) { o = !0, n = r; } finally { try { if (!f && null != t.return && (u = t.return(), Object(u) !== u)) return; } finally { if (o) throw n; } } return a; } }
function _arrayWithHoles(arr) { if (Array.isArray(arr)) return arr; }
function _typeof(o) { "@babel/helpers - typeof"; return _typeof = "function" == typeof Symbol && "symbol" == typeof Symbol.iterator ? function (o) { return typeof o; } : function (o) { return o && "function" == typeof Symbol && o.constructor === Symbol && o !== Symbol.prototype ? "symbol" : typeof o; }, _typeof(o); }




var IS_SCROLLING_DEBOUNCE_INTERVAL = 150;
var hasNativePerformanceNow = (typeof performance === "undefined" ? "undefined" : _typeof(performance)) === 'object' && typeof performance.now === 'function';
var now = hasNativePerformanceNow ? function () {
  return performance.now();
} : function () {
  return Date.now();
};
function cancelTimeout(timeoutID) {
  cancelAnimationFrame(timeoutID.id);
}
function requestTimeout(callback, delay) {
  var start = now();
  function tick() {
    if (now() - start >= delay) {
      callback.call(null);
    } else {
      timeoutID.id = requestAnimationFrame(tick);
    }
  }
  var timeoutID = {
    id: requestAnimationFrame(tick)
  };
  return timeoutID;
}
function getItemMetadata(props, index, context) {
  var itemSize = props.itemSize;
  var itemMetadataMap = context.itemMetadataMap,
    lastMeasuredIndex = context.lastMeasuredIndex;
  if (index > lastMeasuredIndex) {
    var offset = 0;
    if (lastMeasuredIndex >= 0) {
      var itemMetadata = itemMetadataMap[lastMeasuredIndex];
      if (itemMetadata) {
        offset = itemMetadata.offset + itemMetadata.size;
      }
    }
    for (var i = lastMeasuredIndex + 1; i <= index; i++) {
      var size = itemSize(i);
      itemMetadataMap[i] = {
        offset: offset,
        size: size
      };
      offset += size;
    }
    context.lastMeasuredIndex = index;
  }
  var result = itemMetadataMap[index];
  if (!result) {
    throw new Error("Item metadata for index ".concat(index, " is missing"));
  }
  return result;
}
function findNearestItem(props, context, offset) {
  var _itemMetadataMap$last, _itemMetadataMap$last2;
  var itemMetadataMap = context.itemMetadataMap,
    lastMeasuredIndex = context.lastMeasuredIndex;
  var lastMeasuredItemOffset = lastMeasuredIndex > 0 ? (_itemMetadataMap$last = (_itemMetadataMap$last2 = itemMetadataMap[lastMeasuredIndex]) === null || _itemMetadataMap$last2 === void 0 ? void 0 : _itemMetadataMap$last2.offset) !== null && _itemMetadataMap$last !== void 0 ? _itemMetadataMap$last : 0 : 0;
  if (lastMeasuredItemOffset >= offset) {
    // If we've already measured items within this range just use a binary search as it's faster.
    return findNearestItemBinarySearch(props, context, lastMeasuredIndex, 0, offset);
  } else {
    // If we haven't yet measured this high, fallback to an exponential search with an inner binary search.
    // The exponential search avoids pre-computing sizes for the full set of items as a binary search would.
    // The overall complexity for this approach is O(log n).
    return findNearestItemExponentialSearch(props, context, Math.max(0, lastMeasuredIndex), offset);
  }
}
function findNearestItemBinarySearch(props, context, high, low, offset) {
  while (low <= high) {
    var middle = low + Math.floor((high - low) / 2);
    var currentOffset = getItemMetadata(props, middle, context).offset;
    if (currentOffset === offset) {
      return middle;
    } else if (currentOffset < offset) {
      low = middle + 1;
    } else if (currentOffset > offset) {
      high = middle - 1;
    }
  }
  if (low > 0) {
    return low - 1;
  } else {
    return 0;
  }
}
function findNearestItemExponentialSearch(props, context, index, offset) {
  var itemCount = props.itemCount;
  var interval = 1;
  while (index < itemCount && getItemMetadata(props, index, context).offset < offset) {
    index += interval;
    interval *= 2;
  }
  return findNearestItemBinarySearch(props, context, Math.min(index, itemCount - 1), Math.floor(index / 2), offset);
}
function getEstimatedTotalSize(_ref, _ref2) {
  var itemCount = _ref.itemCount;
  var itemMetadataMap = _ref2.itemMetadataMap,
    estimatedItemSize = _ref2.estimatedItemSize,
    lastMeasuredIndex = _ref2.lastMeasuredIndex;
  var totalSizeOfMeasuredItems = 0;
  // Edge case check for when the number of items decreases while a scroll is in progress.
  // https://github.com/bvaughn/react-window/pull/138
  if (lastMeasuredIndex >= itemCount) {
    lastMeasuredIndex = itemCount - 1;
  }
  if (lastMeasuredIndex >= 0) {
    var itemMetadata = itemMetadataMap[lastMeasuredIndex];
    if (itemMetadata) {
      totalSizeOfMeasuredItems = itemMetadata.offset + itemMetadata.size;
    }
  }
  var numUnmeasuredItems = itemCount - lastMeasuredIndex - 1;
  var totalSizeOfUnmeasuredItems = numUnmeasuredItems * estimatedItemSize;
  return totalSizeOfMeasuredItems + totalSizeOfUnmeasuredItems;
}
function List(props) {
  var _props$initialScrollO = props.initialScrollOffset,
    initialScrollOffset = _props$initialScrollO === void 0 ? 0 : _props$initialScrollO,
    _props$itemData = props.itemData,
    itemData = _props$itemData === void 0 ? undefined : _props$itemData,
    _props$overscanCount = props.overscanCount,
    overscanCount = _props$overscanCount === void 0 ? 2 : _props$overscanCount,
    _props$estimatedItemS = props.estimatedItemSize,
    estimatedItemSize = _props$estimatedItemS === void 0 ? 50 : _props$estimatedItemS,
    listRef = props.listRef,
    itemCount = props.itemCount,
    _props$itemKey = props.itemKey,
    itemKey = _props$itemKey === void 0 ? function (index) {
      return index;
    } : _props$itemKey,
    children = props.children,
    outerElementType = props.outerElementType,
    innerElementType = props.innerElementType,
    innerClassName = props.innerClassName,
    className = props.className,
    innerRef = props.innerRef,
    height = props.height,
    width = props.width,
    style = props.style,
    innerStyle = props.innerStyle;
  var _useStateWithCallback = (0,_bem_ts_hook_useStateWithCallback__WEBPACK_IMPORTED_MODULE_22__["default"])({
      isScrolling: false,
      scrollUpdateWasRequested: false,
      scrollDirection: 'forward',
      scrollOffset: initialScrollOffset
    }),
    _useStateWithCallback2 = _slicedToArray(_useStateWithCallback, 2),
    _useStateWithCallback3 = _useStateWithCallback2[0],
    isScrolling = _useStateWithCallback3.isScrolling,
    scrollUpdateWasRequested = _useStateWithCallback3.scrollUpdateWasRequested,
    scrollDirection = _useStateWithCallback3.scrollDirection,
    scrollOffset = _useStateWithCallback3.scrollOffset,
    setState = _useStateWithCallback2[1];
  var context = (0,react__WEBPACK_IMPORTED_MODULE_20__.useRef)({
    itemMetadataMap: {},
    lastMeasuredIndex: -1,
    estimatedItemSize: estimatedItemSize
  });
  var resetIsScrollingTimeoutId = (0,react__WEBPACK_IMPORTED_MODULE_20__.useRef)(null);
  var outerListRef = (0,react__WEBPACK_IMPORTED_MODULE_20__.useRef)(null);
  var getItemStyleCache = (0,memoize_one__WEBPACK_IMPORTED_MODULE_23__["default"])(function () {
    return {};
  });
  var forceUpdate = (0,_bem_ts_hook_useForceUpdate__WEBPACK_IMPORTED_MODULE_21__["default"])();
  // const scrollTo = (scrollOffset: number) => {
  //     scrollOffset = Math.max(0, scrollOffset);
  //     setState((prevState) => {
  //         if (prevState.scrollOffset === scrollOffset) {
  //             return {...prevState};
  //         }
  //         return {
  //             ...prevState,
  //             scrollDirection: prevState.scrollOffset < scrollOffset ? 'forward' : 'backward',
  //             scrollOffset: scrollOffset,
  //             scrollUpdateWasRequested: true,
  //         };
  //     }, resetIsScrollingDebounced);
  // };
  // const scrollToItem = (index: number, align: ScrollToAlign = 'auto') => {
  //     const { itemCount } = props;
  //     index = Math.max(0, Math.min(index, itemCount - 1));
  //     scrollTo(getOffsetForIndexAndAlignment(props, index, align, scrollOffset, context.current));
  // };
  var outerRefSetter = function outerRefSetter(ref) {
    var outerRef = props.outerRef;
    outerListRef.current = ref;
    if (typeof outerRef === 'function') {
      // @ts-expect-error 123
      outerRef(ref);
    } else if (outerRef != null && _typeof(outerRef) === 'object' && Object.prototype.hasOwnProperty.call(outerRef, 'current')) {
      // @ts-expect-error 123
      outerRef.current = ref;
    }
  };
  // const getOffsetForIndexAndAlignment = (
  //     props: Props<T>,
  //     index: number,
  //     align: ScrollToAlign,
  //     scrollOffset: number,
  //     context: Context,
  // ) => {
  //     const { height } = props;
  //     const size = height;
  //     const itemMetadata = getItemMetadata(props, index, context);
  //     // Get estimated total size after ItemMetadata is computed,
  //     // To ensure it reflects actual measurements instead of just estimates.
  //     const estimatedTotalSize = getEstimatedTotalSize(props, context);
  //     const maxOffset = Math.max(0, Math.min(estimatedTotalSize - size, itemMetadata.offset));
  //     const minOffset = Math.max(0, itemMetadata.offset - size + itemMetadata.size);
  //     if (align === 'smart') {
  //         if (scrollOffset >= minOffset - size && scrollOffset <= maxOffset + size) {
  //             align = 'auto';
  //         } else {
  //             align = 'center';
  //         }
  //     }
  //     switch (align) {
  //         case 'start':
  //             return maxOffset;
  //         case 'end':
  //             return minOffset;
  //         case 'center':
  //             return Math.round(minOffset + (maxOffset - minOffset) / 2);
  //         case 'auto':
  //         default:
  //             if (scrollOffset >= minOffset && scrollOffset <= maxOffset) {
  //                 return scrollOffset;
  //             } else if (scrollOffset < minOffset) {
  //                 return minOffset;
  //             } else {
  //                 return maxOffset;
  //             }
  //     }
  // };
  var getStopIndexForStartIndex = function getStopIndexForStartIndex(props, startIndex, scrollOffset, context) {
    var height = props.height,
      itemCount = props.itemCount;
    var size = height;
    var itemMetadata = getItemMetadata(props, startIndex, context);
    var maxOffset = scrollOffset + size;
    var offset = itemMetadata.offset + itemMetadata.size;
    var stopIndex = startIndex;
    while (stopIndex < itemCount - 1 && offset < maxOffset) {
      stopIndex++;
      offset += getItemMetadata(props, stopIndex, context).size;
    }
    return stopIndex;
  };
  var getRangeToRender = function getRangeToRender() {
    var itemCount = props.itemCount;
    if (itemCount === 0) {
      return [0, 0, 0, 0];
    }
    var startIndex = findNearestItem(props, context.current, scrollOffset);
    var stopIndex = getStopIndexForStartIndex(props, startIndex, scrollOffset, context.current);
    // Overscan by one item in each direction so that tab/focus works.
    // If there isn't at least one extra item, tab loops back around.
    var overscanBackward = !isScrolling || scrollDirection === 'backward' ? Math.max(1, overscanCount) : 1;
    var overscanForward = !isScrolling || scrollDirection === 'forward' ? Math.max(1, overscanCount) : 1;
    return [Math.max(0, startIndex - overscanBackward), Math.max(0, Math.min(itemCount - 1, stopIndex + overscanForward)), startIndex, stopIndex];
  };
  var onItemsRendered = props.onItemsRendered,
    onScrollCallable = props.onScroll;
  var callOnItemsRendered = (0,memoize_one__WEBPACK_IMPORTED_MODULE_23__["default"])(function (overscanStartIndex, overscanStopIndex, visibleStartIndex, visibleStopIndex) {
    if (onItemsRendered) {
      onItemsRendered(overscanStartIndex, overscanStopIndex, visibleStartIndex, visibleStopIndex);
      return;
    }
  });
  var callOnScroll = (0,memoize_one__WEBPACK_IMPORTED_MODULE_23__["default"])(function (scrollDirection, scrollOffset, scrollUpdateWasRequested) {
    if (onScrollCallable) {
      onScrollCallable(scrollDirection, scrollOffset, scrollUpdateWasRequested);
      return;
    }
  });
  var callPropsCallbacks = function callPropsCallbacks() {
    var onItemsRendered = props.onItemsRendered,
      onScroll = props.onScroll,
      itemCount = props.itemCount;
    if (typeof onItemsRendered === 'function') {
      if (itemCount > 0) {
        var _getRangeToRender = getRangeToRender(),
          _getRangeToRender2 = _slicedToArray(_getRangeToRender, 4),
          overscanStartIndex = _getRangeToRender2[0],
          overscanStopIndex = _getRangeToRender2[1],
          visibleStartIndex = _getRangeToRender2[2],
          visibleStopIndex = _getRangeToRender2[3];
        callOnItemsRendered(overscanStartIndex, overscanStopIndex, visibleStartIndex, visibleStopIndex);
      }
    }
    if (typeof onScroll === 'function') {
      callOnScroll(scrollDirection, scrollOffset, scrollUpdateWasRequested);
    }
  };
  var getItemStyle = function getItemStyle(index) {
    var itemStyleCache = getItemStyleCache(-1);
    var style;
    var itemStyleCacheValue = itemStyleCache[index];
    if (itemStyleCacheValue) {
      style = itemStyleCacheValue;
    } else {
      var _context$current$item, _context$current$item2;
      var offset = getItemMetadata(props, index, context.current).offset;
      var size = (_context$current$item = (_context$current$item2 = context.current.itemMetadataMap[index]) === null || _context$current$item2 === void 0 ? void 0 : _context$current$item2.size) !== null && _context$current$item !== void 0 ? _context$current$item : 0;
      itemStyleCache[index] = style = {
        position: 'absolute',
        left: 0,
        top: offset,
        height: size,
        width: '100%'
      };
    }
    return style;
  };
  var onScrollVertical = function onScrollVertical(event) {
    var _event$currentTarget = event.currentTarget,
      clientHeight = _event$currentTarget.clientHeight,
      scrollHeight = _event$currentTarget.scrollHeight,
      scrollTop = _event$currentTarget.scrollTop;
    setState(function (prevState) {
      if (prevState.scrollOffset === scrollTop) {
        // Scroll position may have been updated by cDM/cDU,
        // In which case we don't need to trigger another render,
        // And we don't want to update state.isScrolling.
        return _objectSpread({}, prevState);
      }
      // Prevent Safari's elastic scrolling from causing visual shaking when scrolling past bounds.
      var scrollOffset = Math.max(0, Math.min(scrollTop, scrollHeight - clientHeight));
      return _objectSpread(_objectSpread({}, prevState), {}, {
        isScrolling: true,
        scrollDirection: prevState.scrollOffset < scrollOffset ? 'forward' : 'backward',
        scrollOffset: scrollOffset,
        scrollUpdateWasRequested: false
      });
    }, resetIsScrollingDebounced);
  };
  var resetIsScrollingDebounced = function resetIsScrollingDebounced() {
    if (resetIsScrollingTimeoutId.current !== null) {
      cancelTimeout(resetIsScrollingTimeoutId.current);
    }
    resetIsScrollingTimeoutId.current = requestTimeout(resetIsScrolling, IS_SCROLLING_DEBOUNCE_INTERVAL);
  };
  var resetIsScrolling = function resetIsScrolling() {
    resetIsScrollingTimeoutId.current = null;
    setState(function (prevState) {
      return _objectSpread(_objectSpread({}, prevState), {}, {
        isScrolling: false
      });
    }, function () {
      getItemStyleCache(-1);
    });
  };
  (0,react__WEBPACK_IMPORTED_MODULE_20__.useImperativeHandle)(listRef, function () {
    return {
      resetAfterIndex: function resetAfterIndex(index) {
        var shouldForceUpdate = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : true;
        context.current.lastMeasuredIndex = Math.min(context.current.lastMeasuredIndex, index - 1);
        // We could potentially optimize further by only evicting styles after this index,
        // But since styles are only cached while scrolling is in progress-
        // It seems an unnecessary optimization.
        // It's unlikely that resetAfterIndex() will be called while a user is scrolling.
        getItemStyleCache(-1);
        if (shouldForceUpdate) {
          forceUpdate();
        }
      },
      getItemStyleCache: getItemStyleCache,
      forceUpdate: forceUpdate
    };
  });
  (0,react__WEBPACK_IMPORTED_MODULE_20__.useEffect)(function () {
    if (outerListRef.current != null) {
      outerListRef.current.scrollTop = initialScrollOffset;
    }
    callPropsCallbacks();
  }, []);
  (0,react__WEBPACK_IMPORTED_MODULE_20__.useEffect)(function () {
    if (scrollUpdateWasRequested && outerListRef.current != null) {
      outerListRef.current.scrollTop = scrollOffset;
    }
    callPropsCallbacks();
    return function () {
      if (resetIsScrollingTimeoutId.current !== null) {
        cancelTimeout(resetIsScrollingTimeoutId.current);
      }
    };
  });
  var onScroll = onScrollVertical;
  var _getRangeToRender3 = getRangeToRender(),
    _getRangeToRender4 = _slicedToArray(_getRangeToRender3, 2),
    startIndex = _getRangeToRender4[0],
    stopIndex = _getRangeToRender4[1];
  var items = [];
  if (itemCount > 0) {
    for (var index = startIndex; index <= stopIndex; index++) {
      items.push( /*#__PURE__*/(0,react__WEBPACK_IMPORTED_MODULE_20__.createElement)(children, {
        data: itemData,
        key: itemKey(index, itemData),
        index: index,
        isScrolling: isScrolling,
        style: getItemStyle(index)
      }));
    }
  }
  // Read this value AFTER items have been created,
  // So their actual sizes (if variable) are taken into consideration.
  var estimatedTotalSize = getEstimatedTotalSize(props, context.current);
  return /*#__PURE__*/(0,react__WEBPACK_IMPORTED_MODULE_20__.createElement)(outerElementType || 'div', {
    className: className,
    onScroll: onScroll,
    ref: outerRefSetter,
    style: _objectSpread({
      position: 'relative',
      height: height,
      width: width,
      WebkitOverflowScrolling: 'touch',
      willChange: 'transform'
    }, style)
  }, /*#__PURE__*/(0,react__WEBPACK_IMPORTED_MODULE_20__.createElement)(innerElementType || 'div', {
    className: innerClassName,
    ref: innerRef,
    style: _objectSpread({
      height: estimatedTotalSize,
      pointerEvents: isScrolling ? 'none' : undefined,
      width: '100%'
    }, innerStyle)
  }, items));
}

/***/ }),

/***/ "./assets/js-deprecated/component-deprecated/infiniteList/Loader.tsx":
/*!***************************************************************************!*\
  !*** ./assets/js-deprecated/component-deprecated/infiniteList/Loader.tsx ***!
  \***************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var core_js_modules_es_object_to_string_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! core-js/modules/es.object.to-string.js */ "./node_modules/core-js/modules/es.object.to-string.js");
/* harmony import */ var core_js_modules_es_object_to_string_js__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_object_to_string_js__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var core_js_modules_web_dom_collections_for_each_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! core-js/modules/web.dom-collections.for-each.js */ "./node_modules/core-js/modules/web.dom-collections.for-each.js");
/* harmony import */ var core_js_modules_web_dom_collections_for_each_js__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_web_dom_collections_for_each_js__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var core_js_modules_es_symbol_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! core-js/modules/es.symbol.js */ "./node_modules/core-js/modules/es.symbol.js");
/* harmony import */ var core_js_modules_es_symbol_js__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_symbol_js__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var core_js_modules_es_symbol_description_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! core-js/modules/es.symbol.description.js */ "./node_modules/core-js/modules/es.symbol.description.js");
/* harmony import */ var core_js_modules_es_symbol_description_js__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_symbol_description_js__WEBPACK_IMPORTED_MODULE_3__);
/* harmony import */ var core_js_modules_es_symbol_iterator_js__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! core-js/modules/es.symbol.iterator.js */ "./node_modules/core-js/modules/es.symbol.iterator.js");
/* harmony import */ var core_js_modules_es_symbol_iterator_js__WEBPACK_IMPORTED_MODULE_4___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_symbol_iterator_js__WEBPACK_IMPORTED_MODULE_4__);
/* harmony import */ var core_js_modules_es_array_iterator_js__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! core-js/modules/es.array.iterator.js */ "./node_modules/core-js/modules/es.array.iterator.js");
/* harmony import */ var core_js_modules_es_array_iterator_js__WEBPACK_IMPORTED_MODULE_5___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_array_iterator_js__WEBPACK_IMPORTED_MODULE_5__);
/* harmony import */ var core_js_modules_es_string_iterator_js__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! core-js/modules/es.string.iterator.js */ "./node_modules/core-js/modules/es.string.iterator.js");
/* harmony import */ var core_js_modules_es_string_iterator_js__WEBPACK_IMPORTED_MODULE_6___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_string_iterator_js__WEBPACK_IMPORTED_MODULE_6__);
/* harmony import */ var core_js_modules_web_dom_collections_iterator_js__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__(/*! core-js/modules/web.dom-collections.iterator.js */ "./node_modules/core-js/modules/web.dom-collections.iterator.js");
/* harmony import */ var core_js_modules_web_dom_collections_iterator_js__WEBPACK_IMPORTED_MODULE_7___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_web_dom_collections_iterator_js__WEBPACK_IMPORTED_MODULE_7__);
/* harmony import */ var core_js_modules_es_array_slice_js__WEBPACK_IMPORTED_MODULE_8__ = __webpack_require__(/*! core-js/modules/es.array.slice.js */ "./node_modules/core-js/modules/es.array.slice.js");
/* harmony import */ var core_js_modules_es_array_slice_js__WEBPACK_IMPORTED_MODULE_8___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_array_slice_js__WEBPACK_IMPORTED_MODULE_8__);
/* harmony import */ var core_js_modules_es_regexp_to_string_js__WEBPACK_IMPORTED_MODULE_9__ = __webpack_require__(/*! core-js/modules/es.regexp.to-string.js */ "./node_modules/core-js/modules/es.regexp.to-string.js");
/* harmony import */ var core_js_modules_es_regexp_to_string_js__WEBPACK_IMPORTED_MODULE_9___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_regexp_to_string_js__WEBPACK_IMPORTED_MODULE_9__);
/* harmony import */ var core_js_modules_es_function_name_js__WEBPACK_IMPORTED_MODULE_10__ = __webpack_require__(/*! core-js/modules/es.function.name.js */ "./node_modules/core-js/modules/es.function.name.js");
/* harmony import */ var core_js_modules_es_function_name_js__WEBPACK_IMPORTED_MODULE_10___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_function_name_js__WEBPACK_IMPORTED_MODULE_10__);
/* harmony import */ var core_js_modules_es_array_from_js__WEBPACK_IMPORTED_MODULE_11__ = __webpack_require__(/*! core-js/modules/es.array.from.js */ "./node_modules/core-js/modules/es.array.from.js");
/* harmony import */ var core_js_modules_es_array_from_js__WEBPACK_IMPORTED_MODULE_11___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_array_from_js__WEBPACK_IMPORTED_MODULE_11__);
/* harmony import */ var core_js_modules_es_regexp_exec_js__WEBPACK_IMPORTED_MODULE_12__ = __webpack_require__(/*! core-js/modules/es.regexp.exec.js */ "./node_modules/core-js/modules/es.regexp.exec.js");
/* harmony import */ var core_js_modules_es_regexp_exec_js__WEBPACK_IMPORTED_MODULE_12___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_regexp_exec_js__WEBPACK_IMPORTED_MODULE_12__);
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_13__ = __webpack_require__(/*! react */ "./node_modules/react/index.js");
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_13___default = /*#__PURE__*/__webpack_require__.n(react__WEBPACK_IMPORTED_MODULE_13__);
function _slicedToArray(arr, i) { return _arrayWithHoles(arr) || _iterableToArrayLimit(arr, i) || _unsupportedIterableToArray(arr, i) || _nonIterableRest(); }
function _nonIterableRest() { throw new TypeError("Invalid attempt to destructure non-iterable instance.\nIn order to be iterable, non-array objects must have a [Symbol.iterator]() method."); }
function _unsupportedIterableToArray(o, minLen) { if (!o) return; if (typeof o === "string") return _arrayLikeToArray(o, minLen); var n = Object.prototype.toString.call(o).slice(8, -1); if (n === "Object" && o.constructor) n = o.constructor.name; if (n === "Map" || n === "Set") return Array.from(o); if (n === "Arguments" || /^(?:Ui|I)nt(?:8|16|32)(?:Clamped)?Array$/.test(n)) return _arrayLikeToArray(o, minLen); }
function _arrayLikeToArray(arr, len) { if (len == null || len > arr.length) len = arr.length; for (var i = 0, arr2 = new Array(len); i < len; i++) arr2[i] = arr[i]; return arr2; }
function _iterableToArrayLimit(r, l) { var t = null == r ? null : "undefined" != typeof Symbol && r[Symbol.iterator] || r["@@iterator"]; if (null != t) { var e, n, i, u, a = [], f = !0, o = !1; try { if (i = (t = t.call(r)).next, 0 === l) { if (Object(t) !== t) return; f = !1; } else for (; !(f = (e = i.call(t)).done) && (a.push(e.value), a.length !== l); f = !0); } catch (r) { o = !0, n = r; } finally { try { if (!f && null != t.return && (u = t.return(), Object(u) !== u)) return; } finally { if (o) throw n; } } return a; } }
function _arrayWithHoles(arr) { if (Array.isArray(arr)) return arr; }














function scanForUnloadedRanges(isItemLoaded, itemCount, minimumBatchSize, startIndex, stopIndex) {
  var unloadedRanges = [];
  var rangeStartIndex = null;
  var rangeStopIndex = null;
  for (var index = startIndex; index <= stopIndex; index++) {
    var loaded = isItemLoaded(index);
    if (!loaded) {
      rangeStopIndex = index;
      if (rangeStartIndex === null) {
        rangeStartIndex = index;
      }
    } else if (rangeStartIndex !== null && rangeStopIndex !== null) {
      unloadedRanges.push([rangeStartIndex, rangeStopIndex]);
      rangeStartIndex = rangeStopIndex = null;
    }
  }
  // If :rangeStopIndex is not null it means we haven't ran out of unloaded rows.
  // Scan forward to try filling our :minimumBatchSize.
  if (rangeStartIndex !== null && rangeStopIndex !== null) {
    var potentialStopIndex = Math.min(Math.max(rangeStopIndex, rangeStartIndex + minimumBatchSize - 1), itemCount - 1);
    for (var _index = rangeStopIndex + 1; _index <= potentialStopIndex; _index++) {
      if (!isItemLoaded(_index)) {
        rangeStopIndex = _index;
      } else {
        break;
      }
    }
    unloadedRanges.push([rangeStartIndex, rangeStopIndex]);
  }
  // Check to see if our first range ended prematurely.
  // In this case we should scan backwards to try filling our :minimumBatchSize.
  if (unloadedRanges.length) {
    var firstRange = unloadedRanges[0];
    while (firstRange && firstRange[1] - firstRange[0] + 1 < minimumBatchSize && firstRange[0] > 0) {
      var _index2 = firstRange[0] - 1;
      if (!isItemLoaded(_index2)) {
        firstRange[0] = _index2;
      } else {
        break;
      }
    }
  }
  return unloadedRanges;
}
function isRangeVisible(lastRenderedStartIndex, lastRenderedStopIndex, startIndex, stopIndex) {
  return !(startIndex > lastRenderedStopIndex || stopIndex < lastRenderedStartIndex);
}
var Loader = function Loader(props) {
  var context = (0,react__WEBPACK_IMPORTED_MODULE_13__.useRef)({
    lastRenderedStartIndex: -1,
    lastRenderedStopIndex: -1,
    memoizedUnloadedRanges: []
  });
  var _ref5 = (0,react__WEBPACK_IMPORTED_MODULE_13__.useRef)(null);
  function onItemsRendered(visibleStartIndex, visibleStopIndex) {
    context.current.lastRenderedStartIndex = visibleStartIndex;
    context.current.lastRenderedStopIndex = visibleStopIndex;
    ensureRowsLoaded(visibleStartIndex, visibleStopIndex);
  }
  // function resetloadMoreItemsCache(autoReload = false) {
  //     context.current.memoizedUnloadedRanges = [];
  //
  //     if (autoReload) {
  //         ensureRowsLoaded(context.current.lastRenderedStartIndex, context.current.lastRenderedStopIndex);
  //     }
  // }
  function loadUnloadedRanges(unloadedRanges) {
    var loadMoreItems = props.loadMoreItems;
    unloadedRanges.forEach(function (_ref) {
      var _ref2 = _slicedToArray(_ref, 2),
        startIndex = _ref2[0],
        stopIndex = _ref2[1];
      var promise = loadMoreItems(startIndex, stopIndex);
      if (promise != null) {
        promise.then(function () {
          // Refresh the visible rows if any of them have just been loaded.
          // Otherwise they will remain in their unloaded visual state.
          if (isRangeVisible(context.current.lastRenderedStartIndex, context.current.lastRenderedStopIndex, startIndex, stopIndex)) {
            // Handle an unmount while promises are still in flight.
            if (_ref5.current === null) {
              return;
            }
            // Resize cached row sizes for VariableSizeList,
            // otherwise just re-render the list.
            if (typeof _ref5.current.resetAfterIndex === 'function') {
              _ref5.current.resetAfterIndex(startIndex, true);
            } else {
              // HACK reset temporarily cached item styles to force PureComponent to re-render.
              // This is pretty gross, but I'm okay with it for now.
              // Don't judge me.
              if (typeof _ref5.current.getItemStyleCache === 'function') {
                _ref5.current.getItemStyleCache(-1);
              }
              _ref5.current.forceUpdate();
            }
          }
        }).catch(function (e) {
          console.log(e);
        });
      }
    });
  }
  function ensureRowsLoaded(startIndex, stopIndex) {
    var isItemLoaded = props.isItemLoaded,
      itemCount = props.itemCount,
      _props$minimumBatchSi = props.minimumBatchSize,
      minimumBatchSize = _props$minimumBatchSi === void 0 ? 10 : _props$minimumBatchSi,
      _props$threshold = props.threshold,
      threshold = _props$threshold === void 0 ? 15 : _props$threshold;
    var unloadedRanges = scanForUnloadedRanges(isItemLoaded, itemCount, minimumBatchSize, Math.max(0, startIndex - threshold), Math.min(itemCount - 1, stopIndex + threshold));
    // Avoid calling load-rows unless range has changed.
    // This shouldn't be strictly necsesary, but is maybe nice to do.
    if (context.current.memoizedUnloadedRanges.length !== unloadedRanges.length || context.current.memoizedUnloadedRanges.some(function (_ref3, index) {
      var _ref4 = _slicedToArray(_ref3, 2),
        startIndex = _ref4[0],
        stopIndex = _ref4[1];
      var range = unloadedRanges[index];
      return range instanceof Range && (range[0] !== startIndex || range[1] !== stopIndex);
    })) {
      context.current.memoizedUnloadedRanges = unloadedRanges;
      loadUnloadedRanges(unloadedRanges);
    }
  }
  return props.children({
    onItemsRendered: onItemsRendered,
    ref: function ref(listRef) {
      _ref5.current = listRef;
    }
  });
};
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (Loader);

/***/ }),

/***/ "./assets/js-deprecated/component-deprecated/infiniteList/index.ts":
/*!*************************************************************************!*\
  !*** ./assets/js-deprecated/component-deprecated/infiniteList/index.ts ***!
  \*************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   LazyList: () => (/* reexport safe */ _LazyList__WEBPACK_IMPORTED_MODULE_2__["default"]),
/* harmony export */   List: () => (/* reexport safe */ _List__WEBPACK_IMPORTED_MODULE_0__["default"]),
/* harmony export */   Loader: () => (/* reexport safe */ _Loader__WEBPACK_IMPORTED_MODULE_1__["default"])
/* harmony export */ });
/* harmony import */ var _List__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./List */ "./assets/js-deprecated/component-deprecated/infiniteList/List.tsx");
/* harmony import */ var _Loader__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./Loader */ "./assets/js-deprecated/component-deprecated/infiniteList/Loader.tsx");
/* harmony import */ var _LazyList__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./LazyList */ "./assets/js-deprecated/component-deprecated/infiniteList/LazyList.tsx");




/***/ }),

/***/ "./assets/less-deprecated/timeline.less":
/*!**********************************************!*\
  !*** ./assets/less-deprecated/timeline.less ***!
  \**********************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
// extracted by mini-css-extract-plugin


/***/ }),

/***/ "./web/assets/awardwalletnewdesign/less/pages/trips.less":
/*!***************************************************************!*\
  !*** ./web/assets/awardwalletnewdesign/less/pages/trips.less ***!
  \***************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
// extracted by mini-css-extract-plugin


/***/ })

},
/******/ __webpack_require__ => { // webpackRuntimeModules
/******/ var __webpack_exec__ = (moduleId) => (__webpack_require__(__webpack_require__.s = moduleId))
/******/ __webpack_require__.O(0, ["vendors-node_modules_core-js_internals_classof_js-node_modules_core-js_internals_define-built-in_js","vendors-node_modules_core-js_internals_array-slice-simple_js-node_modules_core-js_internals_f-4bac30","vendors-node_modules_core-js_modules_es_string_replace_js","vendors-node_modules_core-js_modules_es_object_keys_js-node_modules_core-js_modules_es_regexp-599444","vendors-node_modules_core-js_internals_array-method-is-strict_js-node_modules_core-js_modules-ea2489","vendors-node_modules_core-js_modules_es_promise_js-node_modules_core-js_modules_web_dom-colle-054322","vendors-node_modules_core-js_internals_string-trim_js-node_modules_core-js_internals_this-num-d4bf43","vendors-node_modules_core-js_modules_es_json_to-string-tag_js-node_modules_core-js_modules_es-dd246b","vendors-node_modules_core-js_modules_es_array_from_js-node_modules_core-js_modules_es_date_to-6fede4","vendors-node_modules_axios-hooks_es_index_js-node_modules_classnames_index_js-node_modules_is-e8b457","vendors-node_modules_prop-types_index_js","vendors-node_modules_memoize-one_dist_memoize-one_esm_js-node_modules_react-measure_dist_inde-f36ad1","assets_bem_ts_service_translator_ts","assets_bem_ts_service_router_ts","assets_bem_ts_service_axios_index_js-assets_bem_ts_service_env_ts","web_assets_common_vendors_date-time-diff_lib_date-time-diff_js","web_assets_awardwalletnewdesign_less_pages_trips_less"], () => (__webpack_exec__("./assets/entry-point-deprecated/timeline/new-index.js")));
/******/ var __webpack_exports__ = __webpack_require__.O();
/******/ }
]);
//# sourceMappingURL=data:application/json;charset=utf-8;base64,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UUFBUSxDQUFDO0lBQ2xCb04sZUFBZSxFQUFmQSxlQUFlO0lBQ2Y3TixHQUFHLEVBQUUsU0FBQUEsSUFBQzBNLE9BQU8sRUFBSztNQUNkMU0sS0FBRyxDQUFDeUssT0FBTyxHQUFHaUMsT0FBTztJQUN6QjtFQUNKLENBQUMsQ0FBQztBQUNOLENBQUM7QUFDRCxpRUFBZVIsTUFBTTs7Ozs7Ozs7Ozs7Ozs7Ozs7OztBQy9Ib0I7QUFDSTs7Ozs7Ozs7Ozs7O0FDRDdDOzs7Ozs7Ozs7Ozs7QUNBQSIsInNvdXJjZXMiOlsid2VicGFjazovL0F3YXJkV2FsbGV0Ly4vYXNzZXRzL2VudHJ5LXBvaW50LWRlcHJlY2F0ZWQvdGltZWxpbmUvbmV3LWluZGV4LmpzIiwid2VicGFjazovL0F3YXJkV2FsbGV0Ly4vYXNzZXRzL2pzLWRlcHJlY2F0ZWQvY29tcG9uZW50LWRlcHJlY2F0ZWQvdGltZWxpbmUvRW1wdHlMaXN0LmpzIiwid2VicGFjazovL0F3YXJkV2FsbGV0Ly4vYXNzZXRzL2pzLWRlcHJlY2F0ZWQvY29tcG9uZW50LWRlcHJlY2F0ZWQvdGltZWxpbmUvTWFpbGJveE9mZmVyLmpzIiwid2VicGFjazovL0F3YXJkV2FsbGV0Ly4vYXNzZXRzL2pzLWRlcHJlY2F0ZWQvY29tcG9uZW50LWRlcHJlY2F0ZWQvdGltZWxpbmUvUGFzdFNlZ21lbnRzTG9hZGVyTGluay5qcyIsIndlYnBhY2s6Ly9Bd2FyZFdhbGxldC8uL2Fzc2V0cy9qcy1kZXByZWNhdGVkL2NvbXBvbmVudC1kZXByZWNhdGVkL3RpbWVsaW5lL1Nob3dEZWxldGVkU2VnbWVudHNMaW5rLmpzIiwid2VicGFjazovL0F3YXJkV2FsbGV0Ly4vYXNzZXRzL2pzLWRlcHJlY2F0ZWQvY29tcG9uZW50LWRlcHJlY2F0ZWQvdGltZWxpbmUvaW5kZXguanMiLCJ3ZWJwYWNrOi8vQXdhcmRXYWxsZXQvLi9hc3NldHMvanMtZGVwcmVjYXRlZC9jb21wb25lbnQtZGVwcmVjYXRlZC90aW1lbGluZS9zZWdtZW50L0RhdGUuanMiLCJ3ZWJwYWNrOi8vQXdhcmRXYWxsZXQvLi9hc3NldHMvanMtZGVwcmVjYXRlZC9jb21wb25lbnQtZGVwcmVjYXRlZC90aW1lbGluZS9zZWdtZW50L1BsYW5FbmQuanMiLCJ3ZWJwYWNrOi8vQXdhcmRXYWxsZXQvLi9hc3NldHMvanMtZGVwcmVjYXRlZC9jb21wb25lbnQtZGVwcmVjYXRlZC90aW1lbGluZS9zZWdtZW50L1BsYW5TdGFydC5qcyIsIndlYnBhY2s6Ly9Bd2FyZFdhbGxldC8uL2Fzc2V0cy9qcy1kZXByZWNhdGVkL2NvbXBvbmVudC1kZXByZWNhdGVkL3RpbWVsaW5lL3NlZ21lbnQvU2VnbWVudC5qcyIsIndlYnBhY2s6Ly9Bd2FyZFdhbGxldC8uL2Fzc2V0cy9qcy1kZXByZWNhdGVkL2NvbXBvbmVudC1kZXByZWNhdGVkL3RpbWVsaW5lL3NlZ21lbnQvaW5kZXguanMiLCJ3ZWJwYWNrOi8vQXdhcmRXYWxsZXQvLi9hc3NldHMvYmVtL3RzL2hvb2svdXNlRm9yY2VVcGRhdGUudHMiLCJ3ZWJwYWNrOi8vQXdhcmRXYWxsZXQvLi9hc3NldHMvYmVtL3RzL2hvb2svdXNlU3RhdGVXaXRoQ2FsbGJhY2sudHMiLCJ3ZWJwYWNrOi8vQXdhcmRXYWxsZXQvLi9hc3NldHMvYmVtL3RzL3NlcnZpY2UvZGF0ZS10aW1lLWRpZmYudHMiLCJ3ZWJwYWNrOi8vQXdhcmRXYWxsZXQvLi9hc3NldHMvYmVtL3RzL3NlcnZpY2Uvb24tcmVhZHkudHMiLCJ3ZWJwYWNrOi8vQXdhcmRXYWxsZXQvLi9hc3NldHMvYmVtL3RzL3N0YXJ0ZXIudHMiLCJ3ZWJwYWNrOi8vQXdhcmRXYWxsZXQvLi9hc3NldHMvanMtZGVwcmVjYXRlZC9jb21wb25lbnQtZGVwcmVjYXRlZC9TcGlubmVyLnRzeCIsIndlYnBhY2s6Ly9Bd2FyZFdhbGxldC8uL2Fzc2V0cy9qcy1kZXByZWNhdGVkL2NvbXBvbmVudC1kZXByZWNhdGVkL2luZmluaXRlTGlzdC9MYXp5TGlzdC50c3giLCJ3ZWJwYWNrOi8vQXdhcmRXYWxsZXQvLi9hc3NldHMvanMtZGVwcmVjYXRlZC9jb21wb25lbnQtZGVwcmVjYXRlZC9pbmZpbml0ZUxpc3QvTGlzdC50c3giLCJ3ZWJwYWNrOi8vQXdhcmRXYWxsZXQvLi9hc3NldHMvanMtZGVwcmVjYXRlZC9jb21wb25lbnQtZGVwcmVjYXRlZC9pbmZpbml0ZUxpc3QvTG9hZGVyLnRzeCIsIndlYnBhY2s6Ly9Bd2FyZFdhbGxldC8uL2Fzc2V0cy9qcy1kZXByZWNhdGVkL2NvbXBvbmVudC1kZXByZWNhdGVkL2luZmluaXRlTGlzdC9pbmRleC50cyIsIndlYnBhY2s6Ly9Bd2FyZFdhbGxldC8uL2Fzc2V0cy9sZXNzLWRlcHJlY2F0ZWQvdGltZWxpbmUubGVzcz84ZDkzIiwid2VicGFjazovL0F3YXJkV2FsbGV0Ly4vd2ViL2Fzc2V0cy9hd2FyZHdhbGxldG5ld2Rlc2lnbi9sZXNzL3BhZ2VzL3RyaXBzLmxlc3M/YzYyYyJdLCJzb3VyY2VzQ29udGVudCI6WyJpbXBvcnQgJy4uLy4uLy4uL3dlYi9hc3NldHMvYXdhcmR3YWxsZXRuZXdkZXNpZ24vbGVzcy9wYWdlcy90cmlwcy5sZXNzJztcbmltcG9ydCAnLi4vLi4vYmVtL3RzL3N0YXJ0ZXInO1xuaW1wb3J0ICcuLi8uLi9sZXNzLWRlcHJlY2F0ZWQvdGltZWxpbmUubGVzcyc7XG5pbXBvcnQge3JlbmRlcn0gZnJvbSAncmVhY3QtZG9tJztcbmltcG9ydCBSZWFjdCBmcm9tICdyZWFjdCc7XG5pbXBvcnQgVGltZWxpbmVBcHAgZnJvbSAnLi4vLi4vanMtZGVwcmVjYXRlZC9jb21wb25lbnQtZGVwcmVjYXRlZC90aW1lbGluZSc7XG5cbmNvbnN0IGFwcEVsZW1lbnQgPSBkb2N1bWVudC5nZXRFbGVtZW50QnlJZCgncmVhY3QtYXBwJyk7XG5jb25zdCBoZWlnaHQgPSBkb2N1bWVudC5nZXRFbGVtZW50c0J5Q2xhc3NOYW1lKCdwYWdlJylbMF0ub2Zmc2V0SGVpZ2h0O1xuY29uc3QgYWxsb3dTaG93RGVsZXRlZFNlZ21lbnRzID0gYXBwRWxlbWVudC5kYXRhc2V0LmFsbG93U2hvd0RlbGV0ZWQgPT09ICd0cnVlJztcblxucmVuZGVyKFxuICAgIDxSZWFjdC5TdHJpY3RNb2RlPlxuICAgICAgICA8VGltZWxpbmVBcHAgY29udGFpbmVySGVpZ2h0PXtoZWlnaHR9IGFsbG93U2hvd0RlbGV0ZWRTZWdtZW50cz17YWxsb3dTaG93RGVsZXRlZFNlZ21lbnRzfSAvPlxuICAgIDwvUmVhY3QuU3RyaWN0TW9kZT4sXG4gICAgYXBwRWxlbWVudFxuKTsiLCJpbXBvcnQgUHJvcFR5cGVzIGZyb20gJ3Byb3AtdHlwZXMnO1xuaW1wb3J0IFJlYWN0IGZyb20gJ3JlYWN0JztcbmltcG9ydCBUcmFuc2xhdG9yIGZyb20gJy4uLy4uLy4uL2JlbS90cy9zZXJ2aWNlL3RyYW5zbGF0b3InO1xuXG5jb25zdCBFbXB0eUxpc3QgPSAoe21lc3NhZ2UgPSBudWxsfSkgPT4ge1xuICAgIGlmICghbWVzc2FnZSkge1xuICAgICAgICBtZXNzYWdlID0gVHJhbnNsYXRvci50cmFucygndHJpcHMubm8tdHJpcHMudGV4dCcpO1xuICAgIH1cblxuICAgIHJldHVybiAoXG4gICAgICAgIDxkaXYgY2xhc3NOYW1lPVwibm8tcmVzdWx0XCI+XG4gICAgICAgICAgICA8ZGl2IGNsYXNzTmFtZT1cIm5vLXJlc3VsdC1pdGVtXCI+XG4gICAgICAgICAgICAgICAgPGkgY2xhc3NOYW1lPVwiaWNvbi13YXJuaW5nLXNtYWxsXCIvPlxuICAgICAgICAgICAgICAgIDxwPnttZXNzYWdlfTwvcD5cbiAgICAgICAgICAgIDwvZGl2PlxuICAgICAgICA8L2Rpdj5cbiAgICApO1xufTtcblxuRW1wdHlMaXN0LnByb3BUeXBlcyA9IHtcbiAgICBtZXNzYWdlOiBQcm9wVHlwZXMuc3RyaW5nLFxufTtcblxuZXhwb3J0IGRlZmF1bHQgRW1wdHlMaXN0OyIsImltcG9ydCBQcm9wVHlwZXMgZnJvbSAncHJvcC10eXBlcyc7XG5pbXBvcnQgUmVhY3QgZnJvbSAncmVhY3QnO1xuaW1wb3J0IFJvdXRlciBmcm9tICcuLi8uLi8uLi9iZW0vdHMvc2VydmljZS9yb3V0ZXInO1xuaW1wb3J0IFRyYW5zbGF0b3IgZnJvbSAnLi4vLi4vLi4vYmVtL3RzL3NlcnZpY2UvdHJhbnNsYXRvcic7XG5cbmNvbnN0IE1haWxib3hPZmZlciA9ICh7Zm9yd2FyZGluZ0VtYWlsfSkgPT4gKFxuICAgIDxkaXZcbiAgICAgICAgY2xhc3NOYW1lPVwidHJpcC1pbmZvXCJcbiAgICAgICAgZGFuZ2Vyb3VzbHlTZXRJbm5lckhUTUw9e3tfX2h0bWw6IFRyYW5zbGF0b3IudHJhbnMoXG4gICAgICAgICAgICAnc2Nhbm5lci5saW5rX21haWxib3hfb3JfZm9yd2FyZCcsXG4gICAgICAgICAgICB7XG4gICAgICAgICAgICAgICAgJ2xpbmtfb24nOiBgPGEgaHJlZj1cIiR7Um91dGVyLmdlbmVyYXRlKCdhd191c2VybWFpbGJveF92aWV3Jyl9XCIgY2xhc3M9XCJibHVlLWxpbmtcIj5gLFxuICAgICAgICAgICAgICAgICdsaW5rX29mZic6ICc8L2E+JyxcbiAgICAgICAgICAgICAgICAnZW1haWwnOiBgPHNwYW4gY2xhc3M9XCJ1c2VyLWVtYWlsXCI+JHtmb3J3YXJkaW5nRW1haWx9PC9zcGFuPmBcbiAgICAgICAgICAgIH1cbiAgICAgICAgKX19IC8+XG4pO1xuXG5NYWlsYm94T2ZmZXIucHJvcFR5cGVzID0ge1xuICAgIGZvcndhcmRpbmdFbWFpbDogUHJvcFR5cGVzLnN0cmluZy5pc1JlcXVpcmVkLFxufTtcblxuZXhwb3J0IGRlZmF1bHQgTWFpbGJveE9mZmVyOyIsImltcG9ydCBQcm9wVHlwZXMgZnJvbSAncHJvcC10eXBlcyc7XG5pbXBvcnQgUmVhY3QgZnJvbSAncmVhY3QnO1xuaW1wb3J0IFRyYW5zbGF0b3IgZnJvbSAnLi4vLi4vLi4vYmVtL3RzL3NlcnZpY2UvdHJhbnNsYXRvcic7XG5cbmNvbnN0IFBhc3RTZWdtZW50c0xvYWRlckxpbmsgPSAoe2xvYWRpbmcgPSBmYWxzZX0pID0+IChcbiAgICA8PlxuICAgICAgICA8YSBocmVmPVwiI1wiIGNsYXNzTmFtZT1cInBhc3QtdHJhdmVsXCI+XG4gICAgICAgICAgICA8aSBjbGFzc05hbWU9XCJpY29uLWRvdWJsZS1hcnJvdy11cC1kYXJrXCIgLz5cbiAgICAgICAgICAgIHtcbiAgICAgICAgICAgICAgICAhbG9hZGluZyAmJlxuICAgICAgICAgICAgICAgIDxzcGFuPnsgVHJhbnNsYXRvci50cmFucygndGltZWxpbmUucGFzdC50cmF2ZWwnKSB9PC9zcGFuPlxuICAgICAgICAgICAgfVxuICAgICAgICA8L2E+XG5cbiAgICAgICAge1xuICAgICAgICAgICAgbG9hZGluZyAmJlxuICAgICAgICAgICAgPGEgaHJlZj1cIlwiIGNsYXNzTmFtZT1cInBhc3QtdHJhdmVsXCI+XG4gICAgICAgICAgICAgICAgPGRpdiBjbGFzc05hbWU9XCJsb2FkZXJcIiAvPlxuICAgICAgICAgICAgPC9hPlxuICAgICAgICB9XG4gICAgPC8+XG4pO1xuXG5QYXN0U2VnbWVudHNMb2FkZXJMaW5rLnByb3BUeXBlcyA9IHtcbiAgICBsb2FkaW5nOiBQcm9wVHlwZXMuYm9vbCxcbn07XG5cbmV4cG9ydCBkZWZhdWx0IFBhc3RTZWdtZW50c0xvYWRlckxpbms7IiwiaW1wb3J0IFByb3BUeXBlcyBmcm9tICdwcm9wLXR5cGVzJztcbmltcG9ydCBSZWFjdCBmcm9tICdyZWFjdCc7XG5pbXBvcnQgVHJhbnNsYXRvciBmcm9tICcuLi8uLi8uLi9iZW0vdHMvc2VydmljZS90cmFuc2xhdG9yJztcblxuY29uc3QgU2hvd0RlbGV0ZWRTZWdtZW50c0xpbmsgPSAoe3JldmVyc2V9KSA9PiAoXG4gICAgPGEgaHJlZj1cIiNcIiBjbGFzc05hbWU9XCJkZWxldGVkIGYtcmlnaHRcIj5cbiAgICAgICAge1xuICAgICAgICAgICAgIXJldmVyc2UgJiZcbiAgICAgICAgICAgIDxzcGFuPntUcmFuc2xhdG9yLnRyYW5zKCdzaG93LmRlbGV0ZWQuc2VnbWVudHMnKX08L3NwYW4+XG4gICAgICAgIH1cbiAgICAgICAge1xuICAgICAgICAgICAgcmV2ZXJzZSAmJlxuICAgICAgICAgICAgPHNwYW4+e1RyYW5zbGF0b3IudHJhbnMoJ2hpZGUuZGVsZXRlZC5zZWdtZW50cycpfTwvc3Bhbj5cbiAgICAgICAgfVxuICAgIDwvYT5cbik7XG5cblNob3dEZWxldGVkU2VnbWVudHNMaW5rLnByb3BUeXBlcyA9IHtcbiAgICByZXZlcnNlOiBQcm9wVHlwZXMuYm9vbCxcbn07XG5TaG93RGVsZXRlZFNlZ21lbnRzTGluay5kZWZhdWx0UHJvcHMgPSB7XG4gICAgcmV2ZXJzZTogZmFsc2Vcbn07XG5cbmV4cG9ydCBkZWZhdWx0IFNob3dEZWxldGVkU2VnbWVudHNMaW5rOyIsImltcG9ydCB7IExhenlMaXN0IH0gZnJvbSAnLi4vaW5maW5pdGVMaXN0JztcbmltcG9ydCBBUEkgZnJvbSAnLi4vLi4vLi4vYmVtL3RzL3NlcnZpY2UvYXhpb3MnO1xuaW1wb3J0IEVtcHR5TGlzdCBmcm9tICcuL0VtcHR5TGlzdCc7XG5pbXBvcnQgTWFpbGJveE9mZmVyIGZyb20gJy4vTWFpbGJveE9mZmVyJztcbmltcG9ydCBQYXN0U2VnbWVudHNMb2FkZXJMaW5rIGZyb20gJy4vUGFzdFNlZ21lbnRzTG9hZGVyTGluayc7XG5pbXBvcnQgUHJvcFR5cGVzIGZyb20gJ3Byb3AtdHlwZXMnO1xuaW1wb3J0IFJlYWN0LCB7IHVzZUVmZmVjdCwgdXNlU3RhdGUgfSBmcm9tICdyZWFjdCc7XG5pbXBvcnQgUm91dGVyIGZyb20gJy4uLy4uLy4uL2JlbS90cy9zZXJ2aWNlL3JvdXRlcic7XG5pbXBvcnQgU2VnbWVudFR5cGVzIGZyb20gJy4vc2VnbWVudCc7XG5pbXBvcnQgU2hvd0RlbGV0ZWRTZWdtZW50c0xpbmsgZnJvbSAnLi9TaG93RGVsZXRlZFNlZ21lbnRzTGluayc7XG5pbXBvcnQgU3Bpbm5lciBmcm9tICcuLi9TcGlubmVyJztcbmltcG9ydCBfIGZyb20gJ2xvZGFzaCc7XG5cbmNvbnN0IFRpbWVsaW5lQXBwID0gKHsgY29udGFpbmVySGVpZ2h0LCBhbGxvd1Nob3dEZWxldGVkU2VnbWVudHMgfSkgPT4ge1xuICAgIGNvbnN0IFtmb3J3YXJkaW5nRW1haWwsIHNldEZvcndhcmRpbmdFbWFpbF0gPSB1c2VTdGF0ZShudWxsKTtcbiAgICBjb25zdCBbc2hvd0RlbGV0ZWQsIHNldFNob3dEZWxldGVkXSA9IHVzZVN0YXRlKHRydWUpO1xuICAgIGNvbnN0IFtzZWdtZW50cywgc2V0U2VnbWVudHNdID0gdXNlU3RhdGUoW10pO1xuICAgIGNvbnN0IFtsb2FkaW5nQXBwLCBzZXRMb2FkaW5nQXBwXSA9IHVzZVN0YXRlKHRydWUpO1xuICAgIGNvbnN0IGVtcHR5TGlzdCA9IHNlZ21lbnRzLmxlbmd0aCA9PT0gMDtcblxuICAgIGFzeW5jIGZ1bmN0aW9uIGxvYWRNb3JlKCkge1xuICAgICAgICBjb25zdCBwYXJhbXMgPSB7IHNob3dEZWxldGVkOiBzaG93RGVsZXRlZCA/IDEgOiAwIH07XG4gICAgICAgIGNvbnN0IGJlZm9yZSA9IF8uZ2V0KF8ubGFzdChzZWdtZW50cyksICdzdGFydERhdGUnLCBudWxsKTtcblxuICAgICAgICBpZiAoIV8uaXNOdWxsKGJlZm9yZSkpIHtcbiAgICAgICAgICAgIHBhcmFtcy5iZWZvcmUgPSBiZWZvcmU7XG4gICAgICAgIH1cblxuICAgICAgICBsZXQgZGF0YTtcbiAgICAgICAgdHJ5IHtcbiAgICAgICAgICAgIGNvbnN0IHJlc3BvbnNlID0gYXdhaXQgQVBJLmdldChSb3V0ZXIuZ2VuZXJhdGUoJ2F3X3RpbWVsaW5lX2RhdGEnLCBwYXJhbXMpKTtcbiAgICAgICAgICAgIGRhdGEgPSByZXNwb25zZS5kYXRhO1xuICAgICAgICAgICAgLy8gZXNsaW50LWRpc2FibGUtbmV4dC1saW5lXG4gICAgICAgIH0gY2F0Y2ggKGUpIHt9XG5cbiAgICAgICAgaWYgKGRhdGEpIHtcbiAgICAgICAgICAgIGNvbnN0IHsgc2VnbWVudHM6IG5ld1NlZ21lbnRzLCBmb3J3YXJkaW5nRW1haWwgfSA9IGRhdGE7XG5cbiAgICAgICAgICAgIHNldFNlZ21lbnRzKChzZWdtZW50cykgPT4ge1xuICAgICAgICAgICAgICAgIHJldHVybiBfLnVuaW9uQnkoc2VnbWVudHMsIG5ld1NlZ21lbnRzID8gbmV3U2VnbWVudHMgOiBbXSwgKHNlZ21lbnQpID0+IHNlZ21lbnQuaWQpO1xuICAgICAgICAgICAgfSk7XG4gICAgICAgICAgICBzZXRGb3J3YXJkaW5nRW1haWwoZm9yd2FyZGluZ0VtYWlsKTtcbiAgICAgICAgICAgIHNldExvYWRpbmdBcHAoZmFsc2UpO1xuICAgICAgICB9XG4gICAgfVxuXG4gICAgZnVuY3Rpb24gaXRlbUtleShpbmRleCkge1xuICAgICAgICByZXR1cm4gc2VnbWVudHNbaW5kZXhdLmlkO1xuICAgIH1cblxuICAgIGZ1bmN0aW9uIFNlZ21lbnRSZW5kZXJlcihpbmRleCwgcmVmKSB7XG4gICAgICAgIGNvbnN0IHNlZ21lbnREYXRhID0gc2VnbWVudHNbaW5kZXhdO1xuICAgICAgICAvLyBlc2xpbnQtZGlzYWJsZS1uZXh0LWxpbmUgcmVhY3QvcHJvcC10eXBlc1xuICAgICAgICBjb25zdCB7IHR5cGU6IHNlZ21lbnRUeXBlLCAuLi5zZWdtZW50UHJvcHMgfSA9IHNlZ21lbnREYXRhO1xuXG4gICAgICAgIGlmIChfLmhhcyhTZWdtZW50VHlwZXMsIHNlZ21lbnRUeXBlKSkge1xuICAgICAgICAgICAgcmV0dXJuIFJlYWN0LmNyZWF0ZUVsZW1lbnQoU2VnbWVudFR5cGVzW3NlZ21lbnRUeXBlXSwgeyAuLi5zZWdtZW50UHJvcHMsIHJlZiB9KTtcbiAgICAgICAgfVxuXG4gICAgICAgIHJldHVybiBSZWFjdC5jcmVhdGVFbGVtZW50KFNlZ21lbnRUeXBlcy5zZWdtZW50LCB7IC4uLnNlZ21lbnRQcm9wcywgcmVmIH0pO1xuICAgIH1cblxuICAgIGNvbnN0IFRpbWVsaW5lQ29udGFpbmVyID0gUmVhY3QuZm9yd2FyZFJlZigoeyBjaGlsZHJlbiwgLi4ucHJvcHMgfSwgcmVmKSA9PiB7XG4gICAgICAgIHJldHVybiAoXG4gICAgICAgICAgICA8PlxuICAgICAgICAgICAgICAgIHshXy5pc0VtcHR5KGZvcndhcmRpbmdFbWFpbCkgJiYgPE1haWxib3hPZmZlciBmb3J3YXJkaW5nRW1haWw9e2ZvcndhcmRpbmdFbWFpbH0gLz59XG4gICAgICAgICAgICAgICAgPFBhc3RTZWdtZW50c0xvYWRlckxpbmsgLz5cbiAgICAgICAgICAgICAgICB7YWxsb3dTaG93RGVsZXRlZFNlZ21lbnRzICYmIDxTaG93RGVsZXRlZFNlZ21lbnRzTGluayByZXZlcnNlPXtzaG93RGVsZXRlZH0gLz59XG4gICAgICAgICAgICAgICAgPGRpdiByZWY9e3JlZn0gey4uLnByb3BzfSBjbGFzc05hbWU9eyd0cmlwLWxpc3QnfT5cbiAgICAgICAgICAgICAgICAgICAge2NoaWxkcmVufVxuICAgICAgICAgICAgICAgIDwvZGl2PlxuICAgICAgICAgICAgPC8+XG4gICAgICAgICk7XG4gICAgfSk7XG4gICAgVGltZWxpbmVDb250YWluZXIuZGlzcGxheU5hbWUgPSAnVGltZWxpbmVDb250YWluZXInO1xuICAgIFRpbWVsaW5lQ29udGFpbmVyLnByb3BUeXBlcyA9IHtcbiAgICAgICAgY2hpbGRyZW46IFByb3BUeXBlcy5ub2RlLFxuICAgIH07XG5cbiAgICB1c2VFZmZlY3QoKCkgPT4ge1xuICAgICAgICBsb2FkTW9yZSgpO1xuICAgICAgICAvLyBlc2xpbnQtZGlzYWJsZS1uZXh0LWxpbmVcbiAgICB9LCBbXSk7XG5cbiAgICBpZiAobG9hZGluZ0FwcCkge1xuICAgICAgICByZXR1cm4gKFxuICAgICAgICAgICAgPGRpdiBjbGFzc05hbWU9XCJ0cmlwXCI+XG4gICAgICAgICAgICAgICAgPFNwaW5uZXIgLz5cbiAgICAgICAgICAgIDwvZGl2PlxuICAgICAgICApO1xuICAgIH1cblxuICAgIHJldHVybiAoXG4gICAgICAgIDxkaXYgY2xhc3NOYW1lPVwidHJpcFwiIHN0eWxlPXt7IGhlaWdodDogY29udGFpbmVySGVpZ2h0IH19PlxuICAgICAgICAgICAgeyFlbXB0eUxpc3QgJiYgKFxuICAgICAgICAgICAgICAgIDxMYXp5TGlzdFxuICAgICAgICAgICAgICAgICAgICBpdGVtQ291bnQ9e3NlZ21lbnRzLmxlbmd0aH1cbiAgICAgICAgICAgICAgICAgICAgbG9hZE1vcmU9e2xvYWRNb3JlfVxuICAgICAgICAgICAgICAgICAgICBoZWlnaHQ9e2NvbnRhaW5lckhlaWdodH1cbiAgICAgICAgICAgICAgICAgICAgbGlzdFByb3BzPXt7XG4gICAgICAgICAgICAgICAgICAgICAgICBpdGVtS2V5LFxuICAgICAgICAgICAgICAgICAgICAgICAgaW5uZXJFbGVtZW50VHlwZTogVGltZWxpbmVDb250YWluZXIsXG4gICAgICAgICAgICAgICAgICAgICAgICBzdHlsZToge1xuICAgICAgICAgICAgICAgICAgICAgICAgICAgIG92ZXJmbG93WTogJ3Njcm9sbCcsXG4gICAgICAgICAgICAgICAgICAgICAgICB9LFxuICAgICAgICAgICAgICAgICAgICAgICAgaW5uZXJTdHlsZToge1xuICAgICAgICAgICAgICAgICAgICAgICAgICAgIHdpZHRoOiAnaW5oZXJpdCcsXG4gICAgICAgICAgICAgICAgICAgICAgICB9LFxuICAgICAgICAgICAgICAgICAgICB9fVxuICAgICAgICAgICAgICAgID5cbiAgICAgICAgICAgICAgICAgICAge1NlZ21lbnRSZW5kZXJlcn1cbiAgICAgICAgICAgICAgICA8L0xhenlMaXN0PlxuICAgICAgICAgICAgKX1cbiAgICAgICAgICAgIHtlbXB0eUxpc3QgJiYgPEVtcHR5TGlzdCAvPn1cbiAgICAgICAgPC9kaXY+XG4gICAgKTtcbn07XG5cblRpbWVsaW5lQXBwLnByb3BUeXBlcyA9IHtcbiAgICBjb250YWluZXJIZWlnaHQ6IFByb3BUeXBlcy5udW1iZXIuaXNSZXF1aXJlZCxcbiAgICBhbGxvd1Nob3dEZWxldGVkU2VnbWVudHM6IFByb3BUeXBlcy5ib29sLFxufTtcblRpbWVsaW5lQXBwLmRlZmF1bHRQcm9wcyA9IHtcbiAgICBhbGxvd1Nob3dEZWxldGVkU2VnbWVudHM6IGZhbHNlLFxufTtcblxuZXhwb3J0IGRlZmF1bHQgVGltZWxpbmVBcHA7XG4iLCJpbXBvcnQgKiBhcyBfIGZyb20gJ2xvZGFzaCc7XG5pbXBvcnQgRGF0ZVRpbWVEaWZmIGZyb20gJy4uLy4uLy4uLy4uL2JlbS90cy9zZXJ2aWNlL2RhdGUtdGltZS1kaWZmJztcbmltcG9ydCBQcm9wVHlwZXMgZnJvbSAncHJvcC10eXBlcyc7XG5pbXBvcnQgUmVhY3QgZnJvbSAncmVhY3QnO1xuaW1wb3J0IGNsYXNzTmFtZXMgZnJvbSAnY2xhc3NuYW1lcyc7XG5cbmNvbnN0IERhdGVTZWdtZW50ID0gUmVhY3QuZm9yd2FyZFJlZigocHJvcHMsIHJlZikgPT4ge1xuICAgIGNvbnN0IHtcbiAgICAgICAgaWQsXG4gICAgICAgIHN0YXJ0RGF0ZSxcbiAgICAgICAgbG9jYWxEYXRlLFxuICAgICAgICBsb2NhbERhdGVJU08sXG4gICAgfSA9IHByb3BzO1xuXG4gICAgZnVuY3Rpb24gZ2V0UmVsYXRpdmVEYXRlKCkge1xuICAgICAgICByZXR1cm4gRGF0ZVRpbWVEaWZmLmxvbmdGb3JtYXRWaWFEYXRlcyhuZXcgRGF0ZSgpLCBuZXcgRGF0ZShsb2NhbERhdGVJU08pKTtcbiAgICB9XG5cbiAgICBmdW5jdGlvbiBnZXREYXlzTnVtYmVyRnJvbVRvZGF5KCkge1xuICAgICAgICBjb25zdCBkaWZmID0gTWF0aC5hYnMobmV3IERhdGUoc3RhcnREYXRlICogMTAwMCkgLSBuZXcgRGF0ZSgpKTtcblxuICAgICAgICByZXR1cm4gTWF0aC5mbG9vcihkaWZmIC8gMTAwMCAvIDYwIC8gNjAgLyAyNCk7XG4gICAgfVxuXG4gICAgZnVuY3Rpb24gY2FwaXRhbGl6ZShzdHIpIHtcbiAgICAgICAgcmV0dXJuIHN0ci5jaGFyQXQoMCkudG9VcHBlckNhc2UoKSArIHN0ci5zbGljZSgxKTtcbiAgICB9XG5cbiAgICBmdW5jdGlvbiBnZXREYXRlQmxvY2soKSB7XG4gICAgICAgIGNvbnN0IHJlbGF0aXZlRGF0ZSA9IGdldFJlbGF0aXZlRGF0ZSgpO1xuXG4gICAgICAgIGlmIChnZXREYXlzTnVtYmVyRnJvbVRvZGF5KCkgPD0gMzApIHtcbiAgICAgICAgICAgIHJldHVybiAoXG4gICAgICAgICAgICAgICAgPD5cbiAgICAgICAgICAgICAgICAgICAge2NhcGl0YWxpemUocmVsYXRpdmVEYXRlKX1cbiAgICAgICAgICAgICAgICAgICAgPHNwYW4gY2xhc3NOYW1lPVwiZGF0ZVwiPntjYXBpdGFsaXplKGxvY2FsRGF0ZSl9PC9zcGFuPlxuICAgICAgICAgICAgICAgIDwvPlxuICAgICAgICAgICAgKTtcbiAgICAgICAgfVxuXG4gICAgICAgIHJldHVybiAoXG4gICAgICAgICAgICA8PlxuICAgICAgICAgICAgICAgIHtjYXBpdGFsaXplKGxvY2FsRGF0ZSl9XG4gICAgICAgICAgICAgICAgPHNwYW4gY2xhc3NOYW1lPVwiZGF0ZVwiPntjYXBpdGFsaXplKHJlbGF0aXZlRGF0ZSl9PC9zcGFuPlxuICAgICAgICAgICAgPC8+XG4gICAgICAgICk7XG4gICAgfVxuXG4gICAgY29uc3QgY2xhc3NOYW1lID0gY2xhc3NOYW1lcyh7XG4gICAgICAgICd0cmlwLWJsayc6IHRydWUsXG4gICAgICAgIGRpc2FibGU6ICgoKSA9PiB7XG4gICAgICAgICAgICBjb25zdCBkYXlTdGFydCA9IG5ldyBEYXRlKCk7XG4gICAgICAgICAgICBkYXlTdGFydC5zZXRIb3VycygwLDAsMCwwKTtcbiAgICAgICAgICAgIHJldHVybiBzdGFydERhdGUgPD0gKGRheVN0YXJ0IC8gMTAwMCk7XG4gICAgICAgIH0pKCksXG4gICAgfSk7XG5cbiAgICByZXR1cm4gKFxuICAgICAgICA8ZGl2IGNsYXNzTmFtZT17Y2xhc3NOYW1lfSByZWY9e3JlZn0gaWQ9e2lkfT5cbiAgICAgICAgICAgIDxkaXYgZGF0YS1pZD17aWR9IGNsYXNzTmFtZT1cImRhdGUtYmxrXCI+XG4gICAgICAgICAgICAgICAgPGRpdj57Z2V0RGF0ZUJsb2NrKCl9PC9kaXY+XG4gICAgICAgICAgICA8L2Rpdj5cbiAgICAgICAgPC9kaXY+XG4gICAgKTtcbn0pO1xuXG5EYXRlU2VnbWVudC5kaXNwbGF5TmFtZSA9ICdEYXRlJztcbkRhdGVTZWdtZW50LnByb3BUeXBlcyA9IHtcbiAgICAvLyBpZDogUHJvcFR5cGVzLnN0cmluZy5pc1JlcXVpcmVkLFxuICAgIC8vIHN0YXJ0RGF0ZTogUHJvcFR5cGVzLm51bWJlci5pc1JlcXVpcmVkLCAvLyB1bml4IHRpbWVzdGFtcFxuICAgIC8vIGVuZERhdGU6IFByb3BUeXBlcy5udW1iZXIuaXNSZXF1aXJlZCwgLy8gdW5peCB0aW1lc3RhbXBcbiAgICAvLyBzdGFydFRpbWV6b25lOiBQcm9wVHlwZXMuc3RyaW5nLmlzUmVxdWlyZWQsXG4gICAgLy8gYnJlYWtBZnRlcjogUHJvcFR5cGVzLmJvb2wuaXNSZXF1aXJlZCwgLy8gY2FuIHdlIHNldCBwYXN0L2Z1dHVyZSBicmVha3BvaW50IGFmdGVyIHRoaXMgaXRlbT9cbiAgICAvL1xuICAgIC8vIGxvY2FsRGF0ZTogUHJvcFR5cGVzLnN0cmluZy5pc1JlcXVpcmVkLFxuICAgIC8vIGxvY2FsRGF0ZUlTTzogUHJvcFR5cGVzLnN0cmluZy5pc1JlcXVpcmVkLFxuICAgIC8vIGxvY2FsRGF0ZVRpbWVJU086IFByb3BUeXBlcy5zdHJpbmcuaXNSZXF1aXJlZCxcbiAgICAvLyBjcmVhdGVQbGFuOiBQcm9wVHlwZXMuYm9vbC5pc1JlcXVpcmVkLFxufTtcblxuZXhwb3J0IGRlZmF1bHQgRGF0ZVNlZ21lbnQ7IiwiaW1wb3J0IFByb3BUeXBlcyBmcm9tICdwcm9wLXR5cGVzJztcbmltcG9ydCBSZWFjdCBmcm9tICdyZWFjdCc7XG5cbmNvbnN0IFBsYW5FbmQgPSBSZWFjdC5mb3J3YXJkUmVmKChwcm9wcywgcmVmKSA9PiB7XG4gICAgY29uc3Qge1xuICAgICAgICBpZCxcbiAgICB9ID0gcHJvcHM7XG5cbiAgICByZXR1cm4gKFxuICAgICAgICA8ZGl2PlxuICAgICAgICA8L2Rpdj5cbiAgICApO1xufSk7XG5cblBsYW5FbmQuZGlzcGxheU5hbWUgPSAnUGxhbkVuZCc7XG5QbGFuRW5kLnByb3BUeXBlcyA9IHtcbiAgICAvLyBpZDogUHJvcFR5cGVzLnN0cmluZy5pc1JlcXVpcmVkLFxuICAgIC8vIHN0YXJ0RGF0ZTogUHJvcFR5cGVzLm51bWJlci5pc1JlcXVpcmVkLCAvLyB1bml4IHRpbWVzdGFtcFxuICAgIC8vIGVuZERhdGU6IFByb3BUeXBlcy5udW1iZXIuaXNSZXF1aXJlZCwgLy8gdW5peCB0aW1lc3RhbXBcbiAgICAvLyBzdGFydFRpbWV6b25lOiBQcm9wVHlwZXMuc3RyaW5nLmlzUmVxdWlyZWQsXG4gICAgLy8gYnJlYWtBZnRlcjogUHJvcFR5cGVzLmJvb2wuaXNSZXF1aXJlZCwgLy8gY2FuIHdlIHNldCBwYXN0L2Z1dHVyZSBicmVha3BvaW50IGFmdGVyIHRoaXMgaXRlbT9cbiAgICAvL1xuICAgIC8vIG5hbWU6IFByb3BUeXBlcy5zdHJpbmcuaXNSZXF1aXJlZCxcbiAgICAvLyBwbGFuSWQ6IFByb3BUeXBlcy5udW1iZXIuaXNSZXF1aXJlZCxcbiAgICAvLyBsb2NhbERhdGU6IFByb3BUeXBlcy5zdHJpbmcuaXNSZXF1aXJlZCxcbiAgICAvLyBjYW5FZGl0OiBQcm9wVHlwZXMuYm9vbC5pc1JlcXVpcmVkLFxufTtcblxuZXhwb3J0IGRlZmF1bHQgUGxhbkVuZDsiLCJpbXBvcnQgUHJvcFR5cGVzIGZyb20gJ3Byb3AtdHlwZXMnO1xuaW1wb3J0IFJlYWN0IGZyb20gJ3JlYWN0JztcblxuY29uc3QgUGxhblN0YXJ0ID0gUmVhY3QuZm9yd2FyZFJlZigocHJvcHMsIHJlZikgPT4ge1xuICAgIGNvbnN0IHtcbiAgICAgICAgaWQsXG4gICAgfSA9IHByb3BzO1xuXG4gICAgcmV0dXJuIChcbiAgICAgICAgPGRpdj5cbiAgICAgICAgPC9kaXY+XG4gICAgKTtcbn0pO1xuXG5QbGFuU3RhcnQuZGlzcGxheU5hbWUgPSAnUGxhblN0YXJ0JztcblBsYW5TdGFydC5wcm9wVHlwZXMgPSB7XG4gICAgLy8gaWQ6IFByb3BUeXBlcy5zdHJpbmcuaXNSZXF1aXJlZCxcbiAgICAvLyBzdGFydERhdGU6IFByb3BUeXBlcy5udW1iZXIuaXNSZXF1aXJlZCwgLy8gdW5peCB0aW1lc3RhbXBcbiAgICAvLyBlbmREYXRlOiBQcm9wVHlwZXMubnVtYmVyLmlzUmVxdWlyZWQsIC8vIHVuaXggdGltZXN0YW1wXG4gICAgLy8gYnJlYWtBZnRlcjogUHJvcFR5cGVzLmJvb2wuaXNSZXF1aXJlZCwgLy8gY2FuIHdlIHNldCBwYXN0L2Z1dHVyZSBicmVha3BvaW50IGFmdGVyIHRoaXMgaXRlbT9cbiAgICAvL1xuICAgIC8vIG5hbWU6IFByb3BUeXBlcy5zdHJpbmcuaXNSZXF1aXJlZCxcbiAgICAvLyBwbGFuSWQ6IFByb3BUeXBlcy5udW1iZXIuaXNSZXF1aXJlZCxcbiAgICAvLyBjYW5FZGl0OiBQcm9wVHlwZXMuYm9vbC5pc1JlcXVpcmVkLFxuICAgIC8vIG1hcDogUHJvcFR5cGVzLnNoYXBlKHtcbiAgICAvLyAgICAgcG9pbnRzOiBQcm9wVHlwZXMuYXJyYXlPZihQcm9wVHlwZXMuc3RyaW5nKSxcbiAgICAvLyAgICAgYXJyVGltZTogUHJvcFR5cGVzLnN0cmluZyxcbiAgICAvLyB9KS5pc1JlcXVpcmVkLFxuICAgIC8vIGxvY2FsRGF0ZTogUHJvcFR5cGVzLnN0cmluZy5pc1JlcXVpcmVkLFxuICAgIC8vIGxhc3RVcGRhdGVkOiBQcm9wVHlwZXMubnVtYmVyLFxuICAgIC8vIHNoYXJlQ29kZTogUHJvcFR5cGVzLnN0cmluZyxcbn07XG5cbmV4cG9ydCBkZWZhdWx0IFBsYW5TdGFydDsiLCJpbXBvcnQgUHJvcFR5cGVzIGZyb20gJ3Byb3AtdHlwZXMnO1xuaW1wb3J0IFJlYWN0IGZyb20gJ3JlYWN0JztcbmltcG9ydCBUcmFuc2xhdG9yIGZyb20gJy4uLy4uLy4uLy4uL2JlbS90cy9zZXJ2aWNlL3RyYW5zbGF0b3InO1xuaW1wb3J0IF8gZnJvbSAnbG9kYXNoJztcbmltcG9ydCBjbGFzc05hbWVzIGZyb20gJ2NsYXNzbmFtZXMnO1xuXG5jb25zdCBTZWdtZW50ID0gUmVhY3QuZm9yd2FyZFJlZigocHJvcHMsIHJlZikgPT4ge1xuICAgIGNvbnN0IHtcbiAgICAgICAgaWQsXG4gICAgICAgIGljb24sXG4gICAgICAgIGNoYW5nZWQsXG4gICAgICAgIGVuZERhdGUsXG4gICAgICAgIGRldGFpbHMsXG4gICAgICAgIGRlbGV0ZWQgPSBmYWxzZSxcbiAgICAgICAgc3RhcnRUaW1lem9uZSxcbiAgICAgICAgcHJldlRpbWUsXG4gICAgICAgIGxvY2FsVGltZSxcbiAgICAgICAgdGl0bGUsXG4gICAgICAgIGNvbmZObyxcbiAgICAgICAgbWFwLFxuICAgIH0gPSBwcm9wcztcbiAgICBjb25zdCBjbGFzc05hbWUgPSBjbGFzc05hbWVzKHtcbiAgICAgICAgZGlzYWJsZTogZW5kRGF0ZSA8PSBEYXRlLm5vdygpIC8gMTAwMCxcbiAgICAgICAgJ25vLWhhbmQnOiAhZGV0YWlscyxcbiAgICAgICAgJ2RlbGV0ZWQtc2VnbWVudCc6IGRlbGV0ZWQsXG4gICAgfSk7XG5cbiAgICBjb25zdCB0cmlwUm93Q2xhc3NJY29uID0gaWNvbi5zcGxpdCgnICcpLnNoaWZ0KCk7XG4gICAgY29uc3QgdHJpcFJvd0NsYXNzID0gY2xhc3NOYW1lcyh7XG4gICAgICAgICd0cmlwLXJvdyc6IHRydWUsXG4gICAgICAgIFsndHJpcC0tJyArIHRyaXBSb3dDbGFzc0ljb25dOiB0cnVlLFxuICAgICAgICBlcnJvcjogY2hhbmdlZCxcbiAgICAgICAgYWN0aXZlOiBmYWxzZSxcbiAgICB9KTtcblxuICAgIGZ1bmN0aW9uIGdldExvY2FsVGltZSh0aW1lKSB7XG4gICAgICAgIGNvbnN0IHBhcnRzID0gdGltZS5zcGxpdCgnICcpO1xuXG4gICAgICAgIGlmIChwYXJ0cy5sZW5ndGggPiAxKSB7XG4gICAgICAgICAgICByZXR1cm4gKFxuICAgICAgICAgICAgICAgIDw+XG4gICAgICAgICAgICAgICAgICAgIHtwYXJ0c1swXX1cbiAgICAgICAgICAgICAgICAgICAgPHNwYW4+e3BhcnRzWzFdfTwvc3Bhbj5cbiAgICAgICAgICAgICAgICA8Lz5cbiAgICAgICAgICAgICk7XG4gICAgICAgIH1cblxuICAgICAgICByZXR1cm4gdGltZTtcbiAgICB9XG5cbiAgICByZXR1cm4gKFxuICAgICAgICA8ZGl2IGNsYXNzTmFtZT17Y2xhc3NOYW1lfSByZWY9e3JlZn0+XG4gICAgICAgICAgICB7ZGVsZXRlZCAmJiAoXG4gICAgICAgICAgICAgICAgPGRpdiBjbGFzc05hbWU9XCJkZWxldGVkLW1lc3NhZ2VcIj5cbiAgICAgICAgICAgICAgICAgICAgPHNwYW4+e1RyYW5zbGF0b3IudHJhbnMoJ3NlZ21lbnQuZGVsZXRlZCcpfTwvc3Bhbj5cbiAgICAgICAgICAgICAgICA8L2Rpdj5cbiAgICAgICAgICAgICl9XG4gICAgICAgICAgICA8ZGl2IGNsYXNzTmFtZT17dHJpcFJvd0NsYXNzfT5cbiAgICAgICAgICAgICAgICA8ZGl2IGNsYXNzTmFtZT1cInRpbWVcIj5cbiAgICAgICAgICAgICAgICAgICAgPGRpdiBjbGFzc05hbWU9XCJ0aW1lLXpvbmVcIj57c3RhcnRUaW1lem9uZX08L2Rpdj5cbiAgICAgICAgICAgICAgICAgICAgPGRpdiBjbGFzc05hbWU9XCJ0aW1lLWl0ZW1cIj5cbiAgICAgICAgICAgICAgICAgICAgICAgIHtfLmlzU3RyaW5nKHByZXZUaW1lKSAmJiBjaGFuZ2VkICYmIDxwIGNsYXNzTmFtZT1cIm9sZC10aW1lXCI+e3ByZXZUaW1lfTwvcD59XG4gICAgICAgICAgICAgICAgICAgICAgICA8cD57Z2V0TG9jYWxUaW1lKGxvY2FsVGltZSl9PC9wPlxuICAgICAgICAgICAgICAgICAgICA8L2Rpdj5cbiAgICAgICAgICAgICAgICA8L2Rpdj5cbiAgICAgICAgICAgICAgICA8ZGl2XG4gICAgICAgICAgICAgICAgICAgIGNsYXNzTmFtZT17Y2xhc3NOYW1lcyh7XG4gICAgICAgICAgICAgICAgICAgICAgICAndHJpcC10aXRsZSc6IHRydWUsXG4gICAgICAgICAgICAgICAgICAgICAgICBbaWNvbl06IHRydWUsXG4gICAgICAgICAgICAgICAgICAgIH0pfVxuICAgICAgICAgICAgICAgICAgICBkYXRhLWlkPXtpZH1cbiAgICAgICAgICAgICAgICA+XG4gICAgICAgICAgICAgICAgICAgIDxkaXYgY2xhc3NOYW1lPVwiaXRlbVwiPlxuICAgICAgICAgICAgICAgICAgICAgICAgPGRpdiBjbGFzc05hbWU9XCJhcnJvd1wiPlxuICAgICAgICAgICAgICAgICAgICAgICAgICAgIDxpIGNsYXNzTmFtZT1cImljb24tc2lsdmVyLWFycm93LWRvd25cIj48L2k+XG4gICAgICAgICAgICAgICAgICAgICAgICA8L2Rpdj5cbiAgICAgICAgICAgICAgICAgICAgICAgIDxkaXYgY2xhc3NOYW1lPVwicHJldlwiPlxuICAgICAgICAgICAgICAgICAgICAgICAgICAgIDxkaXYgY2xhc3NOYW1lPVwicHJldi1pdGVtXCI+XG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIDxpXG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICBjbGFzc05hbWU9e2NsYXNzTmFtZXMoe1xuICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIFsnaWNvbi0nICsgaWNvbl06IHRydWUsXG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICB9KX1cbiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgPjwvaT5cbiAgICAgICAgICAgICAgICAgICAgICAgICAgICA8L2Rpdj5cbiAgICAgICAgICAgICAgICAgICAgICAgIDwvZGl2PlxuICAgICAgICAgICAgICAgICAgICAgICAgPGRpdiBjbGFzc05hbWU9XCJ0aXRsZVwiPlxuICAgICAgICAgICAgICAgICAgICAgICAgICAgIDxoMyBkYW5nZXJvdXNseVNldElubmVySFRNTD17eyBfX2h0bWw6IHRpdGxlIH19PjwvaDM+XG4gICAgICAgICAgICAgICAgICAgICAgICA8L2Rpdj5cbiAgICAgICAgICAgICAgICAgICAgICAgIDxkaXYgY2xhc3NOYW1lPVwibnVtYmVyXCI+XG4gICAgICAgICAgICAgICAgICAgICAgICAgICAge18uaXNTdHJpbmcoY29uZk5vKSAmJiAoXG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIDw+XG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICB7VHJhbnNsYXRvci50cmFucygndGltZWxpbmUuc2VjdGlvbi5jb25mJyl9IHtjb25mTm99XG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIDwvPlxuICAgICAgICAgICAgICAgICAgICAgICAgICAgICl9XG4gICAgICAgICAgICAgICAgICAgICAgICA8L2Rpdj5cbiAgICAgICAgICAgICAgICAgICAgICAgIHtfLmlzQXJyYXkobWFwKSAmJiAoXG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgPGRpdiBjbGFzc05hbWU9XCJtYXBcIj5cbiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgPGltZ1xuICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgc3R5bGU9e3sgd2lkdGg6ICc0NHB4JywgaGVpZ2h0OiAnNDRweCcgfX1cbiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIHNyYz1cIi90cmlwcy9nY21hcC5waHA/Y29kZT00NS40NDIwNjQxJTJDJTIwMTMuNTIzNzQyNSZzaXplPTg4eDg4XCJcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIGFsdD1cIm1hcFwiXG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIC8+XG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgPC9kaXY+XG4gICAgICAgICAgICAgICAgICAgICAgICApfVxuICAgICAgICAgICAgICAgICAgICA8L2Rpdj5cbiAgICAgICAgICAgICAgICA8L2Rpdj5cbiAgICAgICAgICAgIDwvZGl2PlxuICAgICAgICA8L2Rpdj5cbiAgICApO1xufSk7XG5cblNlZ21lbnQuZGlzcGxheU5hbWUgPSAnU2VnbWVudCc7XG5TZWdtZW50LnByb3BUeXBlcyA9IHtcbiAgICBpZDogUHJvcFR5cGVzLnN0cmluZy5pc1JlcXVpcmVkLFxuICAgIHN0YXJ0RGF0ZTogUHJvcFR5cGVzLm51bWJlci5pc1JlcXVpcmVkLCAvLyB1bml4IHRpbWVzdGFtcFxuICAgIGVuZERhdGU6IFByb3BUeXBlcy5udW1iZXIuaXNSZXF1aXJlZCwgLy8gdW5peCB0aW1lc3RhbXBcbiAgICBzdGFydFRpbWV6b25lOiBQcm9wVHlwZXMuc3RyaW5nLmlzUmVxdWlyZWQsXG4gICAgYnJlYWtBZnRlcjogUHJvcFR5cGVzLmJvb2wuaXNSZXF1aXJlZCwgLy8gY2FuIHdlIHNldCBwYXN0L2Z1dHVyZSBicmVha3BvaW50IGFmdGVyIHRoaXMgaXRlbT9cblxuICAgIGljb246IFByb3BUeXBlcy5zdHJpbmcuaXNSZXF1aXJlZCxcbiAgICBsb2NhbFRpbWU6IFByb3BUeXBlcy5zdHJpbmcuaXNSZXF1aXJlZCxcbiAgICBsb2NhbERhdGU6IFByb3BUeXBlcy5udW1iZXIuaXNSZXF1aXJlZCxcbiAgICBsb2NhbERhdGVJU086IFByb3BUeXBlcy5zdHJpbmcuaXNSZXF1aXJlZCxcbiAgICBsb2NhbERhdGVUaW1lSVNPOiBQcm9wVHlwZXMuc3RyaW5nLmlzUmVxdWlyZWQsXG4gICAgbWFwOiBQcm9wVHlwZXMuc2hhcGUoe1xuICAgICAgICBwb2ludHM6IFByb3BUeXBlcy5hcnJheU9mKFByb3BUeXBlcy5zdHJpbmcpLFxuICAgICAgICBhcnJUaW1lOiBQcm9wVHlwZXMub25lT2ZUeXBlKFtQcm9wVHlwZXMuc3RyaW5nLCBQcm9wVHlwZXMuYm9vbF0pLFxuICAgIH0pLFxuICAgIGRldGFpbHM6IFByb3BUeXBlcy5zaGFwZSh7XG4gICAgICAgIGFjY291bnRJZDogUHJvcFR5cGVzLm51bWJlcixcbiAgICAgICAgYWdlbnRJZDogUHJvcFR5cGVzLm51bWJlcixcbiAgICAgICAgcmVmcmVzaExpbms6IFByb3BUeXBlcy5zdHJpbmcsXG4gICAgICAgIGF1dG9Mb2dpbkxpbms6IFByb3BUeXBlcy5zdHJpbmcsXG4gICAgICAgIGJvb2tpbmdMaW5rOiBQcm9wVHlwZXMuc2hhcGUoe1xuICAgICAgICAgICAgaW5mbzogUHJvcFR5cGVzLnN0cmluZy5pc1JlcXVpcmVkLFxuICAgICAgICAgICAgdXJsOiBQcm9wVHlwZXMuc3RyaW5nLmlzUmVxdWlyZWQsXG4gICAgICAgICAgICBmb3JtRmllbGRzOiBQcm9wVHlwZXMuc2hhcGUoe1xuICAgICAgICAgICAgICAgIGRlc3RpbmF0aW9uOiBQcm9wVHlwZXMuc3RyaW5nLmlzUmVxdWlyZWQsXG4gICAgICAgICAgICAgICAgY2hlY2tpbkRhdGU6IFByb3BUeXBlcy5zdHJpbmcuaXNSZXF1aXJlZCxcbiAgICAgICAgICAgICAgICBjaGVja291dERhdGU6IFByb3BUeXBlcy5zdHJpbmcuaXNSZXF1aXJlZCxcbiAgICAgICAgICAgICAgICB1cmw6IFByb3BUeXBlcy5zdHJpbmcuaXNSZXF1aXJlZCxcbiAgICAgICAgICAgIH0pLmlzUmVxdWlyZWQsXG4gICAgICAgIH0pLFxuICAgICAgICBjYW5FZGl0OiBQcm9wVHlwZXMuYm9vbCxcbiAgICAgICAgY2FuQ2hlY2s6IFByb3BUeXBlcy5ib29sLFxuICAgICAgICBjYW5BdXRvTG9naW46IFByb3BUeXBlcy5ib29sLFxuICAgICAgICBTdGF0dXM6IFByb3BUeXBlcy5zdHJpbmcsXG4gICAgICAgIHNoYXJlQ29kZTogUHJvcFR5cGVzLnN0cmluZyxcbiAgICAgICAgbW9uaXRvcmVkU3RhdHVzOiBQcm9wVHlwZXMuc3RyaW5nLFxuICAgICAgICBjb2x1bW5zOiBQcm9wVHlwZXMuYXJyYXlPZihcbiAgICAgICAgICAgIFByb3BUeXBlcy5vbmVPZlR5cGUoW1xuICAgICAgICAgICAgICAgIFByb3BUeXBlcy5zaGFwZSh7XG4gICAgICAgICAgICAgICAgICAgIHR5cGU6ICdhcnJvdycsXG4gICAgICAgICAgICAgICAgfSksXG4gICAgICAgICAgICAgICAgUHJvcFR5cGVzLnNoYXBlKHtcbiAgICAgICAgICAgICAgICAgICAgdHlwZTogJ2luZm8nLFxuICAgICAgICAgICAgICAgICAgICByb3dzOiBQcm9wVHlwZXMuYXJyYXlPZihcbiAgICAgICAgICAgICAgICAgICAgICAgIFByb3BUeXBlcy5vbmVPZlR5cGUoW1xuICAgICAgICAgICAgICAgICAgICAgICAgICAgIFByb3BUeXBlcy5zaGFwZSh7XG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIHR5cGU6ICdhcnJvdycsXG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgfSksXG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgUHJvcFR5cGVzLnNoYXBlKHtcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgdHlwZTogJ2NoZWNraW4nLFxuICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICBkYXRlOiBQcm9wVHlwZXMuc3RyaW5nLmlzUmVxdWlyZWQsXG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIG5pZ2h0czogUHJvcFR5cGVzLm51bWJlci5pc1JlcXVpcmVkLFxuICAgICAgICAgICAgICAgICAgICAgICAgICAgIH0pLFxuICAgICAgICAgICAgICAgICAgICAgICAgICAgIFByb3BUeXBlcy5zaGFwZSh7XG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIHR5cGU6ICdkYXRldGltZScsXG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIGRhdGU6IFByb3BUeXBlcy5zdHJpbmcuaXNSZXF1aXJlZCxcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgdGltZTogUHJvcFR5cGVzLnN0cmluZy5pc1JlcXVpcmVkLFxuICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICBwcmV2VGltZTogUHJvcFR5cGVzLnN0cmluZyxcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgcHJldkRhdGU6IFByb3BUeXBlcy5zdHJpbmcsXG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIHRpbWVzdGFtcDogUHJvcFR5cGVzLm51bWJlcixcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgdGltZXpvbmU6IFByb3BUeXBlcy5zdHJpbmcsXG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIGZvcm1hdHRlZERhdGU6IFByb3BUeXBlcy5zdHJpbmcsXG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIGFycml2YWxEYXk6IFByb3BUeXBlcy5zdHJpbmcsXG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgfSksXG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgUHJvcFR5cGVzLnNoYXBlKHtcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgdHlwZTogJ3RleHQnLFxuICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICB0ZXh0OiBQcm9wVHlwZXMuc3RyaW5nLmlzUmVxdWlyZWQsXG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIGdlbzogUHJvcFR5cGVzLnNoYXBlKHtcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIGNvdW50cnk6IFByb3BUeXBlcy5zdHJpbmcsXG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICBzdGF0ZTogUHJvcFR5cGVzLnN0cmluZyxcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIGNpdHk6IFByb3BUeXBlcy5zdHJpbmcsXG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIH0pLFxuICAgICAgICAgICAgICAgICAgICAgICAgICAgIH0pLFxuICAgICAgICAgICAgICAgICAgICAgICAgICAgIFByb3BUeXBlcy5zaGFwZSh7XG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIHR5cGU6ICdwYWlycycsXG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIHBhaXJzOiBQcm9wVHlwZXMuYXJyYXlPZihQcm9wVHlwZXMub2JqZWN0KS5pc1JlcXVpcmVkLFxuICAgICAgICAgICAgICAgICAgICAgICAgICAgIH0pLFxuICAgICAgICAgICAgICAgICAgICAgICAgICAgIFByb3BUeXBlcy5zaGFwZSh7XG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIHR5cGU6ICdwYWlyJyxcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgbmFtZTogJ0d1ZXN0cycsXG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIHZhbHVlOiBQcm9wVHlwZXMubnVtYmVyLmlzUmVxdWlyZWQsXG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgfSksXG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgUHJvcFR5cGVzLnNoYXBlKHtcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgdHlwZTogJ3BhcmtpbmdTdGFydCcsXG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIGRhdGU6IFByb3BUeXBlcy5zdHJpbmcuaXNSZXF1aXJlZCxcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgZGF5czogUHJvcFR5cGVzLm51bWJlci5pc1JlcXVpcmVkLFxuICAgICAgICAgICAgICAgICAgICAgICAgICAgIH0pLFxuICAgICAgICAgICAgICAgICAgICAgICAgICAgIFByb3BUeXBlcy5zaGFwZSh7XG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIHR5cGU6ICdwaWNrdXAnLFxuICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICBkYXRlOiBQcm9wVHlwZXMuc3RyaW5nLmlzUmVxdWlyZWQsXG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIGRheXM6IFByb3BUeXBlcy5udW1iZXIuaXNSZXF1aXJlZCxcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICB9KSxcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICBQcm9wVHlwZXMuc2hhcGUoe1xuICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICB0eXBlOiAncGlja3VwLnRheGknLFxuICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICBkYXRlOiBQcm9wVHlwZXMuc3RyaW5nLmlzUmVxdWlyZWQsXG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIHRpbWU6IFByb3BUeXBlcy5zdHJpbmcuaXNSZXF1aXJlZCxcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICB9KSxcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICBQcm9wVHlwZXMuc2hhcGUoe1xuICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICB0eXBlOiAnZHJvcG9mZicsXG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIGRhdGU6IFByb3BUeXBlcy5zdHJpbmcuaXNSZXF1aXJlZCxcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgdGltZTogUHJvcFR5cGVzLnN0cmluZy5pc1JlcXVpcmVkLFxuICAgICAgICAgICAgICAgICAgICAgICAgICAgIH0pLFxuICAgICAgICAgICAgICAgICAgICAgICAgICAgIFByb3BUeXBlcy5zaGFwZSh7XG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIHR5cGU6ICdhaXJwb3J0JyxcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgdGV4dDogUHJvcFR5cGVzLnNoYXBlKHtcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIHBsYWNlOiBQcm9wVHlwZXMuc3RyaW5nLmlzUmVxdWlyZWQsXG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICBjb2RlOiBQcm9wVHlwZXMuc3RyaW5nLmlzUmVxdWlyZWQsXG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIH0pLmlzUmVxdWlyZWQsXG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgfSksXG4gICAgICAgICAgICAgICAgICAgICAgICBdKSxcbiAgICAgICAgICAgICAgICAgICAgKSxcbiAgICAgICAgICAgICAgICB9KSxcbiAgICAgICAgICAgIF0pLFxuICAgICAgICApLFxuICAgICAgICBGYXg6IFByb3BUeXBlcy5zdHJpbmcsXG4gICAgICAgIEd1ZXN0Q291bnQ6IFByb3BUeXBlcy5udW1iZXIsXG4gICAgICAgIEtpZHNDb3VudDogUHJvcFR5cGVzLm51bWJlcixcbiAgICAgICAgUm9vbXM6IFByb3BUeXBlcy5udW1iZXIsXG4gICAgICAgIFJvb21Mb25nRGVzY3JpcHRpb25zOiBQcm9wVHlwZXMuc3RyaW5nLFxuICAgICAgICBSb29tU2hvcnREZXNjcmlwdGlvbnM6IFByb3BUeXBlcy5zdHJpbmcsXG4gICAgICAgIFJvb21SYXRlOiBQcm9wVHlwZXMuc3RyaW5nLFxuICAgICAgICBSb29tUmF0ZURlc2NyaXB0aW9uOiBQcm9wVHlwZXMuc3RyaW5nLFxuICAgICAgICBUcmF2ZWxlck5hbWVzOiBQcm9wVHlwZXMuc3RyaW5nLFxuICAgICAgICBDYW5jZWxsYXRpb25Qb2xpY3k6IFByb3BUeXBlcy5zdHJpbmcsXG4gICAgICAgIENhckRlc2NyaXB0aW9uOiBQcm9wVHlwZXMuc3RyaW5nLFxuICAgICAgICBMaWNlbnNlUGxhdGU6IFByb3BUeXBlcy5zdHJpbmcsXG4gICAgICAgIFNwb3ROdW1iZXI6IFByb3BUeXBlcy5zdHJpbmcsXG4gICAgICAgIENhck1vZGVsOiBQcm9wVHlwZXMuc3RyaW5nLFxuICAgICAgICBDYXJUeXBlOiBQcm9wVHlwZXMuc3RyaW5nLFxuICAgICAgICBQaWNrVXBGYXg6IFByb3BUeXBlcy5zdHJpbmcsXG4gICAgICAgIERyb3BPZmZGYXg6IFByb3BUeXBlcy5zdHJpbmcsXG4gICAgICAgIERpbmVyTmFtZTogUHJvcFR5cGVzLnN0cmluZyxcbiAgICAgICAgQ3J1aXNlTmFtZTogUHJvcFR5cGVzLnN0cmluZyxcbiAgICAgICAgRGVjazogUHJvcFR5cGVzLnN0cmluZyxcbiAgICAgICAgQ2FiaW5OdW1iZXI6IFByb3BUeXBlcy5zdHJpbmcsXG4gICAgICAgIFNoaXBDb2RlOiBQcm9wVHlwZXMuc3RyaW5nLFxuICAgICAgICBTaGlwTmFtZTogUHJvcFR5cGVzLnN0cmluZyxcbiAgICAgICAgU2hpcENhYmluQ2xhc3M6IFByb3BUeXBlcy5zdHJpbmcsXG4gICAgICAgIFNtb2tpbmc6IFByb3BUeXBlcy5zdHJpbmcsXG4gICAgICAgIFN0b3BzOiBQcm9wVHlwZXMubnVtYmVyLFxuICAgICAgICBTZXJ2aWNlQ2xhc3NlczogUHJvcFR5cGVzLnN0cmluZyxcbiAgICAgICAgU2VydmljZU5hbWU6IFByb3BUeXBlcy5zdHJpbmcsXG4gICAgICAgIENhck51bWJlcjogUHJvcFR5cGVzLnN0cmluZyxcbiAgICAgICAgQWR1bHRzQ291bnQ6IFByb3BUeXBlcy5udW1iZXIsXG4gICAgICAgIEFpcmNyYWZ0OiBQcm9wVHlwZXMuc3RyaW5nLFxuICAgICAgICBUaWNrZXROdW1iZXJzOiBQcm9wVHlwZXMuc3RyaW5nLFxuICAgICAgICBUcmF2ZWxsZWRNaWxlczogUHJvcFR5cGVzLnN0cmluZyxcbiAgICAgICAgTWVhbDogUHJvcFR5cGVzLnN0cmluZyxcbiAgICAgICAgQm9va2luZ0NsYXNzOiBQcm9wVHlwZXMuc3RyaW5nLFxuICAgICAgICBDYWJpbkNsYXNzOiBQcm9wVHlwZXMuc3RyaW5nLFxuICAgICAgICBwaG9uZTogUHJvcFR5cGVzLnN0cmluZyxcbiAgICB9KSxcbiAgICBvcmlnaW5zOiBQcm9wVHlwZXMuc2hhcGUoe1xuICAgICAgICBhdXRvOiBQcm9wVHlwZXMuYXJyYXlPZihcbiAgICAgICAgICAgIFByb3BUeXBlcy5vbmVPZlR5cGUoW1xuICAgICAgICAgICAgICAgIFByb3BUeXBlcy5zaGFwZSh7XG4gICAgICAgICAgICAgICAgICAgIHR5cGU6ICdhY2NvdW50JyxcbiAgICAgICAgICAgICAgICAgICAgYWNjb3VudElkOiBQcm9wVHlwZXMubnVtYmVyLmlzUmVxdWlyZWQsXG4gICAgICAgICAgICAgICAgICAgIHByb3ZpZGVyOiBQcm9wVHlwZXMuc3RyaW5nLmlzUmVxdWlyZWQsXG4gICAgICAgICAgICAgICAgICAgIGFjY291bnROdW1iZXI6IFByb3BUeXBlcy5zdHJpbmcuaXNSZXF1aXJlZCxcbiAgICAgICAgICAgICAgICAgICAgb3duZXI6IFByb3BUeXBlcy5zdHJpbmcuaXNSZXF1aXJlZCxcbiAgICAgICAgICAgICAgICB9KSxcbiAgICAgICAgICAgICAgICBQcm9wVHlwZXMuc2hhcGUoe1xuICAgICAgICAgICAgICAgICAgICB0eXBlOiAnY29uZk51bWJlcicsXG4gICAgICAgICAgICAgICAgICAgIHByb3ZpZGVyOiBQcm9wVHlwZXMuc3RyaW5nLmlzUmVxdWlyZWQsXG4gICAgICAgICAgICAgICAgICAgIGNvbmZOdW1iZXI6IFByb3BUeXBlcy5zdHJpbmcuaXNSZXF1aXJlZCxcbiAgICAgICAgICAgICAgICB9KSxcbiAgICAgICAgICAgICAgICBQcm9wVHlwZXMuc2hhcGUoe1xuICAgICAgICAgICAgICAgICAgICB0eXBlOiAnZW1haWwnLFxuICAgICAgICAgICAgICAgICAgICBmcm9tOiBQcm9wVHlwZXMubnVtYmVyLmlzUmVxdWlyZWQsXG4gICAgICAgICAgICAgICAgICAgIGVtYWlsOiBQcm9wVHlwZXMuc3RyaW5nLFxuICAgICAgICAgICAgICAgIH0pLFxuICAgICAgICAgICAgXSksXG4gICAgICAgICksXG4gICAgICAgIG1hbnVhbDogUHJvcFR5cGVzLmJvb2wsXG4gICAgfSksXG4gICAgY29uZk5vOiBQcm9wVHlwZXMuc3RyaW5nLFxuICAgIGdyb3VwOiBQcm9wVHlwZXMuc3RyaW5nLFxuICAgIGNoYW5nZWQ6IFByb3BUeXBlcy5ib29sLFxuICAgIGRlbGV0ZWQ6IFByb3BUeXBlcy5ib29sLFxuICAgIGxhc3RTeW5jOiBQcm9wVHlwZXMubnVtYmVyLFxuICAgIGxhc3RVcGRhdGVkOiBQcm9wVHlwZXMubnVtYmVyLFxuICAgIHRpdGxlOiBQcm9wVHlwZXMuc3RyaW5nLFxuICAgIHByZXZUaW1lOiBQcm9wVHlwZXMuc3RyaW5nLFxuICAgIHNlZ21lbnRzOiBQcm9wVHlwZXMubnVtYmVyLFxufTtcblxuZXhwb3J0IGRlZmF1bHQgU2VnbWVudDtcbiIsImltcG9ydCBEYXRlIGZyb20gJy4vRGF0ZSc7XG5pbXBvcnQgUGxhbkVuZCBmcm9tICcuL1BsYW5FbmQnO1xuaW1wb3J0IFBsYW5TdGFydCBmcm9tICcuL1BsYW5TdGFydCc7XG5pbXBvcnQgU2VnbWVudCBmcm9tICcuL1NlZ21lbnQnO1xuXG5leHBvcnQgZGVmYXVsdCB7XG4gICAgJ2RhdGUnOiBEYXRlLFxuICAgICdwbGFuU3RhcnQnOiBQbGFuU3RhcnQsXG4gICAgJ3BsYW5FbmQnOiBQbGFuRW5kLFxuICAgICdzZWdtZW50JzogU2VnbWVudCxcbn07IiwiaW1wb3J0IHsgdXNlU3RhdGUgfSBmcm9tICdyZWFjdCc7XG5leHBvcnQgZGVmYXVsdCBmdW5jdGlvbiB1c2VGb3JjZVVwZGF0ZSgpIHtcbiAgICBjb25zdCBbLCBzZXRDb3VudF0gPSB1c2VTdGF0ZSgwKTtcbiAgICByZXR1cm4gKCkgPT4geyBzZXRDb3VudCgocHJldkNvdW50KSA9PiBwcmV2Q291bnQgKyAxKTsgfTtcbn1cbiIsImltcG9ydCB7IHVzZUVmZmVjdCwgdXNlUmVmLCB1c2VTdGF0ZSB9IGZyb20gJ3JlYWN0JztcbmV4cG9ydCBkZWZhdWx0IGZ1bmN0aW9uIHVzZVN0YXRlV2l0aENhbGxiYWNrKGluaXRpYWxWYWx1ZSkge1xuICAgIGNvbnN0IGNhbGxiYWNrUmVmID0gdXNlUmVmKG51bGwpO1xuICAgIGNvbnN0IFt2YWx1ZSwgc2V0VmFsdWVdID0gdXNlU3RhdGUoaW5pdGlhbFZhbHVlKTtcbiAgICB1c2VFZmZlY3QoKCkgPT4ge1xuICAgICAgICBpZiAoY2FsbGJhY2tSZWYuY3VycmVudCkge1xuICAgICAgICAgICAgY2FsbGJhY2tSZWYuY3VycmVudCh2YWx1ZSk7XG4gICAgICAgICAgICBjYWxsYmFja1JlZi5jdXJyZW50ID0gbnVsbDtcbiAgICAgICAgfVxuICAgIH0sIFt2YWx1ZV0pO1xuICAgIGNvbnN0IHNldFZhbHVlV2l0aENhbGxiYWNrID0gKG5ld1ZhbHVlLCBjYWxsYmFjaykgPT4ge1xuICAgICAgICBpZiAoY2FsbGJhY2spIHtcbiAgICAgICAgICAgIGNhbGxiYWNrUmVmLmN1cnJlbnQgPSBjYWxsYmFjaztcbiAgICAgICAgfVxuICAgICAgICBzZXRWYWx1ZShuZXdWYWx1ZSk7XG4gICAgfTtcbiAgICByZXR1cm4gW3ZhbHVlLCBzZXRWYWx1ZVdpdGhDYWxsYmFja107XG59XG4iLCJpbXBvcnQgeyBleHRyYWN0T3B0aW9ucyB9IGZyb20gJy4vZW52JztcbmltcG9ydCB7IGlzTnVsbCB9IGZyb20gJ2xvZGFzaCc7XG5pbXBvcnQgRFREaWZmIGZyb20gJ2RhdGUtdGltZS1kaWZmJztcbmltcG9ydCBUcmFuc2xhdG9yIGZyb20gJy4vdHJhbnNsYXRvcic7XG5mdW5jdGlvbiBnZXRGb3JtYXR0ZXIoKSB7XG4gICAgY29uc3Qgb3B0cyA9IGV4dHJhY3RPcHRpb25zKCk7XG4gICAgdHJ5IHtcbiAgICAgICAgcmV0dXJuIEludGwuTnVtYmVyRm9ybWF0KG9wdHMubG9jYWxlKTtcbiAgICB9XG4gICAgY2F0Y2ggKGUpIHtcbiAgICAgICAgaWYgKGUgaW5zdGFuY2VvZiBSYW5nZUVycm9yKSB7XG4gICAgICAgICAgICByZXR1cm4gSW50bC5OdW1iZXJGb3JtYXQob3B0cy5kZWZhdWx0TG9jYWxlKTtcbiAgICAgICAgfVxuICAgICAgICBlbHNlIHtcbiAgICAgICAgICAgIHJldHVybiBudWxsO1xuICAgICAgICB9XG4gICAgfVxufVxuZXhwb3J0IGRlZmF1bHQgbmV3IERURGlmZihUcmFuc2xhdG9yLCAobnVtYmVyKSA9PiB7XG4gICAgY29uc3QgZm9ybWF0dGVyID0gZ2V0Rm9ybWF0dGVyKCk7XG4gICAgaWYgKGlzTnVsbChmb3JtYXR0ZXIpKSB7XG4gICAgICAgIHJldHVybiBudW1iZXIudG9TdHJpbmcoKTtcbiAgICB9XG4gICAgcmV0dXJuIGZvcm1hdHRlci5mb3JtYXQobnVtYmVyKTtcbn0pO1xuIiwiZXhwb3J0IGRlZmF1bHQgZnVuY3Rpb24gb25SZWFkeShjYWxsYmFjaykge1xuICAgIGlmIChkb2N1bWVudC5yZWFkeVN0YXRlID09PSAnbG9hZGluZycpIHtcbiAgICAgICAgLy8gVGhlIERPTSBpcyBub3QgeWV0IHJlYWR5LlxuICAgICAgICBkb2N1bWVudC5hZGRFdmVudExpc3RlbmVyKCdET01Db250ZW50TG9hZGVkJywgY2FsbGJhY2spO1xuICAgIH1cbiAgICBlbHNlIHtcbiAgICAgICAgLy8gVGhlIERPTSBpcyBhbHJlYWR5IHJlYWR5LlxuICAgICAgICBjYWxsYmFjaygpO1xuICAgIH1cbn1cbiIsImltcG9ydCB7IGV4dHJhY3RPcHRpb25zIH0gZnJvbSAnLi9zZXJ2aWNlL2Vudic7XG5pbXBvcnQgb25SZWFkeSBmcm9tICcuL3NlcnZpY2Uvb24tcmVhZHknO1xub25SZWFkeShmdW5jdGlvbiAoKSB7XG4gICAgY29uc3Qgb3B0cyA9IGV4dHJhY3RPcHRpb25zKCk7XG4gICAgaWYgKG9wdHMuZW5hYmxlZFRyYW5zSGVscGVyIHx8IG9wdHMuaGFzUm9sZVRyYW5zbGF0b3IpIHtcbiAgICAgICAgY29uc29sZS5sb2coJ2luaXQgdHJhbnNoZWxwZXInKTtcbiAgICAgICAgaW1wb3J0KC8qIHdlYnBhY2tQcmVsb2FkOiB0cnVlICovICcuL3NlcnZpY2UvdHJhbnNIZWxwZXInKVxuICAgICAgICAgICAgLnRoZW4oKHsgZGVmYXVsdDogaW5pdCB9KSA9PiB7IGluaXQoKTsgfSwgKCkgPT4geyBjb25zb2xlLmVycm9yKCd0cmFuc2hlbHBlciBmYWlsZWQgdG8gbG9hZCcpOyB9KTtcbiAgICB9XG59KTtcbiIsImltcG9ydCBSZWFjdCBmcm9tICdyZWFjdCc7XG5jb25zdCBTcGlubmVyID0gKCkgPT4gKFJlYWN0LmNyZWF0ZUVsZW1lbnQoXCJkaXZcIiwgeyBjbGFzc05hbWU6IFwiYWpheC1sb2FkZXJcIiB9LFxuICAgIFJlYWN0LmNyZWF0ZUVsZW1lbnQoXCJkaXZcIiwgeyBjbGFzc05hbWU6IFwibG9hZGluZ1wiIH0pKSk7XG5leHBvcnQgZGVmYXVsdCBTcGlubmVyO1xuIiwiaW1wb3J0IExpc3QgZnJvbSAnLi9MaXN0JztcbmltcG9ydCBMb2FkZXIgZnJvbSAnLi9Mb2FkZXInO1xuaW1wb3J0IE1lYXN1cmUgZnJvbSAncmVhY3QtbWVhc3VyZSc7XG5pbXBvcnQgUmVhY3QsIHsgdXNlUmVmIH0gZnJvbSAncmVhY3QnO1xuY29uc3QgbWVyZ2VSZWZzID0gKC4uLnJlZnMpID0+IChpbmNvbWluZ1JlZikgPT4ge1xuICAgIHJlZnMuZm9yRWFjaCgocmVmKSA9PiB7XG4gICAgICAgIGlmICh0eXBlb2YgcmVmID09PSAnZnVuY3Rpb24nKSB7XG4gICAgICAgICAgICByZWYoaW5jb21pbmdSZWYpO1xuICAgICAgICB9XG4gICAgICAgIGVsc2UgaWYgKHJlZikge1xuICAgICAgICAgICAgcmVmLmN1cnJlbnQgPSBpbmNvbWluZ1JlZjtcbiAgICAgICAgfVxuICAgIH0pO1xufTtcbmV4cG9ydCBkZWZhdWx0IGZ1bmN0aW9uIExhenlMaXN0KHByb3BzKSB7XG4gICAgY29uc3QgeyBjaGlsZHJlbiwgaXRlbUNvdW50LCBsb2FkTW9yZSwgbGlzdFByb3BzLCBoZWlnaHQgfSA9IHByb3BzO1xuICAgIGNvbnN0IGl0ZW1TaXplcyA9IHVzZVJlZih7fSk7XG4gICAgY29uc3QgbGlzdFJlZiA9IHVzZVJlZigpO1xuICAgIGNvbnN0IGdldEl0ZW1TaXplID0gKGluZGV4KSA9PiBpdGVtU2l6ZXMuY3VycmVudFtpbmRleF0gfHwgNTA7XG4gICAgY29uc3QgaGFuZGxlSXRlbVJlc2l6ZSA9IChpbmRleCwgeyBib3VuZHMsIG1hcmdpbiB9KSA9PiB7XG4gICAgICAgIGl0ZW1TaXplcy5jdXJyZW50W2luZGV4XSA9IChib3VuZHM/LmhlaWdodCA/PyAwKSArIChtYXJnaW4/LnRvcCA/PyAwKSArIChtYXJnaW4/LmJvdHRvbSA/PyAwKTtcbiAgICAgICAgaWYgKGxpc3RSZWYuY3VycmVudCkge1xuICAgICAgICAgICAgbGlzdFJlZi5jdXJyZW50LnJlc2V0QWZ0ZXJJbmRleChpbmRleCwgZmFsc2UpO1xuICAgICAgICB9XG4gICAgfTtcbiAgICBjb25zdCBSb3cgPSAoeyBpbmRleCwgc3R5bGUgfSkgPT4ge1xuICAgICAgICByZXR1cm4gKFJlYWN0LmNyZWF0ZUVsZW1lbnQoXCJkaXZcIiwgeyBzdHlsZTogc3R5bGUgfSxcbiAgICAgICAgICAgIFJlYWN0LmNyZWF0ZUVsZW1lbnQoTWVhc3VyZSwgeyBib3VuZHM6IHRydWUsIG1hcmdpbjogdHJ1ZSwgb25SZXNpemU6IChyZXNpemVEYXRhKSA9PiB7IGhhbmRsZUl0ZW1SZXNpemUoaW5kZXgsIHJlc2l6ZURhdGEpOyB9IH0sICh7IG1lYXN1cmVSZWYgfSkgPT4gY2hpbGRyZW4oaW5kZXgsIG1lYXN1cmVSZWYpKSkpO1xuICAgIH07XG4gICAgcmV0dXJuIChSZWFjdC5jcmVhdGVFbGVtZW50KExvYWRlciwgeyBpc0l0ZW1Mb2FkZWQ6IChpbmRleCkgPT4ge1xuICAgICAgICAgICAgcmV0dXJuIGluZGV4IDwgaXRlbUNvdW50O1xuICAgICAgICB9LCBpdGVtQ291bnQ6IGl0ZW1Db3VudCArIDEsIGxvYWRNb3JlSXRlbXM6IGxvYWRNb3JlIH0sICh7IG9uSXRlbXNSZW5kZXJlZCwgcmVmIH0pID0+IHtcbiAgICAgICAgY29uc3QgcmVmcyA9IFtyZWYsIGxpc3RSZWZdO1xuICAgICAgICByZXR1cm4gKFJlYWN0LmNyZWF0ZUVsZW1lbnQoTGlzdCwgeyAuLi5saXN0UHJvcHMsIGxpc3RSZWY6IG1lcmdlUmVmcyguLi5yZWZzKSwgb25JdGVtc1JlbmRlcmVkOiBvbkl0ZW1zUmVuZGVyZWQsIGl0ZW1Db3VudDogaXRlbUNvdW50LCBpdGVtU2l6ZTogZ2V0SXRlbVNpemUsIGhlaWdodDogaGVpZ2h0LCB3aWR0aDogJ2F1dG8nIH0sIFJvdykpO1xuICAgIH0pKTtcbn1cbiIsImltcG9ydCB7IGNyZWF0ZUVsZW1lbnQsIHVzZUVmZmVjdCwgdXNlSW1wZXJhdGl2ZUhhbmRsZSwgdXNlUmVmLCB9IGZyb20gJ3JlYWN0JztcbmltcG9ydCBtZW1vaXplT25lIGZyb20gJ21lbW9pemUtb25lJztcbmltcG9ydCB1c2VGb3JjZVVwZGF0ZSBmcm9tICcuLi8uLi8uLi9iZW0vdHMvaG9vay91c2VGb3JjZVVwZGF0ZSc7XG5pbXBvcnQgdXNlU3RhdGVXaXRoQ2FsbGJhY2sgZnJvbSAnLi4vLi4vLi4vYmVtL3RzL2hvb2svdXNlU3RhdGVXaXRoQ2FsbGJhY2snO1xuY29uc3QgSVNfU0NST0xMSU5HX0RFQk9VTkNFX0lOVEVSVkFMID0gMTUwO1xuY29uc3QgaGFzTmF0aXZlUGVyZm9ybWFuY2VOb3cgPSB0eXBlb2YgcGVyZm9ybWFuY2UgPT09ICdvYmplY3QnICYmIHR5cGVvZiBwZXJmb3JtYW5jZS5ub3cgPT09ICdmdW5jdGlvbic7XG5jb25zdCBub3cgPSBoYXNOYXRpdmVQZXJmb3JtYW5jZU5vdyA/ICgpID0+IHBlcmZvcm1hbmNlLm5vdygpIDogKCkgPT4gRGF0ZS5ub3coKTtcbmZ1bmN0aW9uIGNhbmNlbFRpbWVvdXQodGltZW91dElEKSB7XG4gICAgY2FuY2VsQW5pbWF0aW9uRnJhbWUodGltZW91dElELmlkKTtcbn1cbmZ1bmN0aW9uIHJlcXVlc3RUaW1lb3V0KGNhbGxiYWNrLCBkZWxheSkge1xuICAgIGNvbnN0IHN0YXJ0ID0gbm93KCk7XG4gICAgZnVuY3Rpb24gdGljaygpIHtcbiAgICAgICAgaWYgKG5vdygpIC0gc3RhcnQgPj0gZGVsYXkpIHtcbiAgICAgICAgICAgIGNhbGxiYWNrLmNhbGwobnVsbCk7XG4gICAgICAgIH1cbiAgICAgICAgZWxzZSB7XG4gICAgICAgICAgICB0aW1lb3V0SUQuaWQgPSByZXF1ZXN0QW5pbWF0aW9uRnJhbWUodGljayk7XG4gICAgICAgIH1cbiAgICB9XG4gICAgY29uc3QgdGltZW91dElEID0ge1xuICAgICAgICBpZDogcmVxdWVzdEFuaW1hdGlvbkZyYW1lKHRpY2spLFxuICAgIH07XG4gICAgcmV0dXJuIHRpbWVvdXRJRDtcbn1cbmZ1bmN0aW9uIGdldEl0ZW1NZXRhZGF0YShwcm9wcywgaW5kZXgsIGNvbnRleHQpIHtcbiAgICBjb25zdCB7IGl0ZW1TaXplIH0gPSBwcm9wcztcbiAgICBjb25zdCB7IGl0ZW1NZXRhZGF0YU1hcCwgbGFzdE1lYXN1cmVkSW5kZXggfSA9IGNvbnRleHQ7XG4gICAgaWYgKGluZGV4ID4gbGFzdE1lYXN1cmVkSW5kZXgpIHtcbiAgICAgICAgbGV0IG9mZnNldCA9IDA7XG4gICAgICAgIGlmIChsYXN0TWVhc3VyZWRJbmRleCA+PSAwKSB7XG4gICAgICAgICAgICBjb25zdCBpdGVtTWV0YWRhdGEgPSBpdGVtTWV0YWRhdGFNYXBbbGFzdE1lYXN1cmVkSW5kZXhdO1xuICAgICAgICAgICAgaWYgKGl0ZW1NZXRhZGF0YSkge1xuICAgICAgICAgICAgICAgIG9mZnNldCA9IGl0ZW1NZXRhZGF0YS5vZmZzZXQgKyBpdGVtTWV0YWRhdGEuc2l6ZTtcbiAgICAgICAgICAgIH1cbiAgICAgICAgfVxuICAgICAgICBmb3IgKGxldCBpID0gbGFzdE1lYXN1cmVkSW5kZXggKyAxOyBpIDw9IGluZGV4OyBpKyspIHtcbiAgICAgICAgICAgIGNvbnN0IHNpemUgPSBpdGVtU2l6ZShpKTtcbiAgICAgICAgICAgIGl0ZW1NZXRhZGF0YU1hcFtpXSA9IHtcbiAgICAgICAgICAgICAgICBvZmZzZXQsXG4gICAgICAgICAgICAgICAgc2l6ZSxcbiAgICAgICAgICAgIH07XG4gICAgICAgICAgICBvZmZzZXQgKz0gc2l6ZTtcbiAgICAgICAgfVxuICAgICAgICBjb250ZXh0Lmxhc3RNZWFzdXJlZEluZGV4ID0gaW5kZXg7XG4gICAgfVxuICAgIGNvbnN0IHJlc3VsdCA9IGl0ZW1NZXRhZGF0YU1hcFtpbmRleF07XG4gICAgaWYgKCFyZXN1bHQpIHtcbiAgICAgICAgdGhyb3cgbmV3IEVycm9yKGBJdGVtIG1ldGFkYXRhIGZvciBpbmRleCAke2luZGV4fSBpcyBtaXNzaW5nYCk7XG4gICAgfVxuICAgIHJldHVybiByZXN1bHQ7XG59XG5mdW5jdGlvbiBmaW5kTmVhcmVzdEl0ZW0ocHJvcHMsIGNvbnRleHQsIG9mZnNldCkge1xuICAgIGNvbnN0IHsgaXRlbU1ldGFkYXRhTWFwLCBsYXN0TWVhc3VyZWRJbmRleCB9ID0gY29udGV4dDtcbiAgICBjb25zdCBsYXN0TWVhc3VyZWRJdGVtT2Zmc2V0ID0gbGFzdE1lYXN1cmVkSW5kZXggPiAwID8gaXRlbU1ldGFkYXRhTWFwW2xhc3RNZWFzdXJlZEluZGV4XT8ub2Zmc2V0ID8/IDAgOiAwO1xuICAgIGlmIChsYXN0TWVhc3VyZWRJdGVtT2Zmc2V0ID49IG9mZnNldCkge1xuICAgICAgICAvLyBJZiB3ZSd2ZSBhbHJlYWR5IG1lYXN1cmVkIGl0ZW1zIHdpdGhpbiB0aGlzIHJhbmdlIGp1c3QgdXNlIGEgYmluYXJ5IHNlYXJjaCBhcyBpdCdzIGZhc3Rlci5cbiAgICAgICAgcmV0dXJuIGZpbmROZWFyZXN0SXRlbUJpbmFyeVNlYXJjaChwcm9wcywgY29udGV4dCwgbGFzdE1lYXN1cmVkSW5kZXgsIDAsIG9mZnNldCk7XG4gICAgfVxuICAgIGVsc2Uge1xuICAgICAgICAvLyBJZiB3ZSBoYXZlbid0IHlldCBtZWFzdXJlZCB0aGlzIGhpZ2gsIGZhbGxiYWNrIHRvIGFuIGV4cG9uZW50aWFsIHNlYXJjaCB3aXRoIGFuIGlubmVyIGJpbmFyeSBzZWFyY2guXG4gICAgICAgIC8vIFRoZSBleHBvbmVudGlhbCBzZWFyY2ggYXZvaWRzIHByZS1jb21wdXRpbmcgc2l6ZXMgZm9yIHRoZSBmdWxsIHNldCBvZiBpdGVtcyBhcyBhIGJpbmFyeSBzZWFyY2ggd291bGQuXG4gICAgICAgIC8vIFRoZSBvdmVyYWxsIGNvbXBsZXhpdHkgZm9yIHRoaXMgYXBwcm9hY2ggaXMgTyhsb2cgbikuXG4gICAgICAgIHJldHVybiBmaW5kTmVhcmVzdEl0ZW1FeHBvbmVudGlhbFNlYXJjaChwcm9wcywgY29udGV4dCwgTWF0aC5tYXgoMCwgbGFzdE1lYXN1cmVkSW5kZXgpLCBvZmZzZXQpO1xuICAgIH1cbn1cbmZ1bmN0aW9uIGZpbmROZWFyZXN0SXRlbUJpbmFyeVNlYXJjaChwcm9wcywgY29udGV4dCwgaGlnaCwgbG93LCBvZmZzZXQpIHtcbiAgICB3aGlsZSAobG93IDw9IGhpZ2gpIHtcbiAgICAgICAgY29uc3QgbWlkZGxlID0gbG93ICsgTWF0aC5mbG9vcigoaGlnaCAtIGxvdykgLyAyKTtcbiAgICAgICAgY29uc3QgY3VycmVudE9mZnNldCA9IGdldEl0ZW1NZXRhZGF0YShwcm9wcywgbWlkZGxlLCBjb250ZXh0KS5vZmZzZXQ7XG4gICAgICAgIGlmIChjdXJyZW50T2Zmc2V0ID09PSBvZmZzZXQpIHtcbiAgICAgICAgICAgIHJldHVybiBtaWRkbGU7XG4gICAgICAgIH1cbiAgICAgICAgZWxzZSBpZiAoY3VycmVudE9mZnNldCA8IG9mZnNldCkge1xuICAgICAgICAgICAgbG93ID0gbWlkZGxlICsgMTtcbiAgICAgICAgfVxuICAgICAgICBlbHNlIGlmIChjdXJyZW50T2Zmc2V0ID4gb2Zmc2V0KSB7XG4gICAgICAgICAgICBoaWdoID0gbWlkZGxlIC0gMTtcbiAgICAgICAgfVxuICAgIH1cbiAgICBpZiAobG93ID4gMCkge1xuICAgICAgICByZXR1cm4gbG93IC0gMTtcbiAgICB9XG4gICAgZWxzZSB7XG4gICAgICAgIHJldHVybiAwO1xuICAgIH1cbn1cbmZ1bmN0aW9uIGZpbmROZWFyZXN0SXRlbUV4cG9uZW50aWFsU2VhcmNoKHByb3BzLCBjb250ZXh0LCBpbmRleCwgb2Zmc2V0KSB7XG4gICAgY29uc3QgeyBpdGVtQ291bnQgfSA9IHByb3BzO1xuICAgIGxldCBpbnRlcnZhbCA9IDE7XG4gICAgd2hpbGUgKGluZGV4IDwgaXRlbUNvdW50ICYmIGdldEl0ZW1NZXRhZGF0YShwcm9wcywgaW5kZXgsIGNvbnRleHQpLm9mZnNldCA8IG9mZnNldCkge1xuICAgICAgICBpbmRleCArPSBpbnRlcnZhbDtcbiAgICAgICAgaW50ZXJ2YWwgKj0gMjtcbiAgICB9XG4gICAgcmV0dXJuIGZpbmROZWFyZXN0SXRlbUJpbmFyeVNlYXJjaChwcm9wcywgY29udGV4dCwgTWF0aC5taW4oaW5kZXgsIGl0ZW1Db3VudCAtIDEpLCBNYXRoLmZsb29yKGluZGV4IC8gMiksIG9mZnNldCk7XG59XG5mdW5jdGlvbiBnZXRFc3RpbWF0ZWRUb3RhbFNpemUoeyBpdGVtQ291bnQgfSwgeyBpdGVtTWV0YWRhdGFNYXAsIGVzdGltYXRlZEl0ZW1TaXplLCBsYXN0TWVhc3VyZWRJbmRleCB9KSB7XG4gICAgbGV0IHRvdGFsU2l6ZU9mTWVhc3VyZWRJdGVtcyA9IDA7XG4gICAgLy8gRWRnZSBjYXNlIGNoZWNrIGZvciB3aGVuIHRoZSBudW1iZXIgb2YgaXRlbXMgZGVjcmVhc2VzIHdoaWxlIGEgc2Nyb2xsIGlzIGluIHByb2dyZXNzLlxuICAgIC8vIGh0dHBzOi8vZ2l0aHViLmNvbS9idmF1Z2huL3JlYWN0LXdpbmRvdy9wdWxsLzEzOFxuICAgIGlmIChsYXN0TWVhc3VyZWRJbmRleCA+PSBpdGVtQ291bnQpIHtcbiAgICAgICAgbGFzdE1lYXN1cmVkSW5kZXggPSBpdGVtQ291bnQgLSAxO1xuICAgIH1cbiAgICBpZiAobGFzdE1lYXN1cmVkSW5kZXggPj0gMCkge1xuICAgICAgICBjb25zdCBpdGVtTWV0YWRhdGEgPSBpdGVtTWV0YWRhdGFNYXBbbGFzdE1lYXN1cmVkSW5kZXhdO1xuICAgICAgICBpZiAoaXRlbU1ldGFkYXRhKSB7XG4gICAgICAgICAgICB0b3RhbFNpemVPZk1lYXN1cmVkSXRlbXMgPSBpdGVtTWV0YWRhdGEub2Zmc2V0ICsgaXRlbU1ldGFkYXRhLnNpemU7XG4gICAgICAgIH1cbiAgICB9XG4gICAgY29uc3QgbnVtVW5tZWFzdXJlZEl0ZW1zID0gaXRlbUNvdW50IC0gbGFzdE1lYXN1cmVkSW5kZXggLSAxO1xuICAgIGNvbnN0IHRvdGFsU2l6ZU9mVW5tZWFzdXJlZEl0ZW1zID0gbnVtVW5tZWFzdXJlZEl0ZW1zICogZXN0aW1hdGVkSXRlbVNpemU7XG4gICAgcmV0dXJuIHRvdGFsU2l6ZU9mTWVhc3VyZWRJdGVtcyArIHRvdGFsU2l6ZU9mVW5tZWFzdXJlZEl0ZW1zO1xufVxuZXhwb3J0IGRlZmF1bHQgZnVuY3Rpb24gTGlzdChwcm9wcykge1xuICAgIGNvbnN0IHsgaW5pdGlhbFNjcm9sbE9mZnNldCA9IDAsIGl0ZW1EYXRhID0gdW5kZWZpbmVkLCBvdmVyc2NhbkNvdW50ID0gMiwgZXN0aW1hdGVkSXRlbVNpemUgPSA1MCwgbGlzdFJlZiwgaXRlbUNvdW50LCBpdGVtS2V5ID0gKGluZGV4KSA9PiBpbmRleCwgY2hpbGRyZW4sIG91dGVyRWxlbWVudFR5cGUsIGlubmVyRWxlbWVudFR5cGUsIGlubmVyQ2xhc3NOYW1lLCBjbGFzc05hbWUsIGlubmVyUmVmLCBoZWlnaHQsIHdpZHRoLCBzdHlsZSwgaW5uZXJTdHlsZSwgfSA9IHByb3BzO1xuICAgIGNvbnN0IFt7IGlzU2Nyb2xsaW5nLCBzY3JvbGxVcGRhdGVXYXNSZXF1ZXN0ZWQsIHNjcm9sbERpcmVjdGlvbiwgc2Nyb2xsT2Zmc2V0IH0sIHNldFN0YXRlXSA9IHVzZVN0YXRlV2l0aENhbGxiYWNrKHtcbiAgICAgICAgaXNTY3JvbGxpbmc6IGZhbHNlLFxuICAgICAgICBzY3JvbGxVcGRhdGVXYXNSZXF1ZXN0ZWQ6IGZhbHNlLFxuICAgICAgICBzY3JvbGxEaXJlY3Rpb246ICdmb3J3YXJkJyxcbiAgICAgICAgc2Nyb2xsT2Zmc2V0OiBpbml0aWFsU2Nyb2xsT2Zmc2V0LFxuICAgIH0pO1xuICAgIGNvbnN0IGNvbnRleHQgPSB1c2VSZWYoe1xuICAgICAgICBpdGVtTWV0YWRhdGFNYXA6IHt9LFxuICAgICAgICBsYXN0TWVhc3VyZWRJbmRleDogLTEsXG4gICAgICAgIGVzdGltYXRlZEl0ZW1TaXplOiBlc3RpbWF0ZWRJdGVtU2l6ZSxcbiAgICB9KTtcbiAgICBjb25zdCByZXNldElzU2Nyb2xsaW5nVGltZW91dElkID0gdXNlUmVmKG51bGwpO1xuICAgIGNvbnN0IG91dGVyTGlzdFJlZiA9IHVzZVJlZihudWxsKTtcbiAgICBjb25zdCBnZXRJdGVtU3R5bGVDYWNoZSA9IG1lbW9pemVPbmUoKCkgPT4gKHt9KSk7XG4gICAgY29uc3QgZm9yY2VVcGRhdGUgPSB1c2VGb3JjZVVwZGF0ZSgpO1xuICAgIC8vIGNvbnN0IHNjcm9sbFRvID0gKHNjcm9sbE9mZnNldDogbnVtYmVyKSA9PiB7XG4gICAgLy8gICAgIHNjcm9sbE9mZnNldCA9IE1hdGgubWF4KDAsIHNjcm9sbE9mZnNldCk7XG4gICAgLy8gICAgIHNldFN0YXRlKChwcmV2U3RhdGUpID0+IHtcbiAgICAvLyAgICAgICAgIGlmIChwcmV2U3RhdGUuc2Nyb2xsT2Zmc2V0ID09PSBzY3JvbGxPZmZzZXQpIHtcbiAgICAvLyAgICAgICAgICAgICByZXR1cm4gey4uLnByZXZTdGF0ZX07XG4gICAgLy8gICAgICAgICB9XG4gICAgLy8gICAgICAgICByZXR1cm4ge1xuICAgIC8vICAgICAgICAgICAgIC4uLnByZXZTdGF0ZSxcbiAgICAvLyAgICAgICAgICAgICBzY3JvbGxEaXJlY3Rpb246IHByZXZTdGF0ZS5zY3JvbGxPZmZzZXQgPCBzY3JvbGxPZmZzZXQgPyAnZm9yd2FyZCcgOiAnYmFja3dhcmQnLFxuICAgIC8vICAgICAgICAgICAgIHNjcm9sbE9mZnNldDogc2Nyb2xsT2Zmc2V0LFxuICAgIC8vICAgICAgICAgICAgIHNjcm9sbFVwZGF0ZVdhc1JlcXVlc3RlZDogdHJ1ZSxcbiAgICAvLyAgICAgICAgIH07XG4gICAgLy8gICAgIH0sIHJlc2V0SXNTY3JvbGxpbmdEZWJvdW5jZWQpO1xuICAgIC8vIH07XG4gICAgLy8gY29uc3Qgc2Nyb2xsVG9JdGVtID0gKGluZGV4OiBudW1iZXIsIGFsaWduOiBTY3JvbGxUb0FsaWduID0gJ2F1dG8nKSA9PiB7XG4gICAgLy8gICAgIGNvbnN0IHsgaXRlbUNvdW50IH0gPSBwcm9wcztcbiAgICAvLyAgICAgaW5kZXggPSBNYXRoLm1heCgwLCBNYXRoLm1pbihpbmRleCwgaXRlbUNvdW50IC0gMSkpO1xuICAgIC8vICAgICBzY3JvbGxUbyhnZXRPZmZzZXRGb3JJbmRleEFuZEFsaWdubWVudChwcm9wcywgaW5kZXgsIGFsaWduLCBzY3JvbGxPZmZzZXQsIGNvbnRleHQuY3VycmVudCkpO1xuICAgIC8vIH07XG4gICAgY29uc3Qgb3V0ZXJSZWZTZXR0ZXIgPSAocmVmKSA9PiB7XG4gICAgICAgIGNvbnN0IHsgb3V0ZXJSZWYgfSA9IHByb3BzO1xuICAgICAgICBvdXRlckxpc3RSZWYuY3VycmVudCA9IHJlZjtcbiAgICAgICAgaWYgKHR5cGVvZiBvdXRlclJlZiA9PT0gJ2Z1bmN0aW9uJykge1xuICAgICAgICAgICAgLy8gQHRzLWV4cGVjdC1lcnJvciAxMjNcbiAgICAgICAgICAgIG91dGVyUmVmKHJlZik7XG4gICAgICAgIH1cbiAgICAgICAgZWxzZSBpZiAob3V0ZXJSZWYgIT0gbnVsbCAmJlxuICAgICAgICAgICAgdHlwZW9mIG91dGVyUmVmID09PSAnb2JqZWN0JyAmJlxuICAgICAgICAgICAgT2JqZWN0LnByb3RvdHlwZS5oYXNPd25Qcm9wZXJ0eS5jYWxsKG91dGVyUmVmLCAnY3VycmVudCcpKSB7XG4gICAgICAgICAgICAvLyBAdHMtZXhwZWN0LWVycm9yIDEyM1xuICAgICAgICAgICAgb3V0ZXJSZWYuY3VycmVudCA9IHJlZjtcbiAgICAgICAgfVxuICAgIH07XG4gICAgLy8gY29uc3QgZ2V0T2Zmc2V0Rm9ySW5kZXhBbmRBbGlnbm1lbnQgPSAoXG4gICAgLy8gICAgIHByb3BzOiBQcm9wczxUPixcbiAgICAvLyAgICAgaW5kZXg6IG51bWJlcixcbiAgICAvLyAgICAgYWxpZ246IFNjcm9sbFRvQWxpZ24sXG4gICAgLy8gICAgIHNjcm9sbE9mZnNldDogbnVtYmVyLFxuICAgIC8vICAgICBjb250ZXh0OiBDb250ZXh0LFxuICAgIC8vICkgPT4ge1xuICAgIC8vICAgICBjb25zdCB7IGhlaWdodCB9ID0gcHJvcHM7XG4gICAgLy8gICAgIGNvbnN0IHNpemUgPSBoZWlnaHQ7XG4gICAgLy8gICAgIGNvbnN0IGl0ZW1NZXRhZGF0YSA9IGdldEl0ZW1NZXRhZGF0YShwcm9wcywgaW5kZXgsIGNvbnRleHQpO1xuICAgIC8vICAgICAvLyBHZXQgZXN0aW1hdGVkIHRvdGFsIHNpemUgYWZ0ZXIgSXRlbU1ldGFkYXRhIGlzIGNvbXB1dGVkLFxuICAgIC8vICAgICAvLyBUbyBlbnN1cmUgaXQgcmVmbGVjdHMgYWN0dWFsIG1lYXN1cmVtZW50cyBpbnN0ZWFkIG9mIGp1c3QgZXN0aW1hdGVzLlxuICAgIC8vICAgICBjb25zdCBlc3RpbWF0ZWRUb3RhbFNpemUgPSBnZXRFc3RpbWF0ZWRUb3RhbFNpemUocHJvcHMsIGNvbnRleHQpO1xuICAgIC8vICAgICBjb25zdCBtYXhPZmZzZXQgPSBNYXRoLm1heCgwLCBNYXRoLm1pbihlc3RpbWF0ZWRUb3RhbFNpemUgLSBzaXplLCBpdGVtTWV0YWRhdGEub2Zmc2V0KSk7XG4gICAgLy8gICAgIGNvbnN0IG1pbk9mZnNldCA9IE1hdGgubWF4KDAsIGl0ZW1NZXRhZGF0YS5vZmZzZXQgLSBzaXplICsgaXRlbU1ldGFkYXRhLnNpemUpO1xuICAgIC8vICAgICBpZiAoYWxpZ24gPT09ICdzbWFydCcpIHtcbiAgICAvLyAgICAgICAgIGlmIChzY3JvbGxPZmZzZXQgPj0gbWluT2Zmc2V0IC0gc2l6ZSAmJiBzY3JvbGxPZmZzZXQgPD0gbWF4T2Zmc2V0ICsgc2l6ZSkge1xuICAgIC8vICAgICAgICAgICAgIGFsaWduID0gJ2F1dG8nO1xuICAgIC8vICAgICAgICAgfSBlbHNlIHtcbiAgICAvLyAgICAgICAgICAgICBhbGlnbiA9ICdjZW50ZXInO1xuICAgIC8vICAgICAgICAgfVxuICAgIC8vICAgICB9XG4gICAgLy8gICAgIHN3aXRjaCAoYWxpZ24pIHtcbiAgICAvLyAgICAgICAgIGNhc2UgJ3N0YXJ0JzpcbiAgICAvLyAgICAgICAgICAgICByZXR1cm4gbWF4T2Zmc2V0O1xuICAgIC8vICAgICAgICAgY2FzZSAnZW5kJzpcbiAgICAvLyAgICAgICAgICAgICByZXR1cm4gbWluT2Zmc2V0O1xuICAgIC8vICAgICAgICAgY2FzZSAnY2VudGVyJzpcbiAgICAvLyAgICAgICAgICAgICByZXR1cm4gTWF0aC5yb3VuZChtaW5PZmZzZXQgKyAobWF4T2Zmc2V0IC0gbWluT2Zmc2V0KSAvIDIpO1xuICAgIC8vICAgICAgICAgY2FzZSAnYXV0byc6XG4gICAgLy8gICAgICAgICBkZWZhdWx0OlxuICAgIC8vICAgICAgICAgICAgIGlmIChzY3JvbGxPZmZzZXQgPj0gbWluT2Zmc2V0ICYmIHNjcm9sbE9mZnNldCA8PSBtYXhPZmZzZXQpIHtcbiAgICAvLyAgICAgICAgICAgICAgICAgcmV0dXJuIHNjcm9sbE9mZnNldDtcbiAgICAvLyAgICAgICAgICAgICB9IGVsc2UgaWYgKHNjcm9sbE9mZnNldCA8IG1pbk9mZnNldCkge1xuICAgIC8vICAgICAgICAgICAgICAgICByZXR1cm4gbWluT2Zmc2V0O1xuICAgIC8vICAgICAgICAgICAgIH0gZWxzZSB7XG4gICAgLy8gICAgICAgICAgICAgICAgIHJldHVybiBtYXhPZmZzZXQ7XG4gICAgLy8gICAgICAgICAgICAgfVxuICAgIC8vICAgICB9XG4gICAgLy8gfTtcbiAgICBjb25zdCBnZXRTdG9wSW5kZXhGb3JTdGFydEluZGV4ID0gKHByb3BzLCBzdGFydEluZGV4LCBzY3JvbGxPZmZzZXQsIGNvbnRleHQpID0+IHtcbiAgICAgICAgY29uc3QgeyBoZWlnaHQsIGl0ZW1Db3VudCB9ID0gcHJvcHM7XG4gICAgICAgIGNvbnN0IHNpemUgPSBoZWlnaHQ7XG4gICAgICAgIGNvbnN0IGl0ZW1NZXRhZGF0YSA9IGdldEl0ZW1NZXRhZGF0YShwcm9wcywgc3RhcnRJbmRleCwgY29udGV4dCk7XG4gICAgICAgIGNvbnN0IG1heE9mZnNldCA9IHNjcm9sbE9mZnNldCArIHNpemU7XG4gICAgICAgIGxldCBvZmZzZXQgPSBpdGVtTWV0YWRhdGEub2Zmc2V0ICsgaXRlbU1ldGFkYXRhLnNpemU7XG4gICAgICAgIGxldCBzdG9wSW5kZXggPSBzdGFydEluZGV4O1xuICAgICAgICB3aGlsZSAoc3RvcEluZGV4IDwgaXRlbUNvdW50IC0gMSAmJiBvZmZzZXQgPCBtYXhPZmZzZXQpIHtcbiAgICAgICAgICAgIHN0b3BJbmRleCsrO1xuICAgICAgICAgICAgb2Zmc2V0ICs9IGdldEl0ZW1NZXRhZGF0YShwcm9wcywgc3RvcEluZGV4LCBjb250ZXh0KS5zaXplO1xuICAgICAgICB9XG4gICAgICAgIHJldHVybiBzdG9wSW5kZXg7XG4gICAgfTtcbiAgICBjb25zdCBnZXRSYW5nZVRvUmVuZGVyID0gKCkgPT4ge1xuICAgICAgICBjb25zdCB7IGl0ZW1Db3VudCB9ID0gcHJvcHM7XG4gICAgICAgIGlmIChpdGVtQ291bnQgPT09IDApIHtcbiAgICAgICAgICAgIHJldHVybiBbMCwgMCwgMCwgMF07XG4gICAgICAgIH1cbiAgICAgICAgY29uc3Qgc3RhcnRJbmRleCA9IGZpbmROZWFyZXN0SXRlbShwcm9wcywgY29udGV4dC5jdXJyZW50LCBzY3JvbGxPZmZzZXQpO1xuICAgICAgICBjb25zdCBzdG9wSW5kZXggPSBnZXRTdG9wSW5kZXhGb3JTdGFydEluZGV4KHByb3BzLCBzdGFydEluZGV4LCBzY3JvbGxPZmZzZXQsIGNvbnRleHQuY3VycmVudCk7XG4gICAgICAgIC8vIE92ZXJzY2FuIGJ5IG9uZSBpdGVtIGluIGVhY2ggZGlyZWN0aW9uIHNvIHRoYXQgdGFiL2ZvY3VzIHdvcmtzLlxuICAgICAgICAvLyBJZiB0aGVyZSBpc24ndCBhdCBsZWFzdCBvbmUgZXh0cmEgaXRlbSwgdGFiIGxvb3BzIGJhY2sgYXJvdW5kLlxuICAgICAgICBjb25zdCBvdmVyc2NhbkJhY2t3YXJkID0gIWlzU2Nyb2xsaW5nIHx8IHNjcm9sbERpcmVjdGlvbiA9PT0gJ2JhY2t3YXJkJyA/IE1hdGgubWF4KDEsIG92ZXJzY2FuQ291bnQpIDogMTtcbiAgICAgICAgY29uc3Qgb3ZlcnNjYW5Gb3J3YXJkID0gIWlzU2Nyb2xsaW5nIHx8IHNjcm9sbERpcmVjdGlvbiA9PT0gJ2ZvcndhcmQnID8gTWF0aC5tYXgoMSwgb3ZlcnNjYW5Db3VudCkgOiAxO1xuICAgICAgICByZXR1cm4gW1xuICAgICAgICAgICAgTWF0aC5tYXgoMCwgc3RhcnRJbmRleCAtIG92ZXJzY2FuQmFja3dhcmQpLFxuICAgICAgICAgICAgTWF0aC5tYXgoMCwgTWF0aC5taW4oaXRlbUNvdW50IC0gMSwgc3RvcEluZGV4ICsgb3ZlcnNjYW5Gb3J3YXJkKSksXG4gICAgICAgICAgICBzdGFydEluZGV4LFxuICAgICAgICAgICAgc3RvcEluZGV4LFxuICAgICAgICBdO1xuICAgIH07XG4gICAgY29uc3QgeyBvbkl0ZW1zUmVuZGVyZWQsIG9uU2Nyb2xsOiBvblNjcm9sbENhbGxhYmxlIH0gPSBwcm9wcztcbiAgICBjb25zdCBjYWxsT25JdGVtc1JlbmRlcmVkID0gbWVtb2l6ZU9uZShmdW5jdGlvbiAob3ZlcnNjYW5TdGFydEluZGV4LCBvdmVyc2NhblN0b3BJbmRleCwgdmlzaWJsZVN0YXJ0SW5kZXgsIHZpc2libGVTdG9wSW5kZXgpIHtcbiAgICAgICAgaWYgKG9uSXRlbXNSZW5kZXJlZCkge1xuICAgICAgICAgICAgb25JdGVtc1JlbmRlcmVkKG92ZXJzY2FuU3RhcnRJbmRleCwgb3ZlcnNjYW5TdG9wSW5kZXgsIHZpc2libGVTdGFydEluZGV4LCB2aXNpYmxlU3RvcEluZGV4KTtcbiAgICAgICAgICAgIHJldHVybjtcbiAgICAgICAgfVxuICAgIH0pO1xuICAgIGNvbnN0IGNhbGxPblNjcm9sbCA9IG1lbW9pemVPbmUoZnVuY3Rpb24gKHNjcm9sbERpcmVjdGlvbiwgc2Nyb2xsT2Zmc2V0LCBzY3JvbGxVcGRhdGVXYXNSZXF1ZXN0ZWQpIHtcbiAgICAgICAgaWYgKG9uU2Nyb2xsQ2FsbGFibGUpIHtcbiAgICAgICAgICAgIG9uU2Nyb2xsQ2FsbGFibGUoc2Nyb2xsRGlyZWN0aW9uLCBzY3JvbGxPZmZzZXQsIHNjcm9sbFVwZGF0ZVdhc1JlcXVlc3RlZCk7XG4gICAgICAgICAgICByZXR1cm47XG4gICAgICAgIH1cbiAgICB9KTtcbiAgICBjb25zdCBjYWxsUHJvcHNDYWxsYmFja3MgPSAoKSA9PiB7XG4gICAgICAgIGNvbnN0IHsgb25JdGVtc1JlbmRlcmVkLCBvblNjcm9sbCwgaXRlbUNvdW50IH0gPSBwcm9wcztcbiAgICAgICAgaWYgKHR5cGVvZiBvbkl0ZW1zUmVuZGVyZWQgPT09ICdmdW5jdGlvbicpIHtcbiAgICAgICAgICAgIGlmIChpdGVtQ291bnQgPiAwKSB7XG4gICAgICAgICAgICAgICAgY29uc3QgW292ZXJzY2FuU3RhcnRJbmRleCwgb3ZlcnNjYW5TdG9wSW5kZXgsIHZpc2libGVTdGFydEluZGV4LCB2aXNpYmxlU3RvcEluZGV4XSA9IGdldFJhbmdlVG9SZW5kZXIoKTtcbiAgICAgICAgICAgICAgICBjYWxsT25JdGVtc1JlbmRlcmVkKG92ZXJzY2FuU3RhcnRJbmRleCwgb3ZlcnNjYW5TdG9wSW5kZXgsIHZpc2libGVTdGFydEluZGV4LCB2aXNpYmxlU3RvcEluZGV4KTtcbiAgICAgICAgICAgIH1cbiAgICAgICAgfVxuICAgICAgICBpZiAodHlwZW9mIG9uU2Nyb2xsID09PSAnZnVuY3Rpb24nKSB7XG4gICAgICAgICAgICBjYWxsT25TY3JvbGwoc2Nyb2xsRGlyZWN0aW9uLCBzY3JvbGxPZmZzZXQsIHNjcm9sbFVwZGF0ZVdhc1JlcXVlc3RlZCk7XG4gICAgICAgIH1cbiAgICB9O1xuICAgIGNvbnN0IGdldEl0ZW1TdHlsZSA9IChpbmRleCkgPT4ge1xuICAgICAgICBjb25zdCBpdGVtU3R5bGVDYWNoZSA9IGdldEl0ZW1TdHlsZUNhY2hlKC0xKTtcbiAgICAgICAgbGV0IHN0eWxlO1xuICAgICAgICBjb25zdCBpdGVtU3R5bGVDYWNoZVZhbHVlID0gaXRlbVN0eWxlQ2FjaGVbaW5kZXhdO1xuICAgICAgICBpZiAoaXRlbVN0eWxlQ2FjaGVWYWx1ZSkge1xuICAgICAgICAgICAgc3R5bGUgPSBpdGVtU3R5bGVDYWNoZVZhbHVlO1xuICAgICAgICB9XG4gICAgICAgIGVsc2Uge1xuICAgICAgICAgICAgY29uc3Qgb2Zmc2V0ID0gZ2V0SXRlbU1ldGFkYXRhKHByb3BzLCBpbmRleCwgY29udGV4dC5jdXJyZW50KS5vZmZzZXQ7XG4gICAgICAgICAgICBjb25zdCBzaXplID0gY29udGV4dC5jdXJyZW50Lml0ZW1NZXRhZGF0YU1hcFtpbmRleF0/LnNpemUgPz8gMDtcbiAgICAgICAgICAgIGl0ZW1TdHlsZUNhY2hlW2luZGV4XSA9IHN0eWxlID0ge1xuICAgICAgICAgICAgICAgIHBvc2l0aW9uOiAnYWJzb2x1dGUnLFxuICAgICAgICAgICAgICAgIGxlZnQ6IDAsXG4gICAgICAgICAgICAgICAgdG9wOiBvZmZzZXQsXG4gICAgICAgICAgICAgICAgaGVpZ2h0OiBzaXplLFxuICAgICAgICAgICAgICAgIHdpZHRoOiAnMTAwJScsXG4gICAgICAgICAgICB9O1xuICAgICAgICB9XG4gICAgICAgIHJldHVybiBzdHlsZTtcbiAgICB9O1xuICAgIGNvbnN0IG9uU2Nyb2xsVmVydGljYWwgPSAoZXZlbnQpID0+IHtcbiAgICAgICAgY29uc3QgeyBjbGllbnRIZWlnaHQsIHNjcm9sbEhlaWdodCwgc2Nyb2xsVG9wIH0gPSBldmVudC5jdXJyZW50VGFyZ2V0O1xuICAgICAgICBzZXRTdGF0ZSgocHJldlN0YXRlKSA9PiB7XG4gICAgICAgICAgICBpZiAocHJldlN0YXRlLnNjcm9sbE9mZnNldCA9PT0gc2Nyb2xsVG9wKSB7XG4gICAgICAgICAgICAgICAgLy8gU2Nyb2xsIHBvc2l0aW9uIG1heSBoYXZlIGJlZW4gdXBkYXRlZCBieSBjRE0vY0RVLFxuICAgICAgICAgICAgICAgIC8vIEluIHdoaWNoIGNhc2Ugd2UgZG9uJ3QgbmVlZCB0byB0cmlnZ2VyIGFub3RoZXIgcmVuZGVyLFxuICAgICAgICAgICAgICAgIC8vIEFuZCB3ZSBkb24ndCB3YW50IHRvIHVwZGF0ZSBzdGF0ZS5pc1Njcm9sbGluZy5cbiAgICAgICAgICAgICAgICByZXR1cm4geyAuLi5wcmV2U3RhdGUgfTtcbiAgICAgICAgICAgIH1cbiAgICAgICAgICAgIC8vIFByZXZlbnQgU2FmYXJpJ3MgZWxhc3RpYyBzY3JvbGxpbmcgZnJvbSBjYXVzaW5nIHZpc3VhbCBzaGFraW5nIHdoZW4gc2Nyb2xsaW5nIHBhc3QgYm91bmRzLlxuICAgICAgICAgICAgY29uc3Qgc2Nyb2xsT2Zmc2V0ID0gTWF0aC5tYXgoMCwgTWF0aC5taW4oc2Nyb2xsVG9wLCBzY3JvbGxIZWlnaHQgLSBjbGllbnRIZWlnaHQpKTtcbiAgICAgICAgICAgIHJldHVybiB7XG4gICAgICAgICAgICAgICAgLi4ucHJldlN0YXRlLFxuICAgICAgICAgICAgICAgIGlzU2Nyb2xsaW5nOiB0cnVlLFxuICAgICAgICAgICAgICAgIHNjcm9sbERpcmVjdGlvbjogcHJldlN0YXRlLnNjcm9sbE9mZnNldCA8IHNjcm9sbE9mZnNldCA/ICdmb3J3YXJkJyA6ICdiYWNrd2FyZCcsXG4gICAgICAgICAgICAgICAgc2Nyb2xsT2Zmc2V0LFxuICAgICAgICAgICAgICAgIHNjcm9sbFVwZGF0ZVdhc1JlcXVlc3RlZDogZmFsc2UsXG4gICAgICAgICAgICB9O1xuICAgICAgICB9LCByZXNldElzU2Nyb2xsaW5nRGVib3VuY2VkKTtcbiAgICB9O1xuICAgIGNvbnN0IHJlc2V0SXNTY3JvbGxpbmdEZWJvdW5jZWQgPSAoKSA9PiB7XG4gICAgICAgIGlmIChyZXNldElzU2Nyb2xsaW5nVGltZW91dElkLmN1cnJlbnQgIT09IG51bGwpIHtcbiAgICAgICAgICAgIGNhbmNlbFRpbWVvdXQocmVzZXRJc1Njcm9sbGluZ1RpbWVvdXRJZC5jdXJyZW50KTtcbiAgICAgICAgfVxuICAgICAgICByZXNldElzU2Nyb2xsaW5nVGltZW91dElkLmN1cnJlbnQgPSByZXF1ZXN0VGltZW91dChyZXNldElzU2Nyb2xsaW5nLCBJU19TQ1JPTExJTkdfREVCT1VOQ0VfSU5URVJWQUwpO1xuICAgIH07XG4gICAgY29uc3QgcmVzZXRJc1Njcm9sbGluZyA9ICgpID0+IHtcbiAgICAgICAgcmVzZXRJc1Njcm9sbGluZ1RpbWVvdXRJZC5jdXJyZW50ID0gbnVsbDtcbiAgICAgICAgc2V0U3RhdGUoKHByZXZTdGF0ZSkgPT4ge1xuICAgICAgICAgICAgcmV0dXJuIHtcbiAgICAgICAgICAgICAgICAuLi5wcmV2U3RhdGUsXG4gICAgICAgICAgICAgICAgaXNTY3JvbGxpbmc6IGZhbHNlLFxuICAgICAgICAgICAgfTtcbiAgICAgICAgfSwgKCkgPT4ge1xuICAgICAgICAgICAgZ2V0SXRlbVN0eWxlQ2FjaGUoLTEpO1xuICAgICAgICB9KTtcbiAgICB9O1xuICAgIHVzZUltcGVyYXRpdmVIYW5kbGUobGlzdFJlZiwgKCkgPT4gKHtcbiAgICAgICAgcmVzZXRBZnRlckluZGV4KGluZGV4LCBzaG91bGRGb3JjZVVwZGF0ZSA9IHRydWUpIHtcbiAgICAgICAgICAgIGNvbnRleHQuY3VycmVudC5sYXN0TWVhc3VyZWRJbmRleCA9IE1hdGgubWluKGNvbnRleHQuY3VycmVudC5sYXN0TWVhc3VyZWRJbmRleCwgaW5kZXggLSAxKTtcbiAgICAgICAgICAgIC8vIFdlIGNvdWxkIHBvdGVudGlhbGx5IG9wdGltaXplIGZ1cnRoZXIgYnkgb25seSBldmljdGluZyBzdHlsZXMgYWZ0ZXIgdGhpcyBpbmRleCxcbiAgICAgICAgICAgIC8vIEJ1dCBzaW5jZSBzdHlsZXMgYXJlIG9ubHkgY2FjaGVkIHdoaWxlIHNjcm9sbGluZyBpcyBpbiBwcm9ncmVzcy1cbiAgICAgICAgICAgIC8vIEl0IHNlZW1zIGFuIHVubmVjZXNzYXJ5IG9wdGltaXphdGlvbi5cbiAgICAgICAgICAgIC8vIEl0J3MgdW5saWtlbHkgdGhhdCByZXNldEFmdGVySW5kZXgoKSB3aWxsIGJlIGNhbGxlZCB3aGlsZSBhIHVzZXIgaXMgc2Nyb2xsaW5nLlxuICAgICAgICAgICAgZ2V0SXRlbVN0eWxlQ2FjaGUoLTEpO1xuICAgICAgICAgICAgaWYgKHNob3VsZEZvcmNlVXBkYXRlKSB7XG4gICAgICAgICAgICAgICAgZm9yY2VVcGRhdGUoKTtcbiAgICAgICAgICAgIH1cbiAgICAgICAgfSxcbiAgICAgICAgZ2V0SXRlbVN0eWxlQ2FjaGUsXG4gICAgICAgIGZvcmNlVXBkYXRlLFxuICAgIH0pKTtcbiAgICB1c2VFZmZlY3QoKCkgPT4ge1xuICAgICAgICBpZiAob3V0ZXJMaXN0UmVmLmN1cnJlbnQgIT0gbnVsbCkge1xuICAgICAgICAgICAgb3V0ZXJMaXN0UmVmLmN1cnJlbnQuc2Nyb2xsVG9wID0gaW5pdGlhbFNjcm9sbE9mZnNldDtcbiAgICAgICAgfVxuICAgICAgICBjYWxsUHJvcHNDYWxsYmFja3MoKTtcbiAgICB9LCBbXSk7XG4gICAgdXNlRWZmZWN0KCgpID0+IHtcbiAgICAgICAgaWYgKHNjcm9sbFVwZGF0ZVdhc1JlcXVlc3RlZCAmJiBvdXRlckxpc3RSZWYuY3VycmVudCAhPSBudWxsKSB7XG4gICAgICAgICAgICBvdXRlckxpc3RSZWYuY3VycmVudC5zY3JvbGxUb3AgPSBzY3JvbGxPZmZzZXQ7XG4gICAgICAgIH1cbiAgICAgICAgY2FsbFByb3BzQ2FsbGJhY2tzKCk7XG4gICAgICAgIHJldHVybiAoKSA9PiB7XG4gICAgICAgICAgICBpZiAocmVzZXRJc1Njcm9sbGluZ1RpbWVvdXRJZC5jdXJyZW50ICE9PSBudWxsKSB7XG4gICAgICAgICAgICAgICAgY2FuY2VsVGltZW91dChyZXNldElzU2Nyb2xsaW5nVGltZW91dElkLmN1cnJlbnQpO1xuICAgICAgICAgICAgfVxuICAgICAgICB9O1xuICAgIH0pO1xuICAgIGNvbnN0IG9uU2Nyb2xsID0gb25TY3JvbGxWZXJ0aWNhbDtcbiAgICBjb25zdCBbc3RhcnRJbmRleCwgc3RvcEluZGV4XSA9IGdldFJhbmdlVG9SZW5kZXIoKTtcbiAgICBjb25zdCBpdGVtcyA9IFtdO1xuICAgIGlmIChpdGVtQ291bnQgPiAwKSB7XG4gICAgICAgIGZvciAobGV0IGluZGV4ID0gc3RhcnRJbmRleDsgaW5kZXggPD0gc3RvcEluZGV4OyBpbmRleCsrKSB7XG4gICAgICAgICAgICBpdGVtcy5wdXNoKGNyZWF0ZUVsZW1lbnQoY2hpbGRyZW4sIHtcbiAgICAgICAgICAgICAgICBkYXRhOiBpdGVtRGF0YSxcbiAgICAgICAgICAgICAgICBrZXk6IGl0ZW1LZXkoaW5kZXgsIGl0ZW1EYXRhKSxcbiAgICAgICAgICAgICAgICBpbmRleCxcbiAgICAgICAgICAgICAgICBpc1Njcm9sbGluZyxcbiAgICAgICAgICAgICAgICBzdHlsZTogZ2V0SXRlbVN0eWxlKGluZGV4KSxcbiAgICAgICAgICAgIH0pKTtcbiAgICAgICAgfVxuICAgIH1cbiAgICAvLyBSZWFkIHRoaXMgdmFsdWUgQUZURVIgaXRlbXMgaGF2ZSBiZWVuIGNyZWF0ZWQsXG4gICAgLy8gU28gdGhlaXIgYWN0dWFsIHNpemVzIChpZiB2YXJpYWJsZSkgYXJlIHRha2VuIGludG8gY29uc2lkZXJhdGlvbi5cbiAgICBjb25zdCBlc3RpbWF0ZWRUb3RhbFNpemUgPSBnZXRFc3RpbWF0ZWRUb3RhbFNpemUocHJvcHMsIGNvbnRleHQuY3VycmVudCk7XG4gICAgcmV0dXJuIGNyZWF0ZUVsZW1lbnQob3V0ZXJFbGVtZW50VHlwZSB8fCAnZGl2Jywge1xuICAgICAgICBjbGFzc05hbWUsXG4gICAgICAgIG9uU2Nyb2xsLFxuICAgICAgICByZWY6IG91dGVyUmVmU2V0dGVyLFxuICAgICAgICBzdHlsZToge1xuICAgICAgICAgICAgcG9zaXRpb246ICdyZWxhdGl2ZScsXG4gICAgICAgICAgICBoZWlnaHQsXG4gICAgICAgICAgICB3aWR0aCxcbiAgICAgICAgICAgIFdlYmtpdE92ZXJmbG93U2Nyb2xsaW5nOiAndG91Y2gnLFxuICAgICAgICAgICAgd2lsbENoYW5nZTogJ3RyYW5zZm9ybScsXG4gICAgICAgICAgICAuLi5zdHlsZSxcbiAgICAgICAgfSxcbiAgICB9LCBjcmVhdGVFbGVtZW50KGlubmVyRWxlbWVudFR5cGUgfHwgJ2RpdicsIHtcbiAgICAgICAgY2xhc3NOYW1lOiBpbm5lckNsYXNzTmFtZSxcbiAgICAgICAgcmVmOiBpbm5lclJlZixcbiAgICAgICAgc3R5bGU6IHtcbiAgICAgICAgICAgIGhlaWdodDogZXN0aW1hdGVkVG90YWxTaXplLFxuICAgICAgICAgICAgcG9pbnRlckV2ZW50czogaXNTY3JvbGxpbmcgPyAnbm9uZScgOiB1bmRlZmluZWQsXG4gICAgICAgICAgICB3aWR0aDogJzEwMCUnLFxuICAgICAgICAgICAgLi4uaW5uZXJTdHlsZSxcbiAgICAgICAgfSxcbiAgICB9LCBpdGVtcykpO1xufVxuIiwiaW1wb3J0IHsgdXNlUmVmIH0gZnJvbSAncmVhY3QnO1xuZnVuY3Rpb24gc2NhbkZvclVubG9hZGVkUmFuZ2VzKGlzSXRlbUxvYWRlZCwgaXRlbUNvdW50LCBtaW5pbXVtQmF0Y2hTaXplLCBzdGFydEluZGV4LCBzdG9wSW5kZXgpIHtcbiAgICBjb25zdCB1bmxvYWRlZFJhbmdlcyA9IFtdO1xuICAgIGxldCByYW5nZVN0YXJ0SW5kZXggPSBudWxsO1xuICAgIGxldCByYW5nZVN0b3BJbmRleCA9IG51bGw7XG4gICAgZm9yIChsZXQgaW5kZXggPSBzdGFydEluZGV4OyBpbmRleCA8PSBzdG9wSW5kZXg7IGluZGV4KyspIHtcbiAgICAgICAgY29uc3QgbG9hZGVkID0gaXNJdGVtTG9hZGVkKGluZGV4KTtcbiAgICAgICAgaWYgKCFsb2FkZWQpIHtcbiAgICAgICAgICAgIHJhbmdlU3RvcEluZGV4ID0gaW5kZXg7XG4gICAgICAgICAgICBpZiAocmFuZ2VTdGFydEluZGV4ID09PSBudWxsKSB7XG4gICAgICAgICAgICAgICAgcmFuZ2VTdGFydEluZGV4ID0gaW5kZXg7XG4gICAgICAgICAgICB9XG4gICAgICAgIH1cbiAgICAgICAgZWxzZSBpZiAocmFuZ2VTdGFydEluZGV4ICE9PSBudWxsICYmIHJhbmdlU3RvcEluZGV4ICE9PSBudWxsKSB7XG4gICAgICAgICAgICB1bmxvYWRlZFJhbmdlcy5wdXNoKFtyYW5nZVN0YXJ0SW5kZXgsIHJhbmdlU3RvcEluZGV4XSk7XG4gICAgICAgICAgICByYW5nZVN0YXJ0SW5kZXggPSByYW5nZVN0b3BJbmRleCA9IG51bGw7XG4gICAgICAgIH1cbiAgICB9XG4gICAgLy8gSWYgOnJhbmdlU3RvcEluZGV4IGlzIG5vdCBudWxsIGl0IG1lYW5zIHdlIGhhdmVuJ3QgcmFuIG91dCBvZiB1bmxvYWRlZCByb3dzLlxuICAgIC8vIFNjYW4gZm9yd2FyZCB0byB0cnkgZmlsbGluZyBvdXIgOm1pbmltdW1CYXRjaFNpemUuXG4gICAgaWYgKHJhbmdlU3RhcnRJbmRleCAhPT0gbnVsbCAmJiByYW5nZVN0b3BJbmRleCAhPT0gbnVsbCkge1xuICAgICAgICBjb25zdCBwb3RlbnRpYWxTdG9wSW5kZXggPSBNYXRoLm1pbihNYXRoLm1heChyYW5nZVN0b3BJbmRleCwgcmFuZ2VTdGFydEluZGV4ICsgbWluaW11bUJhdGNoU2l6ZSAtIDEpLCBpdGVtQ291bnQgLSAxKTtcbiAgICAgICAgZm9yIChsZXQgaW5kZXggPSByYW5nZVN0b3BJbmRleCArIDE7IGluZGV4IDw9IHBvdGVudGlhbFN0b3BJbmRleDsgaW5kZXgrKykge1xuICAgICAgICAgICAgaWYgKCFpc0l0ZW1Mb2FkZWQoaW5kZXgpKSB7XG4gICAgICAgICAgICAgICAgcmFuZ2VTdG9wSW5kZXggPSBpbmRleDtcbiAgICAgICAgICAgIH1cbiAgICAgICAgICAgIGVsc2Uge1xuICAgICAgICAgICAgICAgIGJyZWFrO1xuICAgICAgICAgICAgfVxuICAgICAgICB9XG4gICAgICAgIHVubG9hZGVkUmFuZ2VzLnB1c2goW3JhbmdlU3RhcnRJbmRleCwgcmFuZ2VTdG9wSW5kZXhdKTtcbiAgICB9XG4gICAgLy8gQ2hlY2sgdG8gc2VlIGlmIG91ciBmaXJzdCByYW5nZSBlbmRlZCBwcmVtYXR1cmVseS5cbiAgICAvLyBJbiB0aGlzIGNhc2Ugd2Ugc2hvdWxkIHNjYW4gYmFja3dhcmRzIHRvIHRyeSBmaWxsaW5nIG91ciA6bWluaW11bUJhdGNoU2l6ZS5cbiAgICBpZiAodW5sb2FkZWRSYW5nZXMubGVuZ3RoKSB7XG4gICAgICAgIGNvbnN0IGZpcnN0UmFuZ2UgPSB1bmxvYWRlZFJhbmdlc1swXTtcbiAgICAgICAgd2hpbGUgKGZpcnN0UmFuZ2UgJiYgZmlyc3RSYW5nZVsxXSAtIGZpcnN0UmFuZ2VbMF0gKyAxIDwgbWluaW11bUJhdGNoU2l6ZSAmJiBmaXJzdFJhbmdlWzBdID4gMCkge1xuICAgICAgICAgICAgY29uc3QgaW5kZXggPSBmaXJzdFJhbmdlWzBdIC0gMTtcbiAgICAgICAgICAgIGlmICghaXNJdGVtTG9hZGVkKGluZGV4KSkge1xuICAgICAgICAgICAgICAgIGZpcnN0UmFuZ2VbMF0gPSBpbmRleDtcbiAgICAgICAgICAgIH1cbiAgICAgICAgICAgIGVsc2Uge1xuICAgICAgICAgICAgICAgIGJyZWFrO1xuICAgICAgICAgICAgfVxuICAgICAgICB9XG4gICAgfVxuICAgIHJldHVybiB1bmxvYWRlZFJhbmdlcztcbn1cbmZ1bmN0aW9uIGlzUmFuZ2VWaXNpYmxlKGxhc3RSZW5kZXJlZFN0YXJ0SW5kZXgsIGxhc3RSZW5kZXJlZFN0b3BJbmRleCwgc3RhcnRJbmRleCwgc3RvcEluZGV4KSB7XG4gICAgcmV0dXJuICEoc3RhcnRJbmRleCA+IGxhc3RSZW5kZXJlZFN0b3BJbmRleCB8fCBzdG9wSW5kZXggPCBsYXN0UmVuZGVyZWRTdGFydEluZGV4KTtcbn1cbmNvbnN0IExvYWRlciA9IGZ1bmN0aW9uIChwcm9wcykge1xuICAgIGNvbnN0IGNvbnRleHQgPSB1c2VSZWYoe1xuICAgICAgICBsYXN0UmVuZGVyZWRTdGFydEluZGV4OiAtMSxcbiAgICAgICAgbGFzdFJlbmRlcmVkU3RvcEluZGV4OiAtMSxcbiAgICAgICAgbWVtb2l6ZWRVbmxvYWRlZFJhbmdlczogW10sXG4gICAgfSk7XG4gICAgY29uc3QgcmVmID0gdXNlUmVmKG51bGwpO1xuICAgIGZ1bmN0aW9uIG9uSXRlbXNSZW5kZXJlZCh2aXNpYmxlU3RhcnRJbmRleCwgdmlzaWJsZVN0b3BJbmRleCkge1xuICAgICAgICBjb250ZXh0LmN1cnJlbnQubGFzdFJlbmRlcmVkU3RhcnRJbmRleCA9IHZpc2libGVTdGFydEluZGV4O1xuICAgICAgICBjb250ZXh0LmN1cnJlbnQubGFzdFJlbmRlcmVkU3RvcEluZGV4ID0gdmlzaWJsZVN0b3BJbmRleDtcbiAgICAgICAgZW5zdXJlUm93c0xvYWRlZCh2aXNpYmxlU3RhcnRJbmRleCwgdmlzaWJsZVN0b3BJbmRleCk7XG4gICAgfVxuICAgIC8vIGZ1bmN0aW9uIHJlc2V0bG9hZE1vcmVJdGVtc0NhY2hlKGF1dG9SZWxvYWQgPSBmYWxzZSkge1xuICAgIC8vICAgICBjb250ZXh0LmN1cnJlbnQubWVtb2l6ZWRVbmxvYWRlZFJhbmdlcyA9IFtdO1xuICAgIC8vXG4gICAgLy8gICAgIGlmIChhdXRvUmVsb2FkKSB7XG4gICAgLy8gICAgICAgICBlbnN1cmVSb3dzTG9hZGVkKGNvbnRleHQuY3VycmVudC5sYXN0UmVuZGVyZWRTdGFydEluZGV4LCBjb250ZXh0LmN1cnJlbnQubGFzdFJlbmRlcmVkU3RvcEluZGV4KTtcbiAgICAvLyAgICAgfVxuICAgIC8vIH1cbiAgICBmdW5jdGlvbiBsb2FkVW5sb2FkZWRSYW5nZXModW5sb2FkZWRSYW5nZXMpIHtcbiAgICAgICAgY29uc3QgeyBsb2FkTW9yZUl0ZW1zIH0gPSBwcm9wcztcbiAgICAgICAgdW5sb2FkZWRSYW5nZXMuZm9yRWFjaCgoW3N0YXJ0SW5kZXgsIHN0b3BJbmRleF0pID0+IHtcbiAgICAgICAgICAgIGNvbnN0IHByb21pc2UgPSBsb2FkTW9yZUl0ZW1zKHN0YXJ0SW5kZXgsIHN0b3BJbmRleCk7XG4gICAgICAgICAgICBpZiAocHJvbWlzZSAhPSBudWxsKSB7XG4gICAgICAgICAgICAgICAgcHJvbWlzZVxuICAgICAgICAgICAgICAgICAgICAudGhlbigoKSA9PiB7XG4gICAgICAgICAgICAgICAgICAgIC8vIFJlZnJlc2ggdGhlIHZpc2libGUgcm93cyBpZiBhbnkgb2YgdGhlbSBoYXZlIGp1c3QgYmVlbiBsb2FkZWQuXG4gICAgICAgICAgICAgICAgICAgIC8vIE90aGVyd2lzZSB0aGV5IHdpbGwgcmVtYWluIGluIHRoZWlyIHVubG9hZGVkIHZpc3VhbCBzdGF0ZS5cbiAgICAgICAgICAgICAgICAgICAgaWYgKGlzUmFuZ2VWaXNpYmxlKGNvbnRleHQuY3VycmVudC5sYXN0UmVuZGVyZWRTdGFydEluZGV4LCBjb250ZXh0LmN1cnJlbnQubGFzdFJlbmRlcmVkU3RvcEluZGV4LCBzdGFydEluZGV4LCBzdG9wSW5kZXgpKSB7XG4gICAgICAgICAgICAgICAgICAgICAgICAvLyBIYW5kbGUgYW4gdW5tb3VudCB3aGlsZSBwcm9taXNlcyBhcmUgc3RpbGwgaW4gZmxpZ2h0LlxuICAgICAgICAgICAgICAgICAgICAgICAgaWYgKHJlZi5jdXJyZW50ID09PSBudWxsKSB7XG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgcmV0dXJuO1xuICAgICAgICAgICAgICAgICAgICAgICAgfVxuICAgICAgICAgICAgICAgICAgICAgICAgLy8gUmVzaXplIGNhY2hlZCByb3cgc2l6ZXMgZm9yIFZhcmlhYmxlU2l6ZUxpc3QsXG4gICAgICAgICAgICAgICAgICAgICAgICAvLyBvdGhlcndpc2UganVzdCByZS1yZW5kZXIgdGhlIGxpc3QuXG4gICAgICAgICAgICAgICAgICAgICAgICBpZiAodHlwZW9mIHJlZi5jdXJyZW50LnJlc2V0QWZ0ZXJJbmRleCA9PT0gJ2Z1bmN0aW9uJykge1xuICAgICAgICAgICAgICAgICAgICAgICAgICAgIHJlZi5jdXJyZW50LnJlc2V0QWZ0ZXJJbmRleChzdGFydEluZGV4LCB0cnVlKTtcbiAgICAgICAgICAgICAgICAgICAgICAgIH1cbiAgICAgICAgICAgICAgICAgICAgICAgIGVsc2Uge1xuICAgICAgICAgICAgICAgICAgICAgICAgICAgIC8vIEhBQ0sgcmVzZXQgdGVtcG9yYXJpbHkgY2FjaGVkIGl0ZW0gc3R5bGVzIHRvIGZvcmNlIFB1cmVDb21wb25lbnQgdG8gcmUtcmVuZGVyLlxuICAgICAgICAgICAgICAgICAgICAgICAgICAgIC8vIFRoaXMgaXMgcHJldHR5IGdyb3NzLCBidXQgSSdtIG9rYXkgd2l0aCBpdCBmb3Igbm93LlxuICAgICAgICAgICAgICAgICAgICAgICAgICAgIC8vIERvbid0IGp1ZGdlIG1lLlxuICAgICAgICAgICAgICAgICAgICAgICAgICAgIGlmICh0eXBlb2YgcmVmLmN1cnJlbnQuZ2V0SXRlbVN0eWxlQ2FjaGUgPT09ICdmdW5jdGlvbicpIHtcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgcmVmLmN1cnJlbnQuZ2V0SXRlbVN0eWxlQ2FjaGUoLTEpO1xuICAgICAgICAgICAgICAgICAgICAgICAgICAgIH1cbiAgICAgICAgICAgICAgICAgICAgICAgICAgICByZWYuY3VycmVudC5mb3JjZVVwZGF0ZSgpO1xuICAgICAgICAgICAgICAgICAgICAgICAgfVxuICAgICAgICAgICAgICAgICAgICB9XG4gICAgICAgICAgICAgICAgfSlcbiAgICAgICAgICAgICAgICAgICAgLmNhdGNoKChlKSA9PiB7XG4gICAgICAgICAgICAgICAgICAgIGNvbnNvbGUubG9nKGUpO1xuICAgICAgICAgICAgICAgIH0pO1xuICAgICAgICAgICAgfVxuICAgICAgICB9KTtcbiAgICB9XG4gICAgZnVuY3Rpb24gZW5zdXJlUm93c0xvYWRlZChzdGFydEluZGV4LCBzdG9wSW5kZXgpIHtcbiAgICAgICAgY29uc3QgeyBpc0l0ZW1Mb2FkZWQsIGl0ZW1Db3VudCwgbWluaW11bUJhdGNoU2l6ZSA9IDEwLCB0aHJlc2hvbGQgPSAxNSB9ID0gcHJvcHM7XG4gICAgICAgIGNvbnN0IHVubG9hZGVkUmFuZ2VzID0gc2NhbkZvclVubG9hZGVkUmFuZ2VzKGlzSXRlbUxvYWRlZCwgaXRlbUNvdW50LCBtaW5pbXVtQmF0Y2hTaXplLCBNYXRoLm1heCgwLCBzdGFydEluZGV4IC0gdGhyZXNob2xkKSwgTWF0aC5taW4oaXRlbUNvdW50IC0gMSwgc3RvcEluZGV4ICsgdGhyZXNob2xkKSk7XG4gICAgICAgIC8vIEF2b2lkIGNhbGxpbmcgbG9hZC1yb3dzIHVubGVzcyByYW5nZSBoYXMgY2hhbmdlZC5cbiAgICAgICAgLy8gVGhpcyBzaG91bGRuJ3QgYmUgc3RyaWN0bHkgbmVjc2VzYXJ5LCBidXQgaXMgbWF5YmUgbmljZSB0byBkby5cbiAgICAgICAgaWYgKGNvbnRleHQuY3VycmVudC5tZW1vaXplZFVubG9hZGVkUmFuZ2VzLmxlbmd0aCAhPT0gdW5sb2FkZWRSYW5nZXMubGVuZ3RoIHx8XG4gICAgICAgICAgICBjb250ZXh0LmN1cnJlbnQubWVtb2l6ZWRVbmxvYWRlZFJhbmdlcy5zb21lKGZ1bmN0aW9uIChbc3RhcnRJbmRleCwgc3RvcEluZGV4XSwgaW5kZXgpIHtcbiAgICAgICAgICAgICAgICBjb25zdCByYW5nZSA9IHVubG9hZGVkUmFuZ2VzW2luZGV4XTtcbiAgICAgICAgICAgICAgICByZXR1cm4gcmFuZ2UgaW5zdGFuY2VvZiBSYW5nZSAmJiAocmFuZ2VbMF0gIT09IHN0YXJ0SW5kZXggfHwgcmFuZ2VbMV0gIT09IHN0b3BJbmRleCk7XG4gICAgICAgICAgICB9KSkge1xuICAgICAgICAgICAgY29udGV4dC5jdXJyZW50Lm1lbW9pemVkVW5sb2FkZWRSYW5nZXMgPSB1bmxvYWRlZFJhbmdlcztcbiAgICAgICAgICAgIGxvYWRVbmxvYWRlZFJhbmdlcyh1bmxvYWRlZFJhbmdlcyk7XG4gICAgICAgIH1cbiAgICB9XG4gICAgcmV0dXJuIHByb3BzLmNoaWxkcmVuKHtcbiAgICAgICAgb25JdGVtc1JlbmRlcmVkLFxuICAgICAgICByZWY6IChsaXN0UmVmKSA9PiB7XG4gICAgICAgICAgICByZWYuY3VycmVudCA9IGxpc3RSZWY7XG4gICAgICAgIH0sXG4gICAgfSk7XG59O1xuZXhwb3J0IGRlZmF1bHQgTG9hZGVyO1xuIiwiZXhwb3J0IHsgZGVmYXVsdCBhcyBMaXN0IH0gZnJvbSAnLi9MaXN0JztcbmV4cG9ydCB7IGRlZmF1bHQgYXMgTG9hZGVyIH0gZnJvbSAnLi9Mb2FkZXInO1xuZXhwb3J0IHsgZGVmYXVsdCBhcyBMYXp5TGlzdCB9IGZyb20gJy4vTGF6eUxpc3QnO1xuIiwiLy8gZXh0cmFjdGVkIGJ5IG1pbmktY3NzLWV4dHJhY3QtcGx1Z2luXG5leHBvcnQge307IiwiLy8gZXh0cmFjdGVkIGJ5IG1pbmktY3NzLWV4dHJhY3QtcGx1Z2luXG5leHBvcnQge307Il0sIm5hbWVzIjpbInJlbmRlciIsIlJlYWN0IiwiVGltZWxpbmVBcHAiLCJhcHBFbGVtZW50IiwiZG9jdW1lbnQiLCJnZXRFbGVtZW50QnlJZCIsImhlaWdodCIsImdldEVsZW1lbnRzQnlDbGFzc05hbWUiLCJvZmZzZXRIZWlnaHQiLCJhbGxvd1Nob3dEZWxldGVkU2VnbWVudHMiLCJkYXRhc2V0IiwiYWxsb3dTaG93RGVsZXRlZCIsImNyZWF0ZUVsZW1lbnQiLCJTdHJpY3RNb2RlIiwiY29udGFpbmVySGVpZ2h0IiwiUHJvcFR5cGVzIiwiVHJhbnNsYXRvciIsIkVtcHR5TGlzdCIsIl9yZWYiLCJfcmVmJG1lc3NhZ2UiLCJtZXNzYWdlIiwidHJhbnMiLCJjbGFzc05hbWUiLCJwcm9wVHlwZXMiLCJzdHJpbmciLCJSb3V0ZXIiLCJNYWlsYm94T2ZmZXIiLCJmb3J3YXJkaW5nRW1haWwiLCJkYW5nZXJvdXNseVNldElubmVySFRNTCIsIl9faHRtbCIsImNvbmNhdCIsImdlbmVyYXRlIiwiaXNSZXF1aXJlZCIsIlBhc3RTZWdtZW50c0xvYWRlckxpbmsiLCJfcmVmJGxvYWRpbmciLCJsb2FkaW5nIiwiRnJhZ21lbnQiLCJocmVmIiwiYm9vbCIsIlNob3dEZWxldGVkU2VnbWVudHNMaW5rIiwicmV2ZXJzZSIsImRlZmF1bHRQcm9wcyIsIl9yZWdlbmVyYXRvclJ1bnRpbWUiLCJlIiwidCIsInIiLCJPYmplY3QiLCJwcm90b3R5cGUiLCJuIiwiaGFzT3duUHJvcGVydHkiLCJvIiwiZGVmaW5lUHJvcGVydHkiLCJ2YWx1ZSIsImkiLCJTeW1ib2wiLCJhIiwiaXRlcmF0b3IiLCJjIiwiYXN5bmNJdGVyYXRvciIsInUiLCJ0b1N0cmluZ1RhZyIsImRlZmluZSIsImVudW1lcmFibGUiLCJjb25maWd1cmFibGUiLCJ3cml0YWJsZSIsIndyYXAiLCJHZW5lcmF0b3IiLCJjcmVhdGUiLCJDb250ZXh0IiwibWFrZUludm9rZU1ldGhvZCIsInRyeUNhdGNoIiwidHlwZSIsImFyZyIsImNhbGwiLCJoIiwibCIsImYiLCJzIiwieSIsIkdlbmVyYXRvckZ1bmN0aW9uIiwiR2VuZXJhdG9yRnVuY3Rpb25Qcm90b3R5cGUiLCJwIiwiZCIsImdldFByb3RvdHlwZU9mIiwidiIsInZhbHVlcyIsImciLCJkZWZpbmVJdGVyYXRvck1ldGhvZHMiLCJmb3JFYWNoIiwiX2ludm9rZSIsIkFzeW5jSXRlcmF0b3IiLCJpbnZva2UiLCJfdHlwZW9mIiwicmVzb2x2ZSIsIl9fYXdhaXQiLCJ0aGVuIiwiY2FsbEludm9rZVdpdGhNZXRob2RBbmRBcmciLCJFcnJvciIsImRvbmUiLCJtZXRob2QiLCJkZWxlZ2F0ZSIsIm1heWJlSW52b2tlRGVsZWdhdGUiLCJzZW50IiwiX3NlbnQiLCJkaXNwYXRjaEV4Y2VwdGlvbiIsImFicnVwdCIsInJldHVybiIsIlR5cGVFcnJvciIsInJlc3VsdE5hbWUiLCJuZXh0IiwibmV4dExvYyIsInB1c2hUcnlFbnRyeSIsInRyeUxvYyIsImNhdGNoTG9jIiwiZmluYWxseUxvYyIsImFmdGVyTG9jIiwidHJ5RW50cmllcyIsInB1c2giLCJyZXNldFRyeUVudHJ5IiwiY29tcGxldGlvbiIsInJlc2V0IiwiaXNOYU4iLCJsZW5ndGgiLCJkaXNwbGF5TmFtZSIsImlzR2VuZXJhdG9yRnVuY3Rpb24iLCJjb25zdHJ1Y3RvciIsIm5hbWUiLCJtYXJrIiwic2V0UHJvdG90eXBlT2YiLCJfX3Byb3RvX18iLCJhd3JhcCIsImFzeW5jIiwiUHJvbWlzZSIsImtleXMiLCJwb3AiLCJwcmV2IiwiY2hhckF0Iiwic2xpY2UiLCJzdG9wIiwicnZhbCIsImhhbmRsZSIsImNvbXBsZXRlIiwiZmluaXNoIiwiY2F0Y2giLCJfY2F0Y2giLCJkZWxlZ2F0ZVlpZWxkIiwiX2V4dGVuZHMiLCJhc3NpZ24iLCJiaW5kIiwidGFyZ2V0IiwiYXJndW1lbnRzIiwic291cmNlIiwia2V5IiwiYXBwbHkiLCJvd25LZXlzIiwiZ2V0T3duUHJvcGVydHlTeW1ib2xzIiwiZmlsdGVyIiwiZ2V0T3duUHJvcGVydHlEZXNjcmlwdG9yIiwiX29iamVjdFNwcmVhZCIsIl9kZWZpbmVQcm9wZXJ0eSIsImdldE93blByb3BlcnR5RGVzY3JpcHRvcnMiLCJkZWZpbmVQcm9wZXJ0aWVzIiwib2JqIiwiX3RvUHJvcGVydHlLZXkiLCJfdG9QcmltaXRpdmUiLCJTdHJpbmciLCJpbnB1dCIsImhpbnQiLCJwcmltIiwidG9QcmltaXRpdmUiLCJ1bmRlZmluZWQiLCJyZXMiLCJOdW1iZXIiLCJfb2JqZWN0V2l0aG91dFByb3BlcnRpZXMiLCJleGNsdWRlZCIsIl9vYmplY3RXaXRob3V0UHJvcGVydGllc0xvb3NlIiwic291cmNlU3ltYm9sS2V5cyIsImluZGV4T2YiLCJwcm9wZXJ0eUlzRW51bWVyYWJsZSIsInNvdXJjZUtleXMiLCJhc3luY0dlbmVyYXRvclN0ZXAiLCJnZW4iLCJyZWplY3QiLCJfbmV4dCIsIl90aHJvdyIsImluZm8iLCJlcnJvciIsIl9hc3luY1RvR2VuZXJhdG9yIiwiZm4iLCJzZWxmIiwiYXJncyIsImVyciIsIl9zbGljZWRUb0FycmF5IiwiYXJyIiwiX2FycmF5V2l0aEhvbGVzIiwiX2l0ZXJhYmxlVG9BcnJheUxpbWl0IiwiX3Vuc3VwcG9ydGVkSXRlcmFibGVUb0FycmF5IiwiX25vbkl0ZXJhYmxlUmVzdCIsIm1pbkxlbiIsIl9hcnJheUxpa2VUb0FycmF5IiwidG9TdHJpbmciLCJBcnJheSIsImZyb20iLCJ0ZXN0IiwibGVuIiwiYXJyMiIsImlzQXJyYXkiLCJMYXp5TGlzdCIsIkFQSSIsInVzZUVmZmVjdCIsInVzZVN0YXRlIiwiU2VnbWVudFR5cGVzIiwiU3Bpbm5lciIsIl8iLCJfdXNlU3RhdGUiLCJfdXNlU3RhdGUyIiwic2V0Rm9yd2FyZGluZ0VtYWlsIiwiX3VzZVN0YXRlMyIsIl91c2VTdGF0ZTQiLCJzaG93RGVsZXRlZCIsInNldFNob3dEZWxldGVkIiwiX3VzZVN0YXRlNSIsIl91c2VTdGF0ZTYiLCJzZWdtZW50cyIsInNldFNlZ21lbnRzIiwiX3VzZVN0YXRlNyIsIl91c2VTdGF0ZTgiLCJsb2FkaW5nQXBwIiwic2V0TG9hZGluZ0FwcCIsImVtcHR5TGlzdCIsImxvYWRNb3JlIiwiX2xvYWRNb3JlIiwiX2NhbGxlZSIsInBhcmFtcyIsImJlZm9yZSIsImRhdGEiLCJyZXNwb25zZSIsIl9kYXRhIiwibmV3U2VnbWVudHMiLCJfZm9yd2FyZGluZ0VtYWlsIiwiX2NhbGxlZSQiLCJfY29udGV4dCIsImdldCIsImxhc3QiLCJpc051bGwiLCJ0MCIsInVuaW9uQnkiLCJzZWdtZW50IiwiaWQiLCJpdGVtS2V5IiwiaW5kZXgiLCJTZWdtZW50UmVuZGVyZXIiLCJyZWYiLCJzZWdtZW50RGF0YSIsInNlZ21lbnRUeXBlIiwic2VnbWVudFByb3BzIiwiX2V4Y2x1ZGVkIiwiaGFzIiwiVGltZWxpbmVDb250YWluZXIiLCJmb3J3YXJkUmVmIiwiX3JlZjIiLCJjaGlsZHJlbiIsInByb3BzIiwiX2V4Y2x1ZGVkMiIsImlzRW1wdHkiLCJub2RlIiwic3R5bGUiLCJpdGVtQ291bnQiLCJsaXN0UHJvcHMiLCJpbm5lckVsZW1lbnRUeXBlIiwib3ZlcmZsb3dZIiwiaW5uZXJTdHlsZSIsIndpZHRoIiwibnVtYmVyIiwiRGF0ZVRpbWVEaWZmIiwiY2xhc3NOYW1lcyIsIkRhdGVTZWdtZW50Iiwic3RhcnREYXRlIiwibG9jYWxEYXRlIiwibG9jYWxEYXRlSVNPIiwiZ2V0UmVsYXRpdmVEYXRlIiwibG9uZ0Zvcm1hdFZpYURhdGVzIiwiRGF0ZSIsImdldERheXNOdW1iZXJGcm9tVG9kYXkiLCJkaWZmIiwiTWF0aCIsImFicyIsImZsb29yIiwiY2FwaXRhbGl6ZSIsInN0ciIsInRvVXBwZXJDYXNlIiwiZ2V0RGF0ZUJsb2NrIiwicmVsYXRpdmVEYXRlIiwiZGlzYWJsZSIsImRheVN0YXJ0Iiwic2V0SG91cnMiLCJQbGFuRW5kIiwiUGxhblN0YXJ0IiwiU2VnbWVudCIsIl9jbGFzc05hbWVzIiwiaWNvbiIsImNoYW5nZWQiLCJlbmREYXRlIiwiZGV0YWlscyIsIl9wcm9wcyRkZWxldGVkIiwiZGVsZXRlZCIsInN0YXJ0VGltZXpvbmUiLCJwcmV2VGltZSIsImxvY2FsVGltZSIsInRpdGxlIiwiY29uZk5vIiwibWFwIiwibm93IiwidHJpcFJvd0NsYXNzSWNvbiIsInNwbGl0Iiwic2hpZnQiLCJ0cmlwUm93Q2xhc3MiLCJnZXRMb2NhbFRpbWUiLCJ0aW1lIiwicGFydHMiLCJpc1N0cmluZyIsInNyYyIsImFsdCIsImJyZWFrQWZ0ZXIiLCJsb2NhbERhdGVUaW1lSVNPIiwic2hhcGUiLCJwb2ludHMiLCJhcnJheU9mIiwiYXJyVGltZSIsIm9uZU9mVHlwZSIsImFjY291bnRJZCIsImFnZW50SWQiLCJyZWZyZXNoTGluayIsImF1dG9Mb2dpbkxpbmsiLCJib29raW5nTGluayIsInVybCIsImZvcm1GaWVsZHMiLCJkZXN0aW5hdGlvbiIsImNoZWNraW5EYXRlIiwiY2hlY2tvdXREYXRlIiwiY2FuRWRpdCIsImNhbkNoZWNrIiwiY2FuQXV0b0xvZ2luIiwiU3RhdHVzIiwic2hhcmVDb2RlIiwibW9uaXRvcmVkU3RhdHVzIiwiY29sdW1ucyIsInJvd3MiLCJkYXRlIiwibmlnaHRzIiwicHJldkRhdGUiLCJ0aW1lc3RhbXAiLCJ0aW1lem9uZSIsImZvcm1hdHRlZERhdGUiLCJhcnJpdmFsRGF5IiwidGV4dCIsImdlbyIsImNvdW50cnkiLCJzdGF0ZSIsImNpdHkiLCJwYWlycyIsIm9iamVjdCIsImRheXMiLCJwbGFjZSIsImNvZGUiLCJGYXgiLCJHdWVzdENvdW50IiwiS2lkc0NvdW50IiwiUm9vbXMiLCJSb29tTG9uZ0Rlc2NyaXB0aW9ucyIsIlJvb21TaG9ydERlc2NyaXB0aW9ucyIsIlJvb21SYXRlIiwiUm9vbVJhdGVEZXNjcmlwdGlvbiIsIlRyYXZlbGVyTmFtZXMiLCJDYW5jZWxsYXRpb25Qb2xpY3kiLCJDYXJEZXNjcmlwdGlvbiIsIkxpY2Vuc2VQbGF0ZSIsIlNwb3ROdW1iZXIiLCJDYXJNb2RlbCIsIkNhclR5cGUiLCJQaWNrVXBGYXgiLCJEcm9wT2ZmRmF4IiwiRGluZXJOYW1lIiwiQ3J1aXNlTmFtZSIsIkRlY2siLCJDYWJpbk51bWJlciIsIlNoaXBDb2RlIiwiU2hpcE5hbWUiLCJTaGlwQ2FiaW5DbGFzcyIsIlNtb2tpbmciLCJTdG9wcyIsIlNlcnZpY2VDbGFzc2VzIiwiU2VydmljZU5hbWUiLCJDYXJOdW1iZXIiLCJBZHVsdHNDb3VudCIsIkFpcmNyYWZ0IiwiVGlja2V0TnVtYmVycyIsIlRyYXZlbGxlZE1pbGVzIiwiTWVhbCIsIkJvb2tpbmdDbGFzcyIsIkNhYmluQ2xhc3MiLCJwaG9uZSIsIm9yaWdpbnMiLCJhdXRvIiwicHJvdmlkZXIiLCJhY2NvdW50TnVtYmVyIiwib3duZXIiLCJjb25mTnVtYmVyIiwiZW1haWwiLCJtYW51YWwiLCJncm91cCIsImxhc3RTeW5jIiwibGFzdFVwZGF0ZWQiLCJ1c2VGb3JjZVVwZGF0ZSIsInNldENvdW50IiwicHJldkNvdW50IiwidXNlUmVmIiwidXNlU3RhdGVXaXRoQ2FsbGJhY2siLCJpbml0aWFsVmFsdWUiLCJjYWxsYmFja1JlZiIsInNldFZhbHVlIiwiY3VycmVudCIsInNldFZhbHVlV2l0aENhbGxiYWNrIiwibmV3VmFsdWUiLCJjYWxsYmFjayIsImV4dHJhY3RPcHRpb25zIiwiRFREaWZmIiwiZ2V0Rm9ybWF0dGVyIiwib3B0cyIsIkludGwiLCJOdW1iZXJGb3JtYXQiLCJsb2NhbGUiLCJSYW5nZUVycm9yIiwiZGVmYXVsdExvY2FsZSIsImZvcm1hdHRlciIsImZvcm1hdCIsIm9uUmVhZHkiLCJyZWFkeVN0YXRlIiwiYWRkRXZlbnRMaXN0ZW5lciIsImVuYWJsZWRUcmFuc0hlbHBlciIsImhhc1JvbGVUcmFuc2xhdG9yIiwiY29uc29sZSIsImxvZyIsImluaXQiLCJkZWZhdWx0IiwiTGlzdCIsIkxvYWRlciIsIk1lYXN1cmUiLCJtZXJnZVJlZnMiLCJfbGVuIiwicmVmcyIsIl9rZXkiLCJpbmNvbWluZ1JlZiIsIml0ZW1TaXplcyIsImxpc3RSZWYiLCJnZXRJdGVtU2l6ZSIsImhhbmRsZUl0ZW1SZXNpemUiLCJfYm91bmRzJGhlaWdodCIsIl9tYXJnaW4kdG9wIiwiX21hcmdpbiRib3R0b20iLCJib3VuZHMiLCJtYXJnaW4iLCJ0b3AiLCJib3R0b20iLCJyZXNldEFmdGVySW5kZXgiLCJSb3ciLCJvblJlc2l6ZSIsInJlc2l6ZURhdGEiLCJfcmVmMyIsIm1lYXN1cmVSZWYiLCJpc0l0ZW1Mb2FkZWQiLCJsb2FkTW9yZUl0ZW1zIiwiX3JlZjQiLCJvbkl0ZW1zUmVuZGVyZWQiLCJpdGVtU2l6ZSIsInVzZUltcGVyYXRpdmVIYW5kbGUiLCJtZW1vaXplT25lIiwiSVNfU0NST0xMSU5HX0RFQk9VTkNFX0lOVEVSVkFMIiwiaGFzTmF0aXZlUGVyZm9ybWFuY2VOb3ciLCJwZXJmb3JtYW5jZSIsImNhbmNlbFRpbWVvdXQiLCJ0aW1lb3V0SUQiLCJjYW5jZWxBbmltYXRpb25GcmFtZSIsInJlcXVlc3RUaW1lb3V0IiwiZGVsYXkiLCJzdGFydCIsInRpY2siLCJyZXF1ZXN0QW5pbWF0aW9uRnJhbWUiLCJnZXRJdGVtTWV0YWRhdGEiLCJjb250ZXh0IiwiaXRlbU1ldGFkYXRhTWFwIiwibGFzdE1lYXN1cmVkSW5kZXgiLCJvZmZzZXQiLCJpdGVtTWV0YWRhdGEiLCJzaXplIiwicmVzdWx0IiwiZmluZE5lYXJlc3RJdGVtIiwiX2l0ZW1NZXRhZGF0YU1hcCRsYXN0IiwiX2l0ZW1NZXRhZGF0YU1hcCRsYXN0MiIsImxhc3RNZWFzdXJlZEl0ZW1PZmZzZXQiLCJmaW5kTmVhcmVzdEl0ZW1CaW5hcnlTZWFyY2giLCJmaW5kTmVhcmVzdEl0ZW1FeHBvbmVudGlhbFNlYXJjaCIsIm1heCIsImhpZ2giLCJsb3ciLCJtaWRkbGUiLCJjdXJyZW50T2Zmc2V0IiwiaW50ZXJ2YWwiLCJtaW4iLCJnZXRFc3RpbWF0ZWRUb3RhbFNpemUiLCJlc3RpbWF0ZWRJdGVtU2l6ZSIsInRvdGFsU2l6ZU9mTWVhc3VyZWRJdGVtcyIsIm51bVVubWVhc3VyZWRJdGVtcyIsInRvdGFsU2l6ZU9mVW5tZWFzdXJlZEl0ZW1zIiwiX3Byb3BzJGluaXRpYWxTY3JvbGxPIiwiaW5pdGlhbFNjcm9sbE9mZnNldCIsIl9wcm9wcyRpdGVtRGF0YSIsIml0ZW1EYXRhIiwiX3Byb3BzJG92ZXJzY2FuQ291bnQiLCJvdmVyc2NhbkNvdW50IiwiX3Byb3BzJGVzdGltYXRlZEl0ZW1TIiwiX3Byb3BzJGl0ZW1LZXkiLCJvdXRlckVsZW1lbnRUeXBlIiwiaW5uZXJDbGFzc05hbWUiLCJpbm5lclJlZiIsIl91c2VTdGF0ZVdpdGhDYWxsYmFjayIsImlzU2Nyb2xsaW5nIiwic2Nyb2xsVXBkYXRlV2FzUmVxdWVzdGVkIiwic2Nyb2xsRGlyZWN0aW9uIiwic2Nyb2xsT2Zmc2V0IiwiX3VzZVN0YXRlV2l0aENhbGxiYWNrMiIsIl91c2VTdGF0ZVdpdGhDYWxsYmFjazMiLCJzZXRTdGF0ZSIsInJlc2V0SXNTY3JvbGxpbmdUaW1lb3V0SWQiLCJvdXRlckxpc3RSZWYiLCJnZXRJdGVtU3R5bGVDYWNoZSIsImZvcmNlVXBkYXRlIiwib3V0ZXJSZWZTZXR0ZXIiLCJvdXRlclJlZiIsImdldFN0b3BJbmRleEZvclN0YXJ0SW5kZXgiLCJzdGFydEluZGV4IiwibWF4T2Zmc2V0Iiwic3RvcEluZGV4IiwiZ2V0UmFuZ2VUb1JlbmRlciIsIm92ZXJzY2FuQmFja3dhcmQiLCJvdmVyc2NhbkZvcndhcmQiLCJvblNjcm9sbENhbGxhYmxlIiwib25TY3JvbGwiLCJjYWxsT25JdGVtc1JlbmRlcmVkIiwib3ZlcnNjYW5TdGFydEluZGV4Iiwib3ZlcnNjYW5TdG9wSW5kZXgiLCJ2aXNpYmxlU3RhcnRJbmRleCIsInZpc2libGVTdG9wSW5kZXgiLCJjYWxsT25TY3JvbGwiLCJjYWxsUHJvcHNDYWxsYmFja3MiLCJfZ2V0UmFuZ2VUb1JlbmRlciIsIl9nZXRSYW5nZVRvUmVuZGVyMiIsImdldEl0ZW1TdHlsZSIsIml0ZW1TdHlsZUNhY2hlIiwiaXRlbVN0eWxlQ2FjaGVWYWx1ZSIsIl9jb250ZXh0JGN1cnJlbnQkaXRlbSIsIl9jb250ZXh0JGN1cnJlbnQkaXRlbTIiLCJwb3NpdGlvbiIsImxlZnQiLCJvblNjcm9sbFZlcnRpY2FsIiwiZXZlbnQiLCJfZXZlbnQkY3VycmVudFRhcmdldCIsImN1cnJlbnRUYXJnZXQiLCJjbGllbnRIZWlnaHQiLCJzY3JvbGxIZWlnaHQiLCJzY3JvbGxUb3AiLCJwcmV2U3RhdGUiLCJyZXNldElzU2Nyb2xsaW5nRGVib3VuY2VkIiwicmVzZXRJc1Njcm9sbGluZyIsInNob3VsZEZvcmNlVXBkYXRlIiwiX2dldFJhbmdlVG9SZW5kZXIzIiwiX2dldFJhbmdlVG9SZW5kZXI0IiwiaXRlbXMiLCJlc3RpbWF0ZWRUb3RhbFNpemUiLCJXZWJraXRPdmVyZmxvd1Njcm9sbGluZyIsIndpbGxDaGFuZ2UiLCJwb2ludGVyRXZlbnRzIiwic2NhbkZvclVubG9hZGVkUmFuZ2VzIiwibWluaW11bUJhdGNoU2l6ZSIsInVubG9hZGVkUmFuZ2VzIiwicmFuZ2VTdGFydEluZGV4IiwicmFuZ2VTdG9wSW5kZXgiLCJsb2FkZWQiLCJwb3RlbnRpYWxTdG9wSW5kZXgiLCJmaXJzdFJhbmdlIiwiaXNSYW5nZVZpc2libGUiLCJsYXN0UmVuZGVyZWRTdGFydEluZGV4IiwibGFzdFJlbmRlcmVkU3RvcEluZGV4IiwibWVtb2l6ZWRVbmxvYWRlZFJhbmdlcyIsImVuc3VyZVJvd3NMb2FkZWQiLCJsb2FkVW5sb2FkZWRSYW5nZXMiLCJwcm9taXNlIiwiX3Byb3BzJG1pbmltdW1CYXRjaFNpIiwiX3Byb3BzJHRocmVzaG9sZCIsInRocmVzaG9sZCIsInNvbWUiLCJyYW5nZSIsIlJhbmdlIl0sInNvdXJjZVJvb3QiOiIifQ==