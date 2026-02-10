<?php

namespace AwardWallet\MainBundle\Service\CreditCards\Schema;

use AwardWallet\MainBundle\Globals\StringUtils;

use function AwardWallet\MainBundle\Globals\Utils\iterFluent\it;

class MerchantGroupList extends \TBaseList
{
    public function __construct(string $table,
        array $fields
    ) {
        parent::__construct($table, $fields);

        $this->SQL = "
            select
                mg.MerchantGroupID, 
                mg.Name, 
                mg.ClickURL, 
                (                
                    select
                    JSON_ARRAYAGG(
                        JSON_OBJECT(
                            'ID', mp.MerchantPatternID,
                            'Name', mp.Name,
                            'Pattern', mp.Patterns,
                            'MerchantsCount', (
                                select count(*)
                                from Merchant m
                                where m.MerchantPatternID = mp.MerchantPatternID
                            )
                        )
                    ) as Patterns
                    from MerchantPatternGroup mpg
                    join MerchantPattern mp on 
                        mpg.MerchantPatternID = mp.MerchantPatternID
                    where
                        mg.MerchantGroupID = mpg.MerchantGroupID
                ) as Patterns,
                (
                    select
                    JSON_ARRAYAGG(
                        if(
                            ccmg.EndDate is null or ccmg.EndDate > now(),
                            JSON_OBJECT(
                                'ID', ccmg.CreditCardID,
                                'Name', cc.Name,
                                'Multiplier', ccmg.Multiplier
                            ),
                            null
                        ) 
                    ) as BonusEarns
                    from CreditCardMerchantGroup ccmg
                    join CreditCard cc on 
                        ccmg.CreditCardID = cc.CreditCardID
                    where
                        mg.MerchantGroupID = ccmg.MerchantGroupID
                ) as BonusEarns
            from MerchantGroup mg
            where 1=1
                [Filters]
            group by mg.MerchantGroupID
        ";
    }

    public function FormatFields($output = "html")
    {
        parent::FormatFields($output);

        if ($output === "html") {
            $this->Query->Fields['Patterns'] = self::addUl(
                it(\json_decode($this->OriginalFields['Patterns'], true) ?? [])
                ->usort(fn (array $pattern1, array $pattern2) => ($pattern1['ID'] <=> $pattern2['ID']))
                ->map(fn (array $pattern) =>
                    "<li>
                        <a href='edit.php?Schema=MerchantPattern&ID={$pattern['ID']}' target='blank' title='{$pattern['Pattern']}'>{$pattern['Name']}</a>
                    "
                    . (
                        $pattern['MerchantsCount'] ?
                            "<a href='list.php?Schema=Merchant&MerchantPatternID={$pattern['ID']}'>[{$pattern['MerchantsCount']}]</a>" :
                            ""
                    )
                    . "</li>"
                )
                ->joinToString("\n"));
            [$inactiveCC, $activeCC] =
                it(\json_decode($this->OriginalFields['BonusEarns'], true) ?? [])
                ->partition(fn ($bonus) => \is_null($bonus));
            $activeCCHtml = $activeCC
                ->usort(fn (array $bonus1, array $bonus2) =>
                    ($bonus2['Multiplier'] <=> $bonus1['Multiplier']) ?:
                        ($bonus1['ID'] <=> $bonus2['ID'])
                )
                ->collect()
                ->map(fn (array $bonus) => "<li><a href=\"edit.php?Schema=CreditCard&ID={$bonus['ID']}\" target=\"blank\">{$bonus['Name']} - " . number_format($bonus['Multiplier'], 2, '.', '') . " </a></li>")
                ->joinToString("\n");
            $inactiveCCCount = $inactiveCC->count();

            $this->Query->Fields['BonusEarns'] =
                self::addUl($activeCCHtml)
                . ($inactiveCCCount ?
                        '<p style="color: #b3b1bb">' . \sprintf(
                            StringUtils::isNotEmpty($activeCCHtml) ?
                                "<br/> and %d inactive" :
                                "%d inactive",
                            $inactiveCCCount
                        ) . "</p>" :
                    '');

            if ($this->Query->Fields['ClickURL'] !== null) {
                $this->Query->Fields['Name'] = "<a href=\"{$this->Query->Fields['ClickURL']}\" target=\"_blank\">{$this->Query->Fields['Name']}</a>";
            }
        }
    }

    public function GetEditLinks()
    {
        $result = parent::GetEditLinks();

        $row = $this->OriginalFields;

        $result .= " [<a target='_blank' href='list.php?Schema=CreditCardMerchantGroup&MerchantGroupID={$row['MerchantGroupID']}'>Credit Cards</a>]";
        $result .= " [<a target='blank' href='list.php?Schema=MerchantPattern&Groups={$row['MerchantGroupID']}'>Patterns</aa>]";

        return $result;
    }

    private static function addUl(string $html): string
    {
        if ($html === '') {
            return $html;
        }

        return "<ul>" . $html . "</ul>";
    }
}
