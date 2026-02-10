<?php

namespace AwardWallet\WidgetBundle\Widget;

use AwardWallet\WidgetBundle\Widget\Classes\UserWidgetInterface;
use AwardWallet\WidgetBundle\Widget\Classes\UserWidgetTrait;
use Doctrine\ORM\EntityManager;

class ButtonBookingsWidget extends TemplateWidget implements UserWidgetInterface
{
    use UserWidgetTrait;

    protected $template;
    protected $params;

    protected $activeBookingsCount;

    public function getWidgetContent($options = [])
    {
        $options = array_merge($options, $this->params);

        $user = $this->getCurrentUser();

        if ($this->activeBookingsCount === null) {
            /** @var EntityManager $em */
            $em = $this->container->get('doctrine.orm.entity_manager');
            $rep = $em->getRepository(\AwardWallet\MainBundle\Entity\AbRequest::class);
            // Balances
            $this->activeBookingsCount = $rep->getActiveRequestsCountByUser($user);

            if (empty($this->activeBookingsCount)) {
                $this->activeBookingsCount = 0;
            }
        }

        $options['bookings'] = $this->activeBookingsCount;
        $options['user'] = $user;

        $options['activeAdd'] = '';
        $options['active'] = '';

        $sectionName = '/MainBundle\\\Controller\\\Booking/';
        $httpRequest = $this->container->get('request_stack')->getCurrentRequest();
        $controllerName = $httpRequest->attributes->get('_controller');

        $route = $httpRequest->get('_route');

        $addRoutes = [
            'aw_booking_add_index',
        ];

        if (array_search($route, $addRoutes) !== false) {
            $options['activeAdd'] = 'active';
        } elseif (preg_match($sectionName, $controllerName)) {
            $options['active'] = 'active';
        }

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
