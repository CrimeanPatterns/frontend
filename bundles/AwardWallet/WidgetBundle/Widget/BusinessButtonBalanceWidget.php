<?php

namespace AwardWallet\WidgetBundle\Widget;

use AwardWallet\MainBundle\Globals\Localizer\LocalizeService;
use AwardWallet\WidgetBundle\Widget\Classes\UserWidgetInterface;
use AwardWallet\WidgetBundle\Widget\Classes\UserWidgetTrait;

class BusinessButtonBalanceWidget extends TemplateWidget implements UserWidgetInterface
{
    use UserWidgetTrait;

    protected $balance;

    public function getWidgetContent($options = [])
    {
        $options = array_merge($options, $this->params);
        $user = $this->getCurrentUser();

        if ($business = $this->container->get('doctrine.orm.entity_manager')->getRepository(\AwardWallet\MainBundle\Entity\Usr::class)->getBusinessByUser($user)) {
            $translator = $this->container->get('translator');
            $businessInfo = $business->getBusinessInfo();
            $balance = $businessInfo->getBalance();
            $isTrial = $businessInfo->isTrial();
            $isInsufficientBalance = $businessInfo->isInsufficientBalance();

            if ($isTrial) {
                $this->balance = $translator->trans(/** @Desc("Trial") */ 'trial');
            } elseif ($isInsufficientBalance) {
                $this->balance = $translator->trans(/** @Desc("Insufficient") */ 'balance_insufficient');
            } else {
                $localizer = $this->container->get(LocalizeService::class);
                $this->balance = $localizer->formatCurrency($balance, 'USD', false);
            }
            $options['balance'] = $this->balance;
            $options['active'] = 'aw_business_balance' === $this->container->get('request_stack')->getCurrentRequest()->get('_route');

            return $this->renderTemplate($options);
        }

        return '';
    }

    public function getBalance()
    {
        return $this->balance;
    }

    public function setBalance($balance)
    {
        $this->balance = $balance;
    }
}
