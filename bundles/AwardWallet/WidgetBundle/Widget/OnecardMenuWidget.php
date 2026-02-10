<?php

namespace AwardWallet\WidgetBundle\Widget;

use AwardWallet\WidgetBundle\Widget\Classes\AbstractWidgetContainer;
use AwardWallet\WidgetBundle\Widget\Classes\UserWidgetInterface;
use AwardWallet\WidgetBundle\Widget\Classes\UserWidgetTrait;

class OnecardMenuWidget extends AbstractWidgetContainer implements UserWidgetInterface
{
    use UserWidgetTrait;

    protected $template;
    protected $params;

    public function __construct($template, $params = [])
    {
        parent::__construct();
        $this->template = $template;
        $this->params = $params;
    }

    public function init()
    {
        if (parent::init() === true) {
            return true;
        }

        $translator = $this->container->get('translator');
        $router = $this->container->get('router');

        $this->addItem(new PersonButtonWidget($translator->trans('menu.onecard.new', [], 'menu'), $router->generate('aw_one_card'), 'new'), 'new');
        $this->addItem(new PersonButtonWidget($translator->trans('menu.onecard.history', [], 'menu'), '/onecard/history.php', 'history'), 'history');

        //		$this->setActiveItem(0);
    }

    public function getWidgetContent($options = [])
    {
        $options = array_merge($options, $this->params);

        $options['items'] = $this->getItems();

        return $this->container->get('twig')->render('@AwardWalletWidget/' . $this->template, $options);
    }

    public function isVisible()
    {
        return $this->visible;
    }
}
