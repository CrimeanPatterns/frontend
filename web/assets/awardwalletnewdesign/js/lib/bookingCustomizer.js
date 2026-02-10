/* global InputStyle */

define(['lib/customizer', 'awardwalletmain/js/formInputs'], function(customizer) {
    InputStyle.date = function(e) {
        customizer.initDatepickers($(e).parent())
    };
});