<?php

namespace AwardWallet\MainBundle\Controller;

use AwardWallet\MainBundle\Entity\Repositories\BonusConversionRepository;
use AwardWallet\MainBundle\Entity\Repositories\InvitesRepository;
use AwardWallet\MainBundle\FrameworkExtension\AwTokenStorageInterface;
use AwardWallet\MainBundle\Globals\AwReferralIncomeManager;
use AwardWallet\MainBundle\Service\InviteUser\InviteUserHelper;
use JMS\TranslationBundle\Model\Message;
use JMS\TranslationBundle\Translation\TranslationContainerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @Route("/")
 */
class InviteController extends AbstractController implements TranslationContainerInterface
{
    private AwTokenStorageInterface $tokenStorage;

    public function __construct(AwTokenStorageInterface $tokenStorage)
    {
        $this->tokenStorage = $tokenStorage;
    }

    /**
     * @Security("is_granted('ROLE_USER')")
     * @Route("/invites", name="aw_user_invites", options={"expose"=true})
     * @Template("@AwardWalletMain/Invite/manageInvites.html.twig")
     */
    public function manageInvitesAction(
        Request $request,
        AwReferralIncomeManager $referralIncomeManager,
        InvitesRepository $invitesRepository,
        BonusConversionRepository $bonusConversionRepository
    ) {
        $user = $this->tokenStorage->getBusinessUser();
        $formatter = new \NumberFormatter($request->getLocale(), \NumberFormatter::GROUPING_SEPARATOR_SYMBOL);

        $invites = $invitesRepository->getUserInvitesData($user);
        $redeemed = $bonusConversionRepository->getRedeemedBonusByUser($user->getUserid());

        foreach ($invites as $key => $invite) {
            if ($invite['InviteeID'] != null) {
                $invites[$key]['Bonus'] = $formatter->format($bonus = $referralIncomeManager->getTotalBonusByUser($invite['InviteeID']));
                $invites[$key]['Email'] = preg_replace('/(.{2}).+(@)/', '$1...$2', $invites[$key]['Email']);
            }
        }

        return [
            'invites' => $invites,
            'total_bonus' => $referralIncomeManager->getTotalReferralBonusEligibleIncomePointsByUser($user->getUserid()),
            'redeemed' => $redeemed,
        ];
    }

    /**
     * @Security("is_granted('ROLE_USER') and is_granted('NOT_USER_IMPERSONATED') and is_granted('CSRF')")
     * @Route("/invites/delete",
     *      name="aw_invites_delete",
     *      methods={"POST"},
     *      options={"expose"=true}
     * )
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function deleteConnectionAction(Request $request)
    {
        $userID = $this->tokenStorage->getBusinessUser()->getUserid();

        $invites = $request->request->get('invites');

        if (!isset($invites) || !is_array($invites)) {
            throw $this->createNotFoundException();
        }

        foreach ($invites as $invite) {
            if (!empty($invite) && is_scalar($invite)) {
                $this->getDoctrine()->getConnection()->executeQuery("delete from Invites where InvitesID = " . intval($invite) . " and InviterID = {$userID}");
            }
        }

        $response = new JsonResponse([
            'invites' => $invites,
        ]);

        return $response;
    }

    /**
     * Отправляет приглашение на регистрацию.
     *
     * @Route("/invites/send", name="aw_invites_send", methods={"POST"}, options={"expose"=true})
     * @Security("is_granted('ROLE_USER') and is_granted('CSRF')")
     */
    public function sendAction(
        Request $request,
        InviteUserHelper $inviteHelper,
        TranslatorInterface $translator,
        RouterInterface $router
    ): JsonResponse {
        $result = $inviteHelper->send($request->request->get('inviteEmail'));

        switch ($result) {
            case InviteUserHelper::STATUS_EMAIL_NOT_VERIFIED:
                $message = $translator->trans('email.not_verified', [
                    '%link_on%' => '<a target="_blank" href="' . $router->generate('aw_profile_overview') . '">',
                    '%link_off%' => '</a>',
                ], 'validators');

                return new JsonResponse(['error' => $message]);

            case InviteUserHelper::STATUS_SENT:
                return new JsonResponse(['success' => true]);

            case InviteUserHelper::STATUS_NOT_SENT:
            default:
                $message = $translator->trans(/** @Desc("We could not send the invitation email; please try again later.") */ 'invitation.not_sent', [], 'validators');

                return new JsonResponse(['error' => $message]);
        }
    }

    /**
     * Returns an array of messages.
     *
     * @return array<Message>
     */
    public static function getTranslationMessages()
    {
        return [
            (new Message("invation.sent.to"))->setDesc("Invitation was sent to"),
            (new Message("thank.you"))->setDesc("Thank You"),
        ];
    }
}
