<?php

namespace AwardWallet\MainBundle\Service\Lounge;

use AwardWallet\MainBundle\Entity\Airline;
use AwardWallet\MainBundle\Entity\Alliance;
use AwardWallet\MainBundle\Entity\Provider;
use AwardWallet\MainBundle\Entity\Repositories\AirlineRepository;
use AwardWallet\MainBundle\Entity\Repositories\ProviderRepository;
use AwardWallet\MainBundle\Globals\StringHandler;
use AwardWallet\MainBundle\Globals\Utils\LazyVal;
use AwardWallet\MainBundle\Service\Lounge\DTO\CarrierDTO;
use AwardWallet\MainBundle\Service\Lounge\DTO\LocationDTO;
use AwardWallet\MainBundle\Service\Lounge\DTO\ValueDTO;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;

use function AwardWallet\MainBundle\Globals\Utils\lazy;

class LoungeHelper
{
    private Connection $conn;

    private AirlineRepository $airlineRepository;

    private EntityRepository $allianceRepository;

    private ProviderRepository $providerRepository;

    /**
     * @var Alliance[]
     */
    private array $alliances;

    private Logger $logger;

    private LazyVal $airportTimezoneMap;

    public function __construct(Connection $connection, EntityManagerInterface $entityManager, Logger $logger)
    {
        $this->conn = $connection;
        $this->airlineRepository = $entityManager->getRepository(Airline::class);
        $this->allianceRepository = $entityManager->getRepository(Alliance::class);
        $this->providerRepository = $entityManager->getRepository(Provider::class);
        $this->alliances = $this->allianceRepository->findAll();
        $this->logger = $logger;
        $this->airportTimezoneMap = lazy(function () {
            return $this->conn->fetchAllKeyValue("
                SELECT AirCode, TimeZoneLocation
                FROM AirCode
                WHERE AirCode <> ''
            ");
        });
    }

    /**
     * @param ValueDTO[] $terminalLocations
     * @param ValueDTO[] $gateLocations
     */
    public function parseLocation(array $terminalLocations, array $gateLocations): LocationDTO
    {
        $location = new LocationDTO();

        foreach ($terminalLocations as $terminalLocation) {
            $value = $terminalLocation->getValue();
            $isInaccurate = $terminalLocation->isInaccurate();

            if ($isInaccurate) {
                if (!empty($value) && !empty($terminal = $this->parseTerminal($value))) {
                    $location->setTerminal($terminal);

                    break;
                }
            } else {
                if (!empty($value) && !empty($terminal = $this->parseTerminal($value))) {
                    $value = $terminal;
                }

                $location->setTerminal(empty($value) ? null : $value);

                break;
            }
        }

        $explicitGates = [];
        $parsedGates = [];

        foreach ($gateLocations as $gateLocation) {
            $value = $gateLocation->getValue();

            if (!empty($value) && is_array($gates = $this->parseGates($value))) {
                if ($gateLocation->isInaccurate()) {
                    $parsedGates = array_unique(array_merge($parsedGates, array_map(fn (string $gate) => $this->cleanGate($gate), $gates)));
                } else {
                    $explicitGates = array_unique(array_merge($explicitGates, array_map(fn (string $gate) => $this->cleanGate($gate), $gates)));
                }
            }
        }

        if (!empty($explicitGates)) {
            if (\count($explicitGates) > 2) {
                $this->logger->error(sprintf('Too many explicit gates: %s', implode(', ', $explicitGates)));
            } else {
                $explicitGates = array_values($explicitGates);

                if (isset($explicitGates[0])) {
                    $location->setGate($explicitGates[0]);
                }

                if (isset($explicitGates[1])) {
                    $location->setGate2($explicitGates[1]);
                }
            }
        } elseif (!empty($parsedGates)) {
            if (\count($parsedGates) > 2) {
                $this->logger->error(sprintf('Too many parsed gates: %s', implode(', ', $parsedGates)));
            } else {
                $parsedGates = array_values($parsedGates);

                if (isset($parsedGates[0])) {
                    $location->setGate($parsedGates[0]);
                }

                if (isset($parsedGates[1])) {
                    $location->setGate2($parsedGates[1]);
                }
            }
        }

        if (empty($location->getTerminal())) {
            $parsedTerminals = array_values(array_filter([
                !empty($location->getGate()) ? $this->parseTerminalFromGate($location->getGate()) : null,
                !empty($location->getGate2()) ? $this->parseTerminalFromGate($location->getGate2()) : null,
            ]));

            if (count($parsedTerminals) === 1 || (count($parsedTerminals) === 2 && $parsedTerminals[0] === $parsedTerminals[1])) {
                $location->setTerminal($parsedTerminals[0]);
            }
        }

        return $location;
    }

    /**
     * @param ValueDTO[] $airlines
     * @param ValueDTO[] $alliances
     */
    public function parseCarrier(array $airlines, array $alliances): CarrierDTO
    {
        $carrier = new CarrierDTO();

        foreach ($airlines as $airline) {
            $airlineName = $airline->getValue();

            if (StringHandler::isEmpty($airlineName)) {
                continue;
            }

            if (mb_strlen($airlineName) === 2 && ($airlineEntity = $this->airlineRepository->findOneBy(['code' => $airlineName]))) {
                $carrier->addAirline($airlineEntity);

                continue;
            }

            $airlineName = trim(str_replace(['lounge'], '', mb_strtolower($airlineName)));

            if (preg_match('/(\bair india\b|\bvirgin atlantic\b|\bs7\b)/i', $airlineName, $matches)) {
                $airlineName = $matches[1];
            }

            if (stripos($airlineName, 'kal ') === 0) {
                $airlineName = 'Korean Air';
            }

            $id = $this->conn->executeQuery("
                SELECT
                    a.AirlineID
                FROM Airline a
                    LEFT JOIN AirlineAlias aa ON a.AirlineID = aa.AirlineID
                WHERE
                    (
                        aa.Alias = :name OR a.Name = :name
                    ) AND a.Active = 1
                ORDER BY aa.AirlineAliasID DESC
            ", [':name' => $airlineName])->fetchOne();

            if ($id !== false && ($airlineEntity = $this->airlineRepository->find($id))) {
                $carrier->addAirline($airlineEntity);
            }

            $providers = $this->providerRepository->searchProviderByText(
                $airlineName,
                null,
                $this->conn->executeQuery("
                    SELECT p.AllianceID, p.IATACode, p.ProviderID, KeyWords
                    FROM Provider p
                    WHERE 
                        p.Kind IN (?) 
                        AND (p.AllianceID IS NOT NULL OR p.IATACode IS NOT NULL)
                        AND p.State IN (?)
                    ORDER BY CASE WHEN p.DisplayName LIKE ? THEN 0 ELSE 1 END
                ", [
                    [PROVIDER_KIND_AIRLINE, PROVIDER_KIND_TRAIN, PROVIDER_KIND_CRUISES],
                    ProviderRepository::PROVIDER_SEARCH_ALLOWED_STATES,
                    $airlineName,
                ], [
                    Connection::PARAM_INT_ARRAY,
                    Connection::PARAM_INT_ARRAY,
                    \PDO::PARAM_STR,
                ]),
                5
            );

            foreach ($providers as $provider) {
                if (!empty($provider['IATACode']) && ($airlineEntity = $this->airlineRepository->findOneBy(['code' => $provider['IATACode']]))) {
                    $carrier->addAirline($airlineEntity);
                }

                if (!empty($provider['AllianceID']) && ($allianceEntity = $this->allianceRepository->find($provider['AllianceID']))) {
                    $carrier->addAlliance($allianceEntity);
                }
            }
        }

        foreach ($alliances as $alliance) {
            $allianceName = $alliance->getValue();

            if (StringHandler::isEmpty($allianceName)) {
                continue;
            }

            foreach ($this->alliances as $allianceFromDB) {
                if (stripos($allianceFromDB->getName(), $allianceName) !== false) {
                    $carrier->addAlliance($allianceFromDB);
                }
            }
        }

        return $carrier;
    }

    public function getAirportTimezone(string $aircode, ?string $defaultTz = 'UTC'): ?string
    {
        return $this->airportTimezoneMap->getValue()[mb_strtoupper($aircode)] ?? $defaultTz;
    }

    private function parseTerminalFromGate(string $gate): ?string
    {
        if (preg_match('/^([A-Z]+)\d+$/ims', $gate, $matches)) {
            return $matches[1];
        }

        return null;
    }

    private function cleanGate(string $gate): string
    {
        if (preg_match("#^\s*([A-Z]-?\d{1,3})\s*$#i", $gate)) {
            return str_replace('-', '', $gate);
        } elseif (preg_match("#^\s*(\d{1,3}-?[A-Z])\s*$#i", $gate)) {
            return str_replace('-', '', $gate);
        }

        return $gate;
    }

    private function parseTerminal(string $value): ?string
    {
        // example: "Xxx Terminal"
        if (preg_match('/(?<terminal>(?:\b[A-Z][a-z]+\s+)+)(?<!Boarding\s|International\s)(?:[Tt]erminal|[Cc]oncourse)\b/m', $value, $m)) {
            return trim($m['terminal']);
        }

        // example: "Terminal Xxx"
        if (preg_match('/(?<=\b[Tt]erminal|[Cc]oncourse)\s+(?<terminal>\b[A-Z][a-z]+(?:\s|$)+)/m', $value, $m)) {
            return trim($m['terminal']);
        }

        // example: "Terminal 1"
        if (preg_match('/\b(?:Terminal|Concourse)\b\s*[-:]?\s*(?!Terminal|Concourse)(?!by|in)(?<terminal>\b[A-Z\d]{1,3})\b/im', $value, $m)) {
            return $m['terminal'];
        }

        // example: "1A Terminal"
        if (preg_match('/(?<terminal>\b[A-Z\d]{1,3})(?<!\bthe|of)\s*(?:Terminal|Concourse)/im', $value, $m)) {
            return $m['terminal'];
        }

        // example: " T2"
        if (preg_match('/^\s*T(?<terminal>\d+)\s*$/im', $value, $m)) {
            return $m['terminal'];
        }

        return null;
    }

    /**
     * @return string[]|null
     */
    private function parseGates(string $value): ?array
    {
        // range
        if (preg_match('/\bGates? ((?:[A-Z\-]*\d+)|(?:\d+[A-Z\-]*))(?:(?:(?:\s+(?:and|to)\s+))|(?:\s*(?:&|-|\/)\s*))(?:Gate\s+)?((?:[A-Z\-]*\d+)|(?:\d+[A-Z\-]*))\b/im', $value, $m)) {
            $gates = [$m[1], $m[2]];
            sort($gates);

            return $gates;
        }

        if (preg_match_all('/\bGates? ((?:[A-Z\-]*\d+)|(?:\d+[A-Z\-]*))\b/im', $value, $m)) {
            if (\count($m[1]) <= 2) {
                sort($m[1]);

                return array_values(array_filter($m[1]));
            }
        }

        return null;
    }
}
