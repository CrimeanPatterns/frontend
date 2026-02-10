<?php

namespace AwardWallet\WidgetBundle\Widget;

use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\Globals\Localizer\LocalizeService;
use AwardWallet\MainBundle\Service\Counter;
use AwardWallet\WidgetBundle\Widget\Classes\UserWidgetInterface;
use AwardWallet\WidgetBundle\Widget\Classes\UserWidgetTrait;
use Doctrine\ORM\EntityManager;

class BusinessButtonAccountsWidget extends TemplateWidget implements UserWidgetInterface
{
    use UserWidgetTrait;

    protected $template;
    protected $params;

    protected $accountsCount;

    public function getWidgetContent($options = [])
    {
        $options = array_merge($options, $this->params);

        $localizer = $this->container->get(LocalizeService::class);

        if ($this->accountsCount === null) {
            /** @var EntityManager $em */
            $counter = $this->container->get(Counter::class);
            $tokenStorage = $this->container->get("aw.security.token_storage");
            $user = $tokenStorage->getBusinessUser();
            // accounts
            $this->accountsCount = 0;

            if ($user instanceof Usr) {
                $this->accountsCount = $counter->getTotalAccounts($user->getUserid());
            }
        }

        $options['accounts'] = $localizer->formatNumber((int) $this->accountsCount);

        $options['active'] = 'aw_business_account_list' === $this->container->get('request_stack')->getCurrentRequest()->get('_route') ? 'active' : '';

        return $this->renderTemplate($options);
    }

    /**
     * @return int
     */
    public function getAccountsCount()
    {
        return $this->accountsCount;
    }

    /**
     * @param int $accountsCount
     */
    public function setAccountsCount($accountsCount)
    {
        $this->accountsCount = $accountsCount;
    }
}
