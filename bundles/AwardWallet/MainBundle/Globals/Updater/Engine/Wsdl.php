<?php

namespace AwardWallet\MainBundle\Globals\Updater\Engine;

use AwardWallet\MainBundle\Entity\Account;
use AwardWallet\MainBundle\Entity\Provider;
use AwardWallet\MainBundle\Entity\Useragent;
use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\Event\NewExtensionSessionEvent;
use AwardWallet\MainBundle\Globals\Updater\UpdaterUtils;
use AwardWallet\MainBundle\Loyalty\ApiCommunicator;
use AwardWallet\MainBundle\Loyalty\ApiCommunicatorException;
use AwardWallet\MainBundle\Loyalty\Converter;
use AwardWallet\MainBundle\Loyalty\Resources\PostCheckAccountResponse;
use AwardWallet\MainBundle\Loyalty\Resources\ProviderInfoResponse;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class Wsdl implements UpdaterEngineInterface
{
    public const LOYALTY_WAITING_TIMEOUT = 300;

    /**
     * @var LoggerInterface
     */
    private $logger;
    /**
     * @var \Memcached
     */
    private $memcached;
    /**
     * @var ApiCommunicator
     */
    private $communicator;
    /**
     * @var Converter
     */
    private $converter;
    private EventDispatcherInterface $eventDispatcher;

    public function __construct(
        LoggerInterface $logger,
        \Memcached $memcached,
        ApiCommunicator $communicator,
        Converter $converter,
        EventDispatcherInterface $eventDispatcher
    ) {
        $this->logger = $logger;
        $this->memcached = $memcached;
        $this->communicator = $communicator;
        $this->converter = $converter;
        $this->eventDispatcher = $eventDispatcher;
    }

    /**
     * @return CheckAccountResponse[]
     */
    public function sendAccounts(array $accounts, $options = 0, $source = null): array
    {
        $accounts = $this->prepareAccounts($accounts, $options);
        $checker = new \GroupWsdlCheckAccount($accounts);

        if ($source === \AwardWallet\MainBundle\Globals\Updater\Engine\UpdaterEngineInterface::SOURCE_BACKGROUND) {
            $checker->priority = 2;
        } else {
            $checker->priority = 7;
        }
        $checker->returnResponses = true;

        return $checker->WSDLCheckAccounts($source);
    }

    public function getUpdateSlots(Usr $usr)
    {
        return \AccountAuditor::getUserFreeThreads($usr->getUserid(), $usr->getAccountlevel());
    }

    public function getLogs($partner, $accountId, $providerCode = null, $login = null, $login2 = null, $login3 = null)
    {
        return [];
    }

    public function retrieveConfirmation(array $fields, Provider $provider, array &$trips, Usr $user, ?Useragent $familyMember = null)
    {
        if (null !== $familyMember && !$familyMember->isFamilyMember()) {
            throw new \InvalidArgumentException("Expected family member, got connection");
        }
        $error = null;

        try {
            $userID = $user->getUserid();
            $familyMemberId = ($familyMember instanceof Useragent) ? $familyMember->getUseragentid() : null;
            // JSON Checking
            $loyaltyRequest = $this->converter->prepareCheckConfirmationRequest($provider->getCode(), $fields, $userID, $familyMemberId);
            $loyaltyResponse = $this->communicator->CheckConfirmation($loyaltyRequest);

            if (!$loyaltyResponse instanceof PostCheckAccountResponse) {
                $this->logger->critical('Confirmation retrieve error', ['UserID' => $userID]);

                throw new ApiCommunicatorException("Failed to check ConfNo");
            }

            $this->logger->debug("Confirmation successfully sent to retrive. RequestId: " . $loyaltyResponse->getRequestid());
            $this->memcached->set('check_confirmation_request_' . $loyaltyResponse->getRequestid(), $loyaltyRequest);

            if ($loyaltyResponse->getBrowserExtensionSessionId()) {
                $this->eventDispatcher->dispatch(new NewExtensionSessionEvent($loyaltyResponse->getBrowserExtensionSessionId(), $loyaltyResponse->getBrowserExtensionConnectionToken()), NewExtensionSessionEvent::NAME);
            }

            // Waiting callback result in memchached
            $check = false;
            $checkResult = false;
            $timeStart = time();

            while (!$check && $timeStart + self::LOYALTY_WAITING_TIMEOUT > time()) {
                $checkResult = $this->memcached->get('check_confirmation_result_' . $loyaltyResponse->getRequestid());

                if ($checkResult === false) {
                    sleep(2);

                    continue;
                }

                $check = true;
            }

            if ($check === false) {
                throw new ApiCommunicatorException("The process timed out while parsing reservations");
            }

            if (is_string($checkResult)) {
                throw new ApiCommunicatorException($checkResult);
            }

            if (!is_array($checkResult)) {
                throw new ApiCommunicatorException(json_encode($checkResult));
            }

            $trips = $checkResult;
            // END JSON Checking
        } catch (\WsdlFailureException $e) {
            $error = $e->getMessage();
        } catch (ApiCommunicatorException $e) {
            $error = $e->getCode() === 1 ? "Failed to check ConfNo" : $e->getMessage();
        }

        return $error;
    }

    public function getRedirectFrameUrl(?Account $account = null, Usr $user, ?Provider $provider = null)
    {
        $accountId = !empty($account) ? $account->getAccountid() : 0;

        return '/account/redirectProxy.php?ID=' . $accountId;
    }

    public function changePassword(Account $account)
    {
    }

    public function getProviderInfo(string $code): ProviderInfoResponse
    {
        return $this->communicator->getProviderInfo($code);
    }

    public function getCheckStrategy()
    {
        return \CommonCheckAccountFactory::STRATEGY_CHECK_WSDL;
    }

    private function prepareAccounts(array $accounts, $options)
    {
        foreach ($accounts as &$account) {
            if (self::OPTION_IT_AUTO & $options) {
                $account['AutoGatherPlans'] = UpdaterUtils::shouldCheckTrips($account);
            }
        }

        return $accounts;
    }
}
