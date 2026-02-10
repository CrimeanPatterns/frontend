<?php

namespace AwardWallet\MainBundle\Service\FlightInfo\Request;

use AwardWallet\MainBundle\Service\FlightInfo\Engine\CacherInterface;
use AwardWallet\MainBundle\Service\FlightInfo\Engine\EngineInterface;
use AwardWallet\MainBundle\Service\FlightInfo\Exceptions\Exception;

abstract class CommonRequestFactory
{
    /** @var EngineInterface */
    protected $engine;

    /** @var CacherInterface */
    protected $cacher;

    protected $cache;

    protected $namespace;

    protected $classMap = [];

    /**
     * @param string $class
     * @return RequestInterface
     * @throws Exception
     */
    public function create($class)
    {
        if (substr($class, 0, 1) != '\\') {
            $class = $this->resolve($class);
        }

        if (strpos($class, $this->namespace) !== 0) {
            throw new Exception($class . ' namespace must be ' . $this->namespace);
        }

        if (!class_exists($class)) {
            throw new Exception('Class ' . $class . ' not exists');
        }
        $request = new $class();

        if (!($request instanceof RequestInterface)) {
            throw new Exception($class . ' must be instance of RequestInterface');
        }

        $request->setEngine($this->engine);

        if ($this->cacher && ($request instanceof CachedRequestInterface)) {
            $request->setCacher($this->cacher);
        }

        return $request;
    }

    /**
     * @return array
     */
    public function getSupported()
    {
        return array_keys($this->classMap);
    }

    /**
     * @param string $requestAlias
     * @return string
     */
    protected function resolve($requestAlias)
    {
        if (array_key_exists(strtolower($requestAlias), $this->classMap)) {
            return $this->namespace . '\\' . $this->classMap[strtolower($requestAlias)];
        }

        if (in_array($requestAlias, array_values($this->classMap))) {
            return $this->namespace . '\\' . $requestAlias;
        }

        return $requestAlias;
    }
}
