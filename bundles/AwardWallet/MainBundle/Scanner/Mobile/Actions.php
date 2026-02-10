<?php

namespace AwardWallet\MainBundle\Scanner\Mobile;

use AwardWallet\MainBundle\Service\EmailParsing\Client\Model\Mailbox;

use function AwardWallet\MainBundle\Globals\Utils\f\call;
use function AwardWallet\MainBundle\Globals\Utils\iterFluent\it;

class Actions
{
    public const UPDATE = 'update';
    public const DELETE = 'delete';

    /**
     * @return string[]
     */
    public function getActions(Mailbox $mailbox): array
    {
        return
            it(call(function () use ($mailbox) {
                if ($mailbox->getErrorCode() === Mailbox::ERROR_CODE_AUTHENTICATION) {
                    yield self::UPDATE;
                }

                yield self::DELETE;
            }))
            ->toArray();
    }
}
