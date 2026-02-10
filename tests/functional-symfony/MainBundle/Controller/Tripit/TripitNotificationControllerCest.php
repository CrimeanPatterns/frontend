<?php

namespace AwardWallet\Tests\FunctionalSymfony\MainBundle\Controller\Tripit;

use AwardWallet\MainBundle\Entity\Repositories\TripRepository;
use AwardWallet\MainBundle\Entity\Trip;
use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\Globals\StringHandler;
use AwardWallet\MainBundle\Service\Tripit\Async\ImportReservationsExecutor;
use AwardWallet\MainBundle\Service\Tripit\Async\ImportReservationsTask;
use AwardWallet\MainBundle\Service\Tripit\TripitHttpClient;
use AwardWallet\MainBundle\Worker\AsyncProcess\Process;
use AwardWallet\MainBundle\Worker\AsyncProcess\Response;
use AwardWallet\MainBundle\Worker\AsyncProcess\Task;
use Symfony\Component\Routing\RouterInterface;

/**
 * @group frontend-functional
 */
class TripitNotificationControllerCest
{
    use \Awardwallet\Tests\Modules\AutoVerifyMocksTrait;
    private $userId;
    /**
     * @var Usr
     */
    private $user;
    /**
     * @var RouterInterface
     */
    private $router;
    /**
     * @var TripRepository
     */
    private $tripRepository;
    /**
     * @var ImportReservationsTask
     */
    private $task;

    private ?string $accessToken = null;

    private ?string $accessSecret = null;

    public function _before(\TestSymfonyGuy $I)
    {
        $this->userId = $I->createAwUser(null, null, [], true);
        $this->user = $I->grabService('doctrine')->getRepository(Usr::class)->find($this->userId);
        $this->router = $I->grabService('router');
        $this->tripRepository = $I->grabService('doctrine')->getRepository(Trip::class);
        $this->accessToken = StringHandler::getRandomCode(20);
        $this->accessSecret = StringHandler::getRandomCode(20);

        $this->user->setTripitOauthToken([
            'oauth_request_token' => null,
            'oauth_request_secret' => null,
            'oauth_access_token' => $this->accessToken,
            'oauth_access_secret' => $this->accessSecret,
        ]);

        $entityManager = $I->grabService('doctrine')->getManager();
        $entityManager->flush();

        $I->mockService(Process::class, $I->stubMakeEmpty(Process::class, [
            'execute' => function (Task $task) {
                if ($task instanceof ImportReservationsTask) {
                    $this->task = $task;
                }

                return new Response(Response::STATUS_READY);
            },
        ]));
    }

    public function _after(\TestSymfonyGuy $I)
    {
        unset($this->userId, $this->user, $this->tripRepository, $this->task);
    }

    /**
     * Проверяет получение нового события из API (создание новой резервации).
     */
    public function checkNotificationCreated(\TestSymfonyGuy $I)
    {
        $I->mockService(TripitHttpClient::class, $I->stubMakeEmpty(TripitHttpClient::class, [
            'request' => function ($user, $verb, $entity, $queryParams) {
                switch ($entity) {
                    case 'trip':
                        return self::getTrips(2);

                    case 'profile':
                        return self::getProfile();

                    default:
                        throw new \RuntimeException('Incorrect verb or entity');
                }
            },
        ]));

        /** @var Trip[] $trips */
        $trips = $this->tripRepository->findBy(['user' => $this->userId]);
        $I->assertEquals(0, count($trips));

        $I->sendPost('/tripit/notifications', http_build_query([
            'type' => 'trip',
            'id' => 20,
            'change' => 'plans_created',
            'oauth_token_key' => $this->accessToken,
        ]));
        $this->executeAsyncTask($I);
        $I->seeResponseCodeIs(200);

        $trips = $this->tripRepository->findBy(['user' => $this->userId]);
        $I->assertEquals(2, count($trips));
    }

    /**
     * Проверяет получение нового события из API (удаление существующей резервации).
     */
    public function checkNotificationDeleted(\TestSymfonyGuy $I)
    {
        $runCount = 0;
        $I->mockService(TripitHttpClient::class, $I->stubMakeEmpty(TripitHttpClient::class, [
            'request' => function ($user, $verb, $entity, $queryParams) use (&$runCount) {
                switch ($entity) {
                    case 'trip':
                        $result = $runCount > 0 ? self::getTrips(1) : self::getTrips(2);
                        $runCount++;

                        return $result;

                    case 'profile':
                        return self::getProfile();

                    default:
                        throw new \RuntimeException('Incorrect verb or entity');
                }
            },
        ]));

        $I->amOnPage($this->router->generate('aw_timeline', ['_switch_user' => $this->user->getLogin()]));
        $I->followRedirects(false);
        $I->sendGet('/tripit/import');

        /** @var Trip[] $trips */
        $trips = $this->tripRepository->findBy(['user' => $this->userId, 'hidden' => false]);
        $I->assertEquals(2, count($trips));

        $I->sendPost('/tripit/notifications', http_build_query([
            'type' => 'trip',
            'id' => 100,
            'change' => 'plans_deleted',
            'oauth_token_key' => $this->accessToken,
        ]));
        $this->executeAsyncTask($I);
        $I->seeResponseCodeIs(200);

        $trips = $this->tripRepository->findBy(['user' => $this->userId, 'hidden' => false]);
        $I->assertEquals(1, count($trips));
    }

    /**
     * Получить объекты путешествий, которые приходят от API.
     *
     * @param int $count количество возвращаемых объектов
     */
    private static function getTrips(int $count): string
    {
        $airObject = json_decode(file_get_contents(__DIR__ . '/Fixtures/tripAirObject.json'), true);
        $transportObject = json_decode(file_get_contents(__DIR__ . '/Fixtures/tripTransportObject.json'), true);

        $dateTime = new \DateTime();
        $date = $dateTime->add(new \DateInterval('P7D'))->format('Y-m-d');
        $airObject['AirObject']['Segment'][0]['StartDateTime']['date'] = $date;
        $airObject['AirObject']['Segment'][0]['EndDateTime']['date'] = $date;

        $date = $dateTime->add(new \DateInterval('P5D'))->format('Y-m-d');
        $airObject['AirObject']['Segment'][1]['StartDateTime']['date'] = $date;
        $airObject['AirObject']['Segment'][1]['EndDateTime']['date'] = $date;

        $date = $dateTime->sub(new \DateInterval('P5D'))->format('Y-m-d');
        $transportObject['TransportObject']['Segment']['StartDateTime']['date'] = $date;
        $transportObject['TransportObject']['Segment']['EndDateTime']['date'] = $date;

        return ($count === 1) ? json_encode($airObject) : json_encode(array_merge($airObject, $transportObject));
    }

    /**
     * Получить объект профиля пользователя, который приходит от API.
     */
    private static function getProfile(): string
    {
        return file_get_contents(__DIR__ . '/Fixtures/profile.json');
    }

    private function executeAsyncTask(\TestSymfonyGuy $I)
    {
        /** @var ImportReservationsExecutor $executor */
        $executor = $I->grabService(ImportReservationsExecutor::class);
        $executor->execute($this->task);
    }
}
