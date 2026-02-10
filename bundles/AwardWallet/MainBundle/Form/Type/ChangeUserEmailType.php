<?php

namespace AwardWallet\MainBundle\Form\Type;

use AwardWallet\MainBundle\Form\Model\Profile\EmailModel;
use JMS\TranslationBundle\Model\Message;
use JMS\TranslationBundle\Translation\TranslationContainerInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ChangeUserEmailType extends AbstractType implements TranslationContainerInterface
{
    /**
     * @var DataTransformerInterface
     */
    private $dataTransformer;

    public function __construct(DataTransformerInterface $dataTransformer)
    {
        $this->dataTransformer = $dataTransformer;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('email', EmailType::class, [
            'label' => /** @Desc("New email") */ 'user.change-email.form.email',
            'help' => 'user.change-email.form.email.note',
        ]);

        $builder->addModelTransformer($this->dataTransformer);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'error_bubbling' => false,
            'data_class' => EmailModel::class,
            'reauthRequired' => false,
        ]);
    }

    public function getBlockPrefix()
    {
        return 'desktop_change_user_email';
    }

    /**
     * Returns an array of messages.
     *
     * @return array<Message>
     */
    public static function getTranslationMessages()
    {
        return [
            (new Message('user.change-email.form.email.note'))->setDesc('We are not in business of selling your email to anybody, please provide a valid address here.'),
        ];
    }
}
