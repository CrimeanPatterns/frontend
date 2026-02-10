"use strict";
(self["webpackChunkAwardWallet"] = self["webpackChunkAwardWallet"] || []).push([["assets_bem_ts_starter_ts-_c1f70"],{

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

/***/ })

}]);
//# sourceMappingURL=data:application/json;charset=utf-8;base64,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Q0FBQyxDQUFDIiwic291cmNlcyI6WyJ3ZWJwYWNrOi8vQXdhcmRXYWxsZXQvLi9hc3NldHMvYmVtL3RzL3NlcnZpY2UvZW52LnRzIiwid2VicGFjazovL0F3YXJkV2FsbGV0Ly4vYXNzZXRzL2JlbS90cy9zZXJ2aWNlL29uLXJlYWR5LnRzIiwid2VicGFjazovL0F3YXJkV2FsbGV0Ly4vYXNzZXRzL2JlbS90cy9zdGFydGVyLnRzIl0sInNvdXJjZXNDb250ZW50IjpbImV4cG9ydCBmdW5jdGlvbiBleHRyYWN0T3B0aW9ucygpIHtcbiAgICBjb25zdCBlbnYgPSBkb2N1bWVudC5ib2R5LmRhdGFzZXQ7XG4gICAgY29uc3QgZGVmYXVsdExhbmcgPSAnZW4nO1xuICAgIGNvbnN0IGRlZmF1bHRMb2NhbGUgPSAnZW4nO1xuICAgIGVudi5sb2NhbGU7XG4gICAgY29uc3QgYXBwTG9jYWxlID0gZW52LmxvY2FsZT8ucmVwbGFjZSgnXycsICctJykgfHwgZGVmYXVsdExvY2FsZTtcbiAgICBjb25zdCByZXN1bHQgPSB7XG4gICAgICAgIGRlZmF1bHRMYW5nOiBkZWZhdWx0TGFuZyxcbiAgICAgICAgZGVmYXVsdExvY2FsZTogZGVmYXVsdExvY2FsZSxcbiAgICAgICAgYXV0aG9yaXplZDogZW52LmF1dGhvcml6ZWQgPT09ICd0cnVlJyxcbiAgICAgICAgYm9va2luZzogZW52LmJvb2tpbmcgPT09ICd0cnVlJyxcbiAgICAgICAgYnVzaW5lc3M6IGVudi5idXNpbmVzcyA9PT0gJ3RydWUnLFxuICAgICAgICBkZWJ1ZzogZW52LmRlYnVnID09PSAndHJ1ZScsXG4gICAgICAgIGVuYWJsZWRUcmFuc0hlbHBlcjogZW52LmVuYWJsZWRUcmFuc0hlbHBlciA9PT0gJ3RydWUnLFxuICAgICAgICBoYXNSb2xlVHJhbnNsYXRvcjogZW52LnJvbGVUcmFuc2xhdG9yID09PSAndHJ1ZScsXG4gICAgICAgIGltcGVyc29uYXRlZDogZW52LmltcGVyc29uYXRlZCA9PT0gJ3RydWUnLFxuICAgICAgICBsYW5nOiBlbnYubGFuZyB8fCBkZWZhdWx0TGFuZyxcbiAgICAgICAgbG9jYWxlOiBhcHBMb2NhbGUsXG4gICAgICAgIGxvYWRFeHRlcm5hbFNjcmlwdHM6IGVudi5sb2FkRXh0ZXJuYWxTY3JpcHRzIHx8IGZhbHNlLFxuICAgIH07XG4gICAgaWYgKGVudi50aGVtZSkge1xuICAgICAgICByZXN1bHQudGhlbWUgPSBlbnYudGhlbWU7XG4gICAgfVxuICAgIHJldHVybiByZXN1bHQ7XG59XG5leHBvcnQgZnVuY3Rpb24gaXNJb3MoKSB7XG4gICAgcmV0dXJuIC9pUGFkfGlQaG9uZXxpUG9kL2kudGVzdChuYXZpZ2F0b3IudXNlckFnZW50KTtcbn1cbmV4cG9ydCBmdW5jdGlvbiBpc0FuZHJvaWQoKSB7XG4gICAgcmV0dXJuIC9hbmRyb2lkL2kudGVzdChuYXZpZ2F0b3IudXNlckFnZW50LnRvTG93ZXJDYXNlKCkpO1xufVxuIiwiZXhwb3J0IGRlZmF1bHQgZnVuY3Rpb24gb25SZWFkeShjYWxsYmFjaykge1xuICAgIGlmIChkb2N1bWVudC5yZWFkeVN0YXRlID09PSAnbG9hZGluZycpIHtcbiAgICAgICAgLy8gVGhlIERPTSBpcyBub3QgeWV0IHJlYWR5LlxuICAgICAgICBkb2N1bWVudC5hZGRFdmVudExpc3RlbmVyKCdET01Db250ZW50TG9hZGVkJywgY2FsbGJhY2spO1xuICAgIH1cbiAgICBlbHNlIHtcbiAgICAgICAgLy8gVGhlIERPTSBpcyBhbHJlYWR5IHJlYWR5LlxuICAgICAgICBjYWxsYmFjaygpO1xuICAgIH1cbn1cbiIsImltcG9ydCB7IGV4dHJhY3RPcHRpb25zIH0gZnJvbSAnLi9zZXJ2aWNlL2Vudic7XG5pbXBvcnQgb25SZWFkeSBmcm9tICcuL3NlcnZpY2Uvb24tcmVhZHknO1xub25SZWFkeShmdW5jdGlvbiAoKSB7XG4gICAgY29uc3Qgb3B0cyA9IGV4dHJhY3RPcHRpb25zKCk7XG4gICAgaWYgKG9wdHMuZW5hYmxlZFRyYW5zSGVscGVyIHx8IG9wdHMuaGFzUm9sZVRyYW5zbGF0b3IpIHtcbiAgICAgICAgY29uc29sZS5sb2coJ2luaXQgdHJhbnNoZWxwZXInKTtcbiAgICAgICAgaW1wb3J0KC8qIHdlYnBhY2tQcmVsb2FkOiB0cnVlICovICcuL3NlcnZpY2UvdHJhbnNIZWxwZXInKVxuICAgICAgICAgICAgLnRoZW4oKHsgZGVmYXVsdDogaW5pdCB9KSA9PiB7IGluaXQoKTsgfSwgKCkgPT4geyBjb25zb2xlLmVycm9yKCd0cmFuc2hlbHBlciBmYWlsZWQgdG8gbG9hZCcpOyB9KTtcbiAgICB9XG59KTtcbiJdLCJuYW1lcyI6WyJleHRyYWN0T3B0aW9ucyIsIl9lbnYkbG9jYWxlIiwiZW52IiwiZG9jdW1lbnQiLCJib2R5IiwiZGF0YXNldCIsImRlZmF1bHRMYW5nIiwiZGVmYXVsdExvY2FsZSIsImxvY2FsZSIsImFwcExvY2FsZSIsInJlcGxhY2UiLCJyZXN1bHQiLCJhdXRob3JpemVkIiwiYm9va2luZyIsImJ1c2luZXNzIiwiZGVidWciLCJlbmFibGVkVHJhbnNIZWxwZXIiLCJoYXNSb2xlVHJhbnNsYXRvciIsInJvbGVUcmFuc2xhdG9yIiwiaW1wZXJzb25hdGVkIiwibGFuZyIsImxvYWRFeHRlcm5hbFNjcmlwdHMiLCJ0aGVtZSIsImlzSW9zIiwidGVzdCIsIm5hdmlnYXRvciIsInVzZXJBZ2VudCIsImlzQW5kcm9pZCIsInRvTG93ZXJDYXNlIiwib25SZWFkeSIsImNhbGxiYWNrIiwicmVhZHlTdGF0ZSIsImFkZEV2ZW50TGlzdGVuZXIiLCJvcHRzIiwiY29uc29sZSIsImxvZyIsInRoZW4iLCJfcmVmIiwiaW5pdCIsImRlZmF1bHQiLCJlcnJvciJdLCJzb3VyY2VSb290IjoiIn0=