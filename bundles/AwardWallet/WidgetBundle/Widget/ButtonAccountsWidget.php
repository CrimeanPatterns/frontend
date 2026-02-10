<?php

namespace AwardWallet\WidgetBundle\Widget;

use AwardWallet\MainBundle\Service\Counter;
use AwardWallet\WidgetBundle\Widget\Classes\UserWidgetInterface;
use AwardWallet\WidgetBundle\Widget\Classes\UserWidgetTrait;
use Doctrine\ORM\EntityManager;

class ButtonAccountsWidget extends TemplateWidget implements UserWidgetInterface
{
    use UserWidgetTrait;

    protected $template;
    protected $params;

    protected $accountsCount;

    public function getWidgetContent($options = [])
    {
        global $eliteUsers;
        $options = array_merge($options, $this->params);

        $user = $this->getCurrentUser();

        if ($this->accountsCount === null) {
            /** @var EntityManager $em */
            $em = $this->container->get('doctrine.orm.entity_manager');
            $rep = $em->getRepository(\AwardWallet\MainBundle\Entity\Account::class);
            // accounts
            $this->setAccountsCount($this->container->get(Counter::class)->getTotalAccounts($user->getUserid()));
        }

        $options['accounts'] = $this->accountsCount;
        $options['user'] = $user;
        $options['activeAdd'] = '';
        $options['active'] = '';

        $sectionName = '/MainBundle\\\Controller\\\Account/';
        $httpRequest = $this->container->get('request_stack')->getCurrentRequest();
        $controllerName = $httpRequest->attributes->get('_controller');

        $route = $httpRequest->get('_route');

        $addRoutes = [
            'aw_select_provider',
            'aw_account_add',
        ];

        if (array_search($route, $addRoutes) !== false) {
            $options['activeAdd'] = 'active';
        } elseif (preg_match($sectionName, $controllerName)) {
            $options['active'] = 'active';
        }

        $limit = in_array($user->getUserid(), $eliteUsers) ? PHP_INT_MAX : PERSONAL_INTERFACE_MAX_ACCOUNTS;
        $personal = !$this->container->get('security.authorization_checker')->isGranted('SITE_BUSINESS_AREA');
        $options['overlimits'] = intval($this->accountsCount > $limit && $personal);
        $options['onlimits'] = intval($this->accountsCount == $limit && $personal);

        return $this->renderTemplate($options);
    }

    /**
     * @return int
     */
    public function getAccountsCount()
    {
        return $this->accountsCount;
    }

    /**
     * @param int $accountsCount
     */
    public function setAccountsCount($accountsCount)
    {
        $this->accountsCount = $accountsCount;
    }
}
