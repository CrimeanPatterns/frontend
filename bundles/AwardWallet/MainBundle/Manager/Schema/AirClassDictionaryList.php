<?php

namespace AwardWallet\MainBundle\Manager\Schema;

use AwardWallet\MainBundle\Service\MileValue\LongHaulDetector;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\FetchMode;
use Symfony\Component\Routing\RouterInterface;

class AirClassDictionaryList extends \TBaseList
{
    /**
     * @var LongHaulDetector|object|null
     */
    private $longHaulDetector;

    public function __construct($table, $fields, $defaultSort)
    {
        parent::__construct($table, $fields, $defaultSort);
        $this->longHaulDetector = getSymfonyContainer()->get(LongHaulDetector::class);
    }

    public function FormatFields($output = "html")
    {
        parent::FormatFields($output);

        /** @var Connection $connection */
        $connection = getSymfonyContainer()->get("database_connection");
        /** @var RouterInterface $router */
        $router = getSymfonyContainer()->get("router");

        $this->Query->Fields["Classes"] = "";

        $providers = [];

        if (!empty($this->Query->Fields["TripIDs"])) {
            $classes = [];
            $this->Query->Fields["TripIDs"] = implode(", ", array_map(function (int $tripId) use ($connection, $router, &$classes, &$providers) {
                $fields = $connection->executeQuery(/** $langMSQL */ "
                    select 
                        t.UserID,
                        group_concat(t.SpentAwardsProviderID, ',') as ProviderIDs,
                        group_concat(ts.BookingClass separator ', ') as BookingClasses,
                        group_concat(concat(ts.DepCode, '-', ts.ArrCode) separator ',') as Route
                    from 
                        Trip t  
                        join TripSegment ts on ts.TripID = t.TripID
                    where
                        t.TripID = ?
                    group by
                        t.UserID
                    ", [$tripId])->fetch(FetchMode::ASSOCIATIVE);

                if (empty($fields)) {
                    return $tripId;
                }

                $fields['Global'] = $this->detectLongHaul($fields['Route']);
                $classes = array_merge($classes, explode(", ", $fields["BookingClasses"]));
                $providers = array_merge($providers, explode(",", $fields["ProviderIDs"]));
                $targetUrl = $router->generate("aw_timeline_html5_itineraries", ["itIds" => 'T.' . $tripId]);
                $link = $router->generate("aw_manager_impersonate", ["UserID" => $fields['UserID'], "Goto" => $targetUrl]);

                return "<a target='_blank' href='{$link}'>{$tripId} ({$fields['Global']} {$fields['BookingClasses']})</a>";
            }, explode(",", $this->Query->Fields["TripIDs"])));
            $classes = array_unique($classes);
            sort($classes);
            $this->Query->Fields["Classes"] = implode(", ", $classes);
        }

        if (!empty($this->Query->Fields["ProviderIDs"])) {
            $providers = array_unique(array_merge($providers, explode(",", $this->Query->Fields["ProviderIDs"])));
        }

        if (!empty($providers)) {
            $this->Query->Fields["ProviderIDs"] = $connection->executeQuery("
                    select group_concat(Provider.Code) from Provider
                    where
                        Provider.ProviderID in (?)
                    ", [$providers], [Connection::PARAM_INT_ARRAY])->fetch(FetchMode::COLUMN);
        }

        if ($this->Query->Fields["SourceFareClass"] == 1) {
            $this->Query->Fields["SourceFareClass"] = '';
        }
    }

    private function detectLongHaul(string $route): string
    {
        $routes = array_map(function (string $route) {
            $pair = explode("-", $route);

            return ["DepCode" => $pair[0], "ArrCode" => $pair[1]];
        }, explode(",", $route));

        return $this->longHaulDetector->isLongHaulRoutes($routes) ? "Glo" : "Reg";
    }
}
