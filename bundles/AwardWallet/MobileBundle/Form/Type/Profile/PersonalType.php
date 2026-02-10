<?php

namespace AwardWallet\MobileBundle\Form\Type\Profile;

use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\Form\Model\Profile\PersonalModel;
use AwardWallet\MobileBundle\Form\Type\BlockContainerType;
use AwardWallet\MobileBundle\Form\Type\MobileType;
use AwardWallet\MobileBundle\Form\View\Block\TextProperty;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class PersonalType extends AbstractType
{
    /**
     * @var TranslatorInterface
     */
    private $translator;
    /**
     * @var UrlGeneratorInterface
     */
    private $router;
    /**
     * @var DataTransformerInterface
     */
    private $dataTransformer;

    public function __construct(TranslatorInterface $translator, UrlGeneratorInterface $router, DataTransformerInterface $dataTransformer)
    {
        $this->translator = $translator;
        $this->router = $router;
        $this->dataTransformer = $dataTransformer;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        /** @var Usr $user */
        $user = $builder->getData();
        $tr = $this->translator;
        $router = $this->router;

        $builder->setAttribute('submit_label', $tr->trans('form.button.update'));

        $builder->add('login', TextType::class, [
            'label' => 'personal_info.login',
            'required' => true,
            'attr' => [
                'notice' => 'personal_info.login.help',
            ],
        ]);

        $builder->add('passwordLink', BlockContainerType::class, [
            'blockData' => (new TextProperty($tr->trans('personal_info.password'), str_repeat('•', 8)))
                    ->setFormLink($router->generate('aw_mobile_change_password'))
                    ->setFormTitle($tr->trans('user.change-password.form.title')),
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

        $builder->add('emailLink', BlockContainerType::class, [
            'blockData' => (new TextProperty($tr->trans('login.email'), $options['data']->getEmail()))
                    ->setFormLink($router->generate('aw_mobile_change_email'))
                    ->setFormTitle($tr->trans('user.change-email.form.title')),
        ]);

        $builder->addModelTransformer($this->dataTransformer);
    }

    public function getBlockPrefix()
    {
        return 'mobile_profile_personal';
    }

    public function getParent()
    {
        return MobileType::class;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => PersonalModel::class,
            'translation_domain' => 'messages',
        ]);
    }
}
