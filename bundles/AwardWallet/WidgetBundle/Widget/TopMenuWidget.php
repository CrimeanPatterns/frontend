<?php

namespace AwardWallet\WidgetBundle\Widget;

use AwardWallet\WidgetBundle\Widget\Classes\UserWidgetTrait;

class TopMenuWidget extends MenuWidget
{
    use UserWidgetTrait;

    public function getWidgetContent($options = [])
    {
        /** @var LinkWidget[] $this */
        foreach (['community', 'supported', 'about', 'blog', 'privacy', 'terms', 'media', 'cards', 'faqs', 'contactUs'] as $key) {
            if ($this[$key]) {
                $this[$key]->setNewTab(true);
            }
        }

        $httpRequest = $this->container->get('request_stack')->getCurrentRequest();
        $route = $httpRequest->get('_route');
        $params = $httpRequest->get('_route_params');

        $routes = [
            'supported' => 'aw_supported',
            'promos' => 'aw_promotions_index',
            'contact' => 'aw_contactus_index',
            'faqs' => 'aw_faq_index',
            'api' => 'aw_api_doc',
            'about' => ['aw_page_index', ['page' => 'about']],
            'partners' => ['aw_page_index', ['page' => 'partners']],
        ];

        foreach ($routes as $key => $value) {
            if (!$this[$key]) {
                continue;
            }

            if (is_string($value) && ($value === $route || $value . '_locale' === $route)) {
                $this[$key]->setActive();

                break;
            }

            if (is_array($value)) {
                if ($value[0] === $route || $value[0] . '_locale' === $route) {
                    $match = true;

                    foreach ($value[1] as $param => $val) {
                        if (!array_key_exists($param, $params)) {
                            $match = false;
                        }

                        if ($params[$param] !== $val) {
                            $match = false;
                        }
                    }

                    if ($match) {
                        $this[$key]->setActive();

                        break;
                    }
                }
            }
        }

        $options['items'] = $this->getItems();

        return $this->container->get('twig')->render('@AwardWalletWidget/' . $this->template, $options);
    }
}
