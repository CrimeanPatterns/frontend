define([
    'angular-boot',
    'lib/utils',
    'dateTimeDiff',
    'lib/dialog',
    'lib/customizer',
    'pages/timeline/main',
    'routing',
    'common/alerts'
], function (angular, utils, dateTimeDiff, dialog, customizer) {
    angular = angular && angular.__esModule ? angular.default : angular;

    angular.module('app')
        .service('$timelineData', ['$stateParams', '$q', '$http', '$sce', '$state', '$window', function ($stateParams, $q, $http, $sce, $state, $window) {
            let options = {};
            var extend = function (item) {

                if(!item.type)
                    return;

                if (!item.type.match(/plan/))
                    item.undroppable = true;

                $.extend(item, {
                    trustHtml: (html) => $sce.trustAsHtml(html),
                });

                if (item.type === 'planStart') {
                    $.extend(item, {
                        getMapUrl: function () {
                            if (typeof this.map === 'undefined' || (angular.isArray(this.map) && this.map.length === 0)) {
                                return null;
                            }

                            return Routing.generate('aw_flight_map', {
                                code: this.map.join(','),
                                size: '240x240'
                            });
                        },
                        printTravelPlan: function () {
                            $window.open(Routing.generate('aw_timeline_print') + 'shared-plan/' + this.shareCode);
                        },
                        planDuration: function () {
                            return $sce.trustAsHtml(Translator.trans(/** @Desc("for %duration%") */ 'plan-duration', {
                                duration: `<b>${this.duration}</b>`
                            }, 'trips'));
                        },
                        getNotes: () => $sce.trustAsHtml(utils.linkify(item.notes.text)),
                    });
                }

                if (item.type === 'date') {
                    $.extend(item, {
                        getRelativeDate: function () {
                            return dateTimeDiff.longFormatViaDates(new Date(), new Date(this.localDateISO));
                        },
                        getState: function () {
                            const dayStart = new Date();
                            dayStart.setHours(0,0,0,0);
                            return this.startDate <= (dayStart / 1000);
                        },
                        getDaysNumberFromToday: function () {
                            var diff = Math.abs(new Date(this.startDate * 1000) - new Date());
                            return Math.floor(diff / 1000 / 60 / 60 / 24);
                        }
                    });
                }

                if (item.type === 'segment') {

                    if(item.details){
                        item.details.extProperties = Object
                            .keys(item.details)
                            .filter(propName => {
                                return typeof item.details[propName] === 'string' && [
                                    'notes',
                                    'monitoredStatus',
                                    'canEdit',
                                    'shareCode',
                                    'autoLoginLink',
                                    'refreshLink',
                                    'accountId',
                                    'currencyCode'
                                ].indexOf(propName) === -1;
                            }).reduce((acc, propName) => {
                                acc[propName] = item.details[propName];
                                return acc;
                            }, {});
                    }

                    $.extend(item, {
                        _formatTime: function (time) {
                            var parts = time.split(' ');
                            return (parts.length > 1) ? $sce.trustAsHtml(parts[0] + '<span>' + parts[1] + '</span>') : $sce.trustAsHtml(time);
                        },
                        getTitle: function () {
                            return $sce.trustAsHtml(this.title);
                        },
                        getImgSrc: function(size) {
                            if (
                                typeof this.map.points === 'undefined'
                                || (angular.isArray(this.map.points) && this.map.points.length === 0)) {
                                return null;
                            }

                            if (this.map.points.length > 1) {
                                return Routing.generate('aw_flight_map', {
                                    code: this.map.points.join('-'),
                                    size: size
                                });
                            } else {
                                return Routing.generate('aw_flight_map', {
                                    code: this.map.points[0],
                                    size: size
                                });
                            }
                        },
                        getLocalTime: function () {
                            return this._formatTime(this.localTime);
                        },
                        getArrDate: function () {
                            return this._formatTime(this.map.arrTime);
                        },
                        getState: function () {
                            return this.endDate <= (Date.now() / 1000);
                        },
                        getBetween: function () {
                            if ('undefined' !== typeof this.localDateISO) {
                                return dateTimeDiff.longFormatViaDates(new Date(), new Date(this.localDateISO));
                            }

                            return dateTimeDiff.longFormatViaDates(new Date(), new Date(this.startDate * 1000));
                        },
                        getBetweenText: function (row) {
                            var date = '<span class="blue">' + row.date + '</span>',
                                term;
                            if (row.type === 'checkin') {
                                term = '<span class="red">' + row.nights + '</span> ' + Translator.transChoice(/** @Desc("night|nights") */ 'nights', row.nights);
                            } else if ('undefined' === typeof row.days) {
                                return $sce.trustAsHtml(date);
                            } else {
                                term = '<span class="red">' + row.days + '</span> ' + Translator.transChoice(/** @Desc("day|days") */ 'days', row.days);
                            }
                            var text = Translator.trans(/** @Desc("on %date% for %term%") */ 'between.text', {
                                date: date,
                                term: term
                            });
                            return $sce.trustAsHtml(text);
                        },
                        getNotes: function(isShort = true) {
                            let notes = this.details.notes;
                            if (-1 !== notes.indexOf("\n") && -1 === notes.indexOf('<br>')) {
                                notes = notes.replace(/\n/g, '<br>');
                            }

                            if (isShort) {
                                const flatTags = ['i', 'em', 'strong', 'b', 'u'];
                                flatTags.forEach(tag => {
                                    notes = notes
                                        .replace(new RegExp("\n<" + tag + '>', "g"), '').replace(new RegExp("\n</" + tag + ">", "g"), '')
                                        .replace(new RegExp('<' + tag + ">\n", "g"), '').replace(new RegExp('</' + tag + ">\n", "g"), '')
                                        .replace(new RegExp('<' + tag + '>', "g"), '').replace(new RegExp('</' + tag + '>', "g"), '');
                                });

                                notes = notes
                                    .replace(/(\r\n){2,}|\r{2,}|\n{2,}/g, ' ')
                                    .replace(/(<([^>]+)>)/gi, ' ');
                            }
                            notes = utils.linkify(notes);

                            return $sce.trustAsHtml(notes);
                        },
                        getRelativeDate: function () {
                            return dateTimeDiff.longFormatViaDates(new Date(), new Date(this.localDateISO));
                        },
                        getDaysNumberFromToday: function () {
                            var diff = Math.abs(new Date(this.startDate * 1000) - new Date());
                            return Math.floor(diff / 1000 / 60 / 60 / 24);
                        },
                        getTimeDiffFormated: function (row) {
                            // var diff = row.timestamp - Date.now() / 1000;
                            //
                            // if (diff > 0 && diff <= 86400) {
                            //     return dateTimeDiff.longFormatViaDateTimes(new Date(), new Date(row.timestamp * 1000));
                            // }

                            return false;
                        },
                        getDiffTimeAgo : function(time) {
                            return dateTimeDiff.longFormatViaDateTimes(new Date(), new Date(time * 1000));
                        },
                        isShowMoreLinks: function () {
                            let is = (this.details.extProperties && Object.keys(this.details.extProperties).length > 0)
                                || this.details.notes
                                || this.isManualSegment()
                                || this.isAutoAddedSegment();
                            if (is
                                && 'undefined' === typeof this.isShownInfo
                                && 'undefined' !== typeof this.alternativeFlights) {
                                this.isShownInfo = true;
                            }
                            return is;
                        },
                        isManualSegment: function() {
                            return this.origins && this.origins.manual;
                        },
                        isAirSegment: function() {
                            return this.air;
                        },
                        isTransferFormat: function () {
                            let position = this.icon.indexOf(' ');
                            let icon = (position !== -1) ? this.icon.substr(0, position) : this.icon;
                            return this.transferFormat && ['fly', 'bus', 'train', 'passage-boat', 'way'].includes(icon);
                        },
                        isAutoAddedSegment: function() {
                            return this.origins && !this.origins.manual && this.origins.auto && this.origins.auto.length > 0;
                        },
                        getEditLink: function () {
                            return Routing.generate('aw_trips_edit', {tripId: this.id});
                        },
                        visible: false,
                        getEliteLevel: function (phoneItem) {
                            return utils.escape(phoneItem.level);
                        },
                        redirectToBooking: function () {
                            var payload;
                            var row = $('.trip-title[data-id="' + this.id + '"]').closest('.trip-row');

                            var checkinDate = new Date(
                                row.find('input[type="hidden"][id^="checkinDate_"]').val()).toISOString();
                            var checkoutDate = new Date(
                                row.find('input[type="hidden"][id^="checkoutDate_"]').val()).toISOString();

                            var destination = this.details.bookingLink.formFields.selectedDestination || this.details.bookingLink.formFields.destination;

                            if(!this.details.bookingLink.formFields.selectedIata && !destination)
                                return;

                            payload = {
                                ss: destination,
                                checkin_monthday: checkinDate.slice(8,10),
                                checkin_year_month: checkinDate.slice(0,7),
                                checkout_monthday: checkoutDate.slice(8,10),
                                checkout_year_month: checkoutDate.slice(0,7),
                                timelineForm: true
                            };

                            payload.ss.replace(/Russian Federation/gi, 'Russia');

                            var url = this.details.bookingLink.formFields.url + '&' + $.param(payload);

                            var link = document.createElement('a');
                            link.href = url;
                            link.target = '_blank';
                            link.click();
                        },
                        formatCost: function(value) {
                            return Intl.NumberFormat(customizer.locales()).format(value);
                        },
                        getTravelersCount: function(count) {
                            return $sce.trustAsHtml(Translator.transChoice(/** @Desc("%number% passenger|%number% passengers") */'number-passengers', count, {'number' : count}, 'trips'));
                        },
                        alternativeFlight : function($event) {
                            let oldPopup = $('.alternative-flight:visible');
                            if (oldPopup.length) {
                                oldPopup.closest('.ui-dialog').find('.ui-dialog-titlebar-close').click();
                            }
                            let popup = jQuery('.alternative-flight', $($event.target).closest('.details-info')).clone();
                            popup.find('input[ng-checked="true"]').prop('checked', true);
                            popup
                                .find('.alternative-flight_block__name, .alternative-flight_block__add span')
                                .click((e) => $(e.target).closest('.alternative-flight_block').find('.alternative-flight_block__check input[name="customPick"]').prop('checked', true).trigger('change'))
                                .end()
                                .find('input[name="customValue"]').keyup((e) => {
                                    if ('' !== $(e.target).val()) {
                                        $(e.target).closest('.alternative-flight_block__add').find('>span').trigger('click');
                                    }
                                })
                                .end()
                                .data('addclass', 'dialog-alternative-flight')
                                .find('.js-btn-save')
                                .click((e) => this.updateAlternativeFlight(e))
                                .find('.alternative-flight-tpl')
                                .removeClass('alternative-flight-tpl');
                            this.dialogFlight = dialog.createNamed('flights', popup, {
                                title : Translator.trans(/** @Desc("Choose Alternative Flight") */ 'choose-alternative-flight', {}, 'trips'),
                                width : 700,
                                resizable : false
                            });

                            this.dialogFlight.open();
                            this.dialogFlight.setOption('close', () => this.dialogFlight.destroy());
                        },
                        updateAlternativeFlight : function(e) {
                            const $dialog = $(e.target).closest('.ui-widget-content');
                            $('.customset-errors', $dialog).empty();
                            let $btn = $(e.target),
                                pick = $('input[name="customPick"]:checked', $dialog).val(),
                                customValue = $('input[name="customValue"]', $dialog).val();
                            $btn.addClass('loader');
                            const self = this;
                            $.post(Routing.generate('aw_timeline_milevalue_customset'), {
                                'id' : this.id,
                                'customPick' : pick,
                                'customValue' : customValue
                            }, function(response) {
                                $btn.removeClass('loader');
                                if (response.success) {
                                    for (let i in response.data) {
                                        self.alternativeFlights[i] = response.data[i];
                                    }
                                    self.dialogFlight.close();
                                } else if (response.errors) {
                                    $('.customset-errors', $dialog).html(Object.values(response.errors).join('<br>'));
                                }
                            }, 'json');
                        },
                        formatFileSize: function(bytes) {
                            return utils.formatFileSize(bytes);
                        },
                        formatDateTime: function(strDate) {
                            return new Intl.DateTimeFormat(customizer.locales(), {
                                dateStyle: 'medium',
                                timeStyle: 'short'
                            }).format(Date.parse(strDate));
                        },
                        getFileLink(fileId) {
                            return Routing.generate('aw_timeline_itinerary_fetch_file', { itineraryFileId: fileId });
                        },
                        setOptions: function(params) {
                            options = params;
                        },
                        printPropertiesValue(name, value) {
                            if (Object.prototype.hasOwnProperty.call(options, 'collapseFieldProperties')
                                && -1 !== options.collapseFieldProperties.indexOf(name)) {
                                return $sce.trustAsHtml(`
                                    <a class="properties-value-collapse" href="#collapse"></a>
                                    <div class="details-property-name"><a class="properties-value-collapse-name" href="#collapse">${name}</a></div>
                                    <div class="details-property-value details-properties-collapse">${value}</div>
                                `);
                            }

                            return $sce.trustAsHtml(`
                                <div class="details-property-name">${name}</div>
                                <div class="details-property-value">${value}</div>
                            `);
                        },
                        isLayoverHasLounges: function() {
                            return 'L.' === this.id.substr(0, 2) && this.lounges && Number.isInteger(this.lounges) && this.lounges > 0;
                        },
                        showPopupNativeApps: function() {
                            const head = Translator.trans(/** @Desc("AwardWallet has native iOS and Android apps, %break%please pick the one you need") */'awardwallet-has-native-apps-pick-need', {'break': ''});
                            const content = `
                                <div>
                                    <a href="https://apps.apple.com/us/app/awardwallet-track-rewards/id388442727" target="app"><img src="/assets/awardwalletnewdesign/img/device/ios/en.png" alt=""></a>
                                    <a href="https://play.google.com/store/apps/details?id=com.itlogy.awardwallet" target="app"><img src="/assets/awardwalletnewdesign/img/device/android/en.png" alt=""></a>
                                </div>
                            `;
                            const popup = dialog.createNamed('nativeApps', $(`<div data-addclass="popup-native-apps">${content}</div>`), {
                                title: head,
                                width: 474,
                                autoOpen: true,
                                modal: true,
                                onClose: function() {
                                    popup.destroy();
                                }
                            });
                        },
                    });
                }

                let segmentClasses = item.icon;

                if (
                    item.air
                    && (
                        (
                            typeof item.map === 'undefined'
                            || typeof item.map.points === 'undefined'
                        )
                        ||
                        (
                            angular.isArray(item.map.points)
                            && item.map.points.length < 2
                        )
                    )
                ) {
                    segmentClasses += ' partial';
                }

                item.segmentClasses = segmentClasses;
            };

            return {
                fetch: function (after) {
                    var defer = $q.defer();
                    defer.promise.cancel = function () {defer.reject()};

                    // preloaded
                    if (Object.prototype.hasOwnProperty.call(window, 'TimelineData') && typeof window.TimelineData == 'object') {
                        var data = window.TimelineData;
                        data.segments.map(function (el) {
                            extend(el, data.segments);
                        });
                        defer.resolve(data);
                        return defer.promise;
                    }

                    var route = Routing.generate('aw_timeline_data', {
                        agentId: $stateParams.agentId || null,
                        before: after ? null : $stateParams.before || null,
                        after: after || null,
                        showDeleted: $stateParams.showDeleted || 0
                    });

                    if ($state.is('shared'))
                        route = Routing.generate('aw_timeline_data_shared', {
                            shareCode: $stateParams.code
                        });

                    if ($state.is('shared-plan'))
                        route = Routing.generate('aw_travelplan_data_shared', {
                            shareCode: $stateParams.code
                        });

                    if ($state.is('itineraries'))
                        route = Routing.generate('aw_timeline_data_segments', {
                            'itIds': $stateParams.itIds,
                            'agentId': $stateParams.agentId || null,
                        });

                    $http({
                        url: route,
                        disableErrorDialog: true
                    }).then(function (response) {

                        if (response.status !== 200 || typeof response.data !== 'object') {
                            if ($stateParams.openSegment)
                                sessionStorage.backUrl = Routing.generate('aw_timeline') + '?openSegment=' + $stateParams.openSegment;

                            location.href = '/login';
                        } else {
                            response.data.segments.map(function (el) {
                                extend(el);
                            });
                            defer.resolve(response.data);
                        }
                    }, function (response) {
                        if (response.status === 403) {
                            var options = {
                                content: Translator.trans(/** @Desc("You are attempting to access a travel reservation that belongs to a different account than the one you are logged in as right now. If you opened this link by mistake, please navigate to another page, if you know this is your travel reservation then you must login as a user to whom this travel reservation belongs. If you are coming to this page from an email you received try using that email address as your login value.") */ 'trips.access.denied.popup'),
                                title: Translator.trans(/** @Desc("Access Denied") */ 'access.denied'),
                                closeOnEscape: false,
                                width: 600,
                                open: function (event, ui) {
                                    $(".ui-dialog-titlebar-close", ui.dialog | ui).hide();
                                },
                                buttons: [
                                    {
                                        text: Translator.trans(/**@Desc("Ok")*/'alerts.btn.ok'),
                                        click: function () {
                                            location.href = '/timeline/';
                                        },
                                        'class': 'btn-blue'
                                    }
                                ]

                            };
                            jAlert(options);
                        }else if (response.status === 406) {
                            const options = {
                                content: response.data.error,
                                title: Translator.trans(/** @Desc("Access Denied") */ 'access.denied'),
                                closeOnEscape: false,
                                width: 600,
                                open: function (event, ui) {
                                    $(".ui-dialog-titlebar-close", ui.dialog | ui).hide();
                                },
                                buttons: [
                                    {
                                        text: Translator.trans(/**@Desc("Ok")*/'alerts.btn.ok'),
                                        click: function () {
                                            location.href = '/members/connection/' + response.data.agentId;
                                        },
                                        'class': 'btn-blue'
                                    }
                                ]

                            };
                            jAlert(options);
                        }
                    });
                    return defer.promise;
                }
            }
        }])
        .service('$travelPlans', ['$http', function ($http) {
            return {
                move: function (params) {
                    return $http.post(Routing.generate('aw_travelplan_move'), $.param(params), {headers: {'Content-Type': 'application/x-www-form-urlencoded'}});
                }
            }
        }])
        .service('scrollAndResizeListener', ['$window', '$document', '$timeout', function ($window, $document, $timeout) {
            let scrollTimeout;
            let resizeTimeout;
            let id = 0;
            const listeners = {};

            function invokeListeners() {
                const clientHeight = $document[0].documentElement.clientHeight;
                const clientWidth = $document[0].documentElement.clientWidth;

                for (let key in listeners) {
                   if (Object.prototype.hasOwnProperty.call(listeners, key)) {
                       listeners[key](clientHeight, clientWidth);
                   }
                }
            }

            $window.addEventListener('scroll', () => {
                $timeout.cancel(scrollTimeout);
                scrollTimeout = $timeout(invokeListeners, 200);
            });

            $window.addEventListener('resize', () => {
                $timeout.cancel(resizeTimeout);
                resizeTimeout = $timeout(invokeListeners, 200);
            });

            return {
                addListener(listener) {
                    let index = ++id;

                    listeners[id] = listener;

                    return () => delete listeners[index];
                }
            };
        }]);
});
