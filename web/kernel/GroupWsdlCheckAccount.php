<?php

use AwardWallet\MainBundle\Globals\Updater\Engine\CheckAccountResponse as CheckAccountResponseLegacyEngine;
use AwardWallet\MainBundle\Loyalty\Resources\PostCheckAccountResponse;

class GroupWsdlCheckAccount implements ObservableInterface
{
    public const EVENT_LOG = 1;
    public const EVENT_ONE_COMPLETE = 2;
    public const EVENT_END = 3;
    public const LOYALTY_EVENT_LOG = 4;
    public const LOYALTY_EVENT_ERROR = 5;
    public const LOYALTY_EVENT_ONE_COMPLETE = 6;
    public int $priority = 5;
    public $parseHistory;
    /**
     * throws \RuntimeException on occurred errors.
     *
     * @var bool
     */
    public $returnResponses = false;
    protected $accounts = [];
    protected $observers = [];
    /**
     * @var array<int accountId, CheckAccountRequest request>
     */
    protected $accountRequestMap;

    /** @var \AwardWallet\MainBundle\Loyalty\Converter */
    protected $loyaltyConverter;
    /** @var \AwardWallet\MainBundle\Entity\Repositories\AccountRepository */
    protected $accountRepo;
    /** @var \AwardWallet\MainBundle\Loyalty\ApiCommunicator */
    protected $loyaltyCommunicator;
    /** @var \Symfony\Bridge\Monolog\Logger */
    protected $logger;

    /** @var \Doctrine\DBAL\Connection */
    protected $doctrineConnection;

    public function __construct($accounts)
    {
        $this->accounts = $accounts;
        $this->accountRequestMap = [];

        // LOYALTY dependencies
        $this->loyaltyConverter = getSymfonyContainer()->get(\AwardWallet\MainBundle\Loyalty\Converter::class);
        $this->loyaltyCommunicator = getSymfonyContainer()->get(\AwardWallet\MainBundle\Loyalty\ApiCommunicator::class);
        $this->accountRepo = getSymfonyContainer()->get('doctrine')->getRepository(\AwardWallet\MainBundle\Entity\Account::class);
        $this->logger = getSymfonyContainer()->get('logger');
        $this->doctrineConnection = getSymfonyContainer()->get('doctrine.dbal.default_connection');
    }

    /**
     * @return \AwardWallet\MainBundle\Globals\Updater\Engine\CheckAccountResponse[]
     */
    public function WSDLCheckAccounts($source = null): array
    {
        $number = 0;
        $responses = [];
        $loyaltyAccountIds = [];

        foreach ($this->accounts as $account) {
            // JSON Checking
            $loyaltyAccountIds[] = $account['AccountID'];
            $loyaltyAccId = $account['AccountID'] . '-' . $account['Code'] . '-' . $account['Login'];

            /** @var \AwardWallet\MainBundle\Entity\Account $accountEntity */
            $accountEntity = $this->accountRepo->find($account['AccountID']);
            $options = (new \AwardWallet\MainBundle\Loyalty\ConverterOptions())
                ->setSource($source)
                ->setParseItineraries($account['AutoGatherPlans'] == '1')
                ->setParseHistory($this->parseHistory)
                ->setBrowserExtensionAllowed(($account['browserExtensionAllowed'] ?? false) === true)
            ;

            $loyaltyRequest = $this->loyaltyConverter->prepareCheckAccountRequest($accountEntity, $options, $this->priority);

            try {
                $loyaltyResponse = $this->loyaltyCommunicator->CheckAccount($loyaltyRequest);
                $responses[] = new CheckAccountResponseLegacyEngine(
                    $loyaltyResponse->getRequestid(),
                    $account['AccountID'],
                    $loyaltyResponse->getBrowserExtensionSessionId(),
                    $loyaltyResponse->getBrowserExtensionConnectionToken()
                );

                if ($loyaltyResponse instanceof PostCheckAccountResponse) {
                    $this->logger->info("Account successfully sent to check.", ["requestId" => $loyaltyResponse->getRequestid(), "accountId" => $account['AccountID'], "browserExtensionAllowed" => $loyaltyRequest->isBrowserExtensionAllowed()]);
                    $this->fireEvent(self::LOYALTY_EVENT_ONE_COMPLETE, $loyaltyAccId . '-' . $loyaltyResponse->getRequestid() . ' sent to check');
                    ++$number;
                } else {
                    $this->logger->critical('Account check error', ['AccountID' => $account['AccountID']]);
                    $this->fireEvent(self::LOYALTY_EVENT_ERROR, $loyaltyAccId . ' failed');
                }
            } catch (\AwardWallet\MainBundle\Loyalty\ApiCommunicatorException $e) {
                // TODO: Errors processing. using \CheckAccountResponse (wsdl) is incorrect
                $this->fireEvent(self::LOYALTY_EVENT_ERROR, $loyaltyAccId . ' failed message: ' . $e->getMessage());
            }
        }

        if ($this->returnResponses) {
            return $responses;
        }

        if (!empty($loyaltyAccountIds)) {
            $this->doctrineConnection->executeUpdate("UPDATE Account SET QueueDate = ADDDATE(NOW(), 7) WHERE AccountID IN (" . implode(", ", $loyaltyAccountIds) . ")");
            $this->fireEvent(self::LOYALTY_EVENT_LOG, "marking account: " . implode(", ", $loyaltyAccountIds) . "");
        }

        $this->fireEvent(self::EVENT_END, "processed $number accounts");

        return $responses;
    }

    public function addObserver($observer, $eventType)
    {
        $this->observers[$eventType][] = $observer;
    }

    public function fireEvent($eventType)
    {
        $message = '';

        if (func_num_args() > 1) {
            $message = func_get_arg(1);
        }

        if (isset($this->observers[$eventType]) && is_array($this->observers[$eventType])) {
            foreach ($this->observers[$eventType] as $observer) {
                if (is_callable($observer)) {
                    call_user_func_array($observer, [$this, $eventType, $message]);
                }
            }
        }
    }
}
