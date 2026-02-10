<?php

namespace AwardWallet\MainBundle\Service\MileValue;

use AwardWallet\MainBundle\Entity\MileValue;
use AwardWallet\MainBundle\FrameworkExtension\AwTokenStorage;
use AwardWallet\MainBundle\Service\Cache\CacheManager;
use AwardWallet\MainBundle\Service\Cache\Tags;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use JMS\Serializer\SerializerInterface;

class MileValueCustom
{
    private EntityManagerInterface $entityManager;

    private Connection $connection;

    private CacheManager $cacheManager;

    private SerializerInterface $serializer;

    private AwTokenStorage $tokenStorage;

    public function __construct(
        EntityManagerInterface $entityManager,
        CacheManager $cacheManager,
        SerializerInterface $serializer,
        AwTokenStorage $tokenStorage
    ) {
        $this->entityManager = $entityManager;
        $this->connection = $entityManager->getConnection();
        $this->cacheManager = $cacheManager;
        $this->serializer = $serializer;
        $this->tokenStorage = $tokenStorage;
    }

    public function setCustomValue(int $tripId, int $customPick, float $alternativeCost): array
    {
        $mileValue = $this->connection->fetchAssoc('SELECT CustomAlternativeCost, TotalTaxesSpent, TotalMilesSpent, CustomPick, FoundPrices FROM MileValue WHERE TripID = ?', [$tripId], [\PDO::PARAM_INT]);
        $data = [
            'CustomPick' => $customPick,
        ];

        if (MileValue::CUSTOM_PICK_USER_INPUT === $customPick) {
            $data['CustomAlternativeCost'] = $alternativeCost;
            $data['CustomMileValue'] = MileValueCalculator::calc($alternativeCost, (float) $mileValue['TotalTaxesSpent'], (int) $mileValue['TotalMilesSpent']);
        } elseif (MileValue::CUSTOM_PICK_YOUR_AWARD === $customPick) {
            $json = substr($mileValue['FoundPrices'], 3);
            /** @var FoundPrices $foundPrice */
            $foundPrice = $this->serializer->deserialize($json, FoundPrices::class, 'json');

            if (empty($foundPrice->exactMatch->price->price)) {
                throw new \InvalidArgumentException();
            }

            $mileValue['CustomAlternativeCost'] =
            $data['CustomAlternativeCost'] = $foundPrice->exactMatch->price->price;
            $data['CustomMileValue'] = MileValueCalculator::calc($mileValue['CustomAlternativeCost'], (float) $mileValue['TotalTaxesSpent'], (int) $mileValue['TotalMilesSpent']);
        }

        $affected = $this->connection->update('MileValue', $data, ['TripID' => $tripId]);
        $data = array_combine(array_map('lcfirst', array_keys($data)), array_values($data));

        if (0 === $affected && $customPick === (int) $mileValue['CustomPick']) {
            return $data;
        }

        $this->cacheManager->invalidateTags([Tags::getTimelineKey($this->tokenStorage->getBusinessUser()->getId())]);

        return $data;
    }
}
