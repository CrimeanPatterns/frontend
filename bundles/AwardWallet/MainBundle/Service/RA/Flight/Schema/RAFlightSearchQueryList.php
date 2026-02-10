<?php

namespace AwardWallet\MainBundle\Service\RA\Flight\Schema;

use AwardWallet\MainBundle\Entity\RAFlightSearchQuery;
use AwardWallet\MainBundle\Globals\Localizer\LocalizeService;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;

class RAFlightSearchQueryList extends \TBaseList
{
    private LocalizeService $localizer;

    private RouterInterface $router;

    public function __construct(
        LocalizeService $localizer,
        RouterInterface $router,
        $table,
        $fields
    ) {
        $this->localizer = $localizer;
        $this->router = $router;

        unset($fields['AutoSelectParsers'], $fields['SubSearchCount'], $fields['LastSearchKey'], $fields['ExcludeParsers']);

        foreach ($fields as $code => $field) {
            if (!isset($field['FilterField'])) {
                $fields[$code]['FilterField'] = 'q.' . $code;
            }
        }

        $fields['UserID']['FilterField'] = 'COALESCE(tp.UserID, q.UserID)';

        parent::__construct($table, $fields);

        $this->SQL = "
            SELECT
                q.*,
                q.UserID AS QueryUserID,
                CONCAT(TRIM(CONCAT(u.FirstName, ' ', COALESCE(u.MidName, ''))), ' ', u.LastName) QueryUserName,
                tp.UserID AS TripUserID,
                IF(tp.UserID IS NULL, NULL, CONCAT(TRIM(CONCAT(u2.FirstName, ' ', COALESCE(u2.MidName, ''))), ' ', u2.LastName)) TripUserName,
                COALESCE(tp.UserID, q.UserID) AS UserID,
                s.Status,
                IFNULL(t.ActiveRoutesCount, 0) AS ActiveRoutesCount
            FROM
                RAFlightSearchQuery q
                LEFT JOIN Usr u ON u.UserID = q.UserID
                JOIN (
                    SELECT
                        RAFlightSearchQueryID,
                        IF((DepDateFrom >= CURRENT_DATE() OR DepDateTo >= CURRENT_DATE()) AND SearchInterval <> '" . RAFlightSearchQuery::SEARCH_INTERVAL_ONCE . "', 0, 1) AS Status
                    FROM RAFlightSearchQuery
                ) s ON s.RAFlightSearchQueryID = q.RAFlightSearchQueryID
                LEFT JOIN (
                    SELECT
                        RAFlightSearchQueryID,
                        COUNT(*) AS ActiveRoutesCount
                    FROM
                        RAFlightSearchRoute
                    WHERE
                        Archived = 0
                    GROUP BY
                        RAFlightSearchQueryID
                ) t ON t.RAFlightSearchQueryID = q.RAFlightSearchQueryID
                LEFT JOIN MileValue mv ON mv.MileValueID = q.MileValueID
                LEFT JOIN Trip tp ON tp.TripID = mv.TripID
                LEFT JOIN Usr u2 ON u2.UserID = tp.UserID
            WHERE 
                1 = 1
                [Filters]
                AND q.DeleteDate IS NULL
        ";

        $this->ShowImport = false;
        $this->DefaultSort = 'Status';
        $this->DefaultSort2 = 'DepDateFrom';
        $this->PageSizes = ['50' => '50', '100' => '100', '500' => '500'];
        $this->PageSize = 100;
    }

    public function GetFilterFields()
    {
        $fields = parent::GetFilterFields();

        $findUserRoute = $this->router->generate(
            'aw_enhanced_action',
            [
                'schema' => 'RAFlightSearchQuery',
                'action' => 'find-user',
            ],
            UrlGeneratorInterface::ABSOLUTE_URL
        );
        $fields['UserID']['InputAttributes'] .= ' data-source="' . htmlspecialchars($findUserRoute) . '" data-param="query"';

        return $fields;
    }

