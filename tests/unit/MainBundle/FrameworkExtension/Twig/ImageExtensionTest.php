<?php

namespace AwardWallet\Tests\Unit\MainBundle\FrameworkExtension\Twig;

use AwardWallet\MainBundle\FrameworkExtension\Twig\ImageExtension;
use AwardWallet\Tests\Unit\BaseContainerTest;
use Symfony\Component\Asset\Packages;

/**
 * @group frontend-unit
 */
class ImageExtensionTest extends BaseContainerTest
{
    public function testimageSrc()
    {
        $packages = $this->createMock(Packages::class);
        $packages->method('getUrl')->willReturnArgument(0);

        $extension = new ImageExtension($packages, 'dev');

        $this->assertEquals('src="a/blocks/image.jpg"', $extension->imageSrc('image.jpg'));
        $this->assertEquals('src="a/blocks/image@2x.jpg"', $extension->imageSrc('image@2x.jpg'));
        $this->assertEquals('src="a/blocks/image@2x.jpg" srcset="a/blocks/image@2x.jpg 2x"', $extension->imageSrc('image@{2}x.jpg'));
        $this->assertEquals(
            'src="a/blocks/image@1x.jpg" srcset="a/blocks/image@1x.jpg 1x, a/blocks/image@2x.jpg 2x, a/blocks/image@3x.jpg 3x, a/blocks/image@4x.jpg 4x"',
            $extension->imageSrc('image@{1-4}x.jpg')
        );
        $this->assertEquals(
            'src="a/blocks/image@4x.jpg" srcset="a/blocks/image@1x.jpg 1x, a/blocks/image@2x.jpg 2x, a/blocks/image@3x.jpg 3x, a/blocks/image@4x.jpg 4x"',
            $extension->imageSrc('image@{4-1}x.jpg')
        );
        $this->assertEquals(
            'src="a/blocks/image@2x.jpg" srcset="a/blocks/image@1x.jpg 1x, a/blocks/image@2x.jpg 2x, a/blocks/image@3x.jpg 3x, a/blocks/image@4x.jpg 4x"',
            $extension->imageSrc('image@{2,1-4}x.jpg')
        );
        $this->assertEquals(
            'src="a/blocks/image@3x.jpg" srcset="a/blocks/image@1x.jpg 1x, a/blocks/image@2x.jpg 2x, a/blocks/image@3x.jpg 3x"',
            $extension->imageSrc('image@{3,1-2}x.jpg')
        );
        $this->assertEquals(
            'src="a/blocks/path/path2/test@2x.png" srcset="a/blocks/path/path2/test@2x.png 2x, a/blocks/path/path2/test@3x.png 3x"',
            $extension->imageSrc('path/path2/test@{2-3}x.png')
        );
        $this->assertEquals(
            'src="a/blocks/image@3x.jpg" srcset="a/blocks/image@1x.jpg 1x, a/blocks/image@2x.jpg 2x, a/blocks/image@3x.jpg 3x, a/blocks/image@4x.jpg 4x"',
            $extension->imageSrc('image@{3,1-2,3-4}x.jpg')
        );
        $this->assertEquals(
            'src="a/blocks/path/path2/test@200w.png" srcset="a/blocks/path/path2/test@200w.png 200w"',
            $extension->imageSrc('path/path2/test@{200}w.png')
        );
        $this->assertEquals(
            'src="a/blocks/image@200w.webp" srcset="a/blocks/image@200w.webp 200w, a/blocks/image@460w.webp 460w, a/blocks/image@600w.webp 600w"',
            $extension->imageSrc('image@{200,460, 600}w.webp')
        );
        $this->assertEquals(
            'src="a/blocks/image@460w.webp" srcset="a/blocks/image@460w.webp 460w, a/blocks/image@600w.webp 600w, a/blocks/image@1000w.webp 1000w"',
            $extension->imageSrc('image@{460,1000, 600}w.webp')
        );
        $this->assertEquals(
            'src="a/blocks/image@400w.webp" srcset="a/blocks/image@200w.webp 200w, a/blocks/image@400w.webp 400w, a/blocks/image@900w.webp 900w"',
            $extension->imageSrc('image@{400,200,400,900}w.webp')
        );
    }
}
