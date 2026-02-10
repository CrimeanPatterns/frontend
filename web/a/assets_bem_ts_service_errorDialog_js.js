(self["webpackChunkAwardWallet"] = self["webpackChunkAwardWallet"] || []).push([["assets_bem_ts_service_errorDialog_js"],{

/***/ "./assets/bem/ts/service/errorDialog.js":
/*!**********************************************!*\
  !*** ./assets/bem/ts/service/errorDialog.js ***!
  \**********************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* binding */ errorDialog)
/* harmony export */ });
/* harmony import */ var lodash__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! lodash */ "./node_modules/lodash/lodash.js");
/* harmony import */ var lodash__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(lodash__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var lib_errorDialog__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! lib/errorDialog */ "./web/assets/awardwalletnewdesign/js/lib/errorDialog.js");
/* harmony import */ var lib_errorDialog__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(lib_errorDialog__WEBPACK_IMPORTED_MODULE_1__);

// noinspection NpmUsedModulesInstalled

function errorDialog(error, disablePopup) {
  lib_errorDialog__WEBPACK_IMPORTED_MODULE_1___default()({
    status: lodash__WEBPACK_IMPORTED_MODULE_0___default().get(error, 'response.status', 0),
    data: lodash__WEBPACK_IMPORTED_MODULE_0___default().get(error, 'response.data'),
    config: {
      method: lodash__WEBPACK_IMPORTED_MODULE_0___default().get(error, 'config.method'),
      url: lodash__WEBPACK_IMPORTED_MODULE_0___default().get(error, 'config.url'),
      data: lodash__WEBPACK_IMPORTED_MODULE_0___default().get(error, 'config.data')
    }
  }, disablePopup);
}

/***/ }),

/***/ "./web/assets/awardwalletnewdesign/js/lib/errorDialog.js":
/*!***************************************************************!*\
  !*** ./web/assets/awardwalletnewdesign/js/lib/errorDialog.js ***!
  \***************************************************************/
