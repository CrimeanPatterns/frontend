"use strict";
(self["webpackChunkAwardWallet"] = self["webpackChunkAwardWallet"] || []).push([["assets_bem_ts_service_axios_index_js-assets_bem_ts_service_env_ts"],{

/***/ "./assets/bem/ts/service/axios/index.js":
/*!**********************************************!*\
  !*** ./assets/bem/ts/service/axios/index.js ***!
  \**********************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__),
/* harmony export */   useAxios: () => (/* binding */ useAxios)
/* harmony export */ });
/* harmony import */ var core_js_modules_es_object_to_string_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! core-js/modules/es.object.to-string.js */ "./node_modules/core-js/modules/es.object.to-string.js");
/* harmony import */ var core_js_modules_es_object_to_string_js__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_object_to_string_js__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var core_js_modules_es_promise_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! core-js/modules/es.promise.js */ "./node_modules/core-js/modules/es.promise.js");
/* harmony import */ var core_js_modules_es_promise_js__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_promise_js__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var core_js_modules_es_array_iterator_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! core-js/modules/es.array.iterator.js */ "./node_modules/core-js/modules/es.array.iterator.js");
/* harmony import */ var core_js_modules_es_array_iterator_js__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_array_iterator_js__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var core_js_modules_es_string_iterator_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! core-js/modules/es.string.iterator.js */ "./node_modules/core-js/modules/es.string.iterator.js");
/* harmony import */ var core_js_modules_es_string_iterator_js__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_string_iterator_js__WEBPACK_IMPORTED_MODULE_3__);
/* harmony import */ var core_js_modules_web_dom_collections_iterator_js__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! core-js/modules/web.dom-collections.iterator.js */ "./node_modules/core-js/modules/web.dom-collections.iterator.js");
/* harmony import */ var core_js_modules_web_dom_collections_iterator_js__WEBPACK_IMPORTED_MODULE_4___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_web_dom_collections_iterator_js__WEBPACK_IMPORTED_MODULE_4__);
/* harmony import */ var core_js_modules_es_symbol_to_primitive_js__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! core-js/modules/es.symbol.to-primitive.js */ "./node_modules/core-js/modules/es.symbol.to-primitive.js");
/* harmony import */ var core_js_modules_es_symbol_to_primitive_js__WEBPACK_IMPORTED_MODULE_5___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_symbol_to_primitive_js__WEBPACK_IMPORTED_MODULE_5__);
/* harmony import */ var core_js_modules_es_date_to_primitive_js__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! core-js/modules/es.date.to-primitive.js */ "./node_modules/core-js/modules/es.date.to-primitive.js");
/* harmony import */ var core_js_modules_es_date_to_primitive_js__WEBPACK_IMPORTED_MODULE_6___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_date_to_primitive_js__WEBPACK_IMPORTED_MODULE_6__);
/* harmony import */ var core_js_modules_es_symbol_js__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__(/*! core-js/modules/es.symbol.js */ "./node_modules/core-js/modules/es.symbol.js");
/* harmony import */ var core_js_modules_es_symbol_js__WEBPACK_IMPORTED_MODULE_7___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_symbol_js__WEBPACK_IMPORTED_MODULE_7__);
/* harmony import */ var core_js_modules_es_symbol_description_js__WEBPACK_IMPORTED_MODULE_8__ = __webpack_require__(/*! core-js/modules/es.symbol.description.js */ "./node_modules/core-js/modules/es.symbol.description.js");
/* harmony import */ var core_js_modules_es_symbol_description_js__WEBPACK_IMPORTED_MODULE_8___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_symbol_description_js__WEBPACK_IMPORTED_MODULE_8__);
/* harmony import */ var core_js_modules_es_number_constructor_js__WEBPACK_IMPORTED_MODULE_9__ = __webpack_require__(/*! core-js/modules/es.number.constructor.js */ "./node_modules/core-js/modules/es.number.constructor.js");
/* harmony import */ var core_js_modules_es_number_constructor_js__WEBPACK_IMPORTED_MODULE_9___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_number_constructor_js__WEBPACK_IMPORTED_MODULE_9__);
/* harmony import */ var core_js_modules_es_object_keys_js__WEBPACK_IMPORTED_MODULE_10__ = __webpack_require__(/*! core-js/modules/es.object.keys.js */ "./node_modules/core-js/modules/es.object.keys.js");
/* harmony import */ var core_js_modules_es_object_keys_js__WEBPACK_IMPORTED_MODULE_10___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_object_keys_js__WEBPACK_IMPORTED_MODULE_10__);
/* harmony import */ var core_js_modules_es_array_filter_js__WEBPACK_IMPORTED_MODULE_11__ = __webpack_require__(/*! core-js/modules/es.array.filter.js */ "./node_modules/core-js/modules/es.array.filter.js");
/* harmony import */ var core_js_modules_es_array_filter_js__WEBPACK_IMPORTED_MODULE_11___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_array_filter_js__WEBPACK_IMPORTED_MODULE_11__);
/* harmony import */ var core_js_modules_es_object_get_own_property_descriptor_js__WEBPACK_IMPORTED_MODULE_12__ = __webpack_require__(/*! core-js/modules/es.object.get-own-property-descriptor.js */ "./node_modules/core-js/modules/es.object.get-own-property-descriptor.js");
/* harmony import */ var core_js_modules_es_object_get_own_property_descriptor_js__WEBPACK_IMPORTED_MODULE_12___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_object_get_own_property_descriptor_js__WEBPACK_IMPORTED_MODULE_12__);
/* harmony import */ var core_js_modules_web_dom_collections_for_each_js__WEBPACK_IMPORTED_MODULE_13__ = __webpack_require__(/*! core-js/modules/web.dom-collections.for-each.js */ "./node_modules/core-js/modules/web.dom-collections.for-each.js");
/* harmony import */ var core_js_modules_web_dom_collections_for_each_js__WEBPACK_IMPORTED_MODULE_13___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_web_dom_collections_for_each_js__WEBPACK_IMPORTED_MODULE_13__);
/* harmony import */ var core_js_modules_es_object_get_own_property_descriptors_js__WEBPACK_IMPORTED_MODULE_14__ = __webpack_require__(/*! core-js/modules/es.object.get-own-property-descriptors.js */ "./node_modules/core-js/modules/es.object.get-own-property-descriptors.js");
/* harmony import */ var core_js_modules_es_object_get_own_property_descriptors_js__WEBPACK_IMPORTED_MODULE_14___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_object_get_own_property_descriptors_js__WEBPACK_IMPORTED_MODULE_14__);
/* harmony import */ var core_js_modules_es_symbol_iterator_js__WEBPACK_IMPORTED_MODULE_15__ = __webpack_require__(/*! core-js/modules/es.symbol.iterator.js */ "./node_modules/core-js/modules/es.symbol.iterator.js");
/* harmony import */ var core_js_modules_es_symbol_iterator_js__WEBPACK_IMPORTED_MODULE_15___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_symbol_iterator_js__WEBPACK_IMPORTED_MODULE_15__);
/* harmony import */ var axios_hooks__WEBPACK_IMPORTED_MODULE_16__ = __webpack_require__(/*! axios-hooks */ "./node_modules/axios-hooks/es/index.js");
/* harmony import */ var lodash__WEBPACK_IMPORTED_MODULE_17__ = __webpack_require__(/*! lodash */ "./node_modules/lodash/lodash.js");
/* harmony import */ var lodash__WEBPACK_IMPORTED_MODULE_17___default = /*#__PURE__*/__webpack_require__.n(lodash__WEBPACK_IMPORTED_MODULE_17__);
/* harmony import */ var axios__WEBPACK_IMPORTED_MODULE_19__ = __webpack_require__(/*! axios */ "./node_modules/axios/lib/axios.js");
/* harmony import */ var _retry__WEBPACK_IMPORTED_MODULE_18__ = __webpack_require__(/*! ./retry */ "./assets/bem/ts/service/axios/retry.js");
function _typeof(o) { "@babel/helpers - typeof"; return _typeof = "function" == typeof Symbol && "symbol" == typeof Symbol.iterator ? function (o) { return typeof o; } : function (o) { return o && "function" == typeof Symbol && o.constructor === Symbol && o !== Symbol.prototype ? "symbol" : typeof o; }, _typeof(o); }
















