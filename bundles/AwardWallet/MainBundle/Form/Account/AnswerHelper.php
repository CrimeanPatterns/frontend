<?php

namespace AwardWallet\MainBundle\Form\Account;

use AwardWallet\MainBundle\Entity\Account;
use AwardWallet\MainBundle\Entity\Answer;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManager;
use JMS\TranslationBundle\Model\Message as TranslationMessage;
use JMS\TranslationBundle\Translation\TranslationContainerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class AnswerHelper implements TranslationContainerInterface
{
    public const PARAM_DATA_KEY = 'unitedAnswerJson';
    /**
     * @var Connection
     */
    protected $connection;

    /**
     * @var EntityManager
     */
    protected $em;

    /**
     * @var TranslatorInterface
     */
    protected $translator;

    public function __construct(Connection $connection, EntityManager $em, TranslatorInterface $translator)
    {
        $this->connection = $connection;
        $this->em = $em;
        $this->translator = $translator;
    }

    /**
     * Retrieving saved account answer from table Answer.
     *
     * @param mixed $accountOrId Account object or integer
     * @param array $params
     *   $params['js'] bool  Only the Question=>Answer fields will be returned
     * @return array
     */
    public function getAnswers($accountOrId, $params = [])
    {
        if (empty($accountOrId)) {
            return [];
        }

        $accountId = is_int($accountOrId) ? $accountOrId : $accountOrId->getAccountid();
        $cols = isset($params['js']) ? ['Question', 'Answer'] : ['AnswerID', 'Question', 'Answer', 'CreateDate', 'Valid'];

        $rows = $this->connection->fetchAll('
            SELECT
                ' . implode(',', $cols) . '
            FROM
                `Answer`
            WHERE
                AccountID = ' . $accountId . '
        ');

        if (isset($params['questionAsKey'])) {
            return $this->questionsAsKeys($rows);
        }

        return $rows;
    }

    /**
     * @param array $answers [Question => Answer]
     * @return bool
     */
    public function saveAnswers($answers, Account $account)
    {
        $existAnswers = $this->getAnswers($account);

        $update = $insert = $process = [];

        foreach ($answers as $question) {
            if (empty($question['Question'])) {
                continue;
            }
            $is = $this->isExistsEqual($question, $existAnswers);

            if (false === $is) {
                $update[$this->findId($question, $existAnswers)] = $question;
                $process[] = $question['Question'];
            } elseif (null === $is) {
                $insert[] = $question;
                $process[] = $question['Question'];
            } elseif (true === $is) {
                $process[] = $question['Question'];
            }
        }

        for ($i = 0, $iCount = count($insert); $i < $iCount; $i++) {
            $answer = new Answer();
            $answer
                ->setAccountid($account)
                ->setQuestion($insert[$i]['Question'])
                ->setAnswer($insert[$i]['Answer']);

            $this->em->persist($answer);
            $this->em->flush($answer);
        }
        // if (!empty($insert))
        //  $this->em->commit();

        foreach ($update as $answerId => $question) {
            $this->connection->update('Answer', [
                'Answer' => $question['Answer'],
            ], ['AnswerID' => $answerId]);
        }

        $remove = [];

        for ($i = 0, $iCount = count($existAnswers); $i < $iCount; $i++) {
            $find = false;

            for ($j = 0, $jCount = count($process); $j < $jCount; $j++) {
                if ($process[$j] == $existAnswers[$i]['Question']) {
                    $find = true;

                    continue;
                }
            }

            if (false === $find) {
                $remove[] = $existAnswers[$i]['AnswerID'];
            }
        }

        if (!empty($remove)) {
            $this->connection->query('DELETE FROM `Answer` WHERE AnswerID IN (' . implode(',', $remove) . ') AND AccountID = ' . $account->getAccountid());
        }

        return true;
    }

    /**
     * Obtaining the question id-key from the text.
     *
     * @param array $answers
     * @param array $questions
     * @return array
     */
    public function convertNameToKeys($answers, $questions = null)
    {
        $values = [];
        !empty($questions) ?: $questions = $this->getUnitedQuestion();

        for ($i = -1, $iCount = count($answers); ++$i < $iCount;) {
            foreach ($questions as $key => $parent) {
                $parent['label'] = html_entity_decode($parent['label'], ENT_QUOTES, 'UTF-8');
                $answers[$i]['Question'] = html_entity_decode($answers[$i]['Question'], ENT_QUOTES, 'UTF-8');

                if ($answers[$i]['Question'] == $parent['label']) {
                    foreach ($parent['items'] as $itemKey => $itemName) {
                        $itemName = html_entity_decode($itemName, ENT_QUOTES, 'UTF-8');
                        $answers[$i]['Answer'] = html_entity_decode($answers[$i]['Answer'], ENT_QUOTES, 'UTF-8');

                        if ($answers[$i]['Answer'] == $itemName) {
                            $values[$key] = $itemKey;
                        }
                    }
                }
            }
        }

        return $values;
    }

    /**
     * Converting id-key to a text representation.
     *
     * @param array $answers
     * @return array
     */
    public function convertKeysToName($answers)
    {
        $questions = $this->getUnitedQuestion();

        if (!$questions) {
            return [];
        }

        $converted = [];

        foreach ($answers as $questionKey => $answerKey) {
            for ($i = -1, $iCount = count($questions); ++$i < $iCount;) {
                if ($questionKey == $questions[$i]['QuestionKey'] || $questionKey == $questions[$i]['QuestionText']) {
                    for ($j = -1, $jCount = count($questions[$i]['Answers']); ++$j < $jCount;) {
                        if ($answerKey == $questions[$i]['Answers'][$j]['AnswerKey'] || $answerKey == $questions[$i]['Answers'][$j]['AnswerText']) {
                            $converted[] = [
                                'Question' => $questions[$i]['QuestionText'],
                                'Answer' => $questions[$i]['Answers'][$j]['AnswerText'],
                            ];

                            continue;
                        }
                    }
                }
            }
        }

        return $converted;
    }

    /**
     * @param array $params
     *   $params['select2Convert'] bool  An indexed array for the list will be created
     * @return array|null
     */
    public function getUnitedQuestion($params = [])
    {
        $data = $this->connection->fetchAssoc('SELECT `BigData` FROM `Param` WHERE `Name` = ?', [self::PARAM_DATA_KEY]);

        if ($data && !empty($data['BigData'])) {
            $data = json_decode($data['BigData'], true);

            if (!empty($data)) {
                if (array_key_exists('select2Convert', $params) && true === $params['select2Convert']) {
                    $tmp = [];

                    for ($i = -1, $iCount = count($data); ++$i < $iCount;) {
                        $key = $data[$i]['QuestionKey'];
                        $tmp[$key] = [
                            'value' => $key,
                            'label' => $data[$i]['QuestionText'],
                            'items' => [],
                        ];

                        $items = [];

                        for ($j = -1, $jCount = count($data[$i]['Answers']); ++$j < $jCount;) {
                            $items[$data[$i]['Answers'][$j]['AnswerKey']] = $data[$i]['Answers'][$j]['AnswerText'];
                        }
                        $tmp[$key]['items'] = $items;
                    }

                    $data = $tmp;
                }

                return $data;
            }
        }

        return null;
    }

    /*
     * @return TranslatorInterface
     */
    public function getTranslator()
    {
        return $this->translator;
    }

    /**
     * @return array
     */
    public static function getTranslationMessages()
    {
        return [
            (new TranslationMessage('question.num-of-count'))->setDesc('Question %index_number% of %count%'),
        ];
    }

    /**
     * Checks for the existence of a response in the list.
     *
     * @param array $question
     * @param array $answers
     * @return bool|null
     */
    protected function isExistsEqual($question, $answers)
    {
        for ($i = 0, $icount = count($answers); $i < $icount; $i++) {
            if ($answers[$i]['Question'] == $question['Question']) {
                if ($answers[$i]['Answer'] == $question['Answer']) {
                    return true;
                }

                return false;
            }
        }

        return null;
    }

    /**
     * Searching id-key when the question text matches.
     *
     * @param array $question
     * @param array $answers
     * @return string|bool
     */
    protected function findId($question, $answers)
    {
        for ($i = 0, $icount = count($answers); $i < $icount; $i++) {
            if ($answers[$i]['Question'] == $question['Question']) {
                return $answers[$i]['AnswerID'];
            }
        }

        return false;
    }

    private function questionsAsKeys(array $answers = []): array
    {
        $result = [];

        foreach ($answers as $item) {
            $result[$item['Question']] = $item['Answer'];
        }

        return $result;
    }
}
