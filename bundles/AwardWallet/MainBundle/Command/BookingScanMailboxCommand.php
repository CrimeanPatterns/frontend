<?php

namespace AwardWallet\MainBundle\Command;

use AwardWallet\Common\Monolog\Processor\AppProcessor;
use AwardWallet\MainBundle\Email\BookerMailImporter;
use AwardWallet\MainBundle\Email\ReconnectException;
use AwardWallet\MainBundle\Entity\AbBookerInfo;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Monolog\Logger;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class BookingScanMailboxCommand extends Command
{
    public const MARKER = 'booking request';

    protected static $defaultName = 'aw:booking:scan-mailbox';

    /**
     * @var resource
     */
    protected $connection;
    /**
     * @var \Symfony\Component\Console\Output\OutputInterface
     */
    protected $output;
    /**
     * @var EntityManager
     */
    protected $entityManager;
    /**
     * @var AbBookerInfo
     */
    protected $bookerInfo;
    /**
     * @var Logger
     */
    protected $logger;
    /**
     * @var string
     */
    protected $name;
    protected $processed = [];
    /**
     * @var BookerMailImporter
     */
    protected $importer;
    protected $requestId;
    private string $lockName;
    /** @var AppProcessor */
    private $appProcessor;
    private \Memcached $memcached;
    private string $lockValue;

    public function __construct(LoggerInterface $logger, EntityManagerInterface $entityManager, BookerMailImporter $importer, AppProcessor $appProcessor, \Memcached $memcached)
    {
        parent::__construct();
        $this->logger = $logger;
        $this->entityManager = $entityManager;
        $this->importer = $importer;
        $this->appProcessor = $appProcessor;
        $this->memcached = $memcached;
    }

    public function logProcessor(array $record)
    {
        $record['context']['worker'] = "BookingScanMailboxCommand";
        $record['context']['BookerID'] = $this->bookerInfo->getUserID()->getUserid();

        return $record;
    }

    protected function configure()
    {
        $this
            ->setDescription("Watch booker mailbox for new replies to booking requests, and import them to database")
            ->setDefinition([
                new InputArgument('UserID', InputArgument::REQUIRED, 'read parameters from AbBookerInfo table with this UserID'),
                new InputOption('AbRequestID', null, InputOption::VALUE_REQUIRED, 'link all messages to this request, test mode'),
                new InputOption('Days', null, InputOption::VALUE_REQUIRED, 'days to scan', 14),
            ])
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->name = substr(basename(str_replace('\\', '/', get_class($this))), 0, -7);
        $this->output = $output;
        $this->bookerInfo = $this->entityManager->getRepository(\AwardWallet\MainBundle\Entity\AbBookerInfo::class)->findOneBy(['UserID' => $input->getArgument('UserID')]);
        $this->logger->pushProcessor([$this, "logProcessor"]);
        $this->requestId = $input->getOption('AbRequestID');

        $this->lockName = preg_replace("#\W+#ims", "_", $this->getName() . "_" . $this->bookerInfo->getUserID()->getUserid());
        $this->lockValue = bin2hex(random_bytes(8));

        pcntl_signal(SIGTERM, function () {
            $this->logger->info("stopping by SIGTERM");
            $this->processStopSignal();
            $this->logger->info("exiting");

            exit;
        });

        if (!$this->memcached->add($this->lockName, $this->lockValue, random_int(20, 30))) {
            $this->logger->info("exiting, no lock");

            exit;
        }

        try {
            $this->logger->info("connecting to mailbox {$this->bookerInfo->getImapMailbox()} as {$this->bookerInfo->getImapLogin()}");
            $this->connection = imap_open($this->bookerInfo->getImapMailbox(), $this->bookerInfo->getImapLogin(), $this->bookerInfo->getImapPassword());

            if ($this->connection === false) {
                throw new \Exception("could not connect to mailbox");
            }
            $this->scan($input->getOption('Days'));
        } finally {
            $this->releaseLock();
        }

        return 0;
    }

    protected function scan($days)
    {
        $lastUid = null;
        $this->logger->info("this command will not stop, it is designed as daemon, break it with ctrl-c when done");

        while (true) {
            $currentLock = $this->memcached->get($this->lockName, null, \Memcached::GET_EXTENDED);

            if ($currentLock === false || $currentLock["value"] !== $this->lockValue) {
                $this->logger->warning("lost lock");

                break;
            }

            try {
                if (empty($lastUid)) {
                    // cold start
                    $criteria = 'SUBJECT "' . self::MARKER . '"';
                    $date = strtotime("-" . $days . " day");
                    $criteria .= ' SINCE "' . date("d-M-Y", $date) . '"';
                    $this->logger->info("searching: " . $criteria);
                    $matches = imap_search($this->connection, $criteria, SE_UID);
                    $this->checkImapErrors();

                    if (is_array($matches)) {
                        $lastUid = max($matches);
                    }
                } else {
                    // look for new messages
                    $criteria = ($lastUid + 1) . ":" . ($lastUid + 21);
                    $this->logger->info("scanning: " . $criteria);
                    $messages = imap_fetch_overview($this->connection, $criteria, FT_UID);
                    $this->checkImapErrors();
                    $matches = [];

                    foreach ($messages as $message) {
                        if (!isset($message->subject)) {
                            $message->subject = '';
                        }
                        $this->logger->info("found new message", ["uid" => $message->uid, "subject" => $message->subject]);

                        if (stripos($message->subject, self::MARKER) !== false) {
                            $matches[] = $message->uid;
                        }
                        $lastUid = $message->uid;
                    }
                }

                if (is_array($matches)) {
                    foreach ($matches as $uid) {
                        $this->processMessage($uid);
                    }
                }
            } catch (ReconnectException $e) {
                $this->logger->warning("error: " . $e->getMessage() . ", trying to reconnect");
            }
            sleep(10);
            $connectCount = 0;

            while (!imap_reopen($this->connection, $this->bookerInfo->getImapMailbox()) && $connectCount < 1000) {
                try {
                    $this->checkImapErrors();
                } catch (ReconnectException $e) {
                    $this->logger->warning("reopen error: " . $e->getMessage() . ", trying to reconnect");
                    sleep(10);
                }
                $connectCount++;
            }
        }
    }

    protected function processMessage($uid)
    {
        $this->appProcessor->setNewRequestId();
        $this->logger->info("processing message", ["uid" => $uid]);

        if (in_array($uid, $this->processed)) {
            throw new \Exception("message already processed");
        }
        $this->processed[] = $uid;
        $body = imap_body($this->connection, $uid, FT_UID | FT_PEEK);
        $this->checkImapErrors();
        $headers = imap_fetchheader($this->connection, $uid, FT_UID);
        $this->checkImapErrors();
        $parser = new \PlancakeEmailParser($headers . $body);
        $this->logger->info($parser->getHeader('From') . ' -> ' . $parser->getHeader('To') . ': ' . $parser->getSubject() . ", " . $parser->getHeader('Date'));
        $result = $this->importer->importMessage($parser, $this->requestId);

        if ($result == BookerMailImporter::RESULT_IMPORTED) {
            $this->logger->info("marking message as read", ["uid" => $uid]);
            imap_setflag_full($this->connection, $uid, "\\Seen", ST_UID);
            $this->checkImapErrors();
        }
        $this->appProcessor->setNewRequestId();
    }

    protected function checkImapErrors()
    {
        $errors = imap_errors();

        if (!empty($errors)) {
            foreach ($errors as $error) {
                if ($error == "Invalid mailbox list: <>") {
                    continue;
                }

                if (stripos($error, "Unexpected characters at end of address") === 0) {
                    continue;
                }

                if (stripos($error, "Unterminated mailbox") === 0) {
                    continue;
                }

                if (stripos($error, "Must use comma to separate addresses") === 0) {
                    continue;
                }

                throw new ReconnectException($error);
            }
        }
    }

    protected function processStopSignal()
    {
        $this->releaseLock();
    }

    private function releaseLock()
    {
        $currentLock = $this->memcached->get($this->lockName, null, \Memcached::GET_EXTENDED);

        if ($currentLock !== false && $currentLock["value"] === $this->lockValue) {
            $this->memcached->cas($currentLock["cas"], $this->lockName, "deleted", 1);
        }
    }
}
