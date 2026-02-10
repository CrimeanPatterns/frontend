<?php

namespace AwardWallet\MainBundle\Service\FlightInfo\Request;

use AwardWallet\MainBundle\Service\FlightInfo\Engine\CacherInterface;
use AwardWallet\MainBundle\Service\FlightInfo\Engine\CacheStorageInterface;
use AwardWallet\MainBundle\Service\FlightInfo\Engine\EngineInterface;
use AwardWallet\MainBundle\Service\FlightInfo\Engine\HttpResponse;
use AwardWallet\MainBundle\Service\FlightInfo\Exceptions\HttpRequestException;
use AwardWallet\MainBundle\Service\FlightInfo\Exceptions\RequestException;
use AwardWallet\MainBundle\Service\FlightInfo\Response\ResponseInterface;

abstract class CommonRequest
{
    /** @var EngineInterface */
    protected $engine;

    /** @var CacherInterface */
    protected $cacher;

    /** @var string */
    protected $host = '';

    /** @var string */
    protected $url = '';

    /** @var array */
    protected $parameters = [];

    /** @var array */
    protected $values = [];

    /** @var ResponseInterface */
    protected $response;

    /** @var CacheStorageInterface */
    protected $responseCache;

    /**
     * @return $this
     */
    final public function setEngine(EngineInterface $engine)
    {
        $this->engine = $engine;

        return $this;
    }

    /**
     * @return $this
     */
    final public function setCacher(CacherInterface $cacher)
    {
        $this->cacher = $cacher;

        return $this;
    }

    /**
     * @param array $config
     * @return $this
     */
    abstract public function setConfig($config);

    /**
     * @return ResponseInterface
     * @throws HttpRequestException
     */
    public function fetch()
    {
        if (!empty($this->response) && $this->response instanceof ResponseInterface) {
            return $this->response;
        }

        $createDate = null;

        $this->validate();
        /** @var RequestInterface|CommonRequest $this */
        $httpRequest = $this->getHttpRequest();

        try {
            if ($this->cacher) {
                $this->responseCache = $this->cacher->get($httpRequest);

                if ($this->responseCache) {
                    $createDate = $this->responseCache->getCreateDate();
                }
            }

            if (!empty($this->responseCache)) {
                $httpResponse = HttpResponse::createFromCache($this->responseCache);
            } else {
                $httpResponse = $this->engine->send($httpRequest);

                if ($this->cacher) {
                    $this->responseCache = $this->cacher->cache($httpRequest, $httpResponse);
                }
            }
        } catch (HttpRequestException $e) {
            throw $e;
        }

        return $this->resolveHttpResponse($httpResponse, $createDate);
    }

    /**
     * @return bool
     * @throws \Exception
     */
    final public function isValid()
    {
        try {
            $this->validate();
        } catch (RequestException $e) {
            return false;
        } catch (\Exception $e) {
            throw $e;
        }

        return true;
    }

    /**
     * @return array
     */
    public function export()
    {
        $export = [];
        $export['_class'] = (new \ReflectionClass(static::class))->getShortName();

        preg_match_all('/\{(.+?)\}/', $this->url, $fields);

        foreach ($fields[1] as $field) {
            $value = null;

            if (isset($this->values[$field])) {
                $value = $this->values[$field];

                if ($value && array_key_exists($field, $this->parameters) && isset($this->parameters[$field]['secure'])) {
                    $value = '***';
                }
                $export[$field] = $value;
            }
        }

        foreach ($this->parameters as $field => $config) {
            $value = null;

            if (isset($this->values[$field])) {
                $value = $this->values[$field];

                if ($value && array_key_exists($field, $this->parameters) && isset($this->parameters[$field]['secure'])) {
                    $value = '***';
                }
                $export[$field] = $value;
            }
        }

        return $export;
    }

