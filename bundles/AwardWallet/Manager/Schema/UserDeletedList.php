<?php

namespace AwardWallet\Manager\Schema;

class UserDeletedList extends \TBaseList
{
    public function DrawButtons($closeTable = true)
    {
        $this->footerScripts = [];

        $extend = '
<div style="float:left;padding:10px 0 10px 10px;">
    <form id="extForm" method="get" action="/manager/list.php" class="qs-form">
    <input type="hidden" name="Schema" value="UserDeleted">
        <div class="qs-filter-date" style="padding: 5px 0;">
            <div style="width: 700px;">
                Deletion Date:
                    from <input type="date" name="dfrom" value="' . ($_GET['dfrom'] ?? '') . '" title="date from">
                    to <input type="date" name="dto" value="' . ($_GET['dto'] ?? '') . '" title="date to"> or 
                <select>
                    <option value="">Choose Period</option>
                    ' . $this->getPeriodOptions() . '
                </select>
                <button type="submit" style="position: relative;margin: 0 15px;padding: 0 10px;"> Apply </button>
                <a href="#reset-period" onclick="$(\'#extForm input[type=date]\').val(\'\')" style="float: right;margin-top:3px;">(reset period)</a>
            </div>
        </div>
    </form>
</div>';

        $this->footerScripts[] = '
            $("#extendFixedMenu ").prepend("' . addslashes(str_replace("\n", '', $extend)) . '");
            $("select", "#extForm").change(function() {
                var dates = $(this).val().split("=");
                $(\'input[name="dfrom"]\', "#extForm").val(dates[0]);
                if (undefined !== dates[1])
                    $(\'input[name="dto"]\', "#extForm").val(dates[1]);
            });
        ';

        $style = '
        <style type="text/css">
            .qs-form label {cursor: pointer}
            #contentBody {margin-top: 80px !important;}
        </style>
        ';
        $this->footerScripts[] = '
            $(document.body).append("' . addslashes(str_replace("\n", '', $style)) . '");
        ';
    }

    public function DrawFooter()
    {
        parent::DrawFooter();
        $this->drawFooterScript();
    }

    public function DrawEmptyList()
    {
        parent::DrawEmptyList();
        $this->drawFooterScript();
    }

    private function drawFooterScript()
    {
        global $Interface;

        if (empty($Interface->isAlreadyFooterScripts) && !empty($this->footerScripts)) {
            $Interface->isAlreadyFooterScripts = true;
            echo '<script>';

            foreach ($this->footerScripts as $script) {
                echo $script;
            }
            echo '</script>';
        }
    }

    private function getPeriodOptions()
    {
        $periods = [
            [
                'title' => 'Yesterday',
                'start' => date('Y-m-d', strtotime('yesterday')),
                'end' => date('Y-m-d', strtotime('yesterday')),
            ],
            [
                'title' => 'Current Week',
                'start' => date('Y-m-d', strtotime('monday this week')),
                'end' => date('Y-m-d', strtotime('sunday this week')),
            ],
            [
                'title' => 'Last Week',
                'start' => date('Y-m-d', strtotime('monday last week')),
                'end' => date('Y-m-d', strtotime('sunday last week')),
            ],
            [
                'title' => 'Current Month',
                'start' => date('Y-m-d', strtotime('first day of this month')),
                'end' => date('Y-m-d', strtotime('last day of this month')),
            ],
            [
                'title' => 'Last Month',
                'start' => date('Y-m-d', strtotime('first day of last month')),
                'end' => date('Y-m-d', strtotime('last day of last month')),
            ],
            [
                'title' => '3 Months Ago',
                'start' => date('Y-m-d', strtotime('first day of 3 month ago')),
                'end' => date('Y-m-d', strtotime('last day of 3 month ago')),
            ],
            [
                'title' => 'Last 6 Months',
                'start' => date('Y-m-d', strtotime('first day of 6 month ago')),
                'end' => date('Y-m-d', strtotime('last day of last month')),
            ],
            [
                'title' => 'Current Year',
                'start' => date('Y-01-01'),
                'end' => date('Y-m-d'),
            ],
        ];

        $options = '';

        foreach ($periods as $period) {
            $start = $period['start'];
            $end = $period['end'];
            $options .= '<option value="' . $start . '=' . $end . '">' . $period['title'] . '</option>';
        }

        return $options;
    }
}
