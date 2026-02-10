"use strict";
(self["webpackChunkAwardWallet"] = self["webpackChunkAwardWallet"] || []).push([["page-landing"],{

/***/ "./assets/bem/block/button-platform/index.ts":
/*!***************************************************!*\
  !*** ./assets/bem/block/button-platform/index.ts ***!
  \***************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _icon_platform__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ../icon-platform */ "./assets/bem/block/icon-platform/index.ts");
/* harmony import */ var _button_platform_scss__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./button-platform.scss */ "./assets/bem/block/button-platform/button-platform.scss");



/***/ }),

/***/ "./assets/bem/block/button/index.ts":
/*!******************************************!*\
  !*** ./assets/bem/block/button/index.ts ***!
  \******************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _button_scss__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./button.scss */ "./assets/bem/block/button/button.scss");


/***/ }),

/***/ "./assets/bem/block/icon-platform/index.ts":
/*!*************************************************!*\
  !*** ./assets/bem/block/icon-platform/index.ts ***!
  \*************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _icon_platform_scss__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./icon-platform.scss */ "./assets/bem/block/icon-platform/icon-platform.scss");


/***/ }),

/***/ "./assets/bem/block/icon-program-kind/index.ts":
/*!*****************************************************!*\
  !*** ./assets/bem/block/icon-program-kind/index.ts ***!
  \*****************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _icon_program_kind_scss__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./icon-program-kind.scss */ "./assets/bem/block/icon-program-kind/icon-program-kind.scss");


/***/ }),

/***/ "./assets/bem/block/logo/index.ts":
/*!****************************************!*\
  !*** ./assets/bem/block/logo/index.ts ***!
  \****************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _logo_scss__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./logo.scss */ "./assets/bem/block/logo/logo.scss");
/* harmony import */ var _ts_service_on_ready__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ../../ts/service/on-ready */ "./assets/bem/ts/service/on-ready.ts");


(0,_ts_service_on_ready__WEBPACK_IMPORTED_MODULE_1__["default"])(function () {
  var contextEvent = new Event('logo:context-menu');
  var logo = document.querySelector('.logo');
  if (logo) {
    logo.addEventListener('contextmenu', function (event) {
      event.preventDefault();
      var target = event.target;
      if (target.tagName === 'IMG') {
        document.dispatchEvent(contextEvent);
      }
    });
  }
});

/***/ }),

/***/ "./assets/bem/block/page/landing/index.ts":
/*!************************************************!*\
  !*** ./assets/bem/block/page/landing/index.ts ***!
  \************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _ts_starter__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ../../../ts/starter */ "./assets/bem/ts/starter.ts");
/* harmony import */ var _button__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ../../button */ "./assets/bem/block/button/index.ts");
/* harmony import */ var _button_platform__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ../../button-platform */ "./assets/bem/block/button-platform/index.ts");
/* harmony import */ var _icon_program_kind__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ../../icon-program-kind */ "./assets/bem/block/icon-program-kind/index.ts");
/* harmony import */ var _logo__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! ../../logo */ "./assets/bem/block/logo/index.ts");
/* harmony import */ var _popup_media_logos__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! ../../popup-media-logos */ "./assets/bem/block/popup-media-logos/index.ts");
/* harmony import */ var _page_landing_scss__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! ./page-landing.scss */ "./assets/bem/block/page/landing/page-landing.scss");
/* harmony import */ var _ts_service_on_ready__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__(/*! ../../../ts/service/on-ready */ "./assets/bem/ts/service/on-ready.ts");
/* harmony import */ var _sticky_header__WEBPACK_IMPORTED_MODULE_8__ = __webpack_require__(/*! ./sticky-header */ "./assets/bem/block/page/landing/sticky-header.ts");









(0,_ts_service_on_ready__WEBPACK_IMPORTED_MODULE_7__["default"])(function () {
  var dataset = document.body.dataset;
  if (dataset.inviteEmail) {
    window.inviteEmail = dataset.inviteEmail;
  }
  if (dataset.inviteFn) {
    window.firstName = dataset.inviteFn;
  }
  if (dataset.inviteLn) {
    window.lastName = dataset.inviteLn;
  }
  if (dataset.inviteCode) {
    window.inviteCode = dataset.inviteCode;
  }
  (0,_sticky_header__WEBPACK_IMPORTED_MODULE_8__["default"])();
});

/***/ }),

/***/ "./assets/bem/block/page/landing/sticky-header.ts":
/*!********************************************************!*\
  !*** ./assets/bem/block/page/landing/sticky-header.ts ***!
  \********************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* export default binding */ __WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var core_js_modules_es_string_starts_with_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! core-js/modules/es.string.starts-with.js */ "./node_modules/core-js/modules/es.string.starts-with.js");
/* harmony import */ var core_js_modules_es_string_starts_with_js__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_string_starts_with_js__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var core_js_modules_es_regexp_exec_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! core-js/modules/es.regexp.exec.js */ "./node_modules/core-js/modules/es.regexp.exec.js");
/* harmony import */ var core_js_modules_es_regexp_exec_js__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_regexp_exec_js__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var core_js_modules_es_string_replace_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! core-js/modules/es.string.replace.js */ "./node_modules/core-js/modules/es.string.replace.js");
/* harmony import */ var core_js_modules_es_string_replace_js__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_string_replace_js__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var core_js_modules_es_object_to_string_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! core-js/modules/es.object.to-string.js */ "./node_modules/core-js/modules/es.object.to-string.js");
/* harmony import */ var core_js_modules_es_object_to_string_js__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_object_to_string_js__WEBPACK_IMPORTED_MODULE_3__);
/* harmony import */ var core_js_modules_es_regexp_to_string_js__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! core-js/modules/es.regexp.to-string.js */ "./node_modules/core-js/modules/es.regexp.to-string.js");
/* harmony import */ var core_js_modules_es_regexp_to_string_js__WEBPACK_IMPORTED_MODULE_4___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_regexp_to_string_js__WEBPACK_IMPORTED_MODULE_4__);
/* harmony import */ var _ts_service_bem__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! ../../../ts/service/bem */ "./assets/bem/ts/service/bem.ts");
/* harmony import */ var lodash_throttle__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! lodash/throttle */ "./node_modules/lodash/throttle.js");
/* harmony import */ var lodash_throttle__WEBPACK_IMPORTED_MODULE_6___default = /*#__PURE__*/__webpack_require__.n(lodash_throttle__WEBPACK_IMPORTED_MODULE_6__);







