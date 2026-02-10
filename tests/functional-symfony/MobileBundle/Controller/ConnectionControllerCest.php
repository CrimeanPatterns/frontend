<?php

namespace AwardWallet\Tests\FunctionalSymfony\MobileBundle\Controller;

use AwardWallet\MainBundle\Globals\Headers\MobileHeaders;
use AwardWallet\MainBundle\Manager\ConnectionManager;
use AwardWallet\Tests\FunctionalSymfony\BaseTraitCest;
use AwardWallet\Tests\FunctionalSymfony\Traits\FreeUser;
use AwardWallet\Tests\FunctionalSymfony\Traits\JsonHeaders;
use AwardWallet\Tests\FunctionalSymfony\Traits\LoggedIn;
use Doctrine\ORM\EntityManager;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

use function AwardWallet\MainBundle\Globals\Utils\iterFluent\it;

/**
 * @group mobile
 * @group frontend-functional
 */
class ConnectionControllerCest extends BaseTraitCest
{
    use \Awardwallet\Tests\Modules\AutoVerifyMocksTrait;
    use FreeUser;
    use LoggedIn;
    use JsonHeaders;
    /**
     * @var EntityManager
     */
    private $em;
    /**
     * @var UrlGeneratorInterface
     */
    private $router;
    /**
     * @var ConnectionManager
     */
    private $connectionManager;

    public function _before(\TestSymfonyGuy $I)
    {
        parent::_before($I);

        $this->router = $I->grabService('router');
        $this->em = $I->grabService('doctrine')->getManager();
        $this->connectionManager = $I->grabService(ConnectionManager::class);
        $I->haveHttpHeader(MobileHeaders::MOBILE_VERSION, '4.9.0+b100500');
    }

    public function editConnection(\TestSymfonyGuy $I)
    {
        $familyMembers =
            it(\iter\range(1, 4))
            ->map(function ($n) use ($I) {
                return $I->createFamilyMember(
                    $this->user->getUserid(),
                    $I->grabRandomString(5),
                    $I->grabRandomString(5)
                );
            })
            ->toArray();

        $otherUserId = $I->createAwUser();
        $I->createConnection($otherUserId, $this->user->getUserid(), true, true);
        $connectionId = $I->createConnection($this->user->getUserid(), $otherUserId, true, true);
        $formUrl = $this->router->generate('awm_connection_edit', ['userAgentId' => $connectionId]);
        $I->sendGET($formUrl);
        $form =
            it(\array_combine(
                $I->grabDataFromJsonResponse('form.children.*.name'),
                $I->grabDataFromJsonResponse('form.children.*.value')
            ))
            ->filterNotNull()
            ->toArrayWithKeys();
        $form['sharedTimelines'] = $I->grabDataFromResponseByJsonPath('$..[?(@.name = "sharedTimelines")].choices.*.name');
        $form['sharebydefault'] = true;
        $form['accesslevel'] = 2;
        $form['tripsharebydefault'] = 1;
        $form['tripAccessLevel'] = 1;
        $I->sendPUT($formUrl, $form);
        $I->seeResponseContainsJson(['success' => true]);

        $I->seeInDatabase('UserAgent', [
            'UserAgentID' => $connectionId,
            'ClientID' => $this->user->getUserid(),
            'AgentID' => $otherUserId,
            'ShareByDefault' => 1,
            'AccessLevel' => 2,
            'TripShareByDefault' => 1,
            'TripAccessLevel' => 1,
        ]);

        foreach ($familyMembers as $familyMember) {
            $I->seeInDatabase('TimelineShare', [
                'FamilyMemberID' => $familyMember,
                'UserAgentID' => $connectionId,
                'TimelineOwnerID' => $this->user->getUserid(),
            ]);
        }
    }

    public function editFamilyMember(\TestSymfonyGuy $I)
    {
        $familyMemberId = $I->createFamilyMember(
            $this->user->getUserid(),
            'First Name',
            'Last Name',
            null,
            'testmail@mail.com'
        );
        $url = $this->router->generate('awm_connection_edit', ['userAgentId' => $familyMemberId]);
        $I->sendGET($url);
        $form = \array_combine(
            $I->grabDataFromJsonResponse('form.children.*.name'),
            $I->grabDataFromJsonResponse('form.children.*.value')
        );
        $form['firstname'] = 'First Name 1';
        $form['lastname'] = 'Last Name 1';
        $form['midname'] = 'Mid Name 1';
        $form['email'] = 'testmail2@mail.com';
        $form['notes'] = 'note note';
        $form['alias'] = '1';
        $I->sendPUT($url, $form);
        $I->seeResponseContainsJson(['success' => true]);

        $I->seeInDatabase('UserAgent', [
            'FirstName' => 'First Name 1',
            'LastName' => 'Last Name 1',
            'MidName' => 'Mid Name 1',
            'Email' => 'testmail2@mail.com',
            'Notes' => 'note note',
        ]);
    }

