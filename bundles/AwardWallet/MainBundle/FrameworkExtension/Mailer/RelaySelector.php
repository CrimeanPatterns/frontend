<?php

namespace AwardWallet\MainBundle\FrameworkExtension\Mailer;

use AwardWallet\MainBundle\Entity\Repositories\ParameterRepository;
use Psr\Log\LoggerInterface;

class RelaySelector
{
    public const RELAY_PARAM_NAME = 'mail-relay';

    /**
     * @var array
     */
    private $relays;

    private $relayTransports = [];
    /**
     * @var \Swift_Transport
     */
    private $defaultTransport;
    /**
     * @var \Memcached
     */
    private $memcached;
    /**
     * @var ParameterRepository
     */
    private $parameterRepository;
    /**
     * @var int
     */
    private $lastRelayCheck = 0;
    /**
     * @var string
     */
    private $cachedForcedRelay = '';
    /**
     * @var LoggerInterface
     */
    private $logger;
    private $relayLastUsedTimes = [];

    public function __construct(\Swift_Transport $defaultTransport, array $relays, \Memcached $memcached, ParameterRepository $parameterRepository, LoggerInterface $logger)
    {
        $this->relays = $relays;
        $this->defaultTransport = $defaultTransport;
        $this->memcached = $memcached;
        $this->parameterRepository = $parameterRepository;
        $this->logger = $logger;
    }

    public function getTransportByOptions(array $options): \Swift_Transport
    {
        if (isset($options[Mailer::OPTION_RELAY])) {
            if (isset($options[Mailer::OPTION_TRANSPORT])) {
                throw new \Exception("relay and transport options are mutually exclusive");
            }

            if (empty($this->relays[$options[Mailer::OPTION_RELAY]])) {
                throw new \Exception("Unknown relay: " . $options[Mailer::OPTION_RELAY] . ", specify one of: " . implode(", ", array_keys($this->relays)));
            }

            return $this->getRelayTransport($options[Mailer::OPTION_RELAY]);
        }

        return $this->getTransportByFlags($options);
    }

    private function getRelayTransport(string $relayName): \Swift_Transport
    {
        $this->logger->debug("getRelayTransport: $relayName");

        // We do not want to use relay after it was sitting idle for 30 seconds or more. There is a chance of socket timeout and error:
        // fwrite(): send of 6 bytes failed with errno=32 Broken pipe (uncaught exception) at /www/awardwallet/vendor/swiftmailer/swiftmailer/lib/classes/Swift/Transport/StreamBuffer.php line 223
        if (isset($this->relayTransports[$relayName]) && (time() - $this->relayLastUsedTimes[$relayName]) < 30) {
            $this->relayLastUsedTimes[$relayName] = time();

            return $this->relayTransports[$relayName];
        }

        $relay = $this->relays[$relayName];
        $transport = new \Swift_SmtpTransport($relay['host'], $relay['port']);
        $this->logger->debug("created relay $relayName: {$relay['host']}:{$relay['port']}");

        if (!empty($relay['user'])) {
            $transport->setUsername($relay['user']);
        }

        if (!empty($relay['password'])) {
            $transport->setPassword($relay['password']);
        }

        if (!empty($relay['encryption'])) {
            $transport->setEncryption($relay['encryption']);
        }
        $transport->registerPlugin(new \Swift_Plugins_AntiFloodPlugin(1000));

        $this->relayTransports[$relayName] = $transport;
        $this->relayLastUsedTimes[$relayName] = time();

        return $transport;
    }

    private function getTransportByFlags(array $options): \Swift_Transport
    {
        $nonTransactional = isset($options[Mailer::OPTION_TRANSACTIONAL]) && $options[Mailer::OPTION_TRANSACTIONAL] === false;
        $noDirect = isset($options[Mailer::OPTION_DIRECT]) && $options[Mailer::OPTION_DIRECT] === false;

        $forcedRelay = $this->getForcedRelay();

        if ($forcedRelay !== '') {
            $this->logger->info("forced relay: {$forcedRelay}");

            if ($nonTransactional && !empty($this->relays['nontransactional_' . $forcedRelay])) {
                return $this->getRelayTransport('nontransactional_' . $forcedRelay);
            }

            if (!empty($this->relays['transactional_' . $forcedRelay])) {
                return $this->getRelayTransport('transactional_' . $forcedRelay);
            }
        }

        if ($nonTransactional && !empty($this->relays['nontransactional']) && $noDirect) {
            return $this->getRelayTransport('nontransactional');
        }

        if ($nonTransactional && !empty($this->relays['nontransactional_direct']) && !$noDirect) {
            return $this->getRelayTransport('nontransactional_direct');
        }

        if (!$nonTransactional && !empty($this->relays['transactional_direct']) && !$noDirect) {
            return $this->getRelayTransport('transactional_direct');
        }

        return $this->defaultTransport;
    }

    private function getForcedRelay(): string
    {
        $time = time();

        if (($this->lastRelayCheck + 60) > $time) {
            return $this->cachedForcedRelay;
        }

        $result = $this->memcached->get(self::RELAY_PARAM_NAME);

        if ($result === false) {
            $result = $this->parameterRepository->getParam(self::RELAY_PARAM_NAME, '');
            $this->memcached->set(self::RELAY_PARAM_NAME, $result, 60);
        }

        $this->lastRelayCheck = $time;
        $this->cachedForcedRelay = $result;

        return $result;
    }
}
