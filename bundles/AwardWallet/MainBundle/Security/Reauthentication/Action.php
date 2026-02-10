<?php

namespace AwardWallet\MainBundle\Security\Reauthentication;

class Action
{
    public static function getChangeEmailAction(): string
    {
        return 'change-email';
    }

    public static function getChangePasswordAction(): string
    {
        return 'change-pass';
    }

    public static function getRevealAccountPasswordAction(int $accountId): string
    {
        return sprintf('reveal-account-%d-password', $accountId);
    }

    public static function getDeleteAccountAction(): string
    {
        return 'delete-aw-account';
    }

    public static function get2FactSetupAction(): string
    {
        return '2fact-setup';
    }

    public static function get2FactCancelAction(): string
    {
        return '2fact-cancel';
    }

    public static function getEnableAutoLoginAction(int $accountId): string
    {
        return sprintf('enable-account-%d-autologin', $accountId);
    }

    public static function getBackupPasswordsAction(): string
    {
        return 'backup-passwords';
    }

    public static function validateAction(string $action): bool
    {
        return (bool) preg_match(
            '/^(' .
            'change-email' .
            '|change-pass' .
            '|reveal-account-\\d+-password' .
            '|enable-account-\\d+-autologin' .
            '|delete-aw-account' .
            '|2fact-(setup|cancel)' .
            '|backup-passwords' .
            ')$/ims',
            $action
        );
    }
}
