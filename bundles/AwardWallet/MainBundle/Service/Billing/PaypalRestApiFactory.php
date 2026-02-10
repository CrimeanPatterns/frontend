<?php

namespace AwardWallet\MainBundle\Service\Billing;

use AwardWallet\Common\PasswordCrypt\PasswordDecryptor;
use AwardWallet\MainBundle\Entity\Usr;
use Psr\Log\LoggerInterface;

class PaypalRestApiFactory
{
    /**
     * @var bool
     */
    private $live;
    /**
     * @var LoggerInterface
     */
    private $logger;

    private PasswordDecryptor $passwordDecryptor;

    public function __construct(bool $live, LoggerInterface $logger, PasswordDecryptor $passwordDecryptor)
    {
        $this->live = $live;
        $this->logger = $logger;
        $this->passwordDecryptor = $passwordDecryptor;
    }

    public function getByBooker(Usr $booker)
    {
        return new PaypalRestApi($this->passwordDecryptor->decrypt($booker->getBookerInfo()->getPayPalClientId()), $this->passwordDecryptor->decrypt($booker->getBookerInfo()->getPayPalSecret()), $this->live, $this->logger);
    }
}
