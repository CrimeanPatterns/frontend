<?php

namespace AwardWallet\Manager;

interface FormTunerInterface
{
    public function tuneForm(\TBaseForm $form): void;
}
