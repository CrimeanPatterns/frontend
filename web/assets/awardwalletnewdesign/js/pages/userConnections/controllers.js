define([
    'angular-boot', 'angular-scroll', 'directives/dialog', 'common/alerts'
], function () {
    'use strict';

    angular
        .module('userConnectionsPage-ctrl', ['dialog-directive'])
        .controller('userConnectionsPageCtrl', [
            '$scope',
            '$filter',
            '$document',
            'dialogService',
            '$compile',
            function ($scope, $filter, $document, dialogService, $compile) {
                $scope.Routing = Routing;
                $scope.sentEmail = [];
                $scope.sentPending = [];
                $scope.pendingVisible = true;

                var invalidEmailError = Translator.trans('ndr.popup.title');

                var updateCounter = function() {
                    var counter = $('a[data-id=all] span');
                    var rows = $('.manage-users:first tbody tr:visible');
                    counter.text(rows.length);
                };

                dialogService.createNamed('family-member-invite', $('<div class="dialog"/>').appendTo('body').html('<input type="email" name="familyMemberEmail"><div class="error-message-blk hidden err m-top"> <i class="icon-warning-small"></i> <p>' + invalidEmailError + '</p> </div>'), {
                    width: 450,
                    height: 'auto',
                    autoOpen: false,
                    modal: true,
                    title: Translator.trans(/** @Desc("Please enter a valid email address:") */ 'connections.popup.invite.header')
                });

                $scope.deleteUserAgent = function (connection) {
                    var userAgentID = connection.UserAgentID;
                    var content = Translator.trans(/** @Desc("You are about to delete <b>%name%`s</b> name from your profile, please note that since <b>%name%</b> is not a connected user with a separate AwardWallet account all of the loyalty accounts (if any) that belong to this user will be transferred to you automatically?") */'connections.popup.delete.fm.new', {'name': connection.FullName});

                    if (connection.ClientID)
                        content = Translator.trans(/** @Desc("Are you sure you want to delete this connection with <b>%name%</b>?") */'connections.popup.delete', {'name': connection.FullName});

                    dialogService.createNamed('connection-delete', $('<div />').appendTo('body').html(content), {
                        title: Translator.trans(/** @Desc("Delete connection") */ 'delete.connection'),
                        width: 450,
                        height: 200,
                        autoOpen: true,
                        modal: true,
                        buttons: [
                            {
                                text: Translator.trans('alerts.btn.cancel'),
                                click: function () {
                                    $(this).dialog("close");
                                },
                                'class': 'btn-silver'
                            },
                            {
                                text: Translator.trans('alerts.btn.ok'),
                                click: function (e) {
                                    var self = this;
                                    $(e.target).addClass('loader');
                                    // todo fail!
                                    $.ajax({
                                        url: Routing.generate('aw_members_deny', {'userAgentId': userAgentID}),
                                        type: 'POST',
                                        success: function (data) {
                                            const {success, error = 'Unknown error occurred'} = data;

                                            if (success) {
                                                $('tr[data-useragent-id="' + userAgentID + '"]').fadeOut(500, function () {
                                                    $(self).dialog("close");
                                                    $('.user-blk-item a[data-id=' + userAgentID + ']').remove();
                                                    updateCounter();
                                                });
                                            } else {
                                                $(self).dialog("close");
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
                        ]
                    });
                };

                $scope.deleteEmailInvite = function (emailInviteID, email) {

                    var content = Translator.trans(/** @Desc("Are you sure you want to delete this connection with <b>%name%</b>?") */'connections.popup.delete', {'name': email});

                    dialogService
                        .createNamed('connection-delete', $('<div />')
                            .appendTo('body')
                            .html(content), {
                            title    : Translator.trans(/** @Desc("Delete connection") */ 'delete.connection'),
                            width    : 450,
                            height   : 200,
                            autoOpen : true,
                            modal    : true,
                            buttons  : [
                                {
                                    text    : Translator.trans('alerts.btn.cancel'),
                                    click   : function() {
                                        $(this).dialog("close");
                                    },
                                    'class' : 'btn-silver'
                                },
                                {
                                    text    : Translator.trans('alerts.btn.ok'),
                                    click   : function(e) {
                                        var self = this;
                                        $(e.target).addClass('loader');
                                        $.ajax({
                                            url     : Routing.generate('aw_cancel_email_invite', {'invitecodeid' : emailInviteID}),
                                            type    : 'POST',
                                            success : function(data) {
                                                $('tr[data-emailinvite-id="' + emailInviteID + '"]').fadeOut(500, function() {
                                                    $(self).dialog("close");
                                                    $(this).remove();
                                                    updateCounter();
                                                });
                                            }
                                        });
                                    },
                                    'class' : 'btn-blue'
                                }
                            ]
                        });
                };

                $('input[name="familyMemberEmail"]').on('keydown blur', function () {
                   $(this).siblings('.error-message-blk').addClass('hidden');
                });

                $scope.inviteFamilyMember = function (userAgentID, connection) {
                    if($scope.sentEmail.indexOf(userAgentID) !== -1) return;

                    var dialog = dialogService.get("family-member-invite");

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
                                var inviteEmail = inviteEmailInput.val().toLowerCase();
                                var regex = /[a-z0-9!#$%&'*+/=?^_`{|}~-]+(?:\.[a-z0-9!#$%&'*+/=?^_`{|}~-]+)*@(?:[a-z0-9](?:[a-z0-9-]*[a-z0-9])?\.)+[a-z0-9](?:[a-z0-9-]*[a-z0-9])?/;

                                if (!regex.test(inviteEmail)) {
                                    $(this).find('.error-message-blk').removeClass('hidden');
                                    return;
                                }

                                $scope.sentEmail.push(userAgentID);
                                $(this).dialog("close");
                                inviteEmailInput.val('');
                                var $tr = $('tr[data-useragent-id="' + userAgentID + '"]');
                                var icon = $('td[class="actions"] i[class="icon-user-add"]', $tr).attr('class', 'loader');

                                $.ajax({
                                    url: Routing.generate('aw_invite_family', {userAgentId: userAgentID, Email: inviteEmail}),
                                    success: function(response){
                                        if (response.success) {
                                            icon.attr('class', 'icon-sent');

                                            $('td[class="status"] p[class="expired"]', $tr).fadeTo(400, 0);

                                            var html = '<div class="prev"><i class="icon-not-updated"></i></div>' +
                                                '<p>' + Translator.trans('user.connections.waiting_approval') + '&nbsp;' + '</p>';

                                            var element = $compile(html)($scope);

                                            $('td[class="status"]', $tr).html('').append(element);
                                            $('td[class="js-email"] > span', $tr).text(inviteEmail);
                                            $('a[data-ng-click^="deleteUserAgent"]', $tr)
                                                .parent().empty()
                                                .append($compile('<a data-ng-click="cancelInvite(' + userAgentID + ')" style="cursor: pointer;" title="' + Translator.trans('user.connections.invitation_cancel') + '"><i class="icon-pending"></i></a>')($scope));
                                        } else {
                                            icon.attr('class', 'icon-user-add');
                                            jAlert({content: response.error, type: 'error'});
                                            $scope.sentEmail.splice($scope.sentEmail.indexOf(userAgentID), 1);
                                        }
                                    },
                                    type: 'POST',
                                    suppressErrors: true,
                                    data : {
                                        'Email'       : inviteEmail,
                                        'AgentID'     : connection.AgentID,
                                        'UserAgentID' : connection.UserAgentID
                                    }
                                });
                            },
                            'class': 'btn-blue'
                        }
                    ]);
                    connection && connection.Email ? $('input[name="familyMemberEmail"]').val(connection.Email) : null;
                    dialog.open();
                };

	            $scope.resendInvite = function (userAgentID, AgentID) {

		            if ($scope.sentEmail.indexOf(userAgentID) !== -1) return;
		            $scope.sentEmail.push(userAgentID);

		            var icon = $('tr[data-useragent-id="' + userAgentID + '"] td[class="actions"] i[class="icon-user-update"]').attr('class', 'loader');
                    // todo fail!
		            $.ajax({
                            type: "POST",
				            //url: "/agent/sendReminder.php?UserID=" + AgentID + '&Source=*',
				            url: Routing.generate('aw_connection_send_reminder'),
                            data: {
                                invitee_id: AgentID
                            },
				            success: function (response) {
					            icon.attr('class', 'icon-sent');
				            }
			            }
		            );
	            };

                $scope.resendEmailInvite = function (emailInviteID, source) {

                    if($scope.sentEmail.indexOf(emailInviteID) !== -1) return;
                    $scope.sentEmail.push(emailInviteID);

	                var icon = $('tr[data-emailinvite-id="' + emailInviteID + '"] td[class="actions"] i[class="icon-user-update"]').attr('class', 'loader');
                    // todo fail!
                    $.ajax(
                        {
                            type: "POST",
                            //url: "/agent/sendEmailReminder.php?InviteCodeID="+emailInviteID+'&Source='+source,
                            url: Routing.generate('aw_connection_send_reminder'),
                            data: {
                                email_invite_id: emailInviteID
                            },
                            success: function(response){
                                icon.attr('class', 'icon-sent');
                            }
                        }
                    );
                };

                $scope.cancelInvite = function(userAgentID) {
                    var $tr = $('tr[data-useragent-id="' + userAgentID + '"]');
                    if (!$tr.length)
                        return;
                    var $statusIco = $tr.find('i.icon-pending');
                    $statusIco.length ? $statusIco.attr('class', 'loader') : null;
                    return $.post(Routing.generate('aw_invite_cancel', {useragentid : userAgentID}), function(response) {
                        $statusIco.length ? $statusIco.attr('class', 'icon-pending') : null;
                        if (response.success) {
                            var indx;
                            if (-1 !== (indx = $scope.sentEmail.indexOf(userAgentID)))
                                $scope.sentEmail.splice(indx, 1);
                            $tr.find('td.status').empty();
                            $tr.find('i.icon-pending, i.icon-not-updated, i.icon-sent', 'ul.list-actions').each(function(i) {
                                $(this).css('opacity', .25).parent().attr('href', '#').off();
                            });

                            var emailInvite = $tr.find('td:eq(3) span').text();
                            if ('' !== emailInvite)
                                $tr.closest('tbody').find('tr[data-emailinvite-id] td:eq(0) span:contains("' + emailInvite + '")').closest('tr').remove();

                            window.location.reload();
                        }
                    }, 'json');
                };

	            $scope.approvePendingConnection = function (AgentID, successPath) {

		            if ($scope.sentPending.indexOf(AgentID) !== -1) return;
		            $scope.sentPending.push(AgentID);

		            var approveButton = $('tr[data-agent-id="' + AgentID + '"] td[class="actions"] a[class="btn-blue"]').addClass('loader');

                    // todo fail!
		            $.ajax({
			            url: '/agent/approve.php?AgentID=' + AgentID,
			            type: 'POST',
			            success: function (response) {
				            if (response == 'OK') {
					            //$('tr[data-agent-id="' + AgentID + '"]').fadeOut(500, function () {
						         //   $(this).remove();
						         //   if ($('.main-blk-content table:nth-of-type(2) tbody tr').length === 0) {
							     //       $('.main-blk-content div.title-note:nth-of-type(2)').slideUp();
							     //       $('.main-blk-content table:nth-of-type(2)').fadeOut();
						         //   }
					            //});
                                window.location.href = successPath;
				            }
				            else {
                                approveButton.removeClass('loader');
					            jAlert(response);
				            }
			            }
		            });
	            };

                $scope.denyPendingConnection = function (AgentID, userAgentID) {

                    if($scope.sentPending.indexOf(AgentID) !== -1) return;
                    $scope.sentPending.push(AgentID);
                    // todo fail!
                    $.ajax({
                        url: Routing.generate('aw_members_deny', {'userAgentId': userAgentID}),
                        type: 'POST',
                        success: function(data){
                            const {success, error = 'Unknown error occurred'} = data;

                            if (success) {
                                $('tr[data-agent-id="' + AgentID + '"]').fadeOut(500, function () {
                                    $(this).remove();
                                    if($('.pending-table tbody tr').length == 0){
                                        $scope.pendingVisible = false;
                                    }
                                    $scope.$apply();
                                });
                                $('tr[data-useragent-id="' + userAgentID + '"]').fadeOut(500, function () {
                                    $(this).remove();
                                });
                            } else {
                                dialogService.alert(
                                    error,
                                    Translator.trans('alerts.error')
                                );
                            }
                        }
                    });
                };
            }]);
});