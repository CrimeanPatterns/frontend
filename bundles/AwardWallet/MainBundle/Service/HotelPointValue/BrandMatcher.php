<?php

namespace AwardWallet\MainBundle\Service\HotelPointValue;

use AwardWallet\MainBundle\Entity\HotelBrand;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ObjectRepository;

class BrandMatcher
{
    private array $preparedPatternsCache = [];
    private array $providerBrandsCache = [];
    private ObjectRepository $brandsRepo;
    private int $cacheTime = 0;

    public function __construct(EntityManagerInterface $em)
    {
        $this->brandsRepo = $em->getRepository(HotelBrand::class);
    }

    public function match(string $hotelName, int $providerId): ?HotelBrand
    {
        // will cache brands and prepared patterns for ten minutes
        if ((time() - $this->cacheTime) > 600) {
            $this->preparedPatternsCache = [];
            $this->providerBrandsCache = [];
        }

        /** @var HotelBrand[] $brands */
        $brands = $this->providerBrandsCache[$providerId] ?? null;

        if ($brands === null) {
            $brands = $this->brandsRepo->findBy([
                'provider' => $providerId,
            ], ['matchingPriority' => 'desc']);
            // we store only id and patterns, not entire entity, to survive em->clear()
            // because em->clear is called often, after each account save in loyalty callback worker
            $brands = array_map(fn (HotelBrand $brand) => ['patterns' => $brand->getPatterns(), 'id' => $brand->getId()], $brands);
            $this->providerBrandsCache[$providerId] = $brands;
        }

        foreach ($brands as $brand) {
            if ($this->matchPatterns($brand['patterns'], $hotelName)) {
                return $this->brandsRepo->find($brand['id']);
            }
        }

        return null;
    }

    private function matchPatterns(string $patterns, string $hotelName): bool
    {
        $preparedPatterns = $this->preparedPatternsCache[$patterns] ?? null;

        if ($preparedPatterns === null) {
            $preparedPatterns = PatternLoader::load($patterns);
            $this->preparedPatternsCache[$patterns] = $preparedPatterns;
        }

        return PatternLoader::matchLoaded($hotelName, $preparedPatterns);
    }
}
