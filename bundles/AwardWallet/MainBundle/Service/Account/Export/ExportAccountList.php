<?php

namespace AwardWallet\MainBundle\Service\Account\Export;

use AwardWallet\MainBundle\Controller\Account\ExportController;
use AwardWallet\MainBundle\Globals\AccountList\Resolver\ExpirationDateResolver;
use AwardWallet\MainBundle\Globals\Localizer\LocalizeService;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;
use PhpOffice\PhpSpreadsheet\Worksheet\HeaderFooter;
use PhpOffice\PhpSpreadsheet\Worksheet\HeaderFooterDrawing;
use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;

class ExportAccountList
{
    public const NEXT_LETTER = 1;

    /**
     * @var string
     */
    private $kernelRootDir;

    /**
     * @var LocalizeService
     */
    private $localizeService;

    public function __construct(string $rootDir, LocalizeService $localizeService)
    {
        $this->kernelRootDir = $rootDir;
        $this->localizeService = $localizeService;
    }

    public function export(array $datas, string $userName, string $type, array $options = []): ?Response
    {
        $spreadsheet = $this->exportPrepare($datas, $userName, $type);

        return $this->download($spreadsheet, $options, $userName, $type);
    }

    private function exportPrepare(array $datas, string $userName, string $type): Spreadsheet
    {
        $spreadsheet = $this->getSpreadsheet($type);
        $sheet = $spreadsheet->setActiveSheetIndex(0);
        $y = 1;
        $isXls = false;

        if (ExportController::EXPORT_TYPE_EXCEL === $type) {
            $isXls = true;
        }
        $isPdf = ExportController::EXPORT_TYPE_PDF === $type;
        $spreadsheet->getActiveSheet()->getRowDimension(1)->setRowHeight(50);

        if (!$isXls) {
            $spreadsheet->getActiveSheet()->getRowDimension(1)->setRowHeight(30);
        }

        $colMap = [
            'AccountId' => 'A',
            'Type' => 'B',
            'AccountOwner' => 'C',
            'DisplayName' => 'D',
            'AccountNumber' => 'E',
            'Login' => 'F',
            'Balance' => 'G',
            'CashEquivalent' => 'H',
            'LastChange' => 'I',
            'ExpirationDate' => 'J',
            'LastUpdate' => 'K',
            'Status' => 'L',
            'Comments' => 'M',
        ];

        if (!$isXls) {
            unset($colMap);
            $colMap = [
                'AccountId' => 'A',
                'Type' => 'B',
                'AccountOwner' => 'C',
                'DisplayName' => 'D',
                'AccountNumber' => 'E',
                'Login' => 'F',
                'Balance' => 'G',
                'CashEquivalent' => 'H',
                'LastChange' => 'I',
                'ExpirationDate' => 'J',
                'LastUpdate' => 'K',
                'Status' => 'L',
            ];
        }
        $columnDimensionBase = [];

        foreach ($colMap as $index) {
            if (in_array($index, ['A', 'B', 'D'])) {
                $columnDimensionBase[$index] = 20;
            } elseif (in_array($index, ['C', 'K'])) {
                $columnDimensionBase[$index] = 30;
            } elseif (in_array($index, ['E', 'F'])) {
                $columnDimensionBase[$index] = 25;
            } else {
                $columnDimensionBase[$index] = 15;
            }
        }

        $colTitles = [
            'AccountId' => $isPdf ? 'ID / SubID' : 'Account Id / Sub Id',
            'Type' => 'Type',
            'AccountOwner' => 'Account Owner',
            'DisplayName' => 'Award Program',
            'AccountNumber' => 'Account Number',
            'Login' => 'Login Name',
            'Balance' => 'Balance',
            'CashEquivalent' => 'Cash Equivalent',
            'LastChange' => 'Last Change',
            'ExpirationDate' => 'Expiration',
            'LastUpdate' => 'Last Update',
            'Status' => 'Status',
            'Comments' => 'Comments',
        ];

        foreach ($colMap as $name => $index) {
            $sheet->setCellValue($index . $y, $colTitles[$name]);
        }

        $kindTitles = [];

        if ($isXls) {
            $sheet->setCellValue($colMap['Comments'] . $y, 'Comments');
            $index = ord($colMap['Comments']) + self::NEXT_LETTER;
            $kindTitles = $this->getExportKindTitles();

            foreach ($kindTitles as $kind => $caption) {
                $sheet->setCellValue($this->excelColName($index) . $y, $caption);
                $index++;
            }
        }
        $cellCoordinate = $colMap['AccountId'] . $y . ':' . $colMap['Status'] . $y;

        if ($isXls) {
            $cellCoordinate = $colMap['AccountId'] . $y . ':' . $this->excelColName($index) . $y;
        }
        $spreadsheet->getActiveSheet()->getStyle($cellCoordinate)->applyFromArray(
            [
                'font' => [
                    'bold' => true,
                ],
            ]
        );

        foreach ($columnDimensionBase as $index => $val) {
            $sheet->getColumnDimension($index)->setWidth($val);
        }

        if ($isXls) {
            $sheet->getColumnDimension($colMap['Status'])->setWidth(30);
            $sheet->getColumnDimension($colMap['Comments'])->setWidth(80);
            $index = ord($colMap['Comments']) + self::NEXT_LETTER;

            foreach ($kindTitles as $kind => $caption) {
                $sheet->getColumnDimension($this->excelColName($index))->setWidth(20);
                $index++;
            }
        }
        $y++;

        // Setting a space to prevent the number from stripping decimals for other locales where period is the separator
        $pdfStringPadding = $isPdf ? ' ' : '';

        foreach ($datas as $row) {
            $props = $this->getPropsByKind($row['Properties'] ?? []);

            if (empty($row['Balance'])) {
                $row['Balance'] = null;
            }

            if (!isset($row['FormattedBalance'])) {
                $row['FormattedBalance'] = $row['Balance'];
            }
            $expirationDate = $this->checkExpirationDate($row['ExpirationDate'], $row['ExpirationStateType'], $row['ExpirationDateTs']);

            $lastChange = '';
            $isNA = is_null($row['Balance']);

            if (is_null($row['Balance'])) {
                $lastChange = '';
            }

            if (!$isNA && array_key_exists('TableName', $row) && 'Coupon' == $row['TableName']) {
                $lastChange = '';
            }

            if (!empty($row['LastChange'])) {
                $lastChange = $row['LastChange'];
            }
            $sheet
                ->setCellValue($colMap['AccountId'] . $y, $row['ID'])
                ->setCellValue($colMap['Type'] . $y, $row['Type'])
                ->setCellValue($colMap['AccountOwner'] . $y, $row['Owner'])
                ->setCellValue($colMap['DisplayName'] . $y, htmlspecialchars_decode($row['DisplayName']))
                ->setCellValue($colMap['AccountNumber'] . $y, $row['AccountNumber'])
                ->setCellValue($colMap['Login'] . $y, htmlspecialchars_decode($row['Login']))
                ->setCellValueExplicit($colMap['Balance'] . $y, $pdfStringPadding . $row['Balance'], DataType::TYPE_STRING)
                ->setCellValueExplicit($colMap['CashEquivalent'] . $y, $pdfStringPadding . $row['CashEquivalent'], DataType::TYPE_STRING)
                ->setCellValueExplicit($colMap['LastChange'] . $y, $pdfStringPadding . $lastChange, DataType::TYPE_STRING)
                ->setCellValue($colMap['ExpirationDate'] . $y, $row['ExpirationDate'])
                ->setCellValue($colMap['LastUpdate'] . $y, $this->localizeService->formatDate(strtotime($row['LastUpdate']), 'full'))
                ->setCellValue($colMap['Status'] . $y, $row['MainProperties']['Status']['Value'] ?? '')
            ;

            if ($isXls) {
                $sheet
                    ->setCellValue($colMap['Comments'] . $y, str_replace(['<br', '/>'], ['', ''], $row['comment']))
                ;

                $index = ord($colMap['Comments']) + self::NEXT_LETTER;

                foreach ($kindTitles as $kind => $caption) {
                    $sheet->setCellValue($this->excelColName($index) . $y, $props[$kind] ?? '');
                    $sheet->getCell($this->excelColName($index) . $y)->setDataType(DataType::TYPE_STRING);
                    $index++;
                }
            }

            foreach ($colMap as $name => $index) {
                $sheet
                    ->getCell($index . $y)->setDataType(DataType::TYPE_STRING);
            }

            $this->convertDates($spreadsheet, $colMap['ExpirationDate'] . $y, $expirationDate);

            if ($lastChange > 0) {
                $spreadsheet->getActiveSheet()->getStyle($colMap['LastChange'] . $y)->applyFromArray([
                    'font' => [
                        'color' => ['rgb' => '4dbfa2'],
                    ],
                ]);
            }

            if ($lastChange < 0) {
                $spreadsheet->getActiveSheet()->getStyle($colMap['LastChange'] . $y)->applyFromArray([
                    'font' => [
                        'color' => ['rgb' => '4684c4'],
                    ],
                ]);
            }

            if (in_array(ArrayVal($row, 'ExpirationState'), ['soon'])) {
                $spreadsheet->getActiveSheet()->getStyle($colMap['LastUpdate'] . $y)->applyFromArray([
                    'font' => [
                        'color' => ['rgb' => 'ee9101'],
                    ],
                ]);
            }

            if (in_array(ArrayVal($row, 'ExpirationState'), ['expired'])) {
                $spreadsheet->getActiveSheet()->getStyle($colMap['LastUpdate'] . $y)->applyFromArray([
                    'font' => [
                        'color' => ['rgb' => 'e60405'],
                    ],
                ]);
            }

            if (in_array(ArrayVal($row, 'ExpirationState'), ['far'])) {
                $spreadsheet->getActiveSheet()->getStyle($colMap['LastUpdate'] . $y)->applyFromArray([
                    'font' => [
                        'color' => ['rgb' => '00971c'],
                    ],
                ]);
            }
            $y++;

            if (!empty($row['SubAccountsArray'])) {
                $subIdPadding = $isPdf ? str_repeat(' ', 3) . '/' : str_repeat(' ', 4) . '/ ';

                foreach ($row['SubAccountsArray'] as $subAccount) {
                    $sheet
                        ->setCellValue($colMap['AccountId'] . $y, $subIdPadding . ($subAccount['SubAccountID'] ?? $subAccount['ID']))
                        ->setCellValue($colMap['Type'] . $y, $row['Type'])
                        ->setCellValue($colMap['AccountOwner'] . $y, $row['Owner'])
                        ->setCellValue($colMap['DisplayName'] . $y, htmlspecialchars_decode($subAccount['DisplayName']))
                        ->setCellValue($colMap['AccountNumber'] . $y, $subAccount['CardNumber'] ?? '')
                        ->setCellValue($colMap['Balance'] . $y, $subAccount['Balance'] ?? '')
                        ->setCellValue($colMap['CashEquivalent'] . $y, $subAccount['CashEquivalent'] ?? '')
                        ->setCellValue($colMap['ExpirationDate'] . $y, $subAccount['ExpirationDate']);

                    if ($isXls) {
                        $comment = strip_tags($subAccount['comment'] ?? $subAccount['Description'] ?? '');

                        if (strlen($comment) > 128) {
                            $comment = substr($comment, 0, 128) . '...';
                        }
                        $sheet
                            ->setCellValue($colMap['Comments'] . $y, $comment)
                            ->getCell($colMap['Comments'] . $y)
                            ->setDataType(DataType::TYPE_STRING);
                    }

                    $y++;
                }
            }
        }
        $y--;

        $sheet->getColumnDimension($colMap['AccountId'])->setWidth(23);
        $cols = ['AccountId', 'Balance', 'AccountNumber'];

        if ($isXls) {
            $cols[] = 'Comments';
        }

        foreach ($cols as $key) {
            $sheet->getStyle($colMap[$key] . '1:' . $colMap[$key] . $y)->applyFromArray(['alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT]]);
        }
        $sheet->setSelectedCell('A1');

        $this->setSpreadsheetSettings($spreadsheet, $userName, $isXls, $kindTitles, $colMap, $y);

        return $spreadsheet;
    }

    private function checkExpirationDate(?string $expDate, ?string $expStateType, int $expDateTs): ?string
    {
        $expirationDate = null;

        if (
            !empty($expDate) && !empty($expStateType)
            && $expDateTs !== ExpirationDateResolver::EXPIRE_DONT_EXPIRE_TS
            && $expDateTs !== ExpirationDateResolver::EXPIRE_EMPTY_TS
            && $expDateTs !== ExpirationDateResolver::EXPIRE_UNKNOWN_TS
        ) {
            $expirationDate = $this->localizeService->formatDate(new \DateTime('@' . $expDateTs));
        } else {
            $expirationDate = '';
        }

        return !empty($expirationDate) ? $expirationDate : null;
    }

    private function setSpreadsheetSettings(
        Spreadsheet $spreadsheet,
        string $userName,
        bool $isXls,
        array $kindTitles,
        array $colMap,
        int $y
    ): void {
        $spreadsheet->getActiveSheet()->setTitle("AwardWallet.com - Accounts");
        $spreadsheet->getActiveSheet()->getHeaderFooter()->setOddHeader('&L&G&C&HAwardWallet.com - Accounts for ' . $userName);
        $spreadsheet->getActiveSheet()->getHeaderFooter()->setOddFooter('&L&B' . $spreadsheet->getProperties()->getTitle() . '&RPage &P of &N');

        if (!$isXls) {
            $spreadsheet->getActiveSheet()->getPageSetup()->setOrientation(PageSetup::ORIENTATION_LANDSCAPE);
            $spreadsheet->getActiveSheet()->getPageSetup()->setPaperSize(PageSetup::PAPERSIZE_A4);
        } else {
            $spreadsheet->getActiveSheet()->getPageSetup()->setOrientation(PageSetup::ORIENTATION_PORTRAIT);
            $spreadsheet->getActiveSheet()->getPageSetup()->setPaperSize(PageSetup::PAPERSIZE_A2_PAPER);
        }

        if ($isXls) {
            $area = "{$colMap['AccountId']}1:" . $this->excelColName(ord($colMap['Comments']) + self::NEXT_LETTER + count($kindTitles) - 1) . "{$y}";
        } else {
            $area = "{$colMap['Type']}1:{$colMap['Status']}{$y}";
        }
        $spreadsheet->getActiveSheet()->getPageSetup()->setPrintArea($area);
        $spreadsheet->getActiveSheet()->getPageSetup()->setRowsToRepeatAtTopByStartAndEnd(1, 1);
        $spreadsheet->getActiveSheet()->getPageSetup()->setFitToWidth(1);
        $spreadsheet->getActiveSheet()->getPageSetup()->setFitToPage(0);
        $spreadsheet->getActiveSheet()->getPageSetup()->setFitToHeight(0);

        if (!$isXls) {
            $style = [
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN,
                    ],
                ],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_LEFT,
                    'indent' => 1,
                ],
                'font' => [
                    'size' => 18,
                ],
            ];

            $spreadsheet->getActiveSheet()->setPrintGridlines(false);
            $spreadsheet->getActiveSheet()->getStyle("{$colMap['AccountId']}1:{$colMap['Status']}{$y}")->applyFromArray($style);
            $spreadsheet->getActiveSheet()->insertNewRowBefore(1, 1);
            $style = [
                'borders' => [
                    'bottom' => [
                        'borderStyle' => Border::BORDER_THIN,
                    ],
                ],
            ];
            $spreadsheet->getActiveSheet()->getStyle("{$colMap['Type']}1:{$colMap['Status']}{$y}")->applyFromArray($style);
            $draw = new HeaderFooterDrawing();
            $draw->setPath($this->kernelRootDir . "/../web/images/export_logo.png");
            $draw->setName('Logo AwardWallet');
            $draw->setHeight(60);
            $draw->setWorksheet($spreadsheet->getActiveSheet()); // bad practice
            $draw->setCoordinates($colMap['Login'] . '1');
            $spreadsheet->getActiveSheet()->getHeaderFooter()->addImage($draw, HeaderFooter::IMAGE_HEADER_CENTER);
        }
    }

    private function convertDates(Spreadsheet $spreadsheet, string $coordinate, ?string $expirationDate = null): void
    {
        $saveTimeZone = date_default_timezone_get();
        date_default_timezone_set('UTC');
        $cell = $spreadsheet->getActiveSheet()->getCell($coordinate);

        if (($expirationDate = strtotime($expirationDate)) !== false && ($expirationDate = Date::PHPToExcel($expirationDate)) !== false) {
            $cell->setValueExplicit($expirationDate, DataType::TYPE_NUMERIC);
            $spreadsheet->getActiveSheet()->getStyle($cell->getCoordinate())->getNumberFormat()->setFormatCode('mm\/dd\/yyyy');
        }
        date_default_timezone_set($saveTimeZone);
    }

    private function getPropsByKind(array $props): array
    {
        $result = [];

        foreach ($props as $prop) {
            if (isset($prop['Kind'])) {
                $result[$prop['Kind']] = $prop['Val'];
            }
        }

        return $result;
    }

    private function getExportKindTitles(): array
    {
        global $arPropertiesKinds;
        $kindTitles = $arPropertiesKinds;
        unset($kindTitles[PROPERTY_KIND_OTHER]);
        unset($kindTitles[PROPERTY_KIND_EXPIRATION]);
        unset($kindTitles[PROPERTY_KIND_NUMBER]);
        unset($kindTitles[PROPERTY_KIND_STATUS]);
        unset($kindTitles[PROPERTY_KIND_FAMILY_BALANCE]);

        return $kindTitles;
    }

    private function excelColName($ascii): string
    {
        if ($ascii > ord('Z')) {
            return 'A' . chr($ascii - (ord('Z') - ord('A')) - 1);
        } else {
            return chr($ascii);
        }
    }

    private function getSpreadsheet(string $type): Spreadsheet
    {
        $spreadsheet = new Spreadsheet();
        $spreadsheet->getProperties()
            ->setCreator('AwardWallet.com')
            ->setLastModifiedBy('AwardWallet.com')
            ->setTitle('AwardWallet.com - Accounts')
            ->setSubject('Accounts')
            ->setDescription('Visit awardwallet.com for more information')
            ->setKeywords('awardwallet')
            ->setCategory('rewards');

        if (ExportController::EXPORT_TYPE_EXCEL === $type) {
            $draw = new Drawing();
            $draw->setName('Logo AwardWallet');
            $draw->setDescription('AwardWallet');
            $draw->setPath($this->kernelRootDir . '/../web/assets/awardwalletnewdesign/img/logo.png');
            $draw->setHeight(33);
            $draw->setWorksheet($spreadsheet->getActiveSheet());
            $draw->setCoordinates('A1');
            $draw->setOffsetX(10)->setOffsetY(10);
        }

        return $spreadsheet;
    }

    private function download(?Spreadsheet $spreadsheet = null, array $options = [], string $userName = '', string $type): ?Response
    {
        if (null === $spreadsheet) {
            return null;
        }

        if (!isset($options['fileName'])) {
            $options['fileName'] = 'AwardWallet.com - Accounts for ' . ucwords(trim($userName)) . '.' . strtolower($type);
        }

        ob_start();
        $type = ucfirst($type);

        if (false !== stripos($type, 'pdf')) {
            $type = 'Mpdf';
        }
        $writer = IOFactory::createWriter($spreadsheet, $type);
        $writer->save('php://output');
        $output = ob_get_clean();

        $contentTypes = [
            'Mpdf' => 'application/pdf',
            'Xls' => 'application/vnd.ms-excel',
        ];
        $response = new Response($output, 200, [
            'Content-Type' => $contentTypes[$type] ?? '',
            'Content-Length' => strlen($output),
            'Pragma' => '',
            'Cache-Control' => 'max-age=0',
        ]);
        $response->setCache(['private' => true]);
        $response->headers->set('Content-Disposition', $response->headers->makeDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT, $options['fileName']));

        return $response;
    }
}
