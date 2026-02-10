/* global cancelPopup */

define(['jquery-boot', 'lib/dialog', 'jqueryui', 'routing', 'translator-boot'], function ($, dialog) {

	var Calendars = null;
	var calendarList = null;
	var linkDialog = null;
	var linkDialogContent = null;
	var userDialog = null;
	var agreeDialogContent = null;

	function calendarExport(){
		if (Calendars == null) {
			// todo fail!
			$.ajax({
				url: Routing.generate('aw_icalendar_ajax', {'action' : 'getPopupData', '_format': 'json', 'new': '1'}),
				type: "POST",
				data: {},
				dataType: 'json',
				success: function(response) {
					if (response.Count > 1) {
						Calendars = response.Agents;
					}
					else {
						Calendars = {};
						Calendars[-1] = response.Agents[0];
					}
					calendarList = response.Content;
					linkDialogContent = response.LinkDialog;
					agreeDialogContent = response.AgreeDialog;
					calendarExport();
				}
			});
		}
		else {
			if (typeof(Calendars[-1]) !== "undefined") {
				showCalendarLink(-1);
			}
			else {
				userDialog = dialog.fastCreate(
					Translator.trans('calendar.auto.import', {}, 'trips'),
					calendarList,
					true,
					true,
					[
						{
							text: Translator.trans('cancel'),
							click: function () {
								$(this).dialog('close');
							},
							'class': 'btn-blue'
						}
					],
					600
				);
			}
		}
	}

	function showCalendarLink(agent, action) {
		if(userDialog) {
			userDialog.close();
			userDialog = null;
		}
		var manageLink = Routing.generate('aw_icalendar_ajax', {'action' : 'manage', '_format': 'json', 'new': '1'});
		switch (action) {
			case "new":
				// todo fail!
				$.ajax(manageLink, {
					type: "POST",
					data: {agent: agent, operation: "new"},
					success: function(data) {
						if (data.result > 0) {
							Calendars[agent].Code = data.Code;
							showCalendarLink(agent);
						}
						else
							cancelPopup();
					}
				});
				break;
			case "remove":
				// todo fail!
				$.ajax(manageLink, {
					type: "POST",
					data: {agent: agent, operation: "remove"},
					success: function(data) {
						if (data.result > 0) {
							Calendars[agent].Code = '0';
							showCalendarLink(agent);
						}
						else
							cancelPopup();
					}
				});
				break;
			default:
				switch (Calendars[agent].Code) {
					case null:
						showAgreePopup(agent);
						break;
					case "0":
						switchManage(false, agent);
						break;
					default:
						switchManage(true, agent);
						break;
				}
		}
	}

	function switchManage(remove, agent) {
		linkDialog = dialog.fastCreate(
			Translator.trans('calendar.auto.import', {}, 'trips'),
			linkDialogContent,
			true,
			true,
			[
				{
					id: 'manageButton',
					text: Translator.trans('button.calendar.link.remove'),
					click: function () {
						$(this).dialog('close');
					},
					'class': 'btn-blue'
				},
				{
					text: Translator.trans('button.ok'),
					click: function () {
						$(this).dialog('close');
					},
					'class': 'btn-blue'
				}
			],
			600
		);

		if (remove) {
			var url = document.location.protocol + '//' + document.location.host + '/iCal/' + Calendars[agent].Code;
			document.getElementById('linkRemovedContainer').style.display = 'none';
			document.getElementById('calendarLinkContainer').style.display = 'block';
			$('#calendarLink').val(url);
			$('#manageButton span').text(Translator.trans('button.calendar.link.remove'));
			$('#manageButton').on('click', null, function(){ showCalendarLink(agent, "remove"); });

		}
		else {
			document.getElementById('linkRemovedContainer').style.display = 'block';
			document.getElementById('calendarLinkContainer').style.display = 'none';
			$('#manageButton span').text(Translator.trans('button.calendar.link.new'));
			$('#manageButton').on('click', null, function(){ showAgreePopup(agent); });
		}
	}

	function showAgreePopup(agent) {
		//if(linkDialog) {
		//	linkDialog.close();
		//	linkDialog = null;
		//}
		var agreeDialog = dialog.fastCreate(
            Translator.trans('calendar.auto.import', {}, 'trips'),
			agreeDialogContent,
			true,
			true,
			[
				{
					text: Translator.trans('button.no'),
					click: function () {
						$(this).dialog('close');
					},
					'class': 'btn-silver'
				},
				{
					text: Translator.trans('button.yes'),
					click: function () {
						$(this).dialog('close');
						showCalendarLink(agent, 'new');
					},
					'class': 'btn-blue'
				}
			],
			600
		);
	}

	$('body').on('click', '.show-calendar-link', null, function(){
		showCalendarLink($(this).data('agent'));
	});

	return calendarExport;

});