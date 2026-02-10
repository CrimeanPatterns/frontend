<?php

namespace AwardWallet\WidgetBundle\Widget;

use AwardWallet\MainBundle\Globals\Localizer\LocalizeService;
use AwardWallet\MainBundle\Service\Counter;
use AwardWallet\WidgetBundle\Widget\Classes\UserWidgetInterface;
use AwardWallet\WidgetBundle\Widget\Classes\UserWidgetTrait;

class BusinessButtonMembersWidget extends TemplateWidget implements UserWidgetInterface
{
    use UserWidgetTrait;

    protected $template;
    protected $params;

    protected $membersCount = 0;

    public function getWidgetContent($options = [])
    {
        $options = array_merge($options, $this->params);

        $localizer = $this->container->get(LocalizeService::class);
        $user = $this->getCurrentUser();
        $route = $this->container->get('request_stack')->getCurrentRequest()->get('_route');

        // fix me
        if ($businessUser = $this->container->get('doctrine.orm.entity_manager')->getRepository(\AwardWallet\MainBundle\Entity\Usr::class)->getBusinessByUser($user)) {
            $this->membersCount = $this->container->get(Counter::class)->getConnections($businessUser->getUserid());
        }

        $options['members'] = $localizer->formatNumber((int) $this->membersCount);
        $options['user'] = $user;
        $options['active'] = 'aw_business_members' === $route ? 'active' : '';
        $options['activeAdd'] = in_array($route, ['aw_create_connection', 'aw_add_agent']);

        return $this->renderTemplate($options);
    }

    /**
     * @return int
     */
    public function getMembersCount()
    {
        return $this->membersCount;
    }

    /**
     * @param int $membersCount
     */
    public function setMembersCount($membersCount)
    {
        $this->membersCount = $membersCount;
    }
}
