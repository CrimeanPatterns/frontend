<?php

namespace AwardWallet\MainBundle\Globals\AccessLevel\Right;

class Travelplan extends AbstractRight
{
    public function fetchFields($ids, $filter)
    {
        $connection = $this->em->getConnection();
        $sql = "
			SELECT
				   a.TravelPlanID AS ID,
				   a.*,
			       t.*
			FROM   TravelPlan a
			       LEFT OUTER JOIN
			              ( SELECT sh.TravelPlanID,
			                      ua.*
			              FROM    TravelPlanShare sh
			                      JOIN UserAgent ua
			                      ON      sh.UserAgentID    = ua.UserAgentID
			                              AND ua.AgentID    = ?
			                              AND ua.IsApproved = 1
			              WHERE   sh.TravelPlanID             IN (" . $filter . ")
			              )
			              t
			       ON     t.TravelPlanID = a.TravelPlanID
			WHERE  a.TravelPlanID       IN (" . $filter . ")
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
            'update', 'login', 'move',
        ];
    }

    public function read()
    {
        $result = $this->getDefaultValues();

        foreach ($this->fields as $fields) {
            $result[$fields['ID']] = true;
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

    public function update()
    {
        return $this->edit();
    }

    public function login()
    {
        return $this->edit();
    }

    public function move()
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
