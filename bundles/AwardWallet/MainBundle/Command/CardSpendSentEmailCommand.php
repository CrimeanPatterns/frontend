<?php

namespace AwardWallet\MainBundle\Command;

use AwardWallet\MainBundle\Entity\Provider;
use AwardWallet\MainBundle\FrameworkExtension\Command;
use AwardWallet\MainBundle\FrameworkExtension\Mailer\Mailer;
use AwardWallet\MainBundle\Service\AccountHistory\SpentAnalysisEmailFactory;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class CardSpendSentEmailCommand extends Command
{
    public const DEBUG = [7, 36521]; // siteadmin, erik

    /** @var LoggerInterface */
    private $logger;

    /** @var EntityManagerInterface */
    private $entityManager;

    /** @var Mailer */
    private $mailer;

    /** @var SpentAnalysisEmailFactory */
    private $factory;

    public function __construct(
        LoggerInterface $logger,
        SpentAnalysisEmailFactory $factory,
        EntityManagerInterface $entityManager,
        Mailer $mailer
    ) {
        $this->logger = $logger;
        $this->entityManager = $entityManager;
        $this->mailer = $mailer;
        $this->factory = $factory;

        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->setName('aw:cardspend:sent-email')
            ->setDescription('Credit Card Spend Analytics Email');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $userRepository = $this->entityManager->getRepository(\AwardWallet\MainBundle\Entity\Usr::class);

        $potentialUsers = $this->entityManager->getConnection()->fetchAll('
            SELECT
                DISTINCT UserID
            FROM
                Account
            WHERE
                ProviderID IN (' . implode(',', Provider::EARNING_POTENTIAL_LIST) . ')
                AND State       = ' . ACCOUNT_ENABLED . '
                AND ErrorCode   = ' . ACCOUNT_CHECKED . '
                AND SubAccounts > 0
                ' . (empty(self::DEBUG) ? '' : 'AND UserID IN (' . implode(',', self::DEBUG) . ')') . '
        ');

        foreach ($potentialUsers as $usr) {
            $user = $userRepository->find($usr['UserID']);
            $template = $this->factory->buildLastMonth($user);

            if (null === $template) {
                continue;
            }

            $message = $this->mailer->getMessageByTemplate($template);
            $this->mailer->send($message);
            //            $user->setLastSpendAnalysisEmail(new \DateTime());
            //            $this->entityManager->persist($user);
        }

        //        $this->entityManager->flush();
        return 0;
    }
}
