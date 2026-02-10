<?php
require __DIR__ . '/../../web/kernel/public.php';

echo "performing request\n";

$requestInfo = [];
$result = curlRequest('https://awardwallet.com/paypal/IPNListener.php', 30, [
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => array (
      'mc_gross' => '10.00',
      'outstanding_balance' => '0.00',
      'period_type' => ' Regular',
        /// paste real transaction here
      'transaction_subject' => '',
      'payment_gross' => '10.00',
      'shipping' => '0.00',
      'product_type' => '1',
      'time_created' => '05:16:55 Oct 21, 2015 PDT',
      'ipn_track_id' => '171fa314a37d5',
    )
    ],
    $requestInfo,
    $errNo
);

echo "response:\n" . var_export($result, true) . "\n";
echo "requestInfo:\n" . var_export($requestInfo, true) . "\n";
echo "errNo:\n" . var_export($errNo, true) . "\n";
