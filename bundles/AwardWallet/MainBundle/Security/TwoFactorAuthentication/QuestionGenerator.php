<?php

namespace AwardWallet\MainBundle\Security\TwoFactorAuthentication;

use AwardWallet\Common\PasswordCrypt\PasswordDecryptor;
use AwardWallet\MainBundle\Entity\UserQuestion;
use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\Security\Encryptor\Encryptor;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class QuestionGenerator
{
    private const CACHE_KEY = 'security_questions_v2';

    private const TYPE_PASSWORD = 'password';
    private const TYPE_QUESTION = 'question';
    private const TYPE_USER_QUESTION = 'user_question';

    /**
     * @var EntityManagerInterface
     */
    private $entityManager;
    /**
     * @var TranslatorInterface
     */
    private $translator;
    /**
     * @var \Memcached
     */
    private $memcached;
    /**
     * @var LoggerInterface
     */
    private $logger;

    private PasswordDecryptor $passwordDecryptor;
    private Encryptor $encryptor;

    public function __construct(
        EntityManagerInterface $entityManager,
        TranslatorInterface $translator,
        \Memcached $memcached,
        LoggerInterface $logger,
        PasswordDecryptor $passwordDecryptor,
        Encryptor $encryptor
    ) {
        $this->entityManager = $entityManager;
        $this->translator = $translator;
        $this->memcached = $memcached;
        $this->logger = $logger;
        $this->passwordDecryptor = $passwordDecryptor;
        $this->encryptor = $encryptor;
    }

    public function getQuestions(Usr $user)
    {
        $cached = $this->memcached->get(self::CACHE_KEY . '_' . $user->getId());

        if (!empty($cached)) {
            return $cached['result'];
        }

        $userQuestions = $this->loadUserQuestions($user);
        $result =
        $context = [];

        if (!empty($userQuestions)) {
            foreach ($userQuestions as $entry) {
                $question = $this->translator->trans($entry['question']);
                $result[] = [
                    'question' => $question,
                    'maskInput' => false,
                    'order' => $entry['order'],
                ];
                $context[$question] = [
                    'type' => self::TYPE_USER_QUESTION,
                    'key' => $entry['question'],
                ];
            }
        }

        if (empty($userQuestions)) {
            $passwords = $this->loadAccountPasswords($user);

            for ($count = 0; $count < 3 && !empty($passwords); $count++) {
                $index = array_rand($passwords, 1);
                $entry = $passwords[$index];
                unset($passwords[$index]);
                $question = $this->translator->trans(/** @Desc("What is the password for your %provider% account %login%?") */ "question.what_password", ["%provider%" => $entry['ShortName'], '%login%' => $entry["Login"]]);
                $result[] = [
                    'question' => $question,
                    'maskInput' => true,
                    'accountId' => $entry['AccountID'],
                ];
                $context[$question] = [
                    'type' => self::TYPE_PASSWORD,
                    'accountId' => $entry['AccountID'],
                ];
            }
        }

        $questions = $this->loadQuestions($user);

        for ($count = 0; $count < 3 && count($questions) > 0; $count++) {
            $index = array_rand($questions, 1);
            $entry = $questions[$index];
            unset($questions[$index]);
            $result[] = [
                'question' => $entry['Question'],
                'maskInput' => false,
                'answerId' => $entry['AnswerID'],
            ];
            $context[$entry['Question']] = [
                'type' => self::TYPE_QUESTION,
            ];
        }

        $this->memcached->set(self::CACHE_KEY . '_' . $user->getId(), ['result' => $result, 'context' => $context], 300);

        return $result;
    }

    public function validate(Usr $user, $question, $answer)
    {
        $cached = $this->memcached->get(self::CACHE_KEY . '_' . $user->getId());

        if (empty($cached)) {
            $this->logger->warning("no cached questions for this user", ["UserID" => $user->getId()]);

            return false;
        }

        if (!isset($cached['context'][$question])) {
            $this->logger->warning("no context for this question", ["UserID" => $user->getId(), "Question" => $question]);

            return false;
        }

        if ($cached['context'][$question]['type'] == self::TYPE_USER_QUESTION) {
            $questions = $this->loadUserQuestions($user);
            $key = $cached['context'][$question]['key'];

            foreach ($questions as $entry) {
                if ($entry['question'] == $key && $this->encryptor->decrypt($entry['answer']) == $answer) {
                    $this->logger->info('given valid answer for security question', ['UserID' => $user->getId(), 'Question' => $question]);

                    return true;
                }
            }
        }

        if ($cached['context'][$question]['type'] == self::TYPE_QUESTION) {
            $questions = $this->loadQuestions($user);

            foreach ($questions as $pair) {
                if ($pair['Question'] == $question && $pair['Answer'] == $answer) {
                    $this->logger->info("given valid answer for question", ["UserID" => $user->getId(), "Question" => $question]);

                    return true;
                }
            }
        }

        if ($cached['context'][$question]['type'] == self::TYPE_PASSWORD) {
            $password = $this->entityManager->getConnection()->executeQuery(
                "select Pass from Account where AccountID = :accountId",
                ["accountId" => $cached['context'][$question]['accountId']]
            )->fetchColumn();
            $password = $this->passwordDecryptor->decrypt($password);

            if (!empty($answer) && $password == $answer) {
                $this->logger->info("given valid password", ["UserID" => $user->getId(), "Question" => $question, "AccountID" => $cached['context'][$question]['accountId']]);

                return true;
            }
        }

        $this->logger->warning("invalid answer for question", ["UserID" => $user->getId(), "Question" => $question]);

        return false;
    }

    private function loadAccountPasswords(Usr $user)
    {
        return $this->entityManager->getConnection()->executeQuery("
            select 
                Account.AccountID,
                Provider.ShortName,
                Account.Login
            from
                Account
                join Provider on Account.ProviderID = Provider.ProviderID
            where
                Account.UserID = :userId 
                and Account.UserAgentID is null
                and Account.ErrorCode = " . ACCOUNT_CHECKED . "
                and Account.SavePassword = " . SAVE_PASSWORD_DATABASE . "
                and Provider.PasswordRequired = 1
                and Account.Pass <> '' and Account.Pass is not null",
            ["userId" => $user->getId()]
        )->fetchAll(\PDO::FETCH_ASSOC);
    }

    private function loadQuestions(Usr $user)
    {
        return $this->entityManager->getConnection()->executeQuery("
            select 
                Answer.AnswerID,
                Answer.Question,
                Answer.Answer
            from
                Answer
                join Account on Answer.AccountID = Account.AccountID
            where 
                Account.UserID = :userId
                and Account.UserAgentID is null
                and Answer.Valid = 1
                and Account.ErrorCode = " . ACCOUNT_CHECKED . "
                and Answer.Question not like '%code%'",
            ["userId" => $user->getId()]
        )->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Searches for security questions in DB.
     */
    private function loadUserQuestions(Usr $user): array
    {
        $queryBuilder = $this->entityManager->createQueryBuilder()
            ->select('uq')
            ->from(UserQuestion::class, 'uq');

        return $queryBuilder
            ->where('uq.user = :userId')
            ->setParameter('userId', $user->getId())
            ->getQuery()
            ->getResult(Query::HYDRATE_ARRAY);
    }
}
