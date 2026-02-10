(self["webpackChunkAwardWallet"] = self["webpackChunkAwardWallet"] || []).push([["iframe"],{

/***/ "./assets/entry-point-deprecated/iframe.js":
/*!*************************************************!*\
  !*** ./assets/entry-point-deprecated/iframe.js ***!
  \*************************************************/
/***/ ((__unused_webpack_module, __unused_webpack_exports, __webpack_require__) => {

__webpack_require__(/*! core-js/modules/es.object.to-string.js */ "./node_modules/core-js/modules/es.object.to-string.js");
function resize() {
  if (window.self === window.top) {
    return;
  }
  var elements = parent.document.getElementsByClassName('autoResizable');
  [].forEach.call(elements, function (elem) {
    if (elem.contentWindow.document === window.document) {
      var height;
      if (elem.dataset.body) {
        height = elem.contentWindow.document.getElementById(elem.dataset.body).scrollHeight;
      } else {
        height = elem.contentWindow.document.body.scrollHeight;
      }
      elem.style.height = height + 'px';
      elem.style.width = '100%';
    }
  });
}
resize();
window.addEventListener('resize', resize);
setInterval(function () {
  resize();
}, 500);

/***/ }),

/***/ "./node_modules/core-js/internals/object-to-string.js":
/*!************************************************************!*\
  !*** ./node_modules/core-js/internals/object-to-string.js ***!
  \************************************************************/
/***/ ((module, __unused_webpack_exports, __webpack_require__) => {

"use strict";

var TO_STRING_TAG_SUPPORT = __webpack_require__(/*! ../internals/to-string-tag-support */ "./node_modules/core-js/internals/to-string-tag-support.js");
var classof = __webpack_require__(/*! ../internals/classof */ "./node_modules/core-js/internals/classof.js");

// `Object.prototype.toString` method implementation
// https://tc39.es/ecma262/#sec-object.prototype.tostring
module.exports = TO_STRING_TAG_SUPPORT ? {}.toString : function toString() {
  return '[object ' + classof(this) + ']';
};


/***/ }),

/***/ "./node_modules/core-js/modules/es.object.to-string.js":
/*!*************************************************************!*\
  !*** ./node_modules/core-js/modules/es.object.to-string.js ***!
  \*************************************************************/
/***/ ((__unused_webpack_module, __unused_webpack_exports, __webpack_require__) => {

"use strict";

var TO_STRING_TAG_SUPPORT = __webpack_require__(/*! ../internals/to-string-tag-support */ "./node_modules/core-js/internals/to-string-tag-support.js");
var defineBuiltIn = __webpack_require__(/*! ../internals/define-built-in */ "./node_modules/core-js/internals/define-built-in.js");
var toString = __webpack_require__(/*! ../internals/object-to-string */ "./node_modules/core-js/internals/object-to-string.js");

// `Object.prototype.toString` method
// https://tc39.es/ecma262/#sec-object.prototype.tostring
if (!TO_STRING_TAG_SUPPORT) {
  defineBuiltIn(Object.prototype, 'toString', toString, { unsafe: true });
}


/***/ })

},
/******/ __webpack_require__ => { // webpackRuntimeModules
/******/ var __webpack_exec__ = (moduleId) => (__webpack_require__(__webpack_require__.s = moduleId))
/******/ __webpack_require__.O(0, ["vendors-node_modules_core-js_internals_classof_js-node_modules_core-js_internals_define-built-in_js"], () => (__webpack_exec__("./assets/entry-point-deprecated/iframe.js")));
/******/ var __webpack_exports__ = __webpack_require__.O();
/******/ }
]);
//# sourceMappingURL=data:application/json;charset=utf-8;base64,eyJ2ZXJzaW9uIjozLCJmaWxlIjoiaWZyYW1lLmpzIiwibWFwcGluZ3MiOiI7Ozs7Ozs7OztBQUFBLFNBQVNBLE1BQU1BLENBQUEsRUFBRztFQUNkLElBQUlDLE1BQU0sQ0FBQ0MsSUFBSSxLQUFLRCxNQUFNLENBQUNFLEdBQUcsRUFBRTtJQUM1QjtFQUNKO0VBRUEsSUFBTUMsUUFBUSxHQUFHQyxNQUFNLENBQUNDLFFBQVEsQ0FBQ0Msc0JBQXNCLENBQUMsZUFBZSxDQUFDO0VBRXhFLEVBQUUsQ0FBQ0MsT0FBTyxDQUFDQyxJQUFJLENBQUNMLFFBQVEsRUFBRSxVQUFVTSxJQUFJLEVBQUU7SUFDdEMsSUFBSUEsSUFBSSxDQUFDQyxhQUFhLENBQUNMLFFBQVEsS0FBS0wsTUFBTSxDQUFDSyxRQUFRLEVBQUU7TUFDakQsSUFBSU0sTUFBTTtNQUNWLElBQUlGLElBQUksQ0FBQ0csT0FBTyxDQUFDQyxJQUFJLEVBQUU7UUFDbkJGLE1BQU0sR0FBR0YsSUFBSSxDQUFDQyxhQUFhLENBQUNMLFFBQVEsQ0FBQ1MsY0FBYyxDQUFDTCxJQUFJLENBQUNHLE9BQU8sQ0FBQ0MsSUFBSSxDQUFDLENBQUNFLFlBQVk7TUFDdkYsQ0FBQyxNQUFNO1FBQ0hKLE1BQU0sR0FBR0YsSUFBSSxDQUFDQyxhQUFhLENBQUNMLFFBQVEsQ0FBQ1EsSUFBSSxDQUFDRSxZQUFZO01BQzFEO01BQ0FOLElBQUksQ0FBQ08sS0FBSyxDQUFDTCxNQUFNLEdBQUdBLE1BQU0sR0FBRyxJQUFJO01BQ2pDRixJQUFJLENBQUNPLEtBQUssQ0FBQ0MsS0FBSyxHQUFHLE1BQU07SUFDN0I7RUFDSixDQUFDLENBQUM7QUFDTjtBQUVBbEIsTUFBTSxDQUFDLENBQUM7QUFDUkMsTUFBTSxDQUFDa0IsZ0JBQWdCLENBQUMsUUFBUSxFQUFFbkIsTUFBTSxDQUFDO0FBQ3pDb0IsV0FBVyxDQUFDLFlBQVk7RUFDcEJwQixNQUFNLENBQUMsQ0FBQztBQUNaLENBQUMsRUFBRSxHQUFHLENBQUM7Ozs7Ozs7Ozs7O0FDekJNO0FBQ2IsNEJBQTRCLG1CQUFPLENBQUMscUdBQW9DO0FBQ3hFLGNBQWMsbUJBQU8sQ0FBQyx5RUFBc0I7O0FBRTVDO0FBQ0E7QUFDQSwyQ0FBMkM7QUFDM0M7QUFDQTs7Ozs7Ozs7Ozs7O0FDUmE7QUFDYiw0QkFBNEIsbUJBQU8sQ0FBQyxxR0FBb0M7QUFDeEUsb0JBQW9CLG1CQUFPLENBQUMseUZBQThCO0FBQzFELGVBQWUsbUJBQU8sQ0FBQywyRkFBK0I7O0FBRXREO0FBQ0E7QUFDQTtBQUNBLDBEQUEwRCxjQUFjO0FBQ3hFIiwic291cmNlcyI6WyJ3ZWJwYWNrOi8vQXdhcmRXYWxsZXQvLi9hc3NldHMvZW50cnktcG9pbnQtZGVwcmVjYXRlZC9pZnJhbWUuanMiLCJ3ZWJwYWNrOi8vQXdhcmRXYWxsZXQvLi9ub2RlX21vZHVsZXMvY29yZS1qcy9pbnRlcm5hbHMvb2JqZWN0LXRvLXN0cmluZy5qcyIsIndlYnBhY2s6Ly9Bd2FyZFdhbGxldC8uL25vZGVfbW9kdWxlcy9jb3JlLWpzL21vZHVsZXMvZXMub2JqZWN0LnRvLXN0cmluZy5qcyJdLCJzb3VyY2VzQ29udGVudCI6WyJmdW5jdGlvbiByZXNpemUoKSB7XG4gICAgaWYgKHdpbmRvdy5zZWxmID09PSB3aW5kb3cudG9wKSB7XG4gICAgICAgIHJldHVybjtcbiAgICB9XG5cbiAgICBjb25zdCBlbGVtZW50cyA9IHBhcmVudC5kb2N1bWVudC5nZXRFbGVtZW50c0J5Q2xhc3NOYW1lKCdhdXRvUmVzaXphYmxlJyk7XG5cbiAgICBbXS5mb3JFYWNoLmNhbGwoZWxlbWVudHMsIGZ1bmN0aW9uIChlbGVtKSB7XG4gICAgICAgIGlmIChlbGVtLmNvbnRlbnRXaW5kb3cuZG9jdW1lbnQgPT09IHdpbmRvdy5kb2N1bWVudCkge1xuICAgICAgICAgICAgbGV0IGhlaWdodDtcbiAgICAgICAgICAgIGlmIChlbGVtLmRhdGFzZXQuYm9keSkge1xuICAgICAgICAgICAgICAgIGhlaWdodCA9IGVsZW0uY29udGVudFdpbmRvdy5kb2N1bWVudC5nZXRFbGVtZW50QnlJZChlbGVtLmRhdGFzZXQuYm9keSkuc2Nyb2xsSGVpZ2h0O1xuICAgICAgICAgICAgfSBlbHNlIHtcbiAgICAgICAgICAgICAgICBoZWlnaHQgPSBlbGVtLmNvbnRlbnRXaW5kb3cuZG9jdW1lbnQuYm9keS5zY3JvbGxIZWlnaHQ7XG4gICAgICAgICAgICB9XG4gICAgICAgICAgICBlbGVtLnN0eWxlLmhlaWdodCA9IGhlaWdodCArICdweCc7XG4gICAgICAgICAgICBlbGVtLnN0eWxlLndpZHRoID0gJzEwMCUnO1xuICAgICAgICB9XG4gICAgfSk7XG59XG5cbnJlc2l6ZSgpO1xud2luZG93LmFkZEV2ZW50TGlzdGVuZXIoJ3Jlc2l6ZScsIHJlc2l6ZSk7XG5zZXRJbnRlcnZhbChmdW5jdGlvbiAoKSB7XG4gICAgcmVzaXplKCk7XG59LCA1MDApOyIsIid1c2Ugc3RyaWN0JztcbnZhciBUT19TVFJJTkdfVEFHX1NVUFBPUlQgPSByZXF1aXJlKCcuLi9pbnRlcm5hbHMvdG8tc3RyaW5nLXRhZy1zdXBwb3J0Jyk7XG52YXIgY2xhc3NvZiA9IHJlcXVpcmUoJy4uL2ludGVybmFscy9jbGFzc29mJyk7XG5cbi8vIGBPYmplY3QucHJvdG90eXBlLnRvU3RyaW5nYCBtZXRob2QgaW1wbGVtZW50YXRpb25cbi8vIGh0dHBzOi8vdGMzOS5lcy9lY21hMjYyLyNzZWMtb2JqZWN0LnByb3RvdHlwZS50b3N0cmluZ1xubW9kdWxlLmV4cG9ydHMgPSBUT19TVFJJTkdfVEFHX1NVUFBPUlQgPyB7fS50b1N0cmluZyA6IGZ1bmN0aW9uIHRvU3RyaW5nKCkge1xuICByZXR1cm4gJ1tvYmplY3QgJyArIGNsYXNzb2YodGhpcykgKyAnXSc7XG59O1xuIiwiJ3VzZSBzdHJpY3QnO1xudmFyIFRPX1NUUklOR19UQUdfU1VQUE9SVCA9IHJlcXVpcmUoJy4uL2ludGVybmFscy90by1zdHJpbmctdGFnLXN1cHBvcnQnKTtcbnZhciBkZWZpbmVCdWlsdEluID0gcmVxdWlyZSgnLi4vaW50ZXJuYWxzL2RlZmluZS1idWlsdC1pbicpO1xudmFyIHRvU3RyaW5nID0gcmVxdWlyZSgnLi4vaW50ZXJuYWxzL29iamVjdC10by1zdHJpbmcnKTtcblxuLy8gYE9iamVjdC5wcm90b3R5cGUudG9TdHJpbmdgIG1ldGhvZFxuLy8gaHR0cHM6Ly90YzM5LmVzL2VjbWEyNjIvI3NlYy1vYmplY3QucHJvdG90eXBlLnRvc3RyaW5nXG5pZiAoIVRPX1NUUklOR19UQUdfU1VQUE9SVCkge1xuICBkZWZpbmVCdWlsdEluKE9iamVjdC5wcm90b3R5cGUsICd0b1N0cmluZycsIHRvU3RyaW5nLCB7IHVuc2FmZTogdHJ1ZSB9KTtcbn1cbiJdLCJuYW1lcyI6WyJyZXNpemUiLCJ3aW5kb3ciLCJzZWxmIiwidG9wIiwiZWxlbWVudHMiLCJwYXJlbnQiLCJkb2N1bWVudCIsImdldEVsZW1lbnRzQnlDbGFzc05hbWUiLCJmb3JFYWNoIiwiY2FsbCIsImVsZW0iLCJjb250ZW50V2luZG93IiwiaGVpZ2h0IiwiZGF0YXNldCIsImJvZHkiLCJnZXRFbGVtZW50QnlJZCIsInNjcm9sbEhlaWdodCIsInN0eWxlIiwid2lkdGgiLCJhZGRFdmVudExpc3RlbmVyIiwic2V0SW50ZXJ2YWwiXSwic291cmNlUm9vdCI6IiJ9