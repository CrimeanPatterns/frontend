<?php

namespace AwardWallet\MainBundle\Service\Tripit;

use AwardWallet\MainBundle\Entity\Itinerary;
use AwardWallet\MainBundle\Entity\Owner;
use AwardWallet\MainBundle\Entity\Repositories\ItineraryRepositoryInterface;
use AwardWallet\MainBundle\Entity\Repositories\TripRepository;
use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\Loyalty\AccountSaving\ProcessingReport;
use AwardWallet\MainBundle\Loyalty\AccountSaving\Processors\ItinerariesProcessor;
use AwardWallet\MainBundle\Loyalty\AccountSaving\SavingOptions;
use AwardWallet\MainBundle\Loyalty\AccountSaving\Sources\Tripit;
use AwardWallet\MainBundle\Service\Tripit\Serializer\ProfileEmailAddressObject;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;

use function AwardWallet\MainBundle\Globals\Utils\iterFluent\it;

class TripitHelper
{
    /**
     * Полный url для OAuth-авторизации.
     */
    public const SIGN_IN_URL = 'https://www.tripit.com/oauth/signIn';

    private TripitConverter $tripitConverter;
    private TripitHttpClient $tripitHttpClient;
    private ItinerariesProcessor $itinerariesProcessor;

    private LoggerInterface $logger;

    private EntityManagerInterface $entityManager;

    /**
     * @var ItineraryRepositoryInterface[]
     */
    private iterable $repositories;

    public function __construct(
        TripitConverter $tripitConverter,
        TripitHttpClient $tripitHttpClient,
        ItinerariesProcessor $itinerariesProcessor,
        LoggerInterface $logger,
        EntityManagerInterface $entityManager,
        iterable $repositories
    ) {
        $this->tripitConverter = $tripitConverter;
        $this->tripitHttpClient = $tripitHttpClient;
        $this->itinerariesProcessor = $itinerariesProcessor;
        $this->logger = $logger;
        $this->entityManager = $entityManager;
        $this->repositories = $repositories;
    }

    /**
     * Получает все предстоящие резервации из привязанного аккаунта.
     *
     * @param TripitUser $user класс, в котором хранятся токены пользователя
     * @param bool $checkDuplicates параметр, указывающий, что нужно только подсчитать количество дублирующихся
     * резерваций без их сохранения в БД
     */
    public function list(TripitUser $user, bool $checkDuplicates = false): TripitImportResult
    {
        $queryParams = ['past' => 'false', 'include_objects' => 'true', 'format' => 'json'];

        try {
            $response = $this->tripitHttpClient->request($user, 'list', 'trip', $queryParams);
        } catch (UnauthorizedHttpException $e) {
            return new TripitImportResult(false);
        }

        $responseArray = $this->jsonDecode($response, $user);

        if ($responseArray === null) {
            return new TripitImportResult(false);
        }

        if ($checkDuplicates) {
            return new TripitImportResult(true, $this->tripitConverter->findDuplicates($responseArray));
        }

        $this->tripitConverter->logResponse($responseArray, $user);
        $itineraries = $this->tripitConverter->convert($responseArray);

        if (!empty($itineraries)) {
            $processingReport = $this->saveItineraries($itineraries, $user);
            $ids = it(array_merge($processingReport->getAdded(), $processingReport->getUpdated()))
                ->map(fn (Itinerary $itinerary) => $itinerary->getIdString())
                ->toArray();

            return (new TripitImportResult(true, $ids))
                ->setCountAdded(count($processingReport->getAdded()))
                ->setCountUpdated(count($processingReport->getUpdated()));
        }

        return new TripitImportResult(true);
    }

    /**
     * Получает резервацию, соответствующую переданному идентификатору.
     *
     * @param TripitUser $user класс, в котором хранятся токены пользователя
     * @param int $id идентификатор резервации в личном кабинете
     */
    public function getTrip(TripitUser $user, int $id): TripitImportResult
    {
        $queryParams = ['include_objects' => 'true', 'format' => 'json', 'id' => $id];

        try {
            $response = $this->tripitHttpClient->request($user, 'get', 'trip', $queryParams);
        } catch (UnauthorizedHttpException $e) {
            return new TripitImportResult(false);
        }

        $responseArray = $this->jsonDecode($response, $user);

        if ($responseArray === null) {
            return new TripitImportResult(false);
        }

        $itineraries = $this->tripitConverter->convert($responseArray);

        if (!empty($itineraries)) {
            $processingReport = $this->saveItineraries($itineraries, $user);
            $ids = it(array_merge($processingReport->getAdded(), $processingReport->getUpdated()))
                ->map(fn (Itinerary $itinerary) => $itinerary->getIdString())
                ->toArray();

            return new TripitImportResult(true, $ids);
        }

        return new TripitImportResult(true);
    }

