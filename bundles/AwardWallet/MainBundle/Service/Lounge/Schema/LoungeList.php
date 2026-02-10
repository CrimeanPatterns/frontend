<?php

namespace AwardWallet\MainBundle\Service\Lounge\Schema;

use AwardWallet\MainBundle\Entity\Lounge;
use AwardWallet\MainBundle\Service\Lounge\Action\FreezeAction;
use AwardWallet\MainBundle\Service\Lounge\Formatter\Mobile\OpeningHoursScheduleBuilder;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Symfony\Component\Routing\RouterInterface;

class LoungeList extends AbstractLoungeList
{
    private EntityRepository $loungeRep;

    private RouterInterface $router;

    public function __construct(
        $table,
        $fields,
        EntityManagerInterface $em,
        OpeningHoursScheduleBuilder $openingHoursScheduleBuilder,
        RouterInterface $router
    ) {
        $this->loungeRep = $em->getRepository(Lounge::class);
        $this->router = $router;

        foreach ($fields as $code => $field) {
            if (!isset($field['FilterField'])) {
                $fields[$code]['FilterField'] = 'l.' . $code;
            }
        }

        parent::__construct($table, $fields, $openingHoursScheduleBuilder);

        $this->SQL = "
            SELECT
                l.*,
                t.Sources,
                COALESCE(t2.ChangesCount, 0) AS ChangesCount,
                t3.DeletedSources,
                IF(t4.LoungeID IS NULL, 0, 1) AS Duplicates,
                IF(t5.LoungeID IS NULL, 0, 1) AS FreezeAction
            FROM Lounge l
                LEFT JOIN (
                    SELECT
                        LoungeID,
                        GROUP_CONCAT(DISTINCT SourceCode SEPARATOR ', ') AS Sources
                    FROM
                        LoungeSource
                    GROUP BY LoungeID
                ) t ON t.LoungeID = l.LoungeID
                LEFT JOIN (
                    SELECT
                        ls.LoungeID,
                        COUNT(*) AS ChangesCount
                    FROM
                        LoungeSourceChange lsc
                        JOIN LoungeSource ls ON lsc.LoungeSourceID = ls.LoungeSourceID
                    WHERE ls.LoungeID IS NOT NULL
                    GROUP BY ls.LoungeID
                ) t2 ON t2.LoungeID = l.LoungeID
                LEFT JOIN (
                    SELECT
                        LoungeID,
                        GROUP_CONCAT(DISTINCT SourceCode SEPARATOR ', ') AS DeletedSources
                    FROM
                        LoungeSource
                    WHERE
                        DeleteDate IS NOT NULL
                    GROUP BY LoungeID
                ) t3 ON t3.LoungeID = l.LoungeID
                LEFT JOIN (
                    SELECT DISTINCT
                        LoungeID
                    FROM
                        LoungeSource
                    WHERE LoungeID IS NOT NULL
                    GROUP BY LoungeID, SourceCode
                    HAVING COUNT(*) > 1
                ) t4 ON t4.LoungeID = l.LoungeID
                LEFT JOIN (
                    SELECT LoungeID
                    FROM LoungeAction
                    WHERE Action->'$.data.type' = 'freeze'
                ) t5 ON t5.LoungeID = l.LoungeID
            WHERE 
                1 = 1
                [Filters]
        ";
    }

    public function DrawButtonsInternal()
    {
        $result = parent::DrawButtonsInternal();

        echo '<input type="button" class="button check-and-remove-changes" value="Check and remove changes"> ';

        return $result;
    }

