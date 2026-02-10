<?php

namespace Codeception\Module;

// here you can define custom functions for WebGuy

use Codeception\TestCase;
use Codeception\TestInterface;
use Codeception\Util\Locator;
use Codeception\Util\Uri;
use Facebook\WebDriver\Exception\NoSuchElementException;
use Facebook\WebDriver\Exception\StaleElementReferenceException;
use Facebook\WebDriver\WebDriverExpectedCondition;

class WebHelper extends WebDriver
{
    protected static $screenshotIndex = 0;
    protected static $clearCookies = true;
    private $cookiesSetOn = [];
    private $inTest = false;
    private static $clean = false;

    public function returnToDomain()
    {
        $url = $this->backupConfig['url'];
        $this->_reconfigure(['url' => $url]);
    }

    public function setUrl($url)
    {
        $this->_reconfigure(['url' => $url]);
    }

    public function seeNumberOfElements($selector, $count)
    {
        $this->assertEquals($count, sizeof($this->match($this->webDriver, $selector)));
    }

    public function grabNumberOfElements($selector)
    {
        return sizeof($this->match($this->webDriver, $selector));
    }

    // HOOK: before test
    public function _before(TestCase $test)
    {
        $this->inTest = true;
        parent::_before($test);
    }

    public function _after(TestCase $test)
    {
        $this->config['clear_cookies'] = self::$clearCookies;
        self::$clearCookies = true;
        parent::_after($test);

        if ($this->webDriver !== null && $this->config['clear_cookies']) {
            unset($this->cookiesSetOn[parse_url($this->webDriver->getCurrentURL(), PHP_URL_HOST)]);
        }
    }

    // HOOK: on fail
    public function _failed(TestInterface $test, $fail)
    {
        $filename = codecept_log_dir(self::$screenshotIndex . "." . basename($test->getMetadata()->getFilename() . '.fail.png'));

        try {
            if (isset($this->webDriver)) {
                $this->_saveScreenshot($filename);
                $this->debug("Screenshot was saved into " . $filename);
                $filename = codecept_log_dir(self::$screenshotIndex . "." . basename($test->getMetadata()->getFilename() . '.fail.text'));
                file_put_contents($filename, htmlspecialchars_decode($this->getVisibleText()));
                $this->debug("Text was saved into " . $filename);
                $filename = codecept_log_dir(self::$screenshotIndex . "." . basename($test->getMetadata()->getFilename() . '.fail.html'));
                file_put_contents($filename, $this->webDriver->getPageSource());
                $this->debug("Html was saved into " . $filename);
            }
        } catch (\WebDriverException $e) {
            $this->debug("could not save screenshot: " . $e->getMessage());
        }

        if (!empty($this->config['keepBrowserOnError'])) {
            $this->sessions = [];

            if ($this->webDriver !== null) {
                unset($this->cookiesSetOn[parse_url($this->webDriver->getCurrentURL(), PHP_URL_HOST)]);
                $this->webDriver = null;
            }
        }
        self::$screenshotIndex++;
        self::$clearCookies = true;
    }

    public function waitAjax()
    {
        $this->waitForJS('return jQuery.active == 0;', 30);
        $this->dontSee('[ajax error');
    }

    /**
     * @param bool $clear
     */
    public function clearCookiesAfterTest($clear)
    {
        self::$clearCookies = $clear;
    }

    /**
     * overriden to deal with 'focus' event not fired, if firefox window is not active
     * https://code.google.com/p/selenium/issues/detail?id=157.
     *
     * alternative solution to try:
     * FirefoxProfile profile = new FirefoxProfile();
     * profile.setPreference("focusmanager.testmode",true);
     * WebDriver webDriver =  new FirefoxDriver(profile);
     */
    public function vovaFillField($field, $value)
    {
        $el = $this->findField($field);
        $el->click();
        $el->clear();
        $el = $this->findField($field);
        $this->webDriver->executeScript('$(arguments[0]).trigger("focus")', [$el]);
        usleep(100); // sometimes field does not get focused
        $el = $this->findField($field);
        $el->sendKeys($value);
        $this->webDriver->executeScript('$(arguments[0]).trigger("change")', [$el]);
    }

