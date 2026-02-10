/*
global
whenRecaptchaLoaded,
renderRecaptcha,
whenRecaptchaSolved,
Dn698tCQ
*/

define([
        'lib/customizer', 'lib/passwordComplexity', 'pages/landing/oauth',
        'jquery-boot', 'directives/dialog', 'angular-boot', 'routing', 'lib/design',
        'translator-boot'
], (customizer, passwordComplexity, initOauthLinks) => {

    function initRecaptcha(scope) {
        setTimeout(() => {
            whenRecaptchaLoaded(() => {
                renderRecaptcha(scope);
            });
        }, 100);
    }

    function isPathSafe(url){
      return  /^\/[a-zA-Z0-9/_\-?&=#]*$/.test(url);
    }

    function navigateToSafePath(backToUrl) {
        try {
            const decodedUrl = decodeURIComponent(backToUrl).trim();
    
            const isSafePath = isPathSafe(decodedUrl);
    
            if (isSafePath) {
                const urlAnchor = document.createElement('a');
                urlAnchor.href = decodedUrl;
    
                const safePath = urlAnchor.pathname + urlAnchor.search + urlAnchor.hash;
    
                window.location.href = safePath;
            }
        } catch (error) { /* empty */ }
    }

    angular
        .module('landingPage-ctrl', ['dialog-directive'])
        .service('User', function() {
            return {
                login: '',
                _remember_me: true
            };
        })
        .controller('registerBusinessCtrl', ['$state', function($state) {
            if (!/^business/.test(window.location.hostname)) {
                return $state.go('register');
            }
            function addError(field, text) {
                const error = $('<div class="req" data-role="tooltip" title="' + text + '"><i class="icon-warning-small"></i></div>');

                $(field).before(error);
                customizer.initTooltips(error);
                error.tooltip('open').off('mouseenter mouseleave');
                field.parents('.row').addClass('error');
                $('#register-button').prop('disabled', true);
            }

            const form = $('#registerForm');

            form
                .on('submit', e => {
                    e.preventDefault();

                    if (passwordComplexity.getErrors().length > 0) {
                        $('#user_pass_Password').focus();
                        return;
                    }

                    $('#register-button').prop('disabled', true).addClass('loader');

                    whenRecaptchaSolved(recaptcha_code => {
                        const data = new FormData($('#registerForm')[0]);
                        data.append('recaptcha', recaptcha_code);
                        $.ajax({
                            url: Routing.generate('aw_users_register_business'),
                            data: data,
                            method: 'post',
                            processData: false,
                            contentType: false,
                            success: data => {
                                if (data.errors && data.errors.length > 0) {
                                    const error = data.errors[0];
                                    addError($('[name="' + error.name + '"]'), error.errorText);
                                } else {
                                    document.location.href = Routing.generate('aw_business_account_list');
                                }
                                $('#register-button').removeClass('loader');
                            }
                        })
                    });

                })
                .find('input').on('keyup paste change', () => {
                    const erroredField = form.find('.req');
                    if (erroredField.length) {
                        erroredField.tooltip('destroy').remove();
                    }
                    $('#register-button').prop('disabled', false);
                });

            initRecaptcha();
            passwordComplexity.init($('#user_pass_Password'), () => {
                return $('#user_login').val()
            }, () => {
                return $('#user_email').val()
            });
        }])
        .controller('registerCtrl', ['$scope', '$http', '$timeout', '$location', 'dialogService', '$state', function($scope, $http, $timeout, $location, dialogService, $state) {
            if (/^business/.test(window.location.hostname)) {
                return $state.go('registerBusiness');
            }

            document.title = Translator.trans('meta.title.register');

            function focusOnError(filter = '') {
                setTimeout(() => {
                    const row = $('.row.error' + filter);
                    if (row.length) {
                        $('.req', row).mouseover();
                        $('input', row).focus();
                    }
                }, 100);
            }

            const stepInit = {};

            function initFirstStep() {
                initOauthLinks(() => {
                    $scope.setStep(3);
                    $scope.$apply();
                }, () => {
                    $scope.setStep(1);
                    $scope.$apply();
                });
            }

            function initSecondStep() {
                passwordComplexity.init($('#password'), null, () => {
                    return $('#registration_email').val()
                });
            }

            function validateForm(form) {
                const deferred = $.Deferred();

                if (form.$invalid) {
                    return deferred.reject();
                }

                if (passwordComplexity.getErrors().length > 0) {
                    return deferred.reject('password');
                }

                deferred.resolve();
                return deferred.promise();
            }

            const uriParams = $location.search();

            $scope.isStep = s => {
                return s === $scope.step;
            };
            $scope.setStep = s => {
                $scope.step = s;

                if (!stepInit[s]) {
                    if (s === 1) {
                        initFirstStep();
                    } else if (s === 2) {
                        initSecondStep();
                    }
                    stepInit[s] = true;
                }
            };
            $scope.setStep(1);

            $scope.submitted = false;
            $scope.showPass = false;
            $scope.form = {
                email: window.inviteEmail || null,
                pass: null,
                firstname: window.firstName || null,
                lastname: window.lastName || null
            };
            $scope.coupon = uriParams.code || uriParams.Code || null;
            $scope.emailChecked = false;

            $scope.toggleShowPass = () => {
                $scope.showPass = !$scope.showPass;
            };
            $scope.resetErrors = function () {
                $scope.submitted = false;
                $scope.registerForm.email.$setValidity("taken", true);
                $scope.registerForm.email.$setValidity("locked", true);
            };

            $scope.submit = function (form) {
                $scope.submitted = true;
                $scope.spinner = true;

                validateForm(form)
                    .done(() => {
                        $timeout(() => {
                            whenRecaptchaSolved(function(captcha_key){
                                $.post('/user/check_email_2', {
                                    value: $scope.form.email,
                                    recaptcha: captcha_key,
                                    token: 'fo32jge'
                                })
                                .done(result => {
                                    if (result === 'false') {
                                        $scope.registerForm.email.$setValidity("taken", false);
                                        $scope.spinner = false;
                                        $scope.$apply();
                                        focusOnError(':first');
                                        return;
                                    }
                                    if (result === 'locked') {
                                        $scope.registerForm.email.$setValidity("locked", false);
                                        $scope.spinner = false;
                                        $scope.$apply();
                                        focusOnError(':first');
                                        return;
                                    }

                                    $http({
                                        url: Routing.generate(
                                            'aw_users_register',
                                            (uriParams.BackTo) ? {"BackTo":uriParams.BackTo} : {}
                                        ),
                                        method: 'post',
                                        data: {user: $scope.form, coupon: $scope.coupon, recaptcha: captcha_key}
                                    }).then(({data}) => {
                                        if (data.success === true) {
                                            console.log('sending registered gtag event');

                                            window.dataLayer = window.dataLayer || [];
                                            window.dataLayer.push({
                                                'event': 'user_registered',
                                                'userRegisteredType': 'desktop_form',
                                                'userRegisteredMethod': 'form',
                                                'eventCallback': function() {
                                                    if (uriParams.BackTo) {
                                                        navigateToSafePath(uriParams.BackTo);
                                                    }

                                                    if ($scope.coupon) {
                                                        window.location.href = data.beta ? Routing.generate('aw_users_usecoupon', {
                                                            back: data.targetPage
                                                        }) : '/user/useCoupon.php?Code=' + $scope.coupon;
                                                    } else {
                                                        window.location.href = data.beta ? data.targetPage : 'account/list';
                                                    }
                                                }
                                            });
                                            if (!customizer.isGtmLoaded()) {
                                                setTimeout(window.dataLayer.at(-1).eventCallback(), 3000);
                                            }    
                                        } else {
                                            $scope.spinner = false;
                                            let error = data.errors;

                                            if (error.indexOf('ERROR:') !== -1) {
                                                error = error.substring(error.indexOf('ERROR:') + 7);
                                            }

                                            dialogService.alert(error, Translator.trans("alerts.error"));
                                        }
                                    })
                                    .finally(() => {
                                        $scope.spinner = false;
                                    });
                                })
                                .fail(() => {
                                    $scope.spinner = false;
                                    $scope.$apply();
                                    focusOnError(':first');
                                });
                            });
                        }, 0);
                    })
                    .fail(field => {
                        focusOnError(':first');
                        if (field === 'password') {
                            $('#password').focus();
                        }
                        $scope.spinner = false;
                        $timeout(() => {
                            $scope.$apply();
                        });
                    });
            };
            $('html, body').animate({scrollTop : $('#register').offset().top - 60}, 1000);
        }])
        .controller('loginCtrl', ['$scope', '$http', '$location', '$timeout', '$sce', 'User', function ($scope, $http, $location, $timeout, $sce, User) {
            document.title = Translator.trans('meta.title.login');
            let uriParams = $location.search();
            let prevStep;

            $scope.user = User;

            $scope.step = 'login';
            $scope.informationMessage = false;
            $scope.answer = '';
            $scope.recaptcha = '';

            if ($location.search().error) {
                $scope.error = $location.search().error;
                $scope.user.login = $('#username').data('login-hint');
            }

            $scope.submitButton = {
                login: Translator.trans(/** @Desc("Sign in") */'sign-in.button'),
                otcRecovery: Translator.trans(/** @Desc("Recover") */'login.button.recovery'),
                question: Translator.trans('login.button.login')
            };
            $scope.submitButton.otc = $scope.submitButton.login;

            initOauthLinks(() => {
                prevStep = $scope.step;
                $scope.step = 'mb_question';
                $scope.$apply();
            }, () => {
                $scope.step = prevStep;
                $scope.$apply();
            });

            // TODO Trace in ie8
            //$timeout(function () {
            //	if(typeof navigator != 'undefined' && !navigator.userAgent.match('Firefox') && $('input:-webkit-autofill').length){
            //		$scope.autofill = true;
            //	}
            //}, 250);

            $scope.popupTitle = {
                login: $sce.trustAsHtml(Translator.trans('login.title.login')),
                mb_question: $sce.trustAsHtml(Translator.trans('login.title.login')),
                question: $sce.trustAsHtml(Translator.trans('login.title.login')),
                otcRecovery: $sce.trustAsHtml(Translator.trans(/** @Desc("Recovery") */'login.title.recovery'))
            };
            $scope.popupTitle.otc = $scope.popupTitle.login;
            $scope.otcInputLabel = Translator.trans(/* @Desc("One-time code") */ 'login.otc');
            $scope.otcInputHint = null;

            $scope.submit = function () {
                if ('otc' !== $scope.step && 'question' !== $scope.step) {
                    $scope.user._otc = null;
                }
                if ('otcRecovery' !== $scope.step) {
                    delete $scope.user._otc_recovery;
                }
                $scope.showForgotLink = false;
                $scope.spinner = true;
                // ie11 fix, see #10625
                var cookie = $.cookie();
                if (Object.prototype.hasOwnProperty.call(cookie, 'XSRF-TOKEN')) {
                    cookie = cookie['XSRF-TOKEN'];
                } else {
                    cookie = $.cookie('XSRF-TOKEN');
                }
                $scope.user.FormToken = cookie;

                $http({
                    url: Routing.generate('aw_login_client_check'),
                    method: 'POST',
                    headers: {'Content-Type': 'application/x-www-form-urlencoded'}
                }).then(function (res) {
                    Dn698tCQ = eval(res.data.expr); // eslint-disable-line no-global-assign
                    $scope.user._csrf_token = res.data.csrf_token;

                    if ($scope.recaptchRequired) {
                        console.log('recaptcha required on submit');
                        initRecaptcha($scope);
                        whenRecaptchaSolved(function(recaptcha_code){
                            $scope.recaptchRequired = false;
                            $scope.recaptcha = recaptcha_code;
                            console.log('sent recaptcha on submit');
                            $scope.tryLogin();
                        });
                        return;
                    }

                    $scope.tryLogin();
                });

            };

            $scope.tryLogin = function () {
                var data = angular.copy($scope.user);
                if($scope.step === 'question'){
                    data._otc = $scope.question.question + '=' + $scope.answer;
                }
                data.recaptcha = $scope.recaptcha;
                $http({
                    url: Routing.generate('aw_users_logincheck'),
                    method: 'POST',
                    headers: {'Content-Type': 'application/x-www-form-urlencoded', 'X-Scripted':  Dn698tCQ || Math.random},
                    data: $.param(data)
                    // todo fail!
                }).then(function (res) {
                    const data = res.data;
                    if (typeof data === 'object') {
                        if (data.success) {
                            if (window.inviteCode){
                                window.location.href = Routing.generate('aw_invite_confirm', {'shareCode': window.inviteCode});
                                return;
                            }
                            if (sessionStorage.backUrl &&
                                sessionStorage.backUrl.indexOf("/logout") === -1 &&
                                sessionStorage.backUrl.indexOf("/loginFrame") === -1) {
                                window.location.href = sessionStorage.backUrl;
                                return;
                            }
                            if (uriParams.BackTo) {
                                const decodedUrl = decodeURIComponent(uriParams.BackTo).trim();
    
                                const isSafePath = isPathSafe(decodedUrl);

                                if(isSafePath){
                                    navigateToSafePath(uriParams.BackTo);
                                    return;
                                }

                            }
                            if ($scope.step === 'otcRecovery' && $scope.user._otc_recovery) {
                                // TODO: change to routing
                                window.location.href = Routing.generate('aw_profile_2factor')
                            } else {
                                window.location.href = '/';
                            }
                        } else {
                            $scope.spinner = false;
                            if ((null !== $scope.user._otc || '' !== $scope.user._otc) && 'otcRecovery' !== $scope.step && !!data.otcRequired) {
                                $scope.otcInputHint = data.otcInputHint;
                                if(typeof(data.otcInputLabel) === 'string') {
                                    $scope.step = 'otc';
                                    $scope.otcInputLabel = data.otcInputLabel;
                                    $timeout(function () {
                                        $('#otc').focus();
                                    }, 200)
                                }
                                else{
                                    $scope.step = 'question';
                                    let selected = 0;
                                    $scope.questions = angular.copy(data.otcInputLabel);
                                    $scope.selectQuestionText = Translator.trans(/** @Desc("Please select a question") */ "select-question");
                                    $scope.questions.unshift({"question": $scope.selectQuestionText, "maskInput": false});
                                    if(typeof($scope.question) === 'object')
                                        for(let idx in $scope.questions){
                                            if(Object.prototype.hasOwnProperty.call($scope.questions, idx) && $scope.questions[idx].question === $scope.question.question){
                                                selected = idx;
                                                break;
                                            }
                                        }
                                    $scope.question = $scope.questions[selected];
                                    if($scope.answer !== "" && $scope.answer !== null)
                                        $scope.error = data.message;
                                    else
                                        $scope.informationMessage = data.message;
                                    if (data.message.indexOf("CSRF") !== -1)
                                        $scope.csrf = true;
                                    $scope.answer = '';
                                    $timeout(function () {
                                        $('#question-answer').focus();
                                    }, 200);
                                    $scope.questionChanged();
                                }
                                $scope.otcShowRecovery = data.otcShowRecovery;
                            }

                            if (data.badCredentials) {
                                $scope.showForgotLink = true;
                            }

                            if (
                                ('otcRecovery' === $scope.step && null !== $scope.user._otc_recovery && '' !== $scope.user._otc_recovery) ||
                                ('otc' === $scope.step && null !== $scope.user._otc && '' !== $scope.user._otc) ||
                                ('question' === $scope.step && null !== $scope.answer && '' !== $scope.answer) ||
                                ('login' === $scope.step)
                            ) {
                                $scope.error = data.message;
                                if (data.message.indexOf("CSRF") !== -1) {
                                    $scope.csrf = true;
                                }
                            } else {
                                $scope.informationMessage = data.message;
                            }

                            if (data.recaptchaRequired) {
                                console.log('recaptcha required');
                                $scope.recaptchRequired = true;
                                if (!$scope.recaptcha) {
                                    console.log('retry submit with recaptcha');
                                    $scope.clearMessages();
                                    setTimeout($scope.submit, 10);
                                }
                            }
                        }
                    }
                })
            };

            $scope.questionChanged = function(){
                $('#question-answer').attr('type', $scope.question.maskInput ? "password" : "text");
            };

            $scope.clearMessages = function () {
                $scope.error = false;
                //$scope.informationMessage = false;
            };

            $scope.back = function () {
                $scope.user._otc = null;
                $scope.answer = '';
                $scope.user._otc_recovery = null;

                switch ($scope.step) {
                    case 'login':
                        return;

                    case 'otc':
                    case 'question':
                        delete $scope.user._otc;
                        $scope.step = 'login';
                        $timeout(function () {
                            $('#username').focus();
                        });
                        break;

                    case 'otcRecovery':
                        $timeout(function () {
                            $('#otc').focus();
                        });
                        $scope.step = 'otc';
                        break;
                }
                $scope.clearMessages();
            };

            $scope.doStep = function (step) {
                switch (step) {
                    case 'login':
                        break;

                    case 'otc':

                        break;

                    case 'otcRecovery':
                        $timeout(function () {
                            $('#otcRecovery').focus();
                        });
                        break;
                }
                $scope.clearMessages();
                $scope.step = step;
            };
        }])
        .controller('restoreCtrl', ['$scope', '$http', '$timeout', 'User', function ($scope, $http, $timeout, User) {
            $scope.success = false;
            $scope.error = false;
            $scope.errorText = false;
            $scope.user = {username: User.login};
            $scope.submitted = false;

            $scope.submit = () => {
                if ($scope.restoreForm.$invalid) {
                    $scope.submitted = true;
                    return false;
                } else {
                    $scope.spinner = true;
                    $http({
                        url: Routing.generate('aw_users_restore'),
                        method: 'POST',
                        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                        data: $.param($scope.user)
                        // todo fail!
                    }).then(({data}) => {
                        $scope.spinner = false;
                        if (data.success)
                            $scope.success = true;
                        else {
                            if (data.error) {
                                $scope.error = false;
                                $scope.errorText = data.error;
                            } else {
                                $scope.error = true;
                            }
                            $timeout(() => {
                                $('#forgot-password').select().focus();
                            });
                        }
                    })
                }
            };

            $scope.change = () => {
                $scope.submitted = false;
                $scope.error = false;
                $scope.errorText = false;
            }
        }])
        .controller('homeCtrl', ['$scope', '$state', '$location', '$http', 'dialogService', function($scope, $state, $location, $http, dialogService) {
            document.title = Translator.trans('meta.title');
            const uriParams = $location.search();

            function createErrorDialog() {
                dialogService.fastCreate(
                    "Error",
                    "Currently we are limiting our users to send no more than 100 lookup requests per 5 minutes. You have reached your limit please come back in 5 minutes if you wish to continue searching.",
                    true,
                    true,
                    [
                        {
                            text: Translator.trans('button.ok'),
                            click: function() {
                                $(this).dialog('close');
                            },
                            'class': 'btn-blue'
                        }
                    ],
                    500
                );
            }
            function autocompleteSource(request, response) {
                merchantInput.addClass('loading-input').removeClass('search-input');

                $http.post(
                    Routing.generate('aw_merchant_lookup_data'),
                    $.param({
                        query: request.term
                    }),
                    { headers: {'Content-Type': 'application/x-www-form-urlencoded'} }
                ).then(({ data }) => {
                    let result;
                    if ($.isEmptyObject(data)) {
                        result = [{
                            label: 'No merchants found',
                            value: ""
                        }];
                    } else {
                        result = data;
                    }
                    response(result);
                    merchantInput.removeClass('loading-input').addClass('search-input');
                }).catch(() => {
                    createErrorDialog();
                    merchantInput.removeClass('loading-input').addClass('search-input');
                });
            }
            $scope.$on('$stateChangeSuccess', (ev, toState, toParams, fromState) => {
                if (uriParams.BackTo && fromState.name !== 'login') {
                    $state.go('login');
                }
            });

            const merchantInput = $('#merchant');
            merchantInput.removeClass('loading-input').addClass('search-input');

            merchantInput.autocomplete({
                minLength: 3,
                delay: 500,
                source: (request, response) => {
                    autocompleteSource(request, response);
                },
                select: (event, ui) => {
                   window.open(
                       ($('html:first').hasClass('mobile-device') ? '/m' : '') +
                       Routing.generate('aw_merchant_lookup') + '/' + ui.item.nameToUrl,
                       '_blank'
                   );
                },
                create: function() {
                    $(this).data('ui-autocomplete')._renderItem = (ul, item) => {
                        const { label, category } = item;
                        if (!label) {
                            return;
                        }
                        const element = $('<a></a>').append($("<span></span>").html(`${label}&nbsp;`));
                        if (category) {
                            element.append($("<span></span>").addClass("blue").html(`(${category})`));
                        }

                        return $('<li></li>')
                            .data("item.autocomplete", item)
                            .append(element)
                            .appendTo(ul);
                    };
                },
                open: function () {
                    $("ul.ui-menu").width($(this).innerWidth());
                }
            }).off('blur');
        }])
});
