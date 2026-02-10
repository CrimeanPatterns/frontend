<?php

namespace AwardWallet\Manager\Schema\Itinerary;

use AwardWallet\MainBundle\Entity\Trip;
use AwardWallet\MainBundle\Globals\StringHandler;
use Symfony\Component\Routing\RouterInterface;

use function AwardWallet\MainBundle\Globals\Utils\iterFluent\it;

class ItinerariesList extends \TBaseList
{
    private RouterInterface $router;

    public function __construct($table, $fields)
    {
        foreach ($fields as $code => $field) {
            if (!isset($field['FilterField'])) {
                $fields[$code]['FilterField'] = 't.' . $code;
            }
        }

        $this->router = getSymfonyContainer()->get('router');

        parent::__construct($table, $fields);
    }

    public function FormatFields($output = 'html')
    {
        parent::FormatFields($output);

        foreach ($this->Query->Fields as $name => $field) {
            if (is_string($field)) {
                $this->Query->Fields[$name] = $this->timeHtml($field);
            }
        }

        switch ($this->Table) {
            case 'Reservation':
                $id = $this->OriginalFields['ReservationID'];
                $segmentId = sprintf('CI.%d', $this->OriginalFields['ReservationID']);
                $itId = sprintf('R.%d', $this->OriginalFields['ReservationID']);
                $this->Query->Fields['ConfirmationNumber'] = $this->confNumberHtml($this->Query->Fields['ConfirmationNumber']);
                $this->Query->Fields['CheckInDate'] = $this->formatTimeOffset($this->Query->Fields['CheckInDate'], $this->Query->Fields['TimeZone']);
                $this->Query->Fields['CheckOutDate'] = $this->formatTimeOffset($this->Query->Fields['CheckOutDate'], $this->Query->Fields['TimeZone']);

                break;

            case 'Rental':
                $id = $this->OriginalFields['RentalID'];
                $segmentId = sprintf('PU.%d', $this->OriginalFields['RentalID']);
                $itId = sprintf('L.%d', $this->OriginalFields['RentalID']);
                $this->Query->Fields['Number'] = $this->confNumberHtml($this->Query->Fields['Number']);
                $this->Query->Fields['PickupDatetime'] = $this->formatTimeOffset($this->Query->Fields['PickupDatetime'], $this->Query->Fields['PickupTimeZone']);
                $this->Query->Fields['DropoffDatetime'] = $this->formatTimeOffset($this->Query->Fields['DropoffDatetime'], $this->Query->Fields['DropoffTimeZone']);

                break;

            case 'Restaurant':
                $id = $this->OriginalFields['RestaurantID'];
                $segmentId = sprintf('E.%d', $this->OriginalFields['RestaurantID']);
                $itId = sprintf('E.%d', $this->OriginalFields['RestaurantID']);
                $this->Query->Fields['ConfNo'] = $this->confNumberHtml($this->Query->Fields['ConfNo']);
                $this->Query->Fields['StartDate'] = $this->formatTimeOffset($this->Query->Fields['StartDate'], $this->Query->Fields['TimeZone']);
                $this->Query->Fields['EndDate'] = $this->formatTimeOffset($this->Query->Fields['EndDate'], $this->Query->Fields['TimeZone']);

                break;

            case 'Parking':
                $id = $this->OriginalFields['ParkingID'];
                $segmentId = sprintf('PS.%d', $this->OriginalFields['ParkingID']);
                $itId = sprintf('P.%d', $this->OriginalFields['ParkingID']);
                $this->Query->Fields['Number'] = $this->confNumberHtml($this->Query->Fields['Number']);
                $this->Query->Fields['StartDatetime'] = $this->formatTimeOffset($this->Query->Fields['StartDatetime'], $this->Query->Fields['TimeZone']);
                $this->Query->Fields['EndDatetime'] = $this->formatTimeOffset($this->Query->Fields['EndDatetime'], $this->Query->Fields['TimeZone']);

                break;

            case 'TripSegment':
                $id = $this->OriginalFields['TripID'];
                $segmentId = sprintf('T.%d', $this->OriginalFields['TripSegmentID']);
                $itId = sprintf('T.%d', $this->OriginalFields['TripSegmentID']);
                $this->Query->Fields['RecordLocator'] = $this->confNumbersHtml(
                    $this->Query->Fields['RecordLocator'],
                    $this->Query->Fields['IssuingAirlineConfirmationNumber'],
                    $this->Query->Fields['MarketingAirlineConfirmationNumber'],
                    $this->Query->Fields['OperatingAirlineConfirmationNumber'],
                );
                $this->Query->Fields['FlightNumber'] = $this->flightNumberHtml($this->Query->Fields['FlightNumber'], $this->Query->Fields['OperatingAirlineFlightNumber']);
                $this->Query->Fields['DepDate'] = $this->formatTimeOffset($this->Query->Fields['DepDate'], $this->Query->Fields['DepTimeZone']);
                $this->Query->Fields['ArrDate'] = $this->formatTimeOffset($this->Query->Fields['ArrDate'], $this->Query->Fields['ArrTimeZone']);
                $this->Query->Fields['TripID'] = sprintf(
                    '%d<br><div style="color:white;text-transform:uppercase;text-align:center;background-color:rgba(0,128,0,0.25);font-size:0.7em;">%s</div>',
                    $this->Query->Fields['TripID'],
                    Trip::CATEGORY_NAMES[$this->Query->Fields['Category']],
                );
                $airlinesInfo = $this->tripAirlinesHtml();

                break;
        }

        $this->Query->Fields['ProviderID'] = sprintf(
            '%s%s',
            !empty($this->OriginalFields['ProviderID'])
                ? sprintf(
                    '<a target="_blank" href="%s">%s</a>',
                    sprintf('/manager/list.php?Schema=Provider&ProviderID=%d', $this->OriginalFields['ProviderID']),
                    $this->OriginalFields['ShortName']
                ) : null,
            $airlinesInfo ?? null
        );

        if (!empty($this->OriginalFields['Sources'])) {
            $sourcesLinks = [];
            $sources = json_decode($this->OriginalFields['Sources'], true);

            if (!empty($sources) && array_key_exists('data', $sources)) {
                foreach ($sources['data'] as $item) {
                    $sourcesLinks[] = $this->sourceLink($item);
                }
            }

            if (!empty($sourcesLinks)) {
                $this->Query->Fields['Sources'] = implode(' ', array_unique($sourcesLinks));
            }
        }

        $this->Query->Fields['Info'] = $this->statusHtml();

        // Actions
        $actions = [];

        if (!empty($this->OriginalFields['UserID'])) {
            $this->Query->Fields['UserID'] = sprintf(
                '<a target="_blank" href="%s">%s</a>%s',
                sprintf('/manager/list.php?UserID=%d&Schema=UserAdmin', $this->OriginalFields['UserID']),
                $this->OriginalFields['UserName'],
                $this->OriginalFields['FamilyMemberName'] ? sprintf('<div style="color:grey">fm: %s</div>', $this->OriginalFields['FamilyMemberName']) : ''
            );

            if (isset($segmentId)) {
                $actions[] = sprintf(
                    '<li><a target="_blank" href="%s">Show on timeline</a></li>',
                    sprintf('/manager/impersonate?UserID=%d&Goto=%s', $this->OriginalFields['UserID'], urlencode("/timeline/show/" . $segmentId))
                );
            }
        }

        if (isset($itId)) {
            $actions[] = sprintf(
                '<li><a href="javascript:void(0);" class="load-info" data-url="%s">Show details</a></li>',
                $this->router->generate('aw_manager_itineraryinfo_info', ['segmentId' => $itId], RouterInterface::ABSOLUTE_URL)
            );
        }

        if (isset($id)) {
            $logLink = function (int $days) use ($id, $itId, $segmentId) {
                return sprintf(
                    '<a target="_blank" href="https://kibana.awardwallet.com/app/discover#/?_g=(refreshInterval:(pause:!t,value:0),time:(from:now-%dd,to:now))&_a=(columns:!(message),interval:auto,query:(language:kuery,query:\'%s\'))">%dd</a>',
                    $days, urlencode(sprintf('%d OR "%s" OR "%s"', $id, $itId, $segmentId)), $days
                );
            };
            $actions[] = sprintf(
                '<li>logs: %s</li>',
                implode(' ', [
                    $logLink(1),
                    $logLink(3),
                    $logLink(7),
                    $logLink(30),
                    $logLink(90),
                ])
            );
        }

        $this->Query->Fields['Actions'] = sprintf('<ol style="font-size:0.9em;color:#686868;margin:0;padding-left:20px;">%s</ol>', implode(' ', $actions));
    }

