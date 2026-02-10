<?php

namespace AwardWallet\MainBundle\Loyalty\HistoryState;

use AwardWallet\MainBundle\Globals\LoggerContext\ContextAwareLoggerWrapper;
use AwardWallet\Strings\Strings;
use JMS\Serializer\SerializerInterface;
use Psr\Log\LoggerInterface;

use function AwardWallet\MainBundle\Globals\Utils\iterFluent\it;

/**
 * this class will mix parse loyalty server history state to correct last dates
 * because we could parse history through extension.
 */
class HistoryStateBuilder
{
    private HistoryDateFinder $historyDateFinder;
    private string $historyStateKey;

    private LoggerInterface $logger;

    private SerializerInterface $serializer;

    public function __construct(HistoryDateFinder $historyDateFinder, string $historyStateKey, LoggerInterface $logger, SerializerInterface $serializer)
    {
        $this->historyDateFinder = $historyDateFinder;
        $this->historyStateKey = $historyStateKey;
        $this->logger = new ContextAwareLoggerWrapper($logger);
        $this->serializer = $serializer;
    }

    public function buildHistoryState(?string $historyState, int $providerCacheVersion, int $accountId): ?string
    {
        $this->logger->pushContext(["accountId" => $accountId]);

        try {
            $dates = $this->historyDateFinder->getHistoryDates($accountId);
            $serverState = null;

            if ($historyState !== null && $historyState !== '') {
                $serverState = $this->decryptHistoryState($historyState);

                if ($serverState === null) {
                    $this->logger->warning("failed to decrypt history state: " . Strings::cutInMiddle($historyState,
                        6));
                }
            }

            if ($serverState === null) {
                $this->logger->info("no server state, will create a new one");
                $serverState = new StructureVersion1();
                $serverState->setCacheVersion($providerCacheVersion);
            }

            if (array_key_exists("", $dates)) {
                $this->logger->info("set last history date as {$dates[""]}");
                $serverState->setLastDate(new \DateTime($dates[null]));
            }

            $subAccountDates = it($dates)
                ->filterIndexed(fn (string $date, string $subAccountCode) => $subAccountCode !== "")
                ->map(fn (string $date) => new \DateTime($date))
                ->toArrayWithKeys();

            $subAccountDates = array_merge($serverState->getSubAccountLastDates() ?? [], $subAccountDates);

            if (count($subAccountDates) > 0) {
                $this->logger->info("set subaccount history dates: " . json_encode($subAccountDates));
                $serverState->setSubAccountLastDates($subAccountDates);
            }

            if ($serverState->getLastDate() === null && count($serverState->getSubAccountLastDates()) === 0) {
                $this->logger->info("no history state to send");

                return null;
            }

            return base64_encode(AESEncode($this->serializer->serialize($serverState, 'json'), $this->historyStateKey));
        } finally {
            $this->logger->popContext();
        }
    }

    private function decryptHistoryState(string $historyState): ?StructureVersion1
    {
        $json = AESDecode(base64_decode($historyState), $this->historyStateKey);

        if ($json === null) {
            return null;
        }

        $this->logger->info("server history state: " . $json);

        try {
            return $this->serializer->deserialize($json, HistoryState::class, 'json');
        } catch (\JMS\Serializer\Exception\Exception $exception) {
            $this->logger->warning("failed to unserialize history state: " . Strings::cutInMiddle($json, 300));

            return null;
        }
    }
}
