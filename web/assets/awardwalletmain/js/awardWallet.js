function setCookie(name, value, expires, path, domain, secure) {
	s = name + "=" + escape(value) +
        ((expires) ? "; expires=" + expires.toGMTString() : "") +
        ((path) ? "; path=" + path : "") +
        ((domain) ? "; domain=" + domain : "") +
        ((secure) ? "; secure" : "");
    document.cookie = s;
}

function getCookie(name)
{
    var dc = document.cookie;
    var prefix = name + "=";
    var begin = dc.indexOf("; " + prefix);
    if (begin == -1)
    {
        begin = dc.indexOf(prefix);
        if (begin != 0) return null;
    }
    else
    {
        begin += 2;
    }
    var end = document.cookie.indexOf(";", begin);
    if (end == -1)
    {
        end = dc.length;
    }
    return unescape(dc.substring(begin + prefix.length, end));
}

function checkMobileBrowser() {
	var test = $("<div id='testdiv'></div>");
	test.css({
		'height': '1in',
		'left': '-100%',
		'position': 'absolute',
		'top': '-100%',
		'width': '1in'
	});
	test.appendTo("body");
	var dpi_x = document.getElementById('testdiv').offsetWidth;
	var dpi_y = document.getElementById('testdiv').offsetHeight;
	var height = screen.height;
	var width = screen.width;
	var width_in = width / dpi_x;
	var height_in = height / dpi_y;
	var diagonal_in = Math.round(10*Math.sqrt(width_in * width_in + height_in * height_in))/10;
	width_in = Math.round(10*width_in)/10;
	height_in = Math.round(10*height_in)/10;
	console.log('diagonal: '+diagonal_in+" inches");
	console.log('width: '+width_in+" inches");
	console.log('height: '+height_in+" inches");
	console.log('resolution: '+width+'x'+height);
	console.log('dpi_x: '+dpi_x);
	console.log('dpi_y: '+dpi_y);
	var expDate = new Date();
	expDate.setTime(expDate.getTime()+(30*24*3600));
	setCookie("MobileBrowser", "sh=" + height + "&sw=" + width + "&swi=" + width_in + "&shi=" + height_in + "&diag=" + diagonal_in + "&dpiw=" + dpi_x + "&dpih=" + dpi_y, expDate, "/");
}

var progressBarInterval;
function getProgressBar(id) {
	clearInterval(progressBarInterval);
	progressBarInterval = setInterval(function()
	{
		x = parseInt($('#'+id+' .bar').css('background-position')) + 1;
		$('#'+id+' .bar').css('background-position', '' + x + 'px 0px');
	}, 35);
	return $('<div class="progress-wrap" id="'+id+'"><div class="progressbar"><div class="bar"></div><div class="percent"></div></div></div>')
}
function updateProgressBar(id, value, complete, duration) {
    if (typeof(duration) == 'undefined')
        duration = 500;
    duration = parseInt(duration);
	$('#'+id+' .bar').stop(true).animate({width: value + '%'},
	{
		step: function(now)
		{
			$(this).next().text(Math.round(now) + '%');
		},
		duration: duration,
        //easing: 'linear',
		complete: function() {
			if (typeof(complete) == 'function')
				complete();
		}
	});
}