function ownKeys(e, r) { var t = Object.keys(e); if (Object.getOwnPropertySymbols) { var o = Object.getOwnPropertySymbols(e); r && (o = o.filter(function (r) { return Object.getOwnPropertyDescriptor(e, r).enumerable; })), t.push.apply(t, o); } return t; }
function _objectSpread(e) { for (var r = 1; r < arguments.length; r++) { var t = null != arguments[r] ? arguments[r] : {}; r % 2 ? ownKeys(Object(t), !0).forEach(function (r) { _defineProperty(e, r, t[r]); }) : Object.getOwnPropertyDescriptors ? Object.defineProperties(e, Object.getOwnPropertyDescriptors(t)) : ownKeys(Object(t)).forEach(function (r) { Object.defineProperty(e, r, Object.getOwnPropertyDescriptor(t, r)); }); } return e; }
function _defineProperty(obj, key, value) { key = _toPropertyKey(key); if (key in obj) { Object.defineProperty(obj, key, { value: value, enumerable: true, configurable: true, writable: true }); } else { obj[key] = value; } return obj; }
function _toPropertyKey(arg) { var key = _toPrimitive(arg, "string"); return _typeof(key) === "symbol" ? key : String(key); }
function _toPrimitive(input, hint) { if (_typeof(input) !== "object" || input === null) return input; var prim = input[Symbol.toPrimitive]; if (prim !== undefined) { var res = prim.call(input, hint || "default"); if (_typeof(res) !== "object") return res; throw new TypeError("@@toPrimitive must return a primitive value."); } return (hint === "string" ? String : Number)(input); }




var X_XSRF_TOKEN = 'x-xsrf-token';
var X_XSRF_FAILED = 'x-xsrf-failed';
var instance = axios__WEBPACK_IMPORTED_MODULE_19__["default"].create({
  headers: {
    'X-Requested-With': 'XMLHttpRequest'
  }
});
var xsrfToken = null;
function getXsrfToken() {
  if (xsrfToken === null) {
    xsrfToken = document.head.querySelector('meta[name="csrf-token"]').content;
  }
  return xsrfToken;
}
function saveXsrfToken(response) {
  if (response && response.headers && response.headers[X_XSRF_TOKEN]) {
    console.log('saving xsrf token', response.headers[X_XSRF_TOKEN]);
    xsrfToken = response.headers[X_XSRF_TOKEN];
  }
}
function isXsrfFailed(response) {
  return lodash__WEBPACK_IMPORTED_MODULE_17___default().get(response, "headers[".concat(X_XSRF_FAILED, "]")) === 'true';
}
function checkXsrfFailed(response) {
  if (isXsrfFailed(response)) {
    console.log('xsrf failed');
    return false;
  }
  return true;
}
(0,_retry__WEBPACK_IMPORTED_MODULE_18__["default"])(instance, {
  retries: 3,
  retryCondition: function retryCondition(error) {
    if ((0,_retry__WEBPACK_IMPORTED_MODULE_18__.isNetworkOrIdempotentRequestError)(error)) {
      console.log('network or idempotent request error');
      return true;
    }
    return !checkXsrfFailed(lodash__WEBPACK_IMPORTED_MODULE_17___default().has(error, 'response') ? error.response : error);
  }
});
instance.interceptors.request.use(function (config) {
  var headers = {};
  var xsrfToken = getXsrfToken();
  if (xsrfToken) {
    headers[X_XSRF_TOKEN.toUpperCase()] = xsrfToken;
  }
  config.headers = _objectSpread(_objectSpread({}, config.headers), headers);
  return config;
});
instance.interceptors.response.use(function (response) {
  saveXsrfToken(response);
  return response;
}, function (rejection) {
  var response = rejection.response;
  saveXsrfToken(response);

  // redirect to login
  if (lodash__WEBPACK_IMPORTED_MODULE_17___default().get(rejection, 'response.data') === 'unauthorized' || lodash__WEBPACK_IMPORTED_MODULE_17___default().get(rejection, 'response.headers["ajax-error"]') === 'unauthorized') {
    try {
      if (window.parent !== window) {
        parent.location.href = '/security/unauthorized.php?BackTo=' + encodeURI(parent.location.href);
        return Promise.resolve();
      }
    } catch (e) {
      // eslint-disable-next-line
    }
    location.href = '/security/unauthorized.php?BackTo=' + encodeURI(location.href);
    return Promise.resolve();
  }
  Promise.all(/*! import() */[__webpack_require__.e("vendors-node_modules_core-js_modules_es_array_find_js-node_modules_core-js_modules_es_array_f-112b41"), __webpack_require__.e("vendors-node_modules_core-js_modules_es_number_to-fixed_js-node_modules_intl_index_js"), __webpack_require__.e("web_assets_common_vendors_jquery_dist_jquery_js"), __webpack_require__.e("web_assets_common_vendors_jquery-ui_jquery-ui_min_js"), __webpack_require__.e("web_assets_awardwalletnewdesign_js_lib_dialog_js"), __webpack_require__.e("assets_bem_ts_service_errorDialog_js")]).then(__webpack_require__.bind(__webpack_require__, /*! ../errorDialog */ "./assets/bem/ts/service/errorDialog.js")).then(function (_ref) {
    var showErrorDialog = _ref.default;
    showErrorDialog(rejection, lodash__WEBPACK_IMPORTED_MODULE_17___default().get(rejection, 'config.disableErrorDialog', false));
  });
  return Promise.reject(rejection);
});
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (instance);
var useAxios = (0,axios_hooks__WEBPACK_IMPORTED_MODULE_16__.makeUseAxios)({
  axios: instance
});

/***/ }),

/***/ "./assets/bem/ts/service/axios/retry.js":
/*!**********************************************!*\
  !*** ./assets/bem/ts/service/axios/retry.js ***!
  \**********************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* binding */ axiosRetry),
