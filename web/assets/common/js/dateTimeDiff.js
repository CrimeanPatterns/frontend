define(['date-time-diff', 'translator-boot', 'jquery-boot'], function (DTDiff) {
    let lang;
    let locale = 'undefined' !== typeof jQuery && (lang = jQuery('a.language[data-target="select-language"]:first')).length ? (lang.data('locale') || lang.attr('data-region') || false) : false;
    locale = locale ? locale.replace('_', '-') : Translator.locale.replace('_', '-');

    return new DTDiff.default(Translator, number => {
        return Intl.NumberFormat(locale).format(number);
    });
});
