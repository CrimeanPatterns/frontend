<?php

namespace AwardWallet\MainBundle\Service\EmailTemplate\DataProvider;

use AwardWallet\MainBundle\Service\EmailTemplate\DataProvider\Fixture\AirHelpView;
use AwardWallet\MainBundle\Service\EmailTemplate\Options;

use function AwardWallet\MainBundle\Globals\Utils\iterFluent\it;

class AirHelpCompensationBrazil extends AbstractAirHelpCompensation
{
    public function getQueryOptions()
    {
        $options = parent::getQueryOptions();
        array_walk($options, function ($option) {
            /** @var Options $option */
            $option->airHelpCompensationEpoch = ['br_06_08'];
            $option->ignoreEmailOffers = true;
            $option->ignoreEmailProductUpdates = true;
            $option->notUserId = self::EXCLUDED_USERS;
        });

        return $options;
    }

    public function getDescription(): string
    {
        return 'AirHelp compensation (Brazil users)';
    }

    public function getTitle(): string
    {
        return 'AirHelp compensation (Brazil users)';
    }

    public function getSortPriority(): int
    {
        return 1;
    }

    protected function decideLocalizedCity(string $normalized, string $localized): string
    {
        return $localized;
    }

    protected function getFlightStatusTextBlock(string $flightStatus): string
    {
        return (self::CANCELLED === $flightStatus) ?
            'cancelamento' :
            'atraso';
    }

    protected function getFlightStatusUpper(string $flight_status): string
    {
        return \mb_strtoupper(
            (self::CANCELLED === $flight_status) ?
                'cancelado' :
                'atrasado'
        );
    }

    protected function getFlightStatusLower(string $flight_status): string
    {
        return (self::CANCELLED === $flight_status) ?
            'cancelado' :
            'atrasado';
    }

    /**
     * @param AirHelpView[] $airHelpViewList
     */
    protected function getBottomFlightEnd(array $airHelpViewList): string
    {
        if (\count($airHelpViewList) === 1) {
            return
                'O seu voo para ' .
                $airHelpViewList[0]->arrival_city .
                ' pode ser considerado como passível de compensação conforme as leis e regulamentações de direitos dos passageiros de cias. aéreas';
        } else {
            /** @var string[] $lastList */
            $airHelpViewList =
                it($airHelpViewList)
                ->map(function (AirHelpView $airHelpView) { return $airHelpView->arrival_city; })
                ->unique()
                ->toArray();

            if (\count($airHelpViewList) === 1) {
                return
                    'Os seus voos para ' . $airHelpViewList[0] .
                    ' podem ser considerados como passíveis de compensação conforme as leis e regulamentações dos direitos dos passageiros de cias. aéreas';
            } else {
                [$prefixList, [$last]] = \array_chunk($airHelpViewList, \count($airHelpViewList) - 1);

                return
                    'Os seus voos para ' .
                    \implode(', ', $prefixList) .
                    (\count($prefixList) > 1 ? ',' : '') .
                    ' e ' .
                    $last .
                    ' podem ser considerados como passíveis de compensação conforme as leis e regulamentações dos direitos dos passageiros de cias. aéreas';
            }
        }
    }

    protected function getListTemplatePath(): string
    {
        return '@MailTemplate/Layout/Offer/AirHelp/air_help_view_brazil.twig';
    }

    /**
     * @param AirHelpView[] $airHelpViewList
     */
    protected function getAirHelpTitle(array $airHelpViewList): string
    {
        if (\count($airHelpViewList) === 1) {
            $firstAirHelpView = $airHelpViewList[0];

            return "o seu <b>voo de {$firstAirHelpView->departure_city} para {$firstAirHelpView->arrival_city} a {$firstAirHelpView->flight_scheduled_departure_top_localized} " . (self::CANCELLED === $firstAirHelpView->status_original ? "foi cancelado" : "tenha atrasado") . '</b>';
        } else {
            return "os seus <b>" . \count($airHelpViewList) . " voos tenham atrasado ou sido cancelados</b>";
        }
    }

    protected function getTopDepartureDate(\DateTime $dateTime): string
    {
        return $this->localizer->formatDate($dateTime, 'long', 'pt');
    }
}
