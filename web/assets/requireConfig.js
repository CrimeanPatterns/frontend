require.config({
    baseUrl: '/assets',
    paths: {
       'angular': 'common/vendors/angular/angular.min', //.min
        'angular-animate': 'common/vendors/angular-animate/angular-animate.min',
		'angular-boot': 'common/js/angular-boot',
        'angular-hotkeys': 'common/vendors/angular-hotkeys/build/hotkeys.min',
        'angular-scroll': 'common/vendors/angular-scroll/angular-scroll.min',
        'angular-ui-router': 'common/vendors/angular-ui-router/release/angular-ui-router.min',
        'awardwallet': '../design/awardWallet',
        'browserext': '../kernel/browserExt', // single word for compatibility with optimizer
        'forge-api-awardwallet': '../extension/forge-api-awardwallet', // single word for compatibility with optimizer
        'extension-main': '../extension/main', // single word for compatibility with optimizer
        'extension-boot': 'common/js/extension-boot',
		'reactjs-boot': 'common/js/reactjs-boot',
		'text': "common/vendors/requirejs-text/text",
		'jsx': "common/vendors/jsx-requirejs-plugin/js/jsx",
        'reactjs': 'common/vendors/react/react-with-addons',
        'json': 'common/vendors/requirejs-plugins/src/json',
		'intl-path': "common/vendors/intl",
		'JSXTransformer': "common/vendors/jsx-requirejs-plugin/js/JSXTransformer",
        'common': 'common/js',
        'controllers': 'awardwalletnewdesign/js/controllers',
        'cookie': 'common/vendors/jquery.cookie/jquery.cookie',
        'directives': 'awardwalletnewdesign/js/directives',
        'directives/extendedDialog': 'awardwalletnewdesign/js/directives/extendedDialog',
        'directives/dialog': 'awardwalletnewdesign/js/directives/dialog',
        'directives/customizer': 'awardwalletnewdesign/js/directives/customizer',
        'directives/tabs': 'awardwalletnewdesign/js/directives/tabs',
        'domReady': 'common/js/domReady',
        'filters': 'awardwalletnewdesign/js/filters',
        'forms': 'awardwalletnewdesign/js/directives/forms',
        'jquery': 'common/vendors/jquery/dist/jquery.min',
        'jquery-boot': 'common/js/jquery-boot',
        'jqueryui': 'common/vendors/jquery-ui/jquery-ui.min',
        'jqueryui-ui': 'common/vendors/jqueryui/ui',
        'lib': 'awardwalletnewdesign/js/lib',
        'lib/utils': 'awardwalletnewdesign/js/lib/utils',
        'lib/customizer': 'awardwalletnewdesign/js/lib/customizer',
        'lib/dialog': 'awardwalletnewdesign/js/lib/dialog',

        // single words for compatibility with optimizer
        'libscripts': '../lib/scripts',
        'extension-callback-manager': '../extension/CallbackManager',
        'extension-communicator': '../extension/ExtensionCommunicator',

        'ng-infinite-scroll': 'common/vendors/ng-infinite-scroller-origin/build/ng-infinite-scroll.min',
        'oldscripts': 'common/js/oldScripts',
        'pages': 'awardwalletnewdesign/js/pages',
        'router': '../bundles/fosjsrouting/js/router',
        'routing': 'common/js/routing',
        'routes': '../js/routes.json',
        'select2': 'common/vendors/select2/select2.min',
        'services': 'awardwalletnewdesign/js/services',
        'touch-punch': 'common/vendors/jqueryui-touch-punch/jquery.ui.touch-punch.min',
        'vendor': 'common/vendors',
        'dateTimeDiff': 'common/js/dateTimeDiff',
        'sockjs': 'common/vendors/sockjs-client/dist/sockjs.min',
        'sockjs-boot': 'common/js/sockjs-boot',
        'centrifuge': 'common/vendors/centrifuge/centrifuge.min',
        'nouislider': 'common/vendors/nouislider/distribute/nouislider.min',
        'jquery-slim': 'awardwalletnewdesign/js/lib/slim.jquery.min',
        'lunr': 'common/js/lunr',
        'lunr_stemmer': 'common/js/lunr/lunr.stemmer.support.min',
        'lunr_es': 'common/js/lunr/lunr.es.min',
        'lunr_fr': 'common/js/lunr/lunr.fr.min',
        'lunr_pt': 'common/js/lunr/lunr.pt.min',
        'lunr_ru': 'common/js/lunr/lunr.ru.min',
        'lunr_de': 'common/js/lunr/lunr.de.min',
        'lunr_multi': 'common/js/lunr/lunr.multi.min',
        'chartjs' : 'common/vendors/chart.js/dist/Chart.bundle.min',
        'chartjs-plugin-watermark' : 'common/js/chartjs-plugin-watermark.min',
        'chartjs-plugin-datalabels' : 'common/js/chartjs-plugin-datalabels',
        'tipjs' : 'common/js/intro.min',
        'cldr' : 'common/vendors/cldrjs/dist/cldr',
        'globalize': 'common/vendors/globalize/dist/globalize',
        'lazysizes': 'common/vendors/lazysizes/lazysizes.min',
        'date-time-diff': 'common/vendors/date-time-diff/lib/date-time-diff.min',
        'trcking': 'common/js/trcking',
    },
	jsx: {
		fileExtension: '.jsx'
	},
    shim: {
        'sockjs': {
            export: 'SockJS'
        },
        'centrifuge': {
            deps: ['sockjs-boot']
        },
		'angular': {
			deps: [
				'jquery-boot'
			], // angular doc: To use jQuery (not jqLite), simply load it before DOMContentLoaded event fired.
			exports: 'angular'
		},
        'ng-infinite-scroll': {
            exports: 'mod',
            deps: ['angular-boot']
        },
        'angular-ui-router': {
            deps: ['angular-boot']
        },
        'angular-scroll': {
            deps: ['angular-boot']
        },
        'angular-hotkeys': {
            deps: ['angular-boot']
        },
        'angular-animate': {
            deps: ['angular-boot']
        },
        'router': {
            exports: 'fos'
        },
        // for CSRF
        'common/jquery-handlers': {
            deps: ['jquery', 'cookie']
        },
        'jqueryui': {
            deps: ['jquery-boot'],
            exports: 'jQueryUI'
        },
        'select2': {
            deps: ['jquery-boot']
        },
        'touch-punch': {
            deps: ['jquery-boot']
        },
        'awardwallet': {
            deps: ['jquery-boot'],
            exports: 'awardwallet'
        },
        'browserext': {
            deps: ['jquery-boot', 'awardwallet' /* for browserDetectNav */, 'libscripts' /* for ajaxError */, 'routing']
        },
        'libscripts': {
            exports: 'libscripts'
        },
        'translations/en': {
            deps: ['common/translator']
        },
        'translations/ru': {
            deps: ['common/translator', 'translations/en']
        },
        'translations/es': {
            deps: ['common/translator', 'translations/en']
        },
        'translations/pt': {
            deps: ['common/translator', 'translations/en']
        },
        'translations/de': {
            deps: ['common/translator', 'translations/en']
        },
        'translations/zh_TW': {
            deps: ['common/translator', 'translations/en']
        },
        'translations/zh_CN': {
            deps: ['common/translator', 'translations/en']
        },
        'translations/fr': {
            deps: ['common/translator', 'translations/en']
        },
		'oldscripts': {
			deps: ['awardwallet', 'libscripts', 'browserext']
		},
		'angular-boot': {
			deps: ['angular', 'common/appConfig'],
			exports: 'angular'
		},
		'extension-boot': {
			deps: ['extension-main']
		},
        'bitcoin-button': {
            deps: ['jquery-boot']
        },
        'reactjs': {
			deps: ['jquery-boot'],
			exports: 'React'
		},
		'reactjs-boot': {
			deps: ['reactjs'],
			exports: 'React'
		},
        'jquery-slim': {
            deps: ['jquery-boot']
        },
        'lib/utils': {
            deps: ['jquery-boot']
        },
        'lib/customizer': {
            deps: ['jquery-boot', 'jqueryui']
        },
        'lib/dialog': {
            deps: ['jquery-boot', 'jqueryui']
        },
        'directives/extendedDialog': {
            deps: ['jquery-boot', 'jqueryui']
        },
        'directives/dialog': {
            deps: ['jquery-boot', 'jqueryui']
        },
        'directives/customizer': {
            deps: ['jquery-boot', 'jqueryui']
        },
        'directives/tabs': {
            deps: ['jquery-boot', 'jqueryui']
        },

		// booking
		'common/alerts': { deps: ['jquery-boot'] },
		'common/darkfader': { deps: ['jquery-boot'] },
		'awardwalletmain/js/formInputs': { deps: ['jquery-boot', 'jqueryui', 'awardwallet', 'libscripts'] },
		'awardwalletmain/js/form/validator': { deps: ['jquery-boot', 'vendor/jquery.scrollTo/jquery.scrollTo.min'] },
		'awardwalletmain/js/CollectionManager': { deps: ['jquery-boot'] },
		'awardwalletmain/js/select-color': { deps: ['jquery-boot'] },
		'awardwalletmain/js/select2_colored': { deps: ['jquery-boot', 'select2'] },
		// bookingJs
		'awardwalletmain/js/booking/common': { deps: ['jquery-boot', 'jqueryui'] },
		'awardwalletmain/js/booking/autocomplete': { deps: ['jquery-boot', 'jqueryui'] },
		'awardwalletmain/js/booking/booker': { deps: ['jquery-boot', 'routing', 'translator-boot', 'vendor/jquery.scrollTo/jquery.scrollTo.min'] },
		'awardwalletmain/js/booking/invoice': {deps: ['jquery-boot', 'routing', 'translator-boot', 'vendor/jquery.scrollTo/jquery.scrollTo.min']},
		//'vendor/jquery-zeroclipboard/jquery-zeroclipboard': { deps: ['jquery-boot'] },
		'awardwalletmain/js/booking/seatAssignments': {deps: ['jquery-boot', 'routing', 'translator-boot', 'awardwalletmain/js/CollectionManager', 'common/darkfader', 'vendor/jquery.scrollTo/jquery.scrollTo.min']},
		// formKeeperJs
		'awardwalletmain/js/FormKeeper': { deps: ['jquery-boot', 'jqueryui', 'routing', 'translator-boot'] },
		// add page
		'awardwalletmain/js/booking/Passengers': { deps: ['jquery-boot', 'routing', 'translator-boot', 'awardwalletmain/js/formInputs'] },
		'awardwalletmain/js/booking/Segments': { deps: ['jquery-boot', 'routing', 'translator-boot', 'nouislider', 'awardwalletmain/js/form/validator'] },
		'awardwalletmain/js/booking/Miles': { deps: ['jquery-boot', 'routing', 'translator-boot', 'awardwalletmain/js/formInputs'] },
		'awardwalletmain/js/booking/Info': { deps: ['jquery-boot', 'routing', 'translator-boot'] },
		'awardwalletmain/js/booking/AddForm': { deps: ['jquery-boot', 'routing', 'translator-boot', 'awardwalletmain/js/formInputs', 'awardwalletmain/js/booking/Segments', 'common/darkfader'] },
		// view page
		'awardwalletmain/js/booking/view': { deps: ['jquery-boot', 'jqueryui', 'routing', 'translator-boot', 'awardwalletmain/js/formInputs', 'awardwalletmain/js/FormKeeper', 'common/alerts', 'awardwalletmain/js/select-color'] },
		'awardwalletmain/js/booking/messages': { deps: ['jquery-boot', 'jqueryui', 'routing', 'translator-boot', 'vendor/jquery.scrollTo/jquery.scrollTo.min', 'common/alerts', 'vendor/jquery-scrollintoview/jquery.scrollintoview'] },
		'awardwalletmain/js/booking/properties': { deps: ['jquery-boot', 'jqueryui', 'routing', 'translator-boot'] },
		// list page
		'awardwalletmain/js/booking/list': { deps: ['jquery-boot', 'jqueryui', 'routing', 'translator-boot'] },
		'vendor/jquery.browser/dist/jquery.browser.min': { deps: ['jquery-boot'] },
		// share page
		'awardwalletmain/js/booking/share': { deps: ['jquery-boot', 'jqueryui', 'routing', 'translator-boot', 'awardwallet'] },
        'chartjs-plugin-watermark' : { deps : ['chartjs'] },
        'chartjs-plugin-datalabels' : { deps : ['chartjs'] }
    },
    waitSeconds: 30
});
