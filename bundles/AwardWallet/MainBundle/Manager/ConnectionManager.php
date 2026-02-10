<?php

namespace AwardWallet\MainBundle\Manager;

use AwardWallet\MainBundle\Entity\Invitecode;
use AwardWallet\MainBundle\Entity\Invites;
use AwardWallet\MainBundle\Entity\Repositories\InvitesRepository;
use AwardWallet\MainBundle\Entity\Repositories\OwnerRepository;
use AwardWallet\MainBundle\Entity\Repositories\TimelineShareRepository;
use AwardWallet\MainBundle\Entity\Repositories\UseragentRepository;
use AwardWallet\MainBundle\Entity\Repositories\UsrRepository;
use AwardWallet\MainBundle\Entity\Useragent;
use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\FrameworkExtension\FormErrorHandler;
use AwardWallet\MainBundle\FrameworkExtension\Mailer\Mailer;
use AwardWallet\MainBundle\FrameworkExtension\Mailer\Template\User\UserAgent\BusinessChoseToShare;
use AwardWallet\MainBundle\FrameworkExtension\Mailer\Template\User\UserAgent\ConnectionApproved;
use AwardWallet\MainBundle\FrameworkExtension\Mailer\Template\User\UserAgent\ConnectionRequest;
use AwardWallet\MainBundle\FrameworkExtension\Mailer\Template\User\UserAgent\InvitationToOwnership;
use AwardWallet\MainBundle\FrameworkExtension\Mailer\Template\User\UserAgent\UninviteOwnership;
use AwardWallet\MainBundle\FrameworkExtension\Mailer\Template\User\UserAgent\UserChoseToShare;
use AwardWallet\MainBundle\Globals\StringUtils;
use AwardWallet\MainBundle\Globals\Utils\Result\Fail;
use AwardWallet\MainBundle\Globals\Utils\Result\ResultInterface;
use AwardWallet\MainBundle\Security\AntiBruteforceLockerService;
use AwardWallet\MainBundle\Security\InviteLocker;
use AwardWallet\MainBundle\Service\Cache\Invalidator;
use Doctrine\Common\Collections\Criteria;
use Doctrine\DBAL\FetchMode;
use Doctrine\ORM\EntityManager;
use Psr\Log\LoggerInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\ConstraintViolationInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

use function AwardWallet\MainBundle\Globals\Utils\Result\fail;
use function AwardWallet\MainBundle\Globals\Utils\Result\success;
use function AwardWallet\MainBundle\Globals\Utils\stmt\stmtAssoc;

class ConnectionManager
{
    /**
     * @var UseragentRepository
     */
    private $useragentRep;
    /**
     * @var EntityManager
     */
    private $entityManager;
    /**
     * @var InvitesRepository
     */
    private $invitesRep;
    /**
     * @var Mailer
     */
    private $mailer;
    /**
     * @var Invalidator
     */
    private $invalidator;
    /**
     * @var TranslatorInterface
     */
    private $translator;
    /**
     * @var LoggerInterface
     */
    private $logger;
    /**
     * @var InviteLocker
     */
    private $inviteLocker;
    /**
     * @var UsrRepository
     */
    private $userRep;
    /**
     * @var ValidatorInterface
     */
    private $validator;
    /**
     * @var FormErrorHandler
     */
    private $formErrorHandler;
    /**
     * @var AntiBruteforceLockerService
     */
    private $connectionSearchLocker;
    /**
     * @var TimelineShareRepository
     */
    private $timelineShareRep;
    /**
     * @var AccountManager
     */
    private $accountManager;
    private RouterInterface $router;

