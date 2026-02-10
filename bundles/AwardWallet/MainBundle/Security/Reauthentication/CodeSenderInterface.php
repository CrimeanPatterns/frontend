<?php

namespace AwardWallet\MainBundle\Security\Reauthentication;

interface CodeSenderInterface
{
    public function send(AuthenticatedUser $authUser, string $code, Environment $environment): SendReport;
}