/* harmony export */   exponentialDelay: () => (/* binding */ exponentialDelay),
/* harmony export */   isIdempotentRequestError: () => (/* binding */ isIdempotentRequestError),
/* harmony export */   isNetworkError: () => (/* binding */ isNetworkError),
/* harmony export */   isNetworkOrIdempotentRequestError: () => (/* binding */ isNetworkOrIdempotentRequestError),
/* harmony export */   isRetryableError: () => (/* binding */ isRetryableError),
/* harmony export */   isSafeRequestError: () => (/* binding */ isSafeRequestError)
/* harmony export */ });
/* harmony import */ var core_js_modules_es_array_concat_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! core-js/modules/es.array.concat.js */ "./node_modules/core-js/modules/es.array.concat.js");
/* harmony import */ var core_js_modules_es_array_concat_js__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_array_concat_js__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var core_js_modules_es_object_to_string_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! core-js/modules/es.object.to-string.js */ "./node_modules/core-js/modules/es.object.to-string.js");
/* harmony import */ var core_js_modules_es_object_to_string_js__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_object_to_string_js__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var core_js_modules_es_promise_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! core-js/modules/es.promise.js */ "./node_modules/core-js/modules/es.promise.js");
/* harmony import */ var core_js_modules_es_promise_js__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_promise_js__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var core_js_modules_es_symbol_to_primitive_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! core-js/modules/es.symbol.to-primitive.js */ "./node_modules/core-js/modules/es.symbol.to-primitive.js");
/* harmony import */ var core_js_modules_es_symbol_to_primitive_js__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_symbol_to_primitive_js__WEBPACK_IMPORTED_MODULE_3__);
/* harmony import */ var core_js_modules_es_date_to_primitive_js__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! core-js/modules/es.date.to-primitive.js */ "./node_modules/core-js/modules/es.date.to-primitive.js");
/* harmony import */ var core_js_modules_es_date_to_primitive_js__WEBPACK_IMPORTED_MODULE_4___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_date_to_primitive_js__WEBPACK_IMPORTED_MODULE_4__);
/* harmony import */ var core_js_modules_es_symbol_js__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! core-js/modules/es.symbol.js */ "./node_modules/core-js/modules/es.symbol.js");
/* harmony import */ var core_js_modules_es_symbol_js__WEBPACK_IMPORTED_MODULE_5___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_symbol_js__WEBPACK_IMPORTED_MODULE_5__);
/* harmony import */ var core_js_modules_es_symbol_description_js__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! core-js/modules/es.symbol.description.js */ "./node_modules/core-js/modules/es.symbol.description.js");
/* harmony import */ var core_js_modules_es_symbol_description_js__WEBPACK_IMPORTED_MODULE_6___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_symbol_description_js__WEBPACK_IMPORTED_MODULE_6__);
/* harmony import */ var core_js_modules_es_number_constructor_js__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__(/*! core-js/modules/es.number.constructor.js */ "./node_modules/core-js/modules/es.number.constructor.js");
/* harmony import */ var core_js_modules_es_number_constructor_js__WEBPACK_IMPORTED_MODULE_7___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_number_constructor_js__WEBPACK_IMPORTED_MODULE_7__);
/* harmony import */ var core_js_modules_es_object_keys_js__WEBPACK_IMPORTED_MODULE_8__ = __webpack_require__(/*! core-js/modules/es.object.keys.js */ "./node_modules/core-js/modules/es.object.keys.js");
/* harmony import */ var core_js_modules_es_object_keys_js__WEBPACK_IMPORTED_MODULE_8___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_object_keys_js__WEBPACK_IMPORTED_MODULE_8__);
/* harmony import */ var core_js_modules_es_array_filter_js__WEBPACK_IMPORTED_MODULE_9__ = __webpack_require__(/*! core-js/modules/es.array.filter.js */ "./node_modules/core-js/modules/es.array.filter.js");
/* harmony import */ var core_js_modules_es_array_filter_js__WEBPACK_IMPORTED_MODULE_9___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_array_filter_js__WEBPACK_IMPORTED_MODULE_9__);
/* harmony import */ var core_js_modules_es_object_get_own_property_descriptor_js__WEBPACK_IMPORTED_MODULE_10__ = __webpack_require__(/*! core-js/modules/es.object.get-own-property-descriptor.js */ "./node_modules/core-js/modules/es.object.get-own-property-descriptor.js");
/* harmony import */ var core_js_modules_es_object_get_own_property_descriptor_js__WEBPACK_IMPORTED_MODULE_10___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_object_get_own_property_descriptor_js__WEBPACK_IMPORTED_MODULE_10__);
/* harmony import */ var core_js_modules_web_dom_collections_for_each_js__WEBPACK_IMPORTED_MODULE_11__ = __webpack_require__(/*! core-js/modules/web.dom-collections.for-each.js */ "./node_modules/core-js/modules/web.dom-collections.for-each.js");
/* harmony import */ var core_js_modules_web_dom_collections_for_each_js__WEBPACK_IMPORTED_MODULE_11___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_web_dom_collections_for_each_js__WEBPACK_IMPORTED_MODULE_11__);
/* harmony import */ var core_js_modules_es_object_get_own_property_descriptors_js__WEBPACK_IMPORTED_MODULE_12__ = __webpack_require__(/*! core-js/modules/es.object.get-own-property-descriptors.js */ "./node_modules/core-js/modules/es.object.get-own-property-descriptors.js");
/* harmony import */ var core_js_modules_es_object_get_own_property_descriptors_js__WEBPACK_IMPORTED_MODULE_12___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_object_get_own_property_descriptors_js__WEBPACK_IMPORTED_MODULE_12__);
/* harmony import */ var core_js_modules_es_symbol_iterator_js__WEBPACK_IMPORTED_MODULE_13__ = __webpack_require__(/*! core-js/modules/es.symbol.iterator.js */ "./node_modules/core-js/modules/es.symbol.iterator.js");
/* harmony import */ var core_js_modules_es_symbol_iterator_js__WEBPACK_IMPORTED_MODULE_13___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_symbol_iterator_js__WEBPACK_IMPORTED_MODULE_13__);
/* harmony import */ var core_js_modules_es_array_iterator_js__WEBPACK_IMPORTED_MODULE_14__ = __webpack_require__(/*! core-js/modules/es.array.iterator.js */ "./node_modules/core-js/modules/es.array.iterator.js");
/* harmony import */ var core_js_modules_es_array_iterator_js__WEBPACK_IMPORTED_MODULE_14___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_array_iterator_js__WEBPACK_IMPORTED_MODULE_14__);
/* harmony import */ var core_js_modules_es_string_iterator_js__WEBPACK_IMPORTED_MODULE_15__ = __webpack_require__(/*! core-js/modules/es.string.iterator.js */ "./node_modules/core-js/modules/es.string.iterator.js");
/* harmony import */ var core_js_modules_es_string_iterator_js__WEBPACK_IMPORTED_MODULE_15___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_string_iterator_js__WEBPACK_IMPORTED_MODULE_15__);
/* harmony import */ var core_js_modules_web_dom_collections_iterator_js__WEBPACK_IMPORTED_MODULE_16__ = __webpack_require__(/*! core-js/modules/web.dom-collections.iterator.js */ "./node_modules/core-js/modules/web.dom-collections.iterator.js");
/* harmony import */ var core_js_modules_web_dom_collections_iterator_js__WEBPACK_IMPORTED_MODULE_16___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_web_dom_collections_iterator_js__WEBPACK_IMPORTED_MODULE_16__);
/* harmony import */ var is_retry_allowed__WEBPACK_IMPORTED_MODULE_17__ = __webpack_require__(/*! is-retry-allowed */ "./node_modules/is-retry-allowed/index.js");
/* harmony import */ var is_retry_allowed__WEBPACK_IMPORTED_MODULE_17___default = /*#__PURE__*/__webpack_require__.n(is_retry_allowed__WEBPACK_IMPORTED_MODULE_17__);
function _typeof(o) { "@babel/helpers - typeof"; return _typeof = "function" == typeof Symbol && "symbol" == typeof Symbol.iterator ? function (o) { return typeof o; } : function (o) { return o && "function" == typeof Symbol && o.constructor === Symbol && o !== Symbol.prototype ? "symbol" : typeof o; }, _typeof(o); }
function ownKeys(e, r) { var t = Object.keys(e); if (Object.getOwnPropertySymbols) { var o = Object.getOwnPropertySymbols(e); r && (o = o.filter(function (r) { return Object.getOwnPropertyDescriptor(e, r).enumerable; })), t.push.apply(t, o); } return t; }
function _objectSpread(e) { for (var r = 1; r < arguments.length; r++) { var t = null != arguments[r] ? arguments[r] : {}; r % 2 ? ownKeys(Object(t), !0).forEach(function (r) { _defineProperty(e, r, t[r]); }) : Object.getOwnPropertyDescriptors ? Object.defineProperties(e, Object.getOwnPropertyDescriptors(t)) : ownKeys(Object(t)).forEach(function (r) { Object.defineProperty(e, r, Object.getOwnPropertyDescriptor(t, r)); }); } return e; }
function _defineProperty(obj, key, value) { key = _toPropertyKey(key); if (key in obj) { Object.defineProperty(obj, key, { value: value, enumerable: true, configurable: true, writable: true }); } else { obj[key] = value; } return obj; }
function _toPropertyKey(arg) { var key = _toPrimitive(arg, "string"); return _typeof(key) === "symbol" ? key : String(key); }
function _toPrimitive(input, hint) { if (_typeof(input) !== "object" || input === null) return input; var prim = input[Symbol.toPrimitive]; if (prim !== undefined) { var res = prim.call(input, hint || "default"); if (_typeof(res) !== "object") return res; throw new TypeError("@@toPrimitive must return a primitive value."); } return (hint === "string" ? String : Number)(input); }


















