<?php

namespace AwardWallet\MainBundle\FrameworkExtension\Ssm;

use Aws\Ssm\SsmClient;
use Monolog\Handler\PsrHandler;
use Monolog\Logger;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\EnvVarProcessorInterface;

class SSMParameterStoreEnvVarProcessor implements EnvVarProcessorInterface
{
    private const CACHE_KEY = 'ssm_params';

    /**
     * @var SsmClient
     */
    private $ssmClient;
    /**
     * @var string
     */
    private $defaultPath;
    /**
     * @var LoggerInterface
     */
    private $logger;

    private $cache = [];
    /**
     * @var Cache
     */
    private $shmCache;
    private bool $fake;

    public function __construct(SsmClient $ssmClient, string $defaultPath, LoggerInterface $logger, Cache $cache, bool $fake)
    {
        $this->ssmClient = $ssmClient;
        $this->defaultPath = $defaultPath;
        $this->logger = new Logger('ssm', [new PsrHandler($logger)], [function (array $record) {
            $record['extra']['service'] = 'ssm';

            return $record;
        }]);
        $this->shmCache = $cache;
        $this->fake = $fake;
    }

    /**
     * @return string[] The PHP-types managed by getEnv(), keyed by prefixes
     */
    public static function getProvidedTypes()
    {
        return [
            'ssm' => 'string',
        ];
    }

    /**
     * Returns the value of the given variable as managed by the current instance.
     *
     * @param string   $prefix The namespace of the variable
     * @param string   $name   The name of the variable within the namespace
     * @param \Closure $getEnv A closure that allows fetching more env vars
     * @throws \Symfony\Component\DependencyInjection\Exception\RuntimeException on error
     */
    public function getEnv($prefix, $name, \Closure $getEnv)
    {
        if ($this->fake) {
            $this->logger->debug("returning fake value for param " . $name);

            return 'ssm_fake_value';
        }

        if (count($this->cache) === 0) {
            $this->warmupCache();
        }

        // EnvPlaceholderParameterBag.php line 39: only "word" characters are allowed.
        $name = str_replace('__', '/', $name);

        if (substr($name, 0, 1) !== '/') {
            $name = $this->defaultPath . "/" . $name;
        }

        $value = $this->cache[$name] ?? null;

        if ($value !== null) {
            return $value;
        }

        $this->logger->debug("reading param " . $name);
        $value = $this->ssmClient->getParameter(['Name' => $name, 'WithDecryption' => true])->search('Parameter.Value');
        $this->cache[$name] = $value;
        $this->saveCacheToApcu();

        return $value;
    }

    private function warmupCache()
    {
        $this->readCacheFromApcu();

        if (count($this->cache) === 0) {
            $this->cache = $this->shmCache->get();
            $this->saveCacheToApcu();
        }
    }

    private function readCacheFromApcu()
    {
        $cache = apcu_fetch(self::CACHE_KEY, $success);

        if ($success && count($cache) > count($this->cache)) {
            $this->cache = $cache;
        }
    }

    private function saveCacheToApcu(): void
    {
        if (!apcu_store(self::CACHE_KEY, $this->cache)) {
            throw new \Exception("failed to record cache");
        }
    }
}
