<?php

namespace AwardWallet\MainBundle\Service\AccountFormHtmlProvider;

use AwardWallet\MainBundle\Entity\Account;
use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\Loyalty\AutologinLinkValidator;
use AwardWallet\MainBundle\Scanner\MailboxFinder;
use Twig\Environment;

class DesktopHtmlRenderer
{
    private Environment $twig;

    private AutologinLinkValidator $autologinLinkValidator;

    private MailboxFinder $mailboxFinder;

    public function __construct(
        Environment $twig,
        AutologinLinkValidator $autologinLinkValidator,
        MailboxFinder $mailboxFinder
    ) {
        $this->twig = $twig;
        $this->autologinLinkValidator = $autologinLinkValidator;
        $this->mailboxFinder = $mailboxFinder;
    }

    public function getBig3HeaderBlock(Account $account): ?string
    {
        $providerLinks = ProviderLinks::get($account->getProviderid()->getCode());

        return $this->twig->load('@Module/AccountFormHtmlProvider/Template/blocks.desktop.html.twig')
            ->renderBlock('header', [
                'account' => $account,
                'providerLinks' => $providerLinks,
                'autologinSignature' => $this->autologinLinkValidator->createSignature($providerLinks['targetUrl'] ?? null),
                'autologinTargetUrl' => $providerLinks['targetUrl'] ?? null,
                'mailboxes' => $this->getMailboxListByUser($account->getUser()),
            ]);
    }

    public function getBig3FooterBlock(Account $account): ?string
    {
        return $this->twig->load('@Module/AccountFormHtmlProvider/Template/blocks.desktop.html.twig')
            ->renderBlock('footer', [
                'account' => $account,
                'providerLinks' => ProviderLinks::get($account->getProviderid()->getCode()),
                'mailboxes' => $this->getMailboxListByUser($account->getUser()),
            ]);
    }

    private function getMailboxListByUser(Usr $user): array
    {
        return $this->mailboxFinder->findAllEmailAddressesByUser($user);
    }
}
