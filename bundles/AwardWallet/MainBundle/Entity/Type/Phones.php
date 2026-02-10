<?php

namespace AwardWallet\MainBundle\Entity\Type;

use AwardWallet\MainBundle\Entity\Provider;

class Phones
{
    private $providerIds;
    private $phoneRows;
    private $levelRows;
    private $userLevels;

    public function __construct(array $providerIds, array $phoneRows, array $levelRows, array $userLevels)
    {
        $this->providerIds = $providerIds;
        $this->phoneRows = $phoneRows;
        $this->levelRows = $levelRows;
        $this->userLevels = $userLevels;
    }

    /**
     * @param string $country
     * @return array - row from ProviderPhone table ['ProviderID' => 1, 'Country' => 'United States', 'EliteLevel' => 'Gold', 'Phone' => '223-322']
     */
    public function getPhonesByProvider(Provider $provider, $eliteLevel = null, $country = null): array
    {
        $providerId = $provider->getProviderid();

        if (!empty($eliteLevel)) {
            $rank = $this->mapEliteLevelTextToRank($providerId, $eliteLevel);
        } else {
            $rank = null;

            if (!empty($this->userLevels[$providerId])) {
                $rank = $this->userLevels[$providerId];
            }
        }

        $result = array_filter($this->phoneRows, function (array $row) use ($providerId, $country, $rank) {
            return $row['ProviderID'] == $providerId
            && (empty($row['Country']) || $row['Country'] == $country)
            && ($row['Rank'] <= $rank);
        });

        usort($result, function (array $a, array $b) {
            return $this->getSortIndex($b) - $this->getSortIndex($a);
        });

        return $result;
    }

    private function mapEliteLevelTextToRank($providerId, $text)
    {
        if (empty($text)) {
            return null;
        }
        $matches = array_filter($this->levelRows, function (array $row) use ($providerId, $text) {
            return $providerId == $row['ProviderID'] && $text == $row['ValueText'];
        });

        if (empty($matches)) {
            return null;
        }

        return array_shift($matches)['Rank'];
    }

    private function getSortIndex(array $row)
    {
        $result = 0;

        if (!empty($row['Country'])) {
            $result += 10;
        }

        if (!empty($row['EliteLevelID'])) {
            $result += 5;
        }

        if (!empty($row['DefaultPhone'])) {
            $result++;
        }

        return $result;
    }
}
