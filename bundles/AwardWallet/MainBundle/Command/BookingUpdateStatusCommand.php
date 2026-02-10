<?php

namespace AwardWallet\MainBundle\Command;

use AwardWallet\MainBundle\Entity\AbRequest;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class BookingUpdateStatusCommand extends Command
{
    private ManagerRegistry $registry;

    public function __construct(ManagerRegistry $registry)
    {
        $this->registry = $registry;
        parent::__construct();
    }

    public function configure()
    {
        $this->setName("aw:booking:update-status");
        $this->setDescription("Change of booking request status from the 'Future' to 'Open'");
        $this->addArgument("until", InputArgument::OPTIONAL, "Until Date (YYYY-MM-DD)");
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $until = $input->getArgument("until");

        if ($until) {
            if (!preg_match("/^\d{4}-\d{2}-\d{2}$/", $until)) {
                throw new \Exception("Invalid date");
            }
            $date = strtotime($until);

            if ($date === false) {
                throw new \Exception("Invalid date");
            }

            $output->writeln("Until Date: " . date("m/d/Y", $date));
            $until = "FROM_UNIXTIME($date)";
        } else {
            $until = "NOW()";
        }

        $doctrine = $this->registry;
        $db = $doctrine->getConnection();

        $requests = $db->executeQuery("SELECT * FROM AbRequest WHERE Status = ? AND RemindDate <= $until",
            [AbRequest::BOOKING_STATUS_FUTURE],
            [\PDO::PARAM_INT]
        )->fetchAll(\PDO::FETCH_ASSOC);

        foreach ($requests as $request) {
            $db->executeUpdate('UPDATE AbRequest SET Status = ?, RemindDate = NULL, LastUpdateDate = NOW() WHERE AbRequestID = ?', [
                AbRequest::BOOKING_STATUS_PENDING, $request['AbRequestID'],
            ]);
        }

        $output->writeln("Done. " . sizeof($requests) . " booking requests have been updated");

        return 0;
    }
}
