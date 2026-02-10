<?php

namespace AwardWallet\MainBundle\Security\Voter;

class ClientPasswordAccessVoter extends AbstractVoter
{
    public function clientPasswordAccess(): bool
    {
        $checker = $this->container->get("security.authorization_checker");

        if ($checker->isGranted('SITE_MOBILE_APP')) {
            return true;
        }

        $request = $this->container->get("request_stack")->getMasterRequest();

        if ($request === null) {
            return false;
        }

        $host = parse_url($request->headers->get('Referer'), PHP_URL_HOST);

        return $host === $request->getHost();
    }

    protected function getAttributes()
    {
        return [
            'CLIENT_PASSWORD_ACCESS' => [$this, 'clientPasswordAccess'],
        ];
    }
}
