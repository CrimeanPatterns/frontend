angular.module('AwardWalletMobile').config([
    '$stateProvider',
    '$urlRouterProvider',
    '$httpProvider',
    '$locationProvider',
    '$stickyStateProvider',
    function ($stateProvider, $urlRouterProvider, $provide, $locationProvider, $stickyStateProvider) {
        $locationProvider.html5Mode(!platform.cordova);
        $urlRouterProvider.otherwise('/');
        $stateProvider.state('unauth', {
            abstract: true,
            templateUrl: 'templates/views/index-unauth.html',
            requireLogin: false,
            resolve: {
                auth: [
                    '$q',
                    'UserService',
                    'Database',
                    function ($q, UserService, Database) {
                        return UserService.isLoginIn().then(function (authorized) {
                            if (authorized) {
                                return Database.get().then(function (response) {
                                    return true;
                                }, function () {
                                    return true;
                                });
                            }
                            return true;
                        }, function () {
                            return true;
                        });
                    }
                ]
            }
        }).state('unauth.miles', {
            url: '/mile',
            requireLogin: false,
            abstract: true,
            templateUrl: 'templates/views/miles/index.html',
            controller: 'MileTimesController',
        }).state('unauth.miles.transfer', {
            url: '/transfer-times',
            deepStateRedirect: true,
            sticky: true,
            views: {
                'header@main': {
                    templateUrl: 'templates/views/miles/headers/transfer.html',
                },
                'content@main': {
                    templateUrl: 'templates/views/miles/content.html',
                    controller: 'MileController',
                }
            },
            requireLogin: false,
            onEnter: ['Translator', 'MetaTagsService', (Translator, MetaTagsService) => {
                MetaTagsService.setTags({
                    title: Translator.trans('transfer_times', {}, 'messages') + ' | AwardWallet',
                    canonical: 'https://awardwallet.com/en/mile-transfer-times',
                });
            }],
        }).state('unauth.miles.purchase', {
            url: '/purchase-times',
            deepStateRedirect: true,
            sticky: true,
            views: {
                'header@main': {
                    templateUrl: 'templates/views/miles/headers/purchase.html',
                },
                'content@second': {
                    templateUrl: 'templates/views/miles/content.html',
                    controller: 'MileController',
                }
            },
            requireLogin: false
        }).state('unauth.merchants', {
            url: '/merchants',
            requireLogin: false,
            abstract: true,
            templateUrl: 'templates/views/merchants/index.html',
            controller: ['SessionService', '$scope', function (SessionService, $scope) {
                $scope.authorized = SessionService.getProperty('authorized');
            }]
        }).state('unauth.merchants.reverse', {
            url: '/merchant-reverse',
            views: {
                'header@main': {
                    templateUrl: 'templates/views/merchants/headers/reverse.html',
                },
                'content@second': {
                    templateUrl: 'templates/views/merchants/content/reverse.html',
                    controller: 'MerchantReverseController',
                }
            },
            requireLogin: false
        }).state('unauth.merchants.lookup', {
            url: '',
            requireLogin: false,
            views: {
                'header@main': {
                    templateUrl: 'templates/views/merchants/headers/lookup.html',
                },
                'content@main': {
                    templateUrl: 'templates/views/merchants/content/lookup.html',
                    controller: 'MerchantLookupController',
                }
            },
            onEnter: ['Translator', 'MetaTagsService', (Translator, MetaTagsService) => {
                MetaTagsService.setTags({
                    title: Translator.trans('merchant_lookup.seo_title', {}, 'messages'),
                    description: Translator.trans('merchant_lookup.description', {}, 'messages'),
                    canonical: 'https://awardwallet.com/en/merchants',
                });
            }],
        }).state('unauth.merchants.lookup.offer', {
            url: '/:merchantName',
            params: {
                merchantName: {
                    value: null
                }
            },
            views: {
                'header@main': {
                    templateUrl: 'templates/views/merchants/headers/offer.html',
                },
                'content@second': {
                    templateUrl: 'templates/views/merchants/content/offer.html',
                    controller: 'MerchantLookupOfferController',
                }
            },
            requireLogin: false
        }).state('unauth.login', {
            url: '/login?BackTo',
            controller: 'LoginController',
            templateUrl: 'templates/views/login.html',
            requireLogin: false,
            params: {
                toState: {
                    value: null
                },
                toParams: {
                    value: null
                },
                toPath: {
                    value: null
                },
                BackTo: {
                    value: null
                },
            },
            onEnter: ['Translator', 'MetaTagsService', (Translator, MetaTagsService) => {
                MetaTagsService.setTags({
                    title: Translator.trans('meta.title', {}, 'messages'),
                    description: Translator.trans('meta.description', {}, 'messages'),
                    canonical: 'https://awardwallet.com/en/login',
                });
            }],
        }).state('unauth.security-questions', {
            url: '/security-questions',
            controller: 'SecurityQuestionsController',
            templateUrl: 'templates/views/questions.html',
            requireLogin: false,
            params: {
                request: {
                    value: null
                },
                securityQuestions: {
                    value: null
                }
            }
        }).state('unauth.registration', {
            url: '/registration',
            controller: 'RegistrationController',
            templateUrl: 'templates/views/registration.html',
            requireLogin: false,
            params: {
                toState: {
                    value: null
                },
                toParams: {
                    value: null
                }
            },
            onEnter: ['MetaTagsService', (MetaTagsService) => {
                MetaTagsService.setTags({
                    canonical: 'https://awardwallet.com/en/register',
                });
            }],
        }).state('unauth.password-recovery', {
            url: '/password-recovery',
            params: {
                UserId: {
                    value: null
                },
                Hash: {
                    value: null
                }
            },
            controller: 'RecoveryPasswordController',
            templateUrl: 'templates/views/recovery-password.html',
            requireLogin: false
        }).state('unauth.password-recovery.recovery', {
            url: '/:UserId/:Hash',
            params: {
                UserId: {
                    value: null
                },
                Hash: {
                    value: null
                }
            },
            controller: 'RecoveryPasswordController',
            templateUrl: 'templates/views/recovery-password.html',
            requireLogin: false
        }).state('unauth.about', {
            url: '/about',
            controller: 'AboutController',
            templateUrl: 'templates/views/static/about.html',
            requireLogin: false,
            onEnter: ['Translator', 'MetaTagsService', (Translator, MetaTagsService) => {
                MetaTagsService.setTags({
                    title: Translator.trans('page.aboutus.title'),
                    description: Translator.trans('technology-company-loyalty-industry'),
                    canonical: 'https://awardwallet.com/en/page/about',
                });
            }],
        }).state('unauth.privacy', {
            url: '/privacy',
            controller: 'PrivacyController',
            templateUrl: 'templates/views/static/privacy.html',
            requireLogin: false,
            onEnter: ['Translator', 'MetaTagsService', (Translator, MetaTagsService) => {
                MetaTagsService.setTags({
                    title: 'Privacy Notice | AwardWallet',
                    canonical: 'https://awardwallet.com/en/page/privacy',
                });
            }],
        }).state('unauth.terms', {
            url: '/terms',
            controller: 'TermsController',
            templateUrl: 'templates/views/static/terms.html',
            requireLogin: false,
            onEnter: ['Translator', 'MetaTagsService', (Translator, MetaTagsService) => {
                MetaTagsService.setTags({
                    title: 'Terms of Use | AwardWallet',
                    canonical: 'https://awardwallet.com/en/page/terms',
                });
            }],
        }).state('index', {
            abstract: true,
            requireLogin: false,
            resolve: {
                auth: [
                    '$q',
                    'UserService',
                    'Database',
                    function ($q, UserService, Database) {
                        return UserService.isLoginIn().then(function (authorized) {
                            if (authorized) {
                                return Database.get().then(function (response) {
                                    return response;
                                }, function () {
                                    return $q.reject({logout: true});
                                });
                            }
                            return $q.reject();
                        }, function () {
                            return $q.reject();
                        });
                    }
                ]
            },
            views: {
                'menu@index': {
                    templateUrl: 'templates/views/menu.html',
                    controller: 'MenuController'
                },
                '': {
                    templateUrl: 'templates/views/index.html',
                    controller: 'IndexController'
                }
            }
        }).state('index.pincode-info', {
            url: '/pincode',
            controller: 'PinInfoController',
            templateUrl: 'templates/views/pincode-info.html',
            requireLogin: true,
            params: {
                toState: {
                    value: null
                },
                toParams: {
                    value: null
                }
            }
        }).state('index.api-access', {
            url: '/connections/approve/:Hash/:AccessLevel/:AuthKey',
            controller: 'ApiAccessController',
            templateUrl: 'templates/views/api/index.html',
            requireLogin: true,
            params: {
                Hash: {
                    value: null
                },
                AccessLevel: {
                    value: null
                },
                AuthKey: {
                    value: null,
                    squash: true
                }
            },
            resolve: {
                connectionInfo: [
                    '$q',
                    '$http',
                    '$stateParams',
                    function ($q, $http, $stateParams) {
                        var q = $q.defer();
                        var url = '/connections/approve/' + $stateParams.Hash + '/' + $stateParams.AccessLevel;

                        if ($stateParams.AuthKey) {
                            url += '/' + $stateParams.AuthKey;
                        }

                        $http({
                            method: 'get',
                            url: url,
                            timeout: 30000,
                            globalError: false
                        }).then(function (response) {
                            return q.resolve(response);
                        }, function (response) {
                            return q.resolve(response);
                        });
                        return q.promise;
                    }
                ]
            }
        }).state('index.booking', {
            url: '/booking',
            controller: 'BookingController',
            templateUrl: 'templates/views/booking/index.html',
            requireLogin: true,
            abstract: true
        }).state('index.booking.list', {
            url: '',
            requireLogin: true,
            views: {
                'header@main': {
                    templateUrl: 'templates/views/booking/headers/bookings.html'
                },
                'content@main': {
                    templateUrl: 'templates/views/booking/content/bookings.html'
                }
            }
        }).state('index.booking.new-request', {
            url: '/new-request',
            requireLogin: true,
            views: {
                'header@main': {
                    templateUrl: 'templates/views/booking/headers/new-request.html'
                },
                'content@main': {
                    templateUrl: 'templates/views/booking/content/new-request.html'
                }
            }
        }).state('index.booking.request', {
            url: '/:Id',
            requireLogin: true,
            abstract: true,
            params: {
                Id: {
                    value: null
                },
                reload: {
                    value: null
                }
            },
            resolve: {
                RequestID: ['$stateParams',
                    function ($stateParams) {
                        return $stateParams.Id || null;
                    }
                ],
                reload: ['$stateParams', 'Database', function ($stateParams, Database) {
                    if ($stateParams.reload) {
                        return Database.update();
                    }
                    return true;
                }]
            },
            views: {
                'content@main': {
                    templateUrl: 'templates/views/booking/content/request.html',
                    controller: 'BookingRequestController'
                }
            }
        }).state('index.booking.request.details', {
            url: '/details',
            requireLogin: true,
            views: {
                'header@main': {
                    templateUrl: 'templates/views/booking/headers/request-details.html'
                },
                '': {
                    templateUrl: 'templates/views/booking/content/request-details.html'
                },
                'footer@main': {
                    templateUrl: 'templates/views/booking/footers/request-details.html'
                }
            }
        }).state('index.booking.request.not-verified', {
            url: '/not-verified',
            requireLogin: true,
            views: {
                'header@main': {
                    templateUrl: 'templates/views/booking/headers/not-verified.html'
                },
                '': {
                    templateUrl: 'templates/views/booking/content/not-verified.html'
                },
                'footer@main': {
                    templateUrl: 'templates/views/booking/footers/not-verified.html'
                }
            }
        }).state('index.booking.request.not-exists', {
            url: '/not-exists',
            requireLogin: true,
            views: {
                'header@main': {
                    templateUrl: 'templates/views/booking/headers/not-exists.html'
                },
                '': {
                    templateUrl: 'templates/views/booking/content/not-exists.html'
                }
            }
        })/*Native*/.state('index.booking-native', {
            url: '/booking-native',
            controller: 'BookingController',
            templateUrl: 'templates/views/booking-native/index.html',
            requireLogin: true,
            abstract: true
        }).state('index.booking-native.request', {
            url: '/:Id',
            requireLogin: true,
            abstract: true,
            params: {
                Id: {
                    value: null
                },
                reload: {
                    value: null
                }
            },
            resolve: {
                RequestID: ['$stateParams',
                    function ($stateParams) {
                        return $stateParams.Id || null;
                    }
                ],
                reload: ['$stateParams', 'Database', function ($stateParams, Database) {
                    if ($stateParams.reload) {
                        return Database.update();
                    }
                    return true;
                }]
            },
            views: {
                'content@main': {
                    templateUrl: 'templates/views/booking-native/content/request.html',
                    controller: 'BookingRequestController'
                }
            }
        }).state('index.booking-native.request.details', {
            url: '/details',
            requireLogin: true,
            views: {
                '': {
                    templateUrl: 'templates/views/booking-native/content/request-details.html'
                },
                'footer@main': {
                    templateUrl: 'templates/views/booking-native/footers/request-details.html'
                }
            }
        }).state('index.booking-native.request.not-verified', {
            url: '/not-verified',
            requireLogin: true,
            views: {
                '': {
                    templateUrl: 'templates/views/booking-native/content/not-verified.html'
                }
            }
        }).state('index.booking-native.request.not-exists', {
            url: '/not-exists',
            requireLogin: true,
            views: {
                '': {
                    templateUrl: 'templates/views/booking-native/content/not-exists.html'
                }
            }
        }).state('shared', {
            url: '^/timeline/shared/:SharedKey',
            abstract: true,
            requireLogin: false,
            templateUrl: 'templates/views/timeline/shared.html',
            controller: 'TimelineSharedController',
            params: {
                SharedKey: {
                    value: null
                }
            },
            resolve: {
                Timeline: ['$stateParams', 'TimelineShared',
                    function ($stateParams, TimelineShared) {
                        return TimelineShared.getShared($stateParams.SharedKey);
                    }
                ],
                SharedKey: ['$stateParams',
                    function ($stateParams) {
                        return $stateParams.SharedKey || null;
                    }
                ]
            }
        }).state('shared.timeline', {
            url: '',
            deepStateRedirect: true,
            sticky: true,
            requireLogin: false,
            views: {
                'header@list': {
                    templateUrl: 'templates/views/timeline/shared/headers/timeline.html'
                },
                'content@list': {
                    templateUrl: 'templates/views/timeline/shared/content/timeline.html'
                }
            }
        }).state('shared.segment', {
            url: '/:Segment/:SubId',
            requireLogin: false,
            deepStateRedirect: true,
            sticky: true,
            abstract: true,
            views: {
                'content@details': {
                    templateUrl: 'templates/views/timeline/shared/content/segment.html',
                    controller: 'TimelineSharedSegmentController'
                }
            },
            params: {
                SharedKey: {
                    value: null
                },
                Segment: {
                    value: null
                },
                SubId: {
                    value: null
                }
            },
            resolve: {
                Segment: ['$stateParams',
                    function ($stateParams) {
                        return $stateParams.Segment || null;
                    }
                ],
                SubId: ['$stateParams',
                    function ($stateParams) {
                        return $stateParams.SubId || null;
                    }
                ]
            }
        }).state('shared.segment.details', {
            url: '/details',
            requireLogin: false,
            views: {
                'header@details': {
                    templateUrl: 'templates/views/timeline/shared/headers/segment-details.html'
                },
                '': {
                    templateUrl: 'templates/views/timeline/shared/content/details.html'
                }
            }
        }).state('shared.segment.phones', {
            url: '/phones',
            requireLogin: false,
            views: {
                'header@details': {
                    templateUrl: 'templates/views/timeline/shared/headers/segment-phones.html'
                },
                '': {
                    templateUrl: 'templates/views/timeline/shared/content/phones.html',
                    controller: 'TimelinePhonesController'
                }
            }
        }).state('shared.segment.flights', {
            url: '/flights',
            requireLogin: false,
            views: {
                'header@details': {
                    templateUrl: 'templates/views/timeline/shared/headers/segment-flights.html'
                },
                '': {
                    templateUrl: 'templates/views/timeline/shared/content/flights.html',
                    controller: 'TimelineAlternativeFlightsController'
                }
            }
        }).state('index.timeline', {
            url: '/timeline/{Id:my|[0-9]+}',
            controller: 'TimelineController',
            templateUrl: 'templates/views/timeline/index.html',
            requireLogin: true,
            abstract: true,
            params: {
                Id: {
                    value: 'my'
                },
                SharedKey: {
                    value: ''
                }
            },
            resolve: {
                travelerId: ['$stateParams',
                    function ($stateParams) {
                        return $stateParams.Id || null;
                    }
                ]
            }
        }).state('index.timeline.travelers', {
            url: '^/timeline',
            requireLogin: true,
            views: {
                'header@index': {
                    templateUrl: 'templates/views/timeline/headers/travelers.html'
                },
                'content@list': {
                    templateUrl: 'templates/views/timeline/content/travelers.html',
                    controller: 'TimelineTravelersController'
                }
            }
        }).state('index.timeline.list', {
            url: '',
            requireLogin: true,
            deepStateRedirect: true,
            sticky: true,
            views: {
                'header@index': {
                    templateUrl: 'templates/views/timeline/headers/timeline.html'
                },
                'content@list': {
                    templateUrl: 'templates/views/timeline/content/timeline.html',
                    controller: 'TimelineListController'
                }
            }
        }).state('index.timeline.segment', {
            url: '/:Segment/:SubId',
            requireLogin: true,
            deepStateRedirect: true,
            sticky: true,
            abstract: true,
            views: {
                'content@details': {
                    templateUrl: 'templates/views/timeline/content/segment.html',
                    controller: 'TimelineSegmentController'
                }
            },
            params: {
                Id: {
                    value: 'my'
                },
                Segment: {
                    value: null
                },
                SubId: {
                    value: null
                },
                reload: {
                    value: null
                }
            },
            resolve: {
                Segment: ['$stateParams',
                    function ($stateParams) {
                        return $stateParams.Segment || null;
                    }
                ],
                SubId: ['$stateParams',
                    function ($stateParams) {
                        return $stateParams.SubId || null;
                    }
                ],
                reload: ['$stateParams', 'Database', function ($stateParams, Database) {
                    if ($stateParams.reload) {
                        return Database.update();
                    }
                    return true;
                }]
            }
        }).state('index.timeline.segment.details', {
            url: '/details',
            requireLogin: true,
            views: {
                'header@index': {
                    templateUrl: 'templates/views/timeline/headers/segment-details.html'
                },
                '': {
                    templateUrl: 'templates/views/timeline/content/details.html'
                }
            }
        }).state('index.timeline.segment.phones', {
            url: '/phones',
            requireLogin: true,
            views: {
                'header@index': {
                    templateUrl: 'templates/views/timeline/headers/segment-phones.html'
                },
                '': {
                    templateUrl: 'templates/views/timeline/content/phones.html',
                    controller: 'TimelinePhonesController'
                }
            }
        }).state('index.timeline.segment.flights', {
            url: '/flights',
            requireLogin: true,
            views: {
                'header@index': {
                    templateUrl: 'templates/views/timeline/headers/segment-flights.html'
                },
                '': {
                    templateUrl: 'templates/views/timeline/content/flights.html',
                    controller: 'TimelineAlternativeFlightsController'
                }
            }
        }).state('index.accounts', {
            url: '',
            abstract: true,
            requireLogin: true,
            deepStateRedirect: true,
            views: {
                '': {
                    templateUrl: 'templates/views/accounts/index.html'
                }
            }
        }).state('index.accounts.list', {
            url: '/',
            requireLogin: true,
            sticky: true,
            views: {
                'content@first': {
                    templateUrl: 'templates/views/accounts/accounts-list.html',
                    controller: 'AccountsListController'
                }
            }
        }).state('index.accounts.totals', {
            url: '/accounts/totals',
            requireLogin: true,
            views: {
                'content@second': {
                    templateUrl: 'templates/views/accounts/accounts-totals.html',
                    controller: 'AccountsTotalsController'
                }
            }
        }).state('index.accounts.account-details', {
            url: '/account/details/:Id/:subId',
            requireLogin: true,
            views: {
                'content@second': {
                    templateUrl: 'templates/views/accounts/account-details.html',
                    controller: 'AccountDetailsController'
                }
            },
            params: {
                Id: {
                    value: null
                },
                subId: {
                    value: null
                }
            },
            resolve: {
                reload: ['$stateParams', 'Database', function ($stateParams, Database) {
                    if ($stateParams.reload) {
                        return Database.update();
                    }
                    return true;
                }]
            }
        }).state('index.accounts.account-history', {
            url: '/account/history/:Id/:subId',
            requireLogin: true,
            views: {
                'content@second': {
                    templateUrl: 'templates/views/accounts/account-history.html',
                    controller: 'AccountHistoryController'
                }
            },
            params: {
                Id: {
                    value: null
                },
                subId: {
                    value: null
                }
            }
        }).state('index.accounts.account-history-offer', {
            url: '/account/history-offer/:uuid',
            requireLogin: true,
            views: {
                'content@second': {
                    templateUrl: 'templates/views/accounts/account-history-offer.html',
                    controller: 'AccountHistoryOfferController'
                }
            },
            params: {
                uuid: {
                    value: null
                },
                extraData: {
                    value: {}
                }
            }
        }).state('index.accounts.account-barcode', {
            url: '/account/barcode/:type/:Id/:subId',
            requireLogin: true,
            params: {
                type: {
                    value: 'parsed'
                },
                Id: {
                    value: null
                },
                subId: {
                    value: null,
                    squash: true
                }
            },
            views: {
                'content@second': {
                    templateUrl: 'templates/views/accounts/barcode.html',
                    controller: 'BarcodeController'
                }
            }
        }).state('index.accounts.add', {
            url: '/accounts/add/:kindId',
            requireLogin: true,
            params: {
                kindId: {
                    value: null,
                    squash: true
                },
                scanData: {
                    value: null
                },
                formData: {
                    value: null
                }
            },
            views: {
                'content@second': {
                    templateUrl: function (stateParams) {
                        if (stateParams.kindId !== null)
                            return 'templates/views/accounts/accounts-select-provider.html';
                        return 'templates/views/accounts/accounts-add.html';
                    },
                    controller: 'AccountsAddController'
                }
            }
        }).state('index.accounts.account-add', {
            url: '/account/add/:providerId/:oauthKey?error',
            requireLogin: true,
            params: {
                providerId: {
                    value: null
                },
                oauthKey: {
                    value: null,
                    squash: true
                },
                error: {
                    value: null,
                    squash: true
                },
                requestData: {
                    value: null
                },
                scanData: {
                    value: null
                },
                formData: {
                    value: null
                }
            },
            views: {
                'content@second': {
                    templateUrl: function (stateParams) {
                        if (stateParams.scanData)
                            return 'templates/views/accounts/card-scan.html';
                        return 'templates/views/accounts/account-add.html';
                    },
                    controller: 'AccountAddController'
                }
            },
            resolve: {
                providerId: ['$stateParams', '$q', function ($stateParams, $q) {
                    if ($stateParams.providerId !== null || $stateParams.scanData !== null)
                        return $q.resolve();
                    return $q.reject({backTo: false});
                }]
            }
        }).state('index.accounts.account-edit', {
            url: '/account/edit/:Id/:oauthKey?error',
            requireLogin: true,
            params: {
                Id: {
                    value: null
                },
                oauthKey: {
                    value: null,
                    squash: true
                },
                error: {
                    value: null,
                    squash: true
                },
                requestData: {
                    value: null
                }
            },
            views: {
                'content@first': {
                    templateUrl: 'templates/views/accounts/account-edit.html',
                    controller: 'AccountEditController'
                }
            }
        }).state('index.accounts.store-location', {
            url: '/account/:accountId/:subId/store-location/:locationId',
            requireLogin: true,
            abstract: true,
            params: {
                accountId: {
                    value: null,
                    squash: true
                },
                subId: {
                    value: null,
                    squash: true
                },
                locationId: {
                    value: null,
                    squash: true
                }
            }
        }).state('index.accounts.store-location.add', {
            url: '/add',
            requireLogin: true,
            views: {
                'content@second': {
                    templateUrl: 'templates/views/store-locations/add-store-location.html',
                    controller: 'StoreLocation'
                }
            }
        }).state('index.accounts.store-location.edit', {
            url: '/edit',
            requireLogin: true,
            views: {
                'content@second': {
                    templateUrl: 'templates/views/store-locations/add-store-location.html',
                    controller: 'StoreLocation'
                }
            }
        }).state('index.accounts.store-location.warning', {
            url: '/warning',
            requireLogin: true,
            views: {
                'content@second': {
                    templateUrl: 'templates/views/store-locations/warning.html'
                }
            }
        }).state('index.accounts.account-phones', {
            url: '/account/phones/:Id',
            requireLogin: true,
            views: {
                'content@second': {
                    templateUrl: 'templates/views/accounts/account-phones.html',
                    controller: 'AccountDetailsController'
                }
            }
        }).state('index.accounts-update', {
            url: '/accounts/update',
            controller: 'AccountsUpdateController',
            templateUrl: 'templates/views/accounts/accounts-update.html',
            requireLogin: true
        }).state('index.accounts.agents-add', {
            url: '/agents/add',
            requireLogin: true,
            params: {
                formLink: {
                    value: null
                },
                formTitle: {
                    value: null
                },
                formData: {
                    value: null
                }
            },
            views: {
                'content@first': {
                    templateUrl: 'templates/views/agents-add.html',
                    controller: 'AgentsAddController'
                }
            }
        }).state('index.profile', {
            url: '/profile',
            controller: 'ProfileController',
            templateUrl: 'templates/views/profile/profile.html',
            requireLogin: true,
            params: {
                needUpdate: {
                    value: null
                }
            }
        }).state('index.profile-edit', {
            url: '/profile/{action:any}',
            controller: 'ProfileEditController',
            templateUrl: 'templates/views/profile/profile-edit.html',
            requireLogin: true,
            params: {
                formLink: {
                    value: null
                },
                formTitle: {
                    value: null
                }
            }
        }).state('index.pay', {
            url: '/pay/:start',
            params: {
                start: {
                    value: null,
                    squash: true
                }
            },
            controller: 'PayController',
            templateUrl: 'templates/views/payment/pay.html',
            requireLogin: true
        }).state('index.subscription', {
            url: '/subscription/:action/:platform',
            params: {
                action: {
                    value: null
                },
                platform: {
                    value: null
                }
            },
            controller: 'SubscriptionController',
            templateUrl: 'templates/views/payment/subscription.html',
            requireLogin: true
        }).state('index.spend-analysis', {
            url: '/spend-analysis',
            requireLogin: true,
            abstract: true,
            templateUrl: 'templates/views/spend-analysis/index.html',
            controller: 'SpendAnalysisOverviewController'
        }).state('index.spend-analysis.overview', {
            url: '',
            views: {
                'page@overview': {
                    templateUrl: 'templates/views/spend-analysis/overview.html',
                }
            }
        }).state('index.spend-analysis.details', {
            url: '/details',
            deepStateRedirect: true,
            sticky: true,
            params: {
                formData: {
                    value: null
                },
                title: {
                    value: null
                },
                merchant: {
                    value: null
                }
            },
            views: {
                'page@details': {
                    templateUrl: 'templates/views/spend-analysis/details.html',
                    controller: 'SpendAnalysisDetailsController'
                }
            }
        }).state('index.spend-analysis.settings', {
            url: '/settings',
            params: {
                form: {
                    value: null
                }
            },
            views: {
                'page@settings': {
                    templateUrl: 'templates/views/spend-analysis/settings.html',
                    controller: 'SpendAnalysisSettingsController'
                }
            }
        }).state('index.spend-analysis.account-history-offer', {
            url: '/history-offer/:uuid',
            deepStateRedirect: true,
            sticky: true,
            views: {
                'page@offer': {
                    templateUrl: 'templates/views/accounts/account-history-offer.html',
                    controller: 'AccountHistoryOfferController'
                }
            },
            params: {
                uuid: {
                    value: null
                },
                extraData: {
                    value: {}
                }
            }
        })/*.state('index.merchants', {
            url: '/merchants',
            requireLogin: false,
            abstract: true,
            templateUrl: 'templates/views/merchants/index.html',
            controller: ['$scope', 'SessionService', function ($scope, SessionService) {
                $scope.authorized = SessionService.getProperty('authorized');
            }]
        }).state('index.merchants.lookup', {
            url: '',
            requireLogin: false,
            views: {
                'header@main': {
                    templateUrl: 'templates/views/merchants/headers/lookup.html',
                },
                'content@main': {
                    templateUrl: 'templates/views/merchants/content/lookup.html',
                    controller: 'MerchantLookupController',
                }
            },
        }).state('index.merchants.lookup.offer', {
            url: '/offer/:id',
            views: {
                'header@main': {
                    templateUrl: 'templates/views/merchants/headers/offer.html',
                },
                'content@second': {
                    templateUrl: 'templates/views/merchants/content/offer.html',
                    controller: 'MerchantLookupOfferController',
                }
            },
            requireLogin: false
        })*/.state('logout', {
            url: '/logout?backTo',
            controller: 'LogoutController',
            template: '',
            requireLogin: true,
            params: {
                toState: {
                    value: null
                },
                toParams: {
                    value: null
                },
                toPath: {
                    value: null
                },
                backTo: {
                    value: null,
                    squash: true
                }
            }
        });
    }
]);
