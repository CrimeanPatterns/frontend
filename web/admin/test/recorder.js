$(document).ready(function() {
	var body = $('body');
	var log = [];
	var active = true;
	var startTime;
	var lastText = null;

	body.append('<div id="recorderButton" style="position: fixed; top: 10px; right: 10px; width: 50px; height: 30px; background-color: yellow; border: 1px solid black; z-index: 100;"></div>');
	var button = $('#recorderButton');

	body.on('mousemove mousedown mouseup click keypress keydown keyup', '*', null, function (event){
		if(!active)
			return;

		var text = event.type;
		switch(event.type){
			case 'mousemove':
			case 'mousedown':
			case 'mouseup':
			case 'click':
				text += ' ' + event.clientX + ' ' + event.clientY;
				break;
			case 'keypress':
			case 'keydown':
			case 'keyup':
				text += ' ' + event.charCode + ' ' + event.keyCode;
				break;
		}

		if(text == lastText)
			return;

		log.push(Math.round(window.performance.now()) + ' ' + text);
	    button.text(log.length);
		localStorage.setItem('recorder', log.join("\n"));
		lastText = text;
	});

	button.on('click', function(){
		console.log('showing recod log');
		body.append('<textarea id="recorderLog" style="position: fixed; top: 10px; left: 10px; width: 500px; height: 600px; overflow: scroll; background-color: white; border: 1px solid black; z-index: 200;"></textarea>');

		$('#recorderLog').val(log.join("\n"));
		logDiv.val(text);
	});
});



