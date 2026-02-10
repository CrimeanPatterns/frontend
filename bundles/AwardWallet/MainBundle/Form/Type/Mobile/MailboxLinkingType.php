<?php

namespace AwardWallet\MainBundle\Form\Type\Mobile;

use AwardWallet\MainBundle\Entity\Account;
use AwardWallet\MainBundle\Scanner\MailboxFinder;
use AwardWallet\MainBundle\Service\AccountFormHtmlProvider\MobileHtmlRenderer;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Translation\TranslatorInterface;

class MailboxLinkingType extends AbstractType
{
    private MailboxFinder $mailboxFinder;

    private TranslatorInterface $translator;

    private MobileHtmlRenderer $mobileHtmlRenderer;

    public function __construct(
        MailboxFinder $mailboxFinder,
        TranslatorInterface $translator,
        MobileHtmlRenderer $mobileHtmlRenderer
    ) {
        $this->mailboxFinder = $mailboxFinder;
        $this->translator = $translator;
        $this->mobileHtmlRenderer = $mobileHtmlRenderer;
    }

    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        /** @var Account $account */
        $account = $options['account'];
        $provider = $account->getProviderid();
        $mailboxes = $this->mailboxFinder->findAllEmailAddressesByUser($account->getUser());
        $count = count($mailboxes);

        $view->vars['mailboxes'] = $mailboxes;
        $view->vars['mailbox_title'] = $this->translator->trans('personal_info.site_settings.email_scanner.mailboxes');

        if ($count > 0) {
            $view->vars['add_mailbox_button'] = $this->translator->trans('link-another-mailbox');
            $view->vars['text'] = $this->translator->trans('account-form.mailbox-sync-ready', [
                '%provider_name%' => $provider->getName(),
                '%program_name%' => $provider->getProgramname(),
                '%short_name%' => $provider->getShortname(),
                '%email_list%' => $this->formatList($mailboxes),
                '%link_on%' => '<a href="#/learn_more">',
                '%link_off%' => '</a>',
            ]);
        } else {
            $view->vars['add_mailbox_button'] = $this->translator->trans('timeline_button_link');
            $view->vars['text'] = $this->translator->trans('account-form.mailbox-instruction', [
                '%provider_name%' => $provider->getName(),
                '%program_name%' => $provider->getProgramname(),
                '%short_name%' => $provider->getShortname(),
                '%link_on%' => '<a href="#/learn_more">',
                '%link_off%' => '</a>',
            ]);
        }

        $view->vars['html'] = $this->mobileHtmlRenderer->getBig3InstructionsBlock($account);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setRequired(['account']);
        $resolver->setDefaults([
            'compound' => false,
            'attr' => ['disableLabel' => true],
            'required' => false,
        ]);
    }

    public function getBlockPrefix()
    {
        return 'mailbox_linking';
    }

    private function formatList(array $mailboxes): string
    {
        $count = count($mailboxes);
        $wrappedMailboxes = array_map(
            fn ($email) => sprintf('<span>%s</span>', $email),
            $mailboxes
        );

        if ($count === 1) {
            return $wrappedMailboxes[0];
        } elseif ($count === 2) {
            return implode($this->translator->trans('email_list_final_separator'), $wrappedMailboxes);
        }

        return implode(
            $this->translator->trans('email_list_separator'),
            array_slice($wrappedMailboxes, 0, -1)
        ) . $this->translator->trans('email_list_final_separator') . end($wrappedMailboxes);
    }
}
