<style>
table.stats{
	border-collapse: collapse;
}
table.stats td{
	border: 1px solid gray;
	padding: 3px;
}
table.stats tr.head td, table.stats tr.total td{
	background-color: yellow;
	font-weight: bold;
}
</style>

<script type="text/javascript">

	function showToday(){
		form = document.forms['editor_form'];
		form['StartDate'].value = '<?=date(DATE_FORMAT)?>';
		form['EndDate'].value = '<?=date(DATE_FORMAT, time() + SECONDS_PER_DAY)?>';
		form.submitButton.value='submit';
		form.submit();
	}

	function shiftDate(form, field, shift){
		var d = new Date(form[field].value);
		d.setDate(d.getDate() + shift);
		var day = d.getDate();
		var month = d.getMonth() + 1; //months are zero based
		var year = d.getFullYear();
		form[field].value = month + "/" + day + "/" + year;
	}

	function navigateDay(shift){
		form = document.forms['editor_form'];
		shiftDate(form, 'StartDate', shift);
		shiftDate(form, 'EndDate', shift);
		form.submitButton.value='submit';
		form.submit();
	}

</script>
<?
function getTodayButtons(){
	return "<input type='button' onclick=\"navigateDay(-1)\" value='&lt;'>
	<input type='button' onclick=\"showToday()\" value='Today'>
	<input type='button' onclick=\"navigateDay(1)\" value='&gt;'>";
}
?>