/***/ ((module, exports, __webpack_require__) => {

var __WEBPACK_AMD_DEFINE_ARRAY__, __WEBPACK_AMD_DEFINE_RESULT__;__webpack_require__(/*! core-js/modules/es.symbol.js */ "./node_modules/core-js/modules/es.symbol.js");
__webpack_require__(/*! core-js/modules/es.symbol.description.js */ "./node_modules/core-js/modules/es.symbol.description.js");
__webpack_require__(/*! core-js/modules/es.object.to-string.js */ "./node_modules/core-js/modules/es.object.to-string.js");
__webpack_require__(/*! core-js/modules/es.symbol.iterator.js */ "./node_modules/core-js/modules/es.symbol.iterator.js");
__webpack_require__(/*! core-js/modules/es.array.iterator.js */ "./node_modules/core-js/modules/es.array.iterator.js");
__webpack_require__(/*! core-js/modules/es.string.iterator.js */ "./node_modules/core-js/modules/es.string.iterator.js");
__webpack_require__(/*! core-js/modules/web.dom-collections.iterator.js */ "./node_modules/core-js/modules/web.dom-collections.iterator.js");
function _typeof(o) { "@babel/helpers - typeof"; return _typeof = "function" == typeof Symbol && "symbol" == typeof Symbol.iterator ? function (o) { return typeof o; } : function (o) { return o && "function" == typeof Symbol && o.constructor === Symbol && o !== Symbol.prototype ? "symbol" : typeof o; }, _typeof(o); }
/* global debugMode */

!(__WEBPACK_AMD_DEFINE_ARRAY__ = [__webpack_require__(/*! lib/dialog */ "./web/assets/awardwalletnewdesign/js/lib/dialog.js"), __webpack_require__(/*! translator-boot */ "./assets/bem/ts/service/translator.ts")], __WEBPACK_AMD_DEFINE_RESULT__ = (function (dialog) {
  return function (error, disablePopup) {
    disablePopup = disablePopup || false;
    if (error.status == 500 || error.status == 400 || error.status == 404 || error.status == 403) {
      if (error.data == 'unauthorized') {
        try {
          if (window.parent != window) {
            parent.location.href = '/security/unauthorized.php?BackTo=' + encodeURI(parent.location.href);
            return;
          }
        }
        // eslint-disable-next-line
        catch (e) {}
        location.href = '/security/unauthorized.php?BackTo=' + encodeURI(location.href);
        return;
      }
      if (disablePopup) return;
      var title = Translator.trans('error.server.other.title');
      if (error.data && typeof error.data.title == 'string') title = error.data.title;
      var message;
      if (error.data && typeof error.data.message == 'string') message = error.data.message;else {
        if (debugMode && typeof error.data == 'string') message = error.data;else message = Translator.trans('alerts.text.error');
      }
      message += '<img src="/ajax_error.gif?message=' + encodeURIComponent(title);
      message += '&status=' + encodeURIComponent(error.status);
      if (_typeof(error.config) == 'object') {
        message += '&url=' + encodeURIComponent(error.config.url);
        message += '&method=' + encodeURIComponent(error.config.method);
        if (typeof error.config.data != 'undefined') {
          message += '&req=' + encodeURIComponent(JSON.stringify(error.config.data).substring(0, 50));
        }
      }
      if (error.data) {
        message += '&res=' + encodeURIComponent(JSON.stringify(error.data).substring(0, 50));
      }
      message += '" width="1" height="1">';
      if (404 == error.status) {
        return;
      }
      dialog.fastCreate(title, message, true, true, [{
        text: Translator.trans( /** @Desc("Reload") */'reload'),
        'class': 'btn-blue',
        click: function click() {
          document.location.reload();
        }
      }], 500, null, 'error');
    }
  };
}).apply(exports, __WEBPACK_AMD_DEFINE_ARRAY__),
		__WEBPACK_AMD_DEFINE_RESULT__ !== undefined && (module.exports = __WEBPACK_AMD_DEFINE_RESULT__));

/***/ })

}]);
//# sourceMappingURL=data:application/json;charset=utf-8;base64,eyJ2ZXJzaW9uIjozLCJmaWxlIjoiYXNzZXRzX2JlbV90c19zZXJ2aWNlX2Vycm9yRGlhbG9nX2pzLmpzIiwibWFwcGluZ3MiOiI7Ozs7Ozs7Ozs7Ozs7Ozs7O0FBQXVCO0FBQ3ZCO0FBQ2dEO0FBRWpDLFNBQVNFLFdBQVdBLENBQUNDLEtBQUssRUFBRUMsWUFBWSxFQUFFO0VBQ3JESCxzREFBaUIsQ0FDYjtJQUNJSSxNQUFNLEVBQUVMLGlEQUFLLENBQUNHLEtBQUssRUFBRSxpQkFBaUIsRUFBRSxDQUFDLENBQUM7SUFDMUNJLElBQUksRUFBRVAsaURBQUssQ0FBQ0csS0FBSyxFQUFFLGVBQWUsQ0FBQztJQUNuQ0ssTUFBTSxFQUFFO01BQ0pDLE1BQU0sRUFBRVQsaURBQUssQ0FBQ0csS0FBSyxFQUFFLGVBQWUsQ0FBQztNQUNyQ08sR0FBRyxFQUFFVixpREFBSyxDQUFDRyxLQUFLLEVBQUUsWUFBWSxDQUFDO01BQy9CSSxJQUFJLEVBQUVQLGlEQUFLLENBQUNHLEtBQUssRUFBRSxhQUFhO0lBQ3BDO0VBQ0osQ0FBQyxFQUNEQyxZQUNKLENBQUM7QUFDTDs7Ozs7Ozs7Ozs7Ozs7Ozs7O0FDakJBOztBQUVBTyxpQ0FBTyxDQUFDLDJGQUFZLEVBQUUsbUZBQWlCLENBQUMsbUNBQUUsVUFBVUMsTUFBTSxFQUFFO0VBRTNELE9BQU8sVUFBVVQsS0FBSyxFQUFFQyxZQUFZLEVBQUU7SUFDckNBLFlBQVksR0FBR0EsWUFBWSxJQUFJLEtBQUs7SUFFcEMsSUFBR0QsS0FBSyxDQUFDRSxNQUFNLElBQUksR0FBRyxJQUFJRixLQUFLLENBQUNFLE1BQU0sSUFBSSxHQUFHLElBQUlGLEtBQUssQ0FBQ0UsTUFBTSxJQUFJLEdBQUcsSUFBSUYsS0FBSyxDQUFDRSxNQUFNLElBQUksR0FBRyxFQUFDO01BQzNGLElBQUlGLEtBQUssQ0FBQ0ksSUFBSSxJQUFJLGNBQWMsRUFBRTtRQUNqQyxJQUFJO1VBQ0gsSUFBSU0sTUFBTSxDQUFDQyxNQUFNLElBQUlELE1BQU0sRUFBRTtZQUM1QkMsTUFBTSxDQUFDQyxRQUFRLENB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cmNlcyI6WyJ3ZWJwYWNrOi8vQXdhcmRXYWxsZXQvLi9hc3NldHMvYmVtL3RzL3NlcnZpY2UvZXJyb3JEaWFsb2cuanMiLCJ3ZWJwYWNrOi8vQXdhcmRXYWxsZXQvLi93ZWIvYXNzZXRzL2F3YXJkd2FsbGV0bmV3ZGVzaWduL2pzL2xpYi9lcnJvckRpYWxvZy5qcyJdLCJzb3VyY2VzQ29udGVudCI6WyJpbXBvcnQgXyBmcm9tICdsb2Rhc2gnO1xuLy8gbm9pbnNwZWN0aW9uIE5wbVVzZWRNb2R1bGVzSW5zdGFsbGVkXG5pbXBvcnQgbGVnYWN5RXJyb3JEaWFsb2cgZnJvbSAnbGliL2Vycm9yRGlhbG9nJztcblxuZXhwb3J0IGRlZmF1bHQgZnVuY3Rpb24gZXJyb3JEaWFsb2coZXJyb3IsIGRpc2FibGVQb3B1cCkge1xuICAgIGxlZ2FjeUVycm9yRGlhbG9nKFxuICAgICAgICB7XG4gICAgICAgICAgICBzdGF0dXM6IF8uZ2V0KGVycm9yLCAncmVzcG9uc2Uuc3RhdHVzJywgMCksXG4gICAgICAgICAgICBkYXRhOiBfLmdldChlcnJvciwgJ3Jlc3BvbnNlLmRhdGEnKSxcbiAgICAgICAgICAgIGNvbmZpZzoge1xuICAgICAgICAgICAgICAgIG1ldGhvZDogXy5nZXQoZXJyb3IsICdjb25maWcubWV0aG9kJyksXG4gICAgICAgICAgICAgICAgdXJsOiBfLmdldChlcnJvciwgJ2NvbmZpZy51cmwnKSxcbiAgICAgICAgICAgICAgICBkYXRhOiBfLmdldChlcnJvciwgJ2NvbmZpZy5kYXRhJyksXG4gICAgICAgICAgICB9LFxuICAgICAgICB9LFxuICAgICAgICBkaXNhYmxlUG9wdXAsXG4gICAgKTtcbn1cbiIsIi8qIGdsb2JhbCBkZWJ1Z01vZGUgKi9cblxuZGVmaW5lKFsnbGliL2RpYWxvZycsICd0cmFuc2xhdG9yLWJvb3QnXSwgZnVuY3Rpb24gKGRpYWxvZykge1xuXG5cdHJldHVybiBmdW5jdGlvbiAoZXJyb3IsIGRpc2FibGVQb3B1cCkge1xuXHRcdGRpc2FibGVQb3B1cCA9IGRpc2FibGVQb3B1cCB8fCBmYWxzZTtcblxuXHRcdGlmKGVycm9yLnN0YXR1cyA9PSA1MDAgfHwgZXJyb3Iuc3RhdHVzID09IDQwMCB8fCBlcnJvci5zdGF0dXMgPT0gNDA0IHx8IGVycm9yLnN0YXR1cyA9PSA0MDMpe1xuXHRcdFx0aWYgKGVycm9yLmRhdGEgPT0gJ3VuYXV0aG9yaXplZCcpIHtcblx0XHRcdFx0dHJ5IHtcblx0XHRcdFx0XHRpZiAod2luZG93LnBhcmVudCAhPSB3aW5kb3cpIHtcblx0XHRcdFx0XHRcdHBhcmVudC5sb2NhdGlvbi5ocmVmID0gJy9zZWN1cml0eS91bmF1dGhvcml6ZWQucGhwP0JhY2tUbz0nICsgZW5jb2RlVVJJKHBhcmVudC5sb2NhdGlvbi5ocmVmKTtcblx0XHRcdFx0XHRcdHJldHVybjtcblx0XHRcdFx0XHR9XG5cdFx0XHRcdH1cblx0XHRcdFx0Ly8gZXNsaW50LWRpc2FibGUtbmV4dC1saW5lXG5cdFx0XHRcdGNhdGNoIChlKSB7XG5cdFx0XHRcdH1cblx0XHRcdFx0bG9jYXRpb24uaHJlZiA9ICcvc2VjdXJpdHkvdW5hdXRob3JpemVkLnBocD9CYWNrVG89JyArIGVuY29kZVVSSShsb2NhdGlvbi5ocmVmKTtcblx0XHRcdFx0cmV0dXJuO1xuXHRcdFx0fVxuXG5cdFx0XHRpZiAoZGlzYWJsZVBvcHVwKSByZXR1cm47XG5cblx0XHRcdGxldCB0aXRsZSA9IFRyYW5zbGF0b3IudHJhbnMoJ2Vycm9yLnNlcnZlci5vdGhlci50aXRsZScpO1xuXHRcdFx0aWYoZXJyb3IuZGF0YSAmJiB0eXBlb2YoZXJyb3IuZGF0YS50aXRsZSkgPT0gJ3N0cmluZycpXG5cdFx0XHRcdHRpdGxlID0gZXJyb3IuZGF0YS50aXRsZTtcblxuICAgICAgICAgICAgbGV0IG1lc3NhZ2U7XG5cdFx0XHRpZihlcnJvci5kYXRhICYmIHR5cGVvZihlcnJvci5kYXRhLm1lc3NhZ2UpID09ICdzdHJpbmcnKVxuXHRcdFx0XHRtZXNzYWdlID0gZXJyb3IuZGF0YS5tZXNzYWdlO1xuXHRcdFx0ZWxzZSB7XG5cdFx0XHRcdGlmIChkZWJ1Z01vZGUgJiYgdHlwZW9mKGVycm9yLmRhdGEpID09ICdzdHJpbmcnKVxuXHRcdFx0XHRcdG1lc3NhZ2UgPSBlcnJvci5kYXRhO1xuXHRcdFx0XHRlbHNlXG5cdFx0XHRcdFx0bWVzc2FnZSA9IFRyYW5zbGF0b3IudHJhbnMoJ2FsZXJ0cy50ZXh0LmVycm9yJyk7XG5cdFx0XHR9XG5cdFx0XHRtZXNzYWdlICs9ICc8aW1nIHNyYz1cIi9hamF4X2Vycm9yLmdpZj9tZXNzYWdlPScgKyBlbmNvZGVVUklDb21wb25lbnQodGl0bGUpO1xuXHRcdFx0bWVzc2FnZSArPSAnJnN0YXR1cz0nICsgZW5jb2RlVVJJQ29tcG9uZW50KGVycm9yLnN0YXR1cyk7XG5cdFx0XHRpZiAodHlwZW9mKGVycm9yLmNvbmZpZykgPT0gJ29iamVjdCcpIHtcblx0XHRcdFx0bWVzc2FnZSArPSAnJnVybD0nICsgZW5jb2RlVVJJQ29tcG9uZW50KGVycm9yLmNvbmZpZy51cmwpO1xuXHRcdFx0XHRtZXNzYWdlICs9ICcmbWV0aG9kPScgKyBlbmNvZGVVUklDb21wb25lbnQoZXJyb3IuY29uZmlnLm1ldGhvZCk7XG4gICAgICAgICAgICAgICAgaWYgKHR5cGVvZihlcnJvci5jb25maWcuZGF0YSkgIT0gJ3VuZGVmaW5lZCcpIHtcbiAgICAgICAgICAgICAgICAgICAgbWVzc2FnZSArPSAnJnJlcT0nICsgZW5jb2RlVVJJQ29tcG9uZW50KEpTT04uc3RyaW5naWZ5KGVycm9yLmNvbmZpZy5kYXRhKS5zdWJzdHJpbmcoMCwgNTApKTtcbiAgICAgICAgICAgICAgICB9XG5cdFx0XHR9XG5cdFx0XHRpZiAoZXJyb3IuZGF0YSkge1xuXHRcdFx0XHRtZXNzYWdlICs9ICcmcmVzPScgKyBlbmNvZGVVUklDb21wb25lbnQoSlNPTi5zdHJpbmdpZnkoZXJyb3IuZGF0YSkuc3Vic3RyaW5nKDAsIDUwKSk7XG5cdFx0XHR9XG5cdFx0XHRtZXNzYWdlICs9ICdcIiB3aWR0aD1cIjFcIiBoZWlnaHQ9XCIxXCI+JztcblxuXHRcdFx0aWYgKDQwNCA9PSBlcnJvci5zdGF0dXMpIHtcblx0XHRcdFx0cmV0dXJuO1xuXHRcdFx0fVxuXG5cdFx0XHRkaWFsb2cuZmFzdENyZWF0ZShcblx0XHRcdFx0dGl0bGUsXG5cdFx0XHRcdG1lc3NhZ2UsXG5cdFx0XHRcdHRydWUsXG5cdFx0XHRcdHRydWUsXG5cdFx0XHRcdFtcblx0XHRcdFx0XHR7XG5cdFx0XHRcdFx0XHR0ZXh0OiBUcmFuc2xhdG9yLnRyYW5zKC8qKiBARGVzYyhcIlJlbG9hZFwiKSAqLyAncmVsb2FkJyksXG5cdFx0XHRcdFx0XHQnY2xhc3MnOiAnYnRuLWJsdWUnLFxuXHRcdFx0XHRcdFx0Y2xpY2s6IGZ1bmN0aW9uICgpIHtcblx0XHRcdFx0XHRcdFx0ZG9jdW1lbnQubG9jYXRpb24ucmVsb2FkKCk7XG5cdFx0XHRcdFx0XHR9XG5cdFx0XHRcdFx0fVxuXHRcdFx0XHRdLFxuXHRcdFx0XHQ1MDAsXG5cdFx0XHRcdG51bGwsXG5cdFx0XHRcdCdlcnJvcidcblx0XHRcdCk7XG5cdFx0fVxuXG5cdH1cblxufSk7Il0sIm5hbWVzIjpbIl8iLCJsZWdhY3lFcnJvckRpYWxvZyIsImVycm9yRGlhbG9nIiwiZXJyb3IiLCJkaXNhYmxlUG9wdXAiLCJzdGF0dXMiLCJnZXQiLCJkYXRhIiwiY29uZmlnIiwibWV0aG9kIiwidXJsIiwiZGVmaW5lIiwiZGlhbG9nIiwid2luZG93IiwicGFyZW50IiwibG9jYXRpb24iLCJocmVmIiwiZW5jb2RlVVJJIiwiZSIsInRpdGxlIiwiVHJhbnNsYXRvciIsInRyYW5zIiwibWVzc2FnZSIsImRlYnVnTW9kZSIsImVuY29kZVVSSUNvbXBvbmVudCIsIl90eXBlb2YiLCJKU09OIiwic3RyaW5naWZ5Iiwic3Vic3RyaW5nIiwiZmFzdENyZWF0ZSIsInRleHQiLCJjbGljayIsImRvY3VtZW50IiwicmVsb2FkIl0sInNvdXJjZVJvb3QiOiIifQ==