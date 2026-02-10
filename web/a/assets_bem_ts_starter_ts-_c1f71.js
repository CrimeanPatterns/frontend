"use strict";
(self["webpackChunkAwardWallet"] = self["webpackChunkAwardWallet"] || []).push([["assets_bem_ts_starter_ts-_c1f71"],{

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
//# sourceMappingURL=data:application/json;charset=utf-8;base64,eyJ2ZXJzaW9uIjozLCJmaWxlIjoiYXNzZXRzX2JlbV90c19zdGFydGVyX3RzLV9jMWY3MS5qcyIsIm1hcHBpbmdzIjoiOzs7Ozs7Ozs7Ozs7O0FBQWUsU0FBU0EsT0FBT0EsQ0FBQ0MsUUFBUSxFQUFFO0VBQ3RDLElBQUlDLFFBQVEsQ0FBQ0MsVUFBVSxLQUFLLFNBQVMsRUFBRTtJQUNuQztJQUNBRCxRQUFRLENBQUNFLGdCQUFnQixDQUFDLGtCQUFrQixFQUFFSCxRQUFRLENBQUM7RUFDM0QsQ0FBQyxNQUNJO0lBQ0Q7SUFDQUEsUUFBUSxDQUFDLENBQUM7RUFDZDtBQUNKOzs7Ozs7Ozs7Ozs7Ozs7Ozs7Ozs7Ozs7Ozs7O0FDVCtDO0FBQ047QUFDekNELDZEQUFPLENBQUMsWUFBWTtFQUNoQixJQUFNTSxJQUFJLEdBQUdELDREQUFjLENBQUMsQ0FBQztFQUM3QixJQUFJQyxJQUFJLENBQUNDLGtCQUFrQixJQUFJRCxJQUFJLENBQUNFLGlCQUFpQixFQUFFO0lBQ25EQyxPQUFPLENBQUNDLEdBQUcsQ0FBQyxrQkFBa0IsQ0FBQztJQUMvQiw4bEJBQTBELENBQ3JEQyxJQUFJLENBQUMsVUFBQUMsSUFBQSxFQUF1QjtNQUFBLElBQVhDLElBQUksR0FBQUQsSUFBQSxDQUFiRSxPQUFPO01BQWVELElBQUksQ0FBQyxDQUFDO0lBQUUsQ0FBQyxFQUFFLFlBQU07TUFBRUosT0FBTyxDQUFDTSxLQUFLLENBQUMsNEJBQTRCLENBQUM7SUFBRSxDQUFDLENBQUM7RUFDekc7QUFDSixDQUFDLENBQUMiLCJzb3VyY2VzIjpbIndlYnBhY2s6Ly9Bd2FyZFdhbGxldC8uL2Fzc2V0cy9iZW0vdHMvc2VydmljZS9vbi1yZWFkeS50cyIsIndlYnBhY2s6Ly9Bd2FyZFdhbGxldC8uL2Fzc2V0cy9iZW0vdHMvc3RhcnRlci50cyJdLCJzb3VyY2VzQ29udGVudCI6WyJleHBvcnQgZGVmYXVsdCBmdW5jdGlvbiBvblJlYWR5KGNhbGxiYWNrKSB7XG4gICAgaWYgKGRvY3VtZW50LnJlYWR5U3RhdGUgPT09ICdsb2FkaW5nJykge1xuICAgICAgICAvLyBUaGUgRE9NIGlzIG5vdCB5ZXQgcmVhZHkuXG4gICAgICAgIGRvY3VtZW50LmFkZEV2ZW50TGlzdGVuZXIoJ0RPTUNvbnRlbnRMb2FkZWQnLCBjYWxsYmFjayk7XG4gICAgfVxuICAgIGVsc2Uge1xuICAgICAgICAvLyBUaGUgRE9NIGlzIGFscmVhZHkgcmVhZHkuXG4gICAgICAgIGNhbGxiYWNrKCk7XG4gICAgfVxufVxuIiwiaW1wb3J0IHsgZXh0cmFjdE9wdGlvbnMgfSBmcm9tICcuL3NlcnZpY2UvZW52JztcbmltcG9ydCBvblJlYWR5IGZyb20gJy4vc2VydmljZS9vbi1yZWFkeSc7XG5vblJlYWR5KGZ1bmN0aW9uICgpIHtcbiAgICBjb25zdCBvcHRzID0gZXh0cmFjdE9wdGlvbnMoKTtcbiAgICBpZiAob3B0cy5lbmFibGVkVHJhbnNIZWxwZXIgfHwgb3B0cy5oYXNSb2xlVHJhbnNsYXRvcikge1xuICAgICAgICBjb25zb2xlLmxvZygnaW5pdCB0cmFuc2hlbHBlcicpO1xuICAgICAgICBpbXBvcnQoLyogd2VicGFja1ByZWxvYWQ6IHRydWUgKi8gJy4vc2VydmljZS90cmFuc0hlbHBlcicpXG4gICAgICAgICAgICAudGhlbigoeyBkZWZhdWx0OiBpbml0IH0pID0+IHsgaW5pdCgpOyB9LCAoKSA9PiB7IGNvbnNvbGUuZXJyb3IoJ3RyYW5zaGVscGVyIGZhaWxlZCB0byBsb2FkJyk7IH0pO1xuICAgIH1cbn0pO1xuIl0sIm5hbWVzIjpbIm9uUmVhZHkiLCJjYWxsYmFjayIsImRvY3VtZW50IiwicmVhZHlTdGF0ZSIsImFkZEV2ZW50TGlzdGVuZXIiLCJleHRyYWN0T3B0aW9ucyIsIm9wdHMiLCJlbmFibGVkVHJhbnNIZWxwZXIiLCJoYXNSb2xlVHJhbnNsYXRvciIsImNvbnNvbGUiLCJsb2ciLCJ0aGVuIiwiX3JlZiIsImluaXQiLCJkZWZhdWx0IiwiZXJyb3IiXSwic291cmNlUm9vdCI6IiJ9