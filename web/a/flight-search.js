(self["webpackChunkAwardWallet"] = self["webpackChunkAwardWallet"] || []).push([["flight-search"],{

/***/ "./assets/entry-point-deprecated/main.js":
/*!***********************************************!*\
  !*** ./assets/entry-point-deprecated/main.js ***!
  \***********************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
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

/***/ "./assets/js-deprecated/component-deprecated/FlightSearch/FlightSearch.js":
/*!********************************************************************************!*\
  !*** ./assets/js-deprecated/component-deprecated/FlightSearch/FlightSearch.js ***!
  \********************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var core_js_modules_es_object_keys_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! core-js/modules/es.object.keys.js */ "./node_modules/core-js/modules/es.object.keys.js");
/* harmony import */ var core_js_modules_es_object_keys_js__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_object_keys_js__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var core_js_modules_es_object_values_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! core-js/modules/es.object.values.js */ "./node_modules/core-js/modules/es.object.values.js");
/* harmony import */ var core_js_modules_es_object_values_js__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_object_values_js__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! react */ "./node_modules/react/index.js");
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(react__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var _bem_ts_service_translator__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ../../../bem/ts/service/translator */ "./assets/bem/ts/service/translator.ts");
/* harmony import */ var _Form__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! ./Form */ "./assets/js-deprecated/component-deprecated/FlightSearch/Form.js");
/* harmony import */ var _GroupList__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! ./GroupList */ "./assets/js-deprecated/component-deprecated/FlightSearch/GroupList.js");






function FlightSearch(props) {
  var _props$data$form;
  var isExpandRoutesExists = 0 !== Object.keys(props.data.expandRoutes).length;
  var groups = Object.values(props.data.primaryList);
  var skyLink = 'https://skyscanner.pxf.io/c/327835/1027991/13416?associateid=AFF_TRA_19354_00001';
  return /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_2___default().createElement("div", {
    className: 'main-blk flight-search'
  }, /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_2___default().createElement("div", {
    className: 'flight-search-skyscanner'
  }, /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_2___default().createElement("a", {
    href: skyLink,
    target: "_blank",
    rel: "noreferrer"
  }, /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_2___default().createElement("img", {
    src: "/assets/awardwalletnewdesign/img/logo/skycanner-stacked--blue.png",
    alt: ""
  }))), /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_2___default().createElement("h1", null, _bem_ts_service_translator__WEBPACK_IMPORTED_MODULE_3__["default"].trans('award-flight-research-tool')), /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_2___default().createElement("div", {
    className: "main-blk-content"
  }, /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_2___default().createElement(_Form__WEBPACK_IMPORTED_MODULE_4__["default"], {
    form: props.data.form
  }), isExpandRoutesExists ? /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_2___default().createElement(SearchResult, {
    expandRoutes: props.data.expandRoutes
  }) : null, /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_2___default().createElement(_GroupList__WEBPACK_IMPORTED_MODULE_5__["default"], {
    groups: groups,
    isFormFilled: undefined !== ((_props$data$form = props.data.form) === null || _props$data$form === void 0 ? void 0 : _props$data$form.from),
    skyLink: skyLink
  })));
}
var SearchResult = /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_2___default().memo(function SearchResult(props) {
  var expandRoutes = props.expandRoutes;
  return /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_2___default().createElement("div", {
    className: "flight-search-expand"
  }, /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_2___default().createElement("div", {
    className: "flight-search-form"
  }, /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_2___default().createElement("div", {
    className: "flightsearch-form-place flightsearch-form-from"
  }, '' !== expandRoutes.linkFrom && undefined !== expandRoutes.linkFrom ? /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_2___default().createElement("a", {
    className: 'btn-blue',
    href: expandRoutes.linkFrom
  }, _bem_ts_service_translator__WEBPACK_IMPORTED_MODULE_3__["default"].trans('expand-to', {
    name: expandRoutes.from.dep.value
  }), /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_2___default().createElement("s", null, typePlaceSign(expandRoutes.from.dep.type))) : null), /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_2___default().createElement("div", {
    className: "flightsearch-form-gap"
  }), /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_2___default().createElement("div", {
    className: "flightsearch-form-place flightseach-form-to"
  }, '' !== expandRoutes.linkTo && undefined !== expandRoutes.linkTo ? /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_2___default().createElement("a", {
    className: 'btn-blue',
    href: expandRoutes.linkTo
  }, _bem_ts_service_translator__WEBPACK_IMPORTED_MODULE_3__["default"].trans('expand-to', {
    name: expandRoutes.to.arr.value
  }), /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_2___default().createElement("s", null, typePlaceSign(expandRoutes.to.arr.type))) : null), /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_2___default().createElement("div", {
    className: "flightsearch-form-menu flightsearch-form-trip"
  }), /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_2___default().createElement("div", {
    className: "flightsearch-form-menu flightsearch-form-class"
  }), /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_2___default().createElement("div", {
    className: "flightsearch-form-submit"
  })));
});
function typePlaceSign(type) {
  if (2 === type) {
    return " (".concat(_bem_ts_service_translator__WEBPACK_IMPORTED_MODULE_3__["default"].trans('city'), ")");
  } else if (3 === type) {
    return " (".concat(_bem_ts_service_translator__WEBPACK_IMPORTED_MODULE_3__["default"].trans('cart.state'), ")");
  } else if (4 === type) {
    return " (".concat(_bem_ts_service_translator__WEBPACK_IMPORTED_MODULE_3__["default"].trans('cart.country'), ")");
  }
  return '';
}
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (FlightSearch);

/***/ }),

/***/ "./assets/js-deprecated/component-deprecated/FlightSearch/Form.js":
/*!************************************************************************!*\
  !*** ./assets/js-deprecated/component-deprecated/FlightSearch/Form.js ***!
  \************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var core_js_modules_es_function_name_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! core-js/modules/es.function.name.js */ "./node_modules/core-js/modules/es.function.name.js");
/* harmony import */ var core_js_modules_es_function_name_js__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_function_name_js__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var core_js_modules_es_string_trim_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! core-js/modules/es.string.trim.js */ "./node_modules/core-js/modules/es.string.trim.js");
/* harmony import */ var core_js_modules_es_string_trim_js__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_string_trim_js__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var core_js_modules_es_array_map_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! core-js/modules/es.array.map.js */ "./node_modules/core-js/modules/es.array.map.js");
/* harmony import */ var core_js_modules_es_array_map_js__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_array_map_js__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var core_js_modules_es_regexp_constructor_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! core-js/modules/es.regexp.constructor.js */ "./node_modules/core-js/modules/es.regexp.constructor.js");
/* harmony import */ var core_js_modules_es_regexp_constructor_js__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_regexp_constructor_js__WEBPACK_IMPORTED_MODULE_3__);
/* harmony import */ var core_js_modules_es_regexp_exec_js__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! core-js/modules/es.regexp.exec.js */ "./node_modules/core-js/modules/es.regexp.exec.js");
/* harmony import */ var core_js_modules_es_regexp_exec_js__WEBPACK_IMPORTED_MODULE_4___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_regexp_exec_js__WEBPACK_IMPORTED_MODULE_4__);
/* harmony import */ var core_js_modules_es_regexp_to_string_js__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! core-js/modules/es.regexp.to-string.js */ "./node_modules/core-js/modules/es.regexp.to-string.js");
/* harmony import */ var core_js_modules_es_regexp_to_string_js__WEBPACK_IMPORTED_MODULE_5___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_regexp_to_string_js__WEBPACK_IMPORTED_MODULE_5__);
/* harmony import */ var core_js_modules_es_string_replace_js__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! core-js/modules/es.string.replace.js */ "./node_modules/core-js/modules/es.string.replace.js");
/* harmony import */ var core_js_modules_es_string_replace_js__WEBPACK_IMPORTED_MODULE_6___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_string_replace_js__WEBPACK_IMPORTED_MODULE_6__);
/* harmony import */ var core_js_modules_es_array_concat_js__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__(/*! core-js/modules/es.array.concat.js */ "./node_modules/core-js/modules/es.array.concat.js");
/* harmony import */ var core_js_modules_es_array_concat_js__WEBPACK_IMPORTED_MODULE_7___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_array_concat_js__WEBPACK_IMPORTED_MODULE_7__);
/* harmony import */ var core_js_modules_es_object_entries_js__WEBPACK_IMPORTED_MODULE_8__ = __webpack_require__(/*! core-js/modules/es.object.entries.js */ "./node_modules/core-js/modules/es.object.entries.js");
/* harmony import */ var core_js_modules_es_object_entries_js__WEBPACK_IMPORTED_MODULE_8___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_object_entries_js__WEBPACK_IMPORTED_MODULE_8__);
/* harmony import */ var core_js_modules_es_symbol_js__WEBPACK_IMPORTED_MODULE_9__ = __webpack_require__(/*! core-js/modules/es.symbol.js */ "./node_modules/core-js/modules/es.symbol.js");
/* harmony import */ var core_js_modules_es_symbol_js__WEBPACK_IMPORTED_MODULE_9___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_symbol_js__WEBPACK_IMPORTED_MODULE_9__);
/* harmony import */ var core_js_modules_es_symbol_description_js__WEBPACK_IMPORTED_MODULE_10__ = __webpack_require__(/*! core-js/modules/es.symbol.description.js */ "./node_modules/core-js/modules/es.symbol.description.js");
/* harmony import */ var core_js_modules_es_symbol_description_js__WEBPACK_IMPORTED_MODULE_10___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_symbol_description_js__WEBPACK_IMPORTED_MODULE_10__);
/* harmony import */ var core_js_modules_es_object_to_string_js__WEBPACK_IMPORTED_MODULE_11__ = __webpack_require__(/*! core-js/modules/es.object.to-string.js */ "./node_modules/core-js/modules/es.object.to-string.js");
/* harmony import */ var core_js_modules_es_object_to_string_js__WEBPACK_IMPORTED_MODULE_11___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_object_to_string_js__WEBPACK_IMPORTED_MODULE_11__);
/* harmony import */ var core_js_modules_es_symbol_iterator_js__WEBPACK_IMPORTED_MODULE_12__ = __webpack_require__(/*! core-js/modules/es.symbol.iterator.js */ "./node_modules/core-js/modules/es.symbol.iterator.js");
/* harmony import */ var core_js_modules_es_symbol_iterator_js__WEBPACK_IMPORTED_MODULE_12___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_symbol_iterator_js__WEBPACK_IMPORTED_MODULE_12__);
/* harmony import */ var core_js_modules_es_array_iterator_js__WEBPACK_IMPORTED_MODULE_13__ = __webpack_require__(/*! core-js/modules/es.array.iterator.js */ "./node_modules/core-js/modules/es.array.iterator.js");
/* harmony import */ var core_js_modules_es_array_iterator_js__WEBPACK_IMPORTED_MODULE_13___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_array_iterator_js__WEBPACK_IMPORTED_MODULE_13__);
/* harmony import */ var core_js_modules_es_string_iterator_js__WEBPACK_IMPORTED_MODULE_14__ = __webpack_require__(/*! core-js/modules/es.string.iterator.js */ "./node_modules/core-js/modules/es.string.iterator.js");
/* harmony import */ var core_js_modules_es_string_iterator_js__WEBPACK_IMPORTED_MODULE_14___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_string_iterator_js__WEBPACK_IMPORTED_MODULE_14__);
/* harmony import */ var core_js_modules_web_dom_collections_iterator_js__WEBPACK_IMPORTED_MODULE_15__ = __webpack_require__(/*! core-js/modules/web.dom-collections.iterator.js */ "./node_modules/core-js/modules/web.dom-collections.iterator.js");
/* harmony import */ var core_js_modules_web_dom_collections_iterator_js__WEBPACK_IMPORTED_MODULE_15___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_web_dom_collections_iterator_js__WEBPACK_IMPORTED_MODULE_15__);
/* harmony import */ var core_js_modules_es_array_slice_js__WEBPACK_IMPORTED_MODULE_16__ = __webpack_require__(/*! core-js/modules/es.array.slice.js */ "./node_modules/core-js/modules/es.array.slice.js");
/* harmony import */ var core_js_modules_es_array_slice_js__WEBPACK_IMPORTED_MODULE_16___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_array_slice_js__WEBPACK_IMPORTED_MODULE_16__);
/* harmony import */ var core_js_modules_es_array_from_js__WEBPACK_IMPORTED_MODULE_17__ = __webpack_require__(/*! core-js/modules/es.array.from.js */ "./node_modules/core-js/modules/es.array.from.js");
/* harmony import */ var core_js_modules_es_array_from_js__WEBPACK_IMPORTED_MODULE_17___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_array_from_js__WEBPACK_IMPORTED_MODULE_17__);
/* harmony import */ var core_js_modules_es_symbol_to_primitive_js__WEBPACK_IMPORTED_MODULE_18__ = __webpack_require__(/*! core-js/modules/es.symbol.to-primitive.js */ "./node_modules/core-js/modules/es.symbol.to-primitive.js");
/* harmony import */ var core_js_modules_es_symbol_to_primitive_js__WEBPACK_IMPORTED_MODULE_18___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_symbol_to_primitive_js__WEBPACK_IMPORTED_MODULE_18__);
/* harmony import */ var core_js_modules_es_date_to_primitive_js__WEBPACK_IMPORTED_MODULE_19__ = __webpack_require__(/*! core-js/modules/es.date.to-primitive.js */ "./node_modules/core-js/modules/es.date.to-primitive.js");
/* harmony import */ var core_js_modules_es_date_to_primitive_js__WEBPACK_IMPORTED_MODULE_19___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_date_to_primitive_js__WEBPACK_IMPORTED_MODULE_19__);
/* harmony import */ var core_js_modules_es_number_constructor_js__WEBPACK_IMPORTED_MODULE_20__ = __webpack_require__(/*! core-js/modules/es.number.constructor.js */ "./node_modules/core-js/modules/es.number.constructor.js");
/* harmony import */ var core_js_modules_es_number_constructor_js__WEBPACK_IMPORTED_MODULE_20___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_number_constructor_js__WEBPACK_IMPORTED_MODULE_20__);
/* harmony import */ var core_js_modules_es_object_keys_js__WEBPACK_IMPORTED_MODULE_21__ = __webpack_require__(/*! core-js/modules/es.object.keys.js */ "./node_modules/core-js/modules/es.object.keys.js");
/* harmony import */ var core_js_modules_es_object_keys_js__WEBPACK_IMPORTED_MODULE_21___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_object_keys_js__WEBPACK_IMPORTED_MODULE_21__);
/* harmony import */ var core_js_modules_es_array_filter_js__WEBPACK_IMPORTED_MODULE_22__ = __webpack_require__(/*! core-js/modules/es.array.filter.js */ "./node_modules/core-js/modules/es.array.filter.js");
/* harmony import */ var core_js_modules_es_array_filter_js__WEBPACK_IMPORTED_MODULE_22___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_array_filter_js__WEBPACK_IMPORTED_MODULE_22__);
/* harmony import */ var core_js_modules_es_object_get_own_property_descriptor_js__WEBPACK_IMPORTED_MODULE_23__ = __webpack_require__(/*! core-js/modules/es.object.get-own-property-descriptor.js */ "./node_modules/core-js/modules/es.object.get-own-property-descriptor.js");
/* harmony import */ var core_js_modules_es_object_get_own_property_descriptor_js__WEBPACK_IMPORTED_MODULE_23___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_object_get_own_property_descriptor_js__WEBPACK_IMPORTED_MODULE_23__);
/* harmony import */ var core_js_modules_web_dom_collections_for_each_js__WEBPACK_IMPORTED_MODULE_24__ = __webpack_require__(/*! core-js/modules/web.dom-collections.for-each.js */ "./node_modules/core-js/modules/web.dom-collections.for-each.js");
/* harmony import */ var core_js_modules_web_dom_collections_for_each_js__WEBPACK_IMPORTED_MODULE_24___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_web_dom_collections_for_each_js__WEBPACK_IMPORTED_MODULE_24__);
/* harmony import */ var core_js_modules_es_object_get_own_property_descriptors_js__WEBPACK_IMPORTED_MODULE_25__ = __webpack_require__(/*! core-js/modules/es.object.get-own-property-descriptors.js */ "./node_modules/core-js/modules/es.object.get-own-property-descriptors.js");
/* harmony import */ var core_js_modules_es_object_get_own_property_descriptors_js__WEBPACK_IMPORTED_MODULE_25___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_object_get_own_property_descriptors_js__WEBPACK_IMPORTED_MODULE_25__);
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_26__ = __webpack_require__(/*! react */ "./node_modules/react/index.js");
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_26___default = /*#__PURE__*/__webpack_require__.n(react__WEBPACK_IMPORTED_MODULE_26__);
/* harmony import */ var _bem_ts_service_router__WEBPACK_IMPORTED_MODULE_27__ = __webpack_require__(/*! ../../../bem/ts/service/router */ "./assets/bem/ts/service/router.ts");
/* harmony import */ var _bem_ts_service_translator__WEBPACK_IMPORTED_MODULE_28__ = __webpack_require__(/*! ../../../bem/ts/service/translator */ "./assets/bem/ts/service/translator.ts");
/* harmony import */ var _entry_point_deprecated_main__WEBPACK_IMPORTED_MODULE_29__ = __webpack_require__(/*! ../../../entry-point-deprecated/main */ "./assets/entry-point-deprecated/main.js");
/* provided dependency */ var $ = __webpack_require__(/*! jquery */ "./web/assets/common/vendors/jquery/dist/jquery.js");
function _typeof(o) { "@babel/helpers - typeof"; return _typeof = "function" == typeof Symbol && "symbol" == typeof Symbol.iterator ? function (o) { return typeof o; } : function (o) { return o && "function" == typeof Symbol && o.constructor === Symbol && o !== Symbol.prototype ? "symbol" : typeof o; }, _typeof(o); }


























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




var Form = /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_26___default().memo(function Form(props) {
  var form = props.form;
  var blankData = {
    id: '',
    type: '',
    value: '',
    name: '',
    query: ''
  };
  var _useState = (0,react__WEBPACK_IMPORTED_MODULE_26__.useState)((form === null || form === void 0 ? void 0 : form.from) || blankData),
    _useState2 = _slicedToArray(_useState, 2),
    from = _useState2[0],
    setFrom = _useState2[1];
  var _useState3 = (0,react__WEBPACK_IMPORTED_MODULE_26__.useState)((form === null || form === void 0 ? void 0 : form.to) || blankData),
    _useState4 = _slicedToArray(_useState3, 2),
    to = _useState4[0],
    setTo = _useState4[1];
  var _useState5 = (0,react__WEBPACK_IMPORTED_MODULE_26__.useState)((form === null || form === void 0 ? void 0 : form.type) || blankData),
    _useState6 = _slicedToArray(_useState5, 2),
    type = _useState6[0],
    setType = _useState6[1];
  var _useState7 = (0,react__WEBPACK_IMPORTED_MODULE_26__.useState)((form === null || form === void 0 ? void 0 : form.class) || blankData),
    _useState8 = _slicedToArray(_useState7, 2),
    classes = _useState8[0],
    setClass = _useState8[1];
  var handleFormSubmit = (0,react__WEBPACK_IMPORTED_MODULE_26__.useMemo)(function () {
    return function (event) {
      '' === from.id ? setFrom(_objectSpread(_objectSpread({}, from), {
        id: from.value
      })) : null;
      '' === to.id ? setTo(_objectSpread(_objectSpread({}, to), {
        id: to.value
      })) : null;
    };
  });
  (0,react__WEBPACK_IMPORTED_MODULE_26__.useEffect)(function () {
    _entry_point_deprecated_main__WEBPACK_IMPORTED_MODULE_29__["default"].initDropdowns('#flightSearch', {
      ofParent: 'div.flightsearch-form-menu',
      position: {
        my: 'left-24 top+16'
      }
    });
  });
  return /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_26___default().createElement("form", {
    id: "flightSearchForm",
    method: "get",
    onSubmit: handleFormSubmit
  }, /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_26___default().createElement("div", {
    id: "flightSearch",
    className: "flight-search-form"
  }, /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_26___default().createElement("div", {
    className: "flightsearch-form-place flightseach-form-from",
    type: from.type
  }, /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_26___default().createElement(AutoComplete, {
    getValues: from,
    setValues: setFrom,
    placeholder: _bem_ts_service_translator__WEBPACK_IMPORTED_MODULE_28__["default"].trans('from')
  }), /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_26___default().createElement("input", {
    name: "from",
    type: "hidden",
    value: from.query || from.value,
    "data-query": true
  })), /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_26___default().createElement("div", {
    className: "flightsearch-form-gap"
  }, /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_26___default().createElement("i", {
    className: "icon-air-two-way"
  })), /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_26___default().createElement("div", {
    className: "flightsearch-form-place flightseach-form-to",
    type: to.type
  }, /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_26___default().createElement(AutoComplete, {
    getValues: to,
    setValues: setTo,
    placeholder: _bem_ts_service_translator__WEBPACK_IMPORTED_MODULE_28__["default"].trans('to')
  }), /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_26___default().createElement("input", {
    name: "to",
    type: "hidden",
    value: to.query || to.value,
    "data-query": true
  })), /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_26___default().createElement("div", {
    className: "flightsearch-form-menu flightsearch-form-trip"
  }, /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_26___default().createElement("input", {
    type: "hidden",
    name: "type",
    value: type.id
  }), /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_26___default().createElement("a", {
    className: "rel-this",
    href: "",
    "data-target": "flight-type"
  }, /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_26___default().createElement("span", null, type.name), /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_26___default().createElement("i", {
    className: "icon-silver-arrow-down"
  })), /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_26___default().createElement(ListSubMenu, {
    id: "flight-type",
    items: form.types,
    setValue: setType
  })), /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_26___default().createElement("div", {
    className: "flightsearch-form-menu flightsearch-form-class"
  }, /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_26___default().createElement("input", {
    type: "hidden",
    name: "class",
    value: classes.id
  }), /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_26___default().createElement("a", {
    className: "rel-this",
    href: "",
    "data-target": "flight-class"
  }, /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_26___default().createElement("span", null, classes.name), /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_26___default().createElement("i", {
    className: "icon-silver-arrow-down"
  })), /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_26___default().createElement(ListSubMenu, {
    id: "flight-class",
    items: form.classes,
    setValue: setClass
  })), /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_26___default().createElement("div", {
    className: "flightsearch-form-submit"
  }, /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_26___default().createElement("button", {
    className: "btn-blue",
    type: "submit"
  }, _bem_ts_service_translator__WEBPACK_IMPORTED_MODULE_28__["default"].trans('search')))));
});
function AutoComplete(props) {
  var _props$getValues;
  var element = (0,react__WEBPACK_IMPORTED_MODULE_26__.useRef)(null);
  (0,react__WEBPACK_IMPORTED_MODULE_26__.useEffect)(function () {
    $(element.current).off('keydown keyup change').on('keyup change', function (e) {
      if ($(element.current).val() !== $(element.current).data('value')) {
        $(element.current).removeAttr('data-value').parent().removeAttr('type');
      }
    }).on('keydown', function (e) {
      var _$$data;
      if (9 === e.keyCode && undefined !== ((_$$data = $(this).data('ui-autocomplete')) === null || _$$data === void 0 || (_$$data = _$$data.menu) === null || _$$data === void 0 || (_$$data = _$$data.element[0]) === null || _$$data === void 0 ? void 0 : _$$data.childNodes[0])) {
        $(this).data('ui-autocomplete').menu.element[0].childNodes[0].click();
      }
      if (!$.trim($(e.target).val()) && (e.keyCode === 0 || e.keyCode === 32)) {
        e.preventDefault();
      }
    }).autocomplete({
      delay: 1,
      minLength: 2,
      source: function source(request, response) {
        if (request.term && request.term.length >= 2) {
          $.get(Routing.generate('aw_flight_search_place', {
            query: request.term
          }), function (data) {
            $(element.current).data('data', data).removeClass('loading-input');
            response(data.map(function (item) {
              return {
                id: item.id,
                type: item.type,
                value: item.value,
                label: item.name,
                info: item.info,
                code: item.code
              };
            }));
          });
        }
      },
      search: function search(event, ui) {
        props.getValues.value.length >= 2 ? element.current.classList.add('loading-input') : element.current.classList.remove('loading-input');
      },
      open: function open(event, ui) {
        element.current.classList.remove('loading-input');
      },
      create: function create() {
        $(this).data('ui-autocomplete')._renderItem = function (ul, item) {
          var regex = new RegExp('(' + this.element.val() + ')', 'gi');
          var code = item.code.replace(regex, '<b>$1</b>');
          var label = item.label.replace(regex, '<b>$1</b>');
          var info = '' === item.info ? '<b>&nbsp;</b>' : item.info.replace(regex, '<b>$1</b>');
          switch (item.type) {
            case 2:
              label += " (".concat(_bem_ts_service_translator__WEBPACK_IMPORTED_MODULE_28__["default"].trans('city'), ")");
              break;
            case 3:
              label += " (".concat(_bem_ts_service_translator__WEBPACK_IMPORTED_MODULE_28__["default"].trans('cart.state'), ")");
              break;
            case 4:
              label += " (".concat(_bem_ts_service_translator__WEBPACK_IMPORTED_MODULE_28__["default"].trans('cart.country'), ")");
              break;
            case 5:
              label += " (".concat(_bem_ts_service_translator__WEBPACK_IMPORTED_MODULE_28__["default"].trans('region'), ")");
              break;
          }
          var transpBg = -1 !== code.indexOf('icon-') ? ' icon-block' : '';
          var html = "<span class=\"silver".concat(transpBg, "\">").concat(code, "</span><i>").concat(label, "</i><span>").concat(info, "</span>");
          return $('<li></li>').data('item.autocomplete', item).append($("<a class=\"address-location address-location-type-".concat(item.type, "\"></a>")).html(html)).appendTo(ul);
        };
      },
      select: function select(event, ui) {
        props.setValues({
          type: ui.item.type,
          value: ui.item.value,
          query: ui.item.type + '-' + ui.item.id
        });
        $(element.current).data('value', ui.item.value).parent().attr('type', ui.item.type);
      }
    });
  }, []);
  return /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_26___default().createElement("input", {
    type: "text",
    placeholder: props.placeholder,
    required: "required",
    ref: element,
    value: props.getValues.value,
    "data-value": ((_props$getValues = props.getValues) === null || _props$getValues === void 0 ? void 0 : _props$getValues.value) || '',
    onChange: (0,react__WEBPACK_IMPORTED_MODULE_26__.useMemo)(function () {
      return function (e) {
        return props.setValues(_objectSpread(_objectSpread({}, props.getValues), {
          value: e.target.value
        }));
      };
    })
  });
}
function ListSubMenu(props) {
  return /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_26___default().createElement("ul", {
    className: "dropdown-submenu ",
    "data-role": "dropdown",
    "data-id": props.id,
    role: "menu"
  }, Object.entries(props.items).map(function (value, index, arr) {
    return /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_26___default().createElement("li", {
      className: "ui-menu-item",
      role: "presentation",
      key: value[0]
    }, /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_26___default().createElement("a", {
      href: "",
      onClick: function onClick(event) {
        event.preventDefault();
        props.setValue({
          id: value[0],
          name: value[1]
        });
      }
    }, /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_26___default().createElement("span", null, value[1])));
  }));
}
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (Form);

