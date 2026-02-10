<?php

namespace AwardWallet\MainBundle\Security;

use AwardWallet\MainBundle\Entity\Repositories\ParameterRepository;
use Aws\CloudWatch\CloudWatchClient;
use Psr\Log\LoggerInterface;

class SiegeModeDetector
{
    public const SIEGE_MODE_PARAM_NAME = 'siege_mode';

    private const SIEGE_MODE_KEY = 'siege_mode';
    private const SIEGE_MODE_UPDATE_LOCK_KEY = 'siege_mode_update_lock';
    private const ALARM_NAME = 'SecurityWarningCount';

    /**
     * @var CloudWatchClient
     */
    private $cloudWatchClient;
    /**
     * @var \Memcached
     */
    private $memcached;
    /**
     * @var LoggerInterface
     */
    private $logger;
    /**
     * @var ParameterRepository
     */
    private $parameterRepository;

    public function __construct(
        CloudWatchClient $cloudWatchClient,
        \Memcached $memcached,
        LoggerInterface $securityLogger,
        ParameterRepository $parameterRepository
    ) {
        $this->cloudWatchClient = $cloudWatchClient;
        $this->memcached = $memcached;
        $this->logger = $securityLogger;
        $this->parameterRepository = $parameterRepository;
    }

    public function isUnderSiege(): bool
    {
        // was mode enabled or disabled through /manager/security/siege-mode ?
        $mode = $this->parameterRepository->getParam(self::SIEGE_MODE_PARAM_NAME, '');

        if ($mode !== '') {
            return $mode === '1';
        }

        // automatic mode based on cloudwatch alarm status
        $cache = $this->memcached->get(self::SIEGE_MODE_KEY);

        if (($cache === false || $cache['updateDate'] < (time() - 60)) && $this->memcached->add(self::SIEGE_MODE_UPDATE_LOCK_KEY, "updating", 30)) {
            $cache = [
                "siege" => $this->cloudWatchClient->describeAlarms(['AlarmNames' => [self::ALARM_NAME]])->get('MetricAlarms')[0]['StateValue'] === 'ALARM',
                "updateDate" => time(),
            ];
            $this->logger->info("updated siege mode: " . json_encode($cache['siege']));
            $this->memcached->set(self::SIEGE_MODE_KEY, $cache, 80);
        }

        return $cache !== false && $cache['siege'] === true;
    }
}
