<?php

namespace AwardWallet\MainBundle\Service\Charts;

use AwardWallet\MainBundle\Entity\QsTransaction;
use AwardWallet\MainBundle\Globals\Localizer\LocalizeService;
use AwardWallet\MainBundle\Service\CreditCards\QsTransactionData;
use Doctrine\DBAL\Connection;
use Psr\Log\LoggerInterface;

class QsTransactionChart
{
    public const COLORS = [
        QsTransaction::ACCOUNT_DIRECT => '#dc3912',
        QsTransaction::ACCOUNT_AWARDTRAVEL101 => '#3366cc',
        QsTransaction::ACCOUNT_CARDRATINGS => '#109617',
    ];

    public const COLORS_LIST = ['#3366cc', '#dc3912', '#ff9901', '#109617', '#990099', '#0099c6', '#dd4478', '#66aa00', '#b82e2e', '#316395', '#994499', '#22aa99', '#aaaa12', '#6633cc', '#e67301', '#8b0707', '#651067', '#329262', '#5574a6', '#3b3eac', '#b77322', '#16d621', '#b91383', '#f4359e', '#9c5935', '#a9c412', '#2a778d'];

    private const CARDS_DISPLAY_LIMIT = 10;

    private LoggerInterface $logger;

    private Connection $connection;

    private LocalizeService $localizeService;

    private QsTransactionData $qsTransactionData;

    public function __construct(
        LoggerInterface $logger,
        Connection $connection,
        LocalizeService $localizeService,
        QsTransactionData $qsTransactionData
    ) {
        $this->logger = $logger;
        $this->connection = $connection;
        $this->localizeService = $localizeService;
        $this->qsTransactionData = $qsTransactionData;
    }

