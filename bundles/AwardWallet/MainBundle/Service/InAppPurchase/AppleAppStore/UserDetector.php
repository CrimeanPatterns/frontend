<?php

namespace AwardWallet\MainBundle\Service\InAppPurchase\AppleAppStore;

use AwardWallet\MainBundle\Entity\Cart;
use AwardWallet\MainBundle\Entity\Repositories\CartRepository;
use AwardWallet\MainBundle\Entity\Repositories\UsrRepository;
use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\Service\InAppPurchase\Billing;
use AwardWallet\MainBundle\Service\InAppPurchase\Consumable\AwPlus as ConsumableAwPlus;
use AwardWallet\MainBundle\Service\InAppPurchase\LoggerContext;
use Doctrine\Common\Collections\Criteria;
use Psr\Log\LoggerInterface;

class UserDetector
{
    public const TIME_OFFSET_SEC = 20 * 60;
    public const MAX_SCAN_USERS = 5;

    private Billing $billing;

    private LoggerInterface $logger;

    private CartRepository $cartRep;

    private UsrRepository $userRep;

    public function __construct(Billing $billing, LoggerInterface $paymentLogger, CartRepository $cartRepository, UsrRepository $usrRepository)
    {
        $this->billing = $billing;
        $this->logger = $paymentLogger;
        $this->cartRep = $cartRepository;
        $this->userRep = $usrRepository;
    }

    /**
     * @param object $json
     */
    public function detect(string $productId, $json, Provider $provider, ?Usr $currentUser = null, $enableScanUsers = true): ?Usr
    {
        if ($productId === ConsumableAwPlus::class && isset($currentUser)) {
            $this->log("consumable product", $currentUser);

            return $currentUser;
        } elseif ($productId !== ConsumableAwPlus::class) {
            $this->log("subscription", $currentUser);
            $user = $this->detectSubscriptionUser($json);

            if ($user) {
                $this->log("detected", $currentUser, [
                    'detected' => $user->getUserid(),
                ]);

                return $user;
            }

            if (!$enableScanUsers) {
                $this->log("disabled scan users", $currentUser);

                return $this->userOrNull($currentUser);
            }

            $this->log("not detected, scan", $currentUser);

            // scan
            $dates = [];

            if (property_exists($json, 'purchase_date_ms') && !empty($json->purchase_date_ms) && is_numeric($json->purchase_date_ms)) {
                $dates[] = intval($json->purchase_date_ms / 1000);
            }

            if (property_exists($json, 'original_purchase_date_ms') && !empty($json->original_purchase_date_ms) && is_numeric($json->original_purchase_date_ms)) {
                $dates[] = intval($json->original_purchase_date_ms / 1000);
            }

            if (count($dates) === 0) {
                $this->log("missing purchase date", $currentUser);

                return $this->userOrNull($currentUser);
            }

            if (!$this->scanUsers($dates, $provider)) {
                return $this->userOrNull($currentUser);
            }

            $user = $this->detectSubscriptionUser($json);

            if ($user) {
                $this->log("new attempt, detected", $currentUser, [
                    'detected' => $user->getUserid(),
                ]);

                return $user;
            }

            return $this->userOrNull($currentUser);
        }

        return null;
    }

    /**
     * @param object $json
     */
    private function detectSubscriptionUser($json): ?Usr
    {
        $e = Criteria::expr();
        $criteria = Criteria::create()
            ->where(
                $e->andX(
                    $e->neq('paydate', null),
                    $e->neq('user', null),
                    $e->eq('paymenttype', Cart::PAYMENTTYPE_APPSTORE)
                )
            );

        $conditions = [];

        if (property_exists($json, 'transaction_id') && !empty($json->transaction_id)) {
            $conditions[] = $e->eq('billingtransactionid', $json->transaction_id);
        }

        if (property_exists($json, 'web_order_line_item_id') && !empty($json->web_order_line_item_id)) {
            $conditions[] = $e->eq('appleTransactionID', $json->web_order_line_item_id);
        }

        if (property_exists($json, 'original_transaction_id') && !empty($json->original_transaction_id)) {
            $conditions[] = $e->eq('billingtransactionid', $json->original_transaction_id);
        }

        if (count($conditions) > 0) {
            $criteria->andWhere(
                $e->orX(...$conditions)
            );
            $carts = $this->cartRep->matching($criteria);

            if ($carts->count() > 0) {
                return $carts->first()->getUser();
            }
        }

        return null;
    }

    /**
     * @param int[] $payDates
     */
    private function scanUsers(array $payDates, Provider $provider): bool
    {
        $q = $this->userRep->createQueryBuilder('u');
        $e = $q->expr();

        $q
            ->join(Cart::class, 'c', 'WITH', $e->eq('c.user', 'u.userid'))
            ->andWhere($e->eq('c.paymenttype', ':paymentType'))
            ->setParameter(':paymentType', Cart::PAYMENTTYPE_APPSTORE);

        $or = [];

        foreach ($payDates as $i => $payDate) {
            $or[] = $e->andX(
                $e->gte('c.paydate', ':startDate' . $i),
                $e->lte('c.paydate', ':endDate' . $i)
            );
            $q->setParameter(':startDate' . $i, date('Y-m-d H:i:s', $payDate - (self::TIME_OFFSET_SEC / 2)));
            $q->setParameter(':endDate' . $i, date('Y-m-d H:i:s', $payDate + (self::TIME_OFFSET_SEC / 2)));
        }

        $users = $q->andWhere($e->orX(...$or))
            ->setMaxResults(self::MAX_SCAN_USERS)
            ->groupBy('c.user')
            ->getQuery()
            ->getResult();

        if (count($users) === 0) {
            $this->log('carts not found');

            return false;
        }

        foreach ($users as $user) {
            $this->log("scan user", $user);
            $provider->scanSubscriptions($user, $this->billing);
        }

        return true;
    }

    private function userOrNull(?Usr $user = null): ?Usr
    {
        if (isset($user)) {
            $this->log("not detected, return current user", $user);

            return $user;
        }

        return null;
    }

    private function log(string $message, ?Usr $user = null, array $extraData = []): void
    {
        $this->logger->info(sprintf("detect user, %s", $message), array_merge(
            LoggerContext::get($user),
            $extraData
        ));
    }
}
