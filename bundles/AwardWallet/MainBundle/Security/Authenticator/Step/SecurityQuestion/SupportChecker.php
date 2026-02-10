<?php

namespace AwardWallet\MainBundle\Security\Authenticator\Step\SecurityQuestion;

use AwardWallet\MainBundle\Entity\Sitegroup;
use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\Security\Authenticator\Credentials;
use AwardWallet\MainBundle\Security\Authenticator\Step\LocationChangedChecker;
use AwardWallet\MainBundle\Security\TwoFactorAuthentication\QuestionGenerator;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;

class SupportChecker
{
    public const FORCE_SECURITY_QUESTIONS_GROUP_NAME = 'Force security questions';
    public const TEST_IP_ADDRESS_COOKIE_NAME = "TestIpAddress";

    /**
     * @var QuestionGenerator
     */
    protected $questionGenerator;
    /**
     * @var LoggerInterface
     */
    protected $logger;
    /**
     * @var LocationChangedChecker
     */
    private $locationChangedChecker;

    public function __construct(
        QuestionGenerator $questionGenerator,
        LocationChangedChecker $locationChangedChecker,
        LoggerInterface $logger
    ) {
        $this->questionGenerator = $questionGenerator;
        $this->logger = $logger;
        $this->locationChangedChecker = $locationChangedChecker;
    }

    public function supports(Credentials $credentials, array $logContext): bool
    {
        $user = $credentials->getUser();
        $request = $credentials->getRequest();

        $supports =
            $this->isUserInForcingGroup($user, $logContext)
            || $this->isTestIpAddress($request, $logContext)
            || (
                $this->isLocationChanged($credentials, $logContext)
                && $this->isPasswordChanged($user, $logContext)
            );

        if (!$supports) {
            $this->logger->info("User doesn't need security questions", $logContext);

            return false;
        }

        if (!empty($questions = $this->questionGenerator->getQuestions($user))) {
            $this->logger->info(
                'User has ' . count($questions) .
                ' available security question(s)',
                \array_merge($logContext, ['questions_count' => count($questions)])
            );

            return true;
        }

        $this->logger->info("User hasn't any security questions", $logContext);

        return false;
    }

    private function isUserInForcingGroup(Usr $user, array $logContext): bool
    {
        $inGroup = $user
            ->getGroups()
            ->exists(function ($_, Sitegroup $sitegroup) {
                return $sitegroup->getGroupname() == self::FORCE_SECURITY_QUESTIONS_GROUP_NAME;
            });

        if ($inGroup) {
            $this->logger->info('User in forcing group "' . self::FORCE_SECURITY_QUESTIONS_GROUP_NAME . '"', $logContext);

            return true;
        }

        return false;
    }

    private function isLocationChanged(Credentials $credentials, array $logContext): bool
    {
        if ($this->locationChangedChecker->isLocationChanged($credentials, $logContext, $last, $current)) {
            $this->logger->warning("User location changed", \array_merge($logContext, ["current" => $current, "last" => $last]));

            return true;
        }

        return false;
    }

    private function isTestIpAddress(Request $request, array $logContext): bool
    {
        if (!empty($request->cookies->get("TestIpAddress"))) {
            $this->logger->info('Request has forcing "' . self::TEST_IP_ADDRESS_COOKIE_NAME . '" cookie', $logContext);

            return true;
        }

        return false;
    }

    private function isPasswordChanged(Usr $user, array $logContext): bool
    {
        if ($user->isPasswordChangedByResetLinkAfterLastLogon()) {
            $passChangeDate = $user->getChangePasswordDate();
            $message = "Required security questions for user, password changed";

            if ($passChangeDate) {
                $message .= ' at ' . $passChangeDate->format(\DateTime::ATOM);
            }

            $this->logger->warning($message, $logContext);

            return true;
        }

        return false;
    }
}
