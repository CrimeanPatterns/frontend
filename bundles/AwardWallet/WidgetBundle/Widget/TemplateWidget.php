<?php

namespace AwardWallet\WidgetBundle\Widget;

use AwardWallet\WidgetBundle\Widget\Classes\AbstractWidget;

class TemplateWidget extends AbstractWidget
{
    /**
     * @var string
     */
    protected $template;

    /**
     * @var array
     */
    protected $params;

    public function __construct($template, $params = [])
    {
        parent::__construct();
        $this->template = $template;
        $this->params = $params;
    }

    public function getWidgetContent($options = [])
    {
        $options = array_merge($options, $this->params);

        return $this->renderTemplate($options);
    }

    /**
     * @return string
     */
    public function getTemplate()
    {
        return $this->template;
    }

    /**
     * @param string $template
     */
    public function setTemplate($template)
    {
        $this->template = $template;
    }

    /**
     * @param array $parameters
     * @return string|void
     */
    protected function renderTemplate($parameters = [])
    {
        return $this->container->get('twig')->render('@AwardWalletWidget/' . $this->template, $parameters);
    }
}
