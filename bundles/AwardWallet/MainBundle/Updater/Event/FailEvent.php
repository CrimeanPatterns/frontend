<?php

namespace AwardWallet\MainBundle\Updater\Event;

use JMS\TranslationBundle\Model\Message;
use JMS\TranslationBundle\Translation\TranslationContainerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Class FailEvent
 * updater-side fail messages.
 */
class FailEvent extends AbstractEvent implements TranslationEventInterface, TranslationContainerInterface
{
    public const TYPE = 'fail';

    public $message;

    public function __construct($accountId, $message)
    {
        parent::__construct($accountId, self::TYPE);
        $this->message = $message;
    }

    public function translate(TranslatorInterface $translator)
    {
        $key = $this->message;
        $this->message = $translator->trans(/** @Ignore */ $this->message, [], 'messages');

        // don't want to translate messages again
        if ($key === 'updater2.messages.fail.extension' || $key === 'updater2.messages.fail.extension_v3_upgrade_required') {
            $this->message = strip_tags($this->message);
        }
    }

    /**
     * Returns an array of messages.
     *
     * @return array<Message>
     */
    public static function getTranslationMessages()
    {
        return [
            (new Message('updater2.messages.fail', 'messages'))->setDesc('Server communication error'),
            (new Message('updater2.messages.fail.not-found', 'messages'))->setDesc('Account not found'),
            (new Message('updater2.messages.fail.access', 'messages'))->setDesc('Account is not accessible'),
            (new Message('updater2.messages.fail.cannot-check', 'messages'))->setDesc('Account cannot be updated'),
            (new Message('updater2.messages.fail.lockout', 'messages'))->setDesc('Account is locked out'),
            (new Message('updater2.messages.fail.extension-disabled', 'messages'))->setDesc('Browser extension is disabled'),
            (new Message('updater2.messages.fail.extension-browser', 'messages'))->setDesc('Browser extension is missing'),
            (new Message('updater2.messages.fail.extension', 'messages'))->setDesc('Browser extension is missing'),
            (new Message('updater2.messages.fail.extension_v3_upgrade_required', 'messages'))->setDesc('Browser extension upgrade required'),
            (new Message('updater2.messages.fail.client-timeout', 'messages'))->setDesc('Account update timed out'),
            (new Message('updater2.messages.fail.server-timeout', 'messages'))->setDesc('Account update timed out'),
            (new Message('updater2.messages.fail.updater', 'messages'))->setDesc('Unknown error'),
            (new Message('updater2.messages.fail.password-missing', 'messages'))->setDesc('Password is missing'),
            (new Message('updater2.messages.fail.capital-unauth', 'messages'))->setDesc('Please authenticate yourself via the "Connect with Capital One" button.'),
            (new Message('updater2.messages.fail.bankofamerica-unauth', 'messages'))->setDesc('Please authenticate yourself via the "Connect with Bank of America" button.'),
            (new Message('updater2.messages.fail.wsdl.invalid-password', 'messages'))->setDesc('Invalid password'),
            (new Message('updater2.messages.fail.wsdl.engine-error', 'messages'))->setDesc('Unknown error'),
            (new Message('updater2.messages.fail.wsdl.timeout', 'messages'))->setDesc('Account update timed out'),
            (new Message('install', 'messages'))->setDesc('Install'),
        ];
    }
}
