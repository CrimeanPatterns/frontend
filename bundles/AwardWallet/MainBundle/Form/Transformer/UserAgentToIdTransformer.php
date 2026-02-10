<?php

namespace AwardWallet\MainBundle\Form\Transformer;

use AwardWallet\MainBundle\Entity\Repositories\UseragentRepository;
use AwardWallet\MainBundle\Entity\Useragent;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\TransformationFailedException;

class UserAgentToIdTransformer implements DataTransformerInterface
{
    /**
     * @var UseragentRepository
     */
    private $userAgentRepository;

    /**
     * UserAgentToIdTransformer constructor.
     */
    public function __construct(UseragentRepository $userAgentRepository)
    {
        $this->userAgentRepository = $userAgentRepository;
    }

    /**
     * @param UserAgent|null $agent
     * @return string
     * @throws TransformationFailedException
     */
    public function transform($agent)
    {
        if (null === $agent) {
            return '';
        }

        if (!$agent instanceof Useragent) {
            throw new TransformationFailedException("Unexpected type");
        }

        return (string) $agent->getUseragentid();
    }

    /**
     * @param string $id
     * @return Useragent
     * @throws TransformationFailedException
     */
    public function reverseTransform($id)
    {
        if ('my' === $id || empty($id)) {
            return null;
        }

        if (!ctype_digit($id)) {
            throw new TransformationFailedException("Unexpected type");
        }
        /** @var Useragent $agent */
        $agent = $this->userAgentRepository->find($id);

        if (null === $agent) {
            throw new TransformationFailedException("Useragent not found");
        }

        return $agent;
    }
}
