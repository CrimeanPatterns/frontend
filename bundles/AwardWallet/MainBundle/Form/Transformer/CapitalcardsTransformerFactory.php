<?php

namespace AwardWallet\MainBundle\Form\Transformer;

use AwardWallet\MainBundle\Event\AccountFormSavedEvent;
use AwardWallet\MainBundle\Repository\AccounthistoryRepository;
use AwardWallet\MainBundle\Service\CapitalcardsHelper;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\DataTransformerInterface;

class CapitalcardsTransformerFactory
{
    /**
     * @var EventDispatcherInterface
     */
    private $eventDispatcher;
    /**
     * @var LoggerInterface
     */
    private $logger;
    /**
     * @var AccounthistoryRepository
     */
    private $historyRepo;
    /**
     * @var CapitalcardsHelper
     */
    private $capitalcardsHelper;

    public function __construct(
        EventDispatcherInterface $eventDispatcher,
        LoggerInterface $logger,
        AccounthistoryRepository $historyRepo,
        CapitalcardsHelper $capitalcardsHelper
    ) {
        $this->eventDispatcher = $eventDispatcher;
        $this->logger = $logger;
        $this->historyRepo = $historyRepo;
        $this->capitalcardsHelper = $capitalcardsHelper;
    }

    public function make(): DataTransformerInterface
    {
        $oldRewardsAuthInfo = null;
        $oldTxAuthInfo = null;

        return new CallbackTransformer(
            function ($value) use (&$oldRewardsAuthInfo, &$oldTxAuthInfo) {
                $value = CapitalcardsHelper::decodeSavedAuthInfo($value);

                if (isset($value['rewards'])) {
                    $oldRewardsAuthInfo = $value['rewards'];
                }

                if (isset($value['tx'])) {
                    $oldTxAuthInfo = $value['tx'];
                }

                return json_encode($value);
            },
            function ($value) use (&$oldRewardsAuthInfo, &$oldTxAuthInfo) {
                if (empty($value)) {
                    $value = ['rewards' => null, 'tx' => null];
                } else {
                    // expecting json {"rewards": null|"encoded_tokens", "tx": null|"encoded_tokens"}
                    $value = @json_decode($value, true);

                    if (!is_array($value) || !array_key_exists('rewards', $value) || !array_key_exists('tx', $value)) {
                        throw new \InvalidArgumentException("bad input data");
                    }
                }

                if ($value['rewards'] === null) {
                    $this->logger->info("rewards access was revoked, will delete account");
                    $this->eventDispatcher->addListener(AccountFormSavedEvent::NAME, function (AccountFormSavedEvent $event) use (&$oldRewardsAuthInfo) {
                        $this->logger->info("rewards access was revoked for {$event->getAccountId()}, deleting account");

                        if ($oldRewardsAuthInfo !== null) {
                            $this->capitalcardsHelper->revokeEncodedTokens(true, $oldRewardsAuthInfo);
                        }
                    });
                }

                if ($value['tx'] === null) {
                    $this->logger->info("tx access was revoked, will clear transactions");
                    $this->eventDispatcher->addListener(AccountFormSavedEvent::NAME, function (AccountFormSavedEvent $event) use (&$oldTxAuthInfo) {
                        $this->logger->info("tx access was revoked for account {$event->getAccountId()}, clearing transactions");
                        $history = $this->historyRepo->findBy(['account' => $event->getAccountId()]);
                        $this->logger->info("deleting " . count($history) . " history rows");

                        foreach ($history as $row) {
                            $this->historyRepo->remove($row);
                        }
                        $this->historyRepo->save();

                        if ($oldTxAuthInfo !== null) {
                            $this->capitalcardsHelper->revokeEncodedTokens(false, $oldTxAuthInfo);
                        }
                    });
                }

                if (!$value['rewards'] && !$value['tx']) {
                    return null;
                }

                return CapitalcardsHelper::encodeAuthInfo($value);
            }
        );
    }
}
