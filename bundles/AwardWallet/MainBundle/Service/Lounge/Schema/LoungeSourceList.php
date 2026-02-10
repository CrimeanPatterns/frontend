<?php

namespace AwardWallet\MainBundle\Service\Lounge\Schema;

use AwardWallet\MainBundle\Entity\LoungeSource;
use AwardWallet\MainBundle\Service\Lounge\Formatter\Mobile\OpeningHoursScheduleBuilder;
use AwardWallet\MainBundle\Service\Lounge\Scheduler;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;

class LoungeSourceList extends AbstractLoungeList
{
    private EntityRepository $loungeSourceRep;

    private Scheduler $scheduler;

    public function __construct(
        $table,
        $fields,
        EntityManagerInterface $em,
        Scheduler $scheduler,
        OpeningHoursScheduleBuilder $openingHoursScheduleBuilder
    ) {
        $this->loungeSourceRep = $em->getRepository(LoungeSource::class);
        $this->scheduler = $scheduler;

        parent::__construct($table, $fields, $openingHoursScheduleBuilder);

        // Exclude PageBody field to avoid memory issues with large HTML content
        $this->SQL = "
            SELECT
                LoungeSourceID, Name, AirportCode, Terminal, Gate, Gate2, OpeningHours,
                IsAvailable, Location, AdditionalInfo, Amenities, Rules, IsRestaurant,
                SourceCode, SourceID, Assets, CreateDate, UpdateDate, DeleteDate, ParseDate,
                PriorityPassAccess, AmexPlatinumAccess, DragonPassAccess, LoungeKeyAccess, LoungeID
            FROM LoungeSource
            WHERE 
                1 = 1
                [Filters]
        ";
    }

    public function DrawHeader()
    {
        $now = new \DateTime();
        $dates = $this->scheduler->getSchedule();
        $dates = array_map(function (\DateTime $date) use ($now) {
            $strDate = $date->format('Y-m-d');

            if ($now->format('Y-m-d') === $strDate) {
                return sprintf('<span style="color: green; font-weight: bolder;">%s</span>', $strDate);
            } elseif ($now > $date) {
                return sprintf('<span style="color: #9a9a9a">%s</span>', $strDate);
            } else {
                return sprintf('<span style="color: #1092bd">%s</span>', $strDate);
            }
        }, $dates);

        echo sprintf('<div style="padding: 10px 0;"><span style="font-weight: bolder;">Schedule:</span> %s</div>', implode(' | ', $dates));

        parent::DrawHeader();
    }

    public function FormatFields($output = 'html')
    {
        parent::FormatFields($output);

        $lounge = $this->loungeSourceRep->find($this->Query->Fields['LoungeSourceID']);
        $this->defaultFormat($lounge);

        if ($this->Query->Fields['Assets']) {
            $assets = @json_decode(html_entity_decode($this->Query->Fields['Assets']), true);

            if (is_array($assets)) {
                $this->formatAssets($assets);
            }
        }

        $this->Query->Fields['SourceCode'] = sprintf(
            '%s<br>%s',
            $this->Query->Fields['SourceCode'],
            '<div title="' . $this->Query->Fields['SourceID'] . '" style="white-space: nowrap; overflow: hidden; text-overflow: ellipsis; width: 150px; color: #797979">' . $this->Query->Fields['SourceID'] . '</div>'
        );

        if ($this->Query->Fields['DeleteDate']) {
            $this->Query->Fields['DeleteDate'] = sprintf(
                '<div style="color: orangered;">%s</div>',
                $this->Query->Fields['DeleteDate']
            );
        }

        if (!empty($this->OriginalFields['LoungeID'])) {
            $this->Query->Fields['LoungeID'] = '<a target="_blank" href="list.php?Schema=Lounge&LoungeID=' . $this->OriginalFields['LoungeID'] . '">' . $this->Query->Fields['LoungeID'] . '</a>';
        }
    }

    protected function getCssStyles(): string
    {
        return <<<CSS
#list-table thead tr td:nth-child(4) {
    min-width: 150px;
}
#list-table thead tr td:nth-child(6) {
    min-width: 150px;
}
#list-table thead tr td:nth-child(12) {
    min-width: 150px;
}
CSS;
    }

    protected function getRowColor(): string
    {
        if ($this->OriginalFields['IsAvailable'] == 0) {
            return '#ebebeb';
        }

        return '#fffcf5';
    }
}
