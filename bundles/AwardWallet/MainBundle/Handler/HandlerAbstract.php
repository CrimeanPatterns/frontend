<?php

namespace AwardWallet\MainBundle\Handler;

use AwardWallet\MainBundle\FrameworkExtension\FormErrorHandler;
use AwardWallet\MainBundle\Globals\Localizer\LocalizeService;
use Doctrine\ORM\EntityManager;
use Symfony\Component\Form\Form;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class HandlerAbstract implements HandlerInterface
{
    /** @var TokenStorage */
    protected $tokenStorage;

    /** @var \Doctrine\ORM\EntityManager */
    protected $em;

    /** @var \AwardWallet\MainBundle\Globals\Localizer\LocalizeService */
    protected $localizer;

    /** @var \AwardWallet\MainBundle\FrameworkExtension\FormErrorHandler */
    protected $formErrorHandler;

    public function setTokenStorage(TokenStorageInterface $tokenStorage)
    {
        $this->tokenStorage = $tokenStorage;
    }

    public function setEntityManager(EntityManager $em)
    {
        $this->em = $em;
    }

    public function setLocalizer(LocalizeService $localizer)
    {
        $this->localizer = $localizer;
    }

    public function setFormErrorHandler(FormErrorHandler $eh)
    {
        $this->formErrorHandler = $eh;
    }

    public function getUser()
    {
        if ($this->tokenStorage->getToken() !== null) {
            return $this->tokenStorage->getToken()->getUser();
        }

        return null;
    }

    public function getFormErrors(Form $form, $children = false, $addLabel = true)
    {
        return $this->formErrorHandler->getFormErrors($form, $children, $addLabel);
    }
}