var container;
var stickyHeaderElem;
var stickyHeaderContentElem;
var backgroundColor;
function computeBackgroundColor() {
  if (!stickyHeaderElem) {
    return;
  }
  stickyHeaderElem.style.transition = 'none';
  stickyHeaderElem.style.backgroundColor = '';
  backgroundColor = window.getComputedStyle(stickyHeaderElem).backgroundColor;
  stickyHeaderElem.style.transition = '';
}
function prepareStickyHeader() {
  container = document.querySelector('.' + (0,_ts_service_bem__WEBPACK_IMPORTED_MODULE_5__.bemClass)('page-landing', 'container'));
  stickyHeaderElem = document.querySelector('.' + (0,_ts_service_bem__WEBPACK_IMPORTED_MODULE_5__.bemClass)('page-landing', 'sticky-header'));
  if (stickyHeaderElem) {
    stickyHeaderContentElem = stickyHeaderElem.querySelector('.' + (0,_ts_service_bem__WEBPACK_IMPORTED_MODULE_5__.bemClass)('page-landing', 'header'));
  }
  computeBackgroundColor();
}
function tickColor() {
  if (!stickyHeaderElem || !stickyHeaderContentElem) {
    return;
  }
  var alpha = Math.min(window.scrollY / stickyHeaderContentElem.clientHeight, 1);
  var newBackgroundColor;
  if (backgroundColor.startsWith('rgba(')) {
    newBackgroundColor = backgroundColor.replace(/[^,]+(?=\))/, alpha.toString());
  } else if (backgroundColor.startsWith('rgb(')) {
    newBackgroundColor = backgroundColor.replace('rgb(', 'rgba(').replace(')', ", ".concat(alpha, ")"));
  } else {
    throw new Error('Unknown background color format');
  }
  stickyHeaderElem.style.backgroundColor = newBackgroundColor;
}
function tickClass() {
  if (!stickyHeaderContentElem) {
    return;
  }
  var smallClass = (0,_ts_service_bem__WEBPACK_IMPORTED_MODULE_5__.bemClass)('page-landing', 'header', 'small');
  var smallHeader = stickyHeaderContentElem.classList.contains(smallClass);
  if (!smallHeader && window.scrollY > stickyHeaderContentElem.clientHeight / 2) {
    if (!stickyHeaderContentElem.classList.contains(smallClass)) {
      stickyHeaderContentElem.classList.add(smallClass);
    }
  } else if (smallHeader && window.scrollY <= stickyHeaderContentElem.clientHeight / 2) {
    if (stickyHeaderContentElem.classList.contains(smallClass)) {
      stickyHeaderContentElem.classList.remove(smallClass);
    }
  }
}
function tickContainerSize() {
  if (!container || !stickyHeaderElem) {
    return;
  }
  stickyHeaderElem.style.width = container.clientWidth.toString() + 'px';
}
function tick() {
  tickColor();
  tickClass();
}
/* harmony default export */ function __WEBPACK_DEFAULT_EXPORT__() {
  prepareStickyHeader();
  window.addEventListener('scroll', lodash_throttle__WEBPACK_IMPORTED_MODULE_6___default()(function () {
    tick();
  }, 50));
  window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', function () {
    computeBackgroundColor();
    tick();
  });
  tickContainerSize();
  tick();
  setTimeout(function () {
    if (stickyHeaderElem) {
      stickyHeaderElem.style.display = 'block';
    }
  }, 50);
  window.addEventListener('resize', lodash_throttle__WEBPACK_IMPORTED_MODULE_6___default()(tickContainerSize, 50));
}
;

/***/ }),

/***/ "./assets/bem/block/popup-media-logos/index.ts":
/*!*****************************************************!*\
  !*** ./assets/bem/block/popup-media-logos/index.ts ***!
  \*****************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _popup_media_logos_scss__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./popup-media-logos.scss */ "./assets/bem/block/popup-media-logos/popup-media-logos.scss");
/* harmony import */ var _ts_service_dialog__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ../../ts/service/dialog */ "./assets/bem/ts/service/dialog.ts");
/* harmony import */ var _ts_service_router__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ../../ts/service/router */ "./assets/bem/ts/service/router.ts");
/* harmony import */ var _ts_service_translator__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ../../ts/service/translator */ "./assets/bem/ts/service/translator.ts");
/* harmony import */ var _ts_service_on_ready__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! ../../ts/service/on-ready */ "./assets/bem/ts/service/on-ready.ts");
/* provided dependency */ var $ = __webpack_require__(/*! jquery */ "./web/assets/common/vendors/jquery/dist/jquery.js");





(0,_ts_service_on_ready__WEBPACK_IMPORTED_MODULE_4__["default"])(function () {
  var popup = document.querySelector('.popup-media-logos');
  if (popup) {
    document.addEventListener('logo:context-menu', function () {
      var d = _ts_service_dialog__WEBPACK_IMPORTED_MODULE_1__["default"].createNamed('mediaLogos', $(popup), {
        autoOpen: true,
        modal: true,
        minWidth: 550,
        buttons: [{
          text: _ts_service_translator__WEBPACK_IMPORTED_MODULE_3__["default"].trans('button.close'),
          class: 'btn-silver',
          click: function click() {
            d.close();
          }
        }, {
          text: _ts_service_translator__WEBPACK_IMPORTED_MODULE_3__["default"].trans('button.proceed_to_page'),
          class: 'btn-blue',
          click: function click() {
            window.location.href = _ts_service_router__WEBPACK_IMPORTED_MODULE_2__["default"].generate('aw_media_logos');
          }
        }]
      });
    });
  }
});

/***/ }),

/***/ "./assets/bem/ts/service/bem.ts":
/*!**************************************!*\
  !*** ./assets/bem/ts/service/bem.ts ***!
  \**************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   bem: () => (/* binding */ bem),
/* harmony export */   bemClass: () => (/* binding */ bemClass)
/* harmony export */ });
/* harmony import */ var core_js_modules_es_array_concat_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! core-js/modules/es.array.concat.js */ "./node_modules/core-js/modules/es.array.concat.js");
/* harmony import */ var core_js_modules_es_array_concat_js__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_array_concat_js__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var core_js_modules_es_object_to_string_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! core-js/modules/es.object.to-string.js */ "./node_modules/core-js/modules/es.object.to-string.js");
/* harmony import */ var core_js_modules_es_object_to_string_js__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_object_to_string_js__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var core_js_modules_web_dom_collections_for_each_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! core-js/modules/web.dom-collections.for-each.js */ "./node_modules/core-js/modules/web.dom-collections.for-each.js");
/* harmony import */ var core_js_modules_web_dom_collections_for_each_js__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_web_dom_collections_for_each_js__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var core_js_modules_es_array_join_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! core-js/modules/es.array.join.js */ "./node_modules/core-js/modules/es.array.join.js");
/* harmony import */ var core_js_modules_es_array_join_js__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_array_join_js__WEBPACK_IMPORTED_MODULE_3__);
/* harmony import */ var _env__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! ./env */ "./assets/bem/ts/service/env.ts");





// returns a string of classes for a BEM component
function bem(block, element) {
  var modifiers = arguments.length > 2 && arguments[2] !== undefined ? arguments[2] : [];
  var opts = (0,_env__WEBPACK_IMPORTED_MODULE_4__.extractOptions)();
  var classes = [];
  var component;
  if (element) {
    classes.push(component = "".concat(block, "__").concat(element));
  } else {
    classes.push(component = block);
  }
  // add the theme modifier
  if (opts.theme) {
    modifiers.push(opts.theme);
  }
  // add the modifiers
  modifiers.forEach(function (modifier) {
    classes.push("".concat(component, "--").concat(modifier));
  });
  return classes.join(' ');
}
function bemClass(block, element, modifier) {
  if (modifier) {
    return "".concat(block, "__").concat(element, "--").concat(modifier);
  } else if (element) {
    return "".concat(block, "__").concat(element);
  } else {
    return block;
  }
}