    /**
     * overriden to allow 4th level subdomains, like business.test.awardwallet.com
     * standard implementation will replace test.awardwallet.com with business.awardwallet.com.
     */
    public function amOnSubdomain($subdomain)
    {
        $url = $this->backupConfig['url'];
        $url = preg_replace("/(https?:\\/\\/)(.*?\\.)?(.*\\..{3,5})/ims", "$1$subdomain.$3", $url);
        $this->_reconfigure(['url' => $url]);
    }

    public function amOnUrl($url)
    {
        $this->setTestCookies($url);
        parent::amOnUrl($url);
    }

    public function amOnPage($page)
    {
        $this->setTestCookies(Uri::appendPath($this->config['url'], $page));
        parent::amOnPage($page);
    }

    public function getHost()
    {
        return $this->config['host'];
    }

    /**
     * Waits up to $timeout seconds for the given string to appear on the page.
     *
     * Can also be passed a selector to search in, be as specific as possible when using selectors.
     * waitForText() will only watch the first instance of the matching selector / text provided.
     * If the given text doesn't appear, a timeout exception is thrown.
     *
     * ``` php
     * <?php
     * $I->waitForText('foo', 30); // secs
     * $I->waitForText('foo', 30, '.title'); // secs
     * ?>
     * ```
     *
     * @param string $text
     * @param int $timeout seconds
     * @param null $selector
     * @throws \Exception
     */
    public function waitForText($text, $timeout = 10, $selector = null)
    {
        $message = sprintf(
            'Waited for %d secs but text %s still not found',
            $timeout,
            Locator::humanReadableString($text)
        );

        if (!$selector) {
            $this->webDriver->wait($timeout)->until(function ($driver) use ($text) {
                try {
                    $visibleText = $this->getVisibleText();
                } catch (StaleElementReferenceException $e) {
                    $visibleText = "";
                }

                return strpos($visibleText, $text) !== false;
            }, $message);

            return;
        }

        $condition = WebDriverExpectedCondition::textToBePresentInElement($this->getLocator($selector), $text);
        $this->webDriver->wait($timeout)->until($condition, $message);
    }

    /**
     * Waits up to $timeout seconds for the given element to be visible on the page.
     * If element doesn't appear, a timeout exception is thrown.
     *
     * ``` php
     * <?php
     * $I->waitForElementVisible('#agree_button', 30); // secs
     * $I->click('#agree_button');
     * ?>
     * ```
     *
     * @param int $timeout seconds
     * @throws \Exception
     */
    public function waitForElementVisible($element, $timeout = 10)
    {
        $by = $this->getLocator($element);
        $this->webDriver->wait($timeout)->until(function (\Facebook\WebDriver\WebDriver $driver) use ($by) {
            $result = false;

            try {
                $elements = $driver->findElements($by);

                foreach ($elements as $element) {
                    try {
                        if ($element->isDisplayed()) {
                            $result = true;

                            break;
                        }
                    } catch (StaleElementReferenceException $e) {
                        continue;
                    }
                }
            } catch (StaleElementReferenceException $e) {
            } catch (NoSuchElementException $e) {
            }

            return $result;
        });
    }

    private function setTestCookies($url)
    {
        $parts = parse_url($url);

        if (!empty($parts['host']) && !array_key_exists($parts['host'], $this->cookiesSetOn)) {
            $this->cookiesSetOn[$parts['host']] = true;
            $this->debug("setting cookies on {$parts['host']}");
            parent::amOnUrl("http://{$parts['host']}/admin/set-test-cookies.html");
        }
    }
}
