<?php

namespace AwardWallet\MainBundle\Form\Type;

use AwardWallet\MainBundle\Form\Model\Profile\BusinessModel;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Routing\Router;
use Symfony\Component\Validator\Constraints as Assert;

class BusinessProfileType extends AbstractType
{
    /**
     * @var Router
     */
    private $router;
    /**
     * @var DataTransformerInterface
     */
    private $dataTransformer;

    public function __construct(Router $router, DataTransformerInterface $dataTransformer)
    {
        $this->router = $router;
        $this->dataTransformer = $dataTransformer;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('company', TextType::class, [
            'label' => 'business.company-name',
            'help' => /** @Desc("4-30 characters. English letters only. No Spaces.") */ 'personal_info.login.help',
            'required' => true,
        ]);

        $builder->add('login', TextType::class, [
            'label' => 'business.email-alias',
            'help' => /** @Desc("4-30 characters. English letters only. No Spaces.") */ 'personal_info.login.help',
            'required' => true,
        ]);

        $builder->add('Avatar', FileType::class, [
            'mapped' => false,
            'required' => false,
            'label' => 'personal_info.avatar',
            'constraints' => [
                new Assert\Image([
                    'maxSize' => '4M',
                    'minHeight' => 64,
                    'minWidth' => 64,
                ]),
            ],
        ]);

        $builder->add('AvatarDelete', HiddenType::class, [
            'mapped' => false,
            'required' => false,
            'label' => 'button.delete',
        ]);

        $builder->addModelTransformer($this->dataTransformer);
    }

    public function getBlockPrefix()
    {
        return 'profile_business';
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => BusinessModel::class,
            'error_bubbling' => false,
            /** @Ignore */
            'label' => false,
            'translation_domain' => 'messages',
        ]);
    }
}
