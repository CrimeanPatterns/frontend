<?php

namespace AwardWallet\MainBundle\Service\VisitedCountries;

use AwardWallet\MainBundle\Globals\Localizer\LocalizeService;
use Cocur\Slugify\Slugify;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;

class Exporter
{
    private LocalizeService $localizer;

    public function __construct(LocalizeService $localizer)
    {
        $this->localizer = $localizer;
    }

    /**
     * @param Period[] $periods
     */
    public function export(string $user, array $periods): Response
    {
        $spreadsheet = new Spreadsheet();
        $spreadsheet->getProperties()
            ->setCreator('AwardWallet.com')
            ->setLastModifiedBy('AwardWallet.com')
            ->setTitle(sprintf('AwardWallet.com - countries visited by %s', $user))
            ->setSubject(sprintf('Countries visited by %s', $user));
        $sheet = $spreadsheet->setActiveSheetIndex(0);
        $sheet->setTitle('Countries');

        $this->setHeader($sheet);

        $k = 2;

        foreach ($periods as $period) {
            $sheet->setCellValue("A$k", $period->startDate ? $this->localizer->formatDate($period->startDate, 'long') : '?');
            $sheet->setCellValue("B$k", $period->endDate ? $this->localizer->formatDate($period->endDate, 'long') : '?');
            $sheet->setCellValue("C$k", $period->getDays() ?? '?');
            $sheet->setCellValue("D$k", $period->country);
            $this->setStyle($sheet, ["A$k", "B$k", "C$k", "D$k"], false, true);

            $k++;
        }

        $this->setupPage($sheet, 'A1:D' . ($k - 1));

        ob_start();
        $writer = IOFactory::createWriter($spreadsheet, 'Xls');
        $writer->save('php://output');
        $output = ob_get_clean();

        $response = new Response($output, 200, [
            'Content-Type' => 'application/vnd.ms-excel',
            'Content-Length' => strlen($output),
            'Pragma' => '',
            'Cache-Control' => 'max-age=0',
        ]);
        $response->setCache(['private' => true]);
        $fileName = (new Slugify(['lowercase' => false]))->slugify($user, ' ');

        $response->headers->set(
            'Content-Disposition',
            $response->headers->makeDisposition(
                ResponseHeaderBag::DISPOSITION_ATTACHMENT,
                sprintf('AwardWallet.com - countries visited by %s.xls', $fileName)
            )
        );

        return $response;
    }

    private function setHeader(Worksheet $sheet)
    {
        $sheet->getRowDimension(1)->setRowHeight(50);
        $sheet->getColumnDimension('A')->setAutoSize(true);
        $sheet->getColumnDimension('B')->setAutoSize(true);
        $sheet->getColumnDimension('C')->setAutoSize(true);
        $sheet->getColumnDimension('D')->setAutoSize(true);
        $sheet->setCellValue('A1', 'From');
        $sheet->setCellValue('B1', 'To');
        $sheet->setCellValue('C1', 'Length of Stay in days');
        $sheet->setCellValue('D1', 'Country');
        $this->setStyle($sheet, ['A1', 'B1', 'C1', 'D1'], true, true, 14);
    }

    private function setupPage(Worksheet $worksheet, string $printingArea)
    {
        $setup = $worksheet->getPageSetup();
        $setup->setOrientation(PageSetup::ORIENTATION_LANDSCAPE);
        $setup->setPaperSize(PageSetup::PAPERSIZE_A4);
        $setup->setHorizontalCentered(true);
    }

    private function setStyle(
        Worksheet $worksheet,
        array $cells,
        bool $bold,
        bool $center = false,
        int $fontSize = 12
    ) {
        foreach ($cells as $cell) {
            $style = $worksheet->getCell($cell)->getStyle();
            $style->getFont()
                ->setBold($bold)
                ->setSize($fontSize);

            if ($center) {
                $style->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $style->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
            } else {
                $style->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
                $style->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
            }
        }
    }
}
