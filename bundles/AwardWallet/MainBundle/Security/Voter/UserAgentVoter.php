<?php

namespace AwardWallet\MainBundle\Security\Voter;

use AwardWallet\MainBundle\Entity\Repositories\TimelineShareRepository;
use AwardWallet\MainBundle\Entity\Useragent;
use AwardWallet\MainBundle\Entity\Usr;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

class UserAgentVoter extends AbstractVoter
{
    /**
     * @var TimelineShareVoter
     */
    private $shareVoter;

    /**
     * @var TimelineShareRepository
     */
    private $shareRepo;
    private EntityManagerInterface $entityManager;

    public function __construct(ContainerInterface $container, TimelineShareVoter $shareVoter, EntityManagerInterface $entityManager)
    {
        parent::__construct($container);

        $this->shareVoter = $shareVoter;
        $this->shareRepo = $container->get('doctrine')->getManager()->getRepository(\AwardWallet\MainBundle\Entity\TimelineShare::class);
        $this->entityManager = $entityManager;
    }

    /**
     * @param Useragent $userAgent
     * @return bool
     */
    public function edit(TokenInterface $token, $userAgent)
    {
        /** @var Usr $user */
        $user = $this->getBusinessUser($token);

        if (!($user instanceof Usr)) {
            return false;
        }

        if ($userAgent->isFamilyMember()) {
            return $userAgent->getAgentid()->getUserid() === $user->getUserid();
        } else {
            return $userAgent->getClientid()->getUserid() === $user->getUserid();
        }
    }

    /**
     * @param Useragent $userAgent
     * @return bool
     */
    public function editTimeline(TokenInterface $token, $userAgent)
    {
        $user = $this->getBusinessUser($token);

        if (!($user instanceof Usr) || !($userAgent instanceof Useragent)) {
            return false;
        }

        $isFM = $userAgent->isFamilyMember();

        if ($userAgent->getAgentid()->getUserid() == $user->getUserid() && $isFM) {
            return true;
        }

        if ($isFM) {
            $share = $this->shareRepo->findOneBy([
                'familyMember' => $userAgent,
                'recipientUser' => $user,
            ]);
        } else {
            $share = $this->shareRepo->findOneBy([
                'userAgent' => $userAgent,
                'recipientUser' => $user,
            ]);
        }

        return !empty($share) && $this->shareVoter->edit($token, $share);
    }

    /**
     * @param Useragent $userAgent
     * @return bool
     */
    public function editAccounts(TokenInterface $token, $userAgent)
    {
        $user = $this->getBusinessUser($token);

        if (!($user instanceof Usr)) {
            return false;
        }

        $isFM = $userAgent->isFamilyMember();

        if ($userAgent->getAgentid()->getUserid() == $user->getUserid() && $isFM) {
            return true;
        }

        $repo = $this->entityManager->getRepository(Useragent::class);

        if ($isFM) {
            $userAgent = $repo->findOneBy(['agentid' => $user, 'clientid' => $userAgent->getAgentid(), 'isapproved' => 1]);
        }

        if (!empty($userAgent)) {
            $backLink = $repo->findOneBy(['clientid' => $userAgent->getAgentid(), 'agentid' => $userAgent->getClientid(), 'isapproved' => 1]);
        }

        if (empty($userAgent) || empty($backLink)) {
            return false;
        }

        return in_array($userAgent->getAccesslevel(), [ACCESS_WRITE]);
    }

    /**
     * @param Useragent $userAgent
     * @return bool
     */
    public function connectionApproved(TokenInterface $token, $userAgent)
    {
        $user = $this->getBusinessUser($token);

        if (!($user instanceof Usr)) {
            return false;
        }

        $isFM = $userAgent->isFamilyMember();

        if ($userAgent->getAgentid()->getUserid() == $user->getUserid() && $isFM) {
            return true;
        }

        $repo = $this->entityManager->getRepository(Useragent::class);

        if ($isFM) {
            $userAgent = $repo->findOneBy(['agentid' => $user, 'clientid' => $userAgent->getAgentid(), 'isapproved' => 1]);
        }

        if (!empty($userAgent)) {
            $backLink = $repo->findOneBy(['clientid' => $userAgent->getAgentid(), 'agentid' => $userAgent->getClientid(), 'isapproved' => 1]);
        }

        if (empty($userAgent) || empty($backLink) || $userAgent->getClientid()->getUserid() != $user->getUserid()) {
            return false;
        }

        return true;
    }

    public function viewSpendAnalysis(TokenInterface $token, Useragent $userAgent): bool
    {
        $user = $this->getBusinessUser($token);

        if (!($user instanceof Usr)) {
            return false;
        }

        if ($user->getAccountlevel() !== ACCOUNT_LEVEL_BUSINESS) {
            return false;
        }

        if (!$userAgent->isApproved()) {
            return false;
        }

        if ($userAgent->getAgentid()->getUserid() === $user->getUserid()) {
            return true;
        }

        return false;
    }

    protected function getAttributes()
    {
        return [
            'EDIT' => [$this, 'edit'],
            'EDIT_TIMELINE' => [$this, 'editTimeline'],
            'EDIT_ACCOUNTS' => [$this, 'editAccounts'],
            'CONNECTION_APPROVED' => [$this, 'connectionApproved'],
            'VIEW_SPEND_ANALYSIS' => [$this, 'viewSpendAnalysis'],
        ];
    }

    protected function getClass()
    {
        return 'AwardWallet\\MainBundle\\Entity\\Useragent';
    }
}
