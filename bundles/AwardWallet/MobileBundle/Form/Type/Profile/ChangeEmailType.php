<?php

namespace AwardWallet\MobileBundle\Form\Type\Profile;

use AwardWallet\MainBundle\Form\Model\Profile\EmailModel;
use AwardWallet\MainBundle\Form\Type\PasswordMaskType;
use AwardWallet\MainBundle\Globals\ApiVersioning\ApiVersioningService;
use AwardWallet\MainBundle\Globals\ApiVersioning\MobileVersions;
use AwardWallet\MobileBundle\Form\Type\MobileType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ChangeEmailType extends AbstractType
{
    /**
     * @var DataTransformerInterface
     */
    private $dataTransformer;
    /**
     * @var ApiVersioningService
     */
    private $apiVersioning;

    public function __construct(
        DataTransformerInterface $dataTransformer,
        ApiVersioningService $apiVersioning
    ) {
        $this->dataTransformer = $dataTransformer;
        $this->apiVersioning = $apiVersioning;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->setAttribute('submit_label', 'user.change-email.form.submit');

        if ($this->apiVersioning->notSupports(MobileVersions::LOGIN_OAUTH)) {
            $builder->add(
                'password',
                PasswordMaskType::class,
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

        $builder->add('email', EmailType::class, [
            'label' => 'user.change-email.form.email',
            'help' => 'user.change-email.form.email.note',
            'mapped' => true,
        ]);

        $builder->addModelTransformer($this->dataTransformer);
    }

    public function getBlockPrefix()
    {
        return "mobile_profile_change_email";
    }

    public function getParent()
    {
        return MobileType::class;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => EmailModel::class,
            'user_login' => null,
            'client_ip' => null,
            'reauthRequired' => $this->apiVersioning->notSupports(MobileVersions::LOGIN_OAUTH),
        ]);
    }
}
