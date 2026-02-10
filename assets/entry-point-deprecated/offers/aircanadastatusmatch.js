import '../../../web/assets/awardwalletmain/css/offer/aircanadastatusmatch.less';

(function() {
    const $offerProceed = $('#offerProceed');
    $('#offerAgree').change(function() {
        if ($(this).prop('checked')) {
            $offerProceed.removeProp('disabled');
        } else {
            $offerProceed.prop('disabled', true);
        }
    });

    $offerProceed.click(function() {
        offerYes(false);
    });

})();
