define([
    'angular-boot',
    'jquery-boot',
    'lib/utils',
    'pages/mailbox/add',
    'lib/dialog',
    'lib/customizer',
    'directives/customizer',
    'jqueryui',
    'routing',
    'angular-ui-router',
    'directives/extendedDialog',
    'pages/timeline/main',
    'pages/timeline/directives',
    'pages/timeline/filters',
    'pages/timeline/services',
    'translator-boot'
], function (angular, $, utils, addMailbox, dialog, customizer) {
    angular = angular && angular.__esModule ? angular.default : angular;

    // persons_menu
    var countWithNull;
    var showWithNull = false;
    var isTimeline = true;

    if (isTimeline) {
        $(document).on('update.hidden.users', function () {
            countWithNull = 0;

            if (!showWithNull) {
                $('.js-persons-menu').find('li').each(function (id, el) {
                    if ($(el).find('.count').length) {
                        if ($(el).find('.count').text() === '0' && !$(el).hasClass('active') && $(el).find('a[data-id=my]').length === 0) {
                            $(el).slideUp();
                            countWithNull++;
                        } else {
                            $(el).slideDown();
                        }
                    }
                });

                if (countWithNull) {
                    $('#users_showmore').closest('li').slideDown();
                } else {
                    $('#users_showmore').closest('li').slideUp();
                }
            }
        });

        $(document).on('click', '#users_showmore', function (e) {
            e.preventDefault();
            $('.js-persons-menu').find('li:hidden').slideDown();
            $('#users_showmore').closest('li').slideUp();
            showWithNull = true;
        });
    }

    $(window).on('person.activate', function (event, id) {
        let $persons = $('.js-persons-menu'), $person = null;
        if (!(id instanceof jQuery)) {
            if (-1 !== id.indexOf('_'))
                id = id.split('_')[1] || 'my';
            if ('' == id) id = 'my';
            $person = $persons.find('a[data-id="' + id + '"]');
            0 === $person.length ? $person = $persons.find('a[data-agentid="' + id + '"]:first') : null;
            0 === $person.length ? $person = $persons.find('a[data-id="my"]') : null;
        }
        if ($person instanceof jQuery) {
            $persons.children().removeClass('active');
            $persons.find('a span.count').removeClass('blue').addClass('silver');
            $person.parents('li').addClass('active');
            $person.find('span.count').removeClass('silver').addClass('blue');
            $(window).trigger('person.active', $($person).data('id'));
        }

        $(document).trigger('update.hidden.users');
    });

    // lib/design
    $(document).on('click', '.js-add-new-person, #add-person-btn, .js-persons-menu a[href="/user/connections"].add', function (e) {
        e.preventDefault();
        require(['pages/agent/addDialog'], function (clickHandler) {
            clickHandler();
        });
    });

    angular
        .module('app')
        .controller('timeline', [
            '$scope',
            '$timelineData',
            '$stateParams',
            '$state',
            '$filter',
            '$http',
            '$timeout',
            '$sce',
            '$travelPlans',
            '$log',
            '$location',
            '$window',
            '$transitions',
            function (
                $scope,
                $timelineData,
                $stateParams,
                $state,
                $filter,
                $http,
                $timeout,
                $sce,
                $travelPlans,
                $log,
                $location,
                $window,
                $transitions
        ) {
            $scope.stateParams = $stateParams;
            $scope.$log = $log;
            $scope.segments = [];
            $scope.haveFutureSegments = false;
            $scope.agents = [];
            $scope.plans = [];
            $scope.agent = {
                newowner: '',
                copy: false
            };
            $scope.canAdd = false;
            $scope.embeddedData = Object.prototype.hasOwnProperty.call(window, 'TimelineData') && typeof window.TimelineData == 'object';
            $scope.activeSegmentNumber = null;
            $scope.noForeignFeesCards = [];
            $scope.options = {};
            var overlay = $('<div class="ui-widget-overlay"></div>').hide().appendTo('body');

            addMailbox.subscribe();
            addMailbox.setRedirectUrl(Routing.generate('aw_usermailbox_view'));

            $scope.methods = {
                segmentLink: function (segmentId) {
                    return Routing.generate('aw_timeline_show', {segmentId})
                },
                tossingFill: function (segment, segments) {
                    if (segment.type.match(/plan/)) {
                        this.tossingClear(segment, segments);

                        var i = segments.indexOf(segment) - 1;
                        while (i > 0 && !segments[i].type.match(/plan/)) {
                            segments[i].undroppable = false;
                            i--;
                        }

                        i = segments.indexOf(segment) + 1;
                        while (i < segments.length && !segments[i].type.match(/plan/)) {
                            segments[i].undroppable = false;
                            i++;
                        }

                        $timeout(function () {
                            $(".ui-sortable").sortable("refresh");
                        }, 100);
                    }
                },
                tossingClear: function (segment, segments) {
                    if (segment.type.match(/plan/)) {
                        angular.forEach(segments, function (seg) {
                            seg.undroppable = true;
                        })
                    }
                },
                tossingDrop: function (segment, segments, $event) {
                    $scope.res = segments;
                },
                escape: function (event, segment) {
                    if (event.keyCode == 27)
                        segment.changeNameState = false;
                },
                move: function (segment, agent, event) {
                    $(event.target).addClass('loader').prop('disabled', true);
                    $http({
                        url: agent.newowner != 'my' ?
                            Routing.generate('aw_timeline_move', {'itCode': segment.id, 'agent': agent.newowner}) :
                            Routing.generate('aw_timeline_move', {'itCode': segment.id}),
                        method: 'POST',
                        data: $.param({copy: agent.copy}),
                        headers: {'Content-Type': 'application/x-www-form-urlencoded'}
                    }).then(function () {
                        $scope.recalculateAfter = true;
                        $state.reload();
                    })["finally"](function () {
                        $(event.target).removeClass('loader').prop('disabled', false);
                    })
                },

                getMoveText: function (segments, conf_no) {
                    var n_segments = Translator.transChoice(/** @Desc("%count% segment|%count% segments") */ 'n_segments', segments, {'count': segments}, 'trips');
                    return Translator.trans(/** @Desc("All %segments% of Conf# %conf_no% will be moved (or copied), if this is not when you intended you can delete the segments you don't need later.") */ 'move_all_segments', {'segments': n_segments, conf_no: conf_no}, 'trips');
                },

                getOriginText: function(origin, listItem) {
                    if (origin.type === 'account') {
                        const params = {
                            'providerName': origin.provider,
                            'accountNumber': origin.accountNumber,
                            'owner': origin.owner,
                            'link_on': '<a target="_blank" href="' + Routing.generate('aw_account_list') + '/?account=' + origin.accountId + '">',
                            'link_off': '</a>',
                            'bold_on': '<b>',
                            'bold_off': '</b>'
                        };

                        if (listItem) {
                            return $sce.trustAsHtml(Translator.trans(/** @Desc("%link_on%%bold_on%%providerName%%bold_off% online account %bold_on%%accountNumber%%bold_off%%link_off% that belongs to %owner%") */ 'trips.segment.added-from.account', params, 'trips'));
                        } else {
                            return $sce.trustAsHtml(Translator.trans(/** @Desc("This trip segment was automatically added by retrieving it from %link_on%%bold_on%%providerName%%bold_off% online account %bold_on%%accountNumber%%bold_off%%link_off%, which belongs to %owner%.") */ 'trips.segment.added-from.account.extended', params, 'trips'));
                        }
                    } else if (origin.type === 'confNumber') {
                        const params = {
                            'providerName': origin.provider,
                            'confNumber': origin.confNumber,
                            'bold_on': '<b>',
                            'bold_off': '</b>'
                        };

                        if (listItem) {
                            return $sce.trustAsHtml(Translator.trans(/** @Desc("From %bold_on%%providerName%%bold_off% using confirmation number %bold_on%%confNumber%%bold_off%") */ 'trips.segment.added-from.conf-number', params, 'trips'));
                        } else {
                            return $sce.trustAsHtml(Translator.trans(/** @Desc("This trip segment was automatically added by retrieving it from %bold_on%%providerName%%bold_off% using confirmation number %bold_on%%confNumber%%bold_off%.") */ 'trips.segment.added-from.conf-number.extended', params, 'trips'));
                        }
                    } else if (origin.type === 'email') {
                        if (origin.from === 2 || origin.from === 1) { // from scanner or plans
                            let params;

                            if (origin.from === 1) {
                                params = {
                                    'email': origin.email,
                                    'link_on': '',
                                    'link_off': ''
                                };
                            } else {
                                params = {
                                    'email': origin.email,
                                    'link_on': '<a target="_blank" href="'+ Routing.generate('aw_usermailbox_view') +'">',
                                    'link_off': '</a>'
                                };
                            }

                            if (listItem) {
                                return $sce.trustAsHtml(Translator.trans(/** @Desc("An email that was sent to %link_on%%email%%link_off%") */ 'trips.segment.added-from.email', params, 'trips'));
                            } else {
                                return $sce.trustAsHtml(Translator.trans(/** @Desc("This trip segment was automatically added by parsing a reservation email that was sent to %link_on%%email%%link_off%.") */ 'trips.segment.added-from.email.extended', params, 'trips'));
                            }
                        } else {
                            if (listItem) {
                                return $sce.trustAsHtml(Translator.trans(/** @Desc("An email that was forwarded to us") */ 'trips.segment.added-from.unknown-email', {}, 'trips'));
                            } else {
                                return $sce.trustAsHtml(Translator.trans(/** @Desc("This trip segment was automatically added by parsing a reservation email that was forwarded to us.") */ 'trips.segment.added-from.unknown-email.extended', {}, 'trips'));
                            }
                        }
                    } else if (origin.type === 'tripit') {
                        let params = {
                            'email': origin.email,
                            'link_on': '<a target="_blank" href="' + Routing.generate('aw_usermailbox_view') + '">',
                            'link_off': '</a>'
                        };
                        if (listItem) {
                            return $sce.trustAsHtml(Translator.trans(/** @Desc("Your TripIt account %email%") */ 'trips.segment.added-from.tripit', params, 'trips'));
                        } else {
                            return $sce.trustAsHtml(Translator.trans(/** @Desc("This trip segment was automatically added by synchronizing with your TripIt account, %link_on%%email%%link_off%") */ 'trips.segment.added-from.tripit.extended', params, 'trips'));
                        }
                    }
                },

                changeName: function (segment, e) {
                    e.preventDefault();
                    segment.renamingState = true;
                    $http({
                        url: Routing.generate('aw_travelplan_rename', {'plan': segment.planId}),
                        data: $.param({name: segment.name}),
                        method: 'POST',
                        headers: {'Content-Type': 'application/x-www-form-urlencoded'}
                    })['finally'](function () {
                        segment.renamingState = false;
                        segment.changeNameState = false;
                    });
                },
                requestDeletePlan: function(segment) {
                    overlay.fadeIn();
                    //segment.deletePlanState = true;
                    $http({
                        url: Routing.generate('aw_travelplan_delete', {'plan': segment.planId}),
                        method: 'POST',
                        headers: {'Content-Type': 'application/x-www-form-urlencoded'}
                    }).then(function (result) {
                        $scope.recalculateAfter = true;
                        $state.reload();
                    });
                },
                deletePlan: function ($event, segment) {
                    if ($($event.currentTarget).closest('div[data-trip-start]').find('.js-notes-filled').length > 0) {
                        const confirmPopup = dialog.fastCreate(
                            Translator.trans('confirmation', {}, 'trips'),
                            Translator.trans('you-sure-also-delete-notes', {}, 'trips'),
                            true,
                            true,
                            [
                                {
                                    'class': 'btn-silver',
                                    'text': Translator.trans('button.no'),
                                    'click': () => confirmPopup.destroy(),
                                },
                                {
                                    'class': 'btn-blue',
                                    'text': Translator.trans('button.yes'),
                                    'click': () => {
                                        confirmPopup.destroy();
                                        this.requestDeletePlan(segment);
                                    },
                                },
                            ],
                            400,
                            300
                        );
                        return;
                    }

                    this.requestDeletePlan(segment);
                },
                deleteOrUndelete: function (segment, isUndelete) {
                    segment.deleteLoader = true;
                    $http.post(Routing.generate('aw_timeline_delete', {
                        segmentId: segment.id,
                        undelete: isUndelete || null
                    })).then(res => {
                        if (res.data === true) {
                            // segment.deleteLoader = false;
                            $scope.recalculateAfter = true;
                            $scope.showDeleted = isUndelete;
                            $state.reload();
                        }
                    })
                },
                hideAiWarn: function (segment) {
                    segment.showAIWarning = false;
                    $http.post(Routing.generate('aw_timeline_hide_ai_warning', {
                        segmentId: segment.id
                    }));
                },
                confirmChanges: function (segment) {
                    segment.confirmLoader = true;
                    $http.post(Routing.generate('aw_timeline_confirm_changes', {
                        segmentId: segment.id
                    })).then(function (res) {
                        segment.confirmLoader = false;
                        if (res.data === true) {
                            segment.changed = false;

                            // refs #15588
                            $scope.segments
                                .filter(_ => _.group === segment.group && !_.details)
                                .forEach(_ => _.changed = false);
                        }
                    })
                },
                goRefresh: function (link) {
                    $('<form method="post"/>').attr('action', link).appendTo('body').submit();
                },
                scrollToTop: function () {
                    $timeout(function () {
                        $("html,body").stop().animate({
                            scrollTop: 0
                        }, 500);
                    }, 0);
                },
                hoverInSegment: function (segment) {
                    $scope.activeSegmentNumber = segment.group ? segment.group : null;
                },
                hoverOutSegment: function () {
                    $scope.activeSegmentNumber = null;
                },
                createPlan: function (segment) {
                    segment.createPlanState = true;
                    window.showTooltips = true;
                    overlay.fadeIn();

                    $http({
                        url: Routing.generate('aw_travelplan_create'),
                        data: $.param({
                            userAgentId: ($stateParams.agentId && $stateParams.agentId != '') ? $stateParams.agentId : null,
                            startTime: segment.startDate
                        }),
                        method: 'POST',
                        headers: {'Content-Type': 'application/x-www-form-urlencoded'}
                    }).then(function (res) {
                        if (res != null) {
                            $scope.shownFrom = res.startTime;
                        }
                        $scope.recalculateAfter = true;
                        $state.reload();
                    }).finally(function () {
                        window.afterPlanCreated = true;
                        //segment.createPlanState = false;
                    })
                }
            };

            $scope.$on('print', function () {
                //печать таймлайна
                if (/\/print\//.test($location.$$absUrl) && !$scope.spinner) {
                    $window.print();
                }
            });

            let dataRequest;
            $transitions.onSuccess({}, function (transition) {
                const toParams = transition.params('to');
                const fromParams = transition.params('from');

                // Редирект на авторизацию, если неавторизованный зашел на таймлайн
                if (!($state.is('shared') || $state.is('shared-plan')) && $('a[href*="login"]').length && !$scope.embeddedData) {
                    location.href = '/login?BackTo=%2Ftimeline%2F';
                }

                if (true !== $scope.recalculateAfter && (fromParams.openSegment !== toParams.openSegment && fromParams.agentId && fromParams.agentId === toParams.agentId)) {
                    return;
                }

                $scope.showDeleted = $stateParams.showDeleted || $scope.showDeleted;

                const agentMatch = location.search.match(/\?agentId=(\d+)/);
                if (agentMatch) {
                    $scope.agentId = agentMatch[1];
                } else {
                    $scope.agentId = $stateParams.agentId || '';
                }

                addMailbox.setOwner($scope.agentId);

                /*
                    Нужно перезагрузить таймлайн в случаях:
                     смены собственника
                     при первой загрузке (не загружены сегменты)
                     удаление before из адресной строки
                 */
                if (fromParams.agentId !== toParams.agentId || !$scope.segments.length || (fromParams.before && !toParams.before)) {
                    $scope.segments.length = 0;
                    $scope.spinner = true;
                    // если оставить after, то таймлайн перезагрузится
                    // на нужном отскроллированном месте (сохранение позиции) в прошлом
                    if (!$scope.forceAfter) {
                        $scope.after = undefined;
                    }
                }

                // Сохранение позиции перед перегрузкой таймлайна
                // при Show/Hide deleted а так же иных действиях над сегментами (recalculateAfter)
                if (
                    (
                        typeof fromParams.showDeleted !== 'undefined' && typeof toParams.showDeleted !== 'undefined'
                        && fromParams.showDeleted !== toParams.showDeleted
                    )
                    || $scope.recalculateAfter
                ) {

                    console.log('Recalculate after...');

                    if ($scope.segments.length) {
                        // after будет использован для запроса данных с сервера начиная с этой даты
                        $scope.after = parseInt($scope.segments[0].startDate);
                        $scope.shownSegments = $filter('filter')($scope.segments, {visible: true});
                        if ($scope.shownSegments.length) {
                            $scope.forceAfter = true;
                            // shownFrom с какого времени показывать сегменты.
                            // Полученные с сервера сегменты будут сравниваться с этой датой показываться/скрываться
                            if ($scope.shownFrom && $scope.after > $scope.shownFrom) {
                                $scope.after = $scope.shownFrom;
                            } else {
                                $scope.shownFrom = parseInt($scope.shownSegments[0].startDate);
                            }
                        }
                    }

                    if (!$scope.recalculateAfter) {
                        $scope.pastSpinner = true;
                    }

                    $scope.containerHeight = $('.trip').height();

                }

                var anchor = null;
                var shownSegments = $filter('filter')($scope.segments, {visible: true});
                var offsetTop = 0;
                if (shownSegments.length) {
                    anchor = shownSegments[0];
                    var anchorElement = $('div[data-id="' + anchor.id + '"]');
                    if (anchorElement.length)
                        offsetTop = anchorElement.offset().top;
                }
                else
                    offsetTop = 0;

                // Если в массиве сегментов есть непоказанные объекты - показываем
                // либо, если страницу таймлайна перегружают с параметром before - открываем future

                // показываем скрытые сегменты (грузим про запас). Например, при скроллинге в прошлое одновременно запрашивая
                // дополнительные данные с сервера
                if ($scope.segments.length && !$scope.after && !$scope.shownFrom) {
                    $scope.segments.map(function (segment) {
                        segment.visible = true;
                        segment.future = false;
                    });
                // загрузить таймлайн с указанной позиции
                } else if (!fromParams.openSegmentDate && toParams.openSegmentDate > 0 && toParams.openSegment) {
                    $scope.after = $stateParams.openSegmentDate;
                    $scope.shownFrom = $stateParams.openSegmentDate;
                    $scope.forceAfter = true;
                    $stateParams.openSegmentDate = null;
                    $state.reload();
                    return;
                // перезагрузка при наличии before (непонятно где используется)
                } else if ($stateParams.before) {
                    $stateParams.before = null;
                    $state.reload();
                    return;
                }

                // Показываем спиннер при нажатии Past, либо при Show/Hide deleted
                // или удалении/востановлении сегментов
                if ($stateParams.before)
                    $scope.pastSpinner = true;

                // отключил анимацию при скроле в прошлое
                // if ($scope.segments.length > 0 && (toParams.before || toParams.after) && !$scope.recalculateAfter) {
                //     // keep position when loading past
                //     if (!anchor)
                //         anchor = $scope.segments[$scope.segments.length - 1];
                //
                //     var id = anchor.id;
                //     setTimeout(function () {
                //         var $el = $('div[data-id="' + id + '"]');
                //         if ($el.length) {
                //             $('html, body').scrollTop($el.offset().top - offsetTop);
                //             setTimeout(function () {
                //                 $('html, body').animate({
                //                     scrollTop: $('div[data-id="' + id + '"]').offset().top - offsetTop - $(window).height() * 0.7
                //                 }, 1000);
                //             }, 200);
                //         }
                //     }, 10);
                // }

                if (typeof dataRequest == 'object' && Object.prototype.hasOwnProperty.call(dataRequest, 'cancel')) {
                    dataRequest.cancel();
                }

                // Загрузка данных (+ поправка времени для корректного отображения, если будущее начинается с травел-плана)
                dataRequest = $timelineData.fetch($scope.after);
                dataRequest.then(function (data) {
                    console.log('loaded');
                    $scope.deleteLoader = false;
                    $scope.agents = data.agents || [];
                    $scope.sharableAgents = $scope.agents.filter(agent => agent.sharable);
                    $scope.canAdd = data.canAdd || false;
                    $scope.noForeignFeesCards = data.noForeignFeesCards || [];
                    $scope.options = data.options || {};
                    overlay.fadeOut();

                    if ($scope.containerHeight)
                        $('.trip').css('min-height', $scope.containerHeight);

                    $scope.segments = $scope.after ? data.segments : data.segments.concat($scope.segments);
                    var now = new Date();

                    for (var i = $scope.segments.length; i > 0; i--) {
                        var segment = $scope.segments[i - 1];
                        segment.future = segment.startDate > (Date.UTC(now.getFullYear(), now.getMonth(), now.getDate(), 0, 0, 0, 0) / 1000);

                        if (segment.future || ($scope.segments[i] && $scope.segments[i].visible && !segment.breakAfter)) {
                            segment.visible = true;
                        }

                        // новые подгруженые сегменты не покажутся а будут скрыты до дальнейшего скрола в прошлое
                        if ($scope.shownFrom && segment.startDate >= $scope.shownFrom)
                            segment.visible = true;

                        if ($state.is('shared') || $state.is('shared-plan') || $state.is('itineraries') || $scope.embeddedData)
                            segment.visible = true;

                        if (segment.details && segment.details.monitoredStatus)
                            $scope.segments
                                    .filter(function (item) {
                                        return item.group == segment.group;
                                    })
                                    .forEach(function (item) {
                                        if (-1 === $.inArray(item.id.substr(0, 2), ['CO', 'L.'])) {
                                            if (!item.details) item.details = {};
                                            item.details.monitoredStatus = segment.details.monitoredStatus;
                                        }
                                    });

                        $scope.segments.forEach(function (item) {
                            if ('CI' === item.id.substr(0, 2) && Object.prototype.hasOwnProperty.call($scope.options, 'reservation')) {
                                item.setOptions($scope.options.reservation);
                            }
                        });
                    }

                    $scope.haveFutureSegments = $filter('filter')($scope.segments, {future: true}).length > 0;

                    // Обновление счетчиков
                    if ($state.is('timeline') && !$scope.embeddedData) {

                        // Forwarding email
                        $scope.forwardingEmail = data.forwardingEmail;
                        $scope.mailboxes = {};

                        var totals = 0;
                        var counts = {};

                        angular.forEach($scope.agents, function (agent) {
                            $scope.mailboxes[agent.id] = agent.mailboxes;
                            counts[agent.id] = agent.count;
                            totals = totals + agent.count;

                            // if (agent.id == 'my')
                            //     $('.user-blk a[data-id]').first().find('.count').text(agent.count);
                            // else if (agent.count >= 0)
                            //     $('.user-blk a[data-id=' + agent.id + ']').find('.count').text(agent.count);
                            //
                            // if (agent.count >= 0)
                            //     totals = totals + agent.count;
                        });

                        $(document).trigger('persons.update', counts);
                        $('#trips-count').text(counts.my);
                    }

                    if (!$state.is('itineraries')) {
                        $scope.fullName = $sce.trustAsHtml(Translator.trans('timeline.of.name', {name: '<b>' + data.fullName + '</b>'}));
                    } else {
                        $scope.fullName = $sce.trustAsHtml(Translator.trans(/** @Desc("Retrieved travel plans") */ 'retrieved.travelplans'));
                    }

                    // Чистим состояния
                    $scope.spinner = $scope.pastSpinner = $scope.after = $scope.shownSegments = $scope.shownFrom = $scope.forceAfter = $scope.recalculateAfter = undefined;
                    $scope.agent.newowner = '';
                    $scope.agent.copy = false;


                    // Rewrapping
                    $timeout(function () {
                        $('.wrapper').remove();

                        var items = $('.trip-list > div');
                        items.each(function (id, item) {
                            var prev = $(item).prev();
                            if (prev.hasClass('trip-blk'))
                                prev.addBack().wrapAll('<div class="undraggable undroppable wrapper" />');
                        });

                        $(".ui-sortable").sortable("refresh");
                        customizer.initHtml5Inputs('.trip');
                    });

                });

                // Подсветка пользователя в левом меню, чей таймлайн загрузили
                if (!$state.is('itineraries'))
                    $(window).trigger('person.activate', $stateParams.agentId || 'my');
                else {
                    var agentId = location.href.match(/agentId=(\d+)/);
                    if (agentId && agentId[1]|0)
                        $(window).trigger('person.activate', agentId[1]);
                }

            });
            $scope.$on('timelineFinishRender', function () {
                var hideTooltips;

                const requestPlanMove = (ui, nextSegment) => {
                    overlay.fadeIn();
                    $travelPlans.move({
                        planId: angular.element(ui.item).scope().segment.planId,
                        nextSegmentId: nextSegment.data('id'),
                        nextSegmentTs: angular.element(nextSegment).scope().$parent.segment.startDate,
                        type: angular.element(ui.item).scope().segment.type
                    }).then(function (resp) {
                        $scope.shownFrom = resp.data.startTime;
                        $scope.recalculateAfter = true;
                        $state.transitionTo($state.current, $stateParams, {reload: true, inherit: true});
                    });
                }

                $('.trip-list').sortable({
                    cancel: '.undraggable,input',
                    axis: "y",
                    handle: '.draggable',
                    items: '> div:not(.undroppable)',
                    revert: true,
                    opacity: 0.7,
                    start: function () {
                        hideTooltips = true;
                    },
                    stop: function (event, ui) {
                        hideTooltips = false;

                        var elements = $('.trip-list').find('div[data-id]');
                        var uiIndex = elements.index($(ui.item).find('div').first());
                        var nextSegment, $notesWrap = null;
                        if (angular.element(ui.item).scope().segment.type == 'planStart') {
                            nextSegment = $(elements[uiIndex + 1]);

                            if ($(nextSegment).is('[data-trip-end]')) {
                                $notesWrap = $(nextSegment).parent().prev();
                            }
                        } else {
                            nextSegment = $(elements[uiIndex - 1]);
                            $notesWrap = $(nextSegment);
                        }

                        if (null !== $notesWrap && $notesWrap.find('.js-notes-filled').length > 0) {
                            const confirmPopup = dialog.fastCreate(
                                Translator.trans('confirmation', {}, 'trips'),
                                Translator.trans('you-sure-also-delete-notes', {}, 'trips'),
                                true,
                                true,
                                [
                                    {
                                        'class': 'btn-silver',
                                        'text': Translator.trans('button.no'),
                                        'click': () => {
                                            confirmPopup.destroy();
                                            $('.trip-list').sortable('cancel');
                                        },
                                    },
                                    {
                                        'class': 'btn-blue',
                                        'text': Translator.trans('button.yes'),
                                        'click': () => {
                                            confirmPopup.destroy();
                                            requestPlanMove(ui, nextSegment);
                                        },
                                    },
                                ],
                                400,
                                300
                            );
                            return;
                        }

                        requestPlanMove(ui, nextSegment);
                    }
                })
                    .on('click', '.details-extproperties-row a[href="#collapse"]', function(e) {
                        e.preventDefault();
                        let $rowParent = $(this).parent();
                        if ($(this).hasClass('properties-value-collapse-name')) {
                            $rowParent = $rowParent.parent();
                        }
                        $rowParent.toggleClass('detail-property-expanded');
                    });

                if (/\/print\//.test($location.$$absUrl) && !$scope.spinner) {
                    $timeout($window.print, 1000);
                    //$window.print();
                }
            });
        }])
        .controller('flashMessageTripit', [
            '$scope',
            function ($scope) {
                dialog.fastCreate(
                    Translator.trans(/** @Desc("Import TripIt Reservations") */ 'timeline.tripit_popup.title'),
                    Translator.trans(/** @Desc("We did not find any travel reservations in your TripIt account.") */ 'timeline.tripit_popup.content'),
                    true,
                    true,
                    [
                        {
                            'text': Translator.trans('button.close'),
                            'click': function () {
                                $(this).dialog('close');
                            },
                            'class': 'btn-silver'
                        }
                    ],
                    500
                )
            }
        ]);

    $(function () {
        angular.bootstrap('body', ['app']);
    });
});
