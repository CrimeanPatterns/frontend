<?php

namespace AwardWallet\MainBundle\Service\OneTimeCodeProcessor;

use AwardWallet\Common\OneTimeCode\ProviderQuestionAnalyzer;
use AwardWallet\MainBundle\Entity\Account;
use AwardWallet\MainBundle\Entity\Answer;
use AwardWallet\MainBundle\Loyalty\ApiCommunicator;
use AwardWallet\MainBundle\Loyalty\ApiCommunicatorException;
use AwardWallet\MainBundle\Loyalty\Converter;
use AwardWallet\MainBundle\Loyalty\ConverterOptions;
use AwardWallet\MainBundle\Loyalty\Resources\CheckAccountResponse;
use AwardWallet\MainBundle\Loyalty\Resources\PostCheckAccountResponse;
use AwardWallet\MainBundle\Service\OneTimeCodeProcessor\Event\AccountSentOTCRecheckEvent;
use Clock\ClockInterface;
use Doctrine\DBAL\Exception;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

class OTCProcessor
{
    public const MAX_WAIT_TIME = 15 * 60;
    private OtcCache $cache;
    private Converter $converter;
    private ApiCommunicator $communicator;
    private EntityManagerInterface $em;
    private LoggerInterface $logger;
    private EventDispatcherInterface $eventDispatcher;
    private ClockInterface $clock;

    public function __construct(
        OtcCache $cache,
        Converter $converter,
        ApiCommunicator $communicator,
        EntityManagerInterface $em,
        LoggerInterface $logger,
        EventDispatcherInterface $eventDispatcher,
        ClockInterface $clock
    ) {
        $this->cache = $cache;
        $this->converter = $converter;
        $this->communicator = $communicator;
        $this->em = $em;
        $this->logger = $logger;
        $this->eventDispatcher = $eventDispatcher;
        $this->clock = $clock;
    }

    public function process(Account $account, ?CheckAccountResponse $checkAccountResponse = null): void
    {
        $providerCode = $account->getProviderid()->getCode();
        $userId = $account->getUser()->getId();

        if (!ProviderQuestionAnalyzer::isProviderOtc($providerCode)) {
            return;
        }

        $this->em->refresh($account);
        $up = $this->cache->getUpdate($account->getId());
        $check = $this->cache->getCheck($account->getId());
        $auto = $this->cache->getAutoCheck($account->getId());

        $this->logger->info('otc: validity debug', [
            'otcIsQuestion' => $account->getErrorcode() == ACCOUNT_QUESTION,
            'otcAccQuestion' => $account->getQuestion(),
            'otcIsOtc' => ProviderQuestionAnalyzer::isQuestionOtc($providerCode, $account->getQuestion() ?? ''),
            'otcUpDate' => $up ? date('Y-m-d H:i:s', $up) : '',
            'otcCheckDate' => $check ? date('Y-m-d H:i:s', $check) : '',
            'otcAutoCheckDate' => $auto ? date('Y-m-d H:i:s', $auto) : '',
            'providerCode' => $account->getProviderid() ? $account->getProviderid()->getCode() : '',
        ]);

        if ($account->getErrorcode() != ACCOUNT_QUESTION
            || empty($account->getQuestion())
            || !ProviderQuestionAnalyzer::isQuestionOtc($providerCode, $account->getQuestion())
            || empty($up)
            || $up < $this->clock->current()->getAsSecondsInt() - OtcCache::CHECK_TTL) {
            $this->logger->info('otc: account is not eligible', ['accountId' => $account->getId(), 'userId' => $userId]);

            return;
        }

        if ($auto) {
            $this->logger->info('otc: account was already checked automatically', ['accountId' => $account->getId(), 'userId' => $userId]);

            return;
        }

        $code = $this->cache->getProviderOtc($userId, $providerCode);

        if (empty($code)) {
            $this->logger->info('otc: no code found, skipping', ['accountId' => $account->getId(), 'userId' => $userId]);

            return;
        }

        if ($this->cache->hasCodeCollision($userId, $providerCode)) {
            $this->logger->info('otc: collision detected, skipping', ['accountId' => $account->getId(), 'userId' => $userId]);

            return;
        }

        if (!$this->saveOtc($account, $code)) {
            $this->logger->notice('otc: failed to save new code', ['accountId' => $account->getId(), 'userId' => $userId]);
        }
        $this->em->refresh($account);
        $this->logger->info('otc: sending to check with new code', ['accountId' => $account->getId(), 'userId' => $userId, 'providerCode' => $account->getProviderid() ? $account->getProviderid()->getCode() : '']);
        $this->cache->setAutoCheck($account->getId());
        $this->cache->dropProviderOtc($userId, $providerCode);

        if ($checkAccountResponse) {
            $this->cache->setAutoCheckRequestId($checkAccountResponse->getRequestid());
        }

        $secondCheckAccountResponse = $this->sendCheckRequest($account);

        if ($checkAccountResponse && $secondCheckAccountResponse) {
            $this->cache->setNextRequestId($checkAccountResponse->getRequestid(), $secondCheckAccountResponse->getRequestid());
        }
    }

    private function saveOtc(Account $account, string $code): bool
    {
        $this->em->refresh($account);

        if (!is_iterable($account->getAnswers())) {
            return false;
        }

        try {
            foreach ($account->getAnswers() as $answer) {
                $this->logger->info('otc: answer debug', [
                    'otcAccQuestion' => $account->getQuestion(),
                    'otcAnswerQuestion' => $answer->getQuestion(),
                    'otcIsQuestion' => strcasecmp(trim($answer->getQuestion()), trim($account->getQuestion())) === 0,
                    'otcAnswerCode' => $answer->getAnswer(),
                    'otcCode' => $code,
                ]);

                if (!empty($answer->getQuestion()) && strcasecmp(trim($answer->getQuestion()), trim($account->getQuestion())) === 0) {
                    $answer->setQuestion($account->getQuestion());
                    $answer->setAnswer($code);
                    $answer->setValid(true);
                    $this->em->flush();

                    return true;
                }
            }
            $answer = new Answer();
            $answer->setAccountid($account)
                ->setCreateDate(new \DateTime())
                ->setQuestion($account->getQuestion())
                ->setAnswer($code);
            $this->em->persist($answer);
            $this->em->flush();

            return true;
        } catch (Exception $e) {
            $this->logger->error($e->getMessage());

            return false;
        }
    }

    private function sendCheckRequest(Account $account): ?PostCheckAccountResponse
    {
        $options = new ConverterOptions();
        $priority = Converter::USER_CHECK_REQUEST_PRIORITY;
        $request = $this->converter->prepareCheckAccountRequest($account, $options, $priority);

        if (empty($request->getPassword())
            && $account->getProviderid()->getPasswordrequired()
            && !empty($localPassword = $this->cache->getTempLocalPassword($account->getId()))) {
            $request->setPassword($localPassword);
        }

        if ($account->getProviderid()->getPasswordrequired() && empty($request->getPassword())) {
            $this->logger->info('empty password in otc followup check request');

            return null;
        }

        try {
            $response = $this->communicator->CheckAccount($request);
            $this->eventDispatcher->dispatch(new AccountSentOTCRecheckEvent($account));

            return $response;
        } catch (ApiCommunicatorException $e) {
            $this->logger->warning($e->getMessage(), ['accountId' => $account->getAccountid()]);

            return null;
        }
    }
}
