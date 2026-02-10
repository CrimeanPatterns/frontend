<?php

namespace AwardWallet\MainBundle\Service\FlightStats\TripAlerts;

use AwardWallet\MainBundle\Service\FlightStats\TripAlerts\API\Api\TripImportApi;
use AwardWallet\MainBundle\Service\FlightStats\TripAlerts\API\Api\TripStatusApi;
use AwardWallet\MainBundle\Service\FlightStats\TripAlerts\API\ApiException;
use AwardWallet\MainBundle\Service\FlightStats\TripAlerts\API\Model\Alerting;
use AwardWallet\MainBundle\Service\FlightStats\TripAlerts\API\Model\DirectApiDelivery;
use AwardWallet\MainBundle\Service\FlightStats\TripAlerts\API\Model\TripImport;
use AwardWallet\MainBundle\Service\FlightStats\TripAlerts\API\Model\TripImportResponse;
use Psr\Log\LoggerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;

class Subscriber
{
    public const SALT = 'hf3u2gh2o3sno';

    /**
     * @var string
     */
    private $appId;
    /**
     * @var string
     */
    private $appKey;
    /**
     * @var TripImportApi
     */
    private $api;
    /**
     * @var RouterInterface
     */
    private $router;
    /**
     * @var LoggerInterface
     */
    private $logger;
    /**
     * @var string
     */
    private $secret;
    /**
     * @var string
     */
    private $suffix;
    /**
     * @var LoggerInterface
     */
    private $fsLogger;

    public function __construct(string $tripAlertsAppId, $tripAlertsAppKey, RouterInterface $router, LoggerInterface $tripAlertsLogger, $secret, LoggerInterface $fsLogger)
    {
        $this->appId = $tripAlertsAppId;
        $this->appKey = $tripAlertsAppKey;
        $this->api = new TripImportApi();
        $this->router = $router;
        $this->logger = $tripAlertsLogger;
        $this->secret = $secret;
        $this->fsLogger = $fsLogger;
    }

    public function subscribe(array $flights, $userId)
    {
        $alerting = new Alerting();
        $travelerDelivery = new DirectApiDelivery();
        $travelerDelivery
            ->setAlertSetting('Traveler')
            ->setUrl($this->router->generate("aw_tripalert_callback", ["userId" => $userId], UrlGeneratorInterface::ABSOLUTE_URL))
        ;
        $statusDelivery = new DirectApiDelivery();
        $statusDelivery
            ->setAlertSetting('Status')
            ->setUrl($this->router->generate("aw_tripalert_callback", ["userId" => $userId], UrlGeneratorInterface::ABSOLUTE_URL))
        ;
        $alerting
            ->setDirectApiDeliveries([$travelerDelivery, $statusDelivery])
        ;

        $tripImport = new TripImport();
        $tripImport
            ->setAttributes([
                'CUSTOMER_ID' => 'AwardWallet_Test',
                'AuthToken' => sha1($userId . $this->secret . self::SALT),
            ])
            ->setReferenceNumber($this->getReferenceNumber($userId))
            ->setAlerting($alerting)
        ;

        // cancelling previous subscription due to FS bug:
        // Trip Reference ID USER-183556 was imported and used several times in the past, so the alerts you received
        // contained old flights that had been used in previous versions of the trip. We're currently working on
        // improvements to the system to handle cases like these. In the meantime, we recommend using the "Cancelled"
        // flag in a Trips Status API request to cancel the trip first before reusing the same Trip ID.
        $tripImport
            ->setCancelled(true)
            ->setFlights([])
        ;
        $response = $this->callApi($tripImport);
        $success = $response->getStatus() === "Success";
        $this->logger->info("subscription cancelled", ["success" => $success, "responseObject" => json_decode((string) $response, true)]);

        $this->logger->info("subscribing to plan", ["userId" => $userId, "segmentsCount" => count($tripImport->getFlights())]);
        $tripImport
            ->setCancelled(false)
            ->setFlights($flights)
        ;
        $this->fsLogger->info('FlightStats call', [
            'app' => 'frontend',
            'partner' => 'awardwallet',
            'api' => 'TripAlertSubscribe',
            'reasons' => [],
        ]);
        $response = $this->callApi($tripImport);
        $success = $response->getStatus() === "Success";
        $this->logger->info("subscription created", ["success" => $success, "responseObject" => json_decode((string) $response, true)]);

        return $success;
    }

    public function get($userId)
    {
        $api = new TripStatusApi();
        $response = $api->getTrip($this->appId, $this->appKey, $this->getReferenceNumber($userId));

        return $response;
    }

    private function callApi(TripImport $tripImport): TripImportResponse
    {
        // workaround about "invalid resource type" exception, when passing array like [$tripImport]
        $httpBody = "[" . $tripImport->__toString() . "]";
        file_put_contents("/tmp/taRequest", $httpBody);
        $this->logger->info("flightstats call data: " . $httpBody);

        try {
            $responses = $this->api->setTrip($httpBody, $this->appId, $this->appKey);

            if (count($responses) > 0 && reset($responses) === null) {
                $this->logger->info("got null response, retry");

                throw new ApiException("Null response");
            }
        } catch (ApiException $exception) {
            $this->logger->info("api exception: " . $exception->getMessage() . ", retrying", ["trace" => $exception->getTraceAsString()]);

            if (stripos($exception->getMessage(), "SSL") !== false) {
                sleep(3);
                $responses = $this->api->setTrip($httpBody, $this->appId, $this->appKey);
            } else {
                throw $exception;
            }
        }

        return array_shift($responses);
    }

    private function getReferenceNumber(int $userId)
    {
        if ($this->suffix === null) {
            $homeUlr = $this->router->generate("aw_home", [], UrlGeneratorInterface::ABSOLUTE_URL);

            if ($homeUlr !== "https://awardwallet.com/") {
                $this->suffix = "-" . parse_url($homeUlr, PHP_URL_HOST);
            }
        }

        return 'user-' . $userId . $this->suffix;
    }
}
