<?php

namespace AwardWallet\MainBundle\Form\Type;

use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\Form\Model\Profile\NotificationModel;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

class NotificationBlogType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('mpNewBlogPosts', CheckboxType::class);
        $builder->add('wpNewBlogPosts', CheckboxType::class);
        $builder->add('emailNewBlogPosts', ChoiceType::class, [
            'choices' => NotificationModel::getEmailBlogPostsChoices(),
        ]);

        $builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event) {
            /** @var Usr $user */
            $user = $event->getData();
            $form = $event->getForm();

            if ($user->isMpDisableAll()) {
                $form->add('mpNewBlogPosts', CheckboxType::class, [
                    'disabled' => true,
                ]);
            }

            if ($user->isWpDisableAll()) {
                $form->add('wpNewBlogPosts', CheckboxType::class, [
                    'disabled' => true,
                ]);
            }
        });
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Usr::class,
            'required' => false,
            'translation_domain' => 'messages',
        ]);
    }

    public function getBlockPrefix(): string
    {
        return 'desktop_blog_notification';
    }
}
