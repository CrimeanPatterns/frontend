<?php

namespace AwardWallet\MainBundle\Service;

use AwardWallet\MainBundle\Entity\Repositories\UseragentRepository;
use AwardWallet\MainBundle\Entity\Useragent;
use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\FrameworkExtension\AwTokenStorageInterface;
use AwardWallet\MainBundle\FrameworkExtension\Mailer\Mailer;
use AwardWallet\MainBundle\FrameworkExtension\Mailer\Template\User\UserAgent\RequestAccess;
use AwardWallet\MainBundle\Globals\AccountList\Options;
use AwardWallet\MainBundle\Globals\AccountList\OptionsFactory;
use AwardWallet\MainBundle\Manager\AccountListManager;
use AwardWallet\MainBundle\Manager\ProgramShareManager;
use Doctrine\ORM\EntityManagerInterface;

class AccessGrantedHelper
{
    private EntityManagerInterface $em;

    private Mailer $mailer;

    private Counter $counter;

    private AwTokenStorageInterface $tokenStorage;

    private ProgramShareManager $shareManager;

    private AccountListManager $accountListManager;

    private OptionsFactory $optionsFactory;

    public function __construct(
        EntityManagerInterface $manager,
        Mailer $mailer,
        AwTokenStorageInterface $tokenStorage,
        AccountListManager $accountListManager,
        OptionsFactory $optionsFactory,
        Counter $counter,
        ProgramShareManager $shareManager
    ) {
        $this->em = $manager;
        $this->mailer = $mailer;
        $this->counter = $counter;
        $this->tokenStorage = $tokenStorage;
        $this->shareManager = $shareManager;
        $this->accountListManager = $accountListManager;
        $this->optionsFactory = $optionsFactory;
    }

    /**
     * @param bool $isFull
     * @return bool
     */
    public function sendMail(Useragent $userAgent, $isFull = false)
    {
        $client = $userAgent->getClientid();
        $user = $this->tokenStorage->getBusinessUser();

        //        $uaRep = $this->em->getRepository(\AwardWallet\MainBundle\Entity\Useragent::class);
        $accountRep = $this->em->getRepository(\AwardWallet\MainBundle\Entity\Account::class);
        $couponRep = $this->em->getRepository(\AwardWallet\MainBundle\Entity\Providercoupon::class);

        $usrAccounts = $this->accountListManager->getAccountList(
            $this->optionsFactory->createDefaultOptions()
                ->set(Options::OPTION_USER, $client)
        );

        $requestedAccounts = [];
        //        $connection = $uaRep->findOneBy(['agentid' => $user, 'clientid' => $agent]);
        $access = $this->calculateAccess($user, $client);
        $allTimelinesShared = $access['sharedTimelinesCount'] === $access['familyMembersCount'];

        if ($isFull) {
            $sendEmail = !($allTimelinesShared && $access['tripDefaults'] && $access['tripLevel'] && $access['accountDefaults']
                && $access['accountFull'] && $access['accessLevelFull']);
        } else {
            $sendEmail = !($allTimelinesShared && $access['tripDefaults'] && $access['accountDefaults'] && $access['accountFull']
                && ($access['accessLevel'] >= 0));
        }

        if ($sendEmail) {
            foreach ($usrAccounts as $id => $usrAccount) {
                if ($usrAccount['TableName'] == 'Account') {
                    $account = $accountRep->find($usrAccount['ID']);

                    if ($account->getUserid()->getUserid() != $client->getUserid()) {
                        continue;
                    }
                    $requestedAccounts[$id] = $account;
                /*
                                    if ($connection) {
                                        $accountShare = $accountShareRep->findOneBy(['accountid' => $account, 'useragentid' => $connection]);
                                        if ($accountShare) {
                                            $sharedAccounts[$id] = $account;
                                        } else {
                                            $requestedAccounts[$id] = $account;
                                        }
                                    } else {
                                        $requestedAccounts[$id] = $account;
                                    }
                */
                } else {
                    $coupon = $couponRep->find($usrAccount['ID']);

                    if ($coupon->getUserid()->getUserid() != $client->getUserid()) {
                        continue;
                    }
                    $requestedAccounts[$id] = $coupon;
                    /*
                                        if ($connection) {
                                            $couponShare = $couponShareRep->findOneBy(['providercouponid' => $coupon, 'useragentid' => $connection]);
                                            if ($couponShare) {
                                                $sharedAccounts[$id] = $coupon;
                                            } else {
                                                $requestedAccounts[$id] = $coupon;
                                            }
                                        } else {
                                            $requestedAccounts[$id] = $coupon;
                                        }
                    */
                }
            }
            $template = new RequestAccess($client);
            $template->agent = $user;
            $template->accounts = $requestedAccounts;
            $template->code = $this->shareManager->getShareAllCode($userAgent, $isFull ? 'full' : 'readonly');
            $template->full = $isFull;

            $message = $this->mailer->getMessageByTemplate($template);
            $this->mailer->send($message);
        }

        return $sendEmail;
    }

