<?php

namespace AwardWallet\MainBundle\Email;

use AwardWallet\MainBundle\Entity\AbMessage;
use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\Manager\BookingRequestManager;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

class BookerMailImporter
{
    public const RESULT_SKIP = 1;
    public const RESULT_IMPORTED = 2;
    public const RESULT_ALREADY_IMPORTED = 3;

    /**
     * @var \Doctrine\ORM\EntityManagerInterface
     */
    protected $em;
    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $logger;
    /**
     * @var \AwardWallet\MainBundle\Manager\BookingRequestManager
     */
    protected $manager;

    public function __construct(EntityManagerInterface $em, BookingRequestManager $manager, LoggerInterface $logger)
    {
        $this->em = $em;
        $this->manager = $manager;
        $this->logger = $logger;
    }

    public function importMessage(\PlancakeEmailParser $parser, $forceRequestId = null)
    {
        $date = strtotime($parser->getHeader('Date')); // strtotime to handle timezones
        $date = new \DateTime(date('Y-m-d H:i:s', $date));
        $this->logger->info("date: " . $date->format('Y-m-d H:i:s'));

        if ($date->diff(new \DateTime(), true)->days > 30) {
            $this->logger->info("date is more than month away, skip");

            return self::RESULT_SKIP;
        }

        $messageId = $parser->getHeader('Message-ID');

        if (is_array($messageId)) {
            $messageId = array_pop($messageId);
        }
        $this->logger->info("message-id: " . $messageId);

        if (empty($messageId)) {
            $this->logger->warning("no Message-ID, skip");

            return self::RESULT_SKIP;
        }

        if (preg_match('#booking\s+request\s+\#(\d+)#ims', $parser->getSubject(), $matches)) {
            if (empty($forceRequestId)) {
                $requestId = $matches[1];
            } else {
                $requestId = $forceRequestId;
            }
            $this->logger->info("importing to booking request id: $requestId");
            $request = $this->em->getRepository(\AwardWallet\MainBundle\Entity\AbRequest::class)->find($requestId);

            if (!empty($request)) {
                $from = $this->clearEmail(Util::clearHeader($parser->getHeader('From')));
                $this->logger->info("from: $from");
                $allowed = array_unique(array_merge([$this->clearEmail($request->getUser()->getEmail())], array_map([$this, 'clearEmail'], $request->getContactEmails())));

                if (!in_array($from, $allowed)
                && empty($forceRequestId)
                && (empty($request->getUser()) || strcasecmp($this->clearEmail($from), $this->clearEmail($request->getUser()->getEmail())) != 0)) {
                    $this->logger->info("message not from user, allowed emails: " . implode(", ", $allowed));

                    return self::RESULT_SKIP;
                }

                $body = $this->getReply($parser, $request->getBooker());

                if (empty($body)) {
                    return self::RESULT_SKIP;
                }

                $message = $this->em->getRepository(\AwardWallet\MainBundle\Entity\AbMessage::class)->findOneBy([
                    'RequestID' => $request,
                    'CreateDate' => $date,
                    'ImapMessageID' => $messageId,
                ]);

                if (empty($message)) {
                    $this->logger->info("creating new message");
                    $message = new AbMessage();
                    $message
                        ->setCreateDate($date)
                        ->setImapMessageID($messageId)
                        ->setUser($request->getUser())
                        ->setRequest($request)
                        ->setType(AbMessage::TYPE_COMMON)
                        ->setPost($body);
                    $this->manager->addMessage($message);
                    $this->em->flush();
                    $this->logger->info("message imported, id: " . $message->getAbMessageID());

                    return self::RESULT_IMPORTED;
                } else {
                    $this->logger->info("message already exists, id: " . $message->getAbMessageID());

                    return self::RESULT_ALREADY_IMPORTED;
                }
            } else {
                $this->logger->info("request not found");
            }
        }

        return self::RESULT_SKIP;
    }

    protected function clearEmail($email)
    {
        return preg_replace('#\+\w+@#ims', '@', $email);
    }

    protected function getReply(\PlancakeEmailParser $parser, Usr $booker)
    {
        $text = $parser->getPlainBody();
        $this->logger->info("text body: " . $text);

        $company = preg_quote($booker->getCompany(), '#') . '|' . preg_quote($booker->getBookerInfo()->getServiceName(), '#');

        $patterns = [
            '#^\-\-\-#ims',
            '#^In\s+a\s+message\s+dated#ims',
            '#^' . preg_quote($booker->getBookerInfo()->getFromEmail(), '#') . '\s+writes\:#ims',
            '#^[^\r\n^]*' . preg_quote($booker->getBookerInfo()->getFromEmail(), '#') . '[^\r\n]*\:#ims',
            '#^On.*' . $company . '.*wrote\:#ims',
            '#^[>\s]*' . $company . '\s+responded\s+to\s+your#ims',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $text, $matches, PREG_OFFSET_CAPTURE)) {
                if (!isset($pos) || $matches[0][1] < $pos) {
                    $pos = $matches[0][1];
                }
            }
        }

        if (!empty($pos)) {
            $text = substr($text, 0, $pos);
        }

        //		$text = trim(preg_replace($regexp, '', $text));
        //		$parts = preg_split($regexp, $text);
        //		$text = $parts[0];
        $text = preg_replace('#[\r\n]#ims', "\n", $text);
        $text = preg_replace('#[\t ]+#ims', ' ', $text);
        $text = trim($text);
        // trim all > lines from end of message
        $lines = explode("\n", $text);

        while (count($lines) && strpos($lines[count($lines) - 1], '>') === 0) {
            array_pop($lines);
        }
        $text = implode("\n", $lines);
        $this->logger->info("reply body: " . $text);

        return nl2br($text);
    }
}
