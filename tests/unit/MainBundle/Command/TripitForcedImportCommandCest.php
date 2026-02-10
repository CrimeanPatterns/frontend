<?php

namespace AwardWallet\Tests\Unit\MainBundle\Command;

use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\Globals\StringHandler;
use AwardWallet\MainBundle\Service\Tripit\TripitHelper;
use AwardWallet\MainBundle\Service\Tripit\TripitImportResult;
use AwardWallet\MainBundle\Service\Tripit\TripitUser;
use Codeception\Util\Stub;
use Doctrine\ORM\EntityManagerInterface;
use Monolog\Logger;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * @group frontend-unit
 */
class TripitForcedImportCommandCest
{
    use \Awardwallet\Tests\Modules\AutoVerifyMocksTrait;

    private ?int $userId;

    /**
     * @var Application
     */
    private $app;
    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    public function _before(\TestSymfonyGuy $I)
    {
        $this->app = new Application($I->grabService('kernel'));
        $date = (new \DateTime())->sub(new \DateInterval('P2W'));
        $this->userId = $I->createAwUser(null, null, [
            'TripitOauthToken' => json_encode([
                'oauth_access_token' => StringHandler::getRandomCode(32),
                'oauth_access_secret' => StringHandler::getRandomCode(32),
                'oauth_request_token' => null,
                'oauth_request_secret' => null,
            ]),
            'TripitLastSync' => $date->format('Y-m-d H:i:s'),
        ]);
        $this->entityManager = $I->grabService('doctrine')->getManager();
    }

    public function _after()
    {
        $this->app = null;
        $this->userId = null;
    }

    public function checkForcedImport(\TestSymfonyGuy $I)
    {
        $I->mockService('logger', $I->stubMakeEmpty(Logger::class, [
            'notice' => Stub::once(function ($message, $context = []) use ($I) {
                $I->assertEquals('TripIt forced import: new reservations have been added', $message);
            }),
        ]));
        $I->mockService(TripitHelper::class, $I->stubMake(TripitHelper::class, [
            'list' => function (TripitUser $user) {
                $result = new TripitImportResult(true, ['T.10570000']);

                if ($user->getCurrentUser()->getId() === $this->userId) {
                    $result->setCountAdded(1)->setCountUpdated(0);
                }

                return $result;
            },
        ]));

        $command = $this->app->find('aw:tripit:run-import');
        $commandTester = new CommandTester($command);
        $commandTester->execute(['command' => $command->getName(), '--weeks' => 1]);

        $user = $this->entityManager->getRepository(Usr::class)->find($this->userId);
        $difference = (new \DateTime())->diff($user->getTripitLastSync());

        $I->assertEquals(0, $difference->format('%a')); // Total number of days
    }
}
