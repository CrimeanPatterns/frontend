(self["webpackChunkAwardWallet"] = self["webpackChunkAwardWallet"] || []).push([["itinerary-edit"],{

/***/ "./assets/js-deprecated/component-deprecated/form/ItineraryEdit.js":
/*!*************************************************************************!*\
  !*** ./assets/js-deprecated/component-deprecated/form/ItineraryEdit.js ***!
  \*************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _less_deprecated_itinerary_form_less__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ../../../less-deprecated/itinerary-form.less */ "./assets/less-deprecated/itinerary-form.less");
/* harmony import */ var _timeline_Notes__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ../timeline/Notes */ "./assets/js-deprecated/component-deprecated/timeline/Notes.js");
/* harmony import */ var _bem_ts_service_listener__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ../../../bem/ts/service/listener */ "./assets/bem/ts/service/listener.ts");
/* harmony import */ var react_dom__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! react-dom */ "./node_modules/react-dom/index.js");
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! react */ "./node_modules/react/index.js");
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_4___default = /*#__PURE__*/__webpack_require__.n(react__WEBPACK_IMPORTED_MODULE_4__);
/* harmony import */ var jqueryui__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! jqueryui */ "./web/assets/common/vendors/jquery-ui/jquery-ui.min.js");
/* harmony import */ var jqueryui__WEBPACK_IMPORTED_MODULE_5___default = /*#__PURE__*/__webpack_require__.n(jqueryui__WEBPACK_IMPORTED_MODULE_5__);
/* provided dependency */ var $ = __webpack_require__(/*! jquery */ "./web/assets/common/vendors/jquery/dist/jquery.js");






/*eslint no-unused-vars: "jqueryui"*/

(0,_bem_ts_service_listener__WEBPACK_IMPORTED_MODULE_2__.listenAddNewPersonPopup)();
function ItineraryEdit(props) {
  var segment = {
    preset: _timeline_Notes__WEBPACK_IMPORTED_MODULE_1__.PRESET_SEGMENT,
    notes: {
      text: props.text,
      files: props.files
    }
  };
  return /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_4___default().createElement("div", null, /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_4___default().createElement(_timeline_Notes__WEBPACK_IMPORTED_MODULE_1__["default"], {
    segment: segment,
    opened: true,
    form: props.form
  }));
}
var $notesRow = $('.row-notes');
if ($notesRow.length) {
  $notesRow.after('<div id="notesEditor" class="editor-wrap"></div>');
  var contentElement = document.getElementById('notesEditor');
  var noteText = $(_timeline_Notes__WEBPACK_IMPORTED_MODULE_1__.SEGMENT_SELECTOR, $notesRow).val();
  var $files = $('form[data-files]');
  var files = $files.data('files');
  $notesRow.hide();
  (0,react_dom__WEBPACK_IMPORTED_MODULE_3__.render)( /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_4___default().createElement((react__WEBPACK_IMPORTED_MODULE_4___default().StrictMode), null, /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_4___default().createElement(ItineraryEdit, {
    text: noteText,
    files: files,
    form: $notesRow.closest('form')
  })), contentElement);
}

/***/ }),

/***/ "./web/assets/awardwalletnewdesign/js/pages/agent/addDialog.js":
/*!*********************************************************************!*\
  !*** ./web/assets/awardwalletnewdesign/js/pages/agent/addDialog.js ***!
  \*********************************************************************/
