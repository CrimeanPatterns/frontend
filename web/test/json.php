<?
require __DIR__.'/../kernel/public.php';

echo json_encode(SQLToArray("select Code, DisplayName, Name from Provider
where State >= ".PROVIDER_ENABLED, "Code", "DisplayName", true));