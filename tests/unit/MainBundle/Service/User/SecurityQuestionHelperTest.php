<?php

namespace AwardWallet\Tests\Unit\MainBundle\Service\User;

use AwardWallet\MainBundle\Entity\UserQuestion;
use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\Form\Model\Profile\SecurityQuestionEntryModel;
use AwardWallet\MainBundle\Form\Model\Profile\SecurityQuestionModel;
use AwardWallet\MainBundle\FrameworkExtension\AwTokenStorageInterface;
use AwardWallet\MainBundle\Security\Encryptor\Encryptor;
use AwardWallet\MainBundle\Service\User\SecurityQuestionHelper;
use AwardWallet\Tests\Unit\BaseContainerTest;
use Doctrine\ORM\Query;

/**
 * @group frontend-unit
 */
class SecurityQuestionHelperTest extends BaseContainerTest
{
    private ?int $userId;
    private ?Usr $user;
    private ?SecurityQuestionHelper $helper;

    public function _before()
    {
        parent::_before();
        $this->userId = $this->aw->createAwUser();
        $this->user = $this->em->getRepository(Usr::class)->find($this->userId);
        $this->helper = new SecurityQuestionHelper(
            $this->makeEmpty(AwTokenStorageInterface::class, ['getUser' => $this->user]),
            $this->container->get(Encryptor::class),
            $this->em
        );
    }

    public function _after()
    {
        $this->userId = null;
        $this->user = null;
        $this->helper = null;
        parent::_after();
    }

    public function testCreateQuestion()
    {
        $this->helper->process($this->createModel());

        foreach ($this->getQuestionsFromDb() as $question) {
            $expected = self::getQuestionsArray()[$question['order']]['question'];
            $this->assertEquals($expected, $question['question']);
        }
    }

    public function testUpdateQuestion()
    {
        $questionModel = $this->createModel();
        $this->helper->process($questionModel);
        $this->em->refresh($this->user);

        foreach ($questionModel->getQuestions() as $entry) {
            if ($entry->getSortIndex() === UserQuestion::ORDER_SECOND) {
                $entry->setQuestion(UserQuestion::CHILDHOOD_PHONE_NUMBER);
                $entry->setAnswer('+78005005000');
            }
        }

        $this->helper->process($questionModel);

        foreach ($this->getQuestionsFromDb() as $question) {
            if ($question['order'] === UserQuestion::ORDER_SECOND) {
                $this->assertEquals(UserQuestion::CHILDHOOD_PHONE_NUMBER, $question['question']);
            }
        }
    }

    public function testDeleteOneQuestion()
    {
        $questionModel = $this->createModel();
        $this->helper->process($questionModel);
        $this->em->refresh($this->user);
        $this->assertCount(UserQuestion::MAX_QUESTIONS_ALLOWED, $this->getQuestionsFromDb());

        foreach ($questionModel->getQuestions() as $entry) {
            if ($entry->getSortIndex() === UserQuestion::ORDER_THIRD) {
                $entry->setQuestion(null);
            }
        }

        $this->helper->process($questionModel);
        $this->assertCount(2, $this->getQuestionsFromDb());
    }

    public function testDeleteAllQuestions()
    {
        $questionModel = $this->createModel();
        $this->helper->process($questionModel);
        $this->assertCount(UserQuestion::MAX_QUESTIONS_ALLOWED, $this->getQuestionsFromDb());

        foreach ($questionModel->getQuestions() as $entry) {
            $entry->setQuestion(null);
        }

        $this->helper->process($questionModel);
        $this->assertCount(0, $this->getQuestionsFromDb());
    }

    private function createModel(): SecurityQuestionModel
    {
        $model = new SecurityQuestionModel();
        $entries = [];

        foreach (self::getQuestionsArray() as $order => $item) {
            $entries[] = (new SecurityQuestionEntryModel())
                ->setSortIndex($order)
                ->setQuestion($item['question'])
                ->setAnswer($item['answer']);
        }

        $model->setQuestions($entries);

        return $model;
    }

    /**
     * Get all the security questions sorted by `SortIndex`.
     */
    private function getQuestionsFromDb(): array
    {
        $queryBuilder = $this->em->createQueryBuilder()
            ->select('uq')
            ->from(UserQuestion::class, 'uq');

        return $queryBuilder
            ->where('uq.user = :userId')
            ->setParameter('userId', $this->userId)
            ->orderBy('uq.order', 'ASC')
            ->getQuery()
            ->getResult(Query::HYDRATE_ARRAY);
    }

    private static function getQuestionsArray(): array
    {
        return [
            UserQuestion::ORDER_FIRST => [
                'question' => UserQuestion::CHILDHOOD_NICKNAME,
                'answer' => 'Ryuuji Takasu',
            ],
            UserQuestion::ORDER_SECOND => [
                'question' => UserQuestion::FAVORITE_CHILDHOOD_FRIEND,
                'answer' => 'Yuusaku Kitamura',
            ],
            UserQuestion::ORDER_THIRD => [
                'question' => UserQuestion::OLDEST_SIBLING_MIDDLE_NAME,
                'answer' => 'Taiga Aisaka',
            ],
        ];
    }
}
