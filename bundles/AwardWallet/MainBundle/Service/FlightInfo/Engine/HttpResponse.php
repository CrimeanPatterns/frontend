<?php

namespace AwardWallet\MainBundle\Service\FlightInfo\Engine;

use AwardWallet\MainBundle\DependencyInjection\Annotation\NoDI;
use AwardWallet\MainBundle\Entity\FlightInfoLog;

/**
 * @NoDI()
 */
class HttpResponse
{
    /** @var string */
    private $code;

    /** @var string */
    private $content;

    /** @var array */
    private $headers;

    /** @var array */
    private $_json;

    public function __construct()
    {
    }

    /**
     * @param string $code
     * @return HttpResponse
     */
    public function setCode($code)
    {
        $this->code = $code;

        return $this;
    }

    /**
     * @return string
     */
    public function getCode()
    {
        return $this->code;
    }

    /**
     * @param string $content
     * @return HttpResponse
     */
    public function setContent($content)
    {
        $this->content = trim($content);
        $this->_json = null;

        return $this;
    }

    /**
     * @return string
     */
    public function getContent()
    {
        return $this->content;
    }

    /**
     * @param array $headers
     * @return HttpResponse
     */
    public function setHeaders($headers)
    {
        $this->headers = $headers;

        return $this;
    }

    /**
     * @return array
     */
    public function getHeaders()
    {
        return $this->headers;
    }

    /**
     * @return array|bool|mixed
     */
    public function getJSON()
    {
        if (!is_null($this->_json)) {
            return $this->_json;
        }

        if (empty($this->content)) {
            return false;
        }

        // jsonp?
        if (!in_array(substr($this->content, 0, 1), ['{', '['])) {
            $content = $this->content;
            $open = strpos($content, '(');
            $close = strrpos($content, ')');

            if ($open !== false && $close !== false && $open < $close) {
                $content = trim(substr($content, $open + 1, $close - $open - 1));
                $this->_json = json_decode($content, true);
            } else {
                $this->_json = false;
            }
        } else {
            $this->_json = json_decode($this->content, true);
        }

        return $this->_json;
    }

    /**
     * @param CacheStorageInterface $cache
     * @return HttpResponse
     */
    public static function createFromCache($cache)
    {
        $response = $cache->getResponse();

        return (new self())->setCode($response['code'])->setHeaders($response['headers'])->setContent($response['content']);
    }

    /**
     * @param CacheStorageInterface $cache
     * @return HttpResponse
     */
    public function fillFromCache($cache)
    {
        $response = $cache->getResponse();

        return $this->setCode($response['code'])->setHeaders($response['headers'])->setContent($response['content']);
    }

    /**
     * @param CacheStorageInterface $cache
     * @return FlightInfoLog
     */
    public function saveToCache($cache)
    {
        $data = [
            'code' => $this->getCode(),
            'headers' => $this->getHeaders(),
            'content' => $this->getContent(),
        ];
        $cache->setResponse($data);

        return $cache;
    }
}
