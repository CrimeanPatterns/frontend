<?php

namespace AwardWallet\MainBundle\Service\Account;

use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\FrameworkExtension\AwTokenStorageInterface;
use AwardWallet\MainBundle\Globals\AccountList\Options;
use AwardWallet\MainBundle\Globals\AccountList\OptionsFactory;
use AwardWallet\MainBundle\Manager\AccountListManager;
use Cocur\Slugify\Slugify;
use Doctrine\ORM\EntityManagerInterface;
use JMS\TranslationBundle\Model\Message;
use JMS\TranslationBundle\Translation\TranslationContainerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Contracts\Translation\TranslatorInterface;

class Export implements TranslationContainerInterface
{
    public const FIELD_USERNAME = 'UserName';
    public const FIELD_BALANCE = 'RawBalance';

    public const BALANCE_FORMAT = '#,##0';

    public const STYLE = [
        'BORDER_COLOR' => '333333',
        'BACKGROUND_LOGO' => 'f0f0f0',
        'BACKGROUND_TOTAL' => 'd8d8d8',
        'BORDER_COLOR_SEP' => 'dddddd',
    ];
    /** @var EntityManagerInterface */
    protected $em;

    /** @var TranslatorInterface */
    private $translator;

    /** @var Usr */
    private $user;
    /**
     * @var \Closure
     */
    private $accountsProvider;

    public function __construct(EntityManagerInterface $em,
        TranslatorInterface $translator,
        AccountListManager $accountListManager,
        OptionsFactory $optionsFactory,
        AwTokenStorageInterface $tokenStorage
    ) {
        $this->em = $em;
        $this->translator = $translator;
        $memoizedAccounts = null;
        $this->accountsProvider = function () use (&$memoizedAccounts, $accountListManager, $optionsFactory, $tokenStorage) {
            if (!isset($memoizedAccounts)) {
                $memoizedAccounts = $accountListManager->getAccountList(
                    $optionsFactory->createDefaultOptions()
                        ->set(Options::OPTION_USER, $tokenStorage->getUser())
                );
            }

            return $memoizedAccounts;
        };
    }

    public function setUser(Usr $usr)
    {
        $this->user = $usr;

        return $this;
    }

    public function getUser()
    {
        return $this->user;
    }

