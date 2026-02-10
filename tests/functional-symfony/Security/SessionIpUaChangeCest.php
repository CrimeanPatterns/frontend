<?php

namespace AwardWallet\Tests\FunctionalSymfony\Security;

use AwardWallet\MainBundle\FrameworkExtension\Translator\Translator;
use Symfony\Component\Routing\Router;

/**
 * @group frontend-functional
 */
class SessionIpUaChangeCest
{
    use \Awardwallet\Tests\Modules\AutoVerifyMocksTrait;
    use LoginTrait;

    public const IP_USA_NY = '157.188.128.1';
    public const IP_USA_NY__DISTANCE = '157.188.192.2';
    public const IP_RUSSIA = '178.161.167.162';
    public const IP_CHINA_SHENYANG = '60.18.182.159';

    public const UA_FIREFOX = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:58.0) Gecko/20100101 Firefox/58.0';
    public const UA_CHROME = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/61.0.3163.100 Safari/537.36';

    /**
     * @var Router
     */
    private $router;

    /**
     * @var Translator
     */
    private $translator;

    public function _before(\TestSymfonyGuy $I)
    {
        $this->router = $I->grabService('router');
        $this->translator = $I->grabService('translator');
    }

    public function bigDistanceChange(\TestSymfonyGuy $I)
    {
        $this->createUserWithAuth($I);
        $I->haveServerParameter("REMOTE_ADDR", self::IP_CHINA_SHENYANG);
        $this->pageRefresh($I);
        $I->assertTrue($I->isLoggedIn());
    }

    public function uaChange(\TestSymfonyGuy $I)
    {
        $this->createUserWithAuth($I);
        $I->haveHttpHeader('User-Agent', self::UA_FIREFOX);
        $this->pageRefresh($I);
        $I->assertTrue($I->isLoggedIn());
    }

    public function smallDistanceChange(\TestSymfonyGuy $I)
    {
        $this->createUserWithAuth($I);
        $I->haveServerParameter("REMOTE_ADDR", self::IP_USA_NY__DISTANCE);
        $this->pageRefresh($I);
        $I->assertTrue($I->isLoggedIn());
    }

    public function smallDistanceAndUaChange(\TestSymfonyGuy $I)
    {
        $this->createUserWithAuth($I);
        $I->haveServerParameter("REMOTE_ADDR", self::IP_USA_NY__DISTANCE);
        $I->haveHttpHeader('User-Agent', self::UA_FIREFOX);
        $this->pageRefresh($I);
        $I->assertTrue($I->isLoggedIn());
    }

    public function bigDistanceAndUaChange(\TestSymfonyGuy $I)
    {
        $this->createUserWithAuth($I);
        $I->haveServerParameter("REMOTE_ADDR", self::IP_CHINA_SHENYANG);
        $I->haveHttpHeader('User-Agent', self::UA_FIREFOX);
        $this->pageRefresh($I);
        $I->assertFalse($I->isLoggedIn());
    }

    public function anonymousDistanceAndUaChange(\TestSymfonyGuy $I)
    {
        $I->haveServerParameter("REMOTE_ADDR", self::IP_USA_NY);
        $I->haveHttpHeader('User-Agent', self::UA_CHROME);
        $I->amOnPage("/contact");
        $I->see("Contact Us");
        $I->haveServerParameter("REMOTE_ADDR", self::IP_CHINA_SHENYANG);
        $I->haveHttpHeader('User-Agent', self::UA_FIREFOX);
        $I->amOnPage("/contact");
        $I->followMetaRedirect(); // logoff ?
        $I->seeCurrentUrlEquals("/contact");
        $I->see("Contact Us");
    }

    private function createUserWithAuth(\TestSymfonyGuy $I, array $fields = [])
    {
        $fields = array_merge([
            'RegistrationIP' => self::IP_USA_NY,
            'LastLogonIP' => self::IP_USA_NY,
            'LastUserAgent' => self::UA_CHROME,
        ], $fields);
        $user = $this->createUser($I, $fields, true);
        $I->seeInDatabase('Usr', $fields);

        $I->haveServerParameter("REMOTE_ADDR", self::IP_USA_NY);
        $I->haveHttpHeader('User-Agent', self::UA_CHROME);

        $this->loginUser($user, $I, $user['oneTimeCodeByApp']);
        $I->seeResponseContainsJson(['success' => true]);
        $I->seeCookie('AuthKey', '/login_check');
        $I->seeCookie('AuthKey', '/m/api/login_check');
        $I->seeInDatabase('Usr', array_merge($fields, ['UserID' => $user['userId']]));

        $this->pageRefresh($I);
        $I->assertTrue($I->isLoggedIn());
    }

    // fire SessionListener
    private function pageRefresh(\TestSymfonyGuy $I)
    {
        $I->amOnPage($this->router->generate('aw_user_connections'));
    }
}
