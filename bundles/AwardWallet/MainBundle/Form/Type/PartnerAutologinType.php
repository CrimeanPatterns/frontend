<?php

namespace AwardWallet\MainBundle\Form\Type;

use AwardWallet\MainBundle\Entity\Provider;
use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\Globals\GlobalVariables;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class PartnerAutologinType extends AbstractType
{
    /**
     * @var TranslatorInterface
     */
    protected $translator;
    /**
     * @var GlobalVariables
     */
    protected $globals;
    /**
     * @var Usr
     */
    protected $user;
    /**
     * @var Provider
     */
    protected $provider;

    public function __construct(Provider $provider, TranslatorInterface $translator, GlobalVariables $globals, Usr $user)
    {
        $this->provider = $provider;
        $this->translator = $translator;
        $this->globals = $globals;
        $this->user = $user;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $checker = $this->globals->getAccountChecker($this->provider);
        $checker->GetPartnerFormBuilder($builder, $options, $this->user);
    }

    public function getBlockPrefix()
    {
        return 'partner_autologin';
    }
}
