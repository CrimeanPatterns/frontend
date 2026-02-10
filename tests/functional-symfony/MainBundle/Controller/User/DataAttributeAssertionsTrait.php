<?php

namespace AwardWallet\Tests\FunctionalSymfony\MainBundle\Controller\User;

trait DataAttributeAssertionsTrait
{
    protected function seeDataAttributeContains(\TestSymfonyGuy $I, string $attribute, $values, string $selector = '#content')
    {
        if (!is_array($values)) {
            $values = [$values];
        }

        $I->seeElement("{$selector}[{$attribute}]");
        $text = $I->grabAttributeFrom($selector, $attribute);

        foreach ($values as $value) {
            $I->assertStringContainsString($value, $text);
        }
    }

    protected function seeDataAttribute(\TestSymfonyGuy $I, string $attribute, string $selector = '#content')
    {
        $I->seeElement("{$selector}[{$attribute}]");
    }

    protected function dontSeeDataAttribute(\TestSymfonyGuy $I, string $attribute, string $selector = '#content')
    {
        $I->dontSeeElement("{$selector}[{$attribute}]");
    }
}
