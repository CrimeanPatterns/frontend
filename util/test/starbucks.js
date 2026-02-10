var page = require('webpage').create();

page.settings.userAgent = 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_11_4) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/50.0.2661.102 Safari/537.36';

page.onConsoleMessage = function(msg) {
  console.log(msg);
};

page.open('https://www.starbucks.com/account/signin', function (status) {
	if (status !== 'success') {
		console.log('Unable to access network: ' + status);
	} else {
		page.evaluate(function () {
			form = $('form#accountForm');
			console.log('form found: ' + form.length);
			var login = form.find('input[placeholder = "Username or email"], input[placeholder = "Benutzername oder E-Mail"]');
			console.log('login found: ' + login.length);
   			var pass = form.find('input[placeholder = "Password"], input[placeholder = "Passwort"]');
			console.log('pass found: ' + pass.length);
			login.val("veresch");
   			pass.val("trtrtrt");
			console.log('credentials set');

			form.find('button').click();
		});

		page.render('form-ready.png');

		console.log('clicking');
		page.evaluate(function () {
			form = $('form#accountForm');
			console.log('form found: ' + form.length);
			form.find('button').click();
		});
		setTimeout(function(){
			page.render('form-sent-5.png');
		}, 5000);
		setTimeout(function(){
			page.render('form-sent-10.png');
			phantom.exit();
		}, 10000);

	}
});
