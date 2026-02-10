<?php

namespace AwardWallet\Tests\FunctionalSymfony\MainBundle\Controller;

use AwardWallet\Tests\FunctionalSymfony\BaseTraitCest;
use AwardWallet\Tests\FunctionalSymfony\Traits\FreeUser;
use AwardWallet\Tests\FunctionalSymfony\Traits\JsonHeaders;
use AwardWallet\Tests\FunctionalSymfony\Traits\LoggedIn;

/**
 * @group frontend-functional
 */
class SocksControllerCest extends BaseTraitCest
{
    use JsonHeaders;
    use FreeUser;
    use LoggedIn;
    private const HOST = '127.0.0.1';

    public function lockout(\TestSymfonyGuy $I): void
    {
        $userChannel = "\$user_topic_{$this->user->getId()}";

        try {
            foreach (\range(1, 100) as $_) {
                $this->sendAuthRequest($I, [
                    'channels' => [$userChannel],
                    'client' => 'some_client',
                ]);
                $I->seeResponseContainsJson([
                    $userChannel => [
                        'sign' => 'fake',
                    ],
                ]);
            }

            $this->sendAuthRequest($I, [
                'channels' => [$userChannel],
                'client' => 'some_client',
            ]);
            $I->seeResponseContainsJson([
                $userChannel => [
                    'status' => 403,
                ],
            ]);
        } finally {
            $I->resetLockout('centrifuge_channel_auth', self::HOST);
        }
    }

    protected function sendAuthRequest(\TestSymfonyGuy $I, array $data): void
    {
        $I->sendPost(
            '/centrifuge/auth/',
            $data
        );
    }
}
