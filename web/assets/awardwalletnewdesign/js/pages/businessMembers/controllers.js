define([
    'angular-boot', 'angular-scroll', 'directives/dialog','directives/customizer','directives/extendedDialog', 'common/alerts', 'routing', 'filters/highlight', 'filters/unsafe'
], function (angular) {
    'use strict';
    angular = angular && angular.__esModule ? angular.default : angular;

    angular
        .module('businessMembersPage-ctrl', ['dialog-directive', 'customizer-directive', 'extendedDialog', 'unsafe-mod', 'highlight-mod'])
        .controller('businessMembersPageCtrl', [
            '$scope',
            '$filter',
            '$document',
            'dialogService',
            '$location',
            'TripsService',
            function ($scope, $filter, $document, dialogService, $location, TripsService) {
                $scope.Routing = Routing;
                $scope.members = $scope.shown = window.membersData;
                $scope.sort = "+Name";
                $scope.activePage = 0;
                $scope.onPage = 20;

                $scope.memberName = '';
                $scope.memberEmail = '';
                $scope.membersType = '';
                $scope.adminRights = ['4', '6', '7'];


                $scope.type = {
                    0: Translator.trans(/** @Desc("Pending") */'business.members.table_filter.pending'),
                    1: Translator.trans(/** @Desc("Connected User") */'business.members.table_filter.connected_user'),
                    2: Translator.trans(/** @Desc("Not Connected") */'business.members.table_filter.not_connected')
                };

                $scope.awplus = {
                    1: Translator.trans(/** @Desc("Regular") */'user.account_type.regular'),
                    2: Translator.trans(/** @Desc("Plus") */'user.account_type.plus')
                };

                var content = Translator.trans(/** @Desc("Are you sure you want to delete this connection?") */'connections.popup.delete.content');

                var dialogElement = $('<div />').appendTo('body').html(
                    Translator.trans(/** @Desc("You have two options, you can connect with another person on AwardWallet, or you can just create another name to better organize your rewards.") */'agents.popup.content')
                );

                dialogService.createNamed('connection-delete', $('<div />').appendTo('body').html(content), {
                    width: 450,
                    height: 200,
                    autoOpen: false,
                    modal: true,
                    title: Translator.trans(/** @Desc("Delete connection") */'delete.connection')
                });

                var invalidEmailError = Translator.trans('ndr.popup.title');

                dialogService.createNamed('family-member-invite', $('<div class="dialog"/>').appendTo('body').html('<input type="email" name="familyMemberEmail"><div class="error-message-blk hidden err m-top"> <i class="icon-warning-small"></i> <p>' + invalidEmailError + '</p> </div>'), {
                    width: 450,
                    height: 'auto',
                    autoOpen: false,
                    modal: true,
                    title: Translator.trans(/** @Desc("Please enter a valid email address:") */ 'connections.popup.invite.header')
                });

                dialogService.createNamed('persons-menu', dialogElement, {
                    width: '600',
                    autoOpen: false,
                    modal: true,
                    title: Translator.trans(/** @Desc("Select connection type") */ 'agents.popup.header'),
                    buttons: [
                        {
                            'text': Translator.trans(/** @Desc("Connect with another person") */'agents.popup.connect.btn'),
                            'class': 'btn-blue',
                            'click': function () {
                                window.location.href = Routing.generate('aw_create_connection')
                            }
                        },
                        {
                            'text': Translator.trans(/** @Desc("Just add a new name") */'agents.popup.add.btn'),
                            'class': 'btn-blue',
                            'click': function () {
                                window.location.href = Routing.generate('aw_add_agent')
                            }
                        }
                    ],
                    open: function () {
                        // Remove bottons focus
                        $('.ui-dialog :button').blur();
                    }
                });

                var initMembersList = function () {
                    var sort = $scope.sort;
                    if ($scope.sort.substring(1) != 'Name') {
                        sort = [$scope.sort, '+Name'];
                    }
                    $scope.shown = $filter('emptyToEnd')($filter('orderBy')($filter('filter')(
                        $filter('filter')(
                            $scope.members,
                            {Name: $scope.memberName, UserEmail: $scope.memberEmail, type: $scope.membersType},
                            false
                        )
                    ), sort), $scope.sort.substring(1));

                    $scope.pageCount = Math.ceil($scope.shown.length / $scope.onPage);
                    $scope.pages = Array.apply(null, new Array($scope.pageCount)).map(function (_, i) {
                        return i;
                    });

                    if($location.search().agentId){
                        var agentId = $location.search().agentId,
                            agent = $filter('filter')($scope.shown, {UserAgentID: agentId}, true),
                            index = $scope.shown.indexOf(agent[0]);

                        $scope.activePage = index === -1 ? 0 : Math.floor(index / $scope.onPage);
                        $location.search('agentId', null);
                    }else{
                        $scope.activePage = 0;
                    }
                };

                $scope.isAdmin = function (member) {
                    return $scope.adminRights.indexOf(member.AccessLevel) == -1 ? 'icon-person' : 'icon-person-key';
                };

                $scope.clearFilters = function () {
                    $scope.membersType = '';
                    $scope.memberName = '';
                    $scope.memberEmail = '';
                    $scope.sort = "+Name";
                    initMembersList();
                };

                $scope.setActivePage = function (page) {
                    $scope.activePage = page;
                };

                $scope.sortBy = function (param) {
                    if (param === $scope.sort.substring(1)) {
                        $scope.sort = ($scope.sort.slice(0, 1) === '+' ? '-' : '+') + param;
                    } else {
                        $scope.sort = '-' + param;
                    }
                    initMembersList();
                };

                $scope.sendReminder = function (member) {
                    member.sending = true;
                    var email = member.UserEmail;
                    var payload = member.InviteCodeID ? {email_invite_id: member.InviteCodeID} : {invitee_id: member.AgentID};


                    // todo fail!
                    $.ajax({
                            type: "POST",
                            url: Routing.generate('aw_connection_send_reminder'),
                            data: payload,
                            success: function(response){
                                member.sending = false;
                                angular.forEach($scope.members, function(value, key) {
                                    if(value.UserEmail == email){
                                        value.sent = 1;
                                    }
                                });
                                $scope.$apply(function () {
                                    member.sent = 1;
                                });
                            }
                        }
                    );
                };

                $('input[name="familyMemberEmail"]').on('keydown blur', function () {
                    $(this).siblings('.error-message-blk').addClass('hidden');
                });

                $scope.inviteFamilyMember = function (member) {
                    var dialog = dialogService.get("family-member-invite"),
                        userAgentID = member.UserAgentID;

                    if(member.Email){
                        $('input[name="familyMemberEmail"]').val(member.Email);
                    }
                    dialog.setOption("buttons", [
                        {
                            text: Translator.trans('alerts.btn.cancel'),
                            click: function () {
                                $(this).find('.error-message-blk').addClass('hidden');
                                $(this).find('input').val('');
                                $(this).dialog("close");
                            },
                            'class': 'btn-silver'
                        },
                        {
                            text:  Translator.trans(/** @Desc("Invite") */'connections.button.invite'),
                            click: function () {
                                var inviteEmailInput = $('input[name="familyMemberEmail"]');
                                var inviteEmail = inviteEmailInput.val();
                                var regex = /[a-z0-9!#$%&'*+/=?^_`{|}~-]+(?:\.[a-z0-9!#$%&'*+/=?^_`{|}~-]+)*@(?:[a-z0-9](?:[a-z0-9-]*[a-z0-9])?\.)+[a-z0-9](?:[a-z0-9-]*[a-z0-9])?/;

                                if (!regex.test(inviteEmail)) {
                                    $(this).find('.error-message-blk').removeClass('hidden');
                                    return;
                                }

                                $(this).dialog("close");
                                inviteEmailInput.val('');
                                $scope.$apply(function () {
                                    member.sending = true;
                                });
                                $.ajax({
                                    url: Routing.generate('aw_invite_family', {userAgentId: userAgentID}),
                                    success: function(response){
                                        if(response.success){
                                            angular.forEach($scope.members, function(value, key) {
                                                if(value.UserAgentID == userAgentID){
                                                    value.sent = 1;
                                                    value.UserEmail = inviteEmail;
                                                }
                                            });

                                            $scope.$apply(function () {
                                                member.sending = false;
                                                member.sent = 1;
                                                member.UserEmail = inviteEmail;
                                            });
                                        }else{
                                            jAlert({content: response.error, type: 'error'});
                                            $scope.$apply(function () {
                                                member.sending = false;
                                            });
                                        }
                                    },
                                    error: function () {
                                        $scope.$apply(function () {
                                            member.sending = false;
                                        });
                                    },
                                    type: 'POST',
                                    data: {
                                        Email: inviteEmail
                                    }
                                });
                            },
                            'class': 'btn-blue'
                        }
                    ]);
                    dialog.open();
                };

                $scope.memberDisconnect = function (member, idx) {
                    var dialog = dialogService.get("connection-delete"),
                        userAgentID = member.UserAgentID,
                        membersCounter = $('#member-btn-counter');

                    var email = member.UserEmail;

                    var url = member.InviteCodeID ?
                            Routing.generate('aw_cancel_email_invite', {'invitecodeid': member.InviteCodeID}) :
                            Routing.generate('aw_members_deny', {'userAgentId': userAgentID});

                    dialog.setOption("buttons", [
                        {
                            text: Translator.trans('alerts.btn.cancel'),
                            click: function () {
                                $(this).dialog("close");
                            },
                            'class': 'btn-silver'
                        },
                        {
                            text: Translator.trans('alerts.btn.ok'),
                            click: function () {
                                $(this).dialog("close");
                                // todo fail!
                                $.ajax({
                                    url: url,
                                    type: 'POST',
                                    success: function (data) {
                                        const {success, error = 'Unknown error occurred'} = data;

                                        if (success) {
                                            angular.forEach($scope.members, function(value, key) {
                                                if(value.UserEmail === email){
                                                    $scope.members.splice(key, 1);
                                                }
                                            });
                                            $scope.$apply(function () {
                                                $scope.shown.splice(idx, 1);
                                            });
                                            if (!member.InviteCodeID){
                                                var count = membersCounter.text().replace(/\D/g,'') - 1;
                                                membersCounter.text(count.toLocaleString('en-US'));
                                            }
                                        } else {
                                            dialogService.alert(
                                                error,
                                                Translator.trans('alerts.error')
                                            );
                                        }
                                    }
                                });
                            },
                            'class': 'btn-blue'
                        }
                    ]);
                    dialog.open();
                };

                $scope.$watchGroup(['memberName', 'membersType', 'memberEmail'], function(newValue, oldValue){
                    if (newValue === oldValue) return;
                    initMembersList();
                });

                initMembersList();

                $(document).on('click', '.js-add-new-person', function (e) {
                    e.preventDefault();
                    dialogElement.dialog('open');
                });

            }])
        .filter('page', function (TripsService) {
            return function (items, begin, length) {
                begin = Math.max(0, begin);
                var members = items.slice(begin, begin + length),
                    noTrips = members
                        .filter(function (member) {
                            return member.Trips === false;
                        });

                if(noTrips.length){
                    TripsService.getTripsCount(noTrips);
                }

                return members;
            }
        })
        .filter("emptyToEnd", function () {
            return function (array, key) {
                var isEmpty = function(val){
                    return val === "" || val === null || val === false || val === -1;
                };
                var present = array.filter(function (item) {
                    return !isEmpty(item[key]);
                });
                var empty = array.filter(function (item) {
                    return isEmpty(item[key]);
                });
                return present.concat(empty);
            };
        })
        .service('TripsService', [
            '$http',
            '$filter',
            function ($http, $filter) {
                const TripsService = this;
                this.sent = false;

                this.getTripsCount = function (users) {

                    if (TripsService.sent) return;
                    TripsService.sent = true;

                    const user_ids = users.map(function(user){return{LinkUserAgentID:user.LinkUserAgentID}});

                    $http.post(Routing.generate('aw_trip_totals'), {user_ids}).then(
                        response => {
                            const tripsCount = response.data;
                            users.forEach(user => {
                                const userTrips = $filter('filter')(tripsCount, { LinkUserAgentID: user.LinkUserAgentID });
                                user.Trips = userTrips.length ? userTrips[0].trips : 0;
                            });
                            TripsService.sent = false;
                        }
                    );
                }
            }
        ]);
});