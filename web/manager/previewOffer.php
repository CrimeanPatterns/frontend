<?
require "../kernel/public.php";

$sTitle = "Preview Offer";
$Interface->FooterScripts[] = "showPreview();";

require "../design/header.php";

$ajaxName = 'http://'.$_SERVER['SERVER_NAME'].'/offer/find/1';

?>
<textarea id="offerContent" style="width: 500px; height: 300px;"></textarea>
<script type="text/javascript">
	function showPreview(){
<?
//	oh = document.getElementById('offerHidden');
//	oh.innerHTML = data;
print "\$.get('$ajaxName',function(data,status){
	oc = document.getElementById('offerContent');
	oc.value = data;
	if (data != 'none'){
	if (data.indexOf('redirect') == 0){
	    var datasplit = data.split(' ');
        var ouid = datasplit[1];
        window.location = 'http://".$_SERVER['SERVER_NAME']."/manager/offer/preview/'+ouid+'?preview=yes';
	}
	else{
        $(document.body).append(data);
        setTimeout(function(){ showPopupWindow(document.getElementById('offer'))},1000);
	}
	}
  });"
?>
	}
</script>
<div id="offerHidden" style = "display: none">
</div>
<?

require "../design/footer.php";