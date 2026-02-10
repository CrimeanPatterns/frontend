<?php

namespace AwardWallet\MainBundle\Scanner;

use AwardWallet\MainBundle\Globals\StringUtils;
use AwardWallet\MainBundle\Globals\Utils\None;
use AwardWallet\MainBundle\Globals\Utils\Result\ResultInterface;
use AwardWallet\MainBundle\Security\AntiBruteforceLockerService;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

use function AwardWallet\MainBundle\Globals\Utils\iterFluent\it;
use function AwardWallet\MainBundle\Globals\Utils\Result\fail;
use function AwardWallet\MainBundle\Globals\Utils\Result\success;

class AntibruteforceChecker
{
    public const EMAIL_CHECK = 'email';
    public const PASSWORD_CHECK = 'password';
    public const IP_CHECK = 'ip';
    public const USER_CHECK = 'user';

    private const CHECK_ORDER = [
        self::IP_CHECK => 0,
        self::USER_CHECK => 1,
        self::EMAIL_CHECK => 2,
        self::PASSWORD_CHECK => 3,
    ];

    /**
     * @var RequestStack
     */
    private $requestStack;
    /**
     * @var AntiBruteforceLockerService[]
     */
    private $lockers = [];
    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(
        RequestStack $requestStack,
        AntiBruteforceLockerService $ipLocker,
        AntiBruteforceLockerService $userLocker,
        AntiBruteforceLockerService $emailLocker,
        AntiBruteforceLockerService $passwordLocker,
        LoggerInterface $logger
    ) {
        $this->requestStack = $requestStack;
        $this->lockers = [
            self::IP_CHECK => $ipLocker,
            self::USER_CHECK => $userLocker,
            self::EMAIL_CHECK => $emailLocker,
            self::PASSWORD_CHECK => $passwordLocker,
        ];
        $this->logger = $logger;
    }

    /**
     * @return ResultInterface<None, string>
     */
    public function check(array $checksMap): ResultInterface
    {
        if (!it($checksMap)
            ->keys()
            ->all(function (string $check) { return isset(self::CHECK_ORDER[$check]); })
        ) {
            throw new \LogicException('Unknown checks');
        }

        \uksort($checksMap, function ($check1, $check2) { return self::CHECK_ORDER[$check1] <=> self::CHECK_ORDER[$check2]; });
        $request = $this->requestStack->getCurrentRequest();

        if ($request && isset($checksMap[self::IP_CHECK])) {
            $checksMap[self::IP_CHECK] = $request->getClientIp();
        } else {
            unset($checksMap[self::IP_CHECK]);
        }

        foreach ($checksMap as $checkerName => $checkerKey) {
            $this->logger->info("checking mailbox for lockout ({$checkerName})", ['checker' => $checkerName]);
            $error = $this->lockers[$checkerName]->checkForLockout((string) $checkerKey);

            if (StringUtils::isNotEmpty($error)) {
                $this->logger->warning("mailbox lockout check failed ({$checkerName})", ['checker' => $checkerName]);

                return fail($error);
            }

            $this->logger->info("mailbox lockout check passed ({$checkerName})", ['checker' => $checkerName]);
        }

        return success();
    }
}