/***/ ((module, exports, __webpack_require__) => {

var __WEBPACK_AMD_DEFINE_ARRAY__, __WEBPACK_AMD_DEFINE_RESULT__;!(__WEBPACK_AMD_DEFINE_ARRAY__ = [__webpack_require__(/*! jquery-boot */ "./web/assets/common/js/jquery-boot.js"), __webpack_require__(/*! lib/dialog */ "./web/assets/awardwalletnewdesign/js/lib/dialog.js"), __webpack_require__(/*! translator-boot */ "./assets/bem/ts/service/translator.ts"), __webpack_require__(/*! routing */ "./assets/bem/ts/service/router.ts")], __WEBPACK_AMD_DEFINE_RESULT__ = (function ($, dialog) {
  var dialogElement;

  // Add persons popup
  if (typeof dialogElement == 'undefined') {
    dialogElement = $('<div />').appendTo('body').html(Translator.trans( /** @Desc("You have two options, you can connect with another person on AwardWallet, or you can just create another name to better organize your rewards.") */'agents.popup.content'));
    dialog.createNamed('persons-menu', dialogElement, {
      width: '600',
      autoOpen: false,
      modal: true,
      title: Translator.trans( /** @Desc("Select connection type") */'agents.popup.header'),
      buttons: [{
        'text': Translator.trans( /** @Desc("Connect with another person") */'agents.popup.connect.btn'),
        'class': 'btn-blue spinnerable',
        'click': function click() {
          window.location.href = Routing.generate('aw_create_connection');
        }
      }, {
        'text': Translator.trans( /** @Desc("Just add a new name") */'agents.popup.add.btn'),
        'class': 'btn-blue spinnerable',
        'click': function click() {
          window.location.href = Routing.generate('aw_add_agent');
        }
      }],
      open: function open() {
        // Remove bottons focus
        $('.ui-dialog :button').blur();
        history.pushState(null, null, '?add-new-person=true');
      },
      close: function close() {
        history.back();
      }
    });
  }
  var clickHandler = function clickHandler() {
    dialogElement.dialog('open');
  };
  return clickHandler;
}).apply(exports, __WEBPACK_AMD_DEFINE_ARRAY__),
		__WEBPACK_AMD_DEFINE_RESULT__ !== undefined && (module.exports = __WEBPACK_AMD_DEFINE_RESULT__));

/***/ }),

/***/ "./assets/bem/ts/service/listener.ts":
/*!*******************************************!*\
  !*** ./assets/bem/ts/service/listener.ts ***!
  \*******************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   listenAddNewPersonPopup: () => (/* binding */ listenAddNewPersonPopup)
/* harmony export */ });
/* provided dependency */ var $ = __webpack_require__(/*! jquery */ "./web/assets/common/vendors/jquery/dist/jquery.js");
// deprecated
function listenAddNewPersonPopup() {
  $(document).on('click', '.js-add-new-person, #add-person-btn, .js-persons-menu a[href="/user/connections"].add', function (e) {
    e.preventDefault();
    // eslint-disable-next-line @typescript-eslint/no-unsafe-call, @typescript-eslint/no-var-requires
    __webpack_require__(/*! ../../../../web/assets/awardwalletnewdesign/js/pages/agent/addDialog */ "./web/assets/awardwalletnewdesign/js/pages/agent/addDialog.js")();
  });
}

/***/ }),