    public function DrawFooter()
    {
        global $Interface;

        parent::DrawFooter();

        $Interface->FooterScripts[] = <<<JS
let popupWindow = null;

$('a.load-info').on('click', function () {
    $.getJSON($(this).data('url'), function (data) {
        if (!data) {
            alert('no info');
            
            return;
        }
        popupWindow = window.open("", "Info", "toolbar=no, location=no, directories=no, status=no, menubar=no, scrollbars=yes, resizable=yes, width=1000, height=800");
        popupWindow.document.body.innerHTML = "";
        popupWindow.document.write(data);
        popupWindow.focus();
    });
});
JS;
    }

    private function formatTimeOffset(?string $date, ?string $tz): string
    {
        if (empty($date)) {
            return '';
        }

        return sprintf('%s <span title="time offset" style="color:darkgrey;">(%s)</span>', $date ?? '', $this->getTimeOffset($tz));
    }

    private function getTimeOffset(?string $tz): string
    {
        $offset = (new \DateTimeZone($tz ?? 'UTC'))->getOffset(new \DateTime()) / 3600;

        if ($offset === 0) {
            $sign = '';
        } else {
            $sign = $offset > 0 ? '+' : '-';
        }

        return sprintf('%s%s', $sign, \abs($offset));
    }

    private function sourceLink(array $item): string
    {
        $style = 'font-size:0.8em;color:blue;';
        $type = $item['type'] ?? 'null';
        $dates = $item['dates'] ?? [];
        $datesTip = implode(', ', $dates);

        if (isset($item['accountId']) || 'account' === $type) {
            return sprintf('<a style="%s" href="/manager/list.php?Schema=AccountInfo&AccountID=%d" title="%s" target="account">account</a>', $style, $item['accountId'], $datesTip);
        } elseif ('email' === $type) {
            $date = $item['date'] ?? $item['dates'][0] ?? null;

            return sprintf(
                '<a style="%s" href="/manager/email/parser/list/all?%s" title="%s" target="email">email</a>',
                $style,
                http_build_query([
                    'requestId' => $item['requestId'] ?? null,
                    'sort' => '',
                    'direction' => '',
                    'preview' => '',
                    'region' => '',
                    'id' => '',
                    'subject' => '',
                    'from' => '',
                    'to' => $item['recipient'] ?? '',
                    'partner' => '',
                    'userData' => 'parser',
                    'date' => date('m/d/Y', strtotime($date)),
                    'show' => ['all'],
                    'subjectAdv' => '',
                    'fromAdv' => '',
                    'toAdv' => '',
                    'providerAdv' => '',
                    'partnerAdv' => '',
                    'userDataAdv' => '',
                ]),
                $datesTip
            );
        } elseif ('confirmationNumber' === $type) {
            return sprintf('<span style="%s" title="%s">confirmationNumber</span>', $style, $datesTip);
        } else {
            return sprintf('<span style="%s">! unknown type: %s</span>', $style, $type);
        }
    }

