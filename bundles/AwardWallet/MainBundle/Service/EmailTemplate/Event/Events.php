<?php

namespace AwardWallet\MainBundle\Service\EmailTemplate\Event;

class Events
{
    public const EVENT_USERS_FOUND = 'email.data-provider.users-found';
    public const EVENT_PRE_SEND = 'email.data-provider.pre-send';
    public const EVENT_POST_SEND = 'email.data-provider.post-send';
    public const EVENT_PROGRESS = 'email.data-provider.progress';
}
