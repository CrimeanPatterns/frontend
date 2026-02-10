<?php

namespace AwardWallet\MainBundle\Entity\Listener;

use AwardWallet\MainBundle\Globals\Localizer\LocalizeService;
use AwardWallet\MainBundle\Service\Billing\ExpirationCalculator;
use Doctrine\ORM\EntityManager;
use Symfony\Contracts\Translation\TranslatorInterface;

class TranslateArgs
{
    /** @var TranslatorInterface */
    protected $translator;

    /** @var EntityManager */
    protected $em;

    /** @var LocalizeService */
    protected $localizer;
    /**
     * @var ExpirationCalculator
     */
    private $expirationCalculator;

    public function __construct(TranslatorInterface $translator, EntityManager $em, LocalizeService $localizer, ExpirationCalculator $expirationCalculator)
    {
        $this->translator = $translator;
        $this->em = $em;
        $this->localizer = $localizer;
        $this->expirationCalculator = $expirationCalculator;
    }

    /**
     * @return TranslatorInterface
     */
    public function getTranslator()
    {
        return $this->translator;
    }

    /**
     * @return EntityManager
     */
    public function getEntityManager()
    {
        return $this->em;
    }

    public function getConnection()
    {
        return $this->em->getConnection();
    }

    public function getRepository($name)
    {
        return $this->em->getRepository($name);
    }

    /**
     * @return LocalizeService
     */
    public function getLocalizer()
    {
        return $this->localizer;
    }

    /**
     * @return ExpirationCalculator
     */
    public function getExpirationCalculator()
    {
        return $this->expirationCalculator;
    }
}
