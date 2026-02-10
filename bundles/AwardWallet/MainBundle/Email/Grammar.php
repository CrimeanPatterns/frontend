<?php

namespace AwardWallet\MainBundle\Email;

class Grammar extends \Swift_Mime_Grammar
{
    /**
     * Get the grammar defined for $name token.
     *
     * @param string $name exactly as written in the RFC
     * @return string
     */
    public function getDefinition($name)
    {
        if ($name == 'addr-spec') {
            return '.*';
        } else {
            return parent::getDefinition($name);
        }
    }
}
