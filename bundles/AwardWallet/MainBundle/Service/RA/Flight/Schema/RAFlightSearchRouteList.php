<?php

namespace AwardWallet\MainBundle\Service\RA\Flight\Schema;

use AwardWallet\MainBundle\Entity\RAFlightSearchQuery;
use AwardWallet\MainBundle\Globals\Localizer\LocalizeService;
use AwardWallet\MainBundle\Service\RA\Flight\Api;
use Symfony\Component\Routing\RouterInterface;

use function AwardWallet\MainBundle\Globals\Utils\iterFluent\it;

class RAFlightSearchRouteList extends \TBaseList
{
    private LocalizeService $localizer;

    private RouterInterface $router;

    private array $parsers;

    public function __construct(LocalizeService $localizer, RouterInterface $router, Api $api, $table, $fields)
    {
        $this->localizer = $localizer;
        $this->router = $router;
        $this->parsers = $api->getParserList();

        $fields = [
            'RAFlightSearchRouteID' => [
                'Type' => 'string',
                'Caption' => 'ID',
                'filterWidth' => 25,
            ],
            'DepCode' => [
                'Type' => 'string',
                'Caption' => 'From',
                'filterWidth' => 45,
            ],
            'ArrCode' => [
                'Type' => 'string',
                'Caption' => 'To',
                'filterWidth' => 45,
            ],
            'DepDate' => [
                'Type' => 'date',
                'Caption' => 'Date',
                'filterWidth' => 30,
            ],
            'Segments' => [
                'Type' => 'html',
                'Database' => false,
                'Sort' => 'DATE(r.LastSeenDate) DESC, r.MileCost ASC, (IFNULL(r.FlightDurationSeconds, 0) + IFNULL(r.LayoverDurationSeconds, 0)) ASC, r.Stops ASC',
            ],
            'Parser' => [
                'Type' => 'string',
                'Options' => array_map(
                    fn (array $parser) => $parser['name'],
                    $this->parsers
                ),
            ],
            'ItineraryCOS' => [
                'Type' => 'string',
                'Caption' => 'ItineraryCOS',
                'filterWidth' => 100,
                'Options' => array_combine(
                    RAFlightSearchQuery::API_FLIGHT_CLASSES,
                    RAFlightSearchQuery::API_FLIGHT_CLASSES
                ),
            ],
            'TimesFound' => [
                'Type' => 'integer',
                'filterWidth' => 30,
            ],
            'LastSeenDate' => [
                'Type' => 'date',
                'filterWidth' => 30,
            ],
            'MileCost' => [
                'Type' => 'integer',
            ],
            'TaxesFees' => [
                'Type' => 'float',
                'Caption' => 'Taxes+Fees',
                'FilterField' => 'IFNULL(r.Taxes, 0) + IFNULL(r.Fees, 0)',
            ],
            'TotalDuration' => [
                'Type' => 'string',
                'Database' => false,
            ],
            'Stops' => [
                'Type' => 'integer',
                'filterWidth' => 30,
            ],
            'Tickets' => [
                'Type' => 'integer',
                'filterWidth' => 30,
            ],
            'AwardTypes' => [
                'Type' => 'string',
                'filterWidth' => 50,
            ],
            'TotalDistance' => [
                'Type' => 'float',
                'filterWidth' => 30,
            ],
            'Archived' => [
                'Type' => 'boolean',
                'filterWidth' => 30,
            ],
            'Flag' => [
                'Type' => 'boolean',
                'filterWidth' => 30,
            ],
            'RAFlightSearchQueryID' => [
                'Caption' => 'Query ID',
                'Type' => 'integer',
                'filterWidth' => 40,
            ],
        ];

        foreach ($fields as $code => $field) {
            if ($code === 'DepDate') {
                if (!isset($field['FilterField'])) {
                    $fields[$code]['FilterField'] = 't.' . $code;
                }

                continue;
            }

            if (!isset($field['FilterField'])) {
                $fields[$code]['FilterField'] = 'r.' . $code;
            }
        }

        parent::__construct($table, $fields);

        $this->SQL = "
            SELECT
                r.*,
                IFNULL(r.FlightDurationSeconds, 0) + IFNULL(r.LayoverDurationSeconds, 0) AS TotalDuration,
                IFNULL(r.Taxes, 0) + IFNULL(r.Fees, 0) AS TaxesFees,
                t.DepDate,   
                t.Segments
            FROM
                RAFlightSearchRoute r
                JOIN (
                    SELECT
                        RAFlightSearchRouteID,
                        MIN(DepDate) AS DepDate,
                        GROUP_CONCAT(
                            CONCAT(
                                '<td>',
                                DATE_FORMAT(DepDate, '%b %d'),
                                '</td><td>',
                                CONCAT(DepCode, ' - ', ArrCode),
                                '</td><td>',
                                IF(FlightDurationSeconds IS NOT NULL, CONCAT(IF(FLOOR(FlightDurationSeconds / 3600) > 0, CONCAT(FLOOR(FlightDurationSeconds / 3600), 'h'), ''), IF(FLOOR((FlightDurationSeconds % 3600) / 60) > 0, CONCAT(FLOOR((FlightDurationSeconds % 3600) / 60), 'm'), '')), ''),
                                '</td><td>',
                                AirlineCode,
                                '</td><td>',
                                Service,
                                '</td><td>',
                                DATE_FORMAT(DepDate, '%l:%i %p'),
                                '</td><td>',
                                IF(DATE_FORMAT(ArrDate, '%b %d') != DATE_FORMAT(DepDate, '%b %d'), CONCAT(DATE_FORMAT(ArrDate, '%l:%i %p'), ' (', DATE_FORMAT(ArrDate, '%b %d'), ')'), DATE_FORMAT(ArrDate, '%l:%i %p')),
                                '</td><td>',
                                ArrCode,
                                '</td><td>',
                                IF(LayoverDurationSeconds IS NOT NULL, CONCAT(IF(FLOOR(LayoverDurationSeconds / 3600) > 0, CONCAT(FLOOR(LayoverDurationSeconds / 3600), 'h'), ''), IF(FLOOR((LayoverDurationSeconds % 3600) / 60) > 0, CONCAT(FLOOR((LayoverDurationSeconds % 3600) / 60), 'm'), '')), ''),
                                '</td>'
                            ) SEPARATOR '{br}'
                        ) AS Segments
                    FROM
                        RAFlightSearchRouteSegment
                    GROUP BY
                        RAFlightSearchRouteID
                ) t ON t.RAFlightSearchRouteID = r.RAFlightSearchRouteID
                JOIN RAFlightSearchQuery q ON q.RAFlightSearchQueryID = r.RAFlightSearchQueryID
            WHERE 
                1 = 1
                [Filters]
                AND q.DeleteDate IS NULL
        ";
    }

