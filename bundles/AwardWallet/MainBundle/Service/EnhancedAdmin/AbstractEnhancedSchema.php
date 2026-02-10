<?php

namespace AwardWallet\MainBundle\Service\EnhancedAdmin;

/**
 * use only for new create/edit actions.
 */
abstract class AbstractEnhancedSchema extends \TBaseSchema
{
    public const BACK_URL_SESSION_KEY = 'EnhancedAdminBackUrl';

    public function isEnhancedEditAction(): bool
    {
        return true;
    }
}
