<?php

namespace AwardWallet\MainBundle\Controller\Manager\MileValue;

use AwardWallet\MainBundle\Entity\Provider;
use AwardWallet\MainBundle\Entity\ProviderMileValue;
use AwardWallet\MainBundle\FrameworkExtension\AwTokenStorage;
use AwardWallet\MainBundle\Globals\NumberHandler;
use AwardWallet\MainBundle\Service\MileValue\CalcMileValueCommand;
use AwardWallet\MainBundle\Service\MileValue\MileValueCalculator;
use AwardWallet\MainBundle\Service\MileValue\MileValueService;
use AwardWallet\MainBundle\Service\SocksMessaging\Client;
use AwardWallet\MainBundle\Worker\AsyncProcess\Process;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ReportStatusController
{
    private Connection $connection;

    public function __construct(
        AwTokenStorage $tokenStorage,
        Connection $connection,
        Process $asyncProcess,
        Client $sockClicent
    ) {
        $this->connection = $connection;
    }

    /**
     * @Route("/manager/mile-value/report-status", name="aw_manager_milevalue_report_status")
     * @Template("@AwardWalletMain/Manager/MileValue/reportStatus.html.twig")
     */
    public function indexAction(Request $request)
    {
        $response = [
            'title' => '',
            'certificationDate' => '',
        ];
        $providerId = (int) $request->query->get('providerId');
        /*
        $conditions = $this->getQueryOptions($request->query->all());

        if (!empty($conditions)) {
            $channel = UserMessaging::getChannelName(
                get_class($this) . bin2hex(random_bytes(3)),
                $tokenStorage->getUser()->getId()
            );

            $task = new UserStatTask($channel, $conditions);

            $asyncProcess->execute($task);
            //$this->get(UserStatExecutor::class)->execute($task);
        }
        */

        $response['providers'] = $this->connection->fetchAllAssociative('
            SELECT
                    p.ProviderID, p.DisplayName
            FROM Provider p
            JOIN ProviderMileValue pmv ON (pmv.ProviderID = p.ProviderID)
            WHERE p.Kind = ' . PROVIDER_KIND_AIRLINE . '
            ORDER BY p.Kind ASC, p.DisplayName ASC
        ');

        if (!empty($providerId)) {
            $response = array_merge($response, $this->fetchAllByProvider($providerId));
        }

        // 'channel' => $channel ?? null,
        // 'centrifuge_config' => $sockClicent->getClientData(),

        return $response;
    }

    /**
     * @Route("/manager/mile-value/report-status/get-by-date", name="aw_manager_milevalue_report_status_getbydate")
     */
    public function getByDateAction(Request $request)
    {
        $providerId = (int) $request->query->get('providerId');
        $year = (int) $request->query->get('year');
        $month = $request->query->get('month');
        $month = (int) (false === strpos($month, '-') ? $month : explode('-', $month)[1]);
        $html = '';

        $result = $this->fetchAllByProvider($providerId, $year, $month);

        $html .= '<tr><th rowspan="' . (count($result['data']) + 1) . '">' . $year . '-' . $month . '<br><a href="#" class="js-remove-box">remove</a></th><td colspan="10"></td></tr>';

        foreach ($result['data'] as $key => $items) {
            $html .= '<tr class="status-' . $key . '">';
            // th rowspan
            $html .= '
                <th class="brs">' . $this->showStatus($key) . '</th>
                    <td>' . $items['RegionalEconomyMileValue']['_count'] . '</td>
                    <td>' . $items['RegionalEconomyMileValue']['_avg'] . '</td>
                    <td>' . $items['RegionalBusinessMileValue']['_count'] . '</td>
                    <td>' . $items['RegionalBusinessMileValue']['_avg'] . '</td>
                    <td>' . $items['GlobalEconomyMileValue']['_count'] . '</td>
                    <td>' . $items['GlobalEconomyMileValue']['_avg'] . '</td>
                    <td>' . $items['GlobalBusinessMileValue']['_count'] . '</td>
                    <td>' . $items['GlobalBusinessMileValue']['_avg'] . '</td>
                    <td>' . $items['total']['count'] . '</td>
                    <td>' . $items['total']['avg'] . '</td>
                </tr>
            ';
        }
        $html .= '
            <tr class="status-all">
                <th></th>
                <th class="brs">All Statuses</th>
                <td>' . $result['all']['RegionalEconomyMileValue_count'] . '</td>
                <td></td>
                <td>' . $result['all']['RegionalBusinessMileValue_count'] . '</td>
                <td></td>
                <td>' . $result['all']['GlobalEconomyMileValue_count'] . '</td>
                <td></td>
                <td>' . $result['all']['GlobalBusinessMileValue_count'] . '</td>
                <td></td>
                <td>' . $result['all']['count'] . '</td>
                <td>' . $result['all']['avg'] . '</td>
            </tr>
        ';

        $response = [
            'html' => $html,
        ];

        exit(json_encode($response));
    }

    /**
     * @Route("/manager/mile-value/report-status/get-report-calc", name="aw_manager_milevalue_report_calc")
     */
    public function getReportCalc(
        Request $request,
        MileValueService $mileValueService,
        EntityManagerInterface $entityManager
    ) {
        $providerStatuses = [ProviderMileValue::STATUS_ENABLED];
        $banks = $this->connection->executeQuery('
            SELECT
                    p.ProviderID, p.DisplayName, p.ShortName,
                    pmv.RegionalEconomyMileValue, pmv.RegionalBusinessMileValue, pmv.GlobalEconomyMileValue, pmv.GlobalBusinessMileValue, pmv.AvgPointValue, pmv.Status
            FROM ProviderMileValue pmv
            JOIN Provider p ON (p.ProviderID = pmv.ProviderID AND p.Kind = :kind)
            WHERE
                    pmv.ProviderID IN (:transferProviderId)
                AND pmv.EndDate IS NULL
                AND pmv.Status IN (:status)
            ORDER BY p.DisplayName',
            [
                'transferProviderId' => $this->getTransfersProviderId(),
                'kind' => PROVIDER_KIND_CREDITCARD,
                'status' => $providerStatuses,
            ],
            [
                'transferProviderId' => Connection::PARAM_INT_ARRAY,
                'kind' => \PDO::PARAM_INT,
                'status' => Connection::PARAM_INT_ARRAY,
            ]
        )->fetchAllAssociative();

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=mileValue_transferable_points.csv');

        $out = fopen('php://output', 'w');

        $data = [];

        foreach ($banks as $bank) {
            $providerId = (int) $bank['ProviderID'];
            $data[$providerId] = [
                'bank' => $bank,
                'items' => [],
                'totals' => [
                    'sumSpent' => 0,
                    'sumTaxesSpent' => 0,
                    'sumAlternativeCost' => 0,
                    'sumSpentClear' => 0,
                ],
                'mileValues' => [],
                'percents' => [],

                'mileValuesClear' => [],
                'percentsClear' => [],
            ];
            $targets = array_keys($mileValueService->getTransferableProviders($bank['ProviderID'])[$bank['ProviderID']] ?? []);

            $transferPartners = $this->connection->executeQuery('
                SELECT
                        ts.TargetProviderID, ts.SourceRate, ts.TargetRate
                FROM TransferStat ts
                WHERE
                        ts.SourceRate IS NOT NULL 
                    AND ts.TargetRate IS NOT NULL
                    AND ts.SourceProviderID = ?',
                [$providerId],
                [\PDO::PARAM_INT]
            )->fetchAllAssociative();
            $transferPartners = array_combine(array_column($transferPartners, 'TargetProviderID'), $transferPartners);

            foreach ($targets as $targetProvidersId) {
                $kind = (int) $entityManager->getConnection()->fetchOne('SELECT Kind FROM Provider WHERE ProviderID = ' . $targetProvidersId);

                if (PROVIDER_KIND_CREDITCARD === $kind
                    || PROVIDER_KIND_HOTEL === $kind
                ) {
                    continue;
                }

                if (PROVIDER_KIND_AIRLINE === $kind) {
                    if (Provider::KLM_ID === (int) $targetProvidersId) {
                        $targetProviders = Provider::KLM_ID . ', ' . Provider::AIRFRANCE_ID;
                    } elseif (Provider::AIRFRANCE_ID === $targetProvidersId) {
                        continue;
                    } else {
                        $targetProviders = $targetProvidersId;
                    }

                    $mv = $entityManager->getConnection()->fetchAssociative("
                        SELECT
                            COUNT(v.MileValueID) as _count,
                            ROUND(SUM(v.TotalMilesSpent), 4) as sumSpent, 
                            ROUND(SUM(v.TotalTaxesSpent), 4) as sumTaxesSpent, 
                            ROUND(SUM(v.AlternativeCost), 4) as sumAlternativeCost,
                            v.ProviderID,
                            pmv.ProviderID, 
                            p.DisplayName, 
                            pmv.CertificationDate,
                            pmv.RegionalEconomyMileValue, pmv.RegionalBusinessMileValue, pmv.GlobalEconomyMileValue, pmv.GlobalBusinessMileValue, pmv.AvgPointValue, pmv.Status
                        FROM ProviderMileValue pmv 
                        JOIN Provider p ON (p.ProviderID = pmv.ProviderID)
                        LEFT JOIN MileValue v ON 
                                pmv.ProviderID = v.ProviderID 
                            AND v.Status NOT IN ('" . implode("','", CalcMileValueCommand::EXCLUDED_STATUSES) . "')
                            AND (
                                    v.TotalMilesSpent > 0
                                AND v.AlternativeCost > 0
                            )
                            AND (v.CreateDate >= pmv.StartDate OR pmv.StartDate IS NULL)
                            AND (v.CreateDate <= ADDDATE(pmv.CertificationDate, 1) OR pmv.CertificationDate IS NULL)
                        WHERE
                                pmv.Status IN(1)
                            AND p.ProviderID IN (" . $targetProviders . ")
                            AND pmv.EndDate IS NULL
                        GROUP BY 
                            v.ProviderID,
                            pmv.ProviderID,
                            p.DisplayName, 
                            pmv.CertificationDate, pmv.RegionalEconomyMileValue, pmv.RegionalBusinessMileValue, pmv.GlobalEconomyMileValue, pmv.GlobalBusinessMileValue, pmv.AvgPointValue, pmv.Status
                        ORDER BY p.DisplayName
                    ");
                /*
                 } elseif (PROVIDER_KIND_HOTEL === $kind) {
                 $mv = $entityManager->getConnection()->fetchAssociative("
                     SELECT
                         COUNT(v.HotelPointValueID) as _count,
                         ROUND(SUM(v.TotalPointsSpent), 2) as sumSpent,
                         ROUND(SUM(v.TotalTaxesSpent), 2) as sumTaxesSpent,
                         ROUND(SUM(v.AlternativeCost), 2) as sumAlternativeCost,
                         v.ProviderID,
                         p.DisplayName,
                         pmv.ProviderID, pmv.CertificationDate, pmv.RegionalEconomyMileValue, pmv.RegionalBusinessMileValue, pmv.GlobalEconomyMileValue, pmv.GlobalBusinessMileValue, pmv.AvgPointValue, pmv.Status
                     FROM ProviderMileValue pmv
                     JOIN Provider p ON (p.ProviderID = pmv.ProviderID)
                     LEFT JOIN HotelPointValue v ON (
                                 pmv.ProviderID = v.ProviderID
                             AND v.Status NOT IN ('" . implode("','", CalcMileValueCommand::EXCLUDED_STATUSES) . "')
                             AND (v.TotalPointsSpent > 0 AND v.AlternativeCost > 0 AND v.PointValue > 0)
                             AND (v.CreateDate >= pmv.StartDate OR pmv.StartDate IS NULL)
                             AND (v.CreateDate <= ADDDATE(pmv.CertificationDate, 1) OR pmv.CertificationDate IS NULL)
                         )
                     WHERE
                             pmv.Status IN(1)
                         AND p.ProviderID IN (" . $targetProvidersId . ")
                         AND pmv.EndDate IS NULL
                     GROUP BY
                         v.ProviderID,
                         pmv.ProviderID,
                         p.DisplayName,
                         pmv.CertificationDate, pmv.RegionalEconomyMileValue, pmv.RegionalBusinessMileValue, pmv.GlobalEconomyMileValue, pmv.GlobalBusinessMileValue, pmv.AvgPointValue, pmv.Status
                     ORDER BY p.DisplayName
                 ");
                 */
                } else {
                    throw new \Exception('Error kind: ' . $kind);
                }

                if (empty($mv)) {
                    continue;
                }

                $targetId = $mv['ProviderID'];

                $ratio = round($transferPartners[$targetId]['TargetRate'] / $transferPartners[$targetId]['SourceRate'],
                    2);
                $mv['_targetRatio'] = $transferPartners[$targetId]['TargetRate'];
                $mv['_sourceRatio'] = $transferPartners[$targetId]['SourceRate'];
                $mv['_ratio'] = $ratio;

                $data[$providerId]['totals']['sumSpentClear'] += (float) $mv['sumSpent'];

                $data[$providerId]['totals']['sumSpent'] += ((float) $mv['sumSpent'] / $ratio);
                $data[$providerId]['totals']['sumTaxesSpent'] += (float) $mv['sumTaxesSpent'];
                $data[$providerId]['totals']['sumAlternativeCost'] += (float) $mv['sumAlternativeCost'];

                $data[$providerId]['items'][] = $mv;
            }
        }

        foreach ($data as $providerId => $bank) {
            if (empty($bank['items'])
                || empty($data[$providerId]['totals']['sumSpent'])) {
                continue;
            }

            fputcsv($out, [
                'Program Name',
                'Total Miles Spent',
                'Total Taxes Spent',
                'Alt Cost',
                'MileValue',
                '% of Total',
                'count',
                '',
                'Target Ratio',
                'Source Ratio',
                'ratio',
                '',
                '% without ratio',
            ]);

            foreach ($bank['items'] as $program) {
                $mileValue = $program['sumAlternativeCost'] > 0 ?
                    MileValueCalculator::calc(
                        $program['sumAlternativeCost'],
                        $program['sumTaxesSpent'],
                        $program['sumSpent']
                    ) : 0;

                if ($mileValue > 0) {
                    $data[$providerId]['mileValues'][] = $mileValue;
                }
                $percent = NumberHandler::numberPrecision(
                    $program['sumSpent'] > 0
                        ? $program['sumSpent'] / $data[$providerId]['totals']['sumSpent'] * 100
                        : 0,
                    2
                );
                $data[$providerId]['percents'][] = $percent;

                $mileValueClear = $program['sumAlternativeCost'] > 0 ?
                    MileValueCalculator::calc(
                        $program['sumAlternativeCost'],
                        $program['sumTaxesSpent'],
                        $program['sumSpent']
                    ) : 0;

                if ($mileValueClear > 0) {
                    $data[$providerId]['mileValuesClear'][] = $mileValueClear;
                }
                $percentClear = NumberHandler::numberPrecision(
                    $program['sumSpent'] > 0
                        ? $program['sumSpent'] / $data[$providerId]['totals']['sumSpentClear'] * 100
                        : 0,
                    2
                );

                $data[$providerId]['percentsClear'][] = $percentClear;

                fputcsv($out, [
                    $program['DisplayName'],
                    $program['sumSpent'] ?? 0,
                    $program['sumTaxesSpent'] ?? 0,
                    $program['sumAlternativeCost'] ?? 0,
                    $mileValue,
                    $percent . '%',
                    $program['_count'],
                    '',
                    $program['_targetRatio'],
                    $program['_sourceRatio'],
                    $program['_ratio'],
                    '',
                    $percentClear . '%',
                ]);
            }

            $totalMileValue = NumberHandler::numberPrecision(
                // array_sum($data[$providerId]['mileValues']) / count($data[$providerId]['mileValues']),
                MileValueCalculator::calc(
                    $data[$providerId]['totals']['sumAlternativeCost'],
                    $data[$providerId]['totals']['sumTaxesSpent'],
                    $data[$providerId]['totals']['sumSpent']
                ),
                2);

            fputcsv($out, [
                'TOTAL: ' . $bank['bank']['DisplayName'],
                $data[$providerId]['totals']['sumSpent'],
                $data[$providerId]['totals']['sumTaxesSpent'],
                $data[$providerId]['totals']['sumAlternativeCost'],
                $totalMileValue,
                array_sum($data[$providerId]['percents']) . '%',
                array_sum(array_column($data[$providerId]['items'], '_count')),
                '',
                '',
                '',
                '',
                '',
                array_sum($data[$providerId]['percentsClear']) . '%',
            ]);

            fputcsv($out, []);
            fputcsv($out, []);
        }
        fclose($out);

        return new Response();
    }

    private function getTransfersProviderId(): array
    {
        return $this->connection->executeQuery('
            SELECT
                    DISTINCT ts.SourceProviderID
            FROM TransferStat ts
            WHERE
                    ts.SourceRate IS NOT NULL 
                AND ts.TargetRate IS NOT NULL
        ')->fetchFirstColumn();
    }

    private function fetchAllByProvider(int $providerId, ?int $year = null, ?int $month = null): array
    {
        $response = [];
        $providerMileValue = $this->connection->fetchAssociative('SELECT CertificationDate FROM ProviderMileValue WHERE ProviderID = ' . $providerId);

        $statuses = [
            ['status' => CalcMileValueCommand::STATUS_GOOD],
            ['status' => CalcMileValueCommand::STATUS_REVIEW],
            ['status' => CalcMileValueCommand::STATUS_AUTO_REVIEW],
            ['status' => CalcMileValueCommand::STATUS_ERROR],
            ['status' => CalcMileValueCommand::STATUS_REPORTED],
        ];

        $certificationDate = $providerMileValue['CertificationDate'];

        if (empty($certificationDate)) {
            array_unshift($statuses, ['status' => CalcMileValueCommand::STATUS_NEW]);
        } else {
            $response['certificationDate'] = date('m/d/Y', strtotime($certificationDate));
            array_unshift($statuses,
                ['status' => CalcMileValueCommand::STATUS_NEW, 'options' => ['certify' => 'live']],
                ['status' => CalcMileValueCommand::STATUS_NEW, 'options' => ['certify' => 'pending']],
            );
        }

        $result = [];

        foreach ($statuses as $status) {
            $key = $status['status']
                . (isset($status['options']) && isset($status['options']['certify'])
                    ? '_' . $status['options']['certify']
                    : ''
                );
            $result[$key] = $this->fetchByStatus($providerId, $status, $year, $month);
        }

        $all = [
            'count' => 0,
            'avg' => 0,
            'sumSpent' => 0,
            'sumTaxesSpent' => 0,
            'sumAlternativeCost' => 0,
        ];

        foreach ($result as $status => $data) {
            $result[$status]['total'] = $all;

            foreach ($data as $type => $values) {
                $result[$status]['total']['count'] += $values['_count'];
                // $result[$status]['total']['avg'] += $values['_avg'];
                $result[$status]['total']['sumSpent'] += $values['sumSpent'];
                $result[$status]['total']['sumTaxesSpent'] += $values['sumTaxesSpent'];
                $result[$status]['total']['sumAlternativeCost'] += $values['sumAlternativeCost'];
            }

            if ((int) $result[$status]['total']['sumSpent'] > 0) {
                $result[$status]['total']['avg'] = MileValueCalculator::calc(
                    (float) $result[$status]['total']['sumAlternativeCost'],
                    (float) $result[$status]['total']['sumTaxesSpent'],
                    (int) $result[$status]['total']['sumSpent']
                );
            }
        }

        foreach ($result as $status => $data) {
            foreach ($data as $key => $values) {
                if (!isset($data[$key]['_count'])) {
                    continue;
                }
                $keyCount = $key . '_count';

                if (!array_key_exists($keyCount, $all)) {
                    $all[$keyCount] = 0;
                }
                $all[$keyCount] += $data[$key]['_count'];
            }

            $all['count'] += $data['total']['count'];
            $all['sumSpent'] += $data['total']['sumSpent'];
            $all['sumTaxesSpent'] += $data['total']['sumTaxesSpent'];
            $all['sumAlternativeCost'] += $data['total']['sumAlternativeCost'];
        }
        $all['avg'] = $all['sumSpent'] > 0
            ? MileValueCalculator::calc(
                (float) $all['sumAlternativeCost'],
                (float) $all['sumTaxesSpent'],
                (int) $all['sumSpent']
            )
            : 0;

        $response['data'] = $result;
        $response['all'] = $all;

        return $response;
    }

    private function fetchByStatus(int $providerId, array $statuses, ?int $year = null, ?int $month = null)
    {
        if (CalcMileValueCommand::STATUS_NEW === $statuses['status'] && isset($statuses['options'])) {
            $certify = $statuses['options']['certify'];
            $providerMileValue = $this->connection->fetchAssociative('SELECT CertificationDate FROM ProviderMileValue WHERE ProviderID = ' . $providerId);

            if ('live' === $certify) {
                $extraSqlCondition = empty($providerMileValue['CertificationDate'])
                    ? ''
                    : " AND (v.CreateDate <= ADDDATE('" . $providerMileValue['CertificationDate'] . "', 1)) ";
            } else {
                $extraSqlCondition = empty($providerMileValue['CertificationDate'])
                    ? ''
                    : " AND (v.CreateDate > ADDDATE('" . $providerMileValue['CertificationDate'] . "', 1)) ";
            }
        } else {
            $extraSqlCondition = '';
        }

        if (!empty($year) && !empty($month)) {
            $endDate = date('Y-m-t 23:59:59', strtotime($year . '-' . $month . '-01'));
            $extraSqlCondition .= ' AND v.CreateDate BETWEEN ' . $this->connection->quote($year . '-' . $month . '-01 00:00:00') . ' AND ' . $this->connection->quote($endDate);
        }

        $data = [];

        foreach (MileValueService::AIR_TYPE_CONDITION as $key => $options) {
            $params = $options['params'];
            $data[$key] = $this->connection->fetchAssociative($sql = '
                SELECT
                    COUNT(v.MileValueID) as _count,
                    ROUND(SUM(v.TotalMilesSpent), 2) as sumSpent, 
                    ROUND(SUM(v.TotalTaxesSpent), 2) as sumTaxesSpent, 
                    ROUND(SUM(v.AlternativeCost), 2) as sumAlternativeCost
                FROM  MileValue v 
                WHERE
                        v.ProviderID = :providerId
                    AND v.International = :int
                    AND v.ClassOfService IN (:classes)
                    AND v.Status = :status
                    AND (
                            v.TotalMilesSpent > 0
                        AND v.AlternativeCost > 0
                    )
                    ' . $extraSqlCondition . '
                    
            ',
                [
                    'providerId' => $providerId,
                    'int' => $params['int'],
                    'classes' => $params['classes'],
                    'status' => $statuses['status'],
                ],
                [
                    'providerId' => \PDO::PARAM_INT,
                    'int' => \PDO::PARAM_INT,
                    'classes' => Connection::PARAM_STR_ARRAY,
                    'status' => \PDO::PARAM_STR,
                ]);

            if (0 === (int) $data[$key]['sumSpent']) {
                $data[$key]['_avg'] = 0;
            } else {
                $data[$key]['_avg'] = MileValueCalculator::calc(
                    (float) $data[$key]['sumAlternativeCost'],
                    (float) $data[$key]['sumTaxesSpent'],
                    (int) $data[$key]['sumSpent'],
                );
            }
        }

        return $data;
    }

    private function showStatus($key): string
    {
        switch ($key) {
            case 'N':
                return 'New';

            case 'N_live':
                return 'New (live)';

            case 'N_pending':
                return 'New (pending)';

            case 'G':
                return 'Good';

            case 'R':
                return 'Review';

            case 'A':
                return 'AutoReview';

            case 'E':
                return 'Error';

            case 'P':
                return 'Reported';
        }

        return 'Unknown';
    }
}
