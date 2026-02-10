<?php

namespace AwardWallet\MainBundle\Entity\Repositories;

use AwardWallet\MainBundle\Entity\Review;
use Doctrine\ORM\EntityRepository;

class ReviewRepository extends EntityRepository
{
    public function getProviderRating($providerId)
    {
        $rating = \Cache::getInstance()->get($this->getCacheKey($providerId));

        if ($rating !== false) {
            return $rating;
        }

        $reviews = $this->getEntityManager()->getRepository(Review::class)
            ->findBy(['providerid' => $providerId]);

        $ratingTotalValues = [];

        foreach ($reviews as $i => $item) {
            /** @var Review $item */
            $ratingTotalValues[] = $item->getReviewTotalRating();
        }

        $ratingTotalValues = array_filter($ratingTotalValues);

        $total = (empty($ratingTotalValues)) ? 0 : round(array_sum($ratingTotalValues) / count($ratingTotalValues));
        $result = [
            'rating' => $total,
            'count' => count($ratingTotalValues),
        ];

        \Cache::getInstance()->set($this->getCacheKey($providerId), $result, Review::CACHE_TIME);

        return $result;
    }

    public function getCacheKey(int $providerId): string
    {
        return sprintf(Review::CACHE_PROVIDER_KEY, $providerId);
    }
}