    public function travelPlanner($format = 'xls')
    {
        if ('xls' != $format) {
            return $this->translator->trans('account.export.format-not-supported', ['%formatName%' => $format]);
        }

        $data = [];
        $providers = $this->getProviders();

        foreach ($providers as $providerCode => $provider) {
            $data[] = [
                'ProviderCode' => $providerCode,
                'ProviderName' => $provider['ProviderName'],
                self::FIELD_BALANCE => $this->getFieldSum($providerCode),
                'isCustom' => $provider['isCustom'],
            ];
        }
        usort($data, function ($a, $b) {
            return $a[self::FIELD_BALANCE] < $b[self::FIELD_BALANCE];
        });

        $totalOwners = [];
        $owners = $this->getOwners();
        $ownersCount = count($owners);

        if (0 === $ownersCount || empty($data)) {
            return $this->translator->trans('account.export.no-data');
        }

        $cells = str_split('ABCDEFGHIJKLMNOPQRSTUVWXYZ', 1);
        $row = 2;
        $xls = $this->fetchExcelObject();
        $xls->getActiveSheet()->getRowDimension($row)->setRowHeight(30);
        $sheet = $xls->setActiveSheetIndex(0);
        $sheet->getStyle('A' . $row)->getAlignment()->setVertical(\PHPExcel_Style_Alignment::VERTICAL_BOTTOM);
        $sheet->setCellValue('A' . $row, $this->translator->trans('award.account.list.column.program'));

        for ($j = 0; $j < $ownersCount; $j++) {
            $cell = $cells[1 + $j];
            $sheet->setCellValue($cell . $row, $owners[$j]);
            $sheet->getColumnDimension($cell)->setWidth(2 + strlen($owners[$j]));
            $totalOwners[$owners[$j]] = 0;
        }

        $colTotal = $cells[1 + $j];
        $cell = $cells[1 + $j];
        $sheet->getColumnDimension('A')->setWidth(40);
        $sheet->setCellValue($cell . $row, $this->translator->trans('account.export.grand-total'));
        $sheet->getColumnDimension($cell)->setWidth(14);
        $sheet->getStyle('A' . $row . ':' . $cell . $row)->applyFromArray([
            'fill' => [
                'type' => \PHPExcel_Style_Fill::FILL_SOLID,
                'color' => ['rgb' => self::STYLE['BACKGROUND_LOGO']],
            ],
            'borders' => [
                'bottom' => [
                    'style' => \PHPExcel_Style_Border::BORDER_THIN,
                    'color' => ['rgb' => self::STYLE['BORDER_COLOR']],
                ],
            ],
            'font' => [
                'bold' => true,
            ],
        ]);

        for ($i = 0, $iCount = count($data); $i < $iCount; $i++) {
            if (empty($data[$i][self::FIELD_BALANCE]) || 1 > (int) $data[$i][self::FIELD_BALANCE]) {
                continue;
            }
            $row++;
            $sheet->setCellValueExplicit('A' . $row, html_entity_decode($data[$i]['ProviderName']), \PHPExcel_Cell_DataType::TYPE_STRING);

            for ($j = 0; $j < $ownersCount; $j++) {
                $customKey = ($data[$i]['ProviderCode'] === $data[$i]['ProviderName'] ? 'DisplayName' : 'ProviderCode');
                $acc = $this->getAccountsBy([
                    $customKey => $data[$i]['ProviderCode'],
                    self::FIELD_USERNAME => $owners[$j],
                ]);

                $balance = '';

                if (!empty($acc)) {
                    $sumBalance = array_sum(array_column($acc, self::FIELD_BALANCE));
                    $balance = empty($sumBalance) ? '' : $sumBalance;
                    $totalOwners[$owners[$j]] += $sumBalance;
                } elseif ($data[$i]['isCustom'] && !empty($data[$i][self::FIELD_BALANCE])) {
                    $balance = (int) $this->getFieldSum($data[$i]['ProviderCode'], $owners[$j]);
                    1 < $balance ?: $balance = '';
                }

                $cell = $cells[1 + $j] . $row;
                $sheet->setCellValue($cell, $balance);
                $sheet->getStyle($cell)->applyFromArray([
                    'alignment' => [
                        'horizontal' => \PHPExcel_Style_Alignment::HORIZONTAL_RIGHT,
                        'indent' => 1,
                    ],
                ])->getNumberFormat()->setFormatCode(self::BALANCE_FORMAT);
            }

            // Column - Total
            // $balance = empty($data[$i][self::FIELD_BALANCE]) ? '' : $data[$i][self::FIELD_BALANCE];
            $cell = $cells[1 + $j] . $row;
            $sheet->setCellValue($cell, '=SUM(B' . $row . ':' . $cells[$j] . $row . ')');
            $sheet->getStyle($cell)->applyFromArray([
                'alignment' => [
                    'horizontal' => \PHPExcel_Style_Alignment::HORIZONTAL_RIGHT,
                    'indent' => 1,
                ],
                'fill' => [
                    'type' => \PHPExcel_Style_Fill::FILL_SOLID,
                    'color' => ['rgb' => self::STYLE['BACKGROUND_LOGO']],
                ],
            ])->getNumberFormat()->setFormatCode(self::BALANCE_FORMAT)->setBuiltInFormatCode();
        }

        // Row - Total
        $row++;
        $sum = 0;
        $sheet->setCellValue('A' . $row, $this->translator->trans('account.export.grand-total'));

        for ($j = 0; $j < $ownersCount; $j++) {
            $sum += $totalOwners[$owners[$j]];
            $cell = $cells[1 + $j] . $row;
            $sheet->setCellValue($cell, '=SUM(' . $cells[1 + $j] . '3:' . $cells[1 + $j] . ($row - 1) . ')');
            $sheet->getStyle($cell)->applyFromArray([
                'alignment' => [
                    'horizontal' => \PHPExcel_Style_Alignment::HORIZONTAL_RIGHT,
                    'indent' => 1,
                ],
            ])->getNumberFormat()->setFormatCode(self::BALANCE_FORMAT);
        }

        if (empty($sum)) {
            return $this->translator->trans('account.export.no-data');
        }

        $cell = $cells[1 + $j] . $row;
        $sheet->setCellValue($cell, '=SUM(' . $cells[1 + $j] . '3:' . $cells[1 + $j] . ($row - 1) . ')');
        $sheet->getStyle($cell)->applyFromArray([
            'alignment' => [
                'horizontal' => \PHPExcel_Style_Alignment::HORIZONTAL_RIGHT,
                'indent' => 1,
            ],
        ])->getNumberFormat()->setFormatCode(self::BALANCE_FORMAT);

        $sheet->getStyle('A2:' . $cell)->applyFromArray([
            'borders' => [
                'allborders' => [
                    'style' => \PHPExcel_Style_Border::BORDER_THIN,
                    'color' => ['rgb' => self::STYLE['BORDER_COLOR_SEP']],
                ],
            ],
        ]);

        $sheet->getStyle('A' . $row . ':' . $cell)->applyFromArray([
            'fill' => [
                'type' => \PHPExcel_Style_Fill::FILL_SOLID,
                'color' => ['rgb' => self::STYLE['BACKGROUND_LOGO']],
            ],
            'borders' => [
                'top' => [
                    'style' => \PHPExcel_Style_Border::BORDER_THIN,
                    'color' => ['rgb' => self::STYLE['BORDER_COLOR']],
                ],
            ],
            'font' => [
                'bold' => true,
            ],
        ]);

        $totalColumn = $colTotal . '3:' . $colTotal . ($row - 1);
        $sheet->getStyle($totalColumn)
            ->applyFromArray([
                'borders' => [
                    'left' => [
                        'style' => \PHPExcel_Style_Border::BORDER_THIN,
                        'color' => ['rgb' => self::STYLE['BORDER_COLOR']],
                    ],
                ],
                'font' => [
                    'bold' => true,
                ],
            ]);
        $sheet->getStyle($totalColumn)
            ->getNumberFormat()->setFormatCode(self::BALANCE_FORMAT);

        $sheet->getStyle('A1:' . $colTotal . '1')->applyFromArray([
            'fill' => [
                'type' => \PHPExcel_Style_Fill::FILL_SOLID,
                'color' => ['rgb' => self::STYLE['BACKGROUND_TOTAL']],
            ],
        ]);

        $sheet->getStyle($colTotal . $row)
            ->applyFromArray([
                'fill' => [
                    'type' => \PHPExcel_Style_Fill::FILL_SOLID,
                    'color' => ['rgb' => '5b7bbb'],
                ],
                'font' => [
                    'color' => ['rgb' => 'ffffff'],
                ],
            ]);

        $xls->getActiveSheet()
            ->freezePane('B3')
            ->setSelectedCell('A1')
            ->setTitle('AwardWallet - Travel Planner');

        return $xls;
    }

