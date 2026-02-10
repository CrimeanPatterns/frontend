<?php

namespace AwardWallet\MainBundle\Service\ChaseEmails;

use AwardWallet\MainBundle\FrameworkExtension\Mailer\EmailLog;
use Doctrine\DBAL\Connection;
use Psr\Log\LoggerInterface;

class EmailBalancer
{
    private $counts = [];

    /**
     * @var \Doctrine\DBAL\Statement
     */
    private $query;
    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(Connection $connection, LoggerInterface $logger)
    {
        $this->query = $connection->prepare("
        select 
            count(*) 
        from 
            EmailLog 
        where 
            MessageKind = " . EmailLog::MESSAGE_KIND_CHASE . "
            and Code = ?
        ");
        $this->logger = $logger;
    }

    public function selectCardByRatios(array $cardRatios): int
    {
        $minCount = null;
        $result = null;

        $totalCount = 0;
        $totalPercent = 0;

        foreach ($cardRatios as $cardId => $expectedPercent) {
            if (!isset($this->counts[$cardId])) {
                $this->query->execute([$cardId]);
                $this->counts[$cardId] = $this->query->fetchColumn();
                $this->logger->info("loaded email count for $cardId: {$this->counts[$cardId]}");
            }
            $totalCount += $this->counts[$cardId];
            $totalPercent += $expectedPercent;
        }

        $maxDiff = null;

        if ($totalCount === 0) {
            $result = array_keys($cardRatios)[0];
        } else {
            foreach ($cardRatios as $cardId => $expectedPercent) {
                $actualPercent = ($this->counts[$cardId] / $totalCount) * $totalPercent;
                $diff = $expectedPercent - $actualPercent;

                if ($diff > $maxDiff || $maxDiff === null) {
                    $result = $cardId;
                    $maxDiff = $diff;
                }
            }
        }

        $this->counts[$result]++;

        return $result;
    }
}
