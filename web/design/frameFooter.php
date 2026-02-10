<script>
function frameLoaded(){
	if(window.parent != window){
		if(document.getElementById('frameLoaded')){
			parent.popupFrameLoaded(1, $(document).height(), document.getElementById('closePopup').value, document.getElementById('reloadParent').value);
		}
		else
			parent.popupFrameLoaded(0, 500, 0, 0);
	}
}
</script>

<input type="hidden" id="closePopup" value="<?=$closePopup ?>">
<input type="hidden" id="reloadParent" value="<?=$reloadParent ?>">
<input type="hidden" id="frameLoaded" value="1">
<?
require "footerCommon.php";
?>
</body>
</html>