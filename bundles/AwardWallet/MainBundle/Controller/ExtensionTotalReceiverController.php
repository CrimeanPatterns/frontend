<?php

namespace AwardWallet\MainBundle\Controller;

use AwardWallet\MainBundle\FrameworkExtension\AwTokenStorageInterface;
use AwardWallet\MainBundle\Globals\LoggerContext\ContextAwareLoggerWrapper;
use AwardWallet\MainBundle\Service\CardSelector\DTO\ReceiveTotalRequest;
use Doctrine\DBAL\Connection;
use JMS\Serializer\Exception\RuntimeException;
use JMS\Serializer\SerializerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class ExtensionTotalReceiverController
{
    private ContextAwareLoggerWrapper $logger;
    private SerializerInterface $serializer;
    private ValidatorInterface $validator;
    private Connection $connection;

    public function __construct(LoggerInterface $logger, SerializerInterface $serializer, ValidatorInterface $validator, Connection $connection)
    {
        $this->logger = new ContextAwareLoggerWrapper($logger);
        $this->logger->pushContext(['controller' => 'ExtensionTotalReceiver']);
        $this->serializer = $serializer;
        $this->validator = $validator;
        $this->connection = $connection;
    }

    /**
     * @Route("/api/extension/v1/receive", name="aw_extension_total_receiver", methods={"POST"})
     */
    public function receive(Request $request, AwTokenStorageInterface $tokenStorage): Response
    {
        $body = $request->getContent();
        $this->logger->info("received request: " . $body);

        if ($tokenStorage->getUser() === null) {
            $this->logger->info("User not logged in");

            return new JsonResponse(['status' => 'ok']);
        }

        try {
            /** @var ReceiveTotalRequest $totalRequest */
            $totalRequest = $this->serializer->deserialize($body, ReceiveTotalRequest::class, 'json');
        } catch (RuntimeException $exception) {
            $this->logger->warning("could not decode json: " . $exception->getMessage());

            return new JsonResponse(['status' => 'ok']);
        }

        $violations = $this->validator->validate($totalRequest);

        if ($violations->count() > 0) {
            $this->logger->warning("request failed validation: " . $violations->__toString());

            return new JsonResponse(['status' => 'ok']);
        }

        $extensionVersion = $this->convertExtensionVersion($totalRequest->extensionVersion);

        if ($extensionVersion === null) {
            $this->logger->warning("invalid extension version: " . $totalRequest->extensionVersion);

            return new JsonResponse(['status' => 'ok']);
        }

        foreach ($totalRequest->urls as $url) {
            $date = $url->datetime;

            if ($date > time() || $date <= (time() - 300)) {
                // correcting invalid time
                $date = time();
            }

            $this->connection->insert("ReceivedTotal", [
                "UserID" => $tokenStorage->getToken()->getUser()->getId(),
                "URL" => $url->url,
                "Total" => $url->total,
                "ReceiveDate" => date("Y-m-d H:i:s", $date),
                "ExtensionVersion" => $extensionVersion,
            ]);
        }

        return new JsonResponse(['status' => 'ok']);
    }

    private function convertExtensionVersion(string $extensionVersion): ?int
    {
        if (!preg_match('/^(\d+)\.(\d+)\.(\d+)$/', $extensionVersion, $matches)) {
            return null;
        }

        if ($matches[1] > 999 || $matches[2] > 999 || $matches[3] > 999) {
            return null;
        }

        return intval($matches[1]) * 1000000 + intval($matches[2]) * 1000 + intval($matches[3]);
    }
}