    /**
     * @return array
     */
    public function calculateAccess(Usr $fromUser, Usr $toUser)
    {
        $ret = [];
        $userAgentRep = $this->em->getRepository(\AwardWallet\MainBundle\Entity\Useragent::class);
        $timelineShareRep = $this->em->getRepository(\AwardWallet\MainBundle\Entity\TimelineShare::class);
        $agentAccessLevelsAll = $userAgentRep->getAgentAccessLevelsAll();

        $ret['accountTotal'] = $this->counter->getTotalAccounts($toUser->getUserid(), 0);
        $toUserFamilyMembers = $userAgentRep->getUserFamilyMembers($toUser);

        foreach ($toUserFamilyMembers as $userAgent) {
            $ret['accountTotal'] += $this->counter->getTotalAccounts($toUser->getUserid(), $userAgent->getUseragentid());
        }

        $connection = $userAgentRep->findOneBy(['agentid' => $fromUser, 'clientid' => $toUser]);

        if ($connection) {
            $accessLevel = $connection->getAccesslevel();

            $ret['connection'] = $connection;

            $ret['accessLevel'] = $accessLevel;
            $ret['accessLevelLabel'] = $agentAccessLevelsAll[$accessLevel];
            $ret['accessLevelFull'] = $accessLevel >= UseragentRepository::ACCESS_WRITE;

            $ret['accountShared'] = $this->counter->getTotalAccounts($fromUser->getUserid(), $connection->getUseragentid());
            $ret['accountFull'] = $ret['accountTotal'] == $ret['accountShared'];

            $ret['accountDefaults'] = $connection->getSharebydefault();

            $ret['tripLevel'] = $connection->getTripAccessLevel();
            $sharedTimelines = $timelineShareRep->findBy(['timelineOwner' => $connection->getClientid(), 'recipientUser' => $connection->getAgentid()]);
            $ret['sharedTimelines'] = $sharedTimelines;
            $ret['sharedTimelinesCount'] = count($sharedTimelines);

            $ret['familyMembersCount'] = count($toUserFamilyMembers);
            $timelines = [
                'user_' . $toUser->getUserid() => ['name' => $toUser->getFullName(), 'isGranted' => false],
            ];

            foreach ($toUserFamilyMembers as $familyMember) {
                $timelines['fm_' . $familyMember->getUseragentid()] = ['name' => $familyMember->getFullName(), 'isGranted' => false];
            }

            foreach ($sharedTimelines as $sharedTimeline) {
                $id = $sharedTimeline->getFamilyMember()
                    ? 'fm_' . $sharedTimeline->getFamilyMember()->getUseragentid()
                    : 'user_' . $sharedTimeline->getTimelineOwner()->getUserid();

                if (!isset($timelines[$id])) {
                    continue;
                }
                $timelines[$id]['isGranted'] = true;
            }

            $ret['timelines'] = $timelines;
            $ret['tripDefaults'] = $connection->getTripsharebydefault();

            return $ret;
        } else {
            $ret['accessLevel'] = -1;
            $ret['accessLevelFull'] = false;
            $ret['accountShared'] = 0;
            $ret['accountFull'] = false;
            $ret['accountDefaults'] = false;
            $ret['tripLevel'] = false;
            $ret['tripDefaults'] = false;
            $ret['sharedTimelinesCount'] = 0;
            $ret['familyMembersCount'] = 0;

            return $ret;
        }
    }
}
