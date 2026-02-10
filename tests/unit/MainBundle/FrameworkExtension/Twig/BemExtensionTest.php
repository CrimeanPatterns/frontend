<?php

namespace AwardWallet\Tests\Unit\MainBundle\FrameworkExtension\Twig;

use AwardWallet\MainBundle\FrameworkExtension\Twig\BemExtension;
use AwardWallet\MainBundle\Service\ThemeResolver;
use AwardWallet\Tests\Unit\BaseContainerTest;

/**
 * @group frontend-unit
 */
class BemExtensionTest extends BaseContainerTest
{
    public function testBem()
    {
        $themeResolver = $this->createMock(ThemeResolver::class);
        $themeResolver->method('getCurrentTheme')->willReturn('testtheme');

        $bemExtension = new BemExtension($themeResolver);

        $this->assertEquals('block block--testtheme', $bemExtension->bem('block'));
        $this->assertEquals('block block--modifier block--testtheme', $bemExtension->bem('block', null, ['modifier']));
        $this->assertEquals('block__element block__element--testtheme', $bemExtension->bem('block', 'element'));
        $this->assertEquals('block__element block__element--modifier block__element--testtheme', $bemExtension->bem('block', 'element', ['modifier']));
        $this->assertEquals('block__element block__element--modifier block__element--modifier2 block__element--testtheme', $bemExtension->bem('block', 'element', ['modifier', 'modifier2']));
    }
}
