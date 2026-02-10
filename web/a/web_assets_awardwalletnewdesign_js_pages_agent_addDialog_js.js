(self["webpackChunkAwardWallet"] = self["webpackChunkAwardWallet"] || []).push([["web_assets_awardwalletnewdesign_js_pages_agent_addDialog_js"],{

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

/***/ })

}]);
//# sourceMappingURL=data:application/json;charset=utf-8;base64,eyJ2ZXJzaW9uIjozLCJmaWxlIjoid2ViX2Fzc2V0c19hd2FyZHdhbGxldG5ld2Rlc2lnbl9qc19wYWdlc19hZ2VudF9hZGREaWFsb2dfanMuanMiLCJtYXBwaW5ncyI6Ijs7Ozs7Ozs7QUFBQUEsZ0VBQUFBLGlDQUFPLENBQUMsK0VBQWEsRUFBRSwyRkFBWSxFQUFFLG1GQUFpQixFQUFFLHVFQUFTLENBQUMsbUNBQUUsVUFBU0MsQ0FBQyxFQUFFQyxNQUFNLEVBQUM7RUFFdEYsSUFBSUMsYUFBYTs7RUFFakI7RUFDQSxJQUFHLE9BQU9BLGFBQWMsSUFBSSxXQUFXLEVBQUU7SUFDeENBLGFBQWEsR0FBR0YsQ0FBQyxDQUFDLFNBQVMsQ0FBQyxDQUFDRyxRQUFRLENBQUMsTUFBTSxDQUFDLENBQUNDLElBQUksQ0FDaERDLFVBQVUsQ0FBQ0MsS0FBSyxFQUFDLDhKQUE4SixzQkFBc0IsQ0FDdk0sQ0FBQztJQUNETCxNQUFNLENBQUNNLFdBQVcsQ0FBQyxjQUFjLEVBQUVMLGFBQWEsRUFBRTtNQUNqRE0sS0FBSyxFQUFFLEtBQUs7TUFDWkMsUUFBUSxFQUFFLEtBQUs7TUFDZkMsS0FBSyxFQUFFLElBQUk7TUFDWEMsS0FBSyxFQUFFTixVQUFVLENBQUNDLEtBQUssRUFBQyxzQ0FBdUMscUJBQXFCLENBQUM7TUFDckZNLE9BQU8sRUFBRSxDQUNSO1FBQ0MsTUFBTSxFQUFFUCxVQUFVLENBQUNDLEtBQUssRUFBQywyQ0FBMkMsMEJBQTBCLENBQUM7UUFDL0YsT0FBTyxFQUFFLHNCQUFzQjtRQUMvQixPQUFPLEVBQUUsU0FBQU8sTUFBQSxFQUFZO1VBQ3BCQyxNQUFNLENBQUNDLFFBQVEsQ0FBQ0MsSUFBSSxHQUFHQyxPQUFPLENBQUNDLFFBQVEsQ0FBQyxzQkFBc0IsQ0FBQztRQUNoRTtNQUNELENBQUMsRUFDRDtRQUNDLE1BQU0sRUFBRWIsVUFBVSxDQUFDQyxLQUFLLEVBQUMsbUNBQW1DLHNCQUFzQixDQUFDO1FBQ25GLE9BQU8sRUFBRSxzQkFBc0I7UUFDL0IsT0FBTyxFQUFFLFNBQUFPLE1BQUEsRUFBWTtVQUNwQkMsTUFBTSxDQUFDQyxRQUFRLENBQUNDLElBQUksR0FBR0MsT0FBTyxDQUFDQyxRQUFRLENBQUMsY0FBYyxDQUFDO1FBQ3hEO01BQ0QsQ0FBQyxDQUNEO01BQ0RDLElBQUksRUFBRSxTQUFBQSxLQUFBLEVBQVk7UUFDakI7UUFDQW5CLENBQUMsQ0FBQyxvQkFBb0IsQ0FBQyxDQUFDb0IsSUFBSSxDQUFDLENBQUM7UUFDbEJDLE9BQU8sQ0FBQ0MsU0FBUyxDQUFDLElBQUksRUFBRSxJQUFJLEVBQUUsc0JBQXNCLENBQUM7TUFFekQsQ0FBQztNQUNEQyxLQUFLLEVBQUUsU0FBQUEsTUFBQSxFQUFXO1FBQ2RGLE9BQU8sQ0FBQ0csSUFBSSxDQUFDLENBQUM7TUFDbEI7SUFDVixDQUFDLENBQUM7RUFDSDtFQUVBLElBQUlDLFlBQVksR0FBRyxTQUFmQSxZQUFZQSxDQUFBLEVBQWM7SUFDN0J2QixhQUFhLENBQUNELE1BQU0sQ0FBQyxNQUFNLENBQUM7RUFDN0IsQ0FBQztFQUVELE9BQU93QixZQUFZO0FBQ3BCLENBQUM7QUFBQSxrR0FBQyIsInNvdXJjZXMiOlsid2VicGFjazovL0F3YXJkV2FsbGV0Ly4vd2ViL2Fzc2V0cy9hd2FyZHdhbGxldG5ld2Rlc2lnbi9qcy9wYWdlcy9hZ2VudC9hZGREaWFsb2cuanMiXSwic291cmNlc0NvbnRlbnQiOlsiZGVmaW5lKFsnanF1ZXJ5LWJvb3QnLCAnbGliL2RpYWxvZycsICd0cmFuc2xhdG9yLWJvb3QnLCAncm91dGluZyddLCBmdW5jdGlvbigkLCBkaWFsb2cpe1xuXG5cdHZhciBkaWFsb2dFbGVtZW50O1xuXG5cdC8vIEFkZCBwZXJzb25zIHBvcHVwXG5cdGlmKHR5cGVvZihkaWFsb2dFbGVtZW50KSA9PSAndW5kZWZpbmVkJykge1xuXHRcdGRpYWxvZ0VsZW1lbnQgPSAkKCc8ZGl2IC8+JykuYXBwZW5kVG8oJ2JvZHknKS5odG1sKFxuXHRcdFx0XHRUcmFuc2xhdG9yLnRyYW5zKC8qKiBARGVzYyhcIllvdSBoYXZlIHR3byBvcHRpb25zLCB5b3UgY2FuIGNvbm5lY3Qgd2l0aCBhbm90aGVyIHBlcnNvbiBvbiBBd2FyZFdhbGxldCwgb3IgeW91IGNhbiBqdXN0IGNyZWF0ZSBhbm90aGVyIG5hbWUgdG8gYmV0dGVyIG9yZ2FuaXplIHlvdXIgcmV3YXJkcy5cIikgKi8nYWdlbnRzLnBvcHVwLmNvbnRlbnQnKVxuXHRcdCk7XG5cdFx0ZGlhbG9nLmNyZWF0ZU5hbWVkKCdwZXJzb25zLW1lbnUnLCBkaWFsb2dFbGVtZW50LCB7XG5cdFx0XHR3aWR0aDogJzYwMCcsXG5cdFx0XHRhdXRvT3BlbjogZmFsc2UsXG5cdFx0XHRtb2RhbDogdHJ1ZSxcblx0XHRcdHRpdGxlOiBUcmFuc2xhdG9yLnRyYW5zKC8qKiBARGVzYyhcIlNlbGVjdCBjb25uZWN0aW9uIHR5cGVcIikgKi8gJ2FnZW50cy5wb3B1cC5oZWFkZXInKSxcblx0XHRcdGJ1dHRvbnM6IFtcblx0XHRcdFx0e1xuXHRcdFx0XHRcdCd0ZXh0JzogVHJhbnNsYXRvci50cmFucygvKiogQERlc2MoXCJDb25uZWN0IHdpdGggYW5vdGhlciBwZXJzb25cIikgKi8nYWdlbnRzLnBvcHVwLmNvbm5lY3QuYnRuJyksXG5cdFx0XHRcdFx0J2NsYXNzJzogJ2J0bi1ibHVlIHNwaW5uZXJhYmxlJyxcblx0XHRcdFx0XHQnY2xpY2snOiBmdW5jdGlvbiAoKSB7XG5cdFx0XHRcdFx0XHR3aW5kb3cubG9jYXRpb24uaHJlZiA9IFJvdXRpbmcuZ2VuZXJhdGUoJ2F3X2NyZWF0ZV9jb25uZWN0aW9uJylcblx0XHRcdFx0XHR9XG5cdFx0XHRcdH0sXG5cdFx0XHRcdHtcblx0XHRcdFx0XHQndGV4dCc6IFRyYW5zbGF0b3IudHJhbnMoLyoqIEBEZXNjKFwiSnVzdCBhZGQgYSBuZXcgbmFtZVwiKSAqLydhZ2VudHMucG9wdXAuYWRkLmJ0bicpLFxuXHRcdFx0XHRcdCdjbGFzcyc6ICdidG4tYmx1ZSBzcGlubmVyYWJsZScsXG5cdFx0XHRcdFx0J2NsaWNrJzogZnVuY3Rpb24gKCkge1xuXHRcdFx0XHRcdFx0d2luZG93LmxvY2F0aW9uLmhyZWYgPSBSb3V0aW5nLmdlbmVyYXRlKCdhd19hZGRfYWdlbnQnKVxuXHRcdFx0XHRcdH1cblx0XHRcdFx0fVxuXHRcdFx0XSxcblx0XHRcdG9wZW46IGZ1bmN0aW9uICgpIHtcblx0XHRcdFx0Ly8gUmVtb3ZlIGJvdHRvbnMgZm9jdXNcblx0XHRcdFx0JCgnLnVpLWRpYWxvZyA6YnV0dG9uJykuYmx1cigpO1xuICAgICAgICAgICAgICAgIGhpc3RvcnkucHVzaFN0YXRlKG51bGwsIG51bGwsICc/YWRkLW5ldy1wZXJzb249dHJ1ZScpO1xuXG4gICAgICAgICAgICB9LFxuICAgICAgICAgICAgY2xvc2U6IGZ1bmN0aW9uKCkge1xuICAgICAgICAgICAgICAgIGhpc3RvcnkuYmFjaygpO1xuICAgICAgICAgICAgfVxuXHRcdH0pO1xuXHR9XG5cblx0dmFyIGNsaWNrSGFuZGxlciA9IGZ1bmN0aW9uKCkge1xuXHRcdGRpYWxvZ0VsZW1lbnQuZGlhbG9nKCdvcGVuJyk7XG5cdH07XG5cblx0cmV0dXJuIGNsaWNrSGFuZGxlcjtcbn0pO1xuIl0sIm5hbWVzIjpbImRlZmluZSIsIiQiLCJkaWFsb2ciLCJkaWFsb2dFbGVtZW50IiwiYXBwZW5kVG8iLCJodG1sIiwiVHJhbnNsYXRvciIsInRyYW5zIiwiY3JlYXRlTmFtZWQiLCJ3aWR0aCIsImF1dG9PcGVuIiwibW9kYWwiLCJ0aXRsZSIsImJ1dHRvbnMiLCJjbGljayIsIndpbmRvdyIsImxvY2F0aW9uIiwiaHJlZiIsIlJvdXRpbmciLCJnZW5lcmF0ZSIsIm9wZW4iLCJibHVyIiwiaGlzdG9yeSIsInB1c2hTdGF0ZSIsImNsb3NlIiwiYmFjayIsImNsaWNrSGFuZGxlciJdLCJzb3VyY2VSb290IjoiIn0=