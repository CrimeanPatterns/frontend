<?
require_once __DIR__ . '/../../web/kernel/public.php';

class TestLogger extends \Psr\Log\AbstractLogger{

	public function log($level, $message, array $context = array()){
		echo $message . "\n";
	}

}

$startTime = microtime(true);
//$mouse = $driver->getMouse();
//$html = $driver->findElement(WebDriverBy::xpath('//body'));
//$coords = $html->getCoordinates();
$vdo = [];

$startTime = microtime(true);
$commands = file(__DIR__ . "/starbucks.rec");
foreach($commands as $command){
	echo $command;
	$command = explode(" ", trim($command));
	$time = intval($command[0]);
	if(isset($lastTime) && $lastTime < $time) {
		$sleep = $time - $lastTime;
		echo "sleep " . $sleep . "ms\n";
		$vdo[] = "pause $sleep";
		//usleep($sleep * 1000);
	}
	$lastTime = $time;
	switch($command[1]){
		case "mousemove":
			//$mouse->mouseMove($coords, intval($command[2]), intval($command[3]));
			$vdo[] = "move {$command[2]} " . (intval($command[3]) + 80);
			break;
		case "mousedown":
			$vdo[] = "mousedown 1";
			break;
		case "mouseup":
			$vdo[] = "mouseup 1";
			break;
		case "keypress":
			$vdo[] = "key " . chr($command[2]);
			break;
		case "click":
//			$driver->action()->click()->perform();
			//$mouse->click();
			break;
		case "keydown":
		case "keyup":
			break;
		default:
			throw new \Exception("Unknown command: " . $command[1]);
	}
}

file_put_contents(__DIR__ . "/starbucks.vdo", implode("\n", $vdo));
echo "duration: " . round(microtime(true) - $startTime, 5) . "\n";

global $capabilities;
$capabilities = DesiredCapabilities::chrome();

$options = new ChromeOptions();
$options->addArguments(array("--proxy-server=http://192.168.10.1:8085"));
$capabilities->setCapability(ChromeOptions::CAPABILITY, $options);
$driver = RemoteWebDriver::create('http://localhost:4444/wd/hub', $capabilities);
$driver->get("https://www.starbucks.com/account/signin");

sleep(2);

passthru('vncdo -w 1000 starbucks.vdo', $exitCode);
if($exitCode != 0)
	throw new \Exception("invalid exit code: " . $exitCode);

sleep(5);

var_dump($driver->manage()->getCookies());

$browser = new HttpBrowser("none", new CurlDriver());
foreach($cookies as $cookie)
	$browser->setCookie($cookie["name"], $cookie["value"], $cookie["domain"], $cookie["path"]);


//
//$input = $driver->findElement(WebDriverBy::id("login"));
//$mover = new MouseMover($driver);
//$mover->logger = new TestLogger();
//
//$mover->moveToElement($input);
//$mover->click();
//$mover->sendKeys($input, "test");
