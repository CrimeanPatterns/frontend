<?php

namespace AwardWallet\MainBundle\Form\Type;

use AwardWallet\MainBundle\Form\Model\Profile\PasswordModel;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Routing\Router;
use Symfony\Contracts\Translation\TranslatorInterface;

class ChangeUserPasswordType extends AbstractType
{
    /**
     * @var Router
     */
    private $router;
    /**
     * @var TranslatorInterface
     */
    private $translator;
    /**
     * @var DataTransformerInterface
     */
    private $dataTransformer;

    public function __construct(Router $router, TranslatorInterface $translator, DataTransformerInterface $dataTransformer)
    {
        $this->router = $router;
        $this->translator = $translator;
        $this->dataTransformer = $dataTransformer;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('pass', RepeatedType::class, [
                'first_name' => 'firstPass',
                'second_name' => 'secondPass',
                'first_options' => [
                    'label' => 'landing.page.restore.password',
                    'attr' => [
                        'autocomplete' => 'off',
                        'autocorrect' => 'off',
                        'autocapitalize' => 'off',
                        'minlength' => 4,
                        'maxlength' => 32,
                    ],
                ],
                'second_options' => [
                    'label' => 'landing.page.restore.password2',
                    'attr' => [
                        'autocomplete' => 'off',
                        'autocorrect' => 'off',
                        'autocapitalize' => 'off',
                        'minlength' => 4,
                        'maxlength' => 32,
                    ],
                ],
                'invalid_message' => 'user.pass_equal',
                'type' => PasswordType::class,
                'options' => [
                    'allow_tags' => true,
                    'allow_quotes' => true,
                    'allow_urls' => true,
                ],
                'required' => true,
                'data' => null,
            ]);

        $builder->addModelTransformer($this->dataTransformer);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'error_bubbling' => false,
            'data_class' => PasswordModel::class,
            'user_login' => null,
            'client_ip' => null,
            'type_old_password' => false,
        ]);
    }

    public function getBlockPrefix()
    {
        return 'desktop_change_user_password';
    }
}
