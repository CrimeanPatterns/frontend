<?php

namespace AwardWallet\Tests\FunctionalSymfony\MobileBundle\Controller;

use AwardWallet\MainBundle\Globals\Headers\MobileHeaders;
use AwardWallet\Tests\FunctionalSymfony\BaseTraitCest;
use AwardWallet\Tests\FunctionalSymfony\Traits\FreeUser;
use AwardWallet\Tests\FunctionalSymfony\Traits\JsonHeaders;
use AwardWallet\Tests\FunctionalSymfony\Traits\LoggedIn;
use Doctrine\ORM\EntityManager;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * @group mobile
 * @group frontend-functional
 */
class AgentControllerCest extends BaseTraitCest
{
    use \Awardwallet\Tests\Modules\AutoVerifyMocksTrait;
    use FreeUser;
    use LoggedIn;
    use JsonHeaders;
    /**
     * @var EntityManager
     */
    private $em;
    /**
     * @var UrlGeneratorInterface
     */
    private $router;

    public function _before(\TestSymfonyGuy $I)
    {
        parent::_before($I);

        $this->router = $I->grabService('router');
        $this->em = $I->grabService('doctrine')->getManager();
        $I->haveHttpHeader(MobileHeaders::MOBILE_VERSION, '4.9.0+b100500');
    }

    public function addExistingUser(\TestSymfonyGuy $I)
    {
        $I->createAwUser(null, null, [
            'Email' => $email = $I->grabRandomString(10) . 'test@yandex.ru',
        ]);
        $I->saveCsrfToken();
        $I->sendPOST($this->router->generate('awm_create_connection'), [
            'email' => $email,
        ]);
        $I->seeResponseContainsJson(['success' => true]);
        $I->seeEmailTo($email, 'Connection invite from');
    }

    public function connectNonAwUser(\TestSymfonyGuy $I)
    {
        $email = $I->grabRandomString(10) . 'test@yandex.ru';
        $I->saveCsrfToken();
        $I->sendPOST($this->router->generate('awm_create_connection'), [
            'email' => $email,
        ]);
        $I->seeResponseContainsJson(['success' => true]);
        $I->seeEmailTo($email, 'wants to connect with you');
    }
}
