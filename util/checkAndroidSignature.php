<?
require '../web/kernel/public.php';

$data = '{"nonce":8279800729451486476,"orders":[{"notificationId":"3516110422803626826","orderId":"505023933393761","packageName":"com.itlogy.awardwallet","productId":"awardwallet_plus","purchaseTime":1305550560000,"purchaseState":0}]}';
$sign = 'OeOo1+Lmic7nCeOKas+bODoYbTeiw+qyPN7A7vl6tg6ljWe5FNW7q9flKcofeAKipALTXD26R9zTuVNP5DLo0NW+lVLZumO5Hxw4CI54HqXfSNN11CSKTGaNMxniDGuHLdvdcnOH4zYC8ktiTKtN3HTN6/jELssSaJD7f0jQ1WRsXF9pP6G0O+e5GvaUpnkWjo8CthDX/y3RK5KMIHXejeeOyt4skaprh0rIGGk7m3QIzcKnIt8djmxlvDZmleF7l4DvljHpipe0E5YGCqu4Ub18PPXqFKsSmo1JaxF16i1CORIemyOnpqYa9RvtIygbmw2fiB4puqCRAIIJCje8/Q==';

$privateKey = openssl_pkey_get_private("file://awPrivateRSA.key", "n9uu3fHWfTUaTW");
$publicKey = openssl_pkey_get_public("file://public2.pem");
//$publicKey = openssl_pkey_get_public('MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEAraWeGGiVyXlDMJ/hC0BR6UsnMDRclaU4EzT0Z5pY7skgh0nC7A0eGyV5GKQnLaT/wJLvyphT5ewJR9yr88mKyp6eBNWlfnadPB6LTPoZT0ViTtROIvmpuwlAFDp9eaEiL47NrJsXTocpQVvdYhle4lAvPd8gXdEJ2QtYsOw95YCcQrIicNh2ArtOhelKQ3CBslke4bToRz05AtLxsYED4oVmWlAo/5WSHkfVvN552GUQ6fo60YcjQ+z0bqsZRlEFMZRILCqDwoEjfGtcV4EmDHIb16IqAtOf1kyDiqRABal4mGKaWSEDAUHYDld+uRcUDXSrOFqYEENWv0xZhXUTNQIDAQAB'));
//openssl_private_encrypt($data, $cryptedData, $privateKey);
var_dump(openssl_verify($data, base64_decode($sign), $publicKey));
//openssl_sign($data, $calculatedSignature, $privateKey);
//echo base64_encode($calculatedSignature)."\n";
openssl_free_key($privateKey);
openssl_free_key($publicKey);

