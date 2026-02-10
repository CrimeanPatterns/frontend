define([
    'angular-boot', 'angular-scroll', 'directives/dialog', 'filters/highlight', 'filters/unsafe'
], function () {
    'use strict';

    angular
        .module('invitesPage-ctrl', ['dialog-directive', 'unsafe-mod', 'highlight-mod'])
        .controller('invitesPageCtrl', [
            '$scope',
            '$filter',
            '$document',
            'dialogService',
            '$compile',
            function ($scope, $filter, $document, dialogService, $compile) {
                $scope.Routing = Routing;
                $scope.invites = $scope.shown = window.invitesData;
                $scope.activePage = 0;
                $scope.onPage = 100;
                $scope.memberName = '';
                $scope.sort = "+InviteDate";

                var initMembersList = function () {
                    $scope.shown = $filter('orderBy')($filter('filter')(
                        $filter('filter')(
                            $scope.invites,
                            {firstname: $scope.memberName},
                            false
                        )
                    ), $scope.sort);

                    $scope.pageCount = Math.ceil($scope.shown.length / $scope.onPage);
                    $scope.pages = Array.apply(null, new Array($scope.pageCount)).map(function (_, i) {
                        return i;
                    });
                    $scope.activePage = 0;
                };

                $scope.$watchGroup(['memberName'], initMembersList);

                initMembersList();

                $scope.setActivePage = function (page) {
                    $scope.activePage = page;
                };

                $scope.sortBy = function (param) {
                    if (param === $scope.sort.substring(1)) {
                        $scope.sort = ($scope.sort.slice(0, 1) === '+' ? '-' : '+') + param;
                    } else {
                        $scope.sort = '+' + param;
                    }
                    initMembersList();
                };

                $scope.clearFilters = function () {
                    $scope.membersType = '';
                    $scope.memberName = '';
                    $scope.sort = "+Name";
                    initMembersList();
                };

                var contentDeleteConnection = Translator.trans(/** @Desc("Are you sure you want to delete this connection?") */'connections.popup.delete.content');

                var contentConnect =
                    "<form id='connectForm' method='post'>"+
                    "<input type='hidden' name='InviteUserID'/>"+
                    "<input type='hidden' name='Source'/>"+
                    "<input type='radio' name='connType' value='A' id='connTypeA'><label for='connTypeA'> Can share only award balances</label><br><br>" +
                    "<input type='radio' name='connType' value='T' id='connTypeT'><label for='connTypeT'> Can share only travel plans</label><br><br>" +
                    "<input type='radio' name='connType' value='*' id='connType*'><label for='connType*'> Can share award balances and travel plans</label><br><br>"+
                    "</form>";

                dialogService.createNamed('connection-delete', $('<div />').appendTo('body').html(contentDeleteConnection), {
                    width: 450,
                    height: 200,
                    autoOpen: false,
                    modal: true
                });

                dialogService.createNamed('invites-connect', $('<div />').appendTo('body').html(contentConnect), {
                    width: 500,
                    height: 270,
                    autoOpen: false,
                    modal: true,
                    title: Translator.trans(/** @Desc("Choose connection type:") */ 'invites.popup.connect.header')
                });

                $scope.connect = function (inviteeID) {
                    var dialog = dialogService.get("invites-connect");
                    dialog.setOption("buttons", [
                        {
                            text: Translator.trans('alerts.btn.cancel'),
                            click: function () {
                                $(this).dialog("close");
                                $('input:radio[name="connType"]:checked').prop('checked', false);
                            },
                            'class': 'btn-silver'
                        },
                        {
                            text: Translator.trans('alerts.btn.ok'),
                            id: 'connectButton',
                            click: function () {

                                var radio = $('input:radio[name="connType"]:checked');

                                if (radio.length === 0) {
                                    return;
                                }

                                var connType = radio.val();

                                $('#connectButton').addClass('loader');

                                var form = document.getElementById('connectForm');
                                form.action = '/agents/add-connection';
                                form.InviteUserID.value = inviteeID;
                                form.Source.value = connType;
                                form.submit();
                                radio.prop('checked', false);
                            },
                            'class': 'btn-blue'
                        }
                    ]);
                    dialog.open();
                };

                $scope.deleteConnection = function (InviteeID, UserAgentID) {
                    var dialog = dialogService.get("connection-delete");

                    var connectElement =
                        $compile(angular.element('<li><a ng-click="connect('+ InviteeID +')" title="'+
                        Translator.trans(/** @Desc("Connect") */ 'user.invites.connect')+
                        '"><i class="icon-connect"></i></a></li>'))($scope);

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
                                    url: Routing.generate('aw_members_deny', {'userAgentId': UserAgentID}),
                                    type: 'POST',
                                    success: function (data) {
                                        const {success, error = 'Unknown error occurred'} = data;

                                        if (success) {
                                            $('tr[data-agent-id="' + InviteeID + '"] td[class="actions"] li').fadeOut(500, function () {

                                                $('tr[data-agent-id="' + InviteeID + '"] td[class="actions"] ul').append(connectElement);

                                                $('tr[data-agent-id="' + InviteeID + '"] td[class="status"]').html('<div class="prev"><i class="icon-green-check"></i></div><p>' +
                                                    Translator.trans(/** @Desc("Registered") */ 'user.invites.registered') +
                                                    '</p>');

                                            });
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

                $scope.resend = function (InviteeID) {
                    // todo fail!
                    $.ajax({
                        url: Routing.generate('aw_connection_send_reminder'),
                        data: {invitee_id: InviteeID},
                        type: 'POST',
                        success: function(response){
                            $('tr[data-agent-id="' + InviteeID + '"] a[title="Resend"] i').attr('class', 'icon-sent');
                        }
                    });
                };

                $scope.redeem = function () {
                  document.location.href = '/agent/redeem.php';
                };

            }])
            .filter('page', function () {
                return function (items, begin, length) {
                    begin = Math.max(0, begin);
                    return items.slice(begin, begin + length);
                }
            })
            .filter('toISODate', function () {
                return function (dateString) {
                    return new Date(dateString.replace(/-/g, "/")).toISOString();
                };
            })
});