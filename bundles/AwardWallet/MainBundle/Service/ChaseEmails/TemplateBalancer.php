<?php

namespace AwardWallet\MainBundle\Service\ChaseEmails;

use AwardWallet\MainBundle\FrameworkExtension\Mailer\EmailLog;
use Doctrine\DBAL\Connection;
use Psr\Log\LoggerInterface;

class TemplateBalancer
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

    public function __construct(
        Connection $connection,
        LoggerInterface $logger,
        TemplateFactory $templateFactory
    ) {
        $this->query = $connection->prepare("
        select 
            count(*) 
        from 
            EmailLog 
        where 
            MessageKind = " . EmailLog::MESSAGE_KIND_CHASE . "
            and Code = :Code
            and Template = :Template
        ");
        $this->logger = $logger;
        $this->enabledTemplates = array_merge(...$templateFactory->getEnabledTemplates());
    }

    public function selectTemplateWithFewerEmails(int $cardId): string
    {
        $minCount = null;
        $result = null;
        $templates = array_intersect(Constants::CARD_TEMPLATES[$cardId], $this->enabledTemplates);
        $resultKey = null;

        foreach ($templates as $template) {
            $key = $cardId . "_" . $template;

            if (!isset($this->counts[$key])) {
                $this->query->execute(["Code" => $cardId, "Template" => $template]);
                $this->counts[$key] = $this->query->fetchColumn();
                $this->logger->info("loaded template count for $key: {$this->counts[$key]}");
            }

            if ($minCount === null || $this->counts[$key] < $minCount) {
                $minCount = $this->counts[$key];
                $result = $template;
                $resultKey = $key;
            }
        }

        $this->counts[$resultKey]++;

        return $result;
    }
}
