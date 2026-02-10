<?php

namespace AwardWallet\WidgetBundle\Widget;

use AwardWallet\Common\DateTimeUtils;
use AwardWallet\WidgetBundle\Widget\Classes\UserWidgetInterface;
use AwardWallet\WidgetBundle\Widget\Classes\UserWidgetTrait;

class AddAccountAndPendingButtonsWidget extends TemplateWidget implements UserWidgetInterface
{
    use UserWidgetTrait;

    /**
     * @param array $options
     * @return string
     */
    public function getWidgetContent($options = [])
    {
        $user = $this->getCurrentUser();
        $options['pendingsCount'] = $this->container->get('doctrine')
            ->getRepository(\AwardWallet\MainBundle\Entity\Account::class)
            ->getPendingsQuery($user)
            ->select('count(a)')
            ->getQuery()->getSingleScalarResult();

        $options['showPopup'] = false;

        $memcached = $this->container->get('aw.memcached');
        $key = sprintf('showed_pending_popup_%s', $user->getId());

        if (
            !$memcached->get($key)
            && $options['pendingsCount'] > 0
            && $this->container->get('request_stack')->getCurrentRequest()->get('_route') == 'aw_account_list'
        ) {
            $options['showPopup'] = true;
            $memcached->set($key, true, DateTimeUtils::SECONDS_PER_DAY);
        }

        return parent::getWidgetContent($options);
    }
}