    public function FormatFields($output = "html")
    {
        parent::FormatFields($output);

        $segments = htmlspecialchars_decode($this->Query->Fields['Segments']);
        $segments = explode('{br}', $segments);
        $countSegments = count($segments);

        $this->Query->Fields['MileCost'] = sprintf('%s mi', $this->localizer->formatNumber($this->Query->Fields['MileCost']));

        if (!empty($this->Query->Fields['Currency'])) {
            $this->Query->Fields['TaxesFees'] = is_numeric($this->Query->Fields['TaxesFees']) ? $this->localizer->formatCurrency($this->Query->Fields['TaxesFees'], $this->Query->Fields['Currency']) : '';
        } else {
            $this->Query->Fields['TaxesFees'] = is_numeric($this->Query->Fields['TaxesFees']) ? $this->localizer->formatNumber($this->Query->Fields['TaxesFees']) : '';
        }

        $this->Query->Fields['DepDate'] = $this->localizer->formatDate(date_create($this->Query->Fields['DepDate']), 'short');
        $this->Query->Fields['Parser'] = $this->parsers[$this->Query->Fields['Parser']]['name'] ?? $this->Query->Fields['Parser'];
        $this->Query->Fields['Segments'] = sprintf(
            '<table class="segments-details">
                        %s%s
                    </table>',
            '<tr><td></td><td>Departure Date</td><td>Segment</td><td>Dur</td><td>Carrier IATA</td><td>Seg COS</td><td>Depart</td><td>Arrive</td><td>Stops</td><td>Lay</td></tr>',
            it($segments)
                ->mapIndexed(function (string $segment, int $key) use ($countSegments, $segments) {
                    if ($key === $countSegments - 1) {
                        $segment = preg_replace('/<td>([^>]*?)<\/td><td>([^>]*?)<\/td>$/', '<td></td><td></td>', $segment);
                    } else {
                        if (preg_match('/<td>[^>]*? - ([^>]*?)<\/td>/', $segment, $matches)) {
                            $arrCode = $matches[1];

                            if (preg_match('/<td>([^>]*?) - [^>]*?<\/td>/', $segments[$key + 1], $matches)) {
                                $nextDepCode = $matches[1];

                                if ($arrCode !== $nextDepCode) {
                                    $segment = preg_replace('/<td>([^>]*?)<\/td><td>([^>]*?)<\/td>$/', sprintf('<td>%s/%s</td><td>$2</td>', $arrCode, $nextDepCode), $segment);
                                }
                            }
                        }
                    }

                    return sprintf('<tr><td>%d</td>%s</tr>', $key + 1, $segment);
                })
                ->joinToString('')
        );
        $this->Query->Fields['RAFlightSearchQueryID'] = sprintf(
            '<a href="list.php?Schema=RAFlightSearchQuery&RAFlightSearchQueryID=%d" target="_blank">%s</a>',
            $this->Query->Fields['RAFlightSearchQueryID'],
            $this->Query->Fields['RAFlightSearchQueryID']
        );

        $this->Query->Fields['TotalDuration'] = $this->formatDuration(floor($this->Query->Fields['TotalDuration'] / 60));
        $this->Query->Fields['TotalDistance'] = sprintf('%s mi', $this->localizer->formatNumber($this->Query->Fields['TotalDistance']));
        $this->Query->Fields['LastSeenDate'] = sprintf(
            '%s<br><span style="color: #818181;" title="Created date">%s</span>',
            $this->localizer->formatDate(date_create($this->Query->Fields['LastSeenDate']), 'short'),
            $this->localizer->formatDate(date_create($this->Query->Fields['CreateDate']), 'short')
        );
        $this->Query->Fields['RAFlightSearchRouteID'] = sprintf(
            '<span data-archive="%d" data-flag="%d">%s</span>',
            $this->OriginalFields['Archived'],
            $this->OriginalFields['Flag'],
            $this->OriginalFields['RAFlightSearchRouteID']
        );
    }