    /**
     * Получает временный токен запроса.
     */
    public function getRequestToken(TripitUser $user)
    {
        return $this->tripitHttpClient->request($user, 'oauth/request_token');
    }

    /**
     * Получает постоянный токен доступа.
     */
    public function getAccessToken(TripitUser $user)
    {
        return $this->tripitHttpClient->request($user, 'oauth/access_token');
    }

    /**
     * Получает информацию о профиле пользователя и возвращает объект email-адреса.
     */
    public function getProfile(TripitUser $user): ?ProfileEmailAddressObject
    {
        $response = $this->tripitHttpClient->request($user, 'get', 'profile', ['format' => 'json']);

        return $this->tripitConverter->convertProfile(json_decode($response, true));
    }

    /**
     * Подписаться на события уведомлений для текущего пользователя.
     */
    public function subscribe(TripitUser $user)
    {
        return $this->tripitHttpClient->request($user, 'subscribe', null, ['type' => 'trip']);
    }

    /**
     * Отказаться от подписки на события уведомлений для текущего пользователя.
     */
    public function unsubscribe(TripitUser $user)
    {
        return $this->tripitHttpClient->request($user, 'unsubscribe', null, ['type' => 'trip']);
    }

    /**
     * Decodes the given JSON string received from the API.
     *
     * @param string $json the JSON string to be decoded
     * @param TripitUser $user current user
     * @return array|null returns an associative array or 'null' if there was an error
     */
    private function jsonDecode($json, TripitUser $user)
    {
        if ($json === null || $json === '') {
            $this->logger->warning('TripIt API request: empty response');

            return null;
        }

        $array = json_decode($json, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->logger->warning('TripIt API request: JSON decoding error - ' . json_encode([
                'userId' => $user->getCurrentUser()->getId(),
                'response' => $json,
            ]));

            return null;
        }

        return $array;
    }

    /**
     * Сохраняет в БД переданные резервации.
     *
     * @param array $data массив объектов `Itinerary`
     * @param TripitUser $user класс, в котором хранятся токены пользователя
     */
    private function saveItineraries(array $data, TripitUser $user): ProcessingReport
    {
        $profile = $this->getProfile($user);
        $owner = new Owner($user->getCurrentUser());
        $options = SavingOptions::savingByTripit($owner, $profile->getAddress() ?? '');
        $report = $this->itinerariesProcessor->save($data, $options);

        if (count($report->getAdded()) > 0 || count($report->getUpdated()) > 0) {
            /** @var Itinerary[] $obsoleteItineraries */
            $obsoleteItineraries = array_udiff(
                $this->getItineraries($user->getCurrentUser()),
                $report->getAdded(),
                $report->getUpdated(),
                function (Itinerary $itineraryA, Itinerary $itineraryB) {
                    return $itineraryA->getId() <=> $itineraryB->getId();
                }
            );
            $updated = false;

            foreach ($obsoleteItineraries as $obsoleteItinerary) {
                if ($obsoleteItinerary->isUndeleted()) {
                    continue;
                }

                $this->logger->info(sprintf(
                    'itinerary %s is obsolete, hiding it',
                    $obsoleteItinerary->getIdString()
                ));
                $obsoleteItinerary->setHidden(true);
                $updated = true;
            }

            if ($updated) {
                $this->entityManager->flush();
            }
        }

        return $report;
    }

    /**
     * @return Itinerary[]
     */
    private function getItineraries(Usr $user): array
    {
        $itineraries = [];

        foreach ($this->repositories as $repository) {
            /** @var QueryBuilder $qb */
            $qb = $repository->createQueryBuilder('t');

            if ($repository instanceof TripRepository) {
                $qb->join('t.segments', 'tripSegments');
            }

            $criteria = $repository->getFutureCriteria();
            $criteria->andWhere(Criteria::expr()->eq('t.user', $user));
            $qb->addCriteria($criteria);

            if ($repository instanceof TripRepository) {
                $qb->andWhere("JSON_EXTRACT(tripSegments.sources, '$.data.tripit.type') = :tripitType");
            } else {
                $qb->andWhere("JSON_EXTRACT(t.sources, '$.data.tripit.type') = :tripitType");
            }

            $qb->setParameter('tripitType', Tripit::SOURCE_ID);
            $its = $qb->getQuery()->getResult();
            $itineraries = array_merge($itineraries, $its);
        }

        return $itineraries;
    }
}
