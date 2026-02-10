<?php

namespace AwardWallet\Tests\Unit\MainBundle\Loyalty\Listener;

use AwardWallet\MainBundle\Entity\Account;
use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\Event\AccountUpdatedEvent;
use AwardWallet\MainBundle\FrameworkExtension\Mailer\Mailer;
use AwardWallet\MainBundle\Loyalty\AccountSaving\ProcessingReport;
use AwardWallet\MainBundle\Loyalty\ApiCommunicator;
use AwardWallet\MainBundle\Loyalty\Converter;
use AwardWallet\MainBundle\Loyalty\Listener\SpentAnalysisEmailListener;
use AwardWallet\MainBundle\Service\AccountHistory\SpentAnalysisEmailFactory;
use AwardWallet\Tests\Unit\BaseTest;
use Codeception\Module\Symfony;
use Doctrine\ORM\EntityManager;
use Psr\Log\NullLogger;

class SpentAnalysisEmailListenerTest extends BaseTest
{
    /** @var EntityManager */
    private $em;

    public function _before()
    {
        parent::_before();
        /** @var Symfony $symfony */
        $symfony = $this->getModule('Symfony');

        $this->em = $symfony->grabService("doctrine.orm.entity_manager");
        $this->factory = $symfony->grabService(SpentAnalysisEmailFactory::class);
    }

    public function _after()
    {
        parent::_after();
    }

    public function _test_RejectNonBankProviders()
    {
        $loyalty = $this->getMockBuilder(ApiCommunicator::class)->disableOriginalConstructor()->getMock();
        $converter = $this->getMockBuilder(Converter::class)->disableOriginalConstructor()->getMock();
        $mailer = $this->getMockBuilder(Mailer::class)->disableOriginalConstructor()->getMock();

        $siteadminChaseAcc = $this->em->getRepository(Account::class)->find(1209586);
        $event = new AccountUpdatedEvent($siteadminChaseAcc, 1, '', json_encode(['priority' => 2]), new ProcessingReport(), AccountUpdatedEvent::UPDATE_METHOD_LOYALTY);

        $listener = new SpentAnalysisEmailListener(new NullLogger(), $loyalty, $converter, $this->factory, $this->em, $mailer);
        $listener->onAccountUpdated($event);

        $user = $this->em->getRepository(Usr::class)->find(7);
        $listener->findAccountsNeedsToCheck($user);
    }
}