    public function DrawButtons($closeTable = true)
    {
        global $Interface;

        parent::DrawButtons($closeTable);

        $url = $this->router->generate('aw_enhanced_action', [
            'schema' => 'Lounge',
            'action' => 'check-and-remove-changes',
        ]);
        $Interface->FooterScripts['loungeList'] = <<<JS
$(function() {
    $('#extendFixedMenu div').append(' <input type="button" class="button check-and-remove-changes" value="Check and remove changes">');
    $('.check-and-remove-changes').click(function() {
        var checked = $('input[name^="sel"]:checked').map(function() {
            return this.value;
        }).get();
        
        if (checked.length === 0) {
            alert('Please select at least one lounge');
            
            return;
        }
        
        if (!confirm('Are you sure?')) {
            return;
        }
        
        // send post request
        $.ajax({
            url: '{$url}',
            type: 'POST',
            data: {
                loungesIds: checked
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
    }

    public function FormatFields($output = 'html')
    {
        parent::FormatFields($output);

        $lounge = $this->loungeRep->find($this->Query->Fields['LoungeID']);
        $state = $lounge->getState();

        if (is_array($state)) {
            $stateMessages = array_filter(array_map(function (array $item) {
                if (
                    !isset($item['date'])
                    || !is_numeric($item['date'])
                    || !isset($item['message'])
                ) {
                    return null;
                }

                return sprintf('<div class="state-message">%s: %s</div>', date('Y-m-d H:i:s', $item['date']), $item['message']) ?? null;
            }, $state));
        }

        if (isset($this->Query->Fields['Sources'])) {
            $sources = explode(', ', $this->Query->Fields['Sources']);
            $deletedSources = explode(', ', $this->Query->Fields['DeletedSources']);
            $sources = array_diff($sources, $deletedSources);

            $this->Query->Fields['Sources'] = sprintf(
                '<a target="_blank" href="list.php?Schema=LoungeSource&LoungeID=%d">%s</a>%s%s%s',
                $this->Query->Fields['LoungeID'],
                implode(', ', $sources),
                $this->Query->Fields['ChangesCount'] > 0
                    ? sprintf('<br>Changes: <span style="color: #9e0505;">%s</span>', $this->Query->Fields['ChangesCount'])
                    : '',
                $deletedSources ? sprintf(' <a style="color: orangered; text-decoration: line-through;" target="_blank" href="list.php?Schema=LoungeSource&LoungeID=%d">%s</a>', $this->Query->Fields['LoungeID'], $this->Query->Fields['DeletedSources']) : '',
                $this->Query->Fields['Duplicates'] ? '<div style="color: orangered;">Remove the duplicates</div>' : ''
            );
        } else {
            $this->Query->Fields['Sources'] = sprintf('<span style="color: orangered;">none</span>');
        }

        if (isset($stateMessages)) {
            $this->Query->Fields['Sources'] .= sprintf('<div class="state-message-container">%s</div>', implode('', $stateMessages));
        }

        // Freeze action
        $action = null;

        foreach ($lounge->getActions() as $loungeAction) {
            if ($loungeAction->getAction() instanceof FreezeAction) {
                $action = $loungeAction;

                break;
            }
        }

        if ($this->Query->Fields['FreezeAction'] == 1 && $action) {
            /** @var FreezeAction $freezeAction */
            $freezeAction = $action->getAction();
            $this->Query->Fields['Sources'] .= sprintf(
                '<div class="freeze-message">Freezed properties: %s, unfreezed at %s</div>',
                implode(', ', $freezeAction->getProps()),
                $action->getDeleteDate() ? $action->getDeleteDate()->format('Y-m-d') : 'never'
            );
        }

        $this->defaultFormat($lounge);

        $warn = [];

        if ($this->OriginalFields['Visible'] == 0) {
            $warn[] = 'lounge not visible';
        }

        if ($this->OriginalFields['IsAvailable'] == 0) {
            $warn[] = 'lounge unavailable';
        }

        $color = count($warn) > 0 ? '#ffe6cc' : '#efffcc';
        $text = count($warn) > 0 ? implode(', ', $warn) : 'Visible';

        $this->Query->Fields['Name'] = sprintf(
            '<span style="background-color: %s;" title="%s">%s</span>',
            $color,
            $text,
            $this->Query->Fields['Name']
        );
    }

    protected function getRowColor(): string
    {
        if ($this->OriginalFields['FreezeAction'] == 1) {
            return '#c8f6fc';
        }

        if ($this->OriginalFields['AttentionRequired'] == 1) {
            return '#fffbdf';
        }

        if ($this->OriginalFields['IsAvailable'] == 0) {
            return '#f3f3f3';
        }

        return '#ffffff';
    }

    protected function getCssStyles(): string
    {
        return <<<CSS
#list-table thead tr td:nth-child(5) {
    min-width: 150px;
}
#list-table thead tr td:nth-child(6) {
    min-width: 150px;
}
#list-table thead tr td:nth-child(12) {
    min-width: 150px;
}
#list-table tr td:nth-child(2) {
    font-size: 9px;
}
.state-message-container {
    padding: 5px 0 0 0;
}
.state-message {
    font-size: 9px;
    border-radius: 5px;
    padding: 2px;
    margin: 2px;
    background-color: #ffffff;
    color: #ff6100;
}
.freeze-message {
    font-size: 9px;
    border-radius: 5px;
    padding: 2px;
    margin: 2px;
    background-color: #ffffff;
    color: #0598b7;
}
CSS;
    }
}
