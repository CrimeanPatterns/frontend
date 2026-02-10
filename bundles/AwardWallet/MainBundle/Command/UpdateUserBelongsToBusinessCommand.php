<?php

namespace AwardWallet\MainBundle\Command;

use AwardWallet\MainBundle\Entity\AbRequest;
use AwardWallet\MainBundle\Entity\Repositories\AbBookerInfoRepository;
use AwardWallet\MainBundle\Entity\Repositories\UsrRepository;
use Doctrine\DBAL\Connection;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class UpdateUserBelongsToBusinessCommand extends Command
{
    protected static $defaultName = "aw:users:update-owned";
    private Connection $connection;
    private UsrRepository $usrRepository;
    private AbBookerInfoRepository $abBookerInfoRepository;

    public function __construct(
        Connection $connection,
        UsrRepository $usrRepository,
        AbBookerInfoRepository $abBookerInfoRepository
    ) {
        parent::__construct();
        $this->connection = $connection;
        $this->usrRepository = $usrRepository;
        $this->abBookerInfoRepository = $abBookerInfoRepository;
    }

    public function configure()
    {
        $this->setDescription("Update User-Belongs-To-Business data");
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $db = $this->connection;

        $userRep = $this->usrRepository;
        $bookerRep = $this->abBookerInfoRepository;

        // fix ref
        $affected = 0;
        $users = $db->executeQuery(
            "SELECT u.UserID, sa.BookerID 
            FROM Usr u 
            JOIN SiteAd sa on u.CameFrom = sa.SiteAdID
            WHERE sa.BookerID is not null and u.OwnedByBusinessID is null"
        )->fetchAll(\PDO::FETCH_ASSOC);

        foreach ($users as $user) {
            if ($this->abBookerInfoRepository->find($user['BookerID']) === null) {
                $output->writeln("booker not found: " . $user['BookerID']);

                continue;
            }

            $affected += $db->executeUpdate('UPDATE Usr SET OwnedByBusinessID = ?, OwnedByManagerID = null WHERE UserID = ?', [
                $user['BookerID'], $user['UserID'],
            ]);
        }
        $output->writeln("Set OwnedByBusinessID by ref for {$affected} users");

        // fix invitee / coupon / user-ref
        $affected = 0;

        foreach ($bookerRep->findAll() as $booker) {
            foreach ($userRep->getBusinessManagers($booker->getUserID()) as $user) {
                $referralPartner = $userRep->getBusinessByUser($user, [ACCESS_BOOKING_VIEW_ONLY]);
                $invites = $db->executeQuery(
                    "SELECT InviteeID FROM Invites i WHERE i.InviterID = " . $user->getUserid() . " AND i.InviteeID IS NOT NULL"
                )->fetchAll(\PDO::FETCH_ASSOC);

                foreach ($invites as $invitee) {
                    if ($referralPartner) {
                        $affected += $db->executeUpdate('UPDATE Usr SET OwnedByBusinessID = ?, OwnedByManagerID = ? WHERE UserID = ?', [
                            $booker->getUserID()->getUserid(), $user->getUserid(), $invitee['InviteeID'],
                        ]);
                    } else {
                        $affected += $db->executeUpdate('UPDATE Usr SET OwnedByBusinessID = ?, OwnedByManagerID = null WHERE UserID = ?', [
                            $booker->getUserID()->getUserid(), $invitee['InviteeID'],
                        ]);
                    }
                }
            }
            $invites = $db->executeQuery(
                "SELECT InviteeID FROM Invites i WHERE i.InviterID = " . $booker->getUserID()->getUserid() . " AND i.InviteeID IS NOT NULL"
            )->fetchAll(\PDO::FETCH_ASSOC);

            foreach ($invites as $invitee) {
                $affected += $db->executeUpdate('UPDATE Usr SET OwnedByBusinessID = ?, OwnedByManagerID = null WHERE UserID = ?', [
                    $booker->getUserID()->getUserid(), $invitee['InviteeID'],
                ]);
            }
        }
        $output->writeln("Set OwnedByBusinessID by invitee / coupon / user-ref for {$affected} users");

        // fix default booker
        /*
                $affected = $db->executeUpdate(
                    "UPDATE Usr u SET u.DefaultBookerID = null where u.OwnedByBusinessID is null"
                );
        */
        $output->writeln("Clean default booker for {$affected} users");
        $affected = $db->executeUpdate(
            "UPDATE Usr u LEFT JOIN AbBookerInfo bi ON bi.UserID = u.OwnedByBusinessID SET u.DefaultBookerID = bi.UserID where u.OwnedByBusinessID is not null"
        );
        $output->writeln("Set default booker to OwnedByBusinessID for {$affected} users");
        /*
                $affected = $db->executeUpdate(
                    "UPDATE Usr u SET u.DefaultBookerID = (select r.BookerUserID from AbRequest r where r.UserID = u.UserID order by r.AbRequestID limit 1) where u.DefaultBookerID is null"
                );
                $output->writeln("Set default booker to first AbRequest for {$affected} users");
        */
        // set default booker as self for booking admins
        $affected = $db->executeUpdate("
			UPDATE Usr u, Usr b, UserAgent ua, AbBookerInfo bi
			SET u.DefaultBookerID = b.UserID
			WHERE
				u.UserID = ua.AgentID
				AND b.UserID = ua.ClientID
				AND ua.AccessLevel = " . ACCESS_ADMIN . "
				AND ua.IsApproved = 1
				AND b.AccountLevel = " . ACCOUNT_LEVEL_BUSINESS . "
				AND u.DefaultBookerID IS NULL
				AND bi.UserID = b.UserID
		");
        $output->writeln("Set default booker as self for booking admins for {$affected} users");

        $output->writeln("Done. ");

        return 0;
    }
}
