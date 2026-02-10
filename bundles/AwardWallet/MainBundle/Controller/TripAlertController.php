<?php

namespace AwardWallet\MainBundle\Controller;

use AwardWallet\Common\Monolog\Handler\FluentHandler;
use AwardWallet\MainBundle\Service\AirportTerminalMatcher\FlightStatsWriter;
use AwardWallet\MainBundle\Service\FlightStats\TripAlerts\API\Model\Alert;
use AwardWallet\MainBundle\Service\FlightStats\TripAlerts\API\ObjectSerializer;
use AwardWallet\MainBundle\Service\FlightStats\TripAlerts\Receiver;
use AwardWallet\MainBundle\Service\FlightStats\TripAlerts\Subscriber;
use AwardWallet\Strings\Strings;
use Doctrine\DBAL\Driver\Connection as ConnectionInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class TripAlertController extends AbstractController
{
    /**
     * @Route("/callback/tripalert/{userId}", name="aw_tripalert_callback", requirements={"userId" = "\d+"})
     * @return Response
     */
    public function callbackAction(
        $userId,
        Request $request,
        LoggerInterface $securityLogger,
        LoggerInterface $tripalertsLogger,
        FlightStatsWriter $flightStatsWriter,
        Receiver $receiver,
        ConnectionInterface $connection,
        LoggerInterface $fsstatLogger,
        \Memcached $memcached,
        string $secretParam
    ) {
        $logger = $tripalertsLogger;
        $content = json_decode($request->getContent());

        $authToken = null;

        // Sometimes flightstats will send AUTHTOKEN and sometimes AuthToken, wtf
        if (!empty($content->trip->attributes->AUTHTOKEN)) {
            $authToken = $content->trip->attributes->AUTHTOKEN;
            $content->trip->attributes->AUTHTOKEN = "hidden, " . strlen($authToken) . " chars";
        }

        if (!empty($content->trip->attributes->AuthToken)) {
            $authToken = $content->trip->attributes->AuthToken;
            $content->trip->attributes->AuthToken = "hidden, " . strlen($authToken) . " chars";
        }

        if ($authToken != sha1($userId . $secretParam . Subscriber::SALT)) {
            $message = "tripalert with invalid AuthToken, resubscribe, userId: {$userId}, token: " . Strings::cutInMiddle($authToken, 2);
            $logger->warning($message, ["content" => $content]);
            $securityLogger->warning($message);
            $connection->executeUpdate("update Usr set TripAlertsHash = 'invalid_auth' where UserID = :userId", ['userId' => $userId]);

            return new Response("ok");
        }

        $logger->info("received tripalert", [
            FluentHandler::MAX_RECURSION_LEVEL_KEY => 15, // want full content of alert
            "userId" => $userId,
            "content" => $content,
            "alertType" => $content->alertDetails->type ?? null,
        ]);
        $fsstatLogger->info('FlightStats call', [
            'app' => 'frontend',
            'partner' => 'awardwallet',
            'api' => 'TripAlertReceive',
            'reasons' => [],
        ]);

        /** @var Alert $alert */
        $alert = ObjectSerializer::deserialize($content, Alert::class);
        $cacheKey = "ta_processed_" . $alert->getId();

        if ($memcached->get($cacheKey) !== false) {
            $logger->info("tripalert " . $alert->getId() . " is already processed", ["userId" => $userId]);

            return new Response("ok");
        }

        $receiver->process($userId, $alert);
        $this->getDoctrine()->getManager()->flush();
        $memcached->set($cacheKey, time(), 3600);
        $flightStatsWriter->write($alert);

        return new Response("ok");
    }
}
