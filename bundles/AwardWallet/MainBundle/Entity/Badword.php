<?php

namespace AwardWallet\MainBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Badword.
 *
 * @ORM\Table(name="BadWord")
 * @ORM\Entity
 */
class Badword
{
    /**
     * @var int
     * @ORM\Column(name="BadWordID", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    protected $badwordid;

    /**
     * @var string
     * @ORM\Column(name="Word", type="string", length=40, nullable=false)
     */
    protected $word;

    /**
     * Get badwordid.
     *
     * @return int
     */
    public function getBadwordid()
    {
        return $this->badwordid;
    }

    /**
     * Set word.
     *
     * @param string $word
     * @return Badword
     */
    public function setWord($word)
    {
        $this->word = $word;

        return $this;
    }

    /**
     * Get word.
     *
     * @return string
     */
    public function getWord()
    {
        return $this->word;
    }
}
