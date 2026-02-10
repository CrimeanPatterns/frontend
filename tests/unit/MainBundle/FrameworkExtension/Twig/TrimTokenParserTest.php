<?php

namespace AwardWallet\Tests\Unit\MainBundle\FrameworkExtension\Twig;

use AwardWallet\Tests\Unit\BaseContainerTest;

/**
 * @group frontend-unit
 */
class TrimTokenParserTest extends BaseContainerTest
{
    /**
     * @var \Twig_Environment
     */
    private $twig;

    public function _before()
    {
        parent::_before();
        $this->twig = $this->container->get('twig');
    }

    public function _after()
    {
        $this->twig = null;
        parent::_after();
    }

    /**
     * @dataProvider dataProvider
     */
    public function testTrim($template, $expected)
    {
        $this->assertEquals(
            $expected,
            $this->twig->createTemplate("{% trim %}" . $template . "{% endtrim %}")
                ->render([])
        );
    }

    public function dataProvider()
    {
        return [
            ['<div>123</div>', '<div>123</div>'],
            [' <div>123</div> ', '<div>123</div>'],
            [' <div> 123  </div> ', '<div> 123  </div>'],
            ["   <p>\n\n <span>  123  </span> \n</p>   ", "<p>\n\n <span>  123  </span> \n</p>"],
        ];
    }
}
