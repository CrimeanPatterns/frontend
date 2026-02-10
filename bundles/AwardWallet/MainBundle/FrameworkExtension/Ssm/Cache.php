<?php

namespace AwardWallet\MainBundle\FrameworkExtension\Ssm;

use Aws\Ssm\SsmClient;
use Monolog\Handler\PsrHandler;
use Monolog\Logger;
use Psr\Log\LoggerInterface;

class Cache
{
    private const SHM_SIZE = 64000;

    /**
     * @var array
     */
    private $paths;
    /**
     * @var SsmClient
     */
    private $ssmClient;
    /**
     * @var LoggerInterface
     */
    private $logger;
    /**
     * @var array
     */
    private $names;

    public function __construct(array $paths, array $names, SsmClient $ssmClient, LoggerInterface $logger)
    {
        $this->paths = $paths;
        $this->ssmClient = $ssmClient;
        $this->logger = new Logger('ssm', [new PsrHandler($logger)], [function (array $record) {
            $record['extra']['service'] = 'ssm';

            return $record;
        }]);
        $this->names = $names;
    }

    public function warmup()
    {
        $this->logger->debug("warming up cache");
        $cache = [];

        foreach ($this->paths as $path) {
            $cache = array_merge($cache, $this->readParamsByPath($path));
        }

        if (count($this->names) > 0) {
            $cache = array_merge($cache, $this->readParamsByNames($this->names));
        }
        $this->logger->debug("read " . count($cache) . " params");
        $this->saveCache($cache);
    }

    public function get(): array
    {
        $shm = @shmop_open($this->getShmId(), "a", 0400, 64000);

        if ($shm === false) {
            $this->logger->debug("no shm cache");

            return [];
        }
        $json = shmop_read($shm, 0, self::SHM_SIZE);

        if ($json === false) {
            $this->logger->debug("failed to read shm cache");

            return [];
        }
        $cache = json_decode($json, true);
        $this->logger->debug("read " . count($cache) . " parameters from shm");

        return $cache;
    }

    private function readParamsByPath($path): array
    {
        $results = [];
        $this->logger->debug("reading all from path: {$path}");

        do {
            $requestParams = [
                "Path" => $path,
                "Recursive" => true,
                "WithDecryption" => true,
                // filter by tag does not work. I don't know why
                // may be will be fixed later
                //                "ParameterFilters" => [
                //                    ["Key" => "tag:preload", "Option" => "Equals", "Values" => ["1"]]
                //                ]
            ];

            if (isset($response) && !empty($response["NextToken"])) {
                $requestParams["NextToken"] = $response["NextToken"];
            }
            $response = $this->ssmClient->getParametersByPath($requestParams);
            $this->logger->debug("got " . count($response["Parameters"]) . " params, next token: " . (empty($response["NextToken"]) ? "No" : "Yes"));

            foreach ($response["Parameters"] as $param) {
                $this->logger->debug("got param " . $param["Name"]);
                $results[$param['Name']] = $param['Value'];
            }
        } while (!empty($response["NextToken"]));

        return $results;
    }

    private function readParamsByNames(array $names): array
    {
        $this->logger->debug("preloading params by names");
        $response = $this->ssmClient->getParameters(["Names" => $names, "WithDecryption" => true]);
        $this->logger->debug("got " . count($response["Parameters"]) . " params");

        $results = [];

        foreach ($response["Parameters"] as $param) {
            $this->logger->debug("got param " . $param["Name"]);
            $results[$param['Name']] = $param['Value'];
        }

        return $results;
    }

    private function saveCache(array $cache): void
    {
        $json = json_encode($cache);
        $this->logger->debug("saving " . strlen($json) . " bytes to cache");

        if (strlen($json) > self::SHM_SIZE) {
            throw new \Exception("too many params");
        }
        $json = str_pad($json, self::SHM_SIZE, ' ');
        $shm = shmop_open($this->getShmId(), "c", 0600, 64000);

        if ($shm === false) {
            throw new \Exception("Failed to create shm");
        }

        if (!shmop_write($shm, $json, 0)) {
            throw new \Exception("Failed to write shm");
        }
    }

    private function getShmId()
    {
        return ftok(__FILE__, 't');
    }
}
