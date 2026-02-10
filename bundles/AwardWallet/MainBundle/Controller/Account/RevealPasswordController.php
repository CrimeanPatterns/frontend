<?php

namespace AwardWallet\MainBundle\Controller\Account;

use AwardWallet\MainBundle\Entity\Account;
use AwardWallet\MainBundle\Manager\LocalPasswordsManager;
use AwardWallet\MainBundle\Security\Reauthentication\Action;
use AwardWallet\MainBundle\Security\Reauthentication\ReauthenticatorWrapper;
use JMS\TranslationBundle\Model\Message;
use JMS\TranslationBundle\Translation\TranslationContainerInterface;
use Psr\Log\LoggerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

class RevealPasswordController extends AbstractController implements TranslationContainerInterface
{
    /**
     * @Security("is_granted('ROLE_USER') and is_granted('CSRF') and is_granted('READ_PASSWORD', account)")
     * @Route("/get-password/{accountId}", name="aw_get_pass", methods={"POST"}, options={"expose"=true})
     * @ParamConverter("account", class="AwardWalletMainBundle:Account", options={"id" = "accountId"})
     */
    public function getPasswordAction(
        Account $account,
        LoggerInterface $securityLogger,
        ReauthenticatorWrapper $reauthenticator,
        LocalPasswordsManager $localPasswordsManager
    ) {
        $action = Action::getRevealAccountPasswordAction($account->getId());

        if ($reauthenticator->isReauthenticated($action)) {
            $reauthenticator->reset($action);
            $securityLogger->info("successful reveal password, password access, accountId: {$account->getAccountid()}, userId: {$account->getUser()->getUserid()}");

            return new JsonResponse([
                'success' => true,
                'password' => ($account->getSavepassword() == SAVE_PASSWORD_DATABASE)
                    ? $account->getPass()
                    : $localPasswordsManager->getPassword($account->getAccountid()),
            ]);
        }

        return new JsonResponse([
            'success' => false,
        ]);
    }

    /**
     * Returns an array of messages.
     *
     * @return array<Message>
     */
    public static function getTranslationMessages()
    {
        return [
            (new Message('aw.reveal-password.btn.text'))->setDesc('Reveal'),
            (new Message('aw.reveal-password.link.hide'))->setDesc('Hide password'),
            (new Message('aw.reveal-password.link.reveal'))->setDesc('Reveal password'),
        ];
    }
}
