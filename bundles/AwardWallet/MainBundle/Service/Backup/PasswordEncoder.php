<?php

namespace AwardWallet\MainBundle\Service\Backup;

use AwardWallet\MainBundle\Entity\Usr;
use Symfony\Component\Security\Core\Encoder\EncoderFactoryInterface;

class PasswordEncoder
{
    private EncoderFactoryInterface $encoderFactory;

    public function __construct(EncoderFactoryInterface $encoderFactory)
    {
        $this->encoderFactory = $encoderFactory;
    }

    public function encodePassword($password): string
    {
        $encoder = $this->encoderFactory->getEncoder(new Usr());

        return $encoder->encodePassword($password, null);
    }
}