    public function __construct(
        UseragentRepository $useragentRep,
        InvitesRepository $invitesRep,
        UsrRepository $userRep,
        TimelineShareRepository $timelineShareRep,
        EntityManager $entityManager,
        Mailer $mailer,
        Invalidator $invalidator,
        TranslatorInterface $translator,
        LoggerInterface $logger,
        InviteLocker $inviteLocker,
        ValidatorInterface $validator,
        FormErrorHandler $formErrorHandler,
        AntiBruteforceLockerService $connectionSearchLocker,
        AccountManager $accountManager,
        RouterInterface $router
    ) {
        $this->useragentRep = $useragentRep;
        $this->entityManager = $entityManager;
        $this->invitesRep = $invitesRep;
        $this->mailer = $mailer;
        $this->invalidator = $invalidator;
        $this->translator = $translator;
        $this->logger = $logger;
        $this->inviteLocker = $inviteLocker;
        $this->userRep = $userRep;
        $this->validator = $validator;
        $this->formErrorHandler = $formErrorHandler;
        $this->connectionSearchLocker = $connectionSearchLocker;
        $this->timelineShareRep = $timelineShareRep;
        $this->accountManager = $accountManager;
        $this->router = $router;
    }

    public function saveAgent(Usr $user, Useragent $agent, $isInvite = false)
    {
        $shareCode = StringUtils::getRandomCode(10);

        $agent->setAgentid($user);
        $alias = $this->useragentRep->createAlias($user, $agent->getFirstname(), $agent->getLastname());

        if (\trim($alias) === '') {
            $agent->setAlias(null);
        } else {
            $agent->setAlias($alias);
        }

        $agent->setAccesslevel(ACCESS_WRITE);
        $agent->setIsapproved(true);
        $agent->setSharecode($shareCode);

        if ($isInvite) {
            $agent->setSharedate(new \DateTime());
        }

        $this->entityManager->persist($agent);
        $this->entityManager->flush();

        if (!$user->isBusiness()) {
            $timelineShareRep = $this->entityManager->getRepository(\AwardWallet\MainBundle\Entity\TimelineShare::class);

            /** @var UserAgent $connection */
            foreach ($this->useragentRep->findBy(['clientid' => $user]) as $connection) {
                $target = $this->useragentRep->findOneBy([
                    'agentid' => $user,
                    'clientid' => $connection->getAgentid(),
                ]);

                if (null !== $target && $connection->getTripsharebydefault() && $target->isApproved()) {
                    $timelineShareRep->addTimelineShare($connection, $agent);
                }
            }
        }

        $this->invalidator->connectionsChanged($user->getUserid(), false);

        if ($isInvite) {
            $this->inviteFamilyMember($agent->getEmail(), $agent, $user);
        }
    }

    public function connectUser(Usr $invitee, Usr $user)
    {
        $this->useragentRep->inviteUser($user, $invitee);

        if (self::isEmailFromRestrictedDomain($invitee->getEmail())) {
            return;
        }

        // Send email
        $template = $user->isBusiness() ?
            new BusinessChoseToShare($invitee) :
            new UserChoseToShare($invitee);
        $template->inviter = $user;
        $message = $this->mailer->getMessageByTemplate($template);
        $this->mailer->send($message, [Mailer::OPTION_THROW_ON_FAILURE => true]);

        $this->invalidator->connectionsChanged($user->getUserid(), false);
    }

