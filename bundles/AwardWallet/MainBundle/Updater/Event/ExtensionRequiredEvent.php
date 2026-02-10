<?php

namespace AwardWallet\MainBundle\Updater\Event;

use JMS\TranslationBundle\Annotation\Ignore;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Class ExtensionRequiredEvent
 * require extension.
 */
class ExtensionRequiredEvent extends AbstractEvent implements TranslationEventInterface
{
    public int $version = 2;
    public ?string $buttonName = null;
    public ?string $buttonLink = null;

    public function __construct(int $accountId, int $version = 2, ?string $buttonLink = null, ?string $buttonName = null)
    {
        parent::__construct($accountId, 'extension_required');
        $this->version = $version;
        $this->buttonLink = $buttonLink;
        $this->buttonName = $buttonName;
    }

    public function translate(TranslatorInterface $translator)
    {
        if ($this->buttonName !== null) {
            $this->buttonName = $translator->trans(/** @Ignore */ $this->buttonName, [], 'messages');
        }
    }
}