    public function getClicksGraph(?\DateTime $setDate = null): ?\Graph
    {
        $this->initJpGraph();
        $date = $this->fetchDate($setDate);
        $data = $this->fetchData('clicks', $date);

        if (empty($data['clicks'])) {
            return null;
        }

        $awTypeKey = QsTransactionData::QS_TYPES[QsTransactionData::QS_TYPE_AW];
        $atTypeKey = QsTransactionData::QS_TYPES[QsTransactionData::QS_TYPE_AT101];

        $graph = new \Graph(1200, 600, 'auto');
        $graph->SetScale('textlin');
        // $graph->SetY2Scale('lin', 0, 90);
        // $graph->SetY2OrderBack(false);

        $graph->SetTheme(new \SoftyTheme());
        $graph->SetBox(false);

        $graph->ygrid->SetFill(false);
        $graph->yaxis->HideLine(false);
        $graph->yaxis->HideTicks(false, false);
        // $graph->xaxis->SetTickLabels($data['listDays']);
        // $graph->xaxis->Hide(true);
        $graph->xaxis->HideLabels(true);

        $formatNumber = function ($label) {
            return $this->localizeService->formatNumber(rtrim($label, '0., '));
        };

        $plotAw = new \BarPlot($data['days'][$awTypeKey]);
        $plotAt = new \BarPlot($data['days'][$atTypeKey]);

        $accBarPlot = new \AccBarPlot([$plotAt, $plotAw]);
        $accBarPlot->SetWidth(24);
        $accBarPlot->value->SetColor('#000000');
        $accBarPlot->value->SetMargin(10);
        $accBarPlot->value->SetFont(FF_FONT1, FS_BOLD, 30);
        $accBarPlot->value->SetFormatCallback($formatNumber);
        $accBarPlot->value->Show();

        $groupBarPlot = new \GroupBarPlot([$accBarPlot]);
        $graph->Add($groupBarPlot);

        $plotAw->value->SetFormatCallback($formatNumber);
        $plotAw->SetColor('#ffffff');
        $plotAw->SetFillColor('#dc3912');
        $plotAw->SetLegend(QsTransactionData::QS_TYPES_TITLE[QsTransactionData::QS_TYPE_AW]);
        $plotAw->value->SetColor('#000000');
        $plotAw->value->SetAngle(45);
        $plotAw->value->SetColor('#ffffff');
        $plotAw->value->SetAlign('', 'bottom');
        $plotAw->value->Show(true);

        $plotAt->SetColor('#ffffff');
        $plotAt->SetFillColor('#3366cc');
        $plotAt->SetLegend(QsTransactionData::QS_TYPES_TITLE[QsTransactionData::QS_TYPE_AT101]);
        $plotAt->value->SetFormatCallback($formatNumber);
        $plotAt->value->SetAngle(45);
        $plotAt->value->SetMargin(10);
        $plotAt->value->SetColor('#ffffff');
        $plotAt->value->Show(true);

        $graph->legend->SetFrameWeight(0);
        // $graph->legend->SetColumns(6);
        $graph->legend->SetColor('#4E4E4E', '#00A78A');

        $graph->SetMargin(60, 20, 60, 100);
        $graph->legend->SetPos(0.5, 0.05, 'center');
        $graph->legend->SetColor('#8f8f8f');
        $graph->legend->font_size = 9;

        $cellwidth = 36;
        $tableypos = 502;
        $tablexpos = 62;
        // $tablewidth = $nbrbar * $cellwidth;

        $tableData = [
            $data['listDays'],
            $data['days'][$awTypeKey],
            $data['days'][$atTypeKey],
        ];
        $table = new \GTextTable();
        $table->Set($tableData);
        $table->SetPos($tablexpos, $tableypos + 1);

        // Basic table formatting
        // $table->SetFont(FF_ARIAL,FS_NORMAL,10);
        $table->SetAlign('right');
        $table->SetMinColWidth($cellwidth);
        // $table->SetNumberFormat('%0.1f');
        $table->SetBorder(0);
        $table->SetGrid(0);

        // Format table header row
        $table->SetRowFillColor(0, '#999999@0.7');
        // $table->SetRowFont(0,FF_ARIAL,FS_BOLD,11);
        $table->SetRowAlign(0, 'center');
        $table->SetRowColor(1, self::COLORS[QsTransaction::ACCOUNT_DIRECT]);
        $table->SetRowColor(2, self::COLORS[QsTransaction::ACCOUNT_AWARDTRAVEL101]);

        $graph->Add($table);

        if (null === $setDate) {
            $dateOf = (new \DateTime())->sub(new \DateInterval('P1D'));
        } else {
            $dateOf = $data['lastDay']->modify('-1 day');
        }
        $graph->title->Set($date->format('F Y') . ', Affiliate Clicks as of ' . $dateOf->format('F d, Y'));

        return $graph;
    }

