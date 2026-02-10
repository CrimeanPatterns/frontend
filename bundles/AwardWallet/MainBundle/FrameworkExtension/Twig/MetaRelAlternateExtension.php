<?php

namespace AwardWallet\MainBundle\FrameworkExtension\Twig;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

class MetaRelAlternateExtension extends \Twig_Extension
{
    public const CACHE_KEY = 'metaRelAlternateRoutes';

    public const ANDROID = 'android';

    /**
     * @var Request
     */
    private $request;

    /**
     * @var string
     */
    private $scheme;

    /**
     * @var string
     */
    private $host;

    /**
     * @var array
     */
    private $config = [
        self::ANDROID => [],
    ];
    /**
     * @var \Memcached
     */
    private $memcached;

    public function __construct(RequestStack $requestStack, $scheme, $host, $configPath, \Memcached $memcached)
    {
        $this->request = $requestStack->getMasterRequest();
        $this->scheme = $scheme;
        $this->host = $host;
        $this->memcached = $memcached;

        $this->loadConfig($configPath);
    }

    public function getFunctions()
    {
        return [
            new \Twig_SimpleFunction('meta_android_alternate', [$this, 'androidAlternate'], ['is_safe' => ['html']]),
        ];
    }

    public function androidAlternate()
    {
        if (!$this->request || !isset($this->config[self::ANDROID][$this->request->get('_route')])) {
            return;
        }
        $path = $this->request->getRequestUri();

        return "<link rel=\"alternate\" href=\"android-app://com.itlogy.awardwallet/{$this->scheme}/{$this->host}{$path}\" />";
    }

    private function loadConfig($configFile)
    {
        $this->config = $this->memcached->get(self::CACHE_KEY);

        if ($this->config === false) {
            $xml = simplexml_load_file($configFile);
            $this->config = [
                self::ANDROID => [],
            ];

            foreach ($xml->xpath("//path") as $route) {
                $attr = $route->attributes();
                $excludeAndroid = isset($attr['exclude-platform']) && strval($attr['exclude-platform']) === "android";

                foreach ($route->xpath("./route") as $routeName) {
                    $routeName = (string) $routeName;

                    if (!$excludeAndroid && !isset($this->config[self::ANDROID][$routeName])) {
                        $this->config[self::ANDROID][$routeName] = $routeName;
                    }
                }
            }
            $this->memcached->set(self::CACHE_KEY, $this->config, 3600);
        }
    }
}
