<?php

namespace AwardWallet\MainBundle\Form\Type;

use AwardWallet\MainBundle\Form\Transformer\RevealPasswordTransformer;
use Doctrine\ORM\EntityManager;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class RevealPasswordType extends AbstractType
{
    public $em;
    public $translator;
    public $session;
    /**
     * @var AuthorizationCheckerInterface
     */
    private $authorizationChecker;

    public function __construct(EntityManager $em, TranslatorInterface $translatorInterface, Session $session, AuthorizationCheckerInterface $authorizationChecker)
    {
        $this->em = $em;
        $this->translator = $translatorInterface;
        $this->session = $session;
        $this->authorizationChecker = $authorizationChecker;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('password', PasswordMaskType::class);
        $builder->add('changed', HiddenType::class);
        $builder->addViewTransformer(new RevealPasswordTransformer($this->session, $options['accountId']));
    }

    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        /**
         * @Desc("Reveal password")
         */
        $notice = '<a href="#" class="reveal-pass">' . ($this->authorizationChecker->isGranted('ROLE_IMPERSONATED') ? 'Request password' : $this->translator->trans('aw.reveal-password.link.reveal')) . '</a>';

        if (isset($options['attr']['notice'])) {
            $notice = $options['attr']['notice'] . ' | ' . $notice;
        }
        $view->vars['attr']['notice'] = $notice;
        $view->vars['impersonated'] = $this->authorizationChecker->isGranted('ROLE_IMPERSONATED');
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'accountId' => '',
        ]);
    }

    public function getBlockPrefix()
    {
        return 'reveal_password';
    }
}
