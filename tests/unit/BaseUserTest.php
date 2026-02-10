<?php

namespace AwardWallet\Tests\Unit;

use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\Globals\Localizer\LocalizeService;
use Codeception\Module\Aw;

abstract class BaseUserTest extends BaseContainerTest
{
    /**
     * @var Usr
     */
    protected $user;

    public function _before()
    {
        parent::_before();

        $userId = $this->aw->createAwUser('test' . $this->aw->grabRandomString(5), Aw::DEFAULT_PASSWORD, [], true /* staff user has access to test provider */);
        $this->afterUserCreated($userId);
        $this->loginUser($userId);
    }

    public function _after()
    {
        $this->user = null;

        parent::_after();
    }

    protected function afterUserCreated(int $userId)
    {
    }

    protected function loginUser(int $userId): void
    {
        $this->assertNotNull($this->user = $this->em->getRepository(Usr::class)->find($userId));
        $this->container->get("aw.manager.user_manager")->loadToken($this->user, false);
        $this->container->get(LocalizeService::class)->setRegionalSettings();
    }
}
