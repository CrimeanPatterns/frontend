<?php

namespace AwardWallet\MainBundle\Controller\Manager;

use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\Security\TwoFactorAuthentication\QuestionGenerator;
use AwardWallet\MainBundle\Security\TwoFactorAuthentication\TwoFactorAuthenticationService;
use Doctrine\DBAL\Driver\Connection as ConnectionInterface;
use Doctrine\ORM\EntityManagerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class QuestionsController extends AbstractController
{
    /**
     * @Security("is_granted('ROLE_MANAGE_QUESTIONS')")
     * @Route("/manager/questions/{userId}", name="aw_manager_questions", requirements={"userId": "\d+"})
     */
    public function showQuestionsAction(
        Request $request,
        $userId = null,
        EntityManagerInterface $entityManager,
        ConnectionInterface $connection,
        QuestionGenerator $questionGenerator
    ) {
        $builder = $this->createFormBuilder();
        $builder->add('userId', TextType::class, [
            'label' => "User ID",
            'required' => false,
        ]);
        $builder->add('lookup', SubmitType::class, ['label' => 'Show']);
        $data = [
            'userId' => $userId,
        ];
        $builder->setData($data);
        $form = $builder->getForm();
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();
            $userId = intval($data['userId']);
        }

        $questions = $connection->executeQuery("
        select 
            a.Question, a.Answer, a.CreateDate, acc.Login, 
           case when acc.ErrorCode in (" . ACCOUNT_CHECKED . ", " . ACCOUNT_WARNING . ") then 'Yes' else 'No' end as CheckedAccount
        from 
            Answer a
            join Account acc on a.AccountID = acc.AccountID
        where 
            acc.UserID = :userId and a.Valid = 1
        order by CreateDate desc", ["userId" => $userId])->fetchAll(\PDO::FETCH_ASSOC);

        /** @var Usr $user */
        if (!empty($userId)) {
            $user = $entityManager->getRepository(Usr::class)->find($userId);
        } else {
            $user = null;
        }

        $possibleQuestions = [];

        if ($user !== null) {
            $possibleQuestions = array_map(function (array $option) {
                return $option['question'];
            }, $questionGenerator->getQuestions($user));
        }

        return $this->render("@AwardWalletMain/Manager/Questions/view.html.twig", [
            "userId" => $userId,
            "questions" => $questions,
            "form" => $form->createView(),
            "willAskQuestions" => !empty($user) ? $user->isPasswordChangedByResetLinkAfterLastLogon() : null,
            "possibleQuestions" => $possibleQuestions,
        ]);
    }

    /**
     * @Security("is_granted('ROLE_MANAGE_QUESTIONS') and is_granted('CSRF')")
     * @Route("/manager/questions/dont-ask/{userid}", name="aw_manager_questions_dont_ask", methods={"POST"}, requirements={"userid": "\d+"})
     * @ParamConverter("user", class="AwardWalletMainBundle:Usr")
     */
    public function dontAskAction(Usr $user, TwoFactorAuthenticationService $twoFactorAuthentication)
    {
        $twoFactorAuthentication->dontAskQuestions($user);

        return new JsonResponse("ok");
    }
}
