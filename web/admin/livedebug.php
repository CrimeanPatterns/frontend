<?php

require __DIR__ . "/../kernel/public.php";

if (ConfigValue(CONFIG_SITE_STATE) == SITE_STATE_DEBUG) {
    try {
        $checker = new TAccountChecker();
        $checker->InitBrowser();

        $body = base64_decode($_POST['body']);
        $xpath = $_POST['xpath'];
        $regexp = $_POST['regexp'];

        $checker->http->SetBody($body);
        $result = $checker->http->FindNodes($xpath, null, $regexp);
        $result = var_export($result, true);

        echo $result;
    } catch (Exception $e) {
        echo $e->getMessage();
    }
}
