<?php

namespace AwardWallet\MainBundle\Security\Voter;

use AwardWallet\MainBundle\Entity\AbRequest;
use AwardWallet\MainBundle\Entity\Useragent;
use AwardWallet\MainBundle\Entity\Usr;
use Doctrine\ORM\EntityRepository;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

class BookingRequestVoter extends AbstractVoter
{
    public function view(TokenInterface $token, $object)
    {
        $user = $token->getUser();

        return $user instanceof Usr && $this->allowView($user, $object);
    }

    /**
     * @param AbRequest $object
     * @return bool
     */
    public function edit(TokenInterface $token, $object)
    {
        $user = $token->getUser();
        $allow = $user instanceof Usr
            && (
                (/* !$object->getByBooker() && */ $this->isAuthor($user, $object) && !$this->isBookerRequest($user, $object))
                || (/* $object->getByBooker() && */ $this->isBookerRequest($user, $object) && $this->container->get(BusinessVoter::class)->businessAccounts($token))
            )
            && in_array($object->getStatus(), [
                $object::BOOKING_STATUS_FUTURE,
                $object::BOOKING_STATUS_PENDING,
                $object::BOOKING_STATUS_NOT_VERIFIED,
                $object::BOOKING_STATUS_PROCESSING,
            ]);

        return $allow;
    }

    /**
     * @param AbRequest $object
     * @return bool
     */
    public function cancel(TokenInterface $token, $object)
    {
        $user = $token->getUser();
        $allow = $user instanceof Usr
            && (
                ($this->isAuthor($user, $object) && !$this->isBookerRequest($user, $object))
                || ($this->isBookerRequest($user, $object) && $this->container->get(BusinessVoter::class)->businessAccounts($token))
            )
            && in_array($object->getStatus(), [
                $object::BOOKING_STATUS_FUTURE,
                $object::BOOKING_STATUS_PENDING,
                $object::BOOKING_STATUS_NOT_VERIFIED,
            ]);

        return $allow;
    }

    public function pay(TokenInterface $token, $object)
    {
        $user = $token->getUser();
        $allow = $user instanceof Usr
            && $this->isAuthor($user, $object)
            && !$this->isBookerRequest($user, $object);

        return $allow;
    }

    public function repost(TokenInterface $token, $object)
    {
        $user = $token->getUser();
        $allow = $user instanceof Usr
            && $this->isAuthor($user, $object)
            && !$this->isBookerRequest($user, $object)
            && in_array($object->getStatus(), [
                $object::BOOKING_STATUS_CANCELED,
            ]);

        return $allow;
    }

    public function author(TokenInterface $token, $object)
    {
        $user = $token->getUser();

        return $user instanceof Usr && $this->isAuthor($user, $object);
    }

    public function booker(TokenInterface $token, $object)
    {
        $user = $token->getUser();

        return $user instanceof Usr && $this->isBookerRequest($user, $object);
    }

    protected function getAttributes()
    {
        return [
            'VIEW' => [$this, 'view'],
            'EDIT' => [$this, 'edit'],
            'PAY' => [$this, 'pay'],
            'CANCEL' => [$this, 'cancel'],
            'REPOST' => [$this, 'repost'],
            'AUTHOR' => [$this, 'author'],
            'BOOKER' => [$this, 'booker'],
        ];
    }

    protected function getClass()
    {
        return 'AwardWallet\\MainBundle\\Entity\\AbRequest';
    }

    private function allowView(Usr $user, AbRequest $request)
    {
        if ($this->isAuthor($user, $request) || $this->isBookerRequest($user, $request)) {
            return true;
        }

        return false;
    }

    private function isBookerRequest(Usr $user, AbRequest $request)
    {
        /** @var Usr $booker */
        $booker = $this->container->get('doctrine')->getRepository(Usr::class)->getBookerByUser($user);

        if ($this->isBusiness() && !empty($booker) && $booker == $request->getBooker()
            // hide disconnected users from BYA
            && ($booker->getUserid() !== 116000 || $this->haveConnectionBetween($booker, $request->getUser()))) {
            $security = $this->getContainer()->get('security.authorization_checker');

            if ($security->isGranted('USER_BOOKING_PARTNER') && !$security->isGranted('USER_BOOKING_MANAGER')) {
                foreach ($request->getSiteAd()->getUsers() as $refUser) {
                    if ($refUser->getUserid() == $user->getUserid()) {
                        return true;
                    }
                }

                return false;
            }

            return true;
        }

        return false;
    }

    private function isAuthor(Usr $user, AbRequest $request)
    {
        if ($user->hasRole('ROLE_USER') && $user == $request->getUser()) {
            return true;
        }

        return false;
    }

    private function haveConnectionBetween(Usr $booker, Usr $user): bool
    {
        /** @var EntityRepository $agentRepo */
        $agentRepo = $this->container->get('doctrine')->getRepository(Useragent::class);

        /** @var Useragent $connection */
        $connection = $agentRepo->findOneBy(['agentid' => $user, 'clientid' => $booker, 'isapproved' => 1]);

        if ($connection === null) {
            return false;
        }

        $connection = $agentRepo->findOneBy(['agentid' => $booker, 'clientid' => $user, 'isapproved' => 1]);

        if ($connection === null) {
            return false;
        }

        return true;
    }
}
