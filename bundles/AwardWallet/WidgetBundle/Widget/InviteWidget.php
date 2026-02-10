<?php

namespace AwardWallet\WidgetBundle\Widget;

use AwardWallet\MainBundle\Entity\Coupon;
use AwardWallet\MainBundle\Globals\Localizer\LocalizeService;
use AwardWallet\MainBundle\Globals\StringHandler;
use AwardWallet\WidgetBundle\Widget\Classes\UserWidgetInterface;
use AwardWallet\WidgetBundle\Widget\Classes\UserWidgetTrait;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManager;

class InviteWidget extends TemplateWidget implements UserWidgetInterface
{
    use UserWidgetTrait;

    public function getWidgetContent($options = [])
    {
        $user = $this->getCurrentUser();

        /** @var EntityManager $em */
        $em = $this->container->get('doctrine.orm.entity_manager');

        $repInvites = $em->getRepository(\AwardWallet\MainBundle\Entity\Invites::class);
        $repCoupon = $em->getRepository(\AwardWallet\MainBundle\Entity\Coupon::class);
        $invitedCount = $repInvites->getCountInvitedByUser($user->getUserid());
        $acceptedCount = $repInvites->getCountAcceptedByUser($user->getUserid());
        $localizer = $this->container->get(LocalizeService::class);

        // RefCode
        $refCode = $user->getRefcode();

        if (!isset($refCode)) {
            $refCode = StringHandler::getRandomString(ord('a'), ord('z'), 10);
            $user->setRefcode($refCode);
            $em->flush();
        }
        $stars = $this->getFreeCouponsCount($user->getUserid());
        $free = $repCoupon->getFreeCouponsByUser($user->getUserid());

        if ($free === false) {
            $sql = "
                    SELECT 1
                    FROM   Cart
                    WHERE  PayDate IS NOT NULL
                           AND UserID = " . $user->getUserid() . "
                    LIMIT  1
                ";
            /** @var Connection $connection */
            $connection = $em->getConnection();
            $result = $connection->query($sql);
            $r = $result->fetch(\PDO::FETCH_ASSOC);

            if ($r !== false) {
                do {
                    $code = "free-" . StringHandler::getRandomCode(10);
                } while ($connection->executeQuery("select 1 from Coupon where Code = :code", ["code" => $code])->fetchColumn());
                $newCoupon = new Coupon();
                $newCoupon->setName('Free upgrade from ' . htmlspecialchars($user->getFullName()));
                $newCoupon->setCode($code);
                $newCoupon->setDiscount(100);
                $newCoupon->setFirsttimeonly(true);
                $newCoupon->setMaxuses(10);
                $newCoupon->setUser($user);
                $em->persist($newCoupon);
                $em->flush();
                $free = ['code' => $code, 'count' => 10];
            } else {
                $free = ['code' => null, 'count' => 0];
            }
        }

        $request = $this->container->get("request_stack")->getCurrentRequest();

        $templateParams = [
            'invitedCount' => $invitedCount,
            'acceptedCount' => $localizer->formatNumber((int) $acceptedCount),
            'stars' => $localizer->formatNumber((int) $stars),
            'user' => $user,
            'freeCoupons' => $free,
            'facebookKey' => (defined('FACEBOOK_KEY') && empty($request->cookies->get("DisableFB")) ? FACEBOOK_KEY : null),
            'totalRefBonus' => $localizer->formatNumber($this->container->get('aw.referral_income_manager')->getTotalReferralBonusBalanceByUser($user->getUserid())),
            'csrfToken' => GetFormToken(),
        ];

        return parent::getWidgetContent($templateParams);
    }

    private function getFreeCouponsCount(int $userId): int
    {
        $likeCode = 'Invite-' . $userId . '-%';

        return $this->container->get("database_connection")->executeQuery("
        SELECT 
          Created.Cnt - Used.Cnt AS Free 
        FROM 
          (SELECT COUNT(*) AS Cnt FROM Coupon WHERE Code LIKE :likeCode) AS Created,
          (SELECT COUNT(*) AS Cnt FROM Cart WHERE PayDate IS NOT NULL AND CouponCode LIKE :likeCode) AS Used
        ", ['likeCode' => $likeCode])->fetchColumn();
    }
}
