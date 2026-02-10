<?php

namespace AwardWallet\MainBundle\Controller\Manager;

use AwardWallet\MainBundle\Loyalty\ApiCommunicator;
use AwardWallet\MainBundle\Service\ProviderProxyStats;
use AwardWallet\MainBundle\Service\RA\RewardAvailabilityStatus;
use Doctrine\DBAL\Connection;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\ServiceLocator;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/manager/reward-availability-status")
 */
class RewardAvailabilityStatusController extends AbstractController
{
    /** @var RewardAvailabilityStatus */
    private $raStatus;

    /** @var ProviderProxyStats */
    private $proxyStats;

    /** @var ServiceLocator */
    private $communicators;

    /** @var Connection */
    private $connection;

    private array $clustersList;

    /**
     * RewardAvailabilityStatusController constructor.
     */
    public function __construct(RewardAvailabilityStatus $raStatus, ProviderProxyStats $proxyStats, ServiceLocator $loyaltyApiCommunicators, Connection $connection)
    {
        $this->raStatus = $raStatus;
        $this->proxyStats = $proxyStats;
        $this->communicators = $loyaltyApiCommunicators;
        $this->connection = $connection;
        $clustersList = array_diff(array_keys($loyaltyApiCommunicators->getProvidedServices()), ['awardwallet']);

        foreach ($clustersList as $c) {
            $this->clustersList[$c] = $c;
        }
    }

