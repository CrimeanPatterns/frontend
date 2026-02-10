<?php

namespace AwardWallet\MainBundle\Command\Fix;

use AwardWallet\MainBundle\FrameworkExtension\Command;
use Doctrine\DBAL\Connection;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class FixCouponsCommand extends Command
{
    public const STEP = 1000;
    protected static $defaultName = 'aw:fix:coupons';

    private LoggerInterface $logger;
    private Connection $connection;

    public function __construct(
        LoggerInterface $logger,
        Connection $connection
    ) {
        parent::__construct();
        $this->logger = $logger;
        $this->connection = $connection;
    }

    public function configure()
    {
        $this
            ->setDescription('command removes incorrectly given coupons')
            ->addOption("minUserId", null, InputOption::VALUE_REQUIRED, "minimum userId", 0)
            ->addOption("maxUserId", null, InputOption::VALUE_REQUIRED, "maximum userId")
            ->addOption("force", "f", InputOption::VALUE_NONE, "apply changes, otherwise dry run");
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $connection = $this->connection;
        $logger = $this->logger;

        $q = $connection->prepare("
		SELECT
		  Invites.InviterID as UserID,
		  Usr.InviteCouponsCorrection,
		  COUNT(distinct InvitesID) AS AcceptedInvites,
		  floor(COUNT(distinct InvitesID) / 5) AS TotalStars,
		  COUNT(distinct Coupon.CouponID) AS CouponsGiven,
		  COUNT(distinct Cart.CartID) AS CouponsUsed,
		  COUNT(distinct Coupon.CouponID) - COUNT(distinct Cart.CartID) as ActualStars
		FROM
		  Invites
		  join Usr on Invites.InviterID = Usr.UserID
		  join Coupon on Coupon.Code like concat('Invite-', Invites.InviterID, '-%')
		  left outer join Cart on Cart.CouponID = Coupon.CouponID and Cart.PayDate is not null
		WHERE
		  Invites.Approved = 1 AND
		  Invites.InviteeID IS NOT NULL
		  AND Invites.InviterID >= :minUserId AND Invites.InviterID < :maxUserId
		  AND Coupon.Code >= :couponStart
		  AND Coupon.Code < :couponEnd
		group by
		  Invites.InviterID, Usr.InviteCouponsCorrection
		having CouponsGiven > TotalStars
		");

        $couponsQuery = $connection->prepare("select Coupon.CouponID
		from Coupon left outer join Cart on Coupon.CouponID = Cart.CouponID and Cart.PayDate is not null
		where Coupon.Code like concat('Invite-', :userId, '-%')
		and Cart.CouponCode is null limit :limit");

        $usedCouponsQuery = $connection->prepare("select Coupon.CouponID, Cart.UserID
		from Coupon join Cart on Coupon.CouponID = Cart.CouponID and Cart.PayDate is not null
		where Coupon.Code like concat('Invite-', :userId, '-%')
		limit :limit");

        $deleteQuery = $connection->prepare("delete from Coupon where CouponID = :couponId");
        $updateCartQuery = $connection->prepare("update Cart set CouponCode = null, CouponID = null where CouponID = :couponId and PayDate is null");
        $correctQuery = $connection->prepare("update Usr set InviteCouponsCorrection = :correction where UserID = :userId");

        $maxUserId = $input->getOption('maxUserId');

        if (empty($maxUserId)) {
            $maxUserId = $connection->executeQuery("select max(UserID) from Usr")->fetchColumn(0) + 10000;
        }
        $logger->info("maxUserId: $maxUserId");

        $matches = 0;
        $corrected = 0;
        $extraCoupons = 0;
        $canRemove = 0;
        $removed = 0;
        $users = [];

        for ($stepStart = $input->getOption('minUserId'); $stepStart < $maxUserId; $stepStart += self::STEP) {
            $stepEnd = $stepStart + self::STEP;

            if (strlen($stepStart) == strlen($stepEnd)) {
                $couponStart = "Invite-$stepStart-";
                $couponEnd = "Invite-$stepEnd-";
            } else {
                $couponStart = "A";
                $couponEnd = "Z";
            }
            $logger->info("processing users from $stepStart..$stepEnd, coupons: $couponStart..$couponEnd");
            $q->execute(["minUserId" => $stepStart, "maxUserId" => $stepEnd, "couponStart" => $couponStart, "couponEnd" => $couponEnd]);

            foreach ($q->fetchAll(\PDO::FETCH_ASSOC) as $row) {
                $this->calcRow($row);
                $logger->notice("row", $row);
                $extraCoupons += $row['ExtraCoupons'];
                $matches++;
                $canRemove += $row['CanRemoveCoupons'];

                if ($input->getOption('force')) {
                    if ($row['CanRemoveCoupons'] > 0) {
                        $logger->warning("removing {$row['CanRemoveCoupons']} for user {$row['UserID']}");
                        $couponsQuery->bindValue('userId', intval($row['UserID']), \PDO::PARAM_INT);
                        $couponsQuery->bindValue('limit', intval($row['CanRemoveCoupons']), \PDO::PARAM_INT);
                        $couponsQuery->execute();

                        while ($couponId = $couponsQuery->fetchColumn()) {
                            $logger->notice("deleting coupon $couponId");
                            $updateCartQuery->execute(["couponId" => $couponId]);
                            $deleteQuery->execute(["couponId" => $couponId]);
                            $removed++;
                        }
                        $q->execute(["minUserId" => $row['UserID'], "maxUserId" => $row['UserID'] + 1, "couponStart" => $couponStart, "couponEnd" => $couponEnd]);
                        $updatedRow = $q->fetch(\PDO::FETCH_ASSOC);

                        if (!empty($updatedRow)) {
                            $this->calcRow($updatedRow);

                            if ($updatedRow['CanRemoveCoupons'] > 0) {
                                $logger->info("updated row", $updatedRow);

                                throw new \Exception("Can't remove coupons for user {$row['UserID']}");
                            }
                            $row['ExtraCoupons'] = $updatedRow['ExtraCoupons'];
                        }
                        $corrected++;
                    }

                    if ($row['ExtraCoupons'] > 0) {
                        $logger->warning("correcting {$row['ExtraCoupons']} extra coupons for user {$row['UserID']}");
                        $correctQuery->execute(["correction" => $row['ExtraCoupons'], "userId" => $row['UserID']]);
                        $corrected++;
                    }
                } else {
                    $logger->info("scanning coupons {$row['ExtraCoupons']} for user {$row['UserID']}");
                    $usedCouponsQuery->bindValue('userId', intval($row['UserID']), \PDO::PARAM_INT);
                    $usedCouponsQuery->bindValue('limit', intval($row['ExtraCoupons']), \PDO::PARAM_INT);
                    $usedCouponsQuery->execute();

                    while ($coupon = $usedCouponsQuery->fetch(\PDO::FETCH_ASSOC)) {
                        $logger->notice("coupon {$coupon['CouponID']}, user {$coupon['UserID']}");

                        if (!isset($users[$coupon['UserID']])) {
                            $users[$coupon['UserID']] = 1;
                        } else {
                            $users[$coupon['UserID']]++;
                        }
                    }
                }
            }
        }

        $logger->info("done, matches: $matches, extra coupons: $extraCoupons, can remove: $canRemove, free users: " . count($users) . ", free upgrades: " . array_sum($users) . ", revenue lost: " . array_sum($users) * 5 . ", users corrected: $corrected, removed: $removed");

        return 0;
    }

    private function calcRow(&$row)
    {
        $row['ExtraCoupons'] = $row['CouponsGiven'] - $row['TotalStars'] - $row['InviteCouponsCorrection'];
        $row['UnusedCoupons'] = $row['CouponsGiven'] - $row['CouponsUsed'];
        $row['CanRemoveCoupons'] = $row['ExtraCoupons'];

        if ($row['CanRemoveCoupons'] > $row['UnusedCoupons']) {
            $row['CanRemoveCoupons'] = $row['UnusedCoupons'];
        }
    }
}
