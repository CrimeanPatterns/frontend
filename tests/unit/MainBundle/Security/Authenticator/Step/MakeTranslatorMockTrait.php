<?php

namespace AwardWallet\Tests\Unit\MainBundle\Security\Authenticator\Step;

use Prophecy\Prophecy\ObjectProphecy;
use Symfony\Contracts\Translation\TranslatorInterface;

trait MakeTranslatorMockTrait
{
    public function makeTranslatorMock(array $translations = []): TranslatorInterface
    {
        return $this->makeTranslatorProphecy($translations)->reveal();
    }

    public function makeTranslatorProphecy(array $translations = []): ObjectProphecy
    {
        /** @var TranslatorInterface|ObjectProphecy $translator */
        $translator = $this->prophesize(TranslatorInterface::class);

        foreach ($translations as $id => $translation) {
            $translator
                ->trans($id)
                ->willReturn($translation)
                ->shouldBeCalled();
        }

        return $translator;
    }
}
