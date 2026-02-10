<?

for($n = 0; $n < 10; $n++) {
	$driver = RemoteWebDriver::create('http://localhost:4444/wd/hub', $capabilities);
	$driver->get("https://yandex.ru");
}

