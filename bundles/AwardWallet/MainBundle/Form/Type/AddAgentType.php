<?php

namespace AwardWallet\MainBundle\Form\Type;

use AwardWallet\MainBundle\Form\Model\AddAgentModel;
use AwardWallet\MainBundle\Form\Transformer\AddAgentTransformer;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Security\Core\Authorization\AuthorizationChecker;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class AddAgentType extends AbstractType
{
    /**
     * @var DataTransformerInterface
     */
    private $dataTransformer;
    /**
     * @var AuthorizationChecker
     */
    private $authorizationChecker;

    private $translator;

    public function __construct(AddAgentTransformer $dataTransformer, AuthorizationCheckerInterface $authorizationChecker, TranslatorInterface $translator)
    {
        $this->dataTransformer = $dataTransformer;
        $this->authorizationChecker = $authorizationChecker;
        $this->translator = $translator;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $isBusiness = $this->authorizationChecker->isGranted('SITE_BUSINESS_AREA');

        $builder->add('firstname', TextType::class, ['label' => 'login.first', 'required' => true]);
        $builder->add('lastname', TextType::class, ['label' => 'login.name', 'required' => true]);
        $builder->add('email', EmailType::class, [
            'label' => 'login.email',
            'required' => $isBusiness,
        ]
        );
        $builder->add('invite', CheckboxType::class, [
            /**
             * @Desc("Also invite this person to register and connect with my account")
             */
            'label' => 'agents.invite.label',
            'required' => false,
            'attr' => ['data-business' => $isBusiness],
        ]);
        $builder->addModelTransformer($this->dataTransformer);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'error_bubbling' => false,
            'data_class' => AddAgentModel::class,
        ]);
    }

    public function getBlockPrefix()
    {
        return 'desktop_add_agent';
    }
}
