<?php

namespace Codeception\Module;

// here you can define custom functions for TestGuy

use AwardWallet\MainBundle\Globals\Headers\MobileHeaders;
use Codeception\Actor;
use Codeception\Module;
use Codeception\Scenario;
use Codeception\Specify;
use Codeception\TestCase;
use PHPUnit\Framework\AssertionFailedError;
use Symfony\Component\BrowserKit\Cookie;

class TestHelper extends Module
{
    public function setCookie($name, $val, $path = '/', $domain = null)
    {
        $client = $this->getClient();
        $cookies = $client->getCookieJar();
        $cookies->set(new Cookie($name, $val, null, $path, $this->getHost() ?? $domain));
        $this->debugSection('Cookies', $client->getCookieJar()->all());
    }

    public function resetCookies()
    {
        $client = $this->getClient();
        $cookies = $client->getCookieJar();
        $cookies->clear();
        $this->debugSection('Cookies', "reset");
    }

    public function grabCookie($name, $path = '/', $domain = null)
    {
        $client = $this->getClient();
        $this->debugSection('Cookies', $client->getCookieJar()->all());
        $cookies = $client->getCookieJar()->get($name, $path, $this->getHost() ?? $domain);
        $this->assertNotNull($cookies);

        return $cookies->getValue();
    }

    public function seeCookie($name, $path = '/', $domain = null)
    {
        $client = $this->getClient();
        $this->debugSection('Cookies', $client->getCookieJar()->all());
        $this->assertNotNull($client->getCookieJar()->get($name, $path, $this->getHost() ?? $domain));
    }

    public function dontSeeCookie($name, $path = '/', $domain = null)
    {
        $client = $this->getClient();
        $this->debugSection('Cookies', $client->getCookieJar()->all());
        $this->assertNull($client->getCookieJar()->get($name, $path, $this->getHost() ?? $domain));
    }

    public function resetCookie($name, $path = '/', $domain = null)
    {
        $client = $this->getClient();
        $client->getCookieJar()->expire($name, $path, $domain);
        $this->debugSection('Cookies', $client->getCookieJar()->all());
    }

    public function getFullRoute(\TestSymfonyGuy $I, $hostParam, $route, $routeParams = [])
    {
        $container = $I->grabService('service_container');

        return
            $container->getParameter('requires_channel') .
            '://' .
            $container->getParameter($hostParam) .
            $container->get('router')->generate($route, $routeParams);
    }

    /**
     * @param array $list
     */
    public function grabDataFromResponseByJsonPathList(...$list)
    {
        return $this
            ->getModule('REST')
            ->grabDataFromResponseByJsonPath(implode('', $list));
    }

    /**
     * Returns data from the current JSON response using specified path
     * so that it can be used in next scenario steps.
     *
     * Example:
     *
     * ``` php
     * <?php
     * $user_id = $I->grabDataFromJsonResponse('user.user_id');
     * $I->sendPUT('/user', array('id' => $user_id, 'name' => 'davert'));
     *
     * // get all user's id
     * $user_ids = $I->grabDataFromJsonResponse('users.*.user_id')
     * ?>
     * ```
     *
     * @param string $path
     * @since 1.1.2
     * @return string
     */
    public function grabDataFromJsonResponse($path = '')
    {
        /** @var REST $module */
        $module = $this->getModule('REST');
        $data = $response = json_decode($module->response, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->fail('Response is not of JSON format or is malformed');
            $this->debugSection('Response', $module->response);
        }

        if ($path === '') {
            return $data;
        }

        // parse
        try {
            $pathSegments = self::_parseJsonPath($path);
        } catch (\RuntimeException $e) {
            $this->fail("Provided path is invalid, see position {$e->getMessage()}");
            $this->debugSection('Response', $response);
        }

        try {
            $result = self::_grabJsonPath($data, $pathSegments);
        } catch (\RuntimeException $e) {
            $this->fail('Response does not have required data');
            $this->debugSection('Response', $response);
        }

        return $result;
    }

    public function dontSeeDataInJsonResponse($path = '')
    {
        /** @var REST $module */
        $module = $this->getModule('REST');
        $data = $response = json_decode($module->response, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->fail('Response is not of JSON format or is malformed');
            $this->debugSection('Response', $module->response);
        }

        if ($path === '') {
            $this->fail('Response has required data');
            $this->debugSection('Response', $response);
        }

        try {
            $pathSegments = self::_parseJsonPath($path);
        } catch (\RuntimeException $e) {
            $this->fail("Provided path is invalid, see position {$e->getMessage()}");
            $this->debugSection('Response', $response);
        }

        $found = true;

        try {
            self::_grabJsonPath($data, $pathSegments);
        } catch (\RuntimeException $e) {
            $found = false;
            $this->debugSection('Response does not have required data', $response);
        } finally {
            if ($found) {
                $this->fail('Response has required data');
                $this->debugSection('Response', $response);
            }
        }
    }

