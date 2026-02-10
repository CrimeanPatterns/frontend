<?php

namespace AwardWallet\MainBundle\Service\MileValue\DataSource;

use AwardWallet\MainBundle\Email\EmailOptions;
use Psr\Log\LoggerInterface;

class QantasDataSource implements DataSourceInterface
{
    private LoggerInterface $logger;

    private \HttpDriverInterface $httpDriver;

    public function __construct(LoggerInterface $logger, \HttpDriverInterface $httpDriver)
    {
        $this->logger = $logger;
        $this->httpDriver = $httpDriver;
    }

    public function getSourceId(): string
    {
        return 'qantas';
    }

    /**
     * @param array $row - row from MileValue and Trip
     * @return array - state
     */
    public function check(array $row, array $state): array
    {
        if ($row['ProviderID'] != 33 || $row['TotalTaxesSpent'] > 0 || !empty($state) || empty($row['TravelerNames']) || empty($row['RecordLocator'])) {
            return $state;
        }

        $this->logger->info("trying to send taxes for qantas, trip {$row["TripID"]}");
        $http = new \HttpBrowser("dir", $this->httpDriver, "/tmp/qantas");
        $http->setUserAgent("Mozilla/5.0 (Macintosh; Intel Mac OS X 10.15; rv:74.0) Gecko/20100101 Firefox/74.0");
        $http->GetURL("https://www.qantas.com/au/en/manage-booking.html");

        $http->PostURL("https://book.qantas.com/pl/QFServicing/wds/tripflow.redirect?adobe_mc=MCMID%3D34029246390681927684618307375194580252%7CMCORGID%3D11B20CF953F3626B0A490D44%2540AdobeOrg%7CTS%3D1584610309", [
            "REC_LOC" => $row["RecordLocator"],
            "DIRECT_RETRIEVE_LASTNAME" => $this->extractLastName($row['TravelerNames']),
            "USER_LANG" => "EN",
            "USER_LOCALE" => "EN_AU",
            "PAGE_FROM" => "/bookingError/v1/redirect/au/en/manage-booking.html",
        ]);

        if (!$http->FindPreg("#DIRECT_RETRIEVE_LASTNAME#")) {
            $this->logger->warning("failed to pass first redirect, trip {$row["TripID"]}");

            return $state;
        }

        $http->PostForm();

        if (!$http->FindPreg("#Resend Tax invoice#")) {
            $this->logger->warning("failed to login, trip {$row["TripID"]}");

            return $state;
        }

        $enc = $http->FindPreg('#DuplicateTaxInvoiceServlet"\,"request":\{"EMAIL":\[""\]\,"ENC":\["([^"]+)"#ims');

        if ($enc === null) {
            $this->logger->warning("failed to extract enc, trip {$row["TripID"]}");

            return $state;
        }

        $tabId = $http->FindPreg('#\?TAB_ID=([^&]+)&#ims');

        if ($tabId === null) {
            $this->logger->warning("failed to extract tabId, trip {$row["TripID"]}");

            return $state;
        }

        $http->setDefaultHeader('Accept', 'application/json, text/javascript, */*; q=0.01');
        $http->setDefaultHeader('X-Requested-With', 'XMLHttpRequest');
        $http->PostURL("https://book.qantas.com/pl/QFServicing/wds/DuplicateTaxInvoiceServlet?TAB_ID=" . urlencode($tabId), [
            "EMAIL" => $this->buildEmailAddress($row),
            "ENC" => $enc,
            "ENCT" => "1",
            "LANGUAGE" => "GB",
            "REQUEST_TYPES" => "FLIGHT",
            "SITE" => "QFQFQFSD",
        ]);

        if (!$http->FindPreg('#hasErrors":false#ims')) {
            $this->logger->warning("failed to send tax email, trip {$row["TripID"]}");

            return $state;
        }

        $this->logger->info("successfully sent taxes for {$row["TripID"]}");
        $state["sent"] = date("Y-m-d H:i:s");

        return $state;
    }

    private function extractLastName(string $travelerNames): string
    {
        $names = explode(",", $travelerNames);
        $names = array_map("trim", $names);
        $parts = explode(" ", $names[0]);

        return $parts[count($parts) - 1];
    }

    private function buildEmailAddress(array $row): string
    {
        $email = $row['UserLogin'];

        if (!empty($row['UserAgentAlias'])) {
            $email .= "." . $row['UserAgentAlias'];
        }
        $email .= EmailOptions::SILENT_SUFFIX . EmailOptions::UPDATE_ONLY_SUFFIX;

        return urlencode($email . "@awardwallet.com");
    }
}
