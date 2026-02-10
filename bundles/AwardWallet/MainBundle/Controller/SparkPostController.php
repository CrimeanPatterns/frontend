<?php

namespace AwardWallet\MainBundle\Controller;

use AwardWallet\MainBundle\Manager\NDRManager;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class SparkPostController
{
    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var string
     */
    private $callbackPassword;

    /**
     * @var NDRManager
     */
    private $ndrManager;

    public function __construct(LoggerInterface $logger, $callbackPassword, NDRManager $manager)
    {
        $this->logger = $logger;
        $this->callbackPassword = $callbackPassword;
        $this->ndrManager = $manager;
    }

    /**
     * @Route("/api/sparkpost/bounce", name="aw_api_sparkpost_bounce")
     */
    public function bounceAction(Request $request)
    {
        if ($request->getPassword() != $this->callbackPassword) {
            return new Response('Unauthorized', 401);
        }

        $bounces = json_decode($request->getContent(), true);
        $this->logger->warning("sparkpost bounce", ["bounces" => $bounces]);

        foreach ($bounces as $bounce) {
            if (isset($bounce["msys"]["message_event"])) {
                $event = $bounce["msys"]["message_event"];
                $errorMessage = ArrayVal($event, 'reason');

                // 10	Invalid Recipient
                // 30	Generic Bounce: No RCPT
                // 90	Unsubscribe
                if (in_array($event['bounce_class'] ?? 0, ["10", "30", "90"])) {
                    $errorMessage = "hard-bounce";
                }

                $category = null;

                if (isset($event['rcpt_meta']['category'])) {
                    $category = $event['rcpt_meta']['category'];
                }

                if (!empty($event['rcpt_to'])) {
                    $this->ndrManager->recordNDR(
                        $event['rcpt_to'],
                        ArrayVal($event, 'message_id', ArrayVal($event, 'event_id')),
                        in_array($event['type'], ['spam_complaint', 'out_of_band', 'policy_rejection']), $errorMessage,
                        $category
                    );
                }
            } elseif ($bounce["msys"]["unsubscribe_event"]) {
                $event = $bounce["msys"]["unsubscribe_event"];
                $this->ndrManager->recordNDR($event['rcpt_to'], $event['message_id'], true, $event['type']);
            }
        }

        return new Response('OK');
    }
}