/***/ "./assets/less-deprecated/itinerary-form.less":
/*!****************************************************!*\
  !*** ./assets/less-deprecated/itinerary-form.less ***!
  \****************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
// extracted by mini-css-extract-plugin


/***/ })

},
/******/ __webpack_require__ => { // webpackRuntimeModules
/******/ var __webpack_exec__ = (moduleId) => (__webpack_require__(__webpack_require__.s = moduleId))
/******/ __webpack_require__.O(0, ["vendors-node_modules_core-js_internals_classof_js-node_modules_core-js_internals_define-built-in_js","vendors-node_modules_core-js_internals_array-slice-simple_js-node_modules_core-js_internals_f-4bac30","vendors-node_modules_core-js_modules_es_string_replace_js","vendors-node_modules_core-js_modules_es_object_keys_js-node_modules_core-js_modules_es_regexp-599444","vendors-node_modules_core-js_internals_array-method-is-strict_js-node_modules_core-js_modules-ea2489","vendors-node_modules_core-js_modules_es_array_find_js-node_modules_core-js_modules_es_array_f-112b41","vendors-node_modules_core-js_modules_es_promise_js-node_modules_core-js_modules_web_dom-colle-054322","vendors-node_modules_core-js_internals_string-trim_js-node_modules_core-js_internals_this-num-d4bf43","vendors-node_modules_core-js_modules_es_json_to-string-tag_js-node_modules_core-js_modules_es-dd246b","vendors-node_modules_core-js_modules_es_array_from_js-node_modules_core-js_modules_es_date_to-6fede4","vendors-node_modules_axios-hooks_es_index_js-node_modules_classnames_index_js-node_modules_is-e8b457","vendors-node_modules_core-js_modules_es_number_to-fixed_js-node_modules_intl_index_js","vendors-node_modules_prop-types_index_js","vendors-node_modules_ckeditor_ckeditor5-react_dist_ckeditor_js-node_modules_core-js_modules_e-eae954","assets_bem_ts_service_translator_ts","web_assets_common_vendors_jquery_dist_jquery_js","web_assets_common_vendors_jquery-ui_jquery-ui_min_js","assets_bem_ts_service_router_ts","web_assets_common_fonts_webfonts_open-sans_css-web_assets_common_fonts_webfonts_roboto_css","web_assets_awardwalletnewdesign_js_lib_dialog_js","assets_bem_ts_service_axios_index_js-assets_bem_ts_service_env_ts","assets_js-deprecated_component-deprecated_timeline_Notes_js"], () => (__webpack_exec__("./assets/js-deprecated/component-deprecated/form/ItineraryEdit.js")));
/******/ var __webpack_exports__ = __webpack_require__.O();
/******/ }
]);
//# sourceMappingURL=data:application/json;charset=utf-8;base64,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c0I7UUFDL0IsT0FBTyxFQUFFLFNBQUFPLE1BQUEsRUFBWTtVQUNwQkMsTUFBTSxDQUFDQyxRQUFRLENBQUNDLElBQUksR0FBR0MsT0FBTyxDQUFDQyxRQUFRLENBQUMsY0FBYyxDQUFDO1FBQ3hEO01BQ0QsQ0FBQyxDQUNEO01BQ0RDLElBQUksRUFBRSxTQUFBQSxLQUFBLEVBQVk7UUFDakI7UUFDQWhDLENBQUMsQ0FBQyxvQkFBb0IsQ0FBQyxDQUFDaUMsSUFBSSxDQUFDLENBQUM7UUFDbEJDLE9BQU8sQ0FBQ0MsU0FBUyxDQUFDLElBQUksRUFBRSxJQUFJLEVBQUUsc0JBQXNCLENBQUM7TUFFekQsQ0FBQztNQUNEQyxLQUFLLEVBQUUsU0FBQUEsTUFBQSxFQUFXO1FBQ2RGLE9BQU8sQ0FBQ0csSUFBSSxDQUFDLENBQUM7TUFDbEI7SUFDVixDQUFDLENBQUM7RUFDSDtFQUVBLElBQUlDLFlBQVksR0FBRyxTQUFmQSxZQUFZQSxDQUFBLEVBQWM7SUFDN0J2QixhQUFhLENBQUNELE1BQU0sQ0FBQyxNQUFNLENBQUM7RUFDN0IsQ0FBQztFQUVELE9BQU93QixZQUFZO0FBQ3BCLENBQUM7QUFBQSxrR0FBQzs7Ozs7Ozs7Ozs7Ozs7OztBQy9DRjtBQUNPLFNBQVN2RCx1QkFBdUJBLENBQUEsRUFBRztFQUN0Q2lCLENBQUMsQ0FBQ0ksUUFBUSxDQUFDLENBQUNtQyxFQUFFLENBQUMsT0FBTyxFQUFFLHVGQUF1RixFQUFFLFVBQVVDLENBQUMsRUFBRTtJQUMxSEEsQ0FBQyxDQUFDQyxjQUFjLENBQUMsQ0FBQztJQUNsQjtJQUNBQyxtQkFBTyxDQUFDLDJJQUFzRSxDQUFDLENBQUMsQ0FBQztFQUNyRixDQUFDLENBQUM7QUFDTjs7Ozs7Ozs7Ozs7O0FDUEEiLCJzb3VyY2VzIjpbIndlYnBhY2s6Ly9Bd2FyZFdhbGxldC8uL2Fzc2V0cy9qcy1kZXByZWNhdGVkL2NvbXBvbmVudC1kZXByZWNhdGVkL2Zvcm0vSXRpbmVyYXJ5RWRpdC5qcyIsIndlYnBhY2s6Ly9Bd2FyZFdhbGxldC8uL3dlYi9hc3NldHMvYXdhcmR3YWxsZXRuZXdkZXNpZ24vanMvcGFnZXMvYWdlbnQvYWRkRGlhbG9nLmpzIiwid2VicGFjazovL0F3YXJkV2FsbGV0Ly4vYXNzZXRzL2JlbS90cy9zZXJ2aWNlL2xpc3RlbmVyLnRzIiwid2VicGFjazovL0F3YXJkV2FsbGV0Ly4vYXNzZXRzL2xlc3MtZGVwcmVjYXRlZC9pdGluZXJhcnktZm9ybS5sZXNzP2FkNzQiXSwic291cmNlc0NvbnRlbnQiOlsiaW1wb3J0ICcuLi8uLi8uLi9sZXNzLWRlcHJlY2F0ZWQvaXRpbmVyYXJ5LWZvcm0ubGVzcyc7XG5pbXBvcnQgeyBQUkVTRVRfU0VHTUVOVCwgU0VHTUVOVF9TRUxFQ1RPUiB9IGZyb20gJy4uL3RpbWVsaW5lL05vdGVzJztcbmltcG9ydCB7bGlzdGVuQWRkTmV3UGVyc29uUG9wdXB9IGZyb20gJy4uLy4uLy4uL2JlbS90cy9zZXJ2aWNlL2xpc3RlbmVyJztcbmltcG9ydCB7IHJlbmRlciB9IGZyb20gJ3JlYWN0LWRvbSc7XG5pbXBvcnQgTm90ZXMgZnJvbSAnLi4vdGltZWxpbmUvTm90ZXMnO1xuaW1wb3J0IFJlYWN0LCB7IHVzZUVmZmVjdCB9IGZyb20gJ3JlYWN0Jztcbi8qZXNsaW50IG5vLXVudXNlZC12YXJzOiBcImpxdWVyeXVpXCIqL1xuaW1wb3J0IGpxdWVyeXVpIGZyb20gJ2pxdWVyeXVpJztcblxubGlzdGVuQWRkTmV3UGVyc29uUG9wdXAoKTtcblxuZnVuY3Rpb24gSXRpbmVyYXJ5RWRpdChwcm9wcykge1xuXG4gICAgbGV0IHNlZ21lbnQgPSB7XG4gICAgICAgIHByZXNldDogUFJFU0VUX1NFR01FTlQsXG4gICAgICAgIG5vdGVzOiB7XG4gICAgICAgICAgICB0ZXh0OiBwcm9wcy50ZXh0LFxuICAgICAgICAgICAgZmlsZXM6IHByb3BzLmZpbGVzLFxuICAgICAgICB9LFxuICAgIH07XG5cbiAgICByZXR1cm4gKDxkaXY+XG4gICAgICAgIDxOb3RlcyBzZWdtZW50PXtzZWdtZW50fSBvcGVuZWQ9e3RydWV9IGZvcm09e3Byb3BzLmZvcm19Lz5cbiAgICA8L2Rpdj4pO1xufVxuXG5jb25zdCAkbm90ZXNSb3cgPSAkKCcucm93LW5vdGVzJyk7XG5pZiAoJG5vdGVzUm93Lmxlbmd0aCkge1xuICAgICRub3Rlc1Jvdy5hZnRlcignPGRpdiBpZD1cIm5vdGVzRWRpdG9yXCIgY2xhc3M9XCJlZGl0b3Itd3JhcFwiPjwvZGl2PicpO1xuICAgIGNvbnN0IGNvbnRlbnRFbGVtZW50ID0gZG9jdW1lbnQuZ2V0RWxlbWVudEJ5SWQoJ25vdGVzRWRpdG9yJyk7XG4gICAgY29uc3Qgbm90ZVRleHQgPSAkKFNFR01FTlRfU0VMRUNUT1IsICRub3Rlc1JvdykudmFsKCk7XG5cbiAgICBjb25zdCAkZmlsZXMgPSAkKCdmb3JtW2RhdGEtZmlsZXNdJyk7XG4gICAgY29uc3QgZmlsZXMgPSAkZmlsZXMuZGF0YSgnZmlsZXMnKTtcblxuICAgICRub3Rlc1Jvdy5oaWRlKCk7XG4gICAgcmVuZGVyKFxuICAgICAgICA8UmVhY3QuU3RyaWN0TW9kZT5cbiAgICAgICAgICAgIDxJdGluZXJhcnlFZGl0IHRleHQ9e25vdGVUZXh0fSBmaWxlcz17ZmlsZXN9IGZvcm09eyRub3Rlc1Jvdy5jbG9zZXN0KCdmb3JtJyl9Lz5cbiAgICAgICAgPC9SZWFjdC5TdHJpY3RNb2RlPixcbiAgICAgICAgY29udGVudEVsZW1lbnRcbiAgICApO1xufSIsImRlZmluZShbJ2pxdWVyeS1ib290JywgJ2xpYi9kaWFsb2cnLCAndHJhbnNsYXRvci1ib290JywgJ3JvdXRpbmcnXSwgZnVuY3Rpb24oJCwgZGlhbG9nKXtcblxuXHR2YXIgZGlhbG9nRWxlbWVudDtcblxuXHQvLyBBZGQgcGVyc29ucyBwb3B1cFxuXHRpZih0eXBlb2YoZGlhbG9nRWxlbWVudCkgPT0gJ3VuZGVmaW5lZCcpIHtcblx0XHRkaWFsb2dFbGVtZW50ID0gJCgnPGRpdiAvPicpLmFwcGVuZFRvKCdib2R5JykuaHRtbChcblx0XHRcdFx0VHJhbnNsYXRvci50cmFucygvKiogQERlc2MoXCJZb3UgaGF2ZSB0d28gb3B0aW9ucywgeW91IGNhbiBjb25uZWN0IHdpdGggYW5vdGhlciBwZXJzb24gb24gQXdhcmRXYWxsZXQsIG9yIHlvdSBjYW4ganVzdCBjcmVhdGUgYW5vdGhlciBuYW1lIHRvIGJldHRlciBvcmdhbml6ZSB5b3VyIHJld2FyZHMuXCIpICovJ2FnZW50cy5wb3B1cC5jb250ZW50Jylcblx0XHQpO1xuXHRcdGRpYWxvZy5jcmVhdGVOYW1lZCgncGVyc29ucy1tZW51JywgZGlhbG9nRWxlbWVudCwge1xuXHRcdFx0d2lkdGg6ICc2MDAnLFxuXHRcdFx0YXV0b09wZW46IGZhbHNlLFxuXHRcdFx0bW9kYWw6IHRydWUsXG5cdFx0XHR0aXRsZTogVHJhbnNsYXRvci50cmFucygvKiogQERlc2MoXCJTZWxlY3QgY29ubmVjdGlvbiB0eXBlXCIpICovICdhZ2VudHMucG9wdXAuaGVhZGVyJyksXG5cdFx0XHRidXR0b25zOiBbXG5cdFx0XHRcdHtcblx0XHRcdFx0XHQndGV4dCc6IFRyYW5zbGF0b3IudHJhbnMoLyoqIEBEZXNjKFwiQ29ubmVjdCB3aXRoIGFub3RoZXIgcGVyc29uXCIpICovJ2FnZW50cy5wb3B1cC5jb25uZWN0LmJ0bicpLFxuXHRcdFx0XHRcdCdjbGFzcyc6ICdidG4tYmx1ZSBzcGlubmVyYWJsZScsXG5cdFx0XHRcdFx0J2NsaWNrJzogZnVuY3Rpb24gKCkge1xuXHRcdFx0XHRcdFx0d2luZG93LmxvY2F0aW9uLmhyZWYgPSBSb3V0aW5nLmdlbmVyYXRlKCdhd19jcmVhdGVfY29ubmVjdGlvbicpXG5cdFx0XHRcdFx0fVxuXHRcdFx0XHR9LFxuXHRcdFx0XHR7XG5cdFx0XHRcdFx0J3RleHQnOiBUcmFuc2xhdG9yLnRyYW5zKC8qKiBARGVzYyhcIkp1c3QgYWRkIGEgbmV3IG5hbWVcIikgKi8nYWdlbnRzLnBvcHVwLmFkZC5idG4nKSxcblx0XHRcdFx0XHQnY2xhc3MnOiAnYnRuLWJsdWUgc3Bpbm5lcmFibGUnLFxuXHRcdFx0XHRcdCdjbGljayc6IGZ1bmN0aW9uICgpIHtcblx0XHRcdFx0XHRcdHdpbmRvdy5sb2NhdGlvbi5ocmVmID0gUm91dGluZy5nZW5lcmF0ZSgnYXdfYWRkX2FnZW50Jylcblx0XHRcdFx0XHR9XG5cdFx0XHRcdH1cblx0XHRcdF0sXG5cdFx0XHRvcGVuOiBmdW5jdGlvbiAoKSB7XG5cdFx0XHRcdC8vIFJlbW92ZSBib3R0b25zIGZvY3VzXG5cdFx0XHRcdCQoJy51aS1kaWFsb2cgOmJ1dHRvbicpLmJsdXIoKTtcbiAgICAgICAgICAgICAgICBoaXN0b3J5LnB1c2hTdGF0ZShudWxsLCBudWxsLCAnP2FkZC1uZXctcGVyc29uPXRydWUnKTtcblxuICAgICAgICAgICAgfSxcbiAgICAgICAgICAgIGNsb3NlOiBmdW5jdGlvbigpIHtcbiAgICAgICAgICAgICAgICBoaXN0b3J5LmJhY2soKTtcbiAgICAgICAgICAgIH1cblx0XHR9KTtcblx0fVxuXG5cdHZhciBjbGlja0hhbmRsZXIgPSBmdW5jdGlvbigpIHtcblx0XHRkaWFsb2dFbGVtZW50LmRpYWxvZygnb3BlbicpO1xuXHR9O1xuXG5cdHJldHVybiBjbGlja0hhbmRsZXI7XG59KTtcbiIsIi8vIGRlcHJlY2F0ZWRcbmV4cG9ydCBmdW5jdGlvbiBsaXN0ZW5BZGROZXdQZXJzb25Qb3B1cCgpIHtcbiAgICAkKGRvY3VtZW50KS5vbignY2xpY2snLCAnLmpzLWFkZC1uZXctcGVyc29uLCAjYWRkLXBlcnNvbi1idG4sIC5qcy1wZXJzb25zLW1lbnUgYVtocmVmPVwiL3VzZXIvY29ubmVjdGlvbnNcIl0uYWRkJywgZnVuY3Rpb24gKGUpIHtcbiAgICAgICAgZS5wcmV2ZW50RGVmYXVsdCgpO1xuICAgICAgICAvLyBlc2xpbnQtZGlzYWJsZS1uZXh0LWxpbmUgQHR5cGVzY3JpcHQtZXNsaW50L25vLXVuc2FmZS1jYWxsLCBAdHlwZXNjcmlwdC1lc2xpbnQvbm8tdmFyLXJlcXVpcmVzXG4gICAgICAgIHJlcXVpcmUoJy4uLy4uLy4uLy4uL3dlYi9hc3NldHMvYXdhcmR3YWxsZXRuZXdkZXNpZ24vanMvcGFnZXMvYWdlbnQvYWRkRGlhbG9nJykoKTtcbiAgICB9KTtcbn1cbiIsIi8vIGV4dHJhY3RlZCBieSBtaW5pLWNzcy1leHRyYWN0LXBsdWdpblxuZXhwb3J0IHt9OyJdLCJuYW1lcyI6WyJQUkVTRVRfU0VHTUVOVCIsIlNFR01FTlRfU0VMRUNUT1IiLCJsaXN0ZW5BZGROZXdQZXJzb25Qb3B1cCIsInJlbmRlciIsIk5vdGVzIiwiUmVhY3QiLCJ1c2VFZmZlY3QiLCJqcXVlcnl1aSIsIkl0aW5lcmFyeUVkaXQiLCJwcm9wcyIsInNlZ21lbnQiLCJwcmVzZXQiLCJub3RlcyIsInRleHQiLCJmaWxlcyIsImNyZWF0ZUVsZW1lbnQiLCJvcGVuZWQiLCJmb3JtIiwiJG5vdGVzUm93IiwiJCIsImxlbmd0aCIsImFmdGVyIiwiY29udGVudEVsZW1lbnQiLCJkb2N1bWVudCIsImdldEVsZW1lbnRCeUlkIiwibm90ZVRleHQiLCJ2YWwiLCIkZmlsZXMiLCJkYXRhIiwiaGlkZSIsIlN0cmljdE1vZGUiLCJjbG9zZXN0IiwiZGVmaW5lIiwiZGlhbG9nIiwiZGlhbG9nRWxlbWVudCIsImFwcGVuZFRvIiwiaHRtbCIsIlRyYW5zbGF0b3IiLCJ0cmFucyIsImNyZWF0ZU5hbWVkIiwid2lkdGgiLCJhdXRvT3BlbiIsIm1vZGFsIiwidGl0bGUiLCJidXR0b25zIiwiY2xpY2siLCJ3aW5kb3ciLCJsb2NhdGlvbiIsImhyZWYiLCJSb3V0aW5nIiwiZ2VuZXJhdGUiLCJvcGVuIiwiYmx1ciIsImhpc3RvcnkiLCJwdXNoU3RhdGUiLCJjbG9zZSIsImJhY2siLCJjbGlja0hhbmRsZXIiLCJvbiIsImUiLCJwcmV2ZW50RGVmYXVsdCIsInJlcXVpcmUiXSwic291cmNlUm9vdCI6IiJ9