    public function approveConnection(\TestSymfonyGuy $I)
    {
        $otherId = $I->createAwUser();
        $otherUser = $this->em->getRepository(\AwardWallet\MainBundle\Entity\Usr::class)->find($otherId);
        $this->logoutUser($I);
        $this->loginUser($I, $otherUser);
        $I->saveCsrfToken();
        $I->sendPOST($this->router->generate('awm_create_connection'), [
            'email' => $this->user->getEmail(),
        ]);
        $I->seeResponseContainsJson(['success' => true]);
        $this->logoutUser($I);
        $this->loginUser($I, $this->user);
        $connectionId = $I->grabFromDatabase('UserAgent', 'UserAgentID', [
            'ClientID' => $this->user->getUserid(),
            'AgentID' => $otherId,
        ]);
        $I->saveCsrfToken();
        $I->sendPOST($this->router->generate('awm_connection_approve', ['userAgentId' => $connectionId]));
        $I->seeResponseContainsJson(['success' => true]);
        $I->seeEmailTo($otherUser->getEmail(), 'connected with you on');
    }

    public function denyConnection(\TestSymfonyGuy $I)
    {
        $otherId = $I->createAwUser();
        $otherUser = $this->em->getRepository(\AwardWallet\MainBundle\Entity\Usr::class)->find($otherId);
        $this->logoutUser($I);
        $this->loginUser($I, $otherUser);
        $I->saveCsrfToken();
        $I->sendPOST($this->router->generate('awm_create_connection'), [
            'email' => $this->user->getEmail(),
        ]);
        $I->seeResponseContainsJson(['success' => true]);
        $this->logoutUser($I);
        $this->loginUser($I, $this->user);
        $connectionId = $I->grabFromDatabase('UserAgent', 'UserAgentID', [
            'ClientID' => $this->user->getUserid(),
            'AgentID' => $otherId,
        ]);
        $I->saveCsrfToken();
        $I->sendDELETE($this->router->generate('awm_connection_delete', ['userAgentId' => $connectionId]));
        $I->seeResponseContainsJson(['success' => true]);
    }

    public function existingUserAcceptedInviteCreateForFamilyMember(\TestSymfonyGuy $I)
    {
        $otherUserId = $I->createAwUser();
        $otherUser = $this->em->getRepository(\AwardWallet\MainBundle\Entity\Usr::class)->find($otherUserId);
        $familyMemberId = $I->createFamilyMember(
            $this->user->getUserid(),
            $I->grabRandomString(5),
            $I->grabRandomString(5)
        );
        $I->saveCsrfToken();
        $I->sendPOST($this->router->generate('awm_invite_family_member', ['userAgentId' => $familyMemberId]), [
            'email' => 'some@ya.ru',
        ]);
        $shareCode = $I->grabFromDatabase('InviteCode', 'Code', ['UserID' => $this->user->getUserid()]);

        $this->logoutUser($I);
        $this->loginUser($I, $otherUser);
        $I->saveCsrfToken();
        $I->sendPOST($this->router->generate('aw_invite_confirm', ['shareCode' => $shareCode]));
        $I->dontSeeInDatabase('UserAgent', ['UserAgentID' => $familyMemberId]);
        $I->dontSeeInDatabase('Invites', ['Code' => $shareCode]);
    }

    public function removeInviteAfterDisconnection(\TestSymfonyGuy $I)
    {
        $otherUserId = $I->createAwUser(null, null, [
            'Email' => $mail = $I->grabRandomString(10) . '@fakemail.com',
        ]);
        $otherUser = $this->em->getRepository(\AwardWallet\MainBundle\Entity\Usr::class)->find($otherUserId);
        $familyMemberId = $I->createFamilyMember(
            $this->user->getUserid(),
            $I->grabRandomString(5),
            $I->grabRandomString(5)
        );
        $I->saveCsrfToken();
        $I->sendPOST($this->router->generate('awm_invite_family_member', ['userAgentId' => $familyMemberId]), [
            'email' => $mail,
        ]);
        $shareCode = $I->grabFromDatabase('InviteCode', 'Code', ['UserID' => $this->user->getUserid()]);

        $this->logoutUser($I);
        $this->loginUser($I, $otherUser);
        $I->saveCsrfToken();
        $I->sendPOST($this->router->generate('aw_invite_confirm', ['shareCode' => $shareCode]));
        $I->dontSeeInDatabase('UserAgent', ['UserAgentID' => $familyMemberId]);
        $I->dontSeeInDatabase('Invites', ['Code' => $shareCode]);
        $I->sendDELETE($this->router->generate('awm_connection_delete', [
            'userAgentId' => $I->grabFromDatabase('UserAgent', 'UserAgentID', ['ClientID' => $otherUserId]),
        ]));
        $I->dontSeeInDatabase('InviteCode', ['UserID' => $this->user->getUserid(), 'Email' => $otherUser->getEmail()]);
    }
}
