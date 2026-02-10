<?php

namespace AwardWallet\Tests\Unit\Booking;

use AwardWallet\MainBundle\Entity\AbMessage;
use AwardWallet\MainBundle\Entity\AbRequestMark;
use AwardWallet\MainBundle\Entity\Repositories\AbRequestRepository;
use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\Tests\Unit\BaseUserTest;
use Codeception\Module\Aw;

use function PHPUnit\Framework\assertCount;
use function PHPUnit\Framework\assertEquals;
use function PHPUnit\Framework\assertFalse;
use function PHPUnit\Framework\assertSame;
use function PHPUnit\Framework\assertTrue;

/**
 * @group frontend-unit
 */
class ReadUnreadTest extends BaseUserTest
{
    /**
     * @var AbRequestRepository
     */
    private $abRepository;
    /**
     * @var Usr
     */
    private $booker1;
    /**
     * @var Usr
     */
    private $booker2;
    /**
     * @var Usr
     */
    private $user1;
    /**
     * @var Usr
     */
    private $user2;
    /**
     * @var Usr
     */
    private $business;

    public function _before()
    {
        parent::_before();

        $this->abRepository = $this->em->getRepository(\AwardWallet\MainBundle\Entity\AbRequest::class);

        $this->user1 = $this->user;

        $userId = $this->aw->createAwUser('test' . $this->aw->grabRandomString(5), Aw::DEFAULT_PASSWORD, []);
        $usrRepository = $this->em->getRepository(\AwardWallet\MainBundle\Entity\Usr::class);
        $this->user2 = $usrRepository->find($userId);

        $businessUserId = $this->aw->createBusinessUserWithBookerInfo();
        $this->business = $usrRepository->find($businessUserId);

        $bookerUserId = $this->aw->createStaffUserForBusinessUser($businessUserId);
        $this->booker1 = $usrRepository->find($bookerUserId);

        $bookerUserId = $this->aw->createStaffUserForBusinessUser($businessUserId);
        $this->booker2 = $usrRepository->find($bookerUserId);
    }

    public function testBookerShouldSeeNewInternalMessages()
    {
        $request1 = $this->createAbRequest([
            'UserID' => $this->user1->getUserid(),
            'BookerUserID' => $this->booker1->getUserid(),
            'CreateDate' => $requestCreateDate = $this->mysqlDate('-10 hours'),
            'LastUpdateDate' => $requestCreateDate,
        ]);
        // booker1 wrote internal message to request1
        $request1->addMessage(
            (new AbMessage())
                ->setCreateDate(new \DateTime('-5 hours'))
                ->setPost('1')
                ->setType(AbMessage::TYPE_INTERNAL)
                ->setUserID($this->booker1)
        );
        // booker2 read some messages
        $this->em->persist(
            (new AbRequestMark())
                ->setReadDate(new \DateTime('-7 hours'))
                ->setRequest($request1)
                ->setUser($this->booker2)
        );
        // booker1 read some messages in request1
        $this->em->persist(
            (new AbRequestMark())
                ->setReadDate(new \DateTime('-4 hours'))
                ->setRequest($request1)
                ->setUser($this->booker1)
        );

        $request2 = $this->createAbRequest([
            'UserID' => $this->user2->getUserid(),
            'BookerUserID' => $this->booker1->getUserid(),
            'CreateDate' => $requestCreateDate = $this->mysqlDate('-5 hour'),
            'LastUpdateDate' => $requestCreateDate,
        ]);
        // booker1 wrote internal message to request2
        $request2->addMessage(
            (new AbMessage())
                ->setCreateDate(new \DateTime('-3 hours'))
                ->setPost('1')
                ->setType(AbMessage::TYPE_INTERNAL)
                ->setUserID($this->booker1)
        );
        // booker1 read some messages in request2
        $this->em->persist(
            (new AbRequestMark())
                ->setReadDate(new \DateTime('-2 hours'))
                ->setRequest($request2)
                ->setUser($this->booker1)
        );
        $this->em->flush();

        assertEquals(0, $this->abRepository->isNewInternal($request1, $this->booker1));
        assertEquals(0, $this->abRepository->isNewInternal($request2, $this->booker1));

        assertEquals(1, $this->abRepository->isNewInternal($request1, $this->booker2));
        assertEquals(0, $this->abRepository->isNewInternal($request2, $this->booker2));
    }

