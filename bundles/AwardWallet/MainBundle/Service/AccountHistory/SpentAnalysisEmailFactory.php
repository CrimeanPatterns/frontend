<?php

namespace AwardWallet\MainBundle\Service\AccountHistory;

use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\FrameworkExtension\Mailer\Template\Account\CardSpendAnalysis;
use AwardWallet\MainBundle\Globals\Localizer\LocalizeService;

class SpentAnalysisEmailFactory
{
    private BankTransactionsAnalyser $analyser;

    private LocalizeService $localizer;

    public function __construct(BankTransactionsAnalyser $analyser, LocalizeService $localizer)
    {
        $this->analyser = $analyser;
        $this->localizer = $localizer;
    }

    public function buildLastMonth(Usr $user): ?CardSpendAnalysis
    {
        $result = null;

        $startDate = new \DateTime('first day of previous month');
        $endDate = new \DateTime('first day of this month');
        $initial = $this->analyser->getSpentAnalysisInitial($user);

        $subAccIds = [];

        foreach ($initial['ownersList'] as $userList) {
            if (!isset($userList['availableCards'])) {
                continue;
            }
            $subAccIds = array_merge($subAccIds, array_column($userList['availableCards'], 'subAccountId'));
        }

        if (empty($subAccIds)) {
            return null;
        }

        $data = $this->analyser->shoppingCategoryGroupAnalytics(
            $user,
            $subAccIds,
            $startDate->format('Y-m-d'),
            $endDate->format('Y-m-d')
        );

        if (empty($data)) {
            return null;
        }

        return $this->buildEmail($user, $data, $startDate, $endDate);
    }

    private function buildEmail(Usr $user, array $data, \DateTime $startDate, \DateTime $endDate): CardSpendAnalysis
    {
        $top = null;
        $chartUri = ['e' => [], 'p' => [], 'l' => []];

        foreach ($data as &$item) {
            $item['category'] = html_entity_decode($item['category']);

            $chartUri['e'][] = $item['miles'];
            $chartUri['p'][] = $item['potentialMiles'];
            $chartUri['l'][] = str_replace('_', ' ', trim($item['category']));

            if (empty($top) && !empty($item['category'])) {
                $top = [
                    'category' => $item['category'],
                    'earned' => $item['miles'],
                    'potential' => $item['potentialMiles'],
                    'blogUrl' => $item['blogUrl'],
                ];
            }
        }

        foreach ($chartUri as &$item) {
            $item = implode('_', $item);
        }

        $template = new CardSpendAnalysis(
            $user,
            $top,
            http_build_query($chartUri),
            $this->localizer->formatDate($startDate) . ' - ' . $this->localizer->formatDate($endDate)
        );

        return $template;
    }
}
