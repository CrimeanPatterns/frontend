<?php

namespace AwardWallet\MainBundle\Command;

use AwardWallet\MainBundle\Entity\Provider;
use AwardWallet\MainBundle\Entity\ProviderStatusHistory;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class SaveCurrentProvidersStatusCommand extends Command
{
    protected static $defaultName = 'aw:support:save-current-providers-status';

    private EntityManagerInterface $entityManager;

    public function __construct(
        EntityManagerInterface $entityManager
    ) {
        parent::__construct();
        $this->entityManager = $entityManager;
    }

    protected function configure()
    {
        $this
            ->setDescription("Save current provider status statistics")
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $providerRepository = $this->entityManager->getRepository(Provider::class);

        // Query copypasted from manager/providerStatus.php
        $sql = "
				SELECT
					p.ProviderID, p.DisplayName, p.State, p.Code, p.Kind, p.WSDL, p.Assignee, u.Login as AssigneeLogin,
					p.AutoLogin, p.DeepLinking, p.CanCheckBalance, p.Corporate, p.CanCheckExpiration ,p.CanCheckItinerary,
					p.CanCheckConfirmation, p.Tier, p.Severity, p.ResponseTime, p.Warning,

					count(a.AccountID) as TotalCount,
					sum(case when a.ErrorCode = " . ACCOUNT_ENGINE_ERROR . " then 1 else 0 end) AS UnkErrors,
					sum(case when a.AccountID is not null and a.UpdateDate > adddate(now(), interval -4 hour) then 1 else 0 end) AS LastChecked,
					sum(case when a.ErrorCode = " . ACCOUNT_ENGINE_ERROR . " and a.UpdateDate > adddate(now(), interval -4 hour) then 1 else 0 end) AS LastUnkErrors,
					sum(case when a.ErrorCode <> " . ACCOUNT_CHECKED . " then 1 else 0 end) AS Errors,
					round(sum(case when a.ErrorCode = " . ACCOUNT_ENGINE_ERROR . " then 1 else 0 end)/count(a.AccountID)*100, 2) AS ErrorRate,
					round(sum(case when a.ErrorCode = " . ACCOUNT_CHECKED . " then 1 else 0 end)/count(a.AccountID)*100, 2) AS SuccessRate
				FROM
					Account a
					inner join Provider p on a.ProviderID = p.ProviderID
					left outer join Usr u on p.Assignee = u.UserID
				WHERE
					p.State >= " . PROVIDER_ENABLED . " and p.State <> " . PROVIDER_COLLECTING_ACCOUNTS . " and p.CanCheck = 1
					and a.UpdateDate > DATE_SUB(NOW(), INTERVAL 1 DAY)
				GROUP BY
					p.ProviderID, p.DisplayName, p.State, p.Code, p.Kind, p.WSDL, p.Assignee, u.Login,
					p.AutoLogin, p.DeepLinking, p.CanCheckBalance, p.Corporate, p.CanCheckExpiration ,p.CanCheckItinerary,
					p.CanCheckConfirmation, p.Tier, p.Severity, p.ResponseTime, p.Warning
			";
        $statistics = $this->entityManager->getConnection()->executeQuery($sql)->fetchAll();

        if (!$statistics) {
            $output->writeln('Error: Query result is empty');

            return 0;
        }

        $datetimeStamp = new \DateTime();
        $output->writeln("Storing provider status for {$datetimeStamp->format('Y-m-d H:i:s')}");
        $output->writeln("Provider\tChecked\tErrors\tUE");

        foreach ($statistics as $stat) {
            $provider = $providerRepository->find($stat['ProviderID']);
            $totalCheckedAccountsCount = $stat['TotalCount'];
            $totalErrorsCount = $stat['Errors'];
            $unknownErrorsCount = $stat['UnkErrors'];

            $output->writeln($provider->getCode() . "\t" . $totalCheckedAccountsCount . "\t" . $totalErrorsCount . "\t" . $unknownErrorsCount);

            // @TODO put to cloudwatch
            //			$record = new ProviderStatusHistory();
            //			$record->setDatetimeStamp($datetimeStamp);
            //			$record->setProvider($provider);
            //			$record->setTotalCheckedAccountsCount($totalCheckedAccountsCount);
            //			$record->setTotalErrorsCount($totalErrorsCount);
            //			$record->setUnknownErrorsCount($unknownErrorsCount);
            //			$entityManager->persist($record);
        }
        $this->entityManager->flush();

        return 0;
    }
}
