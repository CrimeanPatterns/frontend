<?php

namespace AwardWallet\MainBundle\Command\Business;

use AwardWallet\MainBundle\Entity\Useragent;
use AwardWallet\MainBundle\FrameworkExtension\Command;
use AwardWallet\MainBundle\Service\BusinessTransaction\AwPlusProcessor;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class KeepUpgradedCommand extends Command
{
    public static $defaultName = "aw:business:keep-upgraded";

    /**
     * @var EntityManagerInterface
     */
    private $em;

    /**
     * @var LoggerInterface
     */
    private $logger;

    private AwPlusProcessor $awPlusProcessor;

    public function __construct(
        AwPlusProcessor $awPlusProcessor,
        EntityManagerInterface $em,
        LoggerInterface $logger
    ) {
        parent::__construct();

        $this->awPlusProcessor = $awPlusProcessor;
        $this->em = $em;
        $this->logger = $logger;
    }

    public function configure()
    {
        $this->setDescription("Upgrade business-connected users with KeepUpgraded checkbox");
        $this->addOption('business', 'b', InputOption::VALUE_REQUIRED, 'run only on this business id');
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->logger->info("loading data");
        $params = [];
        $filters = [];

        if (!empty($businessId = $input->getOption('business'))) {
            $this->logger->info("limited to business id " . $businessId);
            $filters[] = " and ua.agentid = :business";
            $params["business"] = $businessId;
        }
        $q = $this->em->createQuery("
		select
			ua
		from
		    AwardWallet\MainBundle\Entity\Useragent ua
			join ua.agentid business
			join ua.clientid user
			join AwardWallet\MainBundle\Entity\Useragent au with ua.agentid = au.clientid and au.agentid = ua.clientid
		where
			business.accountlevel = " . ACCOUNT_LEVEL_BUSINESS . "
			and user.accountlevel = " . ACCOUNT_LEVEL_FREE . "
			and ua.isapproved = 1
			and au.keepUpgraded = 1
			" . implode(" and ", $filters) . "
		order by
			ua.agentid"
        );
        // @TODO: add KeepUpgraded filter

        $processed = 0;
        $upgraded = 0;

        foreach ($q->execute($params) as $agent) {
            /** @var Useragent $agent */
            $processed++;

            if ($this->awPlusProcessor->upgradeToAwPlus($agent)) {
                $upgraded++;
            }
        }

        $this->logger->info("done, processed: $processed, upgraded: $upgraded");

        return 0;
    }
}
