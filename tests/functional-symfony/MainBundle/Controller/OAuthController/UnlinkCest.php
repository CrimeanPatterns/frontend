<?php

namespace AwardWallet\Tests\FunctionalSymfony\MainBundle\Controller\OAuthController;

use AwardWallet\MainBundle\Entity\Repositories\UsrRepository;
use AwardWallet\MainBundle\Entity\UserOAuth;
use AwardWallet\MainBundle\Entity\Usr;
use Doctrine\ORM\EntityManagerInterface;

/**
 * @group frontend-functional
 */
class UnlinkCest extends AbstractCest
{
    use \Awardwallet\Tests\Modules\AutoVerifyMocksTrait;

    public function testAccess(\TestSymfonyGuy $I)
    {
        /** @var EntityManagerInterface $em */
        $em = $I->grabService('doctrine')->getManager();
        /** @var UsrRepository $userRep */
        $userRep = $I->grabService('doctrine')->getRepository(Usr::class);

        $user = $userRep->find($I->createAwUser('test' . $I->grabRandomString(5)));
        $user2 = $userRep->find($I->createAwUser('test2' . $I->grabRandomString(5)));
        $user->getOAuth()->add(new UserOAuth(
            $user,
            $user->getEmail(),
            $user->getFirstname(),
            $user->getLastname(),
            'google',
            $user->getLogin()
        ));
        $em->flush();
        $userOAuthId = $user->getOAuth()->first()->getId();

        $params = ['_switch_user' => $user2->getLogin()];
        $I->amOnRoute('aw_account_list', $params);

        $I->amOnRoute('aw_usermailbox_oauth_unlink', array_merge($params, ['id' => 0]));
        $I->seeResponseCodeIs(404);

        $I->amOnRoute('aw_usermailbox_oauth_unlink', array_merge($params, ['id' => $userOAuthId]));
        $I->seeResponseCodeIs(403);

        $params = ['_switch_user' => $user->getLogin()];
        $I->updateInDatabase('Usr', ['Pass' => null], ['UserID' => $user->getUserid()]);
        $I->amOnRoute('aw_usermailbox_oauth_unlink', array_merge($params, ['id' => $userOAuthId]));
        $I->seeResponseCodeIs(200);
        $I->seeResponseContainsJson(['error' => 'setPass']);

        $I->updateInDatabase('Usr', ['Pass' => 'xxx'], ['UserID' => $user->getUserid()]);
        $I->amOnRoute('aw_usermailbox_oauth_unlink', array_merge($params, ['id' => $userOAuthId]));
        $I->seeResponseCodeIs(200);
        $I->seeResponseContainsJson(['success' => true]);
        $I->dontSeeInDatabase('UserOAuth', [
            'UserID' => $user->getUserid(),
        ]);
    }
}