    public function inviteUser($email, Usr $user)
    {
        $em = $this->entityManager;
        $inviteRep = $em->getRepository(\AwardWallet\MainBundle\Entity\Invitecode::class);

        $invite = $em->getRepository(\AwardWallet\MainBundle\Entity\Invites::class)->findOneBy([
            'inviterid' => $user,
            'email' => $email,
        ]);

        $inviteCode = $inviteRep->findOneBy([
            'userid' => $user,
            'email' => $email,
        ]);

        if ($inviteCode) {
            $inviteCode->setCreationdate(new \DateTime());

            if ($invite) {
                $invite->setInvitedate(new \DateTime());
            }

            $inviteCode->setSource('*');
            $this->logger->info("inviting user, code already exists", ["email" => $email, "code" => $inviteCode->getCode()]);
        } else {
            $inviteCode = new Invitecode();
            $inviteCode->setUserid($user);
            $inviteCode->setCode(StringUtils::getRandomCode(20, true));
            $inviteCode->setEmail($email);
            $inviteCode->setCreationdate(new \DateTime());
            $inviteCode->setSource('*');
            $em->persist($inviteCode);
            $this->logger->info("inviting user, created new code", ["email" => $email, "code" => $inviteCode->getCode()]);
        }
        $em->flush();
        $inviteCode2 = $inviteRep->findOneBy([
            'code' => $inviteCode->getCode(),
            'userid' => $user,
        ]);

        if (!$invite) {
            $invite = new Invites();
            $invite->setInviterid($user);
            $invite->setEmail($email);
            $invite->setInvitedate(new \DateTime());
            $invite->setCode($inviteCode->getCode());
            $em->persist($invite);
            $em->flush();
            $this->logger->info("inviting user, created new invite", ["email" => $email, "code" => $inviteCode->getCode()]);
        }

        if (self::isEmailFromRestrictedDomain($email)) {
            return;
        }

        // Send email
        $mailer = $this->mailer;
        $template = new ConnectionRequest($email);
        $template->inviter = $user;
        $template->inviteCode = $inviteCode2;
        $template->invite = $invite;
        $message = $mailer->getMessageByTemplate($template);
        $mailer->send($message, [Mailer::OPTION_THROW_ON_FAILURE => true]);
    }

    /**
     * @internal param $email
     * @return bool
     */
    public function inviteFamilyMember($email, Useragent $invitee, Usr $inviter)
    {
        $invRep = $this->entityManager->getRepository(\AwardWallet\MainBundle\Entity\Invites::class);
        $invCodeRep = $this->entityManager->getRepository(\AwardWallet\MainBundle\Entity\Invitecode::class);
        $invite = $invRep->findOneBy(['inviterid' => $inviter, 'email' => $email, 'approved' => false]);
        $uaExists = $this->entityManager->getConnection()->fetchAll(
            '
            SELECT 
                ua.*
            FROM 
                UserAgent ua
                JOIN Usr u ON (u.UserID = ua.ClientID) 
            WHERE 
                    u.Email    = ?
                AND ua.AgentID = ?',
            [$email, $inviter->getUserid()],
            [\PDO::PARAM_STR, \PDO::PARAM_INT]
        );

        if (
            !empty($uaExists)
            || ($invite && $invite->getFamilyMember() && $invite->getFamilyMember() !== $invitee)
        ) {
            return false;
        }

        if (self::isEmailFromRestrictedDomain($email)) {
            return false;
        }

        if (!$invite) {
            $invite = $invRep->findOneBy(['inviterid' => $inviter, 'email' => $email, 'approved' => true]);

            if (empty($invite)) {
                $invite = new Invites();
                $invite->setInviterid($inviter);
                $invite->setInviteeid(null);
                $invite->setFamilyMember($invitee);
                $invite->setEmail($email);
            } else {
                $invite->setApproved(false);
            }

            $invite->setCode(StringUtils::getRandomCode(20, true));
            $this->entityManager->persist($invite);
        }
        $invite->setInvitedate(new \DateTime());
        $invitee->setSharedate(new \DateTime());
        $invitee->setEmail($email);

        $invCode = $invCodeRep->findOneBy(['code' => $invite->getCode()]);

        if (!$invCode) {
            $invCode = new Invitecode();
            $invCode->setCode($invite->getCode());
            $invCode->setUserid($inviter);
            $invCode->setCreationdate(new \DateTime());
            $invCode->setEmail($email);
            $this->entityManager->persist($invCode);
        }

        $this->entityManager->flush();

        $accounts = $this->entityManager->createQuery("
          SELECT
            a
          FROM
            AwardWallet\MainBundle\Entity\Account a
            JOIN a.providerid p
          WHERE
            a.user = :user
            AND a.userAgent = :ua
            AND {$inviter->getProviderFilter("p.state")}
            AND a.state not in (:states)
          ORDER BY p.displayname, a.login
        ")->setParameters([
            'user' => $inviter->getId(),
            'ua' => $invitee,
            'states' => [ACCOUNT_PENDING, ACCOUNT_IGNORED],
        ])->getResult();

