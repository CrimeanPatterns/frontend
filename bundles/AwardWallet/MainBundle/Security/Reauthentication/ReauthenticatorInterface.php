<?php

namespace AwardWallet\MainBundle\Security\Reauthentication;

interface ReauthenticatorInterface
{
    public function start(AuthenticatedUser $authUser, string $action, Environment $environment): ReauthResponse;

    public function verify(AuthenticatedUser $authUser, ReauthRequest $request, Environment $environment): ResultResponse;

    public function reset(string $action);

    public function support(AuthenticatedUser $authUser): bool;
}
