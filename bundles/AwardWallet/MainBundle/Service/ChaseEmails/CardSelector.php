<?php

namespace AwardWallet\MainBundle\Service\ChaseEmails;

use Psr\Log\LoggerInterface;

class CardSelector
{
    private EmailBalancer $emailBalancer;

    private TemplateBalancer $templateBalancer;

    /**
     * @var array
     */
    private $cardPriority;

    private LoggerInterface $logger;

    private TemplateFactory $templateFactory;

    public function __construct(
        EmailBalancer $emailBalancer,
        TemplateBalancer $templateBalancer,
        LoggerInterface $logger,
        TemplateFactory $templateFactory
    ) {
        $this->emailBalancer = $emailBalancer;
        $this->templateBalancer = $templateBalancer;
        $this->logger = $logger;
        $this->templateFactory = $templateFactory;
        $this->cardPriority = $this->loadCardPriority();
    }

    public function selectCard(array $users): array
    {
        $users = array_map(function (array $user) {
            $selectedCards = $this->selectCardsByPriority($user['Cards']);

            if (empty(count($selectedCards))) {
                return $user;
            }
            $user['CardID'] = $this->emailBalancer->selectCardByRatios($selectedCards);
            $user['Template'] = $this->templateBalancer->selectTemplateWithFewerEmails($user['CardID']);

            return $user;
        }, $users);
        $before = count($users);
        $users = array_filter($users, function (array $user) {
            return isset($user['CardID']);
        });
        $after = count($users);
        $this->logger->info("filtered users from {$before} to {$after}");
        $this->showSelectedStats($users);

        return $users;
    }

    private function loadCardPriority(): array
    {
        $enabledCards = array_keys($this->templateFactory->getEnabledTemplates());
        $result = array_map(function (array $cardsWithSamePriority) use ($enabledCards) {
            return array_intersect_key($cardsWithSamePriority, array_combine($enabledCards, $enabledCards));
        }, Constants::CARD_PRIORITY);
        $result = array_filter($result, function (array $cards) {
            return count($cards) > 0;
        });

        foreach ($result as $priority => $cardPercentages) {
            $this->logger->info("priority $priority: " . json_encode($cardPercentages));
        }

        return $result;
    }

    private function selectCardsByPriority(array $cards): array
    {
        foreach ($this->cardPriority as $priority => $cardsWithThisPriority) {
            $selectedCards = array_intersect_key($cardsWithThisPriority, array_combine($cards, $cards));

            if (count($selectedCards) > 0) {
                return $selectedCards;
            }
        }

        return [];
    }

    private function showSelectedStats(array $users): void
    {
        $templates = [];
        $cards = [];

        foreach ($users as $user) {
            if (!isset($cards[$user['CardID']])) {
                $cards[$user['CardID']] = 1;
            } else {
                $cards[$user['CardID']]++;
            }

            if (!isset($templates[$user['Template']])) {
                $templates[$user['Template']] = 1;
            } else {
                $templates[$user['Template']]++;
            }
        }

        arsort($cards);
        $this->logger->info("selected cards:");

        foreach ($cards as $cardId => $userCount) {
            $this->logger->info(" > " . Constants::CARD_NAMES[$cardId] . ": " . $userCount . " (" . round($userCount / count($users) * 100) . "%)");
        }

        arsort($templates);
        $this->logger->info("selected templates:");

        foreach ($templates as $template => $userCount) {
            $this->logger->info(" > {$template}: " . $userCount . " (" . round($userCount / count($users) * 100) . "%)");
        }
    }
}
