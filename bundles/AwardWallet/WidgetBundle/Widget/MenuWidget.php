<?php

namespace AwardWallet\WidgetBundle\Widget;

use AwardWallet\WidgetBundle\Widget\Classes\AbstractWidgetContainer;
use AwardWallet\WidgetBundle\Widget\Classes\WidgetInterface;

class MenuWidget extends AbstractWidgetContainer
{
    protected $template;
    protected $params;

    public function __construct($template = 'menu.html.twig', $menu = [], $params = [])
    {
        parent::__construct();
        $this->template = $template;
        $this->params = $params;

        foreach ($menu as $key => $item) {
            if ($item instanceof WidgetInterface) {
                $this->addItem($item, $key);
            } elseif (is_array($item)) {
                $options = array_key_exists('items', $item) ? ['items' => $item['items']] : [];

                if (array_key_exists('route', $item)) {
                    $options['isRoute'] = true;
                    $options['params'] = $item['params'] ?? [];
                    $this->addItem(new LinkWidget($item['route'], $item['trans'], $options), $key);
                } elseif (array_key_exists(0, $item)) {
                    $this->addItem(new LinkWidget($item[0], $item[1], $options), $key);
                } else {
                    $this->addItem(new LinkWidget($item['url'], $item['text'], $item), $key);
                }
            } else {
                $this->addItem(new LinkWidget($key, $item));
            }
        }
    }

    public function getWidgetContent($options = [])
    {
        $options = array_merge($options, $this->params);
        $options['items'] = $this->getItems();

        return $this->container->get('twig')->render('@AwardWalletWidget/' . $this->template, $options);
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
}