    /**
     * Breaks path apart into segments.
     *
     * Examples:
     *      ''                  - get all content
     *      'accounts.a1234.ID' - get ID from one account, fails if there is no accounts, account or ID property
     *      '*'                 - get all content
     *      'accounts.*.ID'     - get ID's from all accounts, fails if account has no ID
     *      'accounts.*.Access' - get ID's from all accounts,
     *
     * @todo  support for optional metacharacter '?', ex.: 'accounts.*.ID?'
     * @param $path json structure path
     * @return array
     * @throws \RuntimeException
     */
    public static function _parseJsonPath($path)
    {
        $pathParts = [];
        $part = [];
        $pathChars = preg_split('//u', $path, -1, PREG_SPLIT_NO_EMPTY);

        for ($i = 0; $i < count($pathChars); $i++) {
            $char = $pathChars[$i];

            switch ($char) {
                case '*':
                    if (!empty($part)) {
                        if ('\\' === $part[count($part) - 1]) {
                            array_pop($part);
                            $part[] = $char;
                        } else {
                            throw new \RuntimeException($i);
                        }
                    } else {
                        $part = [[]];
                    }

                    break;

                case '.':
                    if (isset($pathChars[$i - 1])) {
                        if (empty($part)) {
                            if ([] !== $pathParts[count($pathParts) - 1]) {
                                throw new \RuntimeException($i);
                            }
                        } elseif ('\\' === $part[count($part) - 1]) {
                            array_pop($part);
                            $part[] = $char;
                        } else {
                            if (count($pathChars) - 1 === $i) {
                                throw new \RuntimeException($i);
                            }

                            if ([[]] === $part) {
                                $pathParts[] = [];
                            } else {
                                $pathParts[] = join('', $part);
                            }
                            $part = [];
                        }
                    } else {
                        throw new \RuntimeException($i);
                    }

                    break;

                case '?':
                    if (!isset($part[count($part) - 1])) {
                    }

                    break;

                default:
                    if ([[]] === $part) {
                        throw new \RuntimeException($i);
                    } else {
                        $part[] = $char;
                    }
            }
        }

        if ([[]] === $part) {
            if (!empty($pathParts)) {
                $pathParts[] = [];
            }
        } elseif (!empty($part)) {
            $pathParts[] = join('', $part);
        }

        return $pathParts;
    }

    /**
     * @param $data json decoded data
     * @param $segments parsed path segments
     * @return array
     * @throws \RuntimeException
     */
    public static function &_grabJsonPath($data, $segments)
    {
        if (is_null($segment = array_shift($segments))) {
            return $data;
        } elseif ([] === $segment) {
            if (!is_array($data) || empty($data)) {
                throw new \RuntimeException();
            }
            $aggregateResult = [];

            foreach ($data as $value) {
                $aggregateResult[] = self::_grabJsonPath($value, $segments);
            }
            unset($value);

            return $aggregateResult;
        } else {
            if (!is_array($data) || !array_key_exists($segment, $data)) {
                throw new \RuntimeException();
            }

            return self::_grabJsonPath($data[$segment], $segments);
        }
    }

    /**
     * \Codeception\Specify with a little(does not preserve state as original) Cest support.
     *
     * @see \Codeception\Specify::specify
     */
    public function specify($specification, ?\Closure $test = null, $params = [])
    {
        if (!$test) {
            return;
        }

        $throws = $this->_getSpecifyExpectedExceptions($params);
        $examples = $this->_getSpecifyExamples($params);

        foreach ($examples as $example) {
            $this->_specifyExecute($test, $throws, $example);
        }
    }

    /**
     * Dirty way to get scenario to use in "@before" and "_before" methods.
     *
     * @return Scenario
     */
    public function grabScenarioFrom(Actor $actor)
    {
        $property = new \ReflectionProperty($actor, 'scenario');
        $property->setAccessible(true);
        $scenario = $property->getValue($actor);
        $property->setAccessible(false);

        return $scenario;
    }

