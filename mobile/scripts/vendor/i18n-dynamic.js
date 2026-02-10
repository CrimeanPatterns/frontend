var i18nModule = angular.module('i18nDynamic', ['ngLocale']);
i18nModule.factory("i18nLanguage", ['$locale', '$log', function ($locale, $log) {

    var that = this;

    var PLURAL_CATEGORY = {ZERO: "zero", ONE: "one", TWO: "two", FEW: "few", MANY: "many", OTHER: "other"};

    function getDecimals(n) {
        n = n + '';
        var i = n.indexOf('.');
        return (i == -1) ? 0 : n.length - i - 1;
    }

    function getVF(n, opt_precision) {
        var v = opt_precision;

        if (undefined === v) {
            v = Math.min(getDecimals(n), 3);
        }

        var base = Math.pow(10, v);
        var f = ((n * base) | 0) % base;
        return {v: v, f: f};
    }

    // Come from angularJS localization files
    // Add language that you need for your application
    var angularLocale = {
        'ru': {
            "DATETIME_FORMATS": {
                "AMPMS": [
                    "AM",
                    "PM"
                ],
                "DAY": [
                    "\u0432\u043e\u0441\u043a\u0440\u0435\u0441\u0435\u043d\u044c\u0435",
                    "\u043f\u043e\u043d\u0435\u0434\u0435\u043b\u044c\u043d\u0438\u043a",
                    "\u0432\u0442\u043e\u0440\u043d\u0438\u043a",
                    "\u0441\u0440\u0435\u0434\u0430",
                    "\u0447\u0435\u0442\u0432\u0435\u0440\u0433",
                    "\u043f\u044f\u0442\u043d\u0438\u0446\u0430",
                    "\u0441\u0443\u0431\u0431\u043e\u0442\u0430"
                ],
                "ERANAMES": [
                    "\u0434\u043e \u043d. \u044d.",
                    "\u043d. \u044d."
                ],
                "ERAS": [
                    "\u0434\u043e \u043d. \u044d.",
                    "\u043d. \u044d."
                ],
                "FIRSTDAYOFWEEK": 0,
                "MONTH": [
                    "\u044f\u043d\u0432\u0430\u0440\u044f",
                    "\u0444\u0435\u0432\u0440\u0430\u043b\u044f",
                    "\u043c\u0430\u0440\u0442\u0430",
                    "\u0430\u043f\u0440\u0435\u043b\u044f",
                    "\u043c\u0430\u044f",
                    "\u0438\u044e\u043d\u044f",
                    "\u0438\u044e\u043b\u044f",
                    "\u0430\u0432\u0433\u0443\u0441\u0442\u0430",
                    "\u0441\u0435\u043d\u0442\u044f\u0431\u0440\u044f",
                    "\u043e\u043a\u0442\u044f\u0431\u0440\u044f",
                    "\u043d\u043e\u044f\u0431\u0440\u044f",
                    "\u0434\u0435\u043a\u0430\u0431\u0440\u044f"
                ],
                "SHORTDAY": [
                    "\u0432\u0441",
                    "\u043f\u043d",
                    "\u0432\u0442",
                    "\u0441\u0440",
                    "\u0447\u0442",
                    "\u043f\u0442",
                    "\u0441\u0431"
                ],
                "SHORTMONTH": [
                    "\u044f\u043d\u0432.",
                    "\u0444\u0435\u0432\u0440.",
                    "\u043c\u0430\u0440\u0442\u0430",
                    "\u0430\u043f\u0440.",
                    "\u043c\u0430\u044f",
                    "\u0438\u044e\u043d\u044f",
                    "\u0438\u044e\u043b\u044f",
                    "\u0430\u0432\u0433.",
                    "\u0441\u0435\u043d\u0442.",
                    "\u043e\u043a\u0442.",
                    "\u043d\u043e\u044f\u0431.",
                    "\u0434\u0435\u043a."
                ],
                "WEEKENDRANGE": [
                    5,
                    6
                ],
                "fullDate": "EEEE, d MMMM y '\u0433'.",
                "longDate": "d MMMM y '\u0433'.",
                "medium": "d MMM y '\u0433'. H:mm:ss",
                "mediumDate": "d MMM y '\u0433'.",
                "mediumTime": "H:mm:ss",
                "short": "dd.MM.yy H:mm",
                "shortDate": "dd.MM.yy",
                "shortTime": "H:mm",
                "point": "EEE, dd.MM.y '\u0433'.",
                "shortMonth": "d MMM",
                "shortDateTime": "d MMM '\u0432' H:mm"
            },
            "NUMBER_FORMATS": {
                "CURRENCY_SYM": "\u0440\u0443\u0431.",
                "DECIMAL_SEP": ",",
                "GROUP_SEP": "\u00a0",
                "PATTERNS": [
                    {
                        "gSize": 3,
                        "lgSize": 3,
                        "maxFrac": 3,
                        "minFrac": 0,
                        "minInt": 1,
                        "negPre": "-",
                        "negSuf": "",
                        "posPre": "",
                        "posSuf": ""
                    },
                    {
                        "gSize": 3,
                        "lgSize": 3,
                        "maxFrac": 2,
                        "minFrac": 2,
                        "minInt": 1,
                        "negPre": "-",
                        "negSuf": "\u00a0\u00a4",
                        "posPre": "",
                        "posSuf": "\u00a0\u00a4"
                    }
                ]
            },
            "pluralCat": function (n, opt_precision) {
                var i = n | 0;
                var vf = getVF(n, opt_precision);
                if (vf.v == 0 && i % 10 == 1 && i % 100 != 11) {
                    return PLURAL_CATEGORY.ONE;
                }
                if (vf.v == 0 && i % 10 >= 2 && i % 10 <= 4 && (i % 100 < 12 || i % 100 > 14)) {
                    return PLURAL_CATEGORY.FEW;
                }
                if (vf.v == 0 && i % 10 == 0 || vf.v == 0 && i % 10 >= 5 && i % 10 <= 9 || vf.v == 0 && i % 100 >= 11 && i % 100 <= 14) {
                    return PLURAL_CATEGORY.MANY;
                }
                return PLURAL_CATEGORY.OTHER;
            },
            "id": "ru"
        },
        'en': {
            "DATETIME_FORMATS": {
                "MONTH": ["January", "February", "March", "April", "May", "June", "July", "August", "September", "October", "November", "December"],
                "SHORTMONTH": ["Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul", "Aug", "Sep", "Oct", "Nov", "Dec"],
                "DAY": ["Sunday", "Monday", "Tuesday", "Wednesday", "Thursday", "Friday", "Saturday"],
                "SHORTDAY": ["Sun", "Mon", "Tue", "Wed", "Thu", "Fri", "Sat"],
                "AMPMS": ["AM", "PM"],
                "medium": "MMM d, y h:mm:ss a",
                "short": "M/d/yy h:mm a",
                "fullDate": "EEEE, MMMM d, y",
                "longDate": "MMMM d, y",
                "mediumDate": "MMM d, y",
                "shortDate": "M/d/yy",
                "mediumTime": "h:mm:ss a",
                "shortTime": "h:mm a",
                "point": "EEE MM/dd/yy",
                "shortMonth": "MMM d",
                "shortDateTime": "MMM d, h:mm a"
            },
            "NUMBER_FORMATS": {
                "DECIMAL_SEP": ".",
                "GROUP_SEP": ",",
                "PATTERNS": [{
                    "minInt": 1,
                    "minFrac": 0,
                    "macFrac": 0,
                    "posPre": "",
                    "posSuf": "",
                    "negPre": "-",
                    "negSuf": "",
                    "gSize": 3,
                    "lgSize": 3,
                    "maxFrac": 3
                }, {
                    "minInt": 1,
                    "minFrac": 2,
                    "macFrac": 0,
                    "posPre": "\u00A4",
                    "posSuf": "",
                    "negPre": "(\u00A4",
                    "negSuf": ")",
                    "gSize": 3,
                    "lgSize": 3,
                    "maxFrac": 2
                }],
                "CURRENCY_SYM": "$"
            },
            "pluralCat": function (n) {
                if (n == 1) {
                    return PLURAL_CATEGORY.ONE;
                }
                return PLURAL_CATEGORY.OTHER;
            },
            "id": "en"
        },
        'pt': {
            "DATETIME_FORMATS": {
                "AMPMS": [
                    "AM",
                    "PM"
                ],
                "DAY": [
                    "domingo",
                    "segunda-feira",
                    "ter\u00e7a-feira",
                    "quarta-feira",
                    "quinta-feira",
                    "sexta-feira",
                    "s\u00e1bado"
                ],
                "ERANAMES": [
                    "Antes de Cristo",
                    "Ano do Senhor"
                ],
                "ERAS": [
                    "a.C.",
                    "d.C."
                ],
                "FIRSTDAYOFWEEK": 6,
                "MONTH": [
                    "janeiro",
                    "fevereiro",
                    "mar\u00e7o",
                    "abril",
                    "maio",
                    "junho",
                    "julho",
                    "agosto",
                    "setembro",
                    "outubro",
                    "novembro",
                    "dezembro"
                ],
                "SHORTDAY": [
                    "dom",
                    "seg",
                    "ter",
                    "qua",
                    "qui",
                    "sex",
                    "s\u00e1b"
                ],
                "SHORTMONTH": [
                    "jan",
                    "fev",
                    "mar",
                    "abr",
                    "mai",
                    "jun",
                    "jul",
                    "ago",
                    "set",
                    "out",
                    "nov",
                    "dez"
                ],
                "WEEKENDRANGE": [
                    5,
                    6
                ],
                "fullDate": "EEEE, d 'de' MMMM 'de' y",
                "longDate": "d 'de' MMMM 'de' y",
                "medium": "d 'de' MMM 'de' y HH:mm:ss",
                "mediumDate": "d 'de' MMM 'de' y",
                "mediumTime": "HH:mm:ss",
                "short": "dd/MM/yy HH:mm",
                "shortDate": "dd/MM/yy",
                "shortTime": "HH:mm",
                "point": "EEE dd/MM/yy",
                "shortMonth": "d MMM",
                "shortDateTime": "d MMM, HH:mm"
            },
            "NUMBER_FORMATS": {
                "CURRENCY_SYM": "R$",
                "DECIMAL_SEP": ",",
                "GROUP_SEP": ".",
                "PATTERNS": [
                    {
                        "gSize": 3,
                        "lgSize": 3,
                        "maxFrac": 3,
                        "minFrac": 0,
                        "minInt": 1,
                        "negPre": "-",
                        "negSuf": "",
                        "posPre": "",
                        "posSuf": ""
                    },
                    {
                        "gSize": 3,
                        "lgSize": 3,
                        "maxFrac": 2,
                        "minFrac": 2,
                        "minInt": 1,
                        "negPre": "\u00a4-",
                        "negSuf": "",
                        "posPre": "\u00a4",
                        "posSuf": ""
                    }
                ]
            },
            "id": "pt",
            "pluralCat": function (n, opt_precision) {
                if (n >= 0 && n <= 2 && n != 2) {
                    return PLURAL_CATEGORY.ONE;
                }
                return PLURAL_CATEGORY.OTHER;
            }
        },
        'es': {
            "DATETIME_FORMATS": {
                "AMPMS": [
                    "a. m.",
                    "p. m."
                ],
                "DAY": [
                    "domingo",
                    "lunes",
                    "martes",
                    "mi\u00e9rcoles",
                    "jueves",
                    "viernes",
                    "s\u00e1bado"
                ],
                "ERANAMES": [
                    "antes de Cristo",
                    "anno D\u00f3mini"
                ],
                "ERAS": [
                    "a. C.",
                    "d. C."
                ],
                "FIRSTDAYOFWEEK": 0,
                "MONTH": [
                    "enero",
                    "febrero",
                    "marzo",
                    "abril",
                    "mayo",
                    "junio",
                    "julio",
                    "agosto",
                    "septiembre",
                    "octubre",
                    "noviembre",
                    "diciembre"
                ],
                "SHORTDAY": [
                    "dom.",
                    "lun.",
                    "mar.",
                    "mi\u00e9.",
                    "jue.",
                    "vie.",
                    "s\u00e1b."
                ],
                "SHORTMONTH": [
                    "ene.",
                    "feb.",
                    "mar.",
                    "abr.",
                    "may.",
                    "jun.",
                    "jul.",
                    "ago.",
                    "sept.",
                    "oct.",
                    "nov.",
                    "dic."
                ],
                "WEEKENDRANGE": [
                    5,
                    6
                ],
                "fullDate": "EEEE, d 'de' MMMM 'de' y",
                "longDate": "d 'de' MMMM 'de' y",
                "medium": "d 'de' MMM 'de' y H:mm:ss",
                "mediumDate": "d 'de' MMM 'de' y",
                "mediumTime": "H:mm:ss",
                "short": "d/M/yy H:mm",
                "shortDate": "d/M/yy",
                "shortTime": "H:mm",
                "shortMonth": "d MMM",
                "shortDateTime": "d MMM, H:mm"
            },
            "NUMBER_FORMATS": {
                "CURRENCY_SYM": "\u20ac",
                "DECIMAL_SEP": ",",
                "GROUP_SEP": ".",
                "PATTERNS": [
                    {
                        "gSize": 3,
                        "lgSize": 3,
                        "maxFrac": 3,
                        "minFrac": 0,
                        "minInt": 1,
                        "negPre": "-",
                        "negSuf": "",
                        "posPre": "",
                        "posSuf": ""
                    },
                    {
                        "gSize": 3,
                        "lgSize": 3,
                        "maxFrac": 2,
                        "minFrac": 2,
                        "minInt": 1,
                        "negPre": "-",
                        "negSuf": "\u00a0\u00a4",
                        "posPre": "",
                        "posSuf": "\u00a0\u00a4"
                    }
                ]
            },
            "id": "es",
            "pluralCat": function (n, opt_precision) {
                if (n == 1) {
                    return PLURAL_CATEGORY.ONE;
                }
                return PLURAL_CATEGORY.OTHER;
            }
        },
        'de': {
            "DATETIME_FORMATS": {
                "AMPMS": [
                    "vorm.",
                    "nachm."
                ],
                "DAY": [
                    "Sonntag",
                    "Montag",
                    "Dienstag",
                    "Mittwoch",
                    "Donnerstag",
                    "Freitag",
                    "Samstag"
                ],
                "ERANAMES": [
                    "v. Chr.",
                    "n. Chr."
                ],
                "ERAS": [
                    "v. Chr.",
                    "n. Chr."
                ],
                "FIRSTDAYOFWEEK": 0,
                "MONTH": [
                    "Januar",
                    "Februar",
                    "M\u00e4rz",
                    "April",
                    "Mai",
                    "Juni",
                    "Juli",
                    "August",
                    "September",
                    "Oktober",
                    "November",
                    "Dezember"
                ],
                "SHORTDAY": [
                    "So.",
                    "Mo.",
                    "Di.",
                    "Mi.",
                    "Do.",
                    "Fr.",
                    "Sa."
                ],
                "SHORTMONTH": [
                    "Jan.",
                    "Feb.",
                    "M\u00e4rz",
                    "Apr.",
                    "Mai",
                    "Juni",
                    "Juli",
                    "Aug.",
                    "Sep.",
                    "Okt.",
                    "Nov.",
                    "Dez."
                ],
                "WEEKENDRANGE": [
                    5,
                    6
                ],
                "fullDate": "EEEE, d. MMMM y",
                "longDate": "d. MMMM y",
                "medium": "dd.MM.y HH:mm:ss",
                "mediumDate": "dd.MM.y",
                "mediumTime": "HH:mm:ss",
                "short": "dd.MM.yy HH:mm",
                "shortDate": "dd.MM.yy",
                "shortTime": "HH:mm",
                "shortMonth": "d MMM",
                "shortDateTime": "d MMM, HH:mm"
            },
            "NUMBER_FORMATS": {
                "CURRENCY_SYM": "\u20ac",
                "DECIMAL_SEP": ",",
                "GROUP_SEP": ".",
                "PATTERNS": [
                    {
                        "gSize": 3,
                        "lgSize": 3,
                        "maxFrac": 3,
                        "minFrac": 0,
                        "minInt": 1,
                        "negPre": "-",
                        "negSuf": "",
                        "posPre": "",
                        "posSuf": ""
                    },
                    {
                        "gSize": 3,
                        "lgSize": 3,
                        "maxFrac": 2,
                        "minFrac": 2,
                        "minInt": 1,
                        "negPre": "-",
                        "negSuf": "\u00a0\u00a4",
                        "posPre": "",
                        "posSuf": "\u00a0\u00a4"
                    }
                ]
            },
            "id": "de",
            "pluralCat": function (n, opt_precision) {
                var i = n | 0;
                var vf = getVF(n, opt_precision);
                if (i == 1 && vf.v == 0) {
                    return PLURAL_CATEGORY.ONE;
                }
                return PLURAL_CATEGORY.OTHER;
            }

        },
        'zh_TW': {
            "DATETIME_FORMATS": {
                "AMPMS": [
                    "\u4e0a\u5348",
                    "\u4e0b\u5348"
                ],
                "DAY": [
                    "\u661f\u671f\u65e5",
                    "\u661f\u671f\u4e00",
                    "\u661f\u671f\u4e8c",
                    "\u661f\u671f\u4e09",
                    "\u661f\u671f\u56db",
                    "\u661f\u671f\u4e94",
                    "\u661f\u671f\u516d"
                ],
                "ERANAMES": [
                    "\u897f\u5143\u524d",
                    "\u897f\u5143"
                ],
                "ERAS": [
                    "\u897f\u5143\u524d",
                    "\u897f\u5143"
                ],
                "FIRSTDAYOFWEEK": 6,
                "MONTH": [
                    "1\u6708",
                    "2\u6708",
                    "3\u6708",
                    "4\u6708",
                    "5\u6708",
                    "6\u6708",
                    "7\u6708",
                    "8\u6708",
                    "9\u6708",
                    "10\u6708",
                    "11\u6708",
                    "12\u6708"
                ],
                "SHORTDAY": [
                    "\u9031\u65e5",
                    "\u9031\u4e00",
                    "\u9031\u4e8c",
                    "\u9031\u4e09",
                    "\u9031\u56db",
                    "\u9031\u4e94",
                    "\u9031\u516d"
                ],
                "SHORTMONTH": [
                    "1\u6708",
                    "2\u6708",
                    "3\u6708",
                    "4\u6708",
                    "5\u6708",
                    "6\u6708",
                    "7\u6708",
                    "8\u6708",
                    "9\u6708",
                    "10\u6708",
                    "11\u6708",
                    "12\u6708"
                ],
                "STANDALONEMONTH": [
                    "1\u6708",
                    "2\u6708",
                    "3\u6708",
                    "4\u6708",
                    "5\u6708",
                    "6\u6708",
                    "7\u6708",
                    "8\u6708",
                    "9\u6708",
                    "10\u6708",
                    "11\u6708",
                    "12\u6708"
                ],
                "WEEKENDRANGE": [
                    5,
                    6
                ],
                "fullDate": "y\u5e74M\u6708d\u65e5 EEEE",
                "longDate": "y\u5e74M\u6708d\u65e5",
                "medium": "y\u5e74M\u6708d\u65e5 ah:mm:ss",
                "mediumDate": "y\u5e74M\u6708d\u65e5",
                "mediumTime": "ah:mm:ss",
                "short": "y/M/d ah:mm",
                "shortDate": "y/M/d",
                "shortTime": "ah:mm",
                "point": "EEE y/M/d",
                "shortMonth": "d MMM",
                "shortDateTime": "d MMM, ah:mm"
            },
            "NUMBER_FORMATS": {
                "CURRENCY_SYM": "NT$",
                "DECIMAL_SEP": ".",
                "GROUP_SEP": ",",
                "PATTERNS": [
                    {
                        "gSize": 3,
                        "lgSize": 3,
                        "maxFrac": 3,
                        "minFrac": 0,
                        "minInt": 1,
                        "negPre": "-",
                        "negSuf": "",
                        "posPre": "",
                        "posSuf": ""
                    },
                    {
                        "gSize": 3,
                        "lgSize": 3,
                        "maxFrac": 2,
                        "minFrac": 2,
                        "minInt": 1,
                        "negPre": "-\u00a4",
                        "negSuf": "",
                        "posPre": "\u00a4",
                        "posSuf": ""
                    }
                ]
            },
            "id": "zh_TW",
            "pluralCat": function(n, opt_precision) {  return PLURAL_CATEGORY.OTHER;}
        }
    };

    /**
     * Constructor
     */
    function i18nLanguageService() {
        this.setLocale($locale.id);
    }


    // Replace the format content with the new local selected
    i18nLanguageService.prototype.loadAngularLocale = function (language) {
        if (language && angularLocale.hasOwnProperty(language)) {
            $locale.DATETIME_FORMATS = angularLocale[language].DATETIME_FORMATS;
            $locale.NUMBER_FORMATS = angularLocale[language].NUMBER_FORMATS;
            $locale.id = language;
        }
    };

    i18nLanguageService.prototype.setLocale = function (newLocale) {
        if (newLocale) {
            /*if (newLocale.lastIndexOf("-") > 0) {
                newLocale = newLocale.slice(0, newLocale.lastIndexOf("-"));
                if (newLocale) {
                    this.setLocale(newLocale);
                }
            } else {*/
                this.loadAngularLocale(newLocale);
            //}
        }
    };

    return new i18nLanguageService();
}]);




