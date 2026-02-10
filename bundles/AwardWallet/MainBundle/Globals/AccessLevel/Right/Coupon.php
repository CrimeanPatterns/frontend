<?php

namespace AwardWallet\MainBundle\Globals\AccessLevel\Right;

class Coupon extends AbstractRight
{
    public function fetchFields($ids, $filter)
    {
        $connection = $this->em->getConnection();
        $sql = "
			SELECT
				   a.ProviderCouponID AS ID,
				   a.*,
			       t.*
			FROM   ProviderCoupon a
			       LEFT OUTER JOIN
			              ( SELECT sh.ProviderCouponID,
			                      ua.*
			              FROM    ProviderCouponShare sh
			                      JOIN UserAgent ua
			                      ON      sh.UserAgentID    = ua.UserAgentID
			                              AND ua.AgentID    = ?
			                              AND ua.IsApproved = 1
			              WHERE   sh.ProviderCouponID             IN (" . $filter . ")
			              )
			              t
			       ON     t.ProviderCouponID = a.ProviderCouponID
			WHERE  a.ProviderCouponID       IN (" . $filter . ")
		";
        $this->fields = $connection->executeQuery($sql,
            [$this->user->getUserid()],
            [\PDO::PARAM_INT]
        )->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function getAllPermissions()
    {
        return [
            'read', 'edit', 'delete',
        ];
    }

    public function read()
    {
        $result = $this->getDefaultValues();

        foreach ($this->fields as $fields) {
            $r = false;

            if ($this->user->getUserid() == $fields['UserID']) {
                $r = true;
            } else {
                if (!isset($fields['AccessLevel'])) {
                    $r = false;
                } else {
                    $r = in_array($fields['AccessLevel'], [ACCESS_WRITE, ACCESS_ADMIN, ACCESS_BOOKING_MANAGER, ACCESS_BOOKING_VIEW_ONLY, ACCESS_READ_ALL, ACCESS_READ_NUMBER, ACCESS_READ_BALANCE_AND_STATUS]);
                }
            }

            $result[$fields['ID']] = $r;
        }

        return $result;
    }

    public function edit()
    {
        $result = $this->getDefaultValues();

        foreach ($this->fields as $fields) {
            $result[$fields['ID']] = $this->full_rights($fields);
        }

        return $result;
    }

    public function delete()
    {
        return $this->edit();
    }

    protected function full_rights($fields)
    {
        if ($this->user->getUserid() == $fields['UserID']) {
            return true;
        }

        if (!isset($fields['AccessLevel'])) {
            return false;
        }

        return in_array($fields['AccessLevel'], [ACCESS_WRITE, ACCESS_ADMIN, ACCESS_BOOKING_MANAGER, ACCESS_BOOKING_VIEW_ONLY]);
    }
}
