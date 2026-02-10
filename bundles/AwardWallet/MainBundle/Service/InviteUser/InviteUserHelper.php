<?php

namespace AwardWallet\MainBundle\Service\InviteUser;

use AwardWallet\MainBundle\Entity\Invites;
use AwardWallet\MainBundle\Entity\Repositories\InvitesRepository;
use AwardWallet\MainBundle\FrameworkExtension\AwTokenStorageInterface;
use AwardWallet\MainBundle\FrameworkExtension\Mailer\Mailer;
use AwardWallet\MainBundle\FrameworkExtension\Mailer\Template\User\UserAgent\Invitation;
use AwardWallet\MainBundle\Globals\StringHandler;
use AwardWallet\MainBundle\Globals\StringUtils;
use AwardWallet\MainBundle\Manager\ConnectionManager;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Класс для работы с формой отправки приглашения новому пользователю.
 */
class InviteUserHelper
{
    public const STATUS_EMAIL_NOT_VERIFIED = 0;
    public const STATUS_SENT = 1;
    public const STATUS_NOT_SENT = 2;

    private AwTokenStorageInterface $tokenStorage;
    private EntityManagerInterface $entityManager;
    private Mailer $mailer;
    private InvitesRepository $invitesRepository;

    public function __construct(
        AwTokenStorageInterface $tokenStorage,
        EntityManagerInterface $entityManager,
        Mailer $mailer,
        InvitesRepository $invitesRepository
    ) {
        $this->tokenStorage = $tokenStorage;
        $this->entityManager = $entityManager;
        $this->mailer = $mailer;
        $this->invitesRepository = $invitesRepository;
    }

    /**
     * Отправляет приглашение на регистрацию.
     *
     * @param string $email e-mail, на который будет отправлено письмо
     * @return int число, показывающее статус отправки приглашения
     */
    public function send(string $email): int
    {
        $user = $this->tokenStorage->getUser();

        if ($user->getEmailverified() !== EMAIL_VERIFIED) {
            return self::STATUS_EMAIL_NOT_VERIFIED;
        } elseif (StringHandler::isEmpty($email) || !preg_match(EMAIL_REGEXP, $email)) {
            return self::STATUS_NOT_SENT;
        }

        $invite = $this->invitesRepository->findOneBy([
            'inviterid' => $user,
            'email' => $email,
        ]);
        $repeat = true;

        if (!$invite) {
            $invite = new Invites();
            $invite->setInviterid($user);
            $invite->setEmail($email);
            $invite->setCode(StringUtils::getRandomCode(10, true));

            $this->entityManager->persist($invite);
            $repeat = false;
        }

        $invite->setInvitedate(new \DateTime());
        $this->entityManager->flush();

        if (!ConnectionManager::isEmailFromRestrictedDomain($email)) {
            $template = new Invitation($email);
            $template->invite = $invite;
            $template->reminder = $repeat;
            $message = $this->mailer->getMessageByTemplate($template);

            if ($this->mailer->send($message)) {
                return self::STATUS_SENT;
            }
        }

        return self::STATUS_NOT_SENT;
    }
}
