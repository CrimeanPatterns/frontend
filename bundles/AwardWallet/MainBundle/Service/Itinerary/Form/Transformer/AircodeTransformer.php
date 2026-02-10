<?php

namespace AwardWallet\MainBundle\Service\Itinerary\Form\Transformer;

use AwardWallet\Common\Entity\Aircode;
use Doctrine\ORM\EntityRepository;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\TransformationFailedException;

class AircodeTransformer implements DataTransformerInterface
{
    private EntityRepository $aircodeRepository;

    public function __construct(EntityRepository $aircodeRepository)
    {
        $this->aircodeRepository = $aircodeRepository;
    }

    public function transform($aircode)
    {
        if (null === $aircode) {
            return '';
        }

        return $aircode->getAircode();
    }

    public function reverseTransform($aircodeString)
    {
        if (empty($aircodeString)) {
            return null;
        }

        /** @var Aircode $aircode */
        $aircode = $this->aircodeRepository->findOneBy(['aircode' => $aircodeString]);

        if (null === $aircode) {
            throw new TransformationFailedException("Unknown airport code $aircodeString");
        }

        return $aircode;
    }
}
