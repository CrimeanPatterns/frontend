<?php

namespace AwardWallet\Tests\Unit\MainBundle\Security\Question;

use AwardWallet\Common\PasswordCrypt\PasswordDecryptor;
use AwardWallet\MainBundle\Entity\Account;
use AwardWallet\MainBundle\Entity\Answer;
use AwardWallet\MainBundle\Entity\UserQuestion;
use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\Globals\StringUtils;
use AwardWallet\MainBundle\Security\Encryptor\Encryptor;
use AwardWallet\MainBundle\Security\TwoFactorAuthentication\QuestionGenerator;
use AwardWallet\Tests\Unit\BaseContainerTest;
use Symfony\Contracts\Translation\TranslatorInterface;

class QuestionGeneratorTest extends BaseContainerTest
{
    private const PROVIDER_NAME = 'TestProvider';

    private ?int $userId;
    private ?Usr $user;
    private ?TranslatorInterface $translator;
    private ?QuestionGenerator $generator;
    private ?Encryptor $encryptor;

    public function _before()
    {
        parent::_before();
        $this->userId = $this->aw->createAwUser(null, null, [], true);
        $this->user = $this->em->getRepository(Usr::class)->find($this->userId);
        $this->translator = $this->container->get('translator');
        $this->encryptor = $this->container->get(Encryptor::class);
        $this->generator = new QuestionGenerator(
            $this->em,
            $this->translator,
            $this->container->get(\Memcached::class),
            $this->container->get('logger'),
            $this->container->get(PasswordDecryptor::class),
            $this->encryptor
        );
    }

    public function _after()
    {
        $this->userId = null;
        $this->user = null;
        $this->translator = null;
        $this->encryptor = null;
        $this->generator = null;
        parent::_after();
    }

    public function testGetPasswordQuestions()
    {
        $providerId = $this->aw->createAwProvider('testProvider' . StringUtils::getRandomCode(8), StringUtils::getRandomCode(8), [
            'Kind' => PROVIDER_KIND_HOTEL,
            'ShortName' => self::PROVIDER_NAME,
            'PasswordRequired' => 1,
        ]);
        $accountIds = [];

        foreach (self::getPasswordQuestionsArray() as $entry) {
            $accountIds[$entry['login']] = $this->aw->createAwAccount($this->userId, $providerId, $entry['login'], $entry['password'], ['ErrorCode' => ACCOUNT_CHECKED]);
        }

        $expected = array_map(function ($entry) use ($accountIds) {
            return [
                'question' => $this->translator->trans('question.what_password', [
                    '%provider%' => self::PROVIDER_NAME,
                    '%login%' => $entry['login'],
                ]),
                'maskInput' => true,
                'accountId' => $accountIds[$entry['login']],
            ];
        }, self::getPasswordQuestionsArray());

        $result = $this->generator->getQuestions($this->user);
        usort($result, function ($a, $b) {
            return strcmp($a['question'], $b['question']);
        });

        $this->assertEquals($expected, $result);
    }

    public function testGetProviderQuestions()
    {
        $providerId = $this->aw->createAwProvider('testProvider' . StringUtils::getRandomCode(8), StringUtils::getRandomCode(8), [
            'Kind' => PROVIDER_KIND_HOTEL,
            'ShortName' => self::PROVIDER_NAME,
            'PasswordRequired' => 1,
        ]);
        /** @var Answer[] $answers */
        $answers = [];

        foreach (self::getProviderQuestionsArray() as $entry) {
            $account = $this->em->getRepository(Account::class)->find(
                $this->aw->createAwAccount($this->userId, $providerId, 'login' . StringUtils::getRandomCode(8), 'password', ['ErrorCode' => ACCOUNT_CHECKED])
            );
            $answers[$entry['question']] = (new Answer())
                ->setAccountid($account)
                ->setQuestion($entry['question'])
                ->setAnswer($entry['answer']);

            $this->em->persist($answers[$entry['question']]);
        }

        $this->em->flush();

        $expected = array_map(function ($entry) use ($answers) {
            return [
                'question' => $entry['question'],
                'maskInput' => false,
                'answerId' => $answers[$entry['question']]->getAnswerid(),
            ];
        }, self::getProviderQuestionsArray());

        $result = array_filter($this->generator->getQuestions($this->user), function ($entry) {
            return $entry['maskInput'] === false;
        });
        usort($result, function ($a, $b) {
            return strcmp($a['question'], $b['question']);
        });

        $this->assertEquals($expected, $result);
    }

    public function testGetSecurityQuestions()
    {
        foreach (self::getUserQuestionsArray() as $entry) {
            $question = (new UserQuestion())
                ->setUser($this->user)
                ->setOrder($entry['order'])
                ->setQuestion($entry['question'])
                ->setAnswer($this->encryptor->encrypt($entry['answer']));

            $this->em->persist($question);
        }

        $this->em->flush();

        $expected = array_map(function ($entry) {
            return [
                'question' => $this->translator->trans($entry['question']),
                'maskInput' => false,
                'order' => $entry['order'],
            ];
        }, self::getUserQuestionsArray());
        $result = $this->generator->getQuestions($this->user);

        $this->assertEquals($expected, $result);
    }

    private static function getPasswordQuestionsArray(): array
    {
        return [
            ['login' => 'hilton', 'password' => '11000000'],
            ['login' => 'hyatt', 'password' => '22000000'],
            ['login' => 'marriott', 'password' => '33000000'],
        ];
    }

    private static function getProviderQuestionsArray(): array
    {
        return [
            ['question' => 'In what city were you born?', 'answer' => 'Yokohama'],
            ['question' => 'What is the first company you worked for?', 'answer' => 'Square Enix'],
            ['question' => 'What was the make of your first car?', 'answer' => 'Subaru'],
        ];
    }

    private static function getUserQuestionsArray(): array
    {
        return [
            [
                'order' => UserQuestion::ORDER_FIRST,
                'question' => UserQuestion::CHILDHOOD_NICKNAME,
                'answer' => 'Ryuuji Takasu',
            ],
            [
                'order' => UserQuestion::ORDER_SECOND,
                'question' => UserQuestion::FAVORITE_CHILDHOOD_FRIEND,
                'answer' => 'Yuusaku Kitamura',
            ],
            [
                'order' => UserQuestion::ORDER_THIRD,
                'question' => UserQuestion::OLDEST_SIBLING_MIDDLE_NAME,
                'answer' => 'Taiga Aisaka',
            ],
        ];
    }
}
