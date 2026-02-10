<?php

namespace AwardWallet\MainBundle\Service\RA;

class RACalendarSchema extends \TBaseSchema
{
    public function __construct()
    {
        parent::__construct();
        $this->ListClass = RACalendarList::class;
    }

    protected function guessFieldOptions(string $field, array $fieldInfo): ?array
    {
        if ($field === "Provider") {
            return SQLToArray("SELECT Code, CONCAT(ShortName,' (',Code,')') AS ShortName FROM Provider WHERE CanCheckRewardAvailability <> 0", "Code", "ShortName");
        }

        if ($field === "StandardItineraryCOS") {
            return [
                'economy' => 'economy',
                'premiumEconomy' => 'premiumEconomy',
                'business' => 'business',
                'firstClass' => 'firstClass',
                'unknown' => 'unknown',
            ];
        }

        return parent::guessFieldOptions($field, $fieldInfo);
    }
}