/***/ }),

/***/ "./assets/js-deprecated/component-deprecated/FlightSearch/GroupList.js":
/*!*****************************************************************************!*\
  !*** ./assets/js-deprecated/component-deprecated/FlightSearch/GroupList.js ***!
  \*****************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var core_js_modules_es_array_map_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! core-js/modules/es.array.map.js */ "./node_modules/core-js/modules/es.array.map.js");
/* harmony import */ var core_js_modules_es_array_map_js__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_array_map_js__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var core_js_modules_es_function_name_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! core-js/modules/es.function.name.js */ "./node_modules/core-js/modules/es.function.name.js");
/* harmony import */ var core_js_modules_es_function_name_js__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_function_name_js__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var core_js_modules_es_array_concat_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! core-js/modules/es.array.concat.js */ "./node_modules/core-js/modules/es.array.concat.js");
/* harmony import */ var core_js_modules_es_array_concat_js__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_array_concat_js__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var core_js_modules_es_object_to_string_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! core-js/modules/es.object.to-string.js */ "./node_modules/core-js/modules/es.object.to-string.js");
/* harmony import */ var core_js_modules_es_object_to_string_js__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_object_to_string_js__WEBPACK_IMPORTED_MODULE_3__);
/* harmony import */ var core_js_modules_es_string_link_js__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! core-js/modules/es.string.link.js */ "./node_modules/core-js/modules/es.string.link.js");
/* harmony import */ var core_js_modules_es_string_link_js__WEBPACK_IMPORTED_MODULE_4___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_string_link_js__WEBPACK_IMPORTED_MODULE_4__);
/* harmony import */ var core_js_modules_es_symbol_js__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! core-js/modules/es.symbol.js */ "./node_modules/core-js/modules/es.symbol.js");
/* harmony import */ var core_js_modules_es_symbol_js__WEBPACK_IMPORTED_MODULE_5___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_symbol_js__WEBPACK_IMPORTED_MODULE_5__);
/* harmony import */ var core_js_modules_es_symbol_description_js__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! core-js/modules/es.symbol.description.js */ "./node_modules/core-js/modules/es.symbol.description.js");
/* harmony import */ var core_js_modules_es_symbol_description_js__WEBPACK_IMPORTED_MODULE_6___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_symbol_description_js__WEBPACK_IMPORTED_MODULE_6__);
/* harmony import */ var core_js_modules_es_symbol_iterator_js__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__(/*! core-js/modules/es.symbol.iterator.js */ "./node_modules/core-js/modules/es.symbol.iterator.js");
/* harmony import */ var core_js_modules_es_symbol_iterator_js__WEBPACK_IMPORTED_MODULE_7___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_symbol_iterator_js__WEBPACK_IMPORTED_MODULE_7__);
/* harmony import */ var core_js_modules_es_array_iterator_js__WEBPACK_IMPORTED_MODULE_8__ = __webpack_require__(/*! core-js/modules/es.array.iterator.js */ "./node_modules/core-js/modules/es.array.iterator.js");
/* harmony import */ var core_js_modules_es_array_iterator_js__WEBPACK_IMPORTED_MODULE_8___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_array_iterator_js__WEBPACK_IMPORTED_MODULE_8__);
/* harmony import */ var core_js_modules_es_string_iterator_js__WEBPACK_IMPORTED_MODULE_9__ = __webpack_require__(/*! core-js/modules/es.string.iterator.js */ "./node_modules/core-js/modules/es.string.iterator.js");
/* harmony import */ var core_js_modules_es_string_iterator_js__WEBPACK_IMPORTED_MODULE_9___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_string_iterator_js__WEBPACK_IMPORTED_MODULE_9__);
/* harmony import */ var core_js_modules_web_dom_collections_iterator_js__WEBPACK_IMPORTED_MODULE_10__ = __webpack_require__(/*! core-js/modules/web.dom-collections.iterator.js */ "./node_modules/core-js/modules/web.dom-collections.iterator.js");
/* harmony import */ var core_js_modules_web_dom_collections_iterator_js__WEBPACK_IMPORTED_MODULE_10___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_web_dom_collections_iterator_js__WEBPACK_IMPORTED_MODULE_10__);
/* harmony import */ var core_js_modules_es_array_slice_js__WEBPACK_IMPORTED_MODULE_11__ = __webpack_require__(/*! core-js/modules/es.array.slice.js */ "./node_modules/core-js/modules/es.array.slice.js");
/* harmony import */ var core_js_modules_es_array_slice_js__WEBPACK_IMPORTED_MODULE_11___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_array_slice_js__WEBPACK_IMPORTED_MODULE_11__);
/* harmony import */ var core_js_modules_es_regexp_to_string_js__WEBPACK_IMPORTED_MODULE_12__ = __webpack_require__(/*! core-js/modules/es.regexp.to-string.js */ "./node_modules/core-js/modules/es.regexp.to-string.js");
/* harmony import */ var core_js_modules_es_regexp_to_string_js__WEBPACK_IMPORTED_MODULE_12___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_regexp_to_string_js__WEBPACK_IMPORTED_MODULE_12__);
/* harmony import */ var core_js_modules_es_array_from_js__WEBPACK_IMPORTED_MODULE_13__ = __webpack_require__(/*! core-js/modules/es.array.from.js */ "./node_modules/core-js/modules/es.array.from.js");
/* harmony import */ var core_js_modules_es_array_from_js__WEBPACK_IMPORTED_MODULE_13___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_array_from_js__WEBPACK_IMPORTED_MODULE_13__);
/* harmony import */ var core_js_modules_es_regexp_exec_js__WEBPACK_IMPORTED_MODULE_14__ = __webpack_require__(/*! core-js/modules/es.regexp.exec.js */ "./node_modules/core-js/modules/es.regexp.exec.js");
/* harmony import */ var core_js_modules_es_regexp_exec_js__WEBPACK_IMPORTED_MODULE_14___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_regexp_exec_js__WEBPACK_IMPORTED_MODULE_14__);
/* harmony import */ var _bem_ts_service_formatter__WEBPACK_IMPORTED_MODULE_15__ = __webpack_require__(/*! ../../../bem/ts/service/formatter */ "./assets/bem/ts/service/formatter.ts");
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_16__ = __webpack_require__(/*! react */ "./node_modules/react/index.js");
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_16___default = /*#__PURE__*/__webpack_require__.n(react__WEBPACK_IMPORTED_MODULE_16__);
/* harmony import */ var _bem_ts_service_translator__WEBPACK_IMPORTED_MODULE_17__ = __webpack_require__(/*! ../../../bem/ts/service/translator */ "./assets/bem/ts/service/translator.ts");
/* harmony import */ var classnames__WEBPACK_IMPORTED_MODULE_18__ = __webpack_require__(/*! classnames */ "./node_modules/classnames/index.js");
/* harmony import */ var classnames__WEBPACK_IMPORTED_MODULE_18___default = /*#__PURE__*/__webpack_require__.n(classnames__WEBPACK_IMPORTED_MODULE_18__);















function _slicedToArray(arr, i) { return _arrayWithHoles(arr) || _iterableToArrayLimit(arr, i) || _unsupportedIterableToArray(arr, i) || _nonIterableRest(); }
function _nonIterableRest() { throw new TypeError("Invalid attempt to destructure non-iterable instance.\nIn order to be iterable, non-array objects must have a [Symbol.iterator]() method."); }
function _unsupportedIterableToArray(o, minLen) { if (!o) return; if (typeof o === "string") return _arrayLikeToArray(o, minLen); var n = Object.prototype.toString.call(o).slice(8, -1); if (n === "Object" && o.constructor) n = o.constructor.name; if (n === "Map" || n === "Set") return Array.from(o); if (n === "Arguments" || /^(?:Ui|I)nt(?:8|16|32)(?:Clamped)?Array$/.test(n)) return _arrayLikeToArray(o, minLen); }
function _arrayLikeToArray(arr, len) { if (len == null || len > arr.length) len = arr.length; for (var i = 0, arr2 = new Array(len); i < len; i++) arr2[i] = arr[i]; return arr2; }
function _iterableToArrayLimit(r, l) { var t = null == r ? null : "undefined" != typeof Symbol && r[Symbol.iterator] || r["@@iterator"]; if (null != t) { var e, n, i, u, a = [], f = !0, o = !1; try { if (i = (t = t.call(r)).next, 0 === l) { if (Object(t) !== t) return; f = !1; } else for (; !(f = (e = i.call(t)).done) && (a.push(e.value), a.length !== l); f = !0); } catch (r) { o = !0, n = r; } finally { try { if (!f && null != t.return && (u = t.return(), Object(u) !== u)) return; } finally { if (o) throw n; } } return a; } }
function _arrayWithHoles(arr) { if (Array.isArray(arr)) return arr; }




function GroupList(props) {
  var _useState = (0,react__WEBPACK_IMPORTED_MODULE_16__.useState)(props.groups),
    _useState2 = _slicedToArray(_useState, 2),
    groups = _useState2[0],
    setGroups = _useState2[1];
  return /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_16___default().createElement("div", {
    className: 'flight-search-wrap'
  }, /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_16___default().createElement("div", {
    className: "flight-search"
  }, 0 !== props.groups.length ? /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_16___default().createElement(ListResult, {
    groups: groups,
    skyLink: props.skyLink
  }) : props.isFormFilled ? /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_16___default().createElement(SearchNotFound, null) : ''));
}
var ListResult = /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_16___default().memo(function ListResult(props) {
  return /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_16___default().createElement("div", {
    id: "searchResult",
    className: "flight-search-result"
  }, /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_16___default().createElement("div", {
    className: "flight-search-wrap"
  }, /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_16___default().createElement("div", {
    className: "flight-search-result-caption"
  }, /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_16___default().createElement("div", {
    className: "flight-search-dep"
  }, _bem_ts_service_translator__WEBPACK_IMPORTED_MODULE_17__["default"].trans('loyalty-program')), /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_16___default().createElement("div", {
    className: "flight-search-layover"
  }, _bem_ts_service_translator__WEBPACK_IMPORTED_MODULE_17__["default"].trans('layover', {}, 'trips')), /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_16___default().createElement("div", {
    className: "flight-search-arr"
  }), /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_16___default().createElement("div", {
    className: "flight-search-operating"
  }, _bem_ts_service_translator__WEBPACK_IMPORTED_MODULE_17__["default"].trans('itineraries.trip.air.airline-name', {}, 'trips')), /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_16___default().createElement("div", {
    className: "flight-search-miles-spent",
    title: "TotalMilesSpent"
  }, _bem_ts_service_translator__WEBPACK_IMPORTED_MODULE_17__["default"].trans('points')), /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_16___default().createElement("div", {
    className: "flight-search-taxes",
    title: "TotalTaxesSpent"
  }, _bem_ts_service_translator__WEBPACK_IMPORTED_MODULE_17__["default"].trans('taxes')), /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_16___default().createElement("div", {
    className: "flight-search-altcost",
    title: "AlternativeCost"
  }, _bem_ts_service_translator__WEBPACK_IMPORTED_MODULE_17__["default"].trans('itineraries.cost', {}, 'trips')), /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_16___default().createElement("div", {
    className: "flight-search-mile-value",
    title: "MileValue"
  }, _bem_ts_service_translator__WEBPACK_IMPORTED_MODULE_17__["default"].trans('coupon.value')), /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_16___default().createElement("div", {
    className: "flight-search-reduce"
  }), /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_16___default().createElement("div", {
    className: "flight-search-debug-id"
  }, "id")), props.groups.map(function (provider, index) {
    return /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_16___default().createElement(ProviderStack, {
      provider: provider,
      index: index,
      key: provider.providerId,
      skyLink: props.skyLink
    });
  })));
});
var ProviderStack = /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_16___default().memo(function ProviderStack(props) {
  var provider = props.provider;
  var _useState3 = (0,react__WEBPACK_IMPORTED_MODULE_16__.useState)(0 === props.index),
    _useState4 = _slicedToArray(_useState3, 2),
    isExpanded = _useState4[0],
    setExpanded = _useState4[1];
  return /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_16___default().createElement("div", {
    className: classnames__WEBPACK_IMPORTED_MODULE_18___default()({
      'flight-search-provider': true,
      'flight-search-provider--expanded': isExpanded
    })
  }, /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_16___default().createElement("div", {
    className: "flight-search-head"
  }, /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_16___default().createElement("div", {
    className: "flight-search-airline"
  }, /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_16___default().createElement("a", {
    className: "flight-search-items-toggle",
    href: "#",
    onClick: (0,react__WEBPACK_IMPORTED_MODULE_16__.useMemo)(function () {
      return function () {
        return setExpanded(!isExpanded);
      };
    })
  }, /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_16___default().createElement("i", {
    className: "icon-arrow-right-dark"
  }), " ", provider.name)), /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_16___default().createElement("div", {
    className: "flight-search-miles-spent"
  }, (0,_bem_ts_service_formatter__WEBPACK_IMPORTED_MODULE_15__.numberFormat)(provider.avg.TotalMilesSpent)), /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_16___default().createElement("div", {
    className: "flight-search-taxes"
  }, (0,_bem_ts_service_formatter__WEBPACK_IMPORTED_MODULE_15__.currencyFormat)(provider.avg.TotalTaxesSpent)), /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_16___default().createElement("div", {
    className: "flight-search-altcost"
  }, /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_16___default().createElement("a", {
    href: props.skyLink,
    target: "_blank",
    rel: "noreferrer"
  }, (0,_bem_ts_service_formatter__WEBPACK_IMPORTED_MODULE_15__.currencyFormat)(provider.avg.AlternativeCost, 'USD', {
    maximumFractionDigits: 0
  }))), /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_16___default().createElement("div", {
    className: "flight-search-mile-value"
  }, (0,_bem_ts_service_formatter__WEBPACK_IMPORTED_MODULE_15__.numberFormat)(provider.avg.MileValue), _bem_ts_service_translator__WEBPACK_IMPORTED_MODULE_17__["default"].trans('us-cent-symbol')), /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_16___default().createElement("div", {
    className: "flight-search-reduce"
  }), /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_16___default().createElement("div", {
    className: "flight-search-debug-id"
  })), /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_16___default().createElement(ListItems, {
    items: provider.items,
    skyLink: props.skyLink
  }));
});
var ListItems = /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_16___default().memo(function ListItems(props) {
  return /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_16___default().createElement("div", {
    className: "flight-search-body"
  }, props.items.map(function (item) {
    return /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_16___default().createElement("div", {
      key: "".concat(item.ProviderID, "-").concat(item.MileRoute),
      className: "flight-search-item"
    }, /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_16___default().createElement("div", {
      className: "flight-search-dep"
    }, /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_16___default().createElement("div", {
      className: "flight-search-location"
    }, /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_16___default().createElement("div", {
      className: "flight-search-code"
    }, item.dep.code), /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_16___default().createElement("div", {
      className: "flight-search-name"
    }, item.dep.location))), /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_16___default().createElement("div", {
      className: "flight-search-layover flight-search-stops"
    }, item.stops.map(function (stop) {
      return /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_16___default().createElement("div", {
        key: stop.code,
        className: "flight-search-location"
      }, /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_16___default().createElement("div", {
        className: "flight-search-code"
      }, stop.code), /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_16___default().createElement("div", {
        className: "flight-search-name"
      }, stop.location));
    })), /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_16___default().createElement("div", {
      className: "flight-search-arr"
    }, /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_16___default().createElement("div", {
      className: "flight-search-location"
    }, /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_16___default().createElement("div", {
      className: "flight-search-code"
    }, item.arr.code), /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_16___default().createElement("div", {
      className: "flight-search-name"
    }, item.arr.location))), /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_16___default().createElement("div", {
      className: "flight-search-operating",
      dangerouslySetInnerHTML: {
        __html: item.airline
      }
    }), /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_16___default().createElement("div", {
      className: "flight-search-miles-spent"
    }, (0,_bem_ts_service_formatter__WEBPACK_IMPORTED_MODULE_15__.numberFormat)(item.TotalMilesSpent)), /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_16___default().createElement("div", {
      className: "flight-search-taxes"
    }, (0,_bem_ts_service_formatter__WEBPACK_IMPORTED_MODULE_15__.currencyFormat)(item.TotalTaxesSpent)), /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_16___default().createElement("div", {
      className: "flight-search-altcost"
    }, /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_16___default().createElement("a", {
      href: props.skyLink,
      target: "_blank",
      rel: "noreferrer"
    }, (0,_bem_ts_service_formatter__WEBPACK_IMPORTED_MODULE_15__.currencyFormat)(item.AlternativeCost, 'USD', {
      maximumFractionDigits: 0
    }))), /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_16___default().createElement("div", {
      className: "flight-search-mile-value"
    }, item.MileValue.raw, _bem_ts_service_translator__WEBPACK_IMPORTED_MODULE_17__["default"].trans('us-cent-symbol')), /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_16___default().createElement("div", {
      className: "flight-search-reduce"
    }, undefined !== item.arr.reduce ? /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_16___default().createElement("a", {
      className: "btn-silver",
      href: item.arr.reduce.link
    }, _bem_ts_service_translator__WEBPACK_IMPORTED_MODULE_17__["default"].trans('search'), " ", item.arr.reduce.location) : null), /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_16___default().createElement("div", {
      className: "flight-search-debug-id"
    }, item._debug.MileValueID.map(function (id) {
      return /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_16___default().createElement("div", {
        key: id
      }, /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_16___default().createElement("a", {
        href: "/manager/list.php?Schema=MileValue&MileValueID=" + id,
        target: "mv"
      }, id));
    })));
  }));
});
function SearchNotFound() {
  return /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_16___default().createElement("div", {
    className: "routes-not-found"
  }, /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_16___default().createElement("div", {
    className: "alternative-path"
  }, /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_16___default().createElement("i", {
    className: "icon-warning-small"
  }), /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_16___default().createElement("p", {
    dangerouslySetInnerHTML: {
      __html: _bem_ts_service_translator__WEBPACK_IMPORTED_MODULE_17__["default"].trans('we-not-find-any-result', {
        'break': '<br/>'
      })
    }
  })));
}
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (GroupList);

/***/ }),

