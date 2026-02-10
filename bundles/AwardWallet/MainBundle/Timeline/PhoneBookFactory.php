<?php

namespace AwardWallet\MainBundle\Timeline;

use AwardWallet\MainBundle\Entity\Airline;
use AwardWallet\MainBundle\Entity\Itinerary;
use AwardWallet\MainBundle\Entity\Provider;
use AwardWallet\MainBundle\Entity\Repositories\ProviderRepository;
use AwardWallet\MainBundle\Entity\Trip;
use AwardWallet\MainBundle\Entity\Tripsegment;
use AwardWallet\MainBundle\Entity\Type\Phones;
use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\Globals\StringUtils;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Statement;

use function AwardWallet\MainBundle\Globals\Utils\iterFluent\it;

class PhoneBookFactory
{
    private ProviderRepository $providerRepository;

    private Connection $connection;

    private Statement $accountRankQuery;

    public function __construct(
        ProviderRepository $providerRepository,
        Connection $connection
    ) {
        $this->providerRepository = $providerRepository;
        $this->connection = $connection;

        $this->accountRankQuery = $this->connection->prepare('
            SELECT
                a.ProviderID,
                MAX(el.Rank) AS `Rank`
            FROM
                Account a
                JOIN AccountProperty ap ON a.AccountID = ap.AccountID
                JOIN ProviderProperty pp ON ap.ProviderPropertyID = pp.ProviderPropertyID
                JOIN EliteLevel el ON a.ProviderID = el.ProviderID
                JOIN TextEliteLevel tel ON 
                    tel.EliteLevelID = el.EliteLevelID AND 
                    ap.Val = tel.ValueText
            WHERE
                pp.Kind = ' . PROPERTY_KIND_STATUS . '
                AND a.UserID = ?
            GROUP BY a.ProviderID
		');
    }

    /**
     * @param Itinerary[]|Tripsegment[] $itineraries
     */
    public function create(array $itineraries, ?Usr $user): PhoneBook
    {
        [$providersListIds, $airlinePairsMapByIata] = $this->fetchProvidersAndAirlines($itineraries);
        $phones = $this->fetchPhones($providersListIds, $user);
        $eliteLevels = $this->fetchEliteLevels(
            it($itineraries)
                ->map(function ($itinerary) {
                    if ($itinerary instanceof Tripsegment) {
                        $account = $itinerary->getTripid()->getAccount();
                    } else {
                        $account = $itinerary->getAccount();
                    }

                    if ($account) {
                        return $account->getId();
                    }

                    return null;
                })
                ->filterNotNull()
            ->unique()
            ->toArray()
        );

        return new PhoneBook(
            $airlinePairsMapByIata,
            $phones,
            $eliteLevels,
            count($itineraries) === 1 ? $itineraries[0] : null
        );
    }

    /**
     * @param int[] $accountIds
     */
    private function fetchEliteLevels(array $accountIds): array
    {
        if (!$accountIds) {
            return [];
        }

        return $this->connection->executeQuery('
                SELECT ap.AccountID, ap.Val 
                FROM AccountProperty ap
                JOIN ProviderProperty pp ON ap.ProviderPropertyID = pp.ProviderPropertyID
                WHERE pp.Kind = ' . PROPERTY_KIND_STATUS . ' AND ap.AccountID in (?)
            ',
            [$accountIds],
            [Connection::PARAM_INT_ARRAY]
        )->fetchAllKeyValue();
    }

    /**
     * @param int[] $providerIdsList
     */
    private function fetchPhones(array $providerIdsList, ?Usr $user): Phones
    {
        if (!$providerIdsList) {
            return new Phones($providerIdsList, [], [], []);
        }

        // no sense to prepare this queries in constructor - ProviderID in (?) will not allow prepared queries
        $phoneQuery = $this->connection->executeQuery('
            SELECT
                ph.ProviderID,
                el.Name as EliteLevel,
                el.EliteLevelID,
                el.Rank,
                c.Name AS Country,
                c.Code AS CountryCode,
                ph.Phone,
                ph.Paid,
                ph.DefaultPhone,
                ph.DisplayNote,
                ph.PhoneFor,
                UPPER(p.IATACode) as IATACode,
                p.DisplayName
            FROM
                ProviderPhone ph
                JOIN Provider p ON ph.ProviderID = p.ProviderID
                LEFT OUTER JOIN EliteLevel el on el.EliteLevelID = ph.EliteLevelID
                LEFT OUTER JOIN Country c on ph.CountryID = c.CountryID
            WHERE
                ph.ProviderID in (?)
            ',
            [$providerIdsList],
            [Connection::PARAM_INT_ARRAY]
        );

        $levelQuery = $this->connection->executeQuery('
            SELECT
                el.ProviderID,
                el.Rank,
                tel.EliteLevelID,
                tel.ValueText
            FROM
                TextEliteLevel tel
                JOIN EliteLevel el on tel.EliteLevelID = el.EliteLevelID
            WHERE
                el.ProviderID in (?)
            ',
            [$providerIdsList],
            [Connection::PARAM_INT_ARRAY]
        );

        if ($user instanceof Usr) {
            $userLevelsMap = $this->accountRankQuery->executeQuery([$user->getId()])->fetchAllAssociativeIndexed();
        } else {
            $userLevelsMap = [];
        }

        return new Phones(
            $providerIdsList,
            $phoneQuery->fetchAllAssociative(),
            $levelQuery->fetchAllAssociative(),
            $userLevelsMap
        );
    }

    /**
     * @param Itinerary[]|Tripsegment[] $itineraries
     */
    private function fetchProvidersAndAirlines(array $itineraries): array
    {
        /** @var int[] $providersListIds */
        $providersListIds = [];
        /** @var Airline[] $airlinesList */
        $airlinesList = [];

        foreach ($itineraries as $itinerary) {
            if ($itinerary instanceof Tripsegment) {
                $tripSegment = $itinerary;
                $itinerary = $tripSegment->getTripid();
            } else {
                $tripSegment = null;
            }

            if ($itinerary->getAccount() && ($provider = $itinerary->getAccount()->getProviderid())) {
                $providersListIds[] = $provider->getId();
            }

            if ($provider = $itinerary->getRealProvider()) {
                $providersListIds[] = $provider->getId();
            }

            if ($travelAgency = $itinerary->getTravelAgency()) {
                $providersListIds[] = $travelAgency->getId();
            }

            if ($itinerary instanceof Trip) {
                if ($airline = $itinerary->getIssuingAirline()) {
                    $airlinesList[] = $airline;
                }

                if ($tripSegment) {
                    if ($airline = $tripSegment->getOperatingAirline()) {
                        $airlinesList[] = $airline;
                    }

                    if ($airline = $tripSegment->getMarketingAirline()) {
                        $airlinesList[] = $airline;
                    }
                }
            }
        }

        $airlinePairsMapByIata = [];

        if (count($airlinesList) > 0) {
            $iataCodeNormalizer = fn (string $iata) => \strtoupper($iata);
            /** @var Airline[] $airlinesByIataMap */
            $airlinesByIataMap = it($airlinesList)
                ->filter(function (Airline $airline) {
                    return StringUtils::isNotEmpty($airline->getCode());
                })
                ->reindex(function (Airline $airline) use ($iataCodeNormalizer) {
                    return $iataCodeNormalizer($airline->getCode());
                })
                ->toArrayWithKeys();

            if (count($airlinesByIataMap) > 0) {
                $providersWithIataCodeMap = $this->providerRepository->findBy([
                    'IATACode' => array_keys($airlinesByIataMap),
                    'corporate' => false,
                ]);

                $providersWithIataCodeMap =
                    it($providersWithIataCodeMap)
                        ->reindex(function (Provider $provider) use ($iataCodeNormalizer, &$providersListIds) {
                            $providersListIds[] = $provider->getId();

                            return $iataCodeNormalizer($provider->getIATACode());
                        })
                        ->collapseByKey();

                /** @var Provider[] $providersWithIata */
                foreach ($providersWithIataCodeMap as $iataCode => $providersWithIata) {
                    if (isset($airlinesByIataMap[$iataCode])) {
                        $airlinePairsMapByIata[$iataCode] = new ProviderAirlinePair($airlinesByIataMap[$iataCode], $providersWithIata);
                    }
                }
            }
        }

        return [array_unique($providersListIds), $airlinePairsMapByIata];
    }
}
