<?php

namespace AwardWallet\MainBundle\Form\Model\Profile;

use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\Validator\Constraints as AwAssert;
use AwardWallet\MobileBundle\Form\Model\AbstractEntityAwareModel;
use Symfony\Component\Validator\Constraints\Callback;

/**
 * Class BusinessModel.
 *
 * @AwAssert\AndX(constraints = {
 *     @AwAssert\ConstraintReference(
 *         sourceClass = Usr::class,
 *         excludedConstraints = {
 *             Callback::class
 *         },
 *         excludedProperties = {
 *             "entity",
 *             "id",
 *             "ip"
 *         }
 *     )
 * })
 * @property Usr $entity
 */
class BusinessModel extends AbstractEntityAwareModel
{
    /**
     * @var string
     */
    private $company;
    /**
     * @var string
     */
    private $login;

    public function getCompany(): string
    {
        return $this->company;
    }

    public function setCompany(string $company): BusinessModel
    {
        $this->company = $company;

        return $this;
    }

    public function getLogin(): string
    {
        return $this->login;
    }

    public function setLogin(string $login): BusinessModel
    {
        $this->login = $login;

        return $this;
    }
}