    public function getRevenueGraph(?\DateTime $setDate = null): ?\Graph
    {
        $this->initJpGraph();
        $date = $this->fetchDate($setDate);
        $data = $this->fetchData('revenue', $date);

        if (empty($data['earnings'])) {
            return null;
        }

        $awTypeKey = QsTransactionData::QS_TYPES[QsTransactionData::QS_TYPE_AW];
        $atTypeKey = QsTransactionData::QS_TYPES[QsTransactionData::QS_TYPE_AT101];

        $graphWidth = 1200;
        $formatNumber = function ($label) {
            return $this->localizeService->formatNumber(rtrim($label, '., '));
        };

        $graphWidth = 60 + (38 * count($data['days'][$awTypeKey])) + 60;
        $graph = new \Graph($graphWidth, 600, 'auto');
        $graph->SetScale('textlin');
        // $graph->SetY2Scale('lin', 0, 90);
        // $graph->SetY2OrderBack(false);

        $graph->SetTheme(new \SoftyTheme());
        $graph->SetBox(false);

        $graph->ygrid->SetFill(false);
        $graph->yaxis->HideLine(false);
        $graph->yaxis->HideTicks(false, false);
        $graph->xaxis->SetTickLabels($data['listDays']);
        // $graph->xaxis->Hide(true);
        $graph->xaxis->HideLabels(true);
        // $graph->xaxis->SetFont(FF_FONT1, FS_NORMAL, 14);

        $plotAw = new \BarPlot($data['days'][$awTypeKey]);
        $plotAt = new \BarPlot($data['days'][$atTypeKey]);

        $accBarPlot = new \AccBarPlot([$plotAt, $plotAw]);
        $accBarPlot->SetAbsWidth(30);
        $accBarPlot->value->SetColor('#000000');
        $accBarPlot->value->SetMargin(6);
        $accBarPlot->value->SetFont(FF_FONT1, FS_BOLD, 20);
        $accBarPlot->value->SetFormatCallback($formatNumber);
        $accBarPlot->value->Show();

        $groupBarPlot = new \GroupBarPlot([$accBarPlot]);
        $graph->Add($groupBarPlot);

        $plotAw->value->SetFormatCallback($formatNumber);
        $plotAw->SetColor('#ffffff');
        $plotAw->SetFillColor('#dc3912');
        $plotAw->SetLegend(QsTransactionData::QS_TYPES_TITLE[QsTransactionData::QS_TYPE_AW]);
        $plotAw->value->SetColor('#000000');
        $plotAw->value->SetAngle(45);
        $plotAw->value->SetColor('#ffffff');
        $plotAw->value->SetAlign('', 'bottom');
        $plotAw->value->Show(true);

        $plotAt->SetColor('#ffffff');
        $plotAt->SetFillColor('#3366cc');
        $plotAt->SetLegend(QsTransactionData::QS_TYPES_TITLE[QsTransactionData::QS_TYPE_AT101]);
        $plotAt->value->SetFormatCallback($formatNumber);
        $plotAt->value->SetAngle(45);
        $plotAt->value->SetMargin(10);
        $plotAt->value->SetColor('#ffffff');
        $plotAt->value->Show(true);

        $graph->legend->SetFrameWeight(0);
        // $graph->legend->SetColumns(6);
        $graph->legend->SetColor('#4E4E4E', '#00A78A');

        $graph->SetMargin(60, 20, 60, 100);
        $graph->legend->SetPos(0.5, 0.05, 'center');
        $graph->legend->SetColor('#8f8f8f');
        $graph->legend->font_size = 9;

        $cellwidth = 35;
        $tableypos = 505;
        $tablexpos = 60;

        $tableData = [
            $data['listDays'],
            $data['days'][$awTypeKey],
            $data['days'][$atTypeKey],
        ];

        $table = new \GTextTable();
        $table->Init(3, \count($data['listDays']));
        $table->Set($tableData);
        $table->SetPos($tablexpos, $tableypos);
        $table->SetPadding(0);
        $table->SetMinColWidth($cellwidth);

        // $table->SetFont(FF_FONT0, FS_NORMAL, 15);
        $table->SetAlign('right');
        $table->SetMinColWidth($cellwidth);
        $table->SetNumberFormat('%d');
        // $table->SetColFont(4, FF_FONT1, FS_NORMAL, 10);
        $table->SetBorder(0);
        $table->SetGrid(0);

        $table->SetRowFillColor(0, '#999999@0.7');
        // $table->SetRowFont(0,FF_ARIAL,FS_BOLD,11);
        // $table->SetRowAlign(0, 'center');
        $table->SetRowColor(1, self::COLORS[QsTransaction::ACCOUNT_DIRECT]);
        $table->SetRowColor(2, self::COLORS[QsTransaction::ACCOUNT_AWARDTRAVEL101]);

        $graph->Add($table);

        if (null === $setDate) {
            $dateOf = (new \DateTime())->sub(new \DateInterval('P1D'));
        } else {
            $dateOf = $data['lastDay']->modify('-1 day');
        }

        $graph->title->Set($date->format('F Y') . ', Affiliate Revenue as of ' . $dateOf->format('F d, Y'));

        return $graph;
    }