    public function fetchExcelObject()
    {
        require_once $GLOBALS['sPath'] . '/lib/3dParty/PHPExcel.php';

        $xls = new \PHPExcel();
        $xls->getProperties()->setCreator('AwardWallet.com')
            ->setLastModifiedBy('AwardWallet.com')
            ->setTitle('AwardWallet.com - Accounts')
            ->setSubject('Accounts')
            ->setDescription('Visit awardwallet.com for more information')
            ->setKeywords('awardwallet')
            ->setCategory('rewards');

        $draw = new \PHPExcel_Worksheet_Drawing();
        $draw->setName('Logo');
        $draw->setDescription('AwardWallet');
        $draw->setPath($GLOBALS['sPath'] . '/assets/awardwalletnewdesign/img/logo.png');
        $draw->setHeight(22);
        $draw->setWorksheet($xls->getActiveSheet());
        $draw->setCoordinates('A1');
        $draw->setOffsetX(15)->setOffsetY(15);

        $xls->getActiveSheet()->getRowDimension(1)->setRowHeight(39);

        return $xls;
    }

    public function downloadExcel($xls, $options = [])
    {
        if (!($xls instanceof \PHPExcel)) {
            return false;
        }

        require_once $GLOBALS['sPath'] . '/lib/3dParty/PHPExcel/IOFactory.php';

        if (!isset($options['fileName'])) {
            $slugify = new Slugify();
            $userName = ucfirst($slugify->slugify($this->getUser()->getFirstname())) . ' ' . ucfirst($slugify->slugify($this->getUser()->getLastname()));
            $options['fileName'] = 'AwardWallet.com - Accounts for ' . trim($userName) . '.xls';
        }

        try {
            ob_start();
            $xlsWriter = \PHPExcel_IOFactory::createWriter($xls, 'Excel5');
            $xlsWriter->save('php://output');
            $xlsOutput = ob_get_clean();

            $response = new Response($xlsOutput, 200, [
                'Content-Type' => 'application/vnd.ms-excel',
                'Content-Length' => strlen($xlsOutput),
                'Pragma' => '',
            ]);
            $response->setCache(['private' => true]);
            $response->headers->set('Content-Disposition', $response->headers->makeDisposition(ResponseHeaderBag::DISPOSITION_INLINE, $options['fileName']));
        } catch (\Exception $e) {
            return false;
        }

        return $response;
    }

