<?php

namespace AwardWallet\WidgetBundle\Widget;

use AwardWallet\MainBundle\Entity\Query\UserConnectionsQuery\Connection;
use AwardWallet\MainBundle\Entity\Query\UserConnectionsQuery\UserConnectionsQuery;
use AwardWallet\MainBundle\Entity\Repositories\AccountRepository;
use AwardWallet\WidgetBundle\Widget\Classes\AbstractWidgetContainer;
use AwardWallet\WidgetBundle\Widget\Classes\UserWidgetInterface;
use AwardWallet\WidgetBundle\Widget\Classes\UserWidgetTrait;

use function AwardWallet\MainBundle\Globals\Utils\f\compareBy;

class ConnectionsPersonsWidget extends AbstractWidgetContainer implements UserWidgetInterface
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

        $em = $this->container->get('doctrine')->getManager();
        /** @var AccountRepository $accRepo */
        $uaRep = $em->getRepository(\AwardWallet\MainBundle\Entity\Useragent::class);

        $connectionsData = $this->container->get(UserConnectionsQuery::class)->run(
            $user,
            compareBy(function (Connection $connection) { return \strtolower($connection->getFullName()); })
        );

        $request = $this->container->get('request_stack')->getCurrentRequest();
        $userAgentId = $request->query->get('ID', 'all');
        $agentId = $request->get('userAgentId');
        'all' === $userAgentId && !empty($agentId) ? $userAgentId = $agentId : null;

        $pendingConnections = $uaRep->getUserPendingConnections($user);

        if (count($pendingConnections)) {
            $this->params['pendingCount'] = count($pendingConnections);

            if ($this->params['pendingCount'] > 9) {
                $this->params['pendingCount'] = '9+';
            }
        }

        if (count($connectionsData['connections'])) {
            $connectionsCountText = count($connectionsData['connections']) ? count($connectionsData['connections']) : null;

            $item = new PersonButtonWidget(
                $this->container->get('translator')->trans(/** @Desc("Manage Connections%count%") */
                    'menu.connections.manage', ['%count%' => ''], 'menu'),
                $this->container->get('router')->generate('aw_user_connections'),
                'all',
                $connectionsCountText,
                []
            );
            $item->setActive();
            $this->addItem($item, 'all');
        } else {
            $item = new PersonButtonWidget(
                $this->container->get('translator')->trans('menu.header.persons.add-person', [], 'menu'),
                '#',
                'add-new',
                null,
                ['class' => 'wide-row js-add-new-person']
            );

            $item->setActive();
            $this->addItem($item, 'add-new');
        }

        foreach ($connectionsData['connections'] as $index => $connection) {
            if ($connection['ClientID'] && !$connection['IsApproved']) {
                continue;
            }
            $link = $connection['ClientID']
                ? $this->container->get('router')->generate('aw_user_connection_edit', ['userAgentId' => $connection['UserAgentID']])
                : '/user/family/' . $connection['UserAgentID'];

            $item = new PersonButtonWidget(
                htmlspecialchars_decode($connection['FullName']),
                $link,
                $connection['UserAgentID']);

            if ($connection['UserAgentID'] == $userAgentId) {
                $this->setActiveNone();
                $item->setActive();
            }

            $this->addItem($item, $connection['UserAgentID']);
        }
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