    public function getCardsGraph(?\DateTime $setDate = null): ?\Graph
    {
        $this->initJpGraph();
        $date = $this->fetchDate($setDate);
        $data = $this->fetchData('cards', $date);

        if (empty($data['cards']) || 0 === (int) array_sum($data['earnings'])) {
            return null;
        }

        $isStyle3d = true;

        $graph = new \PieGraph(1200, 700);
        $graph->ClearTheme();
        // $graph->title->SetFont(FF_FONT1, FS_NORMAL, 30);
        $font = '../../../../www/awardwallet/web/assets/awardwalletnewdesign/font/opensans-regular-webfont.ttf';
        $graph->SetUserFont($font);
        $graph->title->SetFont(FF_USERFONT, FS_NORMAL, 16);

        if (null === $setDate) {
            $dateOf = (new \DateTime())->sub(new \DateInterval('P1D'));
        } else {
            $dateOf = $data['lastDay']->modify('-1 day');
        }
        $graph->title->Set($date->format('F Y') . ', Card Breakdown as of ' . $dateOf->format('F d, Y'));

        if ($isStyle3d) {
            $p1 = new \PiePlot3D($data['earnings']);
        } else {
            $p1 = new \PiePlot($data['earnings']);
        }

        $p1->SetSliceColors(array_merge(self::COLORS_LIST, self::COLORS_LIST));

        if ($isStyle3d) {
            $p1->SetSize(0.5);
        } else {
            $p1->SetSize(0.4);
        }
        $p1->SetCenter(0.3);

        $p1->SetLabelType(PIE_VALUE_ABS);
        $p1->SetLabelPos(0.8);
        $p1->value->SetColor('#ffffff');
        $p1->value->SetFormatCallback(
            function ($val) {
                return $this->localizeService->formatCurrency(rtrim($val, '%,. '), 'USD');
            });

        $p1->SetLegends($data['cards']);
        $graph->legend->SetLayout(LEGEND_VERT);
        $graph->legend->SetColumns(1);
        $graph->legend->SetAbsPos(10, 40, 'right', 'top');
        $graph->legend->SetFont(FF_USERFONT, FS_NORMAL, 10);
        $graph->legend->SetFillColor('#ffffff');
        $graph->legend->SetFrameWeight(0);
        $graph->legend->SetLineSpacing(16);
        $graph->legend->SetMarkAbsSize(10);
        $graph->legend->SetMarkAbsHSize(4);
        // $graph->legend->SetMarkAbsSize(8);

        // $p1->SetGuideLines(true, true);
        // $p1->SetGuideLinesAdjust(1.5);
        $p1->ExplodeAll(10);
        $p1->SetStartAngle(25);

        if ($isStyle3d) {
            $p1->SetAngle(70);
        }

        $graph->Add($p1);

        return $graph;
    }

    public function fetchDate(?\DateTime $requestDate = null): \DateTime
    {
        $requestDate = empty($requestDate)
            ? date('Y-n', strtotime('first day of this month'))
            : $requestDate->format('Y-n');
        $requestDate = explode('-', $requestDate);

        $year = $requestDate[0];
        $year = $year >= 2018 && $year <= date('Y') ? $year : date('Y');

        $date = new \DateTime();
        $date->setDate($year, $requestDate[1], 1);

        return $date;
    }

    private function initJpGraph()
    {
        \JpGraph\JpGraph::load();
        \JpGraph\JpGraph::module('bar');
        \JpGraph\JpGraph::module('line');
        \JpGraph\JpGraph::module('table');

        \JpGraph\JpGraph::module('pie');
        \JpGraph\JpGraph::module('pie3d');
    }

