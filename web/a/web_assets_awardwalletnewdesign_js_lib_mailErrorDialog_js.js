(self["webpackChunkAwardWallet"] = self["webpackChunkAwardWallet"] || []).push([["web_assets_awardwalletnewdesign_js_lib_mailErrorDialog_js"],{

/***/ "./web/assets/awardwalletnewdesign/js/lib/mailErrorDialog.js":
/*!*******************************************************************!*\
  !*** ./web/assets/awardwalletnewdesign/js/lib/mailErrorDialog.js ***!
  \*******************************************************************/
/***/ ((module, exports, __webpack_require__) => {

var __WEBPACK_AMD_DEFINE_ARRAY__, __WEBPACK_AMD_DEFINE_RESULT__;__webpack_require__(/*! core-js/modules/es.object.keys.js */ "./node_modules/core-js/modules/es.object.keys.js");
__webpack_require__(/*! core-js/modules/es.array.join.js */ "./node_modules/core-js/modules/es.array.join.js");
!(__WEBPACK_AMD_DEFINE_ARRAY__ = [__webpack_require__(/*! lib/dialog */ "./web/assets/awardwalletnewdesign/js/lib/dialog.js"), __webpack_require__(/*! translator-boot */ "./assets/bem/ts/service/translator.ts")], __WEBPACK_AMD_DEFINE_RESULT__ = (function (dialog) {
  return function (mailErrors) {
    var title = Translator.trans('error.server.other.title');
    mailErrors = JSON.parse(mailErrors);
    var message = mailErrors.join("<br/><br/>");
    var dlg = dialog.fastCreate(title, message, true, true, [{
      text: Translator.trans('button.ok'),
      'class': 'btn-blue',
      click: function click() {
        dlg.close();
      }
    }], 500, null, 'error');
  };
}).apply(exports, __WEBPACK_AMD_DEFINE_ARRAY__),
		__WEBPACK_AMD_DEFINE_RESULT__ !== undefined && (module.exports = __WEBPACK_AMD_DEFINE_RESULT__));

/***/ })

}]);
//# sourceMappingURL=data:application/json;charset=utf-8;base64,eyJ2ZXJzaW9uIjozLCJmaWxlIjoid2ViX2Fzc2V0c19hd2FyZHdhbGxldG5ld2Rlc2lnbl9qc19saWJfbWFpbEVycm9yRGlhbG9nX2pzLmpzIiwibWFwcGluZ3MiOiI7Ozs7Ozs7Ozs7QUFBQUEsaUNBQU8sQ0FBQywyRkFBWSxFQUFFLG1GQUFpQixDQUFDLG1DQUFFLFVBQVVDLE1BQU0sRUFBRTtFQUUzRCxPQUFPLFVBQVVDLFVBQVUsRUFBRTtJQUM1QixJQUFNQyxLQUFLLEdBQUdDLFVBQVUsQ0FBQ0MsS0FBSyxDQUFDLDBCQUEwQixDQUFDO0lBRTFESCxVQUFVLEdBQUdJLElBQUksQ0FBQ0MsS0FBSyxDQUFDTCxVQUFVLENBQUM7SUFDbkMsSUFBTU0sT0FBTyxHQUFHTixVQUFVLENBQUNPLElBQUksQ0FBQyxZQUFZLENBQUM7SUFFN0MsSUFBTUMsR0FBRyxHQUFHVCxNQUFNLENBQUNVLFVBQVUsQ0FDNUJSLEtBQUssRUFDTEssT0FBTyxFQUNQLElBQUksRUFDSixJQUFJLEVBQ0osQ0FDQztNQUNDSSxJQUFJLEVBQUVSLFVBQVUsQ0FBQ0MsS0FBSyxDQUFDLFdBQVcsQ0FBQztNQUNuQyxPQUFPLEVBQUUsVUFBVTtNQUNuQlEsS0FBSyxFQUFFLFNBQUFBLE1BQUEsRUFBWTtRQUNsQkgsR0FBRyxDQUFDSSxLQUFLLENBQUMsQ0FBQztNQUNaO0lBQ0QsQ0FBQyxDQUNELEVBQ0QsR0FBRyxFQUNILElBQUksRUFDSixPQUNELENBQUM7RUFDRixDQUFDO0FBRUYsQ0FBQztBQUFBLGtHQUFDIiwic291cmNlcyI6WyJ3ZWJwYWNrOi8vQXdhcmRXYWxsZXQvLi93ZWIvYXNzZXRzL2F3YXJkd2FsbGV0bmV3ZGVzaWduL2pzL2xpYi9tYWlsRXJyb3JEaWFsb2cuanMiXSwic291cmNlc0NvbnRlbnQiOlsiZGVmaW5lKFsnbGliL2RpYWxvZycsICd0cmFuc2xhdG9yLWJvb3QnXSwgZnVuY3Rpb24gKGRpYWxvZykge1xuXG5cdHJldHVybiBmdW5jdGlvbiAobWFpbEVycm9ycykge1xuXHRcdGNvbnN0IHRpdGxlID0gVHJhbnNsYXRvci50cmFucygnZXJyb3Iuc2VydmVyLm90aGVyLnRpdGxlJyk7XG5cblx0XHRtYWlsRXJyb3JzID0gSlNPTi5wYXJzZShtYWlsRXJyb3JzKTtcblx0XHRjb25zdCBtZXNzYWdlID0gbWFpbEVycm9ycy5qb2luKFwiPGJyLz48YnIvPlwiKTtcblxuXHRcdGNvbnN0IGRsZyA9IGRpYWxvZy5mYXN0Q3JlYXRlKFxuXHRcdFx0dGl0bGUsXG5cdFx0XHRtZXNzYWdlLFxuXHRcdFx0dHJ1ZSxcblx0XHRcdHRydWUsXG5cdFx0XHRbXG5cdFx0XHRcdHtcblx0XHRcdFx0XHR0ZXh0OiBUcmFuc2xhdG9yLnRyYW5zKCdidXR0b24ub2snKSxcblx0XHRcdFx0XHQnY2xhc3MnOiAnYnRuLWJsdWUnLFxuXHRcdFx0XHRcdGNsaWNrOiBmdW5jdGlvbiAoKSB7XG5cdFx0XHRcdFx0XHRkbGcuY2xvc2UoKTtcblx0XHRcdFx0XHR9XG5cdFx0XHRcdH1cblx0XHRcdF0sXG5cdFx0XHQ1MDAsXG5cdFx0XHRudWxsLFxuXHRcdFx0J2Vycm9yJ1xuXHRcdCk7XG5cdH1cblxufSk7Il0sIm5hbWVzIjpbImRlZmluZSIsImRpYWxvZyIsIm1haWxFcnJvcnMiLCJ0aXRsZSIsIlRyYW5zbGF0b3IiLCJ0cmFucyIsIkpTT04iLCJwYXJzZSIsIm1lc3NhZ2UiLCJqb2luIiwiZGxnIiwiZmFzdENyZWF0ZSIsInRleHQiLCJjbGljayIsImNsb3NlIl0sInNvdXJjZVJvb3QiOiIifQ==