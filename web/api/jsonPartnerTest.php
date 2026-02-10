<?php
require "../kernel/public.php";
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
<head>
	<meta http-equiv="content-type" content="text/html; charset=UTF-8">
	<title>JSON Partner API test</title>
	<script type="text/javascript" language="JavaScript" src="/assets/common/vendors/jquery/dist/jquery.min.js"></script>
	<script type="text/javascript" language="JavaScript" src="/lib/3dParty/jquery/plugins/jquery.json-2.2.min.js"></script>
</head>

<body>

<form id="testForm">
	Parameters:<br/>
	<div id="inputs">
	<div><input type="text" value="Method" class="caption"/>:<input type="text" class="value" value="listAccounts"/></div>
	<div><input type="text" value="Version" class="caption"/>:<input type="text" class="value" value="11"/></div>
	<div><input type="text" value="PartnerLogin" class="caption"/>:<input type="text" class="value" value="partnerlogin"/></div>
	<div><input type="text" value="PartnerPassword" class="caption"/>:<input type="text" class="value" value="partnerpassword"/></div>
	<div><input type="text" value="Token" class="caption"/>:<input type="text" class="value" value="yourvaletkey"/></div>
	<div><input type="text" value="" class="caption"/>:<input type="text" class="value" value=""/></div>
	</div>
	<input type="button" value="add row" onclick="addRow(); return false;"/>
	<br/><br/>
	<input type="button" value="Submit" onclick="sendForm()"/>
</form>

<br/><br/>

Request:<br/>
<textarea id="request" style="width: 500px; height: 100px;">
</textarea>
<br/>
<input type="button" value="Submit" onclick="sendJson()"/>

<br/><br/>

Response:
<pre id="response">
</pre>

<script>
function addRow(){
	$('#inputs div:first').clone().appendTo('#inputs').children('input').each(function(index, input){
		input.value = '';
	});
}

function sendForm(){
	var form = document.getElementById('testForm');
	var names = new Array();
	var values = new Array();
	$('#inputs input.caption').each(function(index, input){
		names.push($(input).attr('value'));
	});
	$('#inputs input.value').each(function(index, input){
		values.push($(input).attr('value'));
	});
	var request = new Object();
	for(n = 0; n < names.length; n++){
		name = names[n];
		if(name != '')
			request[name] = values[n];
	}
	var jsonRequest = $.toJSON(request);
	document.getElementById('request').innerHTML = jsonRequest;
	sendJson();
}

function sendJson(){
	$.ajax({
		url: "/api/jsonPartner.php",
		type: 'POST',
		data: document.getElementById('request').innerHTML,
		success: function(response){
			document.getElementById('response').innerText = JSON.stringify(response, null, 2);
			return true;
		}
	});
}

</script>

</body>
</html>
