<?php

namespace AwardWallet\MainBundle\Form\Type;

use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\Form\Model\Profile\PersonalModel;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Routing\Router;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Contracts\Translation\TranslatorInterface;

class ProfilePersonalType extends AbstractType
{
    /**
     * @var Router
     */
    private $router;
    /**
     * @var DataTransformerInterface
     */
    private $dataTransformer;
    /**
     * @var TranslatorInterface
     */
    private $translator;
    /**
     * @var AuthorizationCheckerInterface
     */
    private $authorizationChecker;

    public function __construct(
        Router $router,
        DataTransformerInterface $dataTransformer,
        TranslatorInterface $translator,
        AuthorizationCheckerInterface $authorizationChecker
    ) {
        $this->router = $router;
        $this->dataTransformer = $dataTransformer;
        $this->translator = $translator;
        $this->authorizationChecker = $authorizationChecker;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        /** @var Usr $user */
        $user = $builder->getData();

        $builder->add('login', TextType::class, [
            'label' => 'personal_info.login',
            'help' => /** @Desc("4-30 characters. English letters only. No Spaces.") */ 'personal_info.login.help',
            'required' => true,
            'allow_urls' => true,
        ]);

        $builder->add('PasswordLink', ProfileButtonType::class, [
            'mapped' => false,
            'required' => false,
            'label' => 'personal_info.password',
            'value' => str_repeat('•', 8),
            'link' => $this->router->generate('aw_profile_change_password'),
            'link_label' => 'personal_info.change_password',
        ]);

        $questionsCount = $user->getSecurityQuestions()->count();
        $builder->add('securityQuestions', ProfileButtonType::class, [
            'mapped' => false,
            'required' => false,
            'label' => /** @Desc("Security questions") */ 'personal_info.security_question',
            'value' => $questionsCount > 0 ?
                $this->translator->trans('login.security_question.configured', ['%count%' => $questionsCount, '%questions%' => $questionsCount]) :
                $this->translator->trans('login.security_question.not_configured'),
            'link' => $this->router->generate('aw_profile_question'),
            'link_label' => $this->translator->trans(/** @Desc("Set security questions") */ 'personal_info.set_security_questions'),
        ]);

        $builder->add('firstname', TextType::class, [
            'required' => true,
            'label' => 'personal_info.first_name',
        ]);

        $builder->add('midname', TextType::class, [
            'required' => false,
            'label' => 'personal_info.middle_name',
        ]);

        $builder->add('lastname', TextType::class, [
            'required' => true,
            'label' => 'personal_info.last_name',
        ]);

        $builder->add('EmailLink', ProfileButtonType::class, [
            'mapped' => false,
            'required' => false,
            'label' => 'personal_info.email',
            'value' => $user->getEmail(),
            'link' => $this->router->generate('aw_user_change_email'),
            'link_label' => 'personal_info.email.change',
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
        return 'profile_personal';
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => PersonalModel::class,
            'error_bubbling' => false,
            /** @Ignore */
            'label' => false,
            'translation_domain' => 'messages',
        ]);
    }
}
