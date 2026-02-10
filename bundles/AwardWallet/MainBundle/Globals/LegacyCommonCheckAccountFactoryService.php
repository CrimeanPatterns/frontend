<?php

namespace AwardWallet\MainBundle\Globals;

class LegacyCommonCheckAccountFactoryService
{
    public function getAutologinFrame($accountId, $successUrl = null)
    {
        return \CommonCheckAccountFactory::getAutologinFrame($accountId, $successUrl);
    }
}
