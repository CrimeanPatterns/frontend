<?
require "../../kernel/public.php";

$sTitle = "Stats";
$period = ArrayVal($_GET, 'Period', 'day');
$periods = array("hour", "day", "week", "month");
if(!in_array($period, $periods))
	$period = "day";

require "$sPath/lib/admin/design/header.php";

?>
<div>
<form method="get">
	Period: <select name="Period" onchange="this.form.submit();">
		<? echo DrawArrayOptions(array_combine($periods, $periods), $period) ?>
	</select>
</form>
</div>
<table cellpadding="0" cellspacing="0" border="0">
	<tr>
		<td colspan="2" style="font-weight: bold;">Apache</td>
	</tr>
	<tr>
		<td><img src="awardwallet.dir/apache-awardwallet/apache_connections-1<?=$period?>.png" alt=""/></td>
	</tr>
	<tr>
		<td colspan="2" style="font-weight: bold;">CPU</td>
	</tr>
	<tr>
		<td><img src="awardwallet.dir/load/load-1<?=$period?>.png" alt=""/></td>
	</tr>
	<tr>
		<td colspan="2" style="font-weight: bold;">Disk</td>
	</tr>
	<tr>
		<td><img src="awardwallet.dir/disk-sda/disk_octets-1<?=$period?>.png" alt=""/></td>
	</tr>
	<tr>
		<td colspan="2" style="font-weight: bold;">MySQL</td>
	</tr>
	<tr>
		<td><img src="awardwallet.dir/mysql-awardwallet/mysql_commands-select-1<?=$period?>.png" alt=""/></td>
	</tr>
	<tr>
		<td colspan="2" style="font-weight: bold;">Memory</td>
	</tr>
	<tr>
		<td><img src="awardwallet.dir/memory/memory-used-1<?=$period?>.png" alt=""/></td>
	</tr>
	<tr>
		<td colspan="2" style="font-weight: bold;">Account list response time</td>
	</tr>
	<tr>
		<td><img src="awardwallet.dir/curl-account_list/response_time-1<?=$period?>.png" alt=""/></td>
	</tr>
	<tr>
		<td colspan="2" style="font-weight: bold;">Traffic</td>
	</tr>
	<tr>
		<td><img src="awardwallet.dir/interface/if_octets-eth0-1<?=$period?>.png" alt=""/></td>
	</tr>
</table>
<a href="awardwallet.xhtml">All graphics</a>
<?
require "$sPath/lib/admin/design/footer.php";