    public function DrawButtons($closeTable = true)
    {
        global $Interface;

        parent::DrawButtons($closeTable);

        $flagUrl = $this->router->generate('aw_enhanced_action', [
            'schema' => 'RAFlightSearchRoute',
            'action' => 'flag',
        ]);
        $unflagUrl = $this->router->generate('aw_enhanced_action', [
            'schema' => 'RAFlightSearchRoute',
            'action' => 'unflag',
        ]);
        $archiveUrl = $this->router->generate('aw_enhanced_action', [
            'schema' => 'RAFlightSearchRoute',
            'action' => 'archive',
        ]);
        $unarchiveUrl = $this->router->generate('aw_enhanced_action', [
            'schema' => 'RAFlightSearchRoute',
            'action' => 'unarchive',
        ]);
        $deleteUrl = $this->router->generate('aw_enhanced_action', [
            'schema' => 'RAFlightSearchRoute',
            'action' => 'delete',
        ]);

        $Interface->FooterScripts['loungeList'] = <<<JS
$(function() {
    function addButton(className, value, url) {
        $('#listButtons tr td:last-child').prepend('<input type="button" class="action-button ' + className + '" value="' + value + '" data-url="' + url + '"> ');
    }
    
    function addToggleButton(className, value, onUrl, offUrl) {
        $('#listButtons tr td:last-child').prepend('<input type="button" class="action-button toggle ' + className + '" value="' + value + '" data-on-url="' + onUrl + '" data-off-url="' + offUrl + '"> ');
    }
    
    function onAction(className, value) {
        var buttons = $('.action-button.toggle.' + className);
        
        buttons.each(function() {
            var button = $(this);
            
            button.removeClass('off');
            
            if (!button.hasClass('on')) {
                button.addClass('on');
            }
            
            button.val(value);
        });
    }
    
    function offAction(className, value) {
        var buttons = $('.action-button.toggle.' + className);
        
        value = 'Un' + value;
        value = value.charAt(0).toUpperCase() + value.slice(1).toLowerCase();
        
        buttons.each(function() {
            var button = $(this);
            
            button.removeClass('on');
            
            if (!button.hasClass('off')) {
                button.addClass('off');
            }
            
            button.val(value);
        });
    }
    
    function toggleAction(className, value) {
        var checkedElements = $('input[name^="sel"]:checked');
        var checked = checkedElements.map(function() {
            return this.value;
        }).get();
        
        if (checked.length === 0) {
            onAction(className, value);
            
            return;
        }
        
        var data = checkedElements.map(function() {
            return $(this).closest('tr').find('td:nth-child(2) [data-' + className + ']').data(className);
        }).get();
        
        if (data.every(function(item) { return item === 0; })) {
            onAction(className, value);
        } else if (data.every(function(item) { return item === 1; })) {
            offAction(className, value);
        } else {
            onAction(className, value);
        }
    }
    
    addButton('delete', 'Delete', '{$deleteUrl}');
    addToggleButton('archive', 'Archive', '{$archiveUrl}', '{$unarchiveUrl}');
    addToggleButton('flag', 'Flag', '{$flagUrl}', '{$unflagUrl}');
    
    toggleAction('archive', 'Archive');
    toggleAction('flag', 'Flag');
    
    $('input[name^="sel"], #listButtons input[type=checkbox]').change(function() {
        toggleAction('archive', 'Archive');
        toggleAction('flag', 'Flag');
    });
    
    $(document).on('click', '.action-button', function() {
        var checked = $('input[name^="sel"]:checked').map(function() {
            return this.value;
        }).get();
        
        if (checked.length === 0) {
            alert('Please select at least one route');
            
            return;
        }
        
        if (!confirm('Are you sure?')) {
            return;
        }
        
        var _this = $(this);
        var url = null;
        
        if (_this.hasClass('toggle')) {
            if (_this.hasClass('on')) {
                url = _this.data('on-url');
            } else if (_this.hasClass('off')) {
                url = _this.data('off-url');
            }
        } else {
            url = _this.data('url');
        }
        
        $.ajax({
            url: url,
            type: 'POST',
            data: {
                ids: checked
            },
            success: function(data) {
                if (data.success) {
                    location.reload();
                } else {
                    alert('Error');
                }
            },
            error: function() {
                alert('Error');
            }
        });
    });
});
JS;

        $styles = <<<HTML
<style>
    .segments-details {
        border-collapse: collapse;
        width: 100%;
        border: 1px solid #ababab;
    }
    .segments-details tr {
        border: 1px solid #ababab;
    }
    .segments-details td {
        border: 1px solid #ababab !important;
        font-size: 0.75em;
    }
    .segments-details tr:first-child td:nth-child(3) {
        min-width: 50px;
    }
    .segments-details tr:first-child td:nth-child(7), .segments-details tr:first-child td:nth-child(8) {
        min-width: 45px;
    }
    .segments-details tr:first-child td:nth-child(4), .segments-details tr:first-child td:nth-child(10) {
        min-width: 33px;
    }
</style>
HTML;

        $styles = addslashes(str_replace("\n", '', $styles));
        echo "<script>$(document.body).append('$styles');</script>";
    }

