<?php

namespace AwardWallet\WidgetBundle\Widget;

use AwardWallet\MainBundle\Entity\Query\UserConnectionsQuery\Connection;
use AwardWallet\MainBundle\Entity\Query\UserConnectionsQuery\UserConnectionsQuery;
use AwardWallet\MainBundle\Timeline\Manager;
use AwardWallet\WidgetBundle\Widget\Classes\AbstractWidgetContainer;
use AwardWallet\WidgetBundle\Widget\Classes\UserWidgetInterface;
use AwardWallet\WidgetBundle\Widget\Classes\UserWidgetTrait;

use function AwardWallet\MainBundle\Globals\Utils\f\compareBy;

class TripsPersonsWidget extends AbstractWidgetContainer implements UserWidgetInterface
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

        $manager = $this->container->get(Manager::class);
        $totals = $manager->getTotals($user);
        $router = $this->container->get('router');
        $httpRequest = $this->container->get('request_stack')->getCurrentRequest();
        $agentInUrl = $httpRequest->query->get('agentId');

        if (empty($agentInUrl)) {
            $agentInUrl = $httpRequest->request->get('agentId');
        }

        if (is_array($agentInUrl)) {
            $agentInUrl = null;
        }

        if (empty($agentInUrl)) {
            $agentInUrl = $httpRequest->query->get('UserAgentID');
        }

        if (empty($agentInUrl)) {
            $agentInUrl = 'my';
        }

        $em = $this->container->get('doctrine.orm.entity_manager');
        $uaRep = $em->getRepository(\AwardWallet\MainBundle\Entity\Useragent::class);

        $pendingConnections = $uaRep->getUserPendingConnections($user);
        $connectionsData = $this->container->get(UserConnectionsQuery::class)->run(
            $user,
            compareBy(function (Connection $connection) { return \strtolower($connection->getFullName()); })
        );

        if (count($pendingConnections)) {
            $this->params['pendingCount'] = count($pendingConnections);

            if ($this->params['pendingCount'] > 9) {
                $this->params['pendingCount'] = '9+';
            }
        }

        foreach ($totals as $agentId => $agent) {
            $params = [
                'sharedFamilyMember' => $agent['sharedFamilyMember'] ?? null,
                'clientId' => $agent['clientId'] ? $agent['clientId']->getUserid() : null,
            ];

            $button = new PersonButtonWidget(
                $agent['name'],
                $router->generate('aw_timeline') . $agentId,
                $agentId ?: 'my',
                $agent['count'],
                $params
            );

            if (is_null($agent['clientId'])) {
                $button->setAddLink($router->generate('aw_trips_add', ['agentId' => $agentId]));
            } elseif (isset($agent['timeline_access_level']) && $agent['timeline_access_level'] === TRIP_ACCESS_FULL_CONTROL) {
                $button->setAddLink($router->generate('aw_trips_add', ['agentId' => $agentId]));
            }

            $this->addItem($button, 'ua' . ($agentId ?: 'my'));
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
        }

        $this->setActiveItem($agentInUrl ? 'ua' . $agentInUrl : 0);
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
