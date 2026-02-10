<?php

namespace AwardWallet\WidgetBundle\Widget;

use AwardWallet\MainBundle\Entity\Repositories\UsrRepository;
use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\WidgetBundle\Widget\Classes\UserWidgetTrait;
use AwardWallet\WidgetBundle\Widget\Exceptions\AuthenticationRequiredException;

class LogoWidget extends TemplateWidget
{
    use UserWidgetTrait;

    protected $onlyLogo = false;

    public function getWidgetContent($options = [])
    {
        try {
            $user = $this->getCurrentUser();
        } catch (AuthenticationRequiredException $e) {
            $user = null;
        }

        $options['logo'] = 'logo';
        $options['business'] = '';

        if ($this->container->get('request_stack')->getCurrentRequest()->getHost() == $this->container->getParameter('business_host')) { // no voters in 404 handler
            $options['business'] = 'business';
        }

        $logo = $this->container->get("aw.manager.logo")->getLogo();

        if (!empty($logo->shortName)) {
            $options['logo'] = 'logo ' . $logo->shortName;
        }

        $options['logo'] = strtolower($options['logo']);

        if (!$this->onlyLogo && ($user instanceof Usr) && ACCOUNT_LEVEL_AWPLUS === $user->getAccountlevel()) {
            $options['plus'] = 1;

            /** @var UsrRepository $usrRep */
            $usrRep = $this->container->get('doctrine.orm.entity_manager')->getRepository(\AwardWallet\MainBundle\Entity\Usr::class);
            $options['trial'] = $usrRep->isTrialAccount($user);

            if (empty($user->getSubscription()) && null !== $user->getPlusExpirationDate() && 30 > (int) date_diff($user->getPlusExpirationDate(), date_create())->format('%a')) {
                $options['expires'] = 1;
            }
        }

        return parent::getWidgetContent($options);
    }

    /**
     * @return bool
     */
    public function isOnlyLogo()
    {
        return $this->onlyLogo;
    }

    /**
     * @param bool $onlyLogo
     */
    public function setOnlyLogo($onlyLogo)
    {
        $this->onlyLogo = $onlyLogo;
    }

    private function getCacheKey()
    {
        return 'user_trial_status_' . $this->getCurrentUser()->getUserid();
    }
}
