<?php

namespace AwardWallet\MainBundle\Updater\Event;

use Symfony\Contracts\Translation\TranslatorInterface;

class LocalPasswordEvent extends AbstractEvent implements TranslationEventInterface
{
    public $label;

    public $displayName;

    private $login;

    private $userName;

    public function __construct($accountId, $displayName, $login, $userName)
    {
        parent::__construct($accountId, 'local_password');
        $this->label = '';
        $this->displayName = $displayName;
        $this->login = $login;
        $this->userName = $userName;
    }

    public function translate(TranslatorInterface $translator)
    {
        $this->label = $translator->trans('account.label.enter.password.for.account', [
            '%Account%' => $this->login,
            '%UserName%' => $this->userName,
        ]);
    }
}
