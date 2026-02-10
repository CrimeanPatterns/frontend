<?php

namespace AwardWallet\Manager\Schema;

use AwardWallet\MainBundle\Service\MileValue\MileValueCache;
use AwardWallet\MainBundle\Service\MileValue\MileValueService;

/**
 * @property ProviderMileValue $Schema
 */
class ProviderMileValueList extends \TBaseList
{
    public function __construct($sTable, $arFields, $sDefaultSort = null, $request = null)
    {
        parent::__construct($sTable, $arFields, $sDefaultSort, $request);

        if (isset($_GET['updateMileValueCache']) && !$this->isMileValueUpdateInProcess(false)) {
            getSymfonyContainer()->get(MileValueService::class)->getData(false, null, true);
            $cacheKey = MileValueCache::CACHE_KEY . '_full_' . json_encode(null);
            getSymfonyContainer()->get(MileValueCache::class)->clear($cacheKey);

            exit('ok');
        }
    }

    public function FormatFields($output = 'html')
    {
        parent::FormatFields($output);
        $this->Query->Fields = $this->formatFieldsRow($this->Query->Fields);
    }

    public function formatFieldsRow($row)
    {
        if (is_null($this->OriginalFields['EndDate'])) {
            $calculatedValues = $this->Schema->getCurrentValues($this->OriginalFields["ProviderID"]);
        } else {
            $calculatedValues = $this->Schema->getHistoryValues($this->OriginalFields["ProviderMileValueID"]);
        }

        foreach (ProviderMileValue::VALUE_FIELDS as $field) {
            $row[$field] = '<span class="manual-val" title="Manual Input">' . $row[$field] . '</span><span class="auto-val" title="Auto Value' . ($calculatedValues[$field]['title'] ?? '') . '">' . ($calculatedValues[$field]['value'] ?? '') . '</span>';
        }

        return $row;
    }

    public function DrawFooter()
    {
        parent::DrawFooter();

        $style = '
            <style type="text/css">
                .detailsTable span {display:inline-block;min-width: 35px;}
                .manual-val {text-align: right;}
                .auto-val {color: #555;}
                .manual-val:empty:before {content: "-";}
                .auto-val:before {display: inline-block;content: "/";margin: 0 10px;}
                .auto-val:empty:after {display:inline;content: "-";}
                
                #cacheMileValueUpdate {float: right}
            </style>
            ';

        $popularProviders =
            '10,12,17,22,521,36,15,88,383,'
            . '44,37,1,416,31,71,26,2,18,35,86,13,40,184,92,7,48,179,43,34,33,16,97,96,223,78,79,178,275,20,51,136,83,157,176,41,186,52,390,208,1389,152,258,537,768,95,181,274,1103,127,276,66,449,9,553,134,39,560,294,155,85,265,520,389,348,93,1275,434';

        echo '
        <script>
            $(document.body).append("' . addslashes(str_replace("\n", '', $style)) . '");
            $("#fldSort1").after(`
                <label id="mostPopular" style="margin-left:20px;"><input type="checkbox"> Most Popular</label>
                <!--button id="cacheMileValueUpdate" class="btn" title="Click this button when you have made all the necessary changes (Can be used once every 20 minutes)" type="button" ' . ($this->isMileValueUpdateInProcess(true) ? ' disabled' : '') . '>Update Cache</button-->
            `);
            var mostPopular = [' . $popularProviders . '];
            $("#mostPopular input").change(function(e){
                if ($(this).prop("checked")) {
                    $("#list-table>tbody>tr>td:nth-child(2)").each(function(){
                        if (-1 === mostPopular.indexOf(parseInt($(this).text())))
                            $(this).closest("tr").hide();
                    });
                } else {
                    $("#list-table>tbody>tr").show();
                }
            });
            
            $("#cacheMileValueUpdate").click(function(){
                $(this).prop("disabled", true);
                $.get(\'/manager/list.php?Schema=ProviderMileValue&updateMileValueCache=1\', function(response){
                    console.log(response);
                });
            });
        </script>
        ';
    }

    public function isMileValueUpdateInProcess(bool $onlyCheck): bool
    {
        $cache = getSymfonyContainer()->get(\Memcached::class);
        $cacheKey = 'forceCacheMileValueUpdateManager';

        $is = $cache->get($cacheKey);

        if ($is) {
            return true;
        }

        if (!$onlyCheck) {
            $cache->set($cacheKey, 1, 60 * 5);
        }

        return false;
    }
}
