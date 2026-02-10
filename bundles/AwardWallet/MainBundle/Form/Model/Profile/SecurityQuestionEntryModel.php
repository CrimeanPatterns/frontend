<?php

namespace AwardWallet\MainBundle\Form\Model\Profile;

use AwardWallet\MainBundle\Entity\UserQuestion;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Mapping\ClassMetadata;

class SecurityQuestionEntryModel
{
    /**
     * @Assert\Type(type="integer")
     */
    protected int $sortIndex;

    /**
     * @Assert\Type(type="string")
     * @Assert\Length(max="64")
     */
    protected ?string $question = '';

    /**
     * @Assert\Type(type="string")
     * @Assert\Length(min="3", max="250")
     * @Assert\Expression("!(this.getQuestion() !== null and value === null)", message="Answer cannot be blank.")
     */
    protected ?string $answer = '';

    public static function loadValidatorMetadata(ClassMetadata $metadata): void
    {
        $metadata->addPropertyConstraint('sortIndex', new Assert\Choice([
            'callback' => [UserQuestion::class, 'getOrdersArray'],
        ]));
        $metadata->addPropertyConstraint('question', new Assert\Choice([
            'callback' => [UserQuestion::class, 'getQuestionsArray'],
        ]));
    }

    public function getSortIndex(): int
    {
        return $this->sortIndex;
    }

    public function setSortIndex(int $sortIndex): self
    {
        $this->sortIndex = $sortIndex;

        return $this;
    }

    public function getQuestion(): ?string
    {
        return $this->question;
    }

    public function setQuestion(?string $question): self
    {
        $this->question = $question;

        return $this;
    }

    public function getAnswer(): ?string
    {
        return $this->answer;
    }

    public function setAnswer(?string $answer): self
    {
        $this->answer = $answer;

        return $this;
    }
}
