<?php

namespace AwardWallet\MainBundle\Worker;

class ProcessControlWrapper
{
    public function exit($input)
    {
        exit($input);
    }
}
