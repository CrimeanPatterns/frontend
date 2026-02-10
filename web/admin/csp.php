<?php

header("Content-Security-Policy: default-src 'none'");

?>
<script>
    alert("disabled hello from page");
</script>
<input type="text" id="extCommand" value="extCommand"/>
<input type="text" id="extParams" value="extParams"/>
<input type="hidden" id="extBrowserKey" value="zzz"/>
<input type="password" id="extUserId" value="ThisIsPassword"/>
<input type="password" id="extButton" value="extButton"/>
<input type="password" id="extListenButton" value="extListenButton"/>
