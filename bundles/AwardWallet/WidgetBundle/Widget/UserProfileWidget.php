<?php

namespace AwardWallet\WidgetBundle\Widget;

use AwardWallet\WidgetBundle\Widget\Classes\AbstractWidgetContainer;
use AwardWallet\WidgetBundle\Widget\Classes\UserWidgetInterface;
use AwardWallet\WidgetBundle\Widget\Classes\UserWidgetTrait;
use Doctrine\ORM\EntityManager;

class UserProfileWidget extends AbstractWidgetContainer implements UserWidgetInterface
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

        $user = $this->getCurrentUser();

        /** @var EntityManager $em */
        $em = $this->container->get('doctrine.orm.entity_manager');
        $rep = $em->getRepository(\AwardWallet\MainBundle\Entity\Usr::class);

        $checker = $this->container->get('security.authorization_checker');
        $isBusiness = $rep->isAdminBusinessAccount($user->getUserid());
        $businessArea = $checker->isGranted("SITE_BUSINESS_AREA");
        $isStaff = $checker->isGranted("ROLE_STAFF");
        $isBooking = $checker->isGranted("SITE_BOOKER_AREA");

        if (!$businessArea) {
            $this->addItem(new PersonButtonWidget($translator->trans('menu.my-account', [], 'menu'), $router->generate('aw_profile_overview'), 'profile'), 'profile');

            $this->addItem(new PersonButtonWidget($translator->trans('menu.personal-settings', [], 'menu'), $router->generate('aw_profile_personal'), 'personal'), 'personal');

            $this->addItem(new PersonButtonWidget($translator->trans('menu.regional-settings', [], 'menu'), $router->generate('aw_profile_regional'), 'regional'), 'regional');

            $this->addItem(new PersonButtonWidget($translator->trans('menu.edit-notifications', [], 'menu'), $router->generate('aw_profile_notifications'), 'notifications'), 'notifications');

            $this->addItem(new PersonButtonWidget($translator->trans('menu.edit-website-settings', [], 'menu'), $router->generate('aw_profile_settings'), 'websettings'), 'websettings');

            $this->addItem(new PersonButtonWidget($translator->trans('menu.upgrade-coupon', [], 'menu'), $router->generate('aw_users_usecoupon'), 'coupon'), 'coupon');

            $this->addItem(new PersonButtonWidget($translator->trans('menu.upgrade-membership', [], 'menu'), $router->generate('aw_users_pay'), 'upgrade'), 'upgrade');

            if (!$isBusiness) {
                $this->addItem(new PersonButtonWidget($translator->trans('menu.convert-to-business', [], 'menu'), $router->generate('aw_user_convert_to_business'), 'business'), 'business');
            }

            $cards = $em->getRepository(\AwardWallet\MainBundle\Entity\Onecard::class)->OneCardsCountByUser($user->getUserid());

            $this->addItem(new PersonButtonWidget($translator->trans('menu.onecard.order', ['%count%' => $cards['Left']], 'menu'), $router->generate('aw_one_card'), 'onecard'), 'onecard');

            $this->addItem(new PersonButtonWidget($translator->trans('menu.account-delete', [], 'menu'), $router->generate('aw_user_delete'), 'delete'), 'delete');
        } else {
            $businessAccounts = $checker->isGranted('BUSINESS_ACCOUNTS');

            if ($businessAccounts) {
                $this->addItem(new PersonButtonWidget(
                    $translator->trans('menu.business-account', [], 'menu'),
                    '/user/profile',
                    'profile'
                ), 'profile');

                $this->addItem(new PersonButtonWidget(
                    $translator->trans('user.business.title', [], 'messages'),
                    '/user/business-info',
                    'business-info'
                ), 'business-info');
                $this->addItem(new PersonButtonWidget(
                    $translator->trans('menu.personal-settings', [], 'menu'),
                    $router->generate('aw_profile_personal'),
                    'personal'
                ), 'personal');
            }

            if ($isBooking) {
                $this->addItem(new PersonButtonWidget($translator->trans('menu.edit-notifications', [], 'menu'), $router->generate('aw_profile_notifications'), 'notifications'), 'notifications');
            }

            if ($businessAccounts) {
                $this->addItem(new PersonButtonWidget(
                    $translator->trans('menu.business-api', [], 'menu'),
                    $router->generate('aw_profile_business_api'),
                    'api'
                ), 'api');
            }

            if ($checker->isGranted("USER_BUSINESS_ADMIN")) {
                $this->addItem(new PersonButtonWidget($translator->trans('menu.account-delete', [], 'menu'), $router->generate('aw_user_delete'), 'delete'), 'delete');
            }
        }

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