    /**
     * @param bool|false $hideSecure
     * @return string
     * @throws RequestException
     */
    final protected function getHttpRequestUrl($hideSecure = false)
    {
        $url = $this->getApiUrl($hideSecure);
        $options = $this->getParameters('request', $hideSecure);

        if (!empty($options)) {
            $options = array_map(function ($k, $v) {return $k . '=' . rawurlencode($v); }, array_keys($options), array_values($options));
            $options = implode('&', $options);
            $url .= '?' . $options;
        }

        return $this->host . $url;
    }

    /**
     * @param bool|false $hideSecure
     * @return array
     * @throws RequestException
     */
    final protected function getHttpRequestPost($hideSecure = false)
    {
        return $this->getParameters('post', $hideSecure);
    }

    /**
     * @param bool|false $hideSecure
     * @return array
     * @throws RequestException
     */
    final protected function getHttpRequestHeaders($hideSecure = false)
    {
        return $this->getParameters('headers', $hideSecure);
    }

    /**
     * @param bool|false $hideSecure
     * @return string
     * @throws RequestException
     */
    final protected function getApiUrl($hideSecure = false)
    {
        $url = $this->url;

        preg_match_all('/\{(.+?)\}/', $url, $fields);

        foreach ($fields[1] as $field) {
            if (!isset($this->values[$field])) {
                if (array_key_exists($field, $this->parameters) && array_key_exists('default', $this->parameters[$field])) {
                    $value = $this->parameters[$field]['default'];
                } else {
                    throw new RequestException('Unknown parameter in API Url: ' . $field);
                }
            } else {
                $value = $this->values[$field];
            }

            if (is_bool($value)) {
                $value = $value ? 'true' : 'false';
            }

            if ($hideSecure && array_key_exists($field, $this->parameters) && isset($this->parameters[$field]['secure'])) {
                $value = '***';
            }
            $url = str_replace('{' . $field . '}', rawurlencode($value), $url);
        }

        return $url;
    }

    /**
     * @throws RequestException
     */
    protected function validate()
    {
        preg_match_all('/\{(.+?)\}/', $this->url, $fields);

        foreach ($fields[1] as $field) {
            if (!isset($this->values[$field]) && !(array_key_exists($field, $this->parameters) && array_key_exists('default', $this->parameters[$field]))) {
                throw new RequestException('Required parameter: ' . $field);
            }
        }

        foreach ($this->parameters as $field => $config) {
            if (!isset($this->values[$field]) && !array_key_exists('default', $config) && (array_key_exists('required', $config) && !empty($config['required']))) {
                throw new RequestException('Required parameter: ' . $field);
            }
        }
    }

    /**
     * @return bool|mixed
     */
    protected function setCacheState($state)
    {
        if ($this->cacher && $this->responseCache) {
            return $this->cacher->setState($this->responseCache, $state);
        }

        return false;
    }

    /**
     * @return bool|mixed
     */
    protected function setCacheExpire($date)
    {
        if ($this->cacher && $this->responseCache) {
            return $this->cacher->setExpire($this->responseCache, $date);
        }

        return false;
    }

    /**
     * @param string $place
     * @param bool|false $hideSecure
     * @return array
     * @throws RequestException
     */
    final private function getParameters($place, $hideSecure = false)
    {
        $ret = [];

        foreach ($this->parameters as $field => $config) {
            if ($config['place'] != $place) {
                continue;
            }

            if (!isset($this->values[$field])) {
                if (array_key_exists('default', $config)) {
                    continue;
                } else {
                    throw new RequestException('Unknown parameter in API ' . $place . ': ' . $field);
                }
            }
            $default = array_key_exists('default', $config) ? $config['default'] : null;

            if (is_bool($default)) {
                $default = $default ? 'true' : 'false';
            }
            $value = $this->values[$field];

            if (is_bool($value)) {
                $value = $value ? 'true' : 'false';
            }

            if ($hideSecure && array_key_exists($field, $this->parameters) && isset($this->parameters[$field]['secure'])) {
                $value = '***';
            }

            if ($value != $default) {
                $ret[$field] = $value;
            }
        }

        return $ret;
    }
}
