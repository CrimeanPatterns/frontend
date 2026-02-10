<?php

namespace AwardWallet\MainBundle\Form\Type\Mobile;

use AwardWallet\MainBundle\Entity\Useragent;
use AwardWallet\MainBundle\Form\Model\FamilyMemberModel;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Translation\TranslatorInterface;

class FamilyMemberType extends AbstractType
{
    /**
     * @var TranslatorInterface
     */
    private $translator;
    /**
     * @var DataTransformerInterface
     */
    private $dataTransformer;

    public function __construct(
        TranslatorInterface $translator,
        DataTransformerInterface $dataTransformer
    ) {
        $this->translator = $translator;
        $this->dataTransformer = $dataTransformer;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        /** @var Useragent $useragent */
        $useragent = $builder->getData();
        $agent = $useragent->getAgentid();
        $login = $agent ? $agent->getLogin() : '';

        $builder
            ->add('firstname', TextType::class, [
                'label' => 'personal_info.first_name',
                'required' => true,
            ])
            ->add('midname', TextType::class, [
                'label' => 'personal_info.middle_name',
                'required' => false,
            ])
            ->add('lastname', TextType::class, [
                'label' => 'personal_info.last_name',
                'required' => true,
            ])
            ->add('alias', TextType::class, [
                'label' => /** @Desc("Email Alias Suffix") */
                    'email.member.alias-suffix',
                'attr' => [
                    'notice' => $this->translator->trans('email.member.alias-notice', ['%userLogin%' => $login]),
                ],
                'allow_urls' => true,
                'required' => true,
            ])
            ->add('email', EmailType::class, [
                'label' => 'personal_info.email',
                'required' => ($agent ? $agent->isBusiness() : false),
            ])
            ->add('sendemails', CheckboxType::class, [
                'label' => 'email.member.send-relevant',
                'required' => false,
            ])
            ->add('notes', TextareaType::class, [
                'label' => 'notes',
                'allow_quotes' => true,
                'required' => false,
            ]);

        $builder->addModelTransformer($this->dataTransformer);
    }

    public function getBlockPrefix(): string
    {
        return 'family_member_mobile';
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => FamilyMemberModel::class,
            'error_bubbling' => false,
            /** @Ignore */
            'label' => false,
            'translation_domain' => 'messages',
        ]);
    }
}
