<?php

namespace AwardWallet\MainBundle\Service;

use AwardWallet\MainBundle\Entity\Account;
use AwardWallet\MainBundle\Entity\Extensionstat;
use AwardWallet\MainBundle\Entity\Repositories\AccountRepository;
use AwardWallet\MainBundle\Service\MobileExtensionHandler\MobileExtensionHandler;
use Doctrine\DBAL\Connection;

class ExtensionStatSaver
{
    private Connection $connection;
    private AccountRepository $accountRepository;
    private MobileExtensionHandler $mobileExtensionHandler;

    public function __construct(
        Connection $connection,
        AccountRepository $accountRepository,
        MobileExtensionHandler $mobileExtensionHandler
    ) {
        $this->connection = $connection;
        $this->accountRepository = $accountRepository;
        $this->mobileExtensionHandler = $mobileExtensionHandler;
    }

    public function saveExtensionHitByAccountId(string $platform, int $accountId): void
    {
        /** @var Account $account */
        $account = $this->accountRepository->find($accountId);

        if (!$account) {
            return;
        }

        $provider = $account->getProviderid();

        if (!$provider) {
            return;
        }

        $this->saveExtensionHit($platform, $provider->getId());
    }

    public function saveExtensionHitByItineraryId(string $platform, string $itineraryId): void
    {
        $itineraryResult = $this->mobileExtensionHandler->getItineraryByItineraryId($itineraryId);

        if ($itineraryResult->isFail()) {
            return;
        }

        $itinerary = $itineraryResult->unwrap();
        $provider = $itinerary->getProvider();

        if (!$provider) {
            $account = $itinerary->getAccount();

            if ($account) {
                $provider = $account->getProviderid();
            }
        }

        if (!$provider) {
            return;
        }

        $this->saveExtensionHit($platform, $provider->getId());
    }

    public function saveExtensionHit(string $platform, int $providerId): void
    {
        $this->connection->executeStatement("
            INSERT INTO ExtensionStat (ErrorDate, ProviderID, Status, ErrorText, ErrorCode, Platform)
            VALUES (NOW(), :providerId, :status, '', 0, :platform)
            ON DUPLICATE KEY UPDATE Count = Count + 1",
            [
                'providerId' => $providerId,
                'platform' => $platform,
                'status' => Extensionstat::STATUS_TOTAL,
            ]
        );
    }
}
