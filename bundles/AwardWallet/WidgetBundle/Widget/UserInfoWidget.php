<?php

namespace AwardWallet\WidgetBundle\Widget;

use AwardWallet\MainBundle\Entity\Cart;
use AwardWallet\MainBundle\Service\UserAvatar;
use AwardWallet\WidgetBundle\Widget\Classes\UserWidgetInterface;
use AwardWallet\WidgetBundle\Widget\Classes\UserWidgetTrait;
use Doctrine\ORM\EntityManager;

class UserInfoWidget extends TemplateWidget implements UserWidgetInterface
{
    use UserWidgetTrait;

    /**
     * @var UserAvatar
     */
    private $avatar;

    public function __construct(UserAvatar $userAvatar, $template, array $params = [])
    {
        parent::__construct($template, $params);

        $this->avatar = $userAvatar;
    }

    public function getWidgetContent($options = [])
    {
        $user = isGranted('SITE_BUSINESS_AREA') ? $this->container->get('doctrine')->getRepository(\AwardWallet\MainBundle\Entity\Usr::class)->getBusinessByUser($this->getCurrentUser()) : $this->getCurrentUser();

        /** @var EntityManager $em */
        $em = $this->container->get('doctrine.orm.entity_manager');

        $request = $this->container->get('request_stack')->getCurrentRequest();
        $lang = $request->getLocale();
        $region = $user->getRegion();

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

        $cartRep = $em->getRepository(\AwardWallet\MainBundle\Entity\Cart::class);
        /** @var Cart $currentSubscription */
        $currentSubscription = $cartRep->getActiveAwSubscription($user);

        $options['user'] = $user;
        $options['lang'] = $lang;
        $options['langs'] = $langs;
        $options['region'] = $region;
        $options['avatar'] = $this->avatar->getUserUrl($user, false);
        $options['plus'] = $user->getAccountlevel() == ACCOUNT_LEVEL_AWPLUS;
        $options['subscription'] = $options['plus'] && $user->getSubscription() !== null;

        return $this->renderTemplate($options);
    }
}
