<?php

namespace AwardWallet\MainBundle\Form\Transformer;

use AwardWallet\MainBundle\Entity\Owner;
use AwardWallet\MainBundle\Entity\Repositories\OwnerRepository;
use AwardWallet\MainBundle\Entity\Useragent;
use AwardWallet\MainBundle\FrameworkExtension\AwTokenStorageInterface;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\TransformationFailedException;

class OwnerToUserAgentTransformer implements DataTransformerInterface
{
    /**
     * @var AwTokenStorageInterface
     */
    private $tokenStorage;

    /**
     * OwnerToUserAgentTransformer constructor.
     */
    public function __construct(AwTokenStorageInterface $tokenStorage)
    {
        $this->tokenStorage = $tokenStorage;
    }

    /**
     * @param Owner $owner
     * @return Useragent|null The value in the transformed representation
     * @throws TransformationFailedException when the transformation fails
     */
    public function transform($owner)
    {
        if (null === $owner) {
            return null;
        } elseif (!$owner instanceof Owner) {
            throw new TransformationFailedException("Unexpected type");
        }
        $user = $this->tokenStorage->getBusinessUser();

        try {
            return $owner->getUseragentForUser($user);
        } catch (\InvalidArgumentException $e) {
            throw new TransformationFailedException($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * @param Useragent|null $userAgent
     * @return mixed The value in the original representation
     * @throws TransformationFailedException when the transformation fails
     */
    public function reverseTransform($userAgent)
    {
        if (!(null === $userAgent || $userAgent instanceof Useragent)) {
            throw new TransformationFailedException("Unexpected type");
        }
        $user = $this->tokenStorage->getBusinessUser();

        try {
            return OwnerRepository::getByUserAndUseragent($user, $userAgent);
        } catch (\InvalidArgumentException $e) {
            throw new TransformationFailedException($e->getMessage(), $e->getCode(), $e);
        }
    }
}
