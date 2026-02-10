define([
    'angular-boot',
    'jquery-boot',
    'translator-boot',
    'directives/dialog',
    'directives/tabs',
], function(angular, $) {
    'use strict';

    angular = angular && angular.__esModule ? angular.default : angular;

    angular
        .module('statusPage-ctrl', ['dialog-directive', 'tabs-directive'])
        .controller('statusCtrl', function($scope, $compile, $window, dialogService) {
            var $tabNav       = $('.tabs-navigation'),
                $statusTables = $('#statusTables');

            $('a', $tabNav).click(function() {
                var hash = $(this).attr('href').substr(1);
                if ('all' === hash) {
                    $('table.status-table', $statusTables).removeClass('hidden');
                } else {
                    var $activeTable = $('#t_' + hash);
                    $('table.status-table', $statusTables).not($activeTable).addClass('hidden');
                    $activeTable.removeClass('hidden');
                    $('.jst_' + hash, $statusTables).removeClass('hidden');
                }
                $('a.active', $tabNav).removeClass('active');
                $(this).addClass('active');
            });

            $($window)
                .bind('hashchange', function(e) {
                    e.preventDefault();
                    if (document.location.hash)
                        $('a[href="' + location.hash + '"], a[href="#' + location.hash + '"]', $tabNav).click();
                })
                .trigger('hashchange');

            //
            function dialogConfirm(params) {
                params = params || {'$tr' : $, 'id' : 0, 'title' : '', 'content' : '', 'btnConfirm' : '', 'btnCancel' : ''};

                var btns = [], dialog = dialogService.get('confirm');
                dialog.setOption('title', params.title);
                dialog.element.html(params.content);

                if ('undefined' !== typeof params.disableButtons && true === params.disableButtons) {
                    btns.push({
                        text    : params.btnCancel,
                        click   : function() {
                            $(this).dialog('close');
                        },
                        'class' : 'btn-blue'
                    });
                } else {
                    btns.push({
                        text    : params.btnCancel,
                        click   : function() {
                            $(this).dialog('close');
                        },
                        'class' : 'btn-silver'
                    });
                    btns.push({
                        text    : params.btnConfirm,
                        click   : function() {
                            var data     = {id : params.id, v3: window.extensionV3info.version || ''},
                                $comment = $('#statusProviderComment');
                            if ($comment.length) {
                                if ('' == $comment.val()) {
                                    $comment
                                        .closest('.js-comment').addClass('error')
                                        .find('.error-message').show();
                                    return false;
                                }
                                data.comment = $comment.val();
                            }
                            let extensionVersion = localStorage.getItem('extension_version');
                            if ('string' === typeof extensionVersion) {
                                data.extensionVersion = extensionVersion;
                            }

                            $comment.removeClass('field-error');
                            $(this).dialog('close');
                            params.$tr.find('a.js-vote').hide().parent().css('background', "url('/assets/awardwalletnewdesign/img/spinner.gif') center no-repeat");
                            $.ajax({
                                url     : Routing.generate('aw_status_vote'),
                                type    : 'POST',
                                data    : data,
                                success : function(data) {
                                    params.$tr.find('a.js-vote').parent().css('background', 'none');
                                    if ('undefined' !== typeof data.success) {
                                        if (data.success) {
                                            var countVotes = parseInt(params.$tr.find('.js-votes').text());
                                            params.$tr.find('td.js-votes').text((1 + countVotes)).end()
                                                .find('a.js-vote').parent().empty().html('<i class="icon-dark-check"></i>');

                                        } else if ('undefined' !== typeof data.message) {
                                            dialog.element.html(data.message);
                                            dialog.element.closest('.ui-dialog').find('.ui-dialog-buttonpane').hide();
                                            dialog.open();
                                        }
                                    }
                                }
                            });
                        },
                        'class' : 'btn-blue'
                    });
                }

                dialog.setOption('buttons', btns);
                dialog.open();
            }

            dialogService.createNamed('confirm', $('<div />').appendTo('body').html(''), {
                width    : 800,
                height   : 200,
                autoOpen : false,
                modal    : true
            });
            $('a.js-vote').click(function(e) {
                e.preventDefault();
                var $tr    = $(this).closest('tr'),
                    params = {
                        '$tr'        : $tr,
                        'type'       : $tr.closest('table[id]').attr('id'),
                        'id'         : $(this).attr('href').substr(1),
                        'title'      : Translator.trans('status.please-confirm'),
                        'content'    : '',
                        'btnConfirm' : Translator.trans('button.yes'),
                        'btnCancel'  : Translator.trans('button.cancel')
                    };

                var providerName      = $tr.find('div.name-block').text(),
                    providerShortName = $tr.find('td[data-shortname]').data('shortname');

                switch (params.type) {
                    case 't_broken':
                        params.btnConfirm = Translator.trans('status.confirm.broken-yes', {'providerName' : providerShortName});
                        params.content    = Translator.trans('status.confirm.broken-text', {'providerName' : providerName});
                        break;
                    case 't_consideringAdd':
                        params.content = Translator.trans('status.confirm.considering-text', {'providerName' : providerName});
                        break;
                    case 't_working':
                        params.btnCancel  = Translator.trans('button.close');
                        params.btnConfirm = Translator.trans('status.confirm.working-yes', {'providerShortName' : providerShortName});
                        params.content    = '<div style="background: url(\'/assets/awardwalletnewdesign/img/spinner.gif\') center no-repeat;height: 100px;"></div>';
                        // after getMessage
                        break;
                    default:
                        break;
                }

                if ('t_working' === params.type) {
                    params.title          = Translator.trans('status.please-wait');
                    params.disableButtons = true;
                    dialogConfirm(params);
                    $.ajax({
                        url     : Routing.generate('aw_status_getmessage'),
                        type    : 'POST',
                        data    : {id : params.id},
                        success : function(data) {
                            delete params.disableButtons;
                            if ('undefined' !== typeof data.title && 'undefined' !== typeof data.message) {
                                var keys     = {'providerName' : $.trim(providerName), 'providerShortName' : $.trim(providerShortName), 'providerSuccessRate' : data.providerSuccessRate};
                                params.title = data.title;
                                'undefined' !== typeof data.disableButtons && true == data.disableButtons ? params.disableButtons = true : null;

                                $scope.message       = Translator.trans(data.message, $.extend({}, keys));
                                $scope.programmError = Translator.trans((data.accounts && data.accounts.length > 1 ? 'status.error-program-accounts' : 'status.error-program-account'), $.extend({}, keys));
                                $scope.accounts      = (data.accounts && data.accounts.length ? data.accounts : false);
                                $scope.showComment   = (data.showComment ? true : false);
                                $scope.showComment ? params.btnCancel = Translator.trans('button.cancel') : null;

                                var template   = Translator.trans($('#statusConfirmMsg').text(), $.extend({}, keys));
                                params.content = $compile(template)($scope);
                                $scope.$digest();

                                dialogConfirm(params);
                            }
                        }
                    });

                } else {
                    dialogConfirm(params);
                }

                return false;
            });

        });

});
