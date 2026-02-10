<?php

namespace AwardWallet\WidgetBundle\Widget;

use AwardWallet\WidgetBundle\Widget\Classes\UserWidgetInterface;
use AwardWallet\WidgetBundle\Widget\Classes\UserWidgetTrait;
use Symfony\Component\DependencyInjection\Container;

class BusinessButtonTripsWidget extends TemplateWidget implements UserWidgetInterface
{
    use UserWidgetTrait;

    protected $template;
    protected $tripsCount;

    public function getWidgetContent($options = [])
    {
        $options = array_merge($options, $this->params, ButtonTripsWidget::getActiveOptions($this->container->get("request_stack")->getCurrentRequest()));

        /*$user = $this->getCurrentUser()->getBusiness();

        $data = \Cache::getInstance()->get($this->getCacheKey());
        if ($data === false) {
            $manager = $this->container->get('aw.timeline.manager');
            if(empty($this->tripsCount))
                $this->tripsCount = array_sum(array_map(function(array $item){ return $item['count']; }, $manager->getTotals($user)));
            $data['trips'] = $this->tripsCount;

            \Cache::getInstance()->set($this->getCacheKey(), json_encode($data), 10);
        } else {
            $data = @json_decode($data, true);
        }
        $data['user'] = $user;

        $options = array_merge($options, $data);*/

        return $this->renderTemplate($options);
    }

    /**
     * @return int
     */
    public function getTripsCount()
    {
        return $this->tripsCount;
    }

    /**
     * @param int $tripsCount
     */
    public function setTripsCount($tripsCount)
    {
        $this->tripsCount = $tripsCount;
    }

    private function getCacheKey()
    {
        return 'user_widget_button_trips_' . $this->container->get('doctrine.orm.entity_manager')->getRepository(\AwardWallet\MainBundle\Entity\Usr::class)->getBusinessByUser($this->getCurrentUser())->getUserid();
    }
}
