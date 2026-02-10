<?php

namespace AwardWallet\Tests\Unit\Globals\CardImageParser\DOMConverter;

use AwardWallet\CardImageParser\DOMConverter\DOMConverter;
use AwardWallet\Tests\Unit\BaseTest;

use function PHPUnit\Framework\assertEquals;

/**
 * @group frontend-unit
 */
class DOMConverterTest extends BaseTest
{
    /**
     * @var DOMConverter
     */
    protected $converter;

    public function _before()
    {
        $this->converter = new DOMConverter();
    }

    public function testEmptyInput()
    {
        $dom = $this->converter->convert([], [], 0, 0);
        assertEquals(0, $dom->childNodes->length, 'DOM document should be empty');
    }

    public function testOneLineConvert()
    {
        $this->assertDOM(
            [
                ['some', ' ', 'text'],
            ],
            [
                [
                    'description' => 'some text',
                    'boundingPoly' => [
                        'vertices' => [
                            [/* top left */
                                'x' => 0,
                                'y' => 0,
                            ],
                            [/* top right */
                                'x' => 60,
                                'y' => 0,
                            ],
                            [/* bottom right */
                                'x' => 60,
                                'y' => 30,
                            ],
                            [/* bottom left */
                                'x' => 0,
                                'y' => 30,
                            ],
                        ],
                    ],
                ],
                [
                    'description' => 'some',
                    'boundingPoly' => [
                        'vertices' => [
                            [/* top left */
                                'x' => 10,
                                'y' => 20,
                            ],
                            [/* top right */],
                            [/* bottom right */
                                'x' => 40,
                                'y' => 40,
                            ],
                        ],
                    ],
                ],
                [
                    'description' => 'text',
                    'boundingPoly' => [
                        'vertices' => [
                            [/* top left */
                                'x' => 50,
                                'y' => 20,
                            ],
                            [/* top right */],
                            [/* bottom right */
                                'x' => 60,
                                'y' => 30,
                            ],
                        ],
                    ],
                ],
            ],
            100,
            100
        );
    }

    public function testMultiLineConvert()
    {
        $this->assertDOM(
            [
                ['first', ' ', 'line'],
                ['second', ' ', 'line'],
            ],
            [
                [
                    'description' => "first line\nsecond line",
                    'boundingPoly' => [
                        'vertices' => [
                            [/* top left */
                                'x' => 0,
                                'y' => 0,
                            ],
                            [/* top right */
                                'x' => 90,
                                'y' => 0,
                            ],
                            [/* bottom right */
                                'x' => 90,
                                'y' => 90,
                            ],
                            [/* bottom left */
                                'x' => 0,
                                'y' => 90,
                            ],
                        ],
                    ],
                ],
                [
                    'description' => 'first',
                    'boundingPoly' => [
                        'vertices' => [
                            [/* top left */
                                'x' => 10,
                                'y' => 20,
                            ],
                            [/* top right */],
                            [/* bottom right */
                                'x' => 40,
                                'y' => 30,
                            ],
                        ],
                    ],
                ],
                [
                    'description' => 'line',
                    'boundingPoly' => [
                        'vertices' => [
                            [/* top left */
                                'x' => 30,
                                'y' => 30,
                            ],
                            [/* top right */],
                            [/* bottom right */
                                'x' => 60,
                                'y' => 50,
                            ],
                        ],
                    ],
                ],
                [
                    'description' => 'second',
                    'boundingPoly' => [
                        'vertices' => [
                            [/* top left */
                                'x' => 50,
                                'y' => 60,
                            ],
                            [/* top right */],
                            [/* bottom right */
                                'x' => 80,
                                'y' => 80,
                            ],
                        ],
                    ],
                ],
                [
                    'description' => 'line',
                    'boundingPoly' => [
                        'vertices' => [
                            [/* top left */
                                'x' => 70,
                                'y' => 80,
                            ],
                            [/* top right */],
                            [/* bottom right */
                                'x' => 90,
                                'y' => 90,
                            ],
                        ],
                    ],
                ],
            ],
            100,
            100
        );
    }

    protected function assertDOM(array $expectedDOMArray, array $actualTextAnnotations, int $width, int $height, int $maxYDeviation = 30)
    {
        $actualDOM = $this->converter->convert($actualTextAnnotations, [], $width, $height, $maxYDeviation);
        $xpath = new \DOMXPath($actualDOM);
        $actualDOMArray = [];

        /** @var \DOMElement $divNode */
        foreach ($xpath->query('/div') as $divNode) {
            $line = [];

            /** @var \DOMElement $spanNode */
            foreach ($xpath->query('./span', $divNode) as $spanNode) {
                $line[] = $spanNode->textContent;
            }

            $actualDOMArray[] = $line;
        }

        assertEquals($expectedDOMArray, $actualDOMArray, "DOM Array doesn't match");
    }
}

trait Positioned
{
    /**
     * @var int
     */
    private $left;
    /**
     * @var int
     */
    private $top;
    /**
     * @var int
     */
    private $width;
    /**
     * @var int
     */
    private $height;

    public function __construct(int $left, int $top, int $width, int $height)
    {
        $this->left = $left;
        $this->top = $top;
        $this->width = $width;
        $this->height = $height;
    }
}

class Text
{
    use Positioned;
    /**
     * @var string
     */
    public $text;

    public function setText(string $text): self
    {
        $this->text = $text;

        return $this;
    }
}

class Image
{
    use Positioned;
    /**
     * @var string
     */
    public $alt;
    /**
     * @var int
     */
    public $score;

    public function setAlt(string $alt): self
    {
        $this->alt = $alt;

        return $this;
    }

    public function setScore(int $score): self
    {
        $this->score = $score;

        return $this;
    }
}

function text(int $left, int $top, int $width, int $height): Text
{
    return new Text($left, $top, $width, $height);
}

function image(int $left, int $top, int $width, int $height): Image
{
    return new Image($left, $top, $width, $height);
}
