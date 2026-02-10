<?php

namespace AwardWallet\WidgetBundle\Widget;

use AwardWallet\MainBundle\FrameworkExtension\Twig\MetaExtension;
use AwardWallet\WidgetBundle\Widget\Classes\AbstractWidget;

class LinkWidget extends AbstractWidget
{
    protected $link;
    protected $text;
    protected $additional;
    protected $params;
    protected $active;
    protected $newTab = false;
    private $items;

    public function __construct($link, $text, $params = [])
    {
        parent::__construct();
        $this->link = $link;
        $this->text = $text;
        $this->params = $params;

        if (!empty($params['items'])) {
            $this->items = $params['items'];
        }
    }

    public function getWidgetContent($options = []): string
    {
        $options = array_merge($options, $this->params);
        $httpRequest = $this->container->get('request_stack')->getCurrentRequest();

        $link = array_key_exists('isRoute', $options)
            ? $this->container->get('router')->generate($this->link, $options['params'] ?? [])
            : $this->link;

        $locale = $httpRequest->attributes->get('_locale');
        $link = $this->cutStartWithDefaultLocale($link, $locale);

        /** @Ignore */
        $text = $this->container->get('translator')->trans($this->text, [], 'menu');

        if ($text === $this->text || strpos($text, $this->text) !== false) {
            $text = $this->container->get('translator')->trans($this->text);
        }

        if ($this->additional) {
            $text .= $this->additional;
        }
        $class = array_key_exists('class', $options) ? $options['class'] : '';
        $class .= $this->active ? ' active' : '';

        $result = "<a href=\"" . htmlspecialchars($link) . "\" class=\"" . htmlspecialchars($class) . "\" " .
            ($this->getNewTab() ? "target='_blank'" : "target='_self'") .
            " rel='noopener'>$text</a>";

        return $result;
    }

    public function subMenu()
    {
        if (empty($this->items)) {
            return '';
        }

        $result = '';
        $translator = $this->container->get('translator');
        $router = $this->container->get('router');
        $authChecker = $this->container->get('security.authorization_checker');
        $httpRequest = $this->container->get('request_stack')->getCurrentRequest();
        $locale = $httpRequest->attributes->get('_locale');

        $fetchLinkParams = function ($item) use ($router, $translator, $locale) {
            $route = $item['route'] ?? null;

            if (!empty($route)) {
                $url = $router->generate($item['route'], $item['params'] ?? []);
            } elseif (array_key_exists('url', $item)) {
                $url = $item['url'];
            }
            $url = $this->cutStartWithDefaultLocale($url, $locale);

            if (array_key_exists('trans', $item)) {
                if (array_key_exists('domain', $item)) {
                    $text = $translator->trans($item['trans'], [], $item['domain']);
                } else {
                    $text = $translator->trans($item['trans'], [], 'menu');

                    if ($text === $item['trans'] || strpos($text, $item['trans']) !== false) {
                        $text = $translator->trans($item['trans'], [], 'messages');
                    }
                }
            } elseif (array_key_exists('text', $item)) {
                $text = $item['text'];
            }

            if (empty($url) || empty($text)) {
                throw new \Exception('Link Item - not enough data');
            }

            return ['url' => $url, 'text' => $text, 'class' => $item['class'] ?? ''];
        };

        $generateItem = static function ($item) use ($fetchLinkParams, $authChecker): string {
            if (array_key_exists('granted', $item) && !$authChecker->isGranted($item['granted'])) {
                return '';
            }

            $params = $fetchLinkParams($item);

            return '<li><a target="_self" ' . (!empty($params['class']) ? ' class="' . htmlspecialchars($params['class']) . '"' : '') . ' href="' . htmlspecialchars($params['url']) . '" rel="noopener">' . $params['text'] . '</a></li>';
        };

        foreach ($this->items as $key => $item) {
            if (array_key_exists('trans', $item)) {
                $result .= $generateItem($item);
            } elseif (is_array($item)) {
                $result .= '<li><ul>';

                foreach ($item as $sub) {
                    $result .= $generateItem($sub);
                }
                $result .= '</ul></li>';
            }
        }

        return $result;
    }

    public function getLink()
    {
        return $this->link;
    }

    public function setLink($link)
    {
        $this->link = $link;
    }

    public function getText()
    {
        return $this->text;
    }

    public function setText($text)
    {
        $this->text = $text;
    }

    public function getAdditional()
    {
        return $this->additional;
    }

    public function setAdditional($addText)
    {
        $this->additional = $addText;
    }

    public function setActive()
    {
        $this->active = true;
    }

    public function getNewTab()
    {
        return $this->newTab;
    }

    public function setNewTab($newTab)
    {
        $this->newTab = $newTab;
    }

    public function setItems(?array $items)
    {
        $this->items = $items;

        return $this;
    }

    public function getItems(): ?array
    {
        return $this->items;
    }

    private function cutStartWithDefaultLocale(string $url, ?string $locale): string
    {
        $url = str_replace('{_locale}', $locale, $url);

        if (0 === strpos($url, '/' . MetaExtension::DEFAULT_LOCALE . '/')) {
            $url = substr($url, strlen('/' . MetaExtension::DEFAULT_LOCALE));
        }

        if (false === strpos($url, 'http')) {
            return str_replace('//', '/', $url);
        }

        return $url;
    }
}