/***/ "./assets/js-deprecated/component-deprecated/FlightSearch/index.js":
/*!*************************************************************************!*\
  !*** ./assets/js-deprecated/component-deprecated/FlightSearch/index.js ***!
  \*************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
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
/* harmony import */ var core_js_modules_es_object_keys_js__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! core-js/modules/es.object.keys.js */ "./node_modules/core-js/modules/es.object.keys.js");
/* harmony import */ var core_js_modules_es_object_keys_js__WEBPACK_IMPORTED_MODULE_5___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_object_keys_js__WEBPACK_IMPORTED_MODULE_5__);
/* harmony import */ var core_js_modules_es_symbol_js__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! core-js/modules/es.symbol.js */ "./node_modules/core-js/modules/es.symbol.js");
/* harmony import */ var core_js_modules_es_symbol_js__WEBPACK_IMPORTED_MODULE_6___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_symbol_js__WEBPACK_IMPORTED_MODULE_6__);
/* harmony import */ var core_js_modules_es_symbol_description_js__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__(/*! core-js/modules/es.symbol.description.js */ "./node_modules/core-js/modules/es.symbol.description.js");
/* harmony import */ var core_js_modules_es_symbol_description_js__WEBPACK_IMPORTED_MODULE_7___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_symbol_description_js__WEBPACK_IMPORTED_MODULE_7__);
/* harmony import */ var core_js_modules_es_symbol_iterator_js__WEBPACK_IMPORTED_MODULE_8__ = __webpack_require__(/*! core-js/modules/es.symbol.iterator.js */ "./node_modules/core-js/modules/es.symbol.iterator.js");
/* harmony import */ var core_js_modules_es_symbol_iterator_js__WEBPACK_IMPORTED_MODULE_8___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_symbol_iterator_js__WEBPACK_IMPORTED_MODULE_8__);
/* harmony import */ var core_js_modules_es_symbol_async_iterator_js__WEBPACK_IMPORTED_MODULE_9__ = __webpack_require__(/*! core-js/modules/es.symbol.async-iterator.js */ "./node_modules/core-js/modules/es.symbol.async-iterator.js");
/* harmony import */ var core_js_modules_es_symbol_async_iterator_js__WEBPACK_IMPORTED_MODULE_9___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_symbol_async_iterator_js__WEBPACK_IMPORTED_MODULE_9__);
/* harmony import */ var core_js_modules_es_symbol_to_string_tag_js__WEBPACK_IMPORTED_MODULE_10__ = __webpack_require__(/*! core-js/modules/es.symbol.to-string-tag.js */ "./node_modules/core-js/modules/es.symbol.to-string-tag.js");
/* harmony import */ var core_js_modules_es_symbol_to_string_tag_js__WEBPACK_IMPORTED_MODULE_10___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_symbol_to_string_tag_js__WEBPACK_IMPORTED_MODULE_10__);
/* harmony import */ var core_js_modules_es_json_to_string_tag_js__WEBPACK_IMPORTED_MODULE_11__ = __webpack_require__(/*! core-js/modules/es.json.to-string-tag.js */ "./node_modules/core-js/modules/es.json.to-string-tag.js");
/* harmony import */ var core_js_modules_es_json_to_string_tag_js__WEBPACK_IMPORTED_MODULE_11___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_json_to_string_tag_js__WEBPACK_IMPORTED_MODULE_11__);
/* harmony import */ var core_js_modules_es_math_to_string_tag_js__WEBPACK_IMPORTED_MODULE_12__ = __webpack_require__(/*! core-js/modules/es.math.to-string-tag.js */ "./node_modules/core-js/modules/es.math.to-string-tag.js");
/* harmony import */ var core_js_modules_es_math_to_string_tag_js__WEBPACK_IMPORTED_MODULE_12___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_math_to_string_tag_js__WEBPACK_IMPORTED_MODULE_12__);
/* harmony import */ var core_js_modules_es_object_get_prototype_of_js__WEBPACK_IMPORTED_MODULE_13__ = __webpack_require__(/*! core-js/modules/es.object.get-prototype-of.js */ "./node_modules/core-js/modules/es.object.get-prototype-of.js");
/* harmony import */ var core_js_modules_es_object_get_prototype_of_js__WEBPACK_IMPORTED_MODULE_13___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_object_get_prototype_of_js__WEBPACK_IMPORTED_MODULE_13__);
/* harmony import */ var core_js_modules_web_dom_collections_for_each_js__WEBPACK_IMPORTED_MODULE_14__ = __webpack_require__(/*! core-js/modules/web.dom-collections.for-each.js */ "./node_modules/core-js/modules/web.dom-collections.for-each.js");
/* harmony import */ var core_js_modules_web_dom_collections_for_each_js__WEBPACK_IMPORTED_MODULE_14___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_web_dom_collections_for_each_js__WEBPACK_IMPORTED_MODULE_14__);
/* harmony import */ var core_js_modules_es_function_name_js__WEBPACK_IMPORTED_MODULE_15__ = __webpack_require__(/*! core-js/modules/es.function.name.js */ "./node_modules/core-js/modules/es.function.name.js");
/* harmony import */ var core_js_modules_es_function_name_js__WEBPACK_IMPORTED_MODULE_15___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_function_name_js__WEBPACK_IMPORTED_MODULE_15__);
/* harmony import */ var core_js_modules_es_array_slice_js__WEBPACK_IMPORTED_MODULE_16__ = __webpack_require__(/*! core-js/modules/es.array.slice.js */ "./node_modules/core-js/modules/es.array.slice.js");
/* harmony import */ var core_js_modules_es_array_slice_js__WEBPACK_IMPORTED_MODULE_16___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_array_slice_js__WEBPACK_IMPORTED_MODULE_16__);
/* harmony import */ var react_dom__WEBPACK_IMPORTED_MODULE_17__ = __webpack_require__(/*! react-dom */ "./node_modules/react-dom/index.js");
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_18__ = __webpack_require__(/*! react */ "./node_modules/react/index.js");
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_18___default = /*#__PURE__*/__webpack_require__.n(react__WEBPACK_IMPORTED_MODULE_18__);
/* harmony import */ var _FlightSearch_less__WEBPACK_IMPORTED_MODULE_19__ = __webpack_require__(/*! ./FlightSearch.less */ "./assets/js-deprecated/component-deprecated/FlightSearch/FlightSearch.less");
/* harmony import */ var _FlightSearch__WEBPACK_IMPORTED_MODULE_20__ = __webpack_require__(/*! ./FlightSearch */ "./assets/js-deprecated/component-deprecated/FlightSearch/FlightSearch.js");
function _typeof(o) { "@babel/helpers - typeof"; return _typeof = "function" == typeof Symbol && "symbol" == typeof Symbol.iterator ? function (o) { return typeof o; } : function (o) { return o && "function" == typeof Symbol && o.constructor === Symbol && o !== Symbol.prototype ? "symbol" : typeof o; }, _typeof(o); }
function _regeneratorRuntime() { "use strict"; /*! regenerator-runtime -- Copyright (c) 2014-present, Facebook, Inc. -- license (MIT): https://github.com/facebook/regenerator/blob/main/LICENSE */ _regeneratorRuntime = function _regeneratorRuntime() { return e; }; var t, e = {}, r = Object.prototype, n = r.hasOwnProperty, o = Object.defineProperty || function (t, e, r) { t[e] = r.value; }, i = "function" == typeof Symbol ? Symbol : {}, a = i.iterator || "@@iterator", c = i.asyncIterator || "@@asyncIterator", u = i.toStringTag || "@@toStringTag"; function define(t, e, r) { return Object.defineProperty(t, e, { value: r, enumerable: !0, configurable: !0, writable: !0 }), t[e]; } try { define({}, ""); } catch (t) { define = function define(t, e, r) { return t[e] = r; }; } function wrap(t, e, r, n) { var i = e && e.prototype instanceof Generator ? e : Generator, a = Object.create(i.prototype), c = new Context(n || []); return o(a, "_invoke", { value: makeInvokeMethod(t, r, c) }), a; } function tryCatch(t, e, r) { try { return { type: "normal", arg: t.call(e, r) }; } catch (t) { return { type: "throw", arg: t }; } } e.wrap = wrap; var h = "suspendedStart", l = "suspendedYield", f = "executing", s = "completed", y = {}; function Generator() {} function GeneratorFunction() {} function GeneratorFunctionPrototype() {} var p = {}; define(p, a, function () { return this; }); var d = Object.getPrototypeOf, v = d && d(d(values([]))); v && v !== r && n.call(v, a) && (p = v); var g = GeneratorFunctionPrototype.prototype = Generator.prototype = Object.create(p); function defineIteratorMethods(t) { ["next", "throw", "return"].forEach(function (e) { define(t, e, function (t) { return this._invoke(e, t); }); }); } function AsyncIterator(t, e) { function invoke(r, o, i, a) { var c = tryCatch(t[r], t, o); if ("throw" !== c.type) { var u = c.arg, h = u.value; return h && "object" == _typeof(h) && n.call(h, "__await") ? e.resolve(h.__await).then(function (t) { invoke("next", t, i, a); }, function (t) { invoke("throw", t, i, a); }) : e.resolve(h).then(function (t) { u.value = t, i(u); }, function (t) { return invoke("throw", t, i, a); }); } a(c.arg); } var r; o(this, "_invoke", { value: function value(t, n) { function callInvokeWithMethodAndArg() { return new e(function (e, r) { invoke(t, n, e, r); }); } return r = r ? r.then(callInvokeWithMethodAndArg, callInvokeWithMethodAndArg) : callInvokeWithMethodAndArg(); } }); } function makeInvokeMethod(e, r, n) { var o = h; return function (i, a) { if (o === f) throw new Error("Generator is already running"); if (o === s) { if ("throw" === i) throw a; return { value: t, done: !0 }; } for (n.method = i, n.arg = a;;) { var c = n.delegate; if (c) { var u = maybeInvokeDelegate(c, n); if (u) { if (u === y) continue; return u; } } if ("next" === n.method) n.sent = n._sent = n.arg;else if ("throw" === n.method) { if (o === h) throw o = s, n.arg; n.dispatchException(n.arg); } else "return" === n.method && n.abrupt("return", n.arg); o = f; var p = tryCatch(e, r, n); if ("normal" === p.type) { if (o = n.done ? s : l, p.arg === y) continue; return { value: p.arg, done: n.done }; } "throw" === p.type && (o = s, n.method = "throw", n.arg = p.arg); } }; } function maybeInvokeDelegate(e, r) { var n = r.method, o = e.iterator[n]; if (o === t) return r.delegate = null, "throw" === n && e.iterator.return && (r.method = "return", r.arg = t, maybeInvokeDelegate(e, r), "throw" === r.method) || "return" !== n && (r.method = "throw", r.arg = new TypeError("The iterator does not provide a '" + n + "' method")), y; var i = tryCatch(o, e.iterator, r.arg); if ("throw" === i.type) return r.method = "throw", r.arg = i.arg, r.delegate = null, y; var a = i.arg; return a ? a.done ? (r[e.resultName] = a.value, r.next = e.nextLoc, "return" !== r.method && (r.method = "next", r.arg = t), r.delegate = null, y) : a : (r.method = "throw", r.arg = new TypeError("iterator result is not an object"), r.delegate = null, y); } function pushTryEntry(t) { var e = { tryLoc: t[0] }; 1 in t && (e.catchLoc = t[1]), 2 in t && (e.finallyLoc = t[2], e.afterLoc = t[3]), this.tryEntries.push(e); } function resetTryEntry(t) { var e = t.completion || {}; e.type = "normal", delete e.arg, t.completion = e; } function Context(t) { this.tryEntries = [{ tryLoc: "root" }], t.forEach(pushTryEntry, this), this.reset(!0); } function values(e) { if (e || "" === e) { var r = e[a]; if (r) return r.call(e); if ("function" == typeof e.next) return e; if (!isNaN(e.length)) { var o = -1, i = function next() { for (; ++o < e.length;) if (n.call(e, o)) return next.value = e[o], next.done = !1, next; return next.value = t, next.done = !0, next; }; return i.next = i; } } throw new TypeError(_typeof(e) + " is not iterable"); } return GeneratorFunction.prototype = GeneratorFunctionPrototype, o(g, "constructor", { value: GeneratorFunctionPrototype, configurable: !0 }), o(GeneratorFunctionPrototype, "constructor", { value: GeneratorFunction, configurable: !0 }), GeneratorFunction.displayName = define(GeneratorFunctionPrototype, u, "GeneratorFunction"), e.isGeneratorFunction = function (t) { var e = "function" == typeof t && t.constructor; return !!e && (e === GeneratorFunction || "GeneratorFunction" === (e.displayName || e.name)); }, e.mark = function (t) { return Object.setPrototypeOf ? Object.setPrototypeOf(t, GeneratorFunctionPrototype) : (t.__proto__ = GeneratorFunctionPrototype, define(t, u, "GeneratorFunction")), t.prototype = Object.create(g), t; }, e.awrap = function (t) { return { __await: t }; }, defineIteratorMethods(AsyncIterator.prototype), define(AsyncIterator.prototype, c, function () { return this; }), e.AsyncIterator = AsyncIterator, e.async = function (t, r, n, o, i) { void 0 === i && (i = Promise); var a = new AsyncIterator(wrap(t, r, n, o), i); return e.isGeneratorFunction(r) ? a : a.next().then(function (t) { return t.done ? t.value : a.next(); }); }, defineIteratorMethods(g), define(g, u, "Generator"), define(g, a, function () { return this; }), define(g, "toString", function () { return "[object Generator]"; }), e.keys = function (t) { var e = Object(t), r = []; for (var n in e) r.push(n); return r.reverse(), function next() { for (; r.length;) { var t = r.pop(); if (t in e) return next.value = t, next.done = !1, next; } return next.done = !0, next; }; }, e.values = values, Context.prototype = { constructor: Context, reset: function reset(e) { if (this.prev = 0, this.next = 0, this.sent = this._sent = t, this.done = !1, this.delegate = null, this.method = "next", this.arg = t, this.tryEntries.forEach(resetTryEntry), !e) for (var r in this) "t" === r.charAt(0) && n.call(this, r) && !isNaN(+r.slice(1)) && (this[r] = t); }, stop: function stop() { this.done = !0; var t = this.tryEntries[0].completion; if ("throw" === t.type) throw t.arg; return this.rval; }, dispatchException: function dispatchException(e) { if (this.done) throw e; var r = this; function handle(n, o) { return a.type = "throw", a.arg = e, r.next = n, o && (r.method = "next", r.arg = t), !!o; } for (var o = this.tryEntries.length - 1; o >= 0; --o) { var i = this.tryEntries[o], a = i.completion; if ("root" === i.tryLoc) return handle("end"); if (i.tryLoc <= this.prev) { var c = n.call(i, "catchLoc"), u = n.call(i, "finallyLoc"); if (c && u) { if (this.prev < i.catchLoc) return handle(i.catchLoc, !0); if (this.prev < i.finallyLoc) return handle(i.finallyLoc); } else if (c) { if (this.prev < i.catchLoc) return handle(i.catchLoc, !0); } else { if (!u) throw new Error("try statement without catch or finally"); if (this.prev < i.finallyLoc) return handle(i.finallyLoc); } } } }, abrupt: function abrupt(t, e) { for (var r = this.tryEntries.length - 1; r >= 0; --r) { var o = this.tryEntries[r]; if (o.tryLoc <= this.prev && n.call(o, "finallyLoc") && this.prev < o.finallyLoc) { var i = o; break; } } i && ("break" === t || "continue" === t) && i.tryLoc <= e && e <= i.finallyLoc && (i = null); var a = i ? i.completion : {}; return a.type = t, a.arg = e, i ? (this.method = "next", this.next = i.finallyLoc, y) : this.complete(a); }, complete: function complete(t, e) { if ("throw" === t.type) throw t.arg; return "break" === t.type || "continue" === t.type ? this.next = t.arg : "return" === t.type ? (this.rval = this.arg = t.arg, this.method = "return", this.next = "end") : "normal" === t.type && e && (this.next = e), y; }, finish: function finish(t) { for (var e = this.tryEntries.length - 1; e >= 0; --e) { var r = this.tryEntries[e]; if (r.finallyLoc === t) return this.complete(r.completion, r.afterLoc), resetTryEntry(r), y; } }, catch: function _catch(t) { for (var e = this.tryEntries.length - 1; e >= 0; --e) { var r = this.tryEntries[e]; if (r.tryLoc === t) { var n = r.completion; if ("throw" === n.type) { var o = n.arg; resetTryEntry(r); } return o; } } throw new Error("illegal catch attempt"); }, delegateYield: function delegateYield(e, r, n) { return this.delegate = { iterator: values(e), resultName: r, nextLoc: n }, "next" === this.method && (this.arg = t), y; } }, e; }

















function asyncGeneratorStep(gen, resolve, reject, _next, _throw, key, arg) { try { var info = gen[key](arg); var value = info.value; } catch (error) { reject(error); return; } if (info.done) { resolve(value); } else { Promise.resolve(value).then(_next, _throw); } }
function _asyncToGenerator(fn) { return function () { var self = this, args = arguments; return new Promise(function (resolve, reject) { var gen = fn.apply(self, args); function _next(value) { asyncGeneratorStep(gen, resolve, reject, _next, _throw, "next", value); } function _throw(err) { asyncGeneratorStep(gen, resolve, reject, _next, _throw, "throw", err); } _next(undefined); }); }; }




_asyncToGenerator( /*#__PURE__*/_regeneratorRuntime().mark(function _callee() {
  var root, data;
  return _regeneratorRuntime().wrap(function _callee$(_context) {
    while (1) switch (_context.prev = _context.next) {
      case 0:
        _context.next = 2;
        return __webpack_require__.e(/*! import() */ "assets_bem_ts_starter_ts-_c1f71").then(__webpack_require__.bind(__webpack_require__, /*! ../../../bem/ts/starter */ "./assets/bem/ts/starter.ts"));
      case 2:
        root = document.getElementById('content');
        data = JSON.parse(document.getElementById('data').textContent);
        (0,react_dom__WEBPACK_IMPORTED_MODULE_17__.render)( /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_18___default().createElement((react__WEBPACK_IMPORTED_MODULE_18___default().StrictMode), null, /*#__PURE__*/react__WEBPACK_IMPORTED_MODULE_18___default().createElement(_FlightSearch__WEBPACK_IMPORTED_MODULE_20__["default"], {
          data: data
        })), root);
      case 5:
      case "end":
        return _context.stop();
    }
  }, _callee);
}))();

/***/ }),

/***/ "./assets/bem/ts/service/env.ts":
/*!**************************************!*\
  !*** ./assets/bem/ts/service/env.ts ***!
  \**************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
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

/***/ "./assets/bem/ts/service/formatter.ts":
/*!********************************************!*\
  !*** ./assets/bem/ts/service/formatter.ts ***!
  \********************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   currencyFormat: () => (/* binding */ currencyFormat),
/* harmony export */   formatFileSize: () => (/* binding */ formatFileSize),
/* harmony export */   numberFormat: () => (/* binding */ numberFormat)
/* harmony export */ });
/* harmony import */ var core_js_modules_es_regexp_exec_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! core-js/modules/es.regexp.exec.js */ "./node_modules/core-js/modules/es.regexp.exec.js");
/* harmony import */ var core_js_modules_es_regexp_exec_js__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_regexp_exec_js__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var core_js_modules_es_string_replace_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! core-js/modules/es.string.replace.js */ "./node_modules/core-js/modules/es.string.replace.js");
/* harmony import */ var core_js_modules_es_string_replace_js__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_string_replace_js__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var core_js_modules_es_object_assign_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! core-js/modules/es.object.assign.js */ "./node_modules/core-js/modules/es.object.assign.js");
/* harmony import */ var core_js_modules_es_object_assign_js__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_object_assign_js__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var core_js_modules_es_object_to_string_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! core-js/modules/es.object.to-string.js */ "./node_modules/core-js/modules/es.object.to-string.js");
/* harmony import */ var core_js_modules_es_object_to_string_js__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_object_to_string_js__WEBPACK_IMPORTED_MODULE_3__);
/* harmony import */ var core_js_modules_es_regexp_to_string_js__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! core-js/modules/es.regexp.to-string.js */ "./node_modules/core-js/modules/es.regexp.to-string.js");
/* harmony import */ var core_js_modules_es_regexp_to_string_js__WEBPACK_IMPORTED_MODULE_4___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_regexp_to_string_js__WEBPACK_IMPORTED_MODULE_4__);
/* harmony import */ var core_js_modules_es_number_to_fixed_js__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! core-js/modules/es.number.to-fixed.js */ "./node_modules/core-js/modules/es.number.to-fixed.js");
/* harmony import */ var core_js_modules_es_number_to_fixed_js__WEBPACK_IMPORTED_MODULE_5___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_number_to_fixed_js__WEBPACK_IMPORTED_MODULE_5__);
/* harmony import */ var _env__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! ./env */ "./assets/bem/ts/service/env.ts");







function numberFormat(value) {
  return new Intl.NumberFormat((0,_env__WEBPACK_IMPORTED_MODULE_6__.extractOptions)().locale.replace('_', '-')).format(value);
}
function currencyFormat(value) {
  var currency = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : 'USD';
  var options = arguments.length > 2 && arguments[2] !== undefined ? arguments[2] : {};
  return new Intl.NumberFormat((0,_env__WEBPACK_IMPORTED_MODULE_6__.extractOptions)().locale.replace('_', '-'), Object.assign({
    style: 'currency',
    currency: currency
  }, options)).format(value);
}
function formatFileSize(bytes) {
  var dp = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : 1;
  var thresh = 1024;
  if (Math.abs(bytes) < thresh) {
    return bytes.toString() + ' B';
  }
  var units = ['kB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB'];
  var r = Math.pow(10, dp);
  var u = -1;
  do {
    bytes /= thresh;
    ++u;
  } while (Math.round(Math.abs(bytes) * r) / r >= thresh && u < units.length - 1);
  return bytes.toFixed(dp) + ' ' + units[u];
}

/***/ }),

/***/ "./node_modules/classnames/index.js":
/*!******************************************!*\
  !*** ./node_modules/classnames/index.js ***!
  \******************************************/
/***/ ((module, exports) => {

var __WEBPACK_AMD_DEFINE_ARRAY__, __WEBPACK_AMD_DEFINE_RESULT__;/*!
	Copyright (c) 2018 Jed Watson.
	Licensed under the MIT License (MIT), see
	http://jedwatson.github.io/classnames
*/
/* global define */

(function () {
	'use strict';

	var hasOwn = {}.hasOwnProperty;
	var nativeCodeString = '[native code]';

	function classNames() {
		var classes = [];

		for (var i = 0; i < arguments.length; i++) {
			var arg = arguments[i];
			if (!arg) continue;

			var argType = typeof arg;

			if (argType === 'string' || argType === 'number') {
				classes.push(arg);
			} else if (Array.isArray(arg)) {
				if (arg.length) {
					var inner = classNames.apply(null, arg);
					if (inner) {
						classes.push(inner);
					}
				}
			} else if (argType === 'object') {
				if (arg.toString !== Object.prototype.toString && !arg.toString.toString().includes('[native code]')) {
					classes.push(arg.toString());
					continue;
				}

				for (var key in arg) {
					if (hasOwn.call(arg, key) && arg[key]) {
						classes.push(key);
					}
				}
			}
		}

		return classes.join(' ');
	}

	if ( true && module.exports) {
		classNames.default = classNames;
		module.exports = classNames;
	} else if (true) {
		// register as 'classnames', consistent with npm package name
		!(__WEBPACK_AMD_DEFINE_ARRAY__ = [], __WEBPACK_AMD_DEFINE_RESULT__ = (function () {
			return classNames;
		}).apply(exports, __WEBPACK_AMD_DEFINE_ARRAY__),
		__WEBPACK_AMD_DEFINE_RESULT__ !== undefined && (module.exports = __WEBPACK_AMD_DEFINE_RESULT__));
	} else {}
}());


/***/ }),

/***/ "./node_modules/core-js/internals/create-html.js":
/*!*******************************************************!*\
  !*** ./node_modules/core-js/internals/create-html.js ***!
  \*******************************************************/
/***/ ((module, __unused_webpack_exports, __webpack_require__) => {

"use strict";

var uncurryThis = __webpack_require__(/*! ../internals/function-uncurry-this */ "./node_modules/core-js/internals/function-uncurry-this.js");
var requireObjectCoercible = __webpack_require__(/*! ../internals/require-object-coercible */ "./node_modules/core-js/internals/require-object-coercible.js");
var toString = __webpack_require__(/*! ../internals/to-string */ "./node_modules/core-js/internals/to-string.js");

var quot = /"/g;
var replace = uncurryThis(''.replace);

// `CreateHTML` abstract operation
// https://tc39.es/ecma262/#sec-createhtml
module.exports = function (string, tag, attribute, value) {
  var S = toString(requireObjectCoercible(string));
  var p1 = '<' + tag;
  if (attribute !== '') p1 += ' ' + attribute + '="' + replace(toString(value), quot, '&quot;') + '"';
  return p1 + '>' + S + '</' + tag + '>';
};


/***/ }),

/***/ "./node_modules/core-js/internals/object-to-array.js":
/*!***********************************************************!*\
  !*** ./node_modules/core-js/internals/object-to-array.js ***!
  \***********************************************************/
/***/ ((module, __unused_webpack_exports, __webpack_require__) => {

"use strict";

var DESCRIPTORS = __webpack_require__(/*! ../internals/descriptors */ "./node_modules/core-js/internals/descriptors.js");
var fails = __webpack_require__(/*! ../internals/fails */ "./node_modules/core-js/internals/fails.js");
var uncurryThis = __webpack_require__(/*! ../internals/function-uncurry-this */ "./node_modules/core-js/internals/function-uncurry-this.js");
var objectGetPrototypeOf = __webpack_require__(/*! ../internals/object-get-prototype-of */ "./node_modules/core-js/internals/object-get-prototype-of.js");
var objectKeys = __webpack_require__(/*! ../internals/object-keys */ "./node_modules/core-js/internals/object-keys.js");
var toIndexedObject = __webpack_require__(/*! ../internals/to-indexed-object */ "./node_modules/core-js/internals/to-indexed-object.js");
var $propertyIsEnumerable = (__webpack_require__(/*! ../internals/object-property-is-enumerable */ "./node_modules/core-js/internals/object-property-is-enumerable.js").f);

var propertyIsEnumerable = uncurryThis($propertyIsEnumerable);
var push = uncurryThis([].push);

// in some IE versions, `propertyIsEnumerable` returns incorrect result on integer keys
// of `null` prototype objects
var IE_BUG = DESCRIPTORS && fails(function () {
  // eslint-disable-next-line es/no-object-create -- safe
  var O = Object.create(null);
  O[2] = 2;
  return !propertyIsEnumerable(O, 2);
});

// `Object.{ entries, values }` methods implementation
var createMethod = function (TO_ENTRIES) {
  return function (it) {
    var O = toIndexedObject(it);
    var keys = objectKeys(O);
    var IE_WORKAROUND = IE_BUG && objectGetPrototypeOf(O) === null;
    var length = keys.length;
    var i = 0;
    var result = [];
    var key;
    while (length > i) {
      key = keys[i++];
      if (!DESCRIPTORS || (IE_WORKAROUND ? key in O : propertyIsEnumerable(O, key))) {
        push(result, TO_ENTRIES ? [key, O[key]] : O[key]);
      }
    }
    return result;
  };
};

module.exports = {
  // `Object.entries` method
  // https://tc39.es/ecma262/#sec-object.entries
  entries: createMethod(true),
  // `Object.values` method
  // https://tc39.es/ecma262/#sec-object.values
  values: createMethod(false)
};


/***/ }),

/***/ "./node_modules/core-js/internals/string-html-forced.js":
/*!**************************************************************!*\
  !*** ./node_modules/core-js/internals/string-html-forced.js ***!
  \**************************************************************/
/***/ ((module, __unused_webpack_exports, __webpack_require__) => {

"use strict";

var fails = __webpack_require__(/*! ../internals/fails */ "./node_modules/core-js/internals/fails.js");

// check the existence of a method, lowercase
// of a tag and escaping quotes in arguments
module.exports = function (METHOD_NAME) {
  return fails(function () {
    var test = ''[METHOD_NAME]('"');
    return test !== test.toLowerCase() || test.split('"').length > 3;
  });
};


/***/ }),

/***/ "./node_modules/core-js/internals/string-repeat.js":
/*!*********************************************************!*\
  !*** ./node_modules/core-js/internals/string-repeat.js ***!
  \*********************************************************/
/***/ ((module, __unused_webpack_exports, __webpack_require__) => {

"use strict";

var toIntegerOrInfinity = __webpack_require__(/*! ../internals/to-integer-or-infinity */ "./node_modules/core-js/internals/to-integer-or-infinity.js");
var toString = __webpack_require__(/*! ../internals/to-string */ "./node_modules/core-js/internals/to-string.js");
var requireObjectCoercible = __webpack_require__(/*! ../internals/require-object-coercible */ "./node_modules/core-js/internals/require-object-coercible.js");

var $RangeError = RangeError;

// `String.prototype.repeat` method implementation
// https://tc39.es/ecma262/#sec-string.prototype.repeat
module.exports = function repeat(count) {
  var str = toString(requireObjectCoercible(this));
  var result = '';
  var n = toIntegerOrInfinity(count);
  if (n < 0 || n === Infinity) throw $RangeError('Wrong number of repetitions');
  for (;n > 0; (n >>>= 1) && (str += str)) if (n & 1) result += str;
  return result;
};


/***/ }),

/***/ "./node_modules/core-js/modules/es.number.to-fixed.js":
/*!************************************************************!*\
  !*** ./node_modules/core-js/modules/es.number.to-fixed.js ***!
  \************************************************************/
