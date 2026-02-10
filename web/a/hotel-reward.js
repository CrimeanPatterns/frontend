"use strict";
(self["webpackChunkAwardWallet"] = self["webpackChunkAwardWallet"] || []).push([["hotel-reward"],{

/***/ "./assets/entry-point-deprecated/hotel-reward/index.js":
/*!*************************************************************!*\
  !*** ./assets/entry-point-deprecated/hotel-reward/index.js ***!
  \*************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony import */ var core_js_modules_es_object_keys_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! core-js/modules/es.object.keys.js */ "./node_modules/core-js/modules/es.object.keys.js");
/* harmony import */ var core_js_modules_es_object_keys_js__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_object_keys_js__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _bem_ts_starter__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ../../bem/ts/starter */ "./assets/bem/ts/starter.ts");
/* harmony import */ var _less_deprecated_hotel_reward_less__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ../../less-deprecated/hotel-reward.less */ "./assets/less-deprecated/hotel-reward.less");
/* harmony import */ var react_dom__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! react-dom */ "./node_modules/react-dom/index.js");
/* harmony import */ var _js_deprecated_component_deprecated_hotel_reward_HotelReward__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! ../../js-deprecated/component-deprecated/hotel-reward/HotelReward */ "./assets/js-deprecated/component-deprecated/hotel-reward/HotelReward.js");
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! react */ "./node_modules/react/index.js");
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_5___default = /*#__PURE__*/__webpack_require__.n(react__WEBPACK_IMPORTED_MODULE_5__);






var contentElement = document.getElementById('content');
var primaryList = JSON.parse(contentElement.dataset.primaryList);
(0,react_dom__WEBPACK_IMPORTED_MODULE_3__.render)( /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_5___default().createElement((react__WEBPACK_IMPORTED_MODULE_5___default().StrictMode), null, /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_5___default().createElement(_js_deprecated_component_deprecated_hotel_reward_HotelReward__WEBPACK_IMPORTED_MODULE_4__["default"], {
  primaryList: primaryList
})), contentElement);

/***/ }),

/***/ "./assets/entry-point-deprecated/main.js":
/*!***********************************************!*\
  !*** ./assets/entry-point-deprecated/main.js ***!
  \***********************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var core_js_modules_es_array_find_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! core-js/modules/es.array.find.js */ "./node_modules/core-js/modules/es.array.find.js");
/* harmony import */ var core_js_modules_es_array_find_js__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_array_find_js__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var core_js_modules_es_object_to_string_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! core-js/modules/es.object.to-string.js */ "./node_modules/core-js/modules/es.object.to-string.js");
/* harmony import */ var core_js_modules_es_object_to_string_js__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_object_to_string_js__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var core_js_modules_es_regexp_constructor_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! core-js/modules/es.regexp.constructor.js */ "./node_modules/core-js/modules/es.regexp.constructor.js");
/* harmony import */ var core_js_modules_es_regexp_constructor_js__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_regexp_constructor_js__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var core_js_modules_es_regexp_exec_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! core-js/modules/es.regexp.exec.js */ "./node_modules/core-js/modules/es.regexp.exec.js");
/* harmony import */ var core_js_modules_es_regexp_exec_js__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_regexp_exec_js__WEBPACK_IMPORTED_MODULE_3__);
/* harmony import */ var core_js_modules_es_regexp_to_string_js__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! core-js/modules/es.regexp.to-string.js */ "./node_modules/core-js/modules/es.regexp.to-string.js");
/* harmony import */ var core_js_modules_es_regexp_to_string_js__WEBPACK_IMPORTED_MODULE_4___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_regexp_to_string_js__WEBPACK_IMPORTED_MODULE_4__);
/* harmony import */ var core_js_modules_es_string_replace_js__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! core-js/modules/es.string.replace.js */ "./node_modules/core-js/modules/es.string.replace.js");
/* harmony import */ var core_js_modules_es_string_replace_js__WEBPACK_IMPORTED_MODULE_5___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_string_replace_js__WEBPACK_IMPORTED_MODULE_5__);
/* harmony import */ var _less_deprecated_main_less__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! ../less-deprecated/main.less */ "./assets/less-deprecated/main.less");
/* harmony import */ var jqueryui__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__(/*! jqueryui */ "./web/assets/common/vendors/jquery-ui/jquery-ui.min.js");
/* harmony import */ var jqueryui__WEBPACK_IMPORTED_MODULE_7___default = /*#__PURE__*/__webpack_require__.n(jqueryui__WEBPACK_IMPORTED_MODULE_7__);
/* provided dependency */ var $ = __webpack_require__(/*! jquery */ "./web/assets/common/vendors/jquery/dist/jquery.js");







/*eslint no-unused-vars: "jqueryui"*/
 // .menu()

(function main() {
  toggleSidebarVisible();
  initDropdowns($('body'));
})();
function toggleSidebarVisible() {
  $(window).resize(function () {
    var sizeWindow = $('body').width();
    if (sizeWindow < 1024) {
      $('.main-body').addClass('small-desktop');
    } else {
      $('.main-body').removeClass('small-desktop');
    }
    if ($('.main-body').hasClass('manual-hidden')) return;
    if (sizeWindow < 1024) {
      $('.main-body').addClass('hide-menu');
    } else {
      $('.main-body').removeClass('hide-menu');
    }
  });
  var menuClose = document.querySelector('.menu-close');
  if (menuClose) {
    var menuBody = document.querySelector('.main-body');
    menuClose.onclick = function () {
      menuBody.classList.toggle('hide-menu');
      menuBody.classList.add('manual-hidden');
    };
  }
}
function initDropdowns(area, options) {
  options = options || {};
  var selector = '[data-role="dropdown"]';
  var dropdown = undefined != area ? $(area).find(selector).addBack(selector) : $(selector);
  var ofParentSelector = options.ofParent || 'li';
  dropdown.each(function (id, el) {
    $(el).removeAttr('data-role').menu().hide().on('menu.hide', function (e) {
      $(e.target).hide(200);
    });
    $('[data-target=' + $(el).data('id') + ']').on('click', function (e) {
      e.preventDefault();
      e.stopPropagation();
      $('.ui-menu:visible').not('[data-id="' + $(this).data('target') + '"]').trigger('menu.hide');
      $(el).toggle(0, function () {
        var _options;
        $(el).position({
          my: ((_options = options) === null || _options === void 0 || (_options = _options.position) === null || _options === void 0 ? void 0 : _options.my) || 'left top',
          at: "left bottom",
          of: $(e.target).parents(ofParentSelector).find('.rel-this'),
          collision: "fit"
        });
      });
    });
  });
  $(document).on('click', function (e) {
    $('.ui-menu:visible').trigger('menu.hide');
  });
}
;
function autoCompleteRenderItem() {
  var renderFunction = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : null;
  if (null === renderFunction) {
    renderFunction = function renderFunction(ul, item) {
      var regex = new RegExp('(' + this.element.val().replace(/[^A-Za-z0-9А-Яа-я]+/g, '') + ')', 'gi'),
        html = $('<div/>').text(item.label).html().replace(regex, '<b>$1</b>');
      return $('<li></li>').data('item.autocomplete', item).append($('<a></a>').html(html)).appendTo(ul);
    };
  }
  $.ui.autocomplete.prototype._renderItem = renderFunction;
}
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = ({
  initDropdowns: initDropdowns,
  autoCompleteRenderItem: autoCompleteRenderItem
});

/***/ }),

/***/ "./assets/js-deprecated/component-deprecated/ToolTip.js":
/*!**************************************************************!*\
  !*** ./assets/js-deprecated/component-deprecated/ToolTip.js ***!
  \**************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var core_js_modules_es_array_find_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! core-js/modules/es.array.find.js */ "./node_modules/core-js/modules/es.array.find.js");
/* harmony import */ var core_js_modules_es_array_find_js__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_array_find_js__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var core_js_modules_es_object_to_string_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! core-js/modules/es.object.to-string.js */ "./node_modules/core-js/modules/es.object.to-string.js");
/* harmony import */ var core_js_modules_es_object_to_string_js__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_object_to_string_js__WEBPACK_IMPORTED_MODULE_1__);
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
/* harmony import */ var core_js_modules_web_dom_collections_for_each_js__WEBPACK_IMPORTED_MODULE_10__ = __webpack_require__(/*! core-js/modules/web.dom-collections.for-each.js */ "./node_modules/core-js/modules/web.dom-collections.for-each.js");
/* harmony import */ var core_js_modules_web_dom_collections_for_each_js__WEBPACK_IMPORTED_MODULE_10___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_web_dom_collections_for_each_js__WEBPACK_IMPORTED_MODULE_10__);
/* harmony import */ var core_js_modules_es_object_get_own_property_descriptors_js__WEBPACK_IMPORTED_MODULE_11__ = __webpack_require__(/*! core-js/modules/es.object.get-own-property-descriptors.js */ "./node_modules/core-js/modules/es.object.get-own-property-descriptors.js");
/* harmony import */ var core_js_modules_es_object_get_own_property_descriptors_js__WEBPACK_IMPORTED_MODULE_11___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_object_get_own_property_descriptors_js__WEBPACK_IMPORTED_MODULE_11__);
/* harmony import */ var core_js_modules_es_symbol_iterator_js__WEBPACK_IMPORTED_MODULE_12__ = __webpack_require__(/*! core-js/modules/es.symbol.iterator.js */ "./node_modules/core-js/modules/es.symbol.iterator.js");
/* harmony import */ var core_js_modules_es_symbol_iterator_js__WEBPACK_IMPORTED_MODULE_12___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_symbol_iterator_js__WEBPACK_IMPORTED_MODULE_12__);
/* harmony import */ var core_js_modules_es_array_iterator_js__WEBPACK_IMPORTED_MODULE_13__ = __webpack_require__(/*! core-js/modules/es.array.iterator.js */ "./node_modules/core-js/modules/es.array.iterator.js");
/* harmony import */ var core_js_modules_es_array_iterator_js__WEBPACK_IMPORTED_MODULE_13___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_array_iterator_js__WEBPACK_IMPORTED_MODULE_13__);
/* harmony import */ var core_js_modules_es_string_iterator_js__WEBPACK_IMPORTED_MODULE_14__ = __webpack_require__(/*! core-js/modules/es.string.iterator.js */ "./node_modules/core-js/modules/es.string.iterator.js");
/* harmony import */ var core_js_modules_es_string_iterator_js__WEBPACK_IMPORTED_MODULE_14___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_string_iterator_js__WEBPACK_IMPORTED_MODULE_14__);
/* harmony import */ var core_js_modules_web_dom_collections_iterator_js__WEBPACK_IMPORTED_MODULE_15__ = __webpack_require__(/*! core-js/modules/web.dom-collections.iterator.js */ "./node_modules/core-js/modules/web.dom-collections.iterator.js");
/* harmony import */ var core_js_modules_web_dom_collections_iterator_js__WEBPACK_IMPORTED_MODULE_15___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_web_dom_collections_iterator_js__WEBPACK_IMPORTED_MODULE_15__);
/* provided dependency */ var $ = __webpack_require__(/*! jquery */ "./web/assets/common/vendors/jquery/dist/jquery.js");
function _typeof(o) { "@babel/helpers - typeof"; return _typeof = "function" == typeof Symbol && "symbol" == typeof Symbol.iterator ? function (o) { return typeof o; } : function (o) { return o && "function" == typeof Symbol && o.constructor === Symbol && o !== Symbol.prototype ? "symbol" : typeof o; }, _typeof(o); }
function ownKeys(e, r) { var t = Object.keys(e); if (Object.getOwnPropertySymbols) { var o = Object.getOwnPropertySymbols(e); r && (o = o.filter(function (r) { return Object.getOwnPropertyDescriptor(e, r).enumerable; })), t.push.apply(t, o); } return t; }
function _objectSpread(e) { for (var r = 1; r < arguments.length; r++) { var t = null != arguments[r] ? arguments[r] : {}; r % 2 ? ownKeys(Object(t), !0).forEach(function (r) { _defineProperty(e, r, t[r]); }) : Object.getOwnPropertyDescriptors ? Object.defineProperties(e, Object.getOwnPropertyDescriptors(t)) : ownKeys(Object(t)).forEach(function (r) { Object.defineProperty(e, r, Object.getOwnPropertyDescriptor(t, r)); }); } return e; }
function _defineProperty(obj, key, value) { key = _toPropertyKey(key); if (key in obj) { Object.defineProperty(obj, key, { value: value, enumerable: true, configurable: true, writable: true }); } else { obj[key] = value; } return obj; }
function _toPropertyKey(arg) { var key = _toPrimitive(arg, "string"); return _typeof(key) === "symbol" ? key : String(key); }
function _toPrimitive(input, hint) { if (_typeof(input) !== "object" || input === null) return input; var prim = input[Symbol.toPrimitive]; if (prim !== undefined) { var res = prim.call(input, hint || "default"); if (_typeof(res) !== "object") return res; throw new TypeError("@@toPrimitive must return a primitive value."); } return (hint === "string" ? String : Number)(input); }
















function ToolTip(context, options) {
  var tooltip,
    selector = '[data-role="tooltip"]';
  if (undefined !== context) {
    tooltip = $(context).find(selector).addBack(selector);
  } else {
    tooltip = $(selector);
  }
  tooltip.tooltip({
    tooltipClass: 'custom-tooltip-styling',
    position: _objectSpread({
      my: 'center bottom',
      at: 'center top',
      collision: 'flipfit flip',
      using: function using(position, feedback) {
        $(this).css(position);
        $('<div>').addClass('arrow').addClass(feedback.vertical).css({
          marginLeft: feedback.target.left - feedback.element.left - 6 - 7 + feedback.target.width / 2,
          width: 0
        }).appendTo(this);
      }
    }, options)
  }).removeAttr('data-role').off('focusin focusout').prop('tooltip-initialized', true);
}
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (ToolTip);

/***/ }),

/***/ "./assets/js-deprecated/component-deprecated/hotel-reward/HotelReward.js":
/*!*******************************************************************************!*\
  !*** ./assets/js-deprecated/component-deprecated/hotel-reward/HotelReward.js ***!
  \*******************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var core_js_modules_es_regexp_exec_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! core-js/modules/es.regexp.exec.js */ "./node_modules/core-js/modules/es.regexp.exec.js");
