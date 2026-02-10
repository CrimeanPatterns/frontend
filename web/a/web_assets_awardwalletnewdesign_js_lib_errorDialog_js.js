(self["webpackChunkAwardWallet"] = self["webpackChunkAwardWallet"] || []).push([["web_assets_awardwalletnewdesign_js_lib_errorDialog_js"],{

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
//# sourceMappingURL=data:application/json;charset=utf-8;base64,eyJ2ZXJzaW9uIjozLCJmaWxlIjoid2ViX2Fzc2V0c19hd2FyZHdhbGxldG5ld2Rlc2lnbl9qc19saWJfZXJyb3JEaWFsb2dfanMuanMiLCJtYXBwaW5ncyI6Ijs7Ozs7Ozs7Ozs7Ozs7OztBQUFBOztBQUVBQSxpQ0FBTyxDQUFDLDJGQUFZLEVBQUUsbUZBQWlCLENBQUMsbUNBQUUsVUFBVUMsTUFBTSxFQUFFO0VBRTNELE9BQU8sVUFBVUMsS0FBSyxFQUFFQyxZQUFZLEVBQUU7SUFDckNBLFlBQVksR0FBR0EsWUFBWSxJQUFJLEtBQUs7SUFFcEMsSUFBR0QsS0FBSyxDQUFDRSxNQUFNLElBQUksR0FBRyxJQUFJRixLQUFLLENBQUNFLE1BQU0sSUFBSSxHQUFHLElBQUlGLEtBQUssQ0FBQ0UsTUFBTSxJQUFJLEdBQUcsSUFBSUYsS0FBSyxDQUFDRSxNQUFNLElBQUksR0FBRyxFQUFDO01BQzNGLElBQUlGLEtBQUssQ0FBQ0csSUFBSSxJQUFJLGNBQWMsRUFBRTtRQUNqQyxJQUFJO1VBQ0gsSUFBSUMsTUFBTSxDQUFDQyxNQUFNLElBQUlELE1BQU0sRUFBRTtZQUM1QkMsTUFBTSxDQUFDQyxRQUFRLENBQUNDLElBQUksR0FBRyxvQ0FBb0MsR0FBR0MsU0FBUyxDQUFDSCxNQUFNLENBQUNDLFFBQVEsQ0FBQ0MsSUFBSSxDQUFDO1lBQzdGO1VBQ0Q7UUFDRDtRQUNBO1FBQ0EsT0FBT0UsQ0FBQyxFQUFFLENBQ1Y7UUFDQUgsUUFBUSxDQUFDQyxJQUFJLEdBQUcsb0NBQW9DLEdBQUdDLFNBQVMsQ0FBQ0YsUUFBUSxDQUFDQyxJQUFJLENBQUM7UUFDL0U7TUFDRDtNQUVBLElBQUlOLFlBQVksRUFBRTtNQUVsQixJQUFJUyxLQUFLLEdBQUdDLFVBQVUsQ0FBQ0MsS0FBSyxDQUFDLDBCQUEwQixDQUFDO01BQ3hELElBQUdaLEtBQUssQ0FBQ0csSUFBSSxJQUFJLE9BQU9ILEtBQUssQ0FBQ0csSUFBSSxDQUFDTyxLQUFNLElBQUksUUFBUSxFQUNwREEsS0FBSyxHQUFHVixLQUFLLENBQUNHLElBQUksQ0FBQ08sS0FBSztNQUVoQixJQUFJRyxPQUFPO01BQ3BCLElBQUdiLEtBQUssQ0FBQ0csSUFBSSxJQUFJLE9BQU9ILEtBQUssQ0FBQ0csSUFBSSxDQUFDVSxPQUFRLElBQUksUUFBUSxFQUN0REEsT0FBTyxHQUFHYixLQUFLLENBQUNHLElBQUksQ0FBQ1UsT0FBTyxDQUFDLEtBQ3pCO1FBQ0osSUFBSUMsU0FBUyxJQUFJLE9BQU9kLEtBQUssQ0FBQ0csSUFBSyxJQUFJLFFBQVEsRUFDOUNVLE9BQU8sR0FBR2IsS0FBSyxDQUFDRyxJQUFJLENBQUMsS0FFckJVLE9BQU8sR0FBR0YsVUFBVSxDQUFDQyxLQUFLLENBQUMsbUJBQW1CLENBQUM7TUFDakQ7TUFDQUMsT0FBTyxJQUFJLG9DQUFvQyxHQUFHRSxrQkFBa0IsQ0FBQ0wsS0FBSyxDQUFDO01BQzNFRyxPQUFPLElBQUksVUFBVSxHQUFHRSxrQkFBa0IsQ0FBQ2YsS0FBSyxDQUFDRSxNQUFNLENBQUM7TUFDeEQsSUFBSWMsT0FBQSxDQUFPaEIsS0FBSyxDQUFDaUIsTUFBTSxLQUFLLFFBQVEsRUFBRTtRQUNyQ0osT0FBTyxJQUFJLE9BQU8sR0FBR0Usa0JBQWtCLENBQUNmLEtBQUssQ0FBQ2lCLE1BQU0sQ0FBQ0MsR0FBRyxDQUFDO1FBQ3pETCxPQUFPLElBQUksVUFBVSxHQUFHRSxrQkFBa0IsQ0FBQ2YsS0FBSyxDQUFDaUIsTUFBTSxDQUFDRSxNQUFNLENBQUM7UUFDbkQsSUFBSSxPQUFPbkIsS0FBSyxDQUFDaUIsTUFBTSxDQUFDZCxJQUFLLElBQUksV0FBVyxFQUFFO1VBQzFDVSxPQUFPLElBQUksT0FBTyxHQUFHRSxrQkFBa0IsQ0FBQ0ssSUFBSSxDQUFDQyxTQUFTLENBQUNyQixLQUFLLENBQUNpQixNQUFNLENBQUNkLElBQUksQ0FBQyxDQUFDbUIsU0FBUyxDQUFDLENBQUMsRUFBRSxFQUFFLENBQUMsQ0FBQztRQUMvRjtNQUNiO01BQ0EsSUFBSXRCLEtBQUssQ0FBQ0csSUFBSSxFQUFFO1FBQ2ZVLE9BQU8sSUFBSSxPQUFPLEdBQUdFLGtCQUFrQixDQUFDSyxJQUFJLENBQUNDLFNBQVMsQ0FBQ3JCLEtBQUssQ0FBQ0csSUFBSSxDQUFDLENBQUNtQixTQUFTLENBQUMsQ0FBQyxFQUFFLEVBQUUsQ0FBQyxDQUFDO01BQ3JGO01BQ0FULE9BQU8sSUFBSSx5QkFBeUI7TUFFcEMsSUFBSSxHQUFHLElBQUliLEtBQUssQ0FBQ0UsTUFBTSxFQUFFO1FBQ3hCO01BQ0Q7TUFFQUgsTUFBTSxDQUFDd0IsVUFBVSxDQUNoQmIsS0FBSyxFQUNMRyxPQUFPLEVBQ1AsSUFBSSxFQUNKLElBQUksRUFDSixDQUNDO1FBQ0NXLElBQUksRUFBRWIsVUFBVSxDQUFDQyxLQUFLLEVBQUMsc0JBQXVCLFFBQVEsQ0FBQztRQUN2RCxPQUFPLEVBQUUsVUFBVTtRQUNuQmEsS0FBSyxFQUFFLFNBQUFBLE1BQUEsRUFBWTtVQUNsQkMsUUFBUSxDQUFDcEIsUUFBUSxDQUFDcUIsTUFBTSxDQUFDLENBQUM7UUFDM0I7TUFDRCxDQUFDLENBQ0QsRUFDRCxHQUFHLEVBQ0gsSUFBSSxFQUNKLE9BQ0QsQ0FBQztJQUNGO0VBRUQsQ0FBQztBQUVGLENBQUM7QUFBQSxrR0FBQyIsInNvdXJjZXMiOlsid2VicGFjazovL0F3YXJkV2FsbGV0Ly4vd2ViL2Fzc2V0cy9hd2FyZHdhbGxldG5ld2Rlc2lnbi9qcy9saWIvZXJyb3JEaWFsb2cuanMiXSwic291cmNlc0NvbnRlbnQiOlsiLyogZ2xvYmFsIGRlYnVnTW9kZSAqL1xuXG5kZWZpbmUoWydsaWIvZGlhbG9nJywgJ3RyYW5zbGF0b3ItYm9vdCddLCBmdW5jdGlvbiAoZGlhbG9nKSB7XG5cblx0cmV0dXJuIGZ1bmN0aW9uIChlcnJvciwgZGlzYWJsZVBvcHVwKSB7XG5cdFx0ZGlzYWJsZVBvcHVwID0gZGlzYWJsZVBvcHVwIHx8IGZhbHNlO1xuXG5cdFx0aWYoZXJyb3Iuc3RhdHVzID09IDUwMCB8fCBlcnJvci5zdGF0dXMgPT0gNDAwIHx8IGVycm9yLnN0YXR1cyA9PSA0MDQgfHwgZXJyb3Iuc3RhdHVzID09IDQwMyl7XG5cdFx0XHRpZiAoZXJyb3IuZGF0YSA9PSAndW5hdXRob3JpemVkJykge1xuXHRcdFx0XHR0cnkge1xuXHRcdFx0XHRcdGlmICh3aW5kb3cucGFyZW50ICE9IHdpbmRvdykge1xuXHRcdFx0XHRcdFx0cGFyZW50LmxvY2F0aW9uLmhyZWYgPSAnL3NlY3VyaXR5L3VuYXV0aG9yaXplZC5waHA/QmFja1RvPScgKyBlbmNvZGVVUkkocGFyZW50LmxvY2F0aW9uLmhyZWYpO1xuXHRcdFx0XHRcdFx0cmV0dXJuO1xuXHRcdFx0XHRcdH1cblx0XHRcdFx0fVxuXHRcdFx0XHQvLyBlc2xpbnQtZGlzYWJsZS1uZXh0LWxpbmVcblx0XHRcdFx0Y2F0Y2ggKGUpIHtcblx0XHRcdFx0fVxuXHRcdFx0XHRsb2NhdGlvbi5ocmVmID0gJy9zZWN1cml0eS91bmF1dGhvcml6ZWQucGhwP0JhY2tUbz0nICsgZW5jb2RlVVJJKGxvY2F0aW9uLmhyZWYpO1xuXHRcdFx0XHRyZXR1cm47XG5cdFx0XHR9XG5cblx0XHRcdGlmIChkaXNhYmxlUG9wdXApIHJldHVybjtcblxuXHRcdFx0bGV0IHRpdGxlID0gVHJhbnNsYXRvci50cmFucygnZXJyb3Iuc2VydmVyLm90aGVyLnRpdGxlJyk7XG5cdFx0XHRpZihlcnJvci5kYXRhICYmIHR5cGVvZihlcnJvci5kYXRhLnRpdGxlKSA9PSAnc3RyaW5nJylcblx0XHRcdFx0dGl0bGUgPSBlcnJvci5kYXRhLnRpdGxlO1xuXG4gICAgICAgICAgICBsZXQgbWVzc2FnZTtcblx0XHRcdGlmKGVycm9yLmRhdGEgJiYgdHlwZW9mKGVycm9yLmRhdGEubWVzc2FnZSkgPT0gJ3N0cmluZycpXG5cdFx0XHRcdG1lc3NhZ2UgPSBlcnJvci5kYXRhLm1lc3NhZ2U7XG5cdFx0XHRlbHNlIHtcblx0XHRcdFx0aWYgKGRlYnVnTW9kZSAmJiB0eXBlb2YoZXJyb3IuZGF0YSkgPT0gJ3N0cmluZycpXG5cdFx0XHRcdFx0bWVzc2FnZSA9IGVycm9yLmRhdGE7XG5cdFx0XHRcdGVsc2Vcblx0XHRcdFx0XHRtZXNzYWdlID0gVHJhbnNsYXRvci50cmFucygnYWxlcnRzLnRleHQuZXJyb3InKTtcblx0XHRcdH1cblx0XHRcdG1lc3NhZ2UgKz0gJzxpbWcgc3JjPVwiL2FqYXhfZXJyb3IuZ2lmP21lc3NhZ2U9JyArIGVuY29kZVVSSUNvbXBvbmVudCh0aXRsZSk7XG5cdFx0XHRtZXNzYWdlICs9ICcmc3RhdHVzPScgKyBlbmNvZGVVUklDb21wb25lbnQoZXJyb3Iuc3RhdHVzKTtcblx0XHRcdGlmICh0eXBlb2YoZXJyb3IuY29uZmlnKSA9PSAnb2JqZWN0Jykge1xuXHRcdFx0XHRtZXNzYWdlICs9ICcmdXJsPScgKyBlbmNvZGVVUklDb21wb25lbnQoZXJyb3IuY29uZmlnLnVybCk7XG5cdFx0XHRcdG1lc3NhZ2UgKz0gJyZtZXRob2Q9JyArIGVuY29kZVVSSUNvbXBvbmVudChlcnJvci5jb25maWcubWV0aG9kKTtcbiAgICAgICAgICAgICAgICBpZiAodHlwZW9mKGVycm9yLmNvbmZpZy5kYXRhKSAhPSAndW5kZWZpbmVkJykge1xuICAgICAgICAgICAgICAgICAgICBtZXNzYWdlICs9ICcmcmVxPScgKyBlbmNvZGVVUklDb21wb25lbnQoSlNPTi5zdHJpbmdpZnkoZXJyb3IuY29uZmlnLmRhdGEpLnN1YnN0cmluZygwLCA1MCkpO1xuICAgICAgICAgICAgICAgIH1cblx0XHRcdH1cblx0XHRcdGlmIChlcnJvci5kYXRhKSB7XG5cdFx0XHRcdG1lc3NhZ2UgKz0gJyZyZXM9JyArIGVuY29kZVVSSUNvbXBvbmVudChKU09OLnN0cmluZ2lmeShlcnJvci5kYXRhKS5zdWJzdHJpbmcoMCwgNTApKTtcblx0XHRcdH1cblx0XHRcdG1lc3NhZ2UgKz0gJ1wiIHdpZHRoPVwiMVwiIGhlaWdodD1cIjFcIj4nO1xuXG5cdFx0XHRpZiAoNDA0ID09IGVycm9yLnN0YXR1cykge1xuXHRcdFx0XHRyZXR1cm47XG5cdFx0XHR9XG5cblx0XHRcdGRpYWxvZy5mYXN0Q3JlYXRlKFxuXHRcdFx0XHR0aXRsZSxcblx0XHRcdFx0bWVzc2FnZSxcblx0XHRcdFx0dHJ1ZSxcblx0XHRcdFx0dHJ1ZSxcblx0XHRcdFx0W1xuXHRcdFx0XHRcdHtcblx0XHRcdFx0XHRcdHRleHQ6IFRyYW5zbGF0b3IudHJhbnMoLyoqIEBEZXNjKFwiUmVsb2FkXCIpICovICdyZWxvYWQnKSxcblx0XHRcdFx0XHRcdCdjbGFzcyc6ICdidG4tYmx1ZScsXG5cdFx0XHRcdFx0XHRjbGljazogZnVuY3Rpb24gKCkge1xuXHRcdFx0XHRcdFx0XHRkb2N1bWVudC5sb2NhdGlvbi5yZWxvYWQoKTtcblx0XHRcdFx0XHRcdH1cblx0XHRcdFx0XHR9XG5cdFx0XHRcdF0sXG5cdFx0XHRcdDUwMCxcblx0XHRcdFx0bnVsbCxcblx0XHRcdFx0J2Vycm9yJ1xuXHRcdFx0KTtcblx0XHR9XG5cblx0fVxuXG59KTsiXSwibmFtZXMiOlsiZGVmaW5lIiwiZGlhbG9nIiwiZXJyb3IiLCJkaXNhYmxlUG9wdXAiLCJzdGF0dXMiLCJkYXRhIiwid2luZG93IiwicGFyZW50IiwibG9jYXRpb24iLCJocmVmIiwiZW5jb2RlVVJJIiwiZSIsInRpdGxlIiwiVHJhbnNsYXRvciIsInRyYW5zIiwibWVzc2FnZSIsImRlYnVnTW9kZSIsImVuY29kZVVSSUNvbXBvbmVudCIsIl90eXBlb2YiLCJjb25maWciLCJ1cmwiLCJtZXRob2QiLCJKU09OIiwic3RyaW5naWZ5Iiwic3Vic3RyaW5nIiwiZmFzdENyZWF0ZSIsInRleHQiLCJjbGljayIsImRvY3VtZW50IiwicmVsb2FkIl0sInNvdXJjZVJvb3QiOiIifQ==