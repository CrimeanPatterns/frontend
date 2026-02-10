<?php

namespace AwardWallet\MainBundle\Loyalty\Listener;

use AwardWallet\MainBundle\Entity\Account;
use AwardWallet\MainBundle\Entity\Itinerary;
use AwardWallet\MainBundle\Entity\ItineraryCheckError;
use AwardWallet\MainBundle\Entity\Provider;
use AwardWallet\MainBundle\Entity\Repositories\ItineraryRepositoryInterface;
use AwardWallet\MainBundle\Entity\Repositories\TripRepository;
use AwardWallet\MainBundle\Event\AccountUpdatedEvent;
use AwardWallet\MainBundle\Globals\Updater\Engine\UpdaterEngineInterface;
use AwardWallet\Schema\Itineraries\Itinerary as SchemaItinerary;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\EntityManager;
use Psr\Log\LoggerInterface;

class ItinerariesListener
{
    private const ROW_LIMIT_PER_DAY = 50;
    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var EntityManager
     */
    private $entityManager;

    /**
     * @var ItineraryRepositoryInterface[]
     */
    private $repositories;

    public function __construct(
        LoggerInterface $logger,
        EntityManager $entityManager,
        iterable $repositories
    ) {
        $this->logger = $logger;
        $this->entityManager = $entityManager;
        $this->repositories = $repositories;
    }

    public function onAccountUpdated(AccountUpdatedEvent $event): void
    {
        $response = $event->getCheckAccountResponse();

        if (!in_array($response->getState(), [ACCOUNT_WARNING, ACCOUNT_CHECKED])
            || !$response->haveCheckedItineraries()
            || ($event->getUpdateMethod() === AccountUpdatedEvent::UPDATE_METHOD_EXTENSION && $response->getUserdata()->getSource() === UpdaterEngineInterface::SOURCE_MOBILE)
        ) {
            return;
        }
        /** @var SchemaItinerary[] $itineraries */
        $itineraries = $response->getItineraries();
        $noItineraries = $response->getNoitineraries();
        $account = $event->getAccount();
        $provider = $this->entityManager->getRepository(\AwardWallet\MainBundle\Entity\Provider::class)->find($account->getProviderid());

        if ($provider->getState() === PROVIDER_TEST) {
            return;
        }

        $futureItineraries = $this->getItineraries($account);

        if (count($futureItineraries) > 0 && (count($itineraries) == 0 || $noItineraries)
            && $this->checkLimitPerDayAndDuplicates(
                $provider->getProviderid(),
                $account->getId(),
                $response->getCheckdate(),
                ItineraryCheckError::NO_UPDATE)
        ) {
            $this->saveErrorToDatabase($provider, $account, $response->getCheckdate(), ItineraryCheckError::NO_UPDATE);

            return;
        }

        if ($noItineraries
            && !$provider->getCanchecknoitineraries()
            && $this->checkLimitPerDayAndDuplicates(
                $provider->getProviderid(),
                $account->getId(),
                $response->getCheckdate(),
                ItineraryCheckError::SHOULD_BE_NO_ITINERARIES,
                'NoItineraries = true but CanCheckNoItineraries for provider is false')
        ) {
            $this->saveErrorToDatabase($provider, $account, $response->getCheckdate(),
                ItineraryCheckError::SHOULD_BE_NO_ITINERARIES,
                'NoItineraries = true but CanCheckNoItineraries for provider is false');

            return;
        }

        if (count($itineraries) == 0
            && !$noItineraries && $provider->getCanchecknoitineraries()
            && $this->checkLimitPerDayAndDuplicates(
                $provider->getProviderid(),
                $account->getId(),
                $response->getCheckdate(),
                ItineraryCheckError::SHOULD_BE_NO_ITINERARIES)
        ) {
            $this->saveErrorToDatabase($provider, $account, $response->getCheckdate(), ItineraryCheckError::SHOULD_BE_NO_ITINERARIES);
        }
    }

    /**
     * @return Itinerary[]
     */
    private function getItineraries(Account $account): array
    {
        $itineraries = [];

        foreach ($this->repositories as $repository) {
            $qb = $repository->createQueryBuilder('t');

            if ($repository instanceof TripRepository) {
                $qb->join('t.segments', 'tripSegments');
            }
            $criteria = $repository->getFutureCriteria();
            $criteria->andWhere(Criteria::expr()->eq('t.account', $account));
            $criteria->andWhere(Criteria::expr()->eq('hidden', 0));
            $qb->addCriteria($criteria);
            $its = $qb->getQuery()->getResult();
            $itineraries = array_merge($itineraries, $its);
        }

        return $itineraries;
    }

    private function checkLimitPerDayAndDuplicates(int $providerId, int $accountId, \DateTime $date, int $errorType, ?string $errorMsg = ''): bool
    {
        $conn = $this->entityManager->getConnection();

        $dateStart = $date->format("Y-m-d");
        $dateEnd = date("Y-m-d", strtotime("+1 day", strtotime($dateStart)));
        // checking if an error of this type was recorded for this account during the day
        $cnt = $conn->executeQuery("
            SELECT COUNT(*) as cnt FROM ItineraryCheckError 
            WHERE ProviderID={$providerId} AND AccountID={$accountId} AND DetectionDate>='{$dateStart}' AND DetectionDate<'{$dateEnd}' 
            AND ErrorType={$errorType} AND ErrorMessage='{$errorMsg}'
        ")->fetch()['cnt'];

        if ($cnt > 0) {
            return false;
        }

        // check for exceeding the limit per day
        $cnt = $conn->executeQuery("
            SELECT COUNT(*) as cnt FROM ItineraryCheckError 
            WHERE ProviderID={$providerId} AND DetectionDate>='{$dateStart}' AND DetectionDate<'{$dateEnd}' 
            AND ErrorType={$errorType} AND ErrorMessage='{$errorMsg}'
        ")->fetch()['cnt'];

        return $cnt < self::ROW_LIMIT_PER_DAY;
    }

    private function saveErrorToDatabase(Provider $provider, Account $account, \DateTime $checkDate, int $errorType, ?string $errorMsg = '')
    {
        $error = new ItineraryCheckError();
        $error->setDetectiondate($checkDate)
            ->setProviderid($provider)
            ->setAccountid($account)
            ->setErrorType($errorType)
            ->setErrorMessage($errorMsg)
            ->setStatus(ItineraryCheckError::STATUS_NEW);
        $this->entityManager->persist($error);
        $this->entityManager->flush();
    }
}
