define([
        'angular-boot', 'routing', 'translator-boot', 'angular-ui-router',
        'directives/customizer', 'directives/autoFocus',
        'pages/landing/controllers', 'pages/landing/directives'
    ],
    () => {
        angular
            .module('landingPage', [
                'ui.router', 'customizer-directive', 'auto-focus-directive',
                'appConfig', 'landingPage-ctrl', 'landingPage-dir'
            ])
            .config(['$stateProvider', '$urlRouterProvider', '$locationProvider', ($stateProvider, $urlRouterProvider, $locationProvider) => {
                // Video
                $('#video-btn').on('click', function(e) {
                    e.preventDefault();
                    $(this)
                        .replaceWith(
                            $('<iframe src="//player.vimeo.com/video/319469220?color=4684c4" width="545" height="307" frameborder="0" webkitallowfullscreen mozallowfullscreen allowfullscreen></iframe>')
                        );
                });

                $urlRouterProvider.otherwise(($injector, $location) => {
                    // if ('/index.php' !== $location.path()) {
                    //     location.href = $location.path();
                    // }
                });
                $urlRouterProvider.when(/^\/[a-z]{2}\//, $location => {
                    if ($location.path().substr(0, 4) !== `/${locale}/`) {
                        console.log('redirect to ', $location.path());
                        location.href = $location.path();
                        return true;
                    } else {
                        return false;
                    }
                });
                $stateProvider
                    .state('home', {
                        url: '/',
                        controller: 'homeCtrl'
                    })
                    .state('loc-home', {
                        url: '/{locale:[a-z][a-z]}/',
                        controller: 'homeCtrl'
                    })
                    .state('register', {
                        url: '/register',
                        templateUrl: '/register',
                        controller: 'registerCtrl'
                    })
                    .state('loc-register', {
                        url: '/{locale:[a-z][a-z]}/register',
                        templateUrl: '/register',
                        controller: 'registerCtrl'
                    })
                    .state('registerBusiness', {
                        url: '/registerBusiness',
                        templateUrl: '/registerBusiness',
                        controller: 'registerBusinessCtrl'
                    })
                    .state('loc-registerBusiness', {
                        url: '/{locale:[a-z][a-z]}/registerBusiness',
                        templateUrl: '/registerBusiness',
                        controller: 'registerBusinessCtrl'
                    })
                    .state('login', {
                        url: '/login',
                        templateUrl: '/login',
                        controller: 'loginCtrl'
                    })
                    .state('loc-login', {
                        url: '/{locale:[a-z][a-z]}/login',
                        templateUrl: '/login',
                        controller: 'loginCtrl'
                    })
                    .state('restore', {
                        url: '/restore',
                        templateUrl: '/restore',
                        controller: 'restoreCtrl'
                    })
                    .state('loc-restore', {
                        url: '/{locale:[a-z][a-z]}/restore',
                        templateUrl: '/restore',
                        controller: 'restoreCtrl'
                    });

                $locationProvider.html5Mode({
                    enabled: true,
                    rewriteLinks : false
                });

            }])
            .run();

        $(document).ready(() => {
            const app = document.getElementById('main-body');

            if (app) {
                angular.bootstrap(app, ['landingPage']);
            } else {
                angular.bootstrap(document.getElementsByClassName('page-landing__container')[0], ['landingPage']);
            }
        });
    });