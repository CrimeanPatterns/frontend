define(
    [
        'globalize',
        'lib/customizer',
        'json!vendor/cldr-core/supplemental/likelySubtags.json',
        'json!vendor/cldr-core/supplemental/numberingSystems.json',
        'json!vendor/cldr-numbers-full/main/en/numbers.json',
        'globalize/number'
    ], function (
        Globalize,
        customizer,
        likelySubtags,
        numberingSystems,
        enNumbers
    ) {

        var fallbackLocales = {
            'us': 'en'
        };

        var locale = fallbackLocales[customizer.locale] || customizer.locale;

        // if locale zh_CN or zh_TW, use zh_Hans or zh_Hant
        if (locale.indexOf('zh_') === 0) {
            locale = locale.replace('zh_CN', 'zh_Hans').replace('zh_TW', 'zh_Hant');
        }

        var parsers = {
            numberParser: null
        };

        Globalize.load(likelySubtags,numberingSystems);
        $.ajax({
                url: '/assets/common/vendors/cldr-numbers-full/main/'+locale+'/numbers.json',
                dataType: 'json',
                disableErrorDialog: true
            })
            .done(function(data){
                Globalize.load(data);
                Globalize.locale(locale);
            })
            .fail(function(){
                Globalize.load(enNumbers);
                Globalize.locale('en');
            })
            .always(function () {
                parsers.numberParser = Globalize.numberParser();
            });

        return parsers;
});
