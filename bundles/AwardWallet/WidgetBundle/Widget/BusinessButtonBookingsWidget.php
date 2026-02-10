<?php

namespace AwardWallet\WidgetBundle\Widget;

use AwardWallet\MainBundle\FrameworkExtension\AwTokenStorage;
use AwardWallet\WidgetBundle\Widget\Classes\UserWidgetInterface;
use AwardWallet\WidgetBundle\Widget\Classes\UserWidgetTrait;

class BusinessButtonBookingsWidget extends TemplateWidget implements UserWidgetInterface
{
    use UserWidgetTrait;

    protected $template;
    protected $params;

    protected $activeBookingsCount;

    public function getWidgetContent($options = [])
    {
        $options = array_merge($options, $this->params);

        $user = $this->getCurrentUser();
        $rep = $this->container->get("doctrine.orm.entity_manager")->getRepository(\AwardWallet\MainBundle\Entity\AbRequest::class);

        if ($this->container->get('security.authorization_checker')->isGranted("SITE_BOOKER_AREA")) {
            $options['bookings'] = $rep->getUnreadCountForUser($user, true, $this->container->get(AwTokenStorage::class)->getBusinessUser());
        } else {
            $options['bookings'] = 0;
        }
        $options['user'] = $user;
        $options['active'] = 'aw_booking_list_queue' === $this->container->get('request_stack')->getCurrentRequest()->get('_route') ? 'active' : '';

        return $this->renderTemplate($options);
    }

    public function getActiveBookingsCount()
    {
        return $this->activeBookingsCount;
    }

    public function setActiveBookingsCount($activeBookingsCount)
    {
        $this->activeBookingsCount = $activeBookingsCount;
    }
}
