<?php

namespace AwardWallet\MainBundle\Service\User;

use AwardWallet\MainBundle\Entity\UserQuestion;
use AwardWallet\MainBundle\Form\Model\Profile\SecurityQuestionEntryModel;
use AwardWallet\MainBundle\Form\Model\Profile\SecurityQuestionModel;
use AwardWallet\MainBundle\FrameworkExtension\AwTokenStorageInterface;
use AwardWallet\MainBundle\Security\Encryptor\Encryptor;
use Doctrine\ORM\EntityManagerInterface;

use function AwardWallet\MainBundle\Globals\Utils\iterFluent\it;

/**
 * A class to interact with custom security questions and answers.
 */
class SecurityQuestionHelper
{
    public const STATUS_UPDATED = 1;
    public const STATUS_DELETED = 2;

    private AwTokenStorageInterface $tokenStorage;
    private Encryptor $encryptor;
    private EntityManagerInterface $entityManager;

    public function __construct(
        AwTokenStorageInterface $tokenStorage,
        Encryptor $encryptor,
        EntityManagerInterface $entityManager
    ) {
        $this->tokenStorage = $tokenStorage;
        $this->encryptor = $encryptor;
        $this->entityManager = $entityManager;
    }

    public function process(SecurityQuestionModel $model): int
    {
        if ($model->isEmptyQuestions()) {
            $this->delete();

            return self::STATUS_DELETED;
        } else {
            if ($this->tokenStorage->getUser()->getSecurityQuestions()->count() === 0) {
                $this->create($model);
            } else {
                $this->update($model);
            }

            return self::STATUS_UPDATED;
        }
    }

    /**
     * Searches for security questions in DB and generates a model based on them.
     */
    public function findModel(): ?SecurityQuestionModel
    {
        $questions = $this->tokenStorage->getUser()->getSecurityQuestions();

        if ($questions) {
            $maxSortIndex = it($questions)->map(fn (UserQuestion $q) => $q->getOrder())->max();
            $model = new SecurityQuestionModel();

            $entries = it($questions)
                ->map(function (UserQuestion $q) {
                    $entry = new SecurityQuestionEntryModel();
                    $entry->setSortIndex($q->getOrder());
                    $entry->setQuestion($q->getQuestion());
                    $entry->setAnswer($this->encryptor->decrypt($q->getAnswer()));

                    return $entry;
                })
                ->chain(
                    it(\iter\range($maxSortIndex + 1, UserQuestion::MAX_QUESTIONS_ALLOWED))
                    ->map(function (int $i) {
                        $entry = new SecurityQuestionEntryModel();
                        $entry->setSortIndex($i);
                        $entry->setQuestion(null);
                        $entry->setAnswer(null);

                        return $entry;
                    })
                )
                ->take(UserQuestion::MAX_QUESTIONS_ALLOWED)
                ->toArray();

            $model->setQuestions($entries);

            return $model;
        }

        return null;
    }

    /**
     * Creates new security questions for the current user.
     */
    private function create(SecurityQuestionModel $model): void
    {
        foreach (UserQuestion::getOrdersArray() as $i) {
            if ($model->getCustomQuestion($i) === null) {
                continue;
            }

            $question = new UserQuestion();
            $question->setUser($this->tokenStorage->getUser());
            $question->setOrder($i);
            $question->setQuestion($model->getCustomQuestion($i));
            $question->setAnswer($this->encryptor->encrypt($model->getCustomAnswer($i)));

            $this->entityManager->persist($question);
        }

        $this->entityManager->flush();
    }

    /**
     * Updates existing security questions for the current user.
     */
    private function update(SecurityQuestionModel $model): void
    {
        foreach (UserQuestion::getOrdersArray() as $i) {
            $question = $this->tokenStorage->getUser()->getSecurityQuestions()->get($i);

            if ($model->getCustomQuestion($i) === null && $question === null) {
                continue;
            }

            if ($question === null) {
                $question = new UserQuestion();
                $question->setUser($this->tokenStorage->getUser());
                $question->setOrder($i);
            }

            if ($model->getCustomQuestion($i) !== null) {
                $question->setQuestion($model->getCustomQuestion($i));
                $question->setAnswer($this->encryptor->encrypt($model->getCustomAnswer($i)));

                $this->entityManager->persist($question);
            } else {
                $this->entityManager->remove($question);
            }
        }

        $this->entityManager->flush();
    }

    /**
     * Deletes all security questions for the current user.
     */
    private function delete(): bool
    {
        $queryBuilder = $this->entityManager->createQueryBuilder()
            ->select('uq')
            ->from(UserQuestion::class, 'uq');

        $result = $queryBuilder->delete()
            ->where('uq.user = :userId')
            ->setParameter('userId', $this->tokenStorage->getUser()->getId())
            ->getQuery()
            ->execute();

        return $result > 0;
    }
}
