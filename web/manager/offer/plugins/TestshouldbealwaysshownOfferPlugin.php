<?php

require_once __DIR__ . '/BaseOfferPlugin.php';

/**
 * This is test offer
 */
class TestshouldbealwaysshownOfferPlugin extends BaseOfferPlugin
{
    public function searchUsers()
    {
        $this->addAllUsers();
    }

    public function checkUser($userId, $offerUserId)
    {
        return true;
    }
}
