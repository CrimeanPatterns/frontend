<?php

namespace AwardWallet\MainBundle\Service\FlightInfo\Engine;

use AwardWallet\MainBundle\DependencyInjection\Annotation\NoDI;

/**
 * @NoDI()
 */
class HttpRequest
{
    public const METHOD_GET = 'GET';
    public const METHOD_POST = 'POST';
    public const DEFAULT_METHOD = self::METHOD_GET;

    /** @var string */
    private $method;

    /** @var string */
    private $url;

    /** @var array */
    private $post;

    /** @var array */
    private $headers;

    /** @var string */
    private $description;

    /** @var string */
    private $service;

    /**
     * @param string $method
     */
    public function __construct($method = self::DEFAULT_METHOD)
    {
        $this->method = self::DEFAULT_METHOD;

        $this->setMethod($method);
    }

    /**
     * @param string $url
     * @return HttpRequest
     */
    public function setUrl($url)
    {
        $this->url = (string) $url;

        return $this;
    }

    /**
     * @return string
     */
    public function getUrl()
    {
        return $this->url;
    }

    /**
     * @param array|string $post
     * @return HttpRequest
     */
    public function setPost($post)
    {
        $this->post = $post;

        return $this;
    }

    /**
     * @return array|string
     */
    public function getPost()
    {
        return $this->post;
    }

    /**
     * @param string $key
     * @param string $value
     * @return HttpRequest
     */
    public function addPostItem($key, $value)
    {
        $this->post[(string) $key] = (string) $value;

        return $this;
    }

    /**
     * @param string $key
     * @return HttpRequest
     */
    public function removePostItem($key)
    {
        unset($this->post[(string) $key]);

        return $this;
    }

    /**
     * @param string $key
     */
    public function getPostItem($key)
    {
        return $this->post[(string) $key];
    }

    /**
     * @param array $headers
     * @return HttpRequest
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
     * @param string $key
     * @param string $value
     * @return HttpRequest
     */
    public function addHeader($key, $value)
    {
        $this->headers[(string) $key] = (string) $value;

        return $this;
    }

    /**
     * @param string $key
     * @return HttpRequest
     */
    public function removeHeader($key)
    {
        unset($this->headers[(string) $key]);

        return $this;
    }

    /**
     * @param string $key
     */
    public function getHeader($key)
    {
        return $this->headers[(string) $key];
    }

    /**
     * @param string $description
     * @return HttpRequest
     */
    public function setDescription($description)
    {
        $this->description = (string) $description;

        return $this;
    }

    /**
     * @return string
     */
    public function getDescription()
    {
        return $this->description ?: $this->getUrl();
    }

    /**
     * @param string $method
     * @return HttpRequest
     */
    public function setMethod($method)
    {
        $method = strtoupper($method);

        if (in_array($method, $this->allowedMethods())) {
            $this->method = $method;
        }

        return $this;
    }

    /**
     * @return string
     */
    public function getMethod()
    {
        return $this->method;
    }

    /**
     * @param string $service
     * @return HttpRequest
     */
    public function setService($service)
    {
        $this->service = $service;

        return $this;
    }

    /**
     * @return string
     */
    public function getService()
    {
        return $this->service;
    }

    /**
     * @return bool
     */
    public function isPostRequest()
    {
        return $this->getMethod() === HttpRequest::METHOD_POST;
    }

    private function allowedMethods()
    {
        return [self::METHOD_GET, self::METHOD_POST];
    }
}
