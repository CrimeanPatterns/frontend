<?php

namespace AwardWallet\MainBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

class OwnerMetaType extends AbstractType
{
    /**
     * @var AuthorizationCheckerInterface
     */
    private $authorizationChecker;

    /**
     * OwnerMetaType constructor.
     */
    public function __construct(AuthorizationCheckerInterface $authorizationChecker)
    {
        $this->authorizationChecker = $authorizationChecker;
    }

    /**
     * @return string
     */
    public function getParent()
    {
        if ($this->authorizationChecker->isGranted('SITE_BUSINESS_AREA')) {
            return OwnerAutocompleteType::class;
        } else {
            return OwnerChoiceType::class;
        }
    }
}
