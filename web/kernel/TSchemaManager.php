<?php

use AwardWallet\MainBundle\Entity\CartItem\BalanceWatchCredit;
use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\Service\Billing\PlusManager;
use AwardWallet\MainBundle\Service\Cache\Invalidator;

require_once __DIR__ . "/../lib/classes/TBaseSchemaManager.php";

require_once __DIR__ . "/../kernel/TCartManager.php"; // load TCartManager to prevent Cart creation on loading

class TSchemaManager extends TBaseSchemaManager
{
    public function ExploreSchema()
    {
        parent::ExploreSchema();
        $this->Tables["Invites"]["CopyAllowed"] = false;
        $this->Tables["MobileKey"]["CopyAllowed"] = false;
        $this->Tables["VTLog"]["CopyAllowed"] = false;
        $this->Tables["RedirectHit"]["CopyAllowed"] = false;
    }

    // return referenced table basing on source fieldname and tablename
    // return null if can't find reference
    public function ReferencedTable($sTable, $sFieldName)
    {
        if (($sTable == "UserAgent") && ($sFieldName == "AgentID")) {
            return "Usr";
        }

        if ('UserID' === $sFieldName && in_array($sTable, ['ContactUs', 'UsrDeleted'])) {
            return null;
        }

        if (($sTable == "UserAgent") && ($sFieldName == "ClientID")) {
            return "Usr";
        }

        if (($sTable == "Invites") && ($sFieldName == "InviterID")) {
            return "Usr";
        }

        if (($sTable == "RememberMeToken") && ($sFieldName == "UserID")) {
            return null;
        }

        if (($sTable == "AccountHistory") && ($sFieldName == "MerchantID")) {
            return null;
        }

        // if( ( $sTable == "Invites" ) && ( $sFieldName == "InviteeID" ) )
        //	return "Usr";
        return parent::ReferencedTable($sTable, $sFieldName);
    }

    // returns sql for child row
    protected function ChildRowsQuery($sSubTable, $sSubKey, $nID)
    {
        if (($sSubTable == "Cart") && ($sSubKey == "UserID")) {
            return "select * from $sSubTable where $sSubKey = $nID and PayDate is null";
        } else {
            return parent::ChildRowsQuery($sSubTable, $sSubKey, $nID);
        }
    }

    protected function DeleteSingleRow($arRow)
    {
        // do not delete paid orders
        if ($arRow['Table'] == 'Usr') {
            $this->connection->Execute("update Cart set UserID = null where UserID = {$arRow['ID']} and PayDate is not null");
            $user = getSymfonyContainer()->get('doctrine')->getRepository(Usr::class)->find($arRow['ID']);
        }

        if ($arRow['Table'] == 'Provider') {
            getSymfonyContainer()->get(Invalidator::class)->providerStateChanged();
        }

        if ($arRow['Table'] == 'Cart') {
            $clearSubscription = false;
            $q = new TQuery("select PayDate, PaymentType, UserID from Cart where CartID = {$arRow['ID']}", $this->connection);

            if (!empty($q->Fields['PayDate'])) {
                $expireUserId = $q->Fields['UserID'];
            }

            $q2 = new TQuery("select 1 
                from CartItem ci 
                join Usr u on u.LastSubscriptionCartItemID = ci.CartItemID
                where ci.CartID = {$arRow['ID']}");

            if ($q2->EOF && in_array($q->Fields['PaymentType'], [\AwardWallet\MainBundle\Entity\Cart::PAYMENTTYPE_APPSTORE, \AwardWallet\MainBundle\Entity\Cart::PAYMENTTYPE_ANDROIDMARKET])) {
                $clearSubscription = true;
            }

            if ($clearSubscription) {
                $em = getSymfonyContainer()->get('doctrine.orm.entity_manager');
                $logger = getSymfonyContainer()->get('logger');
                $logger->info("clearing user mobile subscription because of cart deletion");
                /** @var Usr $user */
                $user = $em->find(Usr::class, $q->Fields['UserID']);
                $user->clearSubscription();
                $em->flush();
            }
        } elseif ($arRow['Table'] == 'CartItem') {
            $cartItem = getSymfonyContainer()->get('doctrine.orm.entity_manager')->getConnection()->fetchAssoc('
				SELECT ci.CartID, ci.TypeID, ci.Cnt, c.UserID
				FROM CartItem ci, Cart c
				WHERE ci.CartItemID = ' . $arRow['ID'] . ' AND c.CartID = ci.CartID'
            );

            if (!empty($cartItem)) {
                if (BalanceWatchCredit::TYPE === (int) $cartItem['TypeID']) {
                    $expiredBalanceWatchCredit = $cartItem;
                    $expiredBalanceWatchCredit['downgradeCount'] = $cartItem['Cnt'];
                }
            }
        }

        parent::DeleteSingleRow($arRow);

        // after delete
        if (!empty($expireUserId)) {
            /** @var PlusManager $plusManager */
            $plusManager = getSymfonyContainer()->get(PlusManager::class);
            $user = getSymfonyContainer()->get('doctrine')->getRepository(Usr::class)->find($expireUserId);
            $plusManager->checkExpirationAndDowngrade($user);
            $plusManager->calculateAt201ExpirationDate($user);
        }

        if (!empty($expiredBalanceWatchCredit)) {
            $user = getSymfonyContainer()->get('doctrine')->getRepository(Usr::class)->find($expiredBalanceWatchCredit['UserID']);
            $downgradeCount = $user->getBalanceWatchCredits() > $expiredBalanceWatchCredit['downgradeCount'] ? ($user->getBalanceWatchCredits() - $expiredBalanceWatchCredit['downgradeCount']) : 0;
            $user->setBalanceWatchCredits($downgradeCount);
            getSymfonyContainer()->get('doctrine.orm.entity_manager')->flush();
        }
    }

    protected function doDeleteSingleRow($arRow)
    {
        $this->connection->Delete($arRow["Table"], $arRow['ID']);
    }
}
