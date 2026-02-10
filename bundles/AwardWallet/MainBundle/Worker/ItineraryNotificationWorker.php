<?php

namespace AwardWallet\MainBundle\Worker;

use AwardWallet\MainBundle\Service\ItineraryMail\Sender;
use Doctrine\ORM\EntityManager;
use OldSound\RabbitMqBundle\RabbitMq\ConsumerInterface;
use PhpAmqpLib\Message\AMQPMessage;
use Psr\Log\LoggerInterface;

class ItineraryNotificationWorker implements ConsumerInterface
{
    public const MESSAGE_TTL = 3600; /** seconds */

    /**
     * @var EntityManager
     */
    protected $em;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var Sender
     */
    protected $mailer;

    public function __construct(EntityManager $em, LoggerInterface $logger, Sender $mailer)
    {
        $this->em = $em;
        $this->logger = $logger;
        $this->mailer = $mailer;
    }

    public function execute(AMQPMessage $message)
    {
        $this->em->clear();
        $data = @unserialize($message->body);
        $context = [
            'messageBody_string' => var_export($data, true),
        ];

        if (!is_array($data) || sizeof(array_diff(['mode', 'datetime', 'userId'], array_keys($data)))) {
            $this->log('error', 'Broken body', $context);

            return true;
        }

        $context = array_merge($context, [
            'mode_string' => $data['mode'] ?? 'undefined',
            'datetime_string' => isset($data['datetime']) ? date('Y-m-d H:i:s', $data['datetime']) : 'undefined',
            'userId_int' => $data['userId'] ?? 0,
        ]);

        $this->log('info', sprintf("Message delay: %d sec", time() - $data['datetime']), $context);

        if ((time() - $data['datetime']) > self::MESSAGE_TTL) {
            $this->log('info', sprintf("Message has been expired (UserID: %d, datetime: %s)", $data['userId'], date("Y-m-d H:i:s", $data['datetime'])), $context);

            return true;
        }
        $user = $this->em->getRepository(\AwardWallet\MainBundle\Entity\Usr::class)->find($data['userId']);

        if (!$user) {
            $this->log('info', sprintf("User %d not found", $data['userId']), $context);

            return true;
        }
        $lastUpdate = ($data['mode'] == 'add') ? $user->getItineraryadddate() : $user->getItineraryupdatedate();

        if ($lastUpdate != (new \DateTime('@' . $data['datetime']))) {
            $this->log('info', "User ItineraryDate has been updated. Skip.", $context);

            return true;
        }

        if (!$this->mailer->checkMessage($data)) {
            $this->log('info', "Itinerary Mailer working. Skip and resend message.", $context);

            return true;
        }

        $this->log('info', sprintf('process message for user %d', $data['userId']), $context);

        switch ($data['mode']) {
            case "add":
                $this->mailer->mailNewReservations($user, [
                    'ttl' => self::MESSAGE_TTL,
                ]);

                break;

            case "update":
                $this->mailer->mailChangedReservations($user, [
                    'ttl' => self::MESSAGE_TTL,
                ]);

                break;

            default:
                $this->log('error', "Broken body", $context);

                return true;

                break;
        }

        return true;
    }

    private function log($level, $message, array $context = [])
    {
        $this->logger->log($level, sprintf('ItNotificationWorker: %s', $message), $context);
    }
}