    private function confNumberHtml(?string $number): ?string
    {
        if (StringHandler::isEmpty($number)) {
            return null;
        }

        return sprintf('<span style="font-family:Monospace;line-height:24px;color:white;background-color:#8e8e8e;padding:3px;border-radius:4px">%s</span>', $number);
    }

    private function confNumbersHtml(
        ?string $number,
        ?string $issuingNumber = null,
        ?string $marketingNumber = null,
        ?string $operatingNumber = null
    ): ?string {
        if (
            StringHandler::isEmpty($number)
            && StringHandler::isEmpty($issuingNumber)
            && StringHandler::isEmpty($marketingNumber)
            && StringHandler::isEmpty($operatingNumber)
        ) {
            return null;
        }

        $items = [];
        $style = 'font-size:0.9em;line-height:24px;font-family:Monospace;color:#ffffff;background-color:#b8b8b8;padding:3px;border-radius:4px';

        if (!StringHandler::isEmpty($number)) {
            $items[] = $this->confNumberHtml($number);
        }

        if (!StringHandler::isEmpty($issuingNumber) && !in_array($issuingNumber, [$number, $marketingNumber, $operatingNumber])) {
            $items[] = sprintf('<span title="Issuing" style="%s">%s</span>', $style, $issuingNumber);
        }

        if (!StringHandler::isEmpty($marketingNumber) && !in_array($marketingNumber, [$number, $issuingNumber, $operatingNumber])) {
            $items[] = sprintf('<span title="Marketing" style="%s">%s</span>', $style, $marketingNumber);
        }

        if (!StringHandler::isEmpty($operatingNumber) && !in_array($operatingNumber, [$number, $issuingNumber, $marketingNumber])) {
            $items[] = sprintf('<span title="Operating" style="%s">%s</span>', $style, $operatingNumber);
        }

        return implode(' ', $items);
    }

    private function flightNumberHtml(?string $number, ?string $operatingNumber): ?string
    {
        if (StringHandler::isEmpty($number) && StringHandler::isEmpty($operatingNumber)) {
            return null;
        }

        if ($number === $operatingNumber) {
            $operatingNumber = null;
        }

        $items = [];

        if (!StringHandler::isEmpty($number)) {
            $items[] = sprintf('<span title="Marketing Number" style="font-family:Monospace;color:#0454b3;">%s</span>', $number);
        }

        if (!StringHandler::isEmpty($operatingNumber)) {
            $items[] = sprintf('<span title="Operating Number" style="font-size:0.9em;font-family:Monospace;color:#393939;">%s</span>', $operatingNumber);
        }

        return implode('<br>', $items);
    }