/***/ }),

/***/ "./assets/bem/ts/service/dialog.ts":
/*!*****************************************!*\
  !*** ./assets/bem/ts/service/dialog.ts ***!
  \*****************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var lib_dialog__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! lib/dialog */ "./web/assets/awardwalletnewdesign/js/lib/dialog.js");
/* harmony import */ var lib_dialog__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(lib_dialog__WEBPACK_IMPORTED_MODULE_0__);

/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = ((lib_dialog__WEBPACK_IMPORTED_MODULE_0___default()));

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

/***/ "./assets/bem/block/button-platform/button-platform.scss":
/*!***************************************************************!*\
  !*** ./assets/bem/block/button-platform/button-platform.scss ***!
  \***************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
// extracted by mini-css-extract-plugin


/***/ }),

/***/ "./assets/bem/block/button/button.scss":
/*!*********************************************!*\
  !*** ./assets/bem/block/button/button.scss ***!
  \*********************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
// extracted by mini-css-extract-plugin


/***/ }),

/***/ "./assets/bem/block/icon-platform/icon-platform.scss":
/*!***********************************************************!*\
  !*** ./assets/bem/block/icon-platform/icon-platform.scss ***!
  \***********************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
// extracted by mini-css-extract-plugin


/***/ }),

/***/ "./assets/bem/block/icon-program-kind/icon-program-kind.scss":
/*!*******************************************************************!*\
  !*** ./assets/bem/block/icon-program-kind/icon-program-kind.scss ***!
  \*******************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
// extracted by mini-css-extract-plugin


/***/ }),

/***/ "./assets/bem/block/logo/logo.scss":
/*!*****************************************!*\
  !*** ./assets/bem/block/logo/logo.scss ***!
  \*****************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
// extracted by mini-css-extract-plugin


/***/ }),

/***/ "./assets/bem/block/page/landing/page-landing.scss":
/*!*********************************************************!*\
  !*** ./assets/bem/block/page/landing/page-landing.scss ***!
  \*********************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
// extracted by mini-css-extract-plugin


/***/ }),

