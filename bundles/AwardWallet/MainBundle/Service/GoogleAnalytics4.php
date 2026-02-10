<?php

namespace AwardWallet\MainBundle\Service;

use AwardWallet\MainBundle\Globals\LoggerContext\Context;
use AwardWallet\MainBundle\Globals\LoggerContext\ContextAwareLoggerWrapper;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Psr\Log\LoggerInterface;

class GoogleAnalytics4
{
    public const PLATFORM_IOS = 'ios';
    public const PLATFORM_ANDROID = 'android';
    public const PLATFORM_WEB = 'web';
    private const BASE_URL = 'https://www.google-analytics.com/mp/collect';

    private Client $guzzleClient;
    private string $trackingId;
    private array $config;
    private LoggerInterface $logger;

    public function __construct(
        Client $guzzleClientDefault,
        LoggerInterface $logger,
        string $googleAnalyticsTrackingIdGa4,
        array $googleAnalyticsMeasurementProtocol
    ) {
        $this->guzzleClient = $guzzleClientDefault;
        $this->trackingId = $googleAnalyticsTrackingIdGa4;
        $this->config = $googleAnalyticsMeasurementProtocol;
        $this->logger =
            (new ContextAwareLoggerWrapper($logger))
            ->setMessagePrefix('ga4: ')
            ->pushContext([Context::SERVER_MODULE_KEY => 'google_analytics_4'])
            ->withTypedContext();
    }

    public function sendEvents(string $platform, int $userId, $events)
    {
        try {
            $response = $this->guzzleClient->post(
                self::BASE_URL . '?' .
                \http_build_query([
                    'api_secret' => $this->config[$platform]['secret'],
                    'measurement_id' => $this->trackingId,
                ]),
                ['json' => [
                    'client_id' => "{$userId}",
                    'user_id' => "{$userId}",
                    'events' => $events,
                ]]
            );
        } catch (GuzzleException $e) {
            $this->logger->warning('fail', [
                'ga_response' => 'exception: ' . $e->getMessage(),
            ]);

            return;
        }

        $this->logger->info('success', [
            'http_status_code' => $response->getStatusCode(),
            'http_body' => (string) $response->getBody(),
        ]);
    }
}
