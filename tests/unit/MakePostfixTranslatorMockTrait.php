<?php

namespace AwardWallet\Tests\Unit;

use Prophecy\Argument;
use Symfony\Component\Translation\IdentityTranslator;
use Symfony\Contracts\Translation\TranslatorInterface;

trait MakePostfixTranslatorMockTrait
{
    protected function makePostfixTranslatorMockTrait(): TranslatorInterface
    {
        $translator = $this->prophesize(IdentityTranslator::class);
        $translator
            ->trans(Argument::cetera())
            ->will(function ($id) {
                return $id[0] . '###translated';
            });

        return $translator->reveal();
    }
}