    private function fetchData(string $type, ?\DateTime $date = null): ?array
    {
        $awTypeKey = QsTransactionData::QS_TYPES[QsTransactionData::QS_TYPE_AW];
        $atTypeKey = QsTransactionData::QS_TYPES[QsTransactionData::QS_TYPE_AT101];
        $typesCondition = $this->qsTransactionData->getTypesCondition();

        $interval = [
            'begin' => $date->format('Y-m-d'),
            'end' => $date->format('Y-m-t'),
        ];
        $between = 'BETWEEN ' . $this->connection->quote($interval['begin']) . ' AND ' . $this->connection->quote($interval['end']);
        $whereDate = '(ProcessDate ' . $between . ' OR (ClickDate ' . $between . ' AND ProcessDate IS NULL))';

        $lastTransactionDate = $this->connection->fetchOne('SELECT ClickDate FROM QsTransaction WHERE Version = ' . QsTransaction::ACTUAL_VERSION . ' ORDER BY ClickDate DESC LIMIT 1');
        $lastDate = new \DateTime($lastTransactionDate);

        if ('clicks' === $type) {
            $clicks = $this->getClicksBy($between, $typesCondition[$awTypeKey]);
            $awResult = $this->getListByDays('ClickDate', 'Clicks', $awTypeKey, $clicks, $date);

            $clicks = $this->getClicksBy($between, $typesCondition[$atTypeKey]);
            $atResult = $this->getListByDays('ClickDate', 'Clicks', $atTypeKey, $clicks, $date);

            return [
                'listDays' => $awResult['listDays'],
                'clicks' => [
                    $awTypeKey => $awResult['Clicks'][$awTypeKey] ?? [],
                    $atTypeKey => $atResult['Clicks'][$atTypeKey] ?? [],
                ],
                'days' => [
                    $awTypeKey => $awResult['types'][$awTypeKey] ?? [],
                    $atTypeKey => $atResult['types'][$atTypeKey] ?? [],
                ],
                'total' => [
                    $awTypeKey => $awResult['total'][$awTypeKey] ?? [],
                    $atTypeKey => $atResult['total'][$atTypeKey] ?? [],
                ],
                'interval' => $interval,
                'lastDay' => $lastDate,
            ];
        }

        if ('revenue' === $type) {
            $earnings = $this->getEarningsBy($whereDate, $typesCondition[$awTypeKey]);
            $awResult = $this->getListByDays('ProcessDate', 'Earnings', $awTypeKey, $earnings, $date);

            $earnings = $this->getEarningsBy($whereDate, $typesCondition[$atTypeKey]);
            $atResult = $this->getListByDays('ProcessDate', 'Earnings', $atTypeKey, $earnings, $date);

            return [
                'listDays' => $awResult['listDays'],
                'earnings' => [
                    $awTypeKey => array_key_exists($awTypeKey, $awResult['Earnings']) ? $awResult['Earnings'][$awTypeKey] : [],
                    $atTypeKey => array_key_exists($atTypeKey, $atResult['Earnings']) ? $atResult['Earnings'][$atTypeKey] : [],
                ],
                'days' => [
                    $awTypeKey => array_key_exists($awTypeKey, $awResult['types']) ? $awResult['types'][$awTypeKey] : [],
                    $atTypeKey => array_key_exists($atTypeKey, $atResult['types']) ? $atResult['types'][$atTypeKey] : [],
                ],
                'total' => [
                    $awTypeKey => $awResult['total'][$awTypeKey],
                    $atTypeKey => $atResult['total'][$atTypeKey],
                ],
                'interval' => $interval,
                'lastDay' => $lastDate,
            ];
        }

        if ('cards' === $type) {
            $cards = $this->connection->fetchAllAssociative('
                SELECT
                        Card,
                        SUM(Earnings) AS Earnings, SUM(Clicks) AS Clicks
                FROM QsTransaction
                WHERE
                        ' . $whereDate . '  
                    AND Approvals > 0
                    AND Version = ' . QsTransaction::ACTUAL_VERSION . '
                GROUP BY Card
                ORDER by Earnings DESC, Clicks DESC
            ');

            if (empty($cards)) {
                return [];
            }

            $earnings = [];
            $legends = [];

            $index = 0;
            $otherEarnings = 0;
            $otherClicks = 0;

            foreach ($cards as $card) {
                if (strlen($card['Card']) > 50) {
                    $nameParts = explode(' ', $card['Card']);
                    $tmp = array_chunk($nameParts, \count($nameParts) / 2 + 1);
                    $card['Card'] = implode(' ', $tmp[0]) . "\n" . implode(' ', $tmp[1]);
                }

                if (++$index < self::CARDS_DISPLAY_LIMIT) {
                    $earnings[] = (float) $card['Earnings'];
                    $legends[] = $card['Card'] . ' (' . $this->localizeService->formatCurrency($card['Earnings'], 'USD') . ' / ' . $card['Clicks'] . ')';
                } else {
                    $otherEarnings += (float) $card['Earnings'];
                    $otherClicks += (int) $card['Clicks'];
                }
            }

            if (!empty($otherEarnings)) {
                $earnings[] = $otherEarnings;
                $legends[] = 'Other (' . $this->localizeService->formatCurrency($otherEarnings, 'USD') . ' / ' . $otherClicks . ')';
            }

            $result = [
                'earnings' => $earnings,
                'cards' => $legends,
                'interval' => $interval,
                'lastDay' => $lastDate,
            ];

            return $result;
        }

        throw new \Exception('Unknown data type');
    }

    /**
     * @param string $fieldDate [ClickDate, ProcessDate]
     * @param string $fieldCalculate [Clicks, Earnings, ...]
     * @param string $typeKey QsTransactionData::QS_TYPES keys
     * @return array[]
     */
    private function getListByDays(string $fieldDate, string $fieldCalculate, string $typeKey, array $items, \DateTime $date): array
    {
        $result = ['listDays' => []];
        $sumFieldCalculate = '_sum' . $fieldCalculate;

        $data = [];

        foreach ($items as $item) {
            if (!empty($item[$fieldDate]) && !empty($item[$sumFieldCalculate])) {
                $data[$typeKey][$item[$fieldDate]] = $item[$sumFieldCalculate];
            }
        }
        $result[$fieldCalculate] = $data;

        $total = 0;
        $dateCol = $date->format('Y-m-');

        for ($i = 1, $iCount = (int) $date->format('t'); $i <= $iCount; $i++) {
            $dateKey = $dateCol . str_pad($i, 2, '0', STR_PAD_LEFT);
            $value = array_key_exists($typeKey, $data) && array_key_exists($dateKey, $data[$typeKey]) ? $data[$typeKey][$dateKey] : 0;
            $result['types'][$typeKey][] = $value;
            $total += $value;
            $result['listDays'][] = $i;
        }
        $result['total'][$typeKey] = $total;

        return $result;
    }

    private function getClicksBy($between, array $conditions): array
    {
        return $this->connection->fetchAllAssociative('
            SELECT
                ClickDate,
                SUM(Clicks) AS _sumClicks
            FROM QsTransaction
            WHERE
                    ClickDate ' . $between . '
                AND (' . implode(' OR ', $conditions) . ')
                AND Version = ' . QsTransaction::ACTUAL_VERSION . '
            GROUP BY ClickDate
            -- HAVING _sumClicks > 0
            ORDER BY ClickDate ASC
        ');
    }

    private function getEarningsBy(string $where, array $conditions)
    {
        return $this->connection->fetchAllAssociative('
            SELECT
                ProcessDate,
                SUM(Earnings) AS _sumEarnings
            FROM QsTransaction
            WHERE
                    ' . $where . '
                AND (' . implode(' OR ', $conditions) . ')
                AND Approvals > 0
                AND Version = ' . QsTransaction::ACTUAL_VERSION . '
            GROUP BY ProcessDate
            -- HAVING _sumEarnings > 0
            ORDER BY ProcessDate ASC
        ');
    }
}
