<?php

namespace AwardWallet\WidgetBundle\Widget;

use AwardWallet\MainBundle\Entity\Query\UserConnectionsQuery\Connection;
use AwardWallet\MainBundle\Entity\Query\UserConnectionsQuery\UserConnectionsQuery;
use AwardWallet\MainBundle\Entity\Repositories\UseragentRepository;
use AwardWallet\MainBundle\Service\Cache\CacheManager;
use AwardWallet\MainBundle\Service\Cache\Model\CacheItemReference;
use AwardWallet\MainBundle\Service\Cache\Tags;
use AwardWallet\MainBundle\Service\Counter;
use AwardWallet\WidgetBundle\Widget\Classes\AbstractWidgetContainer;
use AwardWallet\WidgetBundle\Widget\Classes\UserWidgetInterface;
use AwardWallet\WidgetBundle\Widget\Classes\UserWidgetTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;

use function AwardWallet\MainBundle\Globals\Utils\f\compareBy;

class AccountsPersonsWidget extends AbstractWidgetContainer implements UserWidgetInterface
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

        $user = $this->getCurrentUser();

        [$pendingConnections, $connectionsData] = $this->container->get(CacheManager::class)->load(new CacheItemReference(
            Tags::getPersonsWidgetKey($user->getUserid()),
            Tags::getPersonsWidgetTags($user->getUserid()),
            function () use ($user) {
                $em = $this->container->get('doctrine.orm.entity_manager');
                /** @var UseragentRepository $uaRep */
                $uaRep = $em->getRepository(\AwardWallet\MainBundle\Entity\Useragent::class);
                $pendingConnections = $uaRep->getUserPendingConnections($user);
                $connectionsData = $this->container->get(UserConnectionsQuery::class)->run(
                    $user,
                    compareBy(function (Connection $connection) { return \strtolower($connection->getFullName()); })
                );

                return [$pendingConnections, $connectionsData];
            }
        ));

        $balances = $this->container->get(Counter::class)->getDetailsCountAccountsByUser($user);
        $router = $this->container->get('router');

        if (count($pendingConnections)) {
            $this->params['pendingCount'] = count($pendingConnections);

            if ($this->params['pendingCount'] > 9) {
                $this->params['pendingCount'] = '9+';
            }
        }

        $request = $this->container->get('request_stack')->getCurrentRequest();

        $agentInUrl = $request->query->get('agentId', null);

        if (
            ($requestStack = $this->container->get('request_stack', ContainerInterface::NULL_ON_INVALID_REFERENCE))
            && ($request = $requestStack->getMasterRequest())
        ) {
            if (is_null($agentInUrl) && in_array($request->attributes->get('_route'), [
                'aw_select_provider',
                'aw_account_add',
                'aw_account_edit',
                'aw_coupon_add',
                'aw_coupon_edit',
            ])) {
                $agentInUrl = 'my';
            }
        }
        $activeMenuItem = 0;
        $masterLink = $router->generate('aw_account_list');

        foreach ($balances as $index => $balance) {
            if ($balance['Count'] == 0 && (isset($balance['AccessLevel']) && $balance['AccessLevel'] != ACCESS_WRITE)) {
                continue;
            }
            $params = [
                'clientId' => $balance['ClientID'] ?? null,
            ];
            $text = html_entity_decode($balance['UserName']);
            $link = $masterLink;

            if (strcasecmp($text, 'all') === 0) {
                $id = 'All';
                /** @Desc("ALL") */
                $text = $this->container->get('translator')->trans('menu.persons.all.title', [], 'menu');
                $params['class'] = 'all';
            } elseif (empty($balance['UserAgentID'])) {
                if (count($balances) === 2) {
                    /** @Desc("My accounts") */
                    $text = $this->container->get('translator')->trans('menu.persons.my_accounts.title', [], 'menu');
                }
                $id = 'my';
                $link .= '?agentId=my';
            } else {
                $id = $balance['UserAgentID'];
                $link .= '?agentId=' . $balance['UserAgentID'];
            }

            if ($id == $agentInUrl) {
                $activeMenuItem = count($this->widgets);
            }

            $button = new PersonButtonWidget($text, $link, $id, $balance['Count'], $params);

            if ($id == 'All') {
                $button->setIsAllButton(true);
            }

            if (isset($balance['AccessLevel']) && $balance['AccessLevel'] == ACCESS_WRITE) {
                $button->setAddLink($router->generate('aw_select_provider', ['agentId' => $id]));
            }
            $this->addItem($button);
        }

        if (count($connectionsData['connections'])) {
            $manageConnectionsButton = new PersonButtonWidget(
                $this->container->get('translator')->trans(/** @Desc("Manage Connections%count%") */ 'menu.connections.manage', ['%count%' => ''], 'menu'),
                $this->container->get('router')->generate('aw_user_connections'),
                'manage-connections',
                count($connectionsData['connections']),
                []);
            $manageConnectionsButton->setAddLink($router->generate('aw_user_connections'));
            $this->addItem($manageConnectionsButton);
        } else {
            $addConnectionButton = new PersonButtonWidget(
                $this->container->get('translator')->trans('menu.header.persons.add-person', [], 'menu'),
                '#',
                'add-new-person',
                'empty',
                ['class' => 'js-add-new-person']);
            $addConnectionButton->setAddLink('#');
            $this->addItem($addConnectionButton);
        }

        $this->setActiveItem($activeMenuItem);
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