    public function saveCsrfToken($header = 'X-XSRF-TOKEN')
    {
        /** @var REST $rest */
        $rest = $this->getModule('REST');
        $token = $rest->grabHttpHeader($header);

        if ($token) {
            $rest->haveHttpHeader($header, $token);
        }

        return $token;
    }

    public function setMobileVersion(string $version, string $platform = 'android')
    {
        /** @var REST $rest */
        $rest = $this->getModule('REST');

        if (strpos($version, '+') === false) {
            $version .= '+b100500';
        }

        $rest->haveHttpHeader(MobileHeaders::MOBILE_VERSION, $version);
        $rest->haveHttpHeader(MobileHeaders::MOBILE_PLATFORM, $platform);
    }

    public function grabHttpHeaderValue($headerName)
    {
        /** @var REST $rest */
        $rest = $this->getModule('REST');

        $headers = $rest->grabHttpHeader($headerName, false);

        if ($headers) {
            return implode("; ", array_map(function ($header) { return (string) $header; }, $headers));
        }

        return null;
    }

    public function setXdebugCookie($value = null)
    {
        if (!isset($value) && isset($this->config['xDebugKey'])) {
            $value = $this->config['xDebugKey'];
        }
        $this->setCookie('XDEBUG_SESSION', $value);
    }

    public function resetXdebugCookie()
    {
        $this->resetCookie('XDEBUG_SESSION');
    }

    /**
     * overriden to allow 4th level subdomains, like business.test.awardwallet.com, and return to main domain
     * standard implementation will replace test.awardwallet.com with business.awardwallet.com.
     */
    public function amOnSubdomain($subdomain)
    {
        $businessHost = getSymfonyContainer()->getParameter("business_host");

        if ($this->hasModule('PhpBrowser')) {
            /** @var PhpBrowser $browser */
            $browser = $this->getModule('PhpBrowser');
            $url = $browser->backupConfig['url'];
            $parts = parse_url($url);

            if (!empty($subdomain) && strtolower($subdomain) == 'business' && !empty($businessHost)) {
                $host = $businessHost;
            } else {
                if (!empty($subdomain)) {
                    $host = "{$subdomain}.{$parts['host']}";
                } else {
                    $host = $parts['host'];
                }
            }

            $url = $parts['scheme'] . '://' . $host;

            if (!empty($parts['path'])) {
                $url .= $parts['path'];
            }

            if (!empty($parts['query'])) {
                $url .= '?' . $parts['query'];
            }

            if (!empty($parts['fragment'])) {
                $url .= '#' . $parts['fragment'];
            }

            $browser->_reconfigure(['url' => $url]);
        }

        if ($this->hasModule('Symfony')) {
            /** @var Symfony $symfony */
            $symfony = $this->getModule('Symfony');

            if (empty($subdomain)) {
                $symfony->client->setServerParameter('HTTP_HOST', $symfony->_getContainer()->getParameter("host"));
            } else {
                if ($subdomain == 'business') {
                    $host = $businessHost;
                } else {
                    $host = $subdomain . "." . getSymfonyContainer()->getParameter("host");
                }
                $symfony->client->setServerParameter('HTTP_HOST', $host);
            }
            // prevent taking host from history, see Symfony\Component\BrowserKit\Client::getAbsoluteUrl
            $symfony->client->getHistory()->clear();
        }
    }

    public function clearHistory()
    {
        /** @var Symfony $symfony */
        $symfony = $this->getModule('Symfony');
        $symfony->client->getHistory()->clear();
    }

    /**
     * Toggle redirections on and off.
     *
     * By default, BrowserKit will follow redirections, so to check for 30*
     * HTTP status codes and Location headers, they have to be turned off.
     *
     * @since 1.0.0
     * @param bool $followRedirects Optional. Whether to follow redirects or not.
     *                              Default is true.
     */
    public function followRedirects($followRedirects = true)
    {
        $this->getClient()->followRedirects($followRedirects);
    }

    /**
     * Check that a 301 HTTP Status is returned with the correct Location URL.
     *
     * @since 1.0.0
     * @param string $url relative or absolute URL of redirect destination
     */
    public function seeRedirectTo($url)
    {
        $this->assertEquals($url, $this->getRedirectLocation());
    }

    /**
     * Check that a 3XX HTTP Status is returned with the correct host.
     */
    public function seeRedirectToHost(string $host): void
    {
        $this->assertEquals($host, parse_url($this->getRedirectLocation(), PHP_URL_HOST));
    }

