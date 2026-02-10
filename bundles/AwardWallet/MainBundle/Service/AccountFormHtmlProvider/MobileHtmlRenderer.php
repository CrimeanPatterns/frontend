<?php

namespace AwardWallet\MainBundle\Service\AccountFormHtmlProvider;

use AwardWallet\MainBundle\Entity\Account;
use Twig\Environment;

class MobileHtmlRenderer
{
    private Environment $twig;

    public function __construct(Environment $twig)
    {
        $this->twig = $twig;
    }

    public function getBig3InstructionsBlock(Account $account): ?string
    {
        return $this->twig->load('@Module/AccountFormHtmlProvider/Template/blocks.mobile.html.twig')
            ->renderBlock('instructions', [
                'account' => $account,
                'providerLinks' => ProviderLinks::get($account->getProviderid()->getCode()),
            ]);
    }

    public function getBig3StatementNotice(Account $account): ?string
    {
        return $this->twig->load('@Module/AccountFormHtmlProvider/Template/blocks.mobile.html.twig')
            ->renderBlock('statement_notice', [
                'account' => $account,
            ]);
    }

    public function getProviderWarning(string $providerCode): string
    {
        return $this->twig->load('@Module/AccountFormHtmlProvider/Template/blocks.mobile.html.twig')
            ->renderBlock('warning_provider', [
                'providerCode' => $providerCode,
            ]);
    }
}
