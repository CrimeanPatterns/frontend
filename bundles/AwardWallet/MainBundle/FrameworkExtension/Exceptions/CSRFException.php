<?php

namespace AwardWallet\MainBundle\FrameworkExtension\Exceptions;

use Symfony\Component\Security\Core\Exception\AccessDeniedException;

class CSRFException extends AccessDeniedException
{
}
