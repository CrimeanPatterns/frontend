<?php

namespace AwardWallet\WidgetBundle\Widget;

use AwardWallet\MainBundle\Entity\Usr;
use Symfony\Component\DependencyInjection\ContainerInterface;

class LocalesWidget extends TemplateWidget
{
    public function getWidgetContent($options = [])
    {
        if (!$this->checkContext()) {
            return '';
        }

        $request = $this->container->get('request_stack')->getCurrentRequest();
        $lang = $request->getLocale();

        if ($request->attributes->has('_aw_allowed_locales')) {
            $langs = $request->attributes->get('_aw_allowed_locales');
        } else {
            $langs = $this->container->getParameter('locales');
        }

        //		$creator->addButton('user.settings.language.' . $lng, '#', 'icon-flag-' . $lng, 'disabled smaller');
        //		$creator->addButton('', '#', '', 'flat')
        //			->addPullMenu()
        // //            ->addPullItem('Sorry this feature is not available now','','',null,'icon-flag-en')
        //			->addPullItem('user.settings.language.en', '?locale=en_US', '', NULL, 'icon-flag-en')
        //			->addPullItem('user.settings.language.ru', '?locale=ru_RU', '', NULL, 'icon-flag-ru')
        //			->addPullItem('user.settings.language.de', '?locale=de_DE', '', NULL, 'icon-flag-de')
        //			->addPullItem('user.settings.language.fr', '?locale=fr_FR', '', NULL, 'icon-flag-fr')
        //			->addPullItem('user.settings.language.ch', '?locale=ch_CH', '', NULL, 'icon-flag-ch');

        $options['lang'] = $lang;
        $options['langs'] = $langs;

        if (
            ($requestStack = $this->container->get('request_stack', ContainerInterface::NULL_ON_INVALID_REFERENCE))
            && ($request = $requestStack->getCurrentRequest())
        ) {
            $options['current_route'] = $request->attributes->get('_route');
            $options['route_params'] = $request->attributes->get('_route_params');
            unset($options['route_params']['_locale']);

            if (strpos($options['current_route'], '_locale') !== false) {
                $options['current_route'] = str_replace('_locale', '', $options['current_route']);
            }
        }

        // 404 handling
        if (empty($options['current_route'])) {
            $options['current_route'] = 'aw_home';
        }

        if (empty($options['route_params'])) {
            $options['route_params'] = [];
        }

        unset($options['route_params']['_canonical'], $options['route_params']['_alternate']);

        return $this->renderTemplate($options);
    }

    protected function checkContext()
    {
        $token = $this->container->get('security.token_storage')->getToken();

        if (!empty($token) && $token->isAuthenticated()) {
            $user = $token->getUser();

            if ($user instanceof Usr) {
                return false;
            }
        }

        return true;
    }
}
