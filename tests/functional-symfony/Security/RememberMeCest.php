<?php

namespace AwardWallet\Tests\FunctionalSymfony\Security;

use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\Globals\StringUtils;
use AwardWallet\MainBundle\Security\RememberMe\RememberMeServices;
use AwardWallet\MainBundle\Security\RememberMe\RememberMeTokenProvider;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Security\Guard\Token\PostAuthenticationGuardToken;

/**
 * @group frontend-functional
 */
class RememberMeCest
{
    use \Awardwallet\Tests\Modules\AutoVerifyMocksTrait;
    use LoginTrait;

    private $login;
    private $password;
    private $userId;

    public function _before(\TestSymfonyGuy $I)
    {
        $this->login = 'test' . $I->grabRandomString();
        $this->password = StringUtils::getRandomCode(10);
        $this->userId = $I->createAwUser($this->login, $this->password);
    }

    public function testNoRememberMe(\TestSymfonyGuy $I)
    {
        $I->assertTrue($I->login($this->login, $this->password));
        $I->assertTrue($I->isLoggedIn());
        $I->resetCookie("MOCKSESSID");
        $I->assertFalse($I->isLoggedIn());
    }

    public function testRememberMe(\TestSymfonyGuy $I)
    {
        $I->assertTrue($I->login($this->login, $this->password, true));
        $I->assertStringContainsString("PwdHash", $I->grabHttpHeaderValue("Set-Cookie"));
        $I->assertNotEmpty($I->grabCookie("PwdHash"));
        $I->assertTrue($I->isLoggedIn());
        $rememberTokenId = $I->grabFromDatabase('Session', 'RememberMeTokenID', ['UserID' => $this->userId]);
        $I->assertNotEmpty($rememberTokenId);

        $I->resetCookie("MOCKSESSID");
        $I->assertTrue($I->isLoggedIn());
        $tokenId2 = $I->grabFromDatabase('Session', 'RememberMeTokenID', ['UserID' => $this->userId]);
        $I->assertEquals($rememberTokenId, $tokenId2);

        $I->amOnPage("/contact");
        $lastName = $I->grabFromDatabase("Usr", "LastName", ["UserID" => $this->userId]);
        $I->see($lastName);

        $brId = $I->createAbRequest(); // belongs to SiteAdmin
        $I->amOnPage("/awardBooking/view/$brId");
        $I->seeResponseCodeIs(403);

        $I->resetCookie("MOCKSESSID");
        $I->amOnPage("/contact");
        $I->see($lastName);

        $I->wantToTest("403 with RememberMeToken");
        $I->amOnPage("/awardBooking/view/$brId");
        $I->seeResponseCodeIs(403);

        $I->resetCookie("PwdHash");
        $I->resetCookie("MOCKSESSID");
        $I->amOnPage("/contact");
        $I->dontSee($lastName);
    }

    public function testBusinessRememberMe(\TestSymfonyGuy $I)
    {
        $businessUserId = $I->createAwUser(null, null, ['AccountLevel' => ACCOUNT_LEVEL_BUSINESS]);
        $I->connectUserWithBusiness($this->userId, $businessUserId, ACCESS_ADMIN);
        $business = $I->query("select * from Usr where UserID = :userId", ["userId" => $businessUserId])->fetch(\PDO::FETCH_ASSOC);

        $I->amOnBusiness();
        $this->loginUser(['login' => $this->login, 'password' => $this->password], $I, null, true);
        $I->seeResponseContainsJson(["success" => true]);

        $I->resetCookie("MOCKSESSID");

        $I->amOnPage("/contact");
        $I->see("Business Account");
        $I->assertEquals($business['Login'], $_SESSION['UserFields']['Login']);

        $I->resetCookie("PwdHash");
        $I->resetCookie("MOCKSESSID");
        $I->amOnPage("/contact");
        $I->dontSee("Business Account");
    }

    public function testCreateValidHash(\TestSymfonyGuy $I)
    {
        /** @var KernelInterface $kernel */
        $kernel = $I->grabService("kernel");
        $container = $kernel->getContainer();
        $hash = $this->getPwdHash($I, $container->getParameter("security.rememberme_key"));

        $I->setCookie("PwdHash", $hash);
        $I->assertTrue($I->isLoggedIn());
        $I->assertStringContainsString("PwdHash", $I->grabHttpHeaderValue("Set-Cookie"));
    }

    public function testCreateInvalidHash(\TestSymfonyGuy $I)
    {
        $hash = $this->getPwdHash($I, "somekey");
        $I->setCookie("PwdHash", $hash);
        $I->assertFalse($I->isLoggedIn());
        $I->assertTrue((bool) preg_match('/PwdHash=(deleted)?;/', $I->grabHttpHeaderValue("Set-Cookie")));
    }

    public function testCreateInvalidCookie(\TestSymfonyGuy $I)
    {
        $I->setCookie("PwdHash", \base64_encode(Usr::class . ':some:some'));
        $I->assertFalse($I->isLoggedIn());
        $I->assertTrue((bool) preg_match('/PwdHash=(deleted)?;/', $I->grabHttpHeaderValue("Set-Cookie")));
    }

    public function testOldKey(\TestSymfonyGuy $I)
    {
        /** @var KernelInterface $kernel */
        $kernel = $I->grabService("kernel");
        $container = $kernel->getContainer();
        $hash = $this->getPwdHash($I, $container->getParameter("security.rememberme_key_old"));
        $I->setCookie("PwdHash", $hash);
        $I->assertTrue($I->isLoggedIn());
        $I->assertStringContainsString("PwdHash", $I->grabHttpHeaderValue("Set-Cookie"));
        $I->assertNotEquals($hash, $I->grabCookie("PwdHash"));
    }

    private function getPwdHash(\TestSymfonyGuy $I, $key)
    {
        /** @var KernelInterface $kernel */
        $kernel = $I->grabService("kernel");
        $container = $kernel->getContainer();

        $services = new RememberMeServices(
            [$container->get("aw.security.user_provider")],
            $key,
            "secured_area",
            [
                "name" => $container->getParameter("security.rememberme_name"),
                "always_remember_me" => false,
                "remember_me_parameter" => $container->getParameter("security.rememberme_parameter"),
                "lifetime" => $container->getParameter("security.rememberme_lifetime"),
                "path" => "/",
                "domain" => null,
                "httponly" => true,
            ],
            $container->get(LoggerInterface::class)
        );
        $services->setTokenProvider($container->get(RememberMeTokenProvider::class));

        $request = new Request([], [], [], [], [], ['REMOTE_ADDR' => '127.0.0.1']);
        $response = new Response();
        $user = $container->get('doctrine')->getRepository(Usr::class)->findOneBy(["login" => $this->login]);
        $token = new PostAuthenticationGuardToken($user, 'test', $user->getRoles());
        $services->onLoginSuccess($request, $response, $token);
        $cookies = $response->headers->getCookies();
        /** @var Cookie[] $cookies */
        $cookies = array_filter($cookies, function (Cookie $cookie) { return $cookie->getName() == 'PwdHash'; });
        $hash = $cookies[0]->getValue();

        return $hash;
    }
}
