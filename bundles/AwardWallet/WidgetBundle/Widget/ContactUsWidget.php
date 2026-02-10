<?php

namespace AwardWallet\WidgetBundle\Widget;

use AwardWallet\WidgetBundle\Widget\Classes\AbstractWidgetContainer;
use AwardWallet\WidgetBundle\Widget\Classes\UserWidgetInterface;
use AwardWallet\WidgetBundle\Widget\Classes\UserWidgetTrait;

class ContactUsWidget extends AbstractWidgetContainer implements UserWidgetInterface
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

        $this->addItem(new PersonButtonWidget($translator->trans('menu.contact-us', [], 'menu'), $router->generate('aw_contactus_index'), 'contactus'), 'contactus');

        $this->addItem(new PersonButtonWidget($translator->trans('menu.faqs', [], 'menu'), $router->generate('aw_faq_index'), 'faqs'), 'faqs');

        $this->addItem(new PersonButtonWidget($translator->trans('menu.provider-dashboard', [], 'menu'), '/status', 'providerhealth'), 'providerhealth');

        $this->setActiveItem(0);
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
