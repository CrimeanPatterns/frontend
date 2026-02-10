<?php

namespace AwardWallet\MainBundle\Service\Lounge\Action;

use JMS\Serializer\Annotation as Serializer;

/**
 * @Serializer\Discriminator(
 *     field = "type",
 *     map = {
 *         "freeze": "AwardWallet\MainBundle\Service\Lounge\Action\FreezeAction"
 *     }
 * )
 */
abstract class AbstractAction
{
}
