<?php

namespace AwardWallet\WidgetBundle\Widget;

use AwardWallet\MainBundle\Timeline\Manager;
use AwardWallet\WidgetBundle\Widget\Classes\UserWidgetInterface;
use AwardWallet\WidgetBundle\Widget\Classes\UserWidgetTrait;
use Symfony\Component\HttpFoundation\Request;

class ButtonTripsWidget extends TemplateWidget implements UserWidgetInterface
{
    use UserWidgetTrait;

    protected $template;
    protected $params;

    protected $tripsCount;

    public function getWidgetContent($options = [])
    {
        $options = array_merge($options, $this->params);

        $user = $this->getCurrentUser();

        $data = \Cache::getInstance()->get($this->getCacheKey());

        if ($data === false) {
            $manager = $this->container->get(Manager::class);

            if (empty($this->tripsCount)) {
                $totals = $manager->getTotals($user);
                $owner = array_shift($totals);
                $this->tripsCount = $owner['count'];
            }
            $data['trips'] = $this->tripsCount;

            \Cache::getInstance()->set($this->getCacheKey(), json_encode($data), 10);
        } else {
            $data = @json_decode($data, true);
        }
        $data['user'] = $user;

        $options = array_merge($options, $data, $this->getActiveOptions($this->container->get('request_stack')->getCurrentRequest()));

        return $this->renderTemplate($options);
    }

    public static function getActiveOptions(Request $request)
    {
        $activeAdd = in_array(
            $request->get('_route'),
            [
                'aw_trips_add',
                'aw_business_trips_add',
                'aw_trips_retrieve_confirmation',
                'aw_trips_update',
            ]
        );

        return [
            'activeAdd' => $activeAdd,
            'active' => !$activeAdd && preg_match('/TripsController|TimelineController/', $request->attributes->get('_controller')),
        ];
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
        return 'user_widget_button_trips_' . $this->getCurrentUser()->getUserid();
    }
}
