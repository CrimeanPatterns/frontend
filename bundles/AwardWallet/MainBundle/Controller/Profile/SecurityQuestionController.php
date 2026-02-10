<?php

namespace AwardWallet\MainBundle\Controller\Profile;

use AwardWallet\MainBundle\Form\Model\Profile\SecurityQuestionModel;
use AwardWallet\MainBundle\Form\Type\ProfileSecurityQuestionType;
use AwardWallet\MainBundle\Service\User\SecurityQuestionHelper;
use AwardWallet\WidgetBundle\Widget\UserProfileWidget;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

class SecurityQuestionController extends AbstractController
{
    /**
     * @Route("/user/question", name="aw_profile_question")
     * @Security("is_granted('ROLE_USER')")
     */
    public function indexAction(
        Request $request,
        UserProfileWidget $userProfileWidget,
        SecurityQuestionHelper $helper,
        TranslatorInterface $translator
    ) {
        $userProfileWidget->setActiveItem('personal');

        $form = $this->createForm(ProfileSecurityQuestionType::class, $helper->findModel());
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var SecurityQuestionModel $model */
            $model = $form->getData();
            $result = $helper->process($model);

            switch ($result) {
                case SecurityQuestionHelper::STATUS_UPDATED:
                    $message = $translator->trans(/** @Desc("You have successfully updated your security questions") */ 'notice.security-question.updated');

                    break;

                case SecurityQuestionHelper::STATUS_DELETED:
                    $message = $translator->trans(/** @Desc("You have successfully deleted all of your security questions") */ 'notice.security-question.deleted');

                    break;
            }

            if (isset($message)) {
                $request->getSession()->getFlashBag()->add('notice', $message);
            }
        }

        return $this->render('@AwardWalletMain/Profile/SecurityQuestions/index.html.twig', [
            'form' => $form->createView(),
        ]);
    }
}
