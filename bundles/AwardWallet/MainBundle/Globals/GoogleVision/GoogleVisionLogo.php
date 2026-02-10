<?php

namespace AwardWallet\MainBundle\Globals\GoogleVision;

class GoogleVisionLogo
{
    /**
     * @var string
     */
    public $text;

    /**
     * @var float
     */
    public $score;

    /**
     * GoogleVisionLogo constructor.
     *
     * @param string $text
     * @param float $score
     */
    public function __construct($text, $score)
    {
        $this->text = $text;
        $this->score = $score;
    }
}
