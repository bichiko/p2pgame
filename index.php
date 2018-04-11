<?php
// Get the session ID.
$ses_id = session_id();
if (empty($ses_id)) {
    session_start();
    $ses_id = session_id();
}
?>
<!DOCTYPE html>
<html>
<head>
	<title></title>
</head>
<script src="http://code.jquery.com/jquery-1.11.0.min.js"></script>
<body>
<h1 id="status">Connecting...</h1>
<form name="chat">
	<div style="border:solid 1px;width: 100%;overflow:auto;height: 200px;" id="output"></div>
	<input autofocus="on" type="text" name="text">
	<input type="button" value="send" name="send">
</form>
<script>
var conn = new WebSocket('ws://localhost:8080');

conn.onopen = function(e) {
	$('#status').html('Connected').css('color','green')
    console.log("Connection established!");
};

conn.onmessage = function(e) {
	$('#output').html(function(){
		return this.innerHTML + '<br> someone: ' + e.data
	})
    console.log(typeof e.data);
    console.log(JSON.parse(e.data))
};

document.forms.chat.onsubmit = function(e){
	e.preventDefault()
}

document.forms.chat.send.onclick = function(){
	conn.send(document.forms.chat.text.value)
	$('#output').html(function(){
		return this.innerHTML + 'you: <b>' + document.forms.chat.text.value + '</b><br>' 
	})
	document.forms.chat.text.value = ''

}
</script>
</body>
</html>