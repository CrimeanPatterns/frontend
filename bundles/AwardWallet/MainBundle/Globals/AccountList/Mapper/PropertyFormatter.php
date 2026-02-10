<?php

namespace AwardWallet\MainBundle\Globals\AccountList\Mapper;

use AwardWallet\MainBundle\Entity\Providerproperty;
use AwardWallet\MainBundle\Globals\Localizer\LocalizeService;

class PropertyFormatter
{
    private LocalizeService $localizer;

    public function __construct(LocalizeService $localizer)
    {
        $this->localizer = $localizer;
    }

    public function format($value, ?int $type, ?string $locale)
    {
        switch ($type) {
            case Providerproperty::TYPE_NUMBER:
                return $this->localizer->formatNumber(filterBalance($value, true), null, $locale);

            case Providerproperty::TYPE_DATE:
                if (is_numeric($value)) {
                    if (preg_match('/^\d{10}$/ims', $value)) {
                        return $this->localizer->formatDate(new \DateTime('@' . $value), 'medium', $locale);
                    }

                    return $value;
                } else {
                    $date = date_create($value);

                    if ($date instanceof \DateTime) {
                        return $this->localizer->formatDate($date, 'medium', $locale);
                    }

                    return $value;
                }
        }

        return $value;
    }
}
