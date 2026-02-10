<?php

if($_SERVER['REQUEST_METHOD'] == 'POST') {
	echo "<pre>" . htmlspecialchars(var_export($_POST, true)) . "</pre>";
	if($_POST['multi'] == ['one', 'two'])
		echo '<b>*PASS*</b>';
}

?>
<html>
<body>
	<form method="POST" enctype="multipart/form-data">
		<input type="hidden" name="multi[]" value="one"/>
		<input type="hidden" name="multi[]" value="two"/>
		<input type="submit" name="s1" value="Submit"/>
	</form>
</body>
</html>