/* harmony import */ var core_js_modules_es_regexp_exec_js__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_regexp_exec_js__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var core_js_modules_es_string_replace_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! core-js/modules/es.string.replace.js */ "./node_modules/core-js/modules/es.string.replace.js");
/* harmony import */ var core_js_modules_es_string_replace_js__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_string_replace_js__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var core_js_modules_es_function_name_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! core-js/modules/es.function.name.js */ "./node_modules/core-js/modules/es.function.name.js");
/* harmony import */ var core_js_modules_es_function_name_js__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_function_name_js__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var core_js_modules_es_string_link_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! core-js/modules/es.string.link.js */ "./node_modules/core-js/modules/es.string.link.js");
/* harmony import */ var core_js_modules_es_string_link_js__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_string_link_js__WEBPACK_IMPORTED_MODULE_3__);
/* harmony import */ var core_js_modules_es_array_find_js__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! core-js/modules/es.array.find.js */ "./node_modules/core-js/modules/es.array.find.js");
/* harmony import */ var core_js_modules_es_array_find_js__WEBPACK_IMPORTED_MODULE_4___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_array_find_js__WEBPACK_IMPORTED_MODULE_4__);
/* harmony import */ var core_js_modules_es_object_to_string_js__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! core-js/modules/es.object.to-string.js */ "./node_modules/core-js/modules/es.object.to-string.js");
/* harmony import */ var core_js_modules_es_object_to_string_js__WEBPACK_IMPORTED_MODULE_5___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_object_to_string_js__WEBPACK_IMPORTED_MODULE_5__);
/* harmony import */ var core_js_modules_es_array_map_js__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! core-js/modules/es.array.map.js */ "./node_modules/core-js/modules/es.array.map.js");
/* harmony import */ var core_js_modules_es_array_map_js__WEBPACK_IMPORTED_MODULE_6___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_array_map_js__WEBPACK_IMPORTED_MODULE_6__);
/* harmony import */ var core_js_modules_es_regexp_constructor_js__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__(/*! core-js/modules/es.regexp.constructor.js */ "./node_modules/core-js/modules/es.regexp.constructor.js");
/* harmony import */ var core_js_modules_es_regexp_constructor_js__WEBPACK_IMPORTED_MODULE_7___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_regexp_constructor_js__WEBPACK_IMPORTED_MODULE_7__);
/* harmony import */ var core_js_modules_es_regexp_to_string_js__WEBPACK_IMPORTED_MODULE_8__ = __webpack_require__(/*! core-js/modules/es.regexp.to-string.js */ "./node_modules/core-js/modules/es.regexp.to-string.js");
/* harmony import */ var core_js_modules_es_regexp_to_string_js__WEBPACK_IMPORTED_MODULE_8___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_regexp_to_string_js__WEBPACK_IMPORTED_MODULE_8__);
/* harmony import */ var core_js_modules_es_symbol_js__WEBPACK_IMPORTED_MODULE_9__ = __webpack_require__(/*! core-js/modules/es.symbol.js */ "./node_modules/core-js/modules/es.symbol.js");
/* harmony import */ var core_js_modules_es_symbol_js__WEBPACK_IMPORTED_MODULE_9___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_symbol_js__WEBPACK_IMPORTED_MODULE_9__);
/* harmony import */ var core_js_modules_es_symbol_description_js__WEBPACK_IMPORTED_MODULE_10__ = __webpack_require__(/*! core-js/modules/es.symbol.description.js */ "./node_modules/core-js/modules/es.symbol.description.js");
/* harmony import */ var core_js_modules_es_symbol_description_js__WEBPACK_IMPORTED_MODULE_10___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_symbol_description_js__WEBPACK_IMPORTED_MODULE_10__);
/* harmony import */ var core_js_modules_es_symbol_iterator_js__WEBPACK_IMPORTED_MODULE_11__ = __webpack_require__(/*! core-js/modules/es.symbol.iterator.js */ "./node_modules/core-js/modules/es.symbol.iterator.js");
/* harmony import */ var core_js_modules_es_symbol_iterator_js__WEBPACK_IMPORTED_MODULE_11___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_symbol_iterator_js__WEBPACK_IMPORTED_MODULE_11__);
/* harmony import */ var core_js_modules_es_array_iterator_js__WEBPACK_IMPORTED_MODULE_12__ = __webpack_require__(/*! core-js/modules/es.array.iterator.js */ "./node_modules/core-js/modules/es.array.iterator.js");
/* harmony import */ var core_js_modules_es_array_iterator_js__WEBPACK_IMPORTED_MODULE_12___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_array_iterator_js__WEBPACK_IMPORTED_MODULE_12__);
/* harmony import */ var core_js_modules_es_string_iterator_js__WEBPACK_IMPORTED_MODULE_13__ = __webpack_require__(/*! core-js/modules/es.string.iterator.js */ "./node_modules/core-js/modules/es.string.iterator.js");
/* harmony import */ var core_js_modules_es_string_iterator_js__WEBPACK_IMPORTED_MODULE_13___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_string_iterator_js__WEBPACK_IMPORTED_MODULE_13__);
/* harmony import */ var core_js_modules_web_dom_collections_iterator_js__WEBPACK_IMPORTED_MODULE_14__ = __webpack_require__(/*! core-js/modules/web.dom-collections.iterator.js */ "./node_modules/core-js/modules/web.dom-collections.iterator.js");
/* harmony import */ var core_js_modules_web_dom_collections_iterator_js__WEBPACK_IMPORTED_MODULE_14___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_web_dom_collections_iterator_js__WEBPACK_IMPORTED_MODULE_14__);
/* harmony import */ var core_js_modules_es_array_slice_js__WEBPACK_IMPORTED_MODULE_15__ = __webpack_require__(/*! core-js/modules/es.array.slice.js */ "./node_modules/core-js/modules/es.array.slice.js");
/* harmony import */ var core_js_modules_es_array_slice_js__WEBPACK_IMPORTED_MODULE_15___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_array_slice_js__WEBPACK_IMPORTED_MODULE_15__);
/* harmony import */ var core_js_modules_es_array_from_js__WEBPACK_IMPORTED_MODULE_16__ = __webpack_require__(/*! core-js/modules/es.array.from.js */ "./node_modules/core-js/modules/es.array.from.js");
/* harmony import */ var core_js_modules_es_array_from_js__WEBPACK_IMPORTED_MODULE_16___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_array_from_js__WEBPACK_IMPORTED_MODULE_16__);
/* harmony import */ var core_js_modules_es_symbol_to_primitive_js__WEBPACK_IMPORTED_MODULE_17__ = __webpack_require__(/*! core-js/modules/es.symbol.to-primitive.js */ "./node_modules/core-js/modules/es.symbol.to-primitive.js");
/* harmony import */ var core_js_modules_es_symbol_to_primitive_js__WEBPACK_IMPORTED_MODULE_17___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_symbol_to_primitive_js__WEBPACK_IMPORTED_MODULE_17__);
/* harmony import */ var core_js_modules_es_date_to_primitive_js__WEBPACK_IMPORTED_MODULE_18__ = __webpack_require__(/*! core-js/modules/es.date.to-primitive.js */ "./node_modules/core-js/modules/es.date.to-primitive.js");
/* harmony import */ var core_js_modules_es_date_to_primitive_js__WEBPACK_IMPORTED_MODULE_18___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_date_to_primitive_js__WEBPACK_IMPORTED_MODULE_18__);
/* harmony import */ var core_js_modules_es_number_constructor_js__WEBPACK_IMPORTED_MODULE_19__ = __webpack_require__(/*! core-js/modules/es.number.constructor.js */ "./node_modules/core-js/modules/es.number.constructor.js");
/* harmony import */ var core_js_modules_es_number_constructor_js__WEBPACK_IMPORTED_MODULE_19___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_number_constructor_js__WEBPACK_IMPORTED_MODULE_19__);
/* harmony import */ var core_js_modules_es_object_keys_js__WEBPACK_IMPORTED_MODULE_20__ = __webpack_require__(/*! core-js/modules/es.object.keys.js */ "./node_modules/core-js/modules/es.object.keys.js");
/* harmony import */ var core_js_modules_es_object_keys_js__WEBPACK_IMPORTED_MODULE_20___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_object_keys_js__WEBPACK_IMPORTED_MODULE_20__);
/* harmony import */ var core_js_modules_es_array_filter_js__WEBPACK_IMPORTED_MODULE_21__ = __webpack_require__(/*! core-js/modules/es.array.filter.js */ "./node_modules/core-js/modules/es.array.filter.js");
/* harmony import */ var core_js_modules_es_array_filter_js__WEBPACK_IMPORTED_MODULE_21___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_array_filter_js__WEBPACK_IMPORTED_MODULE_21__);
/* harmony import */ var core_js_modules_es_object_get_own_property_descriptor_js__WEBPACK_IMPORTED_MODULE_22__ = __webpack_require__(/*! core-js/modules/es.object.get-own-property-descriptor.js */ "./node_modules/core-js/modules/es.object.get-own-property-descriptor.js");
/* harmony import */ var core_js_modules_es_object_get_own_property_descriptor_js__WEBPACK_IMPORTED_MODULE_22___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_object_get_own_property_descriptor_js__WEBPACK_IMPORTED_MODULE_22__);
/* harmony import */ var core_js_modules_web_dom_collections_for_each_js__WEBPACK_IMPORTED_MODULE_23__ = __webpack_require__(/*! core-js/modules/web.dom-collections.for-each.js */ "./node_modules/core-js/modules/web.dom-collections.for-each.js");
/* harmony import */ var core_js_modules_web_dom_collections_for_each_js__WEBPACK_IMPORTED_MODULE_23___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_web_dom_collections_for_each_js__WEBPACK_IMPORTED_MODULE_23__);
/* harmony import */ var core_js_modules_es_object_get_own_property_descriptors_js__WEBPACK_IMPORTED_MODULE_24__ = __webpack_require__(/*! core-js/modules/es.object.get-own-property-descriptors.js */ "./node_modules/core-js/modules/es.object.get-own-property-descriptors.js");
/* harmony import */ var core_js_modules_es_object_get_own_property_descriptors_js__WEBPACK_IMPORTED_MODULE_24___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_object_get_own_property_descriptors_js__WEBPACK_IMPORTED_MODULE_24__);
/* harmony import */ var core_js_modules_es_promise_js__WEBPACK_IMPORTED_MODULE_25__ = __webpack_require__(/*! core-js/modules/es.promise.js */ "./node_modules/core-js/modules/es.promise.js");
/* harmony import */ var core_js_modules_es_promise_js__WEBPACK_IMPORTED_MODULE_25___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_promise_js__WEBPACK_IMPORTED_MODULE_25__);
/* harmony import */ var core_js_modules_es_symbol_async_iterator_js__WEBPACK_IMPORTED_MODULE_26__ = __webpack_require__(/*! core-js/modules/es.symbol.async-iterator.js */ "./node_modules/core-js/modules/es.symbol.async-iterator.js");
/* harmony import */ var core_js_modules_es_symbol_async_iterator_js__WEBPACK_IMPORTED_MODULE_26___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_symbol_async_iterator_js__WEBPACK_IMPORTED_MODULE_26__);
/* harmony import */ var core_js_modules_es_symbol_to_string_tag_js__WEBPACK_IMPORTED_MODULE_27__ = __webpack_require__(/*! core-js/modules/es.symbol.to-string-tag.js */ "./node_modules/core-js/modules/es.symbol.to-string-tag.js");
/* harmony import */ var core_js_modules_es_symbol_to_string_tag_js__WEBPACK_IMPORTED_MODULE_27___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_symbol_to_string_tag_js__WEBPACK_IMPORTED_MODULE_27__);
/* harmony import */ var core_js_modules_es_json_to_string_tag_js__WEBPACK_IMPORTED_MODULE_28__ = __webpack_require__(/*! core-js/modules/es.json.to-string-tag.js */ "./node_modules/core-js/modules/es.json.to-string-tag.js");
/* harmony import */ var core_js_modules_es_json_to_string_tag_js__WEBPACK_IMPORTED_MODULE_28___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_json_to_string_tag_js__WEBPACK_IMPORTED_MODULE_28__);
/* harmony import */ var core_js_modules_es_math_to_string_tag_js__WEBPACK_IMPORTED_MODULE_29__ = __webpack_require__(/*! core-js/modules/es.math.to-string-tag.js */ "./node_modules/core-js/modules/es.math.to-string-tag.js");
/* harmony import */ var core_js_modules_es_math_to_string_tag_js__WEBPACK_IMPORTED_MODULE_29___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_math_to_string_tag_js__WEBPACK_IMPORTED_MODULE_29__);
/* harmony import */ var core_js_modules_es_object_get_prototype_of_js__WEBPACK_IMPORTED_MODULE_30__ = __webpack_require__(/*! core-js/modules/es.object.get-prototype-of.js */ "./node_modules/core-js/modules/es.object.get-prototype-of.js");
/* harmony import */ var core_js_modules_es_object_get_prototype_of_js__WEBPACK_IMPORTED_MODULE_30___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_object_get_prototype_of_js__WEBPACK_IMPORTED_MODULE_30__);
/* harmony import */ var _bem_ts_service_axios__WEBPACK_IMPORTED_MODULE_31__ = __webpack_require__(/*! ../../../bem/ts/service/axios */ "./assets/bem/ts/service/axios/index.js");
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_32__ = __webpack_require__(/*! react */ "./node_modules/react/index.js");
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_32___default = /*#__PURE__*/__webpack_require__.n(react__WEBPACK_IMPORTED_MODULE_32__);
/* harmony import */ var _bem_ts_service_router__WEBPACK_IMPORTED_MODULE_33__ = __webpack_require__(/*! ../../../bem/ts/service/router */ "./assets/bem/ts/service/router.ts");
/* harmony import */ var _bem_ts_service_translator__WEBPACK_IMPORTED_MODULE_34__ = __webpack_require__(/*! ../../../bem/ts/service/translator */ "./assets/bem/ts/service/translator.ts");
/* harmony import */ var classnames__WEBPACK_IMPORTED_MODULE_35__ = __webpack_require__(/*! classnames */ "./node_modules/classnames/index.js");
/* harmony import */ var classnames__WEBPACK_IMPORTED_MODULE_35___default = /*#__PURE__*/__webpack_require__.n(classnames__WEBPACK_IMPORTED_MODULE_35__);
/* harmony import */ var lodash_filter__WEBPACK_IMPORTED_MODULE_36__ = __webpack_require__(/*! lodash/filter */ "./node_modules/lodash/filter.js");
/* harmony import */ var lodash_filter__WEBPACK_IMPORTED_MODULE_36___default = /*#__PURE__*/__webpack_require__.n(lodash_filter__WEBPACK_IMPORTED_MODULE_36__);
/* harmony import */ var lodash_has__WEBPACK_IMPORTED_MODULE_37__ = __webpack_require__(/*! lodash/has */ "./node_modules/lodash/has.js");
/* harmony import */ var lodash_has__WEBPACK_IMPORTED_MODULE_37___default = /*#__PURE__*/__webpack_require__.n(lodash_has__WEBPACK_IMPORTED_MODULE_37__);
/* harmony import */ var lodash_isEmpty__WEBPACK_IMPORTED_MODULE_38__ = __webpack_require__(/*! lodash/isEmpty */ "./node_modules/lodash/isEmpty.js");
/* harmony import */ var lodash_isEmpty__WEBPACK_IMPORTED_MODULE_38___default = /*#__PURE__*/__webpack_require__.n(lodash_isEmpty__WEBPACK_IMPORTED_MODULE_38__);
/* harmony import */ var _ToolTip__WEBPACK_IMPORTED_MODULE_39__ = __webpack_require__(/*! ../ToolTip */ "./assets/js-deprecated/component-deprecated/ToolTip.js");
/* harmony import */ var jqueryui__WEBPACK_IMPORTED_MODULE_40__ = __webpack_require__(/*! jqueryui */ "./web/assets/common/vendors/jquery-ui/jquery-ui.min.js");
/* harmony import */ var jqueryui__WEBPACK_IMPORTED_MODULE_40___default = /*#__PURE__*/__webpack_require__.n(jqueryui__WEBPACK_IMPORTED_MODULE_40__);
/* harmony import */ var lodash_map__WEBPACK_IMPORTED_MODULE_41__ = __webpack_require__(/*! lodash/map */ "./node_modules/lodash/map.js");
/* harmony import */ var lodash_map__WEBPACK_IMPORTED_MODULE_41___default = /*#__PURE__*/__webpack_require__.n(lodash_map__WEBPACK_IMPORTED_MODULE_41__);
/* harmony import */ var lodash_mapValues__WEBPACK_IMPORTED_MODULE_42__ = __webpack_require__(/*! lodash/mapValues */ "./node_modules/lodash/mapValues.js");
/* harmony import */ var lodash_mapValues__WEBPACK_IMPORTED_MODULE_42___default = /*#__PURE__*/__webpack_require__.n(lodash_mapValues__WEBPACK_IMPORTED_MODULE_42__);
/* harmony import */ var lodash_uniqBy__WEBPACK_IMPORTED_MODULE_43__ = __webpack_require__(/*! lodash/uniqBy */ "./node_modules/lodash/uniqBy.js");
/* harmony import */ var lodash_uniqBy__WEBPACK_IMPORTED_MODULE_43___default = /*#__PURE__*/__webpack_require__.n(lodash_uniqBy__WEBPACK_IMPORTED_MODULE_43__);
/* harmony import */ var lodash_values__WEBPACK_IMPORTED_MODULE_44__ = __webpack_require__(/*! lodash/values */ "./node_modules/lodash/values.js");
/* harmony import */ var lodash_values__WEBPACK_IMPORTED_MODULE_44___default = /*#__PURE__*/__webpack_require__.n(lodash_values__WEBPACK_IMPORTED_MODULE_44__);
/* harmony import */ var _bem_ts_service_env__WEBPACK_IMPORTED_MODULE_45__ = __webpack_require__(/*! ../../../bem/ts/service/env */ "./assets/bem/ts/service/env.ts");
/* harmony import */ var _component_deprecated_milevalue_milevalue_box__WEBPACK_IMPORTED_MODULE_46__ = __webpack_require__(/*! ../../component-deprecated/milevalue/milevalue-box */ "./assets/js-deprecated/component-deprecated/milevalue/milevalue-box.js");
/* harmony import */ var _entry_point_deprecated_main__WEBPACK_IMPORTED_MODULE_47__ = __webpack_require__(/*! ../../../entry-point-deprecated/main */ "./assets/entry-point-deprecated/main.js");
/* provided dependency */ var $ = __webpack_require__(/*! jquery */ "./web/assets/common/vendors/jquery/dist/jquery.js");
function _typeof(o) { "@babel/helpers - typeof"; return _typeof = "function" == typeof Symbol && "symbol" == typeof Symbol.iterator ? function (o) { return typeof o; } : function (o) { return o && "function" == typeof Symbol && o.constructor === Symbol && o !== Symbol.prototype ? "symbol" : typeof o; }, _typeof(o); }
function _regeneratorRuntime() { "use strict"; /*! regenerator-runtime -- Copyright (c) 2014-present, Facebook, Inc. -- license (MIT): https://github.com/facebook/regenerator/blob/main/LICENSE */ _regeneratorRuntime = function _regeneratorRuntime() { return e; }; var t, e = {}, r = Object.prototype, n = r.hasOwnProperty, o = Object.defineProperty || function (t, e, r) { t[e] = r.value; }, i = "function" == typeof Symbol ? Symbol : {}, a = i.iterator || "@@iterator", c = i.asyncIterator || "@@asyncIterator", u = i.toStringTag || "@@toStringTag"; function define(t, e, r) { return Object.defineProperty(t, e, { value: r, enumerable: !0, configurable: !0, writable: !0 }), t[e]; } try { define({}, ""); } catch (t) { define = function define(t, e, r) { return t[e] = r; }; } function wrap(t, e, r, n) { var i = e && e.prototype instanceof Generator ? e : Generator, a = Object.create(i.prototype), c = new Context(n || []); return o(a, "_invoke", { value: makeInvokeMethod(t, r, c) }), a; } function tryCatch(t, e, r) { try { return { type: "normal", arg: t.call(e, r) }; } catch (t) { return { type: "throw", arg: t }; } } e.wrap = wrap; var h = "suspendedStart", l = "suspendedYield", f = "executing", s = "completed", y = {}; function Generator() {} function GeneratorFunction() {} function GeneratorFunctionPrototype() {} var p = {}; define(p, a, function () { return this; }); var d = Object.getPrototypeOf, v = d && d(d(values([]))); v && v !== r && n.call(v, a) && (p = v); var g = GeneratorFunctionPrototype.prototype = Generator.prototype = Object.create(p); function defineIteratorMethods(t) { ["next", "throw", "return"].forEach(function (e) { define(t, e, function (t) { return this._invoke(e, t); }); }); } function AsyncIterator(t, e) { function invoke(r, o, i, a) { var c = tryCatch(t[r], t, o); if ("throw" !== c.type) { var u = c.arg, h = u.value; return h && "object" == _typeof(h) && n.call(h, "__await") ? e.resolve(h.__await).then(function (t) { invoke("next", t, i, a); }, function (t) { invoke("throw", t, i, a); }) : e.resolve(h).then(function (t) { u.value = t, i(u); }, function (t) { return invoke("throw", t, i, a); }); } a(c.arg); } var r; o(this, "_invoke", { value: function value(t, n) { function callInvokeWithMethodAndArg() { return new e(function (e, r) { invoke(t, n, e, r); }); } return r = r ? r.then(callInvokeWithMethodAndArg, callInvokeWithMethodAndArg) : callInvokeWithMethodAndArg(); } }); } function makeInvokeMethod(e, r, n) { var o = h; return function (i, a) { if (o === f) throw new Error("Generator is already running"); if (o === s) { if ("throw" === i) throw a; return { value: t, done: !0 }; } for (n.method = i, n.arg = a;;) { var c = n.delegate; if (c) { var u = maybeInvokeDelegate(c, n); if (u) { if (u === y) continue; return u; } } if ("next" === n.method) n.sent = n._sent = n.arg;else if ("throw" === n.method) { if (o === h) throw o = s, n.arg; n.dispatchException(n.arg); } else "return" === n.method && n.abrupt("return", n.arg); o = f; var p = tryCatch(e, r, n); if ("normal" === p.type) { if (o = n.done ? s : l, p.arg === y) continue; return { value: p.arg, done: n.done }; } "throw" === p.type && (o = s, n.method = "throw", n.arg = p.arg); } }; } function maybeInvokeDelegate(e, r) { var n = r.method, o = e.iterator[n]; if (o === t) return r.delegate = null, "throw" === n && e.iterator.return && (r.method = "return", r.arg = t, maybeInvokeDelegate(e, r), "throw" === r.method) || "return" !== n && (r.method = "throw", r.arg = new TypeError("The iterator does not provide a '" + n + "' method")), y; var i = tryCatch(o, e.iterator, r.arg); if ("throw" === i.type) return r.method = "throw", r.arg = i.arg, r.delegate = null, y; var a = i.arg; return a ? a.done ? (r[e.resultName] = a.value, r.next = e.nextLoc, "return" !== r.method && (r.method = "next", r.arg = t), r.delegate = null, y) : a : (r.method = "throw", r.arg = new TypeError("iterator result is not an object"), r.delegate = null, y); } function pushTryEntry(t) { var e = { tryLoc: t[0] }; 1 in t && (e.catchLoc = t[1]), 2 in t && (e.finallyLoc = t[2], e.afterLoc = t[3]), this.tryEntries.push(e); } function resetTryEntry(t) { var e = t.completion || {}; e.type = "normal", delete e.arg, t.completion = e; } function Context(t) { this.tryEntries = [{ tryLoc: "root" }], t.forEach(pushTryEntry, this), this.reset(!0); } function values(e) { if (e || "" === e) { var r = e[a]; if (r) return r.call(e); if ("function" == typeof e.next) return e; if (!isNaN(e.length)) { var o = -1, i = function next() { for (; ++o < e.length;) if (n.call(e, o)) return next.value = e[o], next.done = !1, next; return next.value = t, next.done = !0, next; }; return i.next = i; } } throw new TypeError(_typeof(e) + " is not iterable"); } return GeneratorFunction.prototype = GeneratorFunctionPrototype, o(g, "constructor", { value: GeneratorFunctionPrototype, configurable: !0 }), o(GeneratorFunctionPrototype, "constructor", { value: GeneratorFunction, configurable: !0 }), GeneratorFunction.displayName = define(GeneratorFunctionPrototype, u, "GeneratorFunction"), e.isGeneratorFunction = function (t) { var e = "function" == typeof t && t.constructor; return !!e && (e === GeneratorFunction || "GeneratorFunction" === (e.displayName || e.name)); }, e.mark = function (t) { return Object.setPrototypeOf ? Object.setPrototypeOf(t, GeneratorFunctionPrototype) : (t.__proto__ = GeneratorFunctionPrototype, define(t, u, "GeneratorFunction")), t.prototype = Object.create(g), t; }, e.awrap = function (t) { return { __await: t }; }, defineIteratorMethods(AsyncIterator.prototype), define(AsyncIterator.prototype, c, function () { return this; }), e.AsyncIterator = AsyncIterator, e.async = function (t, r, n, o, i) { void 0 === i && (i = Promise); var a = new AsyncIterator(wrap(t, r, n, o), i); return e.isGeneratorFunction(r) ? a : a.next().then(function (t) { return t.done ? t.value : a.next(); }); }, defineIteratorMethods(g), define(g, u, "Generator"), define(g, a, function () { return this; }), define(g, "toString", function () { return "[object Generator]"; }), e.keys = function (t) { var e = Object(t), r = []; for (var n in e) r.push(n); return r.reverse(), function next() { for (; r.length;) { var t = r.pop(); if (t in e) return next.value = t, next.done = !1, next; } return next.done = !0, next; }; }, e.values = values, Context.prototype = { constructor: Context, reset: function reset(e) { if (this.prev = 0, this.next = 0, this.sent = this._sent = t, this.done = !1, this.delegate = null, this.method = "next", this.arg = t, this.tryEntries.forEach(resetTryEntry), !e) for (var r in this) "t" === r.charAt(0) && n.call(this, r) && !isNaN(+r.slice(1)) && (this[r] = t); }, stop: function stop() { this.done = !0; var t = this.tryEntries[0].completion; if ("throw" === t.type) throw t.arg; return this.rval; }, dispatchException: function dispatchException(e) { if (this.done) throw e; var r = this; function handle(n, o) { return a.type = "throw", a.arg = e, r.next = n, o && (r.method = "next", r.arg = t), !!o; } for (var o = this.tryEntries.length - 1; o >= 0; --o) { var i = this.tryEntries[o], a = i.completion; if ("root" === i.tryLoc) return handle("end"); if (i.tryLoc <= this.prev) { var c = n.call(i, "catchLoc"), u = n.call(i, "finallyLoc"); if (c && u) { if (this.prev < i.catchLoc) return handle(i.catchLoc, !0); if (this.prev < i.finallyLoc) return handle(i.finallyLoc); } else if (c) { if (this.prev < i.catchLoc) return handle(i.catchLoc, !0); } else { if (!u) throw new Error("try statement without catch or finally"); if (this.prev < i.finallyLoc) return handle(i.finallyLoc); } } } }, abrupt: function abrupt(t, e) { for (var r = this.tryEntries.length - 1; r >= 0; --r) { var o = this.tryEntries[r]; if (o.tryLoc <= this.prev && n.call(o, "finallyLoc") && this.prev < o.finallyLoc) { var i = o; break; } } i && ("break" === t || "continue" === t) && i.tryLoc <= e && e <= i.finallyLoc && (i = null); var a = i ? i.completion : {}; return a.type = t, a.arg = e, i ? (this.method = "next", this.next = i.finallyLoc, y) : this.complete(a); }, complete: function complete(t, e) { if ("throw" === t.type) throw t.arg; return "break" === t.type || "continue" === t.type ? this.next = t.arg : "return" === t.type ? (this.rval = this.arg = t.arg, this.method = "return", this.next = "end") : "normal" === t.type && e && (this.next = e), y; }, finish: function finish(t) { for (var e = this.tryEntries.length - 1; e >= 0; --e) { var r = this.tryEntries[e]; if (r.finallyLoc === t) return this.complete(r.completion, r.afterLoc), resetTryEntry(r), y; } }, catch: function _catch(t) { for (var e = this.tryEntries.length - 1; e >= 0; --e) { var r = this.tryEntries[e]; if (r.tryLoc === t) { var n = r.completion; if ("throw" === n.type) { var o = n.arg; resetTryEntry(r); } return o; } } throw new Error("illegal catch attempt"); }, delegateYield: function delegateYield(e, r, n) { return this.delegate = { iterator: values(e), resultName: r, nextLoc: n }, "next" === this.method && (this.arg = t), y; } }, e; }
function asyncGeneratorStep(gen, resolve, reject, _next, _throw, key, arg) { try { var info = gen[key](arg); var value = info.value; } catch (error) { reject(error); return; } if (info.done) { resolve(value); } else { Promise.resolve(value).then(_next, _throw); } }
function _asyncToGenerator(fn) { return function () { var self = this, args = arguments; return new Promise(function (resolve, reject) { var gen = fn.apply(self, args); function _next(value) { asyncGeneratorStep(gen, resolve, reject, _next, _throw, "next", value); } function _throw(err) { asyncGeneratorStep(gen, resolve, reject, _next, _throw, "throw", err); } _next(undefined); }); }; }
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































