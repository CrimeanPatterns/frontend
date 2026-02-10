<?php

namespace AwardWallet\MainBundle\Command\Update;

use AwardWallet\MainBundle\Form\Account\AnswerHelper;
use Doctrine\DBAL\Connection;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class UpdateUnitedAnswerCommand extends Command
{
    protected static $defaultName = 'aw:update-united-questions';

    /* @var \Monolog\Logger $logger */
    private $logger;
    /* @var OutputInterface $output */
    private $output;
    /* @var \Doctrine\DBAL\Connection $connection */
    private $connection;
    private \CurlDriver $curlDriver;
    private AnswerHelper $answerHelper;

    public function __construct(
        LoggerInterface $logger,
        Connection $connection,
        \CurlDriver $curlDriver,
        AnswerHelper $answerHelper
    ) {
        parent::__construct();

        $this->logger = $logger;
        $this->connection = $connection;
        $this->curlDriver = $curlDriver;
        $this->answerHelper = $answerHelper;
    }

    protected function configure()
    {
        $this
            ->setDescription('Update secret questions for united airlines')
            ->setHelp('Updates table `Param` by key `united_questions_data` with data received https://www.united.com/ual/en/us/account/enroll/default')
            ->addOption('update', null, InputOption::VALUE_NONE, 'Run command');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $answer = $this->getQuestions();

        $result = is_array($answer) ? $this->update($answer) : false;
        $s = ($result ? 'Updated united secret questions' : 'Fail updated united secret questions');
        $this->logger->info($s);

        return 0;
    }

    protected function update($answer)
    {
        if (!empty($answer)) {
            if (false !== ($jsonQuestions = json_encode($answer))) {
                $this->connection->executeQuery('
                    INSERT INTO 
                            `Param` (`Name`, `BigData`)
                        VALUES
                            (:paramName, :questions)
                        ON DUPLICATE KEY
                            UPDATE `BigData` = :questions
                ', [
                    'paramName' => AnswerHelper::PARAM_DATA_KEY,
                    'questions' => $jsonQuestions,
                ], [\PDO::PARAM_STR, \PDO::PARAM_STR]);

                return true;
            }
        }

        return false;
    }

    protected function error($s)
    {
        $this->logger->warning('aw:update-united-question: ' . $s);

        return false;
    }

    protected function getQuestions()
    {
        $http = new \HttpBrowser('none', $this->curlDriver);
        $http->GetURL('https://www.united.com/ual/en/us/account/enroll/default');
        $answer = $http->FindSingleNode('//div[@id="divAnswers"]/@data-answers-data');

        if (!empty($answer)) {
            $answer = html_entity_decode($answer, ENT_QUOTES);
            $answer = json_decode($answer, true);

            if (false !== $answer) {
                $existin = $this->answerHelper->getUnitedQuestion();

                if (empty($existin)) {
                    $existin = [];
                }

                for ($i = 0, $iCount = count($answer); $i < $iCount; $i++) {
                    unset($answer[$i]['Used'], $answer[$i]['AnswerKey'], $answer[$i]['AnswersTypeList'], $answer[$i]['QuestionsTypeList']);

                    for ($j = 0, $jCount = count($answer[$i]['Answers']); $j < $jCount; $j++) {
                        unset($answer[$i]['Answers'][$j]['IsSelected']);
                    }

                    $found = false;

                    for ($m = 0, $mCount = count($existin); $m < $mCount; $m++) {
                        if ($existin[$m]['QuestionKey'] == $answer[$i]['QuestionKey'] || $existin[$m]['QuestionText'] == $answer[$i]['QuestionText']) {
                            $existin[$m] = $answer[$i];
                            $found = true;
                        }
                    }
                    $found ?: $existin[] = $answer[$i];
                }
            }
        } else {
            return $this->error('Answer data(json) not found');
        }

        if (false === $answer) {
            return $this->error('Invalid json');
        }

        return $existin;
    }
}
