module.exports = function (grunt) {
    grunt.initConfig({
        meta: {},
        pkg: grunt.file.readJSON('package.json'),
        config: {
            mobile: 'mobile/',
            temp: './tmp/mobile/',
            dist: 'web/m/',
            cordova: 'mobile/build/cordova/www/'
        },
        ngtemplates: {
            awTemplates: {
                cwd: '<%= config.mobile %>',
                src: 'templates/**/*.html',
                dest: '<%= config.mobile %>resources/templates.js',
                options: {
                    standalone: true
                }
            }
        },
        'string-replace': {
            git: {
                files: {
                    '<%= config.temp %>index.html': '<%= config.temp %>index.html'
                },
                options: {
                    replacements: [
                        {
                            pattern: '%version%',
                            replacement: '<%= ' + grunt.option('rev') + '%>'
                        }
                    ]
                }
            },
            cordova: {
                files: {
                    '<%= config.cordova %>index.html': '<%= config.cordova %>index.html'
                },
                options: {
                    replacements: [
                        {
                            pattern: '%version%',
                            replacement: '0+<%= ' + grunt.option('rev') + ' %>'
                        }
                    ]
                }
            },
            host: {
                files: {
                    '<%= config.temp %>index.html': '<%= config.temp %>index.html',
                    '<%= config.temp %>scripts/functions.js': '<%= config.temp %>scripts/functions.js',
                    '<%= config.temp %>resources/urlRules.json': '<%= config.temp %>resources/urlRules.json',
                    '<%= config.dist %>index.html': '<%= config.dist %>index.html',
                    '<%= config.dist %>scripts/functions.js': '<%= config.dist %>scripts/functions.js',
                    '<%= config.dist %>resources/urlRules.json': '<%= config.dist %>resources/urlRules.json',
                    '<%= config.cordova %>index.html': '<%= config.cordova %>index.html',
                    '<%= config.cordova %>scripts/functions.js': '<%= config.cordova %>scripts/functions.js',
                    '<%= config.cordova %>resources/urlRules.json': '<%= config.cordova %>resources/urlRules.json',
                    '<%= config.cordova %>urlRules.xml': '<%= config.cordova %>urlRules.xml'
                },
                options: {
                    replacements: [
                        {
                            pattern: '%host_name%',
                            replacement: '<%= grunt.option("host-name") %>'
                        },
                        {
                            pattern: '%host_scheme%',
                            replacement: '<%= grunt.option("host-scheme") %>'
                        },
                        {
                            pattern: '%base_url%',
                            replacement: '<%= grunt.option("base-url") %>'
                        }
                    ]
                }
            },
            csp: {
                files: {
                    '<%= config.temp %>index.html': '<%= config.temp %>index.html',
                    '<%= config.dist %>index.html': '<%= config.dist %>index.html',
                    '<%= config.cordova %>index.html': '<%= config.cordova %>index.html'
                },
                options: {
                    replacements: [
                        {
                            pattern: '%content-security-policy%',
                            replacement: '<%= grunt.option("csp-header") %>'
                        }
                    ]
                }
            }
        },
        babel: {
            options: {
                sourceMap: false,
                plugins: [
                    "babel-plugin-transform-class-properties"
                ],
                presets: ['@babel/preset-env', '@babel/preset-react']
            },
            dist: {
                files: [
                    {
                        expand: true,
                        cwd: '<%= config.mobile %>',
                        src: [
                            'scripts/controllers/**/*.js',
                            'scripts/directives/**/*.js',
                            'scripts/filters/*.js',
                            'scripts/services/**/*.js',
                            'scripts/*.js'
                        ],
                        dest: '<%= config.temp %>'
                    }
                ]
            }
        },
        ngAnnotate: {
            controllers: {
                expand: true,
                cwd: '<%= config.temp %>',
                src: ['scripts/controllers/**/*.js'],
                dest: '<%= config.temp %>'
            },
            directives: {
                expand: true,
                cwd: '<%= config.temp %>',
                src: ['scripts/directives/**/*.js'],
                dest: '<%= config.temp %>'
            },
            filters: {
                expand: true,
                cwd: '<%= config.temp %>',
                src: ['scripts/filters/*.js'],
                dest: '<%= config.temp %>'
            },
            services: {
                expand: true,
                cwd: '<%= config.temp %>',
                src: ['scripts/services/**/*.js'],
                dest: '<%= config.temp %>'
            },
            app: {
                expand: true,
                cwd: '<%= config.temp %>',
                src: ['scripts/*.js'],
                dest: '<%= config.temp %>'
            },
            vendor: {
                expand: true,
                cwd: '<%= config.mobile %>',
                src: [
                    'scripts/vendor/**/*.js',
                    '!scripts/vendor/bower_components/date-time-diff/src/*.js'
                ],
                dest: '<%= config.temp %>'
            }
        },
        clean: {
            mobile: {
                src: ['<%= config.dist %>']
            },
            build: {
                src: ['<%= config.temp %>']
            },
            cordova: {
                src: '<%= config.cordova %>'
            },
            release: {
                src: ['<%= config.temp %>/resources/languages', '<%= config.temp %>/resources/less', '<%= config.temp %>/scripts', '<%= config.temp %>/resources/css/*.css', '!<%= config.temp %>/resources/css/*.min.css', '<%= config.temp %>/resources/**/*.js']
            },
            bowerComponents:{
                src:[
                   '<%= config.mobile %>/scripts/vendor/bower_components'
                ]
            },
            tmp: {
                src: ['./tmp']
            },
            doc: {
                src: ['<%= config.mobile %>/build/doc']
            }
        },
        copy: {
            resources: {
                expand: true,
                cwd: '<%= config.mobile %>',
                src: 'resources/**/*',
                dest: '<%= config.temp %>'

            },
            templates: {
                expand: true,
                cwd: '<%= config.mobile %>',
                src: 'resources/templates.js',
                dest: '<%= config.temp %>'

            },
            styles: {
                expand: true,
                cwd: '<%= config.mobile %>',
                src: 'resources/css/*',
                dest: '<%= config.temp %>'

            },
            images: {
                expand: true,
                cwd: '<%= config.mobile %>',
                src: 'resources/img/*',
                dest: '<%= config.temp %>'

            },
            translations: {
                expand: true,
                cwd: '<%= config.mobile %>',
                src: 'resources/languages/*',
                dest: '<%= config.temp %>'

            },
            fonts: {
                expand: true,
                cwd: '<%= config.mobile %>',
                src: 'resources/font/*',
                dest: '<%= config.temp %>'

            },
            sounds: {
                expand: true,
                cwd: '<%= config.mobile %>',
                src: 'resources/sounds/*',
                dest: '<%= config.temp %>'

            },
            vendor: {
                expand: true,
                cwd: '<%= config.mobile %>',
                src: 'scripts/vendor/**/*',
                dest: '<%= config.temp %>'
            },
            cordova: {
                expand: true,
                cwd: '<%= config.temp %>',
                src: '**/*',
                dest: '<%= config.cordova %>'
            },
            languages: {
                expand: true,
                cwd: 'web/assets/translations/',
                src: '**/*',
                dest: '<%= config.mobile %>resources/languages/'
            },
            urlRules: {
                expand: true,
                cwd: 'bundles/AwardWallet/MainBundle/Resources/config/',
                src: ['urlRules.xml', 'urlRules.xsd'],
                dest: '<%= config.cordova %>'
            },
            mobile: {
                expand: true,
                cwd: '<%= config.temp %>',
                src: '**/*',
                dest: '<%= config.dist %>'
            }
        },
        watch: {
            options: {
                dateFormat: function (time) {
                    grunt.log.writeln('The watch finished in ' + time + 'ms at' + (new Date()).toString());
                    grunt.log.writeln('Waiting for more changes...');
                }
            },
            scripts: {
                files: ['scripts/**/*', 'resources/**/*', 'index.html'],
                tasks: ['default']
            }
        },
        includeSource: {
            options: {
                basePath: 'mobile'
            },
            'default': {
                files: {
                    '<%= config.temp %>index.html': '<%= config.mobile %>index.html'
                }
            },
            'cordova': {
                files: {
                    '<%= config.cordova %>index.html': '<%= config.cordova %>index.html'
                }
            }
        },
        useminPrepare: {
            html: '<%= config.temp %>index.html',
            options: {
                dest: '<%= config.temp %>',
                staging: './tmp/minified'
            }
        },
        usemin: {
            html: '<%= config.temp %>index.html',
            css: ['<%= config.temp %>/resources/css/{,*/}*.css'],
            js: ['<%= config.temp %>/scripts/*.js'],
            options: {
                assetsDirs: ['<%= config.temp %>', '<%= config.temp%>/resources/img', '<%= config.temp%>/resources/font'],
                patterns: {
                    js: [
                        [/(resources\/img\/.*?\.(?:gif|jpeg|jpg|png|webp))/gm, 'Update the JS to reference our revved images']
                    ]
                },
                baseURL: grunt.option('cdn_host') ? grunt.option('cdn_host') + '/m/' : ''
            }
        },
        filerev: {
            dist: {
                src: [
                    '<%= config.temp %>/scripts/**/*.js',
                    '<%= config.temp %>/resources/css/*.css',
                    '<%= config.temp %>/resources/font/*',
                    '<%= config.temp %>/resources/img/*.{png,gif}'
                ]
            }
        },
        uglify: {
            options: {
                mangle: false
            }
        },
        wiredep: {
            task: {
                // Point to the files that should be updated when
                // you run `grunt wiredep`
                src: [
                    '<%= config.mobile %>index.html'
                ],

                options: {
                    directory: '<%= config.mobile %>scripts/vendor/bower_components', // default: '.bowerrc'.directory || bower_components
                    bowerJson: grunt.file.readJSON('mobile/bower.json'), //bower.json file contents
                    onPathInjected: function (fileObject) {
                        //console.log(fileObject);
                    }
                }
            }
        },
        'bower-install-simple': {
            options: {
                color: true,
                cwd: '<%= config.mobile %>',
                directory: 'scripts/vendor/bower_components'
            },
            "prod": {
                options: {
                    production: true
                }
            }
        },
        remove_usestrict: {
            dist: {
                files: [
                    {
                        expand: true,
                        cwd: '<%= config.temp %>',
                        dest: '<%= config.temp %>',
                        src: ['**/*.js']
                    }
                ]
            }
        },
        less: {
            production: {
                options: {
                    paths: ['<%= config.mobile %>resources/less'],
                    compress: true
                },
                files: {
                    '<%= config.mobile %>resources/css/style.css': '<%= config.mobile %>resources/less/style.less'
                }
            }
        },
        targethtml: {
            cordova: {
                files: {
                    '<%= config.cordova %>index.html': '<%= config.cordova %>index.html'
                }
            },
            cordovaRelease: {
                files: {
                    '<%= config.cordova %>index.html': '<%= config.cordova %>index.html'
                }
            },
            mobile: {
                files: {
                    '<%= config.dist %>index.html': '<%= config.dist %>index.html'
                }
            },
            mobileRelease: {
                files: {
                    '<%= config.temp %>index.html': '<%= config.temp %>index.html'
                }
            }
        },
        convert: {
            options: {
                explicitArray: false
            },
            urlRules: {
                files: [
                    {
                        expand: true,
                        cwd: 'bundles/AwardWallet/MainBundle/Resources/config/',
                        src: ['urlRules.xml'],
                        dest: '<%= config.temp %>resources',
                        ext: '.json'
                    }
                ]
            },
            urlRulesCordova: {
                files: [
                    {
                        expand: true,
                        cwd: 'bundles/AwardWallet/MainBundle/Resources/config/',
                        src: ['urlRules.xml'],
                        dest: '<%= config.cordova %>resources',
                        ext: '.json'
                    }
                ]
            }
        },
        jsdoc : {
            dist : {
                src: ['<%= config.mobile %>/scripts/vendor/ng-cordova.js'],
                options: {
                    destination: '<%= config.mobile %>/build/doc'
                }
            }
        }
    });

    grunt.loadNpmTasks('grunt-ng-annotate');
    grunt.loadNpmTasks('grunt-string-replace');
    grunt.loadNpmTasks('grunt-contrib-copy');
    grunt.loadNpmTasks('grunt-contrib-clean');
    grunt.loadNpmTasks('grunt-contrib-watch');
    grunt.loadNpmTasks('grunt-include-source');
    grunt.loadNpmTasks('grunt-angular-templates');
    grunt.loadNpmTasks('@awardwallet/grunt-usemin');
    grunt.loadNpmTasks('grunt-contrib-uglify');
    grunt.loadNpmTasks('grunt-contrib-concat');
    grunt.loadNpmTasks('grunt-contrib-cssmin');
    grunt.loadNpmTasks('grunt-filerev');
    grunt.loadNpmTasks('grunt-wiredep');
    grunt.loadNpmTasks("grunt-bower-install-simple");
    grunt.loadNpmTasks('grunt-remove-usestrict');
    grunt.loadNpmTasks('grunt-contrib-less');
    grunt.loadNpmTasks('grunt-targethtml');
    grunt.loadNpmTasks('grunt-convert');
    grunt.loadNpmTasks('grunt-jsdoc');
    grunt.loadNpmTasks('grunt-babel');

    grunt.registerTask('build:mobile:templates', ['ngtemplates']);
    grunt.registerTask('build:mobile:bower', ['bower-install-simple', 'wiredep']);
    grunt.registerTask('build:mobile:less', ['less:production']);
    grunt.registerTask('copy:mobile:resources', ['copy:styles', 'copy:templates', 'copy:translations', 'copy:images', 'copy:fonts', 'copy:sounds']);
    grunt.registerTask('build:mobile', '', function () {
        grunt.task.run([
            'clean:build',
            'clean:bowerComponents',
            'build:mobile:bower',
            'babel',
            'ngAnnotate',
            'copy:languages',
            'build:mobile:less',
            'copy:mobile:resources',
            'includeSource:default',
            'replace:mobile:vars',
            'remove_usestrict',
            'string-replace:git',
            'convert:urlRules'
        ]);
    });
    grunt.registerTask('build:mobile:dev', '', function () {
        grunt.task.run([
            'clean:mobile',
            'build:mobile',
            'copy:mobile',
            'targethtml:mobile',
            'replace:mobile:vars',
            'clean:tmp'
        ]);
    });
    grunt.registerTask('build:mobile:release:git', 'Mobile build release', function () {
        grunt.task.run([
            'build:mobile',
            'useminPrepare',
            'concat',
            'clean:release',
            'targethtml:mobileRelease',
            'uglify',
            'cssmin',
            'filerev',
            'usemin',
            'replace:mobile:vars',
            'copy:mobile',
            'clean:tmp'
        ]);
    });
    grunt.registerTask('build:cordova', [
        'build:mobile',
        'clean:cordova',
        'copy:cordova',
        'string-replace:cordova',
        'includeSource:cordova',
        'targethtml:cordova',
        'copy:urlRules',
        'convert:urlRulesCordova',
        'clean:tmp'
    ]);
    grunt.registerTask('build:cordova:release', 'Cordova release', function () {
        grunt.option('host-scheme', 'https');
        grunt.option('host-name', 'awardwallet.com');
        grunt.task.run([
            'build:mobile',
            'clean:cordova',
            'copy:cordova',
            'string-replace:cordova',
            'includeSource:cordova',
            'targethtml:cordovaRelease',
            'copy:urlRules',
            'convert:urlRulesCordova',
            'replace:mobile:vars',
            'clean:tmp'
        ]);
    });
    grunt.registerTask('build:mobile:doc', ['clean:doc', 'jsdoc']);
    grunt.registerTask('replace:mobile:vars', 'Replaces variables', function () {
        var csp = require('csp-header');
        var params = grunt.file.readYAML('app/config/parameters.yml').parameters;

        if (!grunt.option('host-scheme')) grunt.option('host-scheme', params.requires_channel ? params.requires_channel : "https");
        if (!grunt.option('host-name')) grunt.option('host-name', params.host ? params.host : "awardwallet.com");
        var cdn_host = params.cdn_host;
        grunt.option('scheme-host', grunt.option('host-scheme') + "://" + grunt.option('host-name'));
        if (!grunt.option('base-url')) grunt.option('base-url', grunt.option('scheme-host'));
        grunt.option('csp-header', csp({
            policies: {
                'default-src': [
                    '*',
                    'gap:',
                    'https://*.google.com',
                    'https://*.googleapis.com',
                    'https://*.gstatic.com',
                    'https://*.googleusercontent.com'
                ],
                'img-src': [
                    csp.SELF,
                    'https://*.awardwallet.com',
                    'https://awardwallet.com',
                    'data:',
                    'https://www.google-analytics.com',
                    'https://*.gstatic.com',
                    'https://*.googleapis.com',
                    'https://stats.g.doubleclick.net',
                    'https:' + cdn_host,
                    'http:' + cdn_host,
                    'https://hm.baidu.com',
                    'https://www.google.com/ads/',
                    'https://s.yimg.com'
                ],
                'style-src': [
                    csp.SELF,
                    csp.INLINE,
                    'https://*.googleapis.com',
                    'https://www.googletagmanager.com',
                    'https:' + cdn_host,
                    'http:' + cdn_host
                ],
                'script-src': [
                    csp.SELF,
                    csp.INLINE,
                    csp.EVAL,
                    'https://www.google-analytics.com',
                    'https://www.googletagmanager.com',
                    'https://www.google.com/recaptcha/',
                    'https://www.gstatic.com/recaptcha/',
                    'https://maps.googleapis.com',
                    'https:' + cdn_host,
                    'http:' + cdn_host,
                    'https://hm.baidu.com',
                    'https://challenges.cloudflare.com/turnstile/v0/api.js'
                ],
                'frame-src': ['gap:', 'https://*.youcanbook.me', 'https://*.youcanbook.me/*', 'https://www.google.com/recaptcha/', 'https://challenges.cloudflare.com/'],
                'connect-src': [
                    csp.SELF,
                    'https://www.google-analytics.com',
                    'https://www.googletagmanager.com',
                    'https://stats.g.doubleclick.net',
                    'http://*.awardwallet.dev',
                    'http://awardwallet.dev',
                    'https://*.awardwallet.dev',
                    'https://awardwallet.dev',
                    'http://*.awardwallet.com',
                    'http://awardwallet.com',
                    'https://*.awardwallet.com',
                    'https://awardwallet.com',
                    'http://comet-dev.awardwallet.com',
                    'https://comet-dev.awardwallet.com',
                    'wss://awardwallet.com',
                    'wss://*.awardwallet.com',
                    'ws://*.awardwallet.com',
                    'ws://awardwallet.com',
                    'file:'
                ],
                'font-src': [
                    csp.SELF,
                    'https://*.gstatic.com',
                    'https:' + cdn_host,
                    'http:' + cdn_host,
                    'https://awardwallet.com'
                ]
            },
            extend: {
                'img-src': [grunt.option("scheme-host"), grunt.option('base-url')],
                'connect-src': [grunt.option("scheme-host"), grunt.option('base-url')]
            }
        }));

        grunt.log.writeln('Host scheme: ' + grunt.option('host-scheme'));
        grunt.log.writeln('Host name: ' + grunt.option('host-name'));
        grunt.log.writeln('Base URL: ' + grunt.option('base-url'));
        grunt.log.writeln('CSP Header: ' + grunt.option('csp-header'));

        grunt.task.run('string-replace:host');
        grunt.task.run('string-replace:csp');
    });
};
