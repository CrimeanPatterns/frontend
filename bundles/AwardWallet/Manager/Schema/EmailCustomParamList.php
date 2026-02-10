<?php

namespace AwardWallet\Manager\Schema;

use AwardWallet\MainBundle\Service\Blog\EmailNotificationNewPost;

/**
 * @property EmailCustomParam $Schema
 */
class EmailCustomParamList extends \TBaseList
{
    public function FormatFields($output = 'html'): void
    {
        parent::FormatFields($output);

        $this->Query->Fields['Message'] = html_entity_decode($this->Query->Fields['Message']);
        $this->Query->Fields['Message'] = EmailNotificationNewPost::replaceCkeditorStyles($this->Query->Fields['Message']);

        $this->Query->Fields['Type'] = \AwardWallet\MainBundle\Entity\EmailCustomParam::TYPES[(int) $this->Query->Fields['Type']];
    }

    public function DrawFooter()
    {
        parent::DrawFooter();

        $kibanaLinks = [$this->getKibanaLink('monday this week')];

        for ($i = -1; ++$i < 2;) {
            $kibanaLinks[] = $this->getKibanaLink('monday ' . $i . ' week ago');
        }

        echo '
        <script>
        $("#content-title").append("<span>kibana stat:</span>' . implode('', $kibanaLinks) . '");
        </script>
        <style>
        #content-title span {
            display: inline-block;
            margin-left: 3rem;
            color: #000;
            font-size: 16px;
        }
        #content-title a {
            margin-left: 2rem;
            font-size: 16px;
            text-decoration: none;
        }
        #content-title a:hover {
            text-decoration: underline;
        }
        </style>
        ';
    }

    private function getKibanaLink(string $textDate): string
    {
        $link = "https://kibana.awardwallet.com/app/dashboards#/view/57f2a550-a1af-11ea-8601-8313683eea38?_g=(filters:!(),refreshInterval:(pause:!t,value:0),time:(from:'%from%',to:now))&_a=(description:'',filters:!(),fullScreenMode:!f,options:(darkTheme:!f,hidePanelTitles:!f,useMargins:!t),query:(language:lucene,query:list_post_week_%date%),timeRestore:!f,title:'Email%20Offer%20Stats',viewMode:view)";

        $ts = strtotime($textDate);
        $from = date('Y-m-d', $ts) . 'T00:00:00.001Z';
        $date = date('Ymd', $ts);

        return '<a href=\"'
            . str_replace(['%from%', '%date%'], [$from, $date], $link) . '\" target=\"kibana\">'
            . date('m/d/Y', $ts)
            . '</a>';
    }
}