/*global $*/
/*eslint no-undef: "error"*/









/*eslint no-unused-vars: "jqueryui"*/









var locale = (0,_bem_ts_service_env__WEBPACK_IMPORTED_MODULE_45__.extractOptions)().locale;
var cache = {};
var numberFormat = new Intl.NumberFormat(locale.replace('_', '-'));
var currencyFormat = new Intl.NumberFormat(locale.replace('_', '-'), {
  style: 'currency',
  currency: 'USD'
});
function HotelBrand(props) {
  if (lodash_isEmpty__WEBPACK_IMPORTED_MODULE_38___default()(props.providers)) {
    return /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_32___default().createElement("div", null);
  }
  return /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_32___default().createElement("div", {
    className: "hotel-reward-data"
  }, lodash_map__WEBPACK_IMPORTED_MODULE_41___default()(props.providers, function (provider) {
    return /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_32___default().createElement("div", {
      key: provider.providerId,
      className: 'hotel-reward'
    }, /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_32___default().createElement("div", {
      className: "header-data d-inline-block w-100"
    }, /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_32___default().createElement("div", {
      className: "float-left"
    }, /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_32___default().createElement("h3", null, provider.brandName)), /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_32___default().createElement("div", {
      className: "float-right"
    }, /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_32___default().createElement("span", {
      className: 'hotel-brand-value-avg'
    }, _bem_ts_service_translator__WEBPACK_IMPORTED_MODULE_34__["default"].trans('average-value'), ": ", /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_32___default().createElement("b", null, provider.formattedAvgPointValue), _bem_ts_service_translator__WEBPACK_IMPORTED_MODULE_34__["default"].trans('per-point')))), /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_32___default().createElement("div", {
      className: "table-scroll-container"
    }, /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_32___default().createElement("table", {
      className: "main-table no-border brand-hotels mobile-table-v2"
    }, /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_32___default().createElement("thead", null, /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_32___default().createElement("tr", null, /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_32___default().createElement("th", {
      className: 'hotel-name'
    }, _bem_ts_service_translator__WEBPACK_IMPORTED_MODULE_34__["default"].trans('itineraries.reservation.phones.title', {}, 'trips')), /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_32___default().createElement("th", {
      className: 'hotel-value-redemption text-center'
    }, _bem_ts_service_translator__WEBPACK_IMPORTED_MODULE_34__["default"].trans('redemption-value')), /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_32___default().createElement("th", {
      className: 'hotel-value-avg-percent text-center'
    }, _bem_ts_service_translator__WEBPACK_IMPORTED_MODULE_34__["default"].trans('percent-above-avg')), /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_32___default().createElement("th", {
      className: 'hotel-value-avg-cashprice text-center'
    }, _bem_ts_service_translator__WEBPACK_IMPORTED_MODULE_34__["default"].trans('avg-cash-price-night')), /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_32___default().createElement("th", {
      className: 'hotel-value-avg-pointprice text-center'
    }, _bem_ts_service_translator__WEBPACK_IMPORTED_MODULE_34__["default"].trans('avg-point-price-night')), /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_32___default().createElement("th", {
      className: 'hotel-check-link'
    }))), /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_32___default().createElement("tbody", null, lodash_map__WEBPACK_IMPORTED_MODULE_41___default()(provider.hotels, function (hotel) {
      return /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_32___default().createElement("tr", {
        key: hotel.hotelId,
        className: classnames__WEBPACK_IMPORTED_MODULE_35___default()({
          'above-positive': hotel.avgAboveValue > 0,
          'above-negative': hotel.avgAboveValue < 0
        })
      }, /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_32___default().createElement("td", {
        className: 'hotel-name'
      }, /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_32___default().createElement("b", null, hotel.name), /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_32___default().createElement("span", {
        className: 'silver-text'
      }, hotel.location)), /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_32___default().createElement("td", {
        className: 'text-center',
        title: hotel.matchCount ? _bem_ts_service_translator__WEBPACK_IMPORTED_MODULE_34__["default"].trans('based-on-last-bookings', {
          'number': numberFormat.format(hotel.matchCount),
          'as-of-date': ''
        }) : ''
      }, /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_32___default().createElement("span", {
        "data-tip": '',
        "data-role": "tooltip"
      }, numberFormat.format(hotel.pointValue), " \xA2")), /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_32___default().createElement("td", {
        className: 'col-above text-center'
      }, numberFormat.format(hotel.avgAboveValue), "%"), /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_32___default().createElement("td", {
        className: 'text-center'
      }, currencyFormat.format(hotel.cashPrice)), /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_32___default().createElement("td", {
        className: 'text-center'
      }, numberFormat.format(hotel.pointPrice)), /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_32___default().createElement("td", null, lodash_isEmpty__WEBPACK_IMPORTED_MODULE_38___default()(hotel.link) ? '' : /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_32___default().createElement("a", {
        className: 'blue-link',
        target: "_blank",
        href: hotel.link,
        rel: "noreferrer"
      }, _bem_ts_service_translator__WEBPACK_IMPORTED_MODULE_34__["default"].trans('check-availability'))));
    })))));
  }));
}
function FormAddress(props) {
  (0,react__WEBPACK_IMPORTED_MODULE_32__.useEffect)(function () {
    $('.search-input[name="q"]').autocomplete({
      delay: 500,
      minLength: 2,
      search: function search(event) {
        if ($(event.target).val().length >= 2) $(event.target).addClass('loading-input');else $(event.target).removeClass('loading-input');
      },
      open: function open(event) {
        $(event.target).removeClass('loading-input');
      },
      source: function source(request, response) {
        var self = this;
        $(this).closest('.input-item').find('.address-timezone').text('');
        var fragmentNamePos = request.term.indexOf('#'),
          hotelNameFragment = '';
        if (-1 !== fragmentNamePos) {
          hotelNameFragment = request.term.substr(1 + fragmentNamePos);
          request.term = request.term.substr(0, fragmentNamePos);
        }
        $.get(_bem_ts_service_router__WEBPACK_IMPORTED_MODULE_33__["default"].generate('aw_hotelreward_geo', {
          query: encodeURIComponent(request.term)
        })).done(function (data) {
          $(self.element).removeClass('loading-input');
          if (!data) return;
          response(data.map(function (item) {
            var result = {};
            if ('undefined' !== typeof item.place_id) {
              result.place_id = item.place_id;
            }
            if ('undefined' !== typeof item.formatted_address) {
              result.formatted_address = result.label = result.value = item.formatted_address;
            }
            if ('undefined' !== typeof item.extend) {
              result.extend = item.extend;
            }
            result.fragmentName = hotelNameFragment;
            return result;
          }));
        }).fail(function () {
          return [];
        });
      },
      select: function select(event, ui) {
        event.preventDefault();
        props.setAddress(ui.item);
        $(event.target).val(ui.item.value).trigger('change');
        props.formAddressSubmit(event, ui.item);
      },
      create: function create() {
        $(this).data('ui-autocomplete')._renderItem = function (ul, item) {
          var regex = new RegExp('(' + this.element.val() + ')', 'gi');
          var itemLabel = item.label.replace(regex, "<b>$1</b>");
          return $('<li></li>').data('item.autocomplete', item).append($('<a></a>').html(itemLabel)).appendTo(ul);
        };
      }
    });
  }, []);
  return /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_32___default().createElement("form", {
    onSubmit: props.formAddressSubmit
  }, /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_32___default().createElement("div", {
    className: "search column"
  }, /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_32___default().createElement("div", {
    className: "row"
  }, /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_32___default().createElement("div", {
    className: "input"
  }, /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_32___default().createElement("input", {
    className: classnames__WEBPACK_IMPORTED_MODULE_35___default()({
      'input-item': true,
      'search-input': true,
      'search-input-fill': !isEmptyAddress(props.address)
    }),
    name: "q",
    type: "text",
    placeholder: _bem_ts_service_translator__WEBPACK_IMPORTED_MODULE_34__["default"].trans('enter-city-state-country-search'),
    autoComplete: "off",
    value: props.address.value,
    onChange: function onChange(event) {
      return props.setAddress(event.target.value);
    }
  }), /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_32___default().createElement("a", {
    className: 'clear-search',
    href: "",
    onClick: function onClick(event) {
      event.preventDefault();
      props.setAddress({
        'value': '',
        'place_id': ''
      });
    }
  }, /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_32___default().createElement("i", {
    className: "icon-close-silver"
  }))))));
}
function SearchResult(props) {
  var _filteredHotels$;
  var hotels = props.hotelsList;
  if (lodash_has__WEBPACK_IMPORTED_MODULE_37___default()(hotels, 'notFound') || lodash_has__WEBPACK_IMPORTED_MODULE_37___default()(hotels, 'success')) {
    return /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_32___default().createElement("div", null, /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_32___default().createElement("p", {
      id: "notFound",
      className: "no-result"
    }, /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_32___default().createElement("i", {
      className: "icon-warning-small"
    }), /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_32___default().createElement("span", null, _bem_ts_service_translator__WEBPACK_IMPORTED_MODULE_34__["default"].trans('no_results_found'))));
  }
  var brands = lodash_uniqBy__WEBPACK_IMPORTED_MODULE_43___default()(lodash_values__WEBPACK_IMPORTED_MODULE_44___default()(lodash_mapValues__WEBPACK_IMPORTED_MODULE_42___default()(hotels, 'brandName')));
  var brandsMaxLengthName = 0;
  brands.map(function (name) {
    if (name.length > brandsMaxLengthName) {
      brandsMaxLengthName = name.length;
    }
  });
  var filteredHotels = hotels;
  if ('' !== props.filterOptions.brand) {
    filteredHotels = lodash_filter__WEBPACK_IMPORTED_MODULE_36___default()(hotels, function (hotel) {
      return hotel.brandName == props.filterOptions.brand;
    });
  }
  var rowIndex = 0,
    isFirstNeutral = null,
    isFirstNegative = null;
  if (((_filteredHotels$ = filteredHotels[0]) === null || _filteredHotels$ === void 0 ? void 0 : _filteredHotels$.avgAboveValue) < 0) {
    isFirstNegative = isFirstNegative = false;
  }
  return /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_32___default().createElement("div", {
    className: "hotel-reward-place"
  }, /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_32___default().createElement("div", {
    className: 'hotel-reward'
  }, /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_32___default().createElement("h1", null, _bem_ts_service_translator__WEBPACK_IMPORTED_MODULE_34__["default"].trans('search-result-near', {
    'query': props.address.selected
  })), /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_32___default().createElement("div", {
    className: "table-scroll-container"
  }, /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_32___default().createElement("table", {
    className: "main-table no-border brand-hotels mobile-table-v2"
  }, /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_32___default().createElement("thead", null, /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_32___default().createElement("tr", null, /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_32___default().createElement("th", {
    className: 'hotel-name'
  }, _bem_ts_service_translator__WEBPACK_IMPORTED_MODULE_34__["default"].trans('itineraries.reservation.phones.title', {}, 'trips')), /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_32___default().createElement("th", {
    className: 'hotel-value-redemption'
  }, /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_32___default().createElement("div", {
    className: 'styled-select',
    style: {
      'width': brandsMaxLengthName * 11
    }
  }, /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_32___default().createElement("div", null, /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_32___default().createElement("select", {
    value: props.filterOptions.brand,
    onChange: function onChange(event) {
      props.setFilterOptions({
        'brand': event.target.value
      });
    }
  }, /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_32___default().createElement("option", {
    value: ''
  }, _bem_ts_service_translator__WEBPACK_IMPORTED_MODULE_34__["default"].trans('status.all')), lodash_map__WEBPACK_IMPORTED_MODULE_41___default()(brands, function (brand) {
    return /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_32___default().createElement("option", {
      key: brand,
      value: brand
    }, brand);
  }))))), /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_32___default().createElement("th", {
    className: 'text-center'
  }, _bem_ts_service_translator__WEBPACK_IMPORTED_MODULE_34__["default"].trans('redemption-value')), /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_32___default().createElement("th", {
    className: 'hotel-value-avg-percent text-center'
  }, _bem_ts_service_translator__WEBPACK_IMPORTED_MODULE_34__["default"].trans('percent-above-avg')), /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_32___default().createElement("th", {
    className: 'hotel-value-avg-cashprice text-center'
  }, _bem_ts_service_translator__WEBPACK_IMPORTED_MODULE_34__["default"].trans('avg-cash-price-night')), /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_32___default().createElement("th", {
    className: 'hotel-value-avg-pointprice text-center'
  }, _bem_ts_service_translator__WEBPACK_IMPORTED_MODULE_34__["default"].trans('avg-point-price-night')), /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_32___default().createElement("th", {
    className: 'hotel-check-link'
  }))), /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_32___default().createElement("tbody", null, lodash_map__WEBPACK_IMPORTED_MODULE_41___default()(filteredHotels, function (hotel) {
    ++rowIndex;
    if (0 === hotel.avgAboveValue) {
      isFirstNeutral = null === isFirstNeutral;
    }
    if (hotel.avgAboveValue < 0) {
      isFirstNegative = null === isFirstNegative;
    }
    var cssClasses = classnames__WEBPACK_IMPORTED_MODULE_35___default()({
      'above-positive': hotel.avgAboveValue > 0,
      'above-negative': hotel.avgAboveValue < 0,
      'first-neutral': isFirstNeutral,
      'first-negative': isFirstNegative
    });
    if (isFirstNeutral) isFirstNeutral = false;
    if (isFirstNegative) isFirstNegative = false;
    return /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_32___default().createElement("tr", {
      key: hotel.hotelId,
      className: cssClasses
    }, /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_32___default().createElement("td", {
      className: 'hotel-name'
    }, /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_32___default().createElement("b", null, hotel.name), /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_32___default().createElement("span", {
      className: 'silver-text'
    }, hotel.location)), /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_32___default().createElement("td", null, hotel.brandName), /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_32___default().createElement("td", {
      className: 'text-center',
      title: hotel.matchCount ? _bem_ts_service_translator__WEBPACK_IMPORTED_MODULE_34__["default"].trans('based-on-last-bookings', {
        'number': numberFormat.format(hotel.matchCount),
        'as-of-date': ''
      }) : ''
    }, /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_32___default().createElement("span", {
      "data-tip": '',
      "data-role": "tooltip"
    }, numberFormat.format(hotel.pointValue), " \xA2")), /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_32___default().createElement("td", {
      className: 'col-above text-center'
    }, numberFormat.format(hotel.avgAboveValue), "%"), /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_32___default().createElement("td", {
      className: 'text-center'
    }, currencyFormat.format(hotel.cashPrice)), /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_32___default().createElement("td", {
      className: 'text-center'
    }, numberFormat.format(hotel.pointPrice)), /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_32___default().createElement("td", null, lodash_isEmpty__WEBPACK_IMPORTED_MODULE_38___default()(hotel.link) ? '' : /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_32___default().createElement("a", {
      className: 'blue-link',
      target: "_blank",
      href: hotel.link,
      rel: "noreferrer"
    }, _bem_ts_service_translator__WEBPACK_IMPORTED_MODULE_34__["default"].trans('check-availability'))));
  }))))));
}
function ContentData(props) {
  if (!isEmptyAddress(props.address) && !lodash_isEmpty__WEBPACK_IMPORTED_MODULE_38___default()(props.hotelsList)) {
    return SearchResult(props);
  }
  return /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_32___default().createElement(HotelBrand, {
    providers: props.primaryList
  });
}
function HotelReward(props) {
  var _useState = (0,react__WEBPACK_IMPORTED_MODULE_32__.useState)(false),
    _useState2 = _slicedToArray(_useState, 2),
    isLoading = _useState2[0],
    setLoading = _useState2[1];
  var _useState3 = (0,react__WEBPACK_IMPORTED_MODULE_32__.useState)([]),
    _useState4 = _slicedToArray(_useState3, 2),
    hotelsList = _useState4[0],
    setHotelsList = _useState4[1];
  var initialAddress = {
    label: '',
    place_id: '',
    value: '',
    selected: ''
  };
  function handlerSearchAddressState(prev, state) {
    if (isEmptyAddress(state)) {
      setHotelsList([]);
      setLocationState(null);
      return initialAddress;
    }
    if ('string' === typeof state) {
      return _objectSpread(_objectSpread({}, initialAddress), {
        value: state,
        selected: prev.selected
      });
    }
    return state;
  }
  var _useReducer = (0,react__WEBPACK_IMPORTED_MODULE_32__.useReducer)(handlerSearchAddressState, initialAddress),
    _useReducer2 = _slicedToArray(_useReducer, 2),
    address = _useReducer2[0],
    setAddress = _useReducer2[1];
  (0,react__WEBPACK_IMPORTED_MODULE_32__.useEffect)(function () {
    var route = _bem_ts_service_router__WEBPACK_IMPORTED_MODULE_33__["default"].generate('aw_hotelreward_index');
    var _decodeURI$replace$re = decodeURI(decodeURIComponent(location.pathname.substr(location.pathname.indexOf(route) + route.length))).replace(/^\/+|\/+$/g, '').replace(/\+/g, ' ').split('/'),
      _decodeURI$replace$re2 = _slicedToArray(_decodeURI$replace$re, 2),
      placeId = _decodeURI$replace$re2[0],
      placeName = _decodeURI$replace$re2[1];
    if (!lodash_isEmpty__WEBPACK_IMPORTED_MODULE_38___default()(placeId) && !lodash_isEmpty__WEBPACK_IMPORTED_MODULE_38___default()(placeName)) {
      var fetchAddress = {
        place_id: placeId,
        value: placeName,
        label: placeName,
        selected: placeName
      };
      setAddress(fetchAddress);
      handleFormAddressSubmit({
        preventDefault: function preventDefault() {
          return false;
        }
      }, fetchAddress);
    }
    window.onpopstate = function (event) {
      if (lodash_has__WEBPACK_IMPORTED_MODULE_37___default()(event.state, 'place')) {
        return handleFormAddressSubmit({
          preventDefault: function preventDefault() {
            return false;
          }
        }, event.state.place);
      }
      //setHotelsList([]);
      setAddress(initialAddress);
    };
  }, []);
  (0,react__WEBPACK_IMPORTED_MODULE_32__.useEffect)(function () {
    (0,_ToolTip__WEBPACK_IMPORTED_MODULE_39__["default"])();
  });
  var handleFormAddressSubmit = /*#__PURE__*/function () {
    var _ref = _asyncToGenerator( /*#__PURE__*/_regeneratorRuntime().mark(function _callee(event, addr) {
      var placeData, cacheKey, response;
      return _regeneratorRuntime().wrap(function _callee$(_context) {
        while (1) switch (_context.prev = _context.next) {
          case 0:
            event.preventDefault();
            setFilterOptions({
              brand: ''
            });
            placeData = lodash_has__WEBPACK_IMPORTED_MODULE_37___default()(addr, 'place_id') ? addr : address;
            setAddress(_objectSpread(_objectSpread({}, placeData), {
              selected: placeData.value
            }));
            cacheKey = getCacheKey(placeData);
            if (!lodash_has__WEBPACK_IMPORTED_MODULE_37___default()(cache, cacheKey)) {
              _context.next = 8;
              break;
            }
            setLocationState(placeData.placeId, placeData.value);
            return _context.abrupt("return", setHotelsList(cache[cacheKey]));
          case 8:
            setLoading(true);
            _context.next = 11;
            return _bem_ts_service_axios__WEBPACK_IMPORTED_MODULE_31__["default"].get(_bem_ts_service_router__WEBPACK_IMPORTED_MODULE_33__["default"].generate('aw_hotelreward_place', {
              place: placeData
            }));
          case 11:
            response = _context.sent.data;
            if (lodash_has__WEBPACK_IMPORTED_MODULE_37___default()(response, 'placeId')) {
              cache[response.placeId] = response.hotels;
              setHotelsList(cache[response.placeId]);
              setLocationState(response.placeId, placeData.value);
            } else {
              setHotelsList(response);
            }
            setLoading(false);
          case 14:
          case "end":
            return _context.stop();
        }
      }, _callee);
    }));
    return function handleFormAddressSubmit(_x, _x2) {
      return _ref.apply(this, arguments);
    };
  }();
  var initialFilterOptions = {
    'brand': ''
  };
  var _useReducer3 = (0,react__WEBPACK_IMPORTED_MODULE_32__.useReducer)(function (prev, state) {
      return _objectSpread(_objectSpread({}, prev), state);
    }, initialFilterOptions),
    _useReducer4 = _slicedToArray(_useReducer3, 2),
    filterOptions = _useReducer4[0],
    setFilterOptions = _useReducer4[1];
  return /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_32___default().createElement("div", {
    className: "main-blk hotel-reward-page"
  }, /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_32___default().createElement("h1", null, _bem_ts_service_translator__WEBPACK_IMPORTED_MODULE_34__["default"].trans('award-hotel-research-tool')), /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_32___default().createElement("div", {
    className: "main-blk-content"
  }, /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_32___default().createElement(FormAddress, {
    address: address,
    setAddress: setAddress,
    formAddressSubmit: handleFormAddressSubmit,
    setHotels: setHotelsList
  }), /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_32___default().createElement(ContentData, {
    address: address,
    primaryList: props.primaryList,
    hotelsList: hotelsList,
    filterOptions: filterOptions,
    setFilterOptions: setFilterOptions
  }), /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_32___default().createElement("div", {
    className: classnames__WEBPACK_IMPORTED_MODULE_35___default()({
      'ajax-loader': true,
      'ajax-loader-process': isLoading
    })
  }, /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_32___default().createElement("div", {
    className: "loading"
  })), /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_32___default().createElement(_component_deprecated_milevalue_milevalue_box__WEBPACK_IMPORTED_MODULE_46__["default"], {
    providers: props.primaryList
  })));
}
function isEmptyAddress(state) {
  return '' === state || lodash_has__WEBPACK_IMPORTED_MODULE_37___default()(state, 'place_id') && lodash_isEmpty__WEBPACK_IMPORTED_MODULE_38___default()(state.place_id) && lodash_isEmpty__WEBPACK_IMPORTED_MODULE_38___default()(state.value);
}
function getCacheKey(data) {
  var fragmentName = lodash_has__WEBPACK_IMPORTED_MODULE_37___default()(data, 'fragmentName') ? data.fragmentName : '';
  if (lodash_has__WEBPACK_IMPORTED_MODULE_37___default()(data, 'place_id') && !lodash_isEmpty__WEBPACK_IMPORTED_MODULE_38___default()(data.place_id)) {
    return data.place_id + fragmentName;
  }
  if (!lodash_isEmpty__WEBPACK_IMPORTED_MODULE_38___default()(data.value)) {
    return '_' + data.value + fragmentName;
  }
  return 0;
}
function setLocationState(placeId, placeName) {
  if (null === placeId) {
    return window.history.pushState({}, '', _bem_ts_service_router__WEBPACK_IMPORTED_MODULE_33__["default"].generate('aw_hotelreward_index'));
  }
  return window.history.pushState({
    place: {
      place_id: placeId,
      label: placeName,
      value: placeName,
      selected: placeName
    }
  }, '', _bem_ts_service_router__WEBPACK_IMPORTED_MODULE_33__["default"].generate('aw_hotelreward_index_place', {
    placeName: encodeURIComponent(placeName).replace(/%20/g, '+').replace(/%2C/g, ',')
  }));
}
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (HotelReward);

