<?php

namespace AwardWallet\MainBundle\FrameworkExtension\Translator;

use JMS\TranslationBundle\Model\Message;
use JMS\TranslationBundle\Translation\TranslationContainerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class TranslatorHijacker implements TranslatorInterface, TranslationContainerInterface
{
    public static $hijackRules = [
        'mobile' => [ // context, level 0
            'messages' => [ // domain, level 1
                // 'account.choice.save.password.with.aw' => ['account.some.key', 'mobile'], // full key specification
                // 'account.choice.save.password.with.aw' => 'account.some.key', // use same domain as original
                // 'account.choice.save.password.with.aw' => [null ,'mobile'], // use same key
                'account.choice.save.password.locally' => [null, 'mobile'],
                'account.notice.store.locally' => [null, 'mobile'],
                'account.notice.store.database' => [null, 'mobile'],
                'last-time-rewards-retrieving-desktop-v2' => 'last-time-rewards-retrieving',
                'last-time-account-retrieving-desktop-v2' => 'last-time-account-retrieving',
                'provider-description-login-and-find' => [null, 'mobile'],
                'account.notice.you.may.store.locally' => [null, 'mobile'],
                'error.award.account.missing-password.text' => [null, 'mobile'],
            ],
        ],
    ];
    /**
     * @var TranslatorInterface
     */
    private $translator;
    /**
     * @var string
     */
    private $context;

    public function __construct(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }

    public function __call($name, $arguments)
    {
        return call_user_func_array([$this->translator, $name], $arguments);
    }

    /**
     * @api
     */
    public function trans($id, array $parameters = [], $domain = null, $locale = null)
    {
        if (isset($this->context)) {
            $hijackedId = $this->hijack($id, $domain);

            if (isset($hijackedId)) {
                if (empty($hijackedId)) {
                    return null;
                } else {
                    [$id, $domain] = $hijackedId;
                }
            }
        }

        return $this->translator->trans(/** @Ignore */ $id, $parameters, $domain, $locale);
    }

    /**
     * @api
     */
    public function transChoice($id, $number, array $parameters = [], $domain = null, $locale = null)
    {
        if (isset($this->context)) {
            $hijackedId = $this->hijack($id, $domain);

            if (isset($hijackedId)) {
                if (empty($hijackedId)) {
                    return null;
                } else {
                    [$id, $domain] = $hijackedId;
                }
            }
        }

        return $this->translator->trans(/** @Ignore */ $id, array_merge($parameters, ['%count%' => $number]), $domain, $locale);
    }

    /**
     * @param string $context
     * @return $this
     */
    public function setContext($context)
    {
        $this->context = $context;

        return $this;
    }

    public function clearContext()
    {
        $this->context = null;
    }

    /**
     * Sets the current locale.
     *
     * @param string $locale The locale
     * @throws \InvalidArgumentException If the locale contains invalid characters
     * @api
     */
    public function setLocale($locale)
    {
        $this->translator->setLocale($locale);
    }

    /**
     * Returns the current locale.
     *
     * @return string The locale
     * @api
     */
    public function getLocale()
    {
        return $this->translator->getLocale();
    }

    /**
     * Returns an array of messages.
     *
     * @return array<Message>
     */
    public static function getTranslationMessages()
    {
        return [
            new Message('account.choice.save.password.locally'),
            (new Message('account.choice.save.password.locally', 'mobile'))
                ->setDesc('Locally on this device'),

            new Message('account.notice.store.locally'),
            (new Message('account.notice.store.locally', 'mobile'))
                ->setDesc('You are choosing to not store your reward password in our encrypted and secure database. AwardWallet will no longer be able to automatically monitor this reward program. Also if you clear your cookies or simply use another device to check your balances this password would have to be re-entered.'),

            new Message('account.notice.you.may.store.locally'),
            (new Message('account.notice.you.may.store.locally', 'mobile'))
                ->setDesc('You may optionally choose to store your award program passwords locally on this device, if you do so and switch devices you will need to re-enter the passwords again'),

            new Message('account.notice.store.database'),
            (new Message('account.notice.store.database', 'mobile'))
                ->setDesc("You are about to delete your reward password from this device and securely store it in an encrypted AwardWallet database. AwardWallet will now be able to monitor this reward program for changes and expirations."),

            (new Message('last-time-rewards-retrieving-desktop-v2'))
                ->setDesc('Last time your rewards info was retrieved from the %displayName% web site on: %span_on%%time-ago%%span_off%.'),
            new Message('last-time-rewards-retrieving'),

            (new Message('last-time-account-retrieving-desktop-v2'))
                ->setDesc('Last time account information was successfully retrieved %span_on%%time-ago%%span_off%.'),
            new Message('last-time-account-retrieving'),

            new Message('provider-description-login-and-find'),
            new Message('provider-return-error-invalid-password'),

            new Message('error.award.account.missing-password.text'),
            (new Message('error.award.account.missing-password.text', 'mobile'))
                ->setDesc('You opted to save the password for this award program locally, this device does not have it stored.'),

            new Message('provider-description-login-and-find'),
            (new Message('provider-description-login-and-find', 'mobile'))
                ->setDesc('Please make sure you can: (1) successfully login to %displayName% website and (2) find the page where all of your balance information is listed.'),
        ];
    }

    /**
     * Try to substitute translation key.
     *
     * @param string $id
     * @param string|null $domain
     * @return array|null resulting tuple(key, domain)
     */
    protected function hijack($id, $domain = null)
    {
        if (!isset($this->context)) {
            return null;
        }

        if (!isset($domain)) {
            $domain = 'messages';
        }

        if (isset(self::$hijackRules[$this->context][$domain]) && array_key_exists($id, self::$hijackRules[$this->context][$domain])) {
            $target = self::$hijackRules[$this->context][$domain][$id];

            if (!is_array($target)) {
                if (isset($target)) {
                    return [$target, null];
                } else {
                    return [];
                }
            } else {
                if (null === $target[0]) {
                    $target[0] = $id;
                }

                return $target;
            }
        }

        return null;
    }
}
