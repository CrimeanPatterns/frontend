<?php

use AwardWallet\MainBundle\Service\MileValue\MileValueService;
use AwardWallet\MainBundle\Entity\ProviderMileValue;

class TransferStatAdminList extends TBaseList
{
    public function __construct($table, $fields, $defaultSort)
    {
        parent::__construct($table, $fields, $defaultSort);

        if (array_key_exists('PointValue', $fields)) {
            $mileValueService = getSymfonyContainer()->get(MileValueService::class);
            $this->pointValues = ($mileValueService->fetchCombinedMileValueData() + $mileValueService->fetchCombinedHotelValueData());
        } else {
            $this->pointValues = [];
        }
    }

    public function FormatFields($output = 'html')
    {
        $targetProviderId = $this->Query->Fields['TargetProviderID'];
        $html = [];
        if (array_key_exists($targetProviderId, $this->pointValues)) {

            $multiplier = empty($this->Query->Fields['TargetRate']) && empty($this->Query->Fields['SourceRate']) ? '' : round($this->Query->Fields['TargetRate'] / $this->Query->Fields['SourceRate'], 2);
            foreach ($this->pointValues[$targetProviderId]->getAutoValues() as $key => $val) {
                if (!empty($val['value'])) {
                    $rate = '';
                    if (!empty($multiplier)) {
                        $rate = ' * ' . $multiplier . ' = ' . round($multiplier * $val['value'], 2, PHP_ROUND_HALF_UP);
                    }

                    $html[] = $key . ': ' . round($val['value'], 3) .' ' . $rate;
                }
            }
        }

        $this->Query->Fields['PointValue'] = implode('<br>', $html);
        parent::FormatFields($output);
    }
}