/***/ }),

/***/ "./assets/js-deprecated/component-deprecated/milevalue/milevalue-box.js":
/*!******************************************************************************!*\
  !*** ./assets/js-deprecated/component-deprecated/milevalue/milevalue-box.js ***!
  \******************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! react */ "./node_modules/react/index.js");
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(react__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _bem_ts_service_router__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ../../../bem/ts/service/router */ "./assets/bem/ts/service/router.ts");
/* harmony import */ var _bem_ts_service_translator__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ../../../bem/ts/service/translator */ "./assets/bem/ts/service/translator.ts");
/* harmony import */ var lodash_map__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! lodash/map */ "./node_modules/lodash/map.js");
/* harmony import */ var lodash_map__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(lodash_map__WEBPACK_IMPORTED_MODULE_3__);




function MileValueBox(props) {
  return /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("div", {
    style: {
      padding: '15px'
    }
  }, /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("div", {
    id: "mileValueBox",
    className: "chart__filter"
  }, /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("p", {
    dangerouslySetInnerHTML: {
      __html: _bem_ts_service_translator__WEBPACK_IMPORTED_MODULE_2__["default"].trans('we-calculate-points-evaluating-bookings-points', {
        'link_on': "<a href=".concat(_bem_ts_service_router__WEBPACK_IMPORTED_MODULE_1__["default"].generate('aw_points_miles_values'), ">"),
        'link_off': "</a>"
      })
    }
  }), /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("div", {
    className: "chart__filter_container"
  }, /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("div", {
    className: "chart__filter_wrap"
  }, lodash_map__WEBPACK_IMPORTED_MODULE_3___default()(props.providers, function (provider) {
    return /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("div", {
      className: "chart__filter_block",
      key: provider.providerId
    }, /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("span", null, provider.brandName), /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("div", {
      className: "curr-value"
    }, /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("strong", null, provider.formattedAvgPointValue)));
  }), /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_0___default().createElement("div", {
    className: "t-right"
  })))));
}
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (MileValueBox);

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

/***/ "./assets/less-deprecated/hotel-reward.less":
/*!**************************************************!*\
  !*** ./assets/less-deprecated/hotel-reward.less ***!
  \**************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
// extracted by mini-css-extract-plugin


/***/ }),

