<?php

namespace AwardWallet\MainBundle\Loyalty\AccountSaving;

use AwardWallet\MainBundle\Entity\Account;
use AwardWallet\MainBundle\Event\AccountUpdatedEvent;
use AwardWallet\MainBundle\Loyalty\AccountSaving\Processors\AccountProcessor;
use AwardWallet\MainBundle\Loyalty\Resources\CheckAccountResponse;
use AwardWallet\MainBundle\Service\AccountCheckReportConverter;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class Saver
{
    private EntityManagerInterface $em;

    private EventDispatcherInterface $dispatcher;

    private AccountProcessor $accountProcessor;

    private AccountCheckReportConverter $converter;

    private CheckAccountResponsePreparer $responsePreparer;

    public function __construct(
        EntityManagerInterface $em,
        EventDispatcherInterface $dispatcher,
        AccountProcessor $accountProcessor,
        AccountCheckReportConverter $converter,
        CheckAccountResponsePreparer $responsePreparer
    ) {
        $this->em = $em;
        $this->dispatcher = $dispatcher;
        $this->accountProcessor = $accountProcessor;
        $this->converter = $converter;
        $this->responsePreparer = $responsePreparer;
    }

    public function save(
        Account $account,
        \AccountCheckReport $oldReport,
        \AuditorOptions $oldOptions,
        ?int $thrownEvent,
        ?CheckAccountResponse $newResponse = null
    ): ProcessingReport {
        if (is_null($newResponse)) {
            $newResponse = $this->converter->convert($oldReport, $oldOptions->source, $oldOptions->checkIts);
            $newResponse->setRequestid('saver_' . \bin2hex(\random_bytes(16)));
        }

        \CommonCheckAccountFactory::manuallySave($account->getId(), $oldReport, $oldOptions);
        $this->em->refresh($account);

        $this->responsePreparer->prepare($account, $newResponse, $thrownEvent !== AccountUpdatedEvent::UPDATE_METHOD_LOYALTY);
        $saveReport = $this->accountProcessor->saveAccount($account, $newResponse);

        if (is_int($thrownEvent)) {
            $this->dispatcher->dispatch(new AccountUpdateEvent($account, AccountUpdateEvent::SOURCE_LOYALTY_CHECK));
            $this->dispatcher->dispatch(
                new AccountUpdatedEvent($account, $newResponse, $saveReport, $thrownEvent),
                AccountUpdatedEvent::NAME
            );
        }

        return $saveReport;
    }
}
