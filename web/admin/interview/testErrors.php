<?
require __DIR__ . '/../../kernel/public.php';
?>
<html>
<body>
	<? if($_SERVER['REQUEST_METHOD'] == 'GET') {
		if(isset($_GET['type']))
			$type = $_GET['type'];
		else
			$type = 'info';
		?>
		<img src="/lib/images/<?=$type?>.gif"/><br/>
		<form method="post">
			Enter your question:<br/>
			<textarea name="question"></textarea>
			<br/>
			<input type="submit"/>
		</form>
	<? } else {
		/** @var Doctrine\DBAL\Connection $conn */
		$conn = getSymfonyContainer()->get('database_connection');
		$conn->executeQuery("insert into ContactUs(Message) values('".$_POST['question']."')");
		?>
		Thank you.<br/>
		Your question <?=$_POST['question']?> was submitted.

		<script type="text/javascript">
			var question = '<?=addslashes($_POST['question'])?>';
			if(question.length > 1000) {
				alert('You are great writer. Here your free coupon: #1234');
			}
		</script>
	<? } ?>
</body>
</html>

