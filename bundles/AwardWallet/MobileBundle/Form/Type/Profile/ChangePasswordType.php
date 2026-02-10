<?php

namespace AwardWallet\MobileBundle\Form\Type\Profile;

use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\Form\Model\Profile\PasswordModel;
use AwardWallet\MainBundle\Form\Type\PasswordMaskType;
use AwardWallet\MainBundle\FrameworkExtension\Translator\TransChoice;
use AwardWallet\MainBundle\Globals\StringUtils;
use AwardWallet\MobileBundle\Form\Type\BlockContainerType;
use AwardWallet\MobileBundle\Form\Type\MobileType;
use AwardWallet\MobileBundle\Form\View\Block\Action;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class ChangePasswordType extends AbstractType
{
    /**
     * @var DataTransformerInterface
     */
    private $transformer;
    /**
     * @var TranslatorInterface
     */
    private $translator;
    /**
     * @var RouterInterface
     */
    private $router;

    public function __construct(DataTransformerInterface $transformer, TranslatorInterface $translator, UrlGeneratorInterface $router)
    {
        $this->transformer = $transformer;
        $this->translator = $translator;
        $this->router = $router;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $tr = $this->translator;
        /** @var Usr $user */
        $user = $options['data'];

        $builder->setAttribute('submit_label', $tr->trans('landing.page.restore.btn.save'));

        if ($options['type_old_password']) {
            $builder->add(
                'oldPassword', PasswordMaskType::class,
                [
                    'label' => 'aw.password',
                    'required' => true,
                    'attr' => [
                        'autocomplete' => 'off',
                        'autocorrect' => 'off',
                        'autocapitalize' => 'off',
                    ],
                    'mapped' => true,
                ]
            );
        }

        if (
            !$options['is_recovery_mode']
            && StringUtils::isNotEmpty($user->getPass())
        ) {
            $builder->add('forgotPassword', BlockContainerType::class, [
                'blockData' => (
                new Action(
                    $tr->trans('login.bottom.forgot'),
                    $tr->trans('landing.dialog.forgot.success_header'),
                    $this->router->generate('aw_mobile_forgot_action'),
                    'POST'
                )
                ),
            ]);
        }

        $builder->add('pass', RepeatedType::class,
            [
                'type' => PasswordType::class,
                'first_name' => 'first',
                'second_name' => 'second',
                'first_options' => [
                    'label' => 'landing.page.restore.password',
                    'allow_urls' => true,
                    'attr' => array_merge(
                        $attr = [
                            'autocomplete' => 'off',
                            'autocorrect' => 'off',
                            'autocapitalize' => 'off',
                            'trim' => true,
                            'notice' => new TransChoice('user.password.minlength', 8, ['%count%' => 8], 'validators'),
                        ],
                        ['policy' => true]
                    ),
                ],
                'second_options' => [
                    'label' => 'landing.page.restore.password2',
                    'allow_urls' => true,
                    'attr' => $attr,
                ],
                'invalid_message' => 'user.pass_equal',
                'required' => true,
                'mapped' => true,
                'data' => '',
                'error_bubbling' => false,
            ]
        );

        $builder->add('login', HiddenType::class, [
            'data' => $user->getLogin(),
            'mapped' => true,
        ]);

        $builder->add('email', HiddenType::class, [
            'data' => $user->getEmail(),
            'mapped' => true,
        ]);

        $builder->addModelTransformer($this->transformer);
    }

    public function getParent()
    {
        return MobileType::class;
    }

    public function getBlockPrefix()
    {
        return "mobile_profile_change_password";
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => PasswordModel::class,
            'user_login' => null,
            'client_ip' => null,
            'type_old_password' => false,
            'is_recovery_mode' => false,
        ]);
    }
}
