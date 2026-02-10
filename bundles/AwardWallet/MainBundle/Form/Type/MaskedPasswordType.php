<?php

namespace AwardWallet\MainBundle\Form\Type;

use AwardWallet\MainBundle\Form\Transformer\MaskedPasswordTransformer;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class MaskedPasswordType extends AbstractType
{
    /**
     * @var TranslatorInterface
     */
    private $translator;
    /**
     * @var AuthorizationCheckerInterface
     */
    private $authorizationChecker;

    public function __construct(TranslatorInterface $translator, AuthorizationCheckerInterface $authorizationChecker)
    {
        $this->translator = $translator;
        $this->authorizationChecker = $authorizationChecker;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('password', PasswordMaskType::class, [
            'allow_quotes' => true,
            'allow_tags' => true,
            'allow_urls' => true,
            'attr' => [
                // next hidden input contains unmodified password
                'onfocus' => "var i = $(this); if(i.val() == i.next().val()) i.val('')",
                'onblur' => "var i = $(this); if(i.val() == '') i.val(i.next().val())",
            ],
        ]);
        $builder->add('unmodified', HiddenType::class);
        $builder->addViewTransformer(new MaskedPasswordTransformer());
    }

    public function getBlockPrefix()
    {
        return 'masked_password';
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'compound' => true,
            'original_password' => null,
        ]);
    }

    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        $view->vars['impersonated'] = $this->authorizationChecker->isGranted('ROLE_IMPERSONATED');
        /**
         * @Desc("Reveal password")
         */
        $notice = '<a href="#" class="reveal-pass">' . ($view->vars['impersonated'] ? 'Request password' : $this->translator->trans('aw.reveal-password.link.reveal')) . '</a>';

        if (isset($options['attr']['notice'])) {
            $notice = $options['attr']['notice'] . ' | ' . $notice;
        }
        $view->vars['attr']['notice'] = $notice;
    }
}