    private function tripAirlinesHtml(): ?string
    {
        $items = [];
        $style = 'font-size:0.7em;background-color:#eeeeee;color:#1d1d1d;padding:1px';

        foreach (['Marketing', 'Operating', 'Issuing'] as $type) {
            if (
                !empty($this->Query->Fields[$type . 'Airline'])
                || !empty($this->Query->Fields[$type . 'AirlineID'])
                || !empty($this->Query->Fields[$type . 'AirlineIATA'])
                || !empty($this->Query->Fields[$type . 'AirlineName'])
            ) {
                $info = [];
                $info[] = !empty($this->Query->Fields[$type . 'AirlineName']) ? $this->Query->Fields[$type . 'AirlineName'] : $this->Query->Fields[$type . 'Airline'];

                if (!empty($this->Query->Fields[$type . 'AirlineID'])) {
                    $info[] = '#' . $this->Query->Fields[$type . 'AirlineID'];
                }

                $info[] = $this->Query->Fields[$type . 'AirlineIATA'];
                $info = array_filter($info);
                $items[] = ['info' => implode(', ', $info), 'type' => $type];
            }
        }

        if ($items) {
            return it($items)
                ->groupAdjacentBy(fn (array $a, array $b) => $a['info'] <=> $b['info'])
                ->mapIndexed(function (array $group) use ($style) {
                    return sprintf('<div style="%s"><b>%s</b>: %s</div>', $style, implode(', ', array_column($group, 'type')), $group[0]['info']);
                })
                ->joinToString('');
        }

        return null;
    }

    private function statusHtml(): string
    {
        $info = [];
        $statusStyle = 'font-size:0.8em;';
        $notificationDateStyle = 'color:#999999;font-size:0.7em;';

        if (isset($this->OriginalFields['Hidden']) && $this->OriginalFields['Hidden'] == 1) {
            $info[] = sprintf('<div style="color:red;%s">DELETED</div>', $statusStyle);
        } else {
            $info[] = sprintf('<div style="color:green;%s">ACTIVE</div>', $statusStyle);
        }

        if (isset($this->OriginalFields['Undeleted']) && $this->OriginalFields['Undeleted'] == 1) {
            $info[] = sprintf('<div style="color:darkorange;%s">RESTORED</div>', $statusStyle);
        }

        if (isset($this->OriginalFields['Cancelled']) && $this->OriginalFields['Cancelled'] == 1) {
            $info[] = sprintf('<div style="color:grey;%s">CANCELLED</div>', $statusStyle);
        }

        if (isset($this->OriginalFields['Modified']) && $this->OriginalFields['Modified'] == 1) {
            $info[] = sprintf('<div style="color:orange;%s">MODIFIED</div>', $statusStyle);
        }

        switch ($this->Table) {
            case 'TripSegment':
                if (!is_null($this->OriginalFields['PreCheckinNotificationDate'])) {
                    $info[] = sprintf('<div style="%s">PreCheckinNotificationDate: %s</div>', $notificationDateStyle, $this->OriginalFields['PreCheckinNotificationDate']);
                }

                if (!is_null($this->OriginalFields['CheckinNotificationDate'])) {
                    $info[] = sprintf('<div style="%s">CheckinNotificationDate: %s</div>', $notificationDateStyle, $this->OriginalFields['CheckinNotificationDate']);
                }

                if (!is_null($this->OriginalFields['FlightDepartureNotificationDate'])) {
                    $info[] = sprintf('<div style="%s">FlightDepartureNotificationDate: %s</div>', $notificationDateStyle, $this->OriginalFields['FlightDepartureNotificationDate']);
                }

                if (!is_null($this->OriginalFields['FlightBoardingNotificationDate'])) {
                    $info[] = sprintf('<div style="%s">FlightBoardingNotificationDate: %s</div>', $notificationDateStyle, $this->OriginalFields['FlightBoardingNotificationDate']);
                }

                break;
        }

        return implode('', $info);
    }

    private function timeHtml(?string $date): ?string
    {
        if (StringHandler::isEmpty($date)) {
            return null;
        }

        return preg_replace('/\s(\d{2}:\d{2}:\d{2})$/ims', ' <span style="font-family:Monospace;line-height:24px;color:rgba(0,0,0,0.56);background-color:#f8f2f2;padding:3px;border-radius:4px">$1</span>', $date);
    }
}
