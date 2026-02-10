<?php
require __DIR__ . '/../../web/kernel/public.php';

echo curlRequest('https://awardwallet.com/paypal/IPNListener.php', 10, [
    CURLOPT_FAILONERROR => false,
    CURLOPT_POSTFIELDS => json_decode('
    {
      "mc_gross": "30.00",
...
    }
    ')]);
