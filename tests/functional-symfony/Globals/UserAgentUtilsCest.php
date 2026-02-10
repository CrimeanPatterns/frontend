<?php

namespace AwardWallet\Tests\FunctionalSymfony\Globals;

use AwardWallet\MainBundle\Globals\UserAgentUtils;

/**
 * @group frontend-functional
 */
class UserAgentUtilsCest
{
    use \Awardwallet\Tests\Modules\AutoVerifyMocksTrait;

    public function getBrowserTest(\TestSymfonyGuy $I): void
    {
        $androidAppMobile = ['Android', 'Mobile App', true, false];
        $iOsAppMobile = ['iOS', 'Mobile App', true, false];
        $androidChrome = ['Android', 'Chrome', true, false];

        $test = [
            'Mobile App (android 4.40.11)' => $androidAppMobile,
            'Mobile App (ios 4.41.12)' => $iOsAppMobile,
            'Mobile App (browser 3.34.0)' => ['Mobile Browser', 'unknown', true, false],
            'okhttp/4.9.2' => $androidAppMobile,
            'AwardWallet/4.41.12 CFNetwork/1408.0.4 Darwin/22.5.0' => $iOsAppMobile,

            'Mozilla/5.0 (iPhone; CPU iPhone OS 16_6 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) CriOS/115.0.5790.130 Mobile/15E148 Safari/604.1' => ['iOS', 'Chrome', true, false],
            'Mozilla/5.0 (Linux; Android 12; SAMSUNG SM-G970U) AppleWebKit/537.36 (KHTML, like Gecko) SamsungBrowser/20.0 Chrome/106.0.5249.126 Mobile Safari/537.36' => ['Android', 'SamsungBrowser', true, false],
            'Mozilla/5.0 (Linux; Android 13; Pixel 6 Pro) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/112.0.0.0 Mobile Safari/537.36' => $androidChrome,
            'Mozilla/5.0 (Linux; Android 12; moto g stylus 5G) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/112.0.0.0 Mobile Safari/537.36' => $androidChrome,
            'Mozilla/5.0 (Linux; Android 13; SM-S908U) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/112.0.0.0 Mobile Safari/537.36' => $androidChrome,
            'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/111.0.0.0 Mobile Safari/537.36' => $androidChrome,

            'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/14.1 Safari/605.1.15' => ['OS X', 'Safari', false, true],
            'Mozilla/5.0 (X11; CrOS x86_64 14541.0.0) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/114.0.0.0 Safari/537.36' => ['Chrome OS', 'Chrome', false, true],
            'Mozilla/5.0 (Macintosh; Intel Mac OS X 10.14; rv:109.0) Gecko/20100101 Firefox/114.0' => ['OS X', 'Firefox', false, true],
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/115.0.0.0 Safari/537.36 Edg/115.0.1901.183' => ['Windows', 'Edge', false, true],
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36.0 (KHTML, like Gecko) Chrome/114.0.5735.134 Safari/537.36.0' => ['Windows', 'Chrome', false, true],
            'Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:109.0) Gecko/20100101 Firefox/115.0' => ['Linux', 'Firefox', false, true],
        ];

        foreach ($test as $ua => $result) {
            $browser = UserAgentUtils::getBrowser($ua);

            $I->assertEquals($result[0], $browser['platform']);
            $I->assertEquals($result[1], $browser['browser']);

            $I->assertEquals($result[2], $browser['isMobile']);
            $I->assertEquals($result[3], $browser['isDesktop']);
        }
    }
}
