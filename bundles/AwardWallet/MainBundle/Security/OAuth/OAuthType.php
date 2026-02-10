<?php

namespace AwardWallet\MainBundle\Security\OAuth;

use AwardWallet\MainBundle\Service\EmailParsing\Client\Model\Mailbox;

abstract class OAuthType
{
    public const GOOGLE = Mailbox::TYPE_GOOGLE;
    public const MICROSOFT = Mailbox::TYPE_MICROSOFT;
    public const YAHOO = Mailbox::TYPE_YAHOO;
    public const AOL = Mailbox::TYPE_AOL;
    public const APPLE = 'apple';
}