        $this->logger->info("inviting family member", ["email" => $email, "UserAgentID" => $invitee->getUseragentid(), "InviterID" => $inviter->getUserid(), "Code" => $invite->getCode()]);

        $template = new InvitationToOwnership($invitee);
        $template->inviter = $inviter;
        $template->accounts = $accounts;
        $template->shareCode = $invite->getCode();

        $message = $this->mailer->getMessageByTemplate($template);

        $this->mailer->send($message, [
            Mailer::OPTION_THROW_ON_FAILURE => true,
            Mailer::OPTION_ON_SUCCESSFUL_SEND => function ($mailer) use ($template, $inviter, $invitee) {
                /** @var Mailer $mailer */
                if ($inviter->isBusiness()) {
                    return;
                }
                $templateInviter = new UninviteOwnership($inviter);
                $templateInviter->invitee = $invitee;
                $templateInviter->accounts = $template->accounts;
                $message = $mailer->getMessageByTemplate($templateInviter);
                $mailer->send($message);
            },
        ]);

        return true;
    }

    public function denyConnection(UserAgent $useragent, Usr $remover): bool
    {
        $removerId = $remover->getUserid();
        $agent = $useragent->getAgentid();
        $client = $useragent->getClientid();

        if (
            !is_null($agent)
            && (
                (!is_null($client) && $client->getUserid() === $removerId)
                || (is_null($client) && $agent->getUserid() === $removerId)
            )
        ) {
            if ($client) {
                if ($remover->isBusiness()) {
                    $last = \count($this->getBusinessAccountAdminIds($remover)) <= 1;

                    if (!($last && $useragent->getAccesslevel() === Useragent::ACCESS_ADMIN)) {
                        $this->denyAgent($agent, $remover);

                        return true;
                    }
                } else {
                    $isLastAdminBusiness = false;

                    if (ACCOUNT_LEVEL_BUSINESS === $agent->getAccountlevel()) {
                        $admins = $this->getBusinessAccountAdminIds($agent);
                        $isLastAdminBusiness = (sizeof($admins) <= 1 && in_array($removerId, $admins));
                    }

                    if (!$isLastAdminBusiness) {
                        $this->denyAgent($agent, $remover);

                        foreach (
                            [
                                [$remover, $agent],
                                [$agent, $remover],
                            ] as [$inviter, $invitee]
                        ) {
                            $invite = $this->entityManager->getRepository(\AwardWallet\MainBundle\Entity\Invitecode::class)->findOneBy([
                                'userid' => $inviter,
                                'email' => $invitee->getEmail(),
                            ]);

                            if ($invite) {
                                $this->entityManager->remove($invite);
                                $this->entityManager->flush();
                            }
                        }

                        return true;
                    }
                }
            } else {
                $this->deleteUserAgent($useragent, $remover);

                return true;
            }
        }

        return false;
    }

    public function cancelInvite(Invitecode $invitecode): void
    {
        $this->entityManager->remove($invitecode);
        $this->entityManager->flush($invitecode);
    }

    public function sendReminderBruteforceSafe(object $subject, Usr $remindingUser, ?string $clientIp, bool $useLinksInError = true): ResultInterface
    {
        if (isset($clientIp)) {
            $error = $this->connectionSearchLocker->checkForLockout($clientIp);

            if (!empty($error)) {
                return fail();
            }
        }

        if (\EMAIL_VERIFIED !== $remindingUser->getEmailverified()) {
            return $this->makeUnverifiedEmailFail($useLinksInError);
        }

        if ($subject instanceof Invitecode) {
            return $this->sendReminderForInviteCodeBruteforceSafe($subject, $remindingUser);
        } elseif ($subject instanceof Useragent) {
            return $this->sendReminderForUseragentBruteforceSafe($subject, $remindingUser);
        } else {
            throw new \LogicException('Unknown type');
        }
    }

    public function inviteFamilyMemberBruteforceSafe(Useragent $userAgent, string $email, Usr $inviter, bool $useLinksInError = true): ResultInterface
    {
        $email = strtolower(trim($email));
        $this->inviteLocker->init($email);

        if (strtolower($inviter->getEmail()) === $email) {
            return fail($this->translator->trans('agents.connect.email.yourself'));
        }

        if (\EMAIL_VERIFIED !== $inviter->getEmailverified()) {
            return $this->makeUnverifiedEmailFail($useLinksInError);
        }

        if ($message = $this->inviteLocker->check()) {
            return fail($this->translator->trans($message));
        }

        if (count($emailErrors = $this->validator->validate($email, new Email()))) {
            /** @var ConstraintViolationInterface $error */
            $error = $emailErrors[0];

            return fail($this->formErrorHandler->getUserFriendlyErrorMessage(
                $error->getMessageTemplate(),
                $error->getParameters(),
                $error->getPlural()
            ));
        }

        /** @var Useragent $existingUserAgent */
        $existingUserAgent = $this->useragentRep->findOneBy([
            'agentid' => $inviter,
            'email' => $email,
        ]);

        /** @var Invites $invite */
        $invite = $this->invitesRep->findOneBy([
            'inviterid' => $inviter,
            'email' => $email,
            'approved' => false,
        ]);

        if ($existingUserAgent && $invite && $existingUserAgent->getEmail() != $invite->getEmail()) {
            return fail('You have already sent an invitation to this email address');
        }

        $success = $this->inviteFamilyMember($email, $userAgent, $inviter);

        if ($success) {
            return success();
        } else {
            return fail('You have already sent an invitation to this email address');
        }
    }

    public function approveConnection(Useragent $useragent, Usr $approver): void
    {
        $reverseUserAgent = $this->useragentRep->findOneBy([
            "agentid" => $approver->getUserid(),
            "clientid" => $useragent->getAgentid()->getUserid(),
            "isapproved" => 1,
        ]);

        if ($useragent) {
            $useragent->setIsapproved(true);
            $this->entityManager->flush($useragent);

            if ($reverseUserAgent) {
                $template = new ConnectionApproved($reverseUserAgent->getClientid());
                $template->connection = $reverseUserAgent;
                $message = $this->mailer->getMessageByTemplate($template);
                $this->mailer->send($message);

                $this->entityManager->flush($useragent->setFirstname(null)->setLastname(null)->setEmail(null));
                $this->entityManager->flush($reverseUserAgent->setFirstname(null)->setLastname(null)->setEmail(null));
            }

            $this->invalidator->connectionsChanged($useragent->getAgentid()->getUserid(), false);
            $this->invalidator->connectionsChanged($useragent->getClientid()->getUserid(), false);
        }
    }

    public function grantAccess(Useragent $useragent, string $type, Usr $owner): void
    {
        $useragent->setPopupShown(true);

        switch ($type) {
            case 'readonly':
                $this->grantReadonlyAccess($useragent);
                $this->entityManager->flush();
                $this->shareAllTimelines($useragent, $owner);

                break;

            case 'full':
                $this->grantFullAccess($useragent);
                $this->entityManager->flush();
                $this->shareAllTimelines($useragent, $owner);

                break;

            case 'never-show':
                $this->entityManager->flush();

                break;

            default:
                throw new \LogicException('Uknown type.');
        }
    }

    public function acceptInviteByRegisteredUser(Usr $registeredUser, Invites $invite): void
    {
        $business = $this->userRep->getBusinessByUser($registeredUser);
        $userAgent = $this->useragentRep->findOneBy(['agentid' => $invite->getInviterid(), 'clientid' => $registeredUser]);

        if (
            $invite->getInviterid() !== $registeredUser
            && (!$business || $invite->getInviterid() !== $business)
            && !$userAgent
        ) {
            $this->useragentRep->inviteUser($invite->getInviterid(), $registeredUser, true);

            if ($invite->getFamilyMember()) {
                $this->accountManager->changeAccountsOwner(
                    OwnerRepository::getOwner($invite->getInviterid(), $invite->getFamilyMember()),
                    OwnerRepository::getOwner($registeredUser)
                );
                $userAgent = $this->useragentRep->findOneBy([
                    'useragentid' => $invite->getFamilyMember()->getUseragentid(),
                    'agentid' => $invite->getInviterid()->getUserid(),
                ]);
                $this->entityManager->remove($userAgent);
                $this->entityManager->flush();
            }

            $this->entityManager->remove($invite);
            $this->entityManager->flush();

            $this->entityManager->getConnection()->executeStatement(
                'DELETE FROM InviteCode WHERE UserID = ? AND Code = ?',
                [
                    $invite->getInviterid()->getId(),
                    $invite->getCode(),
                ]
            );
        }
    }

    public function shareAllTimelines(Useragent $useragent, Usr $owner): void
    {
        $familyMembers = $owner->getFamilyMembers();

        if (!$this->timelineShareRep->findOneBy(['timelineOwner' => $owner, 'userAgent' => $useragent, 'familyMember' => null])) {
            $this->timelineShareRep->addTimelineShare($useragent);
        }

        foreach ($familyMembers as $familyMember) {
            if (!$this->timelineShareRep->findOneBy(['timelineOwner' => $owner, 'userAgent' => $useragent, 'familyMember' => $familyMember])) {
                $this->timelineShareRep->addTimelineShare($useragent, $familyMember);
            }
        }
    }

    public static function isEmailFromRestrictedDomain(?string $email): bool
    {
        return (bool) \preg_match('/[^@]+@([^@]+\.)?qq\.com/', $email ?? '');
    }

    protected function grantReadonlyAccess(Useragent $userAgent): void
    {
        $userAgent->setAccesslevel(ACCESS_READ_ALL);
        $userAgent->setTripAccessLevel(false);
        $userAgent->setSharebydefault(true);
        $userAgent->setTripsharebydefault(true);
    }

    protected function grantFullAccess(Useragent $userAgent): void
    {
        $userAgent->setAccesslevel(ACCESS_WRITE);
        $userAgent->setTripAccessLevel(true);
        $userAgent->setSharebydefault(true);
        $userAgent->setTripsharebydefault(true);
    }

    protected function getUserCriteria($email, Usr $user): Criteria
    {
        $criteria = Criteria::create()
            ->where(
                Criteria::expr()->andX(
                    Criteria::expr()->eq('email', $email),
                    Criteria::expr()->neq('userid', $user->getUserid()),
                    Criteria::expr()->in('accountlevel', [ACCOUNT_LEVEL_FREE, ACCOUNT_LEVEL_AWPLUS])
                )
            );

        return $criteria;
    }

    protected function deleteUserAgent(Useragent $useragent, Usr $remover): void
    {
        $nUserAgentID = $useragent->getUseragentid();
        $dbConnection = $this->entityManager->getConnection();
        $this->invalidator->connectionsChanged(Lookup("UserAgent", "UserAgentID", "AgentID", $useragent->getUseragentid()));
        $this->invalidator->connectionsChanged($remover->getUserid(), false);
        $dbConnection->executeUpdate("update Account set UserAgentID = null where UserAgentID = {$nUserAgentID}");
        $dbConnection->executeUpdate("delete from AccountShare where UserAgentID = {$nUserAgentID}");
        $dbConnection->executeUpdate("delete from UserAgent where UserAgentID = {$nUserAgentID}");
    }

    protected function denyAgent(Usr $agent, Usr $remover): void
    {
        $agentID = $agent->getUserid();
        $dbConnection = $this->entityManager->getConnection();
        $removerId = $remover->getUserid();

        $connection = $dbConnection->executeQuery("select ua.*, a.FirstName, a.LastName, a.Email, a.UserID from UserAgent ua, Usr a where a.UserID = ua.AgentID and ua.AgentID = {$agentID} and ua.ClientID = {$removerId}")->fetch(FetchMode::ASSOCIATIVE);

        if ($connection) {
            $this->deleteUserAgent($this->entityManager->find(Useragent::class, $connection['UserAgentID']), $remover);
        }

        $connection = $dbConnection->executeQuery("select ua.* from UserAgent ua where ua.ClientID = {$agentID} and ua.AgentID = {$removerId}")->fetch(FetchMode::ASSOCIATIVE);

        if ($connection) {
            $this->deleteUserAgent($this->entityManager->find(Useragent::class, $connection['UserAgentID']), $remover);
        }
    }

    private function sendReminderForUseragentBruteforceSafe(Useragent $useragent, Usr $remindingUser): ResultInterface
    {
        /** @var Usr $invitee */
        $invitee = $useragent->getAgentid();

        if (!$invitee) {
            return fail();
        }

        $locker = $this->inviteLocker->init($invitee->getEmail());

        if ($message = $locker->check()) {
            return fail($this->translator->trans($message));
        }

        $this->connectUser($invitee, $remindingUser);

        return success();
    }

    private function sendReminderForInviteCodeBruteforceSafe(Invitecode $invitecode, Usr $remindingUser): ResultInterface
    {
        $this->inviteLocker->init($invitecode->getEmail());

        if ($message = $this->inviteLocker->check()) {
            return fail($this->translator->trans($message));
        }

        $result = fail();

        if ($invitecode && $invitecode->getUserid()->getUserid() === $remindingUser->getUserid()) {
            $existedUser = $this->userRep->findOneBy(['email' => $invitecode->getEmail()]);

            if (empty($existedUser)) {
                $this->inviteUser($invitecode->getEmail(), $remindingUser);

                return success();
            } else {
                $invitee = $this->userRep->matching($this->getUserCriteria($invitecode->getEmail(), $remindingUser));

                $found = $invitee->count() > 0;
                $result = success();

                if ($found && $this->useragentRep->isExistingConnection($remindingUser, $invitee->first())) {
                    $result = fail();
                } elseif ($found) {
                    $this->connectUser($invitee->first(), $remindingUser);
                } else {
                    $this->inviteUser($invitecode->getEmail(), $remindingUser);
                }

                $this->entityManager->remove($invitecode);
                $this->entityManager->flush();
            }
        }

        return $result;
    }

    private function getBusinessAccountAdminIds(Usr $business): array
    {
        $dbConnection = $this->entityManager->getConnection();
        $businessId = $business->getUserid();

        $stmt = $dbConnection->executeQuery("SELECT
                    AgentID
                FROM
                    UserAgent
                WHERE
                    ClientID = {$businessId}
                    AND AccessLevel = " . ACCESS_ADMIN . "
                    AND IsApproved = 1");

        return
            stmtAssoc($stmt)
            ->column('AgentID')
            ->toArray();
    }

    private function makeUnverifiedEmailFail(bool $useLinksInError): Fail
    {
        return fail($this->translator->trans(
            'email.not_verified',
            $useLinksInError ?
                [
                    '%link_on%' => '<a target="_blank" href="' . $this->router->generate('aw_profile_overview') . '">',
                    '%link_off%' => '</a>',
                ] :
                [
                    '%link_on%' => '',
                    '%link_off%' => '',
                ],
            'validators'
        ));
    }
}