var namespace = 'retry';
var SAFE_HTTP_METHODS = ['get', 'head', 'options'];
var IDEMPOTENT_HTTP_METHODS = SAFE_HTTP_METHODS.concat(['put', 'delete']);
function isNetworkError(error) {
  return !error.response && Boolean(error.code) &&
  // Prevents retrying cancelled requests
  error.code !== 'ECONNABORTED' &&
  // Prevents retrying timed out requests
  is_retry_allowed__WEBPACK_IMPORTED_MODULE_17___default()(error); // Prevents retrying unsafe errors
}

function isRetryableError(error) {
  return error.code !== 'ECONNABORTED' && (!error.response || error.response.status >= 500 && error.response.status <= 599);
}
function isSafeRequestError(error) {
  if (!error.config) {
    // Cannot determine if the request can be retried
    return false;
  }
  return isRetryableError(error) && SAFE_HTTP_METHODS.indexOf(error.config.method) !== -1;
}
function isIdempotentRequestError(error) {
  if (!error.config) {
    // Cannot determine if the request can be retried
    return false;
  }
  return isRetryableError(error) && IDEMPOTENT_HTTP_METHODS.indexOf(error.config.method) !== -1;
}
function isNetworkOrIdempotentRequestError(error) {
  return isNetworkError(error) || isIdempotentRequestError(error);
}
function exponentialDelay() {
  var retryNumber = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : 0;
  var delay = Math.pow(2, retryNumber) * 100;
  var randomSum = delay * 0.2 * Math.random(); // 0-20% of the delay
  return delay + randomSum;
}
function getCurrentState(config) {
  var currentState = config[namespace] || {};
  currentState.retryCount = currentState.retryCount || 0;
  config[namespace] = currentState;
  return currentState;
}
function getRequestOptions(config, defaultOptions) {
  return _objectSpread(_objectSpread({}, defaultOptions), config[namespace]);
}
function noDelay() {
  return 0;
}
function axiosRetry(axios, defaultOptions) {
  axios.interceptors.request.use(function (config) {
    var currentState = getCurrentState(config);
    currentState.lastRequestTime = Date.now();
    return config;
  });
  axios.interceptors.response.use(null, function (error) {
    var config = error.config;

    // If we have no information to retry the request
    if (!config) {
      return Promise.reject(error);
    }
    var _getRequestOptions = getRequestOptions(config, defaultOptions),
      _getRequestOptions$re = _getRequestOptions.retries,
      retries = _getRequestOptions$re === void 0 ? 3 : _getRequestOptions$re,
      _getRequestOptions$re2 = _getRequestOptions.retryCondition,
      retryCondition = _getRequestOptions$re2 === void 0 ? isNetworkOrIdempotentRequestError : _getRequestOptions$re2,
      _getRequestOptions$re3 = _getRequestOptions.retryDelay,
      retryDelay = _getRequestOptions$re3 === void 0 ? noDelay : _getRequestOptions$re3,
      _getRequestOptions$sh = _getRequestOptions.shouldResetTimeout,
      shouldResetTimeout = _getRequestOptions$sh === void 0 ? false : _getRequestOptions$sh;
    var currentState = getCurrentState(config);
    var shouldRetry = retryCondition(error) && currentState.retryCount < retries;
    if (shouldRetry) {
      currentState.retryCount += 1;
      var delay = retryDelay(currentState.retryCount, error);
      if (!shouldResetTimeout && config.timeout && currentState.lastRequestTime) {
        var lastRequestDuration = Date.now() - currentState.lastRequestTime;
        // Minimum 1ms timeout (passing 0 or less to XHR means no timeout)
        config.timeout = Math.max(config.timeout - lastRequestDuration - delay, 1);
      }
      config.transformRequest = [function (data) {
        return data;
      }];
      return new Promise(function (resolve) {
        return setTimeout(function () {
          return resolve(axios(config).catch(function () {
            return null;
          }));
        }, delay);
      });
    }
    return Promise.reject(error);
  });
}

/***/ }),

/***/ "./assets/bem/ts/service/env.ts":
/*!**************************************!*\
  !*** ./assets/bem/ts/service/env.ts ***!
  \**************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   extractOptions: () => (/* binding */ extractOptions),
/* harmony export */   isAndroid: () => (/* binding */ isAndroid),
/* harmony export */   isIos: () => (/* binding */ isIos)
/* harmony export */ });
/* harmony import */ var core_js_modules_es_regexp_exec_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! core-js/modules/es.regexp.exec.js */ "./node_modules/core-js/modules/es.regexp.exec.js");
/* harmony import */ var core_js_modules_es_regexp_exec_js__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_regexp_exec_js__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var core_js_modules_es_string_replace_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! core-js/modules/es.string.replace.js */ "./node_modules/core-js/modules/es.string.replace.js");
/* harmony import */ var core_js_modules_es_string_replace_js__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_string_replace_js__WEBPACK_IMPORTED_MODULE_1__);


function extractOptions() {
  var _env$locale;
  var env = document.body.dataset;
  var defaultLang = 'en';
  var defaultLocale = 'en';
  env.locale;
  var appLocale = ((_env$locale = env.locale) === null || _env$locale === void 0 ? void 0 : _env$locale.replace('_', '-')) || defaultLocale;
  var result = {
    defaultLang: defaultLang,
    defaultLocale: defaultLocale,
    authorized: env.authorized === 'true',
    booking: env.booking === 'true',
    business: env.business === 'true',
    debug: env.debug === 'true',
    enabledTransHelper: env.enabledTransHelper === 'true',
    hasRoleTranslator: env.roleTranslator === 'true',
    impersonated: env.impersonated === 'true',
    lang: env.lang || defaultLang,
    locale: appLocale,
    loadExternalScripts: env.loadExternalScripts || false
  };
  if (env.theme) {
    result.theme = env.theme;
  }
  return result;
}
function isIos() {
  return /iPad|iPhone|iPod/i.test(navigator.userAgent);
}
function isAndroid() {
  return /android/i.test(navigator.userAgent.toLowerCase());
}