/***/ "./assets/less-deprecated/main.less":
/*!******************************************!*\
  !*** ./assets/less-deprecated/main.less ***!
  \******************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
// extracted by mini-css-extract-plugin


/***/ })

},
/******/ __webpack_require__ => { // webpackRuntimeModules
/******/ var __webpack_exec__ = (moduleId) => (__webpack_require__(__webpack_require__.s = moduleId))
/******/ __webpack_require__.O(0, ["vendors-node_modules_core-js_internals_classof_js-node_modules_core-js_internals_define-built-in_js","vendors-node_modules_core-js_internals_array-slice-simple_js-node_modules_core-js_internals_f-4bac30","vendors-node_modules_core-js_modules_es_string_replace_js","vendors-node_modules_core-js_modules_es_object_keys_js-node_modules_core-js_modules_es_regexp-599444","vendors-node_modules_core-js_internals_array-method-is-strict_js-node_modules_core-js_modules-ea2489","vendors-node_modules_core-js_modules_es_array_find_js-node_modules_core-js_modules_es_array_f-112b41","vendors-node_modules_core-js_modules_es_promise_js-node_modules_core-js_modules_web_dom-colle-054322","vendors-node_modules_core-js_internals_string-trim_js-node_modules_core-js_internals_this-num-d4bf43","vendors-node_modules_core-js_modules_es_json_to-string-tag_js-node_modules_core-js_modules_es-dd246b","vendors-node_modules_core-js_modules_es_array_from_js-node_modules_core-js_modules_es_date_to-6fede4","vendors-node_modules_axios-hooks_es_index_js-node_modules_classnames_index_js-node_modules_is-e8b457","vendors-node_modules_core-js_modules_es_string_link_js-node_modules_lodash_filter_js-node_mod-a526c1","assets_bem_ts_service_translator_ts","web_assets_common_vendors_jquery_dist_jquery_js","web_assets_common_vendors_jquery-ui_jquery-ui_min_js","assets_bem_ts_service_router_ts","web_assets_common_fonts_webfonts_open-sans_css-web_assets_common_fonts_webfonts_roboto_css","assets_less-deprecated_main_less","assets_bem_ts_service_axios_index_js-assets_bem_ts_service_env_ts"], () => (__webpack_exec__("./assets/entry-point-deprecated/hotel-reward/index.js")));
/******/ var __webpack_exports__ = __webpack_require__.O();
/******/ }
]);
//# sourceMappingURL=data:application/json;charset=utf-8;base64,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SixDQUFDLENBQUM7Ozs7Ozs7Ozs7O0FDVEY7Ozs7Ozs7Ozs7OztBQ0FBIiwic291cmNlcyI6WyJ3ZWJwYWNrOi8vQXdhcmRXYWxsZXQvLi9hc3NldHMvZW50cnktcG9pbnQtZGVwcmVjYXRlZC9ob3RlbC1yZXdhcmQvaW5kZXguanMiLCJ3ZWJwYWNrOi8vQXdhcmRXYWxsZXQvLi9hc3NldHMvZW50cnktcG9pbnQtZGVwcmVjYXRlZC9tYWluLmpzIiwid2VicGFjazovL0F3YXJkV2FsbGV0Ly4vYXNzZXRzL2pzLWRlcHJlY2F0ZWQvY29tcG9uZW50LWRlcHJlY2F0ZWQvVG9vbFRpcC5qcyIsIndlYnBhY2s6Ly9Bd2FyZFdhbGxldC8uL2Fzc2V0cy9qcy1kZXByZWNhdGVkL2NvbXBvbmVudC1kZXByZWNhdGVkL2hvdGVsLXJld2FyZC9Ib3RlbFJld2FyZC5qcyIsIndlYnBhY2s6Ly9Bd2FyZFdhbGxldC8uL2Fzc2V0cy9qcy1kZXByZWNhdGVkL2NvbXBvbmVudC1kZXByZWNhdGVkL21pbGV2YWx1ZS9taWxldmFsdWUtYm94LmpzIiwid2VicGFjazovL0F3YXJkV2FsbGV0Ly4vYXNzZXRzL2JlbS90cy9zZXJ2aWNlL29uLXJlYWR5LnRzIiwid2VicGFjazovL0F3YXJkV2FsbGV0Ly4vYXNzZXRzL2JlbS90cy9zdGFydGVyLnRzIiwid2VicGFjazovL0F3YXJkV2FsbGV0Ly4vYXNzZXRzL2xlc3MtZGVwcmVjYXRlZC9ob3RlbC1yZXdhcmQubGVzcz81MGQ0Iiwid2VicGFjazovL0F3YXJkV2FsbGV0Ly4vYXNzZXRzL2xlc3MtZGVwcmVjYXRlZC9tYWluLmxlc3M/MTk4NSJdLCJzb3VyY2VzQ29udGVudCI6WyJpbXBvcnQgJy4uLy4uL2JlbS90cy9zdGFydGVyJztcbmltcG9ydCAnLi4vLi4vbGVzcy1kZXByZWNhdGVkL2hvdGVsLXJld2FyZC5sZXNzJztcbmltcG9ydCB7cmVuZGVyfSBmcm9tICdyZWFjdC1kb20nO1xuaW1wb3J0IEhvdGVsUmV3YXJkIGZyb20gJy4uLy4uL2pzLWRlcHJlY2F0ZWQvY29tcG9uZW50LWRlcHJlY2F0ZWQvaG90ZWwtcmV3YXJkL0hvdGVsUmV3YXJkJztcbmltcG9ydCBSZWFjdCBmcm9tICdyZWFjdCc7XG5cbmNvbnN0IGNvbnRlbnRFbGVtZW50ID0gZG9jdW1lbnQuZ2V0RWxlbWVudEJ5SWQoJ2NvbnRlbnQnKTtcbmNvbnN0IHByaW1hcnlMaXN0ID0gSlNPTi5wYXJzZShjb250ZW50RWxlbWVudC5kYXRhc2V0LnByaW1hcnlMaXN0KTtcblxucmVuZGVyKFxuICAgIDxSZWFjdC5TdHJpY3RNb2RlPlxuICAgICAgICA8SG90ZWxSZXdhcmQgcHJpbWFyeUxpc3Q9e3ByaW1hcnlMaXN0fS8+XG4gICAgPC9SZWFjdC5TdHJpY3RNb2RlPixcbiAgICBjb250ZW50RWxlbWVudFxuKTsiLCJpbXBvcnQgJy4uL2xlc3MtZGVwcmVjYXRlZC9tYWluLmxlc3MnO1xuLyplc2xpbnQgbm8tdW51c2VkLXZhcnM6IFwianF1ZXJ5dWlcIiovXG5pbXBvcnQganF1ZXJ5dWkgZnJvbSAnanF1ZXJ5dWknOyAvLyAubWVudSgpXG5cbihmdW5jdGlvbiBtYWluKCkge1xuICAgIHRvZ2dsZVNpZGViYXJWaXNpYmxlKCk7XG4gICAgaW5pdERyb3Bkb3ducygkKCdib2R5JykpO1xufSkoKTtcblxuZnVuY3Rpb24gdG9nZ2xlU2lkZWJhclZpc2libGUoKSB7XG4gICAgJCh3aW5kb3cpLnJlc2l6ZShmdW5jdGlvbigpIHtcbiAgICAgICAgbGV0IHNpemVXaW5kb3cgPSAkKCdib2R5Jykud2lkdGgoKTtcbiAgICAgICAgaWYgKHNpemVXaW5kb3cgPCAxMDI0KSB7XG4gICAgICAgICAgICAkKCcubWFpbi1ib2R5JykuYWRkQ2xhc3MoJ3NtYWxsLWRlc2t0b3AnKTtcbiAgICAgICAgfSBlbHNlIHtcbiAgICAgICAgICAgICQoJy5tYWluLWJvZHknKS5yZW1vdmVDbGFzcygnc21hbGwtZGVza3RvcCcpO1xuICAgICAgICB9XG4gICAgICAgIGlmICgkKCcubWFpbi1ib2R5JykuaGFzQ2xhc3MoJ21hbnVhbC1oaWRkZW4nKSkgcmV0dXJuO1xuICAgICAgICBpZiAoc2l6ZVdpbmRvdyA8IDEwMjQpIHtcbiAgICAgICAgICAgICQoJy5tYWluLWJvZHknKS5hZGRDbGFzcygnaGlkZS1tZW51Jyk7XG4gICAgICAgIH0gZWxzZSB7XG4gICAgICAgICAgICAkKCcubWFpbi1ib2R5JykucmVtb3ZlQ2xhc3MoJ2hpZGUtbWVudScpO1xuICAgICAgICB9XG4gICAgfSk7XG5cbiAgICBjb25zdCBtZW51Q2xvc2UgPSBkb2N1bWVudC5xdWVyeVNlbGVjdG9yKCcubWVudS1jbG9zZScpO1xuICAgIGlmIChtZW51Q2xvc2UpIHtcbiAgICAgICAgY29uc3QgbWVudUJvZHkgPSBkb2N1bWVudC5xdWVyeVNlbGVjdG9yKCcubWFpbi1ib2R5Jyk7XG4gICAgICAgIG1lbnVDbG9zZS5vbmNsaWNrID0gKCkgPT4ge1xuICAgICAgICAgICAgbWVudUJvZHkuY2xhc3NMaXN0LnRvZ2dsZSgnaGlkZS1tZW51Jyk7XG4gICAgICAgICAgICBtZW51Qm9keS5jbGFzc0xpc3QuYWRkKCdtYW51YWwtaGlkZGVuJyk7XG4gICAgICAgIH07XG4gICAgfVxufVxuXG5mdW5jdGlvbiBpbml0RHJvcGRvd25zKGFyZWEsIG9wdGlvbnMpIHtcbiAgICBvcHRpb25zID0gb3B0aW9ucyB8fCB7fTtcbiAgICBjb25zdCBzZWxlY3RvciA9ICdbZGF0YS1yb2xlPVwiZHJvcGRvd25cIl0nO1xuICAgIGNvbnN0IGRyb3Bkb3duID0gdW5kZWZpbmVkICE9IGFyZWFcbiAgICAgICAgPyAkKGFyZWEpLmZpbmQoc2VsZWN0b3IpLmFkZEJhY2soc2VsZWN0b3IpXG4gICAgICAgIDogJChzZWxlY3RvcilcbiAgICBjb25zdCBvZlBhcmVudFNlbGVjdG9yID0gb3B0aW9ucy5vZlBhcmVudCB8fCAnbGknO1xuXG4gICAgZHJvcGRvd24uZWFjaChmdW5jdGlvbihpZCwgZWwpIHtcbiAgICAgICAgJChlbClcbiAgICAgICAgICAgIC5yZW1vdmVBdHRyKCdkYXRhLXJvbGUnKVxuICAgICAgICAgICAgLm1lbnUoKVxuICAgICAgICAgICAgLmhpZGUoKVxuICAgICAgICAgICAgLm9uKCdtZW51LmhpZGUnLCBmdW5jdGlvbihlKSB7XG4gICAgICAgICAgICAgICAgJChlLnRhcmdldCkuaGlkZSgyMDApO1xuICAgICAgICAgICAgfSk7XG4gICAgICAgICQoJ1tkYXRhLXRhcmdldD0nICsgJChlbCkuZGF0YSgnaWQnKSArICddJykub24oJ2NsaWNrJywgZnVuY3Rpb24oZSkge1xuICAgICAgICAgICAgZS5wcmV2ZW50RGVmYXVsdCgpO1xuICAgICAgICAgICAgZS5zdG9wUHJvcGFnYXRpb24oKTtcbiAgICAgICAgICAgICQoJy51aS1tZW51OnZpc2libGUnKS5ub3QoJ1tkYXRhLWlkPVwiJyArICQodGhpcykuZGF0YSgndGFyZ2V0JykgKyAnXCJdJykudHJpZ2dlcignbWVudS5oaWRlJyk7XG4gICAgICAgICAgICAkKGVsKS50b2dnbGUoMCwgZnVuY3Rpb24oKSB7XG4gICAgICAgICAgICAgICAgJChlbCkucG9zaXRpb24oe1xuICAgICAgICAgICAgICAgICAgICBteTogb3B0aW9ucz8ucG9zaXRpb24/Lm15IHx8ICdsZWZ0IHRvcCcsXG4gICAgICAgICAgICAgICAgICAgIGF0OiBcImxlZnQgYm90dG9tXCIsXG4gICAgICAgICAgICAgICAgICAgIG9mOiAkKGUudGFyZ2V0KS5wYXJlbnRzKG9mUGFyZW50U2VsZWN0b3IpLmZpbmQoJy5yZWwtdGhpcycpLFxuICAgICAgICAgICAgICAgICAgICBjb2xsaXNpb246IFwiZml0XCJcbiAgICAgICAgICAgICAgICB9KTtcbiAgICAgICAgICAgIH0pO1xuICAgICAgICB9KTtcbiAgICB9KTtcbiAgICAkKGRvY3VtZW50KS5vbignY2xpY2snLCBmdW5jdGlvbihlKSB7XG4gICAgICAgICQoJy51aS1tZW51OnZpc2libGUnKS50cmlnZ2VyKCdtZW51LmhpZGUnKTtcbiAgICB9KTtcbn07XG5cbmZ1bmN0aW9uIGF1dG9Db21wbGV0ZVJlbmRlckl0ZW0ocmVuZGVyRnVuY3Rpb24gPSBudWxsKSB7XG4gICAgaWYgKG51bGwgPT09IHJlbmRlckZ1bmN0aW9uKSB7XG4gICAgICAgIHJlbmRlckZ1bmN0aW9uID0gZnVuY3Rpb24odWwsIGl0ZW0pIHtcbiAgICAgICAgICAgIGNvbnN0IHJlZ2V4ID0gbmV3IFJlZ0V4cCgnKCcgKyB0aGlzLmVsZW1lbnQudmFsKCkucmVwbGFjZSgvW15BLVphLXowLTnQkC3Qr9CwLdGPXSsvZywgJycpICsgJyknLCAnZ2knKSxcbiAgICAgICAgICAgICAgICBodG1sID0gJCgnPGRpdi8+JykudGV4dChpdGVtLmxhYmVsKS5odG1sKCkucmVwbGFjZShyZWdleCwgJzxiPiQxPC9iPicpO1xuICAgICAgICAgICAgcmV0dXJuICQoJzxsaT48L2xpPicpXG4gICAgICAgICAgICAgICAgLmRhdGEoJ2l0ZW0uYXV0b2NvbXBsZXRlJywgaXRlbSlcbiAgICAgICAgICAgICAgICAuYXBwZW5kKCQoJzxhPjwvYT4nKS5odG1sKGh0bWwpKVxuICAgICAgICAgICAgICAgIC5hcHBlbmRUbyh1bCk7XG4gICAgICAgIH07XG4gICAgfVxuXG4gICAgJC51aS5hdXRvY29tcGxldGUucHJvdG90eXBlLl9yZW5kZXJJdGVtID0gcmVuZGVyRnVuY3Rpb247XG59XG5cbmV4cG9ydCBkZWZhdWx0IHsgaW5pdERyb3Bkb3ducywgYXV0b0NvbXBsZXRlUmVuZGVySXRlbSB9OyIsImZ1bmN0aW9uIFRvb2xUaXAoY29udGV4dCwgb3B0aW9ucykge1xuICAgIGxldCB0b29sdGlwLFxuICAgICAgICBzZWxlY3RvciA9ICdbZGF0YS1yb2xlPVwidG9vbHRpcFwiXSc7XG5cbiAgICBpZiAodW5kZWZpbmVkICE9PSBjb250ZXh0KSB7XG4gICAgICAgIHRvb2x0aXAgPSAkKGNvbnRleHQpLmZpbmQoc2VsZWN0b3IpLmFkZEJhY2soc2VsZWN0b3IpO1xuICAgIH0gZWxzZSB7XG4gICAgICAgIHRvb2x0aXAgPSAkKHNlbGVjdG9yKTtcbiAgICB9XG5cbiAgICB0b29sdGlwXG4gICAgICAgIC50b29sdGlwKHtcbiAgICAgICAgICAgIHRvb2x0aXBDbGFzczogJ2N1c3RvbS10b29sdGlwLXN0eWxpbmcnLFxuICAgICAgICAgICAgcG9zaXRpb246IHtcbiAgICAgICAgICAgICAgICBteTogJ2NlbnRlciBib3R0b20nLFxuICAgICAgICAgICAgICAgIGF0OiAnY2VudGVyIHRvcCcsXG4gICAgICAgICAgICAgICAgY29sbGlzaW9uOiAnZmxpcGZpdCBmbGlwJyxcbiAgICAgICAgICAgICAgICB1c2luZzogZnVuY3Rpb24gKHBvc2l0aW9uLCBmZWVkYmFjaykge1xuICAgICAgICAgICAgICAgICAgICAkKHRoaXMpLmNzcyhwb3NpdGlvbik7XG4gICAgICAgICAgICAgICAgICAgICQoJzxkaXY+JylcbiAgICAgICAgICAgICAgICAgICAgICAgIC5hZGRDbGFzcygnYXJyb3cnKVxuICAgICAgICAgICAgICAgICAgICAgICAgLmFkZENsYXNzKGZlZWRiYWNrLnZlcnRpY2FsKVxuICAgICAgICAgICAgICAgICAgICAgICAgLmNzcyh7XG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgbWFyZ2luTGVmdDogKGZlZWRiYWNrLnRhcmdldC5sZWZ0IC0gZmVlZGJhY2suZWxlbWVudC5sZWZ0IC0gNiAtIDcgKyBmZWVkYmFjay50YXJnZXQud2lkdGggLyAyKSxcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICB3aWR0aDogMFxuICAgICAgICAgICAgICAgICAgICAgICAgfSlcbiAgICAgICAgICAgICAgICAgICAgICAgIC5hcHBlbmRUbyh0aGlzKTtcbiAgICAgICAgICAgICAgICB9LFxuICAgICAgICAgICAgICAgIC4uLm9wdGlvbnNcbiAgICAgICAgICAgIH1cbiAgICAgICAgfSlcbiAgICAgICAgLnJlbW92ZUF0dHIoJ2RhdGEtcm9sZScpXG4gICAgICAgIC5vZmYoJ2ZvY3VzaW4gZm9jdXNvdXQnKVxuICAgICAgICAucHJvcCgndG9vbHRpcC1pbml0aWFsaXplZCcsIHRydWUpO1xufVxuXG5leHBvcnQgZGVmYXVsdCBUb29sVGlwOyIsIi8qZ2xvYmFsICQqL1xuLyplc2xpbnQgbm8tdW5kZWY6IFwiZXJyb3JcIiovXG5cbmltcG9ydCBBUEkgZnJvbSAnLi4vLi4vLi4vYmVtL3RzL3NlcnZpY2UvYXhpb3MnO1xuaW1wb3J0IFJlYWN0LCB7IHVzZUVmZmVjdCwgdXNlUmVkdWNlciwgdXNlU3RhdGUgfSBmcm9tICdyZWFjdCc7XG5pbXBvcnQgUm91dGVyIGZyb20gJy4uLy4uLy4uL2JlbS90cy9zZXJ2aWNlL3JvdXRlcic7XG5pbXBvcnQgVHJhbnNsYXRvciBmcm9tICcuLi8uLi8uLi9iZW0vdHMvc2VydmljZS90cmFuc2xhdG9yJztcbmltcG9ydCBjbGFzc05hbWVzIGZyb20gJ2NsYXNzbmFtZXMnO1xuaW1wb3J0IGZpbHRlciBmcm9tICdsb2Rhc2gvZmlsdGVyJztcbmltcG9ydCBoYXMgZnJvbSAnbG9kYXNoL2hhcyc7XG5pbXBvcnQgaXNFbXB0eSBmcm9tICdsb2Rhc2gvaXNFbXB0eSc7XG4vKmVzbGludCBuby11bnVzZWQtdmFyczogXCJqcXVlcnl1aVwiKi9cbmltcG9ydCBUb29sVGlwIGZyb20gJy4uL1Rvb2xUaXAnO1xuaW1wb3J0IGpxdWVyeXVpIGZyb20gJ2pxdWVyeXVpJztcbmltcG9ydCBtYXAgZnJvbSAnbG9kYXNoL21hcCc7XG5pbXBvcnQgbWFwVmFsdWVzIGZyb20gJ2xvZGFzaC9tYXBWYWx1ZXMnO1xuaW1wb3J0IHVuaXFCeSBmcm9tICdsb2Rhc2gvdW5pcUJ5JztcbmltcG9ydCB2YWx1ZXMgZnJvbSAnbG9kYXNoL3ZhbHVlcyc7XG5cbmltcG9ydCB7IGV4dHJhY3RPcHRpb25zIH0gZnJvbSAnLi4vLi4vLi4vYmVtL3RzL3NlcnZpY2UvZW52JztcbmltcG9ydCBNaWxlVmFsdWVCb3ggZnJvbSAnLi4vLi4vY29tcG9uZW50LWRlcHJlY2F0ZWQvbWlsZXZhbHVlL21pbGV2YWx1ZS1ib3gnO1xuaW1wb3J0IG1haW4gZnJvbSAnLi4vLi4vLi4vZW50cnktcG9pbnQtZGVwcmVjYXRlZC9tYWluJztcblxuY29uc3QgbG9jYWxlID0gZXh0cmFjdE9wdGlvbnMoKS5sb2NhbGU7XG5jb25zdCBjYWNoZSA9IHt9O1xuY29uc3QgbnVtYmVyRm9ybWF0ID0gbmV3IEludGwuTnVtYmVyRm9ybWF0KGxvY2FsZS5yZXBsYWNlKCdfJywgJy0nKSk7XG5jb25zdCBjdXJyZW5jeUZvcm1hdCA9IG5ldyBJbnRsLk51bWJlckZvcm1hdChsb2NhbGUucmVwbGFjZSgnXycsICctJyksIHsgc3R5bGU6ICdjdXJyZW5jeScsIGN1cnJlbmN5OiAnVVNEJyB9KTtcblxuZnVuY3Rpb24gSG90ZWxCcmFuZChwcm9wcykge1xuICAgIGlmIChpc0VtcHR5KHByb3BzLnByb3ZpZGVycykpIHtcbiAgICAgICAgcmV0dXJuICg8ZGl2Lz4pO1xuICAgIH1cblxuICAgIHJldHVybiAoXG4gICAgICAgIDxkaXYgY2xhc3NOYW1lPVwiaG90ZWwtcmV3YXJkLWRhdGFcIj5cbiAgICAgICAgICAgIHttYXAoKHByb3BzLnByb3ZpZGVycyksIChwcm92aWRlcikgPT5cbiAgICAgICAgICAgICAgICA8ZGl2IGtleT17cHJvdmlkZXIucHJvdmlkZXJJZH0gY2xhc3NOYW1lPXsnaG90ZWwtcmV3YXJkJ30+XG4gICAgICAgICAgICAgICAgICAgIDxkaXYgY2xhc3NOYW1lPVwiaGVhZGVyLWRhdGEgZC1pbmxpbmUtYmxvY2sgdy0xMDBcIj5cbiAgICAgICAgICAgICAgICAgICAgICAgIDxkaXYgY2xhc3NOYW1lPVwiZmxvYXQtbGVmdFwiPlxuICAgICAgICAgICAgICAgICAgICAgICAgICAgIDxoMz57cHJvdmlkZXIuYnJhbmROYW1lfTwvaDM+XG4gICAgICAgICAgICAgICAgICAgICAgICA8L2Rpdj5cbiAgICAgICAgICAgICAgICAgICAgICAgIDxkaXYgY2xhc3NOYW1lPVwiZmxvYXQtcmlnaHRcIj5cbiAgICAgICAgICAgICAgICAgICAgICAgICAgICA8c3BhbiBjbGFzc05hbWU9eydob3RlbC1icmFuZC12YWx1ZS1hdmcnfT5cbiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAge1RyYW5zbGF0b3IudHJhbnMoJ2F2ZXJhZ2UtdmFsdWUnKX06IDxiPntwcm92aWRlci5mb3JtYXR0ZWRBdmdQb2ludFZhbHVlfTwvYj5cbiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAge1RyYW5zbGF0b3IudHJhbnMoJ3Blci1wb2ludCcpfTwvc3Bhbj5cbiAgICAgICAgICAgICAgICAgICAgICAgIDwvZGl2PlxuICAgICAgICAgICAgICAgICAgICA8L2Rpdj5cblxuICAgICAgICAgICAgICAgICAgICA8ZGl2IGNsYXNzTmFtZT1cInRhYmxlLXNjcm9sbC1jb250YWluZXJcIj5cblxuICAgICAgICAgICAgICAgICAgICAgICAgPHRhYmxlIGNsYXNzTmFtZT1cIm1haW4tdGFibGUgbm8tYm9yZGVyIGJyYW5kLWhvdGVscyBtb2JpbGUtdGFibGUtdjJcIj5cbiAgICAgICAgICAgICAgICAgICAgICAgICAgICA8dGhlYWQ+XG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgPHRyPlxuICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICA8dGggY2xhc3NOYW1lPXsnaG90ZWwtbmFtZSd9PntUcmFuc2xhdG9yLnRyYW5zKCdpdGluZXJhcmllcy5yZXNlcnZhdGlvbi5waG9uZXMudGl0bGUnLCB7fSwgJ3RyaXBzJyl9PC90aD5cbiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgPHRoIGNsYXNzTmFtZT17J2hvdGVsLXZhbHVlLXJlZGVtcHRpb24gdGV4dC1jZW50ZXInfT57VHJhbnNsYXRvci50cmFucygncmVkZW1wdGlvbi12YWx1ZScpfTwvdGg+XG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIDx0aCBjbGFzc05hbWU9eydob3RlbC12YWx1ZS1hdmctcGVyY2VudCB0ZXh0LWNlbnRlcid9PntUcmFuc2xhdG9yLnRyYW5zKCdwZXJjZW50LWFib3ZlLWF2ZycpfTwvdGg+XG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIDx0aCBjbGFzc05hbWU9eydob3RlbC12YWx1ZS1hdmctY2FzaHByaWNlIHRleHQtY2VudGVyJ30+e1RyYW5zbGF0b3IudHJhbnMoJ2F2Zy1jYXNoLXByaWNlLW5pZ2h0Jyl9PC90aD5cbiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgPHRoIGNsYXNzTmFtZT17J2hvdGVsLXZhbHVlLWF2Zy1wb2ludHByaWNlIHRleHQtY2VudGVyJ30+e1RyYW5zbGF0b3IudHJhbnMoJ2F2Zy1wb2ludC1wcmljZS1uaWdodCcpfTwvdGg+XG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIDx0aCBjbGFzc05hbWU9eydob3RlbC1jaGVjay1saW5rJ30+PC90aD5cbiAgICAgICAgICAgICAgICAgICAgICAgICAgICA8L3RyPlxuICAgICAgICAgICAgICAgICAgICAgICAgICAgIDwvdGhlYWQ+XG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgPHRib2R5PlxuICAgICAgICAgICAgICAgICAgICAgICAgICAgIHttYXAoKHByb3ZpZGVyLmhvdGVscyksIChob3RlbCkgPT5cbiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgPHRyIGtleT17aG90ZWwuaG90ZWxJZH0gY2xhc3NOYW1lPXtjbGFzc05hbWVzKHtcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICdhYm92ZS1wb3NpdGl2ZSc6IGhvdGVsLmF2Z0Fib3ZlVmFsdWUgPiAwLFxuICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgJ2Fib3ZlLW5lZ2F0aXZlJzogaG90ZWwuYXZnQWJvdmVWYWx1ZSA8IDAsXG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIH0pfT5cbiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIDx0ZCBjbGFzc05hbWU9eydob3RlbC1uYW1lJ30+XG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgPGI+e2hvdGVsLm5hbWV9PC9iPlxuICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIDxzcGFuIGNsYXNzTmFtZT17J3NpbHZlci10ZXh0J30+e2hvdGVsLmxvY2F0aW9ufTwvc3Bhbj5cbiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIDwvdGQ+XG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICA8dGQgY2xhc3NOYW1lPXsndGV4dC1jZW50ZXInfSB0aXRsZT17XG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgaG90ZWwubWF0Y2hDb3VudFxuICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICA/IFRyYW5zbGF0b3IudHJhbnMoJ2Jhc2VkLW9uLWxhc3QtYm9va2luZ3MnLCB7XG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAnbnVtYmVyJzogbnVtYmVyRm9ybWF0LmZvcm1hdChob3RlbC5tYXRjaENvdW50KSxcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICdhcy1vZi1kYXRlJzogJydcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgfSlcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgOiAnJ1xuICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgfT48c3BhbiBkYXRhLXRpcD17Jyd9XG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIGRhdGEtcm9sZT1cInRvb2x0aXBcIj57bnVtYmVyRm9ybWF0LmZvcm1hdChob3RlbC5wb2ludFZhbHVlKX0gwqI8L3NwYW4+PC90ZD5cbiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIDx0ZCBjbGFzc05hbWU9eydjb2wtYWJvdmUgdGV4dC1jZW50ZXInfT57bnVtYmVyRm9ybWF0LmZvcm1hdChob3RlbC5hdmdBYm92ZVZhbHVlKX0lPC90ZD5cbiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIDx0ZCBjbGFzc05hbWU9eyd0ZXh0LWNlbnRlcid9PntjdXJyZW5jeUZvcm1hdC5mb3JtYXQoaG90ZWwuY2FzaFByaWNlKX08L3RkPlxuICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgPHRkIGNsYXNzTmFtZT17J3RleHQtY2VudGVyJ30+e251bWJlckZvcm1hdC5mb3JtYXQoaG90ZWwucG9pbnRQcmljZSl9PC90ZD5cbiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIDx0ZD57aXNFbXB0eShob3RlbC5saW5rKVxuICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgID8gJydcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICA6IDxhIGNsYXNzTmFtZT17J2JsdWUtbGluayd9IHRhcmdldD1cIl9ibGFua1wiXG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICBocmVmPXtob3RlbC5saW5rfSByZWw9XCJub3JlZmVycmVyXCI+e1RyYW5zbGF0b3IudHJhbnMoJ2NoZWNrLWF2YWlsYWJpbGl0eScpfTwvYT59PC90ZD5cbiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgPC90cj5cbiAgICAgICAgICAgICAgICAgICAgICAgICAgICApfVxuICAgICAgICAgICAgICAgICAgICAgICAgICAgIDwvdGJvZHk+XG4gICAgICAgICAgICAgICAgICAgICAgICA8L3RhYmxlPlxuXG4gICAgICAgICAgICAgICAgICAgIDwvZGl2PlxuICAgICAgICAgICAgICAgIDwvZGl2PlxuICAgICAgICAgICAgKX1cbiAgICAgICAgPC9kaXY+XG4gICAgKTtcbn1cblxuZnVuY3Rpb24gRm9ybUFkZHJlc3MocHJvcHMpIHtcbiAgICB1c2VFZmZlY3QoKCkgPT4ge1xuICAgICAgICAkKCcuc2VhcmNoLWlucHV0W25hbWU9XCJxXCJdJylcbiAgICAgICAgICAgIC5hdXRvY29tcGxldGUoe1xuICAgICAgICAgICAgICAgIGRlbGF5OiA1MDAsXG4gICAgICAgICAgICAgICAgbWluTGVuZ3RoOiAyLFxuICAgICAgICAgICAgICAgIHNlYXJjaDogZnVuY3Rpb24oZXZlbnQpIHtcbiAgICAgICAgICAgICAgICAgICAgaWYgKCQoZXZlbnQudGFyZ2V0KS52YWwoKS5sZW5ndGggPj0gMilcbiAgICAgICAgICAgICAgICAgICAgICAgICQoZXZlbnQudGFyZ2V0KS5hZGRDbGFzcygnbG9hZGluZy1pbnB1dCcpO1xuICAgICAgICAgICAgICAgICAgICBlbHNlXG4gICAgICAgICAgICAgICAgICAgICAgICAkKGV2ZW50LnRhcmdldCkucmVtb3ZlQ2xhc3MoJ2xvYWRpbmctaW5wdXQnKTtcbiAgICAgICAgICAgICAgICB9LFxuICAgICAgICAgICAgICAgIG9wZW46IGZ1bmN0aW9uKGV2ZW50KSB7XG4gICAgICAgICAgICAgICAgICAgICQoZXZlbnQudGFyZ2V0KS5yZW1vdmVDbGFzcygnbG9hZGluZy1pbnB1dCcpXG4gICAgICAgICAgICAgICAgfSxcbiAgICAgICAgICAgICAgICBzb3VyY2U6IGZ1bmN0aW9uKHJlcXVlc3QsIHJlc3BvbnNlKSB7XG4gICAgICAgICAgICAgICAgICAgIGNvbnN0IHNlbGYgPSB0aGlzO1xuICAgICAgICAgICAgICAgICAgICAkKHRoaXMpLmNsb3Nlc3QoJy5pbnB1dC1pdGVtJykuZmluZCgnLmFkZHJlc3MtdGltZXpvbmUnKS50ZXh0KCcnKTtcbiAgICAgICAgICAgICAgICAgICAgbGV0IGZyYWdtZW50TmFtZVBvcyA9IHJlcXVlc3QudGVybS5pbmRleE9mKCcjJyksIGhvdGVsTmFtZUZyYWdtZW50ID0gJyc7XG4gICAgICAgICAgICAgICAgICAgIGlmICgtMSAhPT0gZnJhZ21lbnROYW1lUG9zKSB7XG4gICAgICAgICAgICAgICAgICAgICAgICBob3RlbE5hbWVGcmFnbWVudCA9IHJlcXVlc3QudGVybS5zdWJzdHIoMSArIGZyYWdtZW50TmFtZVBvcyk7XG4gICAgICAgICAgICAgICAgICAgICAgICByZXF1ZXN0LnRlcm0gPSByZXF1ZXN0LnRlcm0uc3Vic3RyKDAsIGZyYWdtZW50TmFtZVBvcyk7XG4gICAgICAgICAgICAgICAgICAgIH1cbiAgICAgICAgICAgICAgICAgICAgJFxuICAgICAgICAgICAgICAgICAgICAgICAgLmdldChSb3V0ZXIuZ2VuZXJhdGUoJ2F3X2hvdGVscmV3YXJkX2dlbycsIHtcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICBxdWVyeTogZW5jb2RlVVJJQ29tcG9uZW50KHJlcXVlc3QudGVybSlcbiAgICAgICAgICAgICAgICAgICAgICAgIH0pKVxuICAgICAgICAgICAgICAgICAgICAgICAgLmRvbmUoZnVuY3Rpb24oZGF0YSkge1xuICAgICAgICAgICAgICAgICAgICAgICAgICAgICQoc2VsZi5lbGVtZW50KS5yZW1vdmVDbGFzcygnbG9hZGluZy1pbnB1dCcpO1xuICAgICAgICAgICAgICAgICAgICAgICAgICAgIGlmICghZGF0YSkgcmV0dXJuO1xuICAgICAgICAgICAgICAgICAgICAgICAgICAgIHJlc3BvbnNlKGRhdGEubWFwKGZ1bmN0aW9uKGl0ZW0pIHtcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgbGV0IHJlc3VsdCA9IHt9O1xuICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICBpZiAoJ3VuZGVmaW5lZCcgIT09IHR5cGVvZiBpdGVtLnBsYWNlX2lkKSB7XG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICByZXN1bHQucGxhY2VfaWQgPSBpdGVtLnBsYWNlX2lkO1xuICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICB9XG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIGlmICgndW5kZWZpbmVkJyAhPT0gdHlwZW9mIGl0ZW0uZm9ybWF0dGVkX2FkZHJlc3MpIHtcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIHJlc3VsdC5mb3JtYXR0ZWRfYWRkcmVzcyA9XG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgcmVzdWx0LmxhYmVsID1cbiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgcmVzdWx0LnZhbHVlID0gaXRlbS5mb3JtYXR0ZWRfYWRkcmVzcztcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgfVxuICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICBpZiAoJ3VuZGVmaW5lZCcgIT09IHR5cGVvZiBpdGVtLmV4dGVuZCkge1xuICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgcmVzdWx0LmV4dGVuZCA9IGl0ZW0uZXh0ZW5kO1xuICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICB9XG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIHJlc3VsdC5mcmFnbWVudE5hbWUgPSBob3RlbE5hbWVGcmFnbWVudDtcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgcmV0dXJuIHJlc3VsdDtcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICB9KSk7XG4gICAgICAgICAgICAgICAgICAgICAgICB9KVxuICAgICAgICAgICAgICAgICAgICAgICAgLmZhaWwoZnVuY3Rpb24oKSB7XG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgcmV0dXJuIFtdO1xuICAgICAgICAgICAgICAgICAgICAgICAgfSk7XG4gICAgICAgICAgICAgICAgfSxcbiAgICAgICAgICAgICAgICBzZWxlY3Q6IGZ1bmN0aW9uKGV2ZW50LCB1aSkge1xuICAgICAgICAgICAgICAgICAgICBldmVudC5wcmV2ZW50RGVmYXVsdCgpO1xuICAgICAgICAgICAgICAgICAgICBwcm9wcy5zZXRBZGRyZXNzKHVpLml0ZW0pO1xuICAgICAgICAgICAgICAgICAgICAkKGV2ZW50LnRhcmdldClcbiAgICAgICAgICAgICAgICAgICAgICAgIC52YWwodWkuaXRlbS52YWx1ZSlcbiAgICAgICAgICAgICAgICAgICAgICAgIC50cmlnZ2VyKCdjaGFuZ2UnKTtcbiAgICAgICAgICAgICAgICAgICAgcHJvcHMuZm9ybUFkZHJlc3NTdWJtaXQoZXZlbnQsIHVpLml0ZW0pO1xuICAgICAgICAgICAgICAgIH0sXG4gICAgICAgICAgICAgICAgY3JlYXRlOiBmdW5jdGlvbigpIHtcbiAgICAgICAgICAgICAgICAgICAgJCh0aGlzKS5kYXRhKCd1aS1hdXRvY29tcGxldGUnKS5fcmVuZGVySXRlbSA9IGZ1bmN0aW9uKHVsLCBpdGVtKSB7XG4gICAgICAgICAgICAgICAgICAgICAgICBsZXQgcmVnZXggPSBuZXcgUmVnRXhwKCcoJyArIHRoaXMuZWxlbWVudC52YWwoKSArICcpJywgJ2dpJyk7XG4gICAgICAgICAgICAgICAgICAgICAgICBsZXQgaXRlbUxhYmVsID0gaXRlbS5sYWJlbC5yZXBsYWNlKHJlZ2V4LCBcIjxiPiQxPC9iPlwiKTtcbiAgICAgICAgICAgICAgICAgICAgICAgIHJldHVybiAkKCc8bGk+PC9saT4nKVxuICAgICAgICAgICAgICAgICAgICAgICAgICAgIC5kYXRhKCdpdGVtLmF1dG9jb21wbGV0ZScsIGl0ZW0pXG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgLmFwcGVuZCgkKCc8YT48L2E+JykuaHRtbChpdGVtTGFiZWwpKVxuICAgICAgICAgICAgICAgICAgICAgICAgICAgIC5hcHBlbmRUbyh1bCk7XG4gICAgICAgICAgICAgICAgICAgIH07XG4gICAgICAgICAgICAgICAgfVxuICAgICAgICAgICAgfSk7XG4gICAgfSwgW10pO1xuXG4gICAgcmV0dXJuIChcbiAgICAgICAgPGZvcm0gb25TdWJtaXQ9e3Byb3BzLmZvcm1BZGRyZXNzU3VibWl0fT5cbiAgICAgICAgICAgIDxkaXYgY2xhc3NOYW1lPVwic2VhcmNoIGNvbHVtblwiPlxuICAgICAgICAgICAgICAgIDxkaXYgY2xhc3NOYW1lPVwicm93XCI+XG4gICAgICAgICAgICAgICAgICAgIDxkaXYgY2xhc3NOYW1lPVwiaW5wdXRcIj5cbiAgICAgICAgICAgICAgICAgICAgICAgIDxpbnB1dCBjbGFzc05hbWU9e2NsYXNzTmFtZXMoe1xuICAgICAgICAgICAgICAgICAgICAgICAgICAgICdpbnB1dC1pdGVtJzogdHJ1ZSxcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICAnc2VhcmNoLWlucHV0JzogdHJ1ZSxcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICAnc2VhcmNoLWlucHV0LWZpbGwnOiAhaXNFbXB0eUFkZHJlc3MocHJvcHMuYWRkcmVzcylcbiAgICAgICAgICAgICAgICAgICAgICAgIH0pfVxuICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIG5hbWU9XCJxXCJcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICB0eXBlPVwidGV4dFwiXG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgcGxhY2Vob2xkZXI9e1RyYW5zbGF0b3IudHJhbnMoJ2VudGVyLWNpdHktc3RhdGUtY291bnRyeS1zZWFyY2gnKX1cbiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICBhdXRvQ29tcGxldGU9XCJvZmZcIlxuICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIHZhbHVlPXtwcm9wcy5hZGRyZXNzLnZhbHVlfVxuICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIG9uQ2hhbmdlPXsoZXZlbnQpID0+IHByb3BzLnNldEFkZHJlc3MoZXZlbnQudGFyZ2V0LnZhbHVlKX1cbiAgICAgICAgICAgICAgICAgICAgICAgIC8+XG4gICAgICAgICAgICAgICAgICAgICAgICA8YSBjbGFzc05hbWU9eydjbGVhci1zZWFyY2gnfSBocmVmPVwiXCIgb25DbGljaz17KGV2ZW50KSA9PiB7XG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgZXZlbnQucHJldmVudERlZmF1bHQoKTtcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICBwcm9wcy5zZXRBZGRyZXNzKHsgJ3ZhbHVlJzogJycsICdwbGFjZV9pZCc6ICcnIH0pO1xuICAgICAgICAgICAgICAgICAgICAgICAgfX0+PGkgY2xhc3NOYW1lPVwiaWNvbi1jbG9zZS1zaWx2ZXJcIj48L2k+PC9hPlxuICAgICAgICAgICAgICAgICAgICA8L2Rpdj5cbiAgICAgICAgICAgICAgICA8L2Rpdj5cbiAgICAgICAgICAgIDwvZGl2PlxuICAgICAgICA8L2Zvcm0+XG4gICAgKTtcbn1cblxuZnVuY3Rpb24gU2VhcmNoUmVzdWx0KHByb3BzKSB7XG4gICAgbGV0IGhvdGVscyA9IHByb3BzLmhvdGVsc0xpc3Q7XG5cbiAgICBpZiAoaGFzKGhvdGVscywgJ25vdEZvdW5kJykgfHwgaGFzKGhvdGVscywgJ3N1Y2Nlc3MnKSkge1xuICAgICAgICByZXR1cm4gPGRpdj5cbiAgICAgICAgICAgIDxwIGlkPVwibm90Rm91bmRcIiBjbGFzc05hbWU9XCJuby1yZXN1bHRcIj5cbiAgICAgICAgICAgICAgICA8aSBjbGFzc05hbWU9XCJpY29uLXdhcm5pbmctc21hbGxcIj48L2k+XG4gICAgICAgICAgICAgICAgPHNwYW4+e1RyYW5zbGF0b3IudHJhbnMoJ25vX3Jlc3VsdHNfZm91bmQnKX08L3NwYW4+XG4gICAgICAgICAgICA8L3A+XG4gICAgICAgIDwvZGl2PjtcbiAgICB9XG5cbiAgICBsZXQgYnJhbmRzID0gdW5pcUJ5KHZhbHVlcyhtYXBWYWx1ZXMoaG90ZWxzLCAnYnJhbmROYW1lJykpKTtcbiAgICBsZXQgYnJhbmRzTWF4TGVuZ3RoTmFtZSA9IDA7XG4gICAgYnJhbmRzLm1hcCgobmFtZSkgPT4ge1xuICAgICAgICBpZiAobmFtZS5sZW5ndGggPiBicmFuZHNNYXhMZW5ndGhOYW1lKSB7XG4gICAgICAgICAgICBicmFuZHNNYXhMZW5ndGhOYW1lID0gbmFtZS5sZW5ndGg7XG4gICAgICAgIH1cbiAgICB9KTtcblxuICAgIGxldCBmaWx0ZXJlZEhvdGVscyA9IGhvdGVscztcbiAgICBpZiAoJycgIT09IHByb3BzLmZpbHRlck9wdGlvbnMuYnJhbmQpIHtcbiAgICAgICAgZmlsdGVyZWRIb3RlbHMgPSBmaWx0ZXIoaG90ZWxzLCAoaG90ZWwpID0+IHtcbiAgICAgICAgICAgIHJldHVybiBob3RlbC5icmFuZE5hbWUgPT0gcHJvcHMuZmlsdGVyT3B0aW9ucy5icmFuZDtcbiAgICAgICAgfSk7XG4gICAgfVxuICAgIGxldCByb3dJbmRleCA9IDAsIGlzRmlyc3ROZXV0cmFsID0gbnVsbCwgaXNGaXJzdE5lZ2F0aXZlID0gbnVsbDtcblxuICAgIGlmIChmaWx0ZXJlZEhvdGVsc1swXT8uYXZnQWJvdmVWYWx1ZSA8IDApIHtcbiAgICAgICAgaXNGaXJzdE5lZ2F0aXZlID0gaXNGaXJzdE5lZ2F0aXZlID0gZmFsc2U7XG4gICAgfVxuXG4gICAgcmV0dXJuIChcbiAgICAgICAgPGRpdiBjbGFzc05hbWU9XCJob3RlbC1yZXdhcmQtcGxhY2VcIj5cbiAgICAgICAgICAgIDxkaXYgY2xhc3NOYW1lPXsnaG90ZWwtcmV3YXJkJ30+XG4gICAgICAgICAgICAgICAgPGgxPntUcmFuc2xhdG9yLnRyYW5zKCdzZWFyY2gtcmVzdWx0LW5lYXInLCB7ICdxdWVyeSc6IHByb3BzLmFkZHJlc3Muc2VsZWN0ZWQgfSl9PC9oMT5cbiAgICAgICAgICAgICAgICA8ZGl2IGNsYXNzTmFtZT1cInRhYmxlLXNjcm9sbC1jb250YWluZXJcIj5cblxuICAgICAgICAgICAgICAgICAgICA8dGFibGUgY2xhc3NOYW1lPVwibWFpbi10YWJsZSBuby1ib3JkZXIgYnJhbmQtaG90ZWxzIG1vYmlsZS10YWJsZS12MlwiPlxuICAgICAgICAgICAgICAgICAgICAgICAgPHRoZWFkPlxuICAgICAgICAgICAgICAgICAgICAgICAgPHRyPlxuICAgICAgICAgICAgICAgICAgICAgICAgICAgIDx0aCBjbGFzc05hbWU9eydob3RlbC1uYW1lJ30+e1RyYW5zbGF0b3IudHJhbnMoJ2l0aW5lcmFyaWVzLnJlc2VydmF0aW9uLnBob25lcy50aXRsZScsIHt9LCAndHJpcHMnKX08L3RoPlxuICAgICAgICAgICAgICAgICAgICAgICAgICAgIDx0aCBjbGFzc05hbWU9eydob3RlbC12YWx1ZS1yZWRlbXB0aW9uJ30+XG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIDxkaXYgY2xhc3NOYW1lPXsnc3R5bGVkLXNlbGVjdCd9IHN0eWxlPXt7ICd3aWR0aCc6IGJyYW5kc01heExlbmd0aE5hbWUgKiAxMSB9fT5cbiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIDxkaXY+XG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgPHNlbGVjdCB2YWx1ZT17cHJvcHMuZmlsdGVyT3B0aW9ucy5icmFuZH0gb25DaGFuZ2U9eyhldmVudCkgPT4ge1xuICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICBwcm9wcy5zZXRGaWx0ZXJPcHRpb25zKHsgJ2JyYW5kJzogZXZlbnQudGFyZ2V0LnZhbHVlIH0pO1xuICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIH19PlxuICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICA8b3B0aW9uIHZhbHVlPXsnJ30+e1RyYW5zbGF0b3IudHJhbnMoJ3N0YXR1cy5hbGwnKX08L29wdGlvbj5cbiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAge21hcCgoYnJhbmRzKSwgKGJyYW5kKSA9PlxuICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgPG9wdGlvbiBrZXk9e2JyYW5kfSB2YWx1ZT17YnJhbmR9PnticmFuZH08L29wdGlvbj5cbiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgKX1cbiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICA8L3NlbGVjdD5cbiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIDwvZGl2PlxuICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICA8L2Rpdj5cbiAgICAgICAgICAgICAgICAgICAgICAgICAgICA8L3RoPlxuICAgICAgICAgICAgICAgICAgICAgICAgICAgIDx0aCBjbGFzc05hbWU9eyd0ZXh0LWNlbnRlcid9PntUcmFuc2xhdG9yLnRyYW5zKCdyZWRlbXB0aW9uLXZhbHVlJyl9PC90aD5cbiAgICAgICAgICAgICAgICAgICAgICAgICAgICA8dGggY2xhc3NOYW1lPXsnaG90ZWwtdmFsdWUtYXZnLXBlcmNlbnQgdGV4dC1jZW50ZXInfT57VHJhbnNsYXRvci50cmFucygncGVyY2VudC1hYm92ZS1hdmcnKX08L3RoPlxuICAgICAgICAgICAgICAgICAgICAgICAgICAgIDx0aCBjbGFzc05hbWU9eydob3RlbC12YWx1ZS1hdmctY2FzaHByaWNlIHRleHQtY2VudGVyJ30+e1RyYW5zbGF0b3IudHJhbnMoJ2F2Zy1jYXNoLXByaWNlLW5pZ2h0Jyl9PC90aD5cbiAgICAgICAgICAgICAgICAgICAgICAgICAgICA8dGggY2xhc3NOYW1lPXsnaG90ZWwtdmFsdWUtYXZnLXBvaW50cHJpY2UgdGV4dC1jZW50ZXInfT57VHJhbnNsYXRvci50cmFucygnYXZnLXBvaW50LXByaWNlLW5pZ2h0Jyl9PC90aD5cbiAgICAgICAgICAgICAgICAgICAgICAgICAgICA8dGggY2xhc3NOYW1lPXsnaG90ZWwtY2hlY2stbGluayd9PjwvdGg+XG4gICAgICAgICAgICAgICAgICAgICAgICA8L3RyPlxuICAgICAgICAgICAgICAgICAgICAgICAgPC90aGVhZD5cbiAgICAgICAgICAgICAgICAgICAgICAgIDx0Ym9keT5cbiAgICAgICAgICAgICAgICAgICAgICAgIHttYXAoKGZpbHRlcmVkSG90ZWxzKSwgKGhvdGVsKSA9PiB7XG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICsrcm93SW5kZXg7XG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIGlmICgwID09PSBob3RlbC5hdmdBYm92ZVZhbHVlKSB7XG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICBpc0ZpcnN0TmV1dHJhbCA9IG51bGwgPT09IGlzRmlyc3ROZXV0cmFsO1xuICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICB9XG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIGlmIChob3RlbC5hdmdBYm92ZVZhbHVlIDwgMCkge1xuICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgaXNGaXJzdE5lZ2F0aXZlID0gbnVsbCA9PT0gaXNGaXJzdE5lZ2F0aXZlO1xuICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICB9XG5cbiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgbGV0IGNzc0NsYXNzZXMgPSBjbGFzc05hbWVzKHtcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICdhYm92ZS1wb3NpdGl2ZSc6IGhvdGVsLmF2Z0Fib3ZlVmFsdWUgPiAwLFxuICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgJ2Fib3ZlLW5lZ2F0aXZlJzogaG90ZWwuYXZnQWJvdmVWYWx1ZSA8IDAsXG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAnZmlyc3QtbmV1dHJhbCc6IGlzRmlyc3ROZXV0cmFsLFxuICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgJ2ZpcnN0LW5lZ2F0aXZlJzogaXNGaXJzdE5lZ2F0aXZlLFxuICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICB9KTtcblxuICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICBpZiAoaXNGaXJzdE5ldXRyYWwpIGlzRmlyc3ROZXV0cmFsID0gZmFsc2U7XG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIGlmIChpc0ZpcnN0TmVnYXRpdmUpIGlzRmlyc3ROZWdhdGl2ZSA9IGZhbHNlO1xuXG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIHJldHVybiA8dHIga2V5PXtob3RlbC5ob3RlbElkfSBjbGFzc05hbWU9e2Nzc0NsYXNzZXN9PlxuICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgPHRkIGNsYXNzTmFtZT17J2hvdGVsLW5hbWUnfT5cbiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICA8Yj57aG90ZWwubmFtZX08L2I+XG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgPHNwYW4gY2xhc3NOYW1lPXsnc2lsdmVyLXRleHQnfT57aG90ZWwubG9jYXRpb259PC9zcGFuPlxuICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgPC90ZD5cbiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIDx0ZD57aG90ZWwuYnJhbmROYW1lfTwvdGQ+XG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICA8dGQgY2xhc3NOYW1lPXsndGV4dC1jZW50ZXInfSB0aXRsZT17XG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgaG90ZWwubWF0Y2hDb3VudFxuICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICA/IFRyYW5zbGF0b3IudHJhbnMoJ2Jhc2VkLW9uLWxhc3QtYm9va2luZ3MnLCB7XG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAnbnVtYmVyJzogbnVtYmVyRm9ybWF0LmZvcm1hdChob3RlbC5tYXRjaENvdW50KSxcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICdhcy1vZi1kYXRlJzogJycsXG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIH0pXG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIDogJydcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIH0+PHNwYW4gZGF0YS10aXA9eycnfVxuICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICBkYXRhLXJvbGU9XCJ0b29sdGlwXCI+e251bWJlckZvcm1hdC5mb3JtYXQoaG90ZWwucG9pbnRWYWx1ZSl9IMKiPC9zcGFuPjwvdGQ+XG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICA8dGQgY2xhc3NOYW1lPXsnY29sLWFib3ZlIHRleHQtY2VudGVyJ30+e251bWJlckZvcm1hdC5mb3JtYXQoaG90ZWwuYXZnQWJvdmVWYWx1ZSl9JTwvdGQ+XG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICA8dGQgY2xhc3NOYW1lPXsndGV4dC1jZW50ZXInfT57Y3VycmVuY3lGb3JtYXQuZm9ybWF0KGhvdGVsLmNhc2hQcmljZSl9PC90ZD5cbiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIDx0ZCBjbGFzc05hbWU9eyd0ZXh0LWNlbnRlcid9PntudW1iZXJGb3JtYXQuZm9ybWF0KGhvdGVsLnBvaW50UHJpY2UpfTwvdGQ+XG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICA8dGQ+e2lzRW1wdHkoaG90ZWwubGluaylcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICA/ICcnXG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgOiA8YSBjbGFzc05hbWU9eydibHVlLWxpbmsnfSB0YXJnZXQ9XCJfYmxhbmtcIlxuICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgaHJlZj17aG90ZWwubGlua30gcmVsPVwibm9yZWZlcnJlclwiPntUcmFuc2xhdG9yLnRyYW5zKCdjaGVjay1hdmFpbGFiaWxpdHknKX08L2E+fTwvdGQ+XG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIDwvdHI+XG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgfVxuICAgICAgICAgICAgICAgICAgICAgICAgKX1cbiAgICAgICAgICAgICAgICAgICAgICAgIDwvdGJvZHk+XG4gICAgICAgICAgICAgICAgICAgIDwvdGFibGU+XG5cbiAgICAgICAgICAgICAgICA8L2Rpdj5cbiAgICAgICAgICAgIDwvZGl2PlxuICAgICAgICA8L2Rpdj5cbiAgICApO1xufVxuXG5mdW5jdGlvbiBDb250ZW50RGF0YShwcm9wcykge1xuICAgIGlmICghaXNFbXB0eUFkZHJlc3MocHJvcHMuYWRkcmVzcykgJiYgIWlzRW1wdHkocHJvcHMuaG90ZWxzTGlzdCkpIHtcbiAgICAgICAgcmV0dXJuIFNlYXJjaFJlc3VsdChwcm9wcyk7XG4gICAgfVxuXG4gICAgcmV0dXJuIDxIb3RlbEJyYW5kIHByb3ZpZGVycz17cHJvcHMucHJpbWFyeUxpc3R9Lz47XG59XG5cbmZ1bmN0aW9uIEhvdGVsUmV3YXJkKHByb3BzKSB7XG4gICAgY29uc3QgW2lzTG9hZGluZywgc2V0TG9hZGluZ10gPSB1c2VTdGF0ZShmYWxzZSk7XG4gICAgY29uc3QgW2hvdGVsc0xpc3QsIHNldEhvdGVsc0xpc3RdID0gdXNlU3RhdGUoW10pO1xuICAgIGNvbnN0IGluaXRpYWxBZGRyZXNzID0geyBsYWJlbDogJycsIHBsYWNlX2lkOiAnJywgdmFsdWU6ICcnLCBzZWxlY3RlZDogJycgfTtcblxuICAgIGZ1bmN0aW9uIGhhbmRsZXJTZWFyY2hBZGRyZXNzU3RhdGUocHJldiwgc3RhdGUpIHtcbiAgICAgICAgaWYgKGlzRW1wdHlBZGRyZXNzKHN0YXRlKSkge1xuICAgICAgICAgICAgc2V0SG90ZWxzTGlzdChbXSk7XG4gICAgICAgICAgICBzZXRMb2NhdGlvblN0YXRlKG51bGwpO1xuICAgICAgICAgICAgcmV0dXJuIGluaXRpYWxBZGRyZXNzO1xuICAgICAgICB9XG4gICAgICAgIGlmICgnc3RyaW5nJyA9PT0gdHlwZW9mIHN0YXRlKSB7XG4gICAgICAgICAgICByZXR1cm4geyAuLi5pbml0aWFsQWRkcmVzcywgLi4ueyB2YWx1ZTogc3RhdGUsIHNlbGVjdGVkOiBwcmV2LnNlbGVjdGVkIH0gfTtcbiAgICAgICAgfVxuXG4gICAgICAgIHJldHVybiBzdGF0ZTtcbiAgICB9XG5cbiAgICBjb25zdCBbYWRkcmVzcywgc2V0QWRkcmVzc10gPSB1c2VSZWR1Y2VyKGhhbmRsZXJTZWFyY2hBZGRyZXNzU3RhdGUsIGluaXRpYWxBZGRyZXNzKTtcblxuICAgIHVzZUVmZmVjdCgoKSA9PiB7XG4gICAgICAgIGNvbnN0IHJvdXRlID0gUm91dGVyLmdlbmVyYXRlKCdhd19ob3RlbHJld2FyZF9pbmRleCcpO1xuICAgICAgICBjb25zdCBbcGxhY2VJZCwgcGxhY2VOYW1lXSA9IGRlY29kZVVSSShkZWNvZGVVUklDb21wb25lbnQobG9jYXRpb24ucGF0aG5hbWUuc3Vic3RyKGxvY2F0aW9uLnBhdGhuYW1lLmluZGV4T2Yocm91dGUpICsgcm91dGUubGVuZ3RoKSkpXG4gICAgICAgICAgICAucmVwbGFjZSgvXlxcLyt8XFwvKyQvZywgJycpXG4gICAgICAgICAgICAucmVwbGFjZSgvXFwrL2csICcgJylcbiAgICAgICAgICAgIC5zcGxpdCgnLycpO1xuICAgICAgICBpZiAoIWlzRW1wdHkocGxhY2VJZCkgJiYgIWlzRW1wdHkocGxhY2VOYW1lKSkge1xuICAgICAgICAgICAgY29uc3QgZmV0Y2hBZGRyZXNzID0geyBwbGFjZV9pZDogcGxhY2VJZCwgdmFsdWU6IHBsYWNlTmFtZSwgbGFiZWw6IHBsYWNlTmFtZSwgc2VsZWN0ZWQ6IHBsYWNlTmFtZSB9O1xuICAgICAgICAgICAgc2V0QWRkcmVzcyhmZXRjaEFkZHJlc3MpO1xuICAgICAgICAgICAgaGFuZGxlRm9ybUFkZHJlc3NTdWJtaXQoeyBwcmV2ZW50RGVmYXVsdDogKCkgPT4gZmFsc2UgfSwgZmV0Y2hBZGRyZXNzKTtcbiAgICAgICAgfVxuXG4gICAgICAgIHdpbmRvdy5vbnBvcHN0YXRlID0gZnVuY3Rpb24oZXZlbnQpIHtcbiAgICAgICAgICAgIGlmIChoYXMoZXZlbnQuc3RhdGUsICdwbGFjZScpKSB7XG4gICAgICAgICAgICAgICAgcmV0dXJuIGhhbmRsZUZvcm1BZGRyZXNzU3VibWl0KHsgcHJldmVudERlZmF1bHQ6ICgpID0+IGZhbHNlIH0sIGV2ZW50LnN0YXRlLnBsYWNlKTtcbiAgICAgICAgICAgIH1cbiAgICAgICAgICAgIC8vc2V0SG90ZWxzTGlzdChbXSk7XG4gICAgICAgICAgICBzZXRBZGRyZXNzKGluaXRpYWxBZGRyZXNzKTtcbiAgICAgICAgfTtcblxuICAgIH0sIFtdKTtcblxuICAgIHVzZUVmZmVjdCgoKSA9PiB7XG4gICAgICAgIFRvb2xUaXAoKTtcbiAgICB9KTtcblxuICAgIGNvbnN0IGhhbmRsZUZvcm1BZGRyZXNzU3VibWl0ID0gYXN5bmMgKGV2ZW50LCBhZGRyKSA9PiB7XG4gICAgICAgIGV2ZW50LnByZXZlbnREZWZhdWx0KCk7XG4gICAgICAgIHNldEZpbHRlck9wdGlvbnMoeyBicmFuZDogJycgfSk7XG4gICAgICAgIGNvbnN0IHBsYWNlRGF0YSA9IGhhcyhhZGRyLCAncGxhY2VfaWQnKSA/IGFkZHIgOiBhZGRyZXNzO1xuICAgICAgICBzZXRBZGRyZXNzKHsgLi4ucGxhY2VEYXRhLCAuLi57IHNlbGVjdGVkOiBwbGFjZURhdGEudmFsdWUgfSB9KTtcbiAgICAgICAgY29uc3QgY2FjaGVLZXkgPSBnZXRDYWNoZUtleShwbGFjZURhdGEpO1xuICAgICAgICBpZiAoaGFzKGNhY2hlLCBjYWNoZUtleSkpIHtcbiAgICAgICAgICAgIHNldExvY2F0aW9uU3RhdGUocGxhY2VEYXRhLnBsYWNlSWQsIHBsYWNlRGF0YS52YWx1ZSk7XG4gICAgICAgICAgICByZXR1cm4gc2V0SG90ZWxzTGlzdChjYWNoZVtjYWNoZUtleV0pO1xuICAgICAgICB9XG5cbiAgICAgICAgc2V0TG9hZGluZyh0cnVlKTtcbiAgICAgICAgY29uc3QgcmVzcG9uc2UgPSAoYXdhaXQgQVBJLmdldChSb3V0ZXIuZ2VuZXJhdGUoJ2F3X2hvdGVscmV3YXJkX3BsYWNlJywgeyBwbGFjZTogcGxhY2VEYXRhIH0pKSkuZGF0YTtcbiAgICAgICAgaWYgKGhhcyhyZXNwb25zZSwgJ3BsYWNlSWQnKSkge1xuICAgICAgICAgICAgY2FjaGVbcmVzcG9uc2UucGxhY2VJZF0gPSByZXNwb25zZS5ob3RlbHM7XG4gICAgICAgICAgICBzZXRIb3RlbHNMaXN0KGNhY2hlW3Jlc3BvbnNlLnBsYWNlSWRdKTtcbiAgICAgICAgICAgIHNldExvY2F0aW9uU3RhdGUocmVzcG9uc2UucGxhY2VJZCwgcGxhY2VEYXRhLnZhbHVlKTtcbiAgICAgICAgfSBlbHNlIHtcbiAgICAgICAgICAgIHNldEhvdGVsc0xpc3QocmVzcG9uc2UpO1xuICAgICAgICB9XG4gICAgICAgIHNldExvYWRpbmcoZmFsc2UpO1xuICAgIH07XG5cbiAgICBjb25zdCBpbml0aWFsRmlsdGVyT3B0aW9ucyA9IHsgJ2JyYW5kJzogJycgfTtcbiAgICBjb25zdCBbZmlsdGVyT3B0aW9ucywgc2V0RmlsdGVyT3B0aW9uc10gPSB1c2VSZWR1Y2VyKChwcmV2LCBzdGF0ZSkgPT4ge1xuICAgICAgICByZXR1cm4geyAuLi5wcmV2LCAuLi5zdGF0ZSB9XG4gICAgfSwgaW5pdGlhbEZpbHRlck9wdGlvbnMpO1xuXG4gICAgcmV0dXJuIChcbiAgICAgICAgPGRpdiBjbGFzc05hbWU9XCJtYWluLWJsayBob3RlbC1yZXdhcmQtcGFnZVwiPlxuICAgICAgICAgICAgPGgxPntUcmFuc2xhdG9yLnRyYW5zKCdhd2FyZC1ob3RlbC1yZXNlYXJjaC10b29sJyl9PC9oMT5cbiAgICAgICAgICAgIDxkaXYgY2xhc3NOYW1lPVwibWFpbi1ibGstY29udGVudFwiPlxuICAgICAgICAgICAgICAgIDxGb3JtQWRkcmVzcyBhZGRyZXNzPXthZGRyZXNzfSBzZXRBZGRyZXNzPXtzZXRBZGRyZXNzfSBmb3JtQWRkcmVzc1N1Ym1pdD17aGFuZGxlRm9ybUFkZHJlc3NTdWJtaXR9XG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgIHNldEhvdGVscz17c2V0SG90ZWxzTGlzdH1cbiAgICAgICAgICAgICAgICAvPlxuICAgICAgICAgICAgICAgIDxDb250ZW50RGF0YSBhZGRyZXNzPXthZGRyZXNzfVxuICAgICAgICAgICAgICAgICAgICAgICAgICAgICBwcmltYXJ5TGlzdD17cHJvcHMucHJpbWFyeUxpc3R9XG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgIGhvdGVsc0xpc3Q9e2hvdGVsc0xpc3R9XG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgIGZpbHRlck9wdGlvbnM9e2ZpbHRlck9wdGlvbnN9IHNldEZpbHRlck9wdGlvbnM9e3NldEZpbHRlck9wdGlvbnN9XG4gICAgICAgICAgICAgICAgLz5cbiAgICAgICAgICAgICAgICA8ZGl2IGNsYXNzTmFtZT17Y2xhc3NOYW1lcyh7ICdhamF4LWxvYWRlcic6IHRydWUsICdhamF4LWxvYWRlci1wcm9jZXNzJzogaXNMb2FkaW5nIH0pfT5cbiAgICAgICAgICAgICAgICAgICAgPGRpdiBjbGFzc05hbWU9XCJsb2FkaW5nXCI+PC9kaXY+XG4gICAgICAgICAgICAgICAgPC9kaXY+XG4gICAgICAgICAgICAgICAgPE1pbGVWYWx1ZUJveCBwcm92aWRlcnM9e3Byb3BzLnByaW1hcnlMaXN0fS8+XG4gICAgICAgICAgICA8L2Rpdj5cbiAgICAgICAgPC9kaXY+XG4gICAgKTtcbn1cblxuZnVuY3Rpb24gaXNFbXB0eUFkZHJlc3Moc3RhdGUpIHtcbiAgICByZXR1cm4gJycgPT09IHN0YXRlIHx8IChoYXMoc3RhdGUsICdwbGFjZV9pZCcpICYmIGlzRW1wdHkoc3RhdGUucGxhY2VfaWQpICYmIGlzRW1wdHkoc3RhdGUudmFsdWUpKTtcbn1cblxuZnVuY3Rpb24gZ2V0Q2FjaGVLZXkoZGF0YSkge1xuICAgIGNvbnN0IGZyYWdtZW50TmFtZSA9IGhhcyhkYXRhLCAnZnJhZ21lbnROYW1lJykgPyBkYXRhLmZyYWdtZW50TmFtZSA6ICcnO1xuICAgIGlmIChoYXMoZGF0YSwgJ3BsYWNlX2lkJykgJiYgIWlzRW1wdHkoZGF0YS5wbGFjZV9pZCkpIHtcbiAgICAgICAgcmV0dXJuIGRhdGEucGxhY2VfaWQgKyBmcmFnbWVudE5hbWU7XG4gICAgfVxuICAgIGlmICghaXNFbXB0eShkYXRhLnZhbHVlKSkge1xuICAgICAgICByZXR1cm4gJ18nICsgZGF0YS52YWx1ZSArIGZyYWdtZW50TmFtZTtcbiAgICB9XG5cbiAgICByZXR1cm4gMDtcbn1cblxuZnVuY3Rpb24gc2V0TG9jYXRpb25TdGF0ZShwbGFjZUlkLCBwbGFjZU5hbWUpIHtcbiAgICBpZiAobnVsbCA9PT0gcGxhY2VJZCkge1xuICAgICAgICByZXR1cm4gd2luZG93Lmhpc3RvcnkucHVzaFN0YXRlKHt9LCAnJywgUm91dGVyLmdlbmVyYXRlKCdhd19ob3RlbHJld2FyZF9pbmRleCcpKTtcbiAgICB9XG5cbiAgICByZXR1cm4gd2luZG93Lmhpc3RvcnkucHVzaFN0YXRlKHtcbiAgICAgICAgcGxhY2U6IHtcbiAgICAgICAgICAgIHBsYWNlX2lkOiBwbGFjZUlkLFxuICAgICAgICAgICAgbGFiZWw6IHBsYWNlTmFtZSxcbiAgICAgICAgICAgIHZhbHVlOiBwbGFjZU5hbWUsXG4gICAgICAgICAgICBzZWxlY3RlZDogcGxhY2VOYW1lXG4gICAgICAgIH1cbiAgICB9LCAnJywgUm91dGVyLmdlbmVyYXRlKCdhd19ob3RlbHJld2FyZF9pbmRleF9wbGFjZScsIHtcbiAgICAgICAgcGxhY2VOYW1lOiBlbmNvZGVVUklDb21wb25lbnQocGxhY2VOYW1lKVxuICAgICAgICAgICAgLnJlcGxhY2UoLyUyMC9nLCAnKycpXG4gICAgICAgICAgICAucmVwbGFjZSgvJTJDL2csICcsJylcbiAgICB9KSk7XG59XG5cbmV4cG9ydCBkZWZhdWx0IEhvdGVsUmV3YXJkOyIsImltcG9ydCBSZWFjdCBmcm9tICdyZWFjdCc7XG5pbXBvcnQgUm91dGVyIGZyb20gJy4uLy4uLy4uL2JlbS90cy9zZXJ2aWNlL3JvdXRlcic7XG5pbXBvcnQgVHJhbnNsYXRvciBmcm9tICcuLi8uLi8uLi9iZW0vdHMvc2VydmljZS90cmFuc2xhdG9yJztcbmltcG9ydCBtYXAgZnJvbSAnbG9kYXNoL21hcCc7XG5cbmZ1bmN0aW9uIE1pbGVWYWx1ZUJveChwcm9wcykge1xuXG4gICAgcmV0dXJuIChcbiAgICAgICAgPGRpdiBzdHlsZT17e3BhZGRpbmcgOiAnMTVweCd9fT5cbiAgICAgICAgICAgIDxkaXYgaWQ9XCJtaWxlVmFsdWVCb3hcIiBjbGFzc05hbWU9XCJjaGFydF9fZmlsdGVyXCI+XG4gICAgICAgICAgICAgICAgPHAgZGFuZ2Vyb3VzbHlTZXRJbm5lckhUTUw9e3tcbiAgICAgICAgICAgICAgICAgICAgX19odG1sIDogVHJhbnNsYXRvci50cmFucygnd2UtY2FsY3VsYXRlLXBvaW50cy1ldmFsdWF0aW5nLWJvb2tpbmdzLXBvaW50cycsIHtcbiAgICAgICAgICAgICAgICAgICAgICAgICdsaW5rX29uJyA6IGA8YSBocmVmPSR7Um91dGVyLmdlbmVyYXRlKCdhd19wb2ludHNfbWlsZXNfdmFsdWVzJyl9PmAsXG4gICAgICAgICAgICAgICAgICAgICAgICAnbGlua19vZmYnIDogYDwvYT5gXG4gICAgICAgICAgICAgICAgICAgIH0pXG4gICAgICAgICAgICAgICAgfX0vPlxuICAgICAgICAgICAgICAgIDxkaXYgY2xhc3NOYW1lPVwiY2hhcnRfX2ZpbHRlcl9jb250YWluZXJcIj5cbiAgICAgICAgICAgICAgICAgICAgPGRpdiBjbGFzc05hbWU9XCJjaGFydF9fZmlsdGVyX3dyYXBcIj5cbiAgICAgICAgICAgICAgICAgICAgICAgIHttYXAoKHByb3BzLnByb3ZpZGVycyksIChwcm92aWRlcikgPT5cbiAgICAgICAgICAgICAgICAgICAgICAgICAgICA8ZGl2IGNsYXNzTmFtZT1cImNoYXJ0X19maWx0ZXJfYmxvY2tcIiBrZXk9e3Byb3ZpZGVyLnByb3ZpZGVySWR9PlxuICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICA8c3Bhbj57cHJvdmlkZXIuYnJhbmROYW1lfTwvc3Bhbj5cbiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgPGRpdiBjbGFzc05hbWU9XCJjdXJyLXZhbHVlXCI+XG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICA8c3Ryb25nPntwcm92aWRlci5mb3JtYXR0ZWRBdmdQb2ludFZhbHVlfTwvc3Ryb25nPlxuICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICA8L2Rpdj5cbiAgICAgICAgICAgICAgICAgICAgICAgICAgICA8L2Rpdj5cbiAgICAgICAgICAgICAgICAgICAgICAgICl9XG4gICAgICAgICAgICAgICAgICAgICAgICA8ZGl2IGNsYXNzTmFtZT1cInQtcmlnaHRcIj5cbiAgICAgICAgICAgICAgICAgICAgICAgIDwvZGl2PlxuICAgICAgICAgICAgICAgICAgICA8L2Rpdj5cbiAgICAgICAgICAgICAgICA8L2Rpdj5cbiAgICAgICAgICAgIDwvZGl2PlxuICAgICAgICA8L2Rpdj5cbiAgICApO1xufVxuXG5leHBvcnQgZGVmYXVsdCBNaWxlVmFsdWVCb3g7IiwiZXhwb3J0IGRlZmF1bHQgZnVuY3Rpb24gb25SZWFkeShjYWxsYmFjaykge1xuICAgIGlmIChkb2N1bWVudC5yZWFkeVN0YXRlID09PSAnbG9hZGluZycpIHtcbiAgICAgICAgLy8gVGhlIERPTSBpcyBub3QgeWV0IHJlYWR5LlxuICAgICAgICBkb2N1bWVudC5hZGRFdmVudExpc3RlbmVyKCdET01Db250ZW50TG9hZGVkJywgY2FsbGJhY2spO1xuICAgIH1cbiAgICBlbHNlIHtcbiAgICAgICAgLy8gVGhlIERPTSBpcyBhbHJlYWR5IHJlYWR5LlxuICAgICAgICBjYWxsYmFjaygpO1xuICAgIH1cbn1cbiIsImltcG9ydCB7IGV4dHJhY3RPcHRpb25zIH0gZnJvbSAnLi9zZXJ2aWNlL2Vudic7XG5pbXBvcnQgb25SZWFkeSBmcm9tICcuL3NlcnZpY2Uvb24tcmVhZHknO1xub25SZWFkeShmdW5jdGlvbiAoKSB7XG4gICAgY29uc3Qgb3B0cyA9IGV4dHJhY3RPcHRpb25zKCk7XG4gICAgaWYgKG9wdHMuZW5hYmxlZFRyYW5zSGVscGVyIHx8IG9wdHMuaGFzUm9sZVRyYW5zbGF0b3IpIHtcbiAgICAgICAgY29uc29sZS5sb2coJ2luaXQgdHJhbnNoZWxwZXInKTtcbiAgICAgICAgaW1wb3J0KC8qIHdlYnBhY2tQcmVsb2FkOiB0cnVlICovICcuL3NlcnZpY2UvdHJhbnNIZWxwZXInKVxuICAgICAgICAgICAgLnRoZW4oKHsgZGVmYXVsdDogaW5pdCB9KSA9PiB7IGluaXQoKTsgfSwgKCkgPT4geyBjb25zb2xlLmVycm9yKCd0cmFuc2hlbHBlciBmYWlsZWQgdG8gbG9hZCcpOyB9KTtcbiAgICB9XG59KTtcbiIsIi8vIGV4dHJhY3RlZCBieSBtaW5pLWNzcy1leHRyYWN0LXBsdWdpblxuZXhwb3J0IHt9OyIsIi8vIGV4dHJhY3RlZCBieSBtaW5pLWNzcy1leHRyYWN0LXBsdWdpblxuZXhwb3J0IHt9OyJdLCJuYW1lcyI6WyJyZW5kZXIiLCJIb3RlbFJld2FyZCIsIlJlYWN0IiwiY29udGVudEVsZW1lbnQiLCJkb2N1bWVudCIsImdldEVsZW1lbnRCeUlkIiwicHJpbWFyeUxpc3QiLCJKU09OIiwicGFyc2UiLCJkYXRhc2V0IiwiY3JlYXRlRWxlbWVudCIsIlN0cmljdE1vZGUiLCJqcXVlcnl1aSIsIm1haW4iLCJ0b2dnbGVTaWRlYmFyVmlzaWJsZSIsImluaXREcm9wZG93bnMiLCIkIiwid2luZG93IiwicmVzaXplIiwic2l6ZVdpbmRvdyIsIndpZHRoIiwiYWRkQ2xhc3MiLCJyZW1vdmVDbGFzcyIsImhhc0NsYXNzIiwibWVudUNsb3NlIiwicXVlcnlTZWxlY3RvciIsIm1lbnVCb2R5Iiwib25jbGljayIsImNsYXNzTGlzdCIsInRvZ2dsZSIsImFkZCIsImFyZWEiLCJvcHRpb25zIiwic2VsZWN0b3IiLCJkcm9wZG93biIsInVuZGVmaW5lZCIsImZpbmQiLCJhZGRCYWNrIiwib2ZQYXJlbnRTZWxlY3RvciIsIm9mUGFyZW50IiwiZWFjaCIsImlkIiwiZWwiLCJyZW1vdmVBdHRyIiwibWVudSIsImhpZGUiLCJvbiIsImUiLCJ0YXJnZXQiLCJkYXRhIiwicHJldmVudERlZmF1bHQiLCJzdG9wUHJvcGFnYXRpb24iLCJub3QiLCJ0cmlnZ2VyIiwiX29wdGlvbnMiLCJwb3NpdGlvbiIsIm15IiwiYXQiLCJvZiIsInBhcmVudHMiLCJjb2xsaXNpb24iLCJhdXRvQ29tcGxldGVSZW5kZXJJdGVtIiwicmVuZGVyRnVuY3Rpb24iLCJhcmd1bWVudHMiLCJsZW5ndGgiLCJ1bCIsIml0ZW0iLCJyZWdleCIsIlJlZ0V4cCIsImVsZW1lbnQiLCJ2YWwiLCJyZXBsYWNlIiwiaHRtbCIsInRleHQiLCJsYWJlbCIsImFwcGVuZCIsImFwcGVuZFRvIiwidWkiLCJhdXRvY29tcGxldGUiLCJwcm90b3R5cGUiLCJfcmVuZGVySXRlbSIsIlRvb2xUaXAiLCJjb250ZXh0IiwidG9vbHRpcCIsInRvb2x0aXBDbGFzcyIsIl9vYmplY3RTcHJlYWQiLCJ1c2luZyIsImZlZWRiYWNrIiwiY3NzIiwidmVydGljYWwiLCJtYXJnaW5MZWZ0IiwibGVmdCIsIm9mZiIsInByb3AiLCJfcmVnZW5lcmF0b3JSdW50aW1lIiwidCIsInIiLCJPYmplY3QiLCJuIiwiaGFzT3duUHJvcGVydHkiLCJvIiwiZGVmaW5lUHJvcGVydHkiLCJ2YWx1ZSIsImkiLCJTeW1ib2wiLCJhIiwiaXRlcmF0b3IiLCJjIiwiYXN5bmNJdGVyYXRvciIsInUiLCJ0b1N0cmluZ1RhZyIsImRlZmluZSIsImVudW1lcmFibGUiLCJjb25maWd1cmFibGUiLCJ3cml0YWJsZSIsIndyYXAiLCJHZW5lcmF0b3IiLCJjcmVhdGUiLCJDb250ZXh0IiwibWFrZUludm9rZU1ldGhvZCIsInRyeUNhdGNoIiwidHlwZSIsImFyZyIsImNhbGwiLCJoIiwibCIsImYiLCJzIiwieSIsIkdlbmVyYXRvckZ1bmN0aW9uIiwiR2VuZXJhdG9yRnVuY3Rpb25Qcm90b3R5cGUiLCJwIiwiZCIsImdldFByb3RvdHlwZU9mIiwidiIsInZhbHVlcyIsImciLCJkZWZpbmVJdGVyYXRvck1ldGhvZHMiLCJmb3JFYWNoIiwiX2ludm9rZSIsIkFzeW5jSXRlcmF0b3IiLCJpbnZva2UiLCJfdHlwZW9mIiwicmVzb2x2ZSIsIl9fYXdhaXQiLCJ0aGVuIiwiY2FsbEludm9rZVdpdGhNZXRob2RBbmRBcmciLCJFcnJvciIsImRvbmUiLCJtZXRob2QiLCJkZWxlZ2F0ZSIsIm1heWJlSW52b2tlRGVsZWdhdGUiLCJzZW50IiwiX3NlbnQiLCJkaXNwYXRjaEV4Y2VwdGlvbiIsImFicnVwdCIsInJldHVybiIsIlR5cGVFcnJvciIsInJlc3VsdE5hbWUiLCJuZXh0IiwibmV4dExvYyIsInB1c2hUcnlFbnRyeSIsInRyeUxvYyIsImNhdGNoTG9jIiwiZmluYWxseUxvYyIsImFmdGVyTG9jIiwidHJ5RW50cmllcyIsInB1c2giLCJyZXNldFRyeUVudHJ5IiwiY29tcGxldGlvbiIsInJlc2V0IiwiaXNOYU4iLCJkaXNwbGF5TmFtZSIsImlzR2VuZXJhdG9yRnVuY3Rpb24iLCJjb25zdHJ1Y3RvciIsIm5hbWUiLCJtYXJrIiwic2V0UHJvdG90eXBlT2YiLCJfX3Byb3RvX18iLCJhd3JhcCIsImFzeW5jIiwiUHJvbWlzZSIsImtleXMiLCJyZXZlcnNlIiwicG9wIiwicHJldiIsImNoYXJBdCIsInNsaWNlIiwic3RvcCIsInJ2YWwiLCJoYW5kbGUiLCJjb21wbGV0ZSIsImZpbmlzaCIsImNhdGNoIiwiX2NhdGNoIiwiZGVsZWdhdGVZaWVsZCIsImFzeW5jR2VuZXJhdG9yU3RlcCIsImdlbiIsInJlamVjdCIsIl9uZXh0IiwiX3Rocm93Iiwia2V5IiwiaW5mbyIsImVycm9yIiwiX2FzeW5jVG9HZW5lcmF0b3IiLCJmbiIsInNlbGYiLCJhcmdzIiwiYXBwbHkiLCJlcnIiLCJvd25LZXlzIiwiZ2V0T3duUHJvcGVydHlTeW1ib2xzIiwiZmlsdGVyIiwiZ2V0T3duUHJvcGVydHlEZXNjcmlwdG9yIiwiX2RlZmluZVByb3BlcnR5IiwiZ2V0T3duUHJvcGVydHlEZXNjcmlwdG9ycyIsImRlZmluZVByb3BlcnRpZXMiLCJvYmoiLCJfdG9Qcm9wZXJ0eUtleSIsIl90b1ByaW1pdGl2ZSIsIlN0cmluZyIsImlucHV0IiwiaGludCIsInByaW0iLCJ0b1ByaW1pdGl2ZSIsInJlcyIsIk51bWJlciIsIl9zbGljZWRUb0FycmF5IiwiYXJyIiwiX2FycmF5V2l0aEhvbGVzIiwiX2l0ZXJhYmxlVG9BcnJheUxpbWl0IiwiX3Vuc3VwcG9ydGVkSXRlcmFibGVUb0FycmF5IiwiX25vbkl0ZXJhYmxlUmVzdCIsIm1pbkxlbiIsIl9hcnJheUxpa2VUb0FycmF5IiwidG9TdHJpbmciLCJBcnJheSIsImZyb20iLCJ0ZXN0IiwibGVuIiwiYXJyMiIsImlzQXJyYXkiLCJBUEkiLCJ1c2VFZmZlY3QiLCJ1c2VSZWR1Y2VyIiwidXNlU3RhdGUiLCJSb3V0ZXIiLCJUcmFuc2xhdG9yIiwiY2xhc3NOYW1lcyIsImhhcyIsImlzRW1wdHkiLCJtYXAiLCJtYXBWYWx1ZXMiLCJ1bmlxQnkiLCJleHRyYWN0T3B0aW9ucyIsIk1pbGVWYWx1ZUJveCIsImxvY2FsZSIsImNhY2hlIiwibnVtYmVyRm9ybWF0IiwiSW50bCIsIk51bWJlckZvcm1hdCIsImN1cnJlbmN5Rm9ybWF0Iiwic3R5bGUiLCJjdXJyZW5jeSIsIkhvdGVsQnJhbmQiLCJwcm9wcyIsInByb3ZpZGVycyIsImNsYXNzTmFtZSIsInByb3ZpZGVyIiwicHJvdmlkZXJJZCIsImJyYW5kTmFtZSIsInRyYW5zIiwiZm9ybWF0dGVkQXZnUG9pbnRWYWx1ZSIsImhvdGVscyIsImhvdGVsIiwiaG90ZWxJZCIsImF2Z0Fib3ZlVmFsdWUiLCJsb2NhdGlvbiIsInRpdGxlIiwibWF0Y2hDb3VudCIsImZvcm1hdCIsInBvaW50VmFsdWUiLCJjYXNoUHJpY2UiLCJwb2ludFByaWNlIiwibGluayIsImhyZWYiLCJyZWwiLCJGb3JtQWRkcmVzcyIsImRlbGF5IiwibWluTGVuZ3RoIiwic2VhcmNoIiwiZXZlbnQiLCJvcGVuIiwic291cmNlIiwicmVxdWVzdCIsInJlc3BvbnNlIiwiY2xvc2VzdCIsImZyYWdtZW50TmFtZVBvcyIsInRlcm0iLCJpbmRleE9mIiwiaG90ZWxOYW1lRnJhZ21lbnQiLCJzdWJzdHIiLCJnZXQiLCJnZW5lcmF0ZSIsInF1ZXJ5IiwiZW5jb2RlVVJJQ29tcG9uZW50IiwicmVzdWx0IiwicGxhY2VfaWQiLCJmb3JtYXR0ZWRfYWRkcmVzcyIsImV4dGVuZCIsImZyYWdtZW50TmFtZSIsImZhaWwiLCJzZWxlY3QiLCJzZXRBZGRyZXNzIiwiZm9ybUFkZHJlc3NTdWJtaXQiLCJpdGVtTGFiZWwiLCJvblN1Ym1pdCIsImlzRW1wdHlBZGRyZXNzIiwiYWRkcmVzcyIsInBsYWNlaG9sZGVyIiwiYXV0b0NvbXBsZXRlIiwib25DaGFuZ2UiLCJvbkNsaWNrIiwiU2VhcmNoUmVzdWx0IiwiX2ZpbHRlcmVkSG90ZWxzJCIsImhvdGVsc0xpc3QiLCJicmFuZHMiLCJicmFuZHNNYXhMZW5ndGhOYW1lIiwiZmlsdGVyZWRIb3RlbHMiLCJmaWx0ZXJPcHRpb25zIiwiYnJhbmQiLCJyb3dJbmRleCIsImlzRmlyc3ROZXV0cmFsIiwiaXNGaXJzdE5lZ2F0aXZlIiwic2VsZWN0ZWQiLCJzZXRGaWx0ZXJPcHRpb25zIiwiY3NzQ2xhc3NlcyIsIkNvbnRlbnREYXRhIiwiX3VzZVN0YXRlIiwiX3VzZVN0YXRlMiIsImlzTG9hZGluZyIsInNldExvYWRpbmciLCJfdXNlU3RhdGUzIiwiX3VzZVN0YXRlNCIsInNldEhvdGVsc0xpc3QiLCJpbml0aWFsQWRkcmVzcyIsImhhbmRsZXJTZWFyY2hBZGRyZXNzU3RhdGUiLCJzdGF0ZSIsInNldExvY2F0aW9uU3RhdGUiLCJfdXNlUmVkdWNlciIsIl91c2VSZWR1Y2VyMiIsInJvdXRlIiwiX2RlY29kZVVSSSRyZXBsYWNlJHJlIiwiZGVjb2RlVVJJIiwiZGVjb2RlVVJJQ29tcG9uZW50IiwicGF0aG5hbWUiLCJzcGxpdCIsIl9kZWNvZGVVUkkkcmVwbGFjZSRyZTIiLCJwbGFjZUlkIiwicGxhY2VOYW1lIiwiZmV0Y2hBZGRyZXNzIiwiaGFuZGxlRm9ybUFkZHJlc3NTdWJtaXQiLCJvbnBvcHN0YXRlIiwicGxhY2UiLCJfcmVmIiwiX2NhbGxlZSIsImFkZHIiLCJwbGFjZURhdGEiLCJjYWNoZUtleSIsIl9jYWxsZWUkIiwiX2NvbnRleHQiLCJnZXRDYWNoZUtleSIsIl94IiwiX3gyIiwiaW5pdGlhbEZpbHRlck9wdGlvbnMiLCJfdXNlUmVkdWNlcjMiLCJfdXNlUmVkdWNlcjQiLCJzZXRIb3RlbHMiLCJoaXN0b3J5IiwicHVzaFN0YXRlIiwicGFkZGluZyIsImRhbmdlcm91c2x5U2V0SW5uZXJIVE1MIiwiX19odG1sIiwiY29uY2F0Iiwib25SZWFkeSIsImNhbGxiYWNrIiwicmVhZHlTdGF0ZSIsImFkZEV2ZW50TGlzdGVuZXIiLCJvcHRzIiwiZW5hYmxlZFRyYW5zSGVscGVyIiwiaGFzUm9sZVRyYW5zbGF0b3IiLCJjb25zb2xlIiwibG9nIiwiaW5pdCIsImRlZmF1bHQiXSwic291cmNlUm9vdCI6IiJ9