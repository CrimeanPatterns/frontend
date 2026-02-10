<?php

namespace AwardWallet\Tests\Unit\MainBundle\Security\Authenticator\Step\SecurityQuestion;

use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\Security\Authenticator\Credentials;
use AwardWallet\MainBundle\Security\Authenticator\Step\CheckResult;
use AwardWallet\MainBundle\Security\Authenticator\Step\SecurityQuestion\Question;
use AwardWallet\MainBundle\Security\Authenticator\Step\SecurityQuestion\SecurityQuestionsStep;
use AwardWallet\MainBundle\Security\Authenticator\Step\SecurityQuestion\SupportChecker;
use AwardWallet\MainBundle\Security\Authenticator\Step\StepData;
use AwardWallet\MainBundle\Security\TwoFactorAuthentication\QuestionGenerator;
use AwardWallet\Tests\Modules\Utils\Prophecy\ArgumentExtended;
use AwardWallet\Tests\Unit\MainBundle\Security\Authenticator\Step\ExpectStepExceptionTrait;
use AwardWallet\Tests\Unit\MainBundle\Security\Authenticator\Step\MakeTranslatorMockTrait;
use Codeception\TestCase\Test;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @group frontend-unit
 * @group security
 * @coversDefaultClass \AwardWallet\MainBundle\Security\Authenticator\Step\SecurityQuestion\SecurityQuestionsStep
 */
class SecurityQuestionsStepTest extends Test
{
    use ProphecyTrait;
    use MakeTranslatorMockTrait;
    use ExpectStepExceptionTrait;

    public function testDoNothingWhenEmptyQuestions()
    {
        $user = new Usr();
        $questionGenerator = $this->prophesize(QuestionGenerator::class);
        $questionGenerator
            ->getQuestions($user)
            ->willReturn([])
            ->shouldBeCalledOnce();

        $credentials = (new Credentials(new StepData(), new Request()))
            ->setUser($user);

        $step = new SecurityQuestionsStep(
            $questionGenerator->reveal(),
            $this->prophesize(LoggerInterface::class)->reveal(),
            $this->prophesize(TranslatorInterface::class)->reveal(),
            $this->makeSupportingCheckerMock($credentials)
        );

        // expect SUCCESS because of early return in doCheck
        $this->assertEquals(CheckResult::SUCCESS, $step->check($credentials));
    }

    public function testNoClientAnswersProvided()
    {
        $user = new Usr();
        $questionGenerator = $this->prophesize(QuestionGenerator::class);
        $questionGenerator
            ->getQuestions($user)
            ->willReturn([1])
            ->shouldBeCalledOnce();

        $credentials = (new Credentials(new StepData(), new Request()))
            ->setUser($user);

        $logger = $this->prophesize(LoggerInterface::class);
        $logger
            ->warning('No client answers were provided, answers required', Argument::type('array'))
            ->shouldBeCalledOnce();

        $step = new SecurityQuestionsStep(
            $questionGenerator->reveal(),
            $logger->reveal(),
            $this->makeTranslatorMock([
                "login.security_answer_required" => $error = "Security Answer Required",
            ]),
            $this->makeSupportingCheckerMock($credentials)
        );

        $this->expectStepRequiredException($error);

        $step->check($credentials);
    }

    public function testInvalidAnswer()
    {
        $user = new Usr();

        $credentials =
            (new Credentials(
                (new StepData())
                    ->setQuestions([
                        new Question(
                            $question1 = 'question1',
                            $answer1 = 'correctAnswer1'
                        ),
                        new Question(
                            $question2 = 'question2',
                            $answer2 = 'correctAnswer2'
                        ),
                        new Question(
                            $question3 = 'question3',
                            $answer3 = 'correctAnswer3'
                        ),
                    ]),
                new Request()
            ))
            ->setUser($user);

        $questionGenerator = $this->prophesize(QuestionGenerator::class);
        $questionGenerator
            ->getQuestions($user)
            ->willReturn([
                [
                    'question' => $question1,
                    'answerId' => 100,
                ],
                [
                    'question' => $question2, // this question should fail further
                    'answerId' => 200,
                ],
                [
                    'question' => $question3,
                    'answerId' => 300,
                ],
            ])
            ->shouldBeCalledOnce();

        $questionGenerator
            ->validate($user, $question1, $answer1)
            ->willReturn(true)
            ->shouldBeCalledOnce();

        $questionGenerator
            ->validate($user, $question2, $answer2)
            ->willReturn(false)
            ->shouldBeCalledOnce();

        $questionGenerator
            ->validate($user, $question3, $answer3)
            ->shouldNotBeCalled();

        $logger = $this->prophesize(LoggerInterface::class);
        $logger
            ->warning('Invalid answer for question', ArgumentExtended::containsArray(['answerId' => 200]))
            ->shouldBeCalledOnce();

        $step = new SecurityQuestionsStep(
            $questionGenerator->reveal(),
            $logger->reveal(),
            $this->makeTranslatorMock([
                "login.security_answer_invalid" => $error = "The answer you provided does not match our records. Please try again.",
            ]),
            $this->makeSupportingCheckerMock($credentials)
        );

        $this->expectStepErrorException($error);

        $step->check($credentials);
    }

    protected function testAllAnswersIsValid()
    {
        $user = new Usr();
        $credentials =
            (new Credentials(
                (new StepData())
                    ->setQuestions([
                        new Question(
                            $question1 = 'question1',
                            $answer1 = 'correctAnswer1'
                        ),
                    ]),
                new Request()
            ))
            ->setUser($user);

        $questionGenerator = $this->prophesize(QuestionGenerator::class);
        $questionGenerator
            ->getQuestions($user)
            ->willReturn([
                [
                    'question' => $question1,
                    'answerId' => 100,
                ],
            ])
            ->shouldBeCalledOnce();

        $questionGenerator
            ->validate($user, $question1, $answer1)
            ->willReturn(true)
            ->shouldBeCalledOnce();

        $logger = $this->prophesize(LoggerInterface::class);
        $logger
            ->warning('Answers are valid', Argument::type('array'))
            ->shouldBeCalledOnce();

        $step = new SecurityQuestionsStep(
            $questionGenerator->reveal(),
            $logger->reveal(),
            $this->prophesize(TranslatorInterface::class)->reveal(),
            $this->makeSupportingCheckerMock($credentials)
        );

        $this->assertEquals(CheckResult::SUCCESS, $step->check($credentials));
    }

    protected function makeSupportingCheckerMock(Credentials $credentials): SupportChecker
    {
        $supportsChecker = $this->prophesize(SupportChecker::class);
        $supportsChecker
            ->supports($credentials, Argument::type('array'))
            ->willReturn(true)
            ->shouldBeCalledOnce();

        return $supportsChecker->reveal();
    }
}