/***/ })

}]);
//# sourceMappingURL=data:application/json;charset=utf-8;base64,eyJ2ZXJzaW9uIjozLCJmaWxlIjoiYXNzZXRzX2JlbV90c19zZXJ2aWNlX2F4aW9zX2luZGV4X2pzLWFzc2V0c19iZW1fdHNfc2VydmljZV9lbnZfdHMuanMiLCJtYXBwaW5ncyI6Ijs7Ozs7Ozs7Ozs7Ozs7Ozs7Ozs7Ozs7Ozs7Ozs7Ozs7Ozs7Ozs7Ozs7Ozs7Ozs7Ozs7Ozs7Ozs7Ozs7Ozs7Ozs7Ozs7Ozs7OztBQUEyQztBQUNwQjtBQUNHO0FBQ3lDO0FBRW5FLElBQU1LLFlBQVksR0FBRyxjQUFjO0FBQ25DLElBQU1DLGFBQWEsR0FBRyxlQUFlO0FBQ3JDLElBQU1DLFFBQVEsR0FBR0wsOENBQUssQ0FBQ00sTUFBTSxDQUFDO0VBQzFCQyxPQUFPLEVBQUU7SUFDTCxrQkFBa0IsRUFBRTtFQUN4QjtBQUNKLENBQUMsQ0FBQztBQUNGLElBQUlDLFNBQVMsR0FBRyxJQUFJO0FBRXBCLFNBQVNDLFlBQVlBLENBQUEsRUFBRztFQUNwQixJQUFJRCxTQUFTLEtBQUssSUFBSSxFQUFFO0lBQ3BCQSxTQUFTLEdBQUdFLFFBQVEsQ0FBQ0MsSUFBSSxDQUFDQyxhQUFhLENBQUMseUJBQXlCLENBQUMsQ0FBQ0MsT0FBTztFQUM5RTtFQUVBLE9BQU9MLFNBQVM7QUFDcEI7QUFFQSxTQUFTTSxhQUFhQSxDQUFDQyxRQUFRLEVBQUU7RUFDN0IsSUFBSUEsUUFBUSxJQUFJQSxRQUFRLENBQUNSLE9BQU8sSUFBSVEsUUFBUSxDQUFDUixPQUFPLENBQUNKLFlBQVksQ0FBQyxFQUFFO0lBQ2hFYSxPQUFPLENBQUNDLEdBQUcsQ0FBQyxtQkFBbUIsRUFBRUYsUUFBUSxDQUFDUixPQUFPLENBQUNKLFlBQVksQ0FBQyxDQUFDO0lBQ2hFSyxTQUFTLEdBQUdPLFFBQVEsQ0FBQ1IsT0FBTyxDQUFDSixZQUFZLENBQUM7RUFDOUM7QUFDSjtBQUVBLFNBQVNlLFlBQVlBLENBQUNILFFBQVEsRUFBRTtFQUM1QixPQUFPaEIsa0RBQUssQ0FBQ2dCLFFBQVEsYUFBQUssTUFBQSxDQUFhaEIsYUFBYSxNQUFHLENBQUMsS0FBSyxNQUFN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SUFDWFgsTUFBTSxDQUFDVyxLQUFLLEdBQUduQixHQUFHLENBQUNtQixLQUFLO0VBQzVCO0VBQ0EsT0FBT1gsTUFBTTtBQUNqQjtBQUNPLFNBQVNZLEtBQUtBLENBQUEsRUFBRztFQUNwQixPQUFPLG1CQUFtQixDQUFDQyxJQUFJLENBQUNDLFNBQVMsQ0FBQ0MsU0FBUyxDQUFDO0FBQ3hEO0FBQ08sU0FBU0MsU0FBU0EsQ0FBQSxFQUFHO0VBQ3hCLE9BQU8sVUFBVSxDQUFDSCxJQUFJLENBQUNDLFNBQVMsQ0FBQ0MsU0FBUyxDQUFDRSxXQUFXLENBQUMsQ0FBQyxDQUFDO0FBQzdEIiwic291cmNlcyI6WyJ3ZWJwYWNrOi8vQXdhcmRXYWxsZXQvLi9hc3NldHMvYmVtL3RzL3NlcnZpY2UvYXhpb3MvaW5kZXguanMiLCJ3ZWJwYWNrOi8vQXdhcmRXYWxsZXQvLi9hc3NldHMvYmVtL3RzL3NlcnZpY2UvYXhpb3MvcmV0cnkuanMiLCJ3ZWJwYWNrOi8vQXdhcmRXYWxsZXQvLi9hc3NldHMvYmVtL3RzL3NlcnZpY2UvZW52LnRzIl0sInNvdXJjZXNDb250ZW50IjpbImltcG9ydCB7IG1ha2VVc2VBeGlvcyB9IGZyb20gJ2F4aW9zLWhvb2tzJztcbmltcG9ydCBfIGZyb20gJ2xvZGFzaCc7XG5pbXBvcnQgYXhpb3MgZnJvbSAnYXhpb3MnO1xuaW1wb3J0IHJldHJ5LCB7IGlzTmV0d29ya09ySWRlbXBvdGVudFJlcXVlc3RFcnJvciB9IGZyb20gJy4vcmV0cnknO1xuXG5jb25zdCBYX1hTUkZfVE9LRU4gPSAneC14c3JmLXRva2VuJztcbmNvbnN0IFhfWFNSRl9GQUlMRUQgPSAneC14c3JmLWZhaWxlZCc7XG5jb25zdCBpbnN0YW5jZSA9IGF4aW9zLmNyZWF0ZSh7XG4gICAgaGVhZGVyczoge1xuICAgICAgICAnWC1SZXF1ZXN0ZWQtV2l0aCc6ICdYTUxIdHRwUmVxdWVzdCcsXG4gICAgfSxcbn0pO1xubGV0IHhzcmZUb2tlbiA9IG51bGw7XG5cbmZ1bmN0aW9uIGdldFhzcmZUb2tlbigpIHtcbiAgICBpZiAoeHNyZlRva2VuID09PSBudWxsKSB7XG4gICAgICAgIHhzcmZUb2tlbiA9IGRvY3VtZW50LmhlYWQucXVlcnlTZWxlY3RvcignbWV0YVtuYW1lPVwiY3NyZi10b2tlblwiXScpLmNvbnRlbnQ7XG4gICAgfVxuXG4gICAgcmV0dXJuIHhzcmZUb2tlbjtcbn1cblxuZnVuY3Rpb24gc2F2ZVhzcmZUb2tlbihyZXNwb25zZSkge1xuICAgIGlmIChyZXNwb25zZSAmJiByZXNwb25zZS5oZWFkZXJzICYmIHJlc3BvbnNlLmhlYWRlcnNbWF9YU1JGX1RPS0VOXSkge1xuICAgICAgICBjb25zb2xlLmxvZygnc2F2aW5nIHhzcmYgdG9rZW4nLCByZXNwb25zZS5oZWFkZXJzW1hfWFNSRl9UT0tFTl0pO1xuICAgICAgICB4c3JmVG9rZW4gPSByZXNwb25zZS5oZWFkZXJzW1hfWFNSRl9UT0tFTl07XG4gICAgfVxufVxuXG5mdW5jdGlvbiBpc1hzcmZGYWlsZWQocmVzcG9uc2UpIHtcbiAgICByZXR1cm4gXy5nZXQocmVzcG9uc2UsIGBoZWFkZXJzWyR7WF9YU1JGX0ZBSUxFRH1dYCkgPT09ICd0cnVlJztcbn1cblxuZnVuY3Rpb24gY2hlY2tYc3JmRmFpbGVkKHJlc3BvbnNlKSB7XG4gICAgaWYgKGlzWHNyZkZhaWxlZChyZXNwb25zZSkpIHtcbiAgICAgICAgY29uc29sZS5sb2coJ3hzcmYgZmFpbGVkJyk7XG5cbiAgICAgICAgcmV0dXJuIGZhbHNlO1xuICAgIH1cblxuICAgIHJldHVybiB0cnVlO1xufVxuXG5yZXRyeShpbnN0YW5jZSwge1xuICAgIHJldHJpZXM6IDMsXG4gICAgcmV0cnlDb25kaXRpb246IChlcnJvcikgPT4ge1xuICAgICAgICBpZiAoaXNOZXR3b3JrT3JJZGVtcG90ZW50UmVxdWVzdEVycm9yKGVycm9yKSkge1xuICAgICAgICAgICAgY29uc29sZS5sb2coJ25ldHdvcmsgb3IgaWRlbXBvdGVudCByZXF1ZXN0IGVycm9yJyk7XG5cbiAgICAgICAgICAgIHJldHVybiB0cnVlO1xuICAgICAgICB9XG5cbiAgICAgICAgcmV0dXJuICFjaGVja1hzcmZGYWlsZWQoXy5oYXMoZXJyb3IsICdyZXNwb25zZScpID8gZXJyb3IucmVzcG9uc2UgOiBlcnJvcik7XG4gICAgfSxcbn0pO1xuXG5pbnN0YW5jZS5pbnRlcmNlcHRvcnMucmVxdWVzdC51c2UoKGNvbmZpZykgPT4ge1xuICAgIGNvbnN0IGhlYWRlcnMgPSB7fTtcbiAgICBjb25zdCB4c3JmVG9rZW4gPSBnZXRYc3JmVG9rZW4oKTtcblxuICAgIGlmICh4c3JmVG9rZW4pIHtcbiAgICAgICAgaGVhZGVyc1tYX1hTUkZfVE9LRU4udG9VcHBlckNhc2UoKV0gPSB4c3JmVG9rZW47XG4gICAgfVxuXG4gICAgY29uZmlnLmhlYWRlcnMgPSB7IC4uLmNvbmZpZy5oZWFkZXJzLCAuLi5oZWFkZXJzIH07XG5cbiAgICByZXR1cm4gY29uZmlnO1xufSk7XG5pbnN0YW5jZS5pbnRlcmNlcHRvcnMucmVzcG9uc2UudXNlKFxuICAgIChyZXNwb25zZSkgPT4ge1xuICAgICAgICBzYXZlWHNyZlRva2VuKHJlc3BvbnNlKTtcblxuICAgICAgICByZXR1cm4gcmVzcG9uc2U7XG4gICAgfSxcbiAgICAocmVqZWN0aW9uKSA9PiB7XG4gICAgICAgIGNvbnN0IHsgcmVzcG9uc2UgfSA9IHJlamVjdGlvbjtcblxuICAgICAgICBzYXZlWHNyZlRva2VuKHJlc3BvbnNlKTtcblxuICAgICAgICAvLyByZWRpcmVjdCB0byBsb2dpblxuICAgICAgICBpZiAoXG4gICAgICAgICAgICBfLmdldChyZWplY3Rpb24sICdyZXNwb25zZS5kYXRhJykgPT09ICd1bmF1dGhvcml6ZWQnIHx8XG4gICAgICAgICAgICBfLmdldChyZWplY3Rpb24sICdyZXNwb25zZS5oZWFkZXJzW1wiYWpheC1lcnJvclwiXScpID09PSAndW5hdXRob3JpemVkJ1xuICAgICAgICApIHtcbiAgICAgICAgICAgIHRyeSB7XG4gICAgICAgICAgICAgICAgaWYgKHdpbmRvdy5wYXJlbnQgIT09IHdpbmRvdykge1xuICAgICAgICAgICAgICAgICAgICBwYXJlbnQubG9jYXRpb24uaHJlZiA9ICcvc2VjdXJpdHkvdW5hdXRob3JpemVkLnBocD9CYWNrVG89JyArIGVuY29kZVVSSShwYXJlbnQubG9jYXRpb24uaHJlZik7XG4gICAgICAgICAgICAgICAgICAgIHJldHVybiBQcm9taXNlLnJlc29sdmUoKTtcbiAgICAgICAgICAgICAgICB9XG4gICAgICAgICAgICB9IGNhdGNoIChlKSB7XG4gICAgICAgICAgICAgICAgLy8gZXNsaW50LWRpc2FibGUtbmV4dC1saW5lXG4gICAgICAgICAgICB9XG4gICAgICAgICAgICBsb2NhdGlvbi5ocmVmID0gJy9zZWN1cml0eS91bmF1dGhvcml6ZWQucGhwP0JhY2tUbz0nICsgZW5jb2RlVVJJKGxvY2F0aW9uLmhyZWYpO1xuICAgICAgICAgICAgcmV0dXJuIFByb21pc2UucmVzb2x2ZSgpO1xuICAgICAgICB9XG5cbiAgICAgICAgaW1wb3J0KCcuLi9lcnJvckRpYWxvZycpLnRoZW4oKHsgZGVmYXVsdDogc2hvd0Vycm9yRGlhbG9nIH0pID0+IHtcbiAgICAgICAgICAgIHNob3dFcnJvckRpYWxvZyhyZWplY3Rpb24sIF8uZ2V0KHJlamVjdGlvbiwgJ2NvbmZpZy5kaXNhYmxlRXJyb3JEaWFsb2cnLCBmYWxzZSkpO1xuICAgICAgICB9KTtcblxuICAgICAgICByZXR1cm4gUHJvbWlzZS5yZWplY3QocmVqZWN0aW9uKTtcbiAgICB9LFxuKTtcblxuZXhwb3J0IGRlZmF1bHQgaW5zdGFuY2U7XG5cbmV4cG9ydCBjb25zdCB1c2VBeGlvcyA9IG1ha2VVc2VBeGlvcyh7IGF4aW9zOiBpbnN0YW5jZSB9KTtcbiIsImltcG9ydCBpc1JldHJ5QWxsb3dlZCBmcm9tICdpcy1yZXRyeS1hbGxvd2VkJztcblxuY29uc3QgbmFtZXNwYWNlID0gJ3JldHJ5JztcblxuY29uc3QgU0FGRV9IVFRQX01FVEhPRFMgPSBbJ2dldCcsICdoZWFkJywgJ29wdGlvbnMnXTtcbmNvbnN0IElERU1QT1RFTlRfSFRUUF9NRVRIT0RTID0gU0FGRV9IVFRQX01FVEhPRFMuY29uY2F0KFsncHV0JywgJ2RlbGV0ZSddKTtcblxuZXhwb3J0IGZ1bmN0aW9uIGlzTmV0d29ya0Vycm9yKGVycm9yKSB7XG4gICAgcmV0dXJuIChcbiAgICAgICAgIWVycm9yLnJlc3BvbnNlICYmXG4gICAgICAgIEJvb2xlYW4oZXJyb3IuY29kZSkgJiYgLy8gUHJldmVudHMgcmV0cnlpbmcgY2FuY2VsbGVkIHJlcXVlc3RzXG4gICAgICAgIGVycm9yLmNvZGUgIT09ICdFQ09OTkFCT1JURUQnICYmIC8vIFByZXZlbnRzIHJldHJ5aW5nIHRpbWVkIG91dCByZXF1ZXN0c1xuICAgICAgICBpc1JldHJ5QWxsb3dlZChlcnJvcilcbiAgICApOyAvLyBQcmV2ZW50cyByZXRyeWluZyB1bnNhZmUgZXJyb3JzXG59XG5cbmV4cG9ydCBmdW5jdGlvbiBpc1JldHJ5YWJsZUVycm9yKGVycm9yKSB7XG4gICAgcmV0dXJuIChcbiAgICAgICAgZXJyb3IuY29kZSAhPT0gJ0VDT05OQUJPUlRFRCcgJiZcbiAgICAgICAgKCFlcnJvci5yZXNwb25zZSB8fCAoZXJyb3IucmVzcG9uc2Uuc3RhdHVzID49IDUwMCAmJiBlcnJvci5yZXNwb25zZS5zdGF0dXMgPD0gNTk5KSlcbiAgICApO1xufVxuXG5leHBvcnQgZnVuY3Rpb24gaXNTYWZlUmVxdWVzdEVycm9yKGVycm9yKSB7XG4gICAgaWYgKCFlcnJvci5jb25maWcpIHtcbiAgICAgICAgLy8gQ2Fubm90IGRldGVybWluZSBpZiB0aGUgcmVxdWVzdCBjYW4gYmUgcmV0cmllZFxuICAgICAgICByZXR1cm4gZmFsc2U7XG4gICAgfVxuXG4gICAgcmV0dXJuIGlzUmV0cnlhYmxlRXJyb3IoZXJyb3IpICYmIFNBRkVfSFRUUF9NRVRIT0RTLmluZGV4T2YoZXJyb3IuY29uZmlnLm1ldGhvZCkgIT09IC0xO1xufVxuXG5leHBvcnQgZnVuY3Rpb24gaXNJZGVtcG90ZW50UmVxdWVzdEVycm9yKGVycm9yKSB7XG4gICAgaWYgKCFlcnJvci5jb25maWcpIHtcbiAgICAgICAgLy8gQ2Fubm90IGRldGVybWluZSBpZiB0aGUgcmVxdWVzdCBjYW4gYmUgcmV0cmllZFxuICAgICAgICByZXR1cm4gZmFsc2U7XG4gICAgfVxuXG4gICAgcmV0dXJuIGlzUmV0cnlhYmxlRXJyb3IoZXJyb3IpICYmIElERU1QT1RFTlRfSFRUUF9NRVRIT0RTLmluZGV4T2YoZXJyb3IuY29uZmlnLm1ldGhvZCkgIT09IC0xO1xufVxuXG5leHBvcnQgZnVuY3Rpb24gaXNOZXR3b3JrT3JJZGVtcG90ZW50UmVxdWVzdEVycm9yKGVycm9yKSB7XG4gICAgcmV0dXJuIGlzTmV0d29ya0Vycm9yKGVycm9yKSB8fCBpc0lkZW1wb3RlbnRSZXF1ZXN0RXJyb3IoZXJyb3IpO1xufVxuXG5leHBvcnQgZnVuY3Rpb24gZXhwb25lbnRpYWxEZWxheShyZXRyeU51bWJlciA9IDApIHtcbiAgICBjb25zdCBkZWxheSA9IE1hdGgucG93KDIsIHJldHJ5TnVtYmVyKSAqIDEwMDtcbiAgICBjb25zdCByYW5kb21TdW0gPSBkZWxheSAqIDAuMiAqIE1hdGgucmFuZG9tKCk7IC8vIDAtMjAlIG9mIHRoZSBkZWxheVxuICAgIHJldHVybiBkZWxheSArIHJhbmRvbVN1bTtcbn1cblxuZnVuY3Rpb24gZ2V0Q3VycmVudFN0YXRlKGNvbmZpZykge1xuICAgIGNvbnN0IGN1cnJlbnRTdGF0ZSA9IGNvbmZpZ1tuYW1lc3BhY2VdIHx8IHt9O1xuICAgIGN1cnJlbnRTdGF0ZS5yZXRyeUNvdW50ID0gY3VycmVudFN0YXRlLnJldHJ5Q291bnQgfHwgMDtcbiAgICBjb25maWdbbmFtZXNwYWNlXSA9IGN1cnJlbnRTdGF0ZTtcbiAgICByZXR1cm4gY3VycmVudFN0YXRlO1xufVxuXG5mdW5jdGlvbiBnZXRSZXF1ZXN0T3B0aW9ucyhjb25maWcsIGRlZmF1bHRPcHRpb25zKSB7XG4gICAgcmV0dXJuIHsgLi4uZGVmYXVsdE9wdGlvbnMsIC4uLmNvbmZpZ1tuYW1lc3BhY2VdIH07XG59XG5cbmZ1bmN0aW9uIG5vRGVsYXkoKSB7XG4gICAgcmV0dXJuIDA7XG59XG5cbmV4cG9ydCBkZWZhdWx0IGZ1bmN0aW9uIGF4aW9zUmV0cnkoYXhpb3MsIGRlZmF1bHRPcHRpb25zKSB7XG4gICAgYXhpb3MuaW50ZXJjZXB0b3JzLnJlcXVlc3QudXNlKChjb25maWcpID0+IHtcbiAgICAgICAgY29uc3QgY3VycmVudFN0YXRlID0gZ2V0Q3VycmVudFN0YXRlKGNvbmZpZyk7XG4gICAgICAgIGN1cnJlbnRTdGF0ZS5sYXN0UmVxdWVzdFRpbWUgPSBEYXRlLm5vdygpO1xuICAgICAgICByZXR1cm4gY29uZmlnO1xuICAgIH0pO1xuXG4gICAgYXhpb3MuaW50ZXJjZXB0b3JzLnJlc3BvbnNlLnVzZShudWxsLCAoZXJyb3IpID0+IHtcbiAgICAgICAgY29uc3QgY29uZmlnID0gZXJyb3IuY29uZmlnO1xuXG4gICAgICAgIC8vIElmIHdlIGhhdmUgbm8gaW5mb3JtYXRpb24gdG8gcmV0cnkgdGhlIHJlcXVlc3RcbiAgICAgICAgaWYgKCFjb25maWcpIHtcbiAgICAgICAgICAgIHJldHVybiBQcm9taXNlLnJlamVjdChlcnJvcik7XG4gICAgICAgIH1cblxuICAgICAgICBjb25zdCB7XG4gICAgICAgICAgICByZXRyaWVzID0gMyxcbiAgICAgICAgICAgIHJldHJ5Q29uZGl0aW9uID0gaXNOZXR3b3JrT3JJZGVtcG90ZW50UmVxdWVzdEVycm9yLFxuICAgICAgICAgICAgcmV0cnlEZWxheSA9IG5vRGVsYXksXG4gICAgICAgICAgICBzaG91bGRSZXNldFRpbWVvdXQgPSBmYWxzZSxcbiAgICAgICAgfSA9IGdldFJlcXVlc3RPcHRpb25zKGNvbmZpZywgZGVmYXVsdE9wdGlvbnMpO1xuXG4gICAgICAgIGNvbnN0IGN1cnJlbnRTdGF0ZSA9IGdldEN1cnJlbnRTdGF0ZShjb25maWcpO1xuXG4gICAgICAgIGNvbnN0IHNob3VsZFJldHJ5ID0gcmV0cnlDb25kaXRpb24oZXJyb3IpICYmIGN1cnJlbnRTdGF0ZS5yZXRyeUNvdW50IDwgcmV0cmllcztcblxuICAgICAgICBpZiAoc2hvdWxkUmV0cnkpIHtcbiAgICAgICAgICAgIGN1cnJlbnRTdGF0ZS5yZXRyeUNvdW50ICs9IDE7XG4gICAgICAgICAgICBjb25zdCBkZWxheSA9IHJldHJ5RGVsYXkoY3VycmVudFN0YXRlLnJldHJ5Q291bnQsIGVycm9yKTtcblxuICAgICAgICAgICAgaWYgKCFzaG91bGRSZXNldFRpbWVvdXQgJiYgY29uZmlnLnRpbWVvdXQgJiYgY3VycmVudFN0YXRlLmxhc3RSZXF1ZXN0VGltZSkge1xuICAgICAgICAgICAgICAgIGNvbnN0IGxhc3RSZXF1ZXN0RHVyYXRpb24gPSBEYXRlLm5vdygpIC0gY3VycmVudFN0YXRlLmxhc3RSZXF1ZXN0VGltZTtcbiAgICAgICAgICAgICAgICAvLyBNaW5pbXVtIDFtcyB0aW1lb3V0IChwYXNzaW5nIDAgb3IgbGVzcyB0byBYSFIgbWVhbnMgbm8gdGltZW91dClcbiAgICAgICAgICAgICAgICBjb25maWcudGltZW91dCA9IE1hdGgubWF4KGNvbmZpZy50aW1lb3V0IC0gbGFzdFJlcXVlc3REdXJhdGlvbiAtIGRlbGF5LCAxKTtcbiAgICAgICAgICAgIH1cblxuICAgICAgICAgICAgY29uZmlnLnRyYW5zZm9ybVJlcXVlc3QgPSBbKGRhdGEpID0+IGRhdGFdO1xuXG4gICAgICAgICAgICByZXR1cm4gbmV3IFByb21pc2UoKHJlc29sdmUpID0+IHNldFRpbWVvdXQoKCkgPT4gcmVzb2x2ZShheGlvcyhjb25maWcpLmNhdGNoKCgpID0+IG51bGwpKSwgZGVsYXkpKTtcbiAgICAgICAgfVxuXG4gICAgICAgIHJldHVybiBQcm9taXNlLnJlamVjdChlcnJvcik7XG4gICAgfSk7XG59XG4iLCJleHBvcnQgZnVuY3Rpb24gZXh0cmFjdE9wdGlvbnMoKSB7XG4gICAgY29uc3QgZW52ID0gZG9jdW1lbnQuYm9keS5kYXRhc2V0O1xuICAgIGNvbnN0IGRlZmF1bHRMYW5nID0gJ2VuJztcbiAgICBjb25zdCBkZWZhdWx0TG9jYWxlID0gJ2VuJztcbiAgICBlbnYubG9jYWxlO1xuICAgIGNvbnN0IGFwcExvY2FsZSA9IGVudi5sb2NhbGU/LnJlcGxhY2UoJ18nLCAnLScpIHx8IGRlZmF1bHRMb2NhbGU7XG4gICAgY29uc3QgcmVzdWx0ID0ge1xuICAgICAgICBkZWZhdWx0TGFuZzogZGVmYXVsdExhbmcsXG4gICAgICAgIGRlZmF1bHRMb2NhbGU6IGRlZmF1bHRMb2NhbGUsXG4gICAgICAgIGF1dGhvcml6ZWQ6IGVudi5hdXRob3JpemVkID09PSAndHJ1ZScsXG4gICAgICAgIGJvb2tpbmc6IGVudi5ib29raW5nID09PSAndHJ1ZScsXG4gICAgICAgIGJ1c2luZXNzOiBlbnYuYnVzaW5lc3MgPT09ICd0cnVlJyxcbiAgICAgICAgZGVidWc6IGVudi5kZWJ1ZyA9PT0gJ3RydWUnLFxuICAgICAgICBlbmFibGVkVHJhbnNIZWxwZXI6IGVudi5lbmFibGVkVHJhbnNIZWxwZXIgPT09ICd0cnVlJyxcbiAgICAgICAgaGFzUm9sZVRyYW5zbGF0b3I6IGVudi5yb2xlVHJhbnNsYXRvciA9PT0gJ3RydWUnLFxuICAgICAgICBpbXBlcnNvbmF0ZWQ6IGVudi5pbXBlcnNvbmF0ZWQgPT09ICd0cnVlJyxcbiAgICAgICAgbGFuZzogZW52LmxhbmcgfHwgZGVmYXVsdExhbmcsXG4gICAgICAgIGxvY2FsZTogYXBwTG9jYWxlLFxuICAgICAgICBsb2FkRXh0ZXJuYWxTY3JpcHRzOiBlbnYubG9hZEV4dGVybmFsU2NyaXB0cyB8fCBmYWxzZSxcbiAgICB9O1xuICAgIGlmIChlbnYudGhlbWUpIHtcbiAgICAgICAgcmVzdWx0LnRoZW1lID0gZW52LnRoZW1lO1xuICAgIH1cbiAgICByZXR1cm4gcmVzdWx0O1xufVxuZXhwb3J0IGZ1bmN0aW9uIGlzSW9zKCkge1xuICAgIHJldHVybiAvaVBhZHxpUGhvbmV8aVBvZC9pLnRlc3QobmF2aWdhdG9yLnVzZXJBZ2VudCk7XG59XG5leHBvcnQgZnVuY3Rpb24gaXNBbmRyb2lkKCkge1xuICAgIHJldHVybiAvYW5kcm9pZC9pLnRlc3QobmF2aWdhdG9yLnVzZXJBZ2VudC50b0xvd2VyQ2FzZSgpKTtcbn1cbiJdLCJuYW1lcyI6WyJtYWtlVXNlQXhpb3MiLCJfIiwiYXhpb3MiLCJyZXRyeSIsImlzTmV0d29ya09ySWRlbXBvdGVudFJlcXVlc3RFcnJvciIsIlhfWFNSRl9UT0tFTiIsIlhfWFNSRl9GQUlMRUQiLCJpbnN0YW5jZSIsImNyZWF0ZSIsImhlYWRlcnMiLCJ4c3JmVG9rZW4iLCJnZXRYc3JmVG9rZW4iLCJkb2N1bWVudCIsImhlYWQiLCJxdWVyeVNlbGVjdG9yIiwiY29udGVudCIsInNhdmVYc3JmVG9rZW4iLCJyZXNwb25zZSIsImNvbnNvbGUiLCJsb2ciLCJpc1hzcmZGYWlsZWQiLCJnZXQiLCJjb25jYXQiLCJjaGVja1hzcmZGYWlsZWQiLCJyZXRyaWVzIiwicmV0cnlDb25kaXRpb24iLCJlcnJvciIsImhhcyIsImludGVyY2VwdG9ycyIsInJlcXVlc3QiLCJ1c2UiLCJjb25maWciLCJ0b1VwcGVyQ2FzZSIsIl9vYmplY3RTcHJlYWQiLCJyZWplY3Rpb24iLCJ3aW5kb3ciLCJwYXJlbnQiLCJsb2NhdGlvbiIsImhyZWYiLCJlbmNvZGVVUkkiLCJQcm9taXNlIiwicmVzb2x2ZSIsImUiLCJ0aGVuIiwiX3JlZiIsInNob3dFcnJvckRpYWxvZyIsImRlZmF1bHQiLCJyZWplY3QiLCJ1c2VBeGlvcyIsImlzUmV0cnlBbGxvd2VkIiwibmFtZXNwYWNlIiwiU0FGRV9IVFRQX01FVEhPRFMiLCJJREVNUE9URU5UX0hUVFBfTUVUSE9EUyIsImlzTmV0d29ya0Vycm9yIiwiQm9vbGVhbiIsImNvZGUiLCJpc1JldHJ5YWJsZUVycm9yIiwic3RhdHVzIiwiaXNTYWZlUmVxdWVzdEVycm9yIiwiaW5kZXhPZiIsIm1ldGhvZCIsImlzSWRlbXBvdGVudFJlcXVlc3RFcnJvciIsImV4cG9uZW50aWFsRGVsYXkiLCJyZXRyeU51bWJlciIsImFyZ3VtZW50cyIsImxlbmd0aCIsInVuZGVmaW5lZCIsImRlbGF5IiwiTWF0aCIsInBvdyIsInJhbmRvbVN1bSIsInJhbmRvbSIsImdldEN1cnJlbnRTdGF0ZSIsImN1cnJlbnRTdGF0ZSIsInJldHJ5Q291bnQiLCJnZXRSZXF1ZXN0T3B0aW9ucyIsImRlZmF1bHRPcHRpb25zIiwibm9EZWxheSIsImF4aW9zUmV0cnkiLCJsYXN0UmVxdWVzdFRpbWUiLCJEYXRlIiwibm93IiwiX2dldFJlcXVlc3RPcHRpb25zIiwiX2dldFJlcXVlc3RPcHRpb25zJHJlIiwiX2dldFJlcXVlc3RPcHRpb25zJHJlMiIsIl9nZXRSZXF1ZXN0T3B0aW9ucyRyZTMiLCJyZXRyeURlbGF5IiwiX2dldFJlcXVlc3RPcHRpb25zJHNoIiwic2hvdWxkUmVzZXRUaW1lb3V0Iiwic2hvdWxkUmV0cnkiLCJ0aW1lb3V0IiwibGFzdFJlcXVlc3REdXJhdGlvbiIsIm1heCIsInRyYW5zZm9ybVJlcXVlc3QiLCJkYXRhIiwic2V0VGltZW91dCIsImNhdGNoIiwiZXh0cmFjdE9wdGlvbnMiLCJfZW52JGxvY2FsZSIsImVudiIsImJvZHkiLCJkYXRhc2V0IiwiZGVmYXVsdExhbmciLCJkZWZhdWx0TG9jYWxlIiwibG9jYWxlIiwiYXBwTG9jYWxlIiwicmVwbGFjZSIsInJlc3VsdCIsImF1dGhvcml6ZWQiLCJib29raW5nIiwiYnVzaW5lc3MiLCJkZWJ1ZyIsImVuYWJsZWRUcmFuc0hlbHBlciIsImhhc1JvbGVUcmFuc2xhdG9yIiwicm9sZVRyYW5zbGF0b3IiLCJpbXBlcnNvbmF0ZWQiLCJsYW5nIiwibG9hZEV4dGVybmFsU2NyaXB0cyIsInRoZW1lIiwiaXNJb3MiLCJ0ZXN0IiwibmF2aWdhdG9yIiwidXNlckFnZW50IiwiaXNBbmRyb2lkIiwidG9Mb3dlckNhc2UiXSwic291cmNlUm9vdCI6IiJ9