<?php

namespace AwardWallet\MainBundle\Form\Type;

use AwardWallet\MainBundle\Entity\Useragent;
use AwardWallet\MainBundle\Form\Model\FamilyMemberModel;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;
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

    /**
     * @var Useragent
     */
    private $userAgent;

    public function __construct(
        TranslatorInterface $translator,
        DataTransformerInterface $dataTransformer
    ) {
        $this->translator = $translator;
        $this->dataTransformer = $dataTransformer;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $this->userAgent = $builder->getData();
        $agent = $this->userAgent->getAgentid();
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
                    'notice' => $this->translator->trans(/** @Desc("This value will be used as a suffix for your AwardWallet mailbox, i.e. %userLogin%.ThisSuffix@email.AwardWallet.com so that you can send email statements and travel reservations to this address") */
                        'email.member.alias-notice', ['%userLogin%' => $login]),
                ],
                'constraints' => [
                    new Assert\Regex(['pattern' => '#^\w+$#ims', 'message' => $this->translator->trans( /** @Desc("Only English letters and digits allowed, start with a letter") */ 'bad_email_alias', [], 'validators')]),
                ],
                'required' => true,
            ])
            ->add('email', EmailType::class, [
                'label' => 'personal_info.email',
                'required' => ($agent ? $agent->isBusiness() : false),
            ])
            ->add('sendemails', CheckboxType::class, [
                'label' => /** @Desc("Send relevant emails (account expirations and changes) to this person") */
                    'email.member.send-relevant',
                'required' => false,
            ])
            ->add('avatar', FileType::class, [
                'mapped' => false,
                'required' => false,
                'label' => 'personal_info.avatar',
                'constraints' => [
                    new Assert\Image([
                        'minWidth' => 64,
                        'minHeight' => 64,
                        'maxSize' => '4M',
                    ]),
                ],
            ])
            ->add('avatarRemove', HiddenType::class, [
                'mapped' => false,
                'required' => false,
                'label' => 'button.delete',
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
        return 'family_member';
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => FamilyMemberModel::class,
            'error_bubbling' => false,
            /** @Ignore */
            'label' => false,
            'translation_domain' => 'messages',
            'validation_groups' => ['Default'],
        ]);
    }
}
