<?php

namespace AwardWallet\MainBundle\Controller;

use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ExtensionLogsController
{
    private LoggerInterface $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * @Route("/api/extension/v1/logs", name="aw_extension_logs", methods={"POST"})
     */
    public function receiveLogs(Request $request): Response
    {
        $body = $request->getContent();
        $this->logger->info("received logs from extension: $body");

        try {
            $logData = json_decode($body, true, 512, JSON_THROW_ON_ERROR);

            if (!isset($logData['extensionSessionId']) || !isset($logData['logs']) || !is_array($logData['logs'])) {
                $this->logger->warning("Invalid log data format");

                return new JsonResponse(['status' => 'ok']);
            }

            $extensionSessionId = $logData['extensionSessionId'];
            $logs = $logData['logs'];

            $this->logger->pushProcessor(function ($record) use ($extensionSessionId) {
                if (isset($record['context']['time'])) {
                    $record['datetime'] = new \DateTime('@' . $record['context']['time'], new \DateTimeZone('UTC'));
                    unset($record['context']['time']);
                }

                $record['context']['extensionSessionId'] = $extensionSessionId;

                return $record;
            });

            try {
                foreach ($logs as $log) {
                    if (!isset($log['time']) || !is_string($log['time'])) {
                        $this->logger->warning("invalid log line (time): " . json_encode($log));

                        continue;
                    }

                    $time = strtotime($log['time']);

                    if ($time > time() || $time < (time() - 600)) {
                        $time = time();
                    }

                    if (!isset($log['message'])) {
                        $this->logger->warning("invalid log line (message): " . json_encode($log));

                        continue;
                    }

                    $this->logger->info(json_encode($log['message']), ["time" => $time]);
                }
            } finally {
                $this->logger->popProcessor();
            }

            return new JsonResponse(['status' => 'ok']);
        } catch (\JsonException $exception) {
            $this->logger->warning("Could not decode JSON: " . $exception->getMessage());

            return new JsonResponse(['status' => 'ok']);
        }
    }
}