    /**
     * @Security("is_granted('ROLE_MANAGE_REWARD_AVAILABILITY')")
     * @Route("", name="aw_manager_rewardAvailabilityStatus")
     * @Template("@AwardWalletMain/Manager/Support/RewardAvailability/rewardAvalabilityStatus.html.twig")
     */
    public function rewardAvalabilityStatusAction(Request $request)
    {
        $manageLog = $this->canViewLogs();

        [$cluster, $type] = $this->getClusterAndType($manageLog, $request);

        /** @var ApiCommunicator $communicator */
        $communicator = $this->communicators->get($cluster);

        $startDate = $request->get('startDate');
        $endDate = $request->get('endDate');
        $startTime = $request->get('startTime');
        $endTime = $request->get('endTime');
        $priority = $request->get('priority') ?? 0;

        $providers = [];

        if ($type === 'hotel') {
            $providersData = $this->connection->executeQuery(/** @lang MySQL */ "
                SELECT Code, DisplayName FROM Provider WHERE CanCheckRaHotel = 1
            ")->fetchAllAssociative();
        } else {
            $providersData = $this->connection->executeQuery(/** @lang MySQL */ "
                SELECT Code, DisplayName FROM Provider WHERE CanCheckRewardAvailability <> 0
            ")->fetchAllAssociative();
        }

        foreach ($providersData as $row) {
            $providers[$row['Code']] = html_entity_decode($row['DisplayName']);
        }

        if (!isset($startDate, $endDate)) {
            $period = false;
            $hours = $request->get('hours');
            $endDate = time();

            if (!empty($hours)) {
                $minutes = $hours * 60;
                $startDate = strtotime('-' . $minutes . ' minutes', $endDate);
            } else {
                $startDate = strtotime('-24 hours');
            }
        } else {
            $period = true;
            $startDate = strtotime($startTime, strtotime($startDate));
            $endDate = strtotime($endTime, strtotime($endDate));

            if ($endDate < $startDate) {
                $endDate = strtotime("+1 day", $startDate);
            }
        }
        $data = $this->raStatus->search($startDate, $endDate, RewardAvailabilityStatus::ALL, true, $cluster, $type, $priority);
        $startTime = $period && $startDate ? date('H:i', $startDate) : '00:00';
        $endTime = $period && $endDate ? date('H:i', $endDate) : '23:59';

        $rucaptcha = json_decode($communicator->getBalance('rucaptcha'), true);
        $antigate = json_decode($communicator->getBalance('antigate'), true);

        $balance = [
            'rucaptcha' => isset($rucaptcha['balance']) ? number_format($rucaptcha['balance'], 2) : null,
            'antigate' => isset($antigate['balance']) ? number_format($antigate['balance'], 2) : null,
        ];
        $resData = [];

        if ($cluster === 'juicymiles') {
            $resData = $data['aggData'];
            $titleTotal = "states: 1,4,9 and not over 90";
        } else {
            foreach ($data['aggData'] as $p => $res) {
                $resData[$p] = $res;
                $resData[$p]['totalSuccess'] = ($res['success'] ?? 0) + ($res['known'] ?? 0);
            }
            $titleTotal = "states: 1,4,9";
        }

        return [
            'data' => $resData,
            'startDate' => date('m/d/Y H:i', $startDate),
            'endDate' => date('m/d/Y H:i', $endDate),
            'startTime' => $startTime,
            'endTime' => $endTime,
            'period' => $period,
            'providers' => $providers,
            'hours' => $hours ?? 24,
            'manageLog' => $manageLog,
            'cluster' => $cluster,
            'type' => $type,
            'clustersList' => $this->clustersList,
            'balance' => $balance,
            'titleTotal' => $titleTotal,
            'priority' => $priority,
        ];
    }

    /**
     * @Security("is_granted('ROLE_MANAGE_REWARD_AVAILABILITY')")
     * @Route("/proxies", name="aw_manager_rewardAvailabilityProxiesStats")
     * @Template("@AwardWalletMain/Manager/Support/RewardAvailability/proxyAllProvidersStats.html.twig")
     */
    public function proxyAllProvidersStatsAction(Request $request)
    {
        if (!$this->isGranted('ROLE_MANAGE_LOGS')) {
            return new Response('bad request');
        }

        $providerCode = $request->get('providerCode', 'all');
        $startDate = $request->get('startDate');
        $endDate = $request->get('endDate');
        $startTime = $request->get('startTime');
        $endTime = $request->get('endTime');
        [$cluster, $type] = $this->getClusterAndType(true, $request);

        if (!isset($startDate, $endDate)) {
            $period = false;
            $hours = $request->get('hours');
            $endDate = time();

            if (!empty($hours)) {
                $minutes = $hours * 60;
                $startDate = strtotime('-' . $minutes . ' minutes', $endDate);
            } else {
                $startDate = strtotime('-24 hours');
            }
        } else {
            $period = true;
            $startDate = strtotime($startTime, strtotime($startDate));
            $endDate = strtotime($endTime, strtotime($endDate));

            if ($endDate < $startDate) {
                $endDate = strtotime("+1 day", $startDate);
            }
        }

        $data = $this->proxyStats->search($cluster, $type, $startDate, $endDate, $providerCode);

        if (empty($data['prov'])) {
            return new Response('something went wrong');
        }

        $startTime = $period && $startDate ? date('H:i', $startDate) : '00:00';
        $endTime = $period && $endDate ? date('H:i', $endDate) : '23:59';

        return [
            'providerCode' => $providerCode,
            'data' => $data,
            'startDate' => date('m/d/Y H:i', $startDate),
            'endDate' => date('m/d/Y H:i', $endDate),
            'startTime' => $startTime,
            'endTime' => $endTime,
            'period' => $period,
            'hours' => $hours ?? 24,
            'clustersList' => $this->clustersList,
            'cluster' => $cluster,
            'type' => $type,
        ];
    }

    /**
     * @Security("is_granted('ROLE_MANAGE_REWARD_AVAILABILITY')")
     * @Route("/getListError", name="aw_manager_rewardAvailabilityStatus_getListError", methods={"POST"})
     */
    public function getListErrorAction(Request $request)
    {
        $manageLog = $this->canViewLogs();
        $providerCode = $request->get('providerCode');

        if (!isset($providerCode)) {
            return new Response('no errors');
        }

        [$cluster, $type] = $this->getClusterAndType($manageLog, $request);

        $typeState = $request->get('typeState');
        $startDate = $request->get('startDate');
        $endDate = $request->get('endDate');
        $priority = $request->get('priority') ?? 0;

        if (empty($startDate) || empty($endDate)) {
            $startDate = null;
            $endDate = null;
        } else {
            if (!is_numeric($startDate)) {
                $startDate = strtotime($startDate);
            }

            if (!is_numeric($endDate)) {
                $endDate = strtotime($endDate);
            }
        }

        if ($startDate > $endDate) {
            return new Response('something went wrong');
        }

        $data = $this->raStatus->search($startDate, $endDate, $typeState, false, $cluster, $type, $priority, $providerCode);

        if (empty($data['list'])) {
            return new Response('something went wrong');
        }

        if ($type === 'hotel') {
            $data['list'] = array_map(function ($row) {
                $request = json_decode($row['RequestData']);
                $row['Route'] = $request->destination;
                $row['RequestData'] = stripcslashes($row['RequestData']);
                $row['ResponseData'] = stripcslashes($row['ResponseData'] ?? '');

                return $row;
            }, $data['list']);
            $method = 'reward-availability-hotel';
        } else {
            $data['list'] = array_map(function ($row) {
                $request = json_decode($row['RequestData']);
                $row['Route'] = strtoupper($request->departure->airportCode) . '-' . strtoupper($request->arrival);
                $row['RequestData'] = stripcslashes($row['RequestData']);
                $row['ResponseData'] = stripcslashes($row['ResponseData'] ?? '');

                return $row;
            }, $data['list']);
            $method = 'reward-availability';
        }

        return $this->render('@AwardWalletMain/Manager/Support/RewardAvailability/details.html.twig',
            ['data' => $data['list'], 'manageLog' => $manageLog, 'cluster' => $cluster, 'method' => $method, 'type' => $type]);
    }

    /**
     * @Security("is_granted('ROLE_MANAGE_REWARD_AVAILABILITY')")
     * @Route("/getProxyStats", name="aw_manager_rewardAvailabilityStatus_getProxyStats", methods={"POST"})
     */
    public function getProxyStatsAction(Request $request)
    {
        $manageLog = $this->canViewLogs();
        $providerCode = $request->get('providerCode');

        if (isset($providerCode)) {
            return new Response('no statistics of this type by provider');
        }

        [$cluster, $type] = $this->getClusterAndType($manageLog, $request);

        $startDate = $request->get('startDate');
        $endDate = $request->get('endDate');

        if (empty($startDate) || empty($endDate)) {
            $startDate = null;
            $endDate = null;
        } else {
            if (!is_numeric($startDate)) {
                $startDate = strtotime($startDate);
            }

            if (!is_numeric($endDate)) {
                $endDate = strtotime($endDate);
            }
        }

        if ($startDate > $endDate) {
            return new Response('something went wrong');
        }

        $data = $this->proxyStats->search($cluster, $type, $startDate, $endDate);

        if (empty($data['data'])) {
            return new Response('something went wrong');
        }

        return $this->render('@AwardWalletMain/Manager/Support/RewardAvailability/proxyStats.html.twig',
            ['data' => $data['data'], 'manageLog' => $manageLog, 'stat' => $data['stat']]);
    }

    /**
     * @Security("is_granted('ROLE_MANAGE_REWARD_AVAILABILITY')")
     * @Route("/getProviderProxyStats", name="aw_manager_rewardAvailabilityStatus_getProviderProxyStats", methods={"POST"})
     */
    public function getProviderProxyStatsAction(Request $request)
    {
        $manageLog = $this->canViewLogs();
        $providerCode = $request->get('providerCode');

        if (!isset($providerCode)) {
            return new Response('something went wrong');
        }

        [$cluster, $type] = $this->getClusterAndType($manageLog, $request);

        $startDate = $request->get('startDate');
        $endDate = $request->get('endDate');

        if (empty($startDate) || empty($endDate)) {
            $startDate = null;
            $endDate = null;
        } else {
            if (!is_numeric($startDate)) {
                $startDate = strtotime($startDate);
            }

            if (!is_numeric($endDate)) {
                $endDate = strtotime($endDate);
            }
        }

        if ($startDate > $endDate) {
            return new Response('something went wrong');
        }

        $data = $this->proxyStats->search($cluster, $type, $startDate, $endDate, $providerCode);

        if (empty($data['prov'])) {
            return new Response('something went wrong');
        }

        return $this->render('@AwardWalletMain/Manager/Support/RewardAvailability/proxyProviderStats.html.twig',
            ['data' => $data, 'providerCode' => $providerCode, 'manageLog' => $manageLog]);
    }

    private function getClusterAndType(bool $manageLog, Request $request)
    {
        if ($manageLog) {
            $cluster = $request->get('cluster') ?? 'juicymiles';
            $type = $request->get('type') ?? 'flight';

            // TODO tmp
            if ($cluster === 'juicymiles') {
                $type = 'flight';
            }
        } else {
            $cluster = 'juicymiles';
            $type = 'flight';
        }

        return [$cluster, $type];
    }

    private function canViewLogs()
    {
        return $this->isGranted('ROLE_MANAGE_LOGS') || $this->isGranted('ROLE_MANAGE_LOGS_REWARD_AVAILABILITY');
    }
}
