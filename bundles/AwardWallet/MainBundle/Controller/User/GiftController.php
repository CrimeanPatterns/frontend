<?php

namespace AwardWallet\MainBundle\Controller\User;

use AwardWallet\MainBundle\Entity\Repositories\UsrRepository;
use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\Form\Type;
use AwardWallet\MainBundle\FrameworkExtension\AwTokenStorageInterface;
use AwardWallet\MainBundle\FrameworkExtension\Mailer\Mailer;
use AwardWallet\MainBundle\FrameworkExtension\Mailer\Template\User\GiftingAwPlus;
use AwardWallet\MainBundle\Globals\Cart\CartUserInfo;
use AwardWallet\MainBundle\Globals\Cart\Manager;
use JMS\TranslationBundle\Model\Message;
use JMS\TranslationBundle\Translation\TranslationContainerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class GiftController extends AbstractController implements TranslationContainerInterface
{
    private AwTokenStorageInterface $tokenStorage;

    public function __construct(AwTokenStorageInterface $tokenStorage)
    {
        $this->tokenStorage = $tokenStorage;
    }

    /**
     * @Route("/user/gift-awplus-friend", name="aw_user_giftawplus", options={"expose"=true})
     * @Security("is_granted('ROLE_USER')")
     * @Template("@AwardWalletMain/User/Gift/giftAwplusFriend.html.twig")
     */
    public function giftAwplusFriend(
        Request $request,
        Manager $cartManager,
        RouterInterface $router,
        TranslatorInterface $translator,
        UsrRepository $usrRepository
    ) {
        $form = $this->createForm(Type\AwPlusGiftType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $giftData = $form->getData();
            /** @var Usr $recipientUser */
            $recipientUser = $usrRepository->findOneBy(['email' => $giftData['email']]);

            $giftDescription = $translator->trans('gift-from-username.message', [
                '%giverName%' => $this->tokenStorage->getBusinessUser()->getFullName(),
                '%customMessage%' => empty($giftData['message']) ? '' : ' (' . htmlspecialchars($giftData['message'], ENT_QUOTES) . ')',
            ]);
            $cart = $cartManager->createNewCart(new CartUserInfo($recipientUser->getId(), $this->tokenStorage->getBusinessUser()->getId(), false));
            $cartManager->giveGiftAwplus($cart, $this->tokenStorage->getBusinessUser(), $recipientUser, $giftData['payType'], $giftDescription);

            return new RedirectResponse($router->generate('aw_cart_common_paymenttype'));
        }

        return ['form' => $form->createView()];
    }

    /**
     * @Route("/user/gift-awplus-friend/preview", name="aw_user_giftawplus_preview")
     * @Security("is_granted('ROLE_USER')")
     */
    public function giftEmailPreview(Request $request, Mailer $mailer): Response
    {
        $user = $this->tokenStorage->getBusinessUser();
        $template = new GiftingAwPlus($user);
        $template->previewMode = true;
        $template->givingName = $user->getFullName();
        $message = $mailer->getMessageByTemplate($template);
        $html = $message->getBody();

        $regex = '/<a (.*)<\/a>/isU';
        preg_match_all($regex, $html, $result);

        foreach ($result[0] as $res) {
            $regex = '/<a (.*)>(.*)<\/a>/isU';
            $text = preg_replace($regex, '$2', $res);
            $html = str_replace($res, $text, $html);
        }

        return new Response($html);
    }

    /**
     * @return array<Message>
     */
    public static function getTranslationMessages(): array
    {
        return [
            (new Message('gift-payment-complete'))->setDesc('Thank you! AwardWallet Plus was sent as a gift to %email%'),
            (new Message('your-friend-email-addr'))->setDesc("Your Friend's Email Address"),
            (new Message('i-want-to-gift'))->setDesc('I want to gift'),
            (new Message('personal-message'))->setDesc('Personal Message'),
            (new Message('awplus-subscription-recurring-yearly-pay'))->setDesc('AwardWallet Plus subscription (a recurring yearly payment)'),
            (new Message('user-already-awplus'))->setDesc('User already has AwardWallet Plus'),
            (new Message('gift-awplus-1year'))->setDesc('A gift upgrade to AwardWallet Plus for 1 year to %email%'),
            (new Message('gift-awplus-yearly-subscription'))->setDesc('A gift of AwardWallet Plus yearly subscription to %email%'),
        ];
    }
}
