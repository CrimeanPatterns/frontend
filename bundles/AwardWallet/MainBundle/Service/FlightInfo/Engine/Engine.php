<?php

namespace AwardWallet\MainBundle\Service\FlightInfo\Engine;

use AwardWallet\MainBundle\DependencyInjection\Annotation\NoDI;
use AwardWallet\MainBundle\Service\FlightInfo\Exceptions\HttpRequestException;
use Psr\Log\LoggerInterface;

/**
 * @NoDI()
 */
class Engine implements EngineInterface
{
    protected $logger;
    protected $statLogger;
    protected $request_timeout;

    public function __construct($request_timeout, LoggerInterface $logger, LoggerInterface $statLogger)
    {
        $this->request_timeout = $request_timeout;
        $this->logger = $logger;
        $this->statLogger = $statLogger;
    }

    public function send(HttpRequest $request)
    {
        $curl = curl_init();

        try {
            $response = $this->exec($curl, $request);

            if ($response === false) {
                $this->logger->error("request error: " . curl_error($curl), ['request' => $request->getDescription()]);

                throw new HttpRequestException();
            }

            $ret = $this->process($curl, $response);
        } finally {
            curl_close($curl);
        }

        return $ret;
    }

    protected function createHeaders($headers)
    {
        return array_map(function ($k, $v) {return str_replace('_', '-', $k) . ': ' . $v; }, array_keys($headers), array_values($headers));
    }

    protected function parseHeaders($headers)
    {
        $fields = explode("\r\n", preg_replace('/\x0D\x0A[\x09\x20]+/', ' ', $headers));

        if (empty($fields)) {
            return [];
        }

        return array_reduce($fields, function ($carry, $field) {
            $match = [];

            if (!preg_match('/([^:]+): (.+)/m', $field, $match)) {
                return $carry;
            }
            $match[1] = preg_replace_callback('/(?<=^|[\x09\x20\x2D])./', function ($matches) {
                return strtoupper($matches[0]);
            }, strtolower(trim($match[1])));
            $carry[$match[1]] = isset($carry[$match[1]]) ? [$carry[$match[1]], $match[2]] : trim($match[2]);

            return $carry;
        }, []);
    }

    /**
     * @return string|null
     */
    protected function exec($curl, HttpRequest $request)
    {
        $headers = $request->getHeaders();

        if (!is_array($headers)) {
            $headers = [];
        }
        $headers = array_merge(['Content-Type' => 'application/json; charset=utf-8'], $headers);
        $headers = $this->createHeaders($headers);

        $this->logCallStat($request->getUrl());

        curl_setopt_array($curl, [
            CURLOPT_URL => $request->getUrl(),
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS => 3,
            CURLOPT_HEADER => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => $this->request_timeout,
            CURLOPT_HTTPHEADER => $headers,
        ]);

        if ($request->isPostRequest()) {
            curl_setopt($curl, CURLOPT_POST, true);
            curl_setopt($curl, CURLOPT_POSTFIELDS, $request->getPost());
        }
        $response = curl_exec($curl);

        return $response;
    }

    protected function logCallStat($url)
    {
        if (stripos($url, 'https://api.flightstats.com/flex/flightstatus/rest/v2/json/flight/status') === 0) {
            $this->statLogger->info('FlightStats call', [
                'app' => 'frontend',
                'partner' => 'awardwallet',
                'api' => 'FlightStatusByFlight',
                'reasons' => [],
            ]);
        } else {
            $this->logger->warning('Unknown FS method', ['url' => $url]);
        }
    }

    /**
     * @return HttpResponse
     */
    protected function process($curl, $response)
    {
        $responseCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $headerSize = curl_getinfo($curl, CURLINFO_HEADER_SIZE);
        $content = substr($response, $headerSize);
        $content = empty($content) ? '' : $content;
        $headers = substr($response, 0, $headerSize);
        $headers = $this->parseHeaders($headers);

        return (new HttpResponse())
            ->setCode($responseCode)
            ->setHeaders($headers)
            ->setContent($content);
    }
}
