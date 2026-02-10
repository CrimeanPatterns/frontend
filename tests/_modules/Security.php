<?php

namespace Codeception\Module;

class Security extends \Codeception\Module
{
    public function addAwUserToGroup(int $userId, string $groupName)
    {
        /** @var CustomDb $db */
        $db = $this->getModule('CustomDb');
        $groupId = $db->grabFromDatabase("SiteGroup", "SiteGroupID", ["GroupName" => $groupName]);

        if ($groupId === false) {
            throw new \Exception("Group $groupName not found");
        }

        $db->haveInDatabase('GroupUserLink', ['UserID' => $userId, 'SiteGroupID' => $groupId]);
    }
}