    public function GetEditLinks()
    {
        $kibanaLogs = sprintf(
            "https://kibana.awardwallet.com/app/discover#/?_g=(refreshInterval:(pause:!t,value:0),time:(from:now-%dd,to:now))&_a=(columns:!(message),filters:!(),index:f7bcf3e0-1a67-11e9-8067-9bee5e3ddf43,interval:auto,query:(language:kuery,query:'extra.requestId:%s'),sort:!())",
            30,
            $this->Query->Fields['ApiRequestID']
        );
        $parserLogs = sprintf(
            "https://awardwallet.com/manager/loyalty/logs?RequestID=%s&Method=reward-availability&Cluster=ra-awardwallet&Partner=awardwallet",
            $this->Query->Fields['ApiRequestID']
        );

        return sprintf(
            '<a href="%s" target="_blank">kibana</a> | <a href="%s" target="_blank">parser logs</a>',
            $kibanaLogs,
            $parserLogs
        );
    }

    private function formatDuration(int $minutes): string
    {
        $hours = floor($minutes / 60);
        $minutes %= 60;

        if ($hours > 0 && $minutes > 0) {
            return sprintf('%dh %02dm', $hours, $minutes);
        }

        if ($hours > 0) {
            return sprintf('%dh', $hours);
        }

        return sprintf('%dm', $minutes);
    }
}
