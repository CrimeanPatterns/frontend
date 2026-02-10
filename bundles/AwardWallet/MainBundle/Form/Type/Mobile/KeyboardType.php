<?php

namespace AwardWallet\MainBundle\Form\Type\Mobile;

final class KeyboardType
{
    public const DEFAULT = 'default';
    public const NUMBER_PAD = 'number-pad';
    public const DECIMAL_PAD = 'decimal-pad';
    public const NUMERIC = 'numeric';
    public const EMAIL_ADDRESS = 'email-address';
    public const PHONE_PAD = 'phone-pad';
    public const ASCII_CAPABLE = 'ascii-capable';
    public const NUMBERS_AND_PUNCTUATION = 'numbers-and-punctuation';
    public const URL = 'url';
    public const NAME_PHONE_PAD = 'name-phone-pad';
    public const TWITTER = 'twitter';
    public const WEB_SEARCH = 'web-search';
    public const VISIBLE_PASSWORD = 'visible-password';

    private function __construct()
    {
    }
}