/***/ ((__unused_webpack_module, __unused_webpack_exports, __webpack_require__) => {

"use strict";

var $ = __webpack_require__(/*! ../internals/export */ "./node_modules/core-js/internals/export.js");
var uncurryThis = __webpack_require__(/*! ../internals/function-uncurry-this */ "./node_modules/core-js/internals/function-uncurry-this.js");
var toIntegerOrInfinity = __webpack_require__(/*! ../internals/to-integer-or-infinity */ "./node_modules/core-js/internals/to-integer-or-infinity.js");
var thisNumberValue = __webpack_require__(/*! ../internals/this-number-value */ "./node_modules/core-js/internals/this-number-value.js");
var $repeat = __webpack_require__(/*! ../internals/string-repeat */ "./node_modules/core-js/internals/string-repeat.js");
var fails = __webpack_require__(/*! ../internals/fails */ "./node_modules/core-js/internals/fails.js");

var $RangeError = RangeError;
var $String = String;
var floor = Math.floor;
var repeat = uncurryThis($repeat);
var stringSlice = uncurryThis(''.slice);
var nativeToFixed = uncurryThis(1.0.toFixed);

var pow = function (x, n, acc) {
  return n === 0 ? acc : n % 2 === 1 ? pow(x, n - 1, acc * x) : pow(x * x, n / 2, acc);
};

var log = function (x) {
  var n = 0;
  var x2 = x;
  while (x2 >= 4096) {
    n += 12;
    x2 /= 4096;
  }
  while (x2 >= 2) {
    n += 1;
    x2 /= 2;
  } return n;
};

var multiply = function (data, n, c) {
  var index = -1;
  var c2 = c;
  while (++index < 6) {
    c2 += n * data[index];
    data[index] = c2 % 1e7;
    c2 = floor(c2 / 1e7);
  }
};

var divide = function (data, n) {
  var index = 6;
  var c = 0;
  while (--index >= 0) {
    c += data[index];
    data[index] = floor(c / n);
    c = (c % n) * 1e7;
  }
};

var dataToString = function (data) {
  var index = 6;
  var s = '';
  while (--index >= 0) {
    if (s !== '' || index === 0 || data[index] !== 0) {
      var t = $String(data[index]);
      s = s === '' ? t : s + repeat('0', 7 - t.length) + t;
    }
  } return s;
};

var FORCED = fails(function () {
  return nativeToFixed(0.00008, 3) !== '0.000' ||
    nativeToFixed(0.9, 0) !== '1' ||
    nativeToFixed(1.255, 2) !== '1.25' ||
    nativeToFixed(1000000000000000128.0, 0) !== '1000000000000000128';
}) || !fails(function () {
  // V8 ~ Android 4.3-
  nativeToFixed({});
});

// `Number.prototype.toFixed` method
// https://tc39.es/ecma262/#sec-number.prototype.tofixed
$({ target: 'Number', proto: true, forced: FORCED }, {
  toFixed: function toFixed(fractionDigits) {
    var number = thisNumberValue(this);
    var fractDigits = toIntegerOrInfinity(fractionDigits);
    var data = [0, 0, 0, 0, 0, 0];
    var sign = '';
    var result = '0';
    var e, z, j, k;

    // TODO: ES2018 increased the maximum number of fraction digits to 100, need to improve the implementation
    if (fractDigits < 0 || fractDigits > 20) throw $RangeError('Incorrect fraction digits');
    // eslint-disable-next-line no-self-compare -- NaN check
    if (number !== number) return 'NaN';
    if (number <= -1e21 || number >= 1e21) return $String(number);
    if (number < 0) {
      sign = '-';
      number = -number;
    }
    if (number > 1e-21) {
      e = log(number * pow(2, 69, 1)) - 69;
      z = e < 0 ? number * pow(2, -e, 1) : number / pow(2, e, 1);
      z *= 0x10000000000000;
      e = 52 - e;
      if (e > 0) {
        multiply(data, 0, z);
        j = fractDigits;
        while (j >= 7) {
          multiply(data, 1e7, 0);
          j -= 7;
        }
        multiply(data, pow(10, j, 1), 0);
        j = e - 1;
        while (j >= 23) {
          divide(data, 1 << 23);
          j -= 23;
        }
        divide(data, 1 << j);
        multiply(data, 1, 1);
        divide(data, 2);
        result = dataToString(data);
      } else {
        multiply(data, 0, z);
        multiply(data, 1 << -e, 0);
        result = dataToString(data) + repeat('0', fractDigits);
      }
    }
    if (fractDigits > 0) {
      k = result.length;
      result = sign + (k <= fractDigits
        ? '0.' + repeat('0', fractDigits - k) + result
        : stringSlice(result, 0, k - fractDigits) + '.' + stringSlice(result, k - fractDigits));
    } else {
      result = sign + result;
    } return result;
  }
});


/***/ }),

/***/ "./node_modules/core-js/modules/es.object.entries.js":
/*!***********************************************************!*\
  !*** ./node_modules/core-js/modules/es.object.entries.js ***!
  \***********************************************************/
/***/ ((__unused_webpack_module, __unused_webpack_exports, __webpack_require__) => {

"use strict";

var $ = __webpack_require__(/*! ../internals/export */ "./node_modules/core-js/internals/export.js");
var $entries = (__webpack_require__(/*! ../internals/object-to-array */ "./node_modules/core-js/internals/object-to-array.js").entries);

// `Object.entries` method
// https://tc39.es/ecma262/#sec-object.entries
$({ target: 'Object', stat: true }, {
  entries: function entries(O) {
    return $entries(O);
  }
});


/***/ }),

/***/ "./node_modules/core-js/modules/es.object.values.js":
/*!**********************************************************!*\
  !*** ./node_modules/core-js/modules/es.object.values.js ***!
  \**********************************************************/
/***/ ((__unused_webpack_module, __unused_webpack_exports, __webpack_require__) => {

"use strict";

var $ = __webpack_require__(/*! ../internals/export */ "./node_modules/core-js/internals/export.js");
var $values = (__webpack_require__(/*! ../internals/object-to-array */ "./node_modules/core-js/internals/object-to-array.js").values);

// `Object.values` method
// https://tc39.es/ecma262/#sec-object.values
$({ target: 'Object', stat: true }, {
  values: function values(O) {
    return $values(O);
  }
});


/***/ }),

/***/ "./node_modules/core-js/modules/es.string.link.js":
/*!********************************************************!*\
  !*** ./node_modules/core-js/modules/es.string.link.js ***!
  \********************************************************/
/***/ ((__unused_webpack_module, __unused_webpack_exports, __webpack_require__) => {

"use strict";

var $ = __webpack_require__(/*! ../internals/export */ "./node_modules/core-js/internals/export.js");
var createHTML = __webpack_require__(/*! ../internals/create-html */ "./node_modules/core-js/internals/create-html.js");
var forcedStringHTMLMethod = __webpack_require__(/*! ../internals/string-html-forced */ "./node_modules/core-js/internals/string-html-forced.js");

// `String.prototype.link` method
// https://tc39.es/ecma262/#sec-string.prototype.link
$({ target: 'String', proto: true, forced: forcedStringHTMLMethod('link') }, {
  link: function link(url) {
    return createHTML(this, 'a', 'href', url);
  }
});


/***/ }),

/***/ "./assets/js-deprecated/component-deprecated/FlightSearch/FlightSearch.less":
/*!**********************************************************************************!*\
  !*** ./assets/js-deprecated/component-deprecated/FlightSearch/FlightSearch.less ***!
  \**********************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
// extracted by mini-css-extract-plugin


/***/ }),

