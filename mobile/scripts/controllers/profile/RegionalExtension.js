/**
 * @param {AbstractFormExtension} extension
 */
function addFormExtension(extension) {
    var hints = {
        lang: {},
        locale: {}
    };

    function getLocaleHint(lang, region) {
        var locale = lang + '_' + region;
        var locales = Object.keys(hints.locale);
        var fallback = lang.toLowerCase() + '_' + region;

        if (hints.locale.hasOwnProperty(fallback)) {
            locale = fallback;
        } else {
            var regionLocales = locales.filter(function (item) {
                return item.substr(-2) == region;
            });
            if (regionLocales.length)
                locale = regionLocales[0];
            else
                locale = hints.lang[lang];
        }

        return hints.locale[locale];
    }

    /**
     * will be called on field change
     * @param {FormInterface} form
     * @param {string} fieldName in lower case
     */
    extension.onFieldChange = function (form, fieldName) {
        if (['region', 'language'].indexOf(fieldName) > -1) {
            form.setFieldNotice('region', getLocaleHint(form.getValue('language'), form.getValue('region')));
        }
    };

    /**
     * will be called when form loaded and ready
     * @param {FormInterface} form
     */
    extension.onFormReady = function (form) {
        if (form.getValue('lang_hints')) {
            hints.lang = JSON.parse(form.getValue('lang_hints'));
        }
        if (form.getValue('locale_hints')) {
            hints.locale = JSON.parse(form.getValue('locale_hints'));
        }
    };

}

