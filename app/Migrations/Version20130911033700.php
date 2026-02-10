<?php

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
use Symfony\Component\DependencyInjection;

/**
 * Auto-generated Migration: Please modify to your need!
 */
class Version20130911033700 extends AbstractMigration implements DependencyInjection\ContainerAwareInterface
{
    private $container;

    public function setContainer(DependencyInjection\ContainerInterface $container = null)
    {
        $this->container = $container;
    }

    public function up(Schema $schema): void
    {
        /** @var $doctrine \Doctrine\Bundle\DoctrineBundle\Registry */
        $doctrine = $this->container->get('doctrine');
        $em = $doctrine->getManager();
        $conn = $doctrine->getConnection();

        // Transfer booker info
        $stmt = $conn->executeQuery("SELECT * FROM UserBooker");

        while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
            $conn->insert('AbBookerInfo', [
                'AbBookerInfoID' => $row['UserBookerID'],
                'UserID' => $row['UserID'],
                'Price' => $row['Price'],
                'PricingDetails' => $row['PricingDetails'],
                'ServiceName' => $row['ServiceName'],
                'ServiceShortName' => (!isset($row['ServiceShortName'])) ? '' : $row['ServiceShortName'],
                'Address' => (!isset($row['Address'])) ? '' : $row['Address'],
                'ServiceURL' => (!isset($row['ServiceURL'])) ? '' : $row['ServiceURL'],
                'OutboundPercent' => $row['OutboundPercent'],
                'InboundPercent' => $row['InboundPercent'],
                'SmtpServer' => $row['SmtpServer'],
                'SmtpPort' => $row['SmtpPort'],
                'SmtpUseSsl' => $row['SmtpUseSsl'],
                'SmtpUsername' => $row['SmtpUsername'],
                'SmtpPassword' => $row['SmtpPassword'],
                'SmtpError' => $row['SmtpError'],
                'SmtpErrorDate' => $row['SmtpErrorDate'],
                'Greeting' => (!isset($row['Greeting'])) ? '' : $row['Greeting'],
                'AutoReplyMessage' => (!isset($row['AutoReplyMessage'])) ? '' : $row['AutoReplyMessage'],
                'SiteAdID' => (!isset($row['SiteAdID'])) ? null : $row['SiteAdID'],
            ]);
        }
        // BookingTransaction
        $stmt2 = $conn->executeQuery("
			SELECT
				*
			FROM
				BookingTransaction
		");

        while ($row = $stmt2->fetch(\PDO::FETCH_ASSOC)) {
            $conn->insert('AbTransaction', [
                'AbTransactionID' => $row['BookingTransactionID'],
                'ProcessDate' => $row['Date'],
                'Processed' => $row['Processed'],
            ]);
        }

        $row = $conn->executeQuery("SELECT MAX(BookingMessageID) AS Max FROM BookingMessage")->fetch(\PDO::FETCH_ASSOC);
        $messageMaxId = $row['Max'];
        $messageMaxId++;

        // Transfer booking requests
        $stmt = $conn->executeQuery("SELECT br.*, u.FirstName, u.LastName FROM BookingRequest br JOIN Usr u ON u.UserID = br.UserID");

        while ($requestRow = $stmt->fetch(\PDO::FETCH_ASSOC)) {
            //$em->transactional(function($em) use ($requestRow, $conn, $messageMaxId) {
            $value = function ($v, $default = null) {
                return (trim($v) === "") ? $default : $v;
            };

            if ($requestRow['PriorProgram'] != "" && $requestRow['PriorStopsOut'] != "" && $requestRow['PriorMilesToRedeem'] != "") {
                $format = "Loyalty program: %s\nClass of service: %s\nOutbound # of stops/layover times: %d\nInbound # of stops/layover times: %d\n# miles to be redeemed per person: %d";
                $prior = sprintf($format, $requestRow['PriorProgram'], "", $requestRow['PriorStopsOut'], $requestRow['PriorStopsIn'], $requestRow['PriorMilesToRedeem']);
            } else {
                $prior = '';
            }

            $conn->insert('AbRequest', [
                'AbRequestID' => $value($requestRow['BookingRequestID']),
                'ContactName' => (empty($requestRow['ContactName'])) ? $requestRow['FirstName'] . ' ' . $requestRow['LastName'] : $value($requestRow['ContactName']),
                'ContactEmail' => $value($requestRow['ContactEmail']),
                'ContactPhone' => $value($requestRow['ContactPhone']),
                'Passengers' => $value($requestRow['Passengers']),
                'Status' => $value($requestRow['Stat']),
                'Notes' => $value($requestRow['Notes']),
                'CreateDate' => $value($requestRow['CreateDate']),
                'RemindDate' => $value($requestRow['Until']),
                'CabinFirst' => $value($requestRow['CabinFirst']),
                'CabinBusiness' => $value($requestRow['CabinBusiness']),
                'PriorSearchResults' => $value($prior),
                'FinalServiceFee' => $value($requestRow['FinalServiceFee']),
                'FinalTaxes' => $value($requestRow['FinalTaxes']),
                'BookerUserID' => $value($this->checkUser($requestRow['Booker'], $requestRow, 'Request')),
                'AssignedUserID' => $value((!isset($requestRow['Assigned'])) ? null : $requestRow['Assigned']),
                'UserID' => $value($requestRow['UserID']),
                'BookingTransactionID' => $value($requestRow['BookingTransactionID']),
                'FeesPaidToUserID' => $value($requestRow['FeesPaidTo']),
            ]);
            $cancelReason = (!empty($requestRow['ReasonUpdate'])) ? $requestRow['ReasonUpdate'] : null;

            if (isset($requestRow['Ref']) && $requestRow['Ref'] != "") {
                $conn->insert('AbMessage', [
                    'AbMessageID' => $messageMaxId,
                    'CreateDate' => date("Y-m-d H:i:s"),
                    'Post' => null,
                    'Internal' => 0,
                    'Type' => -1,
                    'Metadata' => serialize(['Ref' => $requestRow['Ref']]),
                    'RequestID' => $requestRow['BookingRequestID'],
                    'UserID' => $this->checkUser($requestRow['Booker'], $requestRow, 'Ref'),
                ]);
                $messageMaxId++;
            }

            $nationality = (empty($requestRow['Nationality'])) ? null : $requestRow['Nationality'];

            // BookingHistory
            $stmt2 = $conn->executeQuery("SELECT * FROM BookingHistory WHERE BookingRequestID = ? ORDER BY BookingHistoryID ASC", [$requestRow['BookingRequestID']]);

            while ($row = $stmt2->fetch(\PDO::FETCH_ASSOC)) {
                $user = (isset($row['Agent'])) ? $row['Agent'] : $this->checkUser($row['UserID'], $requestRow, 'History');

                switch ($row['ActivityType']) {
                        case "1":
                            $row['Data'] = null;

                            break;

                        case "2":
                            if (!isset($row['Data'])) {
                                continue 2;
                            }
                            $data = ['status' => $row['Data']];

                            if ($row['Data'] == 3 && isset($cancelReason)) {
                                $data['reason'] = $cancelReason;
                            }
                            $row['Data'] = serialize($data);

                            break;

                        case "3":
                            break;

                        case "4":
                            $paidData = @unserialize($row['Data']);

                            if ($paidData === false) {
                                continue 2;
                            }
                            $row['Data'] = serialize(['booking_invoice' => $paidData['invoiceID']]);

                            break;

                        case "5":
                            $checkData = @unserialize($row['Data']);

                            if ($checkData === false) {
                                continue 2;
                            }

                            break;

                        case "6":
                            break;

                        default:
                            $row['ActivityType'] = 1;

                            break;
                    }
                $user = (empty($user)) ? $requestRow['UserID'] : $user;

                $conn->insert('AbMessage', [
                    'AbMessageID' => $messageMaxId,
                    'CreateDate' => $row['RequestUpdateDate'],
                    'Post' => null,
                    'Internal' => 0,
                    'Type' => $row['ActivityType'],
                    'Metadata' => $value($row['Data']),
                    'RequestID' => $row['BookingRequestID'],
                    'UserID' => $user,
                ]);
                $messageMaxId++;
            }

            // BookingMessage
            $stmt2 = $conn->executeQuery("SELECT * FROM BookingMessage WHERE BookingRequestID = ? ORDER BY BookingMessageID ASC", [$requestRow['BookingRequestID']]);

            while ($row = $stmt2->fetch(\PDO::FETCH_ASSOC)) {
                $user = (isset($row['Agent'])) ? $row['Agent'] : $this->checkUser($row['UserID'], $requestRow, 'Message');
                $post = $row['Post'];
                $type = 0;

                if ('{NEW_AUTO_REPLY}' == $row['Post']) {
                    $post = null;
                    $type = 7;
                }
                $conn->insert('AbMessage', [
                    'AbMessageID' => $row['BookingMessageID'],
                    'CreateDate' => $row['Date'],
                    'Post' => $value($post),
                    'Internal' => $value($row['Internal'], 0),
                    'Type' => $type,
                    'Metadata' => null,
                    'RequestID' => $row['BookingRequestID'],
                    'UserID' => $user,
                ]);
            }
            // BookingInvoice
            $stmt2 = $conn->executeQuery("
					SELECT
						bi.*
					FROM
						BookingInvoice bi
						JOIN BookingMessage bm ON bm.BookingMessageID = bi.BookingMessageID
					WHERE bm.BookingRequestID = ?
					ORDER BY bm.BookingMessageID ASC",
                    [$requestRow['BookingRequestID']]
                );

            while ($row = $stmt2->fetch(\PDO::FETCH_ASSOC)) {
                $conn->insert('AbInvoice', [
                    'AbInvoiceID' => $row['BookingInvoiceID'],
                    'Tickets' => $row['Tickets'],
                    'Price' => $row['Price'],
                    'Discount' => $value($row['Discount']),
                    'Taxes' => $value($row['TaxesPerPersonMin']),
                    'Status' => $value($row['Status']),
                    'PaymentType' => $value($row['PaymentType']),
                    'MessageID' => $row['BookingMessageID'],
                ]);
            }
            // BookingInvoiceMiles
            $stmt2 = $conn->executeQuery("
						SELECT
							bim.*
						FROM
							BookingInvoiceMiles bim
							JOIN BookingInvoice bi ON bi.BookingInvoiceID = bim.BookingInvoiceID
							JOIN BookingMessage bm ON bm.BookingMessageID = bi.BookingMessageID
						WHERE bm.BookingRequestID = ?
						ORDER BY bm.BookingMessageID ASC",
                    [$requestRow['BookingRequestID']]
                );

            while ($row = $stmt2->fetch(\PDO::FETCH_ASSOC)) {
                $conn->insert('AbInvoiceMiles', [
                    'AbInvoiceMilesID' => $row['BookingInvoiceMilesID'],
                    'CustomName' => $row['CustomName'],
                    'Balance' => $row['Balance'],
                    'InvoiceID' => $value($row['BookingInvoiceID']),
                ]);
            }
            // BookingRequestAccount
            $stmt2 = $conn->executeQuery("
					SELECT
						bra.*
					FROM
						BookingRequestAccount bra
						JOIN Account a ON a.AccountID = bra.AccountID
					WHERE bra.BookingRequestID = ?",
                    [$requestRow['BookingRequestID']]
                );

            while ($row = $stmt2->fetch(\PDO::FETCH_ASSOC)) {
                $conn->insert('AbAccountProgram', [
                    'AbAccountProgramID' => $row['BookingRequestAccountID'],
                    'RequestID' => $row['BookingRequestID'],
                    'AccountID' => $row['AccountID'],
                    'SubAccountID' => $value($row['SubAccountID']),
                ]);
            }
            // BookingRequestCustomProgram
            $stmt2 = $conn->executeQuery("
					SELECT
						*
					FROM
						BookingRequestCustomProgram
					WHERE BookingRequestID = ?",
                    [$requestRow['BookingRequestID']]
                );

            while ($row = $stmt2->fetch(\PDO::FETCH_ASSOC)) {
                if (empty($row['CustomName'])) {
                    continue;
                }
                $conn->insert('AbCustomProgram', [
                    'AbCustomProgramID' => $row['BookingRequestCustomProgramID'],
                    'Name' => $value($row['CustomName']),
                    'Balance' => $value($row['Balance']),
                    'RequestID' => $value($row['BookingRequestID']),
                ]);
            }
            // BookingRequestMark
            $mark = [];
            $stmt2 = $conn->executeQuery("
					SELECT
						bm.Date - INTERVAL 1 SECOND AS `Date`, bmm.UserID
					FROM
						BookingMessage bm
						JOIN BookingMessageMark bmm ON bmm.BookingMessageID = bm.BookingMessageID AND IsRead = 0
					WHERE
						bm.BookingRequestID = ?
					GROUP BY bmm.UserID
					ORDER BY bm.Date ASC
					",
                    [$requestRow['BookingRequestID']]
                );

            while ($row = $stmt2->fetch(\PDO::FETCH_ASSOC)) {
                $mark[$row['UserID']] = $row['Date'];
            }
            $stmt2 = $conn->executeQuery("
					SELECT
						brm.UserID, m.Date
					FROM
						BookingRequestMark brm
						JOIN (
							SELECT
								`Date` - INTERVAL 1 SECOND AS Date
							FROM
								BookingMessage
							WHERE
								BookingRequestID = ?
							ORDER BY `Date` DESC
							LIMIT 1
						) AS m
					WHERE
						brm.IsRead = 0
						AND brm.BookingRequestID = ?
					",
                    [$requestRow['BookingRequestID'], $requestRow['BookingRequestID']]
                );

            while ($row = $stmt2->fetch(\PDO::FETCH_ASSOC)) {
                if (!isset($mark[$row['UserID']])) {
                    $mark[$row['UserID']] = $row['Date'];
                }
            }

            foreach ($mark as $userID => $date) {
                $dtNow = new \DateTime("@" . strtotime($date));
                $conn->insert('AbRequestRead', [
                    'ReadDate' => $dtNow->format('Y-m-d H:i:s'),
                    'UserID' => $this->checkUser($userID, $requestRow, 'RequestMark'),
                    'RequestID' => $requestRow['BookingRequestID'],
                ]);
            }
            // BookingRequestPassenger
            $stmt2 = $conn->executeQuery("
					SELECT
						*
					FROM
						BookingRequestPassenger
					WHERE BookingRequestID = ?",
                    [$requestRow['BookingRequestID']]
                );

            while ($row = $stmt2->fetch(\PDO::FETCH_ASSOC)) {
                $conn->insert('AbPassenger', [
                    'AbPassengerID' => $row['BookingRequestPassengerID'],
                    'FirstName' => $value($row['FirstName']),
                    'MiddleName' => $value($row['MiddleName']),
                    'LastName' => $value($row['LastName']),
                    'Birthday' => $value($row['Birthday']),
                    'Nationality' => $value($nationality),
                    'RequestID' => $value($row['BookingRequestID']),
                ]);
            }
            // TODO: BookingRequestProvider / extension
            // BookingRequestSegment
            $stmt2 = $conn->executeQuery("
					SELECT
						*
					FROM
						BookingRequestSegment
					WHERE BookingRequestID = ?",
                    [$requestRow['BookingRequestID']]
                );

            while ($row = $stmt2->fetch(\PDO::FETCH_ASSOC)) {
                $conn->insert('AbSegment', [
                    'AbSegmentID' => $row['BookingRequestSegmentID'],
                    'DepDateFrom' => $value($row['DepDateFrom']),
                    'DepDateTo' => $value($row['DepDateTo']),
                    'DepDateIdeal' => $value($row['DepDateIdeal']),
                    'ArrDateFrom' => $value($row['ReturnDateFrom']),
                    'ArrDateTo' => $value($row['ReturnDateTo']),
                    'ArrDateIdeal' => $value($row['ReturnDateIdeal']),
                    'Priority' => $value($row['Priority']),
                    'RoundTrip' => $value($row['RoundTrip']),
                    'RequestID' => $value($row['BookingRequestID']),
                    'Dep' => $row['DepCode'],
                    'Arr' => $row['ArrCode'],
                ]);
            }
            // TODO: BookingSharingRequest??????????
            //});
        }
    }

    public function down(Schema $schema): void
    {
        $this->addSql("
			TRUNCATE TABLE AbBookerInfo;
			TRUNCATE TABLE AbAccountProgram;
			TRUNCATE TABLE AbCustomProgram;
			TRUNCATE TABLE AbInvoiceMiles;
			TRUNCATE TABLE AbInvoice;
			TRUNCATE TABLE AbMessage;
			TRUNCATE TABLE AbPassenger;
			TRUNCATE TABLE AbRequestRead;
			TRUNCATE TABLE AbSegment;
			TRUNCATE TABLE AbRequest;
			TRUNCATE TABLE AbTransaction;
        ");
    }

    protected function checkUser($userID, $requestFields, $desc)
    {
        if (empty($userID)) {
            return $userID;
        }
        $user = $requestFields['UserID'];
        $booker = 116000;

        if (!in_array($userID, [$user, $booker])) {
            $userID = $booker;
        }

        return $userID;
    }
}
