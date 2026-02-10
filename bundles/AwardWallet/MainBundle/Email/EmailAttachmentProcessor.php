<?php

namespace AwardWallet\MainBundle\Email;

use AwardWallet\MainBundle\Entity\Files\ItineraryFile;
use AwardWallet\MainBundle\Entity\Itinerary;
use AwardWallet\MainBundle\Manager\Files\ItineraryFileManager;
use AwardWallet\MainBundle\Service\Itinerary\Form\Saver;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class EmailAttachmentProcessor
{
    private LoggerInterface $logger;
    private Saver $saver;
    private ItineraryFileManager $itineraryFileManager;

    public function __construct(
        LoggerInterface $logger,
        Saver $saver,
        ItineraryFileManager $itineraryFileManager
    ) {
        $this->logger = $logger;
        $this->saver = $saver;
        $this->itineraryFileManager = $itineraryFileManager;
    }

    public function process(Itinerary $itinerary, $email): void
    {
        $plancake = new \PlancakeEmailParser($email);
        $pdfs = $plancake->searchAttachmentByName('.*[.]pdf');

        if (count($pdfs) < 1 || count($pdfs) > 10) {
            return;
        }
        $this->logger->info('saving attachments as notes');
        /** @var ItineraryFile[] $files */
        $files = $this->itineraryFileManager->getFiles($itinerary->getKind(), $itinerary->getId());

        foreach ($pdfs as $idx) {
            $name = null;

            foreach (['Content-Type', 'Content-Disposition'] as $header) {
                if (preg_match('/name=[\"\'](.+[.]pdf)[\"\']/', $plancake->getAttachmentHeader($idx, $header), $m) > 0) {
                    $name = $m[1];

                    break;
                }
            }

            if (empty($name)) {
                $this->logger->info('attachment name not found in ' . $idx);

                continue;
            }

            foreach ($files as $file) {
                if (strcasecmp($name, $file->getFileName()) === 0) {
                    $this->logger->info('duplicate file name ' . $name);

                    continue 2;
                }
            }
            $pdfBody = $plancake->getAttachmentBody($idx);

            if (strpos($pdfBody, '%PDF') !== 0) {
                $this->logger->info('file ' . $name . ' is not a pdf');

                continue;
            }
            $this->logger->info('saving attachment ' . $name);
            $tmpPath = sys_get_temp_dir() . '/' . uniqid() . substr(md5($name), -8) . '.pdf';
            file_put_contents($tmpPath, $pdfBody);
            $file = new UploadedFile($tmpPath, $name, 'application/pdf');

            try {
                $this->logger->info('saved attachment ' . $name . ' : ' . $this->saver->uploadFile($file, $itinerary));
            } catch (\Exception $e) {
                $this->logger->warning('failed to save attachment: ' . $e->getMessage());
            }
            unlink($tmpPath);
        }
    }
}
