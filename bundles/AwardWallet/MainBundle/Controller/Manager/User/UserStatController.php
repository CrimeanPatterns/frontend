<?php

namespace AwardWallet\MainBundle\Controller\Manager\User;

use AwardWallet\MainBundle\FrameworkExtension\AwTokenStorage;
use AwardWallet\MainBundle\Service\SocksMessaging\Client;
use AwardWallet\MainBundle\Service\SocksMessaging\UserMessaging;
use AwardWallet\MainBundle\Service\User\Async\UserStatExecutor;
use AwardWallet\MainBundle\Service\User\Async\UserStatTask;
use AwardWallet\MainBundle\Worker\AsyncProcess\Process;
use Doctrine\DBAL\Connection;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class UserStatController extends AbstractController
{
    /**
     * @Route("/manager/user/stat", name="aw_manager_user_stat_index")
     * @Security("is_granted('ROLE_MANAGE_USERSTAT')")
     * @Template("@AwardWalletMain/Manager/User/UserStat.html.twig")
     * @return array
     */
    public function indexAction(
        Request $request,
        Process $asyncProcess,
        AwTokenStorage $tokenStorage,
        Client $socksClient,
        Connection $connection,
        UserStatExecutor $userStatExecutor
    ) {
        $conditions = $this->getQueryOptions($request->query->all());

        if (!empty($conditions)) {
            $channel = UserMessaging::getChannelName(
                'userstat' . bin2hex(random_bytes(3)),
                $tokenStorage->getUser()->getId()
            );

            $task = new UserStatTask($channel, $conditions);

            $asyncProcess->execute($task);
            // $userStatExecutor->execute($task);
        }

        return [
            'title' => '',
            'channel' => $channel ?? null,
            'centrifuge_config' => $socksClient->getClientData(),
            'baseLeads' => $connection->fetchAllAssociative('
                SELECT
                    s.SiteAdID, s.Description,
                    COUNT(u.UserID) as usersCount
                FROM SiteAd s
                LEFT JOIN Usr u ON u.CameFrom = s.SiteAdID 
                GROUP BY s.SiteAdID, s.Description
                ORDER BY SiteAdID DESC
            '),
            'periods' => [
                ['label' => 'Choose Period', 'value' => ''],
                ['label' => 'Yesterday', 'value' => date('Y-m-d', strtotime('yesterday')) . '=' . date('Y-m-d', strtotime('yesterday'))],
                ['label' => 'Current Week', 'value' => date('Y-m-d', strtotime('monday this week')) . '=' . date('Y-m-d', strtotime('sunday this week'))],
                ['label' => 'Last Week', 'value' => date('Y-m-d', strtotime('monday last week')) . '=' . date('Y-m-d', strtotime('sunday last week'))],
                ['label' => 'Current Month', 'value' => date('Y-m-d', strtotime('first day of this month')) . '=' . date('Y-m-d', strtotime('last day of this month'))],
                ['label' => 'Last Month', 'value' => date('Y-m-d', strtotime('first day of last month')) . '=' . date('Y-m-d', strtotime('last day of last month'))],
                ['label' => '2 Month Ago', 'value' => date('Y-m-d', strtotime('first day of 2 month ago')) . '=' . date('Y-m-d', strtotime('last day of 2 month ago'))],
                ['label' => '3 Month Ago', 'value' => date('Y-m-d', strtotime('first day of 3 month ago')) . '=' . date('Y-m-d', strtotime('last day of 3 month ago'))],
                ['label' => 'Last 6 Months', 'value' => date('Y-m-d', strtotime('first day of 6 month ago')) . '=' . date('Y-m-d', strtotime('last day of last month'))],
                ['label' => 'Current Year', 'value' => date('Y-01-01') . '=' . date('Y-m-d')],
            ],
        ];
    }

    /**
     * @Route("/manager/user/stat/report/{key}", name="aw_manager_user_stat_report")
     * @Security("is_granted('ROLE_MANAGE_USERSTAT')")
     */
    public function getCsvReport(string $key, \Memcached $memcached)
    {
        $data = $memcached->get($key);

        if (!$data) {
            exit('Data not found');
        }

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=users_report_var2baselead.csv');

        $out = fopen('php://output', 'w');
        fputcsv($out, [
            'UserID',
            'Register Date',
            'US (true/false)',
            'Referer',
            'BaseLead',
            'Source',
            'BlogPostID',
            'MID',
            'CID',
            'Post URL',
        ]);

        foreach ($data as $item) {
            fputcsv($out, [
                $item['userId'],
                $item['registered'],
                $item['isUs'],
                $item['referer'],
                $item['baselead'],
                $item['var2_source'],
                $item['var2_postId'],
                $item['var2_mid'],
                $item['var2_cid'],
                $item['postUrl'],
            ]);
        }

        fclose($out);

        return new Response();
    }

    private function getQueryOptions(array $query): array
    {
        if (empty($query['dfrom'])) {
            return [];
        }

        $creationDate = [
            'min' => !empty($query['dfrom']) ? $query['dfrom'] . ' 00:00:00' : null,
            'max' => !empty($query['dto']) ? $query['dto'] . ' 23:59:59' : null,
        ];

        $reportDuration = (int) ($query['duration'] ?? 0);

        if (!empty($reportDuration)) {
            $durationDate = new \DateTime($creationDate['min']);
            $durationDate->add(new \DateInterval('P' . $reportDuration . 'D'));
        }
        $isDuration = $reportDuration > 0;
        $isMobileApp = !empty($query['isMobileApp']) ? true : false;
        $isPurchasedAwPlus = !empty($query['isPurchasedAwPlus']) ? true : false;
        $users = (int) $query['users'];
        $baseLead = array_map('intval', $query['baseLead'] ?? []);

        $filters = [];
        $minMax = ['Programs', 'TPlans', 'Mailboxes', 'CCClicks', 'CCApprovals'];

        foreach ($minMax as $var) {
            $filters[$var] = [
                'min' => (int) ($query['min' . $var] ?? 0),
                'max' => (int) ($query['max' . $var] ?? 0),
            ];
        }

        return [
            'creationDate' => $creationDate,
            'duration' => $reportDuration,
            'durationDate' => $durationDate ?? null,
            'isDuration' => $isDuration,
            'isMobileApp' => $isMobileApp,
            'isPurchasedAwPlus' => $isPurchasedAwPlus,
            'users' => $users,
            'filters' => $filters,
            'baseLead' => $baseLead,
            'typeSubmit' => $query['submit'] ?? 'default',
            'isDebug' => $query['isDebug'] ?? false,
        ];
    }
}
