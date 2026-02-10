<?php

namespace AwardWallet\MainBundle\Security\Authenticator\Step\SecurityQuestion;

use AwardWallet\MainBundle\Security\Authenticator\Credentials;
use AwardWallet\MainBundle\Security\Authenticator\Step\AbstractStep;
use AwardWallet\MainBundle\Security\TwoFactorAuthentication\QuestionGenerator;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class SecurityQuestionsStep extends AbstractStep
{
    public const ID = 'security_question';

    /**
     * @var QuestionGenerator
     */
    protected $questionGenerator;
    /**
     * @var LoggerInterface
     */
    protected $logger;
    /**
     * @var TranslatorInterface
     */
    protected $translator;
    /**
     * @var SupportChecker
     */
    private $supportsChecker;

    public function __construct(
        QuestionGenerator $questionGenerator,
        LoggerInterface $logger,
        TranslatorInterface $translator,
        SupportChecker $supportsChecker
    ) {
        $this->questionGenerator = $questionGenerator;
        $this->logger = $logger;
        $this->translator = $translator;
        $this->supportsChecker = $supportsChecker;
    }

    protected function supports(Credentials $credentials): bool
    {
        return $this->supportsChecker->supports($credentials, $this->getLogContext($credentials));
    }

    protected function doCheck(Credentials $credentials): void
    {
        $user = $credentials->getUser();
        $questions = $this->questionGenerator->getQuestions($user);

        if (!$questions) {
            return;
        }

        $clientAnswers = $credentials->getStepData()->getQuestions();

        if ($clientAnswers) {
            foreach ($clientAnswers as $answer) {
                if (!$this->questionGenerator->validate($user, $answer->getQuestion(), $answer->getAnswer())) {
                    $this->logger->warning('Invalid answer for question', $this->getLogContext(
                        $credentials,
                        $this->makeQuestionLogContext($answer->getQuestion(), $questions)
                    ));
                    $this->throwErrorException(
                        $this->translator->trans(
                            /** @Desc("The answer you provided does not match our records. Please try again.") */
                            "login.security_answer_invalid"
                        ),
                        $questions
                    );
                }
            }

            $this->logger->info('Answers are valid', $this->getLogContext($credentials));
        } else {
            $this->logger->warning('No client answers were provided, answers required', $this->getLogContext($credentials));
            $this->throwRequiredException(
                $this->translator->trans(/** @Desc("Security Answer Required") */ "login.security_answer_required"),
                $questions
            );
        }
    }

    private function makeQuestionLogContext(string $clientQuestion, array $questions): array
    {
        foreach ($questions as $question) {
            if ($question['question'] !== $clientQuestion) {
                continue;
            }

            if (isset($question['answerId'])) {
                return ['answerId' => $question['answerId']];
            }

            if (isset($question['accountId'])) {
                return ['accountId' => $question['accountId']];
            }
        }

        return [];
    }
}
