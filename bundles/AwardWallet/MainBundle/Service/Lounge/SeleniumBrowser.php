<?php

namespace AwardWallet\MainBundle\Service\Lounge;

use AwardWallet\Common\Selenium\SeleniumDriverFactory;
use Facebook\WebDriver\Exception\StaleElementReferenceException;
use Monolog\Logger;

class SeleniumBrowser
{
    private Logger $logger;

    private Proxy $proxy;

    private SeleniumDriverFactory $seleniumDriverFactory;

    private \SeleniumFinderRequest $seleniumFinderRequest;

    private \SeleniumOptions $seleniumOptions;

    private int $startTimeoutSec = 60;

    private bool $autoProxy = true;

    private ?\SeleniumDriver $driver;

    private bool $sessionStarted = false;

    private ?\HttpDriverResponse $response = null;

    public function __construct(Logger $logger, Proxy $proxy, SeleniumDriverFactory $seleniumDriverFactory)
    {
        $this->logger = $logger;
        $this->proxy = $proxy;
        $this->seleniumDriverFactory = $seleniumDriverFactory;
        $this->seleniumFinderRequest = new \SeleniumFinderRequest();
        $this->seleniumOptions = new \SeleniumOptions();
        $this->driver = null;
    }

    public function resetProxyList(): self
    {
        $this->proxy->reset();

        return $this;
    }

    public function setStartTimeoutSec(int $startTimeoutSec): self
    {
        $this->startTimeoutSec = $startTimeoutSec;

        return $this;
    }

    public function setAutoProxy(bool $autoProxy): self
    {
        $this->autoProxy = $autoProxy;

        return $this;
    }

    public function useChromium(?int $version = null): self
    {
        if (empty($version)) {
            $version = (int) \SeleniumFinderRequest::CHROMIUM_DEFAULT;
        }

        $this->log("Chromium v.{$version}");
        $this->seleniumFinderRequest->request(\SeleniumFinderRequest::BROWSER_CHROMIUM, $version);

        return $this;
    }

    public function useGoogleChrome(?int $version = null): self
    {
        if (empty($version)) {
            $version = (int) \SeleniumFinderRequest::CHROME_DEFAULT;
        }
        $this->log("Google Chrome v.{$version}");
        $this->seleniumFinderRequest->request(\SeleniumFinderRequest::BROWSER_CHROME, $version);

        return $this;
    }

    public function useFirefox(?int $version = null): self
    {
        if (empty($version)) {
            $version = (int) \SeleniumFinderRequest::FIREFOX_DEFAULT;
        }
        $this->log("Firefox v.{$version}");
        $this->seleniumFinderRequest->request(\SeleniumFinderRequest::BROWSER_FIREFOX, $version);

        return $this;
    }

    public function useFirefoxPlaywright(?int $version = null): self
    {
        if (empty($version)) {
            $version = (int) \SeleniumFinderRequest::FIREFOX_PLAYWRIGHT_DEFAULT;
        }
        $this->log("Firefox Playwright v.{$version}");
        $this->seleniumFinderRequest->request(\SeleniumFinderRequest::BROWSER_FIREFOX_PLAYWRIGHT, $version);

        return $this;
    }

    public function showImages(bool $showImages): self
    {
        $this->seleniumOptions->setShowImages($showImages);

        return $this;
    }

    public function setUserAgent(string $userAgent): self
    {
        $this->seleniumOptions->setUserAgent($userAgent);

        return $this;
    }

    public function startSession(): self
    {
        $startTime = time();

        if ($this->autoProxy) {
            $proxy = $this->proxy->getProxy();
            $this->log("start driver with proxy: $proxy");
            $this->setProxy($this->proxy->getProxy());
        }

        while ((time() - $startTime) < $this->startTimeoutSec) {
            try {
                $this->log('start driver');
                $this->driver()->start();

                break;
            } catch (\ThrottledException $e) {
                $this->log($e->getMessage());
                sleep(5);
            }
        }

        $this->sessionStarted = true;

        return $this;
    }

    public function stopSession(): self
    {
        $this->driver()->stop();
        $this->sessionStarted = false;

        return $this;
    }

    public function setProxy(string $host, ?int $port = null, ?string $user = null, ?string $password = null): self
    {
        if (preg_match('/:(\d+)$/ims', $host, $matches)) {
            $port = $matches[1];
            $host = preg_replace('/:\d+$/ims', '', $host);
        }

        $this->seleniumOptions->setProxyHost($host);

        if (isset($port)) {
            $this->seleniumOptions->setProxyPort($port);
        }

        if (isset($user)) {
            $this->seleniumOptions->setProxyUser($user);
        }

        if (isset($password)) {
            $this->seleniumOptions->setProxyPassword($password);
        }

        return $this;
    }

    public function get(string $url, array $headers = []): bool
    {
        return $this->sendRequest('get', $url, null, array_merge([
            'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
        ], $headers));
    }

    public function post(string $url, $data = null, array $headers = []): bool
    {
        return $this->sendRequest('post', $url, $data, array_merge([
            'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
            'Content-Type' => 'application/x-www-form-urlencoded; charset=UTF-8',
        ], $headers));
    }

    public function getResponse(): ?string
    {
        if (!$this->sessionStarted) {
            throw new \RuntimeException('Session not started');
        }

        return $this->response->body;
    }

    public function keepSession(bool $keepSession): self
    {
        $this->log('keep session');
        $this->driver()->setKeepSession($keepSession);

        return $this;
    }

    public function keepCookies(bool $keepCookies): self
    {
        $this->log('keep cookies');
        $this->driver()->setKeepCookies($keepCookies);

        return $this;
    }

    public function webDriver()
    {
        return $this->driver()->webDriver;
    }

