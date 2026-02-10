<?php

namespace AwardWallet\MainBundle\Service\RA\Async;

use AwardWallet\MainBundle\Service\AppBot\Adapter\Slack;
use AwardWallet\MainBundle\Service\AppBot\AppBot;
use AwardWallet\MainBundle\Service\FlightInfo\Exceptions\Exception;
use AwardWallet\MainBundle\Service\RA\AwardPriceService;
use AwardWallet\MainBundle\Worker\AsyncProcess\ExecutorInterface;
use AwardWallet\MainBundle\Worker\AsyncProcess\Response;
use AwardWallet\MainBundle\Worker\AsyncProcess\Task;
use Psr\Log\LoggerInterface;

class AwardPriceExecutor implements ExecutorInterface
{
    private AwardPriceService $awardPriceService;

    private LoggerInterface $logger;

    private \Swift_Mailer $mailer;

    private AppBot $appBot;

    private \Memcached $memcached;

    /**
     * AwardPriceExecutor constructor.
     */
    public function __construct(
        AwardPriceService $awardPriceService,
        LoggerInterface $logger,
        \Swift_Mailer $mailer,
        AppBot $appBot,
        \Memcached $memcached
    ) {
        $this->awardPriceService = $awardPriceService;
        $this->logger = $logger;
        $this->mailer = $mailer;
        $this->appBot = $appBot;
        $this->memcached = $memcached;
    }

    /**
     * @param AwardPriceTask $task
     */
    public function execute(Task $task, $delay = null): Response
    {
        $params = $task->getParams();

        try {
            $fileName = $this->awardPriceService->getAwardPriceCsv($params, $task->getFileName());
        } catch (Exception $e) {
            // for debug
            $this->logger->error("Award Price Report check error",
                [
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'trace' => $e->getTraceAsString(),
                ]
            );

            throw $e;
        } finally {
            $this->memcached->delete(AwardPriceTask::AWARD_PRICE_KEY);
        }
        $messageSlack = [
            'text' => '',
            'blocks' => [],
        ];

        if (null !== $fileName) {
            // send report
            /** @var \Swift_Message $message */
            $message = new \Swift_Message();

            // prepare the message
            $message
                ->setFrom('noreply@awardwallet.com')
                ->setTo($task->getEmail())
                ->setSubject("Award Price Report")
                ->setBody($task->getMessage(), 'text/plain')
                ->attach(\Swift_Attachment::fromPath($fileName));

            $this->mailer->send($message);
            $this->logger->info("Award Price Report send to " . $task->getEmail(),
                ['fileName' => $task->getFileName()]);
            @unlink($fileName);

            $messageSlack['blocks'][] = [
                'type' => "section",
                'text' => [
                    'type' => 'mrkdwn',
                    'text' => sprintf("Report saved to %s and send to %s", $task->getFileName(), $task->getEmail()),
                ],
            ];
        } else {
            $messageSlack['blocks'][] = [
                'type' => "section",
                'text' => [
                    'type' => 'mrkdwn',
                    'text' => "Result of request to file {$task->getFileName()} is empty",
                ],
            ];
        }
        // for debug
        $this->logger->info("Award Price Report send message to slack");
        $this->appBot->send(Slack::CHANNEL_AW_AWARD_ALERTS, $messageSlack);

        return new Response();
    }
}
