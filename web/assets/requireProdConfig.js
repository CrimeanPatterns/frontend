/**
 * WARNING WARNING WARNING
 *
 * every module added to this config should be also added to desktopGrunt.js, section requirejs.compile.options.modules
 *
 */
require.config({
	baseUrl: '%cdn_host%/b/%version%',
	bundles: {
		'boot': [
			'jquery-boot',
			'jqueryui'
		],
		'commons': [
			'router',
			'routing',
			'routes',
			'lib/design',
			'lib/gdpr-quantcast',
			'lib/errorDialog',
			'common/translator',
			'translator-boot'
		],
		'ui-boot': [
			'lib/customizer'
		],
		'angular-boot': [
			'angular',
			/*'angular-hotkeys',*/
			'angular-scroll',
			'angular-ui-router',
			'common/appConfig',
			'directives/autoFocus',
			'directives/customizer',
			'directives/dialog',
			'filters/highlight',
			'filters/unsafe',
			'ng-infinite-scroll'
		],
		'oldscripts':[
			'libscripts',
			'browserext',
			'awardwallet'
		],
		'extension-boot':[
			'extension-main'
		]
	}
});
