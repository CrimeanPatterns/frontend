<?php

namespace AwardWallet\MainBundle\Form\Model\Profile;

use AwardWallet\MainBundle\Entity\UserQuestion;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

class SecurityQuestionModel
{
    /**
     * @var SecurityQuestionEntryModel[]
     * @Assert\Valid()
     */
    protected array $questions = [];

    /**
     * Validates that different questions have been selected in all dropdown lists.
     *
     * @Assert\Callback()
     */
    public function validateUnique(ExecutionContextInterface $context, $payload)
    {
        $index = null;
        $values = [];

        foreach (UserQuestion::getOrdersArray() as $order) {
            if (!in_array($this->getCustomQuestion($order), $values)) {
                if ($this->getCustomQuestion($order) !== null) {
                    $values[] = $this->getCustomQuestion($order);
                }
            } else {
                $index = $this->getEntryIndex($order);

                break;
            }
        }

        if (!$this->isEmptyQuestions() && $index !== null) {
            $context->buildViolation('Each question should be unique.')
                ->atPath("questions[{$index}].question")
                ->addViolation();
        }
    }

    /**
     * Validates that all security questions have been filled in. If, on the contrary, all questions are empty,
     * then deletes them.
     *
     * @Assert\Callback()
     */
    public function validateNotEmpty(ExecutionContextInterface $context, $payload)
    {
        if (!$this->isEmptyQuestions()) {
            $index = null;
            $values = [];

            foreach (UserQuestion::getOrdersArray() as $order) {
                $currentIndex = $this->getEntryIndex($order);
                $values[$currentIndex] = $this->getCustomQuestion($order);

                if (count($values) > 1 && $values[$currentIndex - 1] === null && $values[$currentIndex] !== null) {
                    $index = $currentIndex - 1;
                }
            }

            if ($index !== null) {
                $context->buildViolation('Question cannot be blank.')
                    ->atPath("questions[{$index}].question")
                    ->addViolation();
            }
        }
    }

    public function getQuestions(): array
    {
        return $this->questions;
    }

    public function setQuestions(array $questions): self
    {
        $this->questions = $questions;

        return $this;
    }

    public function getCustomQuestion(int $order): ?string
    {
        foreach ($this->getQuestions() as $entry) {
            if ($entry->getSortIndex() === $order) {
                return $entry->getQuestion();
            }
        }

        throw new \InvalidArgumentException('Invalid order value');
    }

    public function setCustomQuestion(int $order, string $question): SecurityQuestionEntryModel
    {
        foreach ($this->getQuestions() as $entry) {
            if ($entry->getSortIndex() === $order) {
                return $entry->setQuestion($question);
            }
        }

        throw new \InvalidArgumentException('Invalid order value');
    }

    public function getCustomAnswer(int $order): string
    {
        foreach ($this->getQuestions() as $entry) {
            if ($entry->getSortIndex() === $order) {
                return $entry->getAnswer();
            }
        }

        throw new \InvalidArgumentException('Invalid order value');
    }

    public function setCustomAnswer(int $order, string $answer): SecurityQuestionEntryModel
    {
        foreach ($this->getQuestions() as $entry) {
            if ($entry->getSortIndex() === $order) {
                return $entry->setAnswer($answer);
            }
        }

        throw new \InvalidArgumentException('Invalid order value');
    }

    /**
     * Validates that all questions in the request were not filled in.
     */
    public function isEmptyQuestions(): bool
    {
        $isEmpty = true;

        foreach ($this->getQuestions() as $entry) {
            if ($entry->getQuestion() !== null) {
                $isEmpty = false;

                break;
            }
        }

        return $isEmpty;
    }

    /**
     * Get the numeric index of the question in the collection (needed to display the validation error message).
     */
    private function getEntryIndex(int $order): ?int
    {
        foreach ($this->getQuestions() as $key => $entry) {
            if ($entry->getSortIndex() === $order) {
                return $key;
            }
        }

        return null;
    }
}
