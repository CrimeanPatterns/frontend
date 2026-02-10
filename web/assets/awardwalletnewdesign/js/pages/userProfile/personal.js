/* global FB */

define([
		'jquery-boot', 'lib/dialog'],
	function ($, dialog) {
		'use strict';

		var $delete = $('.js-avatar-delete'),
			$loader = $('.js-loader'),
			$avatar = $('.js-avatar'),
			$avatarImage = $avatar.find('img'),
			$avatarField = $('#profile_personal_Avatar'),
			$avatarDeleteField = $('#profile_personal_AvatarDelete, #profile_business_AvatarDelete');

		$delete.on('click', function (e) {
			e.preventDefault();
			$avatarImage.attr('src', '/assets/awardwalletnewdesign/img/no-avatar.gif');
			$avatarDeleteField.val(1);
		});

		$avatarField.on('change', function (e) {
			var src;
			if ('files' in this) {
				var obj = this.files[0];
				src = window.URL ?
					window.URL.createObjectURL(obj) :
					window.webkitURL.createObjectURL(obj);
			} else {
				if (/fake/.test(this.value)) {
					// ie security fixed
				} else {
					src = this.value;
				}
			}
			$avatarImage.attr('src', src);
			$avatarDeleteField.val(0);
		});

		$avatarImage.on('load', function () {
			var css;
			var ratio = $(this).width() / $(this).height();
			if (ratio >= 1) css = {width: 'auto', height: '100%'};
			else css = {width: '100%', height: 'auto'};
			$(this).css(css);
		});

		$('.js-import-facebook').on('click', function (e) {
			e.preventDefault();
			$loader.show();
			FB.getLoginStatus(function (response) {
				if (response.status === 'connected') {
					FBgetAvatar()
				} else {
					FB.login(function (response) {
						if (response.authResponse) {
							FBgetAvatar();
						}
					}, {scope: ''});
				}
			})
		});

		function FBgetAvatar() {
			$avatarDeleteField.val(0);
			$loader.show();
			FB.api(
				"/me/picture",
				{
					"redirect": false,
					"type": "large"
				},
				function (response) {
					if (response && !response.error) {
						uploadImageFromUrl(response.data.url, function () {
							$loader.hide();
//                                            var container = $('<div><a href="https://gravatar.com/" target="_blank">Gravatar</a> does not have any avatars for ' + email + '</div>');
//                                            container.appendTo('body');
//                                            container.dialog({
//                                                width: 400,
//                                                modal: true,
//                                                title: 'Upload from Gravatar',
//                                                buttons: [
//                                                    {
//                                                        text: "Ok",
//                                                        'class': 'btn-blue',
//                                                        click: function () {
//                                                            $(this).dialog("close");
//                                                        }
//                                                    }
//                                                ]
//                                            });
						});
					}
				}
			);
		}

		function uploadImageFromUrl(url, error_callback) {
			var xhr = new XMLHttpRequest(); // Used xhr, jquery is not able blob responseType

			xhr.onreadystatechange = function () {
				if (this.readyState == 4 && this.status == 200) {
					var form = document.forms.personal_info;
					var formData = new FormData(form);
					formData.append($(form).find('input[type=file]').attr('name'), this.response, 'blob.' + this.response.type.match(/\/(.+)/)[1]);

					var post = new XMLHttpRequest();

					// IE10 fix
					if (typeof XDomainRequest != "undefined")
						post = new window.XDomainRequest();

					post.onload = function () {
						window.location.reload();
					};

					post.open('POST', Routing.generate('aw_profile_personal'));
					post.send(formData);
				} else {
					if (this.readyState == 4 && this.status != 200) {
						error_callback();
					}
				}
			};
			xhr.open('GET', url);
			xhr.responseType = 'blob';
			xhr.send();
		}

		$('.js-import-gravatar').on('click',function (e) {
			e.preventDefault();
			var email = $('#personal_info').data('user-email');
			$avatarDeleteField.val(0);
			$loader.show();
			$.ajax({
				url: Routing.generate('aw_common_upload_gravatar', {email: email}),
				method: 'POST',
				success: function (data) {
					data.url = data.url + '?rand=' + Math.random();
					uploadImageFromUrl(data.url, function () {
						$loader.hide();

						var container = $('<div><a href="https://gravatar.com/" target="_blank" rel="noopener noreferrer">Gravatar</a> does not have any avatars for ' + email + '</div>');
						container.appendTo('body');
						container.dialog({
							width: 400,
							modal: true,
							title: 'Upload from Gravatar',
							buttons: [
								{
									text: "Ok",
									'class': 'btn-blue',
									click: function () {
										$(this).dialog("close");
									}
								}
							]
						});

					});
				},
				error: function (xhr, status) {
					document.location.href = Routing.generate('aw_login');
				}
			})
		});

	});