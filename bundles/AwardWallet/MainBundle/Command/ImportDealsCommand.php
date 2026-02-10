<?php

namespace AwardWallet\MainBundle\Command;

use AwardWallet\MainBundle\FrameworkExtension\Mailer\Mailer;
use Doctrine\ORM\EntityManagerInterface;
use SoapClient;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ImportDealsCommand extends Command
{
    public const IMPORT_DEALS_MAPPED = 1;
    public const IMPORT_DEALS_GUESS = 2;

    public $providerDetectMethod = self::IMPORT_DEALS_MAPPED;

    // Define mapping here
    public $mapHash = [
        'Dollar Rent-a-Car, Inc.' => 'Dollar Rent A Car',
        'JCPenney' => 'JCP',
        'CarRentals.com' => 'Car Rentals',
        'Starbucks Canada' => 'Starbucks',
        'American Express Travel' => 'American Express',
        'CheapOair.com' => 'CheapOair',
        'Sixt.com' => 'Sixt',
    ];

    // ignored FlexCoupons programs
    public $ignoreHash = [
        'CarRentals.com' => 1,
        'CheapOair.com' => 1,
    ];
    protected static $defaultName = 'aw:import:flexoffers';

    private EntityManagerInterface $entityManager;
    private Mailer $mailer;

    public function __construct(
        EntityManagerInterface $entityManager,
        Mailer $mailer
    ) {
        parent::__construct();
        $this->entityManager = $entityManager;
        $this->mailer = $mailer;
    }

    public function configure()
    {
        $this->setDescription("Import coupons from FlexOffers.com");
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $r = $this->getCoupons('C85DC7B0-C8A9-4603-8E4E-47FC8D19AAC3');

        $db = $this->entityManager->getConnection();

        $found = [];
        $notfound = [];
        $cache = [];
        $ids = [];

        $inserted = 0;
        $updated = 0;
        $deleted = 0;
        $ignored = 0;

        $array = $r->Coupons_GetListResult->Data->Coupon;

        foreach ($array as $r) {
            if (isset($cache[$r->ProgramName])) {
                $p = $cache[$r->ProgramName];

                if (!$p) {
                    continue;
                }
            } else {
                $p = $this->getProvider($r);

                $cache[$r->ProgramName] = $p;

                if ($p) {
                    $found[] = $r->ProgramName . ' <=> ' . $p->DisplayName;
                } else {
                    $cache[$r->ProgramName] = '';
                }
            }

            if (!$p) {
                if (isset($this->ignoreHash[$r->ProgramName])) {
                    $ignored++;
                } else {
                    $notfound[] = $r->ProgramName;
                }

                continue;
            }

            $sourceId = intval($r->CouponID);
            $source = 'flexoffers';

            $providerId = $p->ProviderID;
            $autologinProviderId = $p->ProviderID;
            $link = $r->LinkURL;
            $title = $r->Name;
            $description = $r->PayoutDisplay;
            $activeDate = $r->ActiveDate;
            $expireDate = $r->ExpireDate;

            if (strlen($title) >= 200) { // database field limit
                $title = substr($description, 0, 200);
            }

            // Set Deals record
            $res = $db->executeQuery("SELECT * FROM Deal WHERE `Source`=? AND `SourceID`=?", [$source, $sourceId]);

            if ($res->rowCount() > 0) {
                $in = $res->fetch(\PDO::FETCH_OBJ);
                $dealId = $in->DealID;

                $res = $db->executeQuery("
                    UPDATE `Deal` SET
                        `ProviderID` = ?,
                        `Title`=?,
                        `AutologinProviderID` = ?,
                        `DealsLink` = ?,
                        `AffiliateLink` = '',
                        `Description` = ?,
                        `BeginDate` = ?,
                        `EndDate` = ?
                    WHERE
                        `Source` = ? AND `SourceID` = ?
                    ", [$providerId, $title, $autologinProviderId, $link, $description, $activeDate, $expireDate, $source, $sourceId]);

                $updated++;
            } else {
                $res = $db->executeQuery("
                    INSERT INTO
                        `Deal`(`ProviderID`,`Title`,`AutologinProviderID`,`DealsLink`,`Source`,`SourceID`,`Description`,`BeginDate`,`EndDate`)
                    VALUES(?,?,?,?,?,?,?,?,?)
                    ", [$providerId, $title, $autologinProviderId, $link, $source, $sourceId, $description, $activeDate, $expireDate]);

                $dealId = $db->lastInsertId();
                $inserted++;
            }

            $ids[] = $dealId;

            // Set Region
            $regionId = 8;

            $res = $db->executeQuery("SELECT * FROM DealRegion WHERE `DealID`=? AND `RegionID`=?", [$dealId, $regionId]);

            if ($res->rowCount() == 0) {
                $res = $db->executeQuery("
                    INSERT INTO
                        `DealRegion`(`DealID`,`RegionID`)
                    VALUES(?,?)
                    ", [$dealId, $regionId]);
            }
        }

        // delete old flexoffer rows
        if (count($ids)) {
            $res = $db->executeQuery("DELETE FROM `Deal` WHERE `Source`=? AND `DealID` NOT IN(" . implode(',', $ids) . ")", [$source]);
            $deleted = $res->rowCount();
        }

        // prepare email report
        $report = '';

        $report .= "[Inserted/updated/ignored/deleted]\n+$inserted/*$updated/~$ignored/-$deleted";

        $report .= "\n\n";

        if (count($notfound)) {
            $report .= "[Not found]\n";
            $report .= implode("\n", $notfound);
            $report .= "\n\n";
        }

        if (count($found)) {
            $report .= "[Found]\n";
            $report .= implode("\n", $found);
        }

        $report .= "\n";
        $output->write($report);

        if (count($notfound) || $inserted + $updated == 0) {
            // send report
            $message = new \Swift_Message();

            // prepare the message
            $message->setFrom('noreply@awardwallet.com');
            $message->setTo('error@awardwallet.com');
            $message->setSubject("Deals Import Report")
                ->setBody($report, 'text/plain');

            $this->mailer->send($message);
        }

        return 0;
    }

    protected function getCoupons($key, $params = [])
    {
        // Create from WSDL
        $client = new \SoapClient('https://services.flexoffers.com/webservices/coupons.asmx?WSDL');
        // $client = new SoapClient('./coupons.wsdl');

        // Prepare credentials header
        $header = new \SoapHeader(
            'http://services.flexoffers.com/WebServices/Coupons.asmx',
            'AuthHeader',
            [
                'APIKey' => $key,
            ]
        );

        $client->__setSoapHeaders($header);

        // Call our function
        return $client->Coupons_GetList($params);
    }

    protected function getProvider($record)
    {
        $db = $this->entityManager->getConnection();

        $name = $programName = $record->ProgramName;

        // prepare transformed program name
        $name = preg_replace("#[.,\-\s]#ix", '', preg_replace("#\.\w{2,3}$#", '', $name));
        $name = preg_replace("#Inc|Org|LLC$#", '', $name);

        // prepare site name
        $siteLike = preg_replace("#^http://#i", '', strtolower($record->ProgramName));
        $siteLike = preg_replace("#^www\.#i", '', strtolower($siteLike));

        // prepare method
        if ($this->providerDetectMethod === self::IMPORT_DEALS_MAPPED) {
            if (isset($this->mapHash[$programName])) {
                $programName = $this->mapHash[$programName];
            }
        }

        $res = $db->executeQuery("SELECT * FROM Provider");

        if ($res->rowCount() > 0) {
            while ($r = $res->fetch(\PDO::FETCH_OBJ)) {
                // equal
                if ($programName === $r->Name || $programName === $r->DisplayName) {
                    return $r;
                }

                // next checks only for guess mode
                if ($this->providerDetectMethod !== self::IMPORT_DEALS_GUESS) {
                    continue;
                }

                if (preg_match("#{$programName}#i", $r->Name)) {
                    return $r;
                }

                if (preg_match("#{$programName}#i", $r->DisplayName)) {
                    return $r;
                }

                // by provider name
                if (preg_match("#{$name}#i", preg_replace("#[.,\-\s]#ix", '', $r->Name))) {
                    return $r;
                }

                // by provider display name
                if (preg_match("#{$name}#i", preg_replace("#[.,\-\s]#ix", '', $r->DisplayName))) {
                    return $r;
                }

                // by provider site address
                $site = preg_replace("#^http://#i", '', strtolower($r->Site));
                $site = preg_replace("#^www\.#i", '', strtolower($site));

                if (preg_match("#{$siteLike}#", $site)) {
                    return $r;
                }
            }
        }

        return null;
    }
}