    public function testUserShouldNotSeeNewInternalMessages()
    {
        $request1 = $this->createAbRequest([
            'UserID' => $this->user1->getUserid(),
            'BookerUserID' => $this->booker1->getUserid(),
            'CreateDate' => $requestCreateDate = $this->mysqlDate('-10 hours'),
            'LastUpdateDate' => $requestCreateDate,
        ]);
        $em = $this->em;
        // user1 wrote message to request1
        $request1->addMessage(
            (new AbMessage())
                ->setCreateDate(new \DateTime('-9 hours'))
                ->setPost('1')
                ->setType(AbMessage::TYPE_COMMON)
                ->setUserID($this->user1)
        );
        // user1 read request1
        $em->persist(
            (new AbRequestMark())
                ->setReadDate(new \DateTime('-8 hours'))
                ->setRequest($request1)
                ->setUser($this->user1)
        );
        $em->flush();

        assertSame(null, $this->abRepository->getLastUnreadByUser($this->user1));
        assertTrue($this->abRepository->isRequestReadByUser($request1, $this->user1));
        assertEquals(0, $this->abRepository->getUnreadCountForUser($this->user1));
        assertEquals([], $this->abRepository->getUnreadListByUser($this->user1));

        // user1 wrote message to request1
        $request1->addMessage(
            (new AbMessage())
                ->setCreateDate(new \DateTime('-7 hours'))
                ->setPost('1')
                ->setType(AbMessage::TYPE_INTERNAL)
                ->setUserID($this->booker1)
        );
        $em->flush();

        assertSame(null, $this->abRepository->getLastUnreadByUser($this->user1));
        assertTrue($this->abRepository->isRequestReadByUser($request1, $this->user1));
        assertEquals(0, $this->abRepository->getUnreadCountForUser($this->user1));
        assertEquals([], $this->abRepository->getUnreadListByUser($this->user1));
    }

    public function testUnreadByUser()
    {
        $request1 = $this->createAbRequest([
            'UserID' => $this->user1->getUserid(),
            'BookerUserID' => $this->business->getUserid(),
            'CreateDate' => $requestCreateDate = $this->mysqlDate('-5 hour'),
            'LastUpdateDate' => $requestCreateDate,
        ]);
        $request1->addMessage(
            (new AbMessage())
                ->setCreateDate(new \DateTime('-2 hours'))
                ->setPost('1')
                ->setType(AbMessage::TYPE_COMMON)
                ->setUserID($this->user1)
        );
        $this->em->persist(
            (new AbRequestMark())
                ->setReadDate(new \DateTime('-1 hours'))
                ->setRequest($request1)
                ->setUser($this->user1)
        );
        // user writes some message
        // booker has no reads

        $request2 = $this->createAbRequest([
            'UserID' => $this->user1->getUserid(),
            'BookerUserID' => $this->booker1->getUserid(),
            'CreateDate' => $requestCreateDate = $this->mysqlDate('-10 hour'),
            'LastUpdateDate' => $requestCreateDate,
        ]);
        $request2->addMessage(
            (new AbMessage())
                ->setCreateDate(new \DateTime('-1 hours'))
                ->setPost('1')
                ->setType(AbMessage::TYPE_COMMON)
                ->setUserID($this->booker1)
                ->setFromBooker(true)
        );
        $this->em->persist(
            (new AbRequestMark())
                ->setReadDate(new \DateTime('-1 hours'))
                ->setRequest($request2)
                ->setUser($this->booker1)
        );
        $request2->addMessage(
            (new AbMessage())
                ->setCreateDate(new \DateTime('-8 hours'))
                ->setPost('1')
                ->setType(AbMessage::TYPE_COMMON)
                ->setUserID($this->user1)
        );
        $this->em->persist(
            (new AbRequestMark())
                ->setReadDate(new \DateTime('-7 hours'))
                ->setRequest($request2)
                ->setUser($this->user1)
        );
        // user reads before booker message
        // booker reads after user message
        $this->em->flush();

        assertSame($request2, $this->abRepository->getLastUnreadByUser($this->user1, false));
        assertTrue($this->abRepository->isRequestReadByUser($request1, $this->user1, false));
        assertFalse($this->abRepository->isRequestReadByUser($request2, $this->user1, false));

        assertSame(null, $this->abRepository->getLastUnreadByUser($this->booker1, true));
        assertTrue($this->abRepository->isRequestReadByUser($request1, $this->booker1, true));
        assertTrue($this->abRepository->isRequestReadByUser($request2, $this->booker1, true));

        assertEquals(1, $this->abRepository->getUnreadCountForUser($this->user1, false));

        $unreadList = $this->abRepository->getUnreadListByUser($this->user1, false);
        assertCount(1, $unreadList);
        assertSame($request2, $unreadList[0]);
    }

    /**
     * @param array $data
     * @return \AwardWallet\MainBundle\Entity\AbRequest
     */
    private function createAbRequest($data)
    {
        return $this->em
            ->getRepository(\AwardWallet\MainBundle\Entity\AbRequest::class)
            ->find($this->aw->createAbRequest($data));
    }

    /**
     * @param string $humanFormat
     * @return string
     */
    private function mysqlDate($humanFormat)
    {
        return (new \DateTime($humanFormat))->format('Y-m-d H:i:s');
    }
}
