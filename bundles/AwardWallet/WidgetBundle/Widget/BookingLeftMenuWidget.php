<?php

namespace AwardWallet\WidgetBundle\Widget;

use AwardWallet\MainBundle\FrameworkExtension\AwTokenStorage;
use AwardWallet\WidgetBundle\Widget\Classes\UserWidgetInterface;
use AwardWallet\WidgetBundle\Widget\Classes\UserWidgetTrait;

class BookingLeftMenuWidget extends TemplateWidget implements UserWidgetInterface
{
    use UserWidgetTrait;

    private $activeItem;

    public function getWidgetContent($options = [])
    {
        $user = $this->getCurrentUser();

        $repo = $this->container->get("doctrine.orm.entity_manager")->getRepository(\AwardWallet\MainBundle\Entity\AbRequest::class);
        $options['active'] = $repo->getActiveRequestsCountByUser($user, false);
        $options['archive'] = $repo->getPreviousRequestsCountByUser($user, false);

        if ($this->container->get('security.authorization_checker')->isGranted("SITE_BOOKER_AREA")) {
            $options['queue'] = $repo->getUnreadCountForUser($user, true, $this->container->get(AwTokenStorage::class)->getBusinessUser());
        }
        $options['activeItem'] = $this->activeItem;

        return $this->renderTemplate($options);
    }

    /**
     * set active menu item. accepted values: 'active', 'archive', 'add'.
     */
    public function setActiveItem($item)
    {
        $this->activeItem = $item;
    }
}