/***/ "./assets/less-deprecated/main.less":
/*!******************************************!*\
  !*** ./assets/less-deprecated/main.less ***!
  \******************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
// extracted by mini-css-extract-plugin


/***/ })

},
/******/ __webpack_require__ => { // webpackRuntimeModules
/******/ var __webpack_exec__ = (moduleId) => (__webpack_require__(__webpack_require__.s = moduleId))
/******/ __webpack_require__.O(0, ["vendors-node_modules_core-js_internals_classof_js-node_modules_core-js_internals_define-built-in_js","vendors-node_modules_core-js_internals_array-slice-simple_js-node_modules_core-js_internals_f-4bac30","vendors-node_modules_core-js_modules_es_string_replace_js","vendors-node_modules_core-js_modules_es_object_keys_js-node_modules_core-js_modules_es_regexp-599444","vendors-node_modules_core-js_internals_array-method-is-strict_js-node_modules_core-js_modules-ea2489","vendors-node_modules_core-js_modules_es_array_find_js-node_modules_core-js_modules_es_array_f-112b41","vendors-node_modules_core-js_modules_es_promise_js-node_modules_core-js_modules_web_dom-colle-054322","vendors-node_modules_core-js_internals_string-trim_js-node_modules_core-js_internals_this-num-d4bf43","vendors-node_modules_core-js_modules_es_json_to-string-tag_js-node_modules_core-js_modules_es-dd246b","vendors-node_modules_core-js_modules_es_array_from_js-node_modules_core-js_modules_es_date_to-6fede4","assets_bem_ts_service_translator_ts","web_assets_common_vendors_jquery_dist_jquery_js","web_assets_common_vendors_jquery-ui_jquery-ui_min_js","assets_bem_ts_service_router_ts","web_assets_common_fonts_webfonts_open-sans_css-web_assets_common_fonts_webfonts_roboto_css","assets_less-deprecated_main_less"], () => (__webpack_exec__("./assets/js-deprecated/component-deprecated/FlightSearch/index.js")));
/******/ var __webpack_exports__ = __webpack_require__.O();
/******/ }
]);
//# sourceMappingURL=data:application/json;charset=utf-8;base64,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QTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0EsTUFBTTtBQUNOO0FBQ0EsTUFBTTtBQUNOO0FBQ0EsQ0FBQzs7Ozs7Ozs7Ozs7O0FDbElZO0FBQ2IsUUFBUSxtQkFBTyxDQUFDLHVFQUFxQjtBQUNyQyxlQUFlLHdIQUErQzs7QUFFOUQ7QUFDQTtBQUNBLElBQUksOEJBQThCO0FBQ2xDO0FBQ0E7QUFDQTtBQUNBLENBQUM7Ozs7Ozs7Ozs7OztBQ1ZZO0FBQ2IsUUFBUSxtQkFBTyxDQUFDLHVFQUFxQjtBQUNyQyxjQUFjLHVIQUE4Qzs7QUFFNUQ7QUFDQTtBQUNBLElBQUksOEJBQThCO0FBQ2xDO0FBQ0E7QUFDQTtBQUNBLENBQUM7Ozs7Ozs7Ozs7OztBQ1ZZO0FBQ2IsUUFBUSxtQkFBTyxDQUFDLHVFQUFxQjtBQUNyQyxpQkFBaUIsbUJBQU8sQ0FBQyxpRkFBMEI7QUFDbkQsNkJBQTZCLG1CQUFPLENBQUMsK0ZBQWlDOztBQUV0RTtBQUNBO0FBQ0EsSUFBSSx1RUFBdUU7QUFDM0U7QUFDQTtBQUNBO0FBQ0EsQ0FBQzs7Ozs7Ozs7Ozs7OztBQ1hEOzs7Ozs7Ozs7Ozs7O0FDQUEiLCJzb3VyY2VzIjpbIndlYnBhY2s6Ly9Bd2FyZFdhbGxldC8uL2Fzc2V0cy9lbnRyeS1wb2ludC1kZXByZWNhdGVkL21haW4uanMiLCJ3ZWJwYWNrOi8vQXdhcmRXYWxsZXQvLi9hc3NldHMvanMtZGVwcmVjYXRlZC9jb21wb25lbnQtZGVwcmVjYXRlZC9GbGlnaHRTZWFyY2gvRmxpZ2h0U2VhcmNoLmpzIiwid2VicGFjazovL0F3YXJkV2FsbGV0Ly4vYXNzZXRzL2pzLWRlcHJlY2F0ZWQvY29tcG9uZW50LWRlcHJlY2F0ZWQvRmxpZ2h0U2VhcmNoL0Zvcm0uanMiLCJ3ZWJwYWNrOi8vQXdhcmRXYWxsZXQvLi9hc3NldHMvanMtZGVwcmVjYXRlZC9jb21wb25lbnQtZGVwcmVjYXRlZC9GbGlnaHRTZWFyY2gvR3JvdXBMaXN0LmpzIiwid2VicGFjazovL0F3YXJkV2FsbGV0Ly4vYXNzZXRzL2pzLWRlcHJlY2F0ZWQvY29tcG9uZW50LWRlcHJlY2F0ZWQvRmxpZ2h0U2VhcmNoL2luZGV4LmpzIiwid2VicGFjazovL0F3YXJkV2FsbGV0Ly4vYXNzZXRzL2JlbS90cy9zZXJ2aWNlL2Vudi50cyIsIndlYnBhY2s6Ly9Bd2FyZFdhbGxldC8uL2Fzc2V0cy9iZW0vdHMvc2VydmljZS9mb3JtYXR0ZXIudHMiLCJ3ZWJwYWNrOi8vQXdhcmRXYWxsZXQvLi9ub2RlX21vZHVsZXMvY2xhc3NuYW1lcy9pbmRleC5qcyIsIndlYnBhY2s6Ly9Bd2FyZFdhbGxldC8uL25vZGVfbW9kdWxlcy9jb3JlLWpzL2ludGVybmFscy9jcmVhdGUtaHRtbC5qcyIsIndlYnBhY2s6Ly9Bd2FyZFdhbGxldC8uL25vZGVfbW9kdWxlcy9jb3JlLWpzL2ludGVybmFscy9vYmplY3QtdG8tYXJyYXkuanMiLCJ3ZWJwYWNrOi8vQXdhcmRXYWxsZXQvLi9ub2RlX21vZHVsZXMvY29yZS1qcy9pbnRlcm5hbHMvc3RyaW5nLWh0bWwtZm9yY2VkLmpzIiwid2VicGFjazovL0F3YXJkV2FsbGV0Ly4vbm9kZV9tb2R1bGVzL2NvcmUtanMvaW50ZXJuYWxzL3N0cmluZy1yZXBlYXQuanMiLCJ3ZWJwYWNrOi8vQXdhcmRXYWxsZXQvLi9ub2RlX21vZHVsZXMvY29yZS1qcy9tb2R1bGVzL2VzLm51bWJlci50by1maXhlZC5qcyIsIndlYnBhY2s6Ly9Bd2FyZFdhbGxldC8uL25vZGVfbW9kdWxlcy9jb3JlLWpzL21vZHVsZXMvZXMub2JqZWN0LmVudHJpZXMuanMiLCJ3ZWJwYWNrOi8vQXdhcmRXYWxsZXQvLi9ub2RlX21vZHVsZXMvY29yZS1qcy9tb2R1bGVzL2VzLm9iamVjdC52YWx1ZXMuanMiLCJ3ZWJwYWNrOi8vQXdhcmRXYWxsZXQvLi9ub2RlX21vZHVsZXMvY29yZS1qcy9tb2R1bGVzL2VzLnN0cmluZy5saW5rLmpzIiwid2VicGFjazovL0F3YXJkV2FsbGV0Ly4vYXNzZXRzL2pzLWRlcHJlY2F0ZWQvY29tcG9uZW50LWRlcHJlY2F0ZWQvRmxpZ2h0U2VhcmNoL0ZsaWdodFNlYXJjaC5sZXNzPzRjNDYiLCJ3ZWJwYWNrOi8vQXdhcmRXYWxsZXQvLi9hc3NldHMvbGVzcy1kZXByZWNhdGVkL21haW4ubGVzcz8xOTg1Il0sInNvdXJjZXNDb250ZW50IjpbImltcG9ydCAnLi4vbGVzcy1kZXByZWNhdGVkL21haW4ubGVzcyc7XG4vKmVzbGludCBuby11bnVzZWQtdmFyczogXCJqcXVlcnl1aVwiKi9cbmltcG9ydCBqcXVlcnl1aSBmcm9tICdqcXVlcnl1aSc7IC8vIC5tZW51KClcblxuKGZ1bmN0aW9uIG1haW4oKSB7XG4gICAgdG9nZ2xlU2lkZWJhclZpc2libGUoKTtcbiAgICBpbml0RHJvcGRvd25zKCQoJ2JvZHknKSk7XG59KSgpO1xuXG5mdW5jdGlvbiB0b2dnbGVTaWRlYmFyVmlzaWJsZSgpIHtcbiAgICAkKHdpbmRvdykucmVzaXplKGZ1bmN0aW9uKCkge1xuICAgICAgICBsZXQgc2l6ZVdpbmRvdyA9ICQoJ2JvZHknKS53aWR0aCgpO1xuICAgICAgICBpZiAoc2l6ZVdpbmRvdyA8IDEwMjQpIHtcbiAgICAgICAgICAgICQoJy5tYWluLWJvZHknKS5hZGRDbGFzcygnc21hbGwtZGVza3RvcCcpO1xuICAgICAgICB9IGVsc2Uge1xuICAgICAgICAgICAgJCgnLm1haW4tYm9keScpLnJlbW92ZUNsYXNzKCdzbWFsbC1kZXNrdG9wJyk7XG4gICAgICAgIH1cbiAgICAgICAgaWYgKCQoJy5tYWluLWJvZHknKS5oYXNDbGFzcygnbWFudWFsLWhpZGRlbicpKSByZXR1cm47XG4gICAgICAgIGlmIChzaXplV2luZG93IDwgMTAyNCkge1xuICAgICAgICAgICAgJCgnLm1haW4tYm9keScpLmFkZENsYXNzKCdoaWRlLW1lbnUnKTtcbiAgICAgICAgfSBlbHNlIHtcbiAgICAgICAgICAgICQoJy5tYWluLWJvZHknKS5yZW1vdmVDbGFzcygnaGlkZS1tZW51Jyk7XG4gICAgICAgIH1cbiAgICB9KTtcblxuICAgIGNvbnN0IG1lbnVDbG9zZSA9IGRvY3VtZW50LnF1ZXJ5U2VsZWN0b3IoJy5tZW51LWNsb3NlJyk7XG4gICAgaWYgKG1lbnVDbG9zZSkge1xuICAgICAgICBjb25zdCBtZW51Qm9keSA9IGRvY3VtZW50LnF1ZXJ5U2VsZWN0b3IoJy5tYWluLWJvZHknKTtcbiAgICAgICAgbWVudUNsb3NlLm9uY2xpY2sgPSAoKSA9PiB7XG4gICAgICAgICAgICBtZW51Qm9keS5jbGFzc0xpc3QudG9nZ2xlKCdoaWRlLW1lbnUnKTtcbiAgICAgICAgICAgIG1lbnVCb2R5LmNsYXNzTGlzdC5hZGQoJ21hbnVhbC1oaWRkZW4nKTtcbiAgICAgICAgfTtcbiAgICB9XG59XG5cbmZ1bmN0aW9uIGluaXREcm9wZG93bnMoYXJlYSwgb3B0aW9ucykge1xuICAgIG9wdGlvbnMgPSBvcHRpb25zIHx8IHt9O1xuICAgIGNvbnN0IHNlbGVjdG9yID0gJ1tkYXRhLXJvbGU9XCJkcm9wZG93blwiXSc7XG4gICAgY29uc3QgZHJvcGRvd24gPSB1bmRlZmluZWQgIT0gYXJlYVxuICAgICAgICA/ICQoYXJlYSkuZmluZChzZWxlY3RvcikuYWRkQmFjayhzZWxlY3RvcilcbiAgICAgICAgOiAkKHNlbGVjdG9yKVxuICAgIGNvbnN0IG9mUGFyZW50U2VsZWN0b3IgPSBvcHRpb25zLm9mUGFyZW50IHx8ICdsaSc7XG5cbiAgICBkcm9wZG93bi5lYWNoKGZ1bmN0aW9uKGlkLCBlbCkge1xuICAgICAgICAkKGVsKVxuICAgICAgICAgICAgLnJlbW92ZUF0dHIoJ2RhdGEtcm9sZScpXG4gICAgICAgICAgICAubWVudSgpXG4gICAgICAgICAgICAuaGlkZSgpXG4gICAgICAgICAgICAub24oJ21lbnUuaGlkZScsIGZ1bmN0aW9uKGUpIHtcbiAgICAgICAgICAgICAgICAkKGUudGFyZ2V0KS5oaWRlKDIwMCk7XG4gICAgICAgICAgICB9KTtcbiAgICAgICAgJCgnW2RhdGEtdGFyZ2V0PScgKyAkKGVsKS5kYXRhKCdpZCcpICsgJ10nKS5vbignY2xpY2snLCBmdW5jdGlvbihlKSB7XG4gICAgICAgICAgICBlLnByZXZlbnREZWZhdWx0KCk7XG4gICAgICAgICAgICBlLnN0b3BQcm9wYWdhdGlvbigpO1xuICAgICAgICAgICAgJCgnLnVpLW1lbnU6dmlzaWJsZScpLm5vdCgnW2RhdGEtaWQ9XCInICsgJCh0aGlzKS5kYXRhKCd0YXJnZXQnKSArICdcIl0nKS50cmlnZ2VyKCdtZW51LmhpZGUnKTtcbiAgICAgICAgICAgICQoZWwpLnRvZ2dsZSgwLCBmdW5jdGlvbigpIHtcbiAgICAgICAgICAgICAgICAkKGVsKS5wb3NpdGlvbih7XG4gICAgICAgICAgICAgICAgICAgIG15OiBvcHRpb25zPy5wb3NpdGlvbj8ubXkgfHwgJ2xlZnQgdG9wJyxcbiAgICAgICAgICAgICAgICAgICAgYXQ6IFwibGVmdCBib3R0b21cIixcbiAgICAgICAgICAgICAgICAgICAgb2Y6ICQoZS50YXJnZXQpLnBhcmVudHMob2ZQYXJlbnRTZWxlY3RvcikuZmluZCgnLnJlbC10aGlzJyksXG4gICAgICAgICAgICAgICAgICAgIGNvbGxpc2lvbjogXCJmaXRcIlxuICAgICAgICAgICAgICAgIH0pO1xuICAgICAgICAgICAgfSk7XG4gICAgICAgIH0pO1xuICAgIH0pO1xuICAgICQoZG9jdW1lbnQpLm9uKCdjbGljaycsIGZ1bmN0aW9uKGUpIHtcbiAgICAgICAgJCgnLnVpLW1lbnU6dmlzaWJsZScpLnRyaWdnZXIoJ21lbnUuaGlkZScpO1xuICAgIH0pO1xufTtcblxuZnVuY3Rpb24gYXV0b0NvbXBsZXRlUmVuZGVySXRlbShyZW5kZXJGdW5jdGlvbiA9IG51bGwpIHtcbiAgICBpZiAobnVsbCA9PT0gcmVuZGVyRnVuY3Rpb24pIHtcbiAgICAgICAgcmVuZGVyRnVuY3Rpb24gPSBmdW5jdGlvbih1bCwgaXRlbSkge1xuICAgICAgICAgICAgY29uc3QgcmVnZXggPSBuZXcgUmVnRXhwKCcoJyArIHRoaXMuZWxlbWVudC52YWwoKS5yZXBsYWNlKC9bXkEtWmEtejAtOdCQLdCv0LAt0Y9dKy9nLCAnJykgKyAnKScsICdnaScpLFxuICAgICAgICAgICAgICAgIGh0bWwgPSAkKCc8ZGl2Lz4nKS50ZXh0KGl0ZW0ubGFiZWwpLmh0bWwoKS5yZXBsYWNlKHJlZ2V4LCAnPGI+JDE8L2I+Jyk7XG4gICAgICAgICAgICByZXR1cm4gJCgnPGxpPjwvbGk+JylcbiAgICAgICAgICAgICAgICAuZGF0YSgnaXRlbS5hdXRvY29tcGxldGUnLCBpdGVtKVxuICAgICAgICAgICAgICAgIC5hcHBlbmQoJCgnPGE+PC9hPicpLmh0bWwoaHRtbCkpXG4gICAgICAgICAgICAgICAgLmFwcGVuZFRvKHVsKTtcbiAgICAgICAgfTtcbiAgICB9XG5cbiAgICAkLnVpLmF1dG9jb21wbGV0ZS5wcm90b3R5cGUuX3JlbmRlckl0ZW0gPSByZW5kZXJGdW5jdGlvbjtcbn1cblxuZXhwb3J0IGRlZmF1bHQgeyBpbml0RHJvcGRvd25zLCBhdXRvQ29tcGxldGVSZW5kZXJJdGVtIH07IiwiaW1wb3J0IFJlYWN0LCB7IHVzZUVmZmVjdCB9IGZyb20gJ3JlYWN0JztcbmltcG9ydCBUcmFuc2xhdG9yIGZyb20gJy4uLy4uLy4uL2JlbS90cy9zZXJ2aWNlL3RyYW5zbGF0b3InO1xuXG5pbXBvcnQgRm9ybSBmcm9tICcuL0Zvcm0nO1xuaW1wb3J0IEdyb3VwTGlzdCBmcm9tICcuL0dyb3VwTGlzdCc7XG5cbmZ1bmN0aW9uIEZsaWdodFNlYXJjaChwcm9wcykge1xuICAgIGNvbnN0IGlzRXhwYW5kUm91dGVzRXhpc3RzID0gMCAhPT0gT2JqZWN0LmtleXMocHJvcHMuZGF0YS5leHBhbmRSb3V0ZXMpLmxlbmd0aDtcbiAgICBjb25zdCBncm91cHMgPSBPYmplY3QudmFsdWVzKHByb3BzLmRhdGEucHJpbWFyeUxpc3QpO1xuICAgIGNvbnN0IHNreUxpbmsgPSAnaHR0cHM6Ly9za3lzY2FubmVyLnB4Zi5pby9jLzMyNzgzNS8xMDI3OTkxLzEzNDE2P2Fzc29jaWF0ZWlkPUFGRl9UUkFfMTkzNTRfMDAwMDEnO1xuXG4gICAgcmV0dXJuIChcbiAgICAgICAgPGRpdiBjbGFzc05hbWU9eydtYWluLWJsayBmbGlnaHQtc2VhcmNoJ30+XG4gICAgICAgICAgICA8ZGl2IGNsYXNzTmFtZT17J2ZsaWdodC1zZWFyY2gtc2t5c2Nhbm5lcid9PlxuICAgICAgICAgICAgICAgIDxhIGhyZWY9e3NreUxpbmt9IHRhcmdldD1cIl9ibGFua1wiIHJlbD1cIm5vcmVmZXJyZXJcIj48aW1nIHNyYz1cIi9hc3NldHMvYXdhcmR3YWxsZXRuZXdkZXNpZ24vaW1nL2xvZ28vc2t5Y2FubmVyLXN0YWNrZWQtLWJsdWUucG5nXCIgYWx0PVwiXCIvPjwvYT5cbiAgICAgICAgICAgIDwvZGl2PlxuICAgICAgICAgICAgPGgxPntUcmFuc2xhdG9yLnRyYW5zKCdhd2FyZC1mbGlnaHQtcmVzZWFyY2gtdG9vbCcpfTwvaDE+XG4gICAgICAgICAgICA8ZGl2IGNsYXNzTmFtZT1cIm1haW4tYmxrLWNvbnRlbnRcIj5cbiAgICAgICAgICAgICAgICA8Rm9ybSBmb3JtPXtwcm9wcy5kYXRhLmZvcm19Lz5cbiAgICAgICAgICAgICAgICB7aXNFeHBhbmRSb3V0ZXNFeGlzdHMgPyA8U2VhcmNoUmVzdWx0IGV4cGFuZFJvdXRlcz17cHJvcHMuZGF0YS5leHBhbmRSb3V0ZXN9Lz4gOiBudWxsfVxuICAgICAgICAgICAgICAgIDxHcm91cExpc3QgZ3JvdXBzPXtncm91cHN9IGlzRm9ybUZpbGxlZD17dW5kZWZpbmVkICE9PSBwcm9wcy5kYXRhLmZvcm0/LmZyb219IHNreUxpbms9e3NreUxpbmt9IC8+XG4gICAgICAgICAgICA8L2Rpdj5cbiAgICAgICAgPC9kaXY+XG4gICAgKTtcbn1cblxuY29uc3QgU2VhcmNoUmVzdWx0ID0gUmVhY3QubWVtbyhmdW5jdGlvbiBTZWFyY2hSZXN1bHQocHJvcHMpIHtcbiAgICBjb25zdCBleHBhbmRSb3V0ZXMgPSBwcm9wcy5leHBhbmRSb3V0ZXM7XG5cbiAgICByZXR1cm4gKFxuICAgICAgICA8ZGl2IGNsYXNzTmFtZT1cImZsaWdodC1zZWFyY2gtZXhwYW5kXCI+XG4gICAgICAgICAgICA8ZGl2IGNsYXNzTmFtZT1cImZsaWdodC1zZWFyY2gtZm9ybVwiPlxuICAgICAgICAgICAgICAgIDxkaXYgY2xhc3NOYW1lPVwiZmxpZ2h0c2VhcmNoLWZvcm0tcGxhY2UgZmxpZ2h0c2VhcmNoLWZvcm0tZnJvbVwiPlxuICAgICAgICAgICAgICAgICAgICB7JycgIT09IGV4cGFuZFJvdXRlcy5saW5rRnJvbSAmJiB1bmRlZmluZWQgIT09IGV4cGFuZFJvdXRlcy5saW5rRnJvbVxuICAgICAgICAgICAgICAgICAgICAgICAgPyA8YSBjbGFzc05hbWU9eydidG4tYmx1ZSd9IGhyZWY9e2V4cGFuZFJvdXRlcy5saW5rRnJvbX0+XG4gICAgICAgICAgICAgICAgICAgICAgICAgICAge1RyYW5zbGF0b3IudHJhbnMoJ2V4cGFuZC10bycsIHsgbmFtZTogZXhwYW5kUm91dGVzLmZyb20uZGVwLnZhbHVlIH0pfVxuICAgICAgICAgICAgICAgICAgICAgICAgICAgIDxzPnt0eXBlUGxhY2VTaWduKGV4cGFuZFJvdXRlcy5mcm9tLmRlcC50eXBlKX08L3M+XG4gICAgICAgICAgICAgICAgICAgICAgICA8L2E+XG4gICAgICAgICAgICAgICAgICAgICAgICA6IG51bGxcbiAgICAgICAgICAgICAgICAgICAgfVxuICAgICAgICAgICAgICAgIDwvZGl2PlxuICAgICAgICAgICAgICAgIDxkaXYgY2xhc3NOYW1lPVwiZmxpZ2h0c2VhcmNoLWZvcm0tZ2FwXCI+PC9kaXY+XG4gICAgICAgICAgICAgICAgPGRpdiBjbGFzc05hbWU9XCJmbGlnaHRzZWFyY2gtZm9ybS1wbGFjZSBmbGlnaHRzZWFjaC1mb3JtLXRvXCI+XG4gICAgICAgICAgICAgICAgICAgIHsnJyAhPT0gZXhwYW5kUm91dGVzLmxpbmtUbyAmJiB1bmRlZmluZWQgIT09IGV4cGFuZFJvdXRlcy5saW5rVG9cbiAgICAgICAgICAgICAgICAgICAgICAgID8gPGEgY2xhc3NOYW1lPXsnYnRuLWJsdWUnfSBocmVmPXtleHBhbmRSb3V0ZXMubGlua1RvfT5cbiAgICAgICAgICAgICAgICAgICAgICAgICAgICB7VHJhbnNsYXRvci50cmFucygnZXhwYW5kLXRvJywgeyBuYW1lOiBleHBhbmRSb3V0ZXMudG8uYXJyLnZhbHVlIH0pfVxuICAgICAgICAgICAgICAgICAgICAgICAgICAgIDxzPnt0eXBlUGxhY2VTaWduKGV4cGFuZFJvdXRlcy50by5hcnIudHlwZSl9PC9zPlxuICAgICAgICAgICAgICAgICAgICAgICAgPC9hPlxuICAgICAgICAgICAgICAgICAgICAgICAgOiBudWxsXG4gICAgICAgICAgICAgICAgICAgIH1cbiAgICAgICAgICAgICAgICA8L2Rpdj5cbiAgICAgICAgICAgICAgICA8ZGl2IGNsYXNzTmFtZT1cImZsaWdodHNlYXJjaC1mb3JtLW1lbnUgZmxpZ2h0c2VhcmNoLWZvcm0tdHJpcFwiPjwvZGl2PlxuICAgICAgICAgICAgICAgIDxkaXYgY2xhc3NOYW1lPVwiZmxpZ2h0c2VhcmNoLWZvcm0tbWVudSBmbGlnaHRzZWFyY2gtZm9ybS1jbGFzc1wiPjwvZGl2PlxuICAgICAgICAgICAgICAgIDxkaXYgY2xhc3NOYW1lPVwiZmxpZ2h0c2VhcmNoLWZvcm0tc3VibWl0XCI+PC9kaXY+XG4gICAgICAgICAgICA8L2Rpdj5cbiAgICAgICAgPC9kaXY+XG4gICAgKTtcbn0pO1xuXG5cbmZ1bmN0aW9uIHR5cGVQbGFjZVNpZ24odHlwZSkge1xuICAgIGlmICgyID09PSB0eXBlKSB7XG4gICAgICAgIHJldHVybiBgICgke1RyYW5zbGF0b3IudHJhbnMoJ2NpdHknKX0pYDtcbiAgICB9IGVsc2UgaWYgKDMgPT09IHR5cGUpIHtcbiAgICAgICAgcmV0dXJuIGAgKCR7VHJhbnNsYXRvci50cmFucygnY2FydC5zdGF0ZScpfSlgO1xuICAgIH0gZWxzZSBpZiAoNCA9PT0gdHlwZSkge1xuICAgICAgICByZXR1cm4gYCAoJHtUcmFuc2xhdG9yLnRyYW5zKCdjYXJ0LmNvdW50cnknKX0pYDtcbiAgICB9XG5cbiAgICByZXR1cm4gJydcbn1cblxuZXhwb3J0IGRlZmF1bHQgRmxpZ2h0U2VhcmNoOyIsImltcG9ydCBSZWFjdCwgeyB1c2VFZmZlY3QsIHVzZU1lbW8sIHVzZVJlZiwgdXNlU3RhdGUgfSBmcm9tICdyZWFjdCc7XG5cbmltcG9ydCBSb3V0ZXIgZnJvbSAnLi4vLi4vLi4vYmVtL3RzL3NlcnZpY2Uvcm91dGVyJztcbmltcG9ydCBUcmFuc2xhdG9yIGZyb20gJy4uLy4uLy4uL2JlbS90cy9zZXJ2aWNlL3RyYW5zbGF0b3InO1xuaW1wb3J0IG1haW4gZnJvbSAnLi4vLi4vLi4vZW50cnktcG9pbnQtZGVwcmVjYXRlZC9tYWluJztcblxuY29uc3QgRm9ybSA9IFJlYWN0Lm1lbW8oZnVuY3Rpb24gRm9ybShwcm9wcykge1xuICAgIGNvbnN0IGZvcm0gPSBwcm9wcy5mb3JtO1xuICAgIGNvbnN0IGJsYW5rRGF0YSA9IHsgaWQ6ICcnLCB0eXBlOiAnJywgdmFsdWU6ICcnLCBuYW1lOiAnJywgcXVlcnk6ICcnIH07XG5cbiAgICBjb25zdCBbZnJvbSwgc2V0RnJvbV0gPSB1c2VTdGF0ZShmb3JtPy5mcm9tIHx8IGJsYW5rRGF0YSk7XG4gICAgY29uc3QgW3RvLCBzZXRUb10gPSB1c2VTdGF0ZShmb3JtPy50byB8fCBibGFua0RhdGEpO1xuICAgIGNvbnN0IFt0eXBlLCBzZXRUeXBlXSA9IHVzZVN0YXRlKGZvcm0/LnR5cGUgfHwgYmxhbmtEYXRhKTtcbiAgICBjb25zdCBbY2xhc3Nlcywgc2V0Q2xhc3NdID0gdXNlU3RhdGUoZm9ybT8uY2xhc3MgfHwgYmxhbmtEYXRhKTtcblxuICAgIGNvbnN0IGhhbmRsZUZvcm1TdWJtaXQgPSB1c2VNZW1vKCgpID0+IChldmVudCkgPT4ge1xuICAgICAgICAnJyA9PT0gZnJvbS5pZCA/IHNldEZyb20oeyAuLi5mcm9tLCAuLi57IGlkOiBmcm9tLnZhbHVlIH0gfSkgOiBudWxsO1xuICAgICAgICAnJyA9PT0gdG8uaWQgPyBzZXRUbyh7IC4uLnRvLCAuLi57IGlkOiB0by52YWx1ZSB9IH0pIDogbnVsbDtcbiAgICB9KTtcblxuICAgIHVzZUVmZmVjdCgoKSA9PiB7XG4gICAgICAgIG1haW4uaW5pdERyb3Bkb3ducygnI2ZsaWdodFNlYXJjaCcsIHtcbiAgICAgICAgICAgIG9mUGFyZW50OiAnZGl2LmZsaWdodHNlYXJjaC1mb3JtLW1lbnUnLFxuICAgICAgICAgICAgcG9zaXRpb246IHsgbXk6ICdsZWZ0LTI0IHRvcCsxNicgfVxuICAgICAgICB9KTtcbiAgICB9KTtcblxuICAgIHJldHVybiAoXG4gICAgICAgIDxmb3JtIGlkPVwiZmxpZ2h0U2VhcmNoRm9ybVwiIG1ldGhvZD1cImdldFwiIG9uU3VibWl0PXtoYW5kbGVGb3JtU3VibWl0fT5cbiAgICAgICAgICAgIDxkaXYgaWQ9XCJmbGlnaHRTZWFyY2hcIiBjbGFzc05hbWU9XCJmbGlnaHQtc2VhcmNoLWZvcm1cIj5cbiAgICAgICAgICAgICAgICA8ZGl2IGNsYXNzTmFtZT1cImZsaWdodHNlYXJjaC1mb3JtLXBsYWNlIGZsaWdodHNlYWNoLWZvcm0tZnJvbVwiIHR5cGU9e2Zyb20udHlwZX0+XG4gICAgICAgICAgICAgICAgICAgIDxBdXRvQ29tcGxldGUgZ2V0VmFsdWVzPXtmcm9tfSBzZXRWYWx1ZXM9e3NldEZyb219IHBsYWNlaG9sZGVyPXtUcmFuc2xhdG9yLnRyYW5zKCdmcm9tJyl9Lz5cbiAgICAgICAgICAgICAgICAgICAgPGlucHV0IG5hbWU9XCJmcm9tXCIgdHlwZT1cImhpZGRlblwiIHZhbHVlPXtmcm9tLnF1ZXJ5IHx8IGZyb20udmFsdWV9IGRhdGEtcXVlcnkvPlxuICAgICAgICAgICAgICAgIDwvZGl2PlxuXG4gICAgICAgICAgICAgICAgPGRpdiBjbGFzc05hbWU9XCJmbGlnaHRzZWFyY2gtZm9ybS1nYXBcIj5cbiAgICAgICAgICAgICAgICAgICAgPGkgY2xhc3NOYW1lPVwiaWNvbi1haXItdHdvLXdheVwiPjwvaT5cbiAgICAgICAgICAgICAgICA8L2Rpdj5cblxuICAgICAgICAgICAgICAgIDxkaXYgY2xhc3NOYW1lPVwiZmxpZ2h0c2VhcmNoLWZvcm0tcGxhY2UgZmxpZ2h0c2VhY2gtZm9ybS10b1wiIHR5cGU9e3RvLnR5cGV9PlxuICAgICAgICAgICAgICAgICAgICA8QXV0b0NvbXBsZXRlIGdldFZhbHVlcz17dG99IHNldFZhbHVlcz17c2V0VG99IHBsYWNlaG9sZGVyPXtUcmFuc2xhdG9yLnRyYW5zKCd0bycpfS8+XG4gICAgICAgICAgICAgICAgICAgIDxpbnB1dCBuYW1lPVwidG9cIiB0eXBlPVwiaGlkZGVuXCIgdmFsdWU9e3RvLnF1ZXJ5IHx8IHRvLnZhbHVlfSBkYXRhLXF1ZXJ5Lz5cbiAgICAgICAgICAgICAgICA8L2Rpdj5cblxuICAgICAgICAgICAgICAgIDxkaXYgY2xhc3NOYW1lPVwiZmxpZ2h0c2VhcmNoLWZvcm0tbWVudSBmbGlnaHRzZWFyY2gtZm9ybS10cmlwXCI+XG4gICAgICAgICAgICAgICAgICAgIDxpbnB1dCB0eXBlPVwiaGlkZGVuXCIgbmFtZT1cInR5cGVcIiB2YWx1ZT17dHlwZS5pZH0vPlxuICAgICAgICAgICAgICAgICAgICA8YSBjbGFzc05hbWU9XCJyZWwtdGhpc1wiIGhyZWY9XCJcIiBkYXRhLXRhcmdldD1cImZsaWdodC10eXBlXCI+XG4gICAgICAgICAgICAgICAgICAgICAgICA8c3Bhbj57dHlwZS5uYW1lfTwvc3Bhbj5cbiAgICAgICAgICAgICAgICAgICAgICAgIDxpIGNsYXNzTmFtZT1cImljb24tc2lsdmVyLWFycm93LWRvd25cIj48L2k+XG4gICAgICAgICAgICAgICAgICAgIDwvYT5cbiAgICAgICAgICAgICAgICAgICAgPExpc3RTdWJNZW51IGlkPVwiZmxpZ2h0LXR5cGVcIiBpdGVtcz17Zm9ybS50eXBlc30gc2V0VmFsdWU9e3NldFR5cGV9Lz5cbiAgICAgICAgICAgICAgICA8L2Rpdj5cblxuICAgICAgICAgICAgICAgIDxkaXYgY2xhc3NOYW1lPVwiZmxpZ2h0c2VhcmNoLWZvcm0tbWVudSBmbGlnaHRzZWFyY2gtZm9ybS1jbGFzc1wiPlxuICAgICAgICAgICAgICAgICAgICA8aW5wdXQgdHlwZT1cImhpZGRlblwiIG5hbWU9XCJjbGFzc1wiIHZhbHVlPXtjbGFzc2VzLmlkfS8+XG4gICAgICAgICAgICAgICAgICAgIDxhIGNsYXNzTmFtZT1cInJlbC10aGlzXCIgaHJlZj1cIlwiIGRhdGEtdGFyZ2V0PVwiZmxpZ2h0LWNsYXNzXCI+XG4gICAgICAgICAgICAgICAgICAgICAgICA8c3Bhbj57Y2xhc3Nlcy5uYW1lfTwvc3Bhbj5cbiAgICAgICAgICAgICAgICAgICAgICAgIDxpIGNsYXNzTmFtZT1cImljb24tc2lsdmVyLWFycm93LWRvd25cIj48L2k+XG4gICAgICAgICAgICAgICAgICAgIDwvYT5cbiAgICAgICAgICAgICAgICAgICAgPExpc3RTdWJNZW51IGlkPVwiZmxpZ2h0LWNsYXNzXCIgaXRlbXM9e2Zvcm0uY2xhc3Nlc30gc2V0VmFsdWU9e3NldENsYXNzfS8+XG4gICAgICAgICAgICAgICAgPC9kaXY+XG5cbiAgICAgICAgICAgICAgICA8ZGl2IGNsYXNzTmFtZT1cImZsaWdodHNlYXJjaC1mb3JtLXN1Ym1pdFwiPlxuICAgICAgICAgICAgICAgICAgICA8YnV0dG9uIGNsYXNzTmFtZT1cImJ0bi1ibHVlXCIgdHlwZT1cInN1Ym1pdFwiPntUcmFuc2xhdG9yLnRyYW5zKCdzZWFyY2gnKX08L2J1dHRvbj5cbiAgICAgICAgICAgICAgICA8L2Rpdj5cblxuICAgICAgICAgICAgPC9kaXY+XG4gICAgICAgIDwvZm9ybT5cbiAgICApO1xufSk7XG5cbmZ1bmN0aW9uIEF1dG9Db21wbGV0ZShwcm9wcykge1xuICAgIGNvbnN0IGVsZW1lbnQgPSB1c2VSZWYobnVsbCk7XG5cbiAgICB1c2VFZmZlY3QoKCkgPT4ge1xuICAgICAgICAkKGVsZW1lbnQuY3VycmVudClcbiAgICAgICAgICAgIC5vZmYoJ2tleWRvd24ga2V5dXAgY2hhbmdlJylcbiAgICAgICAgICAgIC5vbigna2V5dXAgY2hhbmdlJywgZnVuY3Rpb24oZSkge1xuICAgICAgICAgICAgICAgIGlmICgkKGVsZW1lbnQuY3VycmVudCkudmFsKCkgIT09ICQoZWxlbWVudC5jdXJyZW50KS5kYXRhKCd2YWx1ZScpKSB7XG4gICAgICAgICAgICAgICAgICAgICQoZWxlbWVudC5jdXJyZW50KS5yZW1vdmVBdHRyKCdkYXRhLXZhbHVlJykucGFyZW50KCkucmVtb3ZlQXR0cigndHlwZScpO1xuICAgICAgICAgICAgICAgIH1cbiAgICAgICAgICAgIH0pXG4gICAgICAgICAgICAub24oJ2tleWRvd24nLCBmdW5jdGlvbihlKSB7XG4gICAgICAgICAgICAgICAgaWYgKDkgPT09IGUua2V5Q29kZSAmJiB1bmRlZmluZWQgIT09ICQodGhpcykuZGF0YSgndWktYXV0b2NvbXBsZXRlJyk/Lm1lbnU/LmVsZW1lbnRbMF0/LmNoaWxkTm9kZXNbMF0pIHtcbiAgICAgICAgICAgICAgICAgICAgJCh0aGlzKS5kYXRhKCd1aS1hdXRvY29tcGxldGUnKS5tZW51LmVsZW1lbnRbMF0uY2hpbGROb2Rlc1swXS5jbGljaygpO1xuICAgICAgICAgICAgICAgIH1cbiAgICAgICAgICAgICAgICBpZiAoISQudHJpbSgkKGUudGFyZ2V0KS52YWwoKSkgJiYgKGUua2V5Q29kZSA9PT0gMCB8fCBlLmtleUNvZGUgPT09IDMyKSkge1xuICAgICAgICAgICAgICAgICAgICBlLnByZXZlbnREZWZhdWx0KCk7XG4gICAgICAgICAgICAgICAgfVxuICAgICAgICAgICAgfSlcbiAgICAgICAgICAgIC5hdXRvY29tcGxldGUoe1xuICAgICAgICAgICAgICAgIGRlbGF5OiAxLFxuICAgICAgICAgICAgICAgIG1pbkxlbmd0aDogMixcbiAgICAgICAgICAgICAgICBzb3VyY2U6IGZ1bmN0aW9uKHJlcXVlc3QsIHJlc3BvbnNlKSB7XG4gICAgICAgICAgICAgICAgICAgIGlmIChyZXF1ZXN0LnRlcm0gJiYgcmVxdWVzdC50ZXJtLmxlbmd0aCA+PSAyKSB7XG4gICAgICAgICAgICAgICAgICAgICAgICAkLmdldChSb3V0aW5nLmdlbmVyYXRlKCdhd19mbGlnaHRfc2VhcmNoX3BsYWNlJywgeyBxdWVyeTogcmVxdWVzdC50ZXJtIH0pLCBmdW5jdGlvbihkYXRhKSB7XG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgJChlbGVtZW50LmN1cnJlbnQpLmRhdGEoJ2RhdGEnLCBkYXRhKS5yZW1vdmVDbGFzcygnbG9hZGluZy1pbnB1dCcpO1xuICAgICAgICAgICAgICAgICAgICAgICAgICAgIHJlc3BvbnNlKGRhdGEubWFwKGZ1bmN0aW9uKGl0ZW0pIHtcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgcmV0dXJuIHtcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIGlkOiBpdGVtLmlkLFxuICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgdHlwZTogaXRlbS50eXBlLFxuICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgdmFsdWU6IGl0ZW0udmFsdWUsXG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICBsYWJlbDogaXRlbS5uYW1lLFxuICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgaW5mbzogaXRlbS5pbmZvLFxuICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgY29kZTogaXRlbS5jb2RlLFxuICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICB9O1xuICAgICAgICAgICAgICAgICAgICAgICAgICAgIH0pKTtcbiAgICAgICAgICAgICAgICAgICAgICAgIH0pO1xuICAgICAgICAgICAgICAgICAgICB9XG4gICAgICAgICAgICAgICAgfSxcbiAgICAgICAgICAgICAgICBzZWFyY2g6IGZ1bmN0aW9uKGV2ZW50LCB1aSkge1xuICAgICAgICAgICAgICAgICAgICBwcm9wcy5nZXRWYWx1ZXMudmFsdWUubGVuZ3RoID49IDJcbiAgICAgICAgICAgICAgICAgICAgICAgID8gZWxlbWVudC5jdXJyZW50LmNsYXNzTGlzdC5hZGQoJ2xvYWRpbmctaW5wdXQnKVxuICAgICAgICAgICAgICAgICAgICAgICAgOiBlbGVtZW50LmN1cnJlbnQuY2xhc3NMaXN0LnJlbW92ZSgnbG9hZGluZy1pbnB1dCcpO1xuICAgICAgICAgICAgICAgIH0sXG4gICAgICAgICAgICAgICAgb3BlbjogZnVuY3Rpb24oZXZlbnQsIHVpKSB7XG4gICAgICAgICAgICAgICAgICAgIGVsZW1lbnQuY3VycmVudC5jbGFzc0xpc3QucmVtb3ZlKCdsb2FkaW5nLWlucHV0Jyk7XG4gICAgICAgICAgICAgICAgfSxcbiAgICAgICAgICAgICAgICBjcmVhdGU6IGZ1bmN0aW9uKCkge1xuICAgICAgICAgICAgICAgICAgICAkKHRoaXMpLmRhdGEoJ3VpLWF1dG9jb21wbGV0ZScpLl9yZW5kZXJJdGVtID0gZnVuY3Rpb24odWwsIGl0ZW0pIHtcbiAgICAgICAgICAgICAgICAgICAgICAgIGNvbnN0IHJlZ2V4ID0gbmV3IFJlZ0V4cCgnKCcgKyB0aGlzLmVsZW1lbnQudmFsKCkgKyAnKScsICdnaScpO1xuICAgICAgICAgICAgICAgICAgICAgICAgbGV0IGNvZGUgPSBpdGVtLmNvZGUucmVwbGFjZShyZWdleCwgJzxiPiQxPC9iPicpO1xuICAgICAgICAgICAgICAgICAgICAgICAgbGV0IGxhYmVsID0gaXRlbS5sYWJlbC5yZXBsYWNlKHJlZ2V4LCAnPGI+JDE8L2I+Jyk7XG4gICAgICAgICAgICAgICAgICAgICAgICBjb25zdCBpbmZvID0gJycgPT09IGl0ZW0uaW5mbyA/ICc8Yj4mbmJzcDs8L2I+JyA6IGl0ZW0uaW5mby5yZXBsYWNlKHJlZ2V4LCAnPGI+JDE8L2I+Jyk7XG5cbiAgICAgICAgICAgICAgICAgICAgICAgIHN3aXRjaCAoaXRlbS50eXBlKSB7XG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgY2FzZSAyOlxuICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICBsYWJlbCArPSBgICgke1RyYW5zbGF0b3IudHJhbnMoJ2NpdHknKX0pYDtcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgYnJlYWs7XG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgY2FzZSAzOlxuICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICBsYWJlbCArPSBgICgke1RyYW5zbGF0b3IudHJhbnMoJ2NhcnQuc3RhdGUnKX0pYDtcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgYnJlYWs7XG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgY2FzZSA0OlxuICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICBsYWJlbCArPSBgICgke1RyYW5zbGF0b3IudHJhbnMoJ2NhcnQuY291bnRyeScpfSlgO1xuICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICBicmVhaztcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICBjYXNlIDU6XG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIGxhYmVsICs9IGAgKCR7VHJhbnNsYXRvci50cmFucygncmVnaW9uJyl9KWA7XG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIGJyZWFrO1xuICAgICAgICAgICAgICAgICAgICAgICAgfVxuXG4gICAgICAgICAgICAgICAgICAgICAgICBjb25zdCB0cmFuc3BCZyA9IC0xICE9PSBjb2RlLmluZGV4T2YoJ2ljb24tJykgPyAnIGljb24tYmxvY2snIDogJyc7XG4gICAgICAgICAgICAgICAgICAgICAgICBjb25zdCBodG1sID0gYDxzcGFuIGNsYXNzPVwic2lsdmVyJHt0cmFuc3BCZ31cIj4ke2NvZGV9PC9zcGFuPjxpPiR7bGFiZWx9PC9pPjxzcGFuPiR7aW5mb308L3NwYW4+YDtcblxuICAgICAgICAgICAgICAgICAgICAgICAgcmV0dXJuICQoJzxsaT48L2xpPicpXG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgLmRhdGEoJ2l0ZW0uYXV0b2NvbXBsZXRlJywgaXRlbSlcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICAuYXBwZW5kKCQoYDxhIGNsYXNzPVwiYWRkcmVzcy1sb2NhdGlvbiBhZGRyZXNzLWxvY2F0aW9uLXR5cGUtJHtpdGVtLnR5cGV9XCI+PC9hPmApLmh0bWwoaHRtbCkpXG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgLmFwcGVuZFRvKHVsKTtcbiAgICAgICAgICAgICAgICAgICAgfTtcbiAgICAgICAgICAgICAgICB9LFxuICAgICAgICAgICAgICAgIHNlbGVjdDogZnVuY3Rpb24oZXZlbnQsIHVpKSB7XG4gICAgICAgICAgICAgICAgICAgIHByb3BzLnNldFZhbHVlcyh7XG4gICAgICAgICAgICAgICAgICAgICAgICB0eXBlOiB1aS5pdGVtLnR5cGUsXG4gICAgICAgICAgICAgICAgICAgICAgICB2YWx1ZTogdWkuaXRlbS52YWx1ZSxcbiAgICAgICAgICAgICAgICAgICAgICAgIHF1ZXJ5OiB1aS5pdGVtLnR5cGUgKyAnLScgKyB1aS5pdGVtLmlkLFxuICAgICAgICAgICAgICAgICAgICB9KTtcbiAgICAgICAgICAgICAgICAgICAgJChlbGVtZW50LmN1cnJlbnQpLmRhdGEoJ3ZhbHVlJywgdWkuaXRlbS52YWx1ZSkucGFyZW50KCkuYXR0cigndHlwZScsIHVpLml0ZW0udHlwZSk7XG4gICAgICAgICAgICAgICAgfVxuICAgICAgICAgICAgfSk7XG4gICAgfSwgW10pO1xuXG4gICAgcmV0dXJuIChcbiAgICAgICAgPGlucHV0IHR5cGU9XCJ0ZXh0XCIgcGxhY2Vob2xkZXI9e3Byb3BzLnBsYWNlaG9sZGVyfSByZXF1aXJlZD1cInJlcXVpcmVkXCIgcmVmPXtlbGVtZW50fVxuICAgICAgICAgICAgICAgdmFsdWU9e3Byb3BzLmdldFZhbHVlcy52YWx1ZX0gZGF0YS12YWx1ZT17cHJvcHMuZ2V0VmFsdWVzPy52YWx1ZSB8fCAnJ31cbiAgICAgICAgICAgICAgIG9uQ2hhbmdlPXt1c2VNZW1vKCgpID0+IChlKSA9PiBwcm9wcy5zZXRWYWx1ZXMoeyAuLi5wcm9wcy5nZXRWYWx1ZXMsIC4uLnsgdmFsdWU6IGUudGFyZ2V0LnZhbHVlIH0gfSkpfS8+XG4gICAgKVxufVxuXG5mdW5jdGlvbiBMaXN0U3ViTWVudShwcm9wcykge1xuICAgIHJldHVybiAoXG4gICAgICAgIDx1bCBjbGFzc05hbWU9XCJkcm9wZG93bi1zdWJtZW51IFwiIGRhdGEtcm9sZT1cImRyb3Bkb3duXCIgZGF0YS1pZD17cHJvcHMuaWR9IHJvbGU9XCJtZW51XCI+XG4gICAgICAgICAgICB7T2JqZWN0LmVudHJpZXMocHJvcHMuaXRlbXMpLm1hcCgodmFsdWUsIGluZGV4LCBhcnIpID0+XG4gICAgICAgICAgICAgICAgPGxpIGNsYXNzTmFtZT1cInVpLW1lbnUtaXRlbVwiIHJvbGU9XCJwcmVzZW50YXRpb25cIiBrZXk9e3ZhbHVlWzBdfT5cbiAgICAgICAgICAgICAgICAgICAgPGEgaHJlZj1cIlwiIG9uQ2xpY2s9eyhldmVudCkgPT4ge1xuICAgICAgICAgICAgICAgICAgICAgICAgZXZlbnQucHJldmVudERlZmF1bHQoKTtcbiAgICAgICAgICAgICAgICAgICAgICAgIHByb3BzLnNldFZhbHVlKHsgaWQ6IHZhbHVlWzBdLCBuYW1lOiB2YWx1ZVsxXSB9KTtcbiAgICAgICAgICAgICAgICAgICAgfX0+PHNwYW4+e3ZhbHVlWzFdfTwvc3Bhbj48L2E+XG4gICAgICAgICAgICAgICAgPC9saT5cbiAgICAgICAgICAgICl9XG4gICAgICAgIDwvdWw+XG4gICAgKVxufVxuXG5leHBvcnQgZGVmYXVsdCBGb3JtOyIsImltcG9ydCB7IGN1cnJlbmN5Rm9ybWF0LCBudW1iZXJGb3JtYXQgfSBmcm9tICcuLi8uLi8uLi9iZW0vdHMvc2VydmljZS9mb3JtYXR0ZXInO1xuaW1wb3J0IFJlYWN0LCB7IHVzZU1lbW8sIHVzZVN0YXRlIH0gZnJvbSAncmVhY3QnO1xuaW1wb3J0IFRyYW5zbGF0b3IgZnJvbSAnLi4vLi4vLi4vYmVtL3RzL3NlcnZpY2UvdHJhbnNsYXRvcic7XG5pbXBvcnQgY2xhc3NOYW1lcyBmcm9tICdjbGFzc25hbWVzJztcblxuZnVuY3Rpb24gR3JvdXBMaXN0KHByb3BzKSB7XG4gICAgY29uc3QgW2dyb3Vwcywgc2V0R3JvdXBzXSA9IHVzZVN0YXRlKHByb3BzLmdyb3Vwcyk7XG5cbiAgICByZXR1cm4gKFxuICAgICAgICA8ZGl2IGNsYXNzTmFtZT17J2ZsaWdodC1zZWFyY2gtd3JhcCd9PlxuICAgICAgICAgICAgPGRpdiBjbGFzc05hbWU9XCJmbGlnaHQtc2VhcmNoXCI+XG4gICAgICAgICAgICAgICAgezAgIT09IHByb3BzLmdyb3Vwcy5sZW5ndGhcbiAgICAgICAgICAgICAgICAgICAgPyA8TGlzdFJlc3VsdCBncm91cHM9e2dyb3Vwc30gc2t5TGluaz17cHJvcHMuc2t5TGlua30vPlxuICAgICAgICAgICAgICAgICAgICA6IChwcm9wcy5pc0Zvcm1GaWxsZWQgPyA8U2VhcmNoTm90Rm91bmQvPiA6ICcnKVxuICAgICAgICAgICAgICAgIH1cbiAgICAgICAgICAgIDwvZGl2PlxuICAgICAgICA8L2Rpdj5cbiAgICApO1xufVxuXG5jb25zdCBMaXN0UmVzdWx0ID0gUmVhY3QubWVtbyhmdW5jdGlvbiBMaXN0UmVzdWx0KHByb3BzKSB7XG4gICAgcmV0dXJuIChcbiAgICAgICAgPGRpdiBpZD1cInNlYXJjaFJlc3VsdFwiIGNsYXNzTmFtZT1cImZsaWdodC1zZWFyY2gtcmVzdWx0XCI+XG4gICAgICAgICAgICA8ZGl2IGNsYXNzTmFtZT1cImZsaWdodC1zZWFyY2gtd3JhcFwiPlxuICAgICAgICAgICAgICAgIDxkaXYgY2xhc3NOYW1lPVwiZmxpZ2h0LXNlYXJjaC1yZXN1bHQtY2FwdGlvblwiPlxuICAgICAgICAgICAgICAgICAgICA8ZGl2IGNsYXNzTmFtZT1cImZsaWdodC1zZWFyY2gtZGVwXCI+e1RyYW5zbGF0b3IudHJhbnMoJ2xveWFsdHktcHJvZ3JhbScpfTwvZGl2PlxuICAgICAgICAgICAgICAgICAgICA8ZGl2IGNsYXNzTmFtZT1cImZsaWdodC1zZWFyY2gtbGF5b3ZlclwiPlxuICAgICAgICAgICAgICAgICAgICAgICAge1RyYW5zbGF0b3IudHJhbnMoJ2xheW92ZXInLCB7fSwgJ3RyaXBzJyl9XG4gICAgICAgICAgICAgICAgICAgIDwvZGl2PlxuICAgICAgICAgICAgICAgICAgICA8ZGl2IGNsYXNzTmFtZT1cImZsaWdodC1zZWFyY2gtYXJyXCI+PC9kaXY+XG4gICAgICAgICAgICAgICAgICAgIDxkaXYgY2xhc3NOYW1lPVwiZmxpZ2h0LXNlYXJjaC1vcGVyYXRpbmdcIj5cbiAgICAgICAgICAgICAgICAgICAgICAgIHtUcmFuc2xhdG9yLnRyYW5zKCdpdGluZXJhcmllcy50cmlwLmFpci5haXJsaW5lLW5hbWUnLCB7fSwgJ3RyaXBzJyl9XG4gICAgICAgICAgICAgICAgICAgIDwvZGl2PlxuICAgICAgICAgICAgICAgICAgICA8ZGl2IGNsYXNzTmFtZT1cImZsaWdodC1zZWFyY2gtbWlsZXMtc3BlbnRcIlxuICAgICAgICAgICAgICAgICAgICAgICAgIHRpdGxlPVwiVG90YWxNaWxlc1NwZW50XCI+e1RyYW5zbGF0b3IudHJhbnMoJ3BvaW50cycpfTwvZGl2PlxuICAgICAgICAgICAgICAgICAgICA8ZGl2IGNsYXNzTmFtZT1cImZsaWdodC1zZWFyY2gtdGF4ZXNcIiB0aXRsZT1cIlRvdGFsVGF4ZXNTcGVudFwiPntUcmFuc2xhdG9yLnRyYW5zKCd0YXhlcycpfTwvZGl2PlxuICAgICAgICAgICAgICAgICAgICA8ZGl2IGNsYXNzTmFtZT1cImZsaWdodC1zZWFyY2gtYWx0Y29zdFwiIHRpdGxlPVwiQWx0ZXJuYXRpdmVDb3N0XCI+XG4gICAgICAgICAgICAgICAgICAgICAgICB7VHJhbnNsYXRvci50cmFucygnaXRpbmVyYXJpZXMuY29zdCcsIHt9LCAndHJpcHMnKX1cbiAgICAgICAgICAgICAgICAgICAgPC9kaXY+XG4gICAgICAgICAgICAgICAgICAgIDxkaXYgY2xhc3NOYW1lPVwiZmxpZ2h0LXNlYXJjaC1taWxlLXZhbHVlXCIgdGl0bGU9XCJNaWxlVmFsdWVcIj5cbiAgICAgICAgICAgICAgICAgICAgICAgIHtUcmFuc2xhdG9yLnRyYW5zKCdjb3Vwb24udmFsdWUnKX1cbiAgICAgICAgICAgICAgICAgICAgPC9kaXY+XG4gICAgICAgICAgICAgICAgICAgIDxkaXYgY2xhc3NOYW1lPVwiZmxpZ2h0LXNlYXJjaC1yZWR1Y2VcIj48L2Rpdj5cbiAgICAgICAgICAgICAgICAgICAgPGRpdiBjbGFzc05hbWU9XCJmbGlnaHQtc2VhcmNoLWRlYnVnLWlkXCI+aWQ8L2Rpdj5cbiAgICAgICAgICAgICAgICA8L2Rpdj5cbiAgICAgICAgICAgICAgICB7cHJvcHMuZ3JvdXBzLm1hcCgocHJvdmlkZXIsIGluZGV4KSA9PlxuICAgICAgICAgICAgICAgICAgICA8UHJvdmlkZXJTdGFjayBwcm92aWRlcj17cHJvdmlkZXJ9IGluZGV4PXtpbmRleH0ga2V5PXtwcm92aWRlci5wcm92aWRlcklkfSBza3lMaW5rPXtwcm9wcy5za3lMaW5rfS8+XG4gICAgICAgICAgICAgICAgKX1cbiAgICAgICAgICAgIDwvZGl2PlxuICAgICAgICA8L2Rpdj5cbiAgICApO1xufSk7XG5cbmNvbnN0IFByb3ZpZGVyU3RhY2sgPSBSZWFjdC5tZW1vKGZ1bmN0aW9uIFByb3ZpZGVyU3RhY2socHJvcHMpIHtcbiAgICBjb25zdCBwcm92aWRlciA9IHByb3BzLnByb3ZpZGVyO1xuICAgIGNvbnN0IFtpc0V4cGFuZGVkLCBzZXRFeHBhbmRlZF0gPSB1c2VTdGF0ZSgwID09PSBwcm9wcy5pbmRleCk7XG5cbiAgICByZXR1cm4gKFxuICAgICAgICA8ZGl2IGNsYXNzTmFtZT17Y2xhc3NOYW1lcyh7XG4gICAgICAgICAgICAnZmxpZ2h0LXNlYXJjaC1wcm92aWRlcic6IHRydWUsXG4gICAgICAgICAgICAnZmxpZ2h0LXNlYXJjaC1wcm92aWRlci0tZXhwYW5kZWQnOiBpc0V4cGFuZGVkLFxuICAgICAgICB9KX0+XG4gICAgICAgICAgICA8ZGl2IGNsYXNzTmFtZT1cImZsaWdodC1zZWFyY2gtaGVhZFwiPlxuICAgICAgICAgICAgICAgIDxkaXYgY2xhc3NOYW1lPVwiZmxpZ2h0LXNlYXJjaC1haXJsaW5lXCI+XG4gICAgICAgICAgICAgICAgICAgIDxhIGNsYXNzTmFtZT1cImZsaWdodC1zZWFyY2gtaXRlbXMtdG9nZ2xlXCIgaHJlZj1cIiNcIlxuICAgICAgICAgICAgICAgICAgICAgICBvbkNsaWNrPXt1c2VNZW1vKCgpID0+ICgpID0+IHNldEV4cGFuZGVkKCFpc0V4cGFuZGVkKSl9PlxuICAgICAgICAgICAgICAgICAgICAgICAgPGkgY2xhc3NOYW1lPVwiaWNvbi1hcnJvdy1yaWdodC1kYXJrXCI+PC9pPiB7cHJvdmlkZXIubmFtZX1cbiAgICAgICAgICAgICAgICAgICAgPC9hPlxuICAgICAgICAgICAgICAgIDwvZGl2PlxuICAgICAgICAgICAgICAgIDxkaXYgY2xhc3NOYW1lPVwiZmxpZ2h0LXNlYXJjaC1taWxlcy1zcGVudFwiPlxuICAgICAgICAgICAgICAgICAgICB7bnVtYmVyRm9ybWF0KHByb3ZpZGVyLmF2Zy5Ub3RhbE1pbGVzU3BlbnQpfVxuICAgICAgICAgICAgICAgIDwvZGl2PlxuICAgICAgICAgICAgICAgIDxkaXYgY2xhc3NOYW1lPVwiZmxpZ2h0LXNlYXJjaC10YXhlc1wiPlxuICAgICAgICAgICAgICAgICAgICB7Y3VycmVuY3lGb3JtYXQocHJvdmlkZXIuYXZnLlRvdGFsVGF4ZXNTcGVudCl9XG4gICAgICAgICAgICAgICAgPC9kaXY+XG4gICAgICAgICAgICAgICAgPGRpdiBjbGFzc05hbWU9XCJmbGlnaHQtc2VhcmNoLWFsdGNvc3RcIj5cbiAgICAgICAgICAgICAgICAgICAgPGEgaHJlZj17cHJvcHMuc2t5TGlua30gdGFyZ2V0PVwiX2JsYW5rXCIgcmVsPVwibm9yZWZlcnJlclwiPntjdXJyZW5jeUZvcm1hdChwcm92aWRlci5hdmcuQWx0ZXJuYXRpdmVDb3N0LCAnVVNEJywgeyBtYXhpbXVtRnJhY3Rpb25EaWdpdHM6IDAgfSl9PC9hPlxuICAgICAgICAgICAgICAgIDwvZGl2PlxuICAgICAgICAgICAgICAgIDxkaXYgY2xhc3NOYW1lPVwiZmxpZ2h0LXNlYXJjaC1taWxlLXZhbHVlXCI+XG4gICAgICAgICAgICAgICAgICAgIHtudW1iZXJGb3JtYXQocHJvdmlkZXIuYXZnLk1pbGVWYWx1ZSl9XG4gICAgICAgICAgICAgICAgICAgIHtUcmFuc2xhdG9yLnRyYW5zKCd1cy1jZW50LXN5bWJvbCcpfVxuICAgICAgICAgICAgICAgIDwvZGl2PlxuICAgICAgICAgICAgICAgIDxkaXYgY2xhc3NOYW1lPVwiZmxpZ2h0LXNlYXJjaC1yZWR1Y2VcIj48L2Rpdj5cbiAgICAgICAgICAgICAgICA8ZGl2IGNsYXNzTmFtZT1cImZsaWdodC1zZWFyY2gtZGVidWctaWRcIj48L2Rpdj5cbiAgICAgICAgICAgIDwvZGl2PlxuICAgICAgICAgICAgPExpc3RJdGVtcyBpdGVtcz17cHJvdmlkZXIuaXRlbXN9IHNreUxpbms9e3Byb3BzLnNreUxpbmt9IC8+XG4gICAgICAgIDwvZGl2PlxuICAgICk7XG59KTtcblxuY29uc3QgTGlzdEl0ZW1zID0gUmVhY3QubWVtbyhmdW5jdGlvbiBMaXN0SXRlbXMocHJvcHMpIHtcbiAgICByZXR1cm4gKFxuICAgICAgICA8ZGl2IGNsYXNzTmFtZT1cImZsaWdodC1zZWFyY2gtYm9keVwiPlxuICAgICAgICAgICAge3Byb3BzLml0ZW1zLm1hcChpdGVtID0+XG4gICAgICAgICAgICAgICAgPGRpdiBrZXk9e2Ake2l0ZW0uUHJvdmlkZXJJRH0tJHtpdGVtLk1pbGVSb3V0ZX1gfSBjbGFzc05hbWU9XCJmbGlnaHQtc2VhcmNoLWl0ZW1cIj5cbiAgICAgICAgICAgICAgICAgICAgPGRpdiBjbGFzc05hbWU9XCJmbGlnaHQtc2VhcmNoLWRlcFwiPlxuICAgICAgICAgICAgICAgICAgICAgICAgPGRpdiBjbGFzc05hbWU9XCJmbGlnaHQtc2VhcmNoLWxvY2F0aW9uXCI+XG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgPGRpdiBjbGFzc05hbWU9XCJmbGlnaHQtc2VhcmNoLWNvZGVcIj57aXRlbS5kZXAuY29kZX08L2Rpdj5cbiAgICAgICAgICAgICAgICAgICAgICAgICAgICA8ZGl2IGNsYXNzTmFtZT1cImZsaWdodC1zZWFyY2gtbmFtZVwiPntpdGVtLmRlcC5sb2NhdGlvbn08L2Rpdj5cbiAgICAgICAgICAgICAgICAgICAgICAgIDwvZGl2PlxuICAgICAgICAgICAgICAgICAgICA8L2Rpdj5cbiAgICAgICAgICAgICAgICAgICAgPGRpdiBjbGFzc05hbWU9XCJmbGlnaHQtc2VhcmNoLWxheW92ZXIgZmxpZ2h0LXNlYXJjaC1zdG9wc1wiPlxuICAgICAgICAgICAgICAgICAgICAgICAge2l0ZW0uc3RvcHMubWFwKHN0b3AgPT5cbiAgICAgICAgICAgICAgICAgICAgICAgICAgICA8ZGl2IGtleT17c3RvcC5jb2RlfSBjbGFzc05hbWU9XCJmbGlnaHQtc2VhcmNoLWxvY2F0aW9uXCI+XG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIDxkaXYgY2xhc3NOYW1lPVwiZmxpZ2h0LXNlYXJjaC1jb2RlXCI+e3N0b3AuY29kZX08L2Rpdj5cbiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgPGRpdiBjbGFzc05hbWU9XCJmbGlnaHQtc2VhcmNoLW5hbWVcIj57c3RvcC5sb2NhdGlvbn08L2Rpdj5cbiAgICAgICAgICAgICAgICAgICAgICAgICAgICA8L2Rpdj5cbiAgICAgICAgICAgICAgICAgICAgICAgICl9XG4gICAgICAgICAgICAgICAgICAgIDwvZGl2PlxuICAgICAgICAgICAgICAgICAgICA8ZGl2IGNsYXNzTmFtZT1cImZsaWdodC1zZWFyY2gtYXJyXCI+XG4gICAgICAgICAgICAgICAgICAgICAgICA8ZGl2IGNsYXNzTmFtZT1cImZsaWdodC1zZWFyY2gtbG9jYXRpb25cIj5cbiAgICAgICAgICAgICAgICAgICAgICAgICAgICA8ZGl2IGNsYXNzTmFtZT1cImZsaWdodC1zZWFyY2gtY29kZVwiPntpdGVtLmFyci5jb2RlfTwvZGl2PlxuICAgICAgICAgICAgICAgICAgICAgICAgICAgIDxkaXYgY2xhc3NOYW1lPVwiZmxpZ2h0LXNlYXJjaC1uYW1lXCI+e2l0ZW0uYXJyLmxvY2F0aW9ufTwvZGl2PlxuICAgICAgICAgICAgICAgICAgICAgICAgPC9kaXY+XG4gICAgICAgICAgICAgICAgICAgIDwvZGl2PlxuICAgICAgICAgICAgICAgICAgICA8ZGl2IGNsYXNzTmFtZT1cImZsaWdodC1zZWFyY2gtb3BlcmF0aW5nXCIgZGFuZ2Vyb3VzbHlTZXRJbm5lckhUTUw9e3sgX19odG1sOiBpdGVtLmFpcmxpbmUgfX0+PC9kaXY+XG4gICAgICAgICAgICAgICAgICAgIDxkaXYgY2xhc3NOYW1lPVwiZmxpZ2h0LXNlYXJjaC1taWxlcy1zcGVudFwiPntudW1iZXJGb3JtYXQoaXRlbS5Ub3RhbE1pbGVzU3BlbnQpfTwvZGl2PlxuICAgICAgICAgICAgICAgICAgICA8ZGl2IGNsYXNzTmFtZT1cImZsaWdodC1zZWFyY2gtdGF4ZXNcIj57Y3VycmVuY3lGb3JtYXQoaXRlbS5Ub3RhbFRheGVzU3BlbnQpfTwvZGl2PlxuICAgICAgICAgICAgICAgICAgICA8ZGl2IGNsYXNzTmFtZT1cImZsaWdodC1zZWFyY2gtYWx0Y29zdFwiPlxuICAgICAgICAgICAgICAgICAgICAgICAgPGEgaHJlZj17cHJvcHMuc2t5TGlua30gdGFyZ2V0PVwiX2JsYW5rXCIgcmVsPVwibm9yZWZlcnJlclwiPntjdXJyZW5jeUZvcm1hdChpdGVtLkFsdGVybmF0aXZlQ29zdCwgJ1VTRCcsIHsgbWF4aW11bUZyYWN0aW9uRGlnaXRzOiAwIH0pfTwvYT5cbiAgICAgICAgICAgICAgICAgICAgPC9kaXY+XG4gICAgICAgICAgICAgICAgICAgIDxkaXYgY2xhc3NOYW1lPVwiZmxpZ2h0LXNlYXJjaC1taWxlLXZhbHVlXCI+XG4gICAgICAgICAgICAgICAgICAgICAgICB7aXRlbS5NaWxlVmFsdWUucmF3fVxuICAgICAgICAgICAgICAgICAgICAgICAge1RyYW5zbGF0b3IudHJhbnMoJ3VzLWNlbnQtc3ltYm9sJyl9XG4gICAgICAgICAgICAgICAgICAgIDwvZGl2PlxuICAgICAgICAgICAgICAgICAgICA8ZGl2IGNsYXNzTmFtZT1cImZsaWdodC1zZWFyY2gtcmVkdWNlXCI+XG4gICAgICAgICAgICAgICAgICAgICAgICB7dW5kZWZpbmVkICE9PSBpdGVtLmFyci5yZWR1Y2VcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICA/IDxhIGNsYXNzTmFtZT1cImJ0bi1zaWx2ZXJcIlxuICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgaHJlZj17aXRlbS5hcnIucmVkdWNlLmxpbmt9PntUcmFuc2xhdG9yLnRyYW5zKCdzZWFyY2gnKX0ge2l0ZW0uYXJyLnJlZHVjZS5sb2NhdGlvbn08L2E+XG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgOiBudWxsXG4gICAgICAgICAgICAgICAgICAgICAgICB9XG4gICAgICAgICAgICAgICAgICAgIDwvZGl2PlxuICAgICAgICAgICAgICAgICAgICA8ZGl2IGNsYXNzTmFtZT1cImZsaWdodC1zZWFyY2gtZGVidWctaWRcIj5cbiAgICAgICAgICAgICAgICAgICAgICAgIHtpdGVtLl9kZWJ1Zy5NaWxlVmFsdWVJRC5tYXAoaWQgPT5cbiAgICAgICAgICAgICAgICAgICAgICAgICAgICA8ZGl2IGtleT17aWR9PlxuICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICA8YSBocmVmPXtgL21hbmFnZXIvbGlzdC5waHA/U2NoZW1hPU1pbGVWYWx1ZSZNaWxlVmFsdWVJRD1gICsgaWR9XG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIHRhcmdldD1cIm12XCI+e2lkfTwvYT5cbiAgICAgICAgICAgICAgICAgICAgICAgICAgICA8L2Rpdj5cbiAgICAgICAgICAgICAgICAgICAgICAgICl9XG4gICAgICAgICAgICAgICAgICAgIDwvZGl2PlxuICAgICAgICAgICAgICAgIDwvZGl2PlxuICAgICAgICAgICAgKX1cbiAgICAgICAgPC9kaXY+XG4gICAgKTtcbn0pO1xuXG5mdW5jdGlvbiBTZWFyY2hOb3RGb3VuZCgpIHtcbiAgICByZXR1cm4gKFxuICAgICAgICA8ZGl2IGNsYXNzTmFtZT1cInJvdXRlcy1ub3QtZm91bmRcIj5cbiAgICAgICAgICAgIDxkaXYgY2xhc3NOYW1lPVwiYWx0ZXJuYXRpdmUtcGF0aFwiPlxuICAgICAgICAgICAgICAgIDxpIGNsYXNzTmFtZT1cImljb24td2FybmluZy1zbWFsbFwiPjwvaT5cbiAgICAgICAgICAgICAgICA8cCBkYW5nZXJvdXNseVNldElubmVySFRNTD17eyBfX2h0bWw6IFRyYW5zbGF0b3IudHJhbnMoJ3dlLW5vdC1maW5kLWFueS1yZXN1bHQnLCB7ICdicmVhayc6ICc8YnIvPicgfSkgfX0+PC9wPlxuICAgICAgICAgICAgPC9kaXY+XG4gICAgICAgIDwvZGl2PlxuICAgICk7XG59XG5cbmV4cG9ydCBkZWZhdWx0IEdyb3VwTGlzdDsiLCJpbXBvcnQgeyByZW5kZXIgfSBmcm9tICdyZWFjdC1kb20nO1xuaW1wb3J0IFJlYWN0IGZyb20gJ3JlYWN0JztcblxuaW1wb3J0ICcuL0ZsaWdodFNlYXJjaC5sZXNzJztcbmltcG9ydCBGbGlnaHRTZWFyY2ggZnJvbSAnLi9GbGlnaHRTZWFyY2gnO1xuXG4oYXN5bmMgKCkgPT4ge1xuICAgIGF3YWl0IGltcG9ydCgnLi4vLi4vLi4vYmVtL3RzL3N0YXJ0ZXInKTtcblxuICAgIGNvbnN0IHJvb3QgPSBkb2N1bWVudC5nZXRFbGVtZW50QnlJZCgnY29udGVudCcpO1xuICAgIGNvbnN0IGRhdGEgPSBKU09OLnBhcnNlKGRvY3VtZW50LmdldEVsZW1lbnRCeUlkKCdkYXRhJykudGV4dENvbnRlbnQpO1xuXG4gICAgcmVuZGVyKFxuICAgICAgICA8UmVhY3QuU3RyaWN0TW9kZT5cbiAgICAgICAgICAgIDxGbGlnaHRTZWFyY2ggZGF0YT17ZGF0YX0vPlxuICAgICAgICA8L1JlYWN0LlN0cmljdE1vZGU+LFxuICAgICAgICByb290XG4gICAgKTtcblxufSkoKTsiLCJleHBvcnQgZnVuY3Rpb24gZXh0cmFjdE9wdGlvbnMoKSB7XG4gICAgY29uc3QgZW52ID0gZG9jdW1lbnQuYm9keS5kYXRhc2V0O1xuICAgIGNvbnN0IGRlZmF1bHRMYW5nID0gJ2VuJztcbiAgICBjb25zdCBkZWZhdWx0TG9jYWxlID0gJ2VuJztcbiAgICBlbnYubG9jYWxlO1xuICAgIGNvbnN0IGFwcExvY2FsZSA9IGVudi5sb2NhbGU/LnJlcGxhY2UoJ18nLCAnLScpIHx8IGRlZmF1bHRMb2NhbGU7XG4gICAgY29uc3QgcmVzdWx0ID0ge1xuICAgICAgICBkZWZhdWx0TGFuZzogZGVmYXVsdExhbmcsXG4gICAgICAgIGRlZmF1bHRMb2NhbGU6IGRlZmF1bHRMb2NhbGUsXG4gICAgICAgIGF1dGhvcml6ZWQ6IGVudi5hdXRob3JpemVkID09PSAndHJ1ZScsXG4gICAgICAgIGJvb2tpbmc6IGVudi5ib29raW5nID09PSAndHJ1ZScsXG4gICAgICAgIGJ1c2luZXNzOiBlbnYuYnVzaW5lc3MgPT09ICd0cnVlJyxcbiAgICAgICAgZGVidWc6IGVudi5kZWJ1ZyA9PT0gJ3RydWUnLFxuICAgICAgICBlbmFibGVkVHJhbnNIZWxwZXI6IGVudi5lbmFibGVkVHJhbnNIZWxwZXIgPT09ICd0cnVlJyxcbiAgICAgICAgaGFzUm9sZVRyYW5zbGF0b3I6IGVudi5yb2xlVHJhbnNsYXRvciA9PT0gJ3RydWUnLFxuICAgICAgICBpbXBlcnNvbmF0ZWQ6IGVudi5pbXBlcnNvbmF0ZWQgPT09ICd0cnVlJyxcbiAgICAgICAgbGFuZzogZW52LmxhbmcgfHwgZGVmYXVsdExhbmcsXG4gICAgICAgIGxvY2FsZTogYXBwTG9jYWxlLFxuICAgICAgICBsb2FkRXh0ZXJuYWxTY3JpcHRzOiBlbnYubG9hZEV4dGVybmFsU2NyaXB0cyB8fCBmYWxzZSxcbiAgICB9O1xuICAgIGlmIChlbnYudGhlbWUpIHtcbiAgICAgICAgcmVzdWx0LnRoZW1lID0gZW52LnRoZW1lO1xuICAgIH1cbiAgICByZXR1cm4gcmVzdWx0O1xufVxuZXhwb3J0IGZ1bmN0aW9uIGlzSW9zKCkge1xuICAgIHJldHVybiAvaVBhZHxpUGhvbmV8aVBvZC9pLnRlc3QobmF2aWdhdG9yLnVzZXJBZ2VudCk7XG59XG5leHBvcnQgZnVuY3Rpb24gaXNBbmRyb2lkKCkge1xuICAgIHJldHVybiAvYW5kcm9pZC9pLnRlc3QobmF2aWdhdG9yLnVzZXJBZ2VudC50b0xvd2VyQ2FzZSgpKTtcbn1cbiIsImltcG9ydCB7IGV4dHJhY3RPcHRpb25zIH0gZnJvbSAnLi9lbnYnO1xuZXhwb3J0IGZ1bmN0aW9uIG51bWJlckZvcm1hdCh2YWx1ZSkge1xuICAgIHJldHVybiBuZXcgSW50bC5OdW1iZXJGb3JtYXQoZXh0cmFjdE9wdGlvbnMoKS5sb2NhbGUucmVwbGFjZSgnXycsICctJykpXG4gICAgICAgIC5mb3JtYXQodmFsdWUpO1xufVxuZXhwb3J0IGZ1bmN0aW9uIGN1cnJlbmN5Rm9ybWF0KHZhbHVlLCBjdXJyZW5jeSA9ICdVU0QnLCBvcHRpb25zID0ge30pIHtcbiAgICByZXR1cm4gbmV3IEludGwuTnVtYmVyRm9ybWF0KGV4dHJhY3RPcHRpb25zKCkubG9jYWxlLnJlcGxhY2UoJ18nLCAnLScpLCBPYmplY3QuYXNzaWduKHtcbiAgICAgICAgc3R5bGU6ICdjdXJyZW5jeScsXG4gICAgICAgIGN1cnJlbmN5OiBjdXJyZW5jeSxcbiAgICB9LCBvcHRpb25zKSkuZm9ybWF0KHZhbHVlKTtcbn1cbmV4cG9ydCBmdW5jdGlvbiBmb3JtYXRGaWxlU2l6ZShieXRlcywgZHAgPSAxKSB7XG4gICAgY29uc3QgdGhyZXNoID0gMTAyNDtcbiAgICBpZiAoTWF0aC5hYnMoYnl0ZXMpIDwgdGhyZXNoKSB7XG4gICAgICAgIHJldHVybiBieXRlcy50b1N0cmluZygpICsgJyBCJztcbiAgICB9XG4gICAgY29uc3QgdW5pdHMgPSBbJ2tCJywgJ01CJywgJ0dCJywgJ1RCJywgJ1BCJywgJ0VCJywgJ1pCJywgJ1lCJ107XG4gICAgY29uc3QgciA9IDEwICoqIGRwO1xuICAgIGxldCB1ID0gLTE7XG4gICAgZG8ge1xuICAgICAgICBieXRlcyAvPSB0aHJlc2g7XG4gICAgICAgICsrdTtcbiAgICB9IHdoaWxlIChNYXRoLnJvdW5kKE1hdGguYWJzKGJ5dGVzKSAqIHIpIC8gciA+PSB0aHJlc2ggJiYgdSA8IHVuaXRzLmxlbmd0aCAtIDEpO1xuICAgIHJldHVybiBieXRlcy50b0ZpeGVkKGRwKSArICcgJyArIHVuaXRzW3VdO1xufVxuIiwiLyohXG5cdENvcHlyaWdodCAoYykgMjAxOCBKZWQgV2F0c29uLlxuXHRMaWNlbnNlZCB1bmRlciB0aGUgTUlUIExpY2Vuc2UgKE1JVCksIHNlZVxuXHRodHRwOi8vamVkd2F0c29uLmdpdGh1Yi5pby9jbGFzc25hbWVzXG4qL1xuLyogZ2xvYmFsIGRlZmluZSAqL1xuXG4oZnVuY3Rpb24gKCkge1xuXHQndXNlIHN0cmljdCc7XG5cblx0dmFyIGhhc093biA9IHt9Lmhhc093blByb3BlcnR5O1xuXHR2YXIgbmF0aXZlQ29kZVN0cmluZyA9ICdbbmF0aXZlIGNvZGVdJztcblxuXHRmdW5jdGlvbiBjbGFzc05hbWVzKCkge1xuXHRcdHZhciBjbGFzc2VzID0gW107XG5cblx0XHRmb3IgKHZhciBpID0gMDsgaSA8IGFyZ3VtZW50cy5sZW5ndGg7IGkrKykge1xuXHRcdFx0dmFyIGFyZyA9IGFyZ3VtZW50c1tpXTtcblx0XHRcdGlmICghYXJnKSBjb250aW51ZTtcblxuXHRcdFx0dmFyIGFyZ1R5cGUgPSB0eXBlb2YgYXJnO1xuXG5cdFx0XHRpZiAoYXJnVHlwZSA9PT0gJ3N0cmluZycgfHwgYXJnVHlwZSA9PT0gJ251bWJlcicpIHtcblx0XHRcdFx0Y2xhc3Nlcy5wdXNoKGFyZyk7XG5cdFx0XHR9IGVsc2UgaWYgKEFycmF5LmlzQXJyYXkoYXJnKSkge1xuXHRcdFx0XHRpZiAoYXJnLmxlbmd0aCkge1xuXHRcdFx0XHRcdHZhciBpbm5lciA9IGNsYXNzTmFtZXMuYXBwbHkobnVsbCwgYXJnKTtcblx0XHRcdFx0XHRpZiAoaW5uZXIpIHtcblx0XHRcdFx0XHRcdGNsYXNzZXMucHVzaChpbm5lcik7XG5cdFx0XHRcdFx0fVxuXHRcdFx0XHR9XG5cdFx0XHR9IGVsc2UgaWYgKGFyZ1R5cGUgPT09ICdvYmplY3QnKSB7XG5cdFx0XHRcdGlmIChhcmcudG9TdHJpbmcgIT09IE9iamVjdC5wcm90b3R5cGUudG9TdHJpbmcgJiYgIWFyZy50b1N0cmluZy50b1N0cmluZygpLmluY2x1ZGVzKCdbbmF0aXZlIGNvZGVdJykpIHtcblx0XHRcdFx0XHRjbGFzc2VzLnB1c2goYXJnLnRvU3RyaW5nKCkpO1xuXHRcdFx0XHRcdGNvbnRpbnVlO1xuXHRcdFx0XHR9XG5cblx0XHRcdFx0Zm9yICh2YXIga2V5IGluIGFyZykge1xuXHRcdFx0XHRcdGlmIChoYXNPd24uY2FsbChhcmcsIGtleSkgJiYgYXJnW2tleV0pIHtcblx0XHRcdFx0XHRcdGNsYXNzZXMucHVzaChrZXkpO1xuXHRcdFx0XHRcdH1cblx0XHRcdFx0fVxuXHRcdFx0fVxuXHRcdH1cblxuXHRcdHJldHVybiBjbGFzc2VzLmpvaW4oJyAnKTtcblx0fVxuXG5cdGlmICh0eXBlb2YgbW9kdWxlICE9PSAndW5kZWZpbmVkJyAmJiBtb2R1bGUuZXhwb3J0cykge1xuXHRcdGNsYXNzTmFtZXMuZGVmYXVsdCA9IGNsYXNzTmFtZXM7XG5cdFx0bW9kdWxlLmV4cG9ydHMgPSBjbGFzc05hbWVzO1xuXHR9IGVsc2UgaWYgKHR5cGVvZiBkZWZpbmUgPT09ICdmdW5jdGlvbicgJiYgdHlwZW9mIGRlZmluZS5hbWQgPT09ICdvYmplY3QnICYmIGRlZmluZS5hbWQpIHtcblx0XHQvLyByZWdpc3RlciBhcyAnY2xhc3NuYW1lcycsIGNvbnNpc3RlbnQgd2l0aCBucG0gcGFja2FnZSBuYW1lXG5cdFx0ZGVmaW5lKCdjbGFzc25hbWVzJywgW10sIGZ1bmN0aW9uICgpIHtcblx0XHRcdHJldHVybiBjbGFzc05hbWVzO1xuXHRcdH0pO1xuXHR9IGVsc2Uge1xuXHRcdHdpbmRvdy5jbGFzc05hbWVzID0gY2xhc3NOYW1lcztcblx0fVxufSgpKTtcbiIsIid1c2Ugc3RyaWN0JztcbnZhciB1bmN1cnJ5VGhpcyA9IHJlcXVpcmUoJy4uL2ludGVybmFscy9mdW5jdGlvbi11bmN1cnJ5LXRoaXMnKTtcbnZhciByZXF1aXJlT2JqZWN0Q29lcmNpYmxlID0gcmVxdWlyZSgnLi4vaW50ZXJuYWxzL3JlcXVpcmUtb2JqZWN0LWNvZXJjaWJsZScpO1xudmFyIHRvU3RyaW5nID0gcmVxdWlyZSgnLi4vaW50ZXJuYWxzL3RvLXN0cmluZycpO1xuXG52YXIgcXVvdCA9IC9cIi9nO1xudmFyIHJlcGxhY2UgPSB1bmN1cnJ5VGhpcygnJy5yZXBsYWNlKTtcblxuLy8gYENyZWF0ZUhUTUxgIGFic3RyYWN0IG9wZXJhdGlvblxuLy8gaHR0cHM6Ly90YzM5LmVzL2VjbWEyNjIvI3NlYy1jcmVhdGVodG1sXG5tb2R1bGUuZXhwb3J0cyA9IGZ1bmN0aW9uIChzdHJpbmcsIHRhZywgYXR0cmlidXRlLCB2YWx1ZSkge1xuICB2YXIgUyA9IHRvU3RyaW5nKHJlcXVpcmVPYmplY3RDb2VyY2libGUoc3RyaW5nKSk7XG4gIHZhciBwMSA9ICc8JyArIHRhZztcbiAgaWYgKGF0dHJpYnV0ZSAhPT0gJycpIHAxICs9ICcgJyArIGF0dHJpYnV0ZSArICc9XCInICsgcmVwbGFjZSh0b1N0cmluZyh2YWx1ZSksIHF1b3QsICcmcXVvdDsnKSArICdcIic7XG4gIHJldHVybiBwMSArICc+JyArIFMgKyAnPC8nICsgdGFnICsgJz4nO1xufTtcbiIsIid1c2Ugc3RyaWN0JztcbnZhciBERVNDUklQVE9SUyA9IHJlcXVpcmUoJy4uL2ludGVybmFscy9kZXNjcmlwdG9ycycpO1xudmFyIGZhaWxzID0gcmVxdWlyZSgnLi4vaW50ZXJuYWxzL2ZhaWxzJyk7XG52YXIgdW5jdXJyeVRoaXMgPSByZXF1aXJlKCcuLi9pbnRlcm5hbHMvZnVuY3Rpb24tdW5jdXJyeS10aGlzJyk7XG52YXIgb2JqZWN0R2V0UHJvdG90eXBlT2YgPSByZXF1aXJlKCcuLi9pbnRlcm5hbHMvb2JqZWN0LWdldC1wcm90b3R5cGUtb2YnKTtcbnZhciBvYmplY3RLZXlzID0gcmVxdWlyZSgnLi4vaW50ZXJuYWxzL29iamVjdC1rZXlzJyk7XG52YXIgdG9JbmRleGVkT2JqZWN0ID0gcmVxdWlyZSgnLi4vaW50ZXJuYWxzL3RvLWluZGV4ZWQtb2JqZWN0Jyk7XG52YXIgJHByb3BlcnR5SXNFbnVtZXJhYmxlID0gcmVxdWlyZSgnLi4vaW50ZXJuYWxzL29iamVjdC1wcm9wZXJ0eS1pcy1lbnVtZXJhYmxlJykuZjtcblxudmFyIHByb3BlcnR5SXNFbnVtZXJhYmxlID0gdW5jdXJyeVRoaXMoJHByb3BlcnR5SXNFbnVtZXJhYmxlKTtcbnZhciBwdXNoID0gdW5jdXJyeVRoaXMoW10ucHVzaCk7XG5cbi8vIGluIHNvbWUgSUUgdmVyc2lvbnMsIGBwcm9wZXJ0eUlzRW51bWVyYWJsZWAgcmV0dXJucyBpbmNvcnJlY3QgcmVzdWx0IG9uIGludGVnZXIga2V5c1xuLy8gb2YgYG51bGxgIHByb3RvdHlwZSBvYmplY3RzXG52YXIgSUVfQlVHID0gREVTQ1JJUFRPUlMgJiYgZmFpbHMoZnVuY3Rpb24gKCkge1xuICAvLyBlc2xpbnQtZGlzYWJsZS1uZXh0LWxpbmUgZXMvbm8tb2JqZWN0LWNyZWF0ZSAtLSBzYWZlXG4gIHZhciBPID0gT2JqZWN0LmNyZWF0ZShudWxsKTtcbiAgT1syXSA9IDI7XG4gIHJldHVybiAhcHJvcGVydHlJc0VudW1lcmFibGUoTywgMik7XG59KTtcblxuLy8gYE9iamVjdC57IGVudHJpZXMsIHZhbHVlcyB9YCBtZXRob2RzIGltcGxlbWVudGF0aW9uXG52YXIgY3JlYXRlTWV0aG9kID0gZnVuY3Rpb24gKFRPX0VOVFJJRVMpIHtcbiAgcmV0dXJuIGZ1bmN0aW9uIChpdCkge1xuICAgIHZhciBPID0gdG9JbmRleGVkT2JqZWN0KGl0KTtcbiAgICB2YXIga2V5cyA9IG9iamVjdEtleXMoTyk7XG4gICAgdmFyIElFX1dPUktBUk9VTkQgPSBJRV9CVUcgJiYgb2JqZWN0R2V0UHJvdG90eXBlT2YoTykgPT09IG51bGw7XG4gICAgdmFyIGxlbmd0aCA9IGtleXMubGVuZ3RoO1xuICAgIHZhciBpID0gMDtcbiAgICB2YXIgcmVzdWx0ID0gW107XG4gICAgdmFyIGtleTtcbiAgICB3aGlsZSAobGVuZ3RoID4gaSkge1xuICAgICAga2V5ID0ga2V5c1tpKytdO1xuICAgICAgaWYgKCFERVNDUklQVE9SUyB8fCAoSUVfV09SS0FST1VORCA/IGtleSBpbiBPIDogcHJvcGVydHlJc0VudW1lcmFibGUoTywga2V5KSkpIHtcbiAgICAgICAgcHVzaChyZXN1bHQsIFRPX0VOVFJJRVMgPyBba2V5LCBPW2tleV1dIDogT1trZXldKTtcbiAgICAgIH1cbiAgICB9XG4gICAgcmV0dXJuIHJlc3VsdDtcbiAgfTtcbn07XG5cbm1vZHVsZS5leHBvcnRzID0ge1xuICAvLyBgT2JqZWN0LmVudHJpZXNgIG1ldGhvZFxuICAvLyBodHRwczovL3RjMzkuZXMvZWNtYTI2Mi8jc2VjLW9iamVjdC5lbnRyaWVzXG4gIGVudHJpZXM6IGNyZWF0ZU1ldGhvZCh0cnVlKSxcbiAgLy8gYE9iamVjdC52YWx1ZXNgIG1ldGhvZFxuICAvLyBodHRwczovL3RjMzkuZXMvZWNtYTI2Mi8jc2VjLW9iamVjdC52YWx1ZXNcbiAgdmFsdWVzOiBjcmVhdGVNZXRob2QoZmFsc2UpXG59O1xuIiwiJ3VzZSBzdHJpY3QnO1xudmFyIGZhaWxzID0gcmVxdWlyZSgnLi4vaW50ZXJuYWxzL2ZhaWxzJyk7XG5cbi8vIGNoZWNrIHRoZSBleGlzdGVuY2Ugb2YgYSBtZXRob2QsIGxvd2VyY2FzZVxuLy8gb2YgYSB0YWcgYW5kIGVzY2FwaW5nIHF1b3RlcyBpbiBhcmd1bWVudHNcbm1vZHVsZS5leHBvcnRzID0gZnVuY3Rpb24gKE1FVEhPRF9OQU1FKSB7XG4gIHJldHVybiBmYWlscyhmdW5jdGlvbiAoKSB7XG4gICAgdmFyIHRlc3QgPSAnJ1tNRVRIT0RfTkFNRV0oJ1wiJyk7XG4gICAgcmV0dXJuIHRlc3QgIT09IHRlc3QudG9Mb3dlckNhc2UoKSB8fCB0ZXN0LnNwbGl0KCdcIicpLmxlbmd0aCA+IDM7XG4gIH0pO1xufTtcbiIsIid1c2Ugc3RyaWN0JztcbnZhciB0b0ludGVnZXJPckluZmluaXR5ID0gcmVxdWlyZSgnLi4vaW50ZXJuYWxzL3RvLWludGVnZXItb3ItaW5maW5pdHknKTtcbnZhciB0b1N0cmluZyA9IHJlcXVpcmUoJy4uL2ludGVybmFscy90by1zdHJpbmcnKTtcbnZhciByZXF1aXJlT2JqZWN0Q29lcmNpYmxlID0gcmVxdWlyZSgnLi4vaW50ZXJuYWxzL3JlcXVpcmUtb2JqZWN0LWNvZXJjaWJsZScpO1xuXG52YXIgJFJhbmdlRXJyb3IgPSBSYW5nZUVycm9yO1xuXG4vLyBgU3RyaW5nLnByb3RvdHlwZS5yZXBlYXRgIG1ldGhvZCBpbXBsZW1lbnRhdGlvblxuLy8gaHR0cHM6Ly90YzM5LmVzL2VjbWEyNjIvI3NlYy1zdHJpbmcucHJvdG90eXBlLnJlcGVhdFxubW9kdWxlLmV4cG9ydHMgPSBmdW5jdGlvbiByZXBlYXQoY291bnQpIHtcbiAgdmFyIHN0ciA9IHRvU3RyaW5nKHJlcXVpcmVPYmplY3RDb2VyY2libGUodGhpcykpO1xuICB2YXIgcmVzdWx0ID0gJyc7XG4gIHZhciBuID0gdG9JbnRlZ2VyT3JJbmZpbml0eShjb3VudCk7XG4gIGlmIChuIDwgMCB8fCBuID09PSBJbmZpbml0eSkgdGhyb3cgJFJhbmdlRXJyb3IoJ1dyb25nIG51bWJlciBvZiByZXBldGl0aW9ucycpO1xuICBmb3IgKDtuID4gMDsgKG4gPj4+PSAxKSAmJiAoc3RyICs9IHN0cikpIGlmIChuICYgMSkgcmVzdWx0ICs9IHN0cjtcbiAgcmV0dXJuIHJlc3VsdDtcbn07XG4iLCIndXNlIHN0cmljdCc7XG52YXIgJCA9IHJlcXVpcmUoJy4uL2ludGVybmFscy9leHBvcnQnKTtcbnZhciB1bmN1cnJ5VGhpcyA9IHJlcXVpcmUoJy4uL2ludGVybmFscy9mdW5jdGlvbi11bmN1cnJ5LXRoaXMnKTtcbnZhciB0b0ludGVnZXJPckluZmluaXR5ID0gcmVxdWlyZSgnLi4vaW50ZXJuYWxzL3RvLWludGVnZXItb3ItaW5maW5pdHknKTtcbnZhciB0aGlzTnVtYmVyVmFsdWUgPSByZXF1aXJlKCcuLi9pbnRlcm5hbHMvdGhpcy1udW1iZXItdmFsdWUnKTtcbnZhciAkcmVwZWF0ID0gcmVxdWlyZSgnLi4vaW50ZXJuYWxzL3N0cmluZy1yZXBlYXQnKTtcbnZhciBmYWlscyA9IHJlcXVpcmUoJy4uL2ludGVybmFscy9mYWlscycpO1xuXG52YXIgJFJhbmdlRXJyb3IgPSBSYW5nZUVycm9yO1xudmFyICRTdHJpbmcgPSBTdHJpbmc7XG52YXIgZmxvb3IgPSBNYXRoLmZsb29yO1xudmFyIHJlcGVhdCA9IHVuY3VycnlUaGlzKCRyZXBlYXQpO1xudmFyIHN0cmluZ1NsaWNlID0gdW5jdXJyeVRoaXMoJycuc2xpY2UpO1xudmFyIG5hdGl2ZVRvRml4ZWQgPSB1bmN1cnJ5VGhpcygxLjAudG9GaXhlZCk7XG5cbnZhciBwb3cgPSBmdW5jdGlvbiAoeCwgbiwgYWNjKSB7XG4gIHJldHVybiBuID09PSAwID8gYWNjIDogbiAlIDIgPT09IDEgPyBwb3coeCwgbiAtIDEsIGFjYyAqIHgpIDogcG93KHggKiB4LCBuIC8gMiwgYWNjKTtcbn07XG5cbnZhciBsb2cgPSBmdW5jdGlvbiAoeCkge1xuICB2YXIgbiA9IDA7XG4gIHZhciB4MiA9IHg7XG4gIHdoaWxlICh4MiA+PSA0MDk2KSB7XG4gICAgbiArPSAxMjtcbiAgICB4MiAvPSA0MDk2O1xuICB9XG4gIHdoaWxlICh4MiA+PSAyKSB7XG4gICAgbiArPSAxO1xuICAgIHgyIC89IDI7XG4gIH0gcmV0dXJuIG47XG59O1xuXG52YXIgbXVsdGlwbHkgPSBmdW5jdGlvbiAoZGF0YSwgbiwgYykge1xuICB2YXIgaW5kZXggPSAtMTtcbiAgdmFyIGMyID0gYztcbiAgd2hpbGUgKCsraW5kZXggPCA2KSB7XG4gICAgYzIgKz0gbiAqIGRhdGFbaW5kZXhdO1xuICAgIGRhdGFbaW5kZXhdID0gYzIgJSAxZTc7XG4gICAgYzIgPSBmbG9vcihjMiAvIDFlNyk7XG4gIH1cbn07XG5cbnZhciBkaXZpZGUgPSBmdW5jdGlvbiAoZGF0YSwgbikge1xuICB2YXIgaW5kZXggPSA2O1xuICB2YXIgYyA9IDA7XG4gIHdoaWxlICgtLWluZGV4ID49IDApIHtcbiAgICBjICs9IGRhdGFbaW5kZXhdO1xuICAgIGRhdGFbaW5kZXhdID0gZmxvb3IoYyAvIG4pO1xuICAgIGMgPSAoYyAlIG4pICogMWU3O1xuICB9XG59O1xuXG52YXIgZGF0YVRvU3RyaW5nID0gZnVuY3Rpb24gKGRhdGEpIHtcbiAgdmFyIGluZGV4ID0gNjtcbiAgdmFyIHMgPSAnJztcbiAgd2hpbGUgKC0taW5kZXggPj0gMCkge1xuICAgIGlmIChzICE9PSAnJyB8fCBpbmRleCA9PT0gMCB8fCBkYXRhW2luZGV4XSAhPT0gMCkge1xuICAgICAgdmFyIHQgPSAkU3RyaW5nKGRhdGFbaW5kZXhdKTtcbiAgICAgIHMgPSBzID09PSAnJyA/IHQgOiBzICsgcmVwZWF0KCcwJywgNyAtIHQubGVuZ3RoKSArIHQ7XG4gICAgfVxuICB9IHJldHVybiBzO1xufTtcblxudmFyIEZPUkNFRCA9IGZhaWxzKGZ1bmN0aW9uICgpIHtcbiAgcmV0dXJuIG5hdGl2ZVRvRml4ZWQoMC4wMDAwOCwgMykgIT09ICcwLjAwMCcgfHxcbiAgICBuYXRpdmVUb0ZpeGVkKDAuOSwgMCkgIT09ICcxJyB8fFxuICAgIG5hdGl2ZVRvRml4ZWQoMS4yNTUsIDIpICE9PSAnMS4yNScgfHxcbiAgICBuYXRpdmVUb0ZpeGVkKDEwMDAwMDAwMDAwMDAwMDAxMjguMCwgMCkgIT09ICcxMDAwMDAwMDAwMDAwMDAwMTI4Jztcbn0pIHx8ICFmYWlscyhmdW5jdGlvbiAoKSB7XG4gIC8vIFY4IH4gQW5kcm9pZCA0LjMtXG4gIG5hdGl2ZVRvRml4ZWQoe30pO1xufSk7XG5cbi8vIGBOdW1iZXIucHJvdG90eXBlLnRvRml4ZWRgIG1ldGhvZFxuLy8gaHR0cHM6Ly90YzM5LmVzL2VjbWEyNjIvI3NlYy1udW1iZXIucHJvdG90eXBlLnRvZml4ZWRcbiQoeyB0YXJnZXQ6ICdOdW1iZXInLCBwcm90bzogdHJ1ZSwgZm9yY2VkOiBGT1JDRUQgfSwge1xuICB0b0ZpeGVkOiBmdW5jdGlvbiB0b0ZpeGVkKGZyYWN0aW9uRGlnaXRzKSB7XG4gICAgdmFyIG51bWJlciA9IHRoaXNOdW1iZXJWYWx1ZSh0aGlzKTtcbiAgICB2YXIgZnJhY3REaWdpdHMgPSB0b0ludGVnZXJPckluZmluaXR5KGZyYWN0aW9uRGlnaXRzKTtcbiAgICB2YXIgZGF0YSA9IFswLCAwLCAwLCAwLCAwLCAwXTtcbiAgICB2YXIgc2lnbiA9ICcnO1xuICAgIHZhciByZXN1bHQgPSAnMCc7XG4gICAgdmFyIGUsIHosIGosIGs7XG5cbiAgICAvLyBUT0RPOiBFUzIwMTggaW5jcmVhc2VkIHRoZSBtYXhpbXVtIG51bWJlciBvZiBmcmFjdGlvbiBkaWdpdHMgdG8gMTAwLCBuZWVkIHRvIGltcHJvdmUgdGhlIGltcGxlbWVudGF0aW9uXG4gICAgaWYgKGZyYWN0RGlnaXRzIDwgMCB8fCBmcmFjdERpZ2l0cyA+IDIwKSB0aHJvdyAkUmFuZ2VFcnJvcignSW5jb3JyZWN0IGZyYWN0aW9uIGRpZ2l0cycpO1xuICAgIC8vIGVzbGludC1kaXNhYmxlLW5leHQtbGluZSBuby1zZWxmLWNvbXBhcmUgLS0gTmFOIGNoZWNrXG4gICAgaWYgKG51bWJlciAhPT0gbnVtYmVyKSByZXR1cm4gJ05hTic7XG4gICAgaWYgKG51bWJlciA8PSAtMWUyMSB8fCBudW1iZXIgPj0gMWUyMSkgcmV0dXJuICRTdHJpbmcobnVtYmVyKTtcbiAgICBpZiAobnVtYmVyIDwgMCkge1xuICAgICAgc2lnbiA9ICctJztcbiAgICAgIG51bWJlciA9IC1udW1iZXI7XG4gICAgfVxuICAgIGlmIChudW1iZXIgPiAxZS0yMSkge1xuICAgICAgZSA9IGxvZyhudW1iZXIgKiBwb3coMiwgNjksIDEpKSAtIDY5O1xuICAgICAgeiA9IGUgPCAwID8gbnVtYmVyICogcG93KDIsIC1lLCAxKSA6IG51bWJlciAvIHBvdygyLCBlLCAxKTtcbiAgICAgIHogKj0gMHgxMDAwMDAwMDAwMDAwMDtcbiAgICAgIGUgPSA1MiAtIGU7XG4gICAgICBpZiAoZSA+IDApIHtcbiAgICAgICAgbXVsdGlwbHkoZGF0YSwgMCwgeik7XG4gICAgICAgIGogPSBmcmFjdERpZ2l0cztcbiAgICAgICAgd2hpbGUgKGogPj0gNykge1xuICAgICAgICAgIG11bHRpcGx5KGRhdGEsIDFlNywgMCk7XG4gICAgICAgICAgaiAtPSA3O1xuICAgICAgICB9XG4gICAgICAgIG11bHRpcGx5KGRhdGEsIHBvdygxMCwgaiwgMSksIDApO1xuICAgICAgICBqID0gZSAtIDE7XG4gICAgICAgIHdoaWxlIChqID49IDIzKSB7XG4gICAgICAgICAgZGl2aWRlKGRhdGEsIDEgPDwgMjMpO1xuICAgICAgICAgIGogLT0gMjM7XG4gICAgICAgIH1cbiAgICAgICAgZGl2aWRlKGRhdGEsIDEgPDwgaik7XG4gICAgICAgIG11bHRpcGx5KGRhdGEsIDEsIDEpO1xuICAgICAgICBkaXZpZGUoZGF0YSwgMik7XG4gICAgICAgIHJlc3VsdCA9IGRhdGFUb1N0cmluZyhkYXRhKTtcbiAgICAgIH0gZWxzZSB7XG4gICAgICAgIG11bHRpcGx5KGRhdGEsIDAsIHopO1xuICAgICAgICBtdWx0aXBseShkYXRhLCAxIDw8IC1lLCAwKTtcbiAgICAgICAgcmVzdWx0ID0gZGF0YVRvU3RyaW5nKGRhdGEpICsgcmVwZWF0KCcwJywgZnJhY3REaWdpdHMpO1xuICAgICAgfVxuICAgIH1cbiAgICBpZiAoZnJhY3REaWdpdHMgPiAwKSB7XG4gICAgICBrID0gcmVzdWx0Lmxlbmd0aDtcbiAgICAgIHJlc3VsdCA9IHNpZ24gKyAoayA8PSBmcmFjdERpZ2l0c1xuICAgICAgICA/ICcwLicgKyByZXBlYXQoJzAnLCBmcmFjdERpZ2l0cyAtIGspICsgcmVzdWx0XG4gICAgICAgIDogc3RyaW5nU2xpY2UocmVzdWx0LCAwLCBrIC0gZnJhY3REaWdpdHMpICsgJy4nICsgc3RyaW5nU2xpY2UocmVzdWx0LCBrIC0gZnJhY3REaWdpdHMpKTtcbiAgICB9IGVsc2Uge1xuICAgICAgcmVzdWx0ID0gc2lnbiArIHJlc3VsdDtcbiAgICB9IHJldHVybiByZXN1bHQ7XG4gIH1cbn0pO1xuIiwiJ3VzZSBzdHJpY3QnO1xudmFyICQgPSByZXF1aXJlKCcuLi9pbnRlcm5hbHMvZXhwb3J0Jyk7XG52YXIgJGVudHJpZXMgPSByZXF1aXJlKCcuLi9pbnRlcm5hbHMvb2JqZWN0LXRvLWFycmF5JykuZW50cmllcztcblxuLy8gYE9iamVjdC5lbnRyaWVzYCBtZXRob2Rcbi8vIGh0dHBzOi8vdGMzOS5lcy9lY21hMjYyLyNzZWMtb2JqZWN0LmVudHJpZXNcbiQoeyB0YXJnZXQ6ICdPYmplY3QnLCBzdGF0OiB0cnVlIH0sIHtcbiAgZW50cmllczogZnVuY3Rpb24gZW50cmllcyhPKSB7XG4gICAgcmV0dXJuICRlbnRyaWVzKE8pO1xuICB9XG59KTtcbiIsIid1c2Ugc3RyaWN0JztcbnZhciAkID0gcmVxdWlyZSgnLi4vaW50ZXJuYWxzL2V4cG9ydCcpO1xudmFyICR2YWx1ZXMgPSByZXF1aXJlKCcuLi9pbnRlcm5hbHMvb2JqZWN0LXRvLWFycmF5JykudmFsdWVzO1xuXG4vLyBgT2JqZWN0LnZhbHVlc2AgbWV0aG9kXG4vLyBodHRwczovL3RjMzkuZXMvZWNtYTI2Mi8jc2VjLW9iamVjdC52YWx1ZXNcbiQoeyB0YXJnZXQ6ICdPYmplY3QnLCBzdGF0OiB0cnVlIH0sIHtcbiAgdmFsdWVzOiBmdW5jdGlvbiB2YWx1ZXMoTykge1xuICAgIHJldHVybiAkdmFsdWVzKE8pO1xuICB9XG59KTtcbiIsIid1c2Ugc3RyaWN0JztcbnZhciAkID0gcmVxdWlyZSgnLi4vaW50ZXJuYWxzL2V4cG9ydCcpO1xudmFyIGNyZWF0ZUhUTUwgPSByZXF1aXJlKCcuLi9pbnRlcm5hbHMvY3JlYXRlLWh0bWwnKTtcbnZhciBmb3JjZWRTdHJpbmdIVE1MTWV0aG9kID0gcmVxdWlyZSgnLi4vaW50ZXJuYWxzL3N0cmluZy1odG1sLWZvcmNlZCcpO1xuXG4vLyBgU3RyaW5nLnByb3RvdHlwZS5saW5rYCBtZXRob2Rcbi8vIGh0dHBzOi8vdGMzOS5lcy9lY21hMjYyLyNzZWMtc3RyaW5nLnByb3RvdHlwZS5saW5rXG4kKHsgdGFyZ2V0OiAnU3RyaW5nJywgcHJvdG86IHRydWUsIGZvcmNlZDogZm9yY2VkU3RyaW5nSFRNTE1ldGhvZCgnbGluaycpIH0sIHtcbiAgbGluazogZnVuY3Rpb24gbGluayh1cmwpIHtcbiAgICByZXR1cm4gY3JlYXRlSFRNTCh0aGlzLCAnYScsICdocmVmJywgdXJsKTtcbiAgfVxufSk7XG4iLCIvLyBleHRyYWN0ZWQgYnkgbWluaS1jc3MtZXh0cmFjdC1wbHVnaW5cbmV4cG9ydCB7fTsiLCIvLyBleHRyYWN0ZWQgYnkgbWluaS1jc3MtZXh0cmFjdC1wbHVnaW5cbmV4cG9ydCB7fTsiXSwibmFtZXMiOlsianF1ZXJ5dWkiLCJtYWluIiwidG9nZ2xlU2lkZWJhclZpc2libGUiLCJpbml0RHJvcGRvd25zIiwiJCIsIndpbmRvdyIsInJlc2l6ZSIsInNpemVXaW5kb3ciLCJ3aWR0aCIsImFkZENsYXNzIiwicmVtb3ZlQ2xhc3MiLCJoYXNDbGFzcyIsIm1lbnVDbG9zZSIsImRvY3VtZW50IiwicXVlcnlTZWxlY3RvciIsIm1lbnVCb2R5Iiwib25jbGljayIsImNsYXNzTGlzdCIsInRvZ2dsZSIsImFkZCIsImFyZWEiLCJvcHRpb25zIiwic2VsZWN0b3IiLCJkcm9wZG93biIsInVuZGVmaW5lZCIsImZpbmQiLCJhZGRCYWNrIiwib2ZQYXJlbnRTZWxlY3RvciIsIm9mUGFyZW50IiwiZWFjaCIsImlkIiwiZWwiLCJyZW1vdmVBdHRyIiwibWVudSIsImhpZGUiLCJvbiIsImUiLCJ0YXJnZXQiLCJkYXRhIiwicHJldmVudERlZmF1bHQiLCJzdG9wUHJvcGFnYXRpb24iLCJub3QiLCJ0cmlnZ2VyIiwiX29wdGlvbnMiLCJwb3NpdGlvbiIsIm15IiwiYXQiLCJvZiIsInBhcmVudHMiLCJjb2xsaXNpb24iLCJhdXRvQ29tcGxldGVSZW5kZXJJdGVtIiwicmVuZGVyRnVuY3Rpb24iLCJhcmd1bWVudHMiLCJsZW5ndGgiLCJ1bCIsIml0ZW0iLCJyZWdleCIsIlJlZ0V4cCIsImVsZW1lbnQiLCJ2YWwiLCJyZXBsYWNlIiwiaHRtbCIsInRleHQiLCJsYWJlbCIsImFwcGVuZCIsImFwcGVuZFRvIiwidWkiLCJhdXRvY29tcGxldGUiLCJwcm90b3R5cGUiLCJfcmVuZGVySXRlbSIsIlJlYWN0IiwidXNlRWZmZWN0IiwiVHJhbnNsYXRvciIsIkZvcm0iLCJHcm91cExpc3QiLCJGbGlnaHRTZWFyY2giLCJwcm9wcyIsIl9wcm9wcyRkYXRhJGZvcm0iLCJpc0V4cGFuZFJvdXRlc0V4aXN0cyIsIk9iamVjdCIsImtleXMiLCJleHBhbmRSb3V0ZXMiLCJncm91cHMiLCJ2YWx1ZXMiLCJwcmltYXJ5TGlzdCIsInNreUxpbmsiLCJjcmVhdGVFbGVtZW50IiwiY2xhc3NOYW1lIiwiaHJlZiIsInJlbCIsInNyYyIsImFsdCIsInRyYW5zIiwiZm9ybSIsIlNlYXJjaFJlc3VsdCIsImlzRm9ybUZpbGxlZCIsImZyb20iLCJtZW1vIiwibGlua0Zyb20iLCJuYW1lIiwiZGVwIiwidmFsdWUiLCJ0eXBlUGxhY2VTaWduIiwidHlwZSIsImxpbmtUbyIsInRvIiwiYXJyIiwiY29uY2F0IiwidXNlTWVtbyIsInVzZVJlZiIsInVzZVN0YXRlIiwiUm91dGVyIiwiYmxhbmtEYXRhIiwicXVlcnkiLCJfdXNlU3RhdGUiLCJfdXNlU3RhdGUyIiwiX3NsaWNlZFRvQXJyYXkiLCJzZXRGcm9tIiwiX3VzZVN0YXRlMyIsIl91c2VTdGF0ZTQiLCJzZXRUbyIsIl91c2VTdGF0ZTUiLCJfdXNlU3RhdGU2Iiwic2V0VHlwZSIsIl91c2VTdGF0ZTciLCJjbGFzcyIsIl91c2VTdGF0ZTgiLCJjbGFzc2VzIiwic2V0Q2xhc3MiLCJoYW5kbGVGb3JtU3VibWl0IiwiZXZlbnQiLCJfb2JqZWN0U3ByZWFkIiwibWV0aG9kIiwib25TdWJtaXQiLCJBdXRvQ29tcGxldGUiLCJnZXRWYWx1ZXMiLCJzZXRWYWx1ZXMiLCJwbGFjZWhvbGRlciIsIkxpc3RTdWJNZW51IiwiaXRlbXMiLCJ0eXBlcyIsInNldFZhbHVlIiwiX3Byb3BzJGdldFZhbHVlcyIsImN1cnJlbnQiLCJvZmYiLCJwYXJlbnQiLCJfJCRkYXRhIiwia2V5Q29kZSIsImNoaWxkTm9kZXMiLCJjbGljayIsInRyaW0iLCJkZWxheSIsIm1pbkxlbmd0aCIsInNvdXJjZSIsInJlcXVlc3QiLCJyZXNwb25zZSIsInRlcm0iLCJnZXQiLCJSb3V0aW5nIiwiZ2VuZXJhdGUiLCJtYXAiLCJpbmZvIiwiY29kZSIsInNlYXJjaCIsInJlbW92ZSIsIm9wZW4iLCJjcmVhdGUiLCJ0cmFuc3BCZyIsImluZGV4T2YiLCJzZWxlY3QiLCJhdHRyIiwicmVxdWlyZWQiLCJyZWYiLCJvbkNoYW5nZSIsInJvbGUiLCJlbnRyaWVzIiwiaW5kZXgiLCJrZXkiLCJvbkNsaWNrIiwiY3VycmVuY3lGb3JtYXQiLCJudW1iZXJGb3JtYXQiLCJjbGFzc05hbWVzIiwic2V0R3JvdXBzIiwiTGlzdFJlc3VsdCIsIlNlYXJjaE5vdEZvdW5kIiwidGl0bGUiLCJwcm92aWRlciIsIlByb3ZpZGVyU3RhY2siLCJwcm92aWRlcklkIiwiaXNFeHBhbmRlZCIsInNldEV4cGFuZGVkIiwiYXZnIiwiVG90YWxNaWxlc1NwZW50IiwiVG90YWxUYXhlc1NwZW50IiwiQWx0ZXJuYXRpdmVDb3N0IiwibWF4aW11bUZyYWN0aW9uRGlnaXRzIiwiTWlsZVZhbHVlIiwiTGlzdEl0ZW1zIiwiUHJvdmlkZXJJRCIsIk1pbGVSb3V0ZSIsImxvY2F0aW9uIiwic3RvcHMiLCJzdG9wIiwiZGFuZ2Vyb3VzbHlTZXRJbm5lckhUTUwiLCJfX2h0bWwiLCJhaXJsaW5lIiwicmF3IiwicmVkdWNlIiwibGluayIsIl9kZWJ1ZyIsIk1pbGVWYWx1ZUlEIiwiX3JlZ2VuZXJhdG9yUnVudGltZSIsInQiLCJyIiwibiIsImhhc093blByb3BlcnR5IiwibyIsImRlZmluZVByb3BlcnR5IiwiaSIsIlN5bWJvbCIsImEiLCJpdGVyYXRvciIsImMiLCJhc3luY0l0ZXJhdG9yIiwidSIsInRvU3RyaW5nVGFnIiwiZGVmaW5lIiwiZW51bWVyYWJsZSIsImNvbmZpZ3VyYWJsZSIsIndyaXRhYmxlIiwid3JhcCIsIkdlbmVyYXRvciIsIkNvbnRleHQiLCJtYWtlSW52b2tlTWV0aG9kIiwidHJ5Q2F0Y2giLCJhcmciLCJjYWxsIiwiaCIsImwiLCJmIiwicyIsInkiLCJHZW5lcmF0b3JGdW5jdGlvbiIsIkdlbmVyYXRvckZ1bmN0aW9uUHJvdG90eXBlIiwicCIsImQiLCJnZXRQcm90b3R5cGVPZiIsInYiLCJnIiwiZGVmaW5lSXRlcmF0b3JNZXRob2RzIiwiZm9yRWFjaCIsIl9pbnZva2UiLCJBc3luY0l0ZXJhdG9yIiwiaW52b2tlIiwiX3R5cGVvZiIsInJlc29sdmUiLCJfX2F3YWl0IiwidGhlbiIsImNhbGxJbnZva2VXaXRoTWV0aG9kQW5kQXJnIiwiRXJyb3IiLCJkb25lIiwiZGVsZWdhdGUiLCJtYXliZUludm9rZURlbGVnYXRlIiwic2VudCIsIl9zZW50IiwiZGlzcGF0Y2hFeGNlcHRpb24iLCJhYnJ1cHQiLCJyZXR1cm4iLCJUeXBlRXJyb3IiLCJyZXN1bHROYW1lIiwibmV4dCIsIm5leHRMb2MiLCJwdXNoVHJ5RW50cnkiLCJ0cnlMb2MiLCJjYXRjaExvYyIsImZpbmFsbHlMb2MiLCJhZnRlckxvYyIsInRyeUVudHJpZXMiLCJwdXNoIiwicmVzZXRUcnlFbnRyeSIsImNvbXBsZXRpb24iLCJyZXNldCIsImlzTmFOIiwiZGlzcGxheU5hbWUiLCJpc0dlbmVyYXRvckZ1bmN0aW9uIiwiY29uc3RydWN0b3IiLCJtYXJrIiwic2V0UHJvdG90eXBlT2YiLCJfX3Byb3RvX18iLCJhd3JhcCIsImFzeW5jIiwiUHJvbWlzZSIsInJldmVyc2UiLCJwb3AiLCJwcmV2IiwiY2hhckF0Iiwic2xpY2UiLCJydmFsIiwiaGFuZGxlIiwiY29tcGxldGUiLCJmaW5pc2giLCJjYXRjaCIsIl9jYXRjaCIsImRlbGVnYXRlWWllbGQiLCJhc3luY0dlbmVyYXRvclN0ZXAiLCJnZW4iLCJyZWplY3QiLCJfbmV4dCIsIl90aHJvdyIsImVycm9yIiwiX2FzeW5jVG9HZW5lcmF0b3IiLCJmbiIsInNlbGYiLCJhcmdzIiwiYXBwbHkiLCJlcnIiLCJyZW5kZXIiLCJfY2FsbGVlIiwicm9vdCIsIl9jYWxsZWUkIiwiX2NvbnRleHQiLCJnZXRFbGVtZW50QnlJZCIsIkpTT04iLCJwYXJzZSIsInRleHRDb250ZW50IiwiU3RyaWN0TW9kZSIsImV4dHJhY3RPcHRpb25zIiwiX2VudiRsb2NhbGUiLCJlbnYiLCJib2R5IiwiZGF0YXNldCIsImRlZmF1bHRMYW5nIiwiZGVmYXVsdExvY2FsZSIsImxvY2FsZSIsImFwcExvY2FsZSIsInJlc3VsdCIsImF1dGhvcml6ZWQiLCJib29raW5nIiwiYnVzaW5lc3MiLCJkZWJ1ZyIsImVuYWJsZWRUcmFuc0hlbHBlciIsImhhc1JvbGVUcmFuc2xhdG9yIiwicm9sZVRyYW5zbGF0b3IiLCJpbXBlcnNvbmF0ZWQiLCJsYW5nIiwibG9hZEV4dGVybmFsU2NyaXB0cyIsInRoZW1lIiwiaXNJb3MiLCJ0ZXN0IiwibmF2aWdhdG9yIiwidXNlckFnZW50IiwiaXNBbmRyb2lkIiwidG9Mb3dlckNhc2UiLCJJbnRsIiwiTnVtYmVyRm9ybWF0IiwiZm9ybWF0IiwiY3VycmVuY3kiLCJhc3NpZ24iLCJzdHlsZSIsImZvcm1hdEZpbGVTaXplIiwiYnl0ZXMiLCJkcCIsInRocmVzaCIsIk1hdGgiLCJhYnMiLCJ0b1N0cmluZyIsInVuaXRzIiwicG93Iiwicm91bmQiLCJ0b0ZpeGVkIl0sInNvdXJjZVJvb3QiOiIifQ==