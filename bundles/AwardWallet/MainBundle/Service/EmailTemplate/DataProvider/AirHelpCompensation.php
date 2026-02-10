<?php

namespace AwardWallet\MainBundle\Service\EmailTemplate\DataProvider;

use AwardWallet\MainBundle\Service\EmailTemplate\DataProvider\Fixture\AirHelpView;
use AwardWallet\MainBundle\Service\EmailTemplate\Options;

use function AwardWallet\MainBundle\Globals\Utils\iterFluent\it;

class AirHelpCompensation extends AbstractAirHelpCompensation
{
    public function getQueryOptions()
    {
        $options = parent::getQueryOptions();
        array_walk($options, function ($option) {
            /** @var Options $option */
            $option->airHelpCompensationEpoch = ['ec_04_19'];
            $option->ignoreEmailOffers = true;
            $option->ignoreEmailProductUpdates = true;
            $option->notUserId = self::EXCLUDED_USERS;
        });

        return $options;
    }

    public function getDescription(): string
    {
        return 'AirHelp compensation (non-Brazil users)';
    }

    public function getTitle(): string
    {
        return 'AirHelp compensation (non-Brazil users)';
    }

    public function getSortPriority(): int
    {
        return 1;
    }

    protected function decideLocalizedCity(string $normalized, string $localized): string
    {
        return $normalized;
    }

    protected function getFlightStatusTextBlock(string $flightStatus): string
    {
        return (self::CANCELLED === $flightStatus) ?
            'cancellation' :
            'delay';
    }

    protected function getFlightStatusUpper(string $flight_status): string
    {
        return \mb_strtoupper($flight_status, 'UTF-8');
    }

    protected function getFlightStatusLower(string $flight_status): string
    {
        return \mb_strtolower($flight_status, 'UTF-8');
    }

    /**
     * @param AirHelpView[] $airHelpViewList
     */
    protected function getBottomFlightEnd(array $airHelpViewList): string
    {
        if (\count($airHelpViewList) === 1) {
            return $airHelpViewList[0]->arrival_city . ' flight';
        } else {
            /** @var string[] $lastList */
            $airHelpViewList =
                it($airHelpViewList)
                ->map(function (AirHelpView $airHelpView) { return $airHelpView->arrival_city; })
                ->unique()
                ->toArray();

            if (\count($airHelpViewList) === 1) {
                return 'flights to ' . $airHelpViewList[0];
            } else {
                [$prefixList, [$last]] = \array_chunk($airHelpViewList, \count($airHelpViewList) - 1);

                return
                    'flights to ' .
                    \implode(', ', $prefixList) .
                    (\count($prefixList) > 1 ? ',' : '') .
                    ' and ' .
                    $last;
            }
        }
    }

    protected function getListTemplatePath(): string
    {
        return '@MailTemplate/Layout/Offer/AirHelp/air_help_view.twig';
    }

    /**
     * @param AirHelpView[] $airHelpViewList
     */
    protected function getAirHelpTitle(array $airHelpViewList): string
    {
        if (\count($airHelpViewList) === 1) {
            $firstAirHelpView = $airHelpViewList[0];

            return "flight from {$firstAirHelpView->departure_city} to {$firstAirHelpView->arrival_city} on {$firstAirHelpView->flight_scheduled_departure_top_localized} may have been {$firstAirHelpView->flight_status_lower}";
        } else {
            return \count($airHelpViewList) . ' flights may have been delayed or canceled';
        }
    }

    protected function getTopDepartureDate(\DateTime $dateTime): string
    {
        return $this->localizer->formatDate($dateTime, 'long', 'en_US');
    }
}