    public function waitFor(callable $whileCallback, int $timeoutSeconds = 60): bool
    {
        $start = time();

        do {
            try {
                if (call_user_func($whileCallback)) {
                    return true;
                }
            } catch (\Exception $e) {
                $this->reconnectFirefox($e);
            }
            sleep(1);
        } while ((time() - $start) < $timeoutSeconds);

        return false;
    }

    public function waitAjax(): bool
    {
        sleep(1);

        return $this->waitFor(
            function () {
                return $this->webDriver()->executeScript('return jQuery.active') == 0;
            }
        );
    }

    public function waitForElement(\WebDriverBy $by, int $timeout = 60, bool $visible = true)
    {
        /** @var \RemoteWebElement $element */
        $element = null;
        $start = time();

        $this->waitFor(
            function () use ($by, &$element, $visible) {
                try {
                    $elements = $this->webDriver()->findElements($by);
                } catch (StaleElementReferenceException|StaleElementReferenceException $e) {
                    sleep(1);
                    $elements = $this->webDriver()->findElements($by);
                }

                foreach ($elements as $element) {
                    if ($visible && !$element->isDisplayed()) {
                        $element = null;
                    }

                    return !empty($element);
                }

                return false;
            },
            $timeout
        );

        $timeoutSeconds = time() - $start;

        if (!empty($element)) {
            $this->log(
                sprintf('found element %s, displayed: %d, text: \'%s\', spent time: %d', $by->getValue(), $element->isDisplayed(), $element->getText(), $timeoutSeconds)
            );
        } else {
            $this->log(
                sprintf('element %s not found, spent time: %s', $by->getValue(), $timeoutSeconds)
            );
        }

        return $element;
    }

    public function getXMLHttpResponse(string $storageKey = 'responseData')
    {
        return $this->webDriver()->executeScript("return localStorage.getItem('$storageKey');");
    }

    public function listenXMLHttpResponse(?string $filterString, ?string $filterCallback = null, string $storageKey = 'responseData')
    {
        if (!$filterString && !$filterCallback) {
            throw new \InvalidArgumentException('No filter specified');
        }

        if ($filterString) {
            $filterCallback = sprintf('/%s/g.exec(this.responseText)', $filterString);
        }

        $this->webDriver()->executeScript("
            let originalOpen = window.XMLHttpRequest.prototype.open;
            
            window.XMLHttpRequest.prototype.open = function(method, url, async, user, password) {
                this.addEventListener('load', function () {
                    if ($filterCallback) {
                        alert('WOW!');
                        localStorage.setItem('$storageKey', this.responseText);
                    }
                });
                           
                return originalOpen.apply(this, arguments);
            };
        ");
    }

    public function clickCloudFlareCheckboxByMouse(
        string $captchaElemXpath = '//*[contains(text(), "Verify you are human")]/following-sibling::div[1]',
        int $xOffset = 20,
        int $yOffset = 40,
        int $timeout = 0
    ): bool {
        $webDriver = $this->webDriver();
        $mover = new \MouseMover($webDriver);
        $mouse = $webDriver->getMouse();
        $mover->enableCursor();

        $captchaElem = $this->waitForElement(\WebDriverBy::xpath($captchaElemXpath), $timeout);

        if (!$captchaElem) {
            return false;
        }

        $mouse->mouseMove($captchaElem->getCoordinates());

        if ($webDriver instanceof \RemoteWebDriver) {
            // unsupported in new versions of webdriver
            $captchaCoords = $captchaElem->getCoordinates()->inViewPort();
        } else {
            $captchaCoords = $captchaElem->getLocation();
        }

        $x = intval($captchaCoords->getX() + $xOffset);
        $y = intval($captchaCoords->getY() + $yOffset);
        $mover->moveToCoordinates(['x' => $x, 'y' => $y], ['x' => 0, 'y' => 0]);
        $mouse->click();

        return true;
    }

    private function driver(): \SeleniumDriver
    {
        if (!isset($this->driver)) {
            $this->driver = $this->seleniumDriverFactory->getDriver($this->seleniumFinderRequest, $this->seleniumOptions, $this->logger);
        }

        return $this->driver;
    }

    private function reconnectFirefox(\Throwable $e): bool
    {
        if (stripos($e->getMessage(), "can't access dead object") !== false) {
            // https://stackoverflow.com/questions/44005034/cant-access-dead-object-in-geckodriver
            $this->log('firefox bug, reconnecting');
            $this->webDriver()->switchTo()->defaultContent();

            return true;
        }

        return false;
    }

    private function sendRequest(string $method, string $url, $data, array $headers): bool
    {
        if (!$this->sessionStarted) {
            $this->startSession();
        }

        $context = [
            'method' => strtoupper($method),
            'url' => $url,
        ];
        $this->logger->info('send request', $context);

        $this->response = $this->driver()->request(
            new \HttpDriverRequest(
                $url,
                mb_strtoupper($method),
                $data,
                $headers
            )
        );

        $httpCode = $this->response->httpCode;
        $context['httpCode'] = $httpCode;

        if ($this->needChangeProxy()) {
            $this->logger->info('try change proxy', $context);

            return false;
        }

        if (empty($this->response->body)) {
            throw new HttpException('Empty response', $context);
        }

        if ($httpCode >= 400) {
            throw new HttpException('Http error', $context);
        }

        if ($httpCode != 200) {
            $this->logger->error('http error', $context);

            return false;
        }

        return true;
    }

    private function needChangeProxy(): bool
    {
        return $this->response && in_array($this->response->httpCode, [403, 503, 0]);
    }

    private function log(string $msg, int $level = Logger::INFO)
    {
        $this->logger->log($level, "[selenium] $msg");
    }
}
