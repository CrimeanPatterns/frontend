<?php

namespace AwardWallet\MobileBundle\Form\Type;

use AwardWallet\MainBundle\Form\Model\AddAgentModel;
use AwardWallet\MainBundle\Form\Transformer\AddAgentTransformer;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class AddAgentType extends AbstractType
{
    /**
     * @var DataTransformerInterface
     */
    private $dataTransformer;

    public function __construct(AddAgentTransformer $dataTransformer)
    {
        $this->dataTransformer = $dataTransformer;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('firstname', TextType::class, [
            'label' => 'login.first',
            'required' => true,
        ]);

        $builder->add('lastname', TextType::class, [
            'label' => 'login.name',
            'required' => true,
        ]);

        $builder->add('email', EmailType::class, [
            'label' => 'login.email',
            'required' => false,
        ]);

        $builder->add('invite', CheckboxType::class, [
            'label' => 'agents.invite.label',
            'required' => false,
        ]);

        $builder->addModelTransformer($this->dataTransformer);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => AddAgentModel::class,
            'error_bubbling' => false,
        ]);
    }

    public function getBlockPrefix()
    {
        return 'mobile_add_agent';
    }
}
