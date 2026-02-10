<?php

namespace AwardWallet\Tests\Unit\AwardWallet\MainBundle\FrameworkExtension\Twig;

/**
 * @group frontend-unit
 */
class EscaperCest
{
    use \Awardwallet\Tests\Modules\AutoVerifyMocksTrait;
    /** @var \Twig_Environment */
    private $twig;

    public function _before(\TestSymfonyGuy $I)
    {
        $this->twig = $I->grabService("twig");
    }

    public function testArrayEscaping(\TestSymfonyGuy $I)
    {
        // $html = $this->twig->render("@AwardWalletMain/Test/testArrayEscaping.html.twig", ["some_array" => ["hello" => "world"]]);
        // $I->assertEquals("dd", $html);
    }

    public function testString(\TestSymfonyGuy $I)
    {
        $context = ['justVarString' => 'result--justVarString'];
        $renderResult = 'result--justVarString';
        $tpl = $this->twig->createTemplate('{{justVarString}}');
        $I->assertEquals($tpl->render($context), $renderResult);
    }

    public function testHtmlEscapeString(\TestSymfonyGuy $I)
    {
        $context = ['justVarString' => 'result-<br>-justVarString'];
        $renderResult = 'result-&lt;br&gt;-justVarString';
        $tpl = $this->twig->createTemplate('{{justVarString}}');
        $I->assertEquals($tpl->render($context), $renderResult);
    }

    public function testRawHtmlString(\TestSymfonyGuy $I)
    {
        $context = ['justVarString' => 'result-<br>-justVarString'];
        $renderResult = 'result-<br>-justVarString';

        $tpl = $this->twig->createTemplate('{{justVarString|raw}}');
        $I->assertEquals($tpl->render($context), $renderResult);
    }

    public function testArray(\TestSymfonyGuy $I)
    {
        $context = ['varArray' => ['one' => '1', 'two' => '2']];
        $renderResult = '12';
        $tpl = $this->twig->createTemplate('{{varArray.one}}{{varArray.two}}');
        $I->assertEquals($tpl->render($context), $renderResult);
    }

    public function testArrayEscapeHtml(\TestSymfonyGuy $I)
    {
        $context = ['varArray' => ['tag' => 'one-<br>-two']];
        $renderResult = 'one-&lt;br&gt;-two';
        $tpl = $this->twig->createTemplate('{{varArray.tag}}');
        $I->assertEquals($tpl->render($context), $renderResult);
    }

    public function TestTwigExtensionString(\TestSymfonyGuy $I)
    {
        $this->twig->addExtension(new TestTwigExtensionString());
        $tpl = $this->twig->createTemplate('{{ SomeString() }}');
        $renderResult = $tpl->render([]);
        $I->assertEquals($renderResult, 'just-some-&lt;br&gt;-htmlcode');
    }

    public function testExtensionSafeString(\TestSymfonyGuy $I)
    {
        $this->twig->addExtension(new TestTwigExtensionSafeString());
        $tpl = $this->twig->createTemplate('{{ SomeStringHtml() }}');
        $renderResult = $tpl->render([]);
        $I->assertEquals($renderResult, 'just-some-<br>-htmlcode');
    }

    public function testExtensionArray(\TestSymfonyGuy $I)
    {
        $this->twig->addExtension(new TestTwigExtensionArray());
        //        $this->twig->removeExtension('escaper'); // сorrect work with this
        $tpl = $this->twig->createTemplate('{{SomeArray().htmlCode}}'); // can't use "|raw"
        $renderResult = $tpl->render([]);
        // $this->showRenderResult($renderResult);
        $I->assertEquals($renderResult, 'some-<br>-htmlcode');
    }

    private function showRenderResult($html)
    {
        exit("\n\n\r" . $html . "\n\n\r");
    }
}

class TestTwigExtensionString extends \Twig_Extension
{
    public function getFunctions(): array
    {
        return [
            new \Twig_SimpleFunction('SomeString', function () {
                return 'just-some-<br>-htmlcode';
            }),
        ];
    }
}

class TestTwigExtensionSafeString extends \Twig_Extension
{
    public function getFunctions(): array
    {
        return [
            new \Twig_SimpleFunction('SomeStringHtml', function () {
                return 'just-some-<br>-htmlcode';
            }, ['is_safe' => ['html']]),
        ];
    }
}

class TestTwigExtensionArray extends \Twig_Extension
{
    public function getFunctions(): array
    {
        return [
            new \Twig_SimpleFunction('SomeArray', function () {
                return ['htmlCode' => new \Twig_Markup('some-<br>-htmlcode', 'utf-8')];
            }, ['is_safe' => ['html']]),
        ];
    }
}