    /**
     * Returns an array of messages.
     *
     * @return array<Message>
     */
    public static function getTranslationMessages()
    {
        return [
            (new Message('account.export.format-not-supported'))->setDesc('Sorry, we currently do not support %formatName% format'),
            (new Message('account.export.grand-total'))->setDesc('Grand Total'),
            (new Message('account.export.no-data'))->setDesc('No data'),
        ];
    }

    private function getAccounts()
    {
    }

    private function getProviders()
    {
        $providers = [];

        foreach (($this->accountsProvider)() as $account) {
            $providerCode = !empty($account['ProviderCode']) ? $account['ProviderCode'] : $account['DisplayName'];

            if (!array_key_exists($providerCode, $providers)) {
                $providers[$providerCode] = [
                    'ProviderName' => $account['DisplayName'],
                    'isCustom' => !empty($account['isCustom']),
                ];
            }
        }

        return $providers;
    }

    private function getOwners()
    {
        $names = [];

        foreach (($this->accountsProvider)() as $account) {
            if (!in_array($account[self::FIELD_USERNAME], $names)) {
                $names[] = $account[self::FIELD_USERNAME];
            }
        }

        $checkNotEmpty = [];

        for ($i = 0, $iCount = count($names); $i < $iCount; $i++) {
            $checkNotEmpty[$names[$i]] = 0;

            foreach (($this->accountsProvider)() as $account) {
                if ($account[self::FIELD_USERNAME] === $names[$i]) {
                    $checkNotEmpty[$names[$i]] += $account[self::FIELD_BALANCE];
                }
            }

            if (empty($checkNotEmpty[$names[$i]])) {
                unset($names[$i]);
            }
        }

        return array_values($names);
    }

    private function getFieldSum($providerCode, $owner = null, $fieldName = self::FIELD_BALANCE)
    {
        $total = 0;

        foreach (($this->accountsProvider)() as $account) {
            if (($providerCode === $account['ProviderCode'] || 1 == $account['isCustom'] && $providerCode == $account['DisplayName'] && (null == $owner || $account['UserName'] == $owner))
                && !empty($account[$fieldName])
                && 'Account' == $account['TableName']
                && empty($account['SubAccountID'])
            ) {
                $total += $account[$fieldName];
            }
        }

        return $total;
    }

    private function getAccountsBy($condition = [])
    {
        if (empty($condition)) {
            return [];
        }

        $accounts = [];

        foreach (($this->accountsProvider)() as $account) {
            $is = [];

            foreach ($condition as $field => $value) {
                $is[] = isset($account[$field]) && $account[$field] == $value ? true : false;
            }
            $is = array_unique($is);

            if (1 === count($is) && true === $is[0]) {
                $accounts[] = $account;
            }
        }

        return empty($accounts) ? [] : $accounts;
    }
}