    /**
     * Check that a 3XX HTTP Status is returned with the correct path.
     */
    public function seeRedirectToPath(string $path): void
    {
        $this->assertEquals($path, parse_url($this->getRedirectLocation(), PHP_URL_PATH));
    }

    /**
     * Check that a 3XX HTTP Status is returned with the correct string in url.
     */
    public function seeRedirectContains(string $needle): void
    {
        $this->assertStringContainsString($needle, $this->getRedirectLocation());
    }

    /**
     * Check that a 3XX HTTP Status is returned with the correct string in url.
     */
    public function dontSeeRedirectContains(string $needle): void
    {
        $this->assertStringNotContainsString($needle, $this->getRedirectLocation());
    }

    public function seeInSource($text)
    {
        $response = $this->getClient()->getInternalResponse()->getContent();
        $this->assertStringContainsString($text, $response);
    }

    public function seeInHeader($headerName, $text)
    {
        $header = $this->getClient()->getInternalResponse()->getHeader($headerName) ?? '';
        $this->assertStringContainsString($text, $header);
    }

    public function dontSeeInSource($text)
    {
        $response = $this->getClient()->getInternalResponse()->getContent();
        $this->assertStringNotContainsString($text, $response);
    }

    public function _after(TestCase $test)
    {
        codecept_debug("memory: " . round(memory_get_usage(true) / 1024 / 1024) . "Mb");
    }

    protected function getHost(): ?string
    {
        if ($this->hasModule('PhpBrowser')) {
            /** @var PhpBrowser $module */
            $module = $this->getModule('PhpBrowser');

            return parse_url($module->_getConfig('url'), PHP_URL_HOST);
        } elseif ($this->hasModule('Symfony')) {
            /** @var Symfony $module */
            $module = $this->getModule('Symfony');

            return $module->_getContainer()->getParameter('host');
        }

        return null;
    }

    /**
     * @return \Symfony\Component\HttpKernel\Client|\Symfony\Component\BrowserKit\Client
     * @throws \Codeception\Exception\Module
     */
    private function getClient()
    {
        /** @var PhpBrowser $module */
        if ($this->hasModule('PhpBrowser')) {
            $module = $this->getModule('PhpBrowser');
        } else {
            $module = $this->getModule('Symfony');
        }

        return $module->client;
    }

    /**
     * @see \Codeception\Specify::getSpecifyExamples
     */
    private function _getSpecifyExamples($params)
    {
        if (isset($params['examples'])) {
            if (!is_array($params['examples'])) {
                throw new \RuntimeException("Examples should be array");
            }

            return $params['examples'];
        }

        return [[]];
    }

    /**
     * @see \Codeception\Specify::getSpecifyExpectedException
     * return array
     */
    private function _getSpecifyExpectedExceptions($params)
    {
        $result = [];

        if (isset($params['throws'])) {
            $throws = $params['throws'];

            if (!is_array($throws)) {
                $throws = [$throws];
            }

            foreach ($throws as $throwsItem) {
                if (is_object($throwsItem)) {
                    $result[] = get_class($throwsItem);
                } elseif ('fail' === $throwsItem) {
                    $result[] = 'PHPUnit_Framework_AssertionFailedError';
                }
            }
        }

        return array_unique($result);
    }

    /**
     * @see \Codeception\Specify::specifyExecute
     */
    private function _specifyExecute($test, $throws = false, $examples = [])
    {
        try {
            call_user_func_array($test, $examples);
        } catch (AssertionFailedError $e) {
            if (!in_array(get_class($e), $throws, true)) {
                throw $e;
            }
        } catch (\Exception $e) {
            if ($throws) {
                if (!in_array(get_class($e), $throws, true)) {
                    throw new AssertionFailedError("exception from " . str_replace(["\n", ' '], '', print_r($throws, true)) . " was expected, but " . get_class($e) . ' was thrown');
                }
            } else {
                throw $e;
            }
        }

        if ($throws) {
            if (isset($e)) {
                $this->assertTrue(true, 'exception handled');
            } else {
                throw new \PHPUnit_Framework_AssertionFailedError("no exception from " . str_replace(["\n", ' '], '', print_r($throws, true)) . " was thrown as expected");
            }
        }
    }

    private function getRedirectLocation(): string
    {
        // Allow relative URLs.
        $response = $this->getClient()->getInternalResponse();
        $responseCode = $response->getStatusCode();
        $this->assertGreaterThanOrEqual(300, $responseCode);
        $this->assertLessThan(400, $responseCode);

        return $response->getHeaders()['location'][0];
    }
}
