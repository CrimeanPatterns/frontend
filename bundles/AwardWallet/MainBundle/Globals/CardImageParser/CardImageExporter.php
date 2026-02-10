<?php

namespace AwardWallet\MainBundle\Globals\CardImageParser;

use AwardWallet\MainBundle\Globals\UnbufferedConnectionFactory;
use Doctrine\DBAL\Connection;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class CardImageExporter
{
    /**
     * @var UnbufferedConnectionFactory
     */
    private $unbufferedConnectionFactory;
    /**
     * @var UrlGeneratorInterface
     */
    private $urlGenerator;

    /**
     * CardImageExporter constructor.
     */
    public function __construct(
        UnbufferedConnectionFactory $unbufferedConnectionFactory,
        UrlGeneratorInterface $urlGenerator
    ) {
        $this->unbufferedConnectionFactory = $unbufferedConnectionFactory;
        $this->urlGenerator = $urlGenerator;
    }

    /**
     * @param string[] $accountUuids
     * @throws \Doctrine\DBAL\DBALException
     */
    public function export(OutputInterface $output, array $accountUuids)
    {
        $stmt = $this->unbufferedConnectionFactory->createConnection()->executeQuery("
            select 
                ciDest.CardImageID,
                ciDest.UUID,
                ciDest.ComputerVisionResult,
                ciDest.UploadDate,
                ciDest.AccountID,
                ciDest.Width,
                ciDest.Height,
                ciDest.Kind,
                ciDest.CCDetected,
                ciDest.CCDetectorVersion,
                p.DisplayName,
                ciDest.ProviderID,
                p.Code
            from CardImage ciSource
            join Provider p on
                ciSource.ProviderID = p.ProviderID and
                p.Kind <> ?
            join CardImage ciDest on 
                ciSource.AccountID = ciDest.AccountID
            where
                ciSource.UUID in (?) and
                ciDest.ComputerVisionResult is not null and
                ciDest.ComputerVisionResult <> ''
            order by
                ciDest.ProviderID,
                ciDest.AccountID,
                ciDest.Kind
            ",
            [
                PROVIDER_KIND_CREDITCARD,
                $accountUuids,
            ],
            [
                \PDO::PARAM_INT,
                Connection::PARAM_STR_ARRAY,
            ]
        );
        $stmt->setFetchMode(\PDO::FETCH_ASSOC);

        $output->write("[\n");
        $cardImage = $stmt->fetch();
        $isEof = !$cardImage;

        while (!$isEof) {
            if (!$cardImage['ComputerVisionResult']) {
                continue;
            }

            $cardImage['Url'] = $this->urlGenerator->generate('aw_card_image_download_staff_proxy', ['cardImageUUID' => $cardImage['UUID']], UrlGeneratorInterface::ABSOLUTE_URL);
            $output->write(json_encode($cardImage));

            if (!($cardImage = $stmt->fetch())) {
                $isEof = true;
            } else {
                $output->writeln(',');
            }
        }

        $output->write("\n]");
    }
}
