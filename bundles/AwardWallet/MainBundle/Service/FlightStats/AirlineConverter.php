<?php

namespace AwardWallet\MainBundle\Service\FlightStats;

use AwardWallet\Common\Memcached\Item;
use AwardWallet\Common\Memcached\Util;
use Psr\Log\LoggerInterface;

class AirlineConverter
{
    public const REINITIALIZATION_TIME = 60 * 60;

    private Util $memcachedUtil;

    private LoggerInterface $logger;

    private string $appId;

    private string $appKey;

    /**
     * @var string[]
     */
    private $IataToFSCodeIndex = [];

    /**
     * @var string[]
     */
    private $FSCodeToIataIndex = [];

    /**
     * @var string[]
     */
    private $FSCodeToNameIndex = [];

    private $initializationTimestamp;

    public function __construct(Util $memcachedUtil, LoggerInterface $logger, string $flightInfoAppId, string $flightInfoAppKey)
    {
        $this->memcachedUtil = $memcachedUtil;
        $this->logger = $logger;
        $this->appId = $flightInfoAppId;
        $this->appKey = $flightInfoAppKey;
    }

    /**
     * @return string|null
     */
    public function IataToFSCode(string $iata)
    {
        $index = $this->getIataToFSCodeIndex();

        if (isset($this->getIataToFSCodeIndex()[$iata])) {
            return $index[$iata];
        }

        return null;
    }

    /**
     * @return string|null
     */
    public function FSCodeToIata(string $FSCode)
    {
        $index = $this->getFSCodeToIataIndex();

        if (isset($index[$FSCode])) {
            return $index[$FSCode];
        }

        return null;
    }

    /**
     * @return string|null
     */
    public function FSCodeToName(string $FSCode)
    {
        $index = $this->getFSCodeToNameIndex();

        if (isset($index[$FSCode])) {
            return $index[$FSCode];
        }

        return null;
    }

    private function getAllAirlines()
    {
        return $this->memcachedUtil->getThrough("flightstats_all_airlines", function () {
            $result = @json_decode(curlRequest("https://api.flightstats.com/flex/airlines/rest/v1/json/active?appId=" . urlencode($this->appId) . "&appKey=" . urlencode($this->appKey)), true);

            if (is_array($result) && isset($result['airlines'])) {
                return new Item($result['airlines'], SECONDS_PER_DAY);
            } else {
                return new Item([], 300);
            }
        });
    }

    /**
     * @return string[]
     */
    private function getIataToFSCodeIndex()
    {
        $this->initialize();

        return $this->IataToFSCodeIndex;
    }

    /**
     * @return string[]
     */
    private function getFSCodeToIataIndex()
    {
        $this->initialize();

        return $this->FSCodeToIataIndex;
    }

    private function getFSCodeToNameIndex()
    {
        $this->initialize();

        return $this->FSCodeToNameIndex;
    }

    private function initialize()
    {
        if (null !== $this->initializationTimestamp && time() - $this->initializationTimestamp < self::REINITIALIZATION_TIME) {
            return;
        }
        $airlines = $this->getAllAirlines();

        foreach ($airlines as $airline) {
            if (!empty($airline['iata'])) {
                $this->IataToFSCodeIndex[$airline['iata']] = $airline['fs'];
                $this->FSCodeToIataIndex[$airline['fs']] = $airline['iata'];
            }

            if (!empty($airline['name'])) {
                $this->FSCodeToNameIndex[$airline['fs']] = $airline['name'];
            }
        }
        $this->initializationTimestamp = time();
        $this->logger->info("loaded airlines: " . count($this->IataToFSCodeIndex) . ", all airlines: " . count($airlines));
    }
}