    public function DrawButtons($closeTable = true)
    {
        parent::DrawButtons($closeTable);

        $styles = <<<HTML
<style>
    #list-table tr td:nth-child(2),
    #list-table tr td:nth-child(3) {
        font-size: 9px;
    }
    
    #list-table tr td:nth-child(6),
    #list-table tr td:nth-child(7),
    #list-table tr td:nth-child(18),
    #list-table tr td:nth-child(19) {
        font-size: 11px;
    }
</style>
HTML;

        $styles = addslashes(str_replace("\n", '', $styles));
        echo "<script>$(document.body).append('$styles');</script>";
    }

    public function GetFieldFilter($sField, $arField)
    {
        if (in_array($sField, ['DepartureAirports', 'ArrivalAirports']) && !empty($arField['Value'])) {
            $sFilters = $this->prepareAirportsFilter($sField, $arField['Value']);
        } else {
            $sFilters = parent::GetFieldFilter($sField, $arField);
        }

        return $sFilters;
    }

    public function FormatFields($output = "html")
    {
        parent::FormatFields($output);

        $state = @json_decode($this->Query->Fields['State'], true);
        $this->Query->Fields['UserID'] = $this->Query->Fields['TripUserName'] ?? $this->Query->Fields['QueryUserName'];
        $this->Query->Fields['DepartureAirports'] = $this->formatAirportsField($this->Query->Fields['DepartureAirports']);
        $this->Query->Fields['ArrivalAirports'] = $this->formatAirportsField($this->Query->Fields['ArrivalAirports']);
        $this->Query->Fields['FlightClass'] = $this->formatTag($this->Query->Fields['FlightClass'], '#54575c', '#ebf2f8', null, 'div');
        $this->Query->Fields['Parsers'] = $this->formatParsers($this->OriginalFields, $state);
        $this->Query->Fields['EconomyMilesLimit'] = $this->formatLimit($this->Query->Fields['EconomyMilesLimit']);
        $this->Query->Fields['PremiumEconomyMilesLimit'] = $this->formatLimit($this->Query->Fields['PremiumEconomyMilesLimit']);
        $this->Query->Fields['BusinessMilesLimit'] = $this->formatLimit($this->Query->Fields['BusinessMilesLimit']);
        $this->Query->Fields['FirstMilesLimit'] = $this->formatLimit($this->Query->Fields['FirstMilesLimit']);

        $additionFilters = [];

        if (!empty($this->Query->Fields['MaxTotalDuration'])) {
            $additionFilters[] = sprintf('MaxTotalDuration: %sh', $this->localizer->formatNumber($this->Query->Fields['MaxTotalDuration'], 2));
        }

        if (!empty($this->Query->Fields['MaxSingleLayoverDuration'])) {
            $additionFilters[] = sprintf('MaxSingleLayoverDuration: %sh', $this->localizer->formatNumber($this->Query->Fields['MaxSingleLayoverDuration'], 2));
        }

        if (!empty($this->Query->Fields['MaxTotalLayoverDuration'])) {
            $additionFilters[] = sprintf('MaxTotalLayoverDuration: %sh', $this->localizer->formatNumber($this->Query->Fields['MaxTotalLayoverDuration'], 2));
        }

        if (!empty($this->Query->Fields['MaxStops'])) {
            $additionFilters[] = sprintf('MaxStops: %s', $this->localizer->formatNumber($this->Query->Fields['MaxStops']));
        }

        $this->Query->Fields['AdditionFilters'] = implode(' ', array_map(function (string $filter) {
            return sprintf(
                '<div style="font-size: 0.7em; color: slategray;">%s</div>',
                $filter
            );
        }, $additionFilters));

        if (empty($this->Query->Fields['SearchCount'])) {
            $this->Query->Fields['SearchCount'] = 0;
        } else {
            $this->Query->Fields['SearchCount'] = sprintf(
                '<div><i style="cursor: pointer;" title="Total number of search queries">%s</i> (<i style="cursor: pointer;" title="Total number of search subqueries">%s</i>)</div>',
                $this->localizer->formatNumber($this->Query->Fields['SearchCount']),
                $this->localizer->formatNumber($this->Query->Fields['SubSearchCount'])
            );
        }

        if (!empty($this->Query->Fields['MileValueID'])) {
            $this->Query->Fields['MileValueID'] = sprintf(
                '<a href="list.php?Schema=MileValue&MileValueID=%d" target="_blank">%d</a>',
                $this->Query->Fields['MileValueID'],
                $this->Query->Fields['MileValueID']
            );
        }

        if (is_array($state)) {
            $desc = [];

            if (!is_null($state['query']['error'] ?? null)) {
                $desc[] = sprintf(
                    '<div style="font-size: 0.7em;"><b>%s:</b> <span style="color: darkgrey">query error</span>, %s</div>',
                    date('Y-m-d H:i:s', strtotime($state['query']['date'])),
                    $state['query']['error']
                );
            }

            if (!is_null($state['request']['error'] ?? null)) {
                $desc[] = sprintf(
                    '<div style="font-size: 0.7em;"><b>%s:</b> <span style="color: darkgrey">request error</span>, <span title="%s">%s</span></div>',
                    date('Y-m-d H:i:s', strtotime($state['request']['date'])),
                    $state['request']['last_request'],
                    $state['request']['error']
                );
            }

            if (\count($desc) > 0) {
                $this->Query->Fields['Status'] = sprintf(
                    '<b>%s</b><br>%s',
                    $this->Query->Fields['Status'],
                    implode('', $desc)
                );
            } else {
                $this->Query->Fields['Status'] = sprintf('<b>%s</b>', $this->Query->Fields['Status']);
            }
        } else {
            $this->Query->Fields['Status'] = sprintf('<b>%s</b>', $this->Query->Fields['Status']);
        }
    }

    public function GetEditLinks()
    {
        $links = [];

        if (empty($this->OriginalFields['MileValueID'])) {
            $links[] = parent::GetEditLinks();
        }

        $links[] = sprintf(
            '<a href="list.php?Schema=RAFlightSearchRoute&RAFlightSearchQueryID=%d&Archived=0" target="_blank">results%s</a>',
            $this->OriginalFields['RAFlightSearchQueryID'],
            $this->OriginalFields['ActiveRoutesCount'] > 0 ? sprintf(' (%s)', $this->OriginalFields['ActiveRoutesCount']) : ''
        );

        $links[] = sprintf(
            '<a href="javascript:void(0);" class="search-now" data-url="%s">search now</a>',
            $this->router->generate(
                'aw_enhanced_action',
                [
                    'schema' => 'RAFlightSearchQuery',
                    'action' => 'search',
                    'id' => $this->OriginalFields['RAFlightSearchQueryID'],
                ]
            )
        );

        return implode(' | ', $links);
    }

    public function DrawFooter()
    {
        global $Interface;

        parent::DrawFooter();

        $getParsersRoute = $this->router->generate(
            'aw_enhanced_action',
            [
                'schema' => 'RAFlightSearchQuery',
                'action' => 'get-parsers',
            ]
        );
        $Interface->FooterScripts[] = <<<JS
var processed = false;

$(document).on('click', '.search-now', function () {
    if (processed) {
        return;
    }
    
    processed = true;
    
    var \$this = $(this);
    var url = \$this.data('url');
    
    $.ajax({
        url: url,
        method: 'GET',
        dataType: 'json',
        success: function (response) {
            processed = false;
            
            if (response.success) {
                \$this.html('success').css('color', 'green');
                setTimeout(function () {
                    \$this.html('search now').css('color', '');
                }, 2000);
            } else {
                alert('Error occurred while searching');
            }
        },
        error: function () {
            processed = false;
            alert('Error occurred while searching');
        }
    });
});
function loadParsers(queryIds) {
    $.post('$getParsersRoute', {queries: queryIds}, function (response) {
        queryIds.forEach(function (queryId) {
            var \$element = $('[data-load-parser-list="' + queryId + '"]');
            var data = response[queryId];
            
            if (data == null) {
                \$element.remove();
                return;
            }
            
            \$element.parent().html(data);
        });
    });
}
function loadVisibleParsers() {
    var queryIds = [];
    
    $('[data-load-parser-list]').each(function () {
        var \$this = $(this);
        var queryId = \$this.data('load-parser-list');
        
        if (queryIds.indexOf(queryId) === -1) {
            queryIds.push(queryId);
        }
    });
    
    if (queryIds.length > 0) {
        loadParsers(queryIds);
    }
}

$(function() {
    loadVisibleParsers();
    
    var scrollTimeout = null;
    
    $(window).scroll(function () {
        if (scrollTimeout) {
            clearTimeout(scrollTimeout);
        }
        
        scrollTimeout = setTimeout(function () {
            loadVisibleParsers();
        }, 1000);
    });
});
JS;
    }

    private function prepareAirportsFilter($field, $value): string
    {
        $codes = array_map('strtoupper', array_map('trim', explode(',', $value)));

        return sprintf(" AND JSON_CONTAINS(q.%s, '%s', '$')", $field, json_encode($codes));
    }

    private function formatAirportsField($field): string
    {
        if (empty($field)) {
            return '';
        }

        $json = json_decode(htmlspecialchars_decode($field), true);
        $codes = array_map(function (string $code) {
            return $this->formatTag(strtoupper($code), '#818181', '#eaeaea');
        }, $json);

        return sprintf('<div style="line-height: 1.8">%s</div>', implode('', $codes));
    }

    private function formatParsers($fields, ?array $state): string
    {
        if ($fields['AutoSelectParsers'] == '1') {
            return '
                <div style="line-height: 1.8" class="auto-select-parsers" data-load-parser-list="' . $fields['RAFlightSearchQueryID'] . '">
                    <span style="color: #818181; background-color: #eaeaea; padding: 2px; margin: 3px;">
                        Loading...
                    </span>
                </div>
            ';
        } else {
            if (empty($fields['Parsers'])) {
                return '';
            }

            $parsers = array_map(function (string $parser) use ($state) {
                $parser = trim($parser);
                $label = $this->Fields['Parsers']['Options'][$parser] ?? $parser;
                $hasError = $state && ($state['parser'][$parser]['error'] ?? 0) > 0;

                return $this->formatTag(
                    $label,
                    $hasError ? '#ff0000' : '#818181',
                    $hasError ? '#f8d7da' : '#eaeaea',
                    sprintf(
                        'last success requests: %s, last error requests: %s',
                        $this->localizer->formatNumber($state['parser'][$parser]['success'] ?? 0),
                        $this->localizer->formatNumber($state['parser'][$parser]['error'] ?? 0)
                    )
                );
            }, explode(',', htmlspecialchars_decode($fields['Parsers'])));

            return sprintf('<div style="line-height: 1.8">%s</div>', implode('', $parsers));
        }
    }

    private function formatLimit($field): string
    {
        if (empty($field)) {
            return '';
        }

        return $this->formatTag(
            sprintf(htmlspecialchars('<=%s'), $this->localizer->formatNumber($field)),
            '#818181',
            '#d3f3d6'
        );
    }

    private function formatTag(string $text, string $color, string $bgColor, ?string $title = null, string $tag = 'span'): string
    {
        return sprintf(
            '<%s style="%sfont-size: 10px; text-align: center; border: 0; background-color: %s; color: %s; padding: 2px; margin: 3px;%s" title="%s">%s</%s><wbr>',
            $tag,
            !empty($title) ? 'cursor: pointer;' : '',
            $bgColor,
            $color,
            $tag === 'span' ? 'text-wrap: nowrap;' : '',
            $title ?? '',
            $text,
            $tag
        );
    }
}
