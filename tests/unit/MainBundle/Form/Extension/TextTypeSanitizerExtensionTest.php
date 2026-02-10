<?php

namespace AwardWallet\Tests\Unit\MainBundle\Form\Extension;

use AwardWallet\Tests\Unit\BaseContainerTest;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\UrlType;
use Symfony\Component\Form\FormFactoryInterface;

/**
 * @group frontend-unit
 */
class TextTypeSanitizerExtensionTest extends BaseContainerTest
{
    private ?FormFactoryInterface $formFactory;

    public function _before()
    {
        parent::_before();

        $this->formFactory = $this->container->get(FormFactoryInterface::class);
    }

    public function _after()
    {
        $this->formFactory = null;

        parent::_after();
    }

    /**
     * @dataProvider allowUrlsOptionDataProvider
     */
    public function testAllowUrlsOption(?string $value, bool $expectedIsValid, bool $allowUrls = false, string $formType = TextType::class)
    {
        $builder = $this->formFactory->createBuilder()
            ->add('test', $formType, [
                'allow_urls' => $allowUrls,
            ]);
        $form = $builder->getForm();
        $form->submit([
            'test' => $value,
        ]);

        if ($expectedIsValid) {
            $this->dontSeeFormError($form);
        } else {
            $this->seeFormError($form);
        }
    }

    public function allowUrlsOptionDataProvider(): array
    {
        return [
            ['abc', true],
            ['abc.ru', false],
            ['www.abc.ru', false],
            ['http://www.abc.ru', false],
            ['http://www.abc.ru/test/', false],
            ['https://01.ru/test', false],
            ['100.00', true],
            ['1,100.00', true],
            ['1.100,00', true],
            ['999,111,123', true],
            ['   89 . ru', true],
            ['192.168.1.1', false],
            ['192.168.1.1:80', false],
            ['Test message. Random page', true],
            ['Test message.Random page', true],
            ['Test site.com page', false],
            ['Test SITE.VOLKSWAGEN page', false],
            ['Test Subdomain.SITE.VOLKSWAGEN page', false],
            ["test message\n\ntest site.After.xxc ", true],
            ["test message\n\ntest site.ru. xxc ", false],
            ["test message\n\ntest 192.168.1.1. xxc ", false],
            ["test ipv6 example\n\n2001:0db8:85a3:0000:0000:8a2e:0370:7334", false],
            ["test ipv6 example\n\n2001:0db8:85a3:0000:0000:8a2e:0370:7334", true, false, UrlType::class],
        ];
    }

    private function seeFormError($form, $message = 'Invalid symbols.')
    {
        $formField = $form->get('test');
        $this->assertTrue($formField->isSubmitted());
        $this->assertFalse($formField->isValid());
        $this->assertEquals($message, $formField->getErrors()[0]->getMessage());
    }

    private function dontSeeFormError($form)
    {
        $formField = $form->get('test');
        $this->assertTrue($formField->isSubmitted());
        $this->assertTrue($formField->isValid());
        $this->assertEmpty($formField->getErrors());
    }
}
