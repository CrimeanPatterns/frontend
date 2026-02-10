<?php

namespace AwardWallet\MainBundle\Email;

use Symfony\Component\HttpFoundation\Request;

class Api
{
    public const TIMEOUT = 1000;

    protected $adminProxyHeaders = [
        'Email-Admin-Title' => '',
        'Email-Admin-No-Html' => '',
        'Content-Type' => '',
        'Content-Disposition' => '',
        'Location' => '',
    ];

    protected $config;

    public function __construct($config)
    {
        $this->config = $config;
    }

    public function call($name, $isPost, $data = [], $params = [], $dataEncoded = false, $region = null, $headers = [])
    {
        if ($region === null) {
            $region = array_keys($this->config)[0];
        }
        $baseurl = $this->config[$region]['url'];
        $auth = $this->config[$region]['http_auth'];
        $url = $baseurl . "/" . $name;

        if (!empty($params)) {
            $url .= "?";

            foreach ($params as $name => $val) {
                if (!empty($val)) {
                    $url .= $name . "=" . urlencode($val) . "&";
                }
            }
            $url = trim($url, '&');
        }
        $query = curl_init($url);

        if (!$query) {
            return false;
        }
        curl_setopt($query, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
        curl_setopt($query, CURLOPT_CONNECTTIMEOUT, self::TIMEOUT);
        curl_setopt($query, CURLOPT_TIMEOUT, self::TIMEOUT);
        curl_setopt($query, CURLOPT_HEADER, false);
        curl_setopt($query, CURLOPT_FAILONERROR, false);
        curl_setopt($query, CURLOPT_DNS_USE_GLOBAL_CACHE, false);
        curl_setopt($query, CURLOPT_RETURNTRANSFER, true);

        if (!empty($headers)) {
            curl_setopt($query, CURLOPT_HTTPHEADER, $headers);
        }

        //		curl_setopt( $query, CURLOPT_SSL_VERIFYPEER, false);
        if ($isPost) {
            curl_setopt($query, CURLOPT_POST, true);
            curl_setopt($query, CURLOPT_POSTFIELDS, $dataEncoded ? $data : json_encode($data));
        }
        curl_setopt($query, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
        curl_setopt($query, CURLOPT_USERPWD, $auth);
        $result = curl_exec($query);
        $code = curl_getinfo($query, CURLINFO_HTTP_CODE);
        $error = curl_error($query);
        curl_close($query);

        if ($result === false) {
            throw new ApiException("Curl request failed: $error");
        }

        if ($code !== 200) {
            throw new ApiException("API call failed. " . (($code === 400) ? $result : "HTTP code {$code}. {$result}"));
        }
        $response = @json_decode($result, true);

        if ($response === null) {
            throw new ApiException("Could not decode JSON from API");
        }

        return $response;
    }

    public function proxyCall($url, Request $request, &$responseHeaders, $requestHeaders = [], $region = null)
    {
        if ($region === null) {
            $region = array_keys($this->config)[0];
        }
        $baseurl = $this->config[$region]['url'];
        $auth = $this->config[$region]['http_auth'];
        $url = $baseurl . '/' . ltrim($url, '/');
        $query = curl_init($url);

        if (!$query) {
            throw new ApiException('Invalid url', 400);
        }
        curl_setopt($query, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
        curl_setopt($query, CURLOPT_CONNECTTIMEOUT, self::TIMEOUT);
        curl_setopt($query, CURLOPT_TIMEOUT, self::TIMEOUT);
        curl_setopt($query, CURLOPT_HEADER, true);
        curl_setopt($query, CURLOPT_FAILONERROR, false);
        curl_setopt($query, CURLOPT_DNS_USE_GLOBAL_CACHE, false);
        curl_setopt($query, CURLOPT_RETURNTRANSFER, true);

        if (count($requestHeaders) > 0) {
            curl_setopt($query, CURLOPT_HTTPHEADER, $requestHeaders);
        }

        if (strcasecmp($request->getMethod(), 'post') === 0) {
            curl_setopt($query, CURLOPT_POST, true);
            curl_setopt($query, CURLOPT_POSTFIELDS, $request->getContent());
        }
        curl_setopt($query, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
        curl_setopt($query, CURLOPT_USERPWD, $auth);
        $result = curl_exec($query);
        $code = curl_getinfo($query, CURLINFO_HTTP_CODE);
        $error = curl_error($query);
        curl_close($query);

        if ($result === false) {
            throw new ApiException("Curl request failed: $error", 500);
        }

        [$responseHeaders, $body] = explode("\r\n\r\n", $result, 2);

        if (stripos($responseHeaders, 'HTTP/1.1 100 Continue') === 0) {
            [$responseHeaders, $body] = explode("\r\n\r\n", $body, 2);
        }

        if (!in_array($code, [200, 302])) {
            throw new ApiException($body, $code);
        }

        $this->parseHeaders($responseHeaders);

        return $body;
    }

    // doesnt actually parse http headers, just extracts ones we are looking for
    // no multiline
    protected function parseHeaders(&$headers)
    {
        $parsed = $this->adminProxyHeaders;
        $headers = explode("\r\n", $headers);

        foreach ($headers as $line) {
            if (preg_match('/^([^:]+):(.+)$/', $line, $m) && isset($parsed[$m[1]])) {
                $parsed[$m[1]] = trim($m[2]);
            }
        }
        $headers = $parsed;
    }
}
