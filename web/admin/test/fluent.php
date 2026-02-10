<?
require __DIR__ . '/../../kernel/public.php';

use Fluent\Logger\FluentLogger;

Fluent\Autoloader::register();
$logger = new FluentLogger("unix:///var/run/td-agent/td-agent.sock");

$logger->post("php", array("facility"=>"php_test","message"=>"bla bla", "to"=>"userB", "context" => ["a" =>"b", "c" => ["d" => "e", "f" => "g"]]));

echo "OK";