/***/ "./assets/bem/block/popup-media-logos/popup-media-logos.scss":
/*!*******************************************************************!*\
  !*** ./assets/bem/block/popup-media-logos/popup-media-logos.scss ***!
  \*******************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
// extracted by mini-css-extract-plugin


/***/ })

},
/******/ __webpack_require__ => { // webpackRuntimeModules
/******/ var __webpack_exec__ = (moduleId) => (__webpack_require__(__webpack_require__.s = moduleId))
/******/ __webpack_require__.O(0, ["vendors-node_modules_core-js_internals_classof_js-node_modules_core-js_internals_define-built-in_js","vendors-node_modules_core-js_internals_array-slice-simple_js-node_modules_core-js_internals_f-4bac30","vendors-node_modules_core-js_modules_es_string_replace_js","vendors-node_modules_core-js_modules_es_object_keys_js-node_modules_core-js_modules_es_regexp-599444","vendors-node_modules_core-js_internals_array-method-is-strict_js-node_modules_core-js_modules-ea2489","vendors-node_modules_core-js_modules_es_array_find_js-node_modules_core-js_modules_es_array_f-112b41","vendors-node_modules_core-js_modules_es_promise_js-node_modules_core-js_modules_web_dom-colle-054322","vendors-node_modules_core-js_internals_string-trim_js-node_modules_core-js_internals_this-num-d4bf43","vendors-node_modules_core-js_modules_es_number_to-fixed_js-node_modules_intl_index_js","vendors-node_modules_core-js_modules_es_string_starts-with_js-node_modules_lodash_throttle_js","assets_bem_ts_service_translator_ts","web_assets_common_vendors_jquery_dist_jquery_js","web_assets_common_vendors_jquery-ui_jquery-ui_min_js","assets_bem_ts_service_router_ts","web_assets_awardwalletnewdesign_js_lib_dialog_js"], () => (__webpack_exec__("./assets/bem/block/page/landing/index.ts")));
/******/ var __webpack_exports__ = __webpack_require__.O();
/******/ }
]);
//# sourceMappingURL=data:application/json;charset=utf-8;base64,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LENBQUM7SUFDL0IsOGxCQUEwRCxDQUNyREMsSUFBSSxDQUFDLFVBQUFDLElBQUEsRUFBdUI7TUFBQSxJQUFYQyxJQUFJLEdBQUFELElBQUEsQ0FBYkUsT0FBTztNQUFlRCxJQUFJLENBQUMsQ0FBQztJQUFFLENBQUMsRUFBRSxZQUFNO01BQUVKLE9BQU8sQ0FBQ00sS0FBSyxDQUFDLDRCQUE0QixDQUFDO0lBQUUsQ0FBQyxDQUFDO0VBQ3pHO0FBQ0osQ0FBQyxDQUFDOzs7Ozs7Ozs7OztBQ1RGOzs7Ozs7Ozs7Ozs7QUNBQTs7Ozs7Ozs7Ozs7O0FDQUE7Ozs7Ozs7Ozs7OztBQ0FBOzs7Ozs7Ozs7Ozs7QUNBQTs7Ozs7Ozs7Ozs7O0FDQUE7Ozs7Ozs7Ozs7OztBQ0FBIiwic291cmNlcyI6WyJ3ZWJwYWNrOi8vQXdhcmRXYWxsZXQvLi9hc3NldHMvYmVtL2Jsb2NrL2J1dHRvbi1wbGF0Zm9ybS9pbmRleC50cyIsIndlYnBhY2s6Ly9Bd2FyZFdhbGxldC8uL2Fzc2V0cy9iZW0vYmxvY2svYnV0dG9uL2luZGV4LnRzIiwid2VicGFjazovL0F3YXJkV2FsbGV0Ly4vYXNzZXRzL2JlbS9ibG9jay9pY29uLXBsYXRmb3JtL2luZGV4LnRzIiwid2VicGFjazovL0F3YXJkV2FsbGV0Ly4vYXNzZXRzL2JlbS9ibG9jay9pY29uLXByb2dyYW0ta2luZC9pbmRleC50cyIsIndlYnBhY2s6Ly9Bd2FyZFdhbGxldC8uL2Fzc2V0cy9iZW0vYmxvY2svbG9nby9pbmRleC50cyIsIndlYnBhY2s6Ly9Bd2FyZFdhbGxldC8uL2Fzc2V0cy9iZW0vYmxvY2svcGFnZS9sYW5kaW5nL2luZGV4LnRzIiwid2VicGFjazovL0F3YXJkV2FsbGV0Ly4vYXNzZXRzL2JlbS9ibG9jay9wYWdlL2xhbmRpbmcvc3RpY2t5LWhlYWRlci50cyIsIndlYnBhY2s6Ly9Bd2FyZFdhbGxldC8uL2Fzc2V0cy9iZW0vYmxvY2svcG9wdXAtbWVkaWEtbG9nb3MvaW5kZXgudHMiLCJ3ZWJwYWNrOi8vQXdhcmRXYWxsZXQvLi9hc3NldHMvYmVtL3RzL3NlcnZpY2UvYmVtLnRzIiwid2VicGFjazovL0F3YXJkV2FsbGV0Ly4vYXNzZXRzL2JlbS90cy9zZXJ2aWNlL2RpYWxvZy50cyIsIndlYnBhY2s6Ly9Bd2FyZFdhbGxldC8uL2Fzc2V0cy9iZW0vdHMvc2VydmljZS9lbnYudHMiLCJ3ZWJwYWNrOi8vQXdhcmRXYWxsZXQvLi9hc3NldHMvYmVtL3RzL3NlcnZpY2Uvb24tcmVhZHkudHMiLCJ3ZWJwYWNrOi8vQXdhcmRXYWxsZXQvLi9hc3NldHMvYmVtL3RzL3N0YXJ0ZXIudHMiLCJ3ZWJwYWNrOi8vQXdhcmRXYWxsZXQvLi9hc3NldHMvYmVtL2Jsb2NrL2J1dHRvbi1wbGF0Zm9ybS9idXR0b24tcGxhdGZvcm0uc2Nzcz8yNDFlIiwid2VicGFjazovL0F3YXJkV2FsbGV0Ly4vYXNzZXRzL2JlbS9ibG9jay9idXR0b24vYnV0dG9uLnNjc3M/MWVhNyIsIndlYnBhY2s6Ly9Bd2FyZFdhbGxldC8uL2Fzc2V0cy9iZW0vYmxvY2svaWNvbi1wbGF0Zm9ybS9pY29uLXBsYXRmb3JtLnNjc3M/NzUxNCIsIndlYnBhY2s6Ly9Bd2FyZFdhbGxldC8uL2Fzc2V0cy9iZW0vYmxvY2svaWNvbi1wcm9ncmFtLWtpbmQvaWNvbi1wcm9ncmFtLWtpbmQuc2Nzcz80ZjcwIiwid2VicGFjazovL0F3YXJkV2FsbGV0Ly4vYXNzZXRzL2JlbS9ibG9jay9sb2dvL2xvZ28uc2Nzcz9lODQyIiwid2VicGFjazovL0F3YXJkV2FsbGV0Ly4vYXNzZXRzL2JlbS9ibG9jay9wYWdlL2xhbmRpbmcvcGFnZS1sYW5kaW5nLnNjc3M/MWZlNyIsIndlYnBhY2s6Ly9Bd2FyZFdhbGxldC8uL2Fzc2V0cy9iZW0vYmxvY2svcG9wdXAtbWVkaWEtbG9nb3MvcG9wdXAtbWVkaWEtbG9nb3Muc2Nzcz85MjIwIl0sInNvdXJjZXNDb250ZW50IjpbImltcG9ydCAnLi4vaWNvbi1wbGF0Zm9ybSc7XG5pbXBvcnQgJy4vYnV0dG9uLXBsYXRmb3JtLnNjc3MnO1xuIiwiaW1wb3J0ICcuL2J1dHRvbi5zY3NzJztcbiIsImltcG9ydCAnLi9pY29uLXBsYXRmb3JtLnNjc3MnO1xuIiwiaW1wb3J0ICcuL2ljb24tcHJvZ3JhbS1raW5kLnNjc3MnO1xuIiwiaW1wb3J0ICcuL2xvZ28uc2Nzcyc7XG5pbXBvcnQgb25SZWFkeSBmcm9tICcuLi8uLi90cy9zZXJ2aWNlL29uLXJlYWR5Jztcbm9uUmVhZHkoZnVuY3Rpb24gKCkge1xuICAgIGNvbnN0IGNvbnRleHRFdmVudCA9IG5ldyBFdmVudCgnbG9nbzpjb250ZXh0LW1lbnUnKTtcbiAgICBjb25zdCBsb2dvID0gZG9jdW1lbnQucXVlcnlTZWxlY3RvcignLmxvZ28nKTtcbiAgICBpZiAobG9nbykge1xuICAgICAgICBsb2dvLmFkZEV2ZW50TGlzdGVuZXIoJ2NvbnRleHRtZW51JywgZnVuY3Rpb24gKGV2ZW50KSB7XG4gICAgICAgICAgICBldmVudC5wcmV2ZW50RGVmYXVsdCgpO1xuICAgICAgICAgICAgY29uc3QgdGFyZ2V0ID0gZXZlbnQudGFyZ2V0O1xuICAgICAgICAgICAgaWYgKHRhcmdldC50YWdOYW1lID09PSAnSU1HJykge1xuICAgICAgICAgICAgICAgIGRvY3VtZW50LmRpc3BhdGNoRXZlbnQoY29udGV4dEV2ZW50KTtcbiAgICAgICAgICAgIH1cbiAgICAgICAgfSk7XG4gICAgfVxufSk7XG4iLCJpbXBvcnQgJy4uLy4uLy4uL3RzL3N0YXJ0ZXInO1xuaW1wb3J0ICcuLi8uLi9idXR0b24nO1xuaW1wb3J0ICcuLi8uLi9idXR0b24tcGxhdGZvcm0nO1xuaW1wb3J0ICcuLi8uLi9pY29uLXByb2dyYW0ta2luZCc7XG5pbXBvcnQgJy4uLy4uL2xvZ28nO1xuaW1wb3J0ICcuLi8uLi9wb3B1cC1tZWRpYS1sb2dvcyc7XG5pbXBvcnQgJy4vcGFnZS1sYW5kaW5nLnNjc3MnO1xuaW1wb3J0IG9uUmVhZHkgZnJvbSAnLi4vLi4vLi4vdHMvc2VydmljZS9vbi1yZWFkeSc7XG5pbXBvcnQgc3RpY2t5SGVhZGVyIGZyb20gJy4vc3RpY2t5LWhlYWRlcic7XG5vblJlYWR5KGZ1bmN0aW9uICgpIHtcbiAgICBjb25zdCBkYXRhc2V0ID0gZG9jdW1lbnQuYm9keS5kYXRhc2V0O1xuICAgIGlmIChkYXRhc2V0Lmludml0ZUVtYWlsKSB7XG4gICAgICAgIHdpbmRvdy5pbnZpdGVFbWFpbCA9IGRhdGFzZXQuaW52aXRlRW1haWw7XG4gICAgfVxuICAgIGlmIChkYXRhc2V0Lmludml0ZUZuKSB7XG4gICAgICAgIHdpbmRvdy5maXJzdE5hbWUgPSBkYXRhc2V0Lmludml0ZUZuO1xuICAgIH1cbiAgICBpZiAoZGF0YXNldC5pbnZpdGVMbikge1xuICAgICAgICB3aW5kb3cubGFzdE5hbWUgPSBkYXRhc2V0Lmludml0ZUxuO1xuICAgIH1cbiAgICBpZiAoZGF0YXNldC5pbnZpdGVDb2RlKSB7XG4gICAgICAgIHdpbmRvdy5pbnZpdGVDb2RlID0gZGF0YXNldC5pbnZpdGVDb2RlO1xuICAgIH1cbiAgICBzdGlja3lIZWFkZXIoKTtcbn0pO1xuIiwiaW1wb3J0IHsgYmVtQ2xhc3MgfSBmcm9tICcuLi8uLi8uLi90cy9zZXJ2aWNlL2JlbSc7XG5pbXBvcnQgdGhyb3R0bGUgZnJvbSAnbG9kYXNoL3Rocm90dGxlJztcbmxldCBjb250YWluZXI7XG5sZXQgc3RpY2t5SGVhZGVyRWxlbTtcbmxldCBzdGlja3lIZWFkZXJDb250ZW50RWxlbTtcbmxldCBiYWNrZ3JvdW5kQ29sb3I7XG5mdW5jdGlvbiBjb21wdXRlQmFja2dyb3VuZENvbG9yKCkge1xuICAgIGlmICghc3RpY2t5SGVhZGVyRWxlbSkge1xuICAgICAgICByZXR1cm47XG4gICAgfVxuICAgIHN0aWNreUhlYWRlckVsZW0uc3R5bGUudHJhbnNpdGlvbiA9ICdub25lJztcbiAgICBzdGlja3lIZWFkZXJFbGVtLnN0eWxlLmJhY2tncm91bmRDb2xvciA9ICcnO1xuICAgIGJhY2tncm91bmRDb2xvciA9IHdpbmRvdy5nZXRDb21wdXRlZFN0eWxlKHN0aWNreUhlYWRlckVsZW0pLmJhY2tncm91bmRDb2xvcjtcbiAgICBzdGlja3lIZWFkZXJFbGVtLnN0eWxlLnRyYW5zaXRpb24gPSAnJztcbn1cbmZ1bmN0aW9uIHByZXBhcmVTdGlja3lIZWFkZXIoKSB7XG4gICAgY29udGFpbmVyID0gZG9jdW1lbnQucXVlcnlTZWxlY3RvcignLicgKyBiZW1DbGFzcygncGFnZS1sYW5kaW5nJywgJ2NvbnRhaW5lcicpKTtcbiAgICBzdGlja3lIZWFkZXJFbGVtID0gZG9jdW1lbnQucXVlcnlTZWxlY3RvcignLicgKyBiZW1DbGFzcygncGFnZS1sYW5kaW5nJywgJ3N0aWNreS1oZWFkZXInKSk7XG4gICAgaWYgKHN0aWNreUhlYWRlckVsZW0pIHtcbiAgICAgICAgc3RpY2t5SGVhZGVyQ29udGVudEVsZW0gPSBzdGlja3lIZWFkZXJFbGVtLnF1ZXJ5U2VsZWN0b3IoJy4nICsgYmVtQ2xhc3MoJ3BhZ2UtbGFuZGluZycsICdoZWFkZXInKSk7XG4gICAgfVxuICAgIGNvbXB1dGVCYWNrZ3JvdW5kQ29sb3IoKTtcbn1cbmZ1bmN0aW9uIHRpY2tDb2xvcigpIHtcbiAgICBpZiAoIXN0aWNreUhlYWRlckVsZW0gfHwgIXN0aWNreUhlYWRlckNvbnRlbnRFbGVtKSB7XG4gICAgICAgIHJldHVybjtcbiAgICB9XG4gICAgY29uc3QgYWxwaGEgPSBNYXRoLm1pbih3aW5kb3cuc2Nyb2xsWSAvIHN0aWNreUhlYWRlckNvbnRlbnRFbGVtLmNsaWVudEhlaWdodCwgMSk7XG4gICAgbGV0IG5ld0JhY2tncm91bmRDb2xvcjtcbiAgICBpZiAoYmFja2dyb3VuZENvbG9yLnN0YXJ0c1dpdGgoJ3JnYmEoJykpIHtcbiAgICAgICAgbmV3QmFja2dyb3VuZENvbG9yID0gYmFja2dyb3VuZENvbG9yLnJlcGxhY2UoL1teLF0rKD89XFwpKS8sIGFscGhhLnRvU3RyaW5nKCkpO1xuICAgIH1cbiAgICBlbHNlIGlmIChiYWNrZ3JvdW5kQ29sb3Iuc3RhcnRzV2l0aCgncmdiKCcpKSB7XG4gICAgICAgIG5ld0JhY2tncm91bmRDb2xvciA9IGJhY2tncm91bmRDb2xvci5yZXBsYWNlKCdyZ2IoJywgJ3JnYmEoJykucmVwbGFjZSgnKScsIGAsICR7YWxwaGF9KWApO1xuICAgIH1cbiAgICBlbHNlIHtcbiAgICAgICAgdGhyb3cgbmV3IEVycm9yKCdVbmtub3duIGJhY2tncm91bmQgY29sb3IgZm9ybWF0Jyk7XG4gICAgfVxuICAgIHN0aWNreUhlYWRlckVsZW0uc3R5bGUuYmFja2dyb3VuZENvbG9yID0gbmV3QmFja2dyb3VuZENvbG9yO1xufVxuZnVuY3Rpb24gdGlja0NsYXNzKCkge1xuICAgIGlmICghc3RpY2t5SGVhZGVyQ29udGVudEVsZW0pIHtcbiAgICAgICAgcmV0dXJuO1xuICAgIH1cbiAgICBjb25zdCBzbWFsbENsYXNzID0gYmVtQ2xhc3MoJ3BhZ2UtbGFuZGluZycsICdoZWFkZXInLCAnc21hbGwnKTtcbiAgICBjb25zdCBzbWFsbEhlYWRlciA9IHN0aWNreUhlYWRlckNvbnRlbnRFbGVtLmNsYXNzTGlzdC5jb250YWlucyhzbWFsbENsYXNzKTtcbiAgICBpZiAoIXNtYWxsSGVhZGVyICYmIHdpbmRvdy5zY3JvbGxZID4gKHN0aWNreUhlYWRlckNvbnRlbnRFbGVtLmNsaWVudEhlaWdodCAvIDIpKSB7XG4gICAgICAgIGlmICghc3RpY2t5SGVhZGVyQ29udGVudEVsZW0uY2xhc3NMaXN0LmNvbnRhaW5zKHNtYWxsQ2xhc3MpKSB7XG4gICAgICAgICAgICBzdGlja3lIZWFkZXJDb250ZW50RWxlbS5jbGFzc0xpc3QuYWRkKHNtYWxsQ2xhc3MpO1xuICAgICAgICB9XG4gICAgfVxuICAgIGVsc2UgaWYgKHNtYWxsSGVhZGVyICYmIHdpbmRvdy5zY3JvbGxZIDw9IChzdGlja3lIZWFkZXJDb250ZW50RWxlbS5jbGllbnRIZWlnaHQgLyAyKSkge1xuICAgICAgICBpZiAoc3RpY2t5SGVhZGVyQ29udGVudEVsZW0uY2xhc3NMaXN0LmNvbnRhaW5zKHNtYWxsQ2xhc3MpKSB7XG4gICAgICAgICAgICBzdGlja3lIZWFkZXJDb250ZW50RWxlbS5jbGFzc0xpc3QucmVtb3ZlKHNtYWxsQ2xhc3MpO1xuICAgICAgICB9XG4gICAgfVxufVxuZnVuY3Rpb24gdGlja0NvbnRhaW5lclNpemUoKSB7XG4gICAgaWYgKCFjb250YWluZXIgfHwgIXN0aWNreUhlYWRlckVsZW0pIHtcbiAgICAgICAgcmV0dXJuO1xuICAgIH1cbiAgICBzdGlja3lIZWFkZXJFbGVtLnN0eWxlLndpZHRoID0gY29udGFpbmVyLmNsaWVudFdpZHRoLnRvU3RyaW5nKCkgKyAncHgnO1xufVxuZnVuY3Rpb24gdGljaygpIHtcbiAgICB0aWNrQ29sb3IoKTtcbiAgICB0aWNrQ2xhc3MoKTtcbn1cbmV4cG9ydCBkZWZhdWx0IGZ1bmN0aW9uICgpIHtcbiAgICBwcmVwYXJlU3RpY2t5SGVhZGVyKCk7XG4gICAgd2luZG93LmFkZEV2ZW50TGlzdGVuZXIoJ3Njcm9sbCcsIHRocm90dGxlKCgpID0+IHtcbiAgICAgICAgdGljaygpO1xuICAgIH0sIDUwKSk7XG4gICAgd2luZG93Lm1hdGNoTWVkaWEoJyhwcmVmZXJzLWNvbG9yLXNjaGVtZTogZGFyayknKS5hZGRFdmVudExpc3RlbmVyKCdjaGFuZ2UnLCAoKSA9PiB7XG4gICAgICAgIGNvbXB1dGVCYWNrZ3JvdW5kQ29sb3IoKTtcbiAgICAgICAgdGljaygpO1xuICAgIH0pO1xuICAgIHRpY2tDb250YWluZXJTaXplKCk7XG4gICAgdGljaygpO1xuICAgIHNldFRpbWVvdXQoKCkgPT4ge1xuICAgICAgICBpZiAoc3RpY2t5SGVhZGVyRWxlbSkge1xuICAgICAgICAgICAgc3RpY2t5SGVhZGVyRWxlbS5zdHlsZS5kaXNwbGF5ID0gJ2Jsb2NrJztcbiAgICAgICAgfVxuICAgIH0sIDUwKTtcbiAgICB3aW5kb3cuYWRkRXZlbnRMaXN0ZW5lcigncmVzaXplJywgdGhyb3R0bGUodGlja0NvbnRhaW5lclNpemUsIDUwKSk7XG59XG47XG4iLCJpbXBvcnQgJy4vcG9wdXAtbWVkaWEtbG9nb3Muc2Nzcyc7XG5pbXBvcnQgRGlhbG9nIGZyb20gJy4uLy4uL3RzL3NlcnZpY2UvZGlhbG9nJztcbmltcG9ydCBSb3V0ZXIgZnJvbSAnLi4vLi4vdHMvc2VydmljZS9yb3V0ZXInO1xuaW1wb3J0IFRyYW5zbGF0b3IgZnJvbSAnLi4vLi4vdHMvc2VydmljZS90cmFuc2xhdG9yJztcbmltcG9ydCBvblJlYWR5IGZyb20gJy4uLy4uL3RzL3NlcnZpY2Uvb24tcmVhZHknO1xub25SZWFkeShmdW5jdGlvbiAoKSB7XG4gICAgY29uc3QgcG9wdXAgPSBkb2N1bWVudC5xdWVyeVNlbGVjdG9yKCcucG9wdXAtbWVkaWEtbG9nb3MnKTtcbiAgICBpZiAocG9wdXApIHtcbiAgICAgICAgZG9jdW1lbnQuYWRkRXZlbnRMaXN0ZW5lcignbG9nbzpjb250ZXh0LW1lbnUnLCAoKSA9PiB7XG4gICAgICAgICAgICBjb25zdCBkID0gRGlhbG9nLmNyZWF0ZU5hbWVkKCdtZWRpYUxvZ29zJywgJChwb3B1cCksIHtcbiAgICAgICAgICAgICAgICBhdXRvT3BlbjogdHJ1ZSxcbiAgICAgICAgICAgICAgICBtb2RhbDogdHJ1ZSxcbiAgICAgICAgICAgICAgICBtaW5XaWR0aDogNTUwLFxuICAgICAgICAgICAgICAgIGJ1dHRvbnM6IFtcbiAgICAgICAgICAgICAgICAgICAge1xuICAgICAgICAgICAgICAgICAgICAgICAgdGV4dDogVHJhbnNsYXRvci50cmFucygnYnV0dG9uLmNsb3NlJyksXG4gICAgICAgICAgICAgICAgICAgICAgICBjbGFzczogJ2J0bi1zaWx2ZXInLFxuICAgICAgICAgICAgICAgICAgICAgICAgY2xpY2s6ICgpID0+IHtcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICBkLmNsb3NlKCk7XG4gICAgICAgICAgICAgICAgICAgICAgICB9XG4gICAgICAgICAgICAgICAgICAgIH0sXG4gICAgICAgICAgICAgICAgICAgIHtcbiAgICAgICAgICAgICAgICAgICAgICAgIHRleHQ6IFRyYW5zbGF0b3IudHJhbnMoJ2J1dHRvbi5wcm9jZWVkX3RvX3BhZ2UnKSxcbiAgICAgICAgICAgICAgICAgICAgICAgIGNsYXNzOiAnYnRuLWJsdWUnLFxuICAgICAgICAgICAgICAgICAgICAgICAgY2xpY2s6ICgpID0+IHtcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICB3aW5kb3cubG9jYXRpb24uaHJlZiA9IFJvdXRlci5nZW5lcmF0ZSgnYXdfbWVkaWFfbG9nb3MnKTtcbiAgICAgICAgICAgICAgICAgICAgICAgIH1cbiAgICAgICAgICAgICAgICAgICAgfVxuICAgICAgICAgICAgICAgIF1cbiAgICAgICAgICAgIH0pO1xuICAgICAgICB9KTtcbiAgICB9XG59KTtcbiIsImltcG9ydCB7IGV4dHJhY3RPcHRpb25zIH0gZnJvbSAnLi9lbnYnO1xuLy8gcmV0dXJucyBhIHN0cmluZyBvZiBjbGFzc2VzIGZvciBhIEJFTSBjb21wb25lbnRcbmV4cG9ydCBmdW5jdGlvbiBiZW0oYmxvY2ssIGVsZW1lbnQsIG1vZGlmaWVycyA9IFtdKSB7XG4gICAgY29uc3Qgb3B0cyA9IGV4dHJhY3RPcHRpb25zKCk7XG4gICAgY29uc3QgY2xhc3NlcyA9IFtdO1xuICAgIGxldCBjb21wb25lbnQ7XG4gICAgaWYgKGVsZW1lbnQpIHtcbiAgICAgICAgY2xhc3Nlcy5wdXNoKGNvbXBvbmVudCA9IGAke2Jsb2NrfV9fJHtlbGVtZW50fWApO1xuICAgIH1cbiAgICBlbHNlIHtcbiAgICAgICAgY2xhc3Nlcy5wdXNoKGNvbXBvbmVudCA9IGJsb2NrKTtcbiAgICB9XG4gICAgLy8gYWRkIHRoZSB0aGVtZSBtb2RpZmllclxuICAgIGlmIChvcHRzLnRoZW1lKSB7XG4gICAgICAgIG1vZGlmaWVycy5wdXNoKG9wdHMudGhlbWUpO1xuICAgIH1cbiAgICAvLyBhZGQgdGhlIG1vZGlmaWVyc1xuICAgIG1vZGlmaWVycy5mb3JFYWNoKG1vZGlmaWVyID0+IHtcbiAgICAgICAgY2xhc3Nlcy5wdXNoKGAke2NvbXBvbmVudH0tLSR7bW9kaWZpZXJ9YCk7XG4gICAgfSk7XG4gICAgcmV0dXJuIGNsYXNzZXMuam9pbignICcpO1xufVxuZXhwb3J0IGZ1bmN0aW9uIGJlbUNsYXNzKGJsb2NrLCBlbGVtZW50LCBtb2RpZmllcikge1xuICAgIGlmIChtb2RpZmllcikge1xuICAgICAgICByZXR1cm4gYCR7YmxvY2t9X18ke2VsZW1lbnR9LS0ke21vZGlmaWVyfWA7XG4gICAgfVxuICAgIGVsc2UgaWYgKGVsZW1lbnQpIHtcbiAgICAgICAgcmV0dXJuIGAke2Jsb2NrfV9fJHtlbGVtZW50fWA7XG4gICAgfVxuICAgIGVsc2Uge1xuICAgICAgICByZXR1cm4gYmxvY2s7XG4gICAgfVxufVxuIiwiaW1wb3J0IERpYWxvZyBmcm9tICdsaWIvZGlhbG9nJztcbmV4cG9ydCBkZWZhdWx0IERpYWxvZztcbiIsImV4cG9ydCBmdW5jdGlvbiBleHRyYWN0T3B0aW9ucygpIHtcbiAgICBjb25zdCBlbnYgPSBkb2N1bWVudC5ib2R5LmRhdGFzZXQ7XG4gICAgY29uc3QgZGVmYXVsdExhbmcgPSAnZW4nO1xuICAgIGNvbnN0IGRlZmF1bHRMb2NhbGUgPSAnZW4nO1xuICAgIGVudi5sb2NhbGU7XG4gICAgY29uc3QgYXBwTG9jYWxlID0gZW52LmxvY2FsZT8ucmVwbGFjZSgnXycsICctJykgfHwgZGVmYXVsdExvY2FsZTtcbiAgICBjb25zdCByZXN1bHQgPSB7XG4gICAgICAgIGRlZmF1bHRMYW5nOiBkZWZhdWx0TGFuZyxcbiAgICAgICAgZGVmYXVsdExvY2FsZTogZGVmYXVsdExvY2FsZSxcbiAgICAgICAgYXV0aG9yaXplZDogZW52LmF1dGhvcml6ZWQgPT09ICd0cnVlJyxcbiAgICAgICAgYm9va2luZzogZW52LmJvb2tpbmcgPT09ICd0cnVlJyxcbiAgICAgICAgYnVzaW5lc3M6IGVudi5idXNpbmVzcyA9PT0gJ3RydWUnLFxuICAgICAgICBkZWJ1ZzogZW52LmRlYnVnID09PSAndHJ1ZScsXG4gICAgICAgIGVuYWJsZWRUcmFuc0hlbHBlcjogZW52LmVuYWJsZWRUcmFuc0hlbHBlciA9PT0gJ3RydWUnLFxuICAgICAgICBoYXNSb2xlVHJhbnNsYXRvcjogZW52LnJvbGVUcmFuc2xhdG9yID09PSAndHJ1ZScsXG4gICAgICAgIGltcGVyc29uYXRlZDogZW52LmltcGVyc29uYXRlZCA9PT0gJ3RydWUnLFxuICAgICAgICBsYW5nOiBlbnYubGFuZyB8fCBkZWZhdWx0TGFuZyxcbiAgICAgICAgbG9jYWxlOiBhcHBMb2NhbGUsXG4gICAgICAgIGxvYWRFeHRlcm5hbFNjcmlwdHM6IGVudi5sb2FkRXh0ZXJuYWxTY3JpcHRzIHx8IGZhbHNlLFxuICAgIH07XG4gICAgaWYgKGVudi50aGVtZSkge1xuICAgICAgICByZXN1bHQudGhlbWUgPSBlbnYudGhlbWU7XG4gICAgfVxuICAgIHJldHVybiByZXN1bHQ7XG59XG5leHBvcnQgZnVuY3Rpb24gaXNJb3MoKSB7XG4gICAgcmV0dXJuIC9pUGFkfGlQaG9uZXxpUG9kL2kudGVzdChuYXZpZ2F0b3IudXNlckFnZW50KTtcbn1cbmV4cG9ydCBmdW5jdGlvbiBpc0FuZHJvaWQoKSB7XG4gICAgcmV0dXJuIC9hbmRyb2lkL2kudGVzdChuYXZpZ2F0b3IudXNlckFnZW50LnRvTG93ZXJDYXNlKCkpO1xufVxuIiwiZXhwb3J0IGRlZmF1bHQgZnVuY3Rpb24gb25SZWFkeShjYWxsYmFjaykge1xuICAgIGlmIChkb2N1bWVudC5yZWFkeVN0YXRlID09PSAnbG9hZGluZycpIHtcbiAgICAgICAgLy8gVGhlIERPTSBpcyBub3QgeWV0IHJlYWR5LlxuICAgICAgICBkb2N1bWVudC5hZGRFdmVudExpc3RlbmVyKCdET01Db250ZW50TG9hZGVkJywgY2FsbGJhY2spO1xuICAgIH1cbiAgICBlbHNlIHtcbiAgICAgICAgLy8gVGhlIERPTSBpcyBhbHJlYWR5IHJlYWR5LlxuICAgICAgICBjYWxsYmFjaygpO1xuICAgIH1cbn1cbiIsImltcG9ydCB7IGV4dHJhY3RPcHRpb25zIH0gZnJvbSAnLi9zZXJ2aWNlL2Vudic7XG5pbXBvcnQgb25SZWFkeSBmcm9tICcuL3NlcnZpY2Uvb24tcmVhZHknO1xub25SZWFkeShmdW5jdGlvbiAoKSB7XG4gICAgY29uc3Qgb3B0cyA9IGV4dHJhY3RPcHRpb25zKCk7XG4gICAgaWYgKG9wdHMuZW5hYmxlZFRyYW5zSGVscGVyIHx8IG9wdHMuaGFzUm9sZVRyYW5zbGF0b3IpIHtcbiAgICAgICAgY29uc29sZS5sb2coJ2luaXQgdHJhbnNoZWxwZXInKTtcbiAgICAgICAgaW1wb3J0KC8qIHdlYnBhY2tQcmVsb2FkOiB0cnVlICovICcuL3NlcnZpY2UvdHJhbnNIZWxwZXInKVxuICAgICAgICAgICAgLnRoZW4oKHsgZGVmYXVsdDogaW5pdCB9KSA9PiB7IGluaXQoKTsgfSwgKCkgPT4geyBjb25zb2xlLmVycm9yKCd0cmFuc2hlbHBlciBmYWlsZWQgdG8gbG9hZCcpOyB9KTtcbiAgICB9XG59KTtcbiIsIi8vIGV4dHJhY3RlZCBieSBtaW5pLWNzcy1leHRyYWN0LXBsdWdpblxuZXhwb3J0IHt9OyIsIi8vIGV4dHJhY3RlZCBieSBtaW5pLWNzcy1leHRyYWN0LXBsdWdpblxuZXhwb3J0IHt9OyIsIi8vIGV4dHJhY3RlZCBieSBtaW5pLWNzcy1leHRyYWN0LXBsdWdpblxuZXhwb3J0IHt9OyIsIi8vIGV4dHJhY3RlZCBieSBtaW5pLWNzcy1leHRyYWN0LXBsdWdpblxuZXhwb3J0IHt9OyIsIi8vIGV4dHJhY3RlZCBieSBtaW5pLWNzcy1leHRyYWN0LXBsdWdpblxuZXhwb3J0IHt9OyIsIi8vIGV4dHJhY3RlZCBieSBtaW5pLWNzcy1leHRyYWN0LXBsdWdpblxuZXhwb3J0IHt9OyIsIi8vIGV4dHJhY3RlZCBieSBtaW5pLWNzcy1leHRyYWN0LXBsdWdpblxuZXhwb3J0IHt9OyJdLCJuYW1lcyI6WyJvblJlYWR5IiwiY29udGV4dEV2ZW50IiwiRXZlbnQiLCJsb2dvIiwiZG9jdW1lbnQiLCJxdWVyeVNlbGVjdG9yIiwiYWRkRXZlbnRMaXN0ZW5lciIsImV2ZW50IiwicHJldmVudERlZmF1bHQiLCJ0YXJnZXQiLCJ0YWdOYW1lIiwiZGlzcGF0Y2hFdmVudCIsInN0aWNreUhlYWRlciIsImRhdGFzZXQiLCJib2R5IiwiaW52aXRlRW1haWwiLCJ3aW5kb3ciLCJpbnZpdGVGbiIsImZpcnN0TmFtZSIsImludml0ZUxuIiwibGFzdE5hbWUiLCJpbnZpdGVDb2RlIiwiYmVtQ2xhc3MiLCJ0aHJvdHRsZSIsImNvbnRhaW5lciIsInN0aWNreUhlYWRlckVsZW0iLCJzdGlja3lIZWFkZXJDb250ZW50RWxlbSIsImJhY2tncm91bmRDb2xvciIsImNvbXB1dGVCYWNrZ3JvdW5kQ29sb3IiLCJzdHlsZSIsInRyYW5zaXRpb24iLCJnZXRDb21wdXRlZFN0eWxlIiwicHJlcGFyZVN0aWNreUhlYWRlciIsInRpY2tDb2xvciIsImFscGhhIiwiTWF0aCIsIm1pbiIsInNjcm9sbFkiLCJjbGllbnRIZWlnaHQiLCJuZXdCYWNrZ3JvdW5kQ29sb3IiLCJzdGFydHNXaXRoIiwicmVwbGFjZSIsInRvU3RyaW5nIiwiY29uY2F0IiwiRXJyb3IiLCJ0aWNrQ2xhc3MiLCJzbWFsbENsYXNzIiwic21hbGxIZWFkZXIiLCJjbGFzc0xpc3QiLCJjb250YWlucyIsImFkZCIsInJlbW92ZSIsInRpY2tDb250YWluZXJTaXplIiwid2lkdGgiLCJjbGllbnRXaWR0aCIsInRpY2siLCJtYXRjaE1lZGlhIiwic2V0VGltZW91dCIsImRpc3BsYXkiLCJEaWFsb2ciLCJSb3V0ZXIiLCJUcmFuc2xhdG9yIiwicG9wdXAiLCJkIiwiY3JlYXRlTmFtZWQiLCIkIiwiYXV0b09wZW4iLCJtb2RhbCIsIm1pbldpZHRoIiwiYnV0dG9ucyIsInRleHQiLCJ0cmFucyIsImNsYXNzIiwiY2xpY2siLCJjbG9zZSIsImxvY2F0aW9uIiwiaHJlZiIsImdlbmVyYXRlIiwiZXh0cmFjdE9wdGlvbnMiLCJiZW0iLCJibG9jayIsImVsZW1lbnQiLCJtb2RpZmllcnMiLCJhcmd1bWVudHMiLCJsZW5ndGgiLCJ1bmRlZmluZWQiLCJvcHRzIiwiY2xhc3NlcyIsImNvbXBvbmVudCIsInB1c2giLCJ0aGVtZSIsImZvckVhY2giLCJtb2RpZmllciIsImpvaW4iLCJfZW52JGxvY2FsZSIsImVudiIsImRlZmF1bHRMYW5nIiwiZGVmYXVsdExvY2FsZSIsImxvY2FsZSIsImFwcExvY2FsZSIsInJlc3VsdCIsImF1dGhvcml6ZWQiLCJib29raW5nIiwiYnVzaW5lc3MiLCJkZWJ1ZyIsImVuYWJsZWRUcmFuc0hlbHBlciIsImhhc1JvbGVUcmFuc2xhdG9yIiwicm9sZVRyYW5zbGF0b3IiLCJpbXBlcnNvbmF0ZWQiLCJsYW5nIiwibG9hZEV4dGVybmFsU2NyaXB0cyIsImlzSW9zIiwidGVzdCIsIm5hdmlnYXRvciIsInVzZXJBZ2VudCIsImlzQW5kcm9pZCIsInRvTG93ZXJDYXNlIiwiY2FsbGJhY2siLCJyZWFkeVN0YXRlIiwiY29uc29sZSIsImxvZyIsInRoZW4iLCJfcmVmIiwiaW5pdCIsImRlZmF1bHQiLCJlcnJvciJdLCJzb3VyY2VSb290IjoiIn0=