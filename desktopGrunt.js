module.exports = function (grunt) {

	function get_extension_version(){
		manifest = grunt.file.readJSON('extension/compatible/manifest.json');
		grunt.log.writeln('extension version: ' + manifest.version);
		return manifest.version;
	}

	var extension_version = get_extension_version();

	grunt.initConfig({

		host: 'awardwallet.docker',
		businessHost: 'business.awardwallet.docker',

		less: {
		  compile: {
		    // options: {
		    //   plugins: [
		    //     new (require('less-plugin-autoprefix'))({browsers: ["last 2 versions"]}),
		    //     new (require('less-plugin-clean-css'))(cleanCssOptions)
		    //   ]
		    // },
		    files: [{
				src: [
					'web/assets/awardwalletnewdesign/less/*.less',
					'web/assets/awardwalletnewdesign/less/pages/*.less',
					'web/assets/awardwalletnewdesign/less/jquery-ui/*.less'
				],
				expand: true,
				flatten: true,
				rename: function(dest, src) { return dest + '/' + src.replace('.less', '.css') },
				dest: 'web/assets/awardwalletnewdesign/css'
			}]
		  }
		},

		clean: {
			web: ['build/web'],
			b: ['web/b/*'],
            buildEdge: ['extension/build/edge'],
            buildEdgeXml: ['extension/build/edge/AwardWallet/edgeextension/manifest/appxmanifestPatched.xml'],
			buildFirefox: ['extension/build/firefox'],
			buildChrome: ['extension/build/chrome']
		},

		copy: {
			nodeModulesToPublic: {
				expand: true,
				cwd: './',
				src: [
					'node_modules/angular/angular.min.js',
					'node_modules/angular-ui-router/release/angular-ui-router.min.js',
					'node_modules/html5shiv/dist/html5shiv.min.js',
					'node_modules/jquery/dist/jquery.min.js',
					'node_modules/jquery.browser/dist/jquery.browser.min.js',
					'node_modules/jquery.cookie/jquery.cookie.js',
					'node_modules/jquery.scrollto/jquery.scrollTo.min.js',
					'node_modules/components-jqueryui/ui/**',
					'node_modules/components-jqueryui/jquery-ui.min.js',
					'node_modules/ng-infinite-scroll/build/ng-infinite-scroll.min.js',
					'node_modules/requirejs/require.js',
					'node_modules/select2/**',
					'node_modules/selectivizr/selectivizr.js',
					'node_modules/jquery-ui-touch-punch/jquery.ui.touch-punch.min.js',
					'node_modules/angular-scroll/angular-scroll.min.js',
					'node_modules/angular-hotkeys/build/hotkeys.min.js',
					'node_modules/angular-animate/angular-animate.min.js',
					'node_modules/jsx-requirejs-plugin/js/**',
					'node_modules/keymaster/keymaster.js',
					'node_modules/html2canvas/dist/html2canvas.js',
					'node_modules/jquery.scrollintoview/jquery.scrollintoview.js',
					'node_modules/centrifuge/centrifuge.min.js',
					'node_modules/nouislider/distribute/nouislider.min.js',
					'node_modules/clipboard/dist/clipboard.min.js',
					'node_modules/intl/**',
					'node_modules/card-image-parser-js/dist/domOperations.js',
					'node_modules/chart.js/dist/Chart.bundle.min.js',
					'node_modules/ckeditor/**',
					'node_modules/globalize/dist/**',
					'node_modules/cldr-numbers-full/**',
					'node_modules/requirejs-plugins/**',
					'node_modules/cldr-core/**',
					'node_modules/cldrjs/**',
					'node_modules/lazysizes/**',
					'node_modules/sockjs-client/**',
					'node_modules/es6-shim/**',
					'node_modules/@awardwallet/date-time-diff-requirejs/**',
					'node_modules/requirejs-text/**',
				],
				dest: 'web/assets/common/vendors',
				rename: function(dest, src) {
					if (src.indexOf('jquery.scrollto') !== -1) {
            return dest + src.replace('node_modules/jquery.scrollto', '/jquery.scrollTo');
        	}
					if (src.indexOf('ng-infinite-scroll') !== -1) {
            return dest + src.replace('node_modules/ng-infinite-scroll', '/ng-infinite-scroller-origin');
        	}
					if (src.indexOf('components-jqueryui/ui') !== -1) {
            return dest + src.replace('node_modules/components-jqueryui', '/jqueryui');
        	}
					if (src.indexOf('components-jqueryui/jquery-ui.min.js') !== -1) {
            return dest + src.replace('node_modules/components-jqueryui', '/jquery-ui');
        	}
					if (src.indexOf('node_modules/html2canvas') !== -1) {
            return dest + src.replace('node_modules/html2canvas/dist/html2canvas.js', '/html2canvas/index.js');
        	}
					if (src.indexOf('node_modules/jquery.scrollintoview') !== -1) {
            return dest + src.replace('node_modules/jquery.scrollintoview', '/jquery-scrollintoview');
        	}
					if (src.indexOf('node_modules/@awardwallet/date-time-diff-requirejs') !== -1) {
            return dest + src.replace('node_modules/@awardwallet/date-time-diff-requirejs', '/date-time-diff');
        	}
					if (src.indexOf('node_modules/jquery-ui-touch-punch') !== -1) {
            return dest + src.replace('node_modules/jquery-ui-touch-punch', '/jqueryui-touch-punch');
        	}
					if (src.indexOf('select2.js') !== -1) {
            return dest + src.replace('node_modules/select2/select2.js', '/select2/select2.min.js');
        	}
					return dest + src.replace('node_modules', '');
				},
			},
			reactFiles: {
        expand: true,
        cwd: './',
        src: ['node_modules/jsx-requirejs-plugin/js/react-with-addons.js','node_modules/react/umd/**', 'node_modules/react-dom/umd/**'],
        dest: 'web/assets/common/vendors/react/',  
				rename: function(dest, src) { 
					if (src.indexOf('node_modules/jsx-requirejs-plugin/js') !== -1) {
            return dest + src.replace('node_modules/jsx-requirejs-plugin/js', '');
        	}

					if (src.indexOf('node_modules/react-dom') !== -1) {
            return dest + src.replace('node_modules/react-dom/umd', '');
        	}
					return dest + src.replace('node_modules/react/umd', '') },
      },
			webToTemp: {
				expand: true,
				cwd: 'web',
				src: [
					'bundles/**/*.js',
					'assets/**/*.js',
					'assets/**/*.json',
					'js/routes.json',
					'js/routes.js',
					'design/awardWallet.js',
					'lib/scripts.js',
					'kernel/browserExt.js',
					'extension/*.js'
				],
				dest: 'build/web/'
			},
            edge: {
                expand: true,
                cwd: 'extension/compatible',
                src: '**',
                dest: 'extension/build/edge/unpacked'
            },
            edgeSpecific: {
                expand: true,
                cwd: 'extension/edge',
                src: '**',
                dest: 'extension/build/edge/unpacked'
            },
            edgeScripts: {
                expand: true,
                cwd: 'extension/edge',
                src: '**',
                dest: 'extension/build/edge/unpacked'
            },
            buildEdgeIcon44: {
			    src: 'extension/compatible/icons/white44.png',
                dest: 'extension/build/edge/packed/AwardWallet/edgeextension/manifest/Assets/Square44x44Logo.png'
            },
            buildEdgeIcon150: {
			    src: 'extension/compatible/icons/white150.png',
                dest: 'extension/build/edge/packed/AwardWallet/edgeextension/manifest/Assets/Square150x150Logo.png'
            },
            buildEdgeIcon180: {
			    src: 'extension/compatible/icons/white180.png',
                dest: 'extension/build/edge/packed/AwardWallet/edgeextension/manifest/Assets/StoreLogo.png'
            },
            buildEdgeXml: {
			    src: 'extension/build/edge/AwardWallet/edgeextension/manifest/appxmanifestPatched.xml',
                dest: 'extension/build/edge/AwardWallet/edgeextension/manifest/appxmanifest.xml'
            },
            editEdgeJson: {
			    src: 'extension/build/edge/unpacked/manifest.json',
                dest: 'extension/build/edge/unpacked/manifest.json',
				options: {
				  process: function (content, srcpath) {
					var params = JSON.parse(content);
					params["-ms-preload"] = {
						"backgroundScript": "backgroundScriptsAPIBridge.js",
						"contentScript": "contentScriptsAPIBridge.js"
					};
					return JSON.stringify(params, null, 2)
				  }
				}
            },
            firefox: {
				expand: true,
				cwd: 'extension/compatible',
			    src: '**',
                dest: 'extension/build/firefox'
            },
            chrome: {
				expand: true,
				cwd: 'extension/compatible',
			    src: '**',
                dest: 'extension/build/chrome'
            }
		},

		ngAnnotate: {
			all: {
				expand: true,
				cwd: 'build/web/assets',
				src: [
					'awardwalletnewdesign/js/**/*.js',
					'common/js/*.js'
				],
				dest: 'build/web/assets'
			}
		},

		requirejs: {
			compile: {
				options: {
					baseUrl: '.',
					mainConfigFile: "build/web/assets/requireConfig.js",
					appDir: "build/web/assets",
					skipDirOptimize: true,
					optimize: "none",
					optimizeCss: "none",
					dir: "web/b/" + grunt.option('assets_version'),
					paths: {
						requireConfig: 'requireConfig',
						requireProdConfig: 'requireProdConfig',
						boot: 'boot',
						'translator-boot': 'common/js/translator-boot',
						'intl': 'common/js/intl'
					},
					onBuildWrite: function (moduleName, path, contents) {
						//convert plugins into noop since it isn't needed after build
						if (moduleName == 'translator-boot')
                            return "// translator-boot defined in main page body\n"; // see main.html.twig
						else if (moduleName == 'intl')
                            return "// intl defined in main page body\n"; // see main.html.twig
						else {
							var UglifyJS = require('uglify-es'),
								uglified = UglifyJS.minify(contents, {
									safari10: true
								});

							return uglified.code;
						}
					},
					/**
					 * WARNING WARNING WARNING
					 *
					 * every module added to this config should be also added to requireProdConfig.js
					 *
					 */
					modules: [
						{
							name: 'boot',
							create: true,
							include: [
								'vendor/requirejs/require',
								'requireConfig',
								'requireProdConfig',
								'jquery-boot',
								'jqueryui'
							]
						},
						{
							name: 'commons',
							create: true,
							exclude: [
								'boot'
							],
							include: [
								'common/translator',
								'router',
								'routing',
								'lib/design',
								'lib/gdpr-quantcast',
								'lib/errorDialog',
								'translator-boot'
							]
						},
						{
							name: 'ui-boot',
							create: true,
							exclude: [
								'boot',
								'commons'
							],
							include: [
								'lib/customizer'
							]
						},
						{
							name: 'angular-boot',
							create: true,
							exclude: [
								'boot',
								'commons'
							],
							include: [
								'angular',
								/*'angular-hotkeys',*/
								'angular-scroll',
								'angular-ui-router',
								'directives/autoFocus',
								'directives/customizer',
								'directives/dialog',
								'filters/highlight',
								'filters/unsafe',
								'ng-infinite-scroll'
							]
						},
						{
							name: 'pages/landing/main',
							exclude: [
								'angular-boot',
								'commons',
								'boot'
							],
							include: [
								'pages/landing/controllers',
								'pages/landing/directives'
							]
						},
						{
								name: 'pages/addAccount/main',
								exclude: [
										'angular-boot',
										'commons',
										'boot',
										'oldscripts'
								]
						},
						{
								name: 'pages/accounts/controllers/accountForm',
								exclude: [
										'angular-boot',
										'commons',
										'boot',
										'oldscripts'
								]
						},
						{
							name: 'oldscripts',
							exclude: [
								'extension-boot',
								'extension-main',
								'angular-boot',
								'commons',
								'boot'
							]
						},
						{
							name: 'extension-boot',
							create: true,
							exclude: [
								'angular-boot',
								'commons',
								'boot'
							],
							include: [
								'extension-main'
							]
						}
					]
				}
			}
		},

		'string-replace': {
			requireConfig: {
				files: {
					'build/web/assets/requireProdConfig.js': 'build/web/assets/requireProdConfig.js',
					'build/web/assets/awardwalletnewdesign/js/lib/globalizer.js': 'build/web/assets/awardwalletnewdesign/js/lib/globalizer.js',
				},
				options: {
					replacements: [
						{
							pattern: '%version%',
							replacement: grunt.option('assets_version')
						},
						{
							pattern: '%cdn_host%',
							replacement: grunt.option('cdn_host') ? grunt.option('cdn_host') : ""
						}
					]
				}
			},
			firefox_twig: {
				files: {
					'bundles/AwardWallet/MainBundle/Resources/views/Extension/extensionInstall.html.twig': 'bundles/AwardWallet/MainBundle/Resources/views/Extension/extensionInstall.html.twig'
				},
				options: {
					replacements: [
						{
							pattern: /awardwallet-2\.\d+\.xpi/ig,
							replacement: 'awardwallet-' + extension_version + '.xpi'
						}
					]
				}
			},
			chrome_portcomm: {
				files: {
					'extension/build/chrome/src/content.js': 'extension/build/chrome/src/content.js'
				},
				options: {
					replacements: [
						{
							pattern: /var portComm = true/ig,
							replacement: "var portComm = false"
						}
					]
				}
			},
			chrome_version: {
				files: {
					'extension/build/chrome/src/content.js': 'extension/build/chrome/src/content.js'
				},
				options: {
					replacements: [
						{
							pattern: /'version': '\d+\.\d+'/ig,
							replacement: "'version': '" + extension_version + "'"
						}
					]
				}
			},
			firefox_version: {
				files: {
					'extension/build/firefox/src/content.js': 'extension/build/firefox/src/content.js'
				},
				options: {
					replacements: [
						{
							pattern: /'version': '\d+\.\d+'/ig,
							replacement: "'version': '" + extension_version + "'"
						}
					]
				}
			},
			firefox_portcomm: {
				files: {
					'extension/build/firefox/src/content.js': 'extension/build/firefox/src/content.js'
				},
				options: {
					replacements: [
						{
							pattern: /var portComm = true/ig,
							replacement: "var portComm = false"
						}
					]
				}
			},
			edge_version: {
				files: {
					'extension/build/edge/unpacked/src/content.js': 'extension/build/edge/unpacked/src/content.js'
				},
				options: {
					replacements: [
						{
							pattern: /document\.getElementById\('extParams'\).value = '\d+\.\d+'/ig,
							replacement: "document.getElementById('extParams').value = '" + extension_version + "'"
						}
					]
				}
			}
		},

		exec: {
		    buildEdge: {
		      	command: "node_modules/.bin/manifoldjs -l debug -p edgeextension -f edgeextension -m extension/build/edge/unpacked/manifest.json -d extension/build/edge/packed"
		    },
            packageEdge: {
		        command: "node_modules/.bin/manifoldjs -l debug -p edgeextension -d extension/build/edge/packed package extension/build/edge/packed/AwardWallet/edgeextension/manifest/"
            }
	  	},

        xmlpoke: {
            buildEdgeName: {
                options: {
                    namespaces: {
                        'xmlns': 'http://schemas.microsoft.com/appx/manifest/foundation/windows10',
						'uap': 'http://schemas.microsoft.com/appx/manifest/uap/windows10'
                    },
                    failIfMissing: true,
                    replacements: [
                        {
                            xpath: '/xmlns:Package/xmlns:Identity/@Name',
                            value: 'AwardWalletLLC.AwardWallet'
                        },
                        {
                            xpath: '/xmlns:Package/xmlns:Identity/@Publisher',
                            value: 'CN=B86AF1A8-1C44-432B-B454-02A0EEB31A57'
                        },
                        {
                            xpath: '/xmlns:Package/xmlns:Properties/xmlns:PublisherDisplayName',
                            value: 'AwardWallet LLC'
                        },
                        {
                            xpath: '/xmlns:Package/xmlns:Identity/@Version',
                            value: extension_version +'.0.0'
                        },
                        {
                            xpath: '/xmlns:Package/xmlns:Applications/xmlns:Application/uap:VisualElements/@BackgroundColor',
                            value: 'white'
                        }
                    ]
                },
                files: {
                    'extension/build/edge/packed/AwardWallet/edgeextension/manifest/appxmanifest.xml': 'extension/build/edge/packed/AwardWallet/edgeextension/manifest/appxmanifest.xml'
                }
            }
        },

		update_json: {
            firefox_manifest: {
            	options: {
					indent: '\t'
				},
            	src: "extension/build/firefox/manifest.json",
            	dest: "extension/build/firefox/manifest.json",
                fields: {
                    "applications": {
                        "gecko": {
                            "update_url": function(){ return "https://awardwallet.com/extension/firefoxUpdates.json"; }
                        }
                    }
                }
            },
            firefox_update: {
            	src: "web/extension/firefoxUpdates.json",
            	dest: "web/extension/firefoxUpdates.json",
                fields: {

					"$.addons.awardwallet.updates[0].version": extension_version,
					"$.addons.awardwallet.updates[0].update_link": "https://awardwallet.com/extension/awardwallet-" + extension_version + ".xpi"
                }
            }
        },

		compress: {
			firefox: {
				expand: true,
				cwd: 'extension/build/firefox',
				options: {
                    archive: 'extension/build/firefox/packed.zip'
                },
				src: '**',
				dest: ''
			},
			chrome: {
				expand: true,
				cwd: 'extension/build/chrome',
				options: {
                    archive: 'extension/build/chrome/packed.zip'
                },
				src: '**',
				dest: ''
			}
		},

        babel: {
            compile: {
                options: {
                    sourceMap: false,
					plugins: [
						"babel-plugin-transform-class-properties"
					],
                    presets: [
                    	[
                            "@babel/preset-env",
                            {
                                "targets": {
                                    "ie": "11"
                                }
                            }
						],
					],
                },
                files: [
					{
                        expand: true,
                        cwd: 'web',
                        src: [
                            'assets/awardwalletnewdesign/**/*.js',
                            'assets/common/js/*.js',
                        ],
                        dest: 'build/web/'
					}
				],
            },
        },

	});

    require('load-grunt-tasks')(grunt);

	grunt.registerTask('default', ['copy:nodeModulesToPublic','copy:reactFiles','clean:web', 'clean:b', 'copy:webToTemp', 'babel', 'string-replace:requireConfig', 'ngAnnotate', 'requirejs', 'clean:web']);

	grunt.registerTask(
		'build-edge',
		[
			'clean:buildEdge',
			'copy:edge',
			'copy:edgeSpecific',
			'copy:edgeScripts',
			'copy:editEdgeJson',
			'string-replace:edge_version',
			'exec:buildEdge',
			'copy:buildEdgeIcon150',
			'copy:buildEdgeIcon180',
			'copy:buildEdgeIcon44',
			'xmlpoke:buildEdgeName',
			'copy:buildEdgeXml',
			'clean:buildEdgeXml',
			'exec:packageEdge'
		]
	);

    grunt.registerTask('awardwallet:firefox_update', 'write firefox update config.', function () {
        grunt.file.write("web/extension/firefoxUpdates.json", JSON.stringify({
		  "addons": {
		    "awardwallet": {
		      "updates": [
		        {
		          "version": extension_version,
		          "update_link": "https://awardwallet.com/extension/awardwallet-" + extension_version + ".xpi"
		        }
		      ]
		    }
		  }
		}, null, '  '))
    });

    dev_mode = grunt.option('dev');

    function updateDomains(folder) {
    	if (dev_mode) {
    		grunt.log.writeln("dev mode, build folder: " + folder);

    		var data = JSON.parse(grunt.file.read("extension/build/" + folder + "/manifest.json"));
			data["content_scripts"][0]["matches"] = ["*://*.awardwallet.com/*", "*://*.awardwallet.docker/*"];
			data["description"] = data["description"] + " - Dev Version";
			data["name"] = data["name"] + " - Dev Version";
			grunt.file.write("extension/build/" + folder + "/manifest.json", JSON.stringify(data, null, '  '));

			data = grunt.file.read("extension/build/" + folder + "/src/background.js");
			data = data.replace(
				"var mask = /^https:\\/\\/([a-z0-9\\-]+\\.)?awardwallet\\.com[$\\/]/i",
				"var mask = /^http(s?):\\/\\/([a-z0-9\\-]+\\.)?awardwallet\\.(com|docker)[$\\/]/i"
			);
			grunt.file.write("extension/build/" + folder + "/src/background.js", data);
		}
	}

    grunt.registerTask('awardwallet:firefox_update_domains', 'write firefox domains', function () {
    	updateDomains('firefox');
    });

    grunt.registerTask('awardwallet:chrome_update_domains', 'write chrome domains', function () {
    	updateDomains('chrome');
    });

    grunt.registerTask('awardwallet:firefox_manifest', 'updating firefox manifest', function () {
    	var data = JSON.parse(grunt.file.read("extension/build/firefox/manifest.json"));
    	delete data["externally_connectable"];
        grunt.file.write("extension/build/firefox/manifest.json", JSON.stringify(data, null, '  '));
    });

	grunt.registerTask('awardwallet:chrome_replace_portcomm', 'replace chrome portComm', function () {
		if (dev_mode) {
			grunt.task.run(['string-replace:chrome_portcomm']);
		}
	});

	grunt.registerTask(
		'build-firefox',
		[
			'clean:buildFirefox',
			'copy:firefox',
			'update_json:firefox_manifest',
			'awardwallet:firefox_update',
			'awardwallet:firefox_manifest',
			'string-replace:firefox_version',
			'string-replace:firefox_portcomm',
			'awardwallet:firefox_update_domains',
			'compress:firefox',
			'string-replace:firefox_twig'
		]
	);

	grunt.registerTask(
		'build-chrome',
		[
			'clean:buildChrome',
			'copy:chrome',
			'awardwallet:chrome_update_domains',
			'compress:chrome',
			'string-replace:chrome_version',
			'awardwallet:chrome_replace_portcomm'
		]
